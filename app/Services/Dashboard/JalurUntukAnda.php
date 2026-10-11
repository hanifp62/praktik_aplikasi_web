<?php

namespace App\Services\Dashboard;

use App\Enums\TripStatus;
use App\Http\Resources\TrailRowResource;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\TrailFitService;
use App\Services\Trails\SkalaProfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * "Jalur lain untuk Anda" di Beranda (spec v3 bagian 6.1, prototype 01; rencana induk W5 butir
 * 4): paling banyak tiga jalur dari TrailFitService::teratas(), masing-masing dengan label
 * publik, garis profil pada skala yang sama, dan satu alasan (R-003, R-004, R-005).
 *
 * Kandidatnya jalur terbit yang datanya lengkap (R-008 dan R-022 lapis pertama; evaluate()
 * menjadi lapis kedua), tanpa jalur yang sedang direncanakan, sedang didaki, atau baru saja
 * diselesaikan pendaki ini (M2, ruling W5-5b). Dibatasi hiking.beranda.batas_kandidat supaya
 * penilaian di memori tetap terbatas; kueri tidak tumbuh bersama jumlah jalur karena status,
 * pembatasan, dan izin dimuat batch (§96).
 *
 * Fase B1, B2, dan B4 saja. Pendaki tanpa profil (B2 atau B4 sebelum profil terisi) mendapat
 * ajakan mengisi profil, karena tanpa profil tidak ada yang dapat dinilai.
 */
final class JalurUntukAnda
{
    private const JUMLAH = 3;

    public function __construct(private readonly TrailFitService $fit) {}

    /**
     * @return array{tanpaProfil: bool, jalur: array<int, array<string, mixed>>}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (! in_array($konteks->fase, [RingkasanDasbor::B1, RingkasanDasbor::B2, RingkasanDasbor::B4], true)) {
            return null;
        }

        if (! $konteks->profilLengkap) {
            return ['tanpaProfil' => true, 'jalur' => []];
        }

        $kandidat = Trail::query()
            ->published()
            ->dataComplete()
            ->withPublishabilityData()
            ->with('mountain')
            ->whereNotIn('id', TripPlan::query()
                ->whereBelongsTo($konteks->pengguna)
                // Status aktif (TripStatus::isActive()): belum dimulai atau sedang berjalan.
                // Predikat enum, bukan konstanta salinan (M1, ruling W5-5b; pola W5-P4).
                ->whereIn('status', array_values(array_map(
                    fn (TripStatus $status) => $status->value,
                    array_filter(TripStatus::cases(), fn (TripStatus $status) => $status->isActive()),
                )))
                ->select('trail_id'))
            // M2, ruling W5-5b: jalur yang baru saja diselesaikan (B4) tidak ditawarkan lagi
            // sebagai "jalur lain" -- pendaki baru saja mendakinya.
            ->when($konteks->riwayat !== null, fn (Builder $query) => $query->where('id', '!=', $konteks->riwayat->trail_id))
            ->orderBy('id')
            ->limit((int) config('hiking.beranda.batas_kandidat'))
            ->get()
            ->keyBy('id');

        $tawaran = $this->fit->teratas($konteks->pengguna, $kandidat, self::JUMLAH);

        // Berkunci id jalur (I1, ruling W5-5b): only() pada koleksi Eloquent menomori ulang
        // kuncinya menjadi 0..n, sehingga garis tertukar atau hilang untuk jalur ber-id di atas
        // jumlah tawaran. mapWithKeys membangun ulang kunci dari id jalur sungguhan, bukan dari
        // posisinya di koleksi.
        $garis = SkalaProfil::naikBersama(
            $kandidat->only(array_keys($tawaran))->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all()
        )['garis'];

        return [
            'tanpaProfil' => false,
            'jalur' => array_values(array_map(function (int $id, TrailFitSummary $fit) use ($kandidat, $garis) {
                // M6, ruling W5-5b: kartu ini hanya memakai sembilan isian ini (lihat
                // JalurUntukAnda.tsx). TrailRowResource tetap sumber format angka dan label
                // (satu sumber dengan Jelajah), tetapi bentuk lengkapnya bukan kontrak kartu ini.
                $baris = (new TrailRowResource($kandidat[$id], $fit))->resolve();

                return [
                    ...Arr::only($baris, ['id', 'name', 'url', 'mountain', 'province', 'durasi', 'jarak', 'tanjakan']),
                    'fit' => $baris['fit'] === null ? null : Arr::only($baris['fit'], ['label', 'labelText', 'alasan']),
                    'garis' => $garis[$id] ?? null,
                ];
            }, array_keys($tawaran), $tawaran)),
        ];
    }
}
