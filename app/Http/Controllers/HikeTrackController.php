<?php

namespace App\Http\Controllers;

use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Services\Trips\PenerimaanJejak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Jejak pendakian yang direkam di perangkat: pertanyaan apakah sesi boleh menerimanya,
 * unggahannya, dan penghapusannya atas permintaan pemiliknya.
 *
 * Titik direkam di perangkat selama pendakian dan dikirim sesudah pendakian selesai atau
 * dibatalkan, saat ada sinyal (ruling W6-P2). Pengirimnya (resources/js/luring/kirimJejak.ts)
 * berjalan di halaman aplikasi biasa, bukan di mode lapangan, dan bertanya lebih dulu lewat
 * status(); pertanyaan itu tidak membawa lokasi. status() dan store() memakai satu predikat,
 * PenerimaanJejak::alasanTolak, jadi klien lain (bundel lama, tab basi) yang mengirim selama
 * pendakian ditolak 409 dan titiknya tetap di perangkat sampai pendakian berakhir.
 *
 * Ketiga aksi memeriksa trip sesi lebih dulu (ruling W6-P2r): sesi yang tripnya sudah terhapus
 * (hanya trip batal yang dapat dihapus, dan sesinya ikut terhapus lewat kaskade) selalu 404,
 * kapan pun penghapusan itu terjadi, bukan 403 dari Policy yang menerima trip null. Sesi milik
 * orang lain tetap 403 sebelum validasi.
 */
class HikeTrackController extends Controller
{
    /**
     * Batas satu unggahan. Perekam menyimpan paling banyak satu titik per lima detik (ruling
     * W6-P2d), jadi pendakian dua belas jam sekitar 8.640 titik dan dikirim berkelompok; pengirim
     * memakai kelompok 500 titik. Batas ini yang menahan satu permintaan menulis berkas raksasa ke
     * basis data.
     */
    private const MAKS_TITIK = 5000;

    /**
     * Pertanyaan pengirim sebelum satu titik pun dikirim (ruling W6-P2b). Jawabannya hanya
     * keputusan dan alasannya, tanpa data lokasi; 403 untuk sesi orang lain dan 404 untuk sesi yang
     * tidak ada atau yang tripnya sudah terhapus, sebelum predikat dibaca.
     */
    public function status(Request $request, HikingSession $session, PenerimaanJejak $penerimaan): JsonResponse
    {
        $trip = $session->tripPlan;
        abort_if($trip === null, 404, 'Sesi pendakian ini sudah tidak ada.');
        $this->authorize('update', $trip);

        $alasan = $penerimaan->alasanTolak($session, $request->user());

        return response()->json(['kirim' => $alasan === null, 'alasan' => $alasan]);
    }

    public function store(Request $request, HikingSession $session, PenerimaanJejak $penerimaan): JsonResponse
    {
        $trip = $session->tripPlan;
        abort_if($trip === null, 404, 'Sesi pendakian ini sudah tidak ada.');
        $this->authorize('update', $trip);

        $data = $request->validate([
            'points' => ['required', 'array', 'min:1', 'max:'.self::MAKS_TITIK],
            'points.*.recorded_at' => ['required', 'date'],
            'points.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'points.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'points.*.elevation_m' => ['nullable', 'integer', 'between:'.PenerimaanJejak::TINGGI_MIN_M.','.PenerimaanJejak::TINGGI_MAKS_M],
            'points.*.accuracy_m' => ['nullable', 'integer', 'between:0,'.PenerimaanJejak::AKURASI_MAKS_M],
        ]);

        $titik = PenerimaanJejak::titikPerDetik($data['points']);

        return DB::transaction(function () use ($request, $session, $penerimaan, $titik): JsonResponse {
            // Baris sesi dikunci dan dibaca ulang (butir 4): sesi yang terhapus sesudah otorisasi
            // (trip batal dihapus bersamaan, kaskade) menjadi 404, bukan 500 dari kunci asing, dan
            // dua unggahan sesi yang sama berurutan.
            $sesi = HikingSession::query()->whereKey($session->id)->lockForUpdate()->first();

            abort_if($sesi === null, 404, 'Sesi pendakian ini sudah tidak ada.');

            // Perekaman harus dinyalakan pemiliknya, dan pendakiannya sudah berakhir tanpa
            // pendakian lain yang berlangsung (ruling W6-P2b), menurut nilai baris saat ini.
            $alasan = $penerimaan->alasanTolak($sesi, $request->user());

            abort_if($alasan === PenerimaanJejak::REKAM_MATI, 403, 'Perekaman jejak sedang mati, jadi jejak tidak diterima.');
            abort_if($alasan === PenerimaanJejak::BELUM_BERAKHIR, 409, 'Pendakian ini masih berlangsung. Jejaknya diterima sesudah pendakian selesai atau dibatalkan.');
            abort_if($alasan === PenerimaanJejak::PENDAKIAN_LAIN, 409, 'Pendakian lain masih berlangsung. Jejak diterima sesudah pendakian itu selesai atau dibatalkan.');

            // Titik yang direkam sesudah pendakian diselesaikan, lewat kelonggaran jam perangkat,
            // tidak disimpan (ruling W6-FH), menurut ended_at baris yang baru dikunci. Jawabannya
            // tetap 2xx, jadi perangkat membuang kelompok itu seperti biasa.
            $titik = PenerimaanJejak::dalamPendakian($titik, $sesi->ended_at);
            $sekarang = now();

            // Waktu perangkat dalam UTC, bukan waktu tiba. Sinyal dapat putus di tengah unggahan
            // dan pengirim mengulang kelompok yang sama; upsert pada kunci (sesi, detik) membuat
            // kiriman ulang tidak menggandakan jejaknya.
            DB::table('hike_track_points')->upsert(
                array_map(fn (array $satu) => [
                    'hiking_session_id' => $sesi->id,
                    ...$satu,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ], $titik),
                ['hiking_session_id', 'recorded_at'],
                ['latitude', 'longitude', 'elevation_m', 'accuracy_m', 'updated_at']
            );

            return response()->json([
                'tersimpan' => HikeTrackPoint::where('hiking_session_id', $sesi->id)->count(),
            ]);
        });
    }

    /**
     * Hak menghapus adalah bagian dari fiturnya, bukan tambahan. Jejaknya milik
     * pendakinya, dan ia harus dapat mencabutnya kembali tanpa menghapus pendakiannya.
     */
    public function destroy(HikingSession $session): RedirectResponse
    {
        $trip = $session->tripPlan;
        abort_if($trip === null, 404, 'Sesi pendakian ini sudah tidak ada.');
        $this->authorize('update', $trip);

        HikeTrackPoint::where('hiking_session_id', $session->id)->delete();

        return back()->with('status', 'Jejak pendakian dihapus.');
    }
}
