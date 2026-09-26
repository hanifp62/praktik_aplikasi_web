<?php

/*
|--------------------------------------------------------------------------
| Identitas merek
|--------------------------------------------------------------------------
|
| Nama produk TIDAK ada di sini: ia hidup di config('app.name') (env APP_NAME) dan
| dibaca wordmark, judul halaman, manifest, kartu sosial, dan email dari satu sumber
| itu. Nama merek final masih keputusan pemilik (HANDOVER.md §0, DK-13); mengganti
| nama berarti mengganti satu nilai env, bukan menyunting belasan berkas.
|
| Yang ada di sini adalah teks dan warna merek yang dipakai lebih dari satu tempat.
| Tagline dan proposisi mengikuti keputusan pemilik D2 (23 September 2026).
| Kalimat "apa yang masih kurang sebelum berangkat" menggantikan "apakah sudah layak
| berangkat" karena readiness adalah dukungan keputusan, bukan izin (R-014, K32).
|
*/

return [
    'tagline' => 'Pilih jalur yang sesuai. Pahami alasannya.',

    'description' => 'Bantu pendaki menentukan jalur yang sesuai profilnya, apa yang '
        .'perlu disiapkan, dan apa yang masih kurang sebelum berangkat.',

    /*
     * Warna bilah peramban dan jendela PWA per tema. Terang memakai hijau merek yang
     * sudah berlaku; gelap memakai kanvas gelap (stone-900) supaya bilah menyatu dengan
     * halaman. Nilai heksa di sini harus sama dengan token --brand-700 dan --canvas
     * gelap di resources/css/app.css; BrandIdentityTest yang menjaganya.
     */
    'theme_color' => [
        'light' => '#047857',
        'dark' => '#1c1917',
    ],

    'background_color' => [
        'light' => '#fafaf8',
        'dark' => '#1c1917',
    ],
];
