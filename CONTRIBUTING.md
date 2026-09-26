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
   `Halaman.layout = (page) => <AppLayout>{page}</AppLayout>`, tepat satu `<h1>`, memakai
   komponen dari `resources/js/components/ui`.
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
| Wadah gulir mendatar berposisi `relative` (teks pembaca layar tidak melebarkan halaman) | `InertiaLayoutTest` |
| Tautan React tahu rute mana yang bukan Inertia | `InertiaLinkRegistryTest` |
| Setiap peta lewat `usePetaDasar` dan `WadahPeta`; MapLibre dimuat malas | `MapComponentTest`, `MapConfigTest` |
| Kontras warna dihitung di kedua tema | `ColourContrastTest` |
| Skor kecocokan tidak pernah sampai ke halaman | `FactorBarsDoNotLeakScoreTest`, `LockedBusinessRulesTest` |
| Pemilik lain tidak dapat membuka data orang lain | `TripOwnershipTest` dan test otorisasi per fitur |
| Jejak audit mencatat nilai lama dan nilai baru | `AuditLogChangeTest` |
| Tidak ada pola kredensial yang dikenal (daftarnya di `SECURITY.md`) di berkas terlacak dan berkas baru yang tidak diabaikan git | `SecretsNotInRepoTest` |

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
