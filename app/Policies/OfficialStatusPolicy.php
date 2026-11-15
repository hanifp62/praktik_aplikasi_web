<?php

namespace App\Policies;

use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;
use Illuminate\Database\Eloquent\Model;

/**
 * Status resmi (PRD §41; spec v3 bagian 10.7; ruling W8-R7 dan W8-R8).
 *
 * Halaman admin status resmi hanya untuk admin dan hanya membaca. Meja kurator hanya untuk
 * kurator dengan keanggotaan aktif di badan yang berwenang menyatakan status; mencatat dan
 * memeriksa hanya di kawasan badannya, atas nama badan tempat ia menjadi anggota, dan pemeriksa
 * kedua tidak pernah pencatatnya sendiri. Admin dan moderator bukan kurator.
 */
class OfficialStatusPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    /**
     * Halaman admin status resmi: riwayat dan antrean tinjau, tanpa formulir pencatatan (W8).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewDesk(User $user): bool
    {
        return $this->yurisdiksi->kuratorAktif($user);
    }

    /**
     * Mencatat status untuk sebuah gunung, jalur, atau segmen atas nama satu badan.
     */
    public function record(User $user, Model $sasaran, Authority $otoritas): bool
    {
        return $this->yurisdiksi->berwenang($user, $sasaran, $otoritas);
    }

    /**
     * Melihat catatan meja kurator: kurator yang berwenang atas kawasannya. Draf hanya untuk
     * pencatatnya sampai dikirim.
     */
    public function view(User $user, OfficialStatus $status): bool
    {
        if ($status->verification_state === StatusVerificationState::DRAFT && $status->recorded_by !== $user->id) {
            return false;
        }

        return $status->statusable !== null && $this->yurisdiksi->berwenang($user, $status->statusable);
    }

    /**
     * Arsip bukti: kurator yang berwenang atas kawasannya, dan admin untuk tata kelola.
     */
    public function viewEvidence(User $user, OfficialStatus $status): bool
    {
        if ($status->verification_state === StatusVerificationState::DRAFT && $status->recorded_by !== $user->id) {
            return false;
        }

        return $user->isAdmin() || $this->view($user, $status);
    }

    /**
     * Mengubah draf: hanya pencatatnya, selama masih draf dan masih berwenang.
     */
    public function updateDraft(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state === StatusVerificationState::DRAFT
            && $status->recorded_by === $user->id
            && $this->view($user, $status);
    }

    /**
     * Tinjau berkala: catatan meja kurator yang sedang dibaca pendaki dan belum digantikan,
     * oleh kurator mana pun yang berwenang atas kawasannya. Catatan lama dari sebelum meja kurator
     * tidak ditinjau ulang; kurator mencatat penggantinya.
     */
    public function review(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state !== null
            && $status->terlihatPendaki()
            && $status->superseded_by_id === null
            && $this->view($user, $status);
    }

    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi: kurator mana pun yang berwenang
     * atas kawasannya, termasuk pencatatnya sendiri.
     */
    public function dispute(User $user, OfficialStatus $status): bool
    {
        return in_array($status->verification_state, [StatusVerificationState::PENDING, StatusVerificationState::VERIFIED], true)
            && $this->view($user, $status);
    }

    /**
     * Verifikasi orang kedua: kurator lain yang berwenang atas kawasannya, untuk catatan yang
     * menunggu verifikasi.
     */
    public function verify(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state === StatusVerificationState::PENDING
            && $status->recorded_by !== $user->id
            && $this->view($user, $status);
    }
}
