<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Enums\SourceType;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\Profil\FotoProfil;
use Illuminate\Support\Facades\DB;

/**
 * Orkestrasi penghapusan akun sesuai `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2 sampai 6:
 * menghapus laporan kondisi yang belum disetujui beserta fotonya, menganonimkan nama dan nomor
 * sertifikat pada sumber data kontributor ahli tanpa merusak jalur terbit, membersihkan sesi
 * lintas perangkat dan token atur ulang kata sandi, mempertahankan jejak moderasi tanpa nama,
 * serta menulis catatan audit tata kelola tanpa PII.
 */
class AccountDeletionService
{
    public function hapus(User $user): void
    {
        $berkasFoto = $user->profile?->avatar_path;

        DB::transaction(function () use ($user): void {
            $jumlahSumber = $this->anonimkanSumberKontributor($user);

            $jumlahDisetujui = TrailConditionReport::query()
                ->where('user_id', $user->id)
                ->where('moderation_status', ModerationStatus::APPROVED->value)
                ->count();

            $belumTerbit = TrailConditionReport::query()
                ->where('user_id', $user->id)
                ->where('moderation_status', '!=', ModerationStatus::APPROVED->value)
                ->get();

            $jumlahBelumTerbit = $belumTerbit->count();
            $belumTerbit->each->delete();

            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->delete();

            AuditLog::create([
                'actor_id' => null,
                'action' => 'account.deleted',
                'entity_type' => User::class,
                'entity_id' => null,
                'before' => null,
                'after' => [
                    'laporan_dipertahankan_anonim' => $jumlahDisetujui,
                    'laporan_belum_terbit_dihapus' => $jumlahBelumTerbit,
                    'sumber_ahli_dianonimkan' => $jumlahSumber,
                ],
                'ip_address' => null,
            ]);
        });

        // Berkas foto profil tidak ikut cascade basis data; dihapus sesudah barisnya hilang.
        app(FotoProfil::class)->hapusBerkas($berkasFoto);
    }

    private function anonimkanSumberKontributor(User $user): int
    {
        $nomorSertifikat = $user->expertCredentials()
            ->pluck('certificate_number')
            ->filter(fn (?string $nomor): bool => filled($nomor))
            ->values()
            ->all();

        $idSumberJalur = Trail::query()
            ->where('contributed_by', $user->id)
            ->whereNotNull('data_source_id')
            ->pluck('data_source_id')
            ->all();

        $namaSumberPribadi = 'Kontribusi ahli: '.$user->name;

        $daftarSumber = DataSource::query()
            ->where('source_type', SourceType::ACCREDITED_EXPERT->value)
            ->where(function ($kueri) use ($namaSumberPribadi, $idSumberJalur): void {
                $kueri->where('source_name', $namaSumberPribadi);

                if ($idSumberJalur !== []) {
                    $kueri->orWhereIn('id', $idSumberJalur);
                }
            })
            ->get();

        $diubah = 0;

        foreach ($daftarSumber as $sumber) {
            $catatan = (string) ($sumber->notes ?? '');
            $milikPengguna = $sumber->source_name === $namaSumberPribadi
                || ($user->name !== '' && str_contains($catatan, $user->name))
                || ($user->email !== '' && str_contains($catatan, $user->email))
                || collect($nomorSertifikat)->contains(fn (string $nomor): bool => str_contains($catatan, $nomor));

            if (! $milikPengguna) {
                continue;
            }

            foreach ($nomorSertifikat as $nomor) {
                $catatan = str_replace(', sertifikat '.$nomor, '', $catatan);
                $catatan = str_replace($nomor, '[dihapus]', $catatan);
            }

            if ($user->name !== '') {
                $catatan = str_replace($user->name, 'Kontributor terverifikasi', $catatan);
            }

            if ($user->email !== '') {
                $catatan = str_replace($user->email, '[dihapus]', $catatan);
            }

            $sumber->update([
                'source_name' => 'Kontributor terverifikasi (akun dihapus)',
                'notes' => $catatan !== '' ? $catatan : null,
            ]);

            $diubah++;
        }

        return $diubah;
    }
}
