# Kebijakan Data Pribadi

Berkas ini adalah **canonical owner** untuk kebijakan operasional data pribadi:
penghapusan akun, anonimisasi, retensi, penanganan lokasi presisi, dan batas paparan
setelah penghapusan.

- **Provenance:** dipindahkan dari `CLAUDE.md` §23 atas keputusan pemilik produk (D4).
  Seluruh ketentuan di sana dipertahankan dan diperjelas menjadi prosedur.
- **Hubungan dengan PRD:** `PRD.md` BR-13 (*Precise GPS private by default*) tetap
  menjadi referensi business-level, dan `PRD.md` §82–§84 tetap mengatur location
  privacy, data minimization, dan kontrol pengguna. Berkas ini tidak menggantikan
  keduanya; ia merinci apa yang terjadi pada data ketika akun dihapus.
- **Hubungan dengan arsitektur:** `docs/ARCHITECTURE.md` hanya menunjuk ke berkas ini.
  Ia bukan canonical policy.
- **Hubungan dengan komunitas:** `.claude/rules/mountain-core.md` R-010 menetapkan bahwa
  laporan komunitas tidak pernah menggantikan status resmi. Kebijakan ini menentukan apa
  yang terjadi pada laporan tersebut ketika penulisnya pergi.

---

## 1. Prinsip

Laporan kondisi komunitas yang sudah disetujui dan terbit **boleh** dipertahankan
sebagai pengetahuan komunitas yang dianonimkan, sepanjang kebijakan privasi/retensi yang
terdokumentasi mengizinkannya.

Alasannya adalah nilai keselamatan: laporan lapangan yang dihapus bersama akunnya akan
menghilangkan intel kondisi jalur yang mungkin masih dipakai pendaki lain untuk
memutuskan berangkat atau tidak. Yang dilepaskan adalah identitas penulisnya, bukan
informasi jalurnya.

## 2. Perlakuan pada penghapusan akun

| Data | Perlakuan |
|---|---|
| Identitas pengguna | Dihapus atau dianonimkan |
| Laporan yang sudah disetujui dan terbit | Boleh tetap ada, dengan label penulis netral |
| Catatan pribadi | Dihapus |
| GPS privat presisi | Dihapus |
| Foto pribadi | Dihapus secara default, kecuali ada dasar retensi/lisensi yang terdokumentasi |
| Draf | Dihapus |
| Konten berstatus pending atau ditolak | Mengikuti kebijakan terdokumentasi |
| Data tata kelola/audit | Dipertahankan seminimal mungkin, hanya bila memang diperlukan |

## 3. Retensi

Retensi bukan keputusan per kasus. Setiap kategori di tabel §2 memiliki dasar retensinya
sendiri, dan yang tidak punya dasar terdokumentasi diperlakukan sebagai dihapus.

Data tata kelola dan audit adalah satu-satunya kategori yang boleh bertahan atas alasan
operasional, dan hanya sejauh yang benar-benar diperlukan untuk ketertelusuran. Ia tidak
boleh menjadi pintu belakang untuk menyimpan identitas yang seharusnya sudah hilang.

## 4. Lokasi presisi

GPS presisi bersifat privat secara default (`PRD.md` BR-13), tidak diminta di luar Hike
Mode (`PRD.md` §83), dan **dihapus** pada penghapusan akun.

Jejak lokasi tidak boleh bertahan dalam bentuk apa pun yang masih dapat dikaitkan dengan
orang yang sudah menghapus akunnya, termasuk lewat laporan kondisi yang dipertahankan.
Laporan yang dipertahankan membawa konteks jalur dan segmennya, bukan koordinat pribadi
penulisnya.

## 5. Batas paparan setelah penghapusan

Setelah penghapusan akun, hal berikut **tidak boleh pernah** terpapar publik:

- email;
- nama lengkap;
- username;
- foto profil;
- GPS privat presisi.

Daftar ini bersifat tertutup dan tidak tunduk pada pengecualian operasional. Bila sebuah
fitur baru akan menampilkan salah satunya untuk akun yang sudah dihapus, fitur itu
salah, bukan kebijakannya.

## 6. Jejak audit

Peristiwa penghapusan dan anonimisasi dicatat sejauh diperlukan untuk ketertelusuran
tata kelola, dengan catatan yang tidak memuat data yang dilarang pada §5. Catatan itu
membuktikan bahwa penghapusan terjadi; ia bukan arsip dari apa yang dihapus.

## 7. Peninjauan kebijakan

Area ini tunduk pada kewajiban Pelindungan Data Pribadi Indonesia.

**Sebelum produksi publik, panduan hukum dan kewajiban operasional yang berlaku saat itu
wajib diverifikasi ulang.** Berkas ini adalah kebijakan produk, bukan nasihat hukum, dan
tidak boleh diperlakukan sebagai bukti kepatuhan.

Setiap perubahan pada berkas ini mengikuti gerbang persetujuan R-021 di `CLAUDE.md`:
kebijakan privasi tidak boleh berubah diam-diam.
