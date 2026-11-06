# Desain: Memount v2 (IA lima tab, UI/UX terkini, repo tim, konteks tahan-sesi)

**Tanggal:** 25 September 2026
**Status:** ditulis agen dari arahan pemilik 25 September 2026. Dikerjakan di bawah
mandat eksplisit pemilik ("terapkan dan implementasikan disini", "saya tidak mau anda
setengah-setengah tapi maksimal"). Pemilik dapat mengoreksi bagian mana pun; hal yang
membutuhkan izin tersendiri dikumpulkan di §9 dan tidak dikerjakan sebelum izin.
**Klasifikasi:** arsitektural (arsitektur navigasi berubah, beberapa subsistem).

---

## 1. Arahan pemilik (verbatim, 25 September 2026)

> "yang saya lihat masih sama Penilaian "masih CRUD sederhana". yang saya rasakan hanya
> update fitur crud itupun sedikit dan tidak semua fitur nya tidak dapat berfungsi dengan
> baik. riset trend" ui/ux app terbaru baik itu web/mobile kemudian terapkan dan
> implementasikan disini. pastikan untuk selalu menerapkan semua skill disini compare dan
> combine semua skill disini."

> "MEMOUNT adalah platform perjalanan gunung yang dipersonalisasi, yang menghubungkan
> discovery, Route Fit, preparation, readiness, field experience, trust, community, dan
> progression dalam satu perjalanan utuh. MEMOUNT membantu saya menemukan perjalanan
> gunung yang cocok untuk saya."

Navigasi yang ditetapkan pemilik:

```text
CONTENT
Home   Explore   Trips   Track   Progress
```

> "1. Home: Personalized dashboard. 2. Explore: Discovery. 3. Trips: Workspace untuk
> Fit + Plan + Ready. 4. Track: Hike mode. 5. Progress: History + progression.
> Community? Community menjadi cross-cutting layer, bukan primary tab. Contohnya:
> Explore → Trail → Community Reports atau: Trail Detail → Community atau: Progress →
> Hike → Share Report. Dengan ini kita mempertahankan semua fitur tanpa membuat navigasi
> mobile berantakan."

```text
MEMOUNT
├── HOME: Your next journey, Recommended for you, Readiness status, Upcoming trip, Progress snapshot
├── EXPLORE: Map, Trails, Mountains, Filters, Saved
├── TRIPS: Route Fit, Compare, Trip Plan, Preparation, Ready Check
├── TRACK: Start Hike, Live Map, GPS, Checkpoints, Elevation, Finish
├── PROGRESS: Hike History, Statistics, Goals, Milestones
└── PROFILE: Experience, Preferences, Equipment, Settings, Privacy
```

> "riset ui/ux saat ini element" apa yang mereka gunakan fitur" apa imk nya bagaimana
> dll. ... riset seberapa interaktif, modern, menarik nya ui/ux terbaru sebagai contoh
> brand" besar seperti pengembangan os seberapa kompetitif nya mereka."

> "baca dan analisis semua file disini rombak structure rapikan dan jadikan profesional
> baik secara keamanan dan kemudahannya. tujuannya untuk mempermudah repo satu team atau
> anggota atau orang lain ketika ingin mengclone atau update fitur atau ui/ux. ...
> compare dan combine file" atau isi" yang tertumpuk jadikan satu visi, misi, ekspektasi,
> dan kesepakatan bersama. dan juga persiapkan pula ketika ganti terminal baru atau token
> habis jadi saya tidak perlu mengulang konteks agar agent mengerti. kamu adalah engineer
> serta desaigneer dalami peran ini."

## 2. Pemahaman (tulis-balik, dapat dikoreksi pemilik)

**Hasil yang dituju.** Pendaki merasakan satu perjalanan utuh yang hidup dan personal
("MEMOUNT membantu saya menemukan perjalanan gunung yang cocok untuk saya"), bukan
kumpulan formulir dan tabel. Tim dapat meng-clone, memahami, dan mengubah repo tanpa
bertanya. Agen baru dapat melanjutkan tanpa pemilik mengulang konteks.

**Ukuran berhasil.**
1. Navigasi utama persis lima tab pemilik plus Profil; Community muncul di tiga titik
   lintas-halaman yang disebut pemilik.
2. Setiap tab memuat butir pohon IA pemilik, masing-masing berfungsi dengan data nyata
   atau keadaan kosong yang jujur (R-006, R-007).
3. Unsur UI/UX terkini yang diriset (§6) diterapkan sejauh membantu keputusan, dengan
   fallback aksesibilitas dan batas perangkat kelas bawah.
4. Repo: README proyek, panduan kontribusi di root, dokumen tanpa tumpukan, satu PRD
   dengan ID permanen, satu tempat untuk visi/misi/ekspektasi/kesepakatan.
5. HANDOVER memuat prompt lanjut yang cukup untuk terminal baru.

**Yang dikatakan pemilik** ada di §1. **Asumsi agen** (koreksi bila keliru):
- A1. Label tab berbahasa Indonesia sesuai komitmen merek: Beranda (Home), Jelajah
  (Explore), Trip (Trips), Mendaki (Track), Progres (Progress), Profil (Profile).
- A2. "Community lintas-halaman" berarti tab Kabar dihapus dari navigasi utama; kabar
  gunung yang diikuti pindah ke Beranda dan tetap dapat dibuka dari sana dan dari Jelajah.
- A3. Konsol admin, moderator, dan kontribusi pakar tidak masuk tab; dibuka dari Profil.
- A4. "GPS" dan "Live Map" di Track berarti posisi mengikuti langsung selama mode
  pendakian aktif, atas persetujuan eksplisit di perangkat, tanpa mengirim posisi ke
  server di luar rekam jejak yang sudah opsional (PRD §53-§54, kebijakan data pribadi).
- A5. "Equipment" berarti daftar perlengkapan pribadi yang dipakai ulang oleh daftar
  persiapan trip. Ini skema baru (§9).
- A6. "Milestones" adalah pencapaian terhadap diri sendiri (R-015), tanpa peringkat antar
  pengguna dan tanpa mendorong kecepatan (PRODUCT.md, komitmen merek).

## 3. Temuan (FAKTA, dengan bukti)

| # | Temuan | Bukti |
|---|---|---|
| T1 | Rasa "CRUD" sebagian besar berasal dari pilihan desain agen, bukan dari pemilik: dial ENERGY 1, MOTION 1 ("tepat satu momen gerak di seluruh aplikasi"), dan penolakan bento grid serta animasi masuk | `DESIGN.md` bagian Turunan, "Tiga dial" dan "Yang tidak dikerjakan"; bagian itu bertanda "ditarik agen, silakan dibetulkan" |
| T2 | Banyak fitur tampak rusak karena datanya kosong, bukan karena kodenya salah: 0 jalur bergeometri, 0 pos berkoordinat, semua status resmi UNKNOWN (per 20 Sep 2026) | `docs/ARCHITECTURE.md` "Yang belum ada"; `ourplan.md` Golden Trail; DK-22 fase data |
| T3 | Fitur yang sudah disetujui pemilik (DK-07) belum dibangun: catatan pribadi, tautan bagikan, pengingat, detail rencana (tanggal selesai, peserta), target pribadi, catat pendakian manual, masuk Google dan passkey | spec konsolidasi §2 DK-07; kode tidak memuat tabelnya |
| T4 | Batas banding di kode 5, keputusan DK-05 maksimal 3, PRD kerja F-06 "hingga 3", PRODUCT.md "maksimum lima" | `ConsiderationService::BATAS`; spec konsolidasi DK-05; `PRD.md` F-06; `PRODUCT.md` Traveloka |
| T5 | `PRD.md` di working tree (415 baris, format NgodingPakeAI) menimpa PRD kanonis v2.0 (3.670 baris, §1-§146, BR-01-BR-16) yang masih utuh di HEAD; ratusan rujukan "PRD §N" di kode dan CLAUDE.md menunjuk isi yang tidak ada di salinan kerja | `git show HEAD:PRD.md \| wc -l` = 3670; `wc -l PRD.md` = 415 |
| T6 | Rencana konsolidasi (DK-01-DK-12 disetujui 23 Sep 2026) belum dieksekusi: "Belum ada berkas kanonis yang diubah" | `docs/superpowers/specs/2026-09-23-konsolidasi-dokumen-design.md` status |
| T7 | `README.md` masih README bawaan Laravel; `CONTRIBUTING.md` ada di `docs/`, bukan root | `README.md`; `docs/CONTRIBUTING.md` |
| T8 | Berkas kerja penting tidak dilacak git: `features/` (kriteria pemilik), `ourplan.md`, `boost.json`; sisa lain di root: `jelajah-gunung.zip`, `opencode.json.disabled`, `anti-slop/` | `git ls-files`; `ls` root |
| T9 | Alat NgodingPakeAI `plan get` menulis `PRD.md` dan `features/` di root, sehingga dapat menimpa PRD gabungan lagi | `AGENTS.md` bagian NgodingPakeAI; spec konsolidasi §1 |
| T10 | Konflik lisensi: DK-22 "OSM hanya peta dasar, tanpa impor ke katalog (menghindari share-alike ODbL)", sedangkan konsol admin mengimpor garis jalan setapak OSM sebagai geometri jalur | `TrailGeometryController::search/choose`; spec konsolidasi DK-22 |
| T11 | Kriteria `features/` yang bertabrakan dengan aturan kanonis: "skor kecocokan" di tabel banding (R-005), "skor kesiapan (angka)" (R-005, R-014), label status "Waspada" di luar enum status (R-004 tidak menyentuh status, tetapi enum resmi belum memuatnya) | `features/03`, `features/04`, `features/05` |
| T12 | Pintu masuk tab Kabar ada di navigasi utama; arahan baru menjadikannya lapisan lintas-halaman | `resources/js/layouts/NavLinks.tsx` `permukaan` |
| T13 | Tema: pemilih tema React memicu peristiwa di `window` sedangkan pendengarnya di `document`, sehingga tema baru baru terlihat setelah muat ulang | dibuktikan di peramban 25 Sep 2026 (atribut tetap `light` setelah cookie `dark`); diperbaiki, dijaga `ThemePreferenceTest` |

## 4. Keputusan

| ID | Keputusan | Oleh | Dasar |
|---|---|---|---|
| DK-25 | Arsitektur informasi v2: lima tab Home, Explore, Trips, Track, Progress, plus Profile; Community lintas-halaman (verbatim §1). Menggantikan lima permukaan lama (Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar). Governance record GR-02 di PRD | Pemilik | Arahan 25 Sep 2026 |
| DK-26 | Dial desain di `DESIGN.md` Turunan diturunkan ulang dari riset §6: aplikasi boleh hidup dan interaktif selama geraknya menjelaskan (kontinuitas ruang, umpan balik, status) dan tidak menunda isi; mode Track tetap tenang dan kontras | Pemilik (arah), agen (turunan) | Arahan 25 Sep 2026; T1 |
| DK-27 | Rencana konsolidasi 23 Sep dieksekusi sekarang, dengan pembaruan: merek Memount, frontend React, IA v2 (GR-02), visi/misi/ekspektasi/kesepakatan bersama disatukan di PRD Bagian I dan VIII | Pemilik | DK-01-DK-12; arahan 25 Sep 2026 |
| DK-28 | Fitur DK-07 yang tidak butuh dependensi dibangun sekarang dengan migrasi yang diuji di SQLite dan PostgreSQL CI; migrasi baru tidak dijalankan di Supabase sebelum izin (DK-23, R-019). Masuk Google/passkey dan kode WhatsApp/SMS menunggu izin dependensi dan penyedia (DK-24, DK-08) | Agen, dalam batas DK-07, DK-23, DK-24 | T3 |
| DK-29 | Batas banding diselaraskan ke 3 (DK-05); PRODUCT.md diperbaiki mengikutinya | Delegasi pemilik (DK-05) | T4 |

## 5. Arsitektur informasi v2 (DK-25)

Label tab mengikuti A1. Rute lama dipertahankan bila ada, supaya tautan, test, dan
riwayat peramban tidak putus; yang berubah adalah tab mana yang aktif dan isi halamannya.

| Tab | Butir pemilik | Rute | Keadaan sekarang | Yang dibangun |
|---|---|---|---|---|
| **Beranda** (Home) | Your next journey, Recommended for you, Readiness status, Upcoming trip, Progress snapshot | `/dashboard` (`dashboard`) | Dasbor tiga keadaan berbasis teks | Dasbor personal: kartu perjalanan berikutnya (hitung mundur, status resmi, cuaca, kesiapan), rekomendasi "karena ...", status kesiapan, trip mendatang, ringkasan progres, kabar gunung yang diikuti (lapisan Community), satu "langkah berikutnya" |
| **Jelajah** (Explore) | Map, Trails, Mountains, Filters, Saved | `/trails`, `/trails/saved` | Peta sebaran + daftar + saringan; simpanan ada | Peta layar penuh dengan lembar bawah di ponsel, daftar kartu, tampilan Gunung (Mountain ≠ Trail, R-001: gunung membuka jalurnya), pencarian kalimat sederhana (features/01), simpanan dengan catatan pribadi (DK-07) |
| **Trip** (Trips) | Route Fit, Compare, Trip Plan, Preparation, Ready Check | `/trips`, `/goals/create`, `/recommendations/{run}`, `/trails/compare`, `/trips/{trip}`, `/trips/{trip}/preparation`, `/trips/{trip}/readiness` | Halaman terpisah | Ruang kerja per trip dengan langkah Fit, Rencana, Persiapan, Cek kesiapan; banding pindah ke tab ini (maks 3, DK-05); detail rencana (tanggal selesai, peserta), pengingat, tautan bagikan (DK-07) |
| **Mendaki** (Track) | Start Hike, Live Map, GPS, Checkpoints, Elevation, Finish | `/mendaki` (baru, `track`), `/trips/{trip}/hike` | Mode pendakian per trip, posisi per ketukan | Pintu tab: lanjutkan pendakian aktif, atau pilih trip yang siap dimulai; mode lapangan: peta langsung, ikuti posisi atas izin (A4), pos berikutnya dan sisa jarak, posisi pada profil elevasi, Wake Lock, status luring, alur selesai |
| **Progres** (Progress) | Hike History, Statistics, Goals, Milestones | `/progres`, `/history`, `/history/{trip}` | Dua halaman terpisah | Satu hub dengan segmen Riwayat, Statistik, Target (target pribadi, DK-07), Pencapaian (A6); catat pendakian manual (DK-07); ringkasan pendakian dengan "Bagikan laporan" (Community) |
| **Profil** (Profile) | Experience, Preferences, Equipment, Settings, Privacy | `/profile`, `/onboarding` | Akun dan profil pendaki terpisah | Hub profil: Pengalaman, Preferensi, Perlengkapan (A5), Pengaturan (tema, akun, sandi), Privasi (rekam jejak, hapus akun, kebijakan data); pintu konsol admin, moderator, dan kontribusi pakar (A3) |

**Community lintas-halaman** (verbatim pemilik): detail jalur memuat laporan komunitas
dan ajakan melapor; ringkasan pendakian di Progres memuat "Bagikan laporan"; Beranda
memuat kabar gunung yang diikuti. `/kabar` tetap ada sebagai halaman lengkap yang dibuka
dari Beranda dan Jelajah, bukan dari tab.

**Layar lebar.** Lima tab yang sama tampil di navigasi atas (konsistensi antar-ukuran),
Profil sebagai menu avatar. Tata letak halaman memakai dua panel bila isinya
membutuhkannya (peta + daftar, langkah + isi).

**Hubungan dengan ID PRD.** DK-25 mengamandemen §145 (arsitektur informasi) lewat GR-02
dan menggantikan lima permukaan lama. R-001-R-017 dan R-022 tidak berubah.

## 6. Arah desain v2 (DK-26)

Riset empat jalur berjalan paralel dan hasilnya disimpan di
`docs/riset/2026-09-25-uiux/01-bahasa-desain-os.md`, `02-benchmark-aplikasi-outdoor.md`,
`03-imk-pola-interaksi.md`, dan `04-platform-web-implementasi.md`. Sintesisnya ditulis di
`docs/riset/2026-09-25-uiux/00-sintesis.md`, lalu nilai konkretnya (token, kurva gerak,
material, komponen) masuk ke `DESIGN.md` bagian Turunan. Prinsip yang sudah pasti dari
arahan pemilik dan aturan kanonis, sebelum angka dari riset:

1. **Hidup karena menjelaskan.** Gerak dipakai untuk kontinuitas ruang (kartu menjadi
   detail, lembar bawah naik dari peta), umpan balik (simpan, centang, kirim), dan status
   sistem (memuat, luring, menyinkron). Tidak ada gerak yang menunda isi.
2. **Dua mode.** Mode rencana (Beranda, Jelajah, Trip, Progres, Profil) boleh ekspresif.
   Mode lapangan (Mendaki) tenang, kontras tinggi, target besar, hemat baterai.
3. **Material hanya untuk yang melayang.** Efek kaca atau buram hanya pada lapisan yang
   melayang di atas isi (bilah tab, kendali peta, lembar bawah), dengan fallback padat
   untuk `prefers-reduced-transparency`, `prefers-contrast`, dan perangkat lemah.
4. **Personal dan dapat dijelaskan.** Setiap rekomendasi membawa "karena ..." dan jalan
   ke penjelasan per faktor (R-003, R-004, R-005).
5. **Jujur pada kekosongan.** Keadaan kosong adalah tahap dengan langkah berikutnya,
   bukan kotak kosong (R-006, R-007, komitmen merek).
6. **Aksesibel sejak rancangan.** WCAG 2.2 AA, termasuk alternatif non-seret untuk setiap
   gerakan seret (2.5.7) dan target minimum (2.5.8); `prefers-reduced-motion` mematikan
   gerak dekoratif dan menyisakan perubahan keadaan.
7. **Tanpa dependensi baru sampai diizinkan** (DK-24): gerak dengan CSS dan API peramban;
   pustaka hanya diajukan bila riset membuktikan manfaatnya.

## 7. Aliran kerja dan urutan

| Aliran | Isi | Pelaksana | Syarat mulai |
|---|---|---|---|
| A. Stabilisasi | Selesaikan pensiun Blade/Livewire; suite hijau; build | subagen pembersihan | berjalan |
| B. Riset dan arah | Empat laporan, sintesis, `DESIGN.md` Arah (verbatim §1) dan Turunan v2 | empat subagen riset, koordinator | berjalan |
| C. Dokumen dan repo | Eksekusi rencana konsolidasi 23 Sep (PRD gabungan, `docs/fitur/`, GR-02 untuk DK-25), README proyek, `CONTRIBUTING.md` di root, penataan root, pelindung NgodingPakeAI (T9), `.gitignore` | subagen dokumen; koordinator memegang HANDOVER | segera, paralel |
| D. Fondasi desain | Token gerak, material, elevasi; komponen dasar (bilah tab, lembar bawah, kartu, segmen, toast, skeleton, grafik) | koordinator atau satu subagen | setelah B |
| E. Halaman per tab | Beranda, Jelajah, Trip, Mendaki, Progres, Profil, lapisan Community | subagen per tab | setelah D |
| F. Fitur DK-07 tanpa dependensi | Catatan pribadi, tautan bagikan, detail rencana, pengingat email, target pribadi, catat pendakian manual; banding maks 3; Kelola Peran Pengguna (features/08) | subagen backend + halaman terkait | paralel dengan E, migrasi hanya diuji lokal dan CI |
| G. Verifikasi dan serah terima | Suite, tsc, build, audit peramban 360 px dan desktop terang/gelap, audit aksesibilitas, HANDOVER, memori | koordinator | akhir setiap gelombang |

Setiap aliran ditutup dengan bukti (R-023): nama test, keluaran perintah, tangkapan
peramban. Aliran E dan F tidak menyatakan selesai untuk fitur yang butuh data lapangan
(peta jalur, pos berkoordinat) tanpa menyebut bahwa datanya belum ada (T2).

## 8. Butuh izin atau keputusan pemilik (tidak dikerjakan sebelum ada jawaban)

| # | Hal | Mengapa butuh pemilik | Usulan agen |
|---|---|---|---|
| P1 | Menjalankan migrasi pada SQLite lokal agar seluruh alur dapat diuji di peramban | `.claude/settings.json` menolak `php artisan migrate`; HANDOVER mewajibkan izin | Izinkan migrasi hanya ke berkas SQLite lokal, atau jalankan sendiri lewat `!` |
| P2 | Menjalankan migrasi baru DK-28 di Supabase | R-019, DK-23 | Setelah uji PostgreSQL di CI hijau |
| P3 | Dependensi masuk Google dan passkey (DK-07) | DK-24 nol dependensi di fase 1 | Ajukan `laravel/socialite` dan satu pustaka WebAuthn setelah riset 04 |
| P4 | Kode verifikasi WhatsApp/SMS (DK-08) | Penyedia berbayar dan data nomor telepon (UU PDP) | Tunda; email dulu |
| P5 | Impor geometri OSM di konsol admin vs DK-22 | Kewajiban share-alike ODbL | Pilih: nonaktifkan impor OSM, atau terima kewajiban ODbL dengan atribusi dan rilis turunan |
| P6 | Golden trail pertama dan sumber datanya | DK-22 "golden trail pertama" masih terbuka | Satu jalur dengan GPX resmi atau izin pengelola |
| P7 | Kriteria features/ yang bertabrakan dengan aturan kanonis (T11) | R-005, R-014 menang; pemilik perlu tahu | Banding memakai label, bukan skor; kesiapan memakai kategori dan hitungan butir, bukan angka |
| P8 | Tautan bagikan dibuka tanpa akun (features/03) | Paparan data tanpa autentikasi | Tautan bertoken acak, hanya data publik jalur, tanpa catatan pribadi, dapat dicabut dan kedaluwarsa |
| P9 | IDOR menjawab 403 atau 404; lupa sandi membocorkan keberadaan akun; MustVerifyEmail belum ditegakkan | Perilaku keamanan yang terlihat pengguna | 404; jawaban "terkirim" yang sama; tegakkan verifikasi setelah alur email siap |
| P10 | SSR Inertia | Butuh Node di server produksi (DK-12 Vercel) | Tunda sampai halaman publik React membutuhkannya |
| P11 | Melepas paket livewire/livewire, livewire/volt, laravel/breeze | Perubahan dependensi | Lepas setelah suite hijau tanpa rujukan |
| P12 | Commit dan push | R-018, HANDOVER | Tinjau diff lalu izinkan |

## 9. Risiko

- **Cakupan sangat besar.** Dikerjakan bergelombang dengan HANDOVER diperbarui di akhir
  setiap gelombang, sehingga habisnya token tidak menghapus kemajuan.
- **Data lapangan kosong (T2).** UI baru tetap terasa kosong untuk peta dan mode
  pendakian sampai golden trail ada (P6). Keadaan kosong dirancang sebagai tahap.
- **Perangkat kelas bawah.** Efek kaca dan gerak diuji di emulasi 360 px dengan CPU
  diperlambat; fallback padat selalu ada.
- **Konflik penulisan paralel.** Setiap subagen mendapat daftar berkas miliknya; berkas
  bersama (layout, NavLinks, tipe bersama) dipegang koordinator.

## 10. Protokol tahan-sesi

1. `HANDOVER.md` §0 selalu memuat: arahan terakhir pemilik (verbatim), aliran kerja
   yang sedang berjalan beserta berkas miliknya, bukti terakhir (angka test, build),
   keputusan terbuka, dan satu blok "Tempelkan ini di terminal baru".
2. Spec ini dan rencana implementasinya adalah sumber urutan kerja; HANDOVER menunjuk
   ke sana, tidak menyalin isinya.
3. Memori agen (`MEMORY.md`) menyimpan hanya fakta lintas-sesi yang tidak ada di repo.
