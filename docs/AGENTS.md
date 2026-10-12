# Kesepakatan Kerja Tim Memount

Dokumen ini dibaca setiap anggota tim, manusia maupun agen AI, sebelum mengubah apa pun di
repo. Isinya cara kita bekerja: apa yang dituju pemilik, apa yang butuh izin, bagaimana kita
riset, menguji, mengaudit, dan melapor. Aturan di sini dirangkum dari pemiliknya dan
ditautkan; bila rangkuman ini berbeda dari pemiliknya, pemiliknya yang berlaku. Kutipan
visi, misi, urutan prioritas, dan kata-kata pemilik dijaga identik dengan `docs/PRD.md` oleh
`TeamDocsIndexTest`.

Disusun atas arahan pemilik 26 September 2026 (spec v3 bagian 17.1), keputusan DK-53.

## Cara memakai dokumen ini

Urutan baca untuk sesi atau anggota baru:

1. `CLAUDE.md`: kontrak operasi yang berlaku di setiap sesi.
2. `HANDOVER.md` bagian 0: keadaan terkini, arahan terakhir pemilik, dan prompt untuk melanjutkan.
3. Dokumen ini.
4. `docs/PRD.md` sebelum perubahan produk, domain, atau UX; `DESIGN.md` sebelum pekerjaan antarmuka.

Indeks semua dokumen ada di `docs/README.md`. Setiap aturan punya satu pemilik:

| Aturan | Pemilik |
|---|---|
| R-001 sampai R-014 dan R-022 (domain) | `.claude/rules/mountain-core.md` |
| R-015 sampai R-017 (desain) | `DESIGN.md` |
| R-018 sampai R-021, R-023, R-024 (operasi) | `CLAUDE.md` |
| BR-01 sampai BR-16 (aturan bisnis) | `docs/PRD.md` §132 |
| Data pribadi, penghapusan akun, retensi | `docs/KEBIJAKAN-DATA-PRIBADI.md` |
| Lapisan kode dan aturan arsitektur | `docs/ARCHITECTURE.md` |
| Skema dan migrasi | `database/README.md` |
| Klasifikasi skill | `docs/MATRIKS-SKILL.md` |
| Pelaporan kerentanan dan rahasia | `SECURITY.md` |

## Visi, misi, dan ekspektasi pemilik

**Pernyataan merek pemilik** (verbatim, `docs/PRD.md` §1):

> "MEMOUNT adalah platform perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit, preparation, readiness, field experience, trust, community, dan progression dalam satu perjalanan utuh. MEMOUNT membantu saya menemukan perjalanan gunung yang cocok untuk saya."

**Visi** (§4):

> Membantu pendaki mengambil keputusan pendakian yang lebih terinformasi dengan menghubungkan profil pendaki, karakteristik jalur, rencana perjalanan, dan kondisi terbaru dalam satu alur yang dapat dijelaskan.

**Misi** (§5):

1. Mempermudah pemilihan jalur berdasarkan konteks pengguna.
2. Memberikan rekomendasi yang dapat dijelaskan.
3. Mengubah rekomendasi menjadi rencana persiapan.
4. Membedakan informasi resmi, terverifikasi, komunitas, dan interpretasi sistem.
5. Menyediakan informasi penting menjelang dan selama pendakian.
6. Membentuk lingkaran umpan balik lewat laporan kondisi jalur.

**North star** (§6): membantu pengguna berpindah dari "ingin mendaki" menjadi "memahami jalur
yang dipilih, persiapan yang dibutuhkan, kondisi yang perlu diperiksa, dan hasil perjalanan."

**Ekspektasi pemilik** (verbatim, §160):

> "aplikasi web gunung yang memadukan antara aplikasi web atau aplikasi yang popular dan sudah banyak user nya dengan strava dan traveloka, bagaimana user riset di masing-masing aplikasi nya dengan itu saya ingin mengcompare ke 3 itu di padukan dan menerapkan pada aplikasi gunung."

> "saya mengutamakan untuk pertama imk dan ui/ux"

> "impact dari interaksi manusia komputer, ui, dan ux di semua refrensi yang saya cantumkan dari hasil riset, tujuan, dan kesepakatan diatas itu sama dengan aplikasi web yang akan kita bangun."

> "yang saya lihat masih sama Penilaian "masih CRUD sederhana". yang saya rasakan hanya update fitur crud itupun sedikit dan tidak semua fitur nya tidak dapat berfungsi dengan baik."

Artinya: bukan menyerupai rupa rujukan, melainkan mencapai dampak IMK, UI, dan UX yang setara
dengan AllTrails, Strava, dan Traveloka. Halaman yang hanya menampilkan dan mengubah baris data
tanpa menjawab satu pun dari lima pertanyaan pendaki (jalur mana yang sebaiknya didaki, mengapa
jalur itu cocok, bagaimana merencanakan dan bersiap, apa status jalur terkini, bagaimana
melihat perkembangan diri) belum memenuhi ekspektasi ini (§160, "Tesis"). Tujuh dampak
perilaku yang dituju (confidence, efficiency, continuity, clarity, context, exploration,
focus) tercantum di §160 dan berstatus hipotesis sampai divalidasi (§113).

**Arahan yang melahirkan dokumen ini** (verbatim, 26 September 2026, spec v3 bagian 17.1):

> "[...] tambahkan folder docs berupa design md, product md, prd md, dan agent md sebagai aturan untuk rekan satu team lainnya setelah di repo standart disini agar visi, misi, ekspektasi, dan kesepakatan nya sama serta kriteria" pengerjaan lainnya seperti selalu riset detail dan mendalam melalui analisis brainstorming lalu cara audit nya dan kriteria" lainnya yang sudah menjadi kesepakatan disini pastikan detail dan lengkap. [...]"

## Urutan prioritas

Ketika dua kebutuhan bertabrakan, yang lebih atas menang (§160):

1. IMK/HCI;
2. UI/UX;
3. logika produk dan arsitektur informasi;
4. arsitektur dan skalabilitas;
5. keamanan, privasi, kepatuhan;
6. keandalan dan penanganan kegagalan;
7. kesiapan operasional dan observability;
8. manajemen dan konsistensi data;
9. pemasaran, SEO, pertumbuhan.

Urutan ini mengatur prioritas kerja, bukan izin melanggar aturan: aturan kanonis R-001 sampai
R-024 dan BR-01 sampai BR-16 tetap berlaku pada setiap tingkat.

## Kesepakatan kerja dan izin

| Tindakan | Aturannya | Pemilik aturan |
|---|---|---|
| Commit | Izin pemilik untuk setiap commit sampai pemilik mencabut arahan ini. `CLAUDE.md` R-018 membolehkan commit lokal, tetapi arahan pemilik terbaru lebih ketat, dan yang lebih ketat berlaku. Pencabutannya dicatat di `HANDOVER.md` bagian 0 | §161, `HANDOVER.md` bagian 0 |
| Push, merge, tag, rilis, force push, menulis ulang riwayat | Persetujuan eksplisit pemilik. Sebelum push: test, lint dan format, tinjau diff, pindai rahasia, verifikasi migrasi | R-018 |
| Akses Supabase, termasuk membaca kuncinya | Konfirmasi pemilik lebih dulu; lingkungan dan operasi disebut; rahasia tidak pernah dicetak | R-019, `HANDOVER.md` bagian 3 |
| Kredensial | Boleh dipakai, tidak pernah ditulis ke skrip, kode, test, dokumen, atau pesan commit, dan tidak ditampilkan di terminal; tempatnya hanya `.env` | R-019, `SECURITY.md` |
| Migrasi di lingkungan pemilik | Izin per migrasi; diuji di SQLite dan PostgreSQL CI lebih dulu; statusnya diverifikasi sesudahnya. `.claude/settings.json` menolak `php artisan migrate` hanya di Claude Code; agen lain dan manusia tidak dihalangi alat apa pun, jadi izin pemilik per migrasi adalah satu-satunya pagar | R-019, R-024, DK-23, `database/README.md` |
| Menambah atau melepas dependensi, runtime maupun dev | Izin pemilik | DK-24, spec v3 Q3 dan Q17 |
| Mengubah versi kerangka | Izin pemilik; verifikasi `php -v`, `composer -V`, `php artisan --version`, dan `composer show laravel/framework` sebelum perubahan aplikasi | R-020 |
| Mengubah arah produk: Route Fit, batas MVP dan MVP+, navigasi, hierarki otoritas, privasi atau penghapusan, arsitektur basis data, semantik penyedia luar; menambah fitur besar, monetisasi, atau kompetisi sosial | Usulan yang menyebut aturan sekarang, alasan, bukti, alternatif, dampak, risiko, rekomendasi, dan kebutuhan persetujuan; lalu governance record | R-021, §130, §146 |
| Operasi destruktif di produksi | Persetujuan tepat sebelum eksekusi | R-019 |
| Memasang atau memperbarui skill pihak ketiga | Hasil pemindai keamanan dan isi SKILL.md dibaca lebih dulu | DK-57 |
| `npx ngodingpakeai plan get` | Tidak dijalankan di root: perintah itu menulis berkas PRD versi server dan `features/` | GR-04 |

**Kesepakatan kerja lain** (`HANDOVER.md` bagian 3, §161, dan arahan pemilik yang dikutip di bawah beserta tanggalnya; dokumen ini adalah catatan repo untuk kutipan itu):

- **Riset mendalam dan brainstorming sebelum memutuskan, bukan menebak.** Landasan kerja pemilik (23 September 2026, verbatim): "saya ingin anda selalu riset detail dan mendalam di semua aspek dan kriteria untuk mengembangkan web app ini yang disesuaikan/kompatibel dengan tools atau framework yang kita gunakan secara maksimal. Pastikan untuk selalu mencari, mendapat, menerapkan informasi terkini, terbaru, sehingga sesuai/kompatibel dengan user sekarang." Sumber primer terkini (dokumentasi resmi, W3C, OWASP, NIST, regulasi Indonesia) dicocokkan dengan versi yang benar-benar terpasang (`composer show`, `package.json`), bukan klaim blog.
- **Keputusan delegasi.** Bila pemilik menjawab pertanyaan keputusan dengan "riset" atau kalimat landasan kerja di atas, keputusan diambil dari riset terkini yang bersumber, lalu dicatat "diputuskan atas delegasi pemilik" beserta sumbernya supaya dapat dibatalkan. Delegasi tidak mencakup push, akses Supabase, atau operasi destruktif.
- **Uji dan periksa silang, buktikan sebelum melapor.** Temuan dibuktikan dengan pengukuran (test, hitungan kueri, kueri basis data, rasio kontras yang dihitung) sebelum disebut masalah. Hal yang dicurigai lalu terbukti bukan masalah ikut dilaporkan, supaya tidak menjadi temuan palsu.
- **Semua skill yang terpasang dinilai relevansinya** (inti, pendukung, lintas, tidak relevan) sebelum bekerja, dan yang relevan diterapkan (`CLAUDE.md` bagian 7, `docs/MATRIKS-SKILL.md`).
- **Setiap tugas diakhiri evaluasi, rencana berikutnya, dan rekap berpersentase** (format di bawah).
- **Belajar di setiap tugas.** Pelajaran lintas sesi yang tidak ada di repo disimpan di memori agen; yang menyangkut tim ditulis ke dokumen pemiliknya.
- **Lima prinsip rekayasa** dijawab untuk setiap perubahan (bagian di bawah).
- **Bekerja dengan NgodingPakeAI.** Satu tugas per putaran lewat `task next`; setiap tugas dipetakan ke kode yang sudah ada dan memakai backend nyata, bukan tiruan; berhenti dan lapor saat `layer` atau `phase.current` berubah; sinkron ringkasan akhir (`sync` tanpa `--plan`) hanya setelah seluruh tugas selesai.

## Alur kerja: riset lebih dulu

Skill berawalan `superpowers:` dan `ecc:` adalah plugin Claude Code yang dipasang per pengguna, bukan isi repo. Bila
plugin itu tidak ada, teks setiap langkah di bawah tetap menjadi prosedurnya.

1. **Pahami dan riset.** Skill `superpowers:brainstorming` mengubah arahan menjadi pemahaman bersama lewat pertanyaan satu per satu; riset memakai `ecc:deep-research` atau `ecc:search-first` dengan sumber primer. Keputusan yang milik pemilik (R-021) diajukan sebagai pertanyaan dengan pilihan dan rekomendasi, bukan diputuskan diam-diam.
2. **Spec.** Ditulis di `docs/superpowers/specs/` dengan nama berawalan tanggal: arahan pemilik verbatim, temuan berstatus FAKTA dengan bukti, keputusan ber-ID (DK), izin yang dibutuhkan (Q), risiko, dan cara verifikasi. Pemilik menyetujui spec sebelum rencana ditulis.
3. **Rencana.** `superpowers:writing-plans` menulis rencana di `docs/superpowers/plans/`: tugas kecil, test lebih dulu, berkas milik setiap tugas, fokus tinjauan. Pemilik memilih cara eksekusi (subagen atau langsung).
4. **Eksekusi dengan TDD.** `superpowers:test-driven-development`: test ditulis dan dijalankan sampai gagal, implementasi paling sederhana, test lulus. Eksekusi lewat `superpowers:subagent-driven-development` atau `superpowers:executing-plans`. Subagen hanya menyunting berkas miliknya; berkas bersama dipegang koordinator (daftar di rencana induk v3).
5. **Verifikasi.** `superpowers:verification-before-completion`: perintah dijalankan dan keluarannya dibaca sebelum klaim apa pun; runtime nyata diperiksa (peramban 390 px dan 1440 px, klik-lewat setiap kontrol); paritas PostgreSQL bila skema tersentuh.
6. **Tinjauan.** `superpowers:requesting-code-review`, lalu periksa silang produk, UX dan IMK, arsitektur, data, keamanan dan privasi, keandalan, performa, aksesibilitas, testing, dan SEO (`CLAUDE.md` bagian 7).
7. **Antislop akhir dan rekap.** Delivery Gate antislop, rekap berpersentase, dan `HANDOVER.md` bagian 0 diperbarui di akhir setiap gelombang.

Setiap cacat ditangani dengan `superpowers:systematic-debugging` (bukti, hipotesis, uji), dan
setiap cacat produksi yang diperbaiki dilindungi test regresi (R-024).

## Skill: kapan dipakai dan urutan wewenang

Urutan wewenang ketika saran skill bertentangan (DK-57):

1. **Arahan pemilik dan aturan kanonis**: kata-kata dan keputusan pemilik, R-001 sampai R-024, BR-01 sampai BR-16.
2. **`DESIGN.md`**: token dan turunannya.
3. **Saringan antislop**: menyaring, tidak menciptakan arah.
4. **Saran skill**: diambil mekanismenya, bukan rupanya.

| Skill | Kapan dipakai | Batasnya |
|---|---|---|
| `antislop` dengan `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | Selama bekerja dan sekali lagi sebelum menyatakan selesai; Delivery Gate untuk setiap pekerjaan antarmuka dan teks | Saringan, tidak menciptakan arah |
| `impeccable` | Membentuk, mengkritik, mengaudit, dan memoles antarmuka; membaca `PRODUCT.md` dan `DESIGN.md` di root | Arah dari `DESIGN.md`; hasilnya disaring antislop |
| `ui-ux-pro-max` | Pembanding dan pemeriksa: pola UX, daftar periksa aksesibilitas, palet dan pasangan huruf sebagai rujukan | Tidak mengganti DK-38; langkah "Persist Design System" ditulis ke `DESIGN.md`, bukan folder baru |
| `design-taste-frontend` | Setiap pekerjaan antarmuka: perombakan layar yang ada dengan audit lebih dulu, halaman publik (halaman depan, halaman jalur publik), dan pemeriksaan pra-kirim anti-generik | Menurut cakupannya sendiri bukan untuk dasbor, tabel data, dan alur bertahap; di layar itu yang diambil hanya pemeriksaannya. Rekomendasi pustakanya (Motion, GSAP, Tailwind v4) tidak dipakai (DK-24, R-020); resep huruf, warna, dan geraknya kalah oleh `DESIGN.md` |
| `emil-design-eng` | Detail poles dan keputusan gerak | Gerak dial 2 dan daftar tertutup spec v3 bagian 8.6; `prefers-reduced-motion` dihormati |
| `apple-design` | Gestur, pegas, lembar bawah, material | Kaca paling banyak dua elemen per layar, tidak di atas peta, kartu, status resmi, atau laporan komunitas |
| `tailwindcss-development` | Setiap kelas Tailwind 4.3 | Tanpa `dark:` dan warna palet mentah; token dari `resources/css/app.css` |
| `laravel-best-practices` | Setiap perubahan PHP: controller, model, migrasi, request, policy | Konvensi berkas tetangga; API sesuai versi terpasang |
| `testing-best-practices` | Sebelum menulis atau meninjau test | Suite SQLite bukan bukti PostgreSQL (R-024) |

Pada pekerjaan antarmuka, saran `ui-ux-pro-max`, `design-taste-frontend`, `impeccable`, dan
`antislop` dibandingkan lalu digabung dengan urutan di atas; saran yang ditolak ditulis
alasannya di rekap. Matriks lengkap semua skill terpasang, kelasnya, dan status pelacakannya
di git (Q19) ada di `docs/MATRIKS-SKILL.md`.

## Tingkat bukti

Klaim selalu dipisahkan menjadi FACT, DECISION, INFERENCE, HYPOTHESIS, RECOMMENDATION, dan
OPEN ISSUE; bukti yang ambigu berstatus PERLU KLARIFIKASI (R-023). **Tanpa bukti, tidak ada
PASS.** Bukti yang sah: jalur berkas dan rentang baris; nama kelas, service, migrasi, tabel,
atau indeks; nama rute; jalur view; nama test; keluaran runtime; hasil kueri; bukti peramban.
Kode yang ada, tangkapan layar yang rapi, atau suite lokal yang hijau saja bukan bukti
keberhasilan, dan hasil riset pengguna maupun metrik UX tidak pernah dikarang.

Suite SQLite yang hijau tidak membuktikan kebenaran di PostgreSQL dan Supabase (R-024).
Tingkat selesai L0 sampai L4 ada di §161: fitur dinyatakan selesai minimal L3 (diuji dengan
data nyata di PostgreSQL dan PostGIS, integrasi CI lulus), dan permukaan utama menuju L4
(diverifikasi manusia di peramban dengan data realistis).

## Cara audit

- **Audit kelengkapan** (atas permintaan pemilik): `docs/AUDIT-PROTOCOL.md`, checklist A sampai N, standar bukti, dan skor lulus dibagi semua butir yang berlaku. Butir PERLU KLARIFIKASI tidak dihitung lulus; ambang sehat 80%, perlu perbaikan 50% sampai 79%, kritis di bawah 50%; temuan kritis keamanan, privasi, integritas data, atau runtime dilaporkan terpisah dari skor. Kode tidak diubah sebelum rencana audit disetujui.
- **Audit antislop:** skill `antislop` dan Delivery Gate-nya, laporan PASS atau FAIL per butir dengan bukti; laporan bertanggal disimpan di `docs/audit/anti-slop/`.
- **Tinjauan keamanan:** untuk perubahan yang menyentuh input, autentikasi, otorisasi, unggahan, lokasi, atau rahasia, pakai skill `security-review` dan `ecc:laravel-security`. Test keamanan kritis sebelum rilis MVP ada di `CLAUDE.md` bagian 4 (auth bypass, IDOR, eskalasi hak, mass assignment, unggahan, batas laju, kebocoran rahasia, RLS, lokasi privat, penghapusan akun). `.github/workflows/security.yml` menjalankan `composer audit`, `npm audit`, dan `SecretsNotInRepoTest`; pelaporan kerentanan mengikuti `SECURITY.md`.
- **Audit aksesibilitas:** target WCAG 2.2 AA (`CLAUDE.md` bagian 6). `ColourContrastTest` menghitung kontras; pemeriksaan manual mencakup jalur keyboard (Tab, Enter, Escape), fokus terlihat, zoom 200% dan reflow 320 px, `prefers-reduced-motion`, `forced-colors`, dan pembaca layar menurut `docs/PROTOKOL-UJI-KEGUNAAN.md` bagian 4; skill `ecc:accessibility` dan `ecc:frontend-a11y`.
- **Uji kegunaan:** `docs/PROTOKOL-UJI-KEGUNAAN.md`, yaitu evaluasi heuristik oleh anggota tim secara sendiri-sendiri, uji tugas dengan lima pendaki (dua di antaranya pemula), SUS dengan 12 sampai 14 responden, dan telusur pembaca layar. Hasilnya tidak pernah dikarang.

## Definisi selesai

Sebuah tugas selesai bila (`CLAUDE.md` bagian 7): persyaratan terimplementasi; kriteria
penerimaan lulus; validasi dan otorisasi ada; aturan data ditegakkan; galat dan fallback
tertangani; test yang relevan lulus; keamanan diuji; aksesibilitas dan tampilan seluler
diperiksa; antislop selesai; runtime diverifikasi; dokumentasi diperbarui; tidak ada cacat
kritis tersisa; maksud produk diperiksa silang.

Sebuah gelombang selesai bila (rencana induk v3): suite Unit dan Feature hijau, `npx tsc
--noEmit` nol galat, `npm run build` sukses, Pint bersih, tangkapan 390 px dan 1440 px
disandingkan dengan gambar acuan di `docs/desain/prototype/`, klik-lewat setiap kontrol,
Delivery Gate antislop, dan `HANDOVER.md` bagian 0 diperbarui. Produk tidak selesai hanya
karena halaman render, CRUD berjalan, tangkapan layar rapi, atau test hijau (`CLAUDE.md`
bagian 8).

## Kriteria mutu

Rubrik penilaian pemilik (20 September 2026) dipakai sebagai daftar periksa audit berulang:

- **Sistem:** accuracy, reliability, security, maintainability, tepat waktu, relevan.
- **UI/UX:** clarity, konsistensi, responsif, feedback, accessibility; ditambah riset pengguna, beban kognitif, hierarki visual, dan pencegahan galat.

Ukuran pengalaman di `CLAUDE.md` bagian 8 (discoverability, comprehension, comparability,
confidence, preparedness, continuity) dan tujuh dampak perilaku di §160 melengkapi rubrik ini.

## Lima prinsip rekayasa

Arahan pemilik 24 September 2026 (verbatim): "selalu menerapkan dan menambahkan prinsip
proper architecture, scalable system, secure by design, stable, and reliable". Setiap
perubahan dijawab dengan lima pertanyaan ini sebelum dilaporkan, dan jawabannya masuk rekap
(§160, `HANDOVER.md` bagian 3 butir 9):

| Prinsip | Pertanyaan yang dijawab |
|---|---|
| Proper architecture | Apakah setiap konsep punya satu sumber kebenaran? Contoh: nama produk hanya di `config('app.name')`, palet hanya di token `app.css`. |
| Scalable system | Apakah jumlah kueri dan ukuran respons tetap datar saat data bertambah, dan state tidak menempel pada satu proses? |
| Secure by design | Apakah input tidak dipercaya, daftar putih dipakai alih-alih daftar hitam, otorisasi di server, endpoint tanpa autentikasi dibatasi laju, dan tidak ada rahasia di kode? |
| Stable | Apakah kegagalan terkendali (peta gagal, cuaca tidak tersedia, JavaScript belum jalan) dan halaman pertama benar tanpa skrip? |
| Reliable | Apakah klaimnya dibuktikan test yang menghitung, runtime yang dipotret, dan paritas PostgreSQL bila skema tersentuh (R-023, R-024)? |

## Format rekap

Setiap tugas diakhiri rekap dengan susunan ini. Persentase selalu disertai metode hitungnya,
supaya dapat diperiksa ulang.

```text
Rekap: <tugas>, <tanggal>

Progres: <n> dari <total> (<persen>). Metode: <yang dihitung dibagi apa>, misalnya langkah
  bercentang dibagi total langkah tugas di rencana, tugas gelombang yang lulus dengan bukti
  dibagi total tugas gelombang, atau butir audit lulus dibagi butir yang berlaku. Butir yang
  gagal atau PERLU KLARIFIKASI tidak dihitung lulus.
Selesai: <apa yang dikerjakan>
Bukti: <nama test, perintah dan keluarannya dengan angka persis, jalur berkas dan baris>
Masalah dan akar masalah: <apa yang rusak dan kenapa>
Solusi: <apa yang dilakukan>
Dicurigai lalu terbukti bukan masalah: <supaya tidak menjadi temuan palsu>
Sisa: <yang belum dikerjakan dan kenapa>
Risiko: <yang dapat menggigit berikutnya>
Pelajaran: <yang dipelajari di tugas ini>
Lima prinsip: proper architecture <jawaban>; scalable <jawaban>; secure by design
  <jawaban>; stable <jawaban>; reliable <jawaban>
Rencana berikutnya: <langkah dan izin yang dibutuhkan>
```

## Protokol tahan-sesi

- `HANDOVER.md` bagian 0 selalu memuat arahan terakhir pemilik (verbatim), aliran kerja yang sedang berjalan beserta berkas miliknya, bukti terakhir (angka test dan build), keputusan terbuka, dan satu blok "Tempelkan ini di terminal baru" (§161). Bagian itu diperbarui di akhir setiap gelombang.
- Spec dan rencana di `docs/superpowers/` adalah sumber urutan kerja; `HANDOVER.md` menunjuk ke sana dan tidak menyalinnya.
- Sesi baru dimulai dengan menempelkan blok prompt dari `HANDOVER.md` bagian 0, lalu memeriksa keadaan nyata (`git status`, suite, `npx tsc --noEmit`) sebelum melanjutkan.
- Catatan kerja subagen tinggal di `.superpowers/` (diabaikan git); pelajaran lintas sesi yang tidak ada di repo disimpan di memori agen.
- Dokumen bertanggal tidak ditulis ulang; keadaan yang berubah dicatat di dokumen hidup dan di `HANDOVER.md`.

## Konvensi bahasa dan commit

- Bahasa Indonesia untuk dokumen, teks antarmuka, dan rekap, dengan sapaan "Anda". Kode, perintah, dan nama pustaka ditulis apa adanya; nama pengenal mengikuti berkas tetangga (banyak nama domain berbahasa Indonesia).
- Tanpa em dash (antislop R-02; `DesignDirectionTest` memeriksanya di kode dan `DESIGN.md`). Tanpa klaim "aman" dan "layak berangkat" (R-012, R-014). Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok"; "Belum dinilai" untuk UNKNOWN; tanpa skor atau persen untuk kecocokan dan kesiapan (R-004, R-005).
- Konvensi rujukan: "§N" hanya untuk ID di `docs/PRD.md`; bagian dokumen lain ditulis "bagian N" beserta nama berkasnya, misalnya "`HANDOVER.md` bagian 3".
- Di dokumen hidup, jalur `.md` di antara backtick adalah jalur dari root repo dan harus ada; `PrdReferenceIntegrityTest` menolaknya bila tidak.
- Commit hanya atas izin pemilik. Satu commit untuk satu perubahan yang dapat dijelaskan dalam satu kalimat, dengan awalan `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`, atau `ci:` dan ringkasan berbahasa Indonesia yang menjelaskan kenapa, bukan mengulang diff. Commit memakai pathspec supaya perubahan lain yang sudah di-stage tidak ikut.
- Branch dibuat dari branch kerja aktif (saat ini `hardening`); push dan merge mengikuti R-018.
