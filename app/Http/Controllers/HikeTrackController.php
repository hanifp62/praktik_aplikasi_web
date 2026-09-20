<?php

namespace App\Http\Controllers;

use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menerima jejak pendakian yang direkam di perangkat, dan menghapusnya atas permintaan
 * pemiliknya.
 *
 * Lewat controller, bukan lewat komponen Livewire hike mode, karena pengirimannya justru
 * terjadi setelah pendaki turun dan halaman itu sudah lama ditutup.
 */
class HikeTrackController extends Controller
{
    /**
     * Batas satu unggahan. Perekaman tiap lima detik selama dua belas jam menghasilkan
     * sekitar 8.600 titik, jadi pendakian panjang dikirim beberapa kali. Batas ini yang
     * menahan satu permintaan menulis berkas raksasa ke basis data.
     */
    private const MAKS_TITIK = 5000;

    public function store(Request $request, HikingSession $session): JsonResponse
    {
        $this->authorize('update', $session->tripPlan);

        // Perekaman harus dinyalakan pemiliknya lebih dulu. Tanpa penjagaan ini, klien
        // yang dimodifikasi dapat menulis riwayat lokasi seseorang yang tidak pernah
        // memilihnya.
        abort_unless((bool) $request->user()->preference?->record_track, 403);

        $data = $request->validate([
            'points' => ['required', 'array', 'min:1', 'max:'.self::MAKS_TITIK],
            'points.*.recorded_at' => ['required', 'date'],
            'points.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'points.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'points.*.elevation_m' => ['nullable', 'integer', 'between:-500,9000'],
            'points.*.accuracy_m' => ['nullable', 'integer', 'between:0,10000'],
        ]);

        $sekarang = now();

        $baris = collect($data['points'])->map(fn (array $titik) => [
            'hiking_session_id' => $session->id,
            // Waktu perangkat, bukan waktu tiba.
            'recorded_at' => Carbon::parse($titik['recorded_at']),
            'latitude' => $titik['latitude'],
            'longitude' => $titik['longitude'],
            'elevation_m' => $titik['elevation_m'] ?? null,
            'accuracy_m' => $titik['accuracy_m'] ?? null,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ])->all();

        // Sinyal dapat putus di tengah unggahan, dan pendaki akan mengirim ulang
        // potongan yang sama. upsert pada kunci unik membuat pengiriman ulang tidak
        // menggandakan jejaknya.
        DB::table('hike_track_points')->upsert(
            $baris,
            ['hiking_session_id', 'recorded_at'],
            ['latitude', 'longitude', 'elevation_m', 'accuracy_m', 'updated_at']
        );

        return response()->json([
            'tersimpan' => HikeTrackPoint::where('hiking_session_id', $session->id)->count(),
        ]);
    }

    /**
     * Hak menghapus adalah bagian dari fiturnya, bukan tambahan. Jejaknya milik
     * pendakinya, dan ia harus dapat mencabutnya kembali tanpa menghapus pendakiannya.
     */
    public function destroy(HikingSession $session): RedirectResponse
    {
        $this->authorize('update', $session->tripPlan);

        HikeTrackPoint::where('hiking_session_id', $session->id)->delete();

        return back()->with('status', 'Jejak pendakian dihapus.');
    }
}
