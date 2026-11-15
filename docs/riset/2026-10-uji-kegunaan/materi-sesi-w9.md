# Materi sesi uji kegunaan W9 (Q15)

Materi ini menyiapkan sesi uji kegunaan dengan 5 sampai 8 pendaki yang dijalankan pemilik (spec v3 bagian 15, Q15).
Protokol induknya `docs/PROTOKOL-UJI-KEGUNAAN.md`: heuristik, uji tugas, SUS, dan pembaca layar. Berkas ini hanya
menambahkan pemetaan tugas ke permukaan v3 dan tiga uji Q15. **Berkas ini tidak memuat hasil.** Hasil dicatat di
halaman Admin, Studi Kegunaan (`/admin/usability`), dan tidak pernah dikarang (R-023).

## Prasyarat

- **Data.** Sesi butuh aplikasi yang berjalan dengan data. Pratinjau statis tidak menjalankan kiriman apa pun, jadi
  sesi menunggu salah satu izin: `memount:demo` ke SQLite lokal (Q1) atau lingkungan uji sesudah deploy diizinkan.
- **Perangkat.** Android nyata untuk Q15 (render huruf dan angka), ditambah satu laptop untuk lebar 1440 px.
- **Persetujuan peserta.** Rekam layar dan suara hanya bila peserta setuju, diminta sebelum sesi dimulai. Peserta
  dicatat sebagai P01, P02, dan seterusnya, tanpa nama di catatan sesi.
- **Akun.** Satu akun uji per peserta, dibuat peserta sendiri di T1, dan dihapus sesudah sesi lewat Profil, Hapus
  akun.

## Naskah pembuka fasilitator

Bacakan apa adanya:

> Terima kasih sudah mau mencoba. Yang diuji hari ini aplikasinya, bukan Anda, jadi tidak ada jawaban yang salah.
> Tolong ucapkan apa yang Anda pikirkan sambil memakai aplikasinya, termasuk saat ragu atau bingung. Saya tidak akan
> membantu selama tugas berjalan, karena kami ingin melihat di mana aplikasinya membuat Anda tersendat. Anda boleh
> berhenti kapan saja.

## Tugas pada permukaan v3

Kriteria berhasil sama dengan yang tercatat di halaman Studi Kegunaan. Kolom "Yang diamati" menambah hal yang baru
ada sejak W1 sampai W8.

**T1.** Buat akun dan lengkapi profil pendaki. Mulai dari halaman tamu `/`. Melewati onboarding: pengalaman diturunkan
dari jalur terberat yang dipilih (DK-41). Yang diamati: apakah peserta mengerti mengapa ditanya jalur terberatnya, dan
apakah ia menemukan Beranda sesudahnya.

**T2.** Cari tahu jalur mana yang sesuai. Mulai dari Beranda atau tab Jelajah. Yang diamati: apakah label kecocokan
di kartu terbaca tanpa angka, dan apakah ia membuka "Cocokkah untuk saya?" tanpa dituntun.

**T3.** Jelaskan mengapa satu jalur Kurang cocok. Yang diamati: apakah alasan per faktor dibaca dan diulang dengan
kata-kata peserta sendiri. Ini tugas terpenting.

**T4.** Buat rencana trip dan siapkan perlengkapan. Mulai dari "Rencanakan trip" di rincian jalur. Yang diamati:
apakah butir yang menunggu prakiraan BMKG dikira wajib dicentang sekarang, dan apakah Urungkan ditemukan sesudah
salah centang.

**T5.** Periksa apa yang masih kurang sebelum berangkat. Yang diamati: apakah "Persiapan kritis lengkap" dibaca
sebagai jaminan keselamatan (R-014). Bila ya, catat kalimat peserta persis.

**T6.** Cari status resmi jalur dan sumbernya. Yang diamati: apakah status resmi (garis penuh) dibedakan dari laporan
sesama pendaki (garis putus-putus), dan apakah kalimat "Status resmi menjelaskan ketentuan pengelola, bukan jaminan
keselamatan." terbaca (R-010, R-012).

**T7.** Buka aplikasi tanpa sinyal. Matikan jaringan perangkat lebih dulu. Yang diamati: apakah shell luring dibaca
sebagai keputusan sengaja. Paket lapangan masih di balik sakelar yang mati (Q10, Q14), jadi yang tampil hanya teks.

**T8.** Tolak satu laporan komunitas beserta alasannya, khusus peserta moderator. Yang diamati: apakah moderator tahu
alasannya akan dibaca pelapor.

## Uji Q15

### G1. Pengenalan glyph domain

Tiga glyph pilot di `resources/js/components/ui/ikon/glyphDomain.ts`: `pos`, `tanjakan`, dan `resmi`. Tampilkan
satu per satu tanpa label, di 24 px dan 16 px, pada tema terang lalu gelap, dari halaman pameran primitif
(`/dev/primitif`, hanya di lingkungan lokal).

Pertanyaan, dibacakan sama untuk setiap glyph: "Menurut Anda, tanda ini menandai apa di aplikasi pendakian?"

Berhasil bila peserta menyebut artinya tanpa petunjuk. Catat jawaban persis, bukan tafsiran fasilitator. Set lengkap
8 sampai 12 glyph (DK-39) hanya dilanjutkan bila ketiga pilot dikenali sebagian besar peserta; ambangnya diputus
pemilik sebelum sesi.

### G2. Keterbacaan angka dan jam

Di Android nyata, ponsel sejauh lengan, sekali di dalam ruangan dan sekali di bawah cahaya luar ruangan. Minta
peserta membacakan dari kartu jalur dan ruang kerja trip: jarak (misalnya "8,0 km"), tanjakan dalam meter, rentang
waktu tempuh, dan jam berzona (misalnya "07.00 WIB"). Catat setiap salah baca beserta angkanya.

### G3. Mode lapangan di luar ruangan

Buka mode lapangan pada tema terang lalu gelap di bawah cahaya luar ruangan. Tanyakan pos berikutnya dan sisa jarak,
lalu ukur detik sampai peserta menjawab. Catat bila peserta memiringkan atau menaungi layar.

## Lembar catatan G1 sampai G3

```
Uji      : G__
Peserta  : P__
Tema     : terang / gelap
Cahaya   : dalam ruangan / luar ruangan
Jawaban persis peserta:
Salah baca (G2) atau detik sampai menjawab (G3):
Catatan fasilitator:
```

## Sesudah sesi

- Catat setiap tugas T1 sampai T8 dan setiap lembar SUS di `/admin/usability`. Rata-rata SUS tidak dikutip sebelum
  12 responden, sesuai halaman itu.
- Temuan G1 sampai G3 ditulis ke `DESIGN.md` hanya sesudah dibaca pemilik, karena glyph dan huruf adalah arah desain
  (DK-38, DK-39).
- Setiap temuan yang dapat diuji ditulis sebagai test yang gagal lebih dulu (`docs/PROTOKOL-UJI-KEGUNAAN.md`, Setelah
  selesai).

