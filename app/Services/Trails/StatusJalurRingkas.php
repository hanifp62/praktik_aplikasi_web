<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Status akses satu baris jalur untuk daftar (Jelajah, halaman gunung, banding), beserta
 * dari mana status itu datang: jenis sumbernya, nama sumbernya, dan waktunya (spec v3
 * bagian 7; PRD §60, §92).
 *
 * Dua kueri untuk sekumpulan jalur, bukan dua per jalur: satu untuk catatan status jalur,
 * satu untuk catatan status gunungnya, keduanya beserta jenis sumber datanya. Kaskade §42
 * dipilih lewat `OfficialStatusService::catatanPenentu()`, fungsi yang sama dipakai
 * `snapshotForTrail()` untuk rincian satu jalur, sehingga daftar dan rincian selalu
 * mengutip catatan yang sama untuk jalur yang sama (perbaikan P-1, R-009).
 *
 * Jenis sumber dibaca dari data_sources.source_type, tidak diasumsikan dari nama tabelnya:
 * catatan di official_statuses yang disalin admin tanpa bukti resmi bukan "Resmi" (R-009).
 * Catatan tanpa sumber data tercatat hanya menyebut nama sumbernya, tanpa jenis.
 */
final class StatusJalurRingkas
{
    public function __construct(private readonly DataFreshnessService $kesegaran) {}

    /**
     * @param  Collection<int, Trail>  $trails  dengan relasi mountain termuat
     * @return array<int, array{status: string|null, label: string, cakupan: string|null, sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, awalanWaktu: string|null, kesegaran: string|null}>
     */
    public function untuk(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $catatanJalur = $this->terbaru((new Trail)->getMorphClass(), $trails->pluck('id'));
        $catatanGunung = $this->terbaru((new Mountain)->getMorphClass(), $trails->pluck('mountain_id')->filter()->unique()->values());

        $hasil = [];

        foreach ($trails as $trail) {
            $jalur = $catatanJalur[$trail->id] ?? null;
            $gunung = $catatanGunung[$trail->mountain_id] ?? null;
            $penentu = OfficialStatusService::catatanPenentu($jalur, $gunung);

            $hasil[$trail->id] = $this->bentuk($penentu, Timezone::forTrail($trail));
        }

        return $hasil;
    }

    /**
     * Satu catatan (atau ketiadaannya) menjadi bentuk yang dikirim ke halaman.
     *
     * Waktu dikirim bersama awalannya (OfficialStatus::waktuDikutip()), jadi catatan yang
     * belum diverifikasi tidak tertulis "Diverifikasi" di halaman gunung dan halaman
     * penilaian (W3-2c). Tanggal pendek menulis tahunnya bila bukan tahun berjalan di zona
     * gunung (m2 tinjauan akhir W3, parkir W3-1g): halaman penilaian mengutipnya tanpa
     * lencana kesegaran, dan "3 Nov" dari tahun lalu terbaca seperti tahun ini.
     *
     * @return array{status: string|null, label: string, cakupan: string|null, sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, awalanWaktu: string|null, kesegaran: string|null}
     */
    public function bentuk(?OfficialStatus $catatan, string $zona): array
    {
        $nilai = $catatan?->status ?? OfficialStatusValue::UNKNOWN;
        $jenis = $catatan?->dataSource?->source_type;
        $dikutip = OfficialStatus::waktuDikutip($catatan?->verified_at, $catatan?->published_at, $catatan?->effective_at);
        $waktu = $dikutip === null ? null : $dikutip['waktu']->copy()->setTimezone($zona);

        return [
            'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
            'label' => $nilai->label(),
            'cakupan' => $catatan?->scope?->label(),
            'sumber' => $catatan === null ? null : [
                'jenis' => $jenis?->value,
                'label' => $jenis?->label(),
                'nama' => $catatan->source,
            ],
            'waktu' => $waktu?->translatedFormat($waktu->year === Carbon::now($zona)->year ? 'j M' : 'j M Y'),
            'awalanWaktu' => $dikutip['awalan'] ?? null,
            'kesegaran' => $catatan === null ? null : $this->kesegaran->keadaan($catatan),
        ];
    }

    /**
     * Catatan terbaru yang sedang berlaku per entitas, beserta jenis sumbernya.
     *
     * @param  Collection<int, int>  $ids
     * @return array<int, OfficialStatus>
     */
    private function terbaru(string $morph, Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $baris = OfficialStatus::query()
            ->where('statusable_type', $morph)
            ->whereIn('statusable_id', $ids)
            ->currentlyEffective()
            ->with('dataSource:id,source_type')
            ->terbaruDulu()
            ->get();

        $terbaru = [];

        foreach ($baris as $satu) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terbaru.
            $terbaru[$satu->statusable_id] ??= $satu;
        }

        return $terbaru;
    }
}
