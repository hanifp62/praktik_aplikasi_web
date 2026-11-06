# Dukungan Multibahasa (i18n/l10n) untuk Platform Pendakian Indonesia-first: Laravel 13.31 + Livewire 3.8 (status per 23 September 2026)

Konvensi catatan (R-023): **Cited Findings** hanya berisi FAKTA bersumber. **Inferences** diberi tag [INFERENSI], [REKOMENDASI], atau [HIPOTESIS]. **Gaps** memuat OPEN ISSUE. "(cuplikan)" berarti angka diambil dari cuplikan hasil penelusuran karena halamannya tidak dibuka penuh, jadi perlu diverifikasi ke sumber primer sebelum dikutip di dokumen produk. Bukti lokal berasal dari inspeksi repo `E:\praktik_aplikasi_web` (branch `hardening`) pada 2026-09-23 dan ditautkan sebagai `file:///`.

---

## 1. Bahasa mana yang perlu ditambahkan, dan dengan bukti apa?

### Takeaway
Bahasa Inggris (`en`) layak menjadi bahasa tambahan pertama. Kebutuhan bahasa asing terpusat di jalur tertentu: di Rinjani 53,9% pendaki 2025 adalah wisman, sedangkan di Bromo sekitar 4%. Pendaki asing di Rinjani datang dari 78 negara, dan tiga pasar wisman nasional terbesar (Malaysia, Singapura, Australia) juga paling luas terjangkau oleh bahasa Inggris. Bahasa kedua (zh-Hans, fr, atau ms) sebaiknya diputuskan dari data pengunjung aplikasi sendiri. Bahasa daerah tidak direkomendasikan untuk UI saat ini.

### Cited Findings
**Statistik wisman nasional (BPS)**
- Sepanjang 2025 tercatat 15,39 juta kunjungan wisman (+10,80% dibanding 2024), tertinggi sejak 2020 tetapi masih di bawah 16,1 juta pada 2019. Tiga negara asal terbesar: Malaysia 17,3%, Singapura 14,5%, Australia 11,0% (disampaikan Deputi BPS Ateng Hartono, 2 Februari 2026). — [periskop.id](https://periskop.id/pariwisata/20260202/bps-wisman-ke-indonesia-tembus-1539-juta-rekor-tertinggi-sejak-2020)
- Judul Kontan menyebut "Kunjungan Wisman 2025 Tembus 13,98 Juta". Angka ini tidak sama dengan 15,39 juta, kemungkinan karena kumulatifnya belum setahun penuh (UNVERIFIED). — [Kontan](https://nasional.kontan.co.id/news/kunjungan-wisman-2025-tembus-1398-juta-tertinggi-dalam-enam-tahun-terakhir)
- Juli 2025: 1,48 juta kunjungan (+13,01% yoy); Malaysia 14,32%, Australia 11,69%, Tiongkok 9,76% (cuplikan). — [ANTARA](https://www.antaranews.com/berita/5078725/bps-catat-turis-tiga-negara-dominasi-kunjungan-wisman-ke-ri-pada-juli)
- Agustus 2025: Malaysia 229,7 ribu (15,3%), Australia 155,7 ribu (10,3%), Tiongkok 140,7 ribu (9,3%) (cuplikan). — [Infobanknews](https://infobanknews.com/kunjungan-wisman-ke-indonesia-tembus-151-juta-di-agustus-malaysia-terbanyak/amp/)
- November 2025: Malaysia 207 ribu (17,3%), Australia 135,2 ribu (11,3%), Tiongkok 105,51 ribu (8,80%) (cuplikan). — [ANTARA](https://www.antaranews.com/berita/5333728/bps-jumlah-kunjungan-wisman-ke-ri-pada-november-2025-capai-12-juta)
- **Data terbaru, Juli 2026 (rilis 1 September 2026):** 1,52 juta kunjungan (+9,99% mtm, +2,95% yoy); Malaysia 15%, Australia 12,34%, Tiongkok 10,28%. Kumulatif Januari–Juli 2026 mencapai 8,98 juta (+5,23% yoy). — [ANTARA](https://www.antaranews.com/berita/5720563/bps-kunjungan-wisman-di-juli-capai-152-juta-terbanyak-dari-malaysia)
- Tabel primer BPS menurut kebangsaan tersedia di [BPS: Kunjungan Wisman per Bulan menurut Kebangsaan](https://www.bps.go.id/en/statistics-table/2/MTQ3MCMy/kunjungan-wisatawan-mancanegara-per-bulan-menurut-kebangsaan--kunjungan-.html) (halaman tidak dibuka dalam riset ini).

**Gunung Rinjani (Balai TNGR)**
- 2025: 80.214 pendaki, terdiri dari 43.236 wisman dan 36.978 wisnus. — [ANTARA Mataram](https://mataram.antaranews.com/berita/520097/jumlah-pendaki-gunung-rinjani-2025-capai-80214-orang); [Tempo](https://www.tempo.co/hiburan/pendaki-gunung-rinjani-2025-mencapai-80-214-orang-2106529)
- Sampai Oktober 2025 tercatat 72.528 pendaki. — [Kompas](https://regional.kompas.com/read/2025/11/14/094035378/hingga-oktober-2025-pendaki-gunung-rinjani-capai-72528-orang)
- Pertengahan 2025: 51,39% pendaki Rinjani adalah wisman (judul artikel). — [SuaraNTB, 20 Jul 2025](https://suarantb.com/2025/07/20/5139-persen-pendaki-ke-rinjani-mancanegara/)
- 2024: 93.796 pendaki, dengan 47.716 wisman dan 46.126 wisnus (cuplikan). Kedua komponen berjumlah 93.842, selisih 46 dari total yang dilaporkan. — cuplikan yang merujuk [ANTARA Mataram](https://mataram.antaranews.com/berita/520097/jumlah-pendaki-gunung-rinjani-2025-capai-80214-orang)
- Penerimaan PNBP pendakian 2025 sebesar Rp25,9 miliar (judul). — [Tribun Lombok](https://lombok.tribunnews.com/news/99698/gunung-rinjani-dikunjungi-80-ribu-wisatawan-di-tahun-2025-pemasukan-pnbp-rp259-miliar)
- **Juni 2023, situs resmi Balai TNGR (terbit 7 Juli 2023):** Indonesia 4.349; Malaysia 1.115; Singapura 409; Prancis 402; Belanda 376; Tiongkok 240; Jerman 222; Australia 154; Belgia 152; Inggris Raya 139; Irlandia 104. Pengunjung berasal dari 78 negara. — [rinjaninationalpark.id](https://www.rinjaninationalpark.id/detail/jumlah-negara-dengan-pengunjung-terbanyak-di-taman-nasional-gunung-rinjani-periode-bulan-juni-2023)
- Maret–Juli 2022: dari 1.358 pendaki wisman, terbanyak Prancis 335, Belanda 225, Australia 215, Jerman 154, Malaysia 127, Denmark 110, Singapura 103, Belgia 89 (cuplikan). — [Radar Lombok](https://radarlombok.co.id/wisatawan-asal-prancis-dominasi-pendakian-di-rinjani.html)

**Bromo–Tengger–Semeru (Balai Besar TNBTS)**
- 2024: 465.751 wisnus dan 19.926 wisman. Puncak wisman terjadi Agustus (1.964), Juli (1.649), dan Oktober (1.442) (cuplikan). — [Liputan6](https://www.liputan6.com/regional/read/5894759/hampir-setengah-juta-wisatawan-ke-bromo-pada-2024-80-persen-di-penanjakan)
- Semester I 2025: 321.732 pengunjung, terdiri dari 309.564 wisnus dan 12.168 wisman (cuplikan). — [ANTARA](https://www.antaranews.com/berita/4954825/tnbts-catat-kunjungan-wisatawan-ke-bromo-di-semester-i-2025-meningkat)
- Sampai November 2025 hampir 600 ribu pengunjung, naik 30–40% dibanding 2024 (cuplikan). — [kumparan](https://kumparan.com/kumparannews/pengelola-bromo-sebut-kenaikan-wisatawan-di-2025-sebabkan-degradasi-lahan-26SH3T1B0zm)

**Ijen dan Kerinci**
- TWA Kawah Ijen, libur panjang Isra Mikraj dan Imlek 2025: 3.638 pengunjung, 232 di antaranya wisman (cuplikan). — [Kompas](https://surabaya.kompas.com/read/2025/01/29/191416778/3638-wisatawan-kunjungi-kawah-ijen-selama-libur-panjang-isra-miraj-dan)
- Kerinci, 21 Desember 2025–2 Januari 2026: Gunung Kerinci 934 pengunjung dan Danau Gunung Tujuh 1.261 pengunjung, dengan empat pengunjung mancanegara (cuplikan). — [ANTARA Jambi](https://jambi.antaranews.com/berita/643327/balai-besar-tnks-catat-2195-pendaki-dua-gunung-di-kerinci-saat-libur-nataru)

**Bahasa daerah (Long Form SP2020)**
- 74,77% penduduk berusia 5 tahun ke atas memakai bahasa daerah di lingkungan keluarga dan 72,78% dengan tetangga atau kerabat. Sumber lain menyebut angka nasional 73,87%. Pada Gen Z dan Gen Alfa angkanya 61–62%. Penutur bahasa daerah di keluarga mencapai 89,65% pada kelompok berpendidikan SD ke bawah dan 80,97% pada lulusan SMP (cuplikan; dua angka nasional itu berbeda). — [BPS: Hasil Long Form SP2020](https://www.bps.go.id/en/publication/2023/01/27/ffb5939b4393e5b1146a9b91/hasil-long-form-sensus-penduduk-2020.html); [UNFPA Indonesia](https://indonesia.unfpa.org/en/publications/profile-ethnic-groups-and-regional-language-diversity-2020-population-census-long-form); [Dataindonesia](https://dataindonesia.id/varia/detail/data-persentase-penutur-bahasa-daerah-berdasarkan-pendidikan-yang-ditamatkan); [GoodStats](https://data.goodstats.id/statistic/persentase-penggunaan-bahasa-daerah-menurut-generasi-O0gZE)

**Kerangka hukum bahasa**
- UU 24/2009 Pasal 38 ayat (1) mewajibkan Bahasa Indonesia pada "rambu umum, penunjuk jalan, fasilitas umum, spanduk, dan alat informasi lain yang merupakan pelayanan umum". Ayat (2) mengizinkan penggunaannya disertai bahasa daerah dan/atau bahasa asing. — [peraturan.info: UU 24/2009 Pasal 38](https://peraturan.info/uu/2009/24/pasal-38)
- Perpres 63/2019 Pasal 40 mengulang kewajiban yang sama sebagai aturan pelaksana. — [JDIH BPK: Perpres 63/2019](https://peraturan.bpk.go.id/Details/121661/perpres-no-63-tahun-2019)

### Inferences
- [INFERENSI] Porsi wisman sangat berbeda antarjalur. Rinjani 2025 53,9% (43.236/80.214) dan 2024 sekitar 50,9%, sedangkan Bromo 2024 4,1% (19.926/485.677) dan Semester I 2025 3,8%. Ijen 6,4% hanya dari sampel libur panjang yang didominasi wisnus, jadi tidak representatif. Kerinci sekitar 0,2% pada periode Nataru: empat wisman dari 2.195 pengunjung dua lokasi, Gunung Kerinci dan Danau Gunung Tujuh, dengan pembagian per lokasi yang tidak diketahui. Kebutuhan terjemahan konten basis data sebaiknya diprioritaskan per jalur, Rinjani lebih dulu, dan tidak dipukul rata.
- [INFERENSI] Ada klaim yang beredar bahwa "sekitar 60 persen" pendaki Rinjani 2025 adalah wisman. Klaim itu tidak cocok dengan angka mentahnya (53,9%). Gunakan angka mentah, bukan persentase dari artikel.
- [INFERENSI] Angka 3.313 yang muncul dalam ringkasan data Juni 2023 ternyata jumlah **10 negara teratas saja**. Total wisman dari 78 negara tidak terbaca, jadi total sebenarnya lebih besar. Relatif terhadap 10 negara itu, Malaysia menyumbang sekitar 34%, Singapura 12%, Prancis 12%, Belanda 11%, dan Tiongkok 7%.
- [REKOMENDASI] Bahasa pertama: **Inggris**. Bahasa ini menjangkau Australia, Singapura, Inggris Raya, dan Irlandia secara langsung, serta menjadi bahasa perantara paling mungkin bagi pendaki Eropa dari 78 negara asal. Klaim bahwa bahasa Inggris dipahami luas oleh pendaki Eropa dan Malaysia berstatus UNVERIFIED karena tidak ada sumber yang diambil.
- [REKOMENDASI] Kandidat bahasa kedua, diputuskan setelah ada data analitik aplikasi (sebaran `Accept-Language` pengunjung, kueri Search Console, dan pemilihan bahasa manual):
  - **zh-Hans**: Tiongkok menempati peringkat 3–4 pasar nasional (10,28% pada Juli 2026), tetapi hanya sekitar 7% dari 10 negara teratas di Rinjani pada 2023.
  - **fr**: Prancis peringkat 1 dalam sampel Rinjani 2022 dan peringkat 3 pada Juni 2023.
  - **ms**: Malaysia peringkat 1 baik secara nasional maupun di Rinjani 2023, tetapi bahasa Melayu berkerabat dekat dengan Indonesia. Apakah pengguna Malaysia cukup terlayani UI Indonesia berstatus [HIPOTESIS].
  - Setiap bahasa tambahan menggandakan biaya QA untuk istilah keselamatan (lihat §6). Biaya ini ikut menjadi kriteria keputusan.
- [INFERENSI] Tingginya pemakaian bahasa daerah di rumah tidak membuktikan rendahnya pemahaman bahasa Indonesia tertulis. Kewajiban Bahasa Indonesia menurut UU 24/2009 juga membuat UI bahasa daerah bernilai rendah dibanding biaya QA-nya. Karena itu bahasa daerah tidak direkomendasikan sekarang. Bahasa Sasak atau Jawa untuk porter dan pengelola lokal berstatus [HIPOTESIS] tanpa bukti kebutuhan.
- [INFERENSI] Bahasa Indonesia harus tetap tersedia penuh dan menjadi teks yang otoritatif, sedangkan bahasa asing berperan sebagai pendamping. Apakah aplikasi web swasta termasuk "alat informasi lain yang merupakan pelayanan umum" adalah pertanyaan hukum yang PERLU KLARIFIKASI. Pendekatan konservatif: jangan pernah membuat halaman atau fitur yang hanya tersedia dalam bahasa asing.

### Gaps
- Tabel primer BPS tidak dibuka. Porsi Singapura 14,5% setahun penuh 2025 tampak janggal karena Singapura tidak masuk tiga besar pada Juli 2025, Agustus 2025, maupun Juli 2026, sehingga PERLU KLARIFIKASI. Beda 13,98 juta dengan 15,39 juta juga belum terjelaskan.
- Data Kemenparekraf/Kemenpar tidak diambil langsung; BPS dipakai sebagai sumber resmi.
- Data tahunan wisman untuk Ijen dan Kerinci tidak ditemukan; yang ada hanya sampel periode libur.
- Rincian kebangsaan pendaki Rinjani 2024–2025 tidak ditemukan. Data resmi terbaru yang ditemukan adalah Juni 2023.
- Tidak ada data volume pencarian bahasa Inggris (misalnya "Mount Rinjani trail") maupun data sebaran bahasa peramban pengunjung aplikasi ini.

---

## 2. Praktik terbaik lokalisasi di Laravel 13 + Livewire 3 (file terjemahan, ekstraksi, pluralisasi, validasi, middleware, preferensi, Accept-Language, persistensi Livewire/wire:navigate, format, WIB/WITA/WIT)

### Takeaway
Seluruh kebutuhan inti sudah tersedia di Laravel 13.31 tanpa paket tambahan: `__()`/`trans_choice`, file PHP (short key) dan JSON, fallback, `handleMissingKeysUsing`, `Number` berbasis intl, dan Carbon yang locale-nya ikut tersinkron otomatis. Livewire 3.8.9 menyimpan locale di snapshot komponen, dan `wire:navigate` menyalin atribut `<html>` termasuk `lang`. Pekerjaan terbesar ada di tiga tempat: string yang tertanam di 96 view Blade, label enum domain yang ditulis langsung dalam bahasa Indonesia, dan anomali `lang/en/recommendation.php` yang isinya bahasa Indonesia.

### Cited Findings
**Dokumentasi Laravel 13.x**
- Ada dua cara menyimpan terjemahan: file PHP per locale di `lang/{locale}/*.php` (short key) dan file JSON `lang/{locale}.json` (string sebagai key). JSON disebut "recommended for applications that have a large number of translatable strings". Folder `lang` tidak ada di skeleton dan dibuat dengan `php artisan lang:publish`. — [Laravel 13.x Localization](https://laravel.com/docs/13.x/localization)
- Locale default diatur lewat `APP_LOCALE` dan fallback lewat `APP_FALLBACK_LOCALE`. `App::setLocale()` mengganti locale per request; `App::currentLocale()` dan `App::isLocale()` membacanya. Varian wilayah memakai penamaan ISO 15897 (`en_GB`, bukan `en-gb`). Bila key tidak ada, `__()` mengembalikan key itu sendiri. — [Laravel 13.x Localization](https://laravel.com/docs/13.x/localization)
- Pluralisasi memakai pemisah `|` dan rentang seperti `{0}`, `[1,19]`, `[20,*]`, dipanggil lewat `trans_choice()` dengan placeholder `:count`. `Pluralizer::useLanguage()` hanya untuk pluralizer Eloquent (french, norwegian-bokmal, portuguese, spanish, turkish), bukan untuk teks UI. — [Laravel 13.x Localization](https://laravel.com/docs/13.x/localization)
- Kapitalisasi placeholder mengikuti penulisannya (`:NAME` → huruf besar semua, `:Name` → huruf awal kapital). `Lang::stringable()` memformat objek. Ada peringatan konflik key/file: `__('Action')` bisa mengembalikan seluruh isi `nl/action.php` bila `nl.json` tidak ada. Terjemahan paket di-override lewat `lang/vendor/{package}/{locale}`. — [Laravel 13.x Localization](https://laravel.com/docs/13.x/localization)
- Model yang mengimplementasikan `HasLocalePreference::preferredLocale()` membuat Laravel "automatically use the preferred locale when sending mailables and notifications". — [Laravel 13.x Mail: Localizing Mailables](https://laravel.com/docs/13.x/mail#localizing-mailables)
- `Number::format(..., locale:)`, `percentage`, `currency`, `spell`, `useLocale()` (global), `withLocale()` (sementara), dan `defaultLocale()` (bawaan `en`). — [Laravel 13.x Helpers: Numbers](https://laravel.com/docs/13.x/helpers)

**Bukti lokal: konfigurasi dan isi `lang/`**
- `locale` = `id`, `fallback_locale` = `en`, `timezone` = `UTC`, `faker_locale` bawaan `en_US` (sementara `.env.example` memakai `id_ID`). — [config/app.php:68,81,83,85](file:///E:/praktik_aplikasi_web/config/app.php)
- `lang/en/` hanya berisi `recommendation.php`, dan **seluruh isinya berbahasa Indonesia** (misalnya `'official_status_closed' => 'Status resmi jalur ini tercatat TUTUP.'`). `lang/id/` berisi `auth`, `pagination`, `passwords`, dan `validation`. `lang/id.json` (2.839 byte) memetakan string Breeze berbahasa Inggris ke bahasa Indonesia. — [lang/en/recommendation.php](file:///E:/praktik_aplikasi_web/lang/en/recommendation.php)
- Key `recommendation.*` dipakai di [app/Services/TrailFitService.php:121](file:///E:/praktik_aplikasi_web/app/Services/TrailFitService.php) dan [recommendation-results.blade.php:234](file:///E:/praktik_aplikasi_web/resources/views/livewire/recommendations/recommendation-results.blade.php). Karena `lang/id/recommendation.php` tidak ada, pengguna Indonesia saat ini menerima teks itu **lewat fallback `en`**.
- Framework membawa file bahasa Inggris sendiri di `vendor/laravel/framework/src/Illuminate/Translation/lang/en/{auth,pagination,passwords,validation}.php`, sehingga pesan validasi bahasa Inggris tersedia tanpa publish. — [bukti lokal: vendor/laravel/framework/src/Illuminate/Translation/lang/en](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/lang/en)

**Bukti lokal: label domain yang tertanam di kode**
- `RouteFitLabel::label()` mengembalikan `'Cocok'`, `'Perlu persiapan'`, dan `'Kurang cocok'`, lengkap dengan deskripsi kalimat. — [app/Enums/RouteFitLabel.php](file:///E:/praktik_aplikasi_web/app/Enums/RouteFitLabel.php)
- `OfficialStatusValue` memberi label `'Buka'`, `'Dibatasi'`, `'Tutup'`, dan `'Belum diketahui'`. — [app/Enums/OfficialStatusValue.php:15-18](file:///E:/praktik_aplikasi_web/app/Enums/OfficialStatusValue.php)
- `ReadinessState` memberi label `'Siap'`, `'Perlu persiapan'`, dan `'Tidak direkomendasikan'`. Label **'Perlu persiapan' dipakai oleh dua konsep domain yang berbeda** (Route Fit dan readiness). — [app/Enums/ReadinessState.php:14-16](file:///E:/praktik_aplikasi_web/app/Enums/ReadinessState.php)

**Bukti lokal: view dan middleware**
- Ada 96 file Blade dan 38 pemakaian `wire:navigate`; `@persist` tidak dipakai sama sekali.
- `<html lang>` sudah dinamis di `layouts/app`, `layouts/guest`, dan `errors/layout`, tetapi **tertulis mati `lang="id"`** di [resources/views/public/trail.blade.php:14](file:///E:/praktik_aplikasi_web/resources/views/public/trail.blade.php) dan `offline.blade.php:2`.
- Middleware grup `web` hanya menambahkan `SecurityHeaders`; belum ada middleware locale. — [bootstrap/app.php:16-19](file:///E:/praktik_aplikasi_web/bootstrap/app.php)

**Bukti lokal: perilaku Translator (Laravel 13.31.0)**
- `handleMissingKeysUsing()` ada di baris 429, `determineLocalesUsing()` di baris 508, dan `setLocale()` menolak locale yang mengandung `/` atau `\` (baris 576-580). — [Translator.php](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/Translator.php)
- Di `Translator::get()` (baris 151-190), JSON hanya dicari di locale aktif. Untuk short key, locale aktif lalu fallback dicoba berurutan, dan callback key hilang baru dipanggil setelah semuanya gagal. — [Translator.php](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/Translator.php)
- **Uji runtime lokal via `php artisan tinker` (2026-09-23)** dengan handler pencatat terpasang, menghasilkan tiga kasus:
  - (a) locale `id` + short key `recommendation.trail_archived` yang hanya ada di `lang/en/`: fallback mengembalikan teksnya dan **handler tidak terpicu**;
  - (b) locale `en` + key JSON `"Already registered?"` yang hanya ada di `id.json`: **JSON fallback tidak dibaca**, key dikembalikan apa adanya, dan **handler terpicu**;
  - (c) locale `en` + literal Indonesia `"Simpan rencana"`: key dikembalikan dan handler terpicu.
- `MessageSelector`: `id`/`id_ID` masuk kelompok yang indeks pluralnya selalu 0, sedangkan string satu segmen selalu mengembalikan `segments[0]`. Jadi bahasa Indonesia cukup satu bentuk dan bahasa Inggris memakai dua bentuk. — [MessageSelector.php:29-31,120-147,172](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/MessageSelector.php)
- Callback `handleMissingKeysUsing` menerima `(key, replacements, locale)` dan juga ikut terpicu saat pesan validasi dibentuk, sehingga key validasi perlu disaring. — [jcergolj](https://jcergolj.me.uk/blog/avoiding-unnecessary-missing-translation-warnings/). PR #52895 membuatnya tidak terpicu saat mengecek keberadaan key. — [laravel/framework#52895](https://github.com/laravel/framework/pull/52895)

**Bukti lokal: negosiasi bahasa (Symfony HttpFoundation v8.1.6)**
- `Request::getPreferredLanguage(array $locales)` (Request.php:1669) mengembalikan elemen pertama daftar bila request tidak membawa `Accept-Language`, dan mencocokkan prefiks seperti `en-US` → `en`. — [vendor/symfony/http-foundation/Request.php](file:///E:/praktik_aplikasi_web/vendor/symfony/http-foundation/Request.php)

**Livewire 3.8.9**
- Fitur `SupportLocales` menulis `app()->getLocale()` ke memo snapshot saat dehydrate, lalu memanggil `app()->setLocale($memo['locale'])` saat hydrate. — [bukti lokal: vendor/livewire/livewire/src/Features/SupportLocales/SupportLocales.php](file:///E:/praktik_aplikasi_web/vendor/livewire/livewire/src/Features/SupportLocales/SupportLocales.php)
- Route update Livewire adalah `Route::post('/livewire/update', $handle)->middleware('web')`, sehingga middleware grup `web` juga berjalan pada setiap update. — [HandleRequests.php:22-23](file:///E:/praktik_aplikasi_web/vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php)
- Dokumentasi Livewire menjelaskan "persistent middleware": middleware route dijalankan ulang pada update. Daftar bawaannya berisi middleware auth, binding, dan authorize, tanpa menyebut locale. Middleware kustom harus didaftarkan dari Service Provider. — [Livewire 3.x Security](https://livewire.laravel.com/docs/3.x/security)
- "The locale travels inside the snapshot, not the URL." Dokumentasi yang sama memberi peringatan: "don't first-render components on unlocalized routes". — [Laravel Localizer: Livewire](https://localizer.adam-nielsen.de/livewire)
- `wire:navigate` memanggil `replaceHtmlAttributes(newHtml)`, yang menyalin semua atribut `<html>` dari halaman baru dan menghapus atribut yang tidak ada di sana. Jadi `lang` ikut berubah selama server merender nilai yang benar. — [livewire.esm.js:10054,10074-10087](file:///E:/praktik_aplikasi_web/vendor/livewire/livewire/dist/livewire.esm.js)

**Tanggal dan angka**
- `Carbon\Laravel\ServiceProvider` (Carbon 3.13.2) mendengarkan event `LocaleUpdated`, lalu menyetel locale `Carbon`, `CarbonImmutable`, `CarbonPeriod`, `CarbonInterval`, dan `Illuminate\Support\Carbon`. — [bukti lokal: ServiceProvider.php:66-92](file:///E:/praktik_aplikasi_web/vendor/nesbot/carbon/src/Carbon/Laravel/ServiceProvider.php)
- Ekstensi intl aktif (ICU 77.1). Angka 3726.5 diformat `NumberFormatter` menjadi `"3.726,5"` untuk `id_ID` dan `"3,726.5"` untuk `en` (dijalankan lokal, PHP 8.5.10).

**Zona waktu**
- Keppres 41/1987 membagi Indonesia menjadi WIB (GMT+7, meridian 105°BT), WITA (GMT+8, 120°BT), dan WIT (GMT+9, 135°BT). Ditetapkan 26 November 1987 dan berlaku sejak 1 Januari 1988 (cuplikan). — [CNN Indonesia](https://www.cnnindonesia.com/edukasi/20230426104629-569-942144/3-pembagian-waktu-di-indonesia-dan-wilayahnya-wib-wita-wit); [Wikipedia: Zona waktu Indonesia](https://id.wikipedia.org/wiki/Zona_waktu_Indonesia)
- Kode sudah memetakan `Asia/Jakarta`→WIB, `Asia/Makassar`→WITA, dan `Asia/Jayapura`→WIT, dengan default `Asia/Jakarta`. Menurut docblock-nya, zona waktu mengikuti lokasi gunung, bukan lokasi pengguna. — [app/Support/Timezone.php:12-26](file:///E:/praktik_aplikasi_web/app/Support/Timezone.php)

**Cuaca BMKG**
- API publik BMKG mengembalikan `weather_desc` (Indonesia) **dan `weather_desc_en` (Inggris)**. Data diperbarui dua kali sehari, mencakup 3 hari dengan interval per 3 jam. Pengguna wajib mencantumkan BMKG sebagai sumber, dan batasnya 60 permintaan/menit per IP. — [BMKG Data Terbuka: Prakiraan Cuaca](https://data.bmkg.go.id/prakiraan-cuaca/)
- Aplikasi saat ini hanya menyimpan `weather_desc` ke kolom `weather_description`. — [app/Services/WeatherService.php:135](file:///E:/praktik_aplikasi_web/app/Services/WeatherService.php)

### Inferences
- [REKOMENDASI] **Struktur key hibrida.**
  - **Short key PHP** (`lang/{id,en}/status.php`, `fit.php`, `readiness.php`, `weather.php`, `units.php`, `recommendation.php`) untuk semua kosakata domain dan keselamatan serta label enum. Alasannya, key berbentuk string tidak bisa membedakan konteks: "Perlu persiapan" dipakai untuk fit dan readiness, dan "Buka" bisa berarti status maupun kata kerja tombol. Enum cukup mengembalikan `__("status.{$this->value}")`.
  - **JSON** untuk copy UI umum. Dengan kalimat Indonesia sebagai key (`__('Simpan rencana')`), ekstraksi dari Blade cukup dengan membungkus teks yang sudah ada, dan terjemahan yang hilang otomatis tampil dalam bahasa Indonesia, sesuai sifat produk yang Indonesia-first. Kelemahannya, key berubah setiap kali copy Indonesia diedit, jadi perlu deteksi terjemahan yang tertinggal (§7).
  - `id.json` Breeze yang sudah ada (key berbahasa Inggris) tidak bertabrakan dengan pendekatan ini.
- [REKOMENDASI] **Perbaiki dulu anomali `recommendation.php` sebelum menambah `en`.** Pindahkan isinya ke `lang/id/recommendation.php`, tulis teks Inggris sungguhan di `lang/en/`, dan ubah `APP_FALLBACK_LOCALE` menjadi `id` agar key yang hilang tampil dalam bahasa Indonesia, bukan dalam bahasa Inggris atau sebagai key mentah. Mengubah fallback berarti mengubah perilaku konfigurasi, jadi perlu dicatat. Karena short key yang hilang di `en` terisi diam-diam dari fallback tanpa memicu handler (uji runtime kasus a), **deteksi key hilang untuk short key harus lewat test paritas** (§7), bukan lewat logging. Sebaliknya, key JSON berbahasa Indonesia yang tidak ada di `en.json` mengembalikan teks Indonesia (fallback yang jujur) **dan** memicu handler (kasus c), sehingga bisa dicatat sebagai metrik cakupan.
- [REKOMENDASI] **Middleware `SetLocale`**, satu kelas kecil yang di-append ke grup `web`. Urutan penentuan locale:
  1. prefiks URL, untuk halaman publik;
  2. kolom `users.locale` milik pengguna yang login;
  3. session;
  4. `id`.

  Nilainya selalu divalidasi terhadap allowlist `config('app.supported_locales')`, jangan hanya mengandalkan pengecekan `/` dan `\` di Translator. `Accept-Language` hanya dipakai untuk **menawarkan** pindah bahasa lewat banner yang bisa ditutup, tidak untuk redirect (lihat §4). Karena `/livewire/update` juga melewati grup `web`, lalu `SupportLocales` menimpa locale dari memo, komponen tetap memakai locale saat pertama dirender. Livewire tidak perlu dikonfigurasi khusus.
- [REKOMENDASI] **Pemilih bahasa** berupa tautan atau form biasa **tanpa `wire:navigate`**, sehingga terjadi muat ulang penuh. Tujuannya agar semua snapshot komponen yang sudah dirender ulang dengan locale baru. Mengubah locale dari dalam aksi Livewire hanya memengaruhi komponen itu (dari memo dehydrate) dan meninggalkan komponen lain di halaman dalam bahasa lama [INFERENSI]. Tautan menuju halaman padanan dalam bahasa target, bukan ke beranda.
- [REKOMENDASI] **Preferensi pengguna.** Tambahkan kolom `users.locale` (nullable, divalidasi `Rule::in`, diisi hanya lewat form profil) dan implementasikan `HasLocalePreference` supaya email verifikasi dan notifikasi terkirim dalam bahasa pengguna. Ini perubahan skema, jadi migrasinya perlu diverifikasi di PostgreSQL (R-024).
- [REKOMENDASI] **Ekstraksi string Blade.**
  - Kerjakan bertahap dengan urutan: (a) komponen tingkat keselamatan (`fit-line`, `trail-row`, `consideration-tray`, badge status, readiness, cuaca); (b) halaman SEO publik; (c) sisanya.
  - Teks di dalam atribut ditulis `aria-label="{{ __('…') }}"`, dan string untuk Alpine diteruskan lewat `@js(__('…'))`.
  - Laravel tidak menyediakan extractor resmi. Alat pihak ketiga pengekspor `__()` ke JSON tidak diriset (UNVERIFIED).
- [REKOMENDASI] **Angka dan satuan.** Jangan pernah menulis angka yang sudah diformat langsung di teks. "3.726 mdpl" pada halaman berbahasa Inggris bisa terbaca sebagai 3,726 meter, karena pemisah desimal `id` dan `en` terbalik (terbukti dari keluaran ICU di atas). Pakai `Number::format($elevation, locale: app()->getLocale())`. Satuan tetap metrik; "mdpl" diterjemahkan menjadi "m a.s.l.", dan km, m, serta °C tidak berubah.
- [REKOMENDASI] **Tanggal dan waktu.** Pakai `isoFormat('LL')` atau `translatedFormat()`, karena locale Carbon otomatis ikut sinkron. Waktu ditampilkan dalam zona gunung (yang sudah benar) dengan label WIB/WITA/WIT. Untuk `en` tambahkan offset UTC, misalnya "06:00 WITA (UTC+8)", karena singkatan zona Indonesia asing bagi wisman [INFERENSI].
- [REKOMENDASI] **Cuaca.** Simpan juga `weather_desc_en` dari BMKG dan tampilkan sesuai locale. Ini terjemahan dari sumbernya sendiri, jadi garis keturunan datanya tetap BMKG (R-013). Menambah kolom berarti perubahan skema.
- [INFERENSI] Pesan validasi: gunakan kunci `attributes` di `validation.php` untuk kedua locale. Pesan bahasa Inggris dari framework sudah tersedia tanpa perlu `lang:publish`.

### Gaps
- `handleMissingKeysUsing` tidak dibahas di halaman dokumentasi Localization 13.x. Perilakunya disimpulkan dari source 13.31.0 dan PR/diskusi terkait.
- `SupportLocales` tidak ditemukan di halaman dokumentasi Livewire yang dibuka. Buktinya berasal dari source vendor dan dokumentasi paket pihak ketiga.
- Konvensi penulisan jam dalam bahasa Indonesia (misalnya "06.00" menurut EYD) tidak diriset (UNVERIFIED).

---

## 3. Paket komunitas atau Laravel murni? (kompatibilitas L13 dan Livewire 3, status pemeliharaan 2026)

### Takeaway
Untuk `id` + `en`, tidak ada paket yang wajib dipakai: Laravel murni dengan middleware kecil sudah cukup. Paket yang paling bernilai bila dibutuhkan adalah `spatie/laravel-translatable` untuk konten basis data. Untuk routing, `niels-numbers/laravel-localizer` lebih cocok daripada `mcamara`, karena README `mcamara` sendiri menyarankan paket itu sebagai penerus. `laravel-lang/lang` **jangan ditambahkan**: paket ini terkena serangan supply chain pada Mei 2026, dan kebutuhannya (file `en` framework) sudah dibundel Laravel. Setiap penambahan dependensi butuh persetujuan pemilik.

### Cited Findings
- **mcamara/laravel-localization.** Versi v2.4.2 (2026-08-24) dengan `"laravel/framework": "^11.0|^12.0|^13.0"` dan `"php": "^8.2"`, sekitar 10,25 juta instalasi, 3.560 bintang, dan 11 isu terbuka. — [Packagist](https://packagist.org/packages/mcamara/laravel-localization)
  - README-nya merekomendasikan `niels-numbers/laravel-localizer` sebagai "modern successor" untuk proyek baru.
  - Paket ini membuat route secara dinamis per request dan tidak mendukung `route:cache` secara native, sehingga perlu `route:trans:cache` lewat `LoadsTranslatedCachedRoutes`.
  - Middleware yang disediakan: `localize`, `localizationRedirect`, `localeSessionRedirect`, `localeCookieRedirect`, `localeViewPath`. Opsinya antara lain `hideDefaultLocaleInURL`, `useAcceptLanguageHeader`, dan route terjemahan lewat `lang/[locale]/routes.php`.
  - README tidak memuat panduan khusus Livewire. — [GitHub mcamara](https://github.com/mcamara/laravel-localization)
- Ada isu berjudul "Route Trans Caching is broken with Livewire 3" (hanya judulnya yang dibaca). — [mcamara#880](https://github.com/mcamara/laravel-localization/issues/880)
- **niels-numbers/laravel-localizer.** Versi v1.4.0 (2026-06-05) dengan `illuminate/support` `^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0` dan `php ^8.2`, **17.888 instalasi**, diperbarui 2026-09-09. Menyediakan `Route::localize()`. — [Packagist](https://packagist.org/packages/niels-numbers/laravel-localizer)
  - Dokumentasinya menyatakan Livewire 3 dan 4 berjalan "out of the box" tanpa `setUpdateRoute()` dan diuji dengan test integrasi. — [Laravel Localizer: Livewire](https://localizer.adam-nielsen.de/livewire)
- **spatie/laravel-translatable.** Versi 6.14.1 (2026-04-23) dengan `"illuminate/database": "^11.0|^12.0|^13.0"` dan `php ^8.3`, sekitar 30,1 juta instalasi, diperbarui 2026-08-26. Terjemahan disimpan sebagai JSON di kolom model. — [Packagist](https://packagist.org/packages/spatie/laravel-translatable)
  - Fallback bawaan mengikuti `fallback_locale`. Tersedia `Translatable::fallback(fallbackLocale:, fallbackAny:, missingKeyCallback:)`, `getFallbackLocale()` per model, dan `protected $useFallbackLocale = false` untuk mematikan fallback. — [Spatie docs: Handling missing translations](https://spatie.be/docs/laravel-translatable/v6/basic-usage/handling-missing-translations)
- **laravel-lang/lang.** Versi 15.36.0 (2026-09-20) dengan `php ^8.2` dan `laravel-lang/publisher ^16.0`, konflik dengan `laravel/framework <11.0.7`. Kompatibilitas eksplisit dengan L13 tidak tercantum. Paket berisi 126 bahasa dan sekitar 12,28 juta unduhan. Halaman Packagist menampilkan label bahwa beberapa versinya "flagged as malware by Aikido". — [Packagist](https://packagist.org/packages/laravel-lang/lang)
- **Serangan supply chain laravel-lang (terdeteksi 22 Mei 2026).**
  - 233 versi di tiga repositori (`laravel-lang/lang`, `attributes`, `http-statuses`) dikompromikan lewat tag GitHub yang menunjuk ke commit dari fork milik penyerang.
  - Payload `src/helpers.php` berjalan otomatis lewat `autoload.files` dan mencuri kunci cloud, kunci SSH, kredensial Git, serta kata sandi peramban.
  - Packagist menghapus versi berbahaya dan sempat meng-unlist paketnya. — [Aikido](https://www.aikido.dev/blog/supply-chain-attack-targets-laravel-lang-packages-with-credential-stealer); [BleepingComputer](https://www.bleepingcomputer.com/news/security/laravel-lang-packages-hijacked-to-deploy-credential-stealing-malware/)
- Bagian `require` di `composer.json` saat ini hanya berisi php `^8.3`, laravel/framework `^13.17`, laravel/tinker, league/flysystem-aws-s3-v3, livewire/livewire `^3.6.4`, dan livewire/volt `^1.7.0`. Belum ada paket i18n. — [composer.json](file:///E:/praktik_aplikasi_web/composer.json)

### Inferences
- [REKOMENDASI] Perbandingan pilihan:

| Pilihan | Untung | Rugi / risiko | Status 2026 |
|---|---|---|---|
| Laravel murni: grup route `prefix('en')` + middleware `SetLocale` | Tanpa dependensi, route statis (`route:cache` bisa dijalankan saat build di Vercel), kendali penuh atas hreflang | Helper URL padanan dan hreflang harus ditulis sendiri | Direkomendasikan untuk 2 bahasa |
| niels-numbers/laravel-localizer | `Route::localize()`, Livewire 3 teruji | Paket muda (sekitar 18 ribu instalasi) dengan risiko bus-factor | Mendukung L13 |
| mcamara/laravel-localization | Matang, sekitar 10 juta instalasi | Route dinamis per request, perlu `route:trans:cache`, isu cache dengan Livewire 3, dan maintainer-nya sendiri menyarankan penerus | Mendukung L13 |
| spatie/laravel-translatable | Konten basis data per locale di JSON/jsonb, fallback bisa diatur | Tidak menyimpan metadata per terjemahan (siapa penerjemah atau peninjau, status basi) | Mendukung L13, aktif |
| laravel-lang/lang | 126 bahasa untuk string framework | Riwayat malware Mei 2026, dan `en` sudah dibundel framework sementara `id` sudah ada | Hindari |

- [REKOMENDASI] Untuk field konten yang relevan dengan keselamatan, tabel terjemahan eksplisit (misalnya `trail_translations(trail_id, locale, description, …, translated_by, reviewed_by, reviewed_at, source_hash)`) lebih cocok daripada JSON spatie. Alasannya, produk ini menuntut garis keturunan data dan status tinjauan (R-009, R-013). Keputusan ini termasuk perubahan arsitektur basis data (R-021) dan membutuhkan persetujuan pemilik.
- [INFERENSI] Menambah paket apa pun, termasuk spatie, memerlukan persetujuan pemilik (CLAUDE.md dan Boost: "Do not change the application's dependencies without approval"). Kasus laravel-lang menunjukkan bahwa paket i18n yang memakai `autoload.files` ikut memperluas permukaan serangan saat `composer install`.

### Gaps
- Isi isu mcamara#880 tidak dibaca, jadi apakah sudah diperbaiki di v2.4.2 tidak diketahui.
- Rentang versi laravel-lang yang terdampak dan versi yang dinyatakan bersih tidak dirinci.
- Kematangan niels-numbers (jumlah maintainer, isu terbuka) tidak diperiksa.

---

## 4. Strategi URL/SEO untuk halaman publik multibahasa

### Takeaway
Pakai subdirektori `/en/…` untuk bahasa tambahan dan biarkan bahasa Indonesia di root tanpa prefiks, sehingga URL yang sudah terindeks tidak berubah. Pasang hreflang dua arah plus self-reference dan `x-default`. Jangan memakai redirect otomatis maupun cookie atau query sebagai penentu bahasa halaman publik. Halaman `en` yang isi utamanya masih berbahasa Indonesia dianggap duplikat oleh Google, jadi halaman seperti itu jangan dimasukkan ke klaster hreflang atau sitemap sebelum isinya benar-benar diterjemahkan.

### Cited Findings
- **Metode hreflang.** Ada tiga metode yang setara: tag HTML, header HTTP, dan sitemap. Memakai ketiganya sekaligus tidak memberi manfaat tambahan. — [Google: Localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions) (last updated 2026-09-21)
  - Setiap versi wajib mencantumkan dirinya sendiri dan semua versi lain: "If two pages don't both point to each other, the tags will be ignored."
  - `x-default` direkomendasikan sebagai fallback bagi pengguna yang bahasanya tidak cocok dengan versi mana pun.
  - Hanya kode ISO 639-1 dan ISO 3166-1 Alpha-2 yang didukung, dan kode negara tidak boleh berdiri sendiri.
  - "Google doesn't use hreflang or the HTML lang attribute to detect the language of a page."
- **Struktur URL.** Menurut Google, subdirektori "Easy to set up" dan "Low maintenance", sedangkan parameter URL "Not recommended". — [Google: Managing multi-regional and multilingual sites](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites) (last updated 2025-12-10)
  - Google menyarankan URL berbeda untuk setiap bahasa daripada cookie atau pengaturan peramban.
  - "Avoid automatically redirecting users from one language version of a site to a different language version."
  - Satu bahasa per halaman, tanpa terjemahan berdampingan.
  - Kata yang dilokalkan boleh dipakai di URL dengan encoding UTF-8.
  - Googlebot biasanya merayapi dari AS dan "sends HTTP requests without setting `Accept-Language`".
- **Duplikasi antarbahasa.** "Different language versions of a single page are considered duplicates only if the primary content is in the same language", yaitu ketika yang diterjemahkan hanya header, footer, dan teks non-kritis. Canonical berstatus "hint, not a rule". — [Google: Canonicalization](https://developers.google.com/search/docs/crawling-indexing/canonicalization) (last updated 2026-08-20)
- **Spam policy.** Kategori *scaled content abuse* mencakup halaman massal yang dibuat "through automated transformations like synonymizing, translating, or other obfuscation techniques" bila nilainya kecil bagi pengguna. — [Google: Spam policies](https://developers.google.com/search/docs/essentials/spam-policies) (last updated 2026-08-28)
- **Keadaan repo saat ini.**
  - Halaman publik ada di `pendakian/{trail:slug}` (`public.trail`) dan `sitemap.xml`. — [routes/web.php:56-57](file:///E:/praktik_aplikasi_web/routes/web.php)
  - `<link rel="canonical">` dan `og:locale` `id_ID` sudah ada, tetapi `<html lang="id">` masih tertulis mati. — [public/trail.blade.php:14,26,50](file:///E:/praktik_aplikasi_web/resources/views/public/trail.blade.php)
  - Sitemap berupa `urlset` biasa tanpa namespace `xhtml`. — [public/sitemap.blade.php:5](file:///E:/praktik_aplikasi_web/resources/views/public/sitemap.blade.php)

### Inferences
- [REKOMENDASI] Peta URL. Opsi A menerjemahkan segmen struktural; opsi B mempertahankan segmen Indonesia.

| Halaman | `id` (root, tetap) | `en` opsi A | `en` opsi B |
|---|---|---|---|
| Gunung | `/gunung/{gunung}` | `/en/mountains/{gunung}` | `/en/gunung/{gunung}` |
| Jalur | `/gunung/{gunung}/via/{jalur}` | `/en/mountains/{gunung}/via/{jalur}` | `/en/gunung/{gunung}/via/{jalur}` |
| Panduan | `/panduan/{topik}` | `/en/guides/{topic-en}` | `/en/panduan/{topik}` |
| Kondisi | `/kondisi/{jalur}` | `/en/conditions/{jalur}` | `/en/kondisi/{jalur}` |

  - Slug nama diri (`rinjani`, `sembalun`) sama di semua bahasa. Slug panduan boleh diterjemahkan karena bukan nama diri.
  - Opsi A lebih mudah dibaca dan tetap bisa ditulis sebagai route statis tanpa paket. Opsi B lebih murah dikerjakan. Google membolehkan keduanya.
  - Saran: **putuskan migrasi `/pendakian/{slug}` → `/gunung/{slug}/via/{trail}` bersamaan dengan peluncuran `/en/`**, supaya redirect 301 hanya terjadi sekali dan tidak membentuk rantai. Perubahan struktur URL publik mengikuti arsitektur informasi di PRD §145 dan memerlukan persetujuan (R-021).
- [REKOMENDASI] `x-default` diarahkan ke **versi `en`** bila halaman tersebut punya terjemahan Inggris yang sungguhan. Untuk pengguna berbahasa Prancis, Jerman, dan bahasa lain yang tidak cocok dengan `id` maupun `en`, bahasa Inggris lebih berguna. Pengguna berbahasa Indonesia tetap diarahkan ke `id` oleh `hreflang="id"`. Halaman yang belum punya versi `en` tidak perlu klaster hreflang sama sekali [INFERENSI dari definisi x-default Google].
- [REKOMENDASI] **Halaman `en` yang konten utamanya belum diterjemahkan** tetap boleh tampil untuk manusia (UI Inggris dengan teks asli berbahasa Indonesia yang diberi label). Halaman seperti itu harus `noindex`, ber-canonical ke URL `id`, dan dikeluarkan dari hreflang serta sitemap. Menurut Google halaman tersebut duplikat, dan halaman `en` hasil terjemahan mesin massal berisiko masuk kategori scaled content abuse.
- [REKOMENDASI] Sitemap menambahkan `xmlns:xhtml="http://www.w3.org/1999/xhtml"` dan `<xhtml:link rel="alternate" hreflang="…">` hanya untuk pasangan yang lengkap. Cukup pilih **satu** metode (sitemap atau `<head>`). Tambahkan `og:locale` dinamis dan `og:locale:alternate`. `<html lang>` di `public/trail.blade.php` dan `offline.blade.php` harus mengikuti `app()->getLocale()`.
- [INFERENSI] Hindari redirect berbasis IP atau geolokasi di edge Vercel maupun di aplikasi. Karena Googlebot tidak mengirim `Accept-Language`, logika `getPreferredLanguage(['id','en'])` dengan sendirinya mengembalikan `id` untuk crawler.

### Gaps
- Dampak nyata ke peringkat (Search Console) belum bisa diukur karena belum ada data.
- Keputusan opsi A atau B termasuk keputusan produk dan SEO yang PERLU KLARIFIKASI pemilik.

---

## 5. Menerjemahkan konten basis data: field mana, aturan fallback, dan state "belum diterjemahkan" yang jujur

### Takeaway
Yang diterjemahkan hanya teks deskriptif buatan manusia, oleh manusia. Nama diri tidak diterjemahkan. Teks status dan pengumuman resmi tidak pernah diganti: aslinya dalam bahasa Indonesia ditampilkan sebagai teks otoritatif, dengan terjemahan tidak resmi hasil tinjauan manusia sebagai pendamping opsional. Deskripsi cuaca memakai `weather_desc_en` dari BMKG sendiri. Bila terjemahan belum ada atau sudah basi, tampilkan teks asli Indonesia dengan label yang terlihat dan atribut `lang="id"`.

### Cited Findings
- WCAG 2.2 SC 3.1.2 *Language of Parts* (AA): bahasa setiap bagian teks harus dapat ditentukan secara programatis, kecuali nama diri, istilah teknis, kata dengan bahasa tak tentu, dan kata serapan. Tujuannya agar pembaca layar memakai aturan pelafalan yang benar. SC 3.1.1 (A) menetapkan bahasa dasar halaman. — [W3C: Understanding SC 3.1.2](https://www.w3.org/WAI/WCAG22/Understanding/language-of-parts.html)
- spatie/laravel-translatable menyediakan fallback yang bisa diatur, termasuk `missingKeyCallback` dan `$useFallbackLocale = false`. — [Spatie docs](https://spatie.be/docs/laravel-translatable/v6/basic-usage/handling-missing-translations)
- BMKG menyediakan `weather_desc_en` resmi dan mewajibkan atribusi BMKG. — [BMKG Data Terbuka](https://data.bmkg.go.id/prakiraan-cuaca/)
- Google menganggap halaman berbahasa lain sebagai duplikat bila konten utamanya tetap dalam bahasa yang sama. — [Google: Canonicalization](https://developers.google.com/search/docs/crawling-indexing/canonicalization)
- Hierarki otoritas OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI. AI tidak boleh menimpa status resmi (R-009, R-011), dan data yang hilang bukan nilai rendah (R-007). — [.claude/rules/mountain-core.md](file:///E:/praktik_aplikasi_web/.claude/rules/mountain-core.md)
- Skema saat ini: tabel `mountains`, `trails`, `trail_segments`, `official_statuses`, dan `trail_condition_reports` (laporan komunitas). Model `WeatherSnapshot` menyimpan `weather_description`. — [database/migrations](file:///E:/praktik_aplikasi_web/database/migrations); [app/Models/WeatherSnapshot.php:17](file:///E:/praktik_aplikasi_web/app/Models/WeatherSnapshot.php)

### Inferences
- [REKOMENDASI] Klasifikasi field (perlu dicocokkan dengan kolom sebenarnya, karena migrasi belum dibaca per kolom):

| Kelas | Contoh | Perlakuan di `en` |
|---|---|---|
| Nama diri | nama gunung, jalur, pos, desa, basecamp, danau (Segara Anak, Plawangan) | Tidak diterjemahkan. Eksonim Inggris yang lazim (misalnya "Mount Rinjani") boleh tampil sebagai pendamping, bukan pengganti slug atau nama |
| Deskriptif buatan tim | deskripsi jalur, catatan akses dan logistik, sumber air, isi `/panduan` | Diterjemahkan manusia dan ditinjau (§6). Bila belum tersedia, tampilkan teks Indonesia dengan label |
| Otoritatif resmi | catatan status resmi, aturan izin, SOP, kutipan pengumuman Balai | **Teks asli Indonesia selalu tampil** dengan `lang="id"`. Terjemahan manusia opsional berlabel "Unofficial translation — the Indonesian original is authoritative" |
| Nilai enum aplikasi | OPEN/RESTRICTED/CLOSED/UNKNOWN, label fit, readiness | Diterjemahkan lewat glosarium tetap (§6), karena nilainya dikendalikan aplikasi |
| Terjemahan dari sumber | deskripsi cuaca BMKG | Pakai `weather_desc_en`. Bila tidak ada, tampilkan `weather_desc` dengan `lang="id"` dan jangan diterjemahkan sendiri |
| Buatan pengguna | laporan kondisi komunitas | Tampil dalam bahasa aslinya dengan atribut `lang`. Tanpa terjemahan mesin di MVP |

- [REKOMENDASI] **Aturan fallback.** Hanya ada satu fallback, yaitu `id`, tanpa `fallbackAny`. Fallback harus terlihat dan tidak boleh diam-diam. Contoh UI dalam bahasa Inggris: blok teks bertanda `lang="id"` dengan label "Not yet available in English — showing the Indonesian original." Di spatie, `missingKeyCallback` bisa dipakai untuk mencatat metrik cakupan terjemahan.
- [REKOMENDASI] **Terjemahan basi harus terdeteksi.** Simpan `source_hash` atau `source_updated_at` pada setiap terjemahan. Bila teks Indonesia berubah setelah diterjemahkan, perlakukan terjemahan sebagai belum ada, tampilkan aslinya dengan label, dan antrekan tinjauan ulang. Terjemahan catatan keselamatan yang sudah basi lebih berbahaya daripada teks aslinya.
- [INFERENSI] Terjemahan mesin adalah keluaran AI. Menurut R-011 keluaran itu tidak boleh tampil sebagai pengganti teks resmi, jadi terjemahan mesin untuk catatan status resmi dilarang.
- [REKOMENDASI] Ketersediaan terjemahan **tidak boleh** memengaruhi kelayakan rekomendasi atau publikasi (R-008, R-022). "Belum ada deskripsi bahasa Inggris" bukan "data kritis hilang" (R-007). Filter rekomendasi tidak boleh membaca kolom terjemahan.
- [INFERENSI] Query JSON/jsonb per locale (misalnya `where('description->en', …)`) dikompilasi berbeda antara SQLite dan PostgreSQL, sehingga wajib diuji di PostgreSQL (R-024).

### Gaps
- Kolom teks aktual di `trails`, `mountains`, dan `official_statuses` belum dibaca per kolom, jadi klasifikasi di atas perlu dicocokkan dengan skema nyata.
- Belum diketahui apakah Balai TNGR atau TNBTS menerbitkan versi Inggris resmi untuk SOP atau pengumuman mereka (UNVERIFIED). Bila ada, versi itu bisa dipakai sebagai "official English" yang bersumber.

---

## 6. Terjemahan kritis-keselamatan: glosarium, alur QA, larangan terjemahan mesin, dan wording resmi

### Takeaway
Tetapkan glosarium Inggris yang tidak boleh diubah beserta daftar istilah terlarang. OPEN diterjemahkan "Open" dengan penanda status operasional dan tidak pernah "safe"; readiness tidak pernah "cleared" atau "fit to climb". Istilah resmi Indonesia (level gunung api, teks status Balai) tetap ditampilkan verbatim. Alur terjemahan memakai dua orang (penerjemah dan peninjau, sejalan dengan ISO 17100) ditambah back-translation untuk string tingkat keselamatan, dan terjemahan mesin tidak dipakai untuk konten keselamatan.

### Cited Findings
- **Bukti bahaya terjemahan mesin.** Google Translate menerjemahkan 100 instruksi pulang IGD dengan benar 92% untuk bahasa Spanyol dan 81% untuk bahasa Mandarin, dinilai lewat back-translation oleh penerjemah manusia. Namun **2% (Spanyol) dan 8% (Mandarin) berpotensi menimbulkan bahaya klinis signifikan**. — [Khoong et al., JAMA Internal Medicine 2019 (PubMed)](https://pubmed.ncbi.nlm.nih.gov/30801626/); [JAMA Network](https://jamanetwork.com/journals/jamainternalmedicine/fullarticle/2725080)
  - Rekomendasi penulisnya bila MT tetap dipakai: pendekkan kalimat, hindari jargon, dan periksa ejaan (cuplikan). — [ScienceDaily](https://www.sciencedaily.com/releases/2019/02/190225123451.htm)
- **ISO 17100.** Standar ini mewajibkan *revision* oleh orang kedua yang bukan penerjemah dan berkompetensi setara di bahasa sumber, bahasa sasaran, serta bidangnya. Pemeriksaan sendiri oleh penerjemah tidak menggantikan revisi ini (sumber sekunder; teks standarnya berbayar). — [Wikipedia: ISO 17100](https://en.wikipedia.org/wiki/ISO_17100); [TÜV SÜD](https://www.tuvsud.com/en-us/services/auditing-and-system-certification/iso-17100)
- **Definisi resmi level gunung api di MAGMA (PVMBG/ESDM), hanya tersedia dalam bahasa Indonesia:**
  - Level I Normal: "…tidak memperlihatkan peningkatan aktivitas yang signifikan".
  - Level II Waspada: "…mulai memperlihatkan peningkatan aktivitas. Pada beberapa gunung api dapat terjadi erupsi".
  - Level III Siaga: "…peningkatan aktivitas yang semakin nyata atau gunung api mengalami erupsi".
  - Level IV Awas: dirumuskan serupa dengan Siaga.
  - Halaman ini tidak memuat istilah bahasa Inggris. — [MAGMA: Tingkat Aktivitas](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas)
- Padanan Inggris untuk level itu **tidak seragam** antarsumber. Salah satu sumber memakai "Waspada (Alert), Siaga (Standby), Awas (Highest Alert)" (cuplikan). — [Asia News Network](https://asianews.network/between-livelihoods-and-lives-why-indonesias-volcano-safety-measures-are-uneven/)
- BMKG menyediakan deskripsi cuaca bahasa Inggris resmi (`weather_desc_en`). — [BMKG Data Terbuka](https://data.bmkg.go.id/prakiraan-cuaca/)
- Menerjemahkan secara otomatis dalam skala besar dengan nilai kecil termasuk *scaled content abuse*. — [Google Spam policies](https://developers.google.com/search/docs/essentials/spam-policies)
- Kontrak produk yang relevan:
  - R-004: label fit publik hanya COCOK / PERLU PERSIAPAN / KURANG COCOK.
  - R-005: tanpa presisi palsu.
  - R-006: UNKNOWN ≠ LOW.
  - R-012: OPEN ≠ SAFE.
  - R-014: readiness bukan izin medis.
  - Sumber: [.claude/rules/mountain-core.md](file:///E:/praktik_aplikasi_web/.claude/rules/mountain-core.md)
- UU 24/2009 Pasal 38: bahasa asing hanya "menyertai" Bahasa Indonesia. — [peraturan.info](https://peraturan.info/uu/2009/24/pasal-38)

### Inferences
- [REKOMENDASI] **Glosarium usulan** (label ID diambil dari enum di repo; semua label Inggris publik perlu persetujuan produk dan governance record PRD §130/§146 karena R-004):

| Kode | Label ID saat ini | Usulan EN | EN terlarang | Catatan makna |
|---|---|---|---|---|
| `OPEN` | Buka | **Open** + "per [pengelola], [tanggal]" | Safe, All clear, Good to go, Accessible | Status operasional, bukan jaminan. Microcopy: "Open means the managing authority allows climbing. It does not mean conditions are safe." (R-012) |
| `RESTRICTED` | Dibatasi | **Restricted** | Partially open, Limited, Caution | Selalu tampilkan detail pembatasan dari sumber resmi |
| `CLOSED` | Tutup | **Closed** | Temporarily unavailable, Paused, Not available | Jangan dilunakkan |
| `UNKNOWN` (status) | Belum diketahui | **Unknown — no official status on record** | N/A, "–", None, Closed | R-006: tidak boleh dibaca sebagai tutup atau rendah |
| `COCOK` | Cocok | **Good fit** (alt.: Suitable) | Safe for you, Recommended, Easy, Perfect match, persentase apa pun | Kesesuaian profil dengan rute (R-003, R-005) |
| `PERLU_PERSIAPAN` (fit) | Perlu persiapan | **Needs preparation** | Risky, Moderate, Caution | Key terpisah dari readiness walau teksnya sama |
| `KURANG_COCOK` | Kurang cocok | **Poor fit** (alt.: Not a good fit) | Unsafe, Dangerous, Forbidden, Not allowed | Ketidakcocokan, bukan larangan |
| `READY` | Siap | **Ready (based on your checklist)** | Cleared, Fit to climb, Safe to go, Healthy, Approved | R-014. Wajib ada kalimat "Not a medical or safety clearance." |
| `NEEDS_PREPARATION` (readiness) | Perlu persiapan | **Needs preparation** | — | Key sendiri |
| `NOT_RECOMMENDED` | Tidak direkomendasikan | **Not recommended** | Forbidden, Banned, Not allowed, Unsafe | Bukan larangan resmi; larangan hanya berasal dari status resmi |
| Level gunung api | Level II (Waspada), dst. | **"Level II – Waspada"** + gloss dari glosarium tetap + tautan MAGMA | Satu kata Inggris tanpa istilah asli | Padanan Inggris tidak baku, jadi istilah resmi tetap ditampilkan |
| Ketinggian | mdpl | **m a.s.l.** | — | Format angka per locale (§2) |
| Zona waktu | WIB/WITA/WIT | **WITA (UTC+8)**, dst. | Nama zona asing (CST, dll.) | Keppres 41/1987 |
| Cuaca | `weather_desc` | `weather_desc_en` dari BMKG + "Source: BMKG, issued …, reference area …" | Terjemahan buatan sendiri | R-013 |

- [INFERENSI] Kata "fit" dalam bahasa Inggris juga berarti "bugar", sehingga "Good fit" bisa terbaca "Anda bugar". Konteks label "Route fit: …" mengurangi risiko itu, tetapi ambiguitasnya perlu diuji pada penutur asli sebelum label ditetapkan (PERLU KLARIFIKASI).
- [REKOMENDASI] **Alur QA terjemahan:**
  1. Bekukan teks sumber, lalu siapkan termbase (glosarium di atas), daftar do-not-translate, dan catatan konteks per key (lokasi tampil, tangkapan layar, batas karakter).
  2. Penerjemah manusia berbahasa ibu Inggris yang paham pendakian mengerjakan terjemahan.
  3. Revisi oleh orang kedua (ISO 17100).
  4. Back-translation untuk tingkat keselamatan (status, fit, readiness, peringatan, disclaimer, cuaca, izin), lalu bandingkan maknanya dengan sumber. Metode ini sama dengan yang dipakai Khoong dkk.
  5. Sign-off pemilik produk dan governance record untuk label publik.
  6. Pemeriksaan otomatis (§7).
  7. Setiap perubahan teks sumber membuat terjemahan berstatus basi dan kembali ke fallback Indonesia.
- [REKOMENDASI] **Kebijakan terjemahan mesin.**
  - Dilarang untuk tingkat keselamatan dan untuk teks resmi.
  - Untuk UI non-keselamatan, MT hanya boleh menjadi draf penerjemah yang kemudian direvisi penuh. Pascasunting MT diatur standar ISO 18587 (hanya diketahui dari judul halaman TÜV SÜD, UNVERIFIED).
  - Tidak ada tombol "translate" otomatis untuk laporan komunitas di MVP.
- [INFERENSI] "Aman/safe" dan "dijamin/guaranteed" masuk daftar terlarang di **kedua** bahasa. Disclaimer yang justru menyangkal keamanan ("bukan jaminan aman" / "not a safety guarantee") didaftarkan sebagai allowlist per key.

### Gaps
- Tidak ditemukan terjemahan Inggris resmi dari PVMBG untuk level gunung api, maupun dari Balai TN untuk istilah status jalur. Wording resmi berbahasa Inggris dari otoritas Indonesia untuk domain ini belum terverifikasi.
- Teks standar ISO 17100 dan ISO 18587 tidak dibaca langsung (berbayar).
- Belum ada uji pemahaman label Inggris dengan pendaki asing. Jangan mengarang hasilnya (R-023).

---

## 7. Pengujian: key tidak hilang, pergantian locale, dan RTL

### Takeaway
Penjaga utamanya adalah test **paritas key dan placeholder** antarlocale. Untuk short key, handler key hilang di Laravel tidak terpicu bila fallback menemukan nilai, sedangkan key JSON tidak pernah jatuh ke JSON locale fallback (terbukti lewat uji runtime lokal). Tambahkan pemindaian istilah terlarang, test HTTP dan Livewire per locale (`<html lang>`, timbal balik hreflang, alternates di sitemap, tidak ada redirect berbasis `Accept-Language`, locale tidak valid ditolak), serta test PostgreSQL untuk query terjemahan jsonb. RTL tidak dibutuhkan.

### Cited Findings
- Handler key hilang baru dipanggil setelah rantai locale dan fallback gagal seluruhnya. JSON hanya dicari di locale aktif. Uji runtime tinker lokal (2026-09-23) mengonfirmasi dua hal: short key yang ada di fallback tidak memicu handler, sedangkan key JSON yang hanya ada di JSON fallback memicu handler dan dikembalikan apa adanya. — [Translator.php:151-190](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/Translator.php)
- Handler juga terpicu saat validasi. — [jcergolj](https://jcergolj.me.uk/blog/avoiding-unnecessary-missing-translation-warnings/)
- Key yang hilang dikembalikan sebagai key itu sendiri. — [Laravel 13.x Localization](https://laravel.com/docs/13.x/localization)
- Google mengabaikan hreflang yang tidak timbal balik, dan Googlebot tidak mengirim `Accept-Language`. — [Google: Localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions); [Google: Managing multi-regional sites](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites)
- `SupportLocales` mengunci locale komponen lewat memo. — [SupportLocales.php](file:///E:/praktik_aplikasi_web/vendor/livewire/livewire/src/Features/SupportLocales/SupportLocales.php)
- Test SQLite yang hijau tidak membuktikan perilaku PostgreSQL (R-024). — [CLAUDE.md §5](file:///E:/praktik_aplikasi_web/CLAUDE.md)
- SC 3.1.1 dan 3.1.2 menuntut bahasa halaman dan bagian teks dapat ditentukan secara programatis. — [W3C](https://www.w3.org/WAI/WCAG22/Understanding/language-of-parts.html)
- Plural `id` selalu memakai indeks 0, dan string satu segmen tetap valid untuk `trans_choice`. — [MessageSelector.php](file:///E:/praktik_aplikasi_web/vendor/laravel/framework/src/Illuminate/Translation/MessageSelector.php)

### Inferences
- [REKOMENDASI] Test otomatis (PHPUnit, sesuai konvensi repo):
  1. **Paritas key.** Setiap key rekursif di `lang/id/*.php` ada di `lang/en/*.php` dan sebaliknya. Setiap key di `en.json` punya pasangan sumber. Setiap literal `__('grup.key')` hasil pemindaian `app/` dan `resources/views` ada di kedua locale.
  2. **Paritas placeholder.** Himpunan `:name` dan `:count` sama antarlocale. String Inggris yang dipakai `trans_choice` punya dua bentuk, sedangkan bahasa Indonesia boleh satu.
  3. **Enum lengkap.** Iterasi `cases()` pada `OfficialStatusValue`, `RouteFitLabel`, `ReadinessState`, dan `FreshnessState`. Setiap kasus harus punya label dan deskripsi di semua locale, dan key fit harus berbeda dari key readiness.
  4. **Istilah terlarang.** Regex `\b(safe|guaranteed|cleared)\b` untuk `en` dan `\b(aman|dijamin)\b` untuk `id`, dengan allowlist key disclaimer.
  5. **Handler key hilang dalam mode test.** Pasang `Lang::handleMissingKeysUsing()` yang melempar exception di environment `testing`, dengan menyaring `validation.custom.*` dan `validation.attributes.*`. Handler ini efektif untuk key JSON, tetapi tidak menangkap short key yang terisi dari fallback. Karena itu handler hanya jaring kedua, dan paritas tetap jaring pertama.
  6. **Routing locale.** `/en/...` menghasilkan 200 dengan `<html lang="en">`. URL `id` menghasilkan `lang="id"`. Segmen locale yang tidak dikenal menghasilkan 404. `Accept-Language: en` pada URL `id` menghasilkan `assertOk()`, bukan redirect. Request tanpa `Accept-Language` menghasilkan `id`. Nilai `users.locale` di luar allowlist ditolak validasi (mass assignment dan tanpa kepercayaan pada input).
  7. **Timbal balik hreflang.** Untuk setiap halaman ber-hreflang: ada self-reference, semua target merespons 200 dan menautkan balik, dan `x-default` ada. Halaman `en` yang belum diterjemahkan harus `noindex`, ber-canonical ke `id`, dan tidak muncul di sitemap.
  8. **Sitemap.** Namespace `xhtml` ada, dan `xhtml:link` hanya muncul untuk pasangan yang lengkap.
  9. **Livewire.** Komponen yang dirender di route `en`, kemudian `->call()` aksi, tetap berbahasa Inggris. Pemilih bahasa menghasilkan navigasi penuh, bukan `wire:navigate`.
  10. **Format.** Elevasi 3726 menjadi "3.726" untuk `id` dan "3,726" untuk `en`. Label waktu `en` memuat offset UTC. Blok fallback memakai `lang="id"`.
  11. **PostgreSQL.** Query dan index terjemahan jsonb, beserta migrasi kolom `locale` atau tabel terjemahan, diuji di PostgreSQL (R-024).
  12. **Terjemahan basi.** Mengubah teks sumber Indonesia membuat terjemahan tampil sebagai belum tersedia.
- [INFERENSI] **RTL tidak dibutuhkan.** Semua kandidat bahasa (en, zh-Hans, fr, ms, de, nl) ditulis kiri-ke-kanan, jadi atribut `dir` dan CSS logical properties tidak perlu dikerjakan sekarang. Aksara Mandarin membutuhkan pemeriksaan font dan pemenggalan baris bila zh-Hans dipilih kelak (UNVERIFIED, tidak diriset).
- [REKOMENDASI] Pemeriksaan manual yang tidak bisa diotomatiskan: uji dengan pembaca layar pada blok campuran bahasa (sesuai `docs/PROTOKOL-UJI-KEGUNAAN.md`), pengecekan panjang teks dan reflow seluler di 320 px untuk kedua locale, serta tinjauan penutur asli untuk glosarium §6.

### Gaps
- Belum ada alat ekstraksi atau pemindai string Blade yang sudah diverifikasi untuk Laravel 13 dan Volt. Pemindaian regex sederhana di test memiliki false negative untuk key dinamis seperti `__('recommendation.'.$rule)`, sehingga key dinamis perlu diuji lewat daftar nilai sumbernya (misalnya konstanta aturan di RouteFitService).
- Perilaku `NotificationFake` terhadap locale preferensi pengguna tidak diverifikasi di dokumentasi 13.x.
