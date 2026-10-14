# Serah-terima Konteks — Aplikasi Web Gunung

Dokumen ini ditulis supaya percakapan dapat dilanjutkan di terminal baru **tanpa mengulang
dari nol**. Isinya tujuan, kesepakatan, keadaan kode, dan semua keputusan yang sudah
diambil beserta alasannya.

Terakhir diperbarui: 15 Oktober 2026 (bagian 0, blok paling atas: situs Vercel tampil dan aman sesudah `df49705`, tetapi
belum terhubung ke Supabase sampai pemilik mengisi ulang variabel Vercel; W7 sampai W9 ditunda pemilik untuk evaluasi).
Bagian 1–10 berasal dari 21 September 2026 dan akan ditulis ulang penuh di Tugas 13 rencana konsolidasi.

---

## 0. Titik lanjut terkini (tonggak M1, 26 September 2026)

**Arahan pemilik 26 September 2026 malam (verbatim), berlaku sampai W9 selesai:** "ya, selesaikan semuanya baru minta
izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua
tugas anda telah selesai. lanjutkan!" Artinya: W1 sampai W9 dikerjakan berurutan tanpa meminta izin di tengah; tindakan
yang butuh izin (commit, push, Supabase, migrasi, dependensi baru, menjalankan `memount:demo`) dikumpulkan untuk satu
permintaan di akhir. Ledger tiap gelombang ada di `.superpowers/sdd/2026-09-26-memount-v3-w<N>-*/progress.md`
(diabaikan git). Sesudah tiga commit M1 (`40c78d8`, `60400ce`, `ddc27e8`), pohon kerja kembali memuat perubahan
yang belum di-commit sampai permintaan izin akhir. Pada 11 Oktober 2026, atas permintaan pemilik, W1 sampai W6
di-commit sebagai enam commit per gelombang (`857a7a2` sampai `3266069`), masing-masing dengan tree akhir gelombangnya,
di branch `hardening`. Push belum dilakukan dan tetap butuh izin (R-018).

**Arahan pemilik 11 Oktober 2026 (verbatim), menggantikan urutan sampai W9:** "segera selesaikan w6, jika sudah selesai saya minta berhenti terlebih dahulu saya akan mengadakan evaluasi besar"an. tunda pengerjaan w7-w9. lakukan integrasi ke server local utama karena saya butuh inspection untuk. disertai penjelasan hasil kinerja anda apa saja yang sudah anda kerjakan pastikan saya dapat melihat hasil kinerja anda. baru setelah itu saya tentukan langkah selanjutnya." Artinya: sesudah W6 tidak ada gelombang baru sampai pemilik memutuskan; permintaan izin akhir diajukan bersama laporan W1 sampai W6.

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu `docs/AGENTS.md` (kesepakatan kerja tim) dan rencana
> induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md`. Periksa keadaan nyata dulu: `git status`,
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

### 13 sampai 15 Oktober 2026: situs tayang kosong, mode debug di produksi, dan penjaga di image

Blok ini melanjutkan blok deploy di bawahnya. Pemilik mengizinkan push ("saya izinkan"), mengimpor proyek ke Vercel,
lalu melaporkan `https://memount-zeta.vercel.app` kosong dan meminta "ricek database, postgres nya error cek
keseluruhan".

| Commit | Isi |
|---|---|
| `7a27211` | Proxy tepercaya dibaca dari `config('app.trusted_proxies')` dan `URL::forceHttps()` di produksi. Premis di pesan commit-nya, bahwa `env()` kosong di Vercel sesudah `config:cache`, tidak terbukti (lihat diagnosis); perubahan kodenya tetap benar |
| `df49705` | `docker/start.sh` memaksa `APP_ENV=production` dan `APP_DEBUG=false` sebelum `config:cache`, mencatat nilai yang diabaikan di log runtime, dan memberi bawaan `TRUSTED_PROXIES=*` serta `LOG_CHANNEL=stderr`. Job CI `image` mendapat langkah "Jalankan image dengan variabel keliru"; `CiWorkflowsTest` menjaganya. Komentar yang memuat premis keliru diperbaiki |

- **Basis data Supabase.** Dengan izin pemilik ("diizinkan"), 14 migrasi tertunda diterapkan dari mesin lokal lewat
  koneksi `pgsql`, sesudah koneksinya dipastikan menunjuk Supabase dan bukan SQLite demo bawaan lokal: 65 dari 65
  tercatat, RLS menyala tanpa policy sesuai rancangan, tanpa hak `anon`. Galat `schema "pg_pgrst_no_exposed_schemas"
  does not exist` di log Supabase datang dari PostgREST saat Data API dimatikan, bukan dari aplikasi.
- **Diagnosis, bukti dari luar terhadap deploy yang tayang.**
  - Yang tayang `7a27211`: header `x-vercel-deployment-url` sama dengan `environment_url` deployment GitHub 7034645019.
  - `APP_DEBUG=true`: `PUT /login` menjawab 405 dengan halaman galat Laravel lengkap (jejak, header, routing, kueri).
    Header yang ditampilkan termasuk `x-vercel-oidc-token`. Tidak ada nilai lingkungan di halaman itu: 0 kecocokan untuk
    URL basis data, `APP_KEY`, `base64:`, `CRON_SECRET`, `DEVICE_KEY_SECRET`, dan `SUPABASE_STORAGE`.
  - `APP_ENV=local`: `GET /dev/primitif` menjawab 302 ke `/login`, padahal rute itu hanya terdaftar di `local`.
  - `TRUSTED_PROXIES` kosong: Vercel meneruskan `x-forwarded-proto: https` dan `x-forwarded-host`, tetapi URL aplikasi
    tetap `http://`. Dengan `*`, job CI membuktikan URL yang sama menjadi https.
  - `SESSION_SECURE_COOKIE=true` terisi (cookie sesi `secure`), jadi variabel Vercel campuran blok README dan nilai lain.
  - Akar: nilai variabel di Vercel, bukan kode. Dugaan yang belum terbukti: blok README ada di dalam daftar bernomor,
    sehingga berkas mentahnya berindentasi tiga spasi; salinan mentah memberi nama variabel berawalan spasi, cocok dengan
    galat "contains invalid characters" yang dilihat pemilik.
  - Koreksi premis `7a27211`: sesudah `config:cache` berkas `.env` tidak dimuat, tetapi `env()` tetap membaca variabel
    sistem (dokumentasi Laravel), dan variabel Vercel adalah variabel sistem.
- **Rotasi rahasia tidak diperlukan menurut bukti.** Halaman galat Laravel 13 tidak menampilkan env atau config, dan
  `php.ini-production` di image menyetel `zend.exception_ignore_args=On`, jadi argumen fungsi tidak ikut di jejak. Token
  OIDC Vercel berumur pendek dan proyek ini tidak memakai federasi OIDC apa pun.
- **Bukti perbaikan.**
  - Suite Unit dan Feature lokal: 2.663 test, 2.661 lulus, 2 dilewati, 18.461 asersi; Pint bersih.
  - Uji mutasi: menghapus baris `export APP_ENV=production APP_DEBUG=false` membuat
    `test_the_production_image_ignores_debug_and_non_production_settings` merah.
  - Simulasi `start.sh` dengan tiga set variabel (keliru seperti di Vercel, blok README, dan kosong dengan
    `LOG_CHANNEL=stack`) menghasilkan production tanpa debug, proxy `*`, dan kanal log yang diharapkan.
  - CI hijau untuk `df49705`: Test run 38609572114 (job test dan image, termasuk langkah "Jalankan image dengan variabel
    keliru") dan Security run 38609571889. `main` di-fast-forward ke SHA yang sama sesudahnya.
  - Peramban sebelum perbaikan: halaman hanya menampilkan tautan lewati, dan 11 sumber (skrip dan CSS) diblokir CSP
    karena dimuat lewat `http://` dari halaman `https`.
  - Situs sesudah deploy: pada 15 Oktober (sekitar 00.55 WIB) `/dev/primitif` 404, `PUT /login` 405 tanpa
    jejak, dan 18 dari 18 aset `https`. Di peramban beranda tampil penuh dengan 0 galat dan 0 peringatan di konsol.
- **Masih rusak: aplikasi di Vercel tidak memakai basis data Supabase** (bukti 15 Oktober).
  - `/api/mountains`, `/api/trails`, `/api/checkpoints`, dan `/sitemap.xml` menjawab 500.
  - Supabase tidak menerima tulisan apa pun dari situs: sesi terbaru 28 September, baris cache terbaru 26 September,
    akun terbaru dibuat 20 September dan terakhir diubah 12 Oktober saat akun contoh dikunci, dan `scheduled_task_runs`
    kosong. Padahal situs memasang cookie sesi yang bertahan antarpermintaan (token CSRF sama di
    dua permintaan), dan Vercel Cron semestinya sudah memanggil sekitar tujuh kali sejak 13 Oktober.
  - Log Postgres Supabase tidak memuat galat dari aplikasi; log pooler Supavisor tidak tersedia lewat MCP.
  - Bukan ekstensi PHP yang hilang: kode aplikasi hanya memakai fungsi `mb_*`.
  - Dugaan terkuat, belum terbukti: `DB_URL` di Vercel kosong atau bukan URI Session pooler, dan `SESSION_DRIVER` bukan
    `database`. Bukti penentunya ada di log runtime Vercel atau daftar nama variabel Vercel, yang hanya terbaca pemilik.
- **Langkah pemilik, mendesak untuk basis data.** Kosongkan variabel Production di Vercel, lalu isi ulang persis seperti
  blok README, dengan `DB_URL` dari Supabase Connect > Session pooler. `APP_KEY` baru aman: `app/` tidak memakai cast
  `encrypted` maupun `Crypt::`, dan Supabase tidak memuat tulisan apa pun dari situs Vercel. Sesudah Redeploy, agen
  memeriksa API 200, sesi tercatat di Supabase, dan jalannya cron. Langkah 1 README (bucket foto dan kunci S3) dan
  lima pemeriksaan K27 tetap berlaku.
- **Terbuka (baru).**
  - Job CI `image` belum memanggil endpoint publik yang membaca data (`/api/mountains`, `/sitemap.xml`), jadi kegagalan
    baca data di image tidak tertangkap CI.
  - `LOG_CHANNEL=stack` dari `.env.example` menulis log ke berkas di dalam kontainer, yang tidak terbaca di Vercel;
    `start.sh` hanya memberi bawaan `stderr` bila variabel itu kosong.

### 13 Oktober 2026: deploy ke Vercel (paket Hobby, kontainer FrankenPHP)

Blok ini menggantikan butir "Deploy inspeksi tim" dan dua butir pertama "Keputusan pemilik yang masih terbuka" (Render,
dan workflow migrasi yang disebut opsional) di blok 12 Oktober di bawahnya. Render meminta kartu dan pemilik menolak jalur
tunnel dari laptop, lalu meminta "ya, deploy ke vercel". Jalurnya *container-backed Vercel Function*, yang sudah
direkomendasikan PRD §154 (DK-12), jadi platform tidak butuh governance record baru. Riset 12 Oktober yang menyingkirkan
Vercel keliru karena hanya menilai runtime komunitas `vercel-php`.

| Commit | Isi |
|---|---|
| `b1980d1` | Foto laporan dibersihkan EXIF sebelum disimpan. Alur lama membuang setiap foto di disk S3 (Supabase) dan sempat menyimpan foto asli berkoordinat (PRD §82). Formulir menolak foto di atas 4 MB sebelum mengirim (batas body Vercel 4,5 MB), galat 413 lain mendapat pesan bahasa Indonesia, dan `imagedestroy` dibuang. Commit ini juga memuat penghapusan `render.yaml` yang sudah ter-stage lebih dulu, sehingga sendirian ia membuat `CiWorkflowsTest` merah; keadaan akhir `f7aa0d7` hijau |
| `f7aa0d7` | `vercel.json` (kontainer dari `Dockerfile`, region `sin1`, deploy otomatis hanya `main`, tujuh Vercel Cron harian), `CronController` di `GET /api/cron/{tugas}`, `PENJADWAL_HANYA_HARIAN`, `start.sh` tanpa migrasi dan `schedule:work`, job CI `image` dengan langkah migrasi dan uji cron, `KunciCronTest`, dan langkah pemilik di README |

- **Bukti.**
  - Suite penuh 2.714 test: 2.658 lulus, 56 dilewati (54 Spatial tanpa DSN dan 2 lama), 0 gagal. Pint, `tsc`, dan build
    bersih.
  - Uji mutasi, semuanya merah saat dimutasi: alur foto lama, pemeriksaan rahasia, daftar putih tugas, jam cron, zona
    jadwal, susulan pengingat, migrasi di `start.sh`, pelepasan kunci di jalur galat, gagal simpan foto, dan penjaga
    ukuran di formulir.
  - Rute cron diuji lewat HTTP sungguhan (SAPI cli-server) pada salinan data demo: tanpa rahasia 401, rahasia salah 401,
    `migrate` 404, ketiga tugas 200 dan tercatat, tanpa cookie sesi. `weather:refresh` tidak memanggil BMKG karena data
    demo tanpa area referensi cuaca.
  - Peramban (salinan data demo): berkas 5 MB ditolak di formulir laporan tanpa permintaan terkirim, dan galatnya
    diumumkan lewat `aria-live` dengan `aria-invalid`; foto 200 KB diterima.
  - Tinjauan independen (memount-peninjau): 0 KRITIS, 4 PENTING, 5 MINOR. Tinjauan ulang menyatakan semuanya tertutup
    dan menemukan 5 MINOR baru, yang ikut ditutup sebelum commit.
  - Belum terbukti: job CI `image` (port 80 sebagai www-data, migrasi, cron 401 lalu 200) dan job Spatial
    (`KunciCronTest`) untuk commit ini; perilaku Vercel sesudah deploy (region service, cron lewat rewrite, preset
    framework saat impor, batas 50 image VCR, tombol Run cron mengirim `CRON_SECRET`).
- **Keputusan teknis (dalam batas DK-12 dan §154).**
  - Kontainer tetap `www-data` di port 80, karena image dasar FrankenPHP memberi binarinya `cap_net_bind_service`.
  - Migrasi tidak berjalan saat kontainer mulai. Skema Supabase diperbarui lewat workflow "Migrasi Supabase" sebelum
    commit yang membutuhkannya dibawa ke `main`; urutan itu aman selama migrasinya aditif (kebijakan `database/README.md`).
  - Vercel men-deploy setiap push ke `main` tanpa menunggu CI, jadi `main` hanya menerima commit yang sudah hijau di
    cabang kerja (fast-forward). Preview cabang lain dimatikan (`git.deploymentEnabled`).
  - Hobby hanya mengizinkan cron harian. Pengingat dipicu pada jam jatuh tempo (22.00, 23.00, 00.00 UTC) ditambah
    susulan 02.00 UTC, dan halaman kesehatan memakai toleransi 23 jam bila `PENJADWAL_HANYA_HARIAN=true`. Server dengan
    `schedule:work` tetap memakai jadwal tiap jam di `routes/console.php`.
- **Langkah pemilik, berurutan** (rincian di README bagian "Deploy untuk inspeksi tim"):
  1. izinkan push `hardening`, lalu fast-forward `main` sesudah CI hijau;
  2. GitHub: rahasia `SUPABASE_DB_URL`, lalu workflow "Migrasi Supabase" (pratinjau, lalu terapkan; 14 migrasi tertunda
     per 12 Oktober);
  3. Supabase: bucket privat foto dan kunci S3; disarankan mematikan Data API (lint `spatial_ref_sys`);
  4. Vercel: impor repo, isi variabel lingkungan untuk Production saja (termasuk `APP_KEY`, `DEVICE_KEY_SECRET`, dan
     `CRON_SECRET` baru), lalu deploy;
  5. jalankan lima pemeriksaan K27 di README sebelum membagikan alamat.
- **Terbuka (baru).**
  - Teks jadwal di halaman Kesehatan Sistem menyebut WIB, padahal `routes/console.php` berjalan dalam UTC: cuaca
    sebenarnya pukul 13.00 dan 01.00 WIB, kesegaran pukul 14.00 WIB. Ini pra-ada; pemilik memutuskan mana yang dimaksud.
  - `MAIL_MAILER=log` menulis isi surel, termasuk tautan atur ulang sandi, ke log runtime Vercel (disimpan satu jam,
    hanya terbaca pemilik). Sebelum dipakai publik, butuh penyedia surel sungguhan.
  - Gerbang CI yang ditegakkan platform: branch protection `main` dengan status check `test` dan `image`, atau Deploy
    Hook dari workflow (ketersediaan Deployment Checks di Hobby belum pasti).
  - Verifikasi sebelum produksi kini berlangsung di Production sebelum alamat dibagikan, bukan di lingkungan preview
    seperti bunyi amandemen Vercel di PRD; produksi publik butuh preview atau staging (keputusan pemilik).
  - Pesan 413 global memakai `window.alert`; sebaiknya diganti toast kerangka (`useKerangka`).
  - Durasi `weather:refresh` pada data produksi belum diukur (batas PHP 270 detik di rute cron).
- Butir lain di daftar 12 Oktober tetap terbuka: persetujuan R-021, pin PHP 8.3.33 tim, baris kosong `docs/PRD.md`, makna
  wilayah multi-provinsi, dan CF-7. `DEVICE_KEY_SECRET` produksi terjawab oleh langkah 4.

### 12 Oktober 2026: integrasi tim, CI hijau penuh, upgrade dependensi, bahan presentasi

Blok ini menggantikan daftar "keputusan terbuka" dan "batas yang diketahui" di blok W6 di bawahnya. Permintaan pemilik
(ringkas): integrasikan basis data dengan repo, pull dan push ke `main` dengan rapi, introspeksi, laporan presentasi
bergaya GEMASTIK yang selaras dengan uji nyata, dan rencana lanjutan sampai layak demo dan produksi. Pemilik memilih:
skema kanonik dengan API tim dipertahankan, Supabase sebagai basis data utama yang mengikuti migrasi repo lewat GitHub
Actions, dan upgrade maplibre-gl 6 serta tailwindcss 4 untuk menutup semua advisori npm.

| Commit | Isi |
|---|---|
| `a03bf6c` | Merge `origin/main` tim: API baca publik `/api` (5 rute GET, `throttle:60,1`, hanya jalur terbit) di atas skema kanonik; migrasi duplikat tim dilepas; pin platform PHP 8.3.33 dari tim tidak diambil (R-020); migrasi RLS untuk semua tabel aplikasi |
| `2c55ab5` | Workflow tangan `migrate-supabase.yml` (environment `supabase-produksi`, rahasia `SUPABASE_DB_URL`) |
| `c02d32f`, `b762cda` | Kegagalan ketiga suite diterbitkan sebagai anotasi publik dari JUnit di `RUNNER_TEMP`; pagar `DB_*` hanya untuk workflow yang menjalankan test |
| `501ad0e`, `e524c27` | Migrasi penutupan PostgREST portabel di PostgreSQL biasa; CI meniru peran `anon` dan `authenticated` Supabase; tabel milik ekstensi bukan tabel aplikasi; test hitungan kueri pos diperbaiki |
| `8f9adf1` | maplibre-gl 6.14.0 dan tailwindcss 4.3.3 lewat `@tailwindcss/vite`; npm audit 8 menjadi 0 |
| `b9fd945` | Blok HANDOVER ini dan versi Tailwind di matriks skill |
| `25fbf61` | Tinjauan independen upgrade: select dan textarea putih di tema gelap (C-1), gerak tekan 120 ms (I-1), breakpoint px (I-2); penjaga test bermutasi |
| `670f504` | Temuan minor tinjauan (komentar basi, TypeFloorTest, FocusIndicatorTest menyapu React) dan skill `tailwindcss-development` untuk Tailwind 4 |
| `cd4dbf3` | PRD GR-08 untuk upgrade yang disetujui pemilik; DESIGN.md, register lisensi, ARCHITECTURE.md, AGENTS.md diselaraskan |

- **Bukti.**
  - CI hijau penuh untuk pertama kalinya: Test run 38217749993 dan Security run 38217750034 pada `8f9adf1`.
  - Suite Spatial (52 test, PostGIS 16-3.4) baru benar-benar berjalan hari ini. Sejak 20 September seluruh suite galat di
    migrasi karena peran `anon` tidak ada di PostgreSQL biasa. Begitu berjalan, suite ini menemukan tiga cacat yang lolos
    dari SQLite (R-024).
  - Unit dan Feature: 2.633 test, 2.631 lulus, 2 dilewati, 18.287 asersi.
  - Upgrade: 96 dari 104 tangkapan identik piksel, 8 sisanya teks waktu, dan console error 0. Laporannya ada di
    `.superpowers/sdd/2026-10-07-memount-v3-w6-mendaki/upgrade-dep-report.md`.
  - Tangkapan itu tidak cukup. Peninjau independen menemukan 1 Critical dan 5 Important yang lolos darinya (C-1, I-1,
    I-2 di antaranya); semuanya tertutup menurut tinjauan ulang, dan verifikasi peramban V1 sampai V4 lulus: kontras
    kontrol gelap 14,13:1, scale bertransisi 1 ke 0,98 dalam 160 ms, kepala lebar tetap di huruf 24 px, dan peta mode
    lapangan tergambar luring dengan pekerja dari service worker. Laporannya `verifikasi-peramban-report.md` di folder
    SDD yang sama. Agen verifikasi mengaku dua penyimpangan protokol di cerminnya sendiri (satu `tinker` dan membaca
    dua baris flag `.env` cermin yang dibangkitkan dari `.env.example`); tidak ada rahasia yang terbaca.
  - CI terakhir sebelum push `main`: Test run 38222967967 dan Security run 38222967981 pada `cd4dbf3`, hijau.
- **Dokumen untuk pemilik.**
  - Deck presentasi 15 slide dari tangkapan nyata: https://claude.ai/artifact/UkTXKFUYWFenojPfxcBb35.
  - Introspeksi dan rencana produksi lima fase bergerbang: https://claude.ai/code/artifact/90d5de1d-447f-45a8-af92-255d2f68d4de.
- **Deploy inspeksi tim (12 Oktober 2026, sore).** Pemilik meminta hosting gratis supaya tim menginspeksi dari
  perangkat lain. Riset 11 platform memilih Render paket Free (tanpa kartu kredit, region Singapura); Koyeb, Fly.io,
  dan Zeabur sudah menutup paket gratisnya, dan Vercel berisiko gagal build karena build Vite butuh PHP dan Node
  sekaligus. Disiapkan di `cdd1cce` (proxy tepercaya lewat `TRUSTED_PROXIES`) dan `2bc64c4` (`Dockerfile` FrankenPHP
  PHP 8.5, `docker/`, `.dockerignore`, `render.yaml` dengan `autoDeployTrigger: checksPass`, job CI `image`). Job
  `image` hijau di run 38257261653: image terbangun, start kontainer menjalankan seluruh migrasi di PostGIS, `/up` dan
  `/login` menjawab 200. Langkahnya ada di README bagian "Deploy untuk inspeksi tim". GR-09 untuk mengganti target
  DK-12 (Vercel) ditulis sesudah pemilik mengonfirmasi Render.
- **Akun contoh di Supabase produksi dikunci (persetujuan pemilik 12 Oktober).** `admin@`, `moderator@`, dan
  `pendaki@example.com` (sandinya tertulis di README repo publik) diberi hash bcrypt dari rahasia acak yang dibuang,
  dan `remember_token` dikosongkan; tidak ada sesi aktif. Data dan relasinya utuh.
- **Keputusan pemilik yang masih terbuka:**
  - Render: masuk dengan GitHub, buat Blueprint dari `render.yaml`, isi `APP_KEY`, `APP_URL`, `DB_URL`, dan empat
    nilai `SUPABASE_STORAGE_*`; di Supabase matikan Data API (lint `spatial_ref_sys`), buat bucket privat foto, dan buat
    kunci S3. Start kontainer menerapkan 14 migrasi yang tertunda di Supabase (51 dari 65 per 12 Oktober);
  - isi rahasia `SUPABASE_DB_URL` (Session pooler), lalu jalankan workflow "Migrasi Supabase": pratinjau dulu, baru
    terapkan (opsional sesudah Render memakai `migrate --force` saat start);
  - persetujuan R-021 untuk satu rumah per pekerjaan inti. Saat ini empat pekerjaan punya 2 sampai 4 pintu masuk di tab
    berbeda; rinciannya ada di dokumen introspeksi;
  - hosting staging. Syaratnya PHP 8.5, cron untuk 3 perintah terjadwal di `routes/console.php`, surel, dan pengelola
    rahasia;
  - `DEVICE_KEY_SECRET` produksi (izin akhir 40), sebelum deploy pertama;
  - pin platform PHP 8.3.33 dari tim, karena runtime proyek PHP 8.5;
  - satu baris kosong baru di `docs/PRD.md` baris 26 yang tidak dibuat agen, dibiarkan tidak di-commit;
  - makna isian wilayah yang memuat banyak provinsi;
  - jeda toast Urungkan pada modalitas campuran (CF-7).
- **Batas yang diketahui:**
  - data contoh tanpa geometri jalur dan tanpa akun moderator dan admin;
  - pembaca layar belum diuji;
  - uji kegunaan 5 pendaki dan SUS belum dijalankan;
  - halaman peta kini memuat sekitar 225 kB gzip lebih banyak (MapLibre 6), dan paket lapangan naik dari 0,4 menjadi
    0,6 MB gzip;
  - lantai peramban Tailwind 4 dan MapLibre 6 (Safari 16.4+, Chrome 111+, Firefox 128+, WebGL2) kini dicatat di PRD
    GR-08 §88 dan DESIGN.md; penerimaannya, juga pencabutan `blob:` dari CSP `worker-src`, keputusan pemilik di GR-08.

### W6 Mendaki dan paket luring (selesai 11 Oktober 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-10-07-memount-v3-w6-mendaki.md`, 13 tugas, ditambah dua sisipan. Kedua sisipan
diputus koordinator:
- **T9b:** shell paket disimpan saat paket pertama diunduh (W6-9g).
- **W6-P2:** jejak dikirim sesudah turun.

Ledger dan seluruh ruling ada di `.superpowers/sdd/2026-10-07-memount-v3-w6-mendaki/progress.md`, yaitu:
- W6-K1 sampai W6-K13;
- W6-1a sampai W6-11h;
- W6-P2a sampai W6-P2s;
- W6-Fpre, W6-FA sampai W6-FK, W6-FR1 sampai W6-FR3, dan W6-FT.

Setiap tugas ditinjau terpisah. Putaran perbaikannya:

| Tugas | Putaran perbaikan |
|---|---|
| Tugas 1 | 2 |
| Tugas 2 | 1 |
| Tugas 3 | 6 |
| Tugas 4 | 3 |
| Tugas 5 | 3 |
| Tugas 6 | 3 |
| Tugas 7 | 1, ditambah satu patch mikro |
| Tugas 8, 9, dan 11 | masing-masing 1 |
| T9b | satu patch mikro |
| Tugas 10 | 2 |
| W6-P2 | 1 |

Tugas 12 (dokumen) diperiksa koordinator. Pemeriksaan itu mengoreksi tiga klaim dokumen yang tidak sesuai kode.

Sesudah itu:
- satu tinjauan akhir lintas tugas: 0 Critical, 2 Important, 5 Minor;
- satu gelombang perbaikan W6-FA sampai W6-FI, ditambah putaran mikro W6-FJ dan W6-FK;
- satu pemeriksaan terlingkup (16 dari 16 ruling tertutup) dan satu putaran residu W6-FR1 sampai W6-FR3;
- Tugas 13 (verifikasi dan Delivery Gate);
- sesudah arahan pemilik "lanjutkan sampai W6 selesai": W6-FT (batal menutup sesi) dan uji luring manual dengan server
  Laravel sungguhan di atas data contoh `database/demo.sqlite` (butir izin 3).

Pemilik menunda W7 sampai W9 pada 11 Oktober 2026 untuk evaluasi besar. Rencana dan pra-tinjau W7 sampai W9 disimpan
apa adanya.

**Inspeksi lokal (11 Oktober 2026).**
- `composer inspeksi:data` dijalankan sekali. Perintah ini mengisi `database/demo.sqlite` lewat `memount:demo` dan
  mencetak sandi akun contoh `budi@contoh.test`.
- `composer inspeksi` lalu menyalakan `http://127.0.0.1:8000` dengan data contoh dan kedua sakelar W6 menyala. Sesi,
  cache, antrean, surel, dan berkas diputus dari `.env` lewat `@putenv`, sehingga perintah ini berjalan sama di
  PowerShell, CMD, dan Git Bash.
- `php artisan serve` biasa dengan `.env` saat ini galat di setiap permintaan, karena host proyek Supabase dijawab
  NXDOMAIN, juga lewat DNS publik. Menurut dokumentasi Supabase, itu berarti proyek dijeda, dihapus, atau ref-nya salah.
  Pulihkan proyek dari dashboard sebelum memakai Supabase lagi. Sesudah itu migrasi W3 dan W6 (izin akhir 2, 43, dan 52)
  tetap dibutuhkan.
- Atas permintaan pemilik, `.env` lokal di mesin ini diarahkan ke data contoh pada 11 Oktober 2026. Kunci yang diubah:
  `DB_CONNECTION=demo`, `APP_DEMO=true`, `FIELD_PACK_ENABLED=true`, dan `FIELD_SAFETY_ENABLED=true`. Akibatnya
  `php artisan serve` biasa langsung menyajikan data contoh. Penyajian itu teruji: login lewat formulir, Beranda, Mendaki,
  ruang kerja trip, dan mode lapangan tampil tanpa galat console.
- Isi `.env` sebelumnya disimpan di `.env.sebelum-inspeksi` (diabaikan git). Untuk kembali ke Supabase, salin berkas
  itu menjadi `.env` sesudah proyeknya dipulihkan.

**Audit sebelum inspeksi pemilik (11 sampai 12 Oktober 2026).** Atas permintaan pemilik: "ricek sekali lagi ...
setelah semua itu aman dan teruji baru anda boleh melapor sistem layak di uji atau inspek manual oleh saya secara real
world testing". Pemeriksaannya terdiri dari:
- empat putaran uji E2E di server inspeksi (port 8000, data contoh);
- tiga cek peramban terlingkup;
- enam tinjauan kode independen.

Setiap temuan diperbaiki, diuji, dimutasi, lalu di-commit lokal di branch `hardening`. Tidak ada yang di-push.

| Commit | Isi |
|---|---|
| `86ad5e5` | Audit E2E pertama: I-1 sampai I-6, M-1 sampai M-8, C-6 |
| `a63d8c9` | Patch keamanan dalam rentang terkunci, hanya lockfile: commonmark 2.10.3, concurrently, shell-quote, source-map-js |
| `c9cfbde` | Uji ulang E2E dan tinjauan kode pertama: rekomendasi wilayah, ringkasan Jelajah, konfirmasi mulai di setiap pintu, fokus |
| `90641c0` | Uji E2E akhir dan tinjauan ulang: tanggal zona gunung, rute PWA tanpa sesi (flash tidak hilang), sisa fokus |
| `8cb4c0c`, `3c45443`, `488336d`, `b50d517` | Tinjauan akhir dan cek peramban terlingkup: fokus chip tafsiran, pengembalian fokus toast, celah test |

- Putusan akhir:
  - uji E2E penutup "Layak diuji pemilik: YA", dengan 0 temuan baru;
  - cek peramban terakhir (regresi toast) tanpa temuan;
  - tinjauan kode terakhir: kepatuhan YA, mutu DISETUJUI.
- Angka akhir: Unit dan Feature 2.624 test, 2.622 lulus, 2 dilewati, 0 gagal, 18.149 asersi. Hasilnya sama di 12 shard,
  serial, dan salinan bersih dari commit `b50d517`. Pratinjau 29/29, penjaga dokumen 31/31, SecretsNotInRepo 20/20,
  tsc 0, Pint lulus.
- Git: rantai commit-graph basi ditulis ulang, dan `git fsck` bersih. `phpunit.xml` memaksa sakelar W6 dan `APP_DEMO`
  mati, supaya `.env` inspeksi tidak mengubah hasil test.
- Batas yang diketahui:
  - data contoh SQLite tanpa geometri jalur, sehingga peta menulis "menunggu garis rute";
  - suite Spatial dan RLS baru berjalan di CI PostgreSQL sesudah push;
  - data contoh tanpa akun moderator dan admin, jadi kalimat laporan ditolak dan halaman admin hanya terbukti lewat
    test;
  - pembaca layar belum diuji;
  - run paralel pertama di salinan baru dapat gagal palsu karena kompilasi view Blade serentak di Windows (log:
    `rename` di `storage/framework/views`), dan run berikutnya bersih.
- Keputusan pemilik yang masih terbuka:
  - push;
  - `DEVICE_KEY_SECRET`;
  - pemulihan Supabase beserta migrasinya;
  - npm audit mayor: maplibre-gl 6 dan tailwindcss 4. Job npm audit di `security.yml` tetap merah sampai diputuskan;
    fungsi rentan maplibre (`DOM.sanitize`) tidak ada di 4.7.1;
  - makna isian wilayah yang memuat banyak provinsi;
  - jeda toast Urungkan pada modalitas campuran (CF-7).
- Ledger dan semua laporan audit ada di `.superpowers/sdd/2026-10-07-memount-v3-w6-mendaki/` (diabaikan git).

Nilai di bawah terukur 11 Oktober 2026 pada pohon kerja akhir W6 (snapshot tree `2ad3f1a`). Selisihnya terhadap akhir
W5 (`d4a78fe`), sebelum tangkapan dan residu: 90 berkas ditambah, 102 diubah, dan 1 dihapus
(`UpdateHikePositionRequest.php`).

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 2.560 test, 2.558 lulus, 2 dilewati, 0 gagal, 17.585 asersi (W5 akhir: 2.093) | `php artisan test --compact --testsuite=Unit,Feature`, serial dan 12 shard identik |
| Suite bawaan | 2.600 test, 2.549 lulus, 51 dilewati (Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`), 0 gagal | Tugas 13 Step 2, diukur di tree `406716f`, sebelum dua test residu |
| Spatial (PostgreSQL) | **PERLU KLARIFIKASI.** Belum pernah berjalan: mesin ini tanpa PostgreSQL dan Docker. Ada 14 berkas baru sejak W1, termasuk `TripConditionalWritesTest`, dan `SpatialTestCase` diubah. CI kini gagal bila test Spatial dilewati (`--fail-on-skipped`, W6-FI) | Tugas 13 Step 2, `final-fix-check.md` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --dirty` lulus tanpa berkas diformat ulang | Tugas 13 Step 2, putaran residu |
| Build dan bundel | `shell` 11,38/4,27 kB; `app` 11,41/3,31 kB; `AppLayout` 26,43/8,82 kB; `jejak` 4,05/1,62 kB; CSS 90,60/16,95 kB (mentah/gzip). Potongan bersama tata letak 2.732 B gzip sesudah W6-FJ (5.588 sebelumnya) | Tugas 13 Step 3, `final-fix-report.md` putaran mikro 1 |
| Pemasangan service worker | 4 berkas, 30.149 B gzip, baik sakelar paket mati maupun menyala. Shell paket 25 berkas lagi (sekitar 423 kB gzip) diambil saat paket pertama diunduh (T9b; sebelum T9b pemasangan 428.998 B) | Tugas 13 Step 3, `task-9b-report.md` |
| Pratinjau | 29 halaman (W5: 22) | suite `Pratinjau` 29/29 |
| Tangkapan | 28 tangkapan W6. Ada 24 dari pratinjau: mode lapangan, kartu darurat, ringkasan, ringkasan batal, Mendaki, paket, cetak, ruang kerja keselamatan, luring, dan Jelajah dengan strip, pada 390 dan 1440 px, terang dan gelap, tema lapangan gelap, ditambah satu tangkapan fokus. Ada 4 dari uji luring dengan server sungguhan (`luring-1-siap`, `luring-3-lapangan-offline`, `luring-4-sw-baru`, `luring-5-keluar`). Semuanya dibandingkan dengan acuan 11 dan 12 | `docs/desain/tangkapan/w6/README.md` |
| Reflow dan target | `scrollWidth` sama dengan `clientWidth` di 320 dan 390 px untuk 10 halaman (20/20). "Mulai pendakian" 66 px, telepon darurat 70 px, Jeda dan Selesai 60 px, kontrol peta 48 px, kendali lain 44 sampai 49 px | Tugas 13 Step 7 |
| Papan ketik | Urutan Tab mode lapangan 12 posisi dengan cincin terlihat. Enter dan Escape pada lembar Selesai benar. Lembar "Ubah angka" memfokuskan Tutup lebih dulu, pola `<dialog>` yang sama di semua lembar | Tugas 13 Step 8 |
| Uji luring manual | **PASS dengan server Laravel sungguhan** di atas data contoh `database/demo.sqlite` (jalur inspeksi A, butir izin 3, sesudah arahan pemilik). Yang terbukti: unduh paket dan verifikasinya; jaringan mati lalu mode lapangan terbuka dari paket; "Luring. 1 catatan menunggu sinyal untuk dikirim."; Selesai tertahan selagi antrean belum kosong; antrean terkirim saat daring; service worker versi baru membuang shell lama tanpa menyentuh `pack-*` dan paket IndexedDB; keluar dengan `Clear-Site-Data: "storage"` dan penyimpanan kosong. Keterbatasan alat: dua tangkapan berukuran 908x1304, dan emulasi Offline tidak mengubah `navigator.onLine` | Tugas 13 Step 9, README tangkapan W6 |
| Delivery Gate | Salinan, Desain, Interaksi, dan Kebenaran semuanya PASS. Em dash 0. 35 kecocokan kata klaim diperiksa satu per satu tanpa pelanggaran | Tugas 13 Step 10 |

Yang dibangun W6:
- **Satu pendakian berlangsung per pengguna** (Tugas 1, izin akhir 8):
  - penjaga di `TripLifecycle::start()` dengan kunci baris pengguna;
  - indeks unik `hiking_sessions.trip_plan_id`;
  - mulai dan hapus menulis menurut status baris (W6-FA).
- **Mode lapangan di perangkat** (Tugas 2 dan 3):
  - pos berikutnya, sisa jarak, naik lagi, dan tiba di pos dihitung di perangkat (`hitung.ts`). Rute
    `trips.hike.position` dihapus, dan server hanya menerima urutan pos dan waktu jeda;
  - antrean aksi luring dengan idempotensi;
  - jeda dan lanjut tahan putar ulang (`last_resumed_at`, W6-10f);
  - selesai menunggu antrean dan membaca sesi terkunci (W6-FB, W6-FC).
- **Tab Mendaki** (Tugas 4): pintu yang memilih trip yang dapat dimulai.
- **Selesai dan batal** (Tugas 5, DK-50): alasan pembatalan dari daftar tetap, dengan kalimat "belum pasti" untuk galat
  jaringan dan HTTP (W6-FE).
- **Ringkasan dan laporan** (Tugas 6):
  - varian selesai dan dibatalkan;
  - angka yang dapat diubah;
  - banding diri sendiri (R-015);
  - laporan dengan foto tanpa GPS;
  - integritas moderasi dengan sidik isi.
- **Kartu darurat, versi cetak, dan pesan rencana** (Tugas 7):
  - nomor 112 dan 115;
  - kontak pengelola hanya bila resmi diketahui;
  - jam kembali dengan eskalasi;
  - di balik `hiking.field_safety.enabled`, yang mati bawaan.
- **Paket lapangan luring** (Tugas 8 sampai 10, T9b):
  - manifest pemilik saja, hanya jalur terbit, tanpa skor, AI, atau data orang lain;
  - data dinamis berlabel "terakhir diketahui", dan status Buka turun sesudah 24 jam;
  - service worker yang tidak pernah menghapus `pack-*`;
  - unduh terverifikasi SHA-256;
  - shell luring dan "Pakai paket saja";
  - di balik `hiking.field_pack.enabled`, yang mati bawaan.
- **Keluar dan ganti akun** (Tugas 11):
  - basis data perangkat berkunci HMAC id pengguna;
  - data belum terkirim ditanyakan sebelum dihapus;
  - `Clear-Site-Data` sebagai lapis kedua;
  - penjaga tidak berjalan atas halaman dari riwayat.
- **Pengirim jejak** (W6-P2):
  - nol kiriman selama pendakian;
  - satu pengirim di tata letak aplikasi sesudah pendakian berakhir, setelah server menjawab bahwa sesi menerima
    jejak;
  - hapus di ringkasan selalu sampai ke Memount (W6-P2o);
  - titik yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan tidak disimpan (W6-FH,
    W6-FT).
- **Dokumen** (Tugas 12): GR-07 di `docs/PRD.md` §146 (penunjuk berikutnya GR-08), amandemen DK-36 di §53,
  ARCHITECTURE, DESIGN, dan `docs/fitur/06`.

Ruling koordinator atas konflik rencana W6-K1 sampai W6-K13:
- **K1:** GR-07 diterima sebagai keputusan pemilik, dengan pola W3 P8 dan batas "paket lapangan, bukan navigasi
  offline penuh".
- **K2:** ambang 24 jam.
- **K3:** kedua sakelar mati.
- **K4:** uji luring manual menunggu izin Q1.
- **K5:** `encryptHistory` diputus W9.
- **K6:** migrasi masuk daftar izin, dengan pemeriksaan duplikat.
- **K7:** posisi lama tidak lagi ditulis.
- **K8:** ikon jam dicocokkan dengan paket Phosphor.
- **K9:** kecilkan ke strip.
- **K10:** angka ringkasan dan tangga.
- **K11:** strip dan kartu B3 ke mode lapangan.
- **K12:** test perilaku lewat Node.
- **K13:** kabar flash status.

Perubahan penting dari tinjauan (selain isi rencana):
- **W6-1a, W6-1d:** percobaan mulai yang bertabrakan dengan indeks unik dijawab kalimat, bukan 500.
- **W6-3o, W6-3r, W6-5b, W6-6e:** fokus papan ketik dipindah dengan sengaja sesudah setiap aksi yang melepas
  elemennya. Daftar periksa mutu butir 7 lahir dari pola ini.
- **W6-6c:** integritas moderasi. Ubah laporan memakai UPDATE bersyarat PENDING, dan tindakan moderasi memeriksa ulang
  sidik isi di dalam transaksi berkunci.
- **W6-7a:** kontak darurat hanya pengelola resmi yang diketahui. BASECAMP dikecualikan sampai ada tautan badan ke
  jalur.
- **W6-8k:** instan tepat 00.00 UTC ditulis tanggal saja, supaya tidak menampilkan jam yang tidak pernah dimasukkan
  (R-005).
- **W6-9g dan T9b:** pemasangan service worker hanya menyimpan shell kecil. Shell paket disimpan saat paket pertama
  diunduh, sehingga pemasangan turun dari 428.998 menjadi 28.316 bita gzip.
- **W6-9j:** halaman luring menulis "Halaman ini belum dapat dimuat", bukan menyalahkan jaringan pengguna.
- **W6-10f:** `last_resumed_at` menutup jeda yang diputar ulang dua tab (W6-3aa).
- **W6-11g:** penjaga ganti akun tidak berjalan atas halaman dari riwayat, sehingga data akun B tidak terhapus saat B
  menekan Kembali ke halaman akun A.
- **W6-P2o:** hapus jejak di ringkasan selalu sampai ke Memount walau jawaban unggahan hilang.
- **W6-FA:** mulai dan hapus trip menulis menurut status baris, sehingga trip yang dibatalkan di tab lain tidak hidup
  lagi.
- **W6-FB:** selesai membaca sesi dengan kunci baris.
- **W6-FC:** Selesai menunggu aksi lapangan yang masih antre, sehingga waktu bergerak dan pos yang dicapai tidak salah.
- **W6-FH dan W6-FT:** titik jejak yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan
  tidak disimpan. Batal kini menutup sesi seperti selesai (`TripLifecycle::tutupSesi()`), tanpa mengubah status sesi.
- **W6-FI:** CI tidak lagi dapat hijau tanpa test Spatial yang berjalan. `SpatialTestCase` galat bila DSN diisi tetapi
  PostGIS tidak ada, dan langkah Spatial memakai `--fail-on-skipped`.
- **W6-FJ:** fungsi waktu bergerak dipindah ke modul kecil, supaya strip pendakian tidak menarik modul mode lapangan ke
  setiap halaman.

OPEN ISSUE dan butir yang ditunda dengan putusan tertulis (tabel titipan W6, disalin apa adanya menurut P-R14):

| Butir | Asal | Putusan | Penerima |
|---|---|---|---|
| Selesai dari perangkat lain, atau dari tab lain tanpa paket, selagi aksi lapangan menunggu di perangkat atau tab pertama, tidak dicegah. Lintas perangkat memang tidak dapat dicegah, karena catatannya hanya ada di perangkat pertama. Lintas tab hanya terjadi di jendela sempit: tab pertama luring, atau dalam 30 detik sesudah sinyal kembali | W6-FC, N-4 | OPEN ISSUE | Lampiran B, keputusan pemilik |
| Jejak sesudah batal | W6-FH | Tertutup (W6-FT) | `cancel()` menutup sesi lewat `TripLifecycle::tutupSesi()`; status sesi tidak diubah |
| Fokus sesudah aksi ditolak lintas halaman | W6-5n | Defer | W9 Tugas 15. Menurut C4 alamat ini salah, karena Tugas 15 hanya halaman Galat; wajib diperiksa di pra-cek W9 |
| Uji konkurensi dua koneksi PostgreSQL (`lockForUpdate`, UPDATE bersyarat jeda, reports.update) | W6-1b, W6-3h, K-2, K-3 | Defer | W9 R2 |
| Urutan bacaan pembaca layar di mode lapangan, dan `#kabar-ringkasan` sebagai wilayah status sekaligus sasaran fokus | Tugas 3, Tugas 6 | Defer | W9, uji pembaca layar |
| BASECAMP sebagai kontak darurat jalurnya sendiri | W6-7a | Defer | W8, izin akhir 44 |
| Semantik "sampai tanggal" inklusif | W6-8k | Defer | W8, PW8-7.7 |
| Level dan radius bahaya PVMBG di paket lapangan | T-W8-1 | Defer | W8 Tugas 10 |
| Pembaca pembatasan segmen paket disatukan dengan pembaca daring | T-W8-2 | Defer | W8 |
| Dialog galat bawaan Inertia untuk galat HTTP tanpa header Inertia secara umum (lokal sudah ditutup W6-FE) | Tugas 6 | Defer | W9 Tugas 1 |
| Label bilah tab terpatah di 320 px; spasi biasa angka-satuan di `CheckpointJourney` dan `teksLama` | Tugas 6, W6-FJ K-2 | Defer | W9 Tugas 2 dan 4 |
| Pengukuran performa WP-7 dan WP-8 | Program | Defer | Bersama W7 sampai W9, arahan pemilik |
| 13 berkas test Spatial W1 sampai W6 belum pernah berjalan; pemecah seri `orderBy('id')` di PostgreSQL | Seluruh gelombang, W6-4a2 | PERLU KLARIFIKASI | CI PostGIS sesudah push yang diizinkan (izin akhir 4) |
| W6-P2n dibuktikan di Chrome saja; Firefox dan Safari belum | W6-P2 | PERLU KLARIFIKASI | W9 |
| Penyimpanan iPhone Layar Utama terpisah dari tab Safari (spec bagian 10.1) | Tinjauan akhir | PERLU KLARIFIKASI | W9 atau pemilik |

Daftar izin akhir dari W6 (rincian di pelacak program):
- **Butir 3 (`memount:demo`) sudah dijalankan** atas arahan pemilik "lanjutkan sampai W6 selesai", untuk uji luring
  dan inspeksi. Yang ditulis hanya `database/demo.sqlite`, dan Supabase tidak tersentuh.
- **Migrasi aditif, dijalankan sebelum kode terbit (K-2):**
  - `2026_10_07_000100` sampai `000600`;
  - pemeriksaan duplikat di docblock migrasi `000100` dijalankan sebelum indeks unik;
  - izin akhir 2, 8, 43, dan 52.
- **Sakelar:**
  - `FIELD_SAFETY_ENABLED` bersama atau sebelum `FIELD_PACK_ENABLED` (izin akhir 42, Q10, Q14);
  - ambang Q9 24 jam.
- **`DEVICE_KEY_SECRET` produksi (izin akhir 40)** WAJIB diisi sebelum kode W6 pertama kali terbit. Mengisinya sesudah
  itu sama dengan memutar kunci.
- **Kebijakan data perangkat:**
  - retensi paket (izin akhir 39);
  - kalimat kebijakan (izin akhir 41);
  - opsi penutup sisa jejak W6-P2k (izin akhir 50);
  - teks persetujuan W6-P2l (izin akhir 51).
- **Lainnya:**
  - pembersihan `hiking_sessions.last_known_location` dan `location_updated_at` lama;
  - amandemen GR-07 (Q7);
  - `encryptHistory` dan `clearHistory` (izin akhir 9);
  - tautan badan pengelola ke jalur (izin akhir 44);
  - `Cache-Control: immutable` untuk `/build/assets/*` (izin akhir 49).

Titik lanjut: menunggu evaluasi pemilik. Bila W7 dilanjutkan, pra-cek W7 memakai rencana
`docs/superpowers/plans/2026-10-08-memount-v3-w7-progres-profil.md` dan pra-tinjau Tugas 1 sampai 11 (74 temuan). Progres
membaca `recorded_*` untuk total tanjakan dan jarak.

### W5 Beranda (selesai 8 Oktober 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-10-07-memount-v3-w5-beranda.md`, 9 tugas. Ledger dan seluruh ruling ada di
`.superpowers/sdd/2026-10-07-memount-v3-w5-beranda/progress.md`, yaitu:
- W5-C1 sampai W5-C9, W5-P4, dan W5-P8;
- W5-1a sampai W5-7a;
- W5-Fpre, W5-FA sampai W5-FK, dan W5-FR;
- W5-G1 dan W5-G1b.

Setiap tugas ditinjau terpisah. Tugas 2 butuh satu putaran perbaikan, Tugas 3 satu putaran mikro, dan Tugas 5 serta
Tugas 6 masing-masing dua putaran. Sesudah itu:
- satu tinjauan akhir lintas tugas: 0 Critical, 3 Important, 10 Minor;
- satu gelombang perbaikan dengan sebelas kelompok;
- satu tinjauan ulang (13 dari 13 temuan tertutup);
- satu putaran residu;
- satu perbaikan Delivery Gate.

Nilai di bawah terukur 8 Oktober 2026 pada pohon kerja akhir W5 (snapshot tree `d4a78fe`).

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 2.093 test, 2.091 lulus, 2 dilewati, 0 gagal (W4 akhir: 1.991) | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite bawaan | 2.128 test, 2.091 lulus, 37 dilewati (Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`) | `php artisan test --compact` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --dirty` lulus | gerbang akhir W5 |
| Bundel | `app` JS 355,97 kB (gzip 111,89); `app` CSS 87,54 kB (gzip 16,50); potongan Dashboard 18,62 kB (gzip 5,09) | build 8 Oktober |
| Pratinjau | 22 halaman (W4: 18) | suite `Pratinjau` 22/22 |
| Anggaran kueri | Beranda B2 33 kueri dan tidak tumbuh bersama trip, kabar, riwayat (tangga 18 = 18 dari 1 ke 12 riwayat), atau gunung yang diikuti; Jelajah 15, rincian 26, Bandingkan 17, tidak berubah oleh gelombang perbaikan | `PageQueryBudgetTest`, probe tinjauan ulang akhir |
| Tangkapan | 20 tangkapan Beranda (lima fase; 390 dan 1440 px; terang dan gelap) dibandingkan dengan acuan 01; empat tangkapan Bandingkan dan rincian jalur untuk perubahan Tugas 7; satu tangkapan fokus. Empat tangkapan B2 dan tangkapan fokus dipotret ulang sesudah perbaikan gerbang | `docs/desain/tangkapan/w5/README.md` |
| Lapisan dan reflow | `scrollWidth` sama dengan `clientWidth` di 320 dan 390 untuk kelima fase; empat langkah B2 satu baris di 320 (47, 73, 62, 47 px); kartu bertinggi isi; kolom samping 352 px di B1, B2, B4 | README tangkapan W5 |
| Papan ketik | 390 px B2 lengkap tanpa tertutup bilah tab; aksi utama B0, B1, B3, B4 fokus pertama di isi halaman; 1440 px kolom utama lalu kolom samping | README tangkapan W5 |
| Delivery Gate | Salinan 5 butir, Desain 4, Interaksi 3, Kebenaran 6; satu FAIL salinan (klaim "menautkan sistem resmi" tanpa tautan tampil) diperbaiki W5-G1 dan W5-G1b; tidak ada FAIL tanpa putusan | `.superpowers/.../delivery-gate-w5.md` |

Yang dibangun W5:
- **Beranda berfase** (`RingkasanDasbor`, `KonteksBeranda`): lima fase dengan satu aksi utama per fase, urutan
  kepentingan B3, B2, B4, B0, B1. Aksi B2 mengikuti keadaan trip: izin, "Ingatkan saat dibuka", persiapan (tanpa butir
  yang menunggu BMKG), cek kesiapan pada H-3 sampai H-1, dan mulai pendakian pada hari H.
- **Tenggat terdekat** (`TenggatTrip`, `TenggatIzin` satu sumber dengan lembar Pengingat): jendela izin, terbit
  prakiraan BMKG, dan pengingat surel yang masih akan datang (`TripReminder::akanDatang()`), dalam zona gunung.
- **Pengingat hari pemesanan izin dibuka** (FR-24, GR-06): satu surel pukul 07.00 zona gunung. Diputuskan terpisah dari
  H-3 dan H-1, menurut aturan izin yang berlaku saat kirim, dan tidak dikirim bila butir izin sudah diurus. Perubahan
  butir izin di Persiapan menyelaraskan pengingat.
- **Kabar gunung yang diikuti** (`KabarBeranda`, `KabarJalur::ringkas()`): status resmi dipisah dari satu laporan
  komunitas, dengan kalimat R-012. Tombol "Ikuti" lewat rute `mountains.follow` (auth dan batas laju); sesudahnya
  fokus pindah ke judul kabar dengan pengumuman status.
- **Jalur lain untuk Anda** (`TrailFitService::teratas()`, `JalurUntukAnda`): paling banyak tiga, tanpa jalur yang
  sedang direncanakan, Cocok lebih dulu, dan kalimat yang tidak berulang. Satu daftar sebab (faktor lemah, pembatasan
  segmen, data yang belum tersedia) kini menjelaskan label Perlu persiapan di semua permukaan; "Kenapa?" Jelajah tidak
  mengulang kalimat kartu.
- **Tangga tanjakan** (`TanggaTanjakan`): anak tangga membawa id jalur dan nama gunung, karena nama jalur tidak unik.
- **Titipan W3:** rute `trails.timbang` dihapus, laporan komunitas rincian jalur dipisah garis, dan tombol "Kenapa?"
  Bandingkan menyebut jalurnya.
- **Formulir laporan dari B4:** tanggal pendakian bawaan diambil dari riwayat milik pengguna dalam zona gunung, bukan
  tanggal UTC hari ini.
- **Dokumen:** GR-06 di `docs/PRD.md` §146 (penunjuk berikutnya GR-07), DESIGN, ARCHITECTURE, tiga dokumen fitur, dan
  CONTRIBUTING.

Ruling koordinator atas konflik rencana W5-C1 sampai W5-C9, semuanya diterima:
- C1: FR-24 ditambah pengingat hari buka izin (GR-06).
- C2: urutan alasan kartu W3-C7 berlaku global.
- C3: urutan fase B3, B2, B4, B0, B1.
- C4: aksi B2 menurut fase waktu.
- C5: judul halaman "Beranda".
- C6: navigasi "Lanjutkan ke" dilepas.
- C7: trip lama tanpa jadwal IZIN sampai disinkronkan ulang; sinkronisasi sekali jalan hanya atas izin.
- C8: strip pendakian dan kartu B3 sama-sama ke mode lapangan.
- C9: redesain halaman Kabar dipindah ke W8.

Perubahan penting dari tinjauan (selain isi rencana):
- **W5-5c dan W5-FB:** kartu Perlu persiapan dijelaskan sebab turunnya label, tidak pernah status resmi atau izin.
  Awalnya untuk Beranda saja, lalu disatukan untuk Jelajah, rincian jalur, dan semua pemakai `forTrails()` lewat
  pembatasan segmen yang dibawa `RouteFitResult`, tanpa kueri tambahan.
- **W5-FA:** surel IZIN tidak lagi menghilangkan atau menggusur H-3. Surel itu tidak terkirim pada hari yang salah
  sesudah penjadwal terlambat atau aturan dikoreksi.
- **W5-FC:** tanggal pendakian bawaan formulir laporan dari riwayat milik sendiri. Id trip pendaki lain tidak
  berpengaruh (IDOR).
- **W5-G1 dan W5-G1b:** klaim "Memount menautkan sistem resmi" hanya bila tautannya tampil. Berlaku di Tenggat,
  Rencanakan, lembar Pengingat, dan surel, lewat satu sumber `PermitService::kalimatTautan()`.

OPEN ISSUE dan butir yang ditunda dengan putusan tertulis:
- **Ke W6:**
  - pintu tab Mendaki memulai pendakian yang dirujuk aksi hari H Beranda;
  - kata status di Mendaki.
- **Ke W8:**
  - aturan izin yang dimajukan atau dihapus sesudah baris IZIN dijadwalkan (penyelarasan saat aturan ditulis);
  - `bookingUrl` truthy di lembar Pengingat, `KartuIzin`, dan `Kondisi`;
  - redesain `/kabar` dan dokumen fitur 05;
  - foto hero kartu perjalanan (DK-37).
- **Ke W9:**
  - biaya hitung kecocokan hingga 500 kandidat per muat Beranda;
  - `mountain_follows` dua kali;
  - aturan izin dibaca per baris di penjadwal;
  - dua kalimat status UNKNOWN di "Kenapa?" Jelajah;
  - bilah tab 320 px;
  - waktu data contoh "0 detik yang lalu".
- **Menunggu izin:** klik kiriman "Ingatkan saat dibuka" dan "Ikuti" di peramban butuh server lokal (Q1) atau
  Playwright (Q17); keduanya dijaga test Feature.

Daftar izin akhir dari W5 (rincian di pelacak program):
- **W5 tidak menambah migrasi.** `trip_reminders.jenis` menerima nilai `IZIN` dalam kolom yang ada.
- **Keputusan pemilik yang tercatat di GR-06:**
  - pengingat hari buka izin dengan sakelar yang sama;
  - Beranda berfase;
  - urutan alasan kartu dan daftar sebab Perlu persiapan.
- **Menunggu keputusan:** penjadwalan ulang pengingat trip lama (W5-C7).
- **Pelanggaran pagar** implementer Tugas 4 (butir 28): tiga perintah artisan tanpa awalan DB.

Titik lanjut: W6 Mendaki dan paket luring. Rencananya `scratchpad/rencana/2026-10-07-memount-v3-w6-mendaki.md` milik
sesi koordinator (13 tugas), dipindah ke `docs/superpowers/plans/` saat W6 dimulai. Pra-ceknya terhadap pohon akhir W5
ini memakai uji kering `alat6/uji_kering6.py --tanpa-w5` dan memutus konflik K1 sampai K13. Rencana W7 dan W8 sudah
ditulis dan disimulasikan; rencana W9 sedang ditulis.

### W4 Klaster Trip (selesai 7 Oktober 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-10-06-memount-v3-w4-trip.md`, 8 tugas. Ledger dan seluruh ruling ada di
`.superpowers/sdd/2026-10-06-memount-v3-w4-trip/progress.md`, yaitu W4-C1 sampai W4-C7, W4-S1 sampai W4-S4, W4-1a sampai
W4-6l, W4-Fpre, W4-FA sampai W4-FK, dan W4-G1.

Setiap tugas ditinjau terpisah. Tugas 1 dan 3 butuh satu atau dua putaran perbaikan, sedangkan Tugas 4 (Persiapan) dan
Tugas 6 (Cek kesiapan) masing-masing tiga putaran. Sesudah itu:
- satu tinjauan akhir lintas tugas: 1 Critical, 4 Important, 8 Minor, dan 2 butir inventaris yang dinaikkan;
- satu gelombang perbaikan: 118 butir A sampai J, 107 dikerjakan dan 11 ditunda dengan ruling;
- satu tinjauan ulang;
- satu putaran residu;
- satu perbaikan Delivery Gate.

Nilai di bawah terukur 7 Oktober 2026 pada pohon kerja akhir W4 (snapshot tree `9d531a7`).

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 1.991 test, 1.989 lulus, 2 dilewati, 0 gagal (W3 akhir: 1.806) | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite bawaan | 2.026 test, 1.989 lulus, 37 dilewati (Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`) | `php artisan test --compact` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --dirty` lulus | gerbang akhir W4 |
| Bundel | `app` JS 355.924 B (W3 akhir 355.731 B); `app` CSS 86.457 B; potongan Trip: Rencanakan 11.320 B, Cek kesiapan 12.091 B, Persiapan 9.812 B, ruang kerja 7.812 B, indeks 5.146 B | build 7 Oktober |
| Pratinjau | 18 halaman (W3: 14) | suite `Pratinjau` 18/18 |
| Anggaran kueri | Indeks, ruang kerja, Persiapan, dan Cek diuji tidak tumbuh bersama data (Cek 39 = 39 dan 43 = 43, arsip trip 13 = 13) | `TripIndexPageTest`, `TripWorkspaceTest`, `PageQueryBudgetTest`, `ReadinessDashboardTest`, tinjauan ulang akhir |
| Tangkapan | 20 tangkapan (indeks, ruang kerja, Rencanakan, Persiapan, Cek; 390 dan 1440 px; terang dan gelap) dibandingkan dengan acuan 08 sampai 10; delapan tangkapan indeks dan ruang kerja dipotret ulang sesudah perbaikan gerbang | `docs/desain/tangkapan/w4/README.md` |
| Lapisan dan reflow | Bar aksi Rencanakan 106 px di 360 x 740 dan 320 x 568; baris langkah 4 x 44 px satu baris di 320; `scrollWidth` sama dengan `clientWidth` di 320 untuk kelima halaman | README tangkapan W4 |
| Papan ketik | Empat jalur lulus (Rencanakan 390, Persiapan 390, Cek 390, ruang kerja 1440). Fokus ke Urungkan belum dapat dibuktikan di pratinjau statis karena butuh server Laravel | README tangkapan W4 |
| Delivery Gate | Salinan 4 PASS, Desain 4, Interaksi 3, Kebenaran 6; dua FAIL desain (dua tombol penuh di indeks dan ruang kerja) diperbaiki W4-G1; tidak ada FAIL tanpa putusan | `.superpowers/.../delivery-gate-w4.md` |

Yang dibangun W4:
- **Indeks trip** (`IndeksTrip`): kartu trip terdekat dengan fase dan satu aksi berikutnya, kelompok Berlangsung,
  Mendatang, dan Selesai, serta "Lihat semua" berhalaman untuk trip selesai dan batal di luar lima terakhir.
- **Ruang kerja:** empat langkah (`TripSteps`, `KepalaTrip`) dengan satu predikat "tinjauan tercatat" dan satu predikat
  butir menunggu BMKG yang dipakai bersama halaman lain. Rencana belum dimulai yang tanggalnya lewat diberi penanda
  "Tanggal rencana sudah lewat" dan aksi tunggal "Ubah rencana".
- **Rencanakan** (`RencanaTrip`):
  - kecocokan untuk rencana dihitung server lewat muat ulang parsial;
  - jendela izin, termasuk "tanggal buka belum tercatat" bila separuh tercatat;
  - kalimat terbit prakiraan BMKG;
  - jam dalam zona gunung;
  - peserta 1 sampai 12;
  - flash "Rencana trip tersimpan."
- **Persiapan** (`HalamanPersiapan`):
  - centang optimis dengan Urungkan lewat antrean kiriman per halaman dan flash berkunci butir;
  - navigasi ditahan sampai pilihan terakhir tersimpan;
  - butir cuaca menunggu BMKG;
  - saringan "Hanya yang belum dikonfirmasi";
  - butir cuaca direset bila tanggal trip berubah;
  - trip selesai atau batal tidak dapat dicentang.
- **Cek kesiapan** (`HalamanKesiapan`):
  - kata DK-48 beserta semua sebabnya;
  - tiga hal yang diperiksa;
  - "Sejak cek terakhir" per status;
  - pesan hari keberangkatan;
  - alasan penolakan tinjauan satu sumber untuk halaman dan POST;
  - konfirmasi dalam transaksi dengan kunci;
  - snapshot kondisi minimal tanpa identitas, catatan, atau foto pelapor.
- **Batas laju** pada rute tulis trip, persiapan, dan kesiapan. Centang butir memakai batas sendiri, 120 per menit.
- **Perintah pembersihan PII** `memount:bersihkan-snapshot-kondisi` untuk snapshot lama, diuji tetapi BELUM dijalankan
  di basis data mana pun.

Ruling koordinator atas konflik rencana (W4-C1 sampai W4-C7):
- C1: peserta 1 sampai 12 sesuai spec bagian 6.8, dicatat GR-05.
- C2: kesiapan menilai kecocokan untuk rencana.
- C3: kosakata mesin di alasan Cek dipindah ke W9.
- C4: "Siap berangkat" di luar permukaan W4 diputus W5 atau W6.
- C5: pemilih jalur Rencanakan hanya dari simpanan.
- C6: pengecualian R-012 `Trips/Readiness` dicabut.
- C7b: label izin SIMAKSI butuh kunci stabil (W8).

Perubahan penting dari tinjauan (selain isi rencana):
- **C1 tinjauan akhir:** `TripPlan::tujuanPenilaian()` kini selalu memakai jenis dan tanggal trip di atas salinan tujuan
  yang tidak disimpan. Sebelumnya ruang kerja, Cek, dan penilaian kecocokan bisa menilai rencana lain dari tujuan
  terakhir pendaki, sehingga Tektok di jalur 30 jam dapat lolos tinjauan.
- **Pendaki tanpa profil lengkap:** label kecocokan tidak dinilai (R-006, PRD §91), tetapi syarat dasar rencana tetap
  dihitung.
- **Tinjauan pra-keberangkatan:** tercatat dengan waktunya dan tidak ditawarkan ulang. Tinjauan juga ditolak untuk
  rencana yang tanggalnya lewat.
- **Indeks dan ruang kerja:** tidak lagi menunjuk Persiapan bila sisa butirnya hanya butir yang menunggu BMKG.
- **Delivery Gate W4-G1:** tombol kepala indeks dan pemicu hapus menjadi sekunder, dan "Mulai pendakian" baru menjadi
  aksi utama pada hari keberangkatan.

OPEN ISSUE dan butir yang ditunda dengan putusan tertulis:
- **Ke W5:**
  - Beranda mewarisi `TripSteps::aksi()` dan predikat W4;
  - urutan alasan kartu (W3-C7);
  - kata "Siap berangkat" (W4-C4);
  - konflik rencana W5 nomor 1 sampai 9 diputus saat pra-cek.
- **Ke W6:**
  - penjaga satu pendakian berlangsung dan indeks unik `hiking_sessions.trip_plan_id`;
  - kata "Siap berangkat".
- **Ke W7:**
  - alasan pembatalan DK-50;
  - catatan per butir persiapan dan perlengkapan pribadi.
- **Ke W8:**
  - kunci stabil label izin (W4-C7b);
  - kalimat izin ruang kerja per keadaan jendela;
  - kesegaran prakiraan dari konteks 72 jam;
  - cache status 300 detik;
  - kartu gunung api.
- **Ke W9:**
  - kosakata mesin di alasan (W4-C3);
  - sapuan format jam;
  - halaman galat 403, 404, 419, 429, dan 500 berbahasa Indonesia (W4-6j);
  - pemecahan `Readiness.tsx` dan pakai ulang hasil `compute()`;
  - dependensi `TripController`;
  - saringan Persiapan di URL;
  - peringatan isian belum tersimpan di Rencanakan;
  - balapan dua GET pertama Persiapan;
  - test pasangan kunci flash;
  - kontras ruas kosong;
  - tahun pada tanggal `'D, j M'`.
- **PERLU KLARIFIKASI (R-024):** test PostgreSQL `PreDepartureConfirmLockTest` dan `ReadinessForecastDayTest` belum
  pernah berjalan karena tidak ada PostgreSQL lokal. Keduanya dijalankan CI Spatial sesudah izin push.
- **Menunggu izin:** klik kendali POST di peramban dan alur Urungkan butuh server lokal (Q1) atau Playwright (Q17).
- **Batas yang diterima:** "Sejak cek terakhir" tidak mendeteksi pertukaran dua butir yang tidak menggeser hitungan,
  karena snapshot menyimpan hitungan per status, bukan status per butir.

Daftar izin akhir yang bertambah karena W4 (rincian di pelacak program, butir 15 sampai 26):
- **Syarat deploy W4:**
  - `migrate:status` Supabase mencakup `2026_09_25_000917` dan `2026_09_25_000918` (`trip_reminders` kini dibaca
    Persiapan dan ruang kerja; Q22);
  - CI Spatial lulus;
  - pembersihan PII snapshot lama sebelum atau bersama deploy.
- **W4 tidak menambah migrasi.**
- **Keputusan pemilik yang tercatat:**
  - kata DK-48, peserta 1 sampai 12, dan kecocokan untuk rencana pada kesiapan (GR-05);
  - pendaki tanpa profil dan alternatif cap "Masih perlu persiapan";
  - tinjauan ulang pra-keberangkatan;
  - kedekatan "Masih perlu persiapan" dengan label Route Fit;
  - "Lihat semua" trip selesai dan batal;
  - tautan tujuan terakhir otomatis di Rencanakan;
  - harness uji perilaku klien bersama Q17.
- **Dependensi:** framer-motion 14 terpasang atas arahan pemilik, belum diimpor.

Titik lanjut: W5 Beranda. Rencananya sudah ditulis dan disimulasikan, yaitu
`scratchpad/rencana/2026-10-07-memount-v3-w5-beranda.md` milik sesi koordinator: 9 tugas, 1.921 menjadi 1.970 test.
Rencana itu dipindah ke `docs/superpowers/plans/` saat W5 dimulai, lalu dipra-cek terhadap pohon akhir W4 ini, karena
gelombang akhir W4 mengubah `TripSteps`, `PermitService`, dan `PreDepartureCheck`. Rencana W6 juga sudah ditulis (13
tugas).

### W3 Klaster Jelajah (selesai 6 Oktober 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-09-27-memount-v3-w3-jelajah.md`, 12 tugas. Ledger dan seluruh ruling (W3-C0 sampai
W3-C8, W3-1a sampai W3-10a, W3-S1 sampai W3-S6, W3-F1 sampai W3-F7, W3-G1 dan W3-G2) ada di
`.superpowers/sdd/2026-09-27-memount-v3-w3-jelajah/progress.md`. Tugas 1 sampai 3 dikerjakan 28 September; sesi
berhenti saat tinjauan Tugas 4 dan hasilnya hilang, jadi sesi 6 Oktober mengirim ulang tinjauan itu (W3-S1) lalu
menuntaskan Tugas 4 sampai 12. Setiap tugas ditinjau terpisah; Tugas 4 sampai 9 butuh satu atau dua putaran perbaikan.
Sesudahnya ada tinjauan akhir lintas tugas (1 Critical, 7 Important, 11 Minor, triase semua butir tertunda), satu
gelombang perbaikan 75 butir, satu tinjauan ulang, satu perbaikan fixture Spatial, dan satu putaran perbaikan Delivery
Gate. Nilai di bawah terukur 6 Oktober 2026 pada pohon kerja akhir W3.

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 1.806 test, 1.804 lulus, 2 dilewati, 0 gagal (W2 akhir: 1.622) | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite bawaan | 1.838 test, 1.804 lulus, 34 dilewati (Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`) | `php artisan test --compact` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --test` lulus | gerbang akhir W3 |
| Bundel | `app` JS 355.731 B (W2 akhir 354.780 B); `app` CSS 86.590 B (angka mutlak ikut view terkompilasi, W2-C10); potongan Jelajah 28,3 kB, rincian jalur 19,3 kB, penilaian kecocokan 8,8 kB, Bandingkan 9,4 kB, Simpanan 7,0 kB, tautan bagikan 4,5 kB; lisensi Phosphor dan MapLibre ada di bundel | build 6 Oktober |
| Pratinjau | 14 halaman (W2: 9; W3 menambah gunung, kecocokan, simpanan, tautan bagikan, bandingkan) | suite `Pratinjau` 14/14 |
| Anggaran kueri | Setiap halaman baru diuji tidak tumbuh bersama data: Jelajah (4 lawan 24 jalur), halaman gunung, penilaian kecocokan, rincian jalur (pos dan simpanan ditumbuhkan), Simpanan (2 lawan 12), Bandingkan (2 lawan 5), tautan bagikan | `ResultListBudgetTest`, `MountainPageTest`, `FitAssessmentPageTest`, `TrailDetailPageTest`, `SavedRoutesTest`, `PageQueryBudgetTest`, `ComparisonShareTest` |
| Tangkapan | 34 tangkapan (8 halaman x 390 dan 1440 px x terang dan gelap, tiga centang Simpanan, fokus bar aksi) dibandingkan dengan acuan 02 sampai 07 dan 15; enam halaman dipotret ulang sesudah perbaikan gerbang | `docs/desain/tangkapan/w3/README.md` |
| Lapisan bawah | Bar aksi rincian jalur 69 px (360 x 740) dan 82 px (320 x 568), satu baris; baki banding Simpanan 58 sampai 78 px (20 sampai 23 persen layar) di 360 x 740 dan 58 sampai 98 px (27 sampai 34 persen) di 320 x 568; panel saringan dan peta 1440 tidak tertutup strip pendakian | `.superpowers/.../t12-pengukuran.md` |
| Papan ketik | Empat jalur lulus (Jelajah 390, rincian jalur 390, Simpanan 390, Bandingkan 1440). Simpan lalu fokus ke Urungkan belum dapat dibuktikan di pratinjau statis karena butuh server Laravel | README tangkapan W3 |
| Delivery Gate | Sesudah putusan: Salinan 6 PASS, Desain 11, Interaksi 12, Kebenaran 10; delapan FAIL ditunda dengan putusan tertulis (lihat di bawah), tidak ada FAIL tanpa putusan | `.superpowers/.../delivery-gate-w3.md` |

Yang dibangun W3:
- tata bahasa kepercayaan: `LabelKecocokan` (Cocok, Perlu persiapan, Kurang cocok, Belum dinilai dari server),
  `StatusAkses` (UNKNOWN tidak pernah Buka), `Kesegaran`, `LabelSumber`, `BarisStatus`, dengan satu aturan catatan
  status penentu (`OfficialStatusService::catatanPenentu()`) dan satu scope urutan untuk semua pembaca status resmi;
- halaman gunung `/mountains/{mountain:slug}` dan penilaian kecocokan `/trails/{trail:slug}/kecocokan` dengan `?trip=`;
- Jelajah: kartu jalur, saringan dengan hitungan langsung, chip tafsiran yang dapat dilepas, simpan idempoten dengan
  Urungkan, peta berpola garis per label dengan legenda, pemilih, dan kartu pratinjau yang dipasang hanya saat terlihat;
- rincian jalur ditulis ulang (angka sekali jalan naik, tantangan, "Cocokkah untuk saya?", profil elevasi, pos di
  sepanjang garis, kondisi dengan prakiraan per tanggal trip, bar aksi), Simpanan dengan catatan pribadi dan baki banding
  satu baris, tautan bagikan FR-23 (tabel `comparison_shares`, hanya hash token, halaman publik `/b/{token}` tanpa
  indeks, tanpa catatan, label kecocokan, atau nama pembuat), Bandingkan tanpa pemenang, dan tombol v3 di halaman publik
  jalur (Blade);
- kalimat R-012 "Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan." di setiap layar pembaca yang
  menampilkan status resmi, dijaga test cakupan sumber (pengecualian bernama: meja kurator, dan `Trips/Readiness` yang
  wajib dicabut rencana W4).

Ruling koordinator atas konflik rencana (W3-C1 sampai W3-C8):
- C1: halaman publik `/b/{token}` dibangun sesuai spec v3; akses tanpa akun (P8) dan migrasinya masuk izin akhir.
- C2: Banding milik tab Trip.
- C3: baki Jelajah dan `--baki-banding` dilepas; baki Simpanan memakai `BarAksi`.
- C4: tiga angka, bukan empat, karena `elevation_loss_m` adalah turunan pulang (R-007).
- C5: PRD tidak disunting sebelum pemilik memutus P8.
- C6: trip orang lain dijawab 403 sampai Q5 diputus.
- C7: alasan kartu dari `TrailFitService::alasan()`; urutan diputus W5.
- C8: `TrailRow.tsx` dihapus di gelombang perbaikan akhir; niat test lamanya diperbarui menurut kartu tiga angka.

Perubahan penting dari tinjauan (selain isi rencana):
- C1 tinjauan akhir: Urungkan sesudah menghapus jalur tersimpan yang kemudian ditarik dari terbit kini memulihkan
  catatannya lewat penanda pemulihan sekali-pakai di sesi pemilik.
- `ConsiderationOutcome::SUDAH_ADA`: simpan atas jalur yang sudah tersimpan (halaman basi) tidak lagi menawarkan
  Urungkan yang menghapus catatan lama.
- R-024: pencarian kata kunci kini tidak peka huruf besar di PostgreSQL, dan urutan NULL `effective_at` sama di SQLite
  dan PostgreSQL. Keduanya punya test `tests/Spatial`.
- Rincian jalur menampilkan kesegaran status resmi, dan awalan waktu ("Diverifikasi", "Diterbitkan", "Berlaku sejak")
  mengikuti kolom yang benar-benar dipakai.
- Trip "Siap berangkat" ikut dihitung sebagai trip terdekat. Prakiraan cuaca menampilkan baris untuk tanggal trip, atau
  menyatakan jujur bahwa data belum mencapai tanggal itu.
- Kontras angka pos di atas aksen kuning pada tema gelap: dari sekitar 1,7:1 menjadi 8,18:1 lewat token
  `--accent-pos-ink`.
- `OfficialStatusSeederTest` membekukan jam ke 15 September 2026. Test itu sebelumnya gagal sejak 1 Oktober, karena
  catatan Merbabu berakhir 30 September.

OPEN ISSUE dan butir yang ditunda dengan putusan tertulis:
- **Ke W4:** label data "Booking izin pendakian (SIMAKSI)"; rencana W4 wajib mencabut pengecualian R-012 untuk
  `Trips/Readiness.tsx`.
- **Ke W5:**
  - kartu di dalam kartu laporan komunitas;
  - "Kecocokan dasar" berulang di sel Bandingkan dan "Kenapa?" tanpa nama jalur di `FitLine`;
  - rute `trails.timbang` + `toggle()` dihapus bila Beranda tidak memakainya;
  - urutan alasan kartu (W3-C7).
- **Ke W6:** kaca bar aksi yang lewat di atas peta rincian saat bergulir (R-10, INFERENSI, butuh keputusan desain).
- **Ke W8:**
  - WAJIB kolom eksplisit tanpa `geometry` pada kandidat Jelajah, rekomendasi, dan simpanan;
  - penjaga kemonotonan dan jarak pos dari garis;
  - semantik COALESCE urutan status resmi (cukup di satu scope);
  - kurasi `elevation_loss_m` dan sumber lingkungan;
  - default skema `technical_demand`, `navigation_complexity`, dan `camping_available` yang menyamar sebagai data, kini
    juga tampil di halaman publik `/b/` sebagai "Berkemah: Tidak";
  - artefak `Trail::hasGeometry()` di SQLite.
- **Ke W9:**
  - istilah Inggris "elevation gain" dan angka tanpa titik ribuan di kalimat mesin;
  - format jam "19:33" lawan DESIGN "15.06";
  - pengumuman tunggal pembaca layar;
  - refaktor `TrailDetailPage` (11 dependensi);
  - `trustProxies`, `encryptHistory`, dan retensi baris tautan bagikan (menunggu `docs/KEBIJAKAN-DATA-PRIBADI.md`);
  - kedaluwarsa penanda pemulihan sesi;
  - uji konkurensi `lockForUpdate` di PostgreSQL;
  - anggaran ukuran Jelajah pada batas 500 kandidat;
  - aksi kolom kanan rincian 1440 yang membungkus.
- **Menunggu izin:** klik setiap kendali di peramban dan alur simpan-Urungkan butuh server Laravel lokal (Q1
  `memount:demo`) atau Playwright (Q17).

Daftar izin akhir yang bertambah karena W3:
- migrasi `2026_09_28_000100_create_comparison_shares_table` WAJIB dijalankan sebelum atau bersama deploy kode W3,
  karena Simpanan, Bandingkan, dan `POST trails/shares` mengueri tabel itu (tanpanya dua halaman itu 500);
- akses tanpa akun untuk `/b/{token}` (P8);
- Q5 (403 atau 404 untuk trip orang lain);
- catatan bahwa `MAP_ATTRIBUTION` di `.env` pemilik bisa menimpa atribusi OpenTopoMap baru.

Catatan data: `OfficialStatusSeeder` mencatat status September 2026 yang kedaluwarsa paling lambat 31 Oktober 2026. Bila
seeder itu pernah dijalankan di Supabase, status gunung tersebut kini terbaca UNKNOWN (perilaku benar menurut R-013).
Karena itu kurasi status resmi W8 mendesak.

Titik lanjut: W4 Klaster Trip. Rencana `docs/superpowers/plans/2026-10-06-memount-v3-w4-trip.md` (8 tugas, disimulasikan
di salinan akhir W3 Tugas 9, 1.764 menjadi 1.811 test); pra-cek konflik terhadap pohon akhir W3 sebelum Tugas 1.

### W2 Kerangka (selesai 28 September 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-09-27-memount-v3-w2-kerangka.md`, 8 tugas. Rencana itu ditulis subagen dan
disimulasikan di salinan W1 akhir. Setiap tugas ditinjau terpisah, sebagian dengan beberapa putaran perbaikan.
Sesudahnya ada tinjauan akhir lintas tugas, satu gelombang perbaikan beserta dua putaran lanjutan, dan tinjauan ulang
terlingkup. Ledger dan seluruh ruling (W2-C1 sampai W2-F7) ada di
`.superpowers/sdd/2026-09-27-memount-v3-w2-kerangka/progress.md`. Nilai di bawah terukur pada pohon kerja akhir W2.

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 1.622 test, 1.620 lulus, 2 dilewati, 0 gagal (W1 akhir: 1.559) | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite bawaan | 1.642 test, 1.620 lulus, 22 dilewati | `php artisan test --compact` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --test` lulus | gerbang akhir W2 |
| Bundel | `app` JS 354.780 B (W1 akhir 355.096); `app` CSS sekitar 80,2 kB, angka mutlaknya ikut view terkompilasi lokal (OPEN ISSUE, W2-C10) | build 28 September |
| Pratinjau | 9 halaman (Beranda, Jelajah, Jelajah kosong, Jelajah saat mendaki, Trip, Mendaki, Progres, detail jalur, pameran) | suite `Pratinjau` 9/9 |
| Anggaran kueri | +2 kueri tetap per kunjungan penuh (hitungan Simpanan dan pendakian berlangsung), 0 pada muat ulang parsial yang tidak memintanya dan pada mode lapangan; lembar Pengingat 5 kueri tetap hanya saat dibuka | `PageQueryBudgetTest`, `SavedCountPropTest`, `ActiveHikeStripTest`, `ReminderSheetTest` |
| Tangkapan | 20 tangkapan 390, 1024, dan 1440 px dengan perbandingan terhadap acuan 01, 02, 04, 11, 13, 15 | `docs/desain/tangkapan/w2/README.md` |
| Papan ketik | Tiga jalur lulus: Beranda 390 px (lewati, lonceng, avatar, isi, tab; lembar Pengingat Escape kembali ke lonceng), detail jalur 390 px (tombol kembali, tanpa bilah tab), Jelajah 1440 px (lockup, segmen, Simpanan, avatar; menu popover) | README tangkapan W2 |
| Delivery Gate | R-02, R-09, R-10, R-26, R-27, R-32, R-35 lulus; dua komentar "aman" sudah dirumuskan ulang | `.superpowers/.../delivery-gate-w2.md` |

Yang dibangun W2:
- kepala per halaman (`PageHeader` dengan tepat satu `akar` atau `induk`) di 38 halaman, dengan tombol kembali ke induk
  logis dan avatar ke Profil;
- kerangka ponsel dengan bilah tab pil mengambang, dan kerangka layar lebar mulai 1024 px dengan header 68 px, navigasi
  segmen, Simpanan berhitungan, dan menu avatar popover;
- strip pendakian aktif;
- toast Urungkan dan pesan jaringan yang dipasang sekali di kerangka;
- lembar Pengingat dari lonceng Beranda, dengan tautan sistem resmi untuk tenggat izin;
- data contoh diperluas (lima simpanan, pengingat H-3 dan H-1, satu aturan izin tingkat gunung bertanda DATA CONTOH).

Keputusan yang mengikat gelombang berikutnya:
- **Konflik rencana (W2-C1 sampai C9).** Moderasi, Admin, dan Keluar di ponsel pindah ke kartu Akun di Profil (spec v3
  bagian 5.1, disetujui pemilik). h1 akar tab sama dengan label tabnya. Lencana Data contoh di ponsel berada di bawah
  judul.
- **Toast (W2-5i, W2-5k, W2-5l).**
  - Umur toast terikat ke komponen yang memanggil `useKerangka()`.
  - Aksi dengan Urungkan di halaman yang sama memakai `preserveState: true`.
  - Konfirmasi sesudah pindah halaman dimunculkan halaman tujuan dari flash, dengan Urungkan sisi server.
- **Dialog pengirim formulir (W2-5e)** menangani `onNetworkError` di dalam dialog.
- **Variabel lapisan.** `--bilah-tab`, `--strip-pendakian`, `--bar-aksi`, `--baki-banding`, `--pesan-jaringan`, dan
  `--kepala-atas` ditulis komponennya sendiri atau punya cadangan tanpa `:has()`. `scroll-padding` menghitung semuanya
  kecuali toast.
- **Konflik versi aset (W2-F6, W2-F7).** Hanya muat ulang lembar Pengingat yang memuat penuh otomatis. Kunjungan async
  lain menampilkan pemberitahuan versi baru, dan pengguna yang memilih memuat ulang, supaya isian tidak hilang.

Sisa yang tercatat:
- **Ke W3:**
  - baki banding ringkas di layar sempit (W2-F1);
  - pemilik tab halaman Banding (W2-F2);
  - peta Jelajah lengket di layar lebar (W2-F3);
  - anggaran tinggi bar aksi (W2-F5, B-6).
- **Ke W5:** props Dasbor dibuat malas untuk muat ulang parsial (W2-6b).
- **Ke W6:**
  - pemilih pendakian aktif disatukan (W2-4b);
  - race `start`/`networkError`, lompatan tata letak pesan jaringan, dan pengumuman `role=status` (W2-5j, B-5).
- **Ke W8:** `PermitService` menyebut izin terbuka tanpa tanggal (W2-6c, R-007).
- **Ke W9:**
  - ukuran CSS yang ikut view terkompilasi (W2-C10);
  - salinan bawaan "Ini area aman aplikasi" di konfirmasi sandi.
- **Diterima:** popover menu akun tanpa cadangan untuk peramban 2023 (W2-2c).
- **Izin akhir:**
  - aturan satu pendakian berlangsung per pengguna dan indeks unik `hiking_sessions.trip_plan_id` (butir 8);
  - rekomendasi privasi `encryptHistory` dan `Inertia::clearHistory()` saat keluar (butir 9, keputusan pemilik lewat
    `docs/KEBIJAKAN-DATA-PRIBADI.md`).
- **Sudah ditutup:** test yang gagal karena nama faker berapostrof (factory dan `RecommendationExclusionTest`,
  27 September).

Titik lanjut: W3 Klaster Jelajah. Rencananya sedang ditulis paralel di scratchpad (P-R3).

### W1 Fondasi desain (selesai 27 September 2026, di-commit 11 Oktober 2026)

Rencana `docs/superpowers/plans/2026-09-26-memount-v3-w1-fondasi-desain.md`, 12 tugas, masing-masing ditinjau terpisah.
Sesudahnya ada tinjauan akhir lintas tugas, satu gelombang perbaikan, dan tinjauan ulang terlingkup. Ledger dan
seluruh ruling (W1-4a sampai W1-F6) ada di `.superpowers/sdd/2026-09-26-memount-v3-w1-fondasi-desain/progress.md`.
Nilai di bawah terukur pada pohon kerja akhir W1.

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 1.559 test, 1.557 lulus, 2 dilewati, 0 gagal (sebelum W1: 1.338) | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite bawaan (Unit, Feature, Spatial) | 1.579 test, 1.557 lulus, 22 dilewati; 20 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN` | `php artisan test --compact` |
| TypeScript dan format | `npx tsc --noEmit` 0 galat; `vendor/bin/pint --test` lulus | gerbang akhir W1 |
| Bundel | `app` CSS 79.056 B (sebelum W1 62.652), `app` JS 355.096 B (sebelum W1 353.916), potongan pameran `Primitif` 28.893 B yang hanya dimuat `/dev/primitif`; lisensi Phosphor dan MapLibre ikut bundel | build 27 September 12.45, `ThirdPartyLicenseTest` |
| Huruf | Plus Jakarta Sans dihosting sendiri; halaman berteks Indonesia memuat 27.348 B (subset Latin), di disk 49.076 B, anggaran 102.400 B | tugas 12 langkah 6 |
| Tangkapan | 14 halaman (pameran, Dasbor, Jelajah, Jelajah kosong; 390 dan 1440 px; terang dan gelap) ditambah bukti fokus, dibandingkan dengan gambar acuan | `docs/desain/tangkapan/w1/README.md` |
| Delivery Gate antislop | R-02 kini lulus (9 em dash lama di `docs/ARCHITECTURE.md` diganti). Dua butir sengaja dijadwalkan: aksen kuning pos belum tampil di layar mana pun (ruling W1-12b, ditempatkan W3 sampai W5) dan susunan Dasbor/Jelajah masih generik (ruling W1-12a, W2 sampai W5) | `.superpowers/.../delivery-gate-w1.md` |
| Jam pilot glyph | 0 jam 3 menit (Tugas 4); putaran perbaikan bentuk pos W1-4a tidak tercatat waktunya | laporan Tugas 4 |

Yang dibangun W1:
- token v3 terang, gelap, dan dua tema lapangan (mandiri, empat kombinasinya diuji kontras);
- huruf dan ikon Phosphor dengan tiga glyph domain pilot;
- primitif di `resources/js/components/ui/`, dengan fokus, target 44 px dua sumbu, dan toast Urungkan yang bisa dicapai
  papan ketik;
- pameran `/dev/primitif`, dijaga dua lapis dan hanya di local;
- data contoh `DemoSeeder` beserta perintah `memount:demo`. Perintahnya belum pernah dijalankan (Q1). Penjaganya
  menolak apa pun selain SQLite di `database/` dan memutus cache, antrean, surel, disk, broadcasting, disk foto
  laporan, dan sesi dari `.env`;
- lencana "Data contoh" di layout aplikasi dan halaman publik jalur;
- harness pratinjau (suite `Pratinjau` dan router statis yang tidak pernah menjalankan `index.php`);
- `DESIGN.md` bagian Turunan v3.

Pagar keamanan baru yang wajib diikuti gelombang berikutnya:
- awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` untuk perintah Artisan di luar PHPUnit, `npm run build`,
  dan `php -S`. Di PowerShell pakai `$env:DB_URL="(empty)"`;
- `phpunit.xml` menetralkan `DB_*` lewat `<env force>` dan `<server>`.

Sisa yang tercatat:
- **Aset dan merek:** ikon PNG di `public/icons/` belum berganti warna (aset logo menunggu gerbang DK-16); set lengkap
  glyph menunggu Q15.
- **Ke W2:** bilah tab pil dan kerangka.
- **Ke W3:** pemindahan enam komponen domain; Jelajah memeriksa cabang "menunggu" sebelum menyalahkan saringan
  (W1-F1); permukaan Blade halaman publik jalur (W1-F2).
- **Ke W6:** warna teks warisan, `caret-color`, `accent-color`, dan centang di dalam tema lapangan yang berbeda dari
  tema aplikasi.
- **Ke W8:** kolom `technical_demand` dan `navigation_complexity` tanpa UNKNOWN, jadi jalur tanpa data tercatat
  MODERATE. Utang R-006 ini butuh migrasi.
- **Ke W9:** pemeriksaan `forced-colors`; tombol dan judul Blade halaman galat dan offline (W1-F2).
- **Konvensi:** nama prop primitif yang sudah ada tidak diganti; primitif baru memakai `nilai`, `onUbah`, dan `label`
  (W1-F3).
- **Menunggu CI atau izin:** bukti test symlink penjaga data contoh menunggu CI Linux (dilewati di Windows); perilaku
  fokus baru dibuktikan dengan klik-lewat, runner end-to-end menunggu izin Playwright.
- **Sudah tertutup:** butir 17 lama (pemberitahuan lisensi di bundel) oleh Tugas 3.

**Arahan pemilik terakhir** (26 September 2026, verbatim): "lanjut, commit pastikan semua terintegrasi jadi saya bisa
cek di local host". Arahan sebelumnya (verbatim di spec v3 bagian 17.1): restrukturisasi repo untuk
persiapan push dengan rapi dan profesional; folder `docs/` berisi DESIGN, PRODUCT, PRD, dan AGENTS sebagai aturan
tim yang detail dan lengkap; skill UI/UX dipasang dan selalu diterapkan dengan dibandingkan dan digabung. Keputusan
eksekusi: W0 lalu WR, subagen dan peninjau per tugas, tanpa commit sampai tonggak M1.

**Keadaan tonggak M1 (W0 dan WR):**

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite Unit dan Feature, salinan kerja | 1.338 test, 1.337 lulus, 0 gagal, 0 error, 1 dilewati, 7.610 asersi | WR Tugas 10 Step 1 |
| Suite Unit dan Feature, ekspor bersih | 1.338 test, 1.337 lulus, 0 gagal, 1 dilewati, 6.950 asersi, dijalankan pada 946 berkas yang akan di-commit (tanpa berkas yang diabaikan git, tanpa folder Q19, tanpa berkas yang sudah terhapus) | Ruling W15c |
| TypeScript, build, Pint | `npx tsc --noEmit` 0 galat; `npm run build` sukses dalam 1,74 s (bundel terbesar `maplibre-gl` 768 KB, gzip 209,97 kB; `app` 345 KB); `vendor/bin/pint --test` lulus untuk seluruh repo | WR Tugas 10 Step 2 sampai 4 |
| Pemindai rahasia | Lulus atas berkas dilacak dan berkas baru yang tidak diabaikan git; ketujuh polanya disebut di `SECURITY.md` | `SecretsNotInRepoTest` |
| Audit dependensi | Composer bersih; npm merah: 1 critical GHSA-jrc7-96c5-q579 pada `maplibre-gl` 4.7.1 (npm menawarkan 6.11.2, perubahan breaking; Q20) | WR Tugas 10 Step 6 |
| Suite Spatial | PERLU KLARIFIKASI: belum pernah hijau di PostGIS. Cacat `SpatialTestCase` (galat "Target class [config]" sebelum terhubung) sudah diperbaiki, dan `SpatialHarnessTest` membuktikan suite kini mencapai koneksi yang ditunjuk DSN (port 1 ditolak dengan `SQLSTATE[08006]`). Hasil hijau pertama menunggu CI setelah push yang diizinkan; Docker tidak terpasang di mesin pemilik | WR Tugas 10 Step 9 |
| CI publik | FAKTA (API GitHub, 26 September 2026): ada dua run Actions dan keduanya gagal. Run 35658731779 (commit `6d9e090`) lulus build, Pint, Unit, dan Feature, lalu gagal di langkah Spatial; run 35654079411 (commit `855132a`) gagal di Unit dan Feature | `docs/PRD.md` §128 dikoreksi |
| Pelaporan kerentanan privat | FAKTA: private vulnerability reporting repositori ini `enabled: false`; `SECURITY.md` dan formulir isu sudah menyiapkan jalurnya beserta cadangannya | Keputusan 8 |
| Status migrasi Supabase | PERLU KLARIFIKASI sampai izin baca Supabase (Q22) | WR Tugas 10 Step 10 |
| Dokumen tim | `docs/AGENTS.md`, `docs/README.md`, `docs/PRD.md`, penunjuk `docs/DESIGN.md` dan `docs/PRODUCT.md`, `SECURITY.md`, `database/README.md`, `docs/lisensi-pihak-ketiga.md` | `TeamDocsIndexTest`, `RepoPolicyFilesTest`, `ThirdPartyLicenseTest` |
| Struktur repo | Root terinventaris; CI dipecah menjadi `test.yml`, `security.yml`, dan `deploy.yml` manual; `docker-compose.yml` PostGIS; templat PR dan isu; pemeriksaan "berkas ada" memakai isi repo, bukan disk | `RepoHygieneTest`, `CiWorkflowsTest`, `LocalPostgisComposeTest`, `PrdReferenceIntegrityTest` |
| Blok alat | SHA-1 blok Laravel Boost di `CLAUDE.md` dan `AGENTS.md` tetap `3e233a75a9edbcc7cadb54f40eb55cc2f8314386` | WR Tugas 10 Step 7 |
| `tests/e2e` | TERBLOKIR sampai Q17 | WR Tugas 9 |
| Commit dan push | Commit dibuat atas izin pemilik 26 September 2026 dalam tiga commit (riset dan rencana; prototype; kode, W0, dan WR); push belum | R-018 |

**Ringkasan diff untuk seri commit.** Setiap berkas masuk kelompok terakhir yang menyentuhnya; rincian per berkas ada
di `.superpowers/sdd/2026-09-26-memount-v3-wr-struktur-repo/kelompok-diff.json` (diabaikan git).

| Kelompok | Berkas | Baru | Diubah | Dihapus | Dipindah |
|---|---|---|---|---|---|
| Sesi sebelumnya (migrasi React, pensiun Livewire, konsolidasi dokumen) | 555 | 315 | 116 | 121 | 3 |
| W0 stabilisasi | 32 | 23 | 9 | 0 | 0 |
| WR struktur repo dan dokumen tim | 76 | 56 | 17 | 0 | 3 |
| Folder skill Q19 (tidak di-stage) | 209 | 209 | 0 | 0 | 0 |

Dieksekusi 26 September 2026 sebagai tiga commit yang masing-masing utuh: riset, spec, dan rencana (`docs/riset`,
`docs/superpowers`, `docs/audit`); prototype (`docs/desain/prototype`); lalu kode dan struktur repo (migrasi React dan
pensiun Livewire, W0, WR). Pembagian per sesi tidak dipakai karena banyak berkas membawa perubahan dari beberapa sesi
(misalnya `Icon.tsx` dibuat saat migrasi React lalu disunting WR), sehingga commit per sesi tidak dapat berjalan
sendiri. Folder Q19 dan berkas yang diabaikan git tidak ikut.

**Keputusan pemilik yang ditunggu:**

1. Diputuskan 26 September 2026 ("lanjut, commit"): commit dibuat. Yang masih ditunggu: ruang lingkup aturan commit (semua anggota atau hanya agen) di `docs/AGENTS.md` bagian "Kesepakatan kerja dan izin".
2. Persetujuan isi PRD v3.0; berkasnya di-commit sebagai draf konsolidasi atas izin commit (§143).
3. Q17 Playwright untuk `tests/e2e` (WR Tugas 9 terblokir).
4. Q18 `LICENSE` dan `.github/CODEOWNERS`; `composer.json` masih memuat `"name": "laravel/laravel"` dan `"license": "MIT"` warisan kerangka.
5. Q19 folder skill: 14 folder (209 berkas) di `.agents/skills/` belum dilacak dan tidak di-stage.
6. Q20 `maplibre-gl`: upgrade ke 6.x dengan uji peta (butuh izin), atau menerima risiko secara tertulis dengan tanggal tinjau ulang; `security.yml` merah sampai diputuskan.
7. `jelajah-gunung.zip`: usul arsipkan PRD NgodingPakeAI dan delapan berkas features ke `docs/arsip/`, lalu hapus zip-nya.
8. Aktifkan GitHub private vulnerability reporting sebelum push (FAKTA: mati); pertimbangkan juga secret scanning dengan push protection dan proteksi branch.
9. Push ke `origin/hardening` setelah keputusan di atas (R-018). Jadwal mingguan `security.yml` baru berjalan setelah workflow itu ada di cabang bawaan `main`.
10. Izin baca Supabase (Q22): `php artisan migrate:status`, advisor keamanan, RLS pada 12 tabel yang dibuat sesudah migrasi `2026_09_20_000100` (ditambah `trail_considerations` bila migrasi `2026_09_25_002314` belum berjalan), dan satu kueri baca akun `@example.com`. `DatabaseSeeder` membuat admin berkata sandi "password" yang tertulis di README publik; apakah seeder itu pernah dijalankan ke Supabase TIDAK DIKETAHUI.
11. Blok NgodingPakeAI di `AGENTS.md` memuat Workspace ID di repositori publik (bukan kredensial).
12. `docs/db/schema-snapshot-sebelum-hardening.md` memuat ref proyek Supabase yang sudah ada di `origin/hardening`.
13. Q21 prototype: foto dan huruf yang ditanam di bundel (dicatat di `docs/lisensi-pihak-ketiga.md`). Bila pemilik memilih menyimpannya lokal saja, `RepoHygieneTest`, `.gitignore`, `docs/desain/prototype/README.md`, `docs/README.md`, `docs/AGENTS.md`, dan daftar lisensi diubah bersama.
14. Catatan "Transitional duplication" yang basi di `.claude/rules/mountain-core.md` baris 8.
15. `composer run setup` menjalankan `php artisan migrate --force`; dengan `.env` yang menunjuk Supabase, skrip itu memigrasi produksi. Usul: keluarkan langkah migrate dari `setup` atau beri penjaga.
16. `DatabaseSeeder` belum punya penjaga produksi. Usul: tolak berjalan bila lingkungannya produksi atau host basis datanya tampak seperti Supabase.
17. Pemberitahuan lisensi di bundel produksi (Phosphor MIT, MapLibre BSD-3) dibuang minifier; diputuskan bersama penulisan ulang registri ikon di W1 (DK-39).

**Langkah berikutnya, berurutan:** pemilik mengecek aplikasi di localhost (basis datanya perlu diputuskan: Supabase
lewat `.env`, yang mungkin belum menjalankan migrasi terbaru, atau basis data lokal); pemilik memutuskan daftar di
atas; private vulnerability reporting diaktifkan; push atas izin, lalu hasil `test.yml` dan `security.yml` dibaca
(termasuk hasil pertama suite Spatial di PostGIS); rencana rinci W1 ditulis dengan `writing-plans`.

---

## 0-26. Catatan 26 September 2026 sebelum WR (riwayat)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu rencana induk
> `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` dan spec
> `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (prototype pemilik sebagai patokan desain;
> keputusan DK-30 sampai DK-58; §15 daftar izin Q1 sampai Q22). Rencana rinci yang siap dieksekusi, berurutan:
> `docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md` lalu
> `docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`. Periksa keadaan nyata dulu: `git status`,
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik 25 September 2026 (verbatim):** "dan disini saya telah membuat sebuah prototype untuk app memount
nya jadikan patokan untuk desaign.md dan ui/ux nya riset detail dan mendalam analisis imk nya kembangkan desain
tersebut sesuai kan pula dengan logic, workflow, konsep, dan realita nya, dll. kembangkan desain dan architecture
nya." Berkasnya `Memount Prototype.html` di root (5,3 MB, belum dilacak; 16 layar). Jawaban pemilik atas enam
pertanyaan keputusan dicatat verbatim di spec v3 §1: pendekatan A irisan vertikal; data contoh lokal saja; paket
luring disetujui dengan riset rujukan dan fitur input data resmi; foto terkurasi berlisensi; huruf dan ikon
didelegasikan ke riset.

**Keadaan 26 September 2026:**

| Butir | Keadaan |
|---|---|
| Analisis prototype | Selesai: 16 layar dibongkar dan dibaca, kontras 50 pasangan dihitung (43 lolos), cacat diukur di peramban (kartu Beranda terpotong 305 dari 468 px). Temuan di spec v3 §3 |
| Suite nyata | 1.252 test, 1.231 lulus, 5 gagal, 15 error, 1 dilewati. Penyebab di spec v3 §3.5 (FR-22 setengah jadi, komponen Simpanan hilang, `docs/fitur/` tidak ada, label navigasi lama di test, props mati, tugas pengingat tidak dipantau). Angka "1.159 dan 1.158" di catatan 25 September di bawah sudah basi |
| Riset luring dan data resmi | Selesai: lima catatan dan laporan (lihat prompt di atas) |
| Riset huruf dan ikon | Selesai: tiga catatan dan laporan. Keputusan atas delegasi pemilik: Plus Jakarta Sans 400 sampai 800 dengan angka tabular (Geist, Geist Mono, Newsreader dilepas); Phosphor dengan bobot per ukuran ditambah 8 sampai 12 glyph domain dari tanda "Jalur Berpos" |
| Spec v3 | Disetujui pemilik 26 September 2026 ("setuju"); addendum §17 (DK-51 sampai DK-58); koreksi 26 September di §3.2 (skema FR-25 ternyata rintisan) dan §3.5 (12 error, bukan 13; dua cacat runtime); izin dan keputusan terbuka di §15 (Q1 sampai Q22) |
| Rencana | Induk W0 sampai W9 di tingkat gelombang (tugas bernomor dengan berkas, test, keputusan yang ditunggu, dan syarat selesai). Rinci W0: 8 tugas, 77 langkah, diuji kering subagen di salinan proyek (1.268 test, 0 gagal). Rinci WR: prasyarat P1 sampai P4 dan 10 tugas, 127 langkah. Rencana tingkat langkah W1 sampai W9 ditulis sebelum gelombangnya dimulai. Ketiganya ditinjau koordinator: klaim utama dicocokkan ke kode, label bukti diluruskan |
| Kode aplikasi | Belum diubah |
| Memori agen | `prototype-patokan-desain.md` diperbarui (huruf Plus Jakarta Sans dan ikon Phosphor, bukan Geist dan Lucide); `skill-ui-wajib-gabung.md` ditambahkan |

**26 September 2026, lanjutan.** Pemilik menyetujui spec v3 ("setuju") dan menambah arahan: tulis rencana W0
sampai W9; restrukturisasi repo untuk persiapan push dengan membandingkan dan menggabungkan dua panduan struktur
(monorepo dan empat repo); folder `docs/` berisi DESIGN, PRODUCT, PRD, AGENTS sebagai aturan tim yang detail dan
lengkap (visi, misi, ekspektasi, kesepakatan, riset lewat brainstorming, cara audit); pasang dan selalu terapkan
ui-ux-pro-max, Claude Code, design-taste-frontend, digabung dengan impeccable dan skill lain. Verbatim di spec v3
§17.1. Yang sudah dikerjakan: skill terpasang dan ditinjau (spec §17.2); addendum spec §17 (DK-51 sampai DK-58:
tetap monolit modular Laravel, `docs/PRD.md` dan `docs/AGENTS.md` kanonis, `DESIGN.md` dan `PRODUCT.md` tetap di
root karena dibaca impeccable dan antislop, CI dipecah, `docker-compose.yml` PostGIS, perapian root, urutan wewenang
skill, tonggak M1 siap push = W0 + WR); gelombang WR ditambahkan ke spec §12; izin baru Q17 sampai Q19.

**Temuan saat menyusun rencana (26 September 2026, diverifikasi koordinator):**

- FAKTA: dua cacat runtime lolos suite. "Hapus" di Simpanan mengirim DELETE ke `/trails/compare/{id}`, rute yang
  tidak ada (`php artisan route:list`), karena mengimpor keluaran Wayfinder basi yang diabaikan git; `npx tsc` lulus
  hanya karena keluaran basi itu. "Hapus dari perbandingan" memanggil `router.delete(undefined)`, karena server
  mengirim untaian URL (`RouteComparisonController.php:151`) sedangkan komponen membaca `{metode, url}`
  (`TabelBanding.tsx:17`). Keduanya ditutup W0 dengan test regresi.
- FAKTA: migrasi FR-25 `personal_targets`, `user_owned_items`, dan `add_source_and_recorded_values_to_hiking_history`
  masih rintisan kosong (belum dilacak git); empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`,
  `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) lulus tanpa menguji apa pun. Diisi di W7.
- FAKTA: `.github/workflows/ci.yml` memakai `actions/checkout@v4` dan `actions/setup-node@v4` (Node 20), padahal
  GitHub melepas Node 20 dari runner pada 23 September 2026. Push sebelum WR Tugas 3 kemungkinan besar membuat CI
  merah.
- FAKTA: repo GitHub publik; Workspace ID NgodingPakeAI dan ref proyek Supabase sudah terbaca di `origin/hardening`
  (Q21). `maplibre-gl` 4.7.1 terkena CVE-2026-85061 (Q20). 13 tabel yang dibuat sesudah migrasi penutupan PostgREST
  tidak menyalakan RLS di migrasinya (Q22).
- INFERENCE: enam migrasi bertanggal 25 September belum dijalankan di Supabase (bagian 6 mencatat migrasi terakhir yang
  dijalankan `2026_09_21_000100`); bila benar: halaman Simpanan tetap terbuka dan menawarkan editor catatan (mode ketat
  Eloquent mati, kolom yang hilang terbaca null), tetapi menyimpan catatan gagal dengan galat server; halaman trip
  membaca kolom `end_date`, `participants_count`, `reminders_enabled`, dan tabel `trip_reminders` saat merender, jadi
  kemungkinan galatnya terjadi saat halaman dibuka, bukan hanya saat menyimpan (INFERENCE, belum diverifikasi
  menjalankan aplikasi). Verifikasi butuh izin baca (Q22).

**Keputusan pemilik 26 September 2026, setelah rencana W0 dan WR ditinjau penuh oleh koordinator:** mulai eksekusi
W0 lalu WR, berurutan, dengan subagen per tugas dan peninjau per tugas; penutup celah MapLibre (`attributionControl:
false` dan test regresi) masuk W0 sebagai Tugas 8; belum ada commit sampai tonggak M1, dan diff ditinjau pemilik di
sana. Keputusan terbuka: Q21, Q22, Q17 sampai Q19, upgrade MapLibre 6.x sebelum W3.

**W0 Stabilisasi selesai (26 September 2026)** menurut rencana
`docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md`:

| Butir | Keadaan |
|---|---|
| Suite Unit dan Feature | 1.277 test, 1.276 lulus, 0 gagal, 0 error, 1 dilewati (PostGIS), 7.240 assertion; diukur ulang koordinator sesudah gelombang perbaikan tinjauan akhir (sebelumnya 1.269 dan 1.268) |
| Tinjauan | setiap tugas ditinjau spesifikasi dan mutunya; tinjauan akhir W0 menemukan 0 Critical dan 3 Important, semuanya diperbaiki dalam satu gelombang lalu ditinjau ulang: fokus keyboard editor catatan (WCAG 2.4.3), konfirmasi sebelum menghapus simpanan yang berisi catatan, dan penjelasan HANDOVER tentang kegagalan bila kolom `note` belum ada |
| Sisa yang diketahui (backlog W3) | bila kurang dari dua simpanan yang terbit, alasan "tidak terbit" tidak tampil di kartu dan kalimat "Simpan satu jalur lagi" kurang tepat (kriteria 5 `docs/fitur/03-pertimbangkan.md` perlu disesuaikan); label "Bandingkan N jalur" di baki Jelajah dan detail jalur tidak dipotong batas banding; `NoDeadViewDataTest` menerima sebutan di komentar; logika batas pilihan ada di dua tempat; asersi literal sumber TSX rapuh terhadap perombakan W3; batasan angka `[0-9]{1,18}` untuk rute `{trail}` |
| Celah MapLibre (Q20) | kontrol atribusi bawaan dimatikan di `usePetaDasar.ts`, atribusi tetap teks lewat `WadahPeta`; `npm audit` tetap melaporkan CVE-2026-85061 sampai upgrade 6.x yang diputuskan sebelum W3 |
| `npx tsc --noEmit` | 0 galat terhadap keluaran Wayfinder yang dibangkitkan ulang |
| `npm run build` | sukses (diulang koordinator sesudah perbaikan dengan awalan SQLite: built in 1,75 s) |
| Pint | bersih; impor tak terpakai di empat test rintisan FR-25 ikut dibuang |
| FR-22 catatan simpanan | rute `trails.saved.note.update` dan `.destroy`, Form Request, layanan, props, dan editor di kartu Simpanan |
| Props yang dilaporkan mati | `batasBanding`, `jumlahTersimpan`, `pesan`, `pengingat` kini dipakai halaman, begitu juga `pilihan` yang sebelumnya lolos test hanya lewat komentar; tidak ada yang dicabut karena semuanya diikat test lain |
| Penjadwal | `trips:kirim-pengingat` dipantau `SchedulerHealthService::TUGAS` dengan toleransi 3 jam |
| `docs/fitur/` | delapan spesifikasi dengan sembilan heading templat |
| Cacat tersembunyi yang ditutup | impor Wayfinder basi di Simpanan (Hapus mengirim DELETE ke `/trails/compare/{id}` yang tidak ada, dan `tsc` serta build gagal begitu Wayfinder dibangkitkan); "Hapus dari perbandingan" memanggil `router.delete` dengan alamat kosong; halaman Banding membaca `modeTautan` dan `tautanBagikan` yang tidak pernah dikirim |
| Belum diverifikasi | klik-lewat dan tangkapan 390 px serta 1440 px di peramban (menunggu Q1 atau Q2); status task NgodingPakeAI di server |
| Commit | belum, sesuai keputusan pemilik 26 September 2026: diff ditinjau di tonggak M1 (R-018, DK-58) |

**Butuh keputusan pemilik:**
1. Seri commit di M1: seluruh migrasi React (374 entri `git status`) dan perubahan W0 serta WR belum di-commit; ringkasan diff per kelompok disusun di akhir WR untuk diputuskan pemilik.
2. Migrasi yang kemungkinan belum dijalankan di Supabase (INFERENCE: `HANDOVER.md` bagian 6 mencatat migrasi terakhir yang dijalankan `2026_09_21_000100`; verifikasi dengan `php artisan migrate:status` atas izin, R-019) tetapi dipakai fitur yang sudah tampil: `add_note_to_trail_considerations_table` (catatan simpanan), `add_plan_details_to_trip_plans_table`, dan `create_trip_reminders_table` (pengingat). Bila benar belum: halaman Simpanan tetap terbuka dan menawarkan editor catatan, tetapi menyimpan catatan gagal dengan
galat server; halaman trip kemungkinan galat saat dibuka karena kolom dan tabel itu dibaca saat merender (INFERENCE,
Q2, R-024). Bila dijalankan sebelum W7, jalankan per berkas dengan `--path` supaya tiga migrasi rintisan FR-25 tidak ikut.
3. Migrasi rintisan FR-25 (`create_personal_targets_table` dan `create_user_owned_items_table` hanya `id` dan `timestamps`; `add_source_and_recorded_values_to_hiking_history_table` kosong) jangan dijalankan di Supabase sebelum W7 mengisinya. Spec v3 §3.2 keliru menyebut skema itu sudah menampung data prototype.
4. Empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) hanya membuka `/` dan memberi lulus palsu. Usul: ditandai dilewati dengan alasan sampai W7, atau langsung diganti di W7.
5. Penjadwal produksi harus menjalankan `trips:kirim-pengingat` tiap jam dengan driver surel nyata (K11, K24); sampai itu Kesehatan Sistem menyebutnya "Belum pernah berjalan".
6. Toleransi 3 jam untuk tugas pengingat adalah ambang operasional usulan agen.
7. Rencana induk menaruh UI catatan di W3; W0 memasang editor dasar karena rute tanpa pemakai di antarmuka mengulang kelas cacat "pipeline tanpa konsumen" yang dijaga `NoDeadViewDataTest`. W3 merestilnya dengan primitif dari W1.

**Langkah berikutnya:** WR Struktur repo dan dokumen tim
(`docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`), lalu W1.

---

## 0-25. Catatan 25 September 2026 (riwayat)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu spec
> `docs/superpowers/specs/2026-09-25-memount-v2-design.md` (arahan pemilik verbatim,
> temuan, IA v2, aliran kerja A-G, izin yang ditunggu). Periksa keadaan nyata dulu:
> `git status`, `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`.
> Lanjutkan aliran kerja yang belum selesai menurut tabel di bawah. Jangan commit, push,
> mengakses Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik 25 September 2026 (inti, verbatim lengkap di spec §1):** aplikasi masih
terasa "CRUD sederhana" dan tidak semua fitur berfungsi baik; riset tren UI/UX dan IMK
terbaru (termasuk bahasa desain OS brand besar) lalu terapkan; pakai dan gabungkan semua
skill; IA baru lima tab **Home, Explore, Trips, Track, Progress** plus **Profile**, dengan
**Community sebagai lapisan lintas-halaman**; rombak dan rapikan repo agar mudah untuk tim;
satukan dokumen yang bertumpuk menjadi satu visi, misi, ekspektasi, dan kesepakatan
bersama; siapkan konteks tahan-sesi. Pernyataan merek: "MEMOUNT adalah platform
perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit,
preparation, readiness, field experience, trust, community, dan progression dalam satu
perjalanan utuh. MEMOUNT membantu saya menemukan perjalanan gunung yang cocok untuk saya."

**Aliran kerja** (rincian di spec §7):

| Aliran | Keadaan 25 Sep 2026 |
|---|---|
| A. Stabilisasi pasca-migrasi React | **Selesai.** Livewire dipensiunkan (layout, navigasi Volt, 33 komponen Blade mati dihapus; `App\Actions\Logout`); pintu Vite `app.js` dicabut; suite 1.159 test, 1.158 lulus, 1 dilewati (PostGIS), `tsc` 0, build sukses |
| B. Riset UI/UX dan IMK | Empat subagen menulis `docs/riset/2026-09-25-uiux/01-04`; sintesis `00-sintesis.md` menyusul |
| C. Dokumen dan repo | README proyek dan `CONTRIBUTING.md` (root) ditulis ulang; `docs/MATRIKS-SKILL.md`; `anti-slop/` pindah ke `docs/audit/anti-slop/`; `.gitignore` merapikan berkas lokal. Konsolidasi PRD (rencana 23 Sep) dikerjakan subagen; `PRD.md` kerja belum diganti sampai ia selesai |
| D. Fondasi desain | Menunggu B. Navigasi IA v2 (lima tab Beranda, Jelajah, Trip, Mendaki, Progres) dan tab Mendaki (`/mendaki`, `TrackController`) sudah terpasang dengan gaya lama |
| E. Halaman per tab | Menunggu D |
| F. Fitur DK-07 tanpa dependensi | Dua subagen backend: (1) simpanan vs banding maks 3, catatan pribadi, tautan bagikan, Kelola Peran; (2) detail rencana, pengingat, target pribadi, catat pendakian manual, perlengkapan. Migrasi baru hanya diuji lokal; **belum boleh dijalankan di Supabase tanpa izin** |
| G. Verifikasi dan serah terima | Berkelanjutan. Halaman Admin, Kesehatan Sistem kini menampilkan migrasi yang belum dijalankan di basis data yang dipakai (`MigrationStatus`) |

**Sudah dikerjakan 25 Sep 2026 (terverifikasi):** tabel banding tidak lagi melebarkan
halaman di 360 px (wadah gulir kini `relative`, dijaga `InertiaLayoutTest`); target
sentuh kecil diperbesar; log audit kini mencatat nilai lama yang benar (`getPrevious()`,
`AuditLogChangeTest`); penghapusan aturan izin mencatat isi baris; registri tautan React
kini daftar pengecualian `bukanHalamanReact.json` yang dicocokkan dengan rute nyata
(`InertiaLinkRegistryTest`) dan tautan ke situs lain selalu muat penuh; tema baru langsung
berlaku tanpa muat ulang (`ThemePreferenceTest`); lima komponen peta memakai satu dasar
`resources/js/components/peta/usePetaDasar.ts` dengan teks MapLibre berbahasa Indonesia
dan pengakuan gagal gaya peta; pratinjau garis di impor geometri admin; tabel admin
disatukan; CI kini menjalankan `npm run types`; hasil Wayfinder diabaikan git; test acak
`CheckpointJourneyTest` distabilkan (nama khas checkpoint pabrik).

**Keputusan terbuka untuk pemilik:** spec §8 (P1-P12).

---

## 0a. Catatan 24 September 2026 (riwayat)

**Sesi 24 September 2026, ringkas.** Pemilik menambah lima prinsip rekayasa (§3 butir 9)
dan mengusulkan nama **EMOUNT**. Nama itu dinilai dengan rubrik yang sama seperti nama
lain dan **gugur gerbang G3 dan G5** (merek "E-MOUNT" Sony kelas 9 dan "EPLAN EMOUNT"
kelas 9/35/42 aktif di USPTO; awalan "e" + "mount" deskriptif; pencarian tenggelam oleh
lensa Sony), meski PDKI bersih dan `emount.id` tersedia. Bukti dan tiga jalan ke depan:
`docs/riset/2026-09-23-konsolidasi/riset-nama-emount.md`. **Keputusan nama tetap milik
pemilik** (R-021); pekerjaan sesi ini dibangun agar nama apa pun cukup diganti di satu
tempat (`APP_NAME`).

Yang dibangun dan diverifikasi (test hijau, runtime dipotret, Supabase tidak disentuh):

- **Sistem identitas merek:** `config/brand.php` (tagline, deskripsi, warna tema),
  komponen `x-brand.mark` (tanda "Jalur Berpos", konsep A), `x-brand.lockup` (tanda +
  nama dari `config('app.name')`), `x-brand.head` (kepala dokumen bersama untuk enam
  layout). Logo bawaan Laravel dihapus (F5 selesai sebagai sketsa; gerbang DK-16 untuk
  aset final belum). Ikon PWA 192/512/maskable/180, `favicon.svg` dan `favicon.ico`
  digambar dari tanda merek.
- **Tema gelap (DK-17, DK-19):** token gelap di `app.css` (dua blok identik, dijaga
  test), `data-theme` dirender server dari cookie `tema` yang diwhitelist di
  `App\Support\ThemePreference`, `POST /tema` dengan `throttle:60,1`, pemilih tiga
  radio di Profil, mode pendakian, dan kaki halaman depan; `min-h-dvh`; peta diredupkan
  di tema gelap; token `brand-ink` untuk hijau sebagai latar depan; 33 `bg-white` dan
  semua `text-black`/`dark:` dihapus dari view.
- **Temuan ditutup:** F5 (sketsa), F8 (`lang/id/recommendation.php`, fallback `id`),
  F9 (meta "apa yang masih kurang sebelum berangkat"), F11 (nama manifest dari
  `config('app.name')`, ikon maskable terpisah), F12 (orientasi `any`), F13
  (`ColourContrastTest` membaca token per blok). DK-24: `@tailwindcss/vite` dicabut.
- **Test baru:** `ThemePreferenceTest` (8), `BrandIdentityTest` (8), `ColourContrastTest`
  diperluas (pasangan × dua tema, paritas blok gelap, non-teks gelap, larangan warna
  mentah). Bukti runtime: `curl` ke server lokal SQLite menunjukkan `Set-Cookie:
  tema=dark`, `<html data-theme="dark">`, dan penghapusan cookie saat "Ikuti sistem";
  24 tangkapan layar terang/gelap/sistem × ponsel/desktop di scratchpad sesi.

**Pembaruan malam 24 September 2026.** Pemilik mengusulkan MENDAKI dan MEMOUNT, dan
menambah arahan: *"frontend combine dengan react. jadi base nya tetap laravel namun, untuk
menunjang ui/ux nya combine dengan react."*
- **MENDAKI gugur** (kata kerja generik untuk jasanya sendiri, G2/G5; merek MENDAKI kelas 41
  sudah terdaftar di PDKI; `mendaki.id` dan `.com` terdaftar; Yayasan MENDAKI di Singapura).
- **MEMOUNT lolos semua gerbang meja** (v1 9 dari 12, v3 18 dari 26): PDKI bersih, USPTO tanpa
  merek identik, **`memount.com` dan `memount.id` tersedia**, tidak ada aplikasi bernama sama.
  Syarat: uji dengar-lalu-eja K1b dan amankan domain segera. Bukti:
  `docs/riset/2026-09-23-konsolidasi/riset-nama-mendaki-memount.md`.
- **React:** riset dan usulan keputusan di `docs/riset/2026-09-23-konsolidasi/frontend-react.md`.
  Rekomendasi: pulau React di halaman Blade/Livewire untuk peta, banding jalur, dan profil
  elevasi (kontrak: Livewire memegang data dan otorisasi, React memegang interaksi); Inertia 3
  diputuskan setelah tiga pulau pertama hidup. **Belum ada dependensi dipasang**: menunggu
  persetujuan pemilik atas `react`, `react-dom`, `@vitejs/plugin-react` (mengubah DK-24).

**KEPUTUSAN PEMILIK 24 September 2026 (verbatim): "tetapkan Memount sebagai nama brand app
web ini."** Diterapkan: `APP_NAME="Memount"` di `.env` dan `.env.example`, bawaan
`config/app.php` menjadi `Memount`, `BrandIdentityTest::test_the_brand_name_is_memount`
menjaganya; DK-13 di spec konsolidasi terisi; DESIGN.md "Arah" memuat kata-kata pemilik.
Nama ditulis **Memount** (kapital awal) di teks dan aria; wordmark menampilkannya kapital
penuh lewat CSS. Tindakan yang hanya bisa dilakukan pemilik, dan sebaiknya segera karena
ketersediaan bisa hilang: daftarkan `memount.com` dan `memount.id`, ambil `@memount` di
Instagram dan `@memount.id` di TikTok/X/YouTube, ajukan merek DJKI kelas 9 dan 42. Uji
dengar-lalu-eja K1b tetap dijalankan sebelum aset logo final (gerbang DK-16).

**KEPUTUSAN PEMILIK 24 September 2026: jalur C, migrasi penuh ke React + Inertia**
(jawaban "c" atas tiga opsi di `frontend-react.md`). Risiko sudah dinyatakan sebelum
keputusan; sejak itu dikerjakan sebagai migrasi bertahap dengan Livewire dan React
berdampingan sampai halaman Livewire terakhir pindah.

Fondasi yang sudah terpasang dan hijau (979 test, 978 lulus, 1 dilewati; `tsc` bersih;
Vite build sukses):
- Paket: `inertiajs/inertia-laravel` 3.3.4, `laravel/wayfinder` 0.1.21; npm `react` 19.3,
  `react-dom` 19.3, `@inertiajs/react` 3.7, `@inertiajs/vite` 3.7, `@vitejs/plugin-react`
  6.1, `typescript` 5.9, `@laravel/vite-plugin-wayfinder`. Tailwind tetap 3.4.19.
- `HandleInertiaRequests` (props bersama: auth secukupnya, merek, tema, peta, flash),
  templat akar `resources/views/inertia.blade.php` (judul dan meta description dirender
  server, tautan lewati, `x-brand.head`), `vite.config.js` dua pintu (`app.js` Livewire,
  `app.tsx` Inertia, berbagi `dasar.js`), `tsconfig.json`, rute bertipe Wayfinder
  (`php artisan wayfinder:generate` setiap rute berubah). SSR **mati** sampai diputuskan.
- Komponen React (keadaan 6 Oktober 2026, sesudah W3): `layouts/AppLayout`, `GuestLayout`, `Kerangka`, `NavLinks`;
  `components/brand` (Mark, Lockup); `components/ui` (primitif W1/W2: Alert, Avatar, BarAksi, BilahTab, Button, Card,
  Chip, EmptyState, Field, Icon, JudulHalaman, KontrolSegmen, KotakCentang, Lembar, Lencana, LencanaDataContoh,
  PageHeader, Pagination, Sakelar, Skeleton, Stepper, Tautan, TautanProfil, ThemePicker, ToastUrungkan, TombolIkon,
  TombolKembali, TombolSalin); `components/trails` (AngkaRingkas, BarisStatus, FaktorKecocokan, FitLine, GarisProfil,
  Kesegaran, LabelKecocokan, LabelSumber, PetaJelajah, ProfilElevasi, StatusAkses, TombolSimpan, dengan subfolder
  `jelajah`, `rinci`, `gunung`, `banding`, `kecocokan`); `components/peta`, `components/trips`, `components/community`,
  `components/discovery`, `components/admin`, `components/akun`. `FitBadge`, `StatusBadge`, `TrailRow`,
  `ConsiderationTray`, `CheckpointJourney`, dan `ElevationProfile` lama sudah dihapus atau diganti di W3.
- Kontrak arsitektur tercatat di `docs/ARCHITECTURE.md` (server memegang data dan
  otorisasi, React memegang interaksi; skor internal tidak pernah ke props).

**Ledger migrasi halaman** (diperbarui setiap halaman pindah):

| Halaman | Status | Catatan |
|---|---|---|
| `/` halaman depan | **React** (`pages/Welcome.tsx`) | `welcome.blade.php` dihapus |
| `/trails` Jelajah | **React** (`pages/Trails/Index.tsx`, `TrailIndexController`, `Services\Trails\TrailSearch`, `TrailRowResource`, `POST trails/{trail}/timbang`) | Livewire `TrailIndex` dihapus; 12 berkas test diarahkan ke `assertInertia` |
| `/trails/{slug}`, `/trails/compare`, `/trails/saved` | Livewire | berikutnya (berbagi `TrailRowResource`) |
| Dasbor, Rencana, Rekomendasi, Trip, Persiapan, Readiness, Mode pendakian, Laporan, Kabar, Progres, Riwayat, Kontribusi | Livewire | urutan mengikuti loop produk |
| Auth (Breeze Volt), Profil | Livewire | `POST /logout` sudah ada untuk layout React |
| Admin, Moderasi | Livewire | terakhir |
| `/pendakian/{slug}`, sitemap | Blade | tetap Blade sampai SSR diputuskan (SEO) |

Bukti runtime 24 September 2026 malam: tangkapan layar `react-welcome` dan
`react-trails` terang/gelap, ponsel/desktop di scratchpad sesi; lockup Memount,
navigasi lima permukaan, baki simpanan, dan tema gelap terlihat benar. Peta sebaran
(MapLibre di React, `components/ui/MapView.tsx`) terbukti menggambar basemap OpenTopoMap
dan penanda gunung pada tangkapan `react-trails-map.png` (Chrome headless dengan WebGL
perangkat lunak); tanpa WebGL komponen menampilkan kalimat gagal yang jujur dan mencatat
galatnya ke konsol.

Yang perlu pemilik lakukan: set `APP_FALLBACK_LOCALE=id` di `.env` lokal (contohnya sudah
diubah); meninjau dua halaman React pertama di peramban; meninjau sketsa tanda merek di
header sebelum gerbang DK-16 dijalankan; memutuskan SSR (butuh Node di server produksi).

Catatan sesi sebelumnya (23 September 2026) di bawah tetap berlaku kecuali yang ditandai
selesai di tabel temuan.


**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0, lalu spec
> `docs/superpowers/specs/2026-09-23-konsolidasi-dokumen-design.md` dan rencana
> `docs/superpowers/plans/2026-09-23-konsolidasi-dokumen.md`. Catatan riset ada di
> `docs/riset/2026-09-23-konsolidasi/`. Lanjutkan dari "Langkah berikutnya" di bawah.
> Jangan commit, push, mengakses Supabase, atau menambah dependensi tanpa izin saya.

**Sudah selesai**

- Task NgodingPakeAI #10 "Bangun halaman daftar rute tersimpan" berstatus done di server.
  `/trails/saved` membaca `trail_considerations` lewat `ConsiderationService::forUser()`;
  satu tombol "Simpan rute" / "✓ Tersimpan"; pengumuman `role="status"`; tautan dari baki
  Jelajah dan halaman banding. Suite penuh 933 test: 916 lulus, 0 gagal, 17 dilewati.
  Diverifikasi di peramban terhadap SQLite scratch (Supabase tidak disentuh).
- Dihapus: 8 berkas sampah keluaran redirect shell, `debug_trail_search.php`,
  `resources/views/layouts/navigation.blade.php` (kode mati).
- Spec konsolidasi dokumen disetujui pemilik (DK-01 sampai DK-12) dan rencana 14 tugas
  ditulis. Belum ada berkas kanonis yang ditimpa.
- `npx ngodingpakeai sync --if-changed --plan` sudah dijalankan (metadata saja).

**Belum ada yang di-commit.** Working tree mencampur kerja agen sebelumnya (task #1–#9
Jelajah, belum diverifikasi) dengan kerja sesi ini di berkas yang sama.

**Keputusan dan arahan pemilik 23 September 2026**

- Cakupan: dokumen dan kerapian repo; struktur `app/` tidak dirombak.
- PRD digabung dengan pendekatan "satu pemilik per jenis isi"; nomor § adalah ID permanen.
- Diputuskan atas delegasi pemilik kepada riset: simpanan longgar dengan banding
  maksimal 3; peta digerbang data (hanya jalur bergeometri); amandemen R-009 menyebut
  ACCREDITED EXPERT "Ahli bersertifikat".
- Masuk MVP: catatan pribadi + tautan bagikan; pengingat + detail rencana; masuk dengan
  Google dan passkey; target pribadi + catat pendakian manual; verifikasi kode lewat
  email, WhatsApp, SMS.
- Deploy produksi di Vercel.
- Eksekusi rencana memakai subagen per tugas (izin eksplisit).
- Merek baru, kata-kata pemilik: "VELORA adalah platform perjalanan gunung yang
  dipersonalisasi, yang menghubungkan discovery, Route Fit, preparation, readiness,
  field experience, trust, community, dan progression dalam satu perjalanan utuh."
- Tambahan: mode gelap, bahasa tambahan, kompatibel semua perangkat, data gunung seluruh
  Indonesia yang dapat diinput di aplikasi.
- Landasan kerja: selalu riset mendalam dengan informasi terkini yang kompatibel dengan
  stack dan pengguna.

**Riset arahan baru**: lima catatan di `docs/riset/2026-09-23-konsolidasi/`
(strategi merek, identitas visual dan logo, mode gelap dan perangkat, multibahasa,
data gunung Indonesia). Salinan kerja di scratchpad sesi 23 September 2026.

**Temuan terbuka**

| # | Temuan | Status |
|---|---|---|
| F1 | Fokus keyboard hilang ke `<body>` setelah aksi Livewire (`x-ui.button` menonaktifkan diri) | Terukur di peramban |
| F2 | Peta Jelajah memakai koordinat gunung sebagai titik jalur | Diputuskan: dihapus (peta digerbang data) |
| F3 | Pencarian kalimat memakai 5 alias kota dan membuang teks pencarian | Diperbaiki lewat FR-27 |
| F5 | Logo header bawaan Laravel | Selesai 24 Sep 2026 sebagai sketsa tanda "Jalur Berpos" (`x-brand.lockup`); aset final menunggu gerbang DK-16 |
| F6 | Di ponsel daftar jalur mulai jauh di bawah layar pertama | Redesain Jelajah |
| F7 | Baki simpanan `sticky` berpotensi menutupi fokus (WCAG 2.4.11) | Hipotesis, belum diuji |
| F8 | `lang/en/recommendation.php` berisi teks Indonesia; `fallback_locale` = `en` | Selesai 24 Sep 2026: dipindah ke `lang/id`, fallback `id` (`.env` lokal pemilik masih perlu diubah) |
| F9 | Meta description publik "apakah sudah layak berangkat" (`welcome.blade.php:8`) bertentangan R-014 | Selesai 24 Sep 2026: `config('brand.description')`, dijaga `BrandIdentityTest` |
| F10 | Nama VELORA: dipakai protokol DeFi (ex-ParaSwap, April 2025) dan merek lokal; domain `velora.*` sudah terdaftar; PDKI belum diperiksa | Selesai: pemilik menolak nama itu (D1) |
| F11 | Manifest PWA: `app-512.png` dipakai untuk `any` dan `maskable`; nama "Rencana Pendakian"/"Pendakian" vs `APP_NAME` "Jalur Naik" (`ProgressiveWebAppController.php:17-18, 32-33`) | Selesai 24 Sep 2026: nama dari `config('app.name')`, `app-512-maskable.png` terpisah |
| F12 | Manifest `'orientation' => 'portrait'` (`ProgressiveWebAppController.php:22`) berisiko gagal WCAG 1.3.4 | Selesai 24 Sep 2026: `any` |
| F13 | `ColourContrastTest::token()` memakai `preg_match` (baris 259), jadi hanya token pertama yang dibaca; token gelap tidak akan diperiksa | Selesai 24 Sep 2026: token dibaca per blok, dua tema dihitung |

**Langkah berikutnya** (diperbarui 23 September 2026, malam)

1. Selesai: lima riset, laporan sintesis (`laporan-sintesis.md`), riset nama
   (`riset-nama-merek.md`, daftar pendek PAMARGI 12/12, PARWATA, LAMPAH, JALURA; PANDOM gugur).
2. Selesai, menunggu tinjauan pemilik: amandemen spec §2.1 DK-13–DK-24 (keputusan D1–D12),
   §156–§159 baru, K29–K34, D-C, F8–F13, GR-03 (§10.1), V9, sub-proyek 6.
3. Selesai: penilaian dua nama usulan pemilik di
   `docs/riset/2026-09-23-konsolidasi/riset-nama-usulan-pemilik.md`. VERTIKA 5 dari 12, gugur
   (aplikasi "Vertika" identik di App Store Indonesia); GOURVA 7 dari 12, cadangan bersyarat.
   Bukti penentu dicek ulang 14.47 UTC. Pemilik menyebut "ketiga nama" tetapi baru dua yang
   diterima; nama ketiga dinilai dengan rubrik yang sama bila ada.
4. Pemilik tidak memilih dari daftar itu. Jawabannya: *"riset lagi saya ingin go
   internasional"*; gourva.com bukan miliknya. Riset nama internasional berjalan: catatan di
   `docs/riset/2026-09-23-konsolidasi/nama-internasional/`, laporan akhir
   `docs/riset/2026-09-23-konsolidasi/riset-nama-internasional.md`. Bila laporan akhir belum
   ada, lanjutkan dari catatan yang sudah ada, jangan mulai dari nol. Setelah pemilik memilih,
   isi slot [NAMA] di DK-13 dan perbarui memori `velora-merek-dan-arah.md`.
   Keadaan riset per 23 September 2026 malam:
   - selesai: `lanskap-dan-strategi.md` (stem jenuh, Madrid dan DJKI, rubrik v3 berbobot;
     pesaing konsep terdekat muncak.id, dicek ulang) dan `kandidat-dan-saringan.md`
     (62 kandidat baru, 30 gugur; 12 lolos: STEPLARAS, BENDPIN, LIKUPOS, KELOKPOS, PINTANDA,
     LINTAMO, PORANTI, TALAMPI, TANLIMO, TOLAPI, TUMPALO, PAMARGA; PAMARGI turun ke 10 pada
     rubrik v2 internasional karena "gi" dibaca lunak dan "marg" berarti mati dalam bahasa Persia);
   - selesai (24 September 2026 dini hari): verifikasi mendalam tiga kelompok
     (`verifikasi-kelompok-1.md`, `-2.md`, `-3.md`), rubrik v2 dan v3 berdampingan. Skor v3
     (maks 26): TALAMPI 21, LINTAMO 20, TANLIMO 19, TUMPALO 17, PINTANDA 16 (bersyarat: merek
     TANDA di kelas 9/42), PAMARGA 16, PORANTI 16 (tidak disarankan), PAMARGI 14 (tertahan),
     STEPLARAS 12 (tertahan); gugur: TOLAPI (Tohapi, UE), BENDPIN, LIKUPOS, KELOKPOS;
   - selesai: laporan akhir `docs/riset/2026-09-23-konsolidasi/riset-nama-internasional.md`
     (TALAMPI memimpin tipis; usulan uji dengar-lalu-eja TALAMPI, LINTAMO, TANLIMO; TUMPALO
     cadangan). Bukti penentu dicek ulang 23 September 2026 20.00 UTC. Menunggu keputusan
     pemilik: jalankan uji pengguna, pilih langsung, atau kembali ke PAMARGI (Indonesia saja);
     serta apakah "go internasional" juga berarti cakupan produk di luar Indonesia (R-021). Uji dengar-lalu-eja dengan pengguna
     nyata (K1b) tidak bisa dijalankan agen; naskahnya disiapkan untuk pemilik.
5. Perbarui rencana: rentang indeks §1–§159, bagian §156–§159, tugas `DESIGN.md`, GR-03, V9.
6. Eksekusi rencana per tugas dengan subagen; loop NgodingPakeAI dilanjutkan setelah PRD
   gabungan disetujui (sisa halaman Jelajah: task #11–#13).

**Keputusan pemilik atas laporan sintesis (23 September 2026).** Laporan lengkap:
`docs/riset/2026-09-23-konsolidasi/laporan-sintesis.md`, bagian "Keputusan yang dibutuhkan
dari pemilik" (D1–D12).

- **D1: nama VELORA TIDAK dipakai; ganti ke nama lain.** Pernyataan merek dan delapan pilar
  tetap berlaku, hanya namanya berganti. Langkah berikut: riset nama baru (domain .id
  tersedia, konflik PDKI rendah, mudah diucapkan, tanpa konotasi kecepatan atau janji
  keselamatan), pemilik memilih dari daftar pendek. Hasil riset nama ditulis ke
  `docs/riset/2026-09-23-konsolidasi/riset-nama-merek.md`; bila berkas itu belum ada,
  riset nama belum selesai dan harus dijalankan ulang.
- **D4: logo konsep A "Jalur Berpos"** (rute zig-zag berujung cincin pos), dipadukan dengan
  wordmark nama baru; aset final baru setelah verifikasi nama, uji 16 px, audit kemiripan,
  uji pemahaman 5–8 pendaki.
- **D10: katalog gunung Tier A terkurasi** (ratusan entitas bersumber per field), status bahaya
  PVMBG dan status akses Kemenhut disimpan terpisah, OSM hanya peta dasar (tanpa ODbL),
  empat fase: katalog, status, golden trail, kontribusi termoderasi.
- **Disetujui semua rekomendasi lain:** D2 kontrak merek (tagline "Pilih jalur yang sesuai.
  Pahami alasannya.", tetap "Anda", perbaikan "layak berangkat"), D3 Route Fit sebagai fitur
  bermerek, D5 mode gelap token CSS + tema dirender server dari cookie (default "Ikuti
  sistem", tetap Tailwind 3.4.19), D6 peta topo diredupkan + cadangan OpenTopoMap, D7
  orientasi `any` + CSS `@container`, D8 Inggris lebih dulu + fallback `id` setelah F8,
  D9 `/en/mountains/...` + migrasi `/pendakian` bersamaan, D11 skema bertahap dengan izin
  per migrasi, D12 nol dependensi runtime baru + hapus `@tailwindcss/vite` yang mati.

**Selalu perlu izin pemilik:** commit, push, akses Supabase, menambah dependensi,
menjalankan migrasi.

---

## 1. Cara melanjutkan di terminal baru

Buka terminal baru di `E:\praktik_aplikasi_web`, jalankan `claude`, lalu tempelkan ini:

> Baca `HANDOVER.md` di root proyek ini dari awal sampai akhir sebelum melakukan apa pun.
> Itu konteks lengkap pekerjaan kita. Sesudah membacanya, konfirmasi ke saya apa yang kamu
> pahami tentang tujuan, kesepakatan, dan keadaan sekarang, lalu tunggu instruksi saya.
> Jangan mulai menulis kode sebelum saya setujui.

Dokumen ini plus `docs/PRD.md` plus `docs/superpowers/specs/` cukup untuk memulihkan seluruh
konteks.

---

## 2. Tujuan dan ekspektasi pemilik produk

Disalin dari kata-katanya sendiri, bukan ditafsirkan:

> "aplikasi web gunung yang memadukan antara aplikasi web atau aplikasi yang popular dan
> sudah banyak user nya dengan strava dan traveloka, bagaimana user riset di masing-masing
> aplikasi nya dengan itu saya ingin mengcompare ke 3 itu di padukan dan menerapkan pada
> aplikasi gunung."

> "saya mengutamakan untuk pertama imk dan ui/ux"

> "impact dari interaksi manusia komputer, ui, dan ux di semua refrensi yang saya cantumkan
> dari hasil riset, tujuan, dan kesepakatan diatas itu sama dengan aplikasi web yang akan
> kita bangun."

Artinya: **bukan menyerupai rupa ketiga rujukan, melainkan mencapai dampak IMK/UI/UX yang
setara dengan mereka.**

Aspek sistem yang dituntut, di luar UI/UX: arsitektur dan skalabilitas, keamanan dan
kepatuhan, keandalan dan manajemen kegagalan, operasional dan observability, pengelolaan
data dan konsistensi, serta marketing.

Peran yang diminta: lead engineer 30 tahun, sekaligus UI/UX designer, product designer,
software engineer, dan manajer audit.

---

## 3. Kesepakatan yang mengikat

Ini aturan kerja yang diminta pemilik produk dan **tidak boleh dilanggar**:

1. **Git adalah penyerahan kepada klien.** Push dan merge hanya dilakukan ketika aplikasi
   sudah benar-benar sesuai tujuan. Commit lokal boleh; push tidak, sampai diizinkan.
2. **Selalu konfirmasi sebelum mengakses Supabase**, termasuk kuncinya. Kunci boleh
   dipakai, tetapi **tidak pernah ditaruh di skrip mana pun**.
3. **Riset mendalam dan brainstorming terlebih dahulu sebelum memutuskan.** Bukan menebak.
4. **Selalu testing dan crosscheck.** Buktikan dengan pengukuran sebelum melapor temuan.
5. **Terapkan semua skill yang terpasang**, bukan hanya yang relevan tetapi juga yang
   mendukung.
6. **Selalu evaluasi dan susun rencana problem-solving berikutnya.**
7. **Setiap recap wajib memuat persentase progres bermetode**, plus problem, solusi, yang
   masih kurang, dan rencana berikutnya.
8. **Belajar di setiap task.**
9. **Lima prinsip rekayasa berlaku pada setiap perubahan** (arahan pemilik 24 September
   2026: "selalu menerapkan dan menambahkan prinsip proper architecture, scalable system,
   secure by design, stable, and reliable"). Sebelum melapor, setiap perubahan dijawab
   dengan lima pertanyaan ini, dan jawabannya masuk rekap:
   - **Proper architecture:** satu sumber kebenaran per konsep; tidak ada nilai yang
     ditulis di dua tempat (contoh: nama produk hanya di `config('app.name')`, palet
     hanya di token `app.css`, kepala dokumen hanya di `x-brand.head`).
   - **Scalable system:** jumlah query dan ukuran respons tidak tumbuh mengikuti jumlah
     data; state tidak menempel pada satu proses (preferensi tema di cookie, bukan sesi).
   - **Secure by design:** input tak dipercaya, daftar putih bukan daftar hitam, otorisasi
     di server, rate limit pada endpoint tanpa autentikasi, tidak ada secret di kode.
   - **Stable:** kegagalan terkendali dan tidak menjatuhkan halaman (peta gagal, cuaca
     tidak tersedia, JavaScript belum jalan); halaman pertama benar tanpa skrip.
   - **Reliable:** dibuktikan test yang menghitung, runtime yang dipotret, dan paritas
     PostgreSQL bila skema tersentuh (R-023, R-024). Tanpa bukti, tidak ada PASS.

---

## 4. Dasar produk: PRD

`docs/PRD.md` (21 September 2026: 3365 baris, 128 bagian). Yang paling menentukan:

- **§7 Core Product Loop**: PROFILE → ROUTE FIT → PLAN → PREPARE → CHECK → HIKE → REPORT
  → IMPROVE. Aturannya tegas: *"Semua fitur utama harus memiliki hubungan dengan loop
  tersebut."*
- **§8 Diferensiator**: bukan banyak gunung, bukan GPS, bukan review, bukan checklist,
  melainkan **menghubungkan karakteristik pendaki dengan karakteristik jalur, lalu
  menjelaskan alasannya**.
- **§18 / BR-02**: MDPL hanya atribut deskriptif, **tidak pernah** penentu kesulitan.
  Gunung 2.000 mdpl bertanjakan 1.400 m lebih berat daripada 3.000 mdpl bertanjakan 700 m.
- **§89 Progressive disclosure**: Apa ini → Cocokkah untuk saya → Mengapa → Apa
  tantangannya → Apa yang disiapkan → Bagaimana kondisinya.
- **§90 Explainability**: pengguna paham alasannya tanpa membuka dokumentasi teknis.
- **§91 No false precision**: dilarang persentase dan skor; **boleh** menyebut pengukuran
  nyata (8 jam, 700 m). Label hanya: Cocok / Perlu persiapan / Kurang cocok.
- **§92**: keterangan resmi dan masukan komunitas harus terbedakan secara visual.
- **§96**: anggaran query tidak boleh tumbuh mengikuti jumlah data.
- **§87 / §88**: WCAG 2.2 AA dihitung bukan ditaksir; mobile-first, tidak meluber di 400px.
- **BR-09**: skor internal tidak pernah sampai ke antarmuka dalam bentuk apa pun.
- **§60**: model kepercayaan data adalah nilai inti produk.

---

## 5. Hasil riset tiga rujukan, dan sintesisnya

Riset dilakukan dengan pencarian web, bukan dari ingatan. Sumbernya tercantum di
`docs/superpowers/specs/2026-09-21-mesin-kecocokan-design.md`.

**AllTrails** — tujuan pengguna sebenarnya adalah **memperkirakan ongkos**: berapa waktu,
tenaga, dan sumber daya. Titik sakit terbesar: bolak-balik antar halaman jalur karena tidak
ada cara melihat pilihan berdampingan.

**Traveloka** — corongnya **lihat → simpan → bandingkan → pesan**, dan riset menemukan
masalah di ketiga tahap awal, dengan kalimat yang nyaris identik dengan AllTrails. Keunggulan
Traveloka atas Tiket.com ada pada **kekayaan filternya**, bukan kecantikannya.

**Strava** — segment adalah **perbandingan yang dipersempit** ke kelompok acuan yang cukup
kecil sehingga menang terasa mungkin. 70% pengguna menyebut membandingkan diri sebagai
pendorong utama.

**Sintesis: ketiganya bukan katalog, ketiganya mesin pembanding.** Dan §8 PRD — menghubungkan
pendaki dengan jalur lalu menjelaskannya — **juga sebuah perbandingan**. Tujuan produk ini
dan mekanik ketiga rujukan ternyata satu hal yang sama.

**ATM yang sudah diterapkan:**
- Dari AllTrails: perbandingan dibingkai sebagai ongkos versus kemampuan pembaca.
- Dari Traveloka: tahap "Pertimbangkan" (simpan), yang sebelumnya tidak ada sama sekali.
- Dari Strava: tangga kemajuan — **acuannya diri sendiri, bukan pendaki lain**. Papan
  peringkat kontribusi **ditolak** karena memberi hadiah pada jumlah merusak §60.

---

## 6. SELESAI — sembilan migrasi sudah dijalankan di Supabase

**Status: beres pada 21 September 2026, atas izin eksplisit pemilik produk.**

Kesembilan migrasi dijalankan dengan `php artisan migrate --force` terhadap Supabase.
Hasilnya diverifikasi: `migrate:status` menunjukkan **nol Pending**, seluruhnya `Ran`
pada batch [7]. Tabel `trail_considerations`, `mountain_follows`, dan `hike_track_points`
ada beserta indeks, kunci unik, dan foreign key-nya. Aplikasi dijalankan terhadap skema
baru dan halaman publiknya menjawab 200 tanpa satu pun galat di log.

Sebelum ini, bagian berikut adalah masalah terbesar proyek, dan dicatat sebagai riwayat
karena penyebabnya adalah kelas kesalahan yang mudah berulang:

**Pemilik produk tidak pernah melihat satu pun hasil kerja sesi itu.** Basis datanya
tertinggal sembilan migrasi, sementara seluruh 874 test berjalan di SQLite in-memory yang
migrasinya otomatis lengkap. Jadi test hijau dan aplikasi error pada saat yang sama, di dua
basis data yang berbeda.

Lebih buruk lagi, halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah
Tugas 5 menambahkan fitur timbang ke sana, karena fitur itu menanyakan tabel yang belum ada
di produksi. Pekerjaan yang dimaksudkan memperbaiki justru merusak, dan suite tidak dapat
melihatnya.

**Pelajaran untuk sesi berikutnya:** suite hijau tidak membuktikan aplikasi berjalan di
lingkungan pemilik produk. Setiap kali sebuah tugas menambahkan tabel atau kolom, periksa
`migrate:status` terhadap basis data yang sebenarnya dipakai, bukan hanya menjalankan test.

Halaman dan tabel yang dulu terdampak:

| Halaman | Butuh tabel | Status |
|---|---|---|
| Jelajahi | `trail_considerations` | migrasi belum jalan |
| Pertimbangkan | `trail_considerations` | migrasi belum jalan |
| Kabar | `mountain_follows` | migrasi belum jalan |
| Progres | `hike_track_points` | migrasi belum jalan |

Halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah Tugas 5 menambahkan
fitur timbang ke sana. Jadi pekerjaan terakhir memperburuk keadaan yang dilihat pemilik
produk, meskipun seluruh 874 test hijau — karena test berjalan di SQLite yang migrasinya
otomatis lengkap.

**Perbaikannya satu perintah, dan harus dijalankan pemilik produk sendiri** karena lapisan
izin Claude Code memblokir akses Supabase dari agen:

```
! php artisan migrate --force
```

Sembilan migrasi yang tertunda, semuanya **aditif** (tabel dan kolom baru, tidak ada drop):

```
2026_09_20_000450_create_usability_sessions_table
2026_09_20_000500_create_scheduled_task_runs_table
2026_09_20_000600_add_trail_snapshot_to_recommendation_results_table
2026_09_20_000700_add_elevation_profile_to_trails_table
2026_09_20_000800_create_hike_track_points_table
2026_09_20_000810_add_track_recording_preference
2026_09_20_000900_create_report_thanks_table
2026_09_20_001000_create_mountain_follows_table
2026_09_21_000100_create_trail_considerations_table
```

**Alternatif tanpa menyentuh produksi**, untuk sekadar melihat hasilnya:

```
! php artisan migrate --force --database=sqlite --env=local
```

atau jalankan dengan basis data SQLite lokal (`database/local.sqlite`, sudah di-gitignore).

---

## 7. Keadaan kode

- Cabang: `hardening`. **Belum pernah di-push.** `origin/main` masih berisi PRD saja.
- Commit di depan `main`: sekitar 149.
- Suite: **874 test, 863 lulus, 11 dilewati, 0 gagal**. Pint bersih. Build sukses.
- 11 yang dilewati butuh `SPATIAL_TEST_DSN` (uji PostGIS).

**Yang sudah dibangun sesi terakhir** (rencana `docs/superpowers/plans/2026-09-21-mesin-kecocokan.md`):

1. `TrailFitService` — menilai kecocokan sekumpulan jalur tanpa query tambahan
2. Halaman jelajah menyebut kecocokan tiap jalur beserta alasannya
3. Penjelasan kecocokan menyebar ke detail jalur dan halaman trip
4. "Pertimbangkan" — timbangan maks 5, lintas perangkat, penolakan dinyatakan
5. Bandingkan dicapai dari jelajah, dibingkai ongkos versus kemampuan
6. Tangga kemajuan di atas tanjakan, acuannya diri sendiri
7. Menu 9 kata benda jadi 5 permukaan: Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar

**Enam cacat produksi yang ditemukan dan ditutup**, tidak satu pun berasal dari rencana:

1. Izin pendakian salah hitung tujuh jam setiap hari (bisa bilang izin masih terbuka
   padahal sudah tutup)
2. Dasbor berkata "Berangkat besok" untuk keberangkatan hari itu juga
3. Kesegaran laporan kondisi terlihat lebih baru daripada kenyataan
4. Kunci mesin `official_status_closed` bocor ke layar pengguna
5. Halaman perbandingan mati total (`DivisionByZeroError`) untuk jalur berjarak nol
6. Timbangan tidak dapat dicapai dari menunya sendiri

---

## 8. Keputusan yang sudah diambil dan tidak perlu dibahas ulang

- **Papan peringkat kontribusi ditolak** — memberi hadiah pada jumlah merusak §60.
- **Tangga kemajuan memakai tanjakan, bukan MDPL** — BR-02/§18.
- **Foto jalur tidak dijadikan wajah kartu** — foto berasal dari laporan komunitas yang
  membawa nama pelapornya; §82 dan §83 melarang menerbitkannya begitu saja.
- **Peta-dulu seperti AllTrails ditunda** — geometri jalur baru masuk lewat impor admin,
  jadi peta-dulu akan memamerkan kekosongan.
- **Mode gelap belum diputuskan** pemilik produk, tercatat terbuka di `DESIGN.md`.
- **Tautan perbandingan yang dibagikan bersifat read-only** — tidak pernah menulis ke
  timbangan siapa pun.
- **Halaman detail menyatakan jenis penilaiannya** ("Kecocokan dasar" / "untuk rencana
  ini") agar tidak ada label yang terbaca berbeda di dua halaman tanpa penjelasan.

Catatan lengkap seluruh 24 ruling beserta biaya bila keliru ada di
`.superpowers/sdd/2026-09-21-mesin-kecocokan/progress.md`.

---

## 9. Yang masih terbuka

**Menghalangi pemilik produk melihat hasil:**
- Sembilan migrasi belum jalan di Supabase (lihat bagian 6)

**Keterbatasan yang diketahui, dinyatakan bukan disembunyikan:**
- Kunci balapan timbangan tidak terbukti suite: `lockForUpdate()` nyata di PostgreSQL,
  no-op di SQLite
- Penjaga zona waktu masih dapat dielakkan lewat variabel perantara
- Ambang A4 spec (kecocokan hadir di ≥10 dari 12 layar) baru tercapai 6 dari 12

**Butuh manusia, alatnya sudah ada di sistem:**
- Uji pakai 5–8 pemula untuk enam UX validation task §113
- SUS ≥ 80 — `UsabilityStudyService` menuntut minimal 12 responden
- Penelusuran pembaca layar
- Scheduler produksi belum berjalan

---

## 10. Umpan balik terakhir pemilik produk, belum terjawab

> "hasilnya tetap sama. error di bagian jelajahi, pertimbangan, kabar ... saya tidak
> merasakan experience berbeda masih jauh dari tujuan dan ekspektasi saya. yang saya lihat
> masih crud sederhana."

Penyebab error sudah terdiagnosis (bagian 6). **Penilaian "masih CRUD sederhana" belum
dapat diuji secara jujur**, karena pemilik produk belum pernah melihat halaman yang
berfungsi.

Langkah berikutnya yang benar, berurutan:

1. Jalankan sembilan migrasi
2. Pemilik produk membuka aplikasinya dan menilai ulang
3. **Baru** setelah itu evaluasi UI/UX besar-besaran, dengan penilaian yang berdasar pada
   apa yang benar-benar terlihat, bukan pada halaman error

Melakukan riset UI/UX besar-besaran sebelum langkah 1 dan 2 berarti merancang untuk
sesuatu yang belum pernah dilihat siapa pun.
