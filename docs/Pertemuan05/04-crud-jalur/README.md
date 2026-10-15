# 04. CRUD Jalur (padanan CRUD Room)

Jalur dikelola admin dari halaman **Kelola Jalur** (`/admin/trails`). Pendaki hanya membaca
jalur yang sudah terbit; mereka tidak dapat menambah atau mengubah jalur.

## 1. Ringkasan operasi

| CRUD | Di MEMOUNT | Method dan URL | Nama route | Controller |
|---|---|---|---|---|
| Create | Tambah jalur (selalu sebagai draf) | `POST /admin/trails` | `admin.trails.store` | `Admin\TrailController@store` |
| Read (admin) | Daftar jalur + ringkasan kesehatan data | `GET /admin/trails` | `admin.trails` | `Admin\TrailController@index` |
| Read (pendaki) | Jelajah dan rincian jalur terbit | `GET /trails`, `GET /trails/{slug}` | `trails.index`, `trails.show` | `Trails\TrailIndexController`, `Trails\TrailDetailController` |
| Update | Ubah data jalur | `PUT /admin/trails/{trail}` | `admin.trails.update` | `Admin\TrailController@update` |
| Update (publikasi) | Terbitkan atau tarik dari publikasi | `POST /admin/trails/{trail}/publikasi` | `admin.trails.publish` | `Admin\TrailController@togglePublish` |
| Delete | **Arsipkan** atau aktifkan kembali | `POST /admin/trails/{trail}/arsip` | `admin.trails.archive` | `Admin\TrailController@toggleArchive` |

Gunung, induk jalur, punya CRUD yang sama polanya di `/admin/mountains`
(`Admin\MountainController`: index, store, update, toggleArchive).

### Kenapa tidak ada hapus permanen

Kolom `trip_plans.trail_id` memakai `cascadeOnDelete`. Bila jalur dihapus permanen, basis
data ikut menghapus rencana trip, daftar persiapan, cek kesiapan, sesi mendaki, dan riwayat
pendakian milik pendaki yang pernah memilih jalur itu. Karena itu tidak ada route `DELETE`
untuk jalur. "Delete" diwujudkan sebagai arsip:

- `archived_at` diisi waktu sekarang;
- jalur hilang dari pencarian, rekomendasi, dan pilihan trip baru (`scopePublished()`
  mensyaratkan `archived_at IS NULL`, dan `StoreTripRequest` menolak jalur terarsip);
- rencana trip yang sudah ada tetap dapat dibuka dan menyebut bahwa jalurnya ditarik
  (`TripPlan::trailIsWithdrawn()`, `ArchivedTrailWithActiveTripTest`);
- arsip dapat dibatalkan dengan tombol **Aktifkan**.

## 2. Berkas yang terlibat

| Lapisan | Berkas |
|---|---|
| Route | [`routes/web.php`](../../../routes/web.php), grup `middleware(['auth', 'role:admin'])->prefix('admin')` |
| Controller | [`app/Http/Controllers/Admin/TrailController.php`](../../../app/Http/Controllers/Admin/TrailController.php) |
| Validasi | [`app/Http/Requests/Admin/TrailRequest.php`](../../../app/Http/Requests/Admin/TrailRequest.php) |
| Otorisasi | [`app/Policies/TrailPolicy.php`](../../../app/Policies/TrailPolicy.php) |
| Model | [`app/Models/Trail.php`](../../../app/Models/Trail.php) |
| Bentuk data ke halaman | `app/Http/Resources/Admin/TrailAdminRowResource.php` |
| Ringkasan kesehatan data | `app/Services/Admin/TrailDataHealth.php` |
| Jejak audit | `app/Services/AuditLogService.php` |
| Halaman | [`resources/js/pages/Admin/Trails.tsx`](../../../resources/js/pages/Admin/Trails.tsx) (React + Inertia) |

## 3. Alur per operasi

### Read: daftar jalur admin

1. `index()` memanggil `$this->authorize('viewAny', Trail::class)`.
2. Kueri memuat gunung dan data kelengkapan sekaligus
   (`with('mountain')->withPublishabilityData()`), sehingga jumlah kueri tidak bertambah
   per baris.
3. Daftar diurutkan menurut nama dan dipaginasi 10 baris per halaman.
4. Di atas tabel tampil ringkasan kesehatan data, misalnya berapa jalur yang belum punya
   checkpoint. Menekan satu temuan menyaring daftar lewat parameter `?perlu=`.
5. Kolom tabel: Jalur, Gunung, Checkpoint, Publikasi, Aksi. Jalur terbit yang syaratnya
   tidak lagi lengkap diberi tanda "(syarat belum lengkap)".

### Create: tambah jalur

1. Admin menekan **Tambah jalur**; formulir terbuka di halaman yang sama.
2. Formulir dikirim dengan `form.post()` milik Inertia ke `admin.trails.store`.
3. `TrailRequest::authorize()` memeriksa `TrailPolicy::create()` (hanya admin).
4. `TrailRequest::rules()` memvalidasi isian (tabel di bagian 4). Validasi tambahan di
   `after()` menolak nama yang sama di gunung yang sama dengan pesan
   "Jalur dengan nama ini sudah ada di gunung tersebut.", bukan galat 500 dari indeks unik.
5. Controller membentuk atribut: slug dari nama + id gunung, angka kosong tetap `null`.
   `is_published` tidak ada di daftar atribut, jadi jalur baru selalu draf walaupun formulir
   mengirim `is_published=1` (`test_a_new_trail_is_always_a_draft_whatever_the_payload_says`).
6. `Trail::create()` menyimpan, lalu `AuditLogService` mencatat `trail.created`.
7. Halaman kembali dengan pesan "Data jalur tersimpan."

### Update: ubah jalur

1. Tombol **Ubah** membuka formulir yang sama dengan isian jalur itu; dikirim dengan
   `form.put()` ke `admin.trails.update`.
2. `TrailPolicy::update()` mengizinkan admin. Policy juga mengizinkan pemandu bersertifikat
   jenjang Ahli untuk gunung di kawasannya, tetapi route `/admin/trails` sendiri dijaga
   middleware `role:admin`. Ahli menyumbang data lewat halaman kurasi terpisah, misalnya
   pos (`/jalur/{trail}/pos`) dan geometri (`/jalur/{trail}/geometri`).
3. Bila perubahan membuat jalur terbit kehilangan data kritis, model melempar
   `PublicationGateViolation`. Controller menangkapnya dan menampilkan kalimat penolakan,
   bukan halaman galat.
4. `AuditLogService::recordChange()` mencatat `trail.updated` beserta nilai sebelum dan
   sesudah.

### Update: terbitkan dan tarik dari publikasi

1. Tombol **Publikasikan** atau **Tarik dari publikasi** mengirim `router.post()` ke
   `admin.trails.publish`.
2. Menarik dari publikasi meminta konfirmasi lebih dulu: "Tarik jalur ini dari publikasi?
   Pendaki tidak akan menemukannya lagi di pencarian maupun rekomendasi."
3. Menerbitkan diperiksa dua lapis. Controller memanggil `publishabilityReport()` dan
   menolak dengan daftar syarat yang kurang. Bila lolos, `TrailPolicy::publish()` memeriksa
   peran admin dan kelengkapan sekali lagi, lalu penjaga di model menjadi lapis terakhir.
4. Audit mencatat `trail.published` atau `trail.unpublished`.

### Delete: arsipkan dan aktifkan kembali

1. Tombol **Arsipkan** meminta konfirmasi: "Arsipkan jalur ini? Jalur yang diarsipkan hilang
   dari pencarian dan tidak dapat dipilih untuk trip baru."
2. `TrailPolicy::archive()` hanya mengizinkan admin.
3. `archived_at` diisi atau dikosongkan; audit mencatat `trail.archive_toggled`.
4. Tombol berganti menjadi **Aktifkan** untuk jalur terarsip.

## 4. Validasi isian (`TrailRequest`)

| Isian | Aturan |
|---|---|
| `mountain_id` | wajib, harus ada di `mountains` |
| `name` | wajib, teks, maks. 120 karakter, unik per gunung |
| `description` | boleh kosong, maks. 2000 karakter |
| `distance_km` | boleh kosong, angka 0 sampai 500 |
| `elevation_gain_m`, `elevation_loss_m` | boleh kosong, bilangan bulat 0 sampai 9000 |
| `estimated_duration_minutes` | boleh kosong, bilangan bulat 0 sampai 20160 (14 hari) |
| `technical_demand`, `navigation_complexity`, `water_availability` | wajib, nilai enum |
| `terrain_character` | larik; setiap butir nilai enum `TerrainCharacter` |
| `camping_available` | boolean |
| `starting_point`, `weather_reference_area` | boleh kosong, maks. 160 karakter |
| `weather_adm4_code` | boleh kosong, maks. 40 karakter |
| `data_source_id` | boleh kosong, harus ada di `data_sources` |

Isian angka sengaja boleh kosong. Jalur boleh disimpan sebagai draf dengan data belum
lengkap; kelengkapan baru ditagih saat diterbitkan.

## 5. Otorisasi

| Aksi | Hiker | Moderator | Ahli bersertifikat | Admin |
|---|---|---|---|---|
| Buka `/admin/trails` | ditolak (middleware) | ditolak (middleware) | ditolak (middleware) | boleh |
| Tambah jalur | ditolak | ditolak | ditolak (`create()` hanya admin) | boleh |
| Ubah data jalur | ditolak | ditolak | lewat halaman kurasi, hanya gunung di kawasannya | boleh |
| Terbitkan | ditolak | ditolak | ditolak | boleh, bila syarat lengkap |
| Arsipkan | ditolak | ditolak | ditolak | boleh |

Bukti: `AdminAccessTest` (16 test),
`AdminDataManagementTest::test_a_hiker_cannot_write_catalogue_data`,
`TrailPublishGateTest::test_a_hiker_cannot_publish`.

## 6. Test yang menjaga CRUD jalur

| Berkas test | Yang dijaga | Hasil 15 Okt 2026 |
|---|---|---|
| `tests/Feature/AdminDataManagementTest.php` | tambah, ubah, nama kembar, audit, ringkasan kesehatan, gunung | 19 lulus, 125 assertion |
| `tests/Feature/TrailPublishGateTest.php` | gerbang publikasi dari sisi controller dan halaman | 10 lulus, 61 assertion |
| `tests/Feature/PublicationInvariantTest.php` | gerbang publikasi di model untuk semua jalur tulis | 18 lulus, 28 assertion |
| `tests/Feature/AdminAccessTest.php` | akses halaman admin per peran | 16 lulus, 16 assertion |
| `tests/Feature/ArchivedTrailWithActiveTripTest.php` | trip yang jalurnya diarsipkan | 5 lulus, 16 assertion |
| `tests/Spatial/PublicationGeometryInvariantTest.php` | syarat geometri di PostgreSQL | dilewati di SQLite; butuh PostGIS |

Checklist manual dan cara menjalankan test ada di [`../06-test-checklist.md`](../06-test-checklist.md).
