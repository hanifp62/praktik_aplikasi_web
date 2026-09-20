<?php

/*
|--------------------------------------------------------------------------
| Nilai Domain Pendakian
|--------------------------------------------------------------------------
|
| Satu tempat untuk seluruh angka yang menentukan perilaku produk. Tim dapat
| mengkalibrasi di sini tanpa menyentuh kode service.
|
| Yang TIDAK ada di sini: bobot faktor kompatibilitas (PRD §27). Bobot bersifat
| data dan dikelola lewat tabel `recommendation_rules` dari halaman admin,
| sehingga kalibrasinya terekam pada jejak audit tiap recommendation run.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Mesin Route Fit (PRD §25-29)
    |--------------------------------------------------------------------------
    |
    | Ambang label publik dan referensi yang dipakai saat pendaki belum punya
    | riwayat. Skor internal tidak pernah ditampilkan ke pengguna (PRD §28).
    |
    */
    'route_fit' => [
        // Skor tertimbang minimum agar sebuah jalur boleh berlabel COCOK.
        'label_threshold_fit' => 0.75,

        // Di bawah ambang ini label menjadi KURANG COCOK.
        'label_threshold_prepare' => 0.50,

        // Faktor kritis (pengalaman, teknis, navigasi) di bawah nilai ini
        // langsung memaksa KURANG COCOK berapa pun skor totalnya.
        'critical_factor_floor' => 0.34,

        // Batas sebuah faktor dianggap "kuat" pada penjelasan dan ringkasan.
        'strong_factor_threshold' => 0.75,

        // Batas sebuah faktor dianggap "lemah" sehingga menyisakan gap persiapan.
        'weak_factor_threshold' => 0.50,

        // Referensi elevation gain (meter) per rank pengalaman, dipakai hanya
        // ketika pendaki belum mencatat riwayat maupun preferensi sendiri.
        'elevation_reference' => [1 => 600, 2 => 1000, 3 => 1600, 4 => 2200],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hike Mode (PRD §53-54)
    |--------------------------------------------------------------------------
    */
    'hike_mode' => [
        // Jarak (meter) yang dianggap sudah mencapai sebuah checkpoint.
        'checkpoint_arrival_radius_m' => 75,

        'earth_radius_m' => 6371000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Agregasi Kondisi (PRD §51, §59)
    |--------------------------------------------------------------------------
    |
    | Kesegaran bersifat relatif terhadap sumbernya. Tidak ada aturan universal
    | "lebih dari 24 jam berarti basi" (PRD §59).
    |
    */
    'conditions' => [
        // Rentang laporan komunitas yang masih ditampilkan, dihitung dari hike_date.
        'community_recent_days' => 30,
        'community_report_limit' => 10,

        // Kesegaran laporan komunitas.
        'community_current_days' => 7,
        'community_aging_days' => 30,

        // BMKG memperbarui dua kali sehari, jadi ambangnya mengikuti kadens itu.
        'weather_current_hours' => 12,
        'weather_aging_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache (PRD §97)
    |--------------------------------------------------------------------------
    |
    | Hanya data publik yang boleh masuk cache bersama. Data privat pengguna
    | tidak pernah dibagikan lewat shared cache.
    |
    */
    'cache' => [
        'public_ttl_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Peta (PRD §55)
    |--------------------------------------------------------------------------
    |
    | MVP memakai peta online. Sumber tile dibaca dari sini supaya tim dapat
    | berpindah penyedia tanpa mengubah kode. Atribusi wajib tampil di peta.
    |
    */
    'map' => [
        'style_url' => env('MAP_STYLE_URL'),
        'raster_tiles' => [env('MAP_TILE_URL', 'https://tile.opentopomap.org/{z}/{x}/{y}.png')],
        'attribution' => env('MAP_ATTRIBUTION', '© OpenTopoMap (CC-BY-SA) © OpenStreetMap contributors'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 17),
    ],

    /*
    |--------------------------------------------------------------------------
    | Unggahan (PRD §81)
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'report_photo_max_kb' => 4096,

        // Foto dikecilkan sampai sisi terpanjang ini sekaligus dibersihkan EXIF-nya.
        'report_photo_max_dimension' => 2000,

        'gpx_max_kb' => 8192,

        // Jejak yang lebih rapat dari ini ditolak, bukan diencerkan: membuang titik
        // berarti memotong tikungan pada fitur navigasi lapangan. Pada 5000 titik,
        // GeoJSON yang dikirim ke ponsel masih di bawah 200 KB sebelum kompresi.
        'gpx_max_points' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Laju (PRD §100)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'report_submissions_per_hour' => 5,
        'recommendation_runs_per_hour' => 20,
    ],

];
