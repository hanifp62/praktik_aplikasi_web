<?php

namespace App\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\HtmlString;

/**
 * Tag aset untuk muat penuh halaman Inertia (WP-2, audit performa 8 Oktober 2026).
 *
 * Tanpa entri halaman, peramban baru mengetahui potongan JS halaman sesudah app.js selesai
 * diunduh dan dijalankan, karena halaman dimuat malas oleh plugin Inertia. Dengan modul
 * halaman ikut di daftar entri, di mode build Vite menulis modulepreload untuk potongan
 * halaman dan setiap impor statisnya langsung di HTML, sehingga semuanya diunduh bersamaan
 * dengan app.js.
 *
 * Manifest yang lebih tua dari halamannya (halaman baru sebelum build ulang) tidak memuat
 * entri itu. Vite lalu melempar ViteException dan tag jatuh kembali ke dua entri dasar, yaitu
 * perilaku sebelum WP-2. Manifest yang tidak ada sama sekali tetap menjadi galat, karena
 * pemanggilan ulang melempar ViteManifestNotFoundException yang sama.
 *
 * Mode hot (`npm run dev`) tidak membaca manifest sama sekali, jadi tidak ada cabang
 * fallback di sana: Vite menulis satu tag skrip ke server dev untuk setiap entri, tanpa
 * modulepreload, dan entri halaman selalu ikut apa pun isinya. Komponen tanpa berkas
 * sumber tidak melempar apa pun di mode ini; tag skripnya tetap ditulis dan hanya menjadi
 * galat 404 di konsol peramban, tanpa memengaruhi aplikasi.
 *
 * Nama komponen datang dari Inertia::render di server, bukan dari klien.
 */
final class PramuatHalaman
{
    /** @var list<string> */
    private const ENTRI_DASAR = ['resources/css/app.css', 'resources/js/app.tsx'];

    public static function tag(string $komponen): HtmlString
    {
        $vite = app(Vite::class);

        try {
            return $vite([...self::ENTRI_DASAR, "resources/js/pages/{$komponen}.tsx"]);
        } catch (ViteException) {
            return $vite(self::ENTRI_DASAR);
        }
    }
}
