<?php

namespace Tests\Concerns;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Penyiapan bersama test meja kurator W8: satu gunung di bawah satu balai, kurator anggota balai
 * itu, dan isian catatan status yang lengkap. Pengguna dan badan selalu dibuat lewat pabrik,
 * bukan diambil dari isian (ruling W8-R7).
 */
trait MejaKurator
{
    protected Mountain $gunung;

    protected Authority $balai;

    protected function siapkanKawasan(): void
    {
        $this->gunung = Mountain::factory()->create(['name' => 'Gunung Gede', 'timezone' => 'Asia/Jakarta']);
        $this->balai = Authority::factory()->create(['name' => 'Balai Besar TN Gede Pangrango', 'abbreviation' => 'BBTNGGP']);
        $this->balai->mountains()->attach($this->gunung);
    }

    protected function kurator(?Authority $badan = null): User
    {
        $user = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($user)->for($badan ?? $this->balai)->create();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    protected function isianStatus(OfficialStatusValue $nilai, array $ganti = []): array
    {
        return [
            'status' => $nilai->value,
            'document_number' => 'SE.4/KSDAE/PJLHK/2026',
            'document_date' => now()->subDays(2)->toDateString(),
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'source_url' => 'https://gedepangrango.org/pengumuman',
            'captured_at' => now()->subHour(),
            'effective_at' => now()->subHour(),
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'expires_at' => null,
            'reason_category' => StatusReasonCategory::FIRE->value,
            'reason' => 'Kebakaran hutan di sekitar jalur.',
            'limit_description' => null,
            'legal_basis' => null,
            'official_excerpt' => 'Pendakian ditutup sampai pemberitahuan lebih lanjut.',
            'plain_summary' => 'Pendakian ditutup karena kebakaran hutan.',
            ...$ganti,
        ];
    }

    protected function suratPdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-balai.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n");
    }

    /**
     * Draf berbukti PDF di kawasan uji, dicatat kurator yang diberikan.
     *
     * @param  array<string, mixed>  $ganti
     */
    protected function draf(User $kurator, OfficialStatusValue $nilai, array $ganti = [], ?Model $sasaran = null): OfficialStatus
    {
        return app(TerbitStatus::class)->buatDraf($kurator, $sasaran ?? $this->gunung, $this->balai, $this->isianStatus($nilai, $ganti), $this->suratPdf());
    }

    /**
     * Catatan lama dari sebelum meja kurator (keadaan verifikasi kosong).
     *
     * @param  array<string, mixed>  $atribut
     */
    protected function catatanLama(OfficialStatusValue $nilai, array $atribut = [], ?Model $sasaran = null): OfficialStatus
    {
        $sasaran ??= $this->gunung;

        return OfficialStatus::factory()->create([
            'statusable_type' => $sasaran->getMorphClass(),
            'statusable_id' => $sasaran->getKey(),
            'scope' => TerbitStatus::cakupan($sasaran),
            'status' => $nilai->value,
            'effective_at' => now()->subDays(10),
            ...$atribut,
        ]);
    }
}
