<?php

namespace App\Services;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
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
 * Seluruhnya turunan dari hiking_histories dan trails. Tidak ada tabel baru.
 */
class HikerProgressService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $riwayat = HikingHistory::query()
            ->where('user_id', $user->id)
            ->with('trail:id,mountain_id,elevation_gain_m,distance_km')
            ->get();

        /*
         * Pendakian yang dibatalkan tetap terhitung sebagai pendakian, hanya bukan
         * sebagai puncak. Membatalkan karena cuaca adalah keputusan yang benar, dan
         * menghapusnya dari hitungan berarti menghukum keputusan itu dengan angka.
         */
        $tuntas = $riwayat->filter(
            fn (HikingHistory $h) => $h->completion_state === CompletionState::COMPLETED
        );

        // Jalur tanpa elevation_gain_m tidak menaikkan akumulasi dan tidak dihitung
        // sebagai nol. Yang belum diketahui disebut, bukan dianggap nol (§95).
        $berelevasi = $tuntas->filter(fn (HikingHistory $h) => $h->trail?->elevation_gain_m !== null);

        return [
            'pendakian' => $riwayat->count(),
            'tuntas' => $tuntas->count(),
            'gunung' => $tuntas->pluck('trail.mountain_id')->filter()->unique()->count(),
            'jalur' => $tuntas->pluck('trail_id')->unique()->count(),
            'elevasi_total_m' => (int) $berelevasi->sum(fn (HikingHistory $h) => $h->trail->elevation_gain_m),
            'elevasi_belum_diketahui' => $tuntas->count() - $berelevasi->count(),
            'elevasi_tertinggi_m' => $berelevasi->max(fn (HikingHistory $h) => $h->trail->elevation_gain_m),
            'terakhir' => $riwayat->max('completed_at'),
        ] + $this->dampakLaporan($user);
    }

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
     * @return array<string, int>
     */
    private function dampakLaporan(User $user): array
    {
        $terbit = TrailConditionReport::query()
            ->where('user_id', $user->id)
            ->visibleToPublic();

        return [
            'laporan_terbit' => (clone $terbit)->count(),
            'terima_kasih' => (clone $terbit)->withCount('thanks')->get()->sum('thanks_count'),
        ];
    }

    /**
     * Gunung yang sudah didaki, untuk digambar di peta.
     *
     * Hanya gunung yang berkoordinat yang ikut. Gunung tanpa koordinat tidak
     * ditempatkan di tengah laut demi melengkapi peta.
     *
     * @return array<int, array{lng: float, lat: float, label: string}>
     */
    public function summitedMountains(User $user): array
    {
        return HikingHistory::query()
            ->where('user_id', $user->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->with('trail.mountain:id,name,latitude,longitude')
            ->get()
            ->map(fn (HikingHistory $h) => $h->trail?->mountain)
            ->filter(fn ($gunung) => $gunung?->latitude !== null && $gunung?->longitude !== null)
            ->unique('id')
            ->map(fn ($gunung) => [
                'lng' => (float) $gunung->longitude,
                'lat' => (float) $gunung->latitude,
                'label' => $gunung->name,
            ])
            ->values()
            ->all();
    }
}
