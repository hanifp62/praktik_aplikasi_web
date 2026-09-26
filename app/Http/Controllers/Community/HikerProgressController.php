<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Services\HikerProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progres pendaki, dipantulkan kembali kepadanya sendiri (R-015). Tidak ada perbandingan
 * dengan pendaki lain: angka di sini hanya milik pemiliknya.
 */
class HikerProgressController extends Controller
{
    public function __invoke(Request $request, HikerProgressService $progres): Response
    {
        $user = $request->user();
        $angka = $progres->forUser($user);
        $gunung = $progres->summitedMountains($user);
        $tanpaKoordinat = max(0, $angka['gunung'] - count($gunung));

        return Inertia::render('Community/HikerProgress', [
            'title' => 'Progres Pendakian',
            // Keadaan awal setiap pendaki. Nol di sini artinya belum mulai, bukan tertinggal.
            'kosong' => $angka['pendakian'] === 0 && $angka['laporan_terbit'] === 0,
            'gunung' => $gunung,
            'keteranganPeta' => count($gunung) === 1
                ? 'Satu gunung yang sudah Anda daki.'
                : count($gunung).' gunung yang sudah Anda daki.',
            'gunungTanpaKoordinat' => $tanpaKoordinat > 0
                ? $tanpaKoordinat.' gunung lainnya belum berkoordinat, jadi belum dapat digambar.'
                : null,
            'angka' => $angka['pendakian'] === 0 ? null : [
                'pendakian' => (string) $angka['pendakian'],
                // Pendakian yang dibatalkan disebut apa adanya, tanpa nada menghukum.
                'sampaiPuncak' => $angka['pendakian'] > $angka['tuntas'] ? $angka['tuntas'].' sampai puncak' : null,
                'gunung' => (string) $angka['gunung'],
                'jalur' => $angka['jalur'].' jalur',
                'elevasiTotal' => number_format($angka['elevasi_total_m'], 0, ',', '.').' m',
                // Totalnya tidak boleh terbaca lengkap ketika sebagian jalurnya belum punya data (§95).
                'elevasiBelumDiketahui' => $angka['elevasi_belum_diketahui'] > 0
                    ? $angka['elevasi_belum_diketahui'].' jalur belum ada datanya'
                    : null,
                'elevasiTertinggi' => $angka['elevasi_tertinggi_m'] !== null
                    ? number_format($angka['elevasi_tertinggi_m'], 0, ',', '.').' m'
                    : 'belum ada',
            ],
            'laporan' => [
                'terbit' => $angka['laporan_terbit'],
                'terimaKasih' => $angka['terima_kasih'],
            ],
            'grafik' => array_map(fn (array $p) => [
                'tanggal' => $p['tanggal']->translatedFormat('d M Y'),
                'jalur' => $p['jalur'] ?? 'Jalur',
                'm' => $p['elevasi_m'],
                'teks' => $p['elevasi_m'] !== null
                    ? number_format($p['elevasi_m'], 0, ',', '.').' m'
                    : 'belum ada datanya',
            ], $progres->tanjakanPerPendakian($user)),
        ]);
    }
}
