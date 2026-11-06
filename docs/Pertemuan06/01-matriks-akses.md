# 01. Matriks Akses Memount

Matriks ini merangkum aturan akses yang benar-benar diterapkan. Akses ke permukaan publik
tidak berarti akses ke data pribadi atau aksi tulis.

## 1. Matriks per peran

| Permukaan atau aksi | Tamu | Pendaki (`hiker`) | Moderator | Admin |
|---|---|---|---|---|
| Beranda dan informasi jalur yang memang publik | Boleh | Boleh | Boleh | Boleh |
| Registrasi, masuk, lupa dan atur ulang sandi | Boleh saat belum masuk | Dialihkan dari halaman `guest` | Dialihkan dari halaman `guest` | Dialihkan dari halaman `guest` |
| Dashboard, profil, preferensi, trip, persiapan, readiness, riwayat | Harus masuk | Boleh untuk datanya sendiri | Boleh untuk datanya sendiri | Boleh untuk datanya sendiri |
| Membuka, mengubah, atau menghapus rencana trip | Ditolak ke login | Hanya pemilik trip | Hanya pemilik trip | Hanya pemilik trip |
| Membuat dan mengirim laporan kondisi | Harus masuk | Boleh sesuai validasi | Boleh sesuai validasi | Boleh sesuai validasi |
| Membaca laporan kondisi | Hanya bagian yang memang publik | Laporan yang disetujui atau miliknya | Laporan yang disetujui, miliknya, atau yang diperlukan untuk moderasi | Sama dengan moderator |
| Mengubah laporan kondisi sebelum dimoderasi | Ditolak ke login | Hanya pemilik laporan yang masih menunggu | Hanya pemilik laporan yang masih menunggu | Hanya pemilik laporan yang masih menunggu |
| Antrean dan tindakan moderasi laporan | Ditolak ke login | 403 | Boleh | Boleh |
| Panel pengelolaan gunung, jalur, sumber, status, dan audit | Ditolak ke login | 403 | 403 | Boleh |

`Admin` tidak melewati `TripPlanPolicy` sebagai pemilik trip. Hak admin atas konsol data tidak
menjadikannya pemilik objek pribadi pendaki.

## 2. Aturan tingkat objek

| Objek dan kemampuan | Aturan pada server | Bukti |
|---|---|---|
| `TripPlan` dibaca | `trip_plans.user_id` harus sama dengan ID pengguna yang masuk | `TripPlanPolicy::view()` |
| `TripPlan` diubah | Hanya pemilik | `TripPlanPolicy::update()` |
| `TripPlan` dihapus | Hanya pemilik, selain pembatasan siklus hidup | `TripPlanPolicy::delete()` dan `TripLifecycle` |
| Laporan kondisi dibaca | Disetujui, milik pengguna, atau dibaca moderator | `TrailConditionReportPolicy::view()` |
| Laporan kondisi diubah | Pemilik; aksi edit hanya ditawarkan saat status menunggu, dan penulisan tetap bersyarat | `TrailConditionReportPolicy::update()` dan `submitUpdate()` |
| Laporan kondisi dimoderasi | Moderator atau admin | `TrailConditionReportPolicy::moderate()` dan `User::isModerator()` |
| Data konsol admin diakses | Peran admin, lalu policy model untuk operasi yang memerlukannya | Grup `admin.*`, `EnsureUserHasRole`, dan policy terkait |

## 3. Hasil uji akses yang dituju

1. Tamu tidak menerima halaman privat. Route yang dilindungi `auth` mengarahkan tamu ke
   halaman masuk.
2. Pendaki tidak dapat membuka panel admin atau antrean moderasi. Penolakan role mengembalikan
   403.
3. Moderator dapat membuka moderasi, tetapi tidak panel admin.
4. Admin dapat membuka panel admin dan antrean moderasi.
5. Mengganti ID trip pada URL tidak memberi akses ke trip pengguna lain.
6. Mengirim `role=admin` pada formulir daftar tidak memberi hak admin.
7. `user_id` dan status trip ditentukan server pada alur pembuatan, bukan dipercaya dari
   payload formulir.

Test yang memeriksa perilaku tersebut tercantum di
[`03-test-checklist.md`](03-test-checklist.md). Status eksekusi di sana berlaku untuk sesi
penyusunan dokumen ini.
