<?php

namespace App\Services\Dashboard;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Services\ProgressLadderService;

/**
 * Tangga tanjakan di Beranda (spec v3 bagian 6.1 "tangga bila ada riwayat", prototype 01;
 * rencana induk W5 butir 5): tanjakan jalur yang pernah dituntaskan pendaki ini, ditambah trip
 * mendatang sebagai anak tangga rencana, dari yang terendah. Diukur dari elevation gain jalur,
 * bukan ketinggian puncak (BR-02, §18), dan pembandingnya diri sendiri (R-015).
 *
 * Satu anak tangga per jalur, paling banyak ANAK_TANGGA tanjakan tertinggi. Pendakian yang
 * tidak tuntas tidak menjadi anak tangga karena tidak membuktikan tanjakannya tertuntaskan
 * (aturan yang sama dengan ProgressLadderService), dan jalur tanpa angka tanjakan tidak
 * digambar sebagai nol (R-007). Kalimat perbandingannya milik ProgressLadderService, sama
 * dengan rincian jalur.
 *
 * Setiap anak tangga membawa id jalur dan nama gunung (ruling W5-6a): trails.name tidak unik,
 * jadi dua jalur di dua gunung berbeda dapat bernama sama; id dan gunung mendisambiguasinya
 * dan menjadi kunci React yang stabil, konsisten dengan JalurUntukAnda yang juga menyertakan
 * nama gunung.
 */
final class TanggaTanjakan
{
    private const ANAK_TANGGA = 4;

    public function __construct(private readonly ProgressLadderService $tangga) {}

    /**
     * @return array{anakTangga: array<int, array{id: int, jalur: string, gunung: string, m: int, teks: string, rencana: bool}>, kalimat: string}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (! in_array($konteks->fase, [RingkasanDasbor::B1, RingkasanDasbor::B2, RingkasanDasbor::B4], true)) {
            return null;
        }

        $tuntas = HikingHistory::query()
            ->where('hiking_history.user_id', $konteks->pengguna->id)
            ->where('hiking_history.completion_state', CompletionState::COMPLETED->value)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->join('mountains', 'mountains.id', '=', 'trails.mountain_id')
            ->whereNotNull('trails.elevation_gain_m')
            ->groupBy('trails.id', 'trails.name', 'trails.elevation_gain_m', 'mountains.name')
            ->orderByDesc('trails.elevation_gain_m')
            ->orderBy('trails.id')
            ->limit(self::ANAK_TANGGA)
            ->toBase()
            ->get(['trails.id', 'trails.name', 'trails.elevation_gain_m', 'mountains.name as gunung']);

        if ($tuntas->isEmpty()) {
            return null;
        }

        $anakTangga = $tuntas->map(fn (object $t) => self::anak((int) $t->id, (string) $t->name, (string) $t->gunung, (int) $t->elevation_gain_m, false))->all();
        $kalimat = 'Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.';
        $rencana = $konteks->trip?->trail;

        if ($rencana !== null && $rencana->elevation_gain_m !== null) {
            $anakTangga[] = self::anak($rencana->id, $rencana->name, $rencana->mountain->name, $rencana->elevation_gain_m, true);
            $banding = $this->tangga->bandingkanDenganRiwayat($konteks->pengguna, $rencana);
            $kalimat = $banding === null ? $kalimat : $rencana->name.': '.lcfirst($banding).' '.$kalimat;
        }

        usort($anakTangga, fn (array $a, array $b) => $a['m'] <=> $b['m']);

        return ['anakTangga' => $anakTangga, 'kalimat' => $kalimat];
    }

    /**
     * @return array{id: int, jalur: string, gunung: string, m: int, teks: string, rencana: bool}
     */
    private static function anak(int $id, string $jalur, string $gunung, int $meter, bool $rencana): array
    {
        return ['id' => $id, 'jalur' => $jalur, 'gunung' => $gunung, 'm' => $meter, 'teks' => number_format($meter, 0, ',', '.').' m', 'rencana' => $rencana];
    }
}
