<?php

use Illuminate\Support\Facades\Route;

/*
 * Rute pengembangan, dimuat routes/web.php hanya ketika APP_ENV=local (ruling W1-R5).
 *
 * Pameran primitif menampilkan setiap primitif desain di satu halaman, berfungsi dan
 * dengan teks Indonesia yang panjang, supaya perubahan token atau komponen dapat dilihat
 * di 390 px dan 1440 px sebelum dipakai halaman sungguhan. Di luar local rutenya tidak
 * terdaftar sama sekali, jadi jawabannya 404, dan halamannya tidak masuk sitemap.
 *
 * Penjaga kedua (ruling W1-8a): pemuatan bersyarat di routes/web.php hanya berjalan
 * ketika berkas ini benar-benar dieksekusi ulang saat boot. Bila tabel rute sempat
 * di-cache (`php artisan route:cache`) selagi APP_ENV=local, lalu berkas cache itu
 * dipakai di lingkungan lain, pemeriksaan di web.php tidak pernah berjalan lagi karena
 * boot langsung memuat rute dari cache tanpa membaca ulang berkas ini, padahal rutenya
 * tetap terdaftar di cache tersebut. abort_unless di bawah tetap berjalan pada setiap
 * permintaan karena ia bagian dari penanganan rute (dieksekusi ulang tiap kali rutenya
 * dipanggil), bukan pendaftarannya, sehingga bertahan walau rutenya sempat ikut ter-cache
 * atau APP_ENV tidak lagi cocok saat rute itu dijalankan.
 */
Route::middleware('auth')->group(function (): void {
    Route::get('dev/primitif', function () {
        abort_unless(app()->environment('local'), 404);

        return inertia('Dev/Primitif', ['title' => 'Pameran primitif']);
    })->name('dev.primitif');
});
