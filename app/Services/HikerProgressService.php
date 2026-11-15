<?php

namespace App\Services;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\ReportThank;
use App\Models\TrailConditionReport;
use App\Models\User;

/**
 * Apa yang sudah ditempuh seorang pendaki, dipantulkan kembali kepadanya.
 *
 * Dalam Self-Determination Theory inilah competence: melihat kemajuan diri sendiri.
 * Aplikasi ini sebelumnya hanya melayani autonomy, yaitu pendaki menentukan tujuannya
 * sendiri, sehingga tidak ada satu pun alasan membukanya di antara dua pendakian.
 *
 * Tidak ada perbandingan dengan pendaki lain, dan itu keputusan rancangan, bukan
 * kekurangan. Papan peringkat kecepatan mendorong mengejar waktu di medan yang
 * mematikan. Angka di sini hanya milik pemiliknya.
 *
 * Sejak W7 ringkasan tahun, grafik, dan riwayat milik App\Services\Community\HubProgres; yang
 * tersisa di sini dampak laporan dan peta gunung yang sudah didaki, untuk semua tahun.
 */
class HikerProgressService
{
    /**
     * Dampak laporan kondisi yang ditulis pendaki ini.
     *
     * Tanpa ini, menulis laporan terasa seperti mengisi formulir lalu menekan kirim:
     * pelapor tidak pernah tahu laporannya terbit, dibaca, atau menolong siapa pun, dan
     * perilaku yang tidak pernah mendapat umpan balik berhenti dengan sendirinya.
     *
     * Hanya laporan yang lolos moderasi yang terhitung. Laporan yang masih menunggu
     * belum terlihat siapa pun, dan menghitungnya sebagai terbit membuat pelapor
     * mengira sesuatu sudah menolong orang padahal belum.
     *
     * @return array{laporan_terbit: int, terima_kasih: int}
     */
    public function dampakLaporan(User $user): array
    {
        $terbit = TrailConditionReport::query()
            ->where('user_id', $user->id)
            ->visibleToPublic();

        return [
            'laporan_terbit' => (clone $terbit)->count(),
            'terima_kasih' => ReportThank::query()
                ->whereIn('trail_condition_report_id', (clone $terbit)->select('id'))
                ->count(),
        ];
    }

    /**
     * Gunung yang sudah didaki, untuk digambar di peta, dari pendakian yang tuntas di semua
     * tahun. Hanya gunung yang berkoordinat yang digambar; yang tidak berkoordinat dihitung,
     * supaya peta dapat menyebut berapa yang belum dapat digambar alih-alih menempatkannya di
     * tengah laut.
     *
     * @return array{penanda: array<int, array{lng: float, lat: float, label: string}>, tanpaKoordinat: int}
     */
    public function petaGunung(User $user): array
    {
        $gunung = HikingHistory::query()
            ->where('user_id', $user->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->whereNotNull('trail_id')
            ->with('trail.mountain:id,name,latitude,longitude')
            ->get()
            ->map(fn (HikingHistory $h) => $h->trail?->mountain)
            ->filter()
            ->unique('id');

        $berkoordinat = $gunung->filter(fn (Mountain $g) => $g->latitude !== null && $g->longitude !== null);

        return [
            'penanda' => $berkoordinat
                ->map(fn (Mountain $g) => ['lng' => (float) $g->longitude, 'lat' => (float) $g->latitude, 'label' => $g->name])
                ->values()
                ->all(),
            'tanpaKoordinat' => $gunung->count() - $berkoordinat->count(),
        ];
    }
}
