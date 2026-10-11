# Panduan Kontribusi

Cara bekerja di repo Memount. Menyiapkan lingkungan ada di `README.md`; peta kode ada di
`docs/ARCHITECTURE.md`; aturan domain ada di `.claude/rules/mountain-core.md` dan
`DESIGN.md`. Berkas ini menjawab: bagaimana saya mengubah sesuatu tanpa merusak yang lain.
Kesepakatan kerja tim (izin, alur riset lebih dulu, cara audit, definisi selesai, format
rekap) ada di `docs/AGENTS.md`.

## Alur kerja

1. Buat branch dari branch kerja yang sedang aktif (saat ini `hardening`).
2. Tulis test yang menggambarkan perilaku yang diinginkan, jalankan, **pastikan gagal**.
   Test yang lolos sebelum ada implementasi tidak menguji apa pun.
3. Tulis implementasi paling sederhana yang membuatnya lolos.
4. Jalankan gerbang lokal (di bawah). Untuk perubahan UI, lihat hasilnya di peramban pada
   lebar 390 px dan 1440 px, tema terang dan gelap.
5. Satu commit = satu perubahan yang dapat dijelaskan dalam satu kalimat; pesannya
   menjelaskan **kenapa**, bukan mengulang diff. Izin commit mengikuti `docs/AGENTS.md`
   bagian "Kesepakatan kerja dan izin".
6. Buka pull request dengan templatnya (`.github/PULL_REQUEST_TEMPLATE.md`). Push, merge,
   tag, dan rilis butuh persetujuan pemilik produk (CLAUDE.md R-018).

**Gerbang lokal sebelum meminta tinjauan:**

```sh
php artisan test --compact --testsuite=Unit,Feature
npm run types
vendor/bin/pint --test
npm run build
```

**Gerbang di CI** (`.github/workflows/test.yml`) menjalankan gerbang lokal di atas,
ditambah suite Spatial di PostGIS sungguhan yang di lokal hanya dilewati saat
`SPATIAL_TEST_DSN` tidak diisi. `.github/workflows/security.yml` menambah audit dependensi
dan pemindai rahasia. Advisori baru pada dependensi terkunci dapat membuat `security.yml`
merah tanpa perubahan kode; memperbaikinya berarti mengubah versi dependensi, jadi butuh
izin pemilik.

**Bukti, bukan klaim (CLAUDE.md R-023).** Laporan selesai menyebut nama test, keluaran
perintah, atau tangkapan layar. Test hijau di SQLite tidak membuktikan perilaku PostgreSQL
(R-024): perubahan spasial, transaksi, atau constraint butuh suite Spatial.

## Menambah halaman

Satu halaman = satu rute, satu controller, satu komponen React.

1. **Rute** di `routes/web.php`, bernama, di dalam grup middleware yang tepat (`auth`,
   `role:admin`, `role:moderator`). Aksi yang mengubah data diberi `throttle`.
2. **Controller** memanggil `$this->authorize()` (Policy) untuk setiap model dari rute,
   memvalidasi lewat FormRequest, memanggil service untuk logika, memformat lewat
   Resource, lalu `Inertia::render('Area/Halaman', ['title' => 'Judul', ...])`.
   Prop `title` wajib (dijaga `PageTitleTest`). Skor internal tidak pernah masuk props.
3. **Halaman** di `resources/js/pages/Area/Halaman.tsx`, memasang layout lewat
   `Halaman.layout = (page) => <AppLayout>{page}</AppLayout>` (halaman tugas dengan bar aksi
   tetap memakai `<AppLayout bilahTab={false}>`), dan tepat satu `<PageHeader>` yang memberi
   `title` serta `induk={{ href, label }}` ke induk logisnya; hanya lima akar tab yang memberi
   `akar`. TypeScript menolak `PageHeader` tanpa salah satunya, dan `PageHeaderContractTest`
   menjaga siapa akarnya. Memakai komponen dari `resources/js/components/ui`.
4. **Tautan** memakai `Tautan` atau `Button href`. Rute bertipe diimpor dari
   `@/routes/...` (dibangkitkan Wayfinder saat `npm run dev` atau `npm run build`).
   Rute GET yang **bukan** halaman Inertia (Blade, berkas) wajib didaftarkan polanya di
   `resources/js/lib/bukanHalamanReact.json`; `InertiaLinkRegistryTest` menolak lupa.
5. **Test** fitur memakai HTTP dan `assertInertia` untuk props, otorisasi (pemilik lain
   ditolak), dan validasi. Markup yang dirender klien dijaga dari sumbernya.

## Aturan yang ditegakkan test

Melanggar salah satu ini membuat suite merah, bukan sekadar ditegur saat tinjauan.

| Aturan | Penjaga |
|---|---|
| Nilai kebijakan produk (ambang, radius, batas) tinggal di `config/hiking.php` | `HikingConfigTest` |
| Tanpa warna palet mentah, `bg-white`, atau `dark:` di React; pakai token | `InertiaLayoutTest` |
| Setiap halaman mengirim judul tab | `PageTitleTest`, `InertiaLayoutTest` |
| Setiap halaman memberi judul dan tempatnya (akar tab atau induk) | `PageHeaderContractTest` |
| Props bersama kerangka tidak bernama sama dengan props halaman | `ReminderSheetTest` |
| Wadah gulir mendatar berposisi `relative` (teks pembaca layar tidak melebarkan halaman) | `InertiaLayoutTest` |
| Tautan React tahu rute mana yang bukan Inertia | `InertiaLinkRegistryTest` |
| Setiap peta lewat `usePetaDasar` dan `WadahPeta`; MapLibre dimuat malas | `MapComponentTest`, `MapConfigTest` |
| Kontras warna dihitung di kedua tema | `ColourContrastTest` |
| Kontras dua tema lapangan paling sedikit 7:1 untuk informasi kritis | `ColourContrastTest` |
| Teks tidak di bawah 12 px; nilai skala tipe bernama mengikuti spec | `TypeFloorTest`, `TypographySystemTest` |
| Ikon berbobot per ukuran, tidak di bawah 16 px, dan tidak ada panah hiasan di label tombol | `IconSystemTest` |
| Primitif: fokus terlihat, target 44 px, nama aksesibel, label membungkus, tiga jenis keadaan kosong | `DesignPrimitivesTest`, `LongInputLayoutTest` |
| Skor kecocokan tidak pernah sampai ke halaman | `FactorBarsDoNotLeakScoreTest`, `LockedBusinessRulesTest` |
| Pemilik lain tidak dapat membuka data orang lain | `TripOwnershipTest` dan test otorisasi per fitur |
| Jejak audit mencatat nilai lama dan nilai baru | `AuditLogChangeTest` |
| Tidak ada pola kredensial yang dikenal (daftarnya di `SECURITY.md`) di berkas terlacak dan berkas baru yang tidak diabaikan git | `SecretsNotInRepoTest` |

Arah yang belum ditegakkan test: ukuran teks memakai skala tipe bernama (`text-judul`, `text-isi`, `text-kecil`, dan
seterusnya) di halaman yang dirombak W2 sampai W9. Halaman lama masih memakai `text-sm`, `text-xs`, dan sejenisnya
sampai dirombak, jadi pemakaian itu tidak membuat suite merah.

## Basis data dan migrasi

- Ringkasan skema per domain, catatan PostGIS, dan kebijakan migrasi ada di
  `database/README.md`.
- Buat migrasi dengan `php artisan make:migration --no-interaction`. Skema kanonis adalah
  isi `database/migrations/`.
- Setiap migrasi diuji oleh suite (SQLite) dan, bila menyentuh kolom spasial, transaksi,
  atau constraint, oleh suite Spatial di PostgreSQL.
- **Migrasi tidak pernah dijalankan ke Supabase tanpa izin pemilik** (R-019, DK-23).
  Setelah diizinkan, status migrasinya diverifikasi di lingkungan sasaran, bukan disimpulkan
  dari test.
- Kolom yang datanya bisa kosong menyimpan `null`, bukan nol. Nol adalah pernyataan.

## Suite spasial di mesin sendiri

Suite `Spatial` butuh PostgreSQL dengan PostGIS dan memakai `RefreshDatabase`, jadi ia
mengosongkan seluruh tabel basis data yang ditunjuknya. Jangan pernah mengarahkannya ke
Supabase; `tests/Spatial/SpatialTestCase.php` menolak host yang tampak seperti produksi.

Cara termudah, opsional dan butuh Docker Desktop: `docker-compose.yml` menyediakan PostGIS
dengan citra dan kredensial yang sama dengan CI (DK-55).

```sh
docker compose up -d postgis
SPATIAL_TEST_DSN="pgsql://hiking:hiking@127.0.0.1:54329/hiking_test" php artisan test --testsuite=Spatial
docker compose down        # data tetap di volume memount-postgis-data
docker compose down -v     # volume ikut dihapus
```

Port di mesin Anda `54329` supaya tidak bertabrakan dengan PostgreSQL lokal di `5432`.
Untuk port lain, jalankan misalnya `SPATIAL_PG_PORT=55432 docker compose up -d postgis` dan
sesuaikan DSN-nya. Di PowerShell, isi variabel dengan `$env:SPATIAL_TEST_DSN="..."` sebelum
perintah test.

Kata sandi `hiking` bukan rahasia: kontainernya sekali pakai, hanya terikat ke 127.0.0.1,
dan sama dengan layanan PostGIS di `.github/workflows/test.yml`.

## Keamanan dan privasi

- Jangan pernah mempercayai id pemilik atau peran yang dikirim klien; pemilik ditetapkan
  server, otorisasi lewat Policy.
- Unggahan foto lewat `ImageSanitizer` (EXIF dibuang, termasuk lokasi).
- Lokasi pengguna hanya diminta di mode pendakian, atas izin eksplisit.
- Aksi admin, moderator, dan pakar dicatat `AuditLogService`: `recordChange()` sesudah
  `save()`; untuk penghapusan, salin atribut sebelum menghapus lalu catat sesudah berhasil.
- Kebijakan data pribadi dan penghapusan akun: `docs/KEBIJAKAN-DATA-PRIBADI.md` (canonical).
- Kerentanan dilaporkan secara privat menurut `SECURITY.md`.

## UI dan UX

- Arah desain ada di `DESIGN.md`. Warna, radius, dan tema hanya lewat token di
  `resources/css/app.css`.
- Target sentuh utama sekitar 44×44 px, label terikat ke isian, galat dan petunjuk lewat
  `aria-describedby`, fokus selalu terlihat, makna tidak hanya lewat warna.
- Setiap gerakan seret punya alternatif tombol (WCAG 2.5.7). `prefers-reduced-motion`
  dihormati.
- Keadaan kosong menyebut tahapnya dan langkah berikutnya; data yang belum ada tidak
  dikarang.
- Primitif desain tinggal di `resources/js/components/ui/` dan dipakai lebih dulu sebelum
  menulis markup sendiri. Pameran primitif di `/dev/primitif` (hanya `APP_ENV=local`,
  sesudah masuk) menampilkan semuanya dengan contoh isi; periksa perubahan token atau
  komponen di sana pada 390 px dan 1440 px, tema terang dan gelap.

### Pratinjau visual tanpa basis data

Tangkapan 390 px dan 1440 px tidak butuh basis data lokal dan tidak menyentuh Supabase. Suite PHPUnit `Pratinjau`
merender halaman sungguhan di SQLite dalam memori berisi data contoh `DemoSeeder`, lalu menulis HTML-nya ke
`public/_pratinjau/` (diabaikan git). Suite ini tidak ikut `php artisan test` tanpa `--testsuite` dan tidak dijalankan
CI. Dari Git Bash:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
php artisan test --testsuite=Pratinjau
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
```

Dari PowerShell:

```powershell
$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; npm run build
php artisan test --testsuite=Pratinjau
$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
```

Awalan yang sama berlaku untuk setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit. `DB_URL` ikut dikosongkan
karena URL mengalahkan pengemudi koneksi: tanpanya `DB_CONNECTION=sqlite` tetap membuka basis data yang ditunjuk
`DB_URL` di `.env`. Di PowerShell, `$env:DB_URL=""` justru menghapus variabelnya sehingga `.env` mengisinya lagi; Laravel
membaca nilai `(empty)` sebagai string kosong. `php artisan test` tidak butuh awalan: `phpunit.xml` memaksa koneksi uji
sendiri.

`$env:DB_CONNECTION`, `$env:DB_DATABASE`, dan `$env:DB_URL` bertahan sepanjang jendela PowerShell itu, bukan cuma satu
perintah; lanjutan pekerjaan lain di jendela yang sama bisa tanpa sengaja memakai SQLite dalam memori. Bersihkan
sesudahnya dengan `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`, atau pakai jendela terpisah untuk
pratinjau.

Lalu buka `http://127.0.0.1:8100/_pratinjau/primitif.html`, `dasbor.html` (Beranda), `jelajah.html`, `trip.html`,
`mendaki.html`, `progres.html`, `detail-jalur.html` (halaman tugas tanpa bilah tab), `jelajah-mendaki.html` (strip
pendakian aktif), atau `jelajah-kosong.html`.

- Router `tests/Pratinjau/statis.php` membaca dan mengirim sendiri setiap berkas yang boleh disajikan di `public/`.
  Alamat lain dijawab 404 oleh router itu sendiri; `php -S` tidak pernah diberi kesempatan menafsirkan sendiri
  alamat mentahnya, sehingga `public/index.php` tidak pernah tereksekusi dan `.env` tidak pernah dibaca (ruling
  W1-10a: sebelum perbaikan ini, menyerahkan penyajian ke `php -S` sendiri bisa membocorkan alamat seperti
  `//x/favicon.svg` ke `index.php`).
- Halaman pratinjau mengganti alamat di bilah peramban ke rute aslinya (misalnya `/dashboard`) supaya tab navigasi
  yang aktif benar. Karena itu memuat ulang halaman dan menekan tautan berujung 404: buka lagi berkas `.html`-nya.
- Jalankan ulang suite sesudah setiap build, karena nama berkas aset berganti. Hentikan `npm run dev` lebih dulu:
  berkas `public/hot` membuat halaman memuat aset dari server Vite.
- Tema gelap: jalankan `document.cookie = 'tema=dark; path=/'` di konsol peramban, lalu buka lagi berkasnya. Kembali
  ke terang dengan `tema=light`, atau hapus cookie itu untuk mengikuti sistem.
- Lembar Pengingat memuat isinya dari server saat dibuka, jadi di pratinjau statis ia menampilkan keadaan galatnya.
  Isi daftarnya dijaga `ReminderSheetTest`.

## Yang tidak boleh masuk

- Kredensial dalam bentuk apa pun, termasuk di `.env.example`. Pengecualian yang disengaja
  hanya untuk lingkungan lokal dan uji, dan daftarnya ada di `SECURITY.md` bagian "Rahasia".
- Data privat pengguna di cache bersama.
- Pernyataan bahwa gunung atau jalur "aman".
- Skor numerik Route Fit yang dikirim ke peramban.
- Keluaran `npx ngodingpakeai plan get` di root repo (berkas PRD versi server dan `features/`; PRD kanonis ada di `docs/PRD.md`).

## Menguji kegunaan

Yang dapat diperiksa mesin sudah dijaga suite. Yang butuh manusia ada di
`docs/PROTOKOL-UJI-KEGUNAAN.md`: evaluasi heuristik, uji tugas dengan lima pendaki,
kuesioner SUS, dan telusur pembaca layar.
