# Benchmark aplikasi outdoor, kebugaran, dan perjalanan untuk Memount

| | |
|---|---|
| Tanggal | 25 September 2026 |
| Jenis | Riset produk dan UX: benchmark kompetitif, pola lintas aplikasi, konteks Indonesia |
| Status | Masukan untuk keputusan. Tidak mengubah PRD, DESIGN, kode, skema, atau Supabase |
| Akses sumber | Semua tautan diakses 25 September 2026 |
| Skill yang dipakai | `ecc:deep-research`, `ecc:search-first`, `ecc:competitive-platform-analysis`, `ecc:competitive-report-structure`, `ecc:market-research`, `ecc:product-lens`, `antislop` |

**Cara membaca bukti.** **FAKTA** tertulis di sumber `[S#]`; "(cuplikan)" berarti isi hanya terbaca dari
cuplikan mesin pencari karena halamannya menolak diakses (403/404/429), jadi bobotnya lebih rendah.
**INFERENSI** adalah simpulan penulis dan bisa salah. **REKOMENDASI** adalah usulan; yang menyentuh R-021
(Route Fit, navigasi, privasi, arsitektur, semantik penyedia eksternal) ditandai "butuh persetujuan pemilik".
**BELUM TERVERIFIKASI** berarti klaim beredar tetapi tidak saya temukan di sumber primer. Bukti internal
merujuk berkas repositori ini (daftar di bagian 8).

**Metode dan batasnya.** Pencarian web dan pembacaan halaman resmi (rilis pers, pusat bantuan, catatan rilis
App Store, situs kementerian dan balai), lalu pemetaan ke kode Memount. Firecrawl dan Exa tidak terpasang, jadi
kanalnya WebSearch dan WebFetch. Pusat bantuan AllTrails, Komoot, Wikiloc dan Play Store tidak terbaca langsung.
Tanpa uji langsung aplikasi, tanpa wawancara pengguna, tanpa angka pengguna atau rating karangan; penilaian mutu
UX pesaing adalah inferensi dari dokumentasi.

---

## 1. Ringkasan eksekutif

1. **Kategori bergeser dari "katalog + perekam" ke "lingkaran yang tahu pengguna sedang di tahap mana".**
   AllTrails: temukan jalur, rencanakan trip, lalu saat di jalur [S4]; Strava 11 Juni 2026 menyusun paket hiking
   per PLAN / TRACK / SHARE [S13]; Trips Airbnb menjadi itinerary hidup dengan peta bersama (Mei 2026) [S62][S63].
   INFERENSI: kesan "CRUD sederhana" lahir dari halaman per entitas (jalur, trip, laporan), bukan per momen.
2. **Personalisasi pemimpin pasar sempit dan dapat dijelaskan.** Komoot mengubah estimasi durasi menurut
   kebugaran tanpa mengubah rute [S22]; PeakVisor memakai pace pribadi [S34]; AllTrails Peak memakai AI untuk
   memendekkan atau melandaikan rute [S1]. Tak satu pun memberi label kecocokan per jalur dengan alasan per
   faktor. INFERENSI: Route Fit pembeda nyata, tetapi baru terasa bila menghasilkan keluaran pribadi konkret.
3. **Readiness menjadi arus utama di 2026; polanya bisa diambil tanpa angkanya.** Apple Readiness (9 September
   2026): skor 0-10, empat label, faktor pendorong yang bisa diketuk [S48][S49]; Garmin menggabungkan enam
   faktor menjadi skor 0-100 [S42][S47]. Memount mengambil label + faktor + satu tindakan (R-005, R-014).
4. **Keselamatan pesaing bergantung pada sinyal atau operator AS**: peringatan keluar jalur berbayar
   [S13][S14][S3][S36], lokasi langsung [S7][S17][S64], satelit T-Mobile [S11][S28]. INFERENSI: di gunung
   Indonesia yang umumnya tanpa sinyal, nilai Memount ada pada rencana sebelum berangkat dan paket luring.
5. **Mekanisme kepercayaan terbaik: Outdooractive, AllTrails Public Lands, FarOut.** Rute otomatis "closed"
   bila bersinggungan penutupan, kondisi komunitas hilang setelah 14 hari, pemasok diingatkan memverifikasi
   [S31][S32]; pengelola memasang alert di halaman jalur [S6]; komentar per waypoint bisa disaring dan di-flag
   [S37]. Memount punya model otoritas lebih ketat, tetapi belum punya kedaluwarsa laporan dan peringatan.
6. **Status pendakian Indonesia sangat bergerak.** SE Dirjen KSDAE 31 Agustus 2026 menutup 9 taman nasional
   dan membuka terbatas 4 kawasan [S66][S67]; Semeru ditutup sejak erupsi 19 November 2025, dibuka 24 April
   2026 hanya sampai Ranu Kumbolo [S80][S82]; booking eRinjani dihentikan 1 September 2026 [S75]. INFERENSI:
   status resmi per jalur + peringatan perubahan adalah fitur "hidup" paling bernilai dan belum disajikan
   berprovenans oleh platform lokal mana pun yang saya temukan.
7. **Pemerintah memeringkat jalur, bukan gunung.** Grading Kemenhut (Agustus 2025): Grade I-V untuk 81 jalur
   [S69]. Rinjani (Grade IV) meminta bukti pengalaman mandiri, tes kesehatan H-1, asuransi premium, pemandu
   bersertifikat atau pendamping berpengalaman [S71][S72]. Sejalan dengan R-002; membuka "tangga grade" pribadi.
8. **Pemesanan izin menyatu di kanal resmi.** AKTN (Kemenhut, 4 Juni 2026) adalah e-ticketing satu pintu dengan
   rencana pemandu, reschedule, refund, gamifikasi [S68]; eRinjani, TNGGP, TNBTS, Merbabu tetap jalan
   [S74][S84][S80][S85]. REKOMENDASI: Memount memandu waktu, syarat, dan tautan resmi, tidak menjual tiket.
9. **Pesaing lokal lebih dekat dari dugaan**: muncak.id (Unsoed) dengan segmentasi jalur, cuaca sepanjang jalur,
   perencana bekal, rekomendasi TOPSIS [S91]; MDPL dengan peta luring berbayar [S90]; Treki dengan AI "Rimba"
   [S92]. INFERENSI: parit Memount adalah aturan kejujuran, hierarki otoritas, dan kesinambungan trip.
10. **Celah fungsional terbesar: pemakaian luring.** Service worker sengaja tidak menyajikan isi saat luring
    (bukti internal) dan OpenTopoMap melarang prefetch [S99], padahal semua pembanding utama punya peta luring.
11. **Progres tanpa kompetisi punya templat matang, tetapi harus disesuaikan frekuensi mendaki.** Apple Trends
    (90 vs 365 hari, butuh 180 hari data) [S52], mode banding Strava [S15]. Untuk pendaki, pembandingnya
    "pendakian ini vs pendakian Anda sebelumnya", bukan jendela bergulir.
12. **Yang tidak ditiru:** skor angka, papan peringkat, rute buatan AI, unggahan jalur bebas (jalur ilegal
    dikenai blacklist [S97][S96]), keselamatan berbayar, foto komunitas sebagai hero, feed sosial sebagai tab.

---

## 2. Tabel perbandingan per tahap perjalanan

Sel berisi fitur terdokumentasi, bukan penilaian mutu. Tanda "-" berarti tidak ditemukan di sumber, bukan
dipastikan tidak ada. Sengaja tanpa skor gabungan.

| Aplikasi | Discovery | Route Fit / personalisasi | Preparation | Readiness | Field experience | Trust | Community | Progression |
|---|---|---|---|---|---|---|---|---|
| AllTrails | Peta + daftar, saringan, 500 ribu+ jalur [S3] | AI ubah rute: pendek, landai, indah (Peak) [S1] | Trail Conditions: cuaca, udara, serangga (Plus/Peak) [S9] | - | Navigasi, wrong-turn dan suara (Plus), luring (Plus), satelit AS [S3][S11] | Alert pengelola kawasan, moderator validasi jalur [S6][S10] | Ulasan + tag kondisi, feed Activity, daftar kolaboratif [S8][S12] | Riwayat, tantangan amal [S3] |
| Strava | Heatmap, Route Discovery (langganan) [S13] | Athlete Intelligence (AI) [S15] | Route Builder dengan umpan balik elevasi/permukaan [S13] | - | Off-route alert, Live Elevation, rute di Apple Watch [S13][S14] | Deteksi aktivitas kendaraan untuk leaderboard [S15] | Feed, Clubs dengan leaderboard [S13] | Grafik progres + mode banding; segmen, Local Legends [S15][S19] |
| Komoot | Highlights komunitas di peta [S23] | Tingkat kebugaran mengubah durasi, bukan rute [S22] | Perencana rute | - | Navigasi belokan, Apple Watch luring (Jan 2026) [S26] | Tips per Highlight [S23] | Highlights, koleksi | Lencana (peta jalan 2026) [S26] |
| Gaia GPS | Hike Map berwarna kecuraman (Mar 2025) [S28] | Adventure Modes: preset per aktivitas (Agu 2026) [S28] | Lapisan peta, unduhan dengan 3D dan routing luring [S28] | - | Luring, suara belokan, satelit T-Mobile [S28] | Lapisan data publik | - | - |
| onX Backcountry | Discover terkurasi [S29] | - | Sudut/aspek lereng, longsor salju, angin, asap [S29] | - | Luring, tracker, 3D [S29] | Rute panduan pihak ketiga berlabel ATES [S30] | Trail Reports [S29] | - |
| Outdooractive | Konten mitra wisata, peringkat konten (cuplikan) [S33] | - | - | - | BuddyBeacon (cuplikan) | Notices kuning, closures merah, rute otomatis "closed" [S32] | Current Conditions kedaluwarsa 14 hari [S31] | - |
| PeakVisor | AR identifikasi puncak, 3D [S34] | Estimasi waktu sesuai pace pribadi [S34] | Info pondok, Emergency Assistant luring [S34][S35] | - | Luring, navigasi layar kunci [S34] | Halaman dari data dewan wisata (Sep 2026) [S35] | - | Peak bagging, kompetisi bulanan, poster jejak [S34][S35] |
| Wikiloc | Jalur UGC, cari menurut area yang dilalui [S36] | - | Perencana rute (Premium) [S36] | - | Off-route alert, live tracking (Premium) [S36] | - | Jalur UGC | - |
| FarOut | Panduan jalur jarak jauh, cari waypoint (v15.5) [S38] | - | Waypoint air, camp, kota [S38] | - | Luring, Lookahead elevasi, check-in [S38] | Data mitra resmi, alert tertanam, lapor waypoint usang [S37][S38] | Komentar per waypoint, saring topik, flag [S37] | - |
| CalTopo | Shared Maps sebagai overlay rute (Mar 2026) [S39] | - | Peta bersama tim [S39] | - | Live tracking di peta bersama [S39] | Panel asal objek [S39] | Peta publik | - |
| Relive | - | - | - | - | Rekam aktivitas [S40] | - | Feed + video 3D [S40] | Cerita aktivitas [S40] |
| Garmin | Courses, Garmin Explore | Active Intelligence (AI, Connect+) [S41] | ClimbPro: daftar tanjakan dari course [S43] | Training Readiness, 6 faktor [S42][S47] | inReach SOS, LiveTrack [S45][S46] | - | Tantangan, level, leaderboard koneksi [S44] | Lencana, dasbor performa [S41][S44] |
| Apple | Maps hikes (AS), topografi AS/Jepang [S54] | Workout Buddy (AI) [S48] | Rute kustom dan luring di Maps [S54] | Readiness 0-10 + 4 label [S48][S49] | Compass waypoint, Backtrack, titik sinyal terakhir, SOS satelit [S50][S51] | - | Kompetisi 7 hari [S53] | Trends 90 vs 365 hari, awards [S52] |
| Google Maps | Ask Maps (AS dan India) [S57] | Gemini | AI Mode Canvas itinerary (AS) [S58] | - | Navigasi darat | Ulasan tempat | - | - |
| muncak.id | 168 jalur, segmentasi jalur [S91] | Rekomendasi TOPSIS: pengalaman, kebugaran, pemandangan [S91] | Bekal (beta), prediksi kalori, cuaca sepanjang jalur [S91] | - | - | Klaim pendekatan saintifik [S91] | Ulasan kondisi [S91] | - |
| MDPL | 100+ gunung Indonesia [S90] | - | - | - | Peta luring + GPS [S90] | Klaim data terkurasi [S90] | - | - |
| **Memount (kode 25 Sep 2026)** | Peta bentuk jalur, saringan, banding maks 5 | Route Fit 7 faktor, 3 label, penjelasan per faktor | Trip, daftar persiapan dari templat, data izin di skema | Cek kesiapan pra-berangkat, label kualitatif | Mode pendakian, pos terdekat, rekam jejak opsional; **tanpa paket peta luring** | Hierarki otoritas, provenans, status berjenjang, cuaca BMKG | Laporan kondisi termoderasi, "terima kasih" | Riwayat, progres diri; **tanpa notifikasi** |

Baris Memount berasal dari bukti internal: enum `CompatibilityFactor` (7 faktor), `RouteFitLabel`,
`ReadinessState`, `OfficialStatusValue`, `SourceType`, migrasi `permit_requirements`, `resources/views/sw.blade.php`,
dan tidak adanya direktori `app/Notifications`.

---

## 3. Temuan per aplikasi

### 3.1 AllTrails
- **IA (cuplikan pusat bantuan)**: lima tab Explore, Saved, Navigate, Activity, Profile. Saved menampung jalur
  tersimpan, daftar, peta unduhan, rute kustom; Activity berisi riwayat sendiri dan aktivitas orang lain [S8].
- **Peta**: peta dan daftar di Explore; pratinjau 3D (Plus); heatmap lalu lintas jalur dari data agregat
  anonim (Peak) [S1][S3].
- **Terbaru**: Peak (12 Mei 2025, US$79.99/tahun) berisi Custom Routes dengan AI (balik arah, pendekkan,
  kurangi tanjakan, rute indah), Trail Conditions (15 faktor cuaca + ulasan terbaru), Community Heatmap,
  Outdoor Lens [S1][S2]. Navigasi suara untuk Plus dan tag trail running (28 Mei 2026) [S3][S5]. Satelit
  T-Mobile untuk anggota yang memenuhi syarat di AS (cuplikan, Desember 2025) [S11]. TIME (30 April 2026):
  95 juta pengguna per Maret 2026, pembaruan kondisi dan penutupan dari 800+ mitra agensi, integrasi
  ChatGPT dan Apple Intelligence [S4].
- **Kepercayaan**: Public Lands Program (rilis 5 Maret 2024) membuat pengelola kawasan bisa menyunting info
  jalur dan mengirim alert real time; 250+ agensi saat itu [S6]. Jalur usulan divalidasi moderator, bisa
  berhari-hari sampai berbulan-bulan (cuplikan) [S10].
- **Keselamatan**: Live Share (dulu Lifeline) untuk anggota berbayar: hingga 5 kontak, tautan unik per sesi,
  rute rencana, jam mulai dan selesai, lokasi langsung; butuh koneksi data; tautan kedaluwarsa saat aktivitas
  berakhir (cuplikan) [S7]. Peringatan "terlambat kembali" tercatat di rilis Lifeline 2018; keberadaannya di
  Live Share sekarang BELUM TERVERIFIKASI.
- **Kelemahan**: luring dan wrong-turn alert berbayar [S3]; cakupan Indonesia bergantung kontribusi (INFERENSI).
  **Untuk Memount**: tiru alert pengelola di halaman jalur; heatmap hanya sebagai agregat; tolak AI rute.

### 3.2 Strava
- **IA**: Home (feed), Maps (heatmap, segmen, rute, tersimpan), Record, Groups (tantangan, klub), You (profil,
  statistik, kebugaran) (cuplikan liputan media; tanggal rilis tata letak belum saya verifikasi) [S18].
- **Terbaru**: Record didesain ulang 16 Juli 2025 sehingga peta dan statistik tampil bersamaan [S16]. 28 Mei
  2026: Route Deviation Alerts, getar halus saat keluar rute, untuk pelanggan, di Apple Watch, Record, dan
  Live Activity [S14]. 11 Juni 2026, paket hiking: gaya peta dengan trailhead dan camp (gratis), Route
  Discovery dan Route Builder dengan umpan balik jarak, elevasi, permukaan (langganan), Off-Route Alerts dan
  Offline Routes (langganan), rute di Apple Watch dan Live Elevation (gratis), Clubs dengan leaderboard,
  Flyover 3D [S13]. 21 Februari 2025: mode banding pada grafik progres; deteksi aktivitas di kendaraan dengan
  57 faktor untuk menjaga leaderboard [S15][S21].
- **Keselamatan dan privasi**: Beacon gratis di aplikasi ponsel sejak 2021, hingga 3 kontak, tanpa perlu
  akun Strava, termasuk sisa baterai [S17]. Titik awal dan akhir bisa disembunyikan per alamat, semua, atau
  seluruh peta [S20].
- **Kelemahan**: mekanik inti kompetitif (segmen; Local Legends = paling sering melintasi segmen dalam 90 hari)
  [S19]; navigasi hiking utama berbayar. **Untuk Memount**: peta + statistik satu layar dan elevasi langsung di
  Track; privasi titik awal dan akhir untuk semua yang dibagikan.

### 3.3 Komoot
- **IA**: struktur tab tidak terverifikasi (pusat bantuan 403). Desain ulang 15 September 2025: kotak cari
  lebih menonjol, rekomendasi lebih menonjol, penekanan pada foto [S24][S25].
- **Personalisasi**: tingkat kebugaran (Untrained sampai Pro) mengubah estimasi waktu bergerak, tidak mengubah
  rute; tingkat kesulitan (mudah, menengah, ahli) menggabungkan kebugaran, jenis olahraga, dan kerumitan
  teknis [S22].
- **Komunitas**: Highlights adalah tempat atau segmen rekomendasi komunitas dengan nama, foto, dan tips,
  tampil di peta; kontribusi ditumpuk pada Highlight yang sama [S23].
- **Terbaru**: diakuisisi Bending Spoons Maret 2025; laporan PHK sekitar 75 persen (heise) sampai 85 persen
  (BikeRadar) [S24][S25]. Aplikasi Apple Watch mandiri dengan peta dan navigasi luring (26 Januari 2026); peta
  jalan 2026: rerouting otomatis, berbagi lokasi langsung, heatmap dan waypoint pribadi, lencana, mode gelap
  [S26]. Pratinjau jalur memakai foto komunitas (cuplikan) [S27].
- **Kelemahan**: ketidakpastian organisasi; sumber pihak ketiga saling bertentangan soal fitur gratis
  sinkron ke perangkat, jadi tidak saya pakai.
- **Untuk Memount**: personalisasi yang mengubah estimasi, bukan rute, adalah pola aman untuk Route Fit.

### 3.4 Gaia GPS
- **Peta dan IA**: berpusat pada peta berlapis. Gaia Hike Map (18 Maret 2025) mewarnai kecuraman jalur dari
  hijau sampai ungu dan menandai isi ulang air serta camp [S28]. Adventure Modes (13 Agustus 2026): preset
  Hike, Off-Road, Snow yang mengatur lapisan dengan satu ketukan [S28]. Unduhan peta kini menyertakan terrain
  3D dan data routing luring; dukungan satelit T-Mobile (cuplikan catatan rilis) [S28].
- **Kelemahan**: INFERENSI, banyaknya lapisan membebani pemula; data berorientasi AS.
- **Untuk Memount**: warna kecuraman pada profil elevasi dan segmen; satu tombol "mode lapangan".

### 3.5 onX Backcountry
- **Fitur**: Route Builder yang menempel ke jalur, peta 3D, luring, tracker, sudut dan aspek lereng,
  prakiraan longsor salju, angin per jam 8 hari, indeks kualitas udara, lapisan kebakaran dan asap, Trail
  Reports komunitas, Share Beta lewat GPX atau tautan [S29]. Musim 2025-26: ratusan rute ski dari Beacon
  Guidebooks berlabel ATES 1-4 [S30]. Kelemahan: fokus AS dan musim salju.
- **Untuk Memount**: label medan resmi pihak ketiga (ATES) ditampilkan apa adanya dengan sumbernya. Analognya
  di Indonesia adalah grade jalur Kemenhut.

### 3.6 Outdooractive
- **Kepercayaan**: organisasi pariwisata memasang notices (kuning) dan closures (merah) sebagai lapisan
  peta; rute yang bersinggungan dengan area tertutup otomatis berstatus "closed"; setiap entri punya tanggal
  berlaku; pengunggah closure menerima pengingat otomatis untuk memverifikasi dan memperbarui [S32].
- **Komunitas**: Current Conditions dari pengguna tersembunyi otomatis setelah dua minggu, tetap bisa dilihat
  sampai 365 hari lewat penggeser waktu, dan terpisah dari komentar yang berlaku tanpa batas [S31]. Konten
  diberi skor 0-100 untuk peringkat (cuplikan) [S33].
- **Kelemahan**: INFERENSI, model bisnis B2B pariwisata bisa mendorong konten promosi.
- **Untuk Memount**: model paling dekat dengan hierarki otoritas Memount. Tiru kedaluwarsa laporan dan
  pengingat verifikasi untuk pemasok status.

### 3.7 PeakVisor
- **Fitur (App Store v5.115, September 2026)**: AR penanda puncak, peta 3D, luring, navigasi layar kunci,
  estimasi waktu yang menyesuaikan pace pribadi, info pondok dan jadwal, Emergency Assistant luring (nomor
  darurat dan P3K), peak bagging dan kompetisi bulanan [S34]. Track Poster (19 Juni 2025); halaman 842 tempat
  di Austria dan Italia dari data dewan wisata lokal (11 September 2026) [S35]. Klaim "Weekly Streak" dan
  "cincin progres" hanya muncul di cuplikan: BELUM TERVERIFIKASI.
- **Untuk Memount**: bantuan darurat luring di paket lapangan; tolak kompetisi peak bagging.

### 3.8 Wikiloc
- **Premium**: perencana rute, peta 3D, live tracking, kirim ke Garmin, Apple Watch, Suunto, COROS; peringatan
  keluar jalur dengan indikator arah dan audio; cari jalur menurut area yang dilalui (cuplikan) [S36].
- **Kelemahan**: INFERENSI, jalur UGC tanpa gerbang publikasi berisiko menyebarkan jalur tak resmi.
- **Untuk Memount**: pencarian "jalur yang melewati titik X" relevan (mis. melewati Ranu Kumbolo).

### 3.9 FarOut
- **Fitur (App Store v15.5, 15 September)**: panduan luring jalur jarak jauh, waypoint air, camp, persimpangan,
  kota; komentar komunitas; check-in lokasi untuk keluarga; follow; Lookahead elevasi; alert jalur tertanam;
  data resmi dari mitra; pencarian waypoint dan rute [S38].
- **Kepercayaan**: komentar dikategorikan AI per topik (air, konektivitas, camp, kondisi jalur, trail magic,
  barang hilang) dan bisa di-flag; waypoint bisa dilaporkan usang, keliru, atau salah letak [S37].
- **Untuk Memount**: laporan tertambat ke pos, saring per topik, tombol "pos ini keliru" ke antrean moderasi.

### 3.10 CalTopo
- **Fitur (27 Maret 2026)**: Shared Maps di seluler sebagai overlay penemuan rute; panel Object Details
  menjelaskan asal objek; live tracking pada peta bersama tim [S39]. **Untuk Memount**: panel "dari mana data
  ini" pada setiap objek peta (pos, segmen, status).

### 3.11 Relive
- **Fitur**: video cerita 3D, feed teman, Relive Plus; tidak ada pengumuman fitur 2025-2026 di situs [S40].
  **Untuk Memount**: kebutuhan berbagi itu nyata, tetapi video 3D berat di ponsel kelas bawah dan bertentangan
  dengan MOTION 1 di DESIGN.md. Paling jauh kartu statis (P2).

### 3.12 Garmin (Connect, Explore, inReach)
- **Connect+ (27 Maret 2025, US$6.99/bulan)**: Active Intelligence (AI, beta), dasbor performa 120+ templat,
  LiveTrack diperluas, lencana dan tantangan eksklusif [S41]. **Gamifikasi**: lencana berpoin, level 1-10,
  leaderboard dengan teman [S44].
- **Training Readiness**: dihitung dari skor tidur, waktu pemulihan, status HRV, beban akut, riwayat tidur,
  dan riwayat stres [S42]. Skala 0-100 dan tingkat Poor sampai Prime bersumber sekunder [S47].
- **ClimbPro**: daftar tanjakan pada course (jarak, gradien rata-rata, total naik), layar khusus saat
  mendekati tanjakan, warna per kecuraman [S43].
- **inReach**: SOS ke tim Garmin Response, LiveTrack dan MapShare; laporan tahunan Garmin menyebut SOS paling
  sering dikirim saat hiking dan backpacking [S45][S46].
- **Untuk Memount**: pola ClimbPro untuk "tanjakan berikutnya"; faktor pendorong kesiapan tanpa angka.

### 3.13 Apple (Maps, Watch, Fitness)
- **Readiness** (9 September 2026, hanya Series 12 dan Ultra 4): skor 0-10, label Go for it 8-10, Ready 5-7,
  Pace Yourself 2-4, Recover 0-1; masukan aktivitas, tanda vital, tidur; faktor pendorong bisa diketuk
  [S48][S49]. Siaran pers tidak memuat penafian medis untuk Readiness, hanya untuk Vitals [S48].
- **watchOS 27** (WWDC 8 Juni 2026; rilis 14 September, cuplikan): Workout Buddy tanpa iPhone; tidak ada
  navigasi hiking baru [S56]. **Maps**: hikes dan topografi AS/Jepang sejak iOS 18 [S54]; iOS 27 tanpa fitur
  hiking baru yang tercatat [S55].
- **Lapangan**: Ultra 3 (9 September 2025) dengan SOS dan pesan via satelit [S50]; Compass dengan waypoint,
  peringatan ketinggian, titik sinyal seluler terakhir, titik panggilan darurat (AS, Kanada, Australia),
  Backtrack [S51]. **Progres**: Trends 90 vs 365 hari (butuh 180 hari data), awards [S52]; kompetisi 7 hari [S53].
- **Untuk Memount**: "titik sinyal terakhir" sebagai data lapangan; Trends diadaptasi untuk frekuensi rendah.

### 3.14 Google Maps
- Ask Maps (Gemini) bergulir di AS dan India (Maret 2026, cuplikan) [S57]; AI Mode Canvas untuk itinerary di
  AS (17 November 2025); Flight Deals mencakup Indonesia [S58]. INFERENSI: di Indonesia, Maps adalah navigasi
  darat ke basecamp. Memount cukup menautkan titik basecamp ke Maps.

---

## 4. Pola lintas aplikasi

### 4.1 Workspace perjalanan (untuk tab Trips)

**FAKTA.**
- TripIt: teruskan email konfirmasi ke plans@tripit.com atau Inbox Sync, itinerary tersusun otomatis;
  bagikan lewat teks, email, aplikasi; sinkron kalender [S59]. Pro: alert real time [S60].
- Wanderlog: itinerary dan peta dalam satu tampilan; kolaborasi real time; checklist (packing, to-do);
  anggaran dan bagi biaya; impor reservasi dari email; luring; Pro: optimasi rute dan asisten AI [S61].
- Airbnb: Trips menjadi itinerary visual dari check-in sampai check-out (2025, cuplikan) [S63]; 20 Mei 2026:
  peta di Trips menampilkan reservasi, tempat sekitar, dan waktu tempuh, plus itinerary bersama [S62].
- Google: Canvas menyusun itinerary yang bisa disunting dan dibagikan (AS) [S58].
- Polarsteps (9 Juni 2026): pilihan menyembunyikan lokasi langsung atau membagikan rute hanya sampai langkah
  terakhir yang direkam [S64].

**INFERENSI: enam pola inti dan bentuknya di Memount (Trips = Fit + Plan + Ready).** (1) Satu wadah: halaman
trip tunggal dari kecocokan sampai pasca (T1). (2) Waktu sebagai sumbu: linimasa H-30 buka booking, H-2 tutup,
H-7, H-1, hari-H (H1, T1). (3) Peta rencana: basecamp, pos, camp, batas status terbatas. (4) Berbagi hanya-baca
ke orang tanpa akun: rencana keselamatan lewat WhatsApp (T5). (5) Peringatan saat berubah: status, jendela
booking, level gunung api (H2). (6) Impor resmi: tautan ke AKTN atau kanal balai, kode booking dicatat sendiri (T3).

### 4.2 Progres tanpa kompetisi

| Pola | Contoh | Pembanding | Sikap Memount |
|---|---|---|---|
| Tren jangka panjang | Apple Trends 90 vs 365 hari [S52] | Diri sendiri | Ambil, ubah ke per musim atau per tahun |
| Mode banding periode | Strava progress chart [S15][S21] | Diri sendiri | Ambil sebagai "pendakian ini vs sebelumnya" |
| Rekor pribadi dan milestone | Apple awards [S52] | Diri sendiri | Ambil, tanpa hadiah untuk perilaku berisiko |
| Koleksi pribadi | PeakVisor peakbagging [S34] | Diri sendiri | Ambil sebagai tangga grade pribadi |
| Poster atau rekap | PeakVisor Track Poster [S35], Strava Sticker Stats [S13] | Diri sendiri | P2, kartu statis |
| Streak | Apple awards streak [S52], Duolingo | Diri sendiri, tetapi memaksa frekuensi | Tolak untuk mendaki |
| Segmen dan Local Legends | Strava [S19] | Orang lain | Tolak (R-015) |
| Leaderboard klub | Strava Clubs [S13] | Orang lain | Tolak |
| Level dan leaderboard | Garmin [S44] | Orang lain | Tolak |
| Kompetisi 7 hari | Apple [S53] | Orang lain | Tolak |
| Liga | Duolingo; keluar dari liga dengan menjadikan profil privat [S65] | Orang lain | Tolak |
| Kompetisi bulanan | PeakVisor [S34] | Orang lain | Tolak |

INFERENSI: pembanding diri yang cocok untuk pendaki berbeda dengan pelari. Pendaki mendaki jarang, jadi jendela
90 hari sering kosong. Satuan yang bermakna adalah pendakian (dibanding pendakian Anda sebelumnya) dan grade
jalur (tangga I-V). `CompletionState` Memount sudah membedakan COMPLETED, PARTIAL, ABANDONED; berbalik sebelum
puncak harus tampil sebagai keputusan yang sah, bukan kegagalan.

### 4.3 Kepercayaan dan komunitas

| Mekanisme | Siapa | Bentuk | Status di Memount |
|---|---|---|---|
| Pemasok resmi memasang status langsung | AllTrails Public Lands [S6], Outdooractive [S32] | Akun pengelola, alert di halaman jalur | Status resmi diisi admin; belum ada akun pengelola kawasan |
| Rute otomatis tertutup bila bersinggungan area tertutup | Outdooractive [S32] | Aturan spasial | Kaskade gunung, jalur, segmen sudah ada |
| Pengingat verifikasi kepada pemasok | Outdooractive [S32] | Pengingat otomatis | `data:freshness-check` harian ada; pengingat ke pemasok belum |
| Kondisi komunitas kedaluwarsa | Outdooractive 14 hari [S31] | Sembunyi otomatis, arsip bisa dilihat | Belum ada kedaluwarsa laporan |
| Tag kondisi pada ulasan | AllTrails [S9] | Tag terstruktur | `ConditionTag` 11 nilai; belum ada sinyal, rintangan, longsor, sampah, asap |
| Komentar tertambat lokasi + saring topik | FarOut [S37] | Per waypoint, filter AI | Laporan per segmen ada; per pos belum; filter topik belum |
| Lapor data usang | FarOut waypoint report [S37] | Antrean tim | Belum ada |
| Validasi kontribusi | AllTrails moderator [S10], Komoot tips [S23] | Moderasi | Antrean moderasi ada |
| Asal objek | CalTopo Object Details [S39] | Panel asal | Provenans ada di data; perlu tampil konsisten |
| Ringkasan AI | AllTrails Trail Conditions [S1], FarOut kategorisasi [S37] | Gabungan sinyal | Belum; hanya boleh dengan label dan di bawah resmi (R-011) |

---

## 5. Konteks dan celah pasar Indonesia

### 5.1 Kanal izin dan pemesanan

| Kawasan | Kanal resmi | Fakta kunci | Sumber |
|---|---|---|---|
| Rinjani (TNGR) | eRinjani (Android; iOS masuk agenda 2026) | Booking musim 2026 dibuka 6 Maret, jalur fisik dibuka 1 April 2026, enam jalur (Sembalun, Senaru, Torean, Aik Berik, Timbanuh, Tetebatu), semua transaksi digital. 1 September 2026 dibuka terbatas; booking eRinjani dihentikan sementara, booking yang sudah dibayar tetap dilayani | [S75][S74] |
| Rinjani, SOP | Balai TNGR, Kemenhut | Grade IV; bukti pengalaman lewat foto atau sertifikat, dibuktikan mandiri; tes kesehatan H-1; asuransi premium mulai 1 Oktober 2025; pemandu bersertifikat atau pendamping berpengalaman, rasio 1:5 lalu 1:4 mulai Januari 2026; tektok wajib surat rekomendasi asosiasi (Juni 2026) | [S71][S72][S76] |
| Gede Pangrango (TNGGP) | booking.gedepangrango.org | Kuota harian Cibodas 300, Gunung Putri 200, Selabintana 100; dibuka lagi 13 April 2026; ditutup 12-18 Agustus 2026 karena kebakaran (cuplikan); termasuk yang ditutup sejak 1 September 2026 | [S84][S66] |
| Semeru (TNBTS) | bromotenggersemeru.id | Booking H-30 sampai H-2; kuota 200 per hari, 2 hari 1 malam; dibuka 24 April 2026 hanya sampai Ranu Kumbolo; KTP asli dan surat keterangan sehat diperiksa di Ranupani (cuplikan); ditutup lagi sejak 1 September 2026 | [S80][S66] |
| Merbabu (TNGMb) | booking.tngunungmerbabu.org + aplikasi Android | Akun dan kode pendaki, OTP lewat email dan WhatsApp (cuplikan); termasuk destinasi AKTN saat peluncuran; dibuka terbatas sejak 1 September 2026 | [S85][S68][S66] |
| Nasional | Ayo Ke Taman Nasional (AKTN) | Diluncurkan 4 Juni 2026 di INDOFEST; satu pintu untuk TN, TWA, SM; Android, web, kios; QRIS dan transfer; tahap lanjut: pemandu, reschedule, refund, gamifikasi, App Store | [S68] |
| Prau | Tidak ditemukan | Kanal booking resmi tidak dapat diverifikasi: BELUM TERVERIFIKASI | - |

Kuota harian Rinjani dan syarat minimal anggota rombongan yang beredar di cuplikan tidak ditemukan di artikel
sumbernya: BELUM TERVERIFIKASI.

### 5.2 Kejadian dan kebijakan yang membentuk kebutuhan

- **Grading jalur (Kemenhut, 15 Agustus 2025)**: klasifikasi risiko per jalur, Grade I (sangat mudah) sampai
  V (sangat berat); 81 jalur: I=4, II=26, III=32, IV=16, V=3; indikator antara lain panjang jalur, kecuraman,
  morfologi, elevasi, paparan belerang, fenomena alam; tujuannya membantu pendaki memilih "berdasarkan
  kapasitas dirinya" [S69][S70]. Pejabat menyarankan urutan Grade I, lalu III, baru IV [S71].
- **Penutupan berbasis risiko (SE.4/KSDAE/SKSDAE/KSA.02.02/B/8/2026, 31 Agustus 2026)**: tutup sementara
  Semeru, Kerinci Seblat, Ciremai, Tambora, Manusela, Bukit Baka Bukit Raya, Gede Pangrango, Leuser, Lorentz
  (jalur tertentu); buka terbatas Rinjani, Merbabu, Argopuro, Halimun Salak; parameter: kerentanan kawasan,
  bahan bakar kering, konsekuensi ekologis, kapasitas pengendalian; durasi dievaluasi berkala [S66][S67].
- **Gunung api**: Marapi (3 Desember 2023) meletus pada Level II dengan rekomendasi zona 3 km; 23 pendaki
  meninggal; sebagian pendaki terdaftar lewat booking daring (cuplikan) [S83]. Semeru (19 November 2025)
  naik dari Level II ke III pukul 16.00 dan ke IV pukul 17.00 WIB; lebih dari 170 pendaki, porter, dan pemandu
  dievakuasi dari Ranu Kumbolo (cuplikan) [S81][S82]. Dua kejadian ini adalah bukti lapangan untuk R-012.
- **Pelacakan resmi**: RFID di Merbabu (Selo) dan uji coba di Semeru (Juli 2025) [S86]; untuk Rinjani, Kemenhut
  memilih aplikasi ponsel "Beacon" ketimbang RFID [S73]; "Rinjani Beacon" dan command center di agenda 2026 [S74].
- **Tektok**: dilarang di Dempo (akhir Juni 2026, sanksi blacklist) dan Arjuno-Welirang (2026); di Lawu via
  Cemoro Sewu registrasi tektok 01.00-09.00 WIB; di Rinjani butuh surat rekomendasi (semua cuplikan kecuali
  Rinjani) [S76][S77][S78][S79].
- **Open trip dan jalur ilegal**: Kemenhut menegur penyelenggara open trip yang "membooking" lahan camp
  (Juni 2025) [S95]; penyelenggara open trip di Lawu kena blacklist dua tahun [S96]; TNBTS mem-blacklist
  8 pendaki ilegal (September 2026) [S97] (ketiganya cuplikan).

### 5.3 Sumber data resmi yang relevan

- **BMKG**: prakiraan 3 hari, per 3 jam, tingkat desa atau kelurahan (adm4), diperbarui dua kali sehari,
  batas 60 permintaan per menit per IP, wajib mencantumkan BMKG sebagai sumber di aplikasi [S87]. Implikasi:
  area rujukan adalah desa, bukan puncak. Tampilkan "Prakiraan untuk Desa X", bukan "cuaca puncak".
- **PVMBG lewat MAGMA Indonesia**: status gunung api, laporan aktivitas (VAR), peta Kawasan Rawan Bencana,
  VONA; web dan Android [S88]. Ini otoritas terpisah dari balai taman nasional.
- **Basarnas**: panggilan darurat 115, 24 jam [S89].
- **Kemenhut**: grading jalur, SE status pendakian, AKTN [S66][S68][S69].

### 5.4 Pemain lokal

| Pemain | Yang dilakukan (FAKTA) | Catatan |
|---|---|---|
| muncak.id (Unsoed) | 168 jalur lintas negara, segmentasi jalur dan estimasi waktu, cuaca sepanjang jalur, prediksi kalori, kamera langsung, Bekal (beta, perencana logistik dengan katalog produk), rekomendasi TOPSIS dengan login, MuncakBot [S91] | Pesaing terdekat untuk "rekomendasi"; cara hasilnya ditampilkan tidak terlihat tanpa login. Ada skripsi Unsoed 2026 yang mengevaluasi UX-nya; isinya tidak berhasil saya baca |
| MDPL | Peta luring 100+ gunung, GPS tanpa internet, data terkurasi, langganan per trip atau tahunan; rencana pelacakan acara lari (20 Agustus 2026) [S90] | Mengisi celah luring yang belum diisi Memount |
| Treki AI | Asisten AI "Rimba", cek grade jalur, blog grading; PT Rejeki Gak Kemana [S92] | Rincian produk tidak tampil di beranda |
| GunungKita | Keselamatan real time, logistik jasa, administrasi basecamp, akses untuk tim SAR (Juli 2026, cuplikan; halaman 404) [S93] | Status peluncuran tidak jelas |
| Gearberg | Platform booking pendakian daring dan info jalur [S94] | Cakupan gunung tidak dinyatakan |
| Tiket Pendakian | Listing Play Store (cuplikan; isi tidak terbaca) [S94] | Tidak terverifikasi |
| AKTN (Kemenhut) | E-ticketing resmi satu pintu [S68] | Bisa menambah informasi dan gamifikasi |

### 5.5 Kebiasaan komunitas

- **FAKTA**: informasi pendakian beredar lewat media sosial; fenomena "pendaki FOMO" dibahas MDMC
  Muhammadiyah Jateng dan media lokal (2025-2026) [S98]. Open trip dan koordinasi rombongan berjalan lewat grup
  WhatsApp (cuplikan TikTok; bukti lemah). Merbabu mengirim OTP lewat WhatsApp [S85].
- **INFERENSI**: WhatsApp adalah kanal berbagi utama, jadi tautan hanya-baca yang rapi di pratinjau WhatsApp
  lebih berguna daripada fitur sosial di dalam aplikasi. Karena aturan berbeda per gunung dan berubah per
  musim, pendaki butuh satu tempat yang menyatakan "apa yang berlaku untuk jalur ini, pada tanggal ini,
  menurut siapa".

### 5.6 Celah pasar

Semua butir di bawah INFERENSI dari pencarian saya, bukan survei pasar lengkap.

| Celah | Siapa yang sudah menyentuh | Kenapa masih terbuka |
|---|---|---|
| Status resmi per jalur (buka, terbatas sampai titik X, tutup, belum diketahui) dengan sumber, waktu, dan peringatan perubahan | Portal berita (Mounture), balai masing-masing | Tersebar per kawasan dan berupa artikel, bukan data terstruktur |
| Syarat resmi per jalur dan tanggal menjadi daftar persiapan pribadi | Artikel panduan, AKTN (tiket) | Tidak ada yang menggabungkan jendela booking, dokumen, asuransi, pemandu, aturan tektok |
| Kecocokan pribadi yang jujur (label, tanpa angka, UNKNOWN bukan LOW) berdampingan dengan grade resmi | muncak.id (TOPSIS), Treki (AI) | Presentasi dan penjelasan per faktor tidak terlihat publik |
| Buku pendakian pribadi untuk bukti pengalaman | Tidak ditemukan | Rinjani meminta bukti mandiri; belum ada alat pencatat yang jujur soal asal data |
| Laporan kondisi yang kedaluwarsa dan terpisah dari status resmi | Tidak ditemukan di pemain lokal | Pola Outdooractive belum ada di Indonesia |
| Paket lapangan luring yang jujur soal waktu unduh | MDPL (peta luring berbayar) | Memount belum punya; MDPL tidak menyatakan status resmi |

**Ancaman.** AKTN bisa menambah informasi jalur dan gamifikasi dengan status resmi sebagai keunggulan [S68].
MDPL, muncak.id, dan Treki bergerak cepat [S90][S91][S92]. Aplikasi global menambah cakupan Indonesia lewat
UGC. Pelacakan resmi Rinjani Beacon bisa membuat rekam jejak Memount terasa ganda [S74].

---

## 6. Rekomendasi untuk Memount

### 6.0 Ringkasan, urutan kerja, dan pemetaan rute yang ada

**Pemetaan IA pemilik ke rute yang sudah ada (bukti internal `routes/web.php`).**

| Tab pemilik | Rute yang sudah ada | Catatan |
|---|---|---|
| Home | `dashboard`, sebagian `news` (`/kabar`) | Dashboard sudah memuat "status jalur berubah" dan "kesiapan belum diperiksa" |
| Explore | `trails.index`, `trails.saved`, `trails.show`, `mountains.follow` | Peta sebaran sudah ada |
| Trips | `trips.*`, `trails.compare`, `recommendations.*`, `goals.*` | Persiapan dan kesiapan masih halaman terpisah |
| Track | `trips.hike`, `trips.hike.position`, `hike.track` | Terikat trip; tidak ada tampilan tanpa trip |
| Progress | `progress`, `history`, `history.summary` | |
| Profile | `profile`, `onboarding`, `profile.preference` | Belum ada perlengkapan dan privasi berbagi |
| Community | `reports.*`, `reports.thanks` | Lintas-halaman sesuai IA pemilik |

**Gelombang P0, diurutkan menurut ketergantungan.**
1. Hidup menurut waktu: H1, T1, lalu H2 (H2 butuh infrastruktur notifikasi).
2. Jujur soal status dan aturan: E1, T2, T4, C1.
3. Route Fit yang terasa: T7.
4. Berguna di lapangan: K2, K7 bersama P1, lalu K1 (K1 butuh keputusan arsitektur).

| ID | Permukaan | Rekomendasi | Prioritas | Skema baru | Butuh persetujuan pemilik |
|---|---|---|---|---|---|
| H1 | Home | Kartu perjalanan berikutnya berfase | P0 | Tidak | Tidak |
| H2 | Home | Peringatan status dan pengingat jendela izin | P0 | Ya | Ya (privasi, kanal notifikasi) |
| H3 | Home | "Untuk Anda" hanya jalur yang bisa didaki | P1 | Tidak | Tidak |
| H4 | Home | Satu baris progres pribadi | P1 | Tidak | Tidak |
| E1 | Explore | Lapisan status resmi + level gunung api | P0 | Ya (level gunung api) | Ya (sumber otoritas baru) |
| E2 | Explore | Grade resmi Kemenhut per jalur | P1 | Ya | Ya (Route Fit, gerbang publikasi) |
| E3 | Explore | Peta layar penuh + lembar bawah | P1 | Tidak | Ya (pola navigasi di DESIGN.md) |
| E4 | Explore | Saringan syarat praktis | P1 | Ya (aturan tektok) | Tidak |
| E5 | Explore | Cari menurut titik yang dilalui | P2 | Tidak | Tidak |
| E6 | Explore | Halaman gunung sebagai hub via | P1 | Tidak | Tidak |
| T1 | Trips | Ruang kerja trip berbasis linimasa | P0 | Tidak | Tidak |
| T2 | Trips | Blok persyaratan resmi untuk tanggal trip | P0 | Ya (kecil) | Tidak |
| T3 | Trips | Catatan pemesanan milik pengguna | P1 | Ya | Ya (data pribadi) |
| T4 | Trips | Kesiapan per faktor pendorong, tanpa angka | P0 | Tidak | Tidak |
| T5 | Trips | Rencana keselamatan hanya-baca untuk keluarga | P1 | Ya | Ya (privasi lokasi) |
| T6 | Trips | Estimasi waktu pribadi antar-pos (rentang) | P1 | Mungkin | Ya (Route Fit) |
| T7 | Trips | "Apa yang dapat mengubah label" | P0 | Tidak | Ya (Route Fit) |
| T8 | Trips | Banding + syarat, status, waktu pribadi | P1 | Tidak | Tidak |
| T9 | Trips | Rombongan menyunting bawaan bersama | P2 | Ya | Ya (privasi) |
| K1 | Track | Paket lapangan luring per trip | P0 | Tidak | Ya (arsitektur, sumber ubin) |
| K2 | Track | Panel tanjakan berikutnya + elevasi langsung | P0 | Tidak | Tidak |
| K3 | Track | Peringatan keluar jalur luring | P1 | Tidak | Tidak |
| K4 | Track | Batas waktu balik | P1 | Ya (kecil) | Tidak |
| K5 | Track | Titik sinyal terakhir dan titik sinyal laporan | P1 | Tidak | Ya (lokasi) |
| K6 | Track | Mode hemat daya | P1 | Tidak | Tidak |
| K7 | Track | Alur Selesai: ringkasan, laporan per pos, progres | P0 | Ya (kecil) | Tidak |
| P1 | Progress | Ringkasan pasca-pendakian dibanding diri | P0 | Tidak | Tidak |
| P2 | Progress | Tangga grade pribadi | P1 | Bergantung E2 | Ya |
| P3 | Progress | Buku pendakian yang bisa diekspor | P1 | Tidak | Ya (privasi) |
| P4 | Progress | Tren per musim atau tahun | P1 | Tidak | Tidak |
| P5 | Progress | Rekap tahunan statis | P2 | Tidak | Tidak |
| P6 | Progress | Target pribadi terhubung rekomendasi | P1 | Tidak | Tidak |
| R1 | Profile | Perlengkapan saya | P1 | Ya | Tidak |
| R2 | Profile | Pengalaman diperbarui dari riwayat | P1 | Tidak | Tidak |
| R3 | Profile | Privasi berbagi dan retensi jejak | P1 | Mungkin | Ya |
| R4 | Profile | Kelola data luring | P1 | Tidak | Tidak |
| C1 | Community | Laporan tertambat pos + masa berlaku | P0 | Ya (kecil) | Ya (enum tag) |
| C2 | Community | Saring laporan per topik | P1 | Tidak | Tidak |
| C3 | Community | Laporkan ketidaksesuaian data | P1 | Ya | Tidak |
| C4 | Community | Portal pengelola kawasan | P1 | Mungkin | Ya (peran, kemitraan) |
| C5 | Community | Ringkasan AI berlabel | P2 | Tidak | Ya (R-011) |
| C6 | Community | Kebijakan anti-jalur-ilegal | P1 | Tidak | Tidak |

### 6.1 Home

**H1. Kartu "Perjalanan berikutnya" yang berubah menurut fase** · P0 · Skema baru: tidak
- **Apa**: satu kartu fokus yang isinya berganti menurut fase trip terdekat. Contoh untuk trip 11 November:
  "Pemesanan izin dibuka 12 Okt (H-30)" → "Pesan di [kanal resmi]; tutup 9 Nov (H-2)" → "3 butir persiapan
  kritis belum siap" (H-7) → "Cek kesiapan" (H-1) → "Mulai mendaki" (hari-H, ke Track) → "Catat dan laporkan
  kondisi" (pasca). Satu aksi utama per fase, plus status resmi dan cuaca ringkas yang menyebut sumber dan
  waktunya. Daftar tautan "Lanjutkan ke" di Dashboard sekarang dihapus karena menduplikasi navigasi.
- **Mengapa**: Airbnb dan TripIt menjadikan trip sebagai itinerary yang hidup [S62][S63][S59]; Apple
  Readiness dirancang untuk sekali lirik [S49]; jendela Semeru H-30 sampai H-2 membuat waktu menentukan
  [S80]. Datanya sudah ada: `trip_plans`, `permit_requirements` (`booking_opens_days_before`,
  `booking_closes_days_before`), `readiness_checks`, `official_statuses`, `weather_snapshots`.
- **Aturan**: status adalah status operasional (R-012); cuaca menyebut BMKG, area rujukan, dan waktu (R-013).

**H2. Peringatan perubahan status dan pengingat jendela izin** · P0 · Skema baru: ya (tabel notifikasi
Laravel standar + preferensi kanal)
- **Apa**: notifikasi dalam aplikasi (email opsional) ketika status resmi jalur di trip, tersimpan, atau
  gunung yang diikuti berubah; ketika level gunung api naik; ketika jendela booking dibuka atau tinggal dua
  hari; H-1 untuk cek kesiapan. Setiap pesan memuat sumber, waktu terbit, dan tautan ke rincian.
- **Mengapa**: 9 taman nasional ditutup dalam satu surat edaran [S66]; Semeru naik dua level dalam satu jam
  [S82]; AllTrails dan TripIt menjadikan alert nilai inti [S6][S60]. Bukti internal: PRD 2.2 menjanjikan
  "Pengingat Perjalanan", tetapi tidak ada `app/Notifications`; penjadwal hanya menjalankan `weather:refresh`
  dan `data:freshness-check`.
- **Aturan**: tanpa kata "aman"; pengaturan email dan notifikasi tunduk pada `docs/KEBIJAKAN-DATA-PRIBADI.md`.

**H3. "Untuk Anda" yang hanya menampilkan jalur yang sedang bisa didaki** · P1 · Skema baru: tidak
- **Apa**: dua sampai tiga jalur dengan label, satu alasan utama, dan status. Bila profil belum lengkap:
  "Lengkapi dua hal ini agar penilaian lebih tepat", bukan menurunkan label.
- **Mengapa**: daftar dekat lokasi di Explore AllTrails [S8]; Route Discovery Strava [S13]. **Aturan**: R-004,
  R-005, R-006, R-008.

**H4. Satu baris progres pribadi** · P1 · Skema baru: tidak
- **Apa**: mis. "Pendakian ke-4. Tanjakan terpanjang Anda sejauh ini: Merbabu via Selo." Tautan ke Progress.
- **Mengapa**: awards dan Trends Apple berbasis diri sendiri [S52]. **Aturan**: R-015.

### 6.2 Explore

**E1. Lapisan status resmi dan level gunung api** · P0 · Skema baru: ya untuk level gunung api
- **Apa**: status BUKA, BUKA TERBATAS, TUTUP, BELUM DIKETAHUI di peta dan daftar; untuk terbatas, batasnya
  tertulis ("hanya sampai Ranu Kumbolo") dan segmen di luar batas digambar tertutup. Level gunung api PVMBG
  (Normal, Waspada, Siaga, Awas) tampil sebagai lapisan otoritas terpisah dengan rekomendasi radiusnya, tidak
  diterjemahkan menjadi BUKA atau TUTUP.
- **Mengapa**: Outdooractive memakai warna notice dan closure serta menutup rute otomatis [S32]; Semeru
  terbatas sampai Ranu Kumbolo [S80]; Marapi meletus pada Level II saat pendaki terdaftar [S83]. Bukti internal:
  `OfficialStatusValue` sudah punya `RESTRICTED`; `StatusScope` sudah punya `SEGMENT` dan `AREA`, jadi batas
  terbatas tidak perlu skema baru. Level gunung api belum punya tempat.
- **Aturan**: R-009, R-012. Menambah PVMBG sebagai sumber otoritas adalah perubahan semantik penyedia
  eksternal (R-021).

**E2. Grade resmi Kemenhut sebagai atribut OFFICIAL per jalur** · P1 · Skema baru: ya
- **Apa**: "Grade III · Kemenhut, Agustus 2025" di kartu dan detail jalur, saringan grade, dan BELUM DIKETAHUI
  bila jalur belum dinilai. Grade menjadi masukan Route Fit hanya setelah disetujui.
- **Mengapa**: grading bersifat per jalur dan dimaksudkan untuk memilih sesuai kapasitas diri [S69]; onX
  menampilkan ATES apa adanya [S30].
- **Aturan**: R-001, R-002, R-006. Memakai grade di Route Fit atau menjadikannya data kritis gerbang publikasi
  (R-022) butuh persetujuan pemilik.

**E3. Peta layar penuh dengan lembar bawah** · P1 · Skema baru: tidak
- **Apa**: di ponsel, peta menjadi dasar dengan lembar bawah tiga tinggi (intip: jumlah hasil dan saringan
  aktif; setengah: daftar; penuh: daftar penuh), klaster saat diperkecil, tombol "Cari di area ini". Daftar
  tetap tersedia sebagai alternatif yang bisa dipakai dengan papan ketik dan pembaca layar.
- **Mengapa**: Strava Maps membuka dengan peta [S18]; AllTrails Explore memadukan peta dan daftar [S8].
- **Aturan**: DESIGN.md memutuskan sakelar Daftar/Peta di ponsel; mengubahnya butuh persetujuan.

**E4. Saringan syarat praktis** · P1 · Skema baru: ya untuk aturan tektok
- **Apa**: "Tektok diizinkan", "Wajib pemandu", "Booking terbuka untuk tanggal saya", "Grade", "Status".
- **Mengapa**: aturan tektok berbeda per gunung [S76][S77][S78][S79]; Rinjani mensyaratkan pemandu
  bersertifikat atau pendamping berpengalaman [S71]. `guide_required` dan jendela booking sudah ada di
  `permit_requirements`; aturan tektok belum.

**E5. Cari menurut titik yang dilalui** · P2 · Skema baru: tidak
- **Apa**: "Jalur yang melewati Ranu Kumbolo". **Mengapa**: Wikiloc mencari menurut area yang dilalui [S36].

**E6. Halaman gunung sebagai hub via** · P1 · Skema baru: tidak
- **Apa**: tiap via tampil berdampingan dengan label kecocokan, grade, status, kuota harian resmi, tanpa
  pemenang.
- **Mengapa**: grade dan kuota berbeda per jalur (Gede: Cibodas 300, Gunung Putri 200, Selabintana 100)
  [S84][S69]. **Aturan**: R-001, R-016. `daily_quota` sudah ada.

### 6.3 Trips (Fit + Plan + Ready)

**T1. Ruang kerja trip berbasis linimasa** · P0 · Skema baru: tidak
- **Apa**: satu halaman trip dengan linimasa Kecocokan → Izin → Persiapan → Kesiapan → Hari-H → Pasca.
  Setiap tahap punya status dan satu langkah berikutnya; peta kecil menampilkan basecamp, pos, dan rencana
  camp. Halaman Persiapan dan Kesiapan yang terpisah menjadi bagian halaman ini.
- **Mengapa**: Wanderlog menyatukan itinerary dan peta [S61]; Airbnb dan TripIt memusatkan semua pada objek
  trip [S62][S59]. PRD 2.2 sudah meminta "langkah berurutan".

**T2. Blok "Persyaratan resmi untuk tanggal ini"** · P0 · Skema baru: ya, kecil
- **Apa**: dokumen (KTP, surat sehat), asuransi, pemandu, aturan tektok, jendela booking dengan tanggal nyata,
  tautan ke kanal resmi (AKTN, eRinjani, booking balai); setiap butir menyebut sumber dan tanggal verifikasi.
  Butir resmi tampil terpisah dari butir perlengkapan.
- **Mengapa**: syarat Semeru, Rinjani, Merbabu, dan AKTN berbeda dan berubah [S80][S72][S85][S68].
  `permit_requirements` belum menampung butir dokumen dan asuransi secara terstruktur.
- **Aturan**: data OFFICIAL; tanpa data berarti BELUM DIKETAHUI (R-007). Memount tidak menjual tiket.

**T3. Catatan pemesanan milik pengguna** · P1 · Skema baru: ya
- **Apa**: "Sudah memesan? Simpan kode booking dan tanggalnya." Catatan pribadi, bukan verifikasi.
  **Aturan**: kode booking adalah data pribadi; ikut kebijakan data.

**T4. Kesiapan per faktor pendorong, tanpa angka** · P0 · Skema baru: tidak
- **Apa**: faktor kecocokan jalur, persiapan kritis, status dan cuaca, persyaratan resmi, rencana keselamatan;
  masing-masing "Siap", "Perlu tindakan", atau "Belum diketahui" dengan satu tindakan dan tautannya. Label
  keseluruhan tetap `ReadinessState` yang sudah ada.
- **Mengapa**: Apple menyorot faktor pendorong yang bisa diketuk [S48][S49]; Garmin memecah kesiapan menjadi
  faktor [S42]. **Aturan**: R-005, R-014. PRD masih memakai frasa "skor kesiapan"; lihat 6.8.

**T5. Rencana keselamatan hanya-baca untuk keluarga** · P1 · Skema baru: ya
- **Apa**: tautan yang rapi di pratinjau WhatsApp: jalur, anggota rombongan (nama panggilan), jadwal, batas
  waktu kembali, basecamp dan kontak pengelola, Basarnas 115, dan "bila belum ada kabar sampai pukul X,
  lakukan 1, 2, 3". Tanpa pelacakan langsung; halaman menyatakan bahwa sinyal di gunung umumnya tidak ada.
  Tautan kedaluwarsa setelah trip selesai.
- **Mengapa**: AllTrails Live Share memuat rute dan jam rencana serta kedaluwarsa [S7]; Strava Beacon tanpa
  akun [S17]; Polarsteps memberi pilihan privasi lokasi [S64]; Basarnas 115 [S89].
- **Aturan**: privasi lokasi di `docs/KEBIJAKAN-DATA-PRIBADI.md` adalah canonical.

**T6. Estimasi waktu pribadi antar-pos** · P1 · Skema baru: mungkin (ringkasan pace per segmen)
- **Apa**: rentang waktu antar-pos dari riwayat Anda pada tanjakan serupa; tanpa riwayat, rentang umum dengan
  label "perkiraan umum, belum dari riwayat Anda".
- **Mengapa**: Komoot [S22] dan PeakVisor [S34] mempersonalisasi estimasi, bukan rute. **Aturan**: rentang,
  bukan angka tunggal (R-005); tanpa riwayat tidak dikarang (R-007).

**T7. "Apa yang dapat mengubah label"** · P0 · Skema baru: tidak
- **Apa**: untuk PERLU PERSIAPAN dan KURANG COCOK, sebutkan faktor yang paling menentukan dan langkah realistis.
  Contoh ilustrasi: "Faktor penentu: total naik jalur ini jauh di atas tanjakan terbesar yang pernah Anda
  selesaikan. Yang bisa mengubahnya: menyelesaikan jalur dengan tanjakan serupa, atau via lain di gunung ini."
  Tanpa janji hasil.
- **Mengapa**: Apple menyorot faktor pendorong [S48]; pejabat Kemenhut menyarankan tangga Grade I, III, IV
  [S71]. INFERENSI: ini membuat Route Fit terasa sebagai pendamping, bukan vonis.
- **Aturan**: R-003, R-004, R-005.

**T8. Banding dengan syarat, status, dan waktu pribadi** · P1 · Skema baru: tidak
- **Apa**: tambah baris syarat resmi, status per tanggal rencana, dan rentang waktu pribadi; bagikan hanya-baca.
- **Mengapa**: daftar kolaboratif AllTrails (15 Februari 2024) [S12]; peta bersama CalTopo [S39]. **Aturan**:
  R-016.

**T9. Rombongan menyunting bawaan bersama** · P2 · Skema baru: ya
- **Apa**: undang anggota rombongan untuk melihat dan membagi bawaan kelompok. **Mengapa**: Wanderlog [S61],
  itinerary bersama Airbnb [S62]. **Aturan**: tanpa grafik sosial dan tanpa kompetisi.

### 6.4 Track

**K1. Paket lapangan luring per trip** · P0 · Skema baru: tidak (penyimpanan di perangkat)
- **Apa**: unduhan eksplisit per trip berisi geometri, pos, profil elevasi, info darurat (Basarnas 115,
  pengelola, basecamp), status dan cuaca bercap waktu ("Status saat diunduh: BUKA TERBATAS, Balai TNGR,
  1 Sep 2026 09.00 WITA; tidak dapat diperiksa tanpa sinyal"), dan ubin peta koridor jalur.
- **Mengapa**: semua pembanding utama menyediakan luring [S3][S26][S28][S36][S38][S90]. Bukti internal: service
  worker menolak menyajikan isi saat luring agar status basi tidak tampil. Paket ini menjaga prinsip itu
  dengan cap waktu yang tegas.
- **Aturan**: OpenTopoMap melarang prefetch dan unduh massal [S99], jadi dibutuhkan sumber ubin yang
  mengizinkan luring (mis. ubin yang di-host sendiri). Ini keputusan arsitektur (R-021) dan pengecualian
  kebijakan service worker. R-013 tetap berlaku.

**K2. Panel "Tanjakan berikutnya" dan elevasi langsung** · P0 · Skema baru: tidak
- **Apa**: jarak ke pos berikutnya, naik yang tersisa, kecuraman rata-rata, rentang waktu pribadi, profil
  elevasi dengan posisi Anda dan warna kecuraman.
- **Mengapa**: ClimbPro [S43], Live Elevation Strava [S13], Lookahead FarOut [S38], warna kecuraman Gaia [S28].
  `elevation_profile` dan `checkpoints` sudah ada.

**K3. Peringatan keluar jalur yang bekerja luring** · P1 · Skema baru: tidak
- **Apa**: getar dan teks saat menjauh dari geometri jalur, dengan arah kembali; ambang bisa disetel;
  keterangan akurasi GPS.
- **Mengapa**: Strava (Mei 2026) [S14], AllTrails [S3], Wikiloc [S36], Backtrack Apple [S51]. **Aturan**:
  bantuan, bukan jaminan. Bergantung K1.

**K4. Batas waktu balik** · P1 · Skema baru: ya, kecil (kolom di `trip_plans`)
- **Apa**: jam batas balik ditetapkan saat merencanakan; saat tercapai, pengingat di perangkat beserta posisi
  terhadap pos dan rentang waktu tersisa ke puncak.
- **Mengapa**: pengelola menetapkan jendela waktu (tektok Lawu 01.00-09.00 WIB [S79]; tektok Rinjani wajib
  turun di hari yang sama [S76]); Live Share AllTrails memuat jam mulai dan selesai [S7].
- **Aturan**: berbalik adalah keputusan sah; `CompletionState` PARTIAL tidak diperlakukan sebagai gagal.

**K5. Titik sinyal terakhir dan titik sinyal laporan** · P1 · Skema baru: tidak
- **Apa**: rekam lokasi terakhir yang tersambung; tampilkan titik sinyal dari laporan komunitas dengan tanggal.
- **Mengapa**: waypoint sinyal seluler terakhir Apple [S51]; kategori "konektivitas" FarOut [S37].
  **Aturan**: lokasi pribadi mengikuti kebijakan data.

**K6. Mode hemat daya** · P1 · Skema baru: tidak
- **Apa**: tampilan kontras tinggi, tombol besar, tanpa animasi, pembaruan posisi jarang.
- **Mengapa**: navigasi layar kunci PeakVisor [S34]; mode gelap Komoot untuk hemat baterai [S26]; DESIGN.md
  MOTION 1.

**K7. Alur "Selesai": ringkasan, laporan per pos, progres** · P0 · Skema baru: ya, kecil (`checkpoint_id`
pada laporan)
- **Apa**: setelah Selesai, tampil ringkasan dibanding diri (P1), lalu laporan kondisi yang sudah terisi
  tanggal dan pos yang dilalui; cukup dua ketukan per pos (tag kondisi); lalu progres. Ini jalur
  "Progress → Pendakian → Bagikan laporan" dalam IA pemilik.
- **Mengapa**: corong di PRODUCT.md (HIKE → REPORT → PROGRESS → SHARE); tag kondisi AllTrails [S9]; komentar
  per waypoint FarOut [S37]. **Aturan**: moderasi tetap; R-010.

### 6.5 Progress

**P1. Ringkasan pasca-pendakian dibanding diri** · P0 · Skema baru: tidak
- **Apa**: "Yang baru untuk Anda": ketinggian tertinggi, tanjakan terpanjang, grade pertama, pace di tanjakan
  dibanding pendakian sebelumnya (rentang). Tanpa orang lain.
- **Mengapa**: awards rekor pribadi Apple [S52]; mode banding Strava [S15]. **Aturan**: R-015.

**P2. Tangga grade pribadi** · P1 · Skema baru: bergantung E2
- **Apa**: jalur yang pernah diselesaikan per grade I-V, dengan saran Kemenhut (mulai Grade I, lalu III, baru
  IV) sebagai informasi, bukan syarat Memount.
- **Mengapa**: [S69][S71]. **Aturan**: bukan izin atau sertifikat (R-014).

**P3. Buku pendakian yang bisa diekspor** · P1 · Skema baru: tidak
- **Apa**: daftar pendakian dengan tanggal, jalur, grade, dan penanda asal data (rekaman GPS atau isian
  manual). Label tegas: "Catatan pribadi yang Anda isi, bukan sertifikat resmi."
- **Mengapa**: Rinjani meminta bukti pengalaman yang dibuktikan mandiri [S72]. **Aturan**: privasi; tidak
  mengklaim keabsahan resmi.

**P4. Tren per musim atau tahun** · P1 · Skema baru: tidak
- **Apa**: jumlah pendakian, total naik, grade tertinggi per musim atau tahun.
- **Mengapa**: Apple Trends butuh 180 hari data [S52]; INFERENSI: jendela 90 hari sering kosong bagi pendaki.

**P5. Rekap tahunan statis** · P2 · Skema baru: tidak
- **Apa**: kartu yang bisa dibagikan, tanpa lokasi rumah atau titik awal presisi. **Mengapa**: Sticker Stats
  Strava [S13], Track Poster PeakVisor [S35]. **Aturan**: R-015, privasi.

**P6. Target pribadi terhubung ke rekomendasi** · P1 · Skema baru: tidak
- **Apa**: "Target: selesaikan Grade III sebelum Desember. Dua jalur cocok yang sedang buka." `hiking_goals`
  sudah ada. **Mengapa**: rencana latihan adaptif Strava (Mei 2026) [S14].

### 6.6 Profile

**R1. Perlengkapan saya** · P1 · Skema baru: ya
- **Apa**: inventaris perlengkapan yang otomatis mencentang butir persiapan yang cocok dan menandai kekurangan.
- **Mengapa**: checklist Wanderlog [S61]; Bekal muncak.id [S91]. **Aturan**: tanpa katalog merek atau afiliasi
  (tanpa monetisasi).

**R2. Pengalaman diperbarui dari riwayat** · P1 · Skema baru: tidak
- **Apa**: `user_experience` (jumlah pendakian, tanjakan tertinggi, durasi terpanjang) diperbarui dari
  pendakian selesai; pengguna tetap bisa mengoreksi. **Mengapa**: alur PRD langkah 16.

**R3. Privasi berbagi dan retensi jejak** · P1 · Skema baru: mungkin
- **Apa**: bawaan menyembunyikan awal dan akhir jejak di semua yang dibagikan; kontrol retensi; kontrol
  rencana keselamatan. **Mengapa**: map visibility Strava [S20]; Polarsteps [S64]. **Aturan**: kebijakan data
  canonical.

**R4. Kelola data luring** · P1 · Skema baru: tidak
- **Apa**: daftar paket lapangan, ukuran, tanggal unduh, hapus. **Mengapa**: menu unduhan Gaia [S28], File
  Manager FarOut [S38].

### 6.7 Community (lintas-halaman)

Penempatan mengikuti IA pemilik: laporan terbaru muncul di Explore dan di bagian Komunitas pada detail jalur;
pengiriman laporan lewat alur Selesai (K7).

**C1. Laporan tertambat pos dengan masa berlaku** · P0 · Skema baru: ya, kecil
- **Apa**: laporan melekat pada pos atau segmen; setiap tag punya masa berlaku usulan (mis. air dan keramaian
  pendek, longsor dan penanda jalur panjang; angka pastinya perlu divalidasi dengan pengelola dan pendaki);
  tampil "x hari lalu" memakai `FreshnessState`; otomatis diarsipkan; tetap terpisah secara visual dari status
  resmi. Tag baru yang diusulkan: sinyal, rintangan atau pohon tumbang, longsor, sampah, asap atau api.
- **Mengapa**: Outdooractive 14 hari [S31]; FarOut per waypoint [S37]; tag AllTrails [S9]. Bukti internal:
  `trail_condition_reports` punya `trail_segment_id` dan `hike_date`, belum punya `checkpoint_id` atau masa
  berlaku; `ConditionTag` belum punya tag di atas.
- **Aturan**: R-009, R-010. Menambah nilai enum adalah perubahan domain.

**C2. Saring laporan per topik** · P1 · Skema baru: tidak
- **Apa**: air, sinyal, camp, kondisi jalur, sampah; urut menurut kesegaran. **Mengapa**: FarOut [S37].

**C3. Laporkan ketidaksesuaian data** · P1 · Skema baru: ya
- **Apa**: "Pos ini salah letak", "Papan di lapangan berbeda" masuk antrean moderasi atau pakar wilayah.
- **Mengapa**: pelaporan waypoint FarOut [S37]; validasi moderator AllTrails [S10].

**C4. Portal pengelola kawasan** · P1 · Skema baru: mungkin
- **Apa**: akun terverifikasi untuk balai atau basecamp resmi yang menerbitkan notice dan penutupan langsung,
  dengan pengingat verifikasi berkala.
- **Mengapa**: AllTrails Public Lands (250+ agensi saat rilis 2024; TIME menyebut 800+ mitra di 2026)
  [S6][S4]; pengingat Outdooractive [S32]. `AuthorityType` sudah punya `NATIONAL_PARK` dan `BASECAMP`.
- **Aturan**: butuh kemitraan nyata; perubahan peran adalah keputusan pemilik.

**C5. Ringkasan AI berlabel** · P2 · Skema baru: tidak
- **Apa**: "Ringkasan otomatis dari N laporan komunitas 14 hari terakhir. Bukan status resmi." Selalu di bawah
  status resmi. **Mengapa**: Trail Conditions AllTrails [S1]; kategorisasi AI FarOut [S37]. **Aturan**: R-009,
  R-011; `SourceType::AI_INTERPRETATION` sudah ada.

**C6. Kebijakan anti-jalur-ilegal** · P1 · Skema baru: tidak
- **Apa**: jejak dan laporan yang dibagikan tidak boleh menampilkan atau menyarankan jalur di luar jalur resmi;
  moderasi menolak. **Mengapa**: blacklist pendaki ilegal Semeru [S97] dan penyelenggara open trip Lawu [S96].
  **Aturan**: R-022.

### 6.8 Isu terbuka dan ketidaksesuaian dokumen

Dilaporkan sesuai CLAUDE.md §1 butir 11. Tidak ada yang diubah.

1. **IA berbeda antara DESIGN.md dan arahan pemilik.** DESIGN.md ("Navigasi dan Jelajah", 25 September 2026)
   menaruh Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar di bilah bawah; arahan pemilik untuk tugas ini
   menaruh Home, Explore, Trips, Track, Progress, dengan Profile terpisah. Perlu keputusan dan pembaruan
   DESIGN.md oleh pemilik.
2. **Frasa "skor kesiapan" di PRD** (Fase 2.2 dan alur langkah 10) berisiko dibaca sebagai angka publik,
   sementara R-005, R-014, dan `ReadinessState` bersifat kualitatif. Usul: nyatakan "status kesiapan".
3. **Luring (K1)**: kebijakan service worker dan kebutuhan lapangan sama-sah; butuh keputusan tertulis soal
   "paket lapangan bercap waktu" dan sumber ubin pengganti OpenTopoMap yang melarang prefetch [S99].
4. **PVMBG sebagai otoritas baru (E1)** dan **grade Kemenhut di Route Fit (E2)** menyentuh R-009 dan R-021.
5. **Belum terverifikasi**: kanal booking Prau; kuota harian Rinjani dan minimal rombongan; "Weekly Streak"
   PeakVisor; peringatan terlambat kembali di AllTrails Live Share; struktur tab Komoot.
6. **Pengingat perjalanan di PRD 2.2 belum dibangun** (tidak ada `app/Notifications`).

---

## 7. Yang tidak boleh ditiru

| Pola | Contoh | Alasan | Aturan |
|---|---|---|---|
| Papan peringkat, segmen, level, kompetisi, liga | Strava [S19][S13], Garmin [S44], Apple [S53], Duolingo [S65], PeakVisor [S34] | Memberi hadiah pada kecepatan dan frekuensi; di gunung mendorong risiko (fenomena FOMO [S98]) | R-015; arahan pemilik |
| Skor angka kesiapan atau kecocokan | Apple 0-10 [S49], Garmin Training Readiness [S42][S47] | Presisi palsu untuk keputusan keselamatan | R-005, R-014 |
| Rute buatan AI atau rute kustom bebas | AllTrails Custom Routes [S1] | Bisa mengarah ke jalur tak resmi yang dikenai sanksi [S96][S97]; AI bukan otoritas | R-011, R-022 |
| Unggahan jalur UGC terbuka dan heatmap global | Wikiloc [S36], AllTrails Heatmap [S1] | Menyebarkan jalur ilegal; heatmap membuka pola lokasi orang | R-022; kebijakan data |
| Fitur keselamatan berbayar | AllTrails Plus [S3], Strava [S13][S14], Wikiloc [S36] | Memount tanpa monetisasi; keselamatan tidak boleh berjenjang | Arahan pemilik |
| Foto komunitas sebagai hero atau pratinjau | Desain ulang Komoot [S24], pratinjau foto komunitas [S27] | Foto belum terverifikasi menyesatkan kondisi | R-017 |
| Streak harian atau mingguan | Duolingo, klaim PeakVisor | Menekan frekuensi dan memberi rasa bersalah; mendaki bukan kebiasaan harian | R-015 |
| Feed sosial sebagai tab | Strava Home, AllTrails Activity [S8] | IA pemilik menaruh komunitas lintas-halaman; feed memicu perbandingan | IA pemilik |
| Kondisi prediktif tunggal tanpa atribusi per sumber | AllTrails Trail Conditions [S1] | Mencampur cuaca, ulasan, dan model | R-009, R-013 |
| Pelacakan langsung yang tersirat sebagai jaminan | Live Share, Beacon [S7][S17] | Bergantung sinyal yang umumnya tidak ada | R-012 (semangat) |
| Menjual atau memperantarai tiket | Gearberg [S94] | Kanal resmi ada (AKTN dan balai); tanpa monetisasi | Arahan pemilik |
| Video flyover 3D sebagai inti berbagi | Relive [S40], Strava Flyover [S13] | Berat di ponsel kelas bawah; bertentangan dengan MOTION 1 | DESIGN.md |
| Peta perjalanan teman dan koneksi | Airbnb connections [S62] | Membuka pola lokasi orang lain | Kebijakan data |

---

## 8. Daftar sumber

Semua diakses 25 September 2026. "(cuplikan)" = isi dibaca dari cuplikan mesin pencari.

**AllTrails**
- S1. PR Newswire, "AllTrails expands membership offering with AllTrails Peak", 12 Mei 2025. https://www.prnewswire.com/news-releases/alltrails-expands-membership-offering-with-alltrails-peak-302451541.html
- S2. TechCrunch, "AllTrails debuts a $80/year membership that includes AI-powered smart routes", 12 Mei 2025. https://techcrunch.com/2025/05/12/alltrails-debuts-a-80-year-membership-that-includes-ai-powered-smart-routes
- S3. App Store, AllTrails v26.9.41, September 2026. https://apps.apple.com/us/app/alltrails-hike-bike-run/id405075943
- S4. TIME100 Most Influential Companies 2026: AllTrails, 30 April 2026. https://time.com/collection/time100-most-influential-companies/2026/alltrails/
- S5. AllTrails, "AllTrails introduces new features for trail runners", 28 Mei 2026 (cuplikan; 403). https://www.alltrails.com/press/alltrails-introduces-new-features-for-trail-runners
- S6. PR Newswire, "AllTrails Announces Public Lands Program", 5 Maret 2024. https://www.prnewswire.com/news-releases/alltrails-announces-public-lands-program-with-new-trail-and-land-management-resources-302079247.html
- S7. AllTrails Help, "How to use Live Share" (cuplikan; 403). https://support.alltrails.com/hc/en-us/articles/37212858771348-How-to-use-Live-Share
- S8. AllTrails Help, "Understanding the AllTrails App" (cuplikan; 403). https://support.alltrails.com/hc/en-us/articles/44409942124052-Understanding-the-AllTrails-App
- S9. AllTrails Help, "Trail Conditions" dan "How to write a review for a trail" (cuplikan). https://support.alltrails.com/hc/en-us/articles/36933535617300-Trail-Conditions
- S10. AllTrails Help, "How does a trail end up on AllTrails?" (cuplikan). https://support.alltrails.com/hc/en-us/articles/30315531476628-How-does-a-trail-end-up-on-AllTrails
- S11. AllTrails Help, "AllTrails T-Satellite connection" (cuplikan). https://support.alltrails.com/hc/en-us/articles/41465775195796-AllTrails-T-Satellite-connection
- S12. AllTrails, "Introducing Collaborative Lists", 15 Februari 2024 (cuplikan). https://www.alltrails.com/press/alltrails-introduces-collaborative-lists

**Strava**
- S13. Strava Press, "Strava Adds New Features for Hiking", 11 Juni 2026. https://press.strava.com/articles/strava-adds-new-features-for-hiking-making-the-outdoor-experience-more-discoverable-navigable-and-social
- S14. Strava Stories, "What's New on Strava" (Route Deviation Alerts), 28 Mei 2026. https://stories.strava.com/articles/whats-new-on-strava-may-2026
- S15. Strava Press, "Strava Enhances Subscriber Experience with Updates to Key Features", 21 Februari 2025. https://press.strava.com/articles/strava-enhances-subscriber-experience-with-updates-to-key-features
- S16. Strava Press, "Strava Launches Redesigned Record Experience", 16 Juli 2025. https://press.strava.com/articles/strava-launches-redesigned-record-experience
- S17. endurance.biz, "Strava Beacon feature available for all app users", 2021. https://endurance.biz/2021/industry-news/safety-first-strava-beacon-feature-available-for-all-app-users/
- S18. BikeRadar, "Strava beta tests new app layout" (cuplikan). https://www.bikeradar.com/news/strava-beta-test-layout
- S19. Strava Help, "Local Legends". https://support.strava.com/hc/en-us/articles/360043099552-Local-Legends
- S20. Strava Help, "Edit Map Visibility". https://support.strava.com/hc/en-us/articles/115000173384-Edit-Map-Visibility
- S21. Strava Help, "Progress Summary Chart" (cuplikan). https://support.strava.com/hc/en-us/articles/28437860016141-Progress-Summary-Chart

**Komoot, Gaia GPS, onX, Outdooractive, PeakVisor, Wikiloc, FarOut, CalTopo, Relive**
- S22. BikeRadar, "Complete guide to Komoot"; Komoot Support, "Plan routes in the app" (cuplikan). https://www.bikeradar.com/advice/buyers-guides/guide-to-using-komoot
- S23. Komoot Support, "Highlights". https://support.komoot.com/hc/en-us/articles/10194639751450-Highlights
- S24. heise online, "After sale and redundancies: Komoot gets a fresh design", 15 September 2025. https://www.heise.de/en/news/After-sale-and-redundancies-Komoot-gets-a-fresh-design-10644247.html
- S25. BikeRadar, "Komoot unveils bold redesign and price increase", 2025. https://www.bikeradar.com/news/komoot-redesign-2025
- S26. BikeRadar, "New Komoot Apple Watch app enables offline navigation", 26 Januari 2026. https://www.bikeradar.com/news/komoot-apple-watch-app-jan-2026
- S27. Bending Spoons di X, daftar peningkatan Komoot (cuplikan). https://x.com/bendingspoons/status/1940334637567320283
- S28. Gaia GPS Blog, kategori New Features (Adventure Modes 13 Agustus 2026; Gaia Hike Map 18 Maret 2025); catatan rilis Android 2026 (cuplikan). https://blog.gaiagps.com/category/new-features/
- S29. onX Backcountry, App Features. https://www.onxmaps.com/backcountry/app/features
- S30. onX, "onX Backcountry Adds Hundreds of New Ski Routes for the 2025-26 Season". https://www.onxmaps.com/blog/onx-backcountry-adds-hundreds-of-new-ski-routes-for-the-2025-26-season
- S31. Outdooractive, "What are Current Conditions?". https://www.outdooractive.com/en/knowledgepage/what-are-current-conditions-/54327348/
- S32. Outdooractive, "Notices and Closures". https://www.outdooractive.com/en/knowledgepage/notices-and-closures/37514055/
- S33. Outdooractive, "Rank and the TOP label" (cuplikan). https://www.outdooractive.com/en/knowledgepage/rank-and-the-top-label/38365392/
- S34. App Store, PeakVisor v5.115, September 2026. https://apps.apple.com/us/app/hiking-and-skiing-peakvisor/id1187259191
- S35. PeakVisor, News and Announcements. https://peakvisor.com/en/news.html
- S36. Wikiloc Premium (cuplikan; 403); Wikiloc Help, Live tracking. https://www.wikiloc.com/premium ; https://help.wikiloc.com/article/468-live-tracking
- S37. FarOut, "New in FarOut: Filter, Flag, & Report for a Better Trail". https://faroutguides.com/new-in-farout-filter-flag-report-for-a-better-trail/
- S38. App Store, FarOut v15.5. https://apps.apple.com/us/app/farout-hike-bike-paddle/id605447532
- S39. CalTopo Blog, "Update: Discover New Routes & Maps with Shared Maps", 27 Maret 2026. https://blog.caltopo.com/2026/03/27/update-discover-new-routes-maps-with-shared-maps/
- S40. Relive, beranda resmi. https://www.relive.com/

**Garmin, Apple, Google**
- S41. Garmin Newsroom, "Elevate your health and fitness goals with Garmin Connect+", 27 Maret 2025. https://www.garmin.com/en-US/newsroom/press-release/wearables-health/elevate-your-health-and-fitness-goals-with-garmin-connect/
- S42. Garmin, Forerunner 965 Owner's Manual, "Training Readiness" (cuplikan). https://www8.garmin.com/manuals/webhelp/GUID-0221611A-992D-495E-8DED-1DD448F7A066/EN-US/GUID-C21BE0C8-A08E-4DA1-B6C6-2E0E2DDDB372.html
- S43. Garmin Technology, "ClimbPro Feature". https://www.garmin.com/en-US/garmin-technology/running-science/physiological-measurements/climbpro-feature/
- S44. Garmin Blog, "The 25 Garmin Connect Badges You Never Knew You Needed". https://www.garmin.com/en-US/blog/fitness/the-25-garmin-connect-badges-you-never-knew-you-needed/
- S45. Garmin Blog, "Garmin releases annual inReach SOS message report". https://www.garmin.com/en-CA/blog/news/garmin-releases-annual-inreach-sos-message-report/
- S46. Garmin Support, "LiveTrack for inReach". https://support.garmin.com/en-US/?faq=dTKb0UdhsZ3g1n3czgF9V8
- S47. the5krunner, "Garmin Training Readiness" (sumber sekunder; cuplikan). https://the5krunner.com/garmin-features/training/training-readiness/
- S48. Apple Newsroom, "Apple advances health and fitness capabilities using Apple Intelligence", 9 September 2026. https://www.apple.com/newsroom/2026/09/apple-advances-health-and-fitness-capabilities-using-apple-intelligence/
- S49. 9to5Mac, "Apple Watch adds an all-new Readiness app, here's how it works", 15 September 2026. https://9to5mac.com/2026/09/15/apple-watch-adds-an-all-new-readiness-app-heres-how-it-works/
- S50. Apple Newsroom, "Introducing Apple Watch Ultra 3", 9 September 2025 (cuplikan). https://www.apple.com/au/newsroom/2025/09/apple-introduces-apple-watch-ultra-3/
- S51. Apple Support, "View and add Compass Waypoints on Apple Watch" dan "Use Backtrack". https://support.apple.com/guide/watch/view-and-add-compass-waypoints-apd4d10b939e/watchos ; https://support.apple.com/guide/watch/use-backtrack-to-retrace-your-steps-apd25bfcec3f/watchos
- S52. Apple Support, "See your activity history, trends, and awards in Fitness on iPhone". https://support.apple.com/guide/iphone/see-your-activity-history-trends-and-awards-iph4c34a8a95/ios
- S53. Apple Support, "Share your activity from Apple Watch". https://support.apple.com/guide/watch/share-your-activity-apd68a69f5c7/watchos
- S54. Apple Newsroom, "Apple Maps introduces new ways to explore the world", September 2024 (cuplikan). https://www.apple.com/newsroom/2024/09/apple-maps-introduces-new-ways-to-explore-the-world/
- S55. 9to5Mac, "Here's everything new for Apple Maps in iOS 27", 10 Agustus 2026. https://9to5mac.com/2026/08/10/heres-everything-new-for-apple-maps-in-ios-27/
- S56. DC Rainmaker, "Apple watchOS 27 New Features Detailed", Juni 2026; MacRumors, roundup watchOS 27 (cuplikan). https://www.dcrainmaker.com/2026/06/apple-watchos27-new-features-detailed.html
- S57. TechCrunch, "Google Maps is getting an AI Ask Maps feature", 12 Maret 2026 (cuplikan). https://www.techcrunch.com/2026/03/12/google-maps-is-getting-an-ai-ask-maps-feature-and-upgraded-immersive-navigation/
- S58. Google Blog, "Explore new ways to plan and book travel with AI in Search", 17 November 2025. https://blog.google/products-and-platforms/products/search/agentic-plans-booking-travel-canvas-ai-mode/

**Aplikasi perjalanan dan progres**
- S59. TripIt, "A single (free) itinerary created for you in seconds". https://www.tripit.com/web/free
- S60. TripIt Pro. https://www.tripit.com/pro
- S61. Wanderlog, beranda resmi. https://wanderlog.com/
- S62. Airbnb Newsroom, "2026 Summer Release", 20 Mei 2026. https://news.airbnb.com/airbnb-2026-summer-release
- S63. Hospitality Design, "Airbnb Adds Services, Experiences, and a Redesigned App", 2025 (cuplikan). https://hospitalitydesign.com/news/airbnb-services-experiences-new-app/179155
- S64. Polarsteps News, "Polarsteps launches summer 2026 release", 9 Juni 2026. https://www.polarsteps.com/news/polarsteps-launches-summer-2026-release-new-tools-for-planning-privacy-and-sharing
- S65. Duolingo Help Center, "What are leaderboards and leagues?" (cuplikan). https://www.duolingo.com/help/leaderboards-and-league

**Indonesia**
- S66. Kementerian Kehutanan, "Antisipasi Puncak Kemarau, Kemenhut Atur Status Pendakian Gunung di Kawasan Konservasi Berbasis Manajemen Risiko", 31 Agustus 2026. https://www.kehutanan.go.id/news/antisipasi-puncak-kemarau-kemenhut-atur-status-pendakian-gunung-di-kawasan-konservasi-berbasis-manajemen-risiko
- S67. Mounture, "9 Taman Nasional Tutup Pendakian Mulai September 2026". https://mounture.com/berita/9-taman-nasional-tutup-pendakian-mulai-september-2026-ini-aturan-terbarunya/
- S68. Kementerian Kehutanan, "Kemenhut Luncurkan Aplikasi E-Ticketing Ayo Ke Taman Nasional", 4 Juni 2026. https://www.kehutanan.go.id/news/kemenhut-luncurkan-aplikasi-e-ticketing-ayo-ke-taman-nasional-wujudkan-transformasi-digital-wisata-alam
- S69. Kompas Travel, "Kemenhut Terbitkan Grade Jalur Pendakian Gunung di Indonesia, Apa Itu?", 15 Agustus 2025. https://travel.kompas.com/read/2025/08/15/200555327/kemenhut-terbitkan-grade-jalur-pendakian-gunung-di-indonesia-apa-itu
- S70. CNN Indonesia, "Daftar Resmi Rangking Jalur Pendakian di RI", 19 Agustus 2025 (cuplikan). https://www.cnnindonesia.com/gaya-hidup/20250819154529-269-1264080/daftar-resmi-rangking-jalur-pendakian-di-ri-ringan-hingga-sulit
- S71. Kompas Travel, "Gunung Rinjani Ditetapkan Masuk Grade 4", 14 Agustus 2025. https://travel.kompas.com/read/2025/08/14/064524427/gunung-rinjani-ditetapkan-masuk-grade-4-bukan-untuk-pendaki-pemula
- S72. CNA Indonesia, "Mau mendaki Gunung Rinjani? Simak SOP baru, sistem grading, sertifikat, dan wajib asuransi premium", 14 Agustus 2025. https://www.cna.id/indonesia/mau-mendaki-gunung-rinjani-simak-sop-baru-sistem-grading-sertifikat-dan-wajib-asuransi-premium-36616
- S73. Liputan6, "Gunung Rinjani Jadi Lokasi Pilot Project Penerapan Aplikasi Pelacakan Pendaki", 14 Agustus 2025. https://www.liputan6.com/lifestyle/read/6132250/gunung-rinjani-jadi-lokasi-pilot-project-penerapan-aplikasi-pelacakan-pendaki-selama-pendakian
- S74. TNGR, "Agenda Rinjani Musim Pendakian 2026", 11 Februari 2026. https://www.rinjaninationalpark.id/detail/agenda-rinjani-musim-pendakian-2026
- S75. Kabarbaik, "War Tiket Rinjani 2026", 6 Maret 2026; Mounture, "Gunung Rinjani Dibuka Terbatas, Booking eRinjani Dihentikan Sementara", 1 September 2026. https://kabarbaik.co/war-tiket-rinjani-2026-panduan-booking-harga-dan-aturan-jalur-terbaru/ ; https://mounture.com/berita/gunung-rinjani-dibuka-terbatas-booking-erinjani-dihentikan-sementara/
- S76. NTBSatu, "Pendaki Tektok Gunung Rinjani Wajib Kantongi Surat Rekomendasi Balai TNGR", Juni 2026. https://ntbsatu.com/pariwisata/malindaratnayu/pendaki-tektok-gunung-rinjani-wajib-kantongi-surat-rekomendasi-balai-tngr/
- S77. detikSumbagsel, "Pendakian Gunung Dempo Dilarang Tektok", 2026 (cuplikan). https://www.detik.com/sumbagsel/wisata/d-8560736/pendakian-gunung-dempo-dilarang-tektok-ini-aturan-2026-dan-saksi-yang-berlaku
- S78. Mounture, "Aturan Pendakian Gunung Arjuno-Welirang 2026" (cuplikan). https://mounture.com/informasi/aturan-pendakian-gunung-arjuno-welirang-2026-tektok-dilarang-drone-dan-speaker-juga-dibatasi/
- S79. IDN Times, "Aturan Tektok Gunung Lawu via Cemoro Sewu Terbaru" (cuplikan). https://www.idntimes.com/travel/destination/apakah-boleh-tektok-gunung-lawu-via-cemoro-sewu-ini-aturan-terbarunya-c1c2-01-6n9g9-r573mk
- S80. ANTARA, "TNBTS buka kembali jalur pendakian Gunung Semeru pada 24 April 2026"; IDN Times, "Pendakian Gunung Semeru Dibuka Kembali 2026" (keduanya cuplikan). https://www.antaranews.com/berita/5539287/tnbts-buka-kembali-jalur-pendakian-gunung-semeru-pada-24-april-2026
- S81. ABC News, "More than 170 people rescued off Mount Semeru after eruption", 21 November 2025 (cuplikan). https://www.abc.net.au/news/2025-11-21/climbers-rescued-locals-evacuated-after-mount-semeru-eruption/106035320
- S82. EnviroNews Asia, "Indonesia Issues Highest Alert as Mount Semeru Eruption Triggers Emergency Evacuation at Ranu Kumbolo" (cuplikan). https://environews.asia/indonesia-issues-highest-alert-as-mount-semeru-eruption-triggers-emergency-evacuation-at-ranu-kumbolo/
- S83. CBS News, "Indonesia volcano death toll rises to 23", Desember 2023 (cuplikan). https://www.cbsnews.com/news/volcano-mount-marapi-death-toll-rises-indonesia/
- S84. booking.gedepangrango.org; Mounture, "Gunung Gede Pangrango Dibuka Kembali 13 April 2026"; Mounture, penutupan 12-18 Agustus 2026 (cuplikan). https://booking.gedepangrango.org/ ; https://mounture.com/berita/gunung-gede-pangrango-dibuka-kembali-13-april-2026-ini-kuota-pendakian-dan-cara-booking-online/
- S85. booking.tngunungmerbabu.org; Mounture, "Panduan Booking Online Gunung Merbabu 2026" (cuplikan). https://booking.tngunungmerbabu.org/app/
- S86. IDN Times, "Daftar Gunung di Indonesia yang Wajibkan Gelang RFID", 7 Juli 2025. https://www.idntimes.com/travel/destination/gunung-di-indonesia-wajibkan-gelang-rfid-00-p3rq2-094dvt
- S87. BMKG, "Data Prakiraan Cuaca Terbuka". https://data.bmkg.go.id/prakiraan-cuaca/
- S88. MAGMA Indonesia (PVMBG, Kementerian ESDM); ESDM, "Aplikasi Magma" (cuplikan). https://magma.esdm.go.id/
- S89. Basarnas, "Darurat SAR hubungi 115". https://basarnas.go.id/berita/kabasarnas-darurat-sar-hubungi-115
- S90. Harapan Rakyat, "Aplikasi MDPL, Teman Digital Pendakian dengan Peta Offline Gunung Indonesia", 20 Agustus 2026. https://www.harapanrakyat.com/2026/08/aplikasi-mdpl-teman-digital-pendakian-dengan-peta-offline-gunung-indonesia/
- S91. muncak.id (beranda, rekomendasi.muncak.id, chatbot.muncak.id); repositori Unsoed, skripsi evaluasi UX muncak.id 2026 (isi tidak terbaca). https://muncak.id/en ; https://rekomendasi.muncak.id/
- S92. Treki AI, "Panduan Mountain Grading", 25 Agustus 2025. https://treki.id/en/blog/panduan-mountain-grading-jalur-pendakian-indonesia
- S93. UNIKOM, "GunungKita: ketika teknologi turun gunung" (cuplikan; halaman 404). https://web.unikom.ac.id/gunungkita-ketika-teknologi-turun-gunung-demi-pendakian-yang-lebih-aman/
- S94. Gearberg, About; listing Play Store "Tiket Pendakian" (cuplikan; isi tidak terbaca). https://gearberg.com/about ; https://play.google.com/store/apps/details?id=id.co.tiketpendakian.android
- S95. Kompas Travel, "Polemik Booking Lahan Camp di Gunung, Kemenhut Tegur Penyelenggara Open Trip", 3 Juni 2025 (cuplikan). https://travel.kompas.com/read/2025/06/03/170000727/polemik-booking-lahan-camp-di-gunung-kemenhut-tegur-penyelenggara-open-trip
- S96. Hariankota, "Penyelenggara Open Trip Gunung Lawu Kena Sanksi Blacklist" (cuplikan). https://hariankota.com/19411-2/
- S97. detik35, "TNBTS Blacklist 8 Pendaki yang Nekat Masuk Gunung Semeru Secara Ilegal", September 2026 (cuplikan). https://www.detik35.com/2026/09/tnbts-blacklist-8-pendaki-yang-nekat.html
- S98. MDMC PWM Jateng, "FOMO Hiking: Gunung Tidak Seperti Mal"; Portal Arjuna, "Fenomena FOMO di Pendakian", Februari 2026 (keduanya cuplikan). https://mdmc.pwmjateng.com/fomo-hiking-gunung-tidak-seperti-mal/
- S99. OSMF Operations, "Tile Usage Policy"; kebijakan OpenTopoMap tentang prefetch (cuplikan). https://operations.osmfoundation.org/policies/tiles/

**Bukti internal (repositori Memount, 25 September 2026)**
- `routes/web.php` (rute `dashboard`, `trails.*`, `trips.*`, `hike.track`, `reports.*`, `news`, `progress`, `history`); migrasi `create_trip_plans_table`, `create_permit_requirements_table`, `create_official_statuses_table`, `create_trail_condition_reports_table`, `create_user_experience_table`, `create_hiking_goals_table`.
- Enum `OfficialStatusValue`, `StatusScope`, `ReadinessState`, `RouteFitLabel`, `CompatibilityFactor`, `ConditionTag`, `FreshnessState`, `CompletionState`, `SourceType`, `AuthorityType` di `app/Enums/`.
- `resources/views/sw.blade.php` (dokumen selalu dari jaringan; luring dijawab halaman pengakuan); `.env.example` (`MAP_TILE_URL` OpenTopoMap); `routes/console.php` (dua jadwal); tidak ada `app/Notifications`; `resources/js/pages/Dashboard.tsx`; DESIGN.md "Navigasi dan Jelajah"; PRODUCT.md "Operating Context".
