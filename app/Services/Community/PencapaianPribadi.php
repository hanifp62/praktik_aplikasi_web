<?php

namespace App\Services\Community;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Support\Timezone;

/**
 * Pencapaian yang diturunkan dari riwayat pendaki sendiri (spec v3 bagian 6.14, prototype 13;
 * rencana induk W7 butir 7). Hanya dua jenis yang datanya memang ada: tonggak tanjakan pertama
 * dari pendakian yang tuntas, dan laporan kondisi pertama yang terbit. Tidak ada pembanding
 * dengan pendaki lain, peringkat, persentil, atau hadiah kecepatan (R-015, PRD §145); setiap
 * kueri dibatasi pada pendaki ini. "Camping pertama di atas N m" tidak dibangun karena lokasi
 * berkemah tidak tercatat (ruling W6-R13, R-007).
 */
final class PencapaianPribadi
{
    /**
     * Tonggak "tanjakan pertama di atas N m", sama dengan ringkasan pendakian
     * (HasilPendakian::TONGGAK_TANJAKAN; kesamaannya dijaga PersonalAchievementTest).
     */
    public const TONGGAK = [500, 1000, 2000, 3000];

    /**
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    public function untuk(User $pengguna): array
    {
        return [...$this->tonggakTanjakan($pengguna), ...$this->laporanPertama($pengguna)];
    }

    /**
     * Pendakian tuntas pertama, urut tanggal, yang tanjakannya melewati setiap tonggak; angka
     * pendakian memakai aturan Progres (HubProgres::angka), jadi angka yang ditulis pendaki
     * sendiri ikut dan yang tidak diketahui tidak pernah dianggap nol.
     *
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    private function tonggakTanjakan(User $pengguna): array
    {
        $tuntas = HikingHistory::query()
            ->where('user_id', $pengguna->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->with('trail:id,name,elevation_gain_m,distance_km')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get();

        $hasil = [];

        foreach (self::TONGGAK as $meter) {
            $pertama = $tuntas->first(fn (HikingHistory $h) => (HubProgres::angka($h)['naik'] ?? 0) >= $meter);

            if ($pertama === null) {
                continue;
            }

            $hasil[] = [
                'kunci' => 'tanjakan-'.$meter,
                'jenis' => 'tanjakan',
                'judul' => 'Tanjakan pertama di atas '.number_format($meter, 0, ',', '.').' m',
                'keterangan' => $pertama->namaJalur().' · '.$pertama->completed_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('M Y'),
            ];
        }

        // Tonggak tertinggi lebih dulu: yang terbaru dicapai paling dekat dengan pembacanya.
        return array_reverse($hasil);
    }

    /**
     * Laporan kondisi pertama pendaki ini yang lolos moderasi, dengan ucapan terima kasih yang
     * diterimanya: dampak pada pendaki lain, bukan skor.
     *
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    private function laporanPertama(User $pengguna): array
    {
        $laporan = TrailConditionReport::query()
            ->where('trail_condition_reports.user_id', $pengguna->id)
            ->visibleToPublic()
            ->leftJoin('trails', 'trails.id', '=', 'trail_condition_reports.trail_id')
            ->select(['trail_condition_reports.id', 'trails.name as trail_name'])
            ->withCount('thanks')
            ->orderBy('trail_condition_reports.created_at')
            ->orderBy('trail_condition_reports.id')
            ->first();

        if ($laporan === null) {
            return [];
        }

        return [[
            'kunci' => 'laporan-pertama',
            'jenis' => 'laporan',
            'judul' => 'Laporan kondisi pertama terbit',
            'keterangan' => implode(' · ', array_filter([
                $laporan->getAttribute('trail_name'),
                $laporan->thanks_count > 0 ? $laporan->thanks_count.' terima kasih' : null,
            ])),
        ]];
    }
}
