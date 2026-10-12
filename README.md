# Memount

> Memount adalah platform perjalanan gunung yang dipersonalisasi, yang menghubungkan
> discovery, Route Fit, preparation, readiness, field experience, trust, community, dan
> progression dalam satu perjalanan utuh. Memount membantu saya menemukan perjalanan
> gunung yang cocok untuk saya.
>
> Pernyataan pemilik produk (23 dan 25 September 2026).

Aplikasi web untuk pendaki gunung di Indonesia: memilih jalur yang sesuai dengan
pengalaman yang benar-benar dimiliki, memahami alasannya, bersiap sampai tahu apa yang
masih kurang, mendaki dengan mode lapangan, lalu melihat perkembangan diri sendiri.
Pembedanya adalah **model kepercayaan data**: setiap keterangan membawa sumber dan waktu,
keterangan resmi tidak pernah tercampur dengan laporan komunitas, dan data yang belum ada
dinyatakan "belum diketahui", tidak pernah ditebak.

## Isi

- [Stack](#stack)
- [Menjalankan di komputer sendiri](#menjalankan-di-komputer-sendiri)
- [Perintah harian](#perintah-harian)
- [Peta repo](#peta-repo)
- [Peta dokumen](#peta-dokumen)
- [Aturan yang tidak boleh dilanggar](#aturan-yang-tidak-boleh-dilanggar)
- [Keamanan](#keamanan)
- [Untuk agen AI](#untuk-agen-ai)

## Stack

| Lapisan | Teknologi (versi terpasang) |
|---|---|
| Server | Laravel 13.31, PHP 8.5 |
| Antarmuka | React 19.3 lewat Inertia 3 (satu aplikasi, tanpa API terpisah), TypeScript 5.9 |
| Gaya | Tailwind CSS 3.4 dengan token warna CSS variable, tema terang dan gelap |
| Rute bertipe | Laravel Wayfinder (dibangkitkan saat build, tidak dilacak git) |
| Peta | MapLibre GL 4.7, dimuat hanya di halaman yang menggambar peta |
| Basis data | PostgreSQL + PostGIS (produksi di Supabase); test berjalan di SQLite in-memory |
| Luring | PWA dengan service worker; jejak pendakian direkam di IndexedDB perangkat |
| Build | Vite 8 |

Keputusan arsitektur dan alasannya ada di `docs/ARCHITECTURE.md`.

## Menjalankan di komputer sendiri

Prasyarat: PHP 8.5 (ekstensi `pdo_pgsql`, `pdo_sqlite`, `mbstring`, `gd`),
Composer 2, Node 22 atau lebih baru, dan PostgreSQL 16+ dengan PostGIS, atau Docker,
bila ingin menguji fitur spasial.

```sh
git clone <url-repo> memount && cd memount
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

**Arahkan `.env` ke basis data lokal, bukan Supabase.** Bawaan `.env.example` adalah
PostgreSQL lokal bernama `hiking`. Supabase adalah basis data produksi; aksesnya hanya
dengan izin pemilik produk (lihat [Keamanan](#keamanan)).

```sh
createdb hiking && psql hiking -c "CREATE EXTENSION IF NOT EXISTS postgis"
php artisan migrate
php artisan db:seed     # data contoh lokal: admin@example.com, moderator@example.com,
                        # pendaki@example.com, kata sandi "password" (hanya untuk lokal)
composer run dev        # server, antrean, log, dan Vite sekaligus
```

Buka `http://localhost:8000`.

**Inspeksi cepat tanpa PostgreSQL.** `composer inspeksi:data` mengisi `database/demo.sqlite` dengan data
contoh dan mencetak sandi akun `budi@contoh.test`; `composer inspeksi` lalu menyalakan `http://127.0.0.1:8000`
dengan data itu. Peta jalur tampil tanpa garis rute, karena SQLite tidak punya PostGIS.

**Windows:** PHP butuh CA bundle untuk panggilan HTTPS (misalnya prakiraan BMKG). Isi
`curl.cainfo` dan `openssl.cafile` di `php.ini` dengan `ca-bundle.crt`; Git for Windows
sudah membawa satu.

## Perintah harian

```sh
php artisan test --compact --testsuite=Unit,Feature   # suite utama (SQLite in-memory)
php artisan test --compact --filter=NamaTest          # satu test
npm run types                                         # pemeriksaan tipe TypeScript
vendor/bin/pint                                       # rapikan format PHP
npm run build                                         # bangun aset produksi
```

Suite spasial butuh PostgreSQL dengan PostGIS dan dilewati bila tidak dikonfigurasi:

```sh
SPATIAL_TEST_DSN="pgsql://user:pass@127.0.0.1:5432/hiking_test" php artisan test --testsuite=Spatial
```

Tanpa PostgreSQL lokal, `docker compose up -d postgis` menyediakannya (opsional), lalu pakai DSN `pgsql://hiking:hiking@127.0.0.1:54329/hiking_test`; rinciannya di `CONTRIBUTING.md`.

CI dipecah menjadi tiga workflow (DK-54). `.github/workflows/test.yml` menjalankan build,
pemeriksaan tipe, Pint, Unit, Feature, dan Spatial di PostGIS untuk push ke `main`,
`develop`, dan `hardening`, serta untuk pull request ke `main` dan `develop`.
`.github/workflows/security.yml` menjalankan `composer audit`, `npm audit`, dan pemindai
rahasia pada pemicu yang sama, ditambah setiap Senin setelah workflow itu ada di cabang
bawaan `main`. `.github/workflows/deploy.yml` hanya dijalankan tangan untuk memverifikasi
kandidat rilis dan belum memuat langkah deploy.

`.github/workflows/migrate-supabase.yml` menyelaraskan Supabase dengan `database/migrations`, satu-satunya
sumber skema. Workflow ini dijalankan tangan dari tab Actions: ia selalu menampilkan status migrasi, dan baru
menerapkannya bila "terapkan" dicentang. Rahasia `SUPABASE_DB_URL` (connection string Session pooler dari dashboard
Supabase) diisi di Settings > Secrets and variables > Actions; kredensial tidak pernah ditulis di repo.

## Peta repo

| Tempat | Isi |
|---|---|
| `app/Http/Controllers` | Satu controller per halaman atau aksi; merender `Inertia::render('Folder/Halaman', $props)` |
| `app/Http/Requests`, `app/Policies` | Validasi server dan otorisasi per objek. Klien tidak pernah dipercaya |
| `app/Http/Resources` | Memformat angka dan tanggal untuk layar; skor internal tidak pernah keluar dari sini |
| `app/Services` | Logika domain: Route Fit, status resmi, cuaca, kesiapan, persiapan, jejak, audit |
| `app/Enums`, `app/Models` | Kosakata domain dan Eloquent; kolom spasial lewat `HasSpatialColumns` |
| `config/hiking.php` | Semua nilai kebijakan produk (ambang, radius, batas laju, sumber peta) |
| `resources/js/pages` | Halaman React, satu berkas per komponen Inertia |
| `resources/js/components` | Komponen per domain (`trails`, `trips`, `community`, `discovery`, `admin`, `peta`) dan `ui` bersama |
| `resources/js/layouts` | Kerangka halaman dan navigasi |
| `resources/css/app.css` | Token warna, radius, dan tema; satu-satunya sumber warna |
| `resources/views` | Blade yang tersisa: templat akar Inertia, halaman publik untuk mesin pencari, halaman galat, halaman luring |
| `routes/web.php`, `routes/auth.php` | Rute aplikasi |
| `tests/Feature`, `tests/Unit`, `tests/Spatial` | Test perilaku, unit, dan PostGIS |
| `database/migrations`, `database/seeders` | Skema kanonis dan data contoh; ringkasan per domain dan kebijakan migrasi di `database/README.md` |

## Peta dokumen

Indeks lengkap dokumen tim ada di [`docs/README.md`](docs/README.md). Dokumen utama untuk
anggota baru, manusia maupun agen; urutan bacanya ada di [`docs/AGENTS.md`](docs/AGENTS.md),
bagian "Cara memakai dokumen ini":

- [`docs/AGENTS.md`](docs/AGENTS.md): kesepakatan kerja tim (izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap).
- [`docs/PRD.md`](docs/PRD.md): sumber kebenaran produk.
- [`DESIGN.md`](DESIGN.md): arah desain; wajib sebelum pekerjaan antarmuka.
- [`CONTRIBUTING.md`](CONTRIBUTING.md): cara mengubah sesuatu tanpa merusak yang lain.
- [`SECURITY.md`](SECURITY.md): pelaporan kerentanan dan kebijakan rahasia.

## Aturan yang tidak boleh dilanggar

Aturan domain lengkap ada di `.claude/rules/mountain-core.md` (R-001 sampai R-014, R-022)
dan `DESIGN.md` (R-015 sampai R-017). Yang paling sering tersentuh saat mengubah UI:

- **Jalur adalah unit keputusan**, bukan gunung. Gunung mengelompokkan jalurnya.
- **Label kecocokan publik hanya tiga**: COCOK, PERLU PERSIAPAN, KURANG COCOK. Tidak ada
  persentase atau skor kecocokan dan keselamatan di layar.
- **Belum diketahui bukan rendah.** Data kosong tampil sebagai "belum tersedia" beserta
  langkah berikutnya, tidak pernah nol atau tebakan.
- **Resmi di atas komunitas.** Laporan komunitas tidak pernah menimpa status resmi, dan
  keduanya dibedakan secara visual.
- **Status BUKA bukan jaminan aman**, dan kesiapan adalah pendukung keputusan, bukan izin.
- **Progres membandingkan diri sendiri**, tanpa papan peringkat antarpengguna.

## Keamanan

- `.env` tidak pernah di-commit. Kredensial tidak pernah ditulis ke kode, skrip, test,
  dokumen, atau pesan commit; `SecretsNotInRepoTest` memindai berkas terlacak dan berkas
  baru yang tidak diabaikan git.
- **Supabase adalah produksi.** Membaca, menulis, atau menjalankan migrasi di sana hanya
  dengan izin pemilik produk untuk operasi dan lingkungan yang disebut.
- Push, merge, tag, dan rilis butuh persetujuan pemilik (CLAUDE.md R-018); izin commit mengikuti
  `docs/AGENTS.md` bagian "Kesepakatan kerja dan izin".
- Laporkan kerentanan secara privat menurut `SECURITY.md`, bukan lewat isu publik.

## Untuk agen AI

Mulai dari `CLAUDE.md`, lalu `HANDOVER.md` bagian 0, lalu `docs/AGENTS.md`. Ketiganya
memuat aturan kerja, keadaan terkini, dan prompt untuk melanjutkan di terminal baru.

**Jangan menjalankan `npx ngodingpakeai plan get` di root repo.** Perintah itu menulis
berkas PRD versi server dan folder `features/` di root. PRD kanonis ada di `docs/PRD.md`
dan salinan di root ditolak `PrdReferenceIntegrityTest`; jalankan di folder sementara
bila perlu melihat keluarannya.
