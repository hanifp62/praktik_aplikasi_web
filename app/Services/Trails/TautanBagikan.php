<?php

namespace App\Services\Trails;

use App\Models\ComparisonShare;
use App\Models\Trail;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148).
 *
 * Token 192 bit dari random_bytes, ditulis base64url (32 karakter) dan hanya ditunjukkan
 * sekali kepada pembuatnya; yang disimpan SHA-256-nya. Tautan berlaku
 * hiking.share_links.ttl_days hari, dapat dicabut pembuatnya kapan saja, dan jumlah tautan
 * aktif per akun dibatasi supaya pembuatan tanpa ujung tidak menumpuk baris. Isinya dua
 * sampai hiking.saved_trails.compare_max jalur TERBIT (R-022); yang tidak terbit ditolak
 * pembuat permintaan, bukan dibuang diam-diam.
 */
final class TautanBagikan
{
    /**
     * @param  Collection<int, Trail>  $trails  jalur terbit dalam urutan kolom
     * @return array{0: string, 1: ComparisonShare}|null token dan barisnya, atau null bila batas tautan aktif tercapai
     */
    public function buat(User $user, Collection $trails): ?array
    {
        return DB::transaction(function () use ($user, $trails) {
            // Kunci per pengguna, sama dengan simpanan: dua permintaan serentak tidak dapat
            // sama-sama lolos di bawah batas.
            User::whereKey($user->id)->lockForUpdate()->first();

            // COUNT di basis data, bukan memuat setiap baris aktif hanya untuk dihitung (T8-M14).
            if (ComparisonShare::query()->whereBelongsTo($user)->berlaku()->count() >= (int) config('hiking.share_links.max_active_per_user')) {
                return null;
            }

            $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
            $share = new ComparisonShare;
            $share->forceFill([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'trail_ids' => $trails->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'expires_at' => now()->addDays($this->hariBerlaku()),
            ])->save();

            return [$token, $share];
        });
    }

    /**
     * Tautan milik $user yang masih berlaku, terbaru dulu.
     *
     * @return Collection<int, ComparisonShare>
     */
    public function aktif(User $user): Collection
    {
        return ComparisonShare::query()->whereBelongsTo($user)->berlaku()->latest()->latest('id')->get();
    }

    /**
     * Tautan aktif $user dalam bentuk yang dikirim ke Simpanan dan Banding, supaya pembuatnya
     * dapat mencabut kapan saja (FR-23). Tokennya tidak tersimpan, jadi yang ditunjukkan isinya
     * dan umurnya. Satu pembentuk untuk kedua halaman (T9-M9 tinjauan akhir W3). Waktu dibuat
     * memuat jam dan zonanya, karena dua tautan dari hari yang sama tidak boleh berbagi nama
     * tombol Cabut yang sama bagi pembaca layar.
     *
     * @return array<int, array{id: int, jumlahJalur: int, dibuat: string, berlakuSampai: string}>
     */
    public function ringkasAktif(User $user): array
    {
        return $this->aktif($user)->map(fn (ComparisonShare $s) => [
            'id' => $s->id,
            'jumlahJalur' => count($s->trail_ids),
            'dibuat' => Timezone::display($s->created_at, Timezone::DEFAULT, 'j M H.i'),
            'berlakuSampai' => $s->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
        ])->values()->all();
    }

    /**
     * Masa berlaku tautan baru dalam hari, dari config, untuk kalimat di lembar bagikan dan
     * halaman tautan yang tidak berlaku (T8-M7 tinjauan akhir W3).
     */
    public function hariBerlaku(): int
    {
        return (int) config('hiking.share_links.ttl_days');
    }

    /**
     * Tautan yang dibuka dari URL: null bila token salah bentuk, tidak dikenal, dicabut, atau
     * kedaluwarsa. Keempatnya dijawab sama, supaya jawaban tidak membocorkan tautan mana
     * yang pernah ada.
     */
    public function buka(string $token): ?ComparisonShare
    {
        if (preg_match('/^[A-Za-z0-9_-]{32}$/', $token) !== 1) {
            return null;
        }

        return ComparisonShare::query()->berlaku()->where('token_hash', hash('sha256', $token))->first();
    }

    public function cabut(ComparisonShare $share): void
    {
        $share->forceFill(['revoked_at' => now()])->save();
    }
}
