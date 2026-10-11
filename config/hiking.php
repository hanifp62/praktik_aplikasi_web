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

        // Jangkauan prakiraan BMKG: tanggal trip yang lebih jauh belum punya prakiraan, dan
        // halaman menyebut kapan ia mulai terbit (spec v3 bagian 6.1 dan 6.4).
        'weather_forecast_days' => 3,
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
        // Rumusan OpenTopoMap termasuk SRTM (spec v3 bagian 10.5): data OpenStreetMap dan SRTM,
        // tampilan OpenTopoMap berlisensi CC-BY-SA.
        'attribution' => env('MAP_ATTRIBUTION', '© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 17),
    ],

    /*
    |--------------------------------------------------------------------------
    | Unggahan (PRD §81)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Kesegaran data resmi (PRD §93, §98)
    |--------------------------------------------------------------------------
    */
    'freshness' => [
        // Status yang tidak diverifikasi ulang selama ini dianggap perlu ditinjau.
        'status_review_days' => 90,

        // Peringatan sebelum masa berlaku habis, agar sempat diperpanjang sebelum
        // jalurnya kehilangan status resmi.
        'status_expiry_warning_days' => 14,
    ],

    'uploads' => [
        'report_photo_max_kb' => 4096,

        // Foto dikecilkan sampai sisi terpanjang ini sekaligus dibersihkan EXIF-nya.
        'report_photo_max_dimension' => 2000,

        'gpx_max_kb' => 8192,

        // Jejak yang lebih rapat dari ini ditolak, bukan diencerkan: membuang titik
        // berarti memotong tikungan pada fitur navigasi lapangan. Pada 5000 titik,
        // GeoJSON yang dikirim ke ponsel masih di bawah 200 KB sebelum kompresi.
        'gpx_max_points' => 5000,

        // Ambang histeresis untuk menjumlahkan tanjakan dari ketinggian GPS.
        //
        // Ketinggian GPS berderau beberapa meter meskipun perangkatnya diam, sehingga
        // penjumlahan tanpa ambang mengubah derau menjadi tanjakan: jalur datar dapat
        // melaporkan ratusan meter elevation gain yang tidak pernah didaki siapa pun.
        // Lima meter adalah nilai yang lazim dipakai perkakas GPS; menaikkannya membuat
        // undakan kecil hilang, menurunkannya memasukkan derau kembali.
        'gpx_elevation_threshold_m' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Laju (PRD §100)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'report_submissions_per_hour' => 5,
        'recommendation_runs_per_hour' => 20,

        // Aksi tulis data pribadi (rencana, pengingat, catatan pendakian, target,
        // perlengkapan). Tiap kelompok punya hitungannya sendiri lewat awalan kunci,
        // sehingga menandai perlengkapan tidak menghabiskan jatah mengubah rencana.
        'personal_writes_per_minute' => 30,

        // Centang butir persiapan (W4-Fpre c): satu ketukan per butir ditambah Urungkan, dan
        // daftar bawaan memuat belasan butir, jadi batas tulisan pribadi 30 per menit terlalu
        // ketat untuk pendaki yang mencentang cepat. Hitungannya sendiri (kunci persiapan-butir).
        'preparation_toggles_per_minute' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Jelajah (spec v3 bagian 6.2)
    |--------------------------------------------------------------------------
    */
    'jelajah' => [
        // Kandidat yang dinilai di memori untuk satu permintaan (kecocokan, status, saringan).
        // Kueri tidak tumbuh bersama angka ini; waktu hitungnya ya, jadi dibatasi, dan halaman
        // menyebut bila hasilnya terpotong alih-alih memotongnya diam-diam.
        'batas_kandidat' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Simpanan dan Banding Jalur (DK-05, DK-29)
    |--------------------------------------------------------------------------
    |
    | Simpanan longgar, banding sempit. Dulu keduanya satu batas lima, sehingga
    | menyimpan jalur keenam ditolak padahal yang sebenarnya tidak terbaca adalah
    | kolom banding keenam, bukan daftar simpanannya.
    |
    */
    'saved_trails' => [
        // Batas teknis daftar simpanan, bukan batas keputusan: keputusan dijaga batas
        // banding di bawah. Angka ini hanya menahan satu akun menumpuk baris tanpa
        // ujung. Lima puluh cukup untuk mengumpulkan pilihan beberapa musim pendakian,
        // dan halaman Tersimpan masih memuat seluruhnya tanpa paginasi.
        'max' => 50,

        // Jalur yang dibandingkan berdampingan sekaligus, juga isi satu tautan bagikan.
        // Lebih dari tiga kolom tidak terbaca di layar 360 px, dan beban memutuskan naik
        // ketika pilihan yang ditimbang bersamaan menumpuk.
        'compare_max' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tautan Bagikan Tanpa Akun (features/03, DK-07)
    |--------------------------------------------------------------------------
    */
    'share_links' => [
        // Umur tautan: cukup untuk merundingkan satu rencana dengan teman, cukup pendek
        // sehingga tautan yang tercecer di grup percakapan tidak hidup selamanya. Tujuh
        // hari mengikuti spec v3 bagian 9.3 dan prototype 06 (sebelumnya 30, dari
        // spesifikasi fitur 03). Status resmi di halamannya dibaca ulang setiap kali
        // dibuka, jadi umur ini tidak membuat isinya basi.
        'ttl_days' => 7,

        // Tautan aktif per akun. Menahan pembuatan tanpa ujung tanpa mengganggu pemakaian
        // wajar. Yang lama dicabut pemiliknya, tidak pernah digeser diam-diam, karena
        // orang lain mungkin sedang membukanya.
        'max_active_per_user' => 20,

        // Halaman publik dibuka tanpa akun, jadi dibatasi per alamat IP.
        'views_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rencana Trip (features/04, DK-07)
    |--------------------------------------------------------------------------
    */
    'trips' => [
        // Batas peserta satu rencana (spec v3 bagian 6.8: 1 sampai 12). Rombongan yang
        // lebih besar diurus lewat pengelola kawasan, bukan lewat satu rencana pribadi.
        'max_participants' => 12,

        // Tanggal selesai paling lambat sekian hari setelah tanggal mulai. Rentang
        // yang lebih panjang hampir selalu salah ketik bulan atau tahun.
        'max_span_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengingat Perjalanan (FR-24)
    |--------------------------------------------------------------------------
    |
    | Jam dibaca dalam zona gunungnya (WIB/WITA/WIT), lalu disimpan sebagai UTC.
    |
    */
    'reminders' => [
        // H-3 memberi waktu mengurus yang masih kurang; H-1 mengingatkan hari
        // keberangkatan. Mengubah daftar ini hanya berlaku untuk jadwal baru.
        'days_before' => [3, 1],

        'local_hour' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Target Pribadi (Progres: Target)
    |--------------------------------------------------------------------------
    */
    'personal_targets' => [
        'max_per_user' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Perlengkapan Pribadi (Profil: Perlengkapan)
    |--------------------------------------------------------------------------
    |
    | Hanya kategori berupa barang yang dapat dimiliki. Pemahaman jalur, logistik,
    | cuaca, dan status resmi adalah tindakan per trip, bukan barang di lemari.
    |
    */
    'equipment' => [
        'ownable_categories' => ['EQUIPMENT'],
    ],

];
