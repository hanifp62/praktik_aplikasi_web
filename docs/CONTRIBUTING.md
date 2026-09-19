# Panduan Kontribusi

Baca `ARCHITECTURE.md` lebih dulu untuk tahu di mana sesuatu berada. Berkas ini soal cara bekerjanya.

## Menyiapkan lingkungan

```sh
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Perintah harian

```sh
php artisan test                      # seluruh suite
php artisan test --filter=NamaTest    # satu test
vendor/bin/pint                       # rapikan format
vendor/bin/pint --test                # periksa tanpa mengubah
npm run dev                           # aset mode watch
```

Suite spasial butuh Postgres dengan PostGIS dan dilewati bila tidak ada:

```sh
SPATIAL_TEST_DSN="pgsql://user:pass@127.0.0.1:5432/hiking_test" php artisan test --testsuite=Spatial
```

## Alur kerja

Proyek ini memakai TDD. Urutannya tidak dibalik:

1. Tulis test yang menggambarkan perilaku yang diinginkan.
2. Jalankan, **pastikan gagal**. Test yang lolos sebelum ada implementasi tidak menguji apa pun.
3. Tulis implementasi paling sederhana yang membuatnya lolos.
4. Jalankan seluruh suite.
5. `vendor/bin/pint`, lalu commit.

Satu commit = satu perubahan yang bisa dijelaskan dalam satu kalimat. Pesan commit menjelaskan **kenapa**, bukan mengulang diff.

## Aturan yang ditegakkan oleh test

Melanggar salah satu ini membuat suite merah, bukan sekadar ditegur saat review.

**Nilai domain masuk `config/hiking.php`, bukan konstanta kelas.** Kalau Anda menulis angka yang mewakili kebijakan produk — ambang, radius, batas — tempatnya di config. Angka yang murni matematis (jari-jari bumi pada rumus haversine tetap konstanta fisika, tapi kami menaruhnya di config juga agar satu pintu) boleh dibicarakan, angka kebijakan tidak. Dijaga `tests/Unit/HikingConfigTest.php`.

**View tidak menyebut palet mentah.** Tidak ada `emerald-600` atau `rose-100` di view. Pakai token `brand-*`, `warn-*`, `danger-*`. Mau warna baru? Tambahkan tokennya di `resources/css/app.css` dan daftarkan di `tailwind.config.js`. Dijaga `tests/Feature/DesignSystemTest.php`.

**Tombol primer dan pesan status lewat komponen.** `<x-ui.button>` dan `<x-ui.alert>`, bukan `<button class="...">` buatan sendiri. Ini yang menjaga ukuran target sentuh dan focus ring WCAG konsisten di seluruh aplikasi. Dijaga test yang sama.

**Komponen Livewire yang menerima model dari route memanggil `authorize()`.** Tanpa itu siapa pun yang menebak id bisa membuka data orang lain. Dijaga `tests/Feature/TripOwnershipTest.php`.

## Menambah fitur

**Faktor kompatibilitas baru:** tambahkan case di `App\Enums\CompatibilityFactor` beserta `defaultWeight()`, tulis method penilai di `CompatibilityScorer`, daftarkan di `score()`, tambahkan barisnya di `RecommendationRuleSeeder`. Faktor yang datanya bisa kosong **wajib** menandai dirinya unknown — jangan memberi skor netral diam-diam.

**Halaman baru:** komponen Livewire dengan `#[Layout('layouts.app')]` dan `#[Title('Judul Halaman')]`, daftarkan di `routes/web.php` dalam grup middleware yang tepat, tambahkan smoke test di `tests/Feature/PageSmokeTest.php`.

**Sumber data eksternal baru:** buat service-nya sendiri, jangan panggil dari komponen. Wajib punya penanganan kegagalan yang melaporkan "tidak tersedia" — tidak pernah menebak nilai. Simpan `source`, `fetched_at`, dan status verifikasinya mengikuti model kepercayaan PRD §60.

## Yang tidak boleh masuk

- Kredensial dalam bentuk apa pun di dalam kode atau `.env.example`
- Data privat pengguna di dalam cache bersama
- Pernyataan bahwa gunung atau jalur "aman"
- Skor numerik route fit yang dikirim ke browser
- Migrasi yang dijalankan langsung ke basis data produksi tanpa cadangan
