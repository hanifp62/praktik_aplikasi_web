# Daftar periksa klik-lewat W9

Tanggal: 9 Oktober 2026 (diperbarui 14 November 2026 untuk penutupan Sprint 4)
Rujukan: rencana induk v3 gelombang 9 butir 1 ("Klik-lewat setiap kontrol di setiap layar pada 390 px dan 1440 px, tema
terang dan gelap, jalur papan ketik, gerak dikurangi, dan `forced-colors`") dan `docs/PROTOKOL-UJI-KEGUNAAN.md` bagian 4.

Penjaga otomatis: `tests/Feature/PreviewCoverageTest.php` memastikan setiap komponen halaman di `resources/js/pages/`
terdaftar di berkas ini dan dirender suite `Pratinjau`.

## Cara menjalankan

Semua perintah memakai SQLite dalam memori dan router statis `tests/Pratinjau/statis.php`, sehingga `public/index.php`
tidak pernah dijalankan dan `.env` tidak pernah dibaca:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
php artisan test --compact --testsuite=Pratinjau
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
```

Buka setiap berkas `http://127.0.0.1:8100/_pratinjau/<nama>.html` di peramban (DevTools terbuka). Untuk tema gelap,
jalankan `document.cookie = 'tema=dark; path=/'` di konsol lalu muat ulang alamat berkas `.html`-nya; untuk gerak
dikurangi dan warna paksa, nyalakan emulasi `prefers-reduced-motion: reduce` dan `forced-colors: active` di panel
Rendering DevTools. Hentikan server `php -S` sesudah selesai.

## Enam pemeriksaan di setiap halaman

1. **Lebar dan tema:** 390 px dan 1440 px, tema terang dan gelap (ditambah dua tema lapangan di mode lapangan, kartu
   darurat, dan tab Mendaki). Tidak ada gulir mendatar halaman (`document.documentElement.scrollWidth <= window.innerWidth`).
2. **Reflow 320 px (WCAG 1.4.10):** kecilkan ke 320 px; teks dan kontrol tetap utuh, kecuali peta dan tabel perbandingan
   yang memang punya wadah gulir bernama.
3. **Jalur papan ketik (WCAG 2.1.1, 2.1.2, 2.4.3, 2.4.7, 2.4.11):** `Tab` dari tautan lewati ke akhir halaman mengikuti
   urutan tampilan, cincin fokus selalu terlihat dan tidak tertutup bilah bawah, bar aksi, atau header lebar; `Escape`
   menutup lembar, dialog, dan menu akun lalu mengembalikan fokus ke pemicunya.
4. **Kontrol di halaman:** klik setiap tombol pembuka lembar, tab dalam halaman, akordeon, saringan klien, dan pemilih.
   Kontrol yang menavigasi atau mengirim formulir ke server tidak dapat selesai di router statis (alamat berubah ke rute
   asli lalu 404); kolom pengiriman dicatat **MENUNGGU SERVER** dan ditautkan ke test Feature yang menguji rute itu,
   bukan diklaim lulus di peramban (R-023). Klik penuh atas pengiriman formulir membutuhkan server lokal dengan basis
   data demo (`Q1`, izin `memount:demo`) atau Playwright (`Q17`).
5. **Gerak dikurangi (WCAG 2.3.3):** dengan `prefers-reduced-motion: reduce`, pembukaan lembar, dialog, dan toast tidak
   meluncur, dan tekan tombol tidak menyusut.
6. **Warna paksa:** dengan `forced-colors: active`, batas kontrol, cincin fokus, ikon berlabel, dan garis status tetap
   terlihat tanpa bergantung pada warna latar CSS.

## Peta setiap halaman React ke berkas pratinjau

| Komponen halaman | Berkas pratinjau (`_pratinjau/<nama>.html`) | Peran | Tema wajib | Kontrol klien utama yang diklik |
|---|---|---|---|---|
| `Welcome` | `depan` | tamu | terang, gelap | Menu masuk dan daftar, tautan contoh jalur terbit, lompat ke bagian cara kerja |
| `Galat` | `galat-404`, `galat-429`, `galat-500` | tamu | terang, gelap | Tautan kembali ke halaman asal dan ke Jelajah; di `galat-429-muat-penuh` rupa Blade tanpa bundel JS |
| `Auth/Login` | `masuk` | tamu | terang, gelap | Isian email dan kata sandi, kotak ingat saya, tautan lupa kata sandi dan daftar |
| `Auth/Register` | `daftar` | tamu | terang, gelap | Isian nama, email, kata sandi dan konfirmasi, tautan masuk |
| `Auth/ForgotPassword` | `lupa-sandi` | tamu | terang, gelap | Isian email, tautan kembali ke masuk |
| `Auth/ResetPassword` | `atur-ulang-sandi` | tamu | terang, gelap | Isian email, kata sandi baru, dan konfirmasi |
| `Auth/VerifyEmail` | `verifikasi-email` | pendaki belum verifikasi | terang, gelap | Tombol kirim ulang email verifikasi dan keluar |
| `Auth/ConfirmPassword` | `konfirmasi-sandi` | pendaki | terang, gelap | Isian kata sandi sebelum aksi sensitif |
| `Onboarding/ProfileSetup` | `onboarding`, `onboarding-pengalaman`, `onboarding-rencana`, `onboarding-selesai` | pendaki | terang, gelap | Pemilih kartu langkah 1 sampai 3, lewati, dan layar selesai dengan tiga jalur pertama |
| `Profile/Edit` | `profil`, `profil-kosong` | pendaki | terang, gelap | Tab identitas, pengalaman, preferensi, dan perlengkapan; unggah foto profil; sakelar tema dan rekam jejak; hapus akun |
| `Dashboard` | `dasbor`, `dasbor-b0`, `dasbor-b1`, `dasbor-b3`, `dasbor-b4` | pendaki | terang, gelap | Kartu fase, tombol Pengingat di kepala, tautan tenggat, kabar, dan tangga tanjakan |
| `Trails/Index` | `jelajah`, `jelajah-kosong`, `jelajah-mendaki` | pendaki | terang, gelap | Kotak cari, lembar saringan, sakelar peta/daftar di ponsel, simpan jalur, strip pendakian aktif |
| `Trails/Show` | `detail-jalur` | pendaki | terang, gelap | Simpan dengan toast Urungkan, bagian lipat pos dan cuaca, tombol Laporkan kesalahan data, bar aksi Rencanakan |
| `Trails/Awaiting` | `jalur-menunggu` | pendaki | terang, gelap | Daftar syarat publikasi yang belum lengkap, tautan kembali ke gunung |
| `Trails/Fit` | `kecocokan` | pendaki | terang, gelap | Pemilih rencana untuk membandingkan kecocokan dasar dan rencana, baris faktor, bar aksi |
| `Trails/Saved` | `simpanan` | pendaki | terang, gelap | Pilih sampai 3 untuk banding, ubah catatan pribadi, hapus dengan Urungkan |
| `Trails/Compare` | `bandingkan` | pendaki | terang, gelap | Tabel 3 jalur bergulir mendatar, buat dan cabut tautan bagikan |
| `Public/SharedComparison` | `bagikan` | tamu | terang, gelap | Tabel baca-saja tanpa catatan pribadi dan tanpa label kecocokan |
| `Mountains/Show` | `gunung`, `gunung-berfoto` | pendaki | terang, gelap | Tombol ikuti gunung, kartu gunung api PVMBG bila ada, kredit foto lisensi, daftar jalur |
| `Goals/Create` | `tujuan-baru` | pendaki | terang, gelap | Pemilih durasi, kebugaran, dan preferensi tujuan pendakian |
| `Recommendations/Show` | `hasil-rekomendasi` | pendaki | terang, gelap | Kartu hasil berlabel kecocokan, buka rincian alasan, mulai rencana trip |
| `Trips/Index` | `trip` | pendaki | terang, gelap | Kelompok trip aktif dan selesai, aksi berikutnya per kartu |
| `Trips/Create` | `rencanakan` | pendaki | terang, gelap | Pemilih jalur, jenis perjalanan, tanggal naik/selesai, jumlah peserta |
| `Trips/Edit` | `ubah-rencana` | pendaki | terang, gelap | Ubah tanggal, peserta, dan catatan rencana sebelum berangkat |
| `Trips/Show` | `ruang-kerja`, `ruang-kerja-keselamatan` | pendaki | terang, gelap | Empat langkah ruang kerja, lembar pesan rencana untuk kontak darurat, batalkan trip |
| `Trips/Preparation` | `persiapan` | pendaki | terang, gelap | Kelompok daftar periksa, saringan belum siap, catatan per butir, tambah butir sendiri |
| `Trips/Readiness` | `cek-kesiapan` | pendaki | terang, gelap | Tiga bagian kesiapan, perubahan sejak cek terakhir, konfirmasi tinjauan pra-berangkat |
| `Trips/Print` | `cetak` | pendaki | terang, gelap | Ringkasan cetak rencana, pos, dan kontak darurat; tombol cetak |
| `Trips/Hike` | `mode-lapangan`, `kartu-darurat` | pendaki | lapangan terang, lapangan gelap | Tombol pos tercapai, selisih jarak dan elevasi ke pos berikutnya, jeda/lanjut, lembar kartu darurat, akhiri atau batalkan pendakian |
| `Track/Index` | `mendaki`, `mendaki-paket` | pendaki | terang, gelap, lapangan | Pilihan mulai dari trip, unduh/perbarui/hapus paket lapangan bila sakelar nyala |
| `Community/HikeSummary` | `ringkasan`, `ringkasan-batal` | pendaki | terang, gelap | Angka pendakian selesai atau dibatalkan tanpa nada gagal, ajakan kirim laporan kondisi |
| `Community/HikerProgress` | `progres`, `progres-kosong` | pendaki | terang, gelap | Grafik per pendakian dengan rangkuman teks, target bulanan, tambah catatan manual, peta gunung |
| `Community/HikingHistory` | `riwayat` | pendaki | terang, gelap | Daftar riwayat pendakian dari trip dan catatan manual, tautan ringkasan dan laporan |
| `Community/ConditionReport` | `lapor-kondisi` | pendaki | terang, gelap | Pemilih jalur, tanggal pengamatan, tag kondisi, unggah foto berbatas 4 MB, catatan |
| `Community/TrailNews` | `kabar` | pendaki | terang, gelap | Saringan gunung diikuti/semua dan jenis sumber (resmi terpisah dari komunitas), terima kasih laporan |
| `Community/TrailContribution` | `kontribusi-ahli` | ahli bersertifikat | terang, gelap | Daftar jalur di kawasan kredensial aktif, tautan pos dan geometri |
| `Moderation/Queue` | `antrean-moderasi` | moderator | terang, gelap | Tinjau laporan menunggu, setujui atau tolak dengan alasan wajib lewat dialog konfirmasi |
| `Curator/Desk` | `meja-kurator` | kurator | terang, gelap | Antrean verifikasi orang kedua, perlu ditinjau, dan laporan kesalahan data di kawasan kurator |
| `Curator/Status` | `catatan-kurator`, `catatan-kurator-draf` | kurator | terang, gelap | Rantai bukti dan verifikasi, tombol verifikasi orang kedua atau kirim draf |
| `Curator/StatusForm` | `formulir-kurator` | kurator | terang, gelap | Sasaran gunung/jalur/segmen, nilai status, jenis bukti, unggah arsip bukti, syarat berakhir |
| `Curator/VolcanoForm` | `formulir-gunung-api` | kurator PVMBG | terang, gelap | Pemilih gunung api, level I sampai IV, sumber dan waktu pengamatan |
| `Admin/Mountains` | `admin-gunung` | admin | terang, gelap | Tabel gunung, formulir tambah/ubah gunung bersumber, tautan foto gunung |
| `Admin/MountainPhoto` | `admin-foto-gunung` | admin | terang, gelap | Unggah foto berlisensi, pemegang hak, lisensi, teks alternatif wajib |
| `Admin/Trails` | `admin-jalur` | admin | terang, gelap | Daftar jalur dengan laporan gerbang publikasi, ubah karakteristik nullable, terbitkan bila lengkap |
| `Admin/Checkpoints` | `admin-pos` | admin | terang, gelap | Daftar pos berurutan, tambah/ubah koordinat dan air, tombol naik/turun urutan |
| `Admin/TrailSegments` | `admin-segmen` | admin | terang, gelap | Daftar segmen jalur berurutan, karakter medan dan tingkat paparan, tambah/ubah/hapus dan pindah urutan |
| `Admin/TrailGeometry` | `admin-geometri` | admin | terang, gelap | Unggah dan pratinjau GPX, cari kandidat OSM untuk tinjauan manual, terapkan tanjakan |
| `Admin/OfficialStatuses` | `admin-status-resmi` | admin | terang, gelap | Tampilan baca-saja riwayat status resmi dan pengumuman pindah ke meja kurator |
| `Admin/DataSources` | `admin-sumber-data` | admin | terang, gelap | Registri sumber data dan jenis otoritasnya |
| `Admin/Authorities` | `admin-otoritas` | admin | terang, gelap | Badan pengelola kawasan dan daftar anggota kurator aktif/dicabut |
| `Admin/Credentials` | `admin-kredensial` | admin | terang, gelap | Verifikasi, tolak, atau cabut kredensial ahli bersertifikat |
| `Admin/Permits` | `admin-izin` | admin | terang, gelap | Aturan izin pendakian, jendela hari buka/tutup, kanal resmi |
| `Admin/Analytics` | `admin-analitik` | admin | terang, gelap | Hitungan corong dari profil sampai laporan kondisi dan kunjungan kembali tanpa data pribadi |
| `Admin/AuditLogs` | `admin-log-audit` | admin | terang, gelap | Jejak perubahan nilai lama dan baru oleh admin, moderator, kurator, dan ahli |
| `Admin/Health` | `admin-kesehatan` | admin | terang, gelap | Kesehatan penjadwal, kesegaran sumber, dan daftar migrasi |
| `Admin/Usability` | `admin-kegunaan` | admin | terang, gelap | Catat sesi uji tugas, heuristik, dan kuesioner SUS; ringkasan hanya muncul di atas ambang responden |
| `Dev/Primitif` | `primitif` | lokal saja | terang, gelap | Seluruh primitif `resources/js/components/ui` dalam keadaan biasa, galat, nonaktif, dan kosong |

## Halaman Blade di luar `resources/js/pages`

Ikut diperiksa pada putaran yang sama karena pendaki atau mesin pencari dapat mendarat di sana:

| View Blade | Cara membuka | Yang diperiksa |
|---|---|---|
| `resources/views/public/mountain.blade.php` | Test `PublicMountainPageTest` (perlu server untuk peramban) | Judul, status resmi terpisah dari komunitas, daftar jalur, JSON-LD sesuai isi |
| `resources/views/public/trail.blade.php` | Test `PublicTrailPageTest` dan `CheckpointJourneyAccessibilityTest` | Karakteristik dengan "Belum tersedia" bila kosong, kalimat jeda pembaca layar di perjalanan pos, sumber cuaca BMKG |
| `resources/views/offline.blade.php` | `http://127.0.0.1:8100/offline` lewat test `OfflinePageTest` | Kalimat jujur tanpa jaminan, tombol Coba muat ulang 44 px, tautan paket bila `?dibuka=paket`, rupa v3 terang dan gelap |
| `resources/views/errors/*.blade.php` | `_pratinjau/galat-429-muat-penuh.html` | Tanpa skrip, dari `HalamanGalat`, fokus dan kontras |

## Hasil

Suite `Pratinjau` merender seluruh 57 komponen halaman React ke `public/_pratinjau/*.html` dengan aset produksi nyata
(`PreviewCoverageTest` dan `php artisan test --compact --testsuite=Pratinjau` hijau). Pemeriksaan interaktif manusia di
peramban dan pengiriman POST/PUT/DELETE dengan server lokal tetap berstatus **MENUNGGU SERVER / L4 MANUSIA** sampai pemilik
menjalankan panduan klik-lewat akhir di peramban (R-023: agen tidak mengarang hasil tangkapan mata manusia):

| Berkas pratinjau | Lebar | Terang | Gelap / lapangan | Reflow 320 px | Papan ketik | Gerak dikurangi | Warna paksa | Pengiriman | Catatan / temuan |
|---|---|---|---|---|---|---|---|---|---|
| Seluruh berkas `_pratinjau/*.html` (76 berkas pratinjau untuk 57 komponen halaman React) | 390 & 1440 px | Pratinjau HTML tervalidasi (`Pratinjau` hijau) | Token diuji `ColourContrastTest` | Kontrak wadah gulir dijaga `InertiaLayoutTest` | Fokus dijaga `FocusIndicatorTest`, `ErrorPageFocusTest`, `CheckpointJourneyAccessibilityTest` | Kontrak CSS dijaga `DesignPrimitivesTest` | Kontrak garis/teks dijaga `TrustGrammarTest` | MENUNGGU SERVER (rute POST/PUT/DELETE diuji suite `Feature`) | Menunggu inspeksi L4 pemilik di peramban nyata sesuai panduan pengujian akhir |
