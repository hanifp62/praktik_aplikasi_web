<?php

namespace App\Livewire\Admin;

use App\Enums\VerificationStatus;
use App\Models\ExpertCredential;
use App\Services\AuditLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Memverifikasi sertifikat kompetensi yang diklaim pemandu (PRD §43, §61).
 *
 * Yang diverifikasi di sini bukan kompetensinya, melainkan keberadaan sertifikatnya.
 * BNSP dan LSP yang menguji kompetensi; tugas kami memastikan nomor sertifikat yang
 * diklaim seseorang benar ada dan masih berlaku.
 *
 * Verifikasi adalah keputusan yang memberi seseorang hak menyunting data yang dibaca
 * pendaki, jadi setiap perubahannya masuk jejak audit.
 */
#[Layout('layouts.app')]
#[Title('Verifikasi Kredensial Ahli')]
class CredentialManager extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = VerificationStatus::UNVERIFIED->value;

    /** @var array<int, string> */
    public array $catatan = [];

    public function verifikasi(int $id, AuditLogService $audit): void
    {
        $this->ubahStatus($id, VerificationStatus::VERIFIED, $audit);
    }

    public function sengketakan(int $id, AuditLogService $audit): void
    {
        $this->ubahStatus($id, VerificationStatus::DISPUTED, $audit);
    }

    /**
     * Mencabut verifikasi mengembalikannya ke belum diverifikasi, bukan menghapusnya.
     * Riwayat klaimnya tetap perlu terbaca.
     */
    public function cabut(int $id, AuditLogService $audit): void
    {
        $this->ubahStatus($id, VerificationStatus::UNVERIFIED, $audit);
    }

    private function ubahStatus(int $id, VerificationStatus $status, AuditLogService $audit): void
    {
        $this->authorize('verifyCredentials', ExpertCredential::class);

        $kredensial = ExpertCredential::findOrFail($id);
        $sebelum = ['verification_status' => $kredensial->verification_status->value];

        $kredensial->update([
            'verification_status' => $status->value,
            'verified_by' => auth()->id(),
            'verified_at' => $status === VerificationStatus::VERIFIED ? now() : null,
            'notes' => $this->catatan[$id] ?? $kredensial->notes,
        ]);

        $audit->record(auth()->user(), 'credential.'.strtolower($status->value), $kredensial, $sebelum, [
            'verification_status' => $status->value,
        ]);

        unset($this->catatan[$id]);

        session()->flash('status', match ($status) {
            VerificationStatus::VERIFIED => 'Kredensial diverifikasi. Pemegangnya kini dapat menyunting data jalur di kawasan yang tercantum.',
            VerificationStatus::DISPUTED => 'Kredensial ditandai bersengketa. Haknya dicabut sampai persoalannya selesai.',
            VerificationStatus::UNVERIFIED => 'Verifikasi dicabut. Haknya berhenti berlaku.',
        });
    }

    public function render()
    {
        $this->authorize('verifyCredentials', ExpertCredential::class);

        return view('livewire.admin.credential-manager', [
            'kredensial' => ExpertCredential::query()
                ->when($this->filter, fn ($q, $f) => $q->where('verification_status', $f))
                ->with(['user:id,name,email', 'issuingAuthority', 'endorsingAuthority', 'mountains:id,name'])
                ->orderByDesc('created_at')
                ->paginate(10),
            'statuses' => VerificationStatus::cases(),
        ]);
    }
}
