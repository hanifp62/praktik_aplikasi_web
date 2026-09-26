# 03 Pertimbangkan

**FR utama:** FR-06 Perbandingan jalur (§33), FR-22 Catatan pribadi (§147), FR-23 Tautan bagikan perbandingan (§148).
**Bahan:** features/03-pertimbangkan.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.6, 6.7, dan 9.3.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Dari jalur yang saya kumpulkan, mana yang paling sesuai dengan rencana saya?"

Pendaki mengumpulkan jalur tanpa batas yang berarti, menandai pertimbangannya sendiri dengan catatan pribadi, lalu membandingkan dua sampai tiga jalur berdampingan tanpa pemenang (R-016). Banding membantu melihat perbedaan; keputusannya tetap milik pendaki.

## Posisi di loop dan permukaan

- **Loop (§7):** antara menemukan rute dan merencanakan trip.
- **Tab:** Simpanan di Jelajah (`/trails/saved`); Banding di Trip (`/trails/compare`), sesuai pemetaan permukaan lama "Pertimbangkan" di §145.
- **Tautan bagikan:** halaman publik baca-saja `/b/{token}` (FR-23), belum dibangun.

## Kriteria penerimaan

1. **Simpan.** Given pendaki menyimpan jalur dari Jelajah atau detail, When membuka Simpanan, Then jalur itu ada, terbaru dulu, dan tetap ada setelah keluar lalu masuk kembali atau di perangkat lain.
2. **Isi kartu.** Given jalur tersimpan, When daftar tampil, Then setiap kartu memuat nama, gunung, durasi, jarak, tanjakan, dan tuntutan teknis; batas teknis simpanan (50) disebut sebelum ditabrak.
3. **Hapus dari simpanan.** Given pendaki menekan "Hapus dari simpanan", When berhasil, Then jalur hilang dari daftar; mengulang aksi tidak mengembalikannya, dan baris pendaki lain tidak pernah tersentuh.
4. **Kosong.** Given belum ada simpanan, When Simpanan dibuka, Then tampil cara menambah jalur dan tautan ke Jelajah.
5. **Pilih untuk banding.** Given dua simpanan atau lebih, When pendaki mencentang, Then hitungan "Dipilih N dari 3" tampil, batas banding dari config (DK-05) ditegakkan sebelum ditabrak, dan jalur yang tidak terbit tidak dapat dicentang dengan alasannya (R-022).
6. **Banding.** Given dua atau tiga jalur dipilih, When Banding dibuka, Then kolom mengikuti urutan pilihan; baris kecocokan (label tanpa skor), waktu, jarak, tanjakan, kecuraman, tuntutan teknis, navigasi, air, berkemah, medan, dan status resmi; yang ditandai hanya apakah sebuah aspek berbeda, tidak pernah mana yang lebih baik (R-016, K2).
7. **Sel kosong.** Given sebuah nilai belum tercatat, When selnya tampil, Then tertulis "Belum tersedia", bukan 0 atau tebakan.
8. **Pilihan dari tautan lama.** Given `?trails=` berisi jalur yang tidak lagi terbit atau melebihi batas, When Banding dibuka, Then jalur tak terbit tidak ditampilkan dan tidak disebut namanya, yang melewati batas disebut namanya, dan kalimatnya tampil di halaman.
9. **Catatan pribadi.** Given jalur tersimpan, When pemilik menulis, mengubah, atau menghapus catatan, Then catatan paling panjang 500 karakter (dihitung per karakter), kosong berarti menghapus, permintaan tanpa kolom catatan tidak menghapus apa pun, hanya pemilik yang dapat menyentuhnya (404 bagi yang lain), pemilik dan jalur datang dari sesi dan rute, dan penulisan dibatasi laju.
10. **Catatan tampil.** Given catatan ada, When Simpanan dibuka, Then catatan tampil di kartu sebagai teks dan tidak melebarkan halaman di 360 px; catatan tidak pernah tampil di Banding atau tautan bagikan.
11. **Tautan bagikan (FR-23).** Given pendaki membuat tautan dari Simpanan atau Banding, When orang lain membukanya tanpa akun, Then yang tampil jalur yang sama dalam mode baca-saja beserta sumber dan waktu pembaruannya, tanpa catatan dan tanpa label kecocokan pembuatnya; tautan bertoken acak, berkedaluwarsa, dapat dicabut, `noindex`, dan dibatasi laju; tautan tidak valid menampilkan pesan dan cara memperoleh tautan baru.

## Aturan yang berlaku

- **Kanonis:** R-002, R-004, R-005, R-006, R-007, R-008, R-016 (milik `DESIGN.md`), R-022.
- **Aturan bisnis:** BR-07, BR-09.
- **PRD:** §33, §77 (otorisasi tingkat objek), §83 (minimisasi data), §119 (kriteria otorisasi), §145, §147, §148.
- **Privasi:** catatan dihapus bersama akunnya (`docs/KEBIJAKAN-DATA-PRIBADI.md`).

## Data dan sumber

| Tabel atau config | Isi | Asal |
|---|---|---|
| `trail_considerations` | `user_id`, `trail_id`, `note` (paling panjang 500, null bila tidak ada), `created_at` | `create_trail_considerations_table`, `add_note_to_trail_considerations_table` (kemungkinan belum dijalankan di Supabase, INFERENCE, menunggu Q22) |
| `trails`, `official_statuses` | kolom banding dan status resmi | spesifikasi 01 dan 02 |
| `config/hiking.php` | `saved_trails.max` (50), `saved_trails.compare_max` (3), `rate_limits.personal_writes_per_minute` (30) | config |
| Tabel tautan bagikan | hash token, pemilik, daftar jalur, `expires_at` 7 hari, `revoked_at` | usulan spec v3 bagian 9.3; belum ada migrasi |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Belum ada simpanan | Cara menambah dan tautan ke Jelajah | Ada: "Belum ada rute tersimpan" di `resources/js/pages/Trails/Saved.tsx` |
| Satu simpanan | "Simpan satu jalur lagi untuk membandingkan." tanpa centang | Ada |
| Jalur ditarik dari terbit | Tetap tampil dengan catatannya, tidak dapat dicentang, alasannya disebut | Ada: `KartuSimpanan.tsx` |
| Batas banding tercapai | Hitungan dan kalimat batas | Ada: `AksiPilihan.tsx` |
| Catatan ditolak | Pesan di isian, fokus pindah ke isian | Ada: `InputError` dan `fokusGalat` di `KartuSimpanan.tsx` |
| Batas laju tercapai | Pesan jujur | Sebagian: server menjawab 429; pesan khusus di kartu menyusul W3 |
| Banding kosong | Membedakan belum memilih dari belum punya cukup simpanan | Ada: `resources/js/pages/Trails/Compare.tsx` |
| Pilihan dari tautan lama tidak lengkap | Kalimat di halaman | Ada: `pesan` dirender lewat `Alert` di `Compare.tsx` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Daftar simpanan | Ada | `app/Http/Controllers/Trails/SavedRoutesController.php`, `resources/js/pages/Trails/Saved.tsx`, `resources/js/components/trails/banding/KartuSimpanan.tsx`, `ConsiderationService::simpanan()`, `tests/Feature/SavedRoutesTest.php` |
| Pilih untuk banding | Ada (W0) | `resources/js/components/trails/banding/AksiPilihan.tsx`, `resources/js/components/trails/banding/PemilihBanding.tsx`; baki banding mengambang ala prototype 06 menyusul W3 |
| Bandingkan rute | Ada | `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php`, `resources/js/components/trails/banding/TabelBanding.tsx`, `tests/Feature/RouteComparisonTest.php`, `tests/Feature/ComparisonAsCostTest.php`; profil elevasi skala sama, sakelar "Sorot yang berbeda", dan "Jadikan dasar rencana" berbobot setara menyusul W3 |
| Catatan pribadi (FR-22) | Ada (W0) | Rute `trails.saved.note.update` dan `trails.saved.note.destroy`, `app/Http/Controllers/Trails/SavedTrailNoteController.php`, `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php`, `ConsiderationService::simpananMilik()` dan `simpanCatatan()`, `app/Policies/TrailConsiderationPolicy.php`, `tests/Feature/SavedTrailNoteTest.php`. Migrasi kolom `note` kemungkinan belum dijalankan di Supabase (INFERENCE, menunggu Q22) |
| Bagikan pilihan (FR-23) | Belum ada | Rancangan di spec v3 bagian 9.3; akses tanpa akun menunggu keputusan pemilik (spec v2 P8); tombol bagikan disembunyikan (DK-40) |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman daftar simpanan dengan data tiruan | `Saved.tsx` dengan data nyata | Ada |
| 2 | Buat kartu jalur tersimpan dengan metadata lengkap | `KartuSimpanan.tsx` di atas `TrailRow` | Ada (W0) |
| 3 | Tambah tombol simpan di jelajah dan detail | `TrailRow.tsx`, `resources/js/pages/Trails/Show.tsx`, rute `trails.timbang` | Ada |
| 4 | Implementasi hapus simpanan dan empty state | Rute `trails.saved.remove`, `ConsiderationService::remove()` | Ada |
| 5 | Bangun tabel bandingkan hingga tiga jalur | `TabelBanding.tsx` | Ada; batas 3, bukan 5 (K1) |
| 6 | Buat pemilih jalur dari daftar simpanan | `AksiPilihan.tsx`, `PemilihBanding.tsx` | Ada (W0) |
| 7 | Tampilkan keterangan jujur untuk data kosong | `TrailFigures::BELUM` di `RouteComparisonTable::baris()` | Ada |
| 8 | Buat editor catatan pribadi pada kartu jalur | `KartuSimpanan.tsx` | Ada (W0) |
| 9 | Tampilkan catatan pribadi di daftar simpanan | Props `trails.*.catatan` | Ada (W0) |
| 10 | Buat tombol bagikan dan salin tautan | Tidak ada | Belum ada (FR-23) |
| 11 | Bangun halaman publik hasil bagikan | Tidak ada | Belum ada |
| 12 | Tangani tampilan tautan kedaluwarsa dan tidak valid | Tidak ada | Belum ada |
| 13 | Buat skema dan migrasi jalur simpanan | `create_trail_considerations_table` | Ada |
| 14 | Buat endpoint CRUD jalur simpanan | Rute `trails.timbang`, `trails.saved`, `trails.saved.remove` | Ada |
| 15 | Buat skema dan endpoint catatan pribadi | `add_note_to_trail_considerations_table`, rute catatan | Ada (W0); migrasi kemungkinan belum dijalankan di Supabase (INFERENCE, menunggu Q22) |
| 16 | Buat endpoint agregasi data perbandingan jalur | `RouteComparisonTable::susun()` | Ada |
| 17 | Buat skema dan endpoint tautan bagikan | Tidak ada | Belum ada |
| 18 | Buat endpoint publik tautan bagikan | Tidak ada | Belum ada |
| 19 | Tangani validasi kedaluwarsa tautan bagikan | Tidak ada | Belum ada |

## Keputusan dan konflik terkait

- **DK-05** simpan longgar, banding sempit, batas banding 3 (diputuskan atas delegasi pemilik); **DK-07** FR-22 dan FR-23 masuk cakupan; **DK-31** "Jadikan dasar rencana" berbobot setara untuk setiap jalur; **DK-40** tombol bagikan baru tampil setelah FR-23 ada; spec v2 **P8** akses tautan tanpa akun menunggu pemilik.
- **K1** banding 3 menggantikan 5; **K2** tanpa skor kecocokan; **K21** tautan bagikan tanpa akun; **K22** catatan pribadi.
- **Cacat yang ditutup W0:** impor Wayfinder basi membuat "Hapus" di Simpanan mengirim DELETE ke rute yang tidak ada; "Hapus dari perbandingan" memanggil `router.delete` dengan alamat kosong padahal server mengirim tautan GET.
