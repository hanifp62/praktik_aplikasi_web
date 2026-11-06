# Protokol Uji Kegunaan

Dokumen ini dapat langsung dijalankan tim. Tidak perlu alat berbayar, tidak perlu puluhan responden.

## Mengapa jumlahnya sekecil ini

Tiga angka yang menentukan bentuk protokol ini:

- **Lima pengguna menangkap sekitar 85% masalah kegunaan.** Responden keenam dan seterusnya sebagian besar menemukan masalah yang sama. Untuk menemukan masalah, lima cukup.
- **SUS butuh 12 sampai 14 responden** untuk menghasilkan skor yang dapat diandalkan. Ini angka yang berbeda karena tujuannya berbeda: mengukur, bukan menemukan.
- **Heuristic evaluation tidak butuh pengguna sama sekali.** Tim sendiri yang menjadi evaluator.

Artinya tim lima orang dapat menyelesaikan bagian pertama sendiri, hari ini juga.

---

## Bagian 1: Heuristic evaluation (tanpa pengguna)

Setiap anggota tim menelusuri aplikasi **sendiri-sendiri**, lalu hasilnya digabung. Sendiri-sendiri itu penting: evaluator yang berdiskusi lebih dulu akan saling menular dan melewatkan hal yang sama.

Waktu: sekitar 60 menit per orang.

Untuk tiap heuristik, catat: **halaman**, **apa yang terjadi**, **mengapa itu masalah**, dan **tingkat keparahan** (0 bukan masalah, 1 kosmetik, 2 kecil, 3 besar, 4 bencana).

| # | Heuristik | Yang diperiksa di aplikasi ini |
|---|---|---|
| 1 | Keterlihatan status sistem | Saat menekan "Simpan" atau "Hitung ulang", apakah terlihat sesuatu sedang berjalan? |
| 2 | Kecocokan dengan dunia nyata | Apakah istilahnya dipakai pendaki, atau istilah basis data? Periksa "elevation gain", "route fit", "readiness" |
| 3 | Kendali dan kebebasan pengguna | Setelah salah menekan "Arsipkan", adakah jalan kembali? |
| 4 | Konsistensi dan standar | Apakah tombol sekunder terlihat sama di semua halaman? Apakah tanggal ditulis dengan format yang sama? |
| 5 | Pencegahan kesalahan | Adakah aksi merusak yang tidak bertanya lebih dulu? |
| 6 | Mengenali, bukan mengingat | Di halaman kesiapan, apakah pengguna perlu mengingat apa yang ia isi di halaman persiapan? |
| 7 | Fleksibilitas dan efisiensi | Bisakah pengguna berpengalaman melompat, atau harus melewati semua langkah? |
| 8 | Desain minimalis | Adakah informasi yang tidak dipakai untuk mengambil keputusan? |
| 9 | Membantu pulih dari kesalahan | Apakah pesan galat menyebutkan **cara memperbaikinya**, bukan hanya menolak? |
| 10 | Bantuan dan dokumentasi | Ketika pengguna bertanya "kenapa jalur ini tidak cocok", apakah jawabannya ada di layar? |

**Sesudahnya:** gabungkan temuan, buang duplikat, urutkan menurut keparahan. Perbaiki yang bernilai 3 dan 4 lebih dulu.

---

## Bagian 2: Uji tugas dengan 5 pendaki

Cari lima orang yang **pernah mendaki**, bukan lima orang yang paham aplikasi. Setidaknya dua di antaranya sebaiknya pemula, karena merekalah pengguna utama produk ini.

### Aturan menjalankan

Yang paling sering merusak hasil uji adalah fasilitator yang membantu. Jadi:

- Minta peserta **berpikir dengan suara keras**. "Saya sedang mencari tombolnya" jauh lebih berguna daripada layar yang diam.
- **Jangan menuntun.** Kalau peserta bertanya "ini yang mana ya?", jawab "menurut Anda yang mana?".
- Catat **waktu** dan **jumlah salah jalan**, bukan hanya berhasil atau gagal.
- Rekam layarnya bila peserta mengizinkan. Ingatan fasilitator selalu memihak aplikasinya.
- Katakan di awal: **yang diuji aplikasinya, bukan pesertanya.** Ini bukan basa-basi; peserta yang merasa diuji akan menyalahkan dirinya dan berhenti bicara.

### Tugas

Tiap tugas punya kriteria berhasil yang dapat diamati, jadi tidak perlu ditafsirkan.

**T1. Buat akun dan lengkapi profil pendaki Anda.**
Mulai dari halaman depan. Berhasil bila profil tersimpan dan peserta sampai di dasbor.
Yang diamati: apakah peserta mengerti mengapa ditanya pengalaman mendaki? Adakah pertanyaan yang ia ragu menjawabnya?

**T2. Cari tahu jalur mana yang sesuai untuk Anda.**
Berhasil bila peserta melihat daftar hasil kecocokan.
Yang diamati: apakah ia menemukan "Buat Rencana" tanpa dituntun? Berapa lama?

**T3. Pilih satu jalur yang tidak direkomendasikan, lalu jelaskan kepada saya mengapa.**
Berhasil bila peserta dapat menyebut alasannya dengan kata-katanya sendiri.
Yang diamati: **ini tugas terpenting.** Kalau peserta tidak dapat menjelaskan alasannya, janji utama produk ini gagal.

**T4. Buat rencana trip untuk salah satu jalur, lalu siapkan perlengkapannya.**
Berhasil bila trip terbuat dan setidaknya lima item persiapan dikonfirmasi.
Yang diamati: apakah ia mengira daftar itu wajib diisi semua? Apakah "belum dikonfirmasi" terbaca sebagai "saya tidak punya"?

**T5. Periksa apakah Anda sudah siap berangkat.**
Berhasil bila peserta sampai di halaman kesiapan dan dapat menyebutkan apa yang masih kurang.
Yang diamati: apakah ia mengira hasil "siap" berarti aman? Ini yang paling berbahaya kalau salah dipahami.

**T6. Cari tahu status resmi jalur itu, dan dari mana informasinya berasal.**
Berhasil bila peserta menemukan status beserta sumber dan tanggalnya.
Yang diamati: apakah ia membedakan status resmi dari laporan sesama pendaki?

**T7. Anda sedang di basecamp dan sinyal hilang. Buka aplikasinya.**
Matikan jaringan di perangkat peserta lebih dulu.
Berhasil bila peserta memahami bahwa data lama sengaja tidak ditampilkan.
Yang diamati: apakah ia merasa aplikasinya rusak, atau mengerti itu keputusan sengaja?

**T8 (khusus moderator).** Tolak satu laporan komunitas beserta alasannya.
Berhasil bila laporan ditolak dan alasannya tersimpan.
Yang diamati: apakah moderator tahu alasannya akan dibaca pelapor?

### Lembar catatan per tugas

```
Tugas    : T__
Peserta  : P__
Waktu    : ____ detik
Hasil    : berhasil / berhasil dengan kesulitan / gagal
Salah jalan (berapa kali menekan hal yang keliru): ____
Kutipan peserta:
Masalah yang terlihat:
Keparahan (0-4):
```

---

## Bagian 3: SUS

Diberikan **setelah** semua tugas selesai, bukan di tengah. Butuh 12 sampai 14 responden untuk skor yang dapat diandalkan, jadi lima peserta uji tugas perlu ditambah responden lain yang cukup mencoba aplikasinya sebentar.

Skala 1 sampai 5: 1 sangat tidak setuju, 5 sangat setuju.

1. Saya rasa saya akan sering memakai aplikasi ini.
2. Saya merasa aplikasi ini terlalu rumit.
3. Saya rasa aplikasi ini mudah dipakai.
4. Saya rasa saya butuh bantuan orang teknis untuk bisa memakai aplikasi ini.
5. Saya rasa berbagai bagian aplikasi ini menyatu dengan baik.
6. Saya rasa ada terlalu banyak hal yang tidak konsisten di aplikasi ini.
7. Saya rasa kebanyakan orang akan cepat bisa memakai aplikasi ini.
8. Saya rasa aplikasi ini sangat merepotkan dipakai.
9. Saya merasa percaya diri saat memakai aplikasi ini.
10. Saya perlu belajar banyak dulu sebelum bisa memakai aplikasi ini.

### Cara menghitung

- Pernyataan ganjil (1, 3, 5, 7, 9): nilai dikurangi 1.
- Pernyataan genap (2, 4, 6, 8, 10): 5 dikurangi nilai.
- Jumlahkan seluruhnya, lalu kalikan 2,5.

Hasilnya 0 sampai 100. **Itu bukan persentase.** Sekitar 68 adalah rata-rata; di bawah itu berarti ada yang perlu diperbaiki.

Ini terjemahan kerja, bukan instrumen yang tervalidasi untuk bahasa Indonesia. Kalau hasilnya akan dipakai dalam tulisan ilmiah, rujuk versi SUS berbahasa Indonesia yang sudah diuji validitasnya.

---

## Bagian 4: Aksesibilitas dengan pembaca layar

Yang dapat diperiksa mesin sudah dijaga 32 test otomatis: label, satu h1 per halaman, bahasa dokumen, kontras teks dan non-teks, dan status yang tidak pernah disampaikan lewat warna saja.

Yang tersisa butuh telinga manusia. Pakai **NVDA** di Windows atau **VoiceOver** di macOS, keduanya gratis dan sudah terpasang atau mudah dipasang.

Telusuri **hanya dengan keyboard**, tanpa menyentuh tetikus:

| Yang diperiksa | Pertanyaannya |
|---|---|
| Urutan fokus | Apakah Tab bergerak mengikuti urutan yang terbaca di layar? |
| Fokus terlihat | Selalu jelas di mana fokusnya berada? |
| Perangkap fokus | Bisakah keluar dari modal hapus akun dengan keyboard? |
| Teks alternatif | Apakah foto laporan kondisi dibacakan dengan bermakna? |
| Pesan dinamis | Ketika penolakan muncul di halaman kesiapan, apakah pembaca layar mengumumkannya? |
| Formulir | Apakah galat dibacakan bersama isian yang salah? |
| Tabel | Apakah tabel admin dibacakan dengan judul kolomnya? |

Waktu: sekitar 90 menit untuk seluruh alur inti.

---

## Setelah selesai

Gabungkan temuan dari empat bagian ke satu daftar, urutkan menurut keparahan, dan **tulis tiap temuan sebagai test yang gagal lebih dulu** bila memungkinkan. Proyek ini sudah memakai pola itu di seluruh perbaikannya: temuan yang punya penjaga tidak akan kembali diam-diam.

---

## Mencatatnya di dalam sistem

Protokol ini tidak lagi berhenti sebagai dokumen. Halaman **Admin → Studi Kegunaan**
(`/admin/usability`) menjalankannya:

- Kedelapan tugas sudah terdaftar, dan kriteria berhasilnya tertulis di sebelah pilihan
  hasilnya, jadi fasilitator tidak menafsirkan ulang dari ingatan.
- "Berhasil dengan kesulitan" dihitung setengah. Membulatkannya menjadi berhasil
  menghapus persis masalah yang sedang dicari.
- SUS dihitung sendiri begitu sepuluh jawabannya lengkap. Lembar setengah terisi tetap
  tersimpan sebagai pengamatan tugas, tetapi tidak menghasilkan angka.
- Halaman itu selalu menyebut posisi studi: berapa peserta sudah ada, berapa persen
  masalah diperkirakan tertemukan menurut kurva 1-(1-0,31)^n, dan berapa responden lagi
  sebelum rata-rata SUS layak dikutip.

Yang tidak dapat digantikan sistem tetap tidak digantikan: peserta, pengamat, dan
pembaca layar harus manusia. Yang dihapus hanya ketergantungan pada ketelitian seseorang
memegang kertas dan menjumlahkan sendiri.
