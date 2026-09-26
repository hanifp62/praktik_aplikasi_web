# IMK, Pola Interaksi, dan Aksesibilitas untuk Memount

> **Tanggal:** 25 September 2026 · **Jenis:** riset, bukan keputusan · **Cakupan:** perencanaan di rumah dan pemakaian di lapangan
> **Kedudukan:** laporan jalur 03 dari empat laporan riset yang disintesis di `00-sintesis.md` (spec `docs/superpowers/specs/2026-09-25-memount-v2-design.md` §6)
> **Akses sumber:** seluruh sumber web diakses **25 September 2026**. Rujukan ditulis [S#]; tautan lengkapnya di §8.
> **Skill yang dipakai:** `ecc:deep-research` (alur riset, aturan sumber, pemisahan fakta dan inferensi), `ecc:search-first` (membaca repo dan riset terdahulu sebelum mencari di web), `ecc:accessibility` dan `ecc:frontend-a11y` (daftar periksa WCAG 2.2 dan pola ARIA). Hasilnya dibandingkan dengan skill proyek `antislop-ui`, `antislop-layoutmobile`, dan `antislop-human` (§0.3). `anthropic-skills:deep-research` tidak dipakai karena skill itu bekerja dengan subagen.

**Jawaban singkat untuk pertanyaan pemilik, "IMK-nya bagaimana":** Memount dipakai dalam dua konteks yang sangat berbeda. Di rumah, pengguna punya waktu, sinyal, dan dua tangan; tugasnya memilih dan bersiap. Di lapangan, pengguna lelah, kedinginan, silau, memakai satu tangan, dan tanpa sinyal; tugasnya tahu posisinya terhadap jalur dan pos berikutnya. IMK Memount berarti merancang dua mode itu dengan aturan berbeda, menyambungkannya lewat satu siklus trip yang terlihat di setiap tab, lalu mengukurnya dengan tugas nyata (§7), bukan dengan kesan visual.

---

## 0. Cara membaca

### 0.1 Label bukti

| Label | Arti |
|---|---|
| **[FAKTA-WEB S#]** | Dinyatakan oleh sumber S#. Dikutip atau diringkas tanpa ditambah |
| **[FAKTA-LOKAL]** | Dibaca langsung dari repositori, dengan path dan nomor baris atau nama simbol |
| **[FAKTA-BRIEF]** | Kata-kata pemilik yang diteruskan koordinator pada 25 Sep 2026 (verbatim juga di spec v2 §1) |
| **[INFERENSI]** | Simpulan peneliti dari fakta di atas. Bisa keliru, dan ditandai supaya dapat dibantah |
| **[REKOMENDASI]** | Usulan yang dapat diuji. Bukan keputusan |
| **[PERLU PERSETUJUAN]** | Menyentuh R-021: arah produk, arsitektur navigasi, arsitektur data, privasi, versi framework |
| **[OPEN ISSUE]** | Ketidaksesuaian antar-dokumen atau dokumen-kode yang harus diputuskan pemilik |

### 0.2 Batas riset

- **Tidak ada riset pengguna baru.** Semua angka target di §7 adalah target usulan. Menurut `PRODUCT.md` §"Hasil UX yang diukur", angka yang belum diuji tidak boleh dikutip sebagai hasil.
- **Kode berubah selama riset.** Pagi 25 Sep 2026, `resources/js/layouts/NavLinks.tsx` masih berisi lima permukaan lama. Beberapa jam kemudian berkas itu sudah berisi IA v2 (DK-25), dan halaman `pages/Track/Index.tsx` muncul. Berkas yang sedang dikerjakan agen lain dikutip dengan nama simbol; nomor barisnya bisa bergeser.
- **PRD yang dikutip adalah salinan kerja.** `PRD.md` di working tree berisi 415 baris (format NgodingPakeAI), sedangkan PRD kanonis v2.0 di HEAD berisi 3.670 baris [FAKTA-LOKAL `git show HEAD:PRD.md | wc -l`; spec v2 T5]. Rujukan "PRD.md:N" di laporan ini menunjuk salinan kerja.
- Situs m3.material.io dan Apple HIG dirender dengan JavaScript sehingga tidak terbaca alat pengambil. Isi Material diambil dari dokumentasi komponen resmi `material-components-android` [S16–S19]. Isi HIG diambil dari berkas data JSON resmi di developer.apple.com [S15, S20, S76, S77]. Keduanya bisa tertinggal dari situs utama.
- Angka yang hanya didapat dari cuplikan mesin pencari ditandai "(cuplikan)".
- Riset terdahulu tidak diulang. Mode gelap di bawah matahari dan lanskap perangkat Indonesia (Android 77,07% lalu lintas web, viewport terbanyak 360×800) sudah ada di `docs/riset/2026-09-23-konsolidasi/mode_gelap_perangkat.md` §4 dan §6 [S94]. Klaim HIG "strive for 7:1" dalam berkas itu sudah saya verifikasi ulang [S77]. Detail implementasi API peramban menjadi bagian laporan 04; laporan ini membahas akibat IMK-nya.

### 0.3 Dibandingkan dengan skill proyek

| Aspek | Skill antislop | Temuan riset | Yang perlu dicatat |
|---|---|---|---|
| Target sentuh | 44×44 px (`antislop-layoutmobile`) | WCAG 2.5.8 AA minimum 24 px [S65]; Apple 44 pt [S76]; Android 48 dp [S78]; NN/g minimal 1 cm, lebih besar bila pengguna bergerak [S79] | 44 px cukup untuk kendali umum. Kendali utama mode lapangan perlu 56–64 px (§4.3) |
| Navigasi | Tab bawah untuk tujuan utama; hamburger harus berlabel | Menyembunyikan navigasi utama memotong discoverability hampir separuh [S22] | Sejalan |
| Keadaan kosong dan galat | Sebutkan sebab dan satu aksi | Tiga pedoman keadaan kosong [S31]; pedoman pesan galat [S32] | Memount butuh jenis ketiga: **data belum ada (UNKNOWN)**, dengan menyebut pihak yang ditunggu |
| Gerak | Tujuan tertulis, tanpa loop, ikuti dial MOTION | 100–500 ms, umumnya 200–300 ms untuk perubahan besar [S33]; kurangi gerak bila diminta [S76] | Mendukung DK-26 (gerak yang menjelaskan), dengan batas di §3.11 |
| Grafik | "Charts without a question" | Deskripsi dua bagian dan tabel data [S72]; heuristik Chartability [S74] | Sejalan; tambahkan tabel data |
| Kontras | 4,5:1 dan 3:1 | HIG: "strive for a contrast ratio of 7:1, especially in small text" [S77] | 7:1 untuk info kritis mode lapangan (§4.2) |

---

## 1. Ringkasan eksekutif

1. **Rasa "CRUD" datang dari struktur, bukan dari kurangnya animasi.** [INFERENSI] Sampai pagi ini navigasi dibagi per kata benda (Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar), Beranda tidak ada di bilah bawah, dan mode pendakian tersembunyi di dalam detail trip. IA v2 (DK-25) memperbaiki kerangkanya, tetapi kerangka saja tidak cukup. Data lapangan hampir seluruhnya kosong (0 jalur bergeometri, 0 pos berkoordinat, semua status `UNKNOWN` per 20 Sep 2026 [FAKTA-LOKAL `docs/ARCHITECTURE.md`, "Yang belum ada"]), jadi banyak layar tetap berakhir sebagai daftar atau kotak kosong. Yang dibutuhkan: satu siklus trip yang menyambung antar-tab, satu langkah berikutnya per layar, umpan balik instan, keadaan UNKNOWN yang informatif, dan mode lapangan yang tetap berjalan tanpa sinyal.
2. **IA v2 sejalan dengan pedoman platform.** Susunannya Beranda, Jelajah, Trip, Mendaki, Progres, ditambah Profil dan Komunitas lintas-halaman. Pedoman: 3–5 tujuan di bilah bawah [S16], label teks wajib ada [S15, S25], dan tab tidak disembunyikan atau dinonaktifkan meski isinya kosong [S15]. IA ini tercatat sebagai DK-25 di spec v2 dan sudah diterapkan di `NavLinks.tsx` (`permukaan`). DESIGN.md:124 dan PRD (GR-02) belum diperbarui.
3. **Tab Mendaki paling berisiko.** Posisi dikirim ke server untuk menentukan pos berikutnya [FAKTA-LOKAL `components/trips/usePendakian.ts:27-45`]. Service worker sengaja tidak menyimpan isi halaman [FAKTA-LOKAL `resources/views/sw.blade.php:6-14`]. Peramban juga dapat berhenti memberi lokasi saat layar mati [S40, S41]. Di gunung tanpa sinyal, fungsi inti tab ini gagal. Usulan: "paket lapangan" yang diunduh sebelum berangkat, pos berikutnya dihitung di perangkat (sejalan dengan asumsi A4 spec v2), dan data yang cepat berubah ditandai "terakhir diketahui" [PERLU PERSETUJUAN].
4. **Jarak ke pos dihitung garis lurus**, tetapi ditulis "Sekitar X dari posisi Anda". Penghitungnya haversine [FAKTA-LOKAL `app/Services/Trips/HikeCheckpointResolver.php:124-127`], teksnya di `pages/Trips/Hike.tsx:51`. Di jalur berkelok, jarak garis lurus lebih pendek dari jarak tempuh. Radius tiba di pos 75 m (`config/hiking.php:57`) juga tidak memperhitungkan akurasi GPS, padahal akurasi Geolocation adalah radius kepercayaan 95% [S37] dan ponsel di bawah kanopi hutan meleset 4,56–14,59 m DRMS [S39].
5. **Komunikasi risiko memakai kategori yang punya definisi, bukan angka peluang.** Peluang satu kejadian ditafsirkan publik dengan cara yang saling bertentangan [S46]. Istilah peluang verbal juga dibaca sangat beragam, bahkan setelah pembaca diberi tabel konversinya [S48]. PAIR menyarankan kategori untuk menyatakan keyakinan [S54], dan skala bahaya longsoran salju memakai lima kategori beserta saran perjalanan [S47]. Semua ini mendukung R-004/R-005, dengan syarat setiap label punya definisi dan saran tindakan. Untuk besaran terukur seperti akurasi GPS, rentang angka hanya sedikit menurunkan kepercayaan pada angkanya dan tidak menurunkan kepercayaan pada sumbernya [S49].
6. **Penjelasan Route Fit bertujuan efektivitas dan transparansi, bukan persuasi** [S55]. Terapkan HAX G11 (jelaskan alasan), G9 (koreksi mudah), G16 (tunjukkan akibat tindakan), dan G17 (kendali global) [S53].
7. **Progres tanpa kompetisi tetap memotivasi.** Grafik performa dan lencana memenuhi kebutuhan kompetensi; rekan satu tim memenuhi kebutuhan keterhubungan [S57, cuplikan]. Hindari *streak*: menampilkan streak yang putus menurunkan keterlibatan berikutnya [S58], dan mendaki bukan kegiatan harian.
8. **WCAG 2.2 tetap standarnya** (edisi 12 Des 2024 [S61]). WCAG 3 masih Working Draft 10 Sep 2026, dan W3C menyarankan memenuhi 2.2 sekarang [S62, S63]. Kriteria baru yang paling mengena Memount: 2.4.11 (fokus tertutup bilah tab atau sheet), 2.5.7 (menyeret peta atau sheet), 2.5.8, 3.3.7, dan 3.3.8 [S60]. Chrome 146 (10 Mar 2026) mendukung `<meta name="text-scale">`, jadi ukuran huruf OS kini dapat dihormati halaman web [S80].
9. **Beberapa ketidaksesuaian dokumen memengaruhi UX langsung** (§1.1). Contoh: PRD F-08 "skor kesiapan" bertentangan dengan larangan skor ke klien; teks mode pendakian memakai "checkpoint" padahal istilah bakunya "pos".
10. **Metode evaluasi:** tree test untuk label IA baru, cognitive walkthrough untuk alur Mendaki dan "Kenapa?", uji 5 pengguna berulang dengan SEQ per tugas, SUS versi Indonesia yang tervalidasi [S88], dan uji lapangan di lokasi berisiko rendah. Protokol perlu ditambah TalkBack karena mayoritas lalu lintas berasal dari Android [S94].

### 1.1 Ketidaksesuaian yang ditemukan [OPEN ISSUE]

| # | Yang bertentangan | Bukti | Dampak UX | Usulan |
|---|---|---|---|---|
| O-1 | IA v2 lawan navigasi terdokumentasi | DK-25 di spec v2 §4-§5 dan `NavLinks.tsx` (`permukaan`) lawan DESIGN.md:124-131 yang masih menulis lima permukaan lama | Perancang yang membaca DESIGN.md membangun IA lama | Jalankan GR-02, lalu perbarui DESIGN.md bagian "Navigasi dan Jelajah" |
| O-2 | "Skor kesiapan" dan "skor kecocokan" di tabel banding, lawan larangan skor ke klien | PRD.md:45 (F-08), PRD.md:88; `docs/ARCHITECTURE.md` aturan 2; PRODUCT.md §Positioning (BR-09); spec v2 T11/P7 | Tim dapat menampilkan angka, dan itu melanggar R-005 | Ikuti usulan P7: banding memakai label, kesiapan memakai kategori dan hitungan butir |
| O-3 | Batas banding 3 lawan 5 jalur | DK-29 menetapkan 3; PRODUCT.md:195 masih "maksimum lima"; `ConsiderationService::BATAS` 5 (spec v2 T4) | Tabel ponsel dirancang untuk jumlah kolom yang salah | Selesaikan DK-29 di PRODUCT.md dan kode |
| O-4 | Hierarki otoritas | PRD.md:28 "Resmi → Admin → Pakar → Komunitas"; R-009 "OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI" | Label sumber di UI berbeda antar-layar | Samakan kosakata label sumber dengan R-009, lalu putuskan kedudukan "Pakar" |
| O-5 | PWA luring lawan kebijakan service worker | PRD.md §7 "tetap bisa dibaca saat sinyal hilang" lawan sw.blade.php:6-14 "shell boleh disimpan, isi tidak" | Tab Mendaki tidak berguna di gunung | Paket lapangan (§4.7) [PERLU PERSETUJUAN] |
| O-6 | "checkpoint" di teks pengguna | PRODUCT.md:97 menetapkan "pos"; `pages/Trips/Hike.tsx` baris 48, 76 (label aria peta), 88, 92, 94 masih "checkpoint" | Heuristik #2 dan #4 | Ganti menjadi "Pos berikutnya", "Daftar pos", "Peta jalur dan pos" |
| O-7 | Ledger migrasi basi | HANDOVER.md §0 masih mencatat Trip, Progres, dan lainnya sebagai Livewire; `resources/js/pages/` sudah berisi Trips/, Track/, Community/, Admin/ versi React | Uji dan audit bisa menyasar halaman yang salah | Perbarui ledger |

---

## 2. Prinsip IMK dan dampaknya pada layar Memount

Kolom "Inti" berisi [FAKTA-WEB] dari sumber yang disebut. Kolom "Dampak" berisi [REKOMENDASI] peneliti, kecuali bagian yang menunjuk berkas repo ([FAKTA-LOKAL]). Kolom "Uji" menunjuk kriteria penerimaan di §6 dan §7.

### 2.1 Sepuluh heuristik Nielsen [S1]

| Heuristik | Inti (kutipan S1) | Dampak konkret di Memount | Uji |
|---|---|---|---|
| 1 Keterlihatan status sistem | "keep users informed about what is going on, through appropriate feedback within a reasonable amount of time" | Mendaki: umur posisi ("diperbarui 2 menit lalu"), akurasi (±m), antrean sinkron ("12 titik belum terkirim"). Jelajah: jumlah hasil diumumkan saat menyaring. Trip: tahap trip. Keadaan luring selalu terlihat | K-2, E-2, GN-6 |
| 2 Kecocokan dengan dunia nyata | "speak the users' language … rather than internal jargon" | "Pos", bukan "checkpoint" (O-6). "Elevasi naik/turun", bukan "elevation gain". Waktu dalam zona gunung dengan WIB/WITA/WIT. Label tab berbahasa Indonesia | K-1, TB-2 |
| 3 Kendali dan kebebasan | "a clearly marked 'emergency exit'" | "Batalkan" untuk simpan dan centang. Back menutup sheet. "Kecilkan" untuk mode lapangan. Keluar dari alur trip tanpa kehilangan isian | E-4, BS-5 |
| 4 Konsistensi dan standar | "Follow platform and industry conventions" | Satu istilah per konsep ("Trip" di tab dan Beranda sudah seragam; "pos" belum, O-6). Urutan tab tetap. Tab bawah dan Back berperilaku seperti aplikasi lain | TB-7 |
| 5 Pencegahan galat | "carefully prevent problems from occurring in the first place" | Tanggal di luar jendela izin ditandai saat dipilih. "Selesai mendaki" lewat lembar ringkasan. Peringatan (bukan blokir) bila paket lapangan belum diunduh | T-5, K-7 |
| 6 Mengenali, bukan mengingat | "should not have to remember information from one part of the interface to another" | Cek kesiapan menampilkan butir persiapan yang belum beres di tempat. Tabel banding berdampingan. Penjelasan kecocokan menampilkan isian profil yang dipakai | T-6, J-3 |
| 7 Fleksibilitas dan efisiensi | "Shortcuts — hidden from novice users" | Beranda memberi jalan pintas sesuai keadaan. Tautan langsung ke trip. Penjelasan panjang terlipat untuk pengguna berpengalaman | B-1 |
| 8 Estetika dan minimalis | "should not contain information that is irrelevant or rarely needed" | Mode lapangan hanya menampilkan info keputusan. Beranda punya satu kartu utama. Kartu jalur memuat paling banyak empat angka | K-1, B-1 |
| 9 Membantu pulih dari galat | "plain language … constructively suggest a solution" | Galat GPS menyebut caranya (periksa izin lokasi, pindah ke tempat terbuka). Galat luring menyebut apa yang masih bisa dipakai | K-8, FM-2 |
| 10 Bantuan dan dokumentasi | "may be necessary to provide documentation" | "Kenapa?" di samping label. Glosarium istilah pendakian. Letak Bantuan konsisten (WCAG 3.2.6) | GN-8 |

### 2.2 Delapan aturan emas Shneiderman [S2]: yang tidak tercakup Nielsen

| Aturan | Inti | Dampak di Memount | Uji |
|---|---|---|---|
| Universal usability | "design for plasticity" | Ponsel 360 px kelas bawah, teks OS 200%, pemula dan berpengalaman, TalkBack | GN-3, GN-7 |
| Informative feedback | "For frequent and minor actions, the response can be modest" | Centang persiapan cukup dengan perubahan ikon dan teks, tanpa toast. Aksi jarang dan besar (mulai atau selesai mendaki) mendapat ringkasan jelas | T-4 |
| Dialog yang menutup | "a beginning, middle, and end" | Siklus trip punya akhir: ringkasan pendakian → catat → lapor (opsional) → progres | K-9, P-1 |
| Mudah dibalik | "relieves anxiety, since users know that errors can be undone" | Undo didahulukan daripada dialog konfirmasi [S29] | E-4 |
| Pengguna memegang kendali | "the interface responds to their actions" | Sistem tidak menandai "tiba di pos" bila akurasi lebih buruk dari radius; ia bertanya kepada pengguna | K-4 |

### 2.3 Prinsip Norman [S3]

| Prinsip | Dampak di Memount | Uji |
|---|---|---|
| Discoverability dan signifier | Garis jalur di peta diberi petunjuk "ketuk garis untuk melihat ringkasan". Pegangan sheet berupa tombol berlabel | E-5, BS-1 |
| Pemetaan (mapping) | Memilih jalur di daftar menyorotnya di peta. Titik di grafik elevasi terhubung dengan posisi di peta | E-3, EL-4 |
| Batasan (constraints) | Pemilih tanggal menandai tanggal di luar jendela izin. Jumlah jalur yang dibanding dibatasi 3 (DK-29) | T-5 |
| Umpan balik | Setiap ketukan menampilkan keadaan tekan dalam 0,1 s [S10] | GN-6 |
| Model konseptual | Memount adalah pendamping, bukan otoritas. Status resmi milik pengelola; label kecocokan hasil aturan yang dapat dijelaskan | J-2 |

### 2.4 Hukum UX

| Hukum | Inti (sumber) | Dampak di Memount |
|---|---|---|
| Fitts | Target besar dan dekat lebih cepat. Di layar sentuh, target di tepi layar justru lebih lama dijangkau [S4] | Tombol utama lebar penuh di sepertiga bawah, tidak menempel tepi. "Perbarui posisi" setinggi 56–64 px |
| Hick | Waktu memutuskan naik seiring jumlah dan kerumitan pilihan; "Be careful not to simplify to the point of abstraction" [S5] | Hasil kecocokan hanya tiga label. Paling banyak empat saringan inti terlihat, sisanya terlipat. Mode lapangan paling banyak tiga aksi |
| Miller | 7±2 dengan peringatan "Don't use the 'magical number seven' to justify unnecessary design limitations" [S6] | Kelompokkan angka jalur (jarak · waktu · elevasi). Informasi keselamatan tidak dipotong demi angka tujuh |
| Jakob | Pengguna membawa harapan dari produk yang sudah dikenal [S7] | Tab bawah, sakelar Peta/Daftar, dan sheet pratinjau seperti aplikasi peta umum. Tidak ada gestur baru |
| Tesler | Kompleksitas inti ditanggung sistem atau pengguna [S8] | Sistem menanggung perhitungan kecocokan, kesegaran data, dan zona waktu. Pengguna melihat label dan alasan; detail tersedia bila diminta |
| Batas waktu respons dan Doherty | 0,1 s terasa instan, 1 s alur pikir tidak putus, 10 s batas perhatian [S10]; Doherty < 400 ms [S9] | Umpan balik tekan ≤ 100 ms. Pindah halaman ≤ 1 s, atau tampilkan skeleton. Proses > 10 s (unggah GPX, kirim jejak, unduh paket) memakai bilah kemajuan |
| Peak-end | Momen puncak dan akhir mendominasi ingatan [S11] | Akhir yang baik adalah ringkasan "sudah turun", bukan hanya puncak. Puncak negatif yang paling mungkin: galat saat luring, jadi rancang itu lebih dulu |
| Goal-gradient | Makin dekat tujuan, makin cepat bergerak. lawsofux menyarankan "artificial progress" [S12] | Pakai kemajuan **nyata** (butir persiapan yang dikonfirmasi). Tolak kemajuan semu (Kivetz dkk. 2006, "illusionary goal progress" [S12]) karena bertentangan dengan R-005 dan R-014 |
| Zeigarnik → Ovsiankina | Meta-analisis 2025: tidak ada keunggulan ingatan untuk tugas yang belum selesai, tetapi ada kecenderungan melanjutkannya [S14]; halaman lawsofux belum mencatat ini [S13] | Tawarkan "Lanjutkan rencana" di Beranda. Jangan mengandalkan notifikasi yang memanfaatkan rasa "mengganjal" |

---

## 3. Pola navigasi dan interaksi

### 3.1 IA v2 dari sudut IMK: satu pertanyaan per tab

[FAKTA-BRIEF] Pemilik: "Community menjadi cross-cutting layer, bukan primary tab … Dengan ini kita mempertahankan semua fitur tanpa membuat navigasi mobile berantakan." Pemetaan rute ada di spec v2 §5; tabel di bawah menambahkan sisi IMK-nya. Kolom "Risiko" adalah [INFERENSI], kolom "Rekomendasi" adalah [REKOMENDASI].

| Tab | Pertanyaan pengguna yang dijawab | Satu aksi utama (berubah menurut keadaan) | Risiko IMK utama | Rekomendasi |
|---|---|---|---|---|
| Beranda | "Apa langkah saya berikutnya?" | Lanjutkan pendakian / Cek kesiapan / Buat rencana / Lengkapi profil | Menjadi dasbor generik berisi empat kartu statistik (`antislop-ui`, "Default Dashboard Shell") | Pertahankan mesin keadaan di `pages/Dashboard.tsx` (`keadaan`: profil, berjalan, terdekat, mulai) dan jadikan satu kartu utama; bagian lain di bawahnya |
| Jelajah | "Jalur mana yang cocok untuk saya?" | Buka ringkasan jalur | Peta tanpa jalur (data kosong) terasa rusak | Daftar menjadi padanan penuh peta; jalur tanpa geometri tampil di daftar sebagai "menunggu data", tidak di peta |
| Trip | "Apa yang masih kurang untuk trip ini?" | Langkah trip berikutnya | Banyak halaman terpisah (Fit, Banding, Rencana, Persiapan, Cek) terasa seperti formulir | Satu ruang kerja per trip dengan penanda langkah; banding dari simpanan |
| Mendaki | "Di mana saya, dan pos berikutnya di mana?" | Perbarui posisi / Mulai mendaki | Gagal tanpa sinyal (§4.7) | Paket lapangan; mode lapangan layar penuh |
| Progres | "Bagaimana perkembangan saya dibanding diri sendiri?" | Catat pendakian / Lihat ringkasan | Grafik tanpa pertanyaan; godaan papan peringkat | Grafik berjudul pertanyaan, tabel padanan, tanpa peringkat (R-015) |
| Profil (bukan tab) | "Data apa yang dipakai untuk menilai saya, dan bagaimana mengubahnya?" | Ubah pengalaman atau preferensi | Pengaturan tersebar | Hub profil; setiap isian menyebut pengaruhnya pada kecocokan (HAX G16) |

### 3.2 Bilah tab bawah

**Fakta.** [FAKTA-WEB S16] Bilah navigasi Material memuat tiga sampai lima tujuan; setiap tujuan berupa ikon dan label. [FAKTA-WEB S15] HIG: "Use a tab bar to support navigation, not to provide actions." Bilah tab tetap terlihat saat berpindah bagian, kecuali tertutup tampilan modal. Label dipakai, dengan satu kata bila memungkinkan. Lencana (badge) hanya untuk informasi penting. "Don't disable or hide tab bar buttons, even when their content is unavailable … If a section is empty, explain why." Tab bar menjaga keadaan navigasi di setiap bagian. [FAKTA-WEB S22] Navigasi tersembunyi memotong discoverability hampir separuh. Artikel lanjutannya membandingkan dua situs seluler: navigasi terlihat atau gabungan dipakai 89% peserta, navigasi tersembunyi 44% [S22b]. [FAKTA-WEB S25] Ikon navigasi memerlukan label teks yang selalu terlihat.

**Keadaan sekarang.** [FAKTA-LOKAL `NavLinks.tsx`, fungsi `TabBawah`, dibaca 25 Sep 2026] Tinggi tab 64 px (`h-16`). Tab aktif ditandai garis, tebal huruf, dan `aria-current`. Label berukuran `text-[0.65rem]` (10,4 px pada ukuran dasar 16 px) dengan `truncate`.

**Rekomendasi.**
1. [REKOMENDASI] Label satu kata berbahasa Indonesia seperti sekarang (Beranda, Jelajah, Trip, Mendaki, Progres), lalu uji dengan tree test (§7.8). Hindari "Lacak" untuk tab Mendaki: [INFERENSI] kata itu berkonotasi pelacakan orang, bertentangan dengan pesan privasi lokasi.
2. [REKOMENDASI] Naikkan label ke minimal 12 px (0,75rem) dan biarkan ikut skala teks OS (§6.7). Pada teks 200%, label boleh dua baris atau bilah boleh meninggi. Jangan memotong label dengan elipsis; label yang terpotong kehilangan makna navigasinya. [INFERENSI] 10,4 px adalah teks terkecil di aplikasi dan terletak di elemen yang paling sering dipakai.
3. [REKOMENDASI] Lencana hanya untuk hal yang menuntut tindakan: "Trip · 1 perlu tindakan" (jendela izin hampir tutup, status resmi jalur berubah). Jumlah konten baru tidak diberi lencana. Teks lencana masuk ke nama aksesibel.
4. [REKOMENDASI] Ingat halaman terakhir di setiap tab (HIG [S15]). [INFERENSI, perlu diuji] Mengetuk tab yang sedang aktif membawa ke akar tab dan ke atas halaman, sesuai kebiasaan aplikasi seluler umum.
5. [REKOMENDASI] Tab Mendaki selalu ada, termasuk saat pengguna belum punya trip. Isinya menjelaskan keadaannya (§3.3), sesuai HIG.
6. [REKOMENDASI] Bila bilah memakai efek tembus pandang (spec v2 §6.3), kontras label dihitung terhadap latar **terburuk** yang mungkin lewat di belakangnya, dengan latar padat sebagai cadangan. Kontras bukan diukur pada satu titik (`antislop-human`, "Text Over a Photo or Gradient").

### 3.3 Perilaku tab Mendaki: ada dan tidak ada pendakian aktif

**Keadaan sekarang.** [FAKTA-LOKAL `app/Http/Controllers/Trips/TrackController.php`] Bila ada trip berstatus berjalan, tab langsung membuka mode pendakian. Bila tidak ada, tab menampilkan sampai sepuluh trip yang direncanakan atau siap berangkat, terdekat lebih dulu. [FAKTA-LOKAL `components/trips/TataLetakPendakian.tsx`] Mode pendakian menyembunyikan navigasi utama "karena setiap tautan yang tidak dibutuhkan adalah sasaran salah sentuh". Ini sejalan dengan pengecualian HIG untuk tampilan modal [S15].

| Keadaan | Isi tab | Aksi utama | Bilah tab |
|---|---|---|---|
| **S0** Belum ada trip | Penjelasan singkat mode lapangan: apa yang bekerja tanpa sinyal dan apa yang tidak. Tanpa permintaan izin lokasi | "Cari jalur di Jelajah" | Tampil |
| **S1** Trip mendatang (sebelum hari H) | Daftar periksa paket lapangan: unduh paket (ukuran dan waktu unduh), uji izin lokasi selagi ada sinyal, catat nomor darurat pengelola | "Siapkan paket lapangan" | Tampil |
| **S2** Hari H atau di basecamp | Pemeriksaan sebelum berangkat: status resmi terakhir dengan waktunya, versi paket, sisa butir kesiapan | "Mulai mendaki" (≥ 56 px) | Tampil |
| **S3** Sedang mendaki | Mode lapangan layar penuh (§4.3) | "Perbarui posisi" | Disembunyikan; ada "Kecilkan" |
| **S3b** Mendaki, dikecilkan | Strip tetap di atas bilah tab: "Sedang mendaki · Pos 3 dari 7 · Kembali". Tab Mendaki diberi penanda keadaan | "Kembali ke mode lapangan" | Tampil |
| **S4** Selesai, data belum terkirim | Ringkasan pendakian, "n titik jejak belum terkirim", "Laporkan kondisi jalur" (opsional) | "Catat pendakian" | Tampil |
| **S5** Luring di keadaan mana pun | Semua dari paket lapangan dengan penanda "Luring · data per HH.MM WIB" dan antrean kiriman | Sesuai keadaan | Tampil |

- [REKOMENDASI] Di S3, tombol Back peramban atau sistem sebaiknya **mengecilkan** mode lapangan, tidak mengakhiri pendakian. Mengakhiri pendakian hanya lewat "Selesai mendaki", yang membuka lembar ringkasan (Shneiderman: dialog yang menutup).
- [REKOMENDASI] Pengaman untuk pendakian "berjalan" yang terlupa: bila status berjalan sudah melewati tanggal rencana lebih dari satu hari, tab tidak langsung membuka mode lapangan tetapi bertanya "Lanjutkan" atau "Tandai selesai". [INFERENSI] Tanpa pengaman ini, pengguna yang lupa menekan selesai akan terus dilempar ke mode lapangan.
- [PERLU PERSETUJUAN] "Mulai mendaki tanpa trip" (mulai cepat dari halaman jalur) tidak direkomendasikan sebagai bawaan: fitur itu melewati cek kesiapan, padahal cek kesiapan adalah inti produk.

### 3.4 Komunitas sebagai lapisan lintas-halaman

[FAKTA-BRIEF] Contoh dari pemilik: "Explore → Trail → Community Reports", "Trail Detail → Community", "Progress → Hike → Share Report". Aturan yang mengikat: R-009, R-010, R-011, dan DESIGN.md (biru komunitas).

| Tempat | Bentuk | Aturan IMK |
|---|---|---|
| Beranda | "Kabar jalur Anda": paling banyak tiga butir, untuk jalur tersimpan dan jalur trip; status resmi lebih dulu | Setiap butir memuat sumber, jenisnya, dan waktu |
| Jelajah (kartu dan ringkasan) | "2 laporan pendaki · 3 hari lalu" | Laporan tidak mengubah warna garis jalur, dan warna itu tidak menyiratkan status resmi |
| Detail jalur | Urutan: Status resmi → Prakiraan area → Laporan pendaki (termoderasi), lalu ajakan "Laporkan kondisi" | Resmi dan komunitas terpisah secara visual **dan** tekstual (label "Laporan pendaki", bukan warna saja) |
| Trip | Kabar jalur trip; ajakan melapor setelah turun | — |
| Mendaki | "Laporkan kondisi" di ringkasan akhir; saat luring laporan disimpan dalam antrean | Tidak ada ajakan melapor selama berjalan kecuali lewat tombol eksplisit |
| Progres | "Bagikan laporan" dari ringkasan pendakian; status verifikasi laporan saya | Tanpa peringkat kontributor (R-015) |
| Profil | Laporan saya, pengaturan privasi foto dan lokasi | — |

[REKOMENDASI] Bila kelak ada ringkasan otomatis (AI) atas laporan komunitas, ia diberi label "Ringkasan otomatis dari n laporan", tautan ke laporannya, dan diletakkan di bawah status resmi (R-011).

### 3.5 Profil, bantuan, dan bilah atas

- [REKOMENDASI] Profil dibuka dari tombol avatar di kanan atas, dengan nama aksesibel "Profil". Di layar lebar, teks "Profil" terlihat. Letaknya mengikuti kebiasaan aplikasi lain (Jakob [S7]).
- [FAKTA-WEB S67] WCAG 3.2.6 (A): bila mekanisme bantuan muncul di beberapa halaman, urutan relatifnya harus sama. Kriteria ini tidak mewajibkan adanya bantuan. [REKOMENDASI] Letakkan "Bantuan" di menu Profil dan di kaki setiap akar tab, selalu di urutan yang sama.
- [REKOMENDASI] Bilah atas: judul halaman (h1) dan satu baris konteks. Di halaman detail: tautan naik ke induk ("← Jelajah"), lalu aksi berlabel teks ("Simpan", "Bagikan").

### 3.6 Kembali (Back) dan tautan dalam

- [FAKTA-WEB S23] Android: "Deep linking simulates manual navigation". Tautan dalam membangun *back stack* sintetis berisi tujuan awal dan tujuan tautan.
- [REKOMENDASI] Halaman yang dibuka dari tautan bagikan (misalnya WhatsApp) menampilkan tautan naik ke induknya, karena riwayat peramban tidak memiliki halaman Memount sebelumnya. Back peramban tetap bekerja seperti biasa.
- [FAKTA-WEB S21] NN/g: "Allow the use of Back for dismissing the bottom sheet." [REKOMENDASI] Back menutup sheet atau dialog yang terbuka lebih dulu, baru kemudian berpindah halaman.

### 3.7 Lembar bawah (bottom sheet)

**Fakta.** [FAKTA-WEB S20] HIG: detent *medium* kira-kira setengah tinggi dan *large* penuh; medium dianjurkan untuk *progressive disclosure*. Grabber dapat diseret dan juga diketuk untuk berpindah detent, serta bekerja dengan VoiceOver. Sheet dapat diusap untuk ditutup, dengan tombol Cancel/Done. Sheet nonmodal memungkinkan interaksi dengan tampilan induk. [FAKTA-WEB S19] Material membedakan sheet standar (hidup berdampingan dengan isi) dan modal (menghalangi isi). Pegangan seret menerima perintah aksesibilitas untuk membuka dan menutup, bisa diketuk untuk berpindah keadaan, dan diketuk dua kali untuk menyembunyikan. [FAKTA-WEB S21] NN/g: dukung Back, sediakan tombol Tutup yang terlihat, jangan menumpuk sheet, dan jangan memakai sheet untuk alur halaman ke halaman. [FAKTA-WEB S64] WCAG 2.5.7 (AA): setiap fungsi yang memakai gerakan seret harus dapat dilakukan dengan satu penunjuk tanpa seret.

| Dipakai untuk | Jenis | Detent | Alternatif tanpa seret |
|---|---|---|---|
| Ringkasan jalur saat garis di peta diketuk | Nonmodal | Intip (nama, label kecocokan, jarak, elevasi) dan setengah | Pegangan berupa tombol dengan `aria-expanded`; tombol "Tutup" |
| Saring hasil Jelajah | Modal | Penuh di ponsel | "Terapkan (n hasil)" dan "Tutup"; Esc |
| "Kenapa?" di label kecocokan | Modal | Setengah, bisa penuh | Tombol "Tutup"; Back |
| Selesai mendaki | Modal | Setengah | Dua tombol besar: "Selesai" dan "Lanjut mendaki" |

[REKOMENDASI] Tidak memakai sheet untuk membuat trip, cek kesiapan, atau formulir panjang; ketiganya halaman penuh (NN/g [S21]).

### 3.8 Tombol aksi mengambang (FAB) dan aksi utama

- [FAKTA-WEB S18] FAB "represents the primary action of a screen". [FAKTA-WEB S25] Ikon tanpa label jarang dipahami.
- [REKOMENDASI] Tidak memakai FAB global. Aksi utama setiap layar berupa tombol berlabel di dalam isi, atau bilah aksi tempel tepat di atas bilah tab di halaman detail ("Buat trip dari jalur ini"). Bilah tempel wajib memakai `scroll-padding-bottom` agar elemen yang difokus tidak tertutup (WCAG 2.4.11 [S66]).

### 3.9 Layar lebar

- [FAKTA-WEB S17] Navigation rail Material untuk perangkat ukuran menengah, 3–7 tujuan; rail yang diperluas menggantikan drawer. [FAKTA-WEB S15] HIG menyarankan sidebar atau tab bar yang dapat berubah menjadi sidebar untuk struktur yang kompleks.
- [FAKTA-LOKAL] Spec v2 §5: di layar lebar lima tab yang sama tampil di navigasi atas, Profil sebagai menu avatar.
- [INFERENSI] Navigasi atas lazim di web desktop (Jakob), jadi keputusan spec itu dapat dipertahankan. Yang perlu diuji adalah tablet potret (sekitar 600–840 px): di sana navigasi atas jauh dari ibu jari, sehingga bilah bawah atau rail lebih mudah dijangkau. Apa pun pilihannya, urutan dan label tab harus identik di semua ukuran (WCAG 3.2.3 Consistent Navigation).

### 3.10 Zona ibu jari dan pemakaian satu tangan

- [FAKTA-WEB S24] Hoober (2013): 1.333 pengamatan, 780 di antaranya menyentuh layar. Satu tangan 49%, *cradled* 36%, dua tangan 15%. Cara memegang berganti "sometimes every few seconds". [FAKTA-WEB S4] Di layar sentuh, target di tepi layar lebih lama dijangkau.
- [REKOMENDASI] Aksi utama di separuh bawah layar, tidak menempel tepi. Aksi merusak tidak bersebelahan dengan bilah tab. Tidak ada aksi kritis yang hanya tersedia di pojok atas. Uji setiap layar utama dalam tiga cara memegang tersebut.

### 3.11 Pola yang membuat aplikasi terasa hidup, bukan CRUD

**Diagnosis.** [INFERENSI] Aplikasi terasa "CRUD" ketika (a) setiap layar menampilkan satu entitas dan tombol simpan; (b) tindakan tidak segera terlihat hasilnya; (c) layar tidak tahu posisi pengguna dalam siklus trip; (d) kekosongan tampil sebagai kotak kosong. Animasi tidak menyelesaikan satu pun dari keempatnya. Yang menyelesaikannya adalah umpan balik, kesinambungan, dan kesadaran konteks.

| Pola | Dipakai di Memount | Tidak dipakai untuk | Sumber |
|---|---|---|---|
| Umpan balik tekan ≤ 100 ms | Semua tombol dan tab | — | [S10], [S33] |
| Optimistic UI | Simpan jalur, centang persiapan, tambah ke banding | Status resmi, hasil cek kesiapan, publikasi, kiriman jejak. Syarat pola ini: tingkat gagal server 1–3% dan respons ≤ 2 s | [S28] |
| Skeleton | Muat halaman penuh 1–10 s (Jelajah, detail jalur) | Muat < 1 s atau > 10 s | [S26] |
| Spinner | Modul tunggal (cuaca, kabar) | Muat halaman penuh | [S26], [S27] |
| Bilah kemajuan | Proses > 10 s: unggah GPX, kirim jejak, unduh paket lapangan | — | [S27] |
| Mikro-interaksi | Konfirmasi butir persiapan, simpan, tiba di pos | Hiasan dan loop | [S35] |
| Undo | Hapus dari simpanan, batal centang, keluarkan dari banding | Hapus akun (tidak dapat dibalik; tetap konfirmasi dan autentikasi ulang) | [S29] |
| Progressive disclosure | "Kenapa?", rincian faktor, data teknis jalur | Informasi keselamatan inti tidak pernah dilipat. Paling banyak dua tingkat | [S30] |
| Langkah berikutnya | Beranda dan ruang kerja trip | — | [INFERENSI dari `pages/Dashboard.tsx`] |
| Kesegaran data | Setiap fakta yang cepat berubah | — | [S43], [S52] |

**Gerak** (mendukung DK-26, spec v2 §6). [FAKTA-WEB S33] Umpan balik sederhana sekitar 100 ms; perubahan layar besar 200–300 ms; pada 500 ms animasi mulai terasa lambat. [FAKTA-WEB S76] Saat Reduce Motion aktif, kurangi animasi otomatis dan berulang, termasuk zoom, scaling, dan gerak periferal. [FAKTA-WEB S75] `prefers-reduced-motion` tersedia luas sejak Januari 2020. [REKOMENDASI] Gerak dipakai untuk (1) kesinambungan ruang, misalnya sheet naik dari peta atau kartu yang terbuka menjadi detail; (2) umpan balik keadaan; (3) status proses. Tidak ada animasi masuk pada isi, tidak ada loop, dan di mode lapangan tidak ada gerak kecuali status. Saat `prefers-reduced-motion: reduce`, perpindahan diganti pudar singkat atau tanpa transisi, dan `flyTo` di peta diganti `jumpTo`.

**Keadaan kosong: tiga jenis.** [FAKTA-WEB S31] Keadaan kosong menjelaskan status sistem, memberi petunjuk belajar, dan menyediakan jalan langsung ke tugas kunci.

| Jenis | Contoh teks | Aksi |
|---|---|---|
| Pertama kali | "Belum ada jalur tersimpan. Simpan jalur dari Jelajah untuk membandingkannya di sini." | "Buka Jelajah" |
| Tersaring habis | "Tidak ada jalur dengan elevasi naik di bawah 500 m di Jawa Timur." | "Longgarkan: hapus saringan elevasi" |
| Data belum ada (khas Memount, R-006/R-007) | "Garis jalur ini belum tercatat. Menunggu data dari pengelola kawasan; peta akan muncul setelah kurator memverifikasinya." | "Lihat daftar pos" atau "Simpan untuk nanti" |

**Onboarding.** [FAKTA-WEB S34] NN/g menyarankan menghindari onboarding bila memungkinkan, tidak memakai tutorial kartu berderet, dan meminta personalisasi konten hanya bila penting, singkat, dan disertai alasan. [REKOMENDASI] Tanyakan hanya pengalaman dan tujuan yang dipakai Route Fit, sebutkan alasannya di setiap pertanyaan, dan tunjukkan akibatnya segera ("Dengan jawaban ini, 4 jalur berlabel Cocok"). Bila dilewati, label kecocokan tampil "Belum dinilai", bukan tebakan (R-006, R-007).

---

## 4. Konteks lapangan dan keselamatan

### 4.1 Hambatan situasional

[FAKTA-WEB S85] Faktor lingkungan menimbulkan *situationally-induced impairments and disabilities* (SIID) pada pemakaian ponsel. Merancang untuk SIID sama dengan merancang untuk disabilitas sementara.

| Kondisi di jalur | Bukti | Akibat desain |
|---|---|---|
| Dingin | Dingin menurunkan kinerja motorik halus, tetapi tidak menurunkan kewaspadaan [S84] | Target lebih besar, jarak antar-target lebih lebar, tanpa gestur presisi |
| Sarung tangan | Tidak ditemukan studi primer yang layak dikutip. NN/g menyarankan target yang jauh lebih besar untuk pemakaian sambil bergerak [S79] | [INFERENSI] Kendali utama 56–64 px; aksi penting dengan ketukan tunggal, tanpa seret atau tekan lama |
| Matahari | Polaritas terang umumnya sama atau lebih baik; tuasnya ukuran teks dan kontras [S94]; HIG: "strive for 7:1, especially in small text" [S77] | Tema terang sebagai dasar; info kritis ≥ 7:1 |
| Berjalan dan lelah | Target lebih besar untuk pemakaian bergerak [S79] | Paling banyak tiga aksi di layar; teks keputusan besar |
| Satu tangan | Pegangan berganti-ganti [S24] | Aksi di separuh bawah |
| Tanpa sinyal | "Tidak ada sinyal di hampir seluruh gunung Indonesia" [FAKTA-LOKAL PRODUCT.md:71] | Luring lebih dulu (§4.7) |
| Baterai | `enableHighAccuracy` dapat menambah konsumsi daya [S37]; Wake Lock menjaga layar menyala [S36] | Pengguna memilih; sistem menjelaskan akibatnya |

### 4.2 Keterbacaan di bawah matahari

- [FAKTA-WEB S94, dari NN/g] Tidak ada bukti mode gelap lebih terbaca di terik matahari. Pada teks kecil, polaritas terang umumnya sama atau lebih baik.
- [FAKTA-WEB S77] HIG: kontras minimum 4,5:1; untuk warna khusus "strive for a contrast ratio of 7:1, especially in small text".
- [REKOMENDASI] Mode lapangan: info kritis (nama pos berikutnya, jarak, status resmi, peringatan) ≥ 7:1 di kedua tema. Nama pos minimal 24 px, angka jarak minimal 28 px, teks pendukung minimal 16 px, dan tidak ada teks di bawah 14 px. [INFERENSI] Ukuran ini adalah titik awal, bukan hasil studi, dan harus diuji di bawah matahari siang (§7.8). Keadaan sekarang: catatan penting di `Hike.tsx` memakai `text-xs` (12 px) berwarna redup.

### 4.3 Spesifikasi mode lapangan

| Elemen | Spesifikasi | Dasar |
|---|---|---|
| Aksi utama | "Perbarui posisi" lebar penuh, tinggi 56–64 px (sekarang `min-h-14` = 56 px) | [S79], [S84], [INFERENSI] |
| Aksi lain | Paling banyak dua: "Tandai tiba di pos" dan "Selesai mendaki"; jarak antar-tombol ≥ 16 px | Hick [S5], NN/g spasi [S79] |
| Info keputusan | Pos berikutnya, jarak (rentang, §4.5), perkiraan waktu dengan sumbernya, umur dan akurasi posisi | H1 [S1], R-005 |
| Peta | Garis jalur, posisi dengan lingkaran akurasi, tombol + dan − | WCAG 2.5.1 [S70] |
| Elevasi | Profil kecil dengan penanda posisi dan kalimat ringkas ("sisa naik sekitar 300 m") | [S72] |
| Getar | Bukan satu-satunya umpan balik. Vibration API tidak Baseline [S83] | [INFERENSI] |
| Tema | Sakelar terang/gelap sekali ketuk (sudah ada: `ThemePicker compact`) | [S94] |

### 4.4 Wake Lock dan baterai

- [FAKTA-WEB S36] Screen Wake Lock mencegah layar meredup atau terkunci. Berstatus "Baseline 2025, newly available" sejak Maret 2025, dan hanya berjalan di konteks aman. Kunci dilepas otomatis saat dokumen tidak aktif dan harus diminta ulang pada `visibilitychange`. Sistem dapat melepasnya bila baterai terlalu rendah. MDN menyarankan melepas kunci saat aktivitas selesai.
- [REKOMENDASI] Sakelar "Layar tetap menyala" di mode lapangan, mati sebagai bawaan, disertai kalimat akibatnya: "Layar menyala menguras baterai lebih cepat." Jangan menampilkan angka penghematan baterai (R-005; [S94] menyebut penghematan bergantung panel dan kecerahan). Status kunci selalu terlihat, dan pengguna diberi tahu bila sistem melepasnya.

### 4.5 GPS yang jujur

- [FAKTA-WEB S37] `accuracy` adalah radius dalam meter pada tingkat kepercayaan 95%; `altitudeAccuracy` bisa null.
- [FAKTA-WEB S38] Ponsel ber-GPS umumnya akurat dalam radius 4,9 m di langit terbuka, dan akurasinya memburuk di dekat bangunan, jembatan, dan pohon.
- [FAKTA-WEB S39] Di bawah kanopi hutan (15 lokasi, 10 ponsel), DRMS horizontal berkisar 4,56 m (ponsel dua frekuensi terbaik) sampai 14,59 m. Ponsel dengan desain sama pun memberi posisi berbeda.
- [FAKTA-LOKAL] Akurasi direkam oleh perekam jejak (`resources/js/jejak.js`, `accuracy_m`), tetapi tidak dikirim bersama posisi mode pendakian dan tidak ditampilkan (`usePendakian.ts`). Koordinat ditampilkan dengan 5 desimal (`HikeModeController.php:61`). Jarak < 1 km ditampilkan per meter, ≥ 1 km dengan dua desimal (`HikeModeController.php:117-119`).

| Aturan tampilan | Rekomendasi |
|---|---|
| Akurasi | Selalu tampil: "±15 m · 2 menit lalu". Bila > 50 m: "Sinyal GPS lemah; posisi bisa meleset lebih dari 50 m. Coba di tempat terbuka." |
| Jarak ke pos | Sepanjang rute bila geometri ada ("sisa sekitar 1,4 km menyusuri jalur"). Bila tidak ada: "garis lurus 600 m; jarak tempuh lebih jauh". Dibulatkan: < 1 km ke 50 m terdekat, ≥ 1 km ke 0,1 km |
| Tiba di pos | Ditandai otomatis hanya bila jarak + akurasi ≤ radius (75 m). Bila jarak ≤ radius tetapi akurasi > radius, tanyakan: "Sudah tiba di Pos 3?" |
| Waktu tempuh | Rentang dengan sumbernya ("sekitar 1–1,5 jam, dari catatan waktu tempuh pengelola"), bukan jam tiba yang tepat |
| Ketinggian | Tidak memakai ketinggian GPS sebagai angka pasti; ambil dari profil elevasi jalur dan sebutkan sumbernya |
| Koordinat | Tetap tersedia untuk dibacakan ke tim SAR, selalu bersama akurasi dan waktu |

[INFERENSI] Angka 1 m dan 10 m di tampilan sekarang lebih teliti daripada pengukurannya. Ini presisi palsu (semangat R-005), dan di jalur berkelok jarak garis lurus meremehkan jarak tempuh.

### 4.6 Batas lokasi di latar belakang

- [FAKTA-WEB S41] Demi privasi, `watchPosition()` dapat berhenti melaporkan lokasi ke PWA saat layar mati. [FAKTA-WEB S40] Bug Firefox Android berstatus NEW sejak 2018: "GPS updates now immediately stop in firefox after the phone's display is powered off". Service worker juga tidak punya akses ke geolokasi.
- [INFERENSI] Aplikasi web tidak dapat menjanjikan rekaman jejak yang utuh saat layar mati. Rekaman di `jejak.js` hanya lengkap selama halaman terbuka dan layar menyala.
- [REKOMENDASI] Katakan terus terang di S1 dan S3: "Rekaman berhenti saat layar mati atau Anda pindah aplikasi." Pengguna memilih antara layar menyala (Wake Lock, boros baterai) atau jejak berlubang. Bagian yang tidak terekam digambar putus-putus dan diberi label "tidak terekam". Pembungkus aplikasi native adalah opsi masa depan [PERLU PERSETUJUAN: arsitektur, R-021].

### 4.7 Luring lebih dulu lawan kebijakan service worker sekarang [PERLU PERSETUJUAN]

**Fakta.** [FAKTA-LOKAL sw.blade.php:6-14, 42-50] Kebijakan sekarang: "shell boleh disimpan, isi tidak". Dokumen selalu diambil dari jaringan; saat luring, yang ditampilkan halaman luring. Alasannya: status "BUKA" yang basi lebih berbahaya daripada halaman yang gagal terbuka. [FAKTA-LOKAL `docs/PROTOKOL-UJI-KEGUNAAN.md`, T7] Tugas T7 menganggap berhasil bila peserta memahami bahwa "data lama sengaja tidak ditampilkan". [INFERENSI, perlu diverifikasi di peramban] Kunjungan Inertia setelah muat pertama adalah permintaan XHR, bukan `document`, jadi tidak tertangkap kebijakan ini sama sekali dan akan gagal biasa saat luring.

**Masalah IMK.** Kebijakan ini tepat untuk data yang cepat berubah, tetapi membuang data yang stabil. Akibatnya tab Mendaki tidak dapat dibuka di tempat yang paling membutuhkannya.

**Usulan: paket lapangan, diunduh sadar oleh pengguna sebelum berangkat.**

| Isi | Kategori | Tampilan saat luring |
|---|---|---|
| Garis jalur, daftar pos dan jaraknya, profil elevasi, catatan waktu tempuh | Stabil | Tampil normal, dengan versi dan tanggal paket |
| Rencana trip, daftar persiapan, kontak darurat pengelola | Milik pengguna atau stabil | Tampil normal |
| Status resmi | Cepat berubah | "Status terakhir diketahui: BUKA, per 24 Sep 18.00 WIB. Mungkin sudah berubah." Tidak pernah tampil sebagai status sekarang |
| Prakiraan cuaca | Cepat berubah | "Prakiraan area per 24 Sep 06.00 WIB (BMKG)", dengan label kedaluwarsa setelah ambang di `config/hiking.php` (`conditions`) |
| Laporan komunitas | Cepat berubah | Disembunyikan, atau tampil dengan umur yang mencolok |
| Ubin peta | Stabil | [FAKTA-WEB S44] OpenTopoMap mempersilakan pemakaian "sofern unser Server z.B. durch Massendownloads nicht zu stark beansprucht wird" (selama server tidak terbebani unduhan massal). [FAKTA-WEB S45] OSM melarang pemakaian luring pada `tile.openstreetmap.org`. Jadi ubin untuk paket perlu di-host sendiri (PRD §7 sudah menyebut Tippecanoe) |

- [FAKTA-WEB S43] Pedoman UX luring: beri tahu pengguna keadaan aplikasi dan tindakan yang masih dapat dilakukan saat jaringan gagal. Tugas boleh mengantre untuk disinkronkan. Jangan memakai warna saja sebagai penanda.
- [FAKTA-WEB S42] Background Synchronization API tidak Baseline. [REKOMENDASI] Kirim otomatis saat halaman terbuka dan kembali daring, dengan tombol "Kirim sekarang" dan penghitung antrean yang selalu terlihat. Perekam jejak sekarang sudah mengikuti pola "kirim setelah turun" (`jejak.js`).
- [REKOMENDASI] Bila paket lapangan disetujui, tugas T7 di protokol uji ikut berubah (§7.8).

### 4.8 Komunikasi risiko tanpa presisi palsu

**Fakta riset.**
- [FAKTA-WEB S46] Peluang satu kejadian ("30% hujan besok") tidak menyebut kelas rujukannya, sehingga publik menafsirkannya dengan cara yang saling bertentangan.
- [FAKTA-WEB S48] Istilah peluang verbal dalam laporan IPCC ditafsirkan jauh dari pedomannya, bahkan oleh pembaca yang diberi tabel konversi. Format skala ganda (kata + rentang) memperbaiki pemahaman.
- [FAKTA-WEB S49] Lima eksperimen (n = 5.780): ketidakpastian yang dinyatakan dengan angka hanya sedikit menurunkan kepercayaan pada angkanya dan tidak menurunkan kepercayaan pada sumbernya; ketidakpastian verbal menurunkannya lebih besar. Kerangka umumnya ada di [S50].
- [FAKTA-WEB S54] PAIR: "showing more granular confidence can be confusing"; pertimbangkan kategori.
- [FAKTA-WEB S47] Skala bahaya longsoran salju Amerika Utara: lima kategori, bukan peluang, masing-masing dengan saran perjalanan.

**Simpulan.** [INFERENSI] R-004 (tiga label) dan R-005 (tanpa persentase) didukung riset, **dengan syarat** setiap label punya definisi satu kalimat dan saran tindakan yang ditulis pemilik produk, bukan diserahkan pada tafsiran pembaca. Untuk besaran fisik yang memang diukur (akurasi GPS, umur data), rentang angka justru membantu.

| Situasi | Hindari | Tulis |
|---|---|---|
| Status resmi BUKA (R-012) | "Jalur aman", "Aman didaki" | "Status resmi: Buka (Balai TN …, 23 Sep 08.00 WIB). Buka berarti jalur dioperasikan, bukan jaminan keselamatan." |
| Status tidak diketahui (R-006) | "—" atau tidak ditampilkan | "Status resmi belum diketahui. Menunggu pengumuman pengelola; pastikan langsung sebelum berangkat." |
| Cuaca (R-013) | "Cuaca puncak cerah" | "Prakiraan area sekitar jalur (BMKG, 06.00 WIB): hujan ringan sore. Bukan cuaca puncak." |
| Kesiapan (R-014) | "Anda siap (85%)", "Aman berangkat" | "Masih kurang 3 hal sebelum berangkat", diikuti daftarnya, dan "Ini bukan pemeriksaan kesehatan." |
| Kecocokan (R-004, R-005) | "Cocok 92%" | "Perlu persiapan, karena elevasi naik jalur ini di atas pengalaman terjauh Anda." |
| Posisi | "Anda di Pos 3" saat belum pasti | "Sekitar 40 m dari Pos 3 (akurasi ±25 m)" |
| Waktu tempuh | "Tiba 14.32" | "Sekitar 1–1,5 jam ke Pos 4" |

### 4.9 Kepercayaan dan provenans

- [FAKTA-WEB S52] Pedoman kredibilitas Stanford (Fogg dkk., lebih dari 4.500 partisipan): "Make it easy to verify the accuracy of the information on your site" dan "Update your site's content often (at least show it's been reviewed recently)".
- [FAKTA-WEB S51] NN/g: konten harus "comprehensive, correct, and current"; kesalahan kecil cepat merusak kredibilitas.
- [REKOMENDASI] Setiap fakta penting membawa **chip sumber** yang dapat diketuk. Isinya jenis otoritas dalam teks (Resmi, Admin, Komunitas), nama lembaga, waktu relatif dengan waktu mutlak dalam zona gunung, cakupan (gunung, jalur, atau segmen), dan keadaan verifikasi. Di dalamnya ada tautan "Laporkan kesalahan data" ke antrean kurator. Warna biru komunitas (DESIGN.md) selalu disertai kata "Laporan pendaki".

---

## 5. Personalisasi yang dapat dijelaskan dan etika progres

### 5.1 Tujuan penjelasan

[FAKTA-WEB S55] Tintarev dan Masthoff membedakan tujuh tujuan penjelasan dalam sistem rekomendasi: transparansi, *scrutability* (pengguna dapat memberi tahu sistem bahwa ia keliru), kepercayaan, efektivitas, persuasi, efisiensi, dan kepuasan. Tujuan-tujuan itu bisa saling bertentangan, sehingga setiap evaluasi harus menyebut tujuan mana yang diuji.

[INFERENSI] Untuk produk keselamatan, prioritasnya efektivitas (membantu keputusan yang tepat bagi dirinya), transparansi, dan scrutability. Persuasi bukan tujuan: penjelasan yang membujuk dapat mendorong pengguna ke jalur yang terlalu berat. Route Fit berbasis aturan, bukan AI (PRD.md §7, Catatan Khusus). Pedoman HAX dan PAIR tetap relevan karena keduanya membahas sistem yang mempersonalisasi dan menjelaskan dirinya.

### 5.2 Pedoman HAX yang diterapkan pada Route Fit

[FAKTA-WEB S53] Amershi dkk. (CHI 2019) merumuskan 18 pedoman dalam empat fase: awal, selama interaksi, saat keliru, dan seiring waktu.

| Pedoman (S53) | Penerapan di Memount |
|---|---|
| G1 Make clear what the system can do | "Kecocokan membandingkan data jalur yang tercatat dengan pengalaman dan tujuan Anda. Ia tidak menilai cuaca hari ini atau kondisi tubuh Anda." |
| G2 Make clear how well the system can do what it can do | Nyatakan kelengkapan data sebagai hitungan: "5 dari 7 faktor tercatat untuk jalur ini". Bukan persentase keyakinan |
| G9 Support efficient correction | Dari "Kenapa?" langsung ke "Ubah pengalaman" dan "Laporkan data jalur keliru" |
| G10 Scope services when in doubt | Profil belum lengkap → "Belum dinilai" dan satu pertanyaan, bukan tebakan. Pencarian kalimat menampilkan tafsirannya sebagai chip yang dapat diubah |
| G11 Make clear why the system did what it did | "Kenapa?" per faktor (§5.4) |
| G13 dan G14 Learn from behavior; Update and adapt cautiously | Riwayat tidak menaikkan tingkat pengalaman diam-diam (BR-11). Sistem boleh menyarankan ("Anda sudah menyelesaikan 3 jalur dengan elevasi naik di atas 1.000 m. Perbarui pengalaman?"), pengguna yang memutuskan |
| G15 Encourage granular feedback | "Penilaian ini tidak sesuai?" dengan pilihan alasan, masuk antrean kurator |
| G16 Convey the consequences of user actions | Saat mengubah profil: "Perubahan ini mengubah label 3 jalur tersimpan" |
| G17 Provide global controls | Pengaturan data yang dipakai penilaian (misalnya riwayat pendakian). Mode "tampilkan tanpa penilaian" [PERLU PERSETUJUAN: menyentuh penyajian Route Fit, R-021] |
| G18 Notify users about changes | Di "Kabar jalur Anda": "Label Jalur X berubah dari Cocok ke Perlu persiapan karena data elevasinya diperbarui" |

### 5.3 PAIR: kalibrasi kepercayaan

[FAKTA-WEB S54] Pengguna "shouldn't trust the system completely"; penjelasan membantu mereka tahu kapan memercayai sistem dan kapan memakai penilaiannya sendiri. Penjelasan sebagian boleh meninggalkan bagian yang tidak diketahui, terlalu rumit, atau tidak berguna. Transparansi data mencakup cakupan, jangkauan (personal atau agregat), dan penghapusan. Angka keyakinan yang terlalu rinci membingungkan; kategori menjadi alternatifnya.

### 5.4 Anatomi "Kenapa?"

1. Label (teks dan ikon) dengan definisi satu kalimat **yang ditulis pemilik produk**. Laporan ini tidak mengusulkan isi definisinya.
2. Dua sampai tiga faktor penentu. Tiap faktor memuat nama dalam bahasa pendaki, nilai jalur, nilai Anda, dan arahnya (sesuai, di atas pengalaman Anda, belum diketahui). Tanpa skor (BR-09).
3. Faktor yang belum diketahui didaftar terpisah: "Belum diketahui: tingkat kesulitan navigasi (data jalur belum ada)" (R-006, R-007).
4. Apa yang akan mengubah label, dinyatakan sebagai fakta profil yang mungkin sudah dimiliki pengguna. [HIPOTESIS, uji dulu] Bagian ini dapat mendorong orang menggelembungkan isian pengalamannya. Amati di uji T11 (§7.8).
5. Sumber dan waktu data jalur, serta versi aturan (kecil, di bawah).
6. Aksi: "Ubah pengalaman", "Bandingkan dengan jalur lain", "Laporkan data keliru".

### 5.5 Kendali pengguna

[FAKTA-WEB S56] NN/g: personalisasi dijalankan sistem, kustomisasi dijalankan pengguna. Keduanya "should not be used as a fix for a broken site". [REKOMENDASI] Gabungkan: sistem mempersonalisasi, pengguna melihat dan dapat mengubah setiap masukan yang dipakai (Profil → Pengalaman, Preferensi, Perlengkapan).

### 5.6 Etika progres dan larangan pola gelap

**Fakta.**
- [FAKTA-WEB S57, cuplikan] Sailer dkk. (2017): lencana, papan peringkat, dan grafik performa meningkatkan kepuasan kebutuhan kompetensi dan kebermaknaan tugas; avatar, cerita bermakna, dan rekan satu tim meningkatkan rasa keterhubungan.
- [FAKTA-WEB S58] Silverman dan Barasch (2023), tujuh studi: streak yang utuh meningkatkan keterlibatan berikutnya dibanding streak yang putus. Efeknya lebih kuat bila putusnya dianggap salah diri sendiri.
- [FAKTA-WEB S59] Mathur dkk. (CSCW 2019): 1.818 pola gelap dari sekitar 53.000 halaman produk di 11.000 situs, dikelompokkan dalam 7 kategori: Sneaking, Urgency, Misdirection, Social Proof, Scarcity, Obstruction, Forced Action.
- [FAKTA-LOKAL] PRODUCT.md, komitmen merek: "Tidak pernah menghukum keputusan yang benar. Pendakian yang dibatalkan karena cuaca tetap tercatat sebagai pendakian" dan "Tidak ada perbandingan kecepatan antarpendaki". DESIGN.md R-015: progres adalah perbandingan dengan diri sendiri.

**Rekomendasi.**
- [INFERENSI] Kebutuhan kompetensi dapat dipenuhi grafik performa dan lencana **tanpa** papan peringkat (Sailer), sehingga R-015 tidak mengorbankan motivasi. Keterhubungan dipenuhi lewat rombongan trip, bukan kompetisi.
- [REKOMENDASI] Tanpa streak. Mendaki tidak harian, dan streak yang putus karena cuaca atau keputusan turun lebih awal akan menghukum keputusan yang benar.
- [REKOMENDASI] Pencapaian (milestone, asumsi A6 spec v2) diberikan untuk perilaku persiapan (cek kesiapan dilakukan sebelum berangkat), jalur atau kawasan baru, laporan yang terverifikasi, dan kesinambungan pribadi. Tidak ada pencapaian berbasis kecepatan atau jumlah puncak dalam waktu singkat.
- [REKOMENDASI] Akhir pendakian (peak-end, [S11]) merayakan kepulangan yang selesai, bukan hanya puncak. Label "Kembali lebih awal" netral dan tidak memutus apa pun.

| Kategori pola gelap (S59) | Bentuk yang dilarang di Memount | Yang boleh |
|---|---|---|
| Urgency | Hitung mundur buatan ("Segera, kuota hampir habis!") | Jendela izin resmi dengan sumber dan waktunya |
| Scarcity | "Tinggal 3 kuota" tanpa data resmi | Kuota dari sumber resmi, bila datanya ada |
| Social Proof | "120 orang mendaki jalur ini minggu ini" sebagai dorongan memilih | Laporan pendaki sebagai informasi kondisi, dengan waktunya |
| Misdirection (confirmshaming) | "Tidak, saya tidak peduli persiapan" | Pilihan netral: "Nanti" |
| Obstruction | Hapus akun atau jejak yang disembunyikan | Hapus mudah ditemukan (KEBIJAKAN-DATA-PRIBADI.md) |
| Forced Action | Meminta izin lokasi saat pertama membuka aplikasi | Izin lokasi hanya di Mendaki (`docs/ARCHITECTURE.md` aturan 6) |
| Sneaking | Berbagi data yang dicentang otomatis | Rekam jejak mati sebagai bawaan (PRODUCT.md) |

---

## 6. Aksesibilitas: daftar periksa WCAG 2.2 AA per komponen

### 6.1 Status standar per September 2026

- [FAKTA-WEB S60] WCAG 2.2 terbit 5 Oktober 2023 dengan sembilan kriteria baru; 4.1.1 Parsing dinyatakan usang dan dihapus. [FAKTA-WEB S61] Rekomendasi yang berlaku bertanggal 12 Desember 2024.
- [FAKTA-WEB S63] WCAG 3.0 berstatus Working Draft 10 September 2026: "This is a draft document and may be updated, replaced, or obsoleted by other documents at any time." [FAKTA-WEB S62] "WCAG 3 is not expected to be a completed W3C standard for a few more years" dan "The best way to prepare for WCAG 3 in the future, is to meet WCAG 2.2 success criteria now."
- [INFERENSI] Target proyek (WCAG 2.2 AA) tetap benar. WCAG 3 belum layak dijadikan target.
- Di tabel §6.2–§6.8, kolom "Kriteria" merujuk kriteria sukses WCAG [S61] atau sumber yang disebut; kolom "Cara uji" dan "Lulus bila" adalah [REKOMENDASI] rancangan uji.

| Kriteria baru (S60) | Tingkat | Di mana mengena Memount |
|---|---|---|
| 2.4.11 Focus Not Obscured (Minimum) | AA | Bilah tab tetap, bilah aksi tempel, sheet nonmodal, spanduk luring |
| 2.4.12 Focus Not Obscured (Enhanced), 2.4.13 Focus Appearance | AAA | Opsional; cincin fokus sudah ada (`fokus` di NavLinks.tsx) |
| 2.5.7 Dragging Movements | AA | Geser peta, seret sheet, penggeser saringan, daftar persiapan yang diurut dengan seret |
| 2.5.8 Target Size (Minimum) | AA | Minimum 24 px; proyek 44 px; kendali utama lapangan 56–64 px |
| 3.2.6 Consistent Help | A | Letak "Bantuan" |
| 3.3.7 Redundant Entry | A | Membuat trip dari rencana, persiapan dari perlengkapan |
| 3.3.8 Accessible Authentication (Minimum) | AA | Masuk dan daftar |
| 3.3.9 Accessible Authentication (Enhanced) | AAA | — |

### 6.2 Bilah tab

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| TB-1 | 2.5.8 [S65]; target proyek 44 px | Ukur kotak batas tiap tab di DevTools pada lebar 320 dan 360 px | Tiap tab ≥ 44×44 px dan tidak bertumpuk |
| TB-2 | 2.4.6, 1.4.4 | Periksa label; aktifkan teks OS 200% (§6.8) | Label teks satu kata berbahasa Indonesia, ≥ 12 px, tidak terpotong elipsis |
| TB-3 | 1.4.3, 1.4.11 | Hitung kontras label, ikon, dan penanda aktif di kedua tema; untuk latar tembus pandang, di latar terburuk | Label ≥ 4,5:1, ikon dan penanda aktif ≥ 3:1 |
| TB-4 | 1.4.1, 4.1.2 | Tampilan abu-abu dan pembaca layar | Tab aktif ditandai lebih dari warna, dan diumumkan sebagai halaman sekarang (`aria-current="page"`, sudah ada) |
| TB-5 | 2.4.11 [S66] | Tekan Tab di halaman panjang sampai elemen di bawah layar | Elemen yang difokus tidak pernah tertutup penuh oleh bilah (`scroll-padding-bottom` = tinggi bilah) |
| TB-6 | 1.3.1, 2.4.1 | Periksa landmark | `<nav aria-label="Navigasi utama">`; tautan lewati ke isi |
| TB-7 | 3.2.3 | Buka setiap tab | Urutan dan label sama di semua halaman dan ukuran layar |
| TB-8 | 4.1.2 | Pembaca layar pada tab berlencana | Isi lencana termasuk nama aksesibel ("Trip, 1 perlu tindakan") |

### 6.3 Lembar bawah (sheet)

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| BS-1 | 2.5.7 [S64] | Coba semua fungsi tanpa menyeret | Pegangan berupa tombol yang berpindah detent (`aria-expanded`); ada tombol "Tutup" |
| BS-2 | 2.1.1, 2.1.2 | Hanya papan ketik | Esc menutup sheet modal; fokus tertahan di sheet modal tetapi tidak terkunci; sheet nonmodal tidak menahan fokus |
| BS-3 | 2.4.3 | Buka lalu tutup sheet | Fokus pindah ke judul sheet saat dibuka dan kembali ke pemicu saat ditutup |
| BS-4 | 4.1.2 | Periksa pohon aksesibilitas | Sheet modal: `role="dialog"`, `aria-modal="true"`, diberi nama oleh judulnya |
| BS-5 | Pola NN/g [S21] | Tombol Back Android dan Back peramban | Back menutup sheet tanpa berpindah halaman |
| BS-6 | 2.4.11 | Tab di halaman di belakang sheet nonmodal | Elemen yang difokus tidak tertutup penuh; halaman bergulir |
| BS-7 | 1.4.10, 1.4.4 | 320 px dan zoom 200% | Isi sheet dapat digulir; tombol aksi terjangkau |
| BS-8 | 2.3.3, preferensi gerak [S75] | Emulasi `prefers-reduced-motion: reduce` | Sheet muncul dengan pudar singkat atau tanpa transisi. Tidak ada sheet bertumpuk |

### 6.4 Peta

[FAKTA-LOKAL PRODUCT.md §Accessibility & Inclusion] Peta memakai `role="region"` dengan padanan teks, tidak pernah `role="img"`. [FAKTA-WEB S73] NPS mewajibkan deskripsi teks untuk setiap peta, mempertahankan akses papan ketik, dan merencanakan tampilan data tabular sebagai "an alternate means of interacting with the data". [FAKTA-WEB S70] Cubit-zoom membutuhkan alternatif satu penunjuk, misalnya tombol plus dan minus.

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| MP-1 | 1.1.1, 1.3.1 | Pembaca layar | Wilayah peta bernama, dengan ringkasan teks (jumlah jalur, wilayah) dan tautan ke daftar padanan |
| MP-2 | 2.5.1 [S70], 2.5.7 | Tanpa cubit dan tanpa seret | Ada tombol + dan −; semua tujuan dapat dicapai lewat daftar dan pencarian |
| MP-3 | 2.1.1, 2.4.7 | Hanya papan ketik | Peta dapat difokus, dapat digeser dan di-zoom dengan tombol panah dan +/− (MapLibre punya pengendali papan ketik; [INFERENSI] verifikasi di peramban), cincin fokus terlihat |
| MP-4 | 1.4.11 | Hitung kontras garis terhadap peta dasar di kedua tema | Garis jalur ≥ 3:1 terhadap sekelilingnya; pakai garis tepi (*casing*) bila peta dasar beragam |
| MP-5 | 1.4.1 | Tampilan abu-abu | Label kecocokan dibedakan juga oleh pola garis dan teks di ringkasan |
| MP-6 | 4.1.3 [S71] | Pilih jalur dari peta | Nama dan label jalur diumumkan lewat `role="status"` |
| MP-7 | Lisensi, bukan WCAG | Periksa sudut peta | Atribusi terlihat dan tidak tertutup UI ([S45]; OpenTopoMap CC-BY-SA [S44]) |
| MP-8 | 1.1.1 | Mode pendakian | Posisi disertai teks "±X m, diperbarui HH.MM" |
| MP-9 | 2.2.2, preferensi gerak | Emulasi gerak dikurangi | `flyTo` diganti `jumpTo` |

### 6.5 Grafik elevasi

[FAKTA-WEB S72] Gambar kompleks butuh deskripsi pendek dan deskripsi panjang, misalnya lewat `<figure>`/`<figcaption>` atau tabel data. [FAKTA-WEB S74] Chartability adalah kumpulan heuristik untuk menilai aksesibilitas visualisasi dari sisi visual, motorik, vestibular, neurologis, dan kognitif.

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| EL-1 | 1.1.1 | Pembaca layar | Judul berupa pertanyaan; kalimat ringkasan (total naik dan turun, titik tertinggi, bagian tercuram); tabel pos, jarak, dan ketinggian |
| EL-2 | 1.4.11 | Hitung kontras garis di kedua tema | ≥ 3:1 (token `brand-ink` sudah dirancang untuk ini) |
| EL-3 | 1.4.1 | Tampilan abu-abu | Bagian curam ditandai dengan label atau pola, bukan warna saja |
| EL-4 | 2.1.1, pemetaan Norman | Papan ketik pada grafik interaktif | Panah berpindah antar-pos, nilainya diumumkan, dan posisi di peta ikut tersorot |
| EL-5 | R-007 | Jalur tanpa data elevasi | Teks "Data ketinggian belum tersedia", bukan grafik kosong (PRD.md §3, fitur 1.2) |
| EL-6 | 1.4.10 | Lebar 320 px | Grafik menyesuaikan lebar; tabel bergulir di wadahnya sendiri |

### 6.6 Kartu jalur dan kartu trip

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| CD-1 | 1.3.1, 4.1.2 | Periksa markup | Kartu berupa butir daftar atau `<article>` dengan judul; satu tautan utama; tombol sekunder tidak bersarang di dalam tautan |
| CD-2 | 2.5.8 | Ukur | Tombol sekunder ("Simpan") ≥ 44 px dan tidak bertumpuk dengan area tautan |
| CD-3 | 1.4.1, 1.4.3 | Abu-abu dan kontras | Label kecocokan berupa teks dan ikon dengan kontras cukup |
| CD-4 | 1.1.1, R-017 | Periksa gambar | Gambar utama dari aset berotoritas dengan alt bermakna; cuplikan peta dekoratif `alt=""` |
| CD-5 | H1, provenans | Inspeksi | Status di kartu memuat sumber dan umurnya ("Resmi · 2 hari lalu") |
| CD-6 | 1.3.1 | Periksa struktur judul | Judul kartu satu tingkat di bawah judul bagian |

### 6.7 Formulir

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| FM-1 | 1.3.1, 3.3.2, 4.1.2 | Periksa label | Setiap isian punya label terlihat yang terhubung; placeholder bukan label |
| FM-2 | 3.3.1, 3.3.3, 4.1.3 | Kirim formulir salah | Galat di dekat isian, terhubung lewat `aria-describedby` dan `aria-invalid`, menyebut cara memperbaikinya [S32]; ringkasan galat diumumkan |
| FM-3 | 3.3.7 [S68] | Alur trip | Data dari langkah sebelumnya terisi otomatis atau dapat dipilih |
| FM-4 | 3.3.8 [S69] | Halaman masuk | Tempel dan pengelola sandi tidak diblokir; `autocomplete` benar; tautan masuk lewat email boleh ditambahkan (teknik G218) |
| FM-5 | 1.3.5 | Periksa atribut | `autocomplete` untuk nama dan email |
| FM-6 | Galat NN/g [S32] | Picu galat server | Isian pengguna tidak hilang |
| FM-7 | 2.4.11, antislop-human | Ponsel nyata dengan papan ketik layar | Isian yang difokus tidak tertutup papan ketik |
| FM-8 | 2.5.7 | Daftar persiapan yang dapat diurut | Ada tombol "Naikkan" dan "Turunkan" selain seret |

### 6.8 Umum: gerak, tema, ukuran teks, reflow

- [FAKTA-WEB S80] Chrome 146 (stabil 10 Maret 2026) mendukung `<meta name="text-scale">`: ukuran huruf bawaan elemen akar mengikuti skala teks OS dan peramban, sehingga halaman yang memakai `rem`/`em` menghormati setelan pengguna. [FAKTA-WEB S81] Per Februari 2026, Firefox sudah menghormatinya tanpa kode khusus, dan Safari melalui `-apple-system-body`. [FAKTA-WEB S82] Riset Appt di Belanda: "more than a fifth of users normally increase the text size on iOS and on Android". [FAKTA-WEB S76] HIG: beri pilihan memperbesar teks setidaknya 200 persen.

| ID | Kriteria | Cara uji | Lulus bila |
|---|---|---|---|
| GN-1 | 2.3.3, 2.2.2, [S75] | Emulasi gerak dikurangi | Tidak ada gerak dekoratif; perubahan keadaan tetap terlihat (pudar atau instan) |
| GN-2 | 1.4.3, 1.4.11 | `ColourContrastTest` (sudah ada, kedua tema) | Ambang sama di kedua tema; info kritis lapangan ≥ 7:1 bila disetujui |
| GN-3 | 1.4.4, [S80] | Chrome Android 146+ dengan teks OS diperbesar; meta `text-scale` terpasang | Teks membesar, tata letak tidak terpotong, tidak ada gulir horizontal |
| GN-4 | 1.4.10 [S61] | Lebar 320 CSS px | Tanpa gulir dua arah, kecuali tabel data |
| GN-5 | 1.3.4 | Putar layar | Potret dan lanskap berfungsi (manifest sudah `any`) |
| GN-6 | 4.1.3 [S71], H1 | Simpan, sinkron, luring | Setiap perubahan keadaan diumumkan lewat `role="status"`, tanpa memindah fokus |
| GN-7 | Semua | TalkBack + Chrome Android pada alur inti | Alur T1–T7 dapat diselesaikan |
| GN-8 | 3.2.6 [S67] | Buka lima akar tab | "Bantuan" dan glosarium berada di urutan relatif yang sama |

---

## 7. Kriteria penerimaan UX per tab dan metode evaluasi

Cara uji: **(A)** otomatis (test HTTP, `assertInertia`, `ColourContrastTest`); **(I)** inspeksi atau evaluasi heuristik; **(U)** uji pengguna; **(L)** uji lapangan. Semua kriteria di bagian ini adalah [REKOMENDASI]; semua angka pada (U) dan (L) adalah **target usulan**, bukan hasil.

### 7.1 Beranda

| ID | Diterima bila | Cara |
|---|---|---|
| B-1 | Pada 360×800, di atas lipatan hanya ada satu tombol aksi utama, sesuai `keadaan` (profil, berjalan, terdekat, mulai), ditambah keadaan baru "kembali dari pendakian" bila ada data belum terkirim | I |
| B-2 | Kartu trip terdekat memuat tanggal dengan penanda zona gunung, hitung mundur, status resmi dengan chip sumber dan umurnya, dan kesiapan berbunyi "n hal masih kurang" atau "belum diperiksa". Tanpa kata "siap" atau "aman" dan tanpa persen | I |
| B-3 | Rekomendasi hanya memuat jalur yang lolos gerbang (R-008, R-022), paling banyak tiga, masing-masing dengan label dan satu alasan "karena …". Bila tidak ada jalur yang layak, keadaan "data belum ada" menyebut pihak yang ditunggu | A, I |
| B-4 | "Kabar jalur Anda": status resmi di atas laporan komunitas; setiap butir memuat sumber dan waktu | I |
| B-5 | Ringkasan progres berupa satu kalimat perbandingan dengan diri sendiri; tanpa streak atau peringkat | I |
| B-6 | LCP ≤ 2,5 s pada profil jaringan uji (PRD.md NFR). Bila memuat > 1 s, skeleton mengikuti tata letak nyata | A |
| B-7 | ≥ 4 dari 5 peserta menyebut langkah berikutnya dalam 10 detik tanpa menggulir | U |

### 7.2 Jelajah

| ID | Diterima bila | Cara |
|---|---|---|
| E-1 | Setiap fungsi peta dapat dilakukan tanpa seret dan tanpa cubit (tombol +/−, daftar, pencarian) | I |
| E-2 | Mengubah saringan memperbarui jumlah hasil yang diumumkan lewat `role="status"` dalam ≤ 1 s, dan posisi peta tidak melompat | A, I |
| E-3 | Label kecocokan tampil sebagai teks di daftar dan ringkasan; garis peta dibedakan warna dan pola; memilih di daftar menyorot di peta | I |
| E-4 | Simpan dan lepas simpan: umpan balik ≤ 100 ms; "Batalkan" tersedia; simpanan juga dapat dipulihkan dari daftar Tersimpan, jadi tidak bergantung pada durasi toast | I |
| E-5 | Mengetuk garis membuka sheet nonmodal; Back dan Esc menutupnya; fokus kembali ke pemicu; petunjuk "Ketuk garis jalur untuk ringkasan" tampil pertama kali | I |
| E-6 | Hasil kosong karena saringan menyebut saringan mana yang sebaiknya dilonggarkan | I |
| E-7 | Gambar utama kartu dari aset berotoritas atau cuplikan peta netral (R-017) | I |
| E-8 | Tugas T2 dan T3: ≥ 4 dari 5 peserta menemukan jalur relevan; ≥ 80% menyebut dua alasan label dengan benar (target PRODUCT.md) | U |

### 7.3 Trip

| ID | Diterima bila | Cara |
|---|---|---|
| T-1 | Setiap trip menampilkan penanda langkah (Kecocokan → Rencana → Persiapan → Cek kesiapan → Berangkat), dengan langkah berikutnya yang dapat diketuk. Kemajuan hanya dari langkah yang benar-benar selesai | I |
| T-2 | Banding: paling banyak 3 jalur (DK-29); atribut sebagai baris, jalur sebagai kolom; tanpa pemenang, tanpa kolom "terbaik", tanpa skor (R-016, O-2); sel kosong berbunyi "Belum diketahui" | I |
| T-3 | Pada 320 px, kolom pertama tabel banding menempel dan tabel bergulir di wadahnya sendiri; judul baris dan kolom dibacakan pembaca layar | I |
| T-4 | Centang persiapan: ikon dan teks berubah ≤ 100 ms, tersimpan, dapat dibatalkan; penghitung berbunyi "12 dari 20 dikonfirmasi" (hitungan, bukan persen) | A, I |
| T-5 | Pemilih tanggal menampilkan jendela izin dalam zona gunung; tanggal di luar jendela ditandai dengan teks, bukan warna saja | I |
| T-6 | Cek kesiapan menampilkan butir yang masih kurang di tempat, dengan tautan untuk melengkapinya; hasil berbunyi "Yang masih kurang …" dan "Ini bukan pemeriksaan kesehatan" (R-014) | I |
| T-7 | Jalur, tanggal dari rencana, dan perlengkapan dari profil tidak diminta ulang (WCAG 3.3.7) | I |
| T-8 | Tugas T4 dan T5: 0 dari 5 peserta menafsirkan hasil cek kesiapan sebagai "aman"; ≥ 4 dari 5 menyebut apa yang masih kurang | U |

### 7.4 Mendaki

| ID | Diterima bila | Cara |
|---|---|---|
| K-1 | Mode lapangan: paling banyak tiga aksi; istilah "pos"; info kritis ≥ 7:1 di kedua tema; nama pos ≥ 24 px; tidak ada teks < 14 px | I, A |
| K-2 | Selalu tampil: umur posisi, akurasi (±m), keadaan daring atau luring, jumlah antrean kiriman | I |
| K-3 | Dengan paket lapangan terunduh dan mode pesawat aktif: garis jalur, pos, profil elevasi, dan pos berikutnya tetap bekerja; data yang cepat berubah berlabel "terakhir diketahui … per …" | L, I |
| K-4 | "Tiba di pos" otomatis hanya bila jarak + akurasi ≤ radius; selain itu muncul pertanyaan konfirmasi | A |
| K-5 | Jarak dihitung sepanjang rute, atau berlabel "garis lurus"; pembulatan sesuai §4.5; waktu tempuh berupa rentang dengan sumbernya | A, I |
| K-6 | "Layar tetap menyala" meminta ulang kunci setelah `visibilitychange` dan memberi tahu bila sistem melepasnya | I |
| K-7 | Memulai tanpa paket lapangan memunculkan peringatan yang dapat dilewati, bukan blokir | I |
| K-8 | Izin ditolak, waktu habis, dan akurasi buruk masing-masing menyebut penyebab dan cara memperbaikinya | I |
| K-9 | "Selesai mendaki" membuka ringkasan; Back di mode lapangan mengecilkan, tidak mengakhiri; strip S3b tampil di tab lain | I |
| K-10 | Dengan sarung tangan tipis, di bawah matahari siang, satu tangan, sambil berjalan pelan: ≥ 4 dari 5 peserta berhasil "Perbarui posisi" dan menyebut pos berikutnya tanpa salah ketuk; ≥ 4 dari 5 dapat menjelaskan arti "±25 m" | L |

### 7.5 Progres

| ID | Diterima bila | Cara |
|---|---|---|
| P-1 | Setiap pendakian selesai punya ringkasan dengan penutup (catat, lapor opsional). Pendakian yang dibatalkan atau turun lebih awal tercatat dengan label netral "Kembali lebih awal", bukan "Gagal" | I |
| P-2 | Setiap grafik berjudul pertanyaan, disertai kalimat ringkasan dan tabel padanan | I |
| P-3 | Tidak ada papan peringkat, streak, atau perbandingan dengan pengguna lain | I |
| P-4 | Target ditetapkan pengguna; kemajuan hanya dari kejadian nyata | I |
| P-5 | Tidak ada pencapaian berbasis kecepatan | I |
| P-6 | ≥ 4 dari 5 peserta dapat menyebut satu hal yang meningkat dibanding pendakian sebelumnya | U |

### 7.6 Profil

| ID | Diterima bila | Cara |
|---|---|---|
| PF-1 | Setiap isian pengalaman dan preferensi menyebut pengaruhnya pada kecocokan; perubahan menampilkan akibatnya (HAX G16) | I |
| PF-2 | Riwayat tidak menaikkan tingkat pengalaman otomatis (BR-11); sistem hanya menyarankan | A |
| PF-3 | Masuk: tempel dan pengelola sandi diizinkan; `autocomplete` benar (3.3.8) | I |
| PF-4 | Lokasi hanya diminta di Mendaki; rekam jejak mati sebagai bawaan; hapus jejak dan hapus akun mudah ditemukan, sesuai `docs/KEBIJAKAN-DATA-PRIBADI.md` | A, I |
| PF-5 | "Bantuan" berada di urutan relatif yang sama di semua halaman (3.2.6) | I |
| PF-6 | Pengaturan tampilan: tema (sudah ada), ukuran teks mengikuti OS; mode hemat data tanpa gambar [HIPOTESIS: berguna untuk kuota mahal, perlu diuji] | I, U |

### 7.7 Lintas tab: Komunitas dan penjelasan kecocokan

| ID | Diterima bila | Cara |
|---|---|---|
| C-1 | Status resmi dan laporan komunitas tidak pernah bercampur; laporan selalu berlabel teks "Laporan pendaki" dengan waktu dan keadaan moderasi | I |
| C-2 | Laporan komunitas tidak mengubah status resmi yang ditampilkan (R-010; `docs/ARCHITECTURE.md` aturan 4) | A |
| C-3 | Ajakan melapor ada di detail jalur dan ringkasan pendakian; laporan yang dibuat saat luring masuk antrean dengan status terlihat | I |
| C-4 | Tugas T6: ≥ 90% peserta membedakan informasi resmi dan komunitas (target PRODUCT.md) | U |
| J-1 | Setiap label kecocokan punya "Kenapa?" berisi 2–3 faktor, faktor yang belum diketahui, dan sumber data | I |
| J-2 | Penjelasan menyebut bahwa penilaian berbasis aturan dari data yang tercatat, bukan penilaian keselamatan | I |
| J-3 | Penjelasan menampilkan isian profil yang dipakai, dengan tautan untuk mengubahnya (HAX G9) | I |
| J-4 | Tugas T3: ≥ 80% peserta menyebut dua alasan dengan benar (target PRODUCT.md) | U |

### 7.8 Metode evaluasi untuk tim kecil

| Metode | Untuk apa | Kapan | Peserta | Keluaran | Sumber |
|---|---|---|---|---|---|
| Evaluasi heuristik | Menemukan masalah tanpa pengguna | Setiap gelombang UI | 3–5 evaluator, masing-masing sendiri dulu sebelum digabung, 1–2 jam | Daftar masalah dengan keparahan 0–4 | [S91], [S92] |
| Cognitive walkthrough | Kemudahan belajar alur baru | Sebelum membangun alur Mendaki S1–S4 dan "Kenapa?" | Tim lintas fungsi: fasilitator, evaluator, pencatat | Jawaban empat pertanyaan di setiap langkah | [S90] |
| Tree test | Label dan struktur IA v2 | Sebelum label dikunci | Sedikit peserta untuk temuan kualitatif; lebih banyak untuk angka | Keberhasilan, keterarahan, waktu | [S93] |
| Uji tugas dengan moderator | Menemukan masalah | Setiap iterasi | 5 per putaran; pemula dan berpengalaman adalah kelompok berbeda (3–4 per kelompok); tiga putaran kecil lebih baik daripada satu putaran besar | Keberhasilan, waktu, salah jalan | [S86] |
| SEQ | Kesulitan per tugas | Segera setelah setiap tugas | Sama | Skala 1–7; rata-rata acuan 5,3–5,6; korelasi dengan keberhasilan dan waktu sekitar r = 0,5 | [S89] |
| SUS | Kegunaan keseluruhan | Akhir sesi | 12–14 responden (protokol) | Skor 0–100, rata-rata 68, bukan persentase | [S87] |
| Uji lapangan | Hambatan situasional nyata | Setelah mode lapangan berjalan | 5, di lokasi berisiko rendah (bukit, taman, jalur wisata pendek) | Tugas K-10 | [S84], [S85] |
| Pembaca layar seluler | Aksesibilitas | Setiap rilis | Tim | TalkBack + Chrome Android; VoiceOver iOS; NVDA desktop (sudah ada) | [S94] |

**Empat heuristik lapangan** sebagai tambahan tabel heuristik di protokol [REKOMENDASI]: (L1) dapat dipakai dengan satu tangan dan sarung tangan; (L2) jujur saat luring: yang basi ditandai, yang tidak tersedia disebut; (L3) sumber dan umur setiap fakta terlihat; (L4) tanpa presisi palsu: rentang dan kategori, bukan angka pasti.

**Perubahan yang diusulkan untuk `docs/PROTOKOL-UJI-KEGUNAAN.md`** (berkas itu tidak diubah oleh riset ini):
- **SEQ setelah setiap tugas.** Protokol sekarang hanya memakai SUS di akhir sesi.
- **SUS versi Indonesia tervalidasi** untuk hasil yang akan dipublikasikan: Sharfina dan Santoso (ICACSIS 2016), Cronbach's alpha 0,841 [S88]. Protokol sudah menyebut perlunya versi tervalidasi, tetapi belum menunjuk rujukannya.
- **T7** berubah bila paket lapangan disetujui: "Berhasil bila peserta membuka paket lapangan dan menyebut bahwa status yang tampil adalah status terakhir diketahui beserta waktunya."
- **T9 (baru)** Mode pesawat di mode lapangan: perbarui posisi dan sebutkan pos berikutnya.
- **T10 (baru)** "Apa arti ±25 m di layar ini?"
- **T11 (baru)** Buka "Kenapa?" untuk jalur berlabel Perlu persiapan, lalu ubah pengalaman. Amati apakah peserta memahami akibatnya, dan apakah ia tergoda menggelembungkan isian (§5.4).
- **T12 (baru)** Di layar banding: "Jalur mana yang terbaik untuk Anda?" Berhasil bila peserta menjelaskan trade-off. Amati apakah UI menyiratkan pemenang (R-016).
- **Tree test T13:** "Bandingkan tiga jalur yang Anda simpan", "Lihat elevasi naik total Anda tahun ini", "Ubah preferensi jarak", "Mulai pendakian yang sudah direncanakan", "Baca laporan pendaki terbaru untuk jalur tersimpan".
- **Bagian 4:** tambahkan TalkBack di Android, karena mayoritas lalu lintas web Indonesia berasal dari Android [S94].
- **Etika:** peserta uji lapangan tidak mengandalkan prototipe sebagai satu-satunya alat navigasi di medan berbahaya.

### 7.9 Urutan tindak lanjut yang diusulkan

| Prioritas | Pekerjaan | Butuh persetujuan? |
|---|---|---|
| P0: kejujuran dan keselamatan | O-6 (pos, bukan checkpoint); label "garis lurus" dan pembulatan jarak (K-5); akurasi tampil (K-2); tiba di pos memperhitungkan akurasi (K-4); tanpa skor di banding dan kesiapan (O-2) | O-2 sudah diusulkan di spec v2 P7 |
| P1: IMK inti | Beranda satu aksi (B-1); label tab ≥ 12 px dan ikut skala teks (TB-2, GN-3); sheet dengan alternatif tanpa seret (BS-1); tiga jenis keadaan kosong; undo (E-4, T-4) | Tidak |
| P2: lapangan | Paket lapangan dan kebijakan service worker (O-5); ubin peta yang di-host sendiri; sakelar Wake Lock; transparansi batas lokasi di latar | Ya (R-021) |
| P3: evaluasi | Tree test IA v2; cognitive walkthrough Mendaki; uji 5 pengguna dengan SEQ; uji lapangan | Tidak |

---

## 8. Daftar sumber

Semua sumber web **diakses 25 September 2026**.

**Heuristik dan hukum UX**
- S1 Nielsen, J. "10 Usability Heuristics for User Interface Design". NN/g, 1994, diperbarui 30 Jan 2024. https://www.nngroup.com/articles/ten-usability-heuristics/
- S2 Shneiderman, B. "The Eight Golden Rules of Interface Design", dari *Designing the User Interface*, ed. 6 (Pearson, 2016). https://www.cs.umd.edu/users/ben/goldenrules.html
- S3 Norman, D. *The Design of Everyday Things, Revised and Expanded Edition*. Basic Books, 2013. https://jnd.org/books/the-design-of-everyday-things-revised-and-expanded-edition/
- S4 Budiu, R. "Fitts's Law and Its Applications in UX". NN/g, 31 Jul 2022. https://www.nngroup.com/articles/fitts-law/
- S5 Laws of UX, "Hick's Law". https://lawsofux.com/hicks-law/
- S6 Laws of UX, "Miller's Law". https://lawsofux.com/millers-law/
- S7 Laws of UX, "Jakob's Law". https://lawsofux.com/jakobs-law/
- S8 Laws of UX, "Tesler's Law". https://lawsofux.com/teslers-law/
- S9 Laws of UX, "Doherty Threshold" (Doherty & Thadani, IBM Systems Journal, 1982). https://lawsofux.com/doherty-threshold/
- S10 Nielsen, J. "Response Times: The 3 Important Limits". NN/g, 1993 (diperbarui 2014). https://www.nngroup.com/articles/response-times-3-important-limits/
- S11 Kane, L. "The Peak–End Rule". NN/g, 30 Des 2018. https://www.nngroup.com/articles/peak-end-rule/
- S12 Laws of UX, "Goal-Gradient Effect", merujuk Kivetz, Urminsky & Zheng, "The Goal-Gradient Hypothesis Resurrected" (Journal of Marketing Research, 2006). https://lawsofux.com/goal-gradient-effect/
- S13 Laws of UX, "Zeigarnik Effect". https://lawsofux.com/zeigarnik-effect/
- S14 Ghibellini, R. & Meier, B. "Interruption, recall and resumption: a meta-analysis of the Zeigarnik and Ovsiankina effects". *Humanities and Social Sciences Communications* 12, 962 (2025). https://www.nature.com/articles/s41599-025-05000-w

**Navigasi dan komponen**
- S15 Apple. Human Interface Guidelines, "Tab bars" (data JSON resmi). https://developer.apple.com/design/human-interface-guidelines/tab-bars
- S16 Material Components Android, "Navigation bar" (BottomNavigation.md). https://github.com/material-components/material-components-android/blob/master/docs/components/BottomNavigation.md
- S17 Material Components Android, "Navigation rail". https://github.com/material-components/material-components-android/blob/master/docs/components/NavigationRail.md
- S18 Material Components Android, "Floating action button". https://github.com/material-components/material-components-android/blob/master/docs/components/FloatingActionButton.md
- S19 Material Components Android, "Bottom sheets". https://github.com/material-components/material-components-android/blob/master/docs/components/BottomSheet.md
- S20 Apple. Human Interface Guidelines, "Sheets" (data JSON resmi). https://developer.apple.com/design/human-interface-guidelines/sheets
- S21 Laubheimer, P. "Bottom Sheets: Definition and UX Guidelines". NN/g, 11 Jun 2023. https://www.nngroup.com/articles/bottom-sheet/
- S22 Pernice, K. & Budiu, R. "Hamburger Menus and Hidden Navigation Hurt UX Metrics". NN/g, 26 Jun 2016. https://www.nngroup.com/articles/hamburger-menus/
- S22b Pernice, K. & Budiu, R. "Beyond the Hamburger: What Makes Navigation Discoverable on Mobile". NN/g, 24 Jul 2016. https://www.nngroup.com/articles/find-navigation-mobile-even-hamburger/
- S23 Android Developers, "Principles of navigation". https://developer.android.com/guide/navigation/principles
- S24 Hoober, S. "How Do Users Really Hold Mobile Devices?". UXmatters, 18 Feb 2013. https://www.uxmatters.com/mt/archives/2013/02/how-do-users-really-hold-mobile-devices.php
- S25 Harley, A. "Icon Usability". NN/g, 27 Jul 2014. https://www.nngroup.com/articles/icon-usability/

**Umpan balik, keadaan, dan gerak**
- S26 Tankala, S. "Skeleton Screens 101". NN/g, 4 Jun 2023. https://www.nngroup.com/articles/skeleton-screens/
- S27 Sherwin, K. "Progress Indicators Make a Slow System Less Insufferable". NN/g, 26 Okt 2014. https://www.nngroup.com/articles/progress-indicators/
- S28 Mishunov, D. "True Lies Of Optimistic User Interfaces". Smashing Magazine, 15 Nov 2016. https://www.smashingmagazine.com/2016/11/true-lies-of-optimistic-user-interfaces/
- S29 Raskin, A. "Never Use a Warning When you Mean Undo". A List Apart, 21 Jul 2007. https://alistapart.com/article/neveruseawarning/
- S30 Nielsen, J. "Progressive Disclosure". NN/g, 3 Des 2006. https://www.nngroup.com/articles/progressive-disclosure/
- S31 Kaplan, K. "Designing Empty States in Complex Applications: 3 Guidelines". NN/g, 19 Sep 2021. https://www.nngroup.com/articles/empty-state-interface-design/
- S32 Neusesser, T. & Sunwall, E. "Error-Message Guidelines". NN/g, 14 Mei 2023. https://www.nngroup.com/articles/error-message-guidelines/
- S33 Laubheimer, P. "Executing UX Animations: Duration and Motion Characteristics". NN/g, 9 Feb 2020. https://www.nngroup.com/articles/animation-duration/
- S34 Kendrick, A. "Mobile-App Onboarding: An Analysis of Components and Techniques". NN/g, 21 Jun 2020. https://www.nngroup.com/articles/mobile-app-onboarding/
- S35 Kendrick, A. "Microinteractions in User Experience". NN/g, 21 Okt 2018. https://www.nngroup.com/articles/microinteractions/

**Lapangan: platform, lokasi, luring**
- S36 MDN, "Screen Wake Lock API". https://developer.mozilla.org/en-US/docs/Web/API/Screen_Wake_Lock_API
- S37 W3C, "Geolocation", Candidate Recommendation Snapshot, 26 Mar 2026. https://www.w3.org/TR/geolocation/
- S38 GPS.gov, "GPS Accuracy". https://www.gps.gov/gps-accuracy
- S39 Purfürst, T. "Evaluation of Static Autonomous GNSS Positioning Accuracy Using Single-, Dual-, and Tri-Frequency Smartphones in Forest Canopy Environments". *Sensors*, 2022. https://pmc.ncbi.nlm.nih.gov/articles/PMC8838512/
- S40 Mozilla Bugzilla 1482733, "Geolocation.watchPosition() doesn't work in background on Android O and above". https://bugzilla.mozilla.org/show_bug.cgi?id=1482733
- S41 Progressier, "Geolocation" (kemampuan PWA). https://progressier.com/pwa-capabilities/geolocation
- S42 MDN, "Background Synchronization API". https://developer.mozilla.org/en-US/docs/Web/API/Background_Synchronization_API
- S43 Kurtuldu, M. & Steiner, T. "Offline UX design guidelines". web.dev, 10 Nov 2016. https://web.dev/articles/offline-ux-design-guidelines
- S44 OpenTopoMap, "About" (lisensi dan ketentuan pemakaian). https://opentopomap.org/about
- S45 OpenStreetMap Foundation, "Tile Usage Policy". https://operations.osmfoundation.org/policies/tiles/

**Risiko, kepercayaan, dan penjelasan**
- S46 Gigerenzer, G., Hertwig, R., van den Broek, E., Fasolo, B. & Katsikopoulos, K. V. "'A 30% chance of rain tomorrow': How does the public understand probabilistic weather forecasts?". *Risk Analysis* 25, 623–629 (2005). https://onlinelibrary.wiley.com/doi/abs/10.1111/j.1539-6924.2005.00608.x
- S47 Avalanche.org, "North American Public Avalanche Danger Scale". https://avalanche.org/avalanche-encyclopedia/human/resources/north-american-public-avalanche-danger-scale/
- S48 Budescu, D. V., Broomell, S. & Por, H.-H. "Improving Communication of Uncertainty in the Reports of the Intergovernmental Panel on Climate Change". *Psychological Science*, 2009. https://journals.sagepub.com/doi/10.1111/j.1467-9280.2009.02284.x
- S49 van der Bles, A. M., van der Linden, S., Freeman, A. L. J. & Spiegelhalter, D. J. "The effects of communicating uncertainty on public trust in facts and numbers". *PNAS*, 2020 (via ringkasan pencarian; halaman PNAS menolak alat pengambil). https://www.pnas.org/doi/10.1073/pnas.1913678117
- S50 van der Bles, A. M. dkk. "Communicating uncertainty about facts, numbers and science". *Royal Society Open Science* 6(5), 181870 (2019). https://royalsocietypublishing.org/rsos/article/6/5/181870/95102/Communicating-uncertainty-about-facts-numbers-and
- S51 Harley, A. "Trustworthiness in Web Design: 4 Credibility Factors". NN/g, 8 Mei 2016. https://www.nngroup.com/articles/trustworthy-design/
- S52 Fogg, B. J. dkk. "Stanford Guidelines for Web Credibility". Stanford Persuasive Technology Lab. https://credibility.stanford.edu/guidelines/index.html
- S53 Amershi, S. dkk. "Guidelines for Human-AI Interaction". CHI 2019 (Tabel 1 dibaca dari PDF resmi Microsoft Research). https://www.microsoft.com/en-us/research/wp-content/uploads/2019/01/Guidelines-for-Human-AI-Interaction-camera-ready.pdf
- S54 Google PAIR, *People + AI Guidebook*, "Explainability + Trust". https://pair.withgoogle.com/chapter/explainability-trust/
- S55 Tintarev, N. & Masthoff, J. "Evaluating the effectiveness of explanations for recommender systems". *User Modeling and User-Adapted Interaction*, 2012; serta "A Survey of Explanations in Recommender Systems", ICDE Workshops 2007 (via ringkasan pencarian). https://link.springer.com/article/10.1007/s11257-011-9117-5

**Personalisasi, motivasi, pola gelap**
- S56 Schade, A. "Customization vs. Personalization in the User Experience". NN/g, 10 Jul 2016. https://www.nngroup.com/articles/customization-personalization/
- S57 Sailer, M., Hense, J. U., Mayr, S. K. & Mandl, H. "How gamification motivates". *Computers in Human Behavior* 69, 371–380 (2017) (temuan via cuplikan). https://doi.org/10.1016/j.chb.2016.12.033
- S58 Silverman, J. & Barasch, A. "On or Off Track: How (Broken) Streaks Affect Consumer Decisions". *Journal of Consumer Research* 49(6), 1095–1117 (2023). https://academic.oup.com/jcr/article-abstract/49/6/1095/6623414
- S59 Mathur, A. dkk. "Dark Patterns at Scale: Findings from a Crawl of 11K Shopping Websites". CSCW 2019; taksonomi dibaca dari Brignull, *Deceptive Patterns*, bab 13. https://arxiv.org/pdf/1907.07032 · https://deceptive.design/book/contents/chapter-13/

**Aksesibilitas**
- S60 W3C WAI, "What's New in WCAG 2.2". https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/
- S61 W3C, "Web Content Accessibility Guidelines (WCAG) 2.2", Recommendation 12 Des 2024. https://www.w3.org/TR/WCAG22/
- S62 W3C WAI, "WCAG 3 Introduction". https://www.w3.org/WAI/standards-guidelines/wcag/wcag3-intro/
- S63 W3C, "W3C Accessibility Guidelines (WCAG) 3.0", Working Draft 10 Sep 2026. https://www.w3.org/TR/wcag-3.0/
- S64 W3C, Understanding SC 2.5.7 Dragging Movements. https://www.w3.org/WAI/WCAG22/Understanding/dragging-movements.html
- S65 W3C, Understanding SC 2.5.8 Target Size (Minimum). https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html
- S66 W3C, Understanding SC 2.4.11 Focus Not Obscured (Minimum). https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html
- S67 W3C, Understanding SC 3.2.6 Consistent Help. https://www.w3.org/WAI/WCAG22/Understanding/consistent-help.html
- S68 W3C, Understanding SC 3.3.7 Redundant Entry. https://www.w3.org/WAI/WCAG22/Understanding/redundant-entry.html
- S69 W3C, Understanding SC 3.3.8 Accessible Authentication (Minimum). https://www.w3.org/WAI/WCAG22/Understanding/accessible-authentication-minimum.html
- S70 W3C, Understanding SC 2.5.1 Pointer Gestures. https://www.w3.org/WAI/WCAG22/Understanding/pointer-gestures.html
- S71 W3C, Understanding SC 4.1.3 Status Messages. https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html
- S72 W3C WAI, "Complex Images" (Images Tutorial). https://www.w3.org/WAI/tutorials/images/complex/
- S73 National Park Service, "Web map accessibility" (NPMap). https://www.nps.gov/maps/web/accessibility
- S74 Elavsky, F., Bennett, C. & Moritz, D. "How accessible is my visualization? Evaluating visualization accessibility with Chartability". *Computer Graphics Forum* 41(3), EuroVis 2022. https://www.domoritz.de/papers/2022-Chartability.pdf
- S75 MDN, "prefers-reduced-motion". https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion
- S76 Apple. Human Interface Guidelines, "Accessibility" (data JSON resmi). https://developer.apple.com/design/human-interface-guidelines/accessibility
- S77 Apple. Human Interface Guidelines, "Dark Mode" (data JSON resmi). https://developer.apple.com/design/human-interface-guidelines/dark-mode
- S78 Android Developers, "Make apps more accessible" (target sentuh 48 dp). https://developer.android.com/guide/topics/ui/accessibility/apps
- S79 Harley, A. "Touch Targets on Touchscreens". NN/g, Mei 2019, merujuk Parhi, Karlson & Bederson, "Target size study for one-handed thumb use on small touchscreen devices" (MobileHCI 2006). https://www.nngroup.com/articles/touch-target-size/
- S80 Chrome for Developers, "Chrome 146 release notes" (stabil 10 Mar 2026); Blink "Intent to Ship: meta name=text-scale" (16 Jan 2026). https://developer.chrome.com/release-notes/146 · https://groups.google.com/a/chromium.org/g/blink-dev/c/0yp2ygJK5HE
- S81 Roselli, A. "Honoring Mobile OS Text Size". 6 Feb 2026. https://adrianroselli.com/2026/02/honoring-mobile-os-text-size.html
- S82 Appt, "Font size" (statistik aksesibilitas Belanda). https://appt.org/en/stats/font-size
- S83 MDN, "Vibration API". https://developer.mozilla.org/en-US/docs/Web/API/Vibration_API

**Hambatan situasional**
- S84 Sarsenbayeva, Z. dkk. "Situational Impairments to Mobile Interaction in Cold Environments". UbiComp 2016, 85–96 (via ringkasan pencarian). https://findanexpert.unimelb.edu.au/scholarlywork/1189980-situational-impairments-to-mobile-interaction-in-cold-environments
- S85 Sarsenbayeva, Z., Kostakos, V. & Goncalves, J. "Situationally-Induced Impairments and Disabilities Research". Lokakarya CHI 2019. https://arxiv.org/abs/1904.06128

**Metode evaluasi**
- S86 Nielsen, J. "Why You Only Need to Test with 5 Users". NN/g, 18 Mar 2000. https://www.nngroup.com/articles/why-you-only-need-to-test-with-5-users/
- S87 Sauro, J. "Measuring Usability with the System Usability Scale (SUS)". MeasuringU, 3 Feb 2011. https://measuringu.com/sus/
- S88 Sharfina, Z. & Santoso, H. B. "An Indonesian adaptation of the System Usability Scale (SUS)". ICACSIS 2016 (via ringkasan pencarian). https://scholar.ui.ac.id/en/publications/an-indonesian-adaptation-of-the-system-usability-scale-sus/
- S89 Sauro, J. "10 Things to Know About the Single Ease Question (SEQ)". MeasuringU, 30 Okt 2012. https://measuringu.com/seq10/
- S90 Flaherty, K. "Cognitive Walkthroughs". NN/g, 13 Feb 2022. https://www.nngroup.com/articles/cognitive-walkthroughs/
- S91 Moran, K. & Gordon, K. "How to Conduct a Heuristic Evaluation". NN/g, 25 Jun 2023. https://www.nngroup.com/articles/how-to-conduct-a-heuristic-evaluation/
- S92 Nielsen, J. "Severity Ratings for Usability Problems". NN/g, 1 Nov 1994. https://www.nngroup.com/articles/how-to-rate-the-severity-of-usability-problems/
- S93 Laubheimer, P. "Tree Testing". NN/g, 6 Agu 2023. https://www.nngroup.com/articles/tree-testing/

**Riset internal**
- S94 `docs/riset/2026-09-23-konsolidasi/mode_gelap_perangkat.md` §4 (NN/g Dark Mode, Piepenbrock 2013, Dobres 2017, Purdue 2021) dan §6 (StatCounter Agustus 2026: Android 77,07%, iOS 22,88%; viewport 360×800).

**Metode riset ini.** Sekitar 60 pencarian dan pengambilan halaman pada 25 Sep 2026, dibagi tujuh pertanyaan (heuristik; navigasi; pola interaksi; lapangan; personalisasi dan etika; aksesibilitas; evaluasi). Prioritas sumber: standar dan pedoman primer (W3C, Apple, Google/Android, MDN), NN/g, lalu jurnal dan konferensi (CHI, CSCW, UbiComp, Risk Analysis, PNAS, JCR). Bukti lokal dibaca dari repo pada hari yang sama. Celah yang diketahui: tidak ada studi primer tentang sarung tangan dan layar sentuh; tidak ada data cakupan sinyal di kawasan gunung Indonesia; angka Sailer dkk., Sharfina dan Santoso, Tintarev dan Masthoff, van der Bles dkk. (PNAS), dan Sarsenbayeva dkk. (2016) berasal dari ringkasan, bukan teks penuh.
