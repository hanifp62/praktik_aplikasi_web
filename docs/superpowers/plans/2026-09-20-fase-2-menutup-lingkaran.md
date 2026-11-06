# Fase 2: Menutup Lingkaran — Rencana Implementasi

**Spec:** `docs/superpowers/specs/2026-09-20-pengalaman-pendaki-design.md`

**Tujuan:** Pendakian tidak lagi berakhir dengan mengisi formulir. Peak-end rule
mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya diingat, dan puncak
emosional produk ini saat ini berakhir di kotak isian catatan pribadi.

## Temuan pengukuran yang mengubah rencana

Diukur sebelum menulis rencana ini, dan hasilnya membelah fase ini menjadi dua bagian
yang sangat berbeda risikonya.

| Yang dibutuhkan spec | Datanya ada? |
|---|---|
| Waktu mulai dan selesai pendakian | **Ada.** `hiking_sessions.started_at` dan `ended_at`, dan sesinya bertahan setelah trip diselesaikan |
| Pos terjauh yang dicapai | **Ada.** `hiking_sessions.reached_sequence` |
| Garis jalur dan profil elevasi | **Ada.** Dibangun di Fase 1 |
| Gunung yang sudah didaki, akumulasi elevasi | **Ada.** Dapat diturunkan dari `hiking_histories` dan `trails` |
| **Jejak GPS pendaki** | **Tidak ada.** `last_known_location` satu titik yang ditimpa tiap pembaruan |
| **Waktu kedatangan tiap pos** | **Tidak ada.** `reached_sequence` hanya menyimpan yang terakhir |

Dua baris terakhir menuntut perekaman baru, dan perekaman itu menabrak kenyataan yang
tidak bisa diakali: **di hampir seluruh gunung Indonesia tidak ada sinyal.** Jejak yang
dikirim ke server saat pendakian berlangsung akan berlubang-lubang, dan lubangnya justru
di bagian yang paling menarik.

Karena itu urutannya: Tugas 1 dan 2 memakai data yang sudah ada dan dapat dikerjakan
sekarang juga; Tugas 3 dan 4 menunggu satu keputusan rancangan yang diminta di bawah.

## Batasan menyeluruh

- WCAG 2.2 AA, §88 mobile-first, anggaran query konstan, suite hijau, Pint bersih.
- **§91**: tidak ada presisi palsu. Durasi dan statistik adalah catatan, bukan klaim
  keselamatan, dan tidak boleh disajikan sebagai penilaian.
- **Tidak ada perbandingan antarpendaki pada kecepatan.** Aturan adaptasi 1 pada spec.
- Setiap tugas: test gagal dulu, perbaikan, Pint, commit.

---

## Tugas 1: Halaman hasil pendakian

**Berkas:**
- Buat: `app/Livewire/History/HikeSummary.php`, `resources/views/livewire/history/hike-summary.blade.php`
- Test: `tests/Feature/HikeSummaryTest.php`
- Ubah: `routes/web.php`, `resources/views/livewire/history/hiking-history-page.blade.php`

Seluruhnya dari data yang sudah ada.

- [x] Test gagal dulu: pemilik trip melihat ringkasan berisi nama jalur dan gunung,
      tanggal, durasi dari `started_at` ke `ended_at`, pos terjauh yang dicapai, persen
      persiapan, hasil pendakian, dan catatan pribadinya.
- [x] Test: pendaki lain mendapat 403; trip yang belum selesai belum punya ringkasan.
- [x] Test: trip tanpa sesi pendakian, yaitu yang tidak pernah memakai hike mode, tetap
      menampilkan ringkasan tanpa durasi, bukan halaman galat. Ini keadaan yang umum.
- [x] Bangun halamannya memakai `<x-ui.map>` dan `<x-ui.elevation-profile>` dari Fase 1.
- [x] Tautkan dari halaman riwayat dan dari halaman trip yang sudah selesai.
- [x] Pint, suite, commit.

## Tugas 2: Progres pribadi

**Berkas:**
- Buat: `app/Services/HikerProgressService.php`, `app/Livewire/History/HikerProgress.php`,
  `resources/views/livewire/history/hiker-progress.blade.php`
- Test: `tests/Feature/HikerProgressTest.php`

Seluruhnya turunan dari `hiking_histories` dan `trails`.

- [x] Test gagal dulu terhadap angka yang dihitung tangan: jumlah pendakian selesai,
      gunung berbeda yang sudah didaki, akumulasi elevation gain, pendakian terpanjang.
- [x] Test: pendakian yang **tidak** selesai tidak ikut dihitung sebagai puncak, tetapi
      tetap terhitung sebagai pendakian. Membatalkan pendakian karena cuaca adalah
      keputusan yang benar, dan menghapusnya dari riwayat menghukum keputusan itu.
- [x] Test: jalur tanpa `elevation_gain_m` tidak menaikkan akumulasi dan tidak dihitung
      sebagai nol; yang belum diketahui disebut, bukan dianggap nol (§95).
- [x] Test: angkanya milik pengguna itu sendiri, tidak pernah tercampur pengguna lain.
- [x] Peta gunung yang sudah didaki memakai `<x-ui.map>` dengan koordinat gunung yang
      sudah ada.
- [x] Pint, suite, commit.

---

## Menunggu keputusan: perekaman jejak

Tugas 3 dan 4 memerlukan perekaman yang belum ada, dan rancangannya bergantung pada satu
pilihan yang bukan keputusan teknis semata.

**Masalahnya:** hike mode mengirim posisi ke server hanya ketika pendaki menekan tombol,
dan di gunung tidak ada sinyal. Jejak yang direkam sisi server akan berlubang.

**Pilihan A — rekam di perangkat, kirim setelah turun.** Posisi direkam terus-menerus
di browser lewat `watchPosition` dan disimpan di IndexedDB, lalu dikirim berkelompok
ketika sinyal kembali. Jejaknya utuh. Biayanya: penyimpanan sisi klien, pengiriman
susulan, dan baterai. Selaras dengan PWA yang sudah ada.

**Pilihan B — rekam seadanya di server.** Tiap posisi yang berhasil terkirim disimpan
sebagai titik. Jauh lebih sederhana, tetapi jejaknya berlubang dan menggambarnya di peta
akan menampilkan garis lurus melintasi lembah yang tidak pernah dilalui siapa pun.

**Pilihan C — tidak merekam jejak sama sekali.** Halaman hasil pendakian memakai garis
jalur resmi, bukan jejak pendaki. Sebagian besar nilainya tetap didapat tanpa satu pun
masalah privasi maupun baterai.

**Rekomendasi: A, dengan perekaman bersifat pilihan dan dapat dihapus pendaki.** Riwayat
lokasi adalah data pribadi yang paling sensitif di aplikasi ini, dan §84 sudah mengatur
retensi. Pilihan B ditolak bukan karena sulit, melainkan karena garis lurus melintasi
lembah adalah gambar yang salah, dan produk ini tidak menampilkan yang tidak
diketahuinya.

Setelah keputusan itu diambil, Tugas 3 dan 4 disusun rinci:

- **Tugas 3:** perekaman jejak dan penggambarannya di halaman hasil pendakian.
- **Tugas 4:** waktu tempuh khas antarpos dari data komunitas, diturunkan dari kedatangan
  pos yang ikut terekam. Ditampilkan sebagai rentang khas, bukan peringkat, dan hanya
  setelah cukup banyak pendakian tercatat agar angkanya tidak dibentuk satu orang.

---

## Hasil

Selesai, dan diverifikasi pada 20 September 2026.

Kotak centang di atas ditandai belakangan, bukan sambil jalan. Cara memastikannya
dinyatakan apa adanya supaya tidak dikira lebih kuat daripada yang sebenarnya:
deliverable tiap tugas dicari di kode dan ditemukan, lalu seluruh suite dijalankan dan
lulus. Setiap langkah tidak ditelusuri ulang satu per satu.

Satu kesalahan terjadi saat verifikasi ini dan dicatat supaya tidak diulang: model
ucapan terima kasih sempat dilaporkan hilang karena dicari dengan nama `ReportThanks`,
sedangkan namanya `ReportThank`. Nama jamak yang dikira benar adalah cara yang sama
persis dengan kekeliruan `forgetCachedSnapshot` sebelumnya.
