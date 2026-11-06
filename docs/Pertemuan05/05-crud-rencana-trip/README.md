# 05. CRUD Rencana Trip (padanan CRUD Booking)

Rencana trip adalah "booking" pribadi pendaki: satu jalur, satu tanggal naik, dan detail
perjalanannya. Setiap pendaki hanya dapat melihat dan mengubah rencananya sendiri.

Rencana trip **bukan** pemesanan ke pengelola gunung. Sistem tidak memeriksa kuota, tidak
mencegah dua pendaki memilih jalur dan tanggal yang sama, dan tidak menerbitkan izin. Untuk
gunung berkuota, halaman trip menampilkan jendela pemesanan izin beserta tautan ke sistem
resmi pengelolanya (`PermitService::bookingWindowFor()`).

## 1. Ringkasan operasi

Semua route berada di grup `Route::middleware(['auth'])`, jadi tamu dialihkan ke halaman
masuk (`TripOwnershipTest::test_guests_are_redirected_to_login`).

| CRUD | Di MEMOUNT | Method dan URL | Nama route | Controller |
|---|---|---|---|---|
| Read (daftar) | Trip Saya | `GET /trips` | `trips.index` | `Trips\TripController@index` |
| Create (formulir) | Rencanakan trip | `GET /trips/create?trail={id}` | `trips.create` | `Trips\TripController@create` |
| Create (simpan) | Simpan rencana | `POST /trips` | `trips.store` | `Trips\TripController@store` |
| Read (satu) | Halaman trip | `GET /trips/{trip}` | `trips.show` | `Trips\TripController@show` |
| Update (formulir) | Ubah rencana | `GET /trips/{trip}/edit` | `trips.edit` | `Trips\TripController@edit` |
| Update (simpan) | Simpan perubahan | `PUT /trips/{trip}` | `trips.update` | `Trips\TripController@update` |
| Delete | Hapus rencana | `DELETE /trips/{trip}` | `trips.destroy` | `Trips\TripController@destroy` |

Di luar CRUD, status trip berpindah lewat `POST /trips/{trip}/start`, `/complete`, dan
`/cancel` (`Trips\TripLifecycleController`). Membatalkan berbeda dari menghapus: trip batal
tetap tersimpan beserta alasannya.

`POST`, `PUT`, dan `DELETE` dibatasi 30 permintaan per menit per pengguna dengan kunci
`rencana-trip` (`config/hiking.php`, `rate_limits.personal_writes_per_minute`).

## 2. Berkas yang terlibat

| Lapisan | Berkas |
|---|---|
| Route | [`routes/web.php`](../../../routes/web.php) |
| Controller | [`app/Http/Controllers/Trips/TripController.php`](../../../app/Http/Controllers/Trips/TripController.php) |
| Validasi | [`app/Http/Requests/Trips/StoreTripRequest.php`](../../../app/Http/Requests/Trips/StoreTripRequest.php), [`UpdateTripRequest.php`](../../../app/Http/Requests/Trips/UpdateTripRequest.php), trait [`ValidatesTripPlanDetails.php`](../../../app/Http/Requests/Trips/ValidatesTripPlanDetails.php) |
| Otorisasi | [`app/Policies/TripPlanPolicy.php`](../../../app/Policies/TripPlanPolicy.php) |
| Logika bisnis | [`app/Services/Trips/TripLifecycle.php`](../../../app/Services/Trips/TripLifecycle.php) |
| Data halaman | `app/Services/Trips/IndeksTrip.php` (daftar), `RencanaTrip.php` (formulir buat), `KepalaTrip.php` dan `TripSteps.php` (halaman trip) |
| Model | [`app/Models/TripPlan.php`](../../../app/Models/TripPlan.php) |
| Halaman | [`resources/js/pages/Trips/`](../../../resources/js/pages/Trips/): `Index.tsx`, `Create.tsx`, `Show.tsx`, `Edit.tsx`; tombol hapus di `resources/js/components/trips/rencana/HapusRencana.tsx` |

## 3. Alur per operasi

### Create: rencanakan dan simpan

1. Pendaki membuka **Rencanakan trip** dari rincian jalur (`?trail=`) atau memilih dari
   jalur tersimpannya. Bila belum ada jalur tersimpan, halaman menampilkan keadaan kosong
   "Belum ada jalur tersimpan" dengan arahan ke Jelajah.
2. Formulir dikirim ke `POST /trips`. `StoreTripRequest::authorize()` hanya mensyaratkan
   pengguna masuk.
3. Validasi (bagian 4). Aturan `trail_id` menolak jalur draf dan jalur terarsip, sehingga
   formulir tidak dapat dipakai untuk melewati gerbang publikasi
   (`test_a_trip_cannot_be_created_on_an_unpublished_trail`,
   `test_a_trip_cannot_be_created_on_an_archived_trail`).
4. `TripLifecycle::create()` menyusun baris baru:
   - `user_id` dari pengguna yang masuk, `status` selalu `PLANNED`;
   - `hiking_goal_id` milik orang lain tidak ditolak, tetapi dilepas menjadi `null`;
   - isian kosong disimpan sebagai `null`, bukan nol.
5. Daftar persiapan dibuat saat itu juga dari jalur yang dipilih
   (`PreparationService::generateFor()`), pengingat disinkronkan, dan dua kejadian analitik
   dicatat (`TRIP_CREATED`, `PREPARATION_STARTED`).
6. Pendaki diarahkan ke halaman Persiapan dengan pesan "Rencana trip tersimpan."

### Read: Trip Saya dan halaman trip

- `GET /trips` menampilkan kartu utama untuk trip terdekat, lalu kelompok Berlangsung,
  Mendatang, dan Selesai. Datanya disusun `IndeksTrip`, hanya dari trip milik pengguna.
- `GET /trips/{trip}` memanggil `$this->authorize('view', $trip)`. Pengguna lain mendapat
  403 (`test_another_user_cannot_open_someone_elses_trip`). Halaman ini menampilkan langkah
  berikutnya, kecocokan jalur untuk rencana ini, jendela izin, pengingat, dan bagian
  "Hapus rencana" yang menyebut alasan bila trip tidak dapat dihapus.

### Update: ubah rencana

1. `edit()` dan `update()` memeriksa `TripPlanPolicy::update()`: hanya pemilik.
2. Rencana hanya dapat diubah selama pendakian belum dimulai (`DRAFT`, `PLANNED`,
   `READY_FOR_DEPARTURE`). Sesudahnya `TripLifecycle::editRefusal()` mengembalikan pendaki
   ke halaman trip dengan kalimat "Rencana tidak dapat diubah lagi: trip berstatus ...".
3. Jalur tidak dapat diganti. Jalur lain berarti rencana lain dengan daftar persiapannya
   sendiri, jadi `trail_id`, `user_id`, dan `status` tidak ada di aturan `UpdateTripRequest`.
4. Tanggal yang tidak diubah tidak diperiksa terhadap batas hari ini. Rencana yang tanggalnya
   sudah lewat tetap dapat dirapikan nama atau catatannya
   (`test_an_unchanged_past_date_does_not_block_other_edits`).
5. `TripLifecycle::update()` berjalan dalam satu transaksi:
   - bila tanggal naik pindah, butir cuaca yang sudah dikonfirmasi kembali menjadi belum
     dikonfirmasi, karena prakiraan untuk tanggal lama tidak berlaku untuk tanggal baru
     (R-013);
   - bila tanggal atau jam mulai pindah, jam kembali yang tersimpan dikosongkan, dan halaman
     trip menyebut sebabnya.
6. Pengingat disinkronkan ulang; pendaki kembali ke halaman trip dengan pesan
   "Rencana trip diperbarui."

### Delete: hapus rencana

1. Tombol **Hapus rencana** meminta konfirmasi: "Hapus rencana trip ini? Daftar persiapan,
   penilaian kesiapan, dan pengingatnya ikut terhapus dan tidak dapat dikembalikan."
2. `destroy()` memeriksa `TripPlanPolicy::delete()`: hanya pemilik.
3. `TripLifecycle::deleteRefusal()` menolak dua keadaan:
   - trip `IN_PROGRESS`: "Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau
     batalkan trip lebih dulu.";
   - trip `COMPLETED` atau yang sudah punya riwayat: "Trip yang sudah selesai tidak dapat
     dihapus karena riwayat pendakian Anda bergantung padanya."
4. `TripLifecycle::delete()` menjalankan `DELETE` bersyarat dengan syarat yang sama di
   kueri. Bila tab lain memulai pendakian sesudah halaman dimuat, tidak ada baris yang
   terhapus dan pendaki mendapat kalimat penolakan
   (`test_deleting_after_another_tab_started_the_hike_is_refused`).
5. Butir persiapan, cek kesiapan, sesi, dan pengingat ikut terhapus lewat foreign key
   cascade. Pendaki diarahkan ke Trip Saya, dan server menyertakan pesan
   "Rencana trip dihapus." Namun halaman Trip Saya (`Trips/Index.tsx`) tidak menampilkan pesan
   itu, sehingga pendaki tidak melihat konfirmasinya. Ini temuan dari tangkapan B8 di
   [`../07-screenshots.pdf`](../07-screenshots.pdf); belum diperbaiki.

## 4. Validasi isian

Aturan rencana sama untuk buat dan ubah karena keduanya memakai trait
`ValidatesTripPlanDetails`.

| Isian | Buat (`StoreTripRequest`) | Ubah (`UpdateTripRequest`) |
|---|---|---|
| `trail_id` | wajib, jalur terbit dan tidak diarsipkan | tidak diterima |
| `hiking_goal_id` | boleh kosong, harus ada di `hiking_goals` | tidak diterima |
| `reminders_enabled` | boolean bila dikirim | tidak diterima (diatur di `/trips/{trip}/pengingat`) |
| `name` | wajib, maks. 120 karakter | sama |
| `planned_date` | wajib, tanggal, tidak sebelum hari ini menurut waktu Indonesia | wajib; batas hari ini hanya bila tanggal diubah |
| `end_date` | boleh kosong, tidak sebelum `planned_date`, paling lambat 30 hari sesudahnya | sama |
| `start_time` | boleh kosong, format `HH:MM` | sama |
| `participants_count` | boleh kosong, 1 sampai 12 | sama |
| `trip_type` | wajib, `TEKTOK`, `CAMPING`, atau `MULTI_DAY` | sama |
| `notes` | boleh kosong, maks. 1000 karakter | sama |

Batas 30 hari dan 12 peserta dibaca dari `config/hiking.php` (`trips.max_span_days`,
`trips.max_participants`). Pesan galatnya berbahasa Indonesia, misalnya "Tanggal selesai
tidak boleh sebelum tanggal mulai pendakian." dan "Jumlah peserta harus antara 1 dan 12
orang."

Batas tanggal memakai `Timezone::earliestDateInIndonesia()`, bukan tanggal UTC server. Pada
pukul 01.00 WIB, tanggal UTC masih kemarin; tanpa aturan ini pendaki dapat menyimpan rencana
untuk tanggal yang sudah lewat (`IndonesianDateFloorTest`).

## 5. Otorisasi

| Aksi | Tamu | Pemilik trip | Pengguna lain (termasuk admin) |
|---|---|---|---|
| Daftar Trip Saya | dialihkan ke login | hanya trip miliknya | hanya trip miliknya sendiri |
| Buat rencana | dialihkan ke login | boleh | boleh untuk dirinya sendiri |
| Lihat, ubah, hapus satu trip | dialihkan ke login | boleh | 403 |

`TripPlanPolicy` membandingkan `$trip->user_id === $user->id` untuk `view`, `update`, dan
`delete`. Admin tidak punya pengecualian: rencana trip adalah data pribadi pendaki.

## 6. Test yang menjaga CRUD rencana trip

| Berkas test | Yang dijaga | Hasil 15 Okt 2026 |
|---|---|---|
| `tests/Feature/TripPlanDetailTest.php` | buat dengan detail, galat validasi, ubah, hapus, penolakan hapus | 19 lulus, 132 assertion |
| `tests/Feature/TripLifecycleTest.php` | gerbang jalur terbit, pemilik dan status dari server, transisi status, batas laju, tab ganda | 25 lulus, 242 assertion |
| `tests/Feature/TripOwnershipTest.php` | akses trip milik orang lain dan tamu | 8 lulus, 21 assertion |
| `tests/Feature/IndonesianDateFloorTest.php` | batas tanggal menurut waktu Indonesia | 5 lulus, 8 assertion |
| `tests/Spatial/TripConditionalWritesTest.php` | penulisan bersyarat di PostgreSQL | hanya berjalan di suite Spatial (PostgreSQL); dilewati di SQLite |

Checklist manual dan cara menjalankan test ada di [`../06-test-checklist.md`](../06-test-checklist.md).
