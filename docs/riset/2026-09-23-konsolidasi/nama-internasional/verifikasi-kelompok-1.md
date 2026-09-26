# Verifikasi Mendalam Kelompok 1: STEPLARAS, BENDPIN, LIKUPOS, KELOKPOS, PINTANDA

> **Status:** RISET, bukan keputusan. Semua cek dijalankan 23 September 2026, 16.43 sampai 19.22 UTC. Uji dengar-eja
> K1b belum dijalankan (butuh pengguna nyata), jadi putusan yang bergantung padanya ditandai sementara.
> Nama merek adalah arah produk, jadi keputusan dan governance record-nya milik pemilik (R-021, `PRD.md` §130 dan §146).
> **Label bukti (R-023):** [FAKTA] hasil cek atau kutipan sumber; [INFERENSI] simpulan dari fakta; [HIPOTESIS] dugaan
> yang belum diuji; [REKOMENDASI] saran; [ISU TERBUKA] belum terjawab. UNVERIFIED berarti tidak diverifikasi langsung.
> **Batas klaim:** "404" pada RDAP berarti registri tidak punya catatan domain itu. Itu sinyal, bukan bukti domain bisa
> didaftarkan. Tidak ada kalimat di berkas ini yang menyatakan domain tersedia atau merek bebas.
> **Hanya cek baca:** tidak ada domain didaftarkan, akun dibuat, pihak lain dihubungi, atau formulir yang membuat rekaman
> dikirim. Tidak ada pencarian di halaman registrar. Pemilik akun dan pengembang perorangan tidak disebut namanya.

**Hasil singkat.**
- **PINTANDA: LOLOS BERSYARAT** (v2 8 dari 12, v3 16 dari 26). Syarat: pendapat Konsultan KI soal merek TANDA aktif di
  kelas 9 dan 42 di AS, UE, Inggris, dan Australia; lulus uji dengar-eja pada penutur Spanyol atau Portugis
  ("pintada") dan Indonesia; Instagram @pintanda sudah dipakai.
- **STEPLARAS: TERTAHAN** (v2 8, v3 12). "laras" juga berarti laras senapan (ID, MS); K1a = 0 sehingga G4 berlaku
  sementara; SELARAS terdaftar di kelas 9 (perangkat lunak) di Indonesia.
- **BENDPIN: GUGUR** (v2 6, gugur G3 dan G4; v3 14, G4 sementara). Terdengar dan dipakai sebagai "bent pin" (cacat
  konektor); BANDPIN terdaftar kelas 9 dan 42 di Norwegia dan aplikasi BandPin aktif.
- **KELOKPOS: GUGUR** (v2 8, v3 11, G1) dan **LIKUPOS: GUGUR** (v2 7, v3 11, G1 dan G3). "POS" adalah singkatan kasar
  umum dalam bahasa Inggris; LAKUPOS & LOGO terdaftar kelas 42 di Indonesia. Dari pasangan ini tidak satu pun
  direkomendasikan; bila harus menyimpan satu pembanding, KELOKPOS.
- Semua 30 domain yang dicek tanpa catatan RDAP; tidak ada merek identik untuk kelima nama di PDKI, USPTO, TMview, atau WIPO.

Isi:
1. Metode bersama, kontrol, dan hasil lintas nama (domain, toko aplikasi, handle, basis data merek)
2. STEPLARAS
3. BENDPIN
4. LIKUPOS
5. KELOKPOS (dengan rekomendasi satu dari pasangan LIKUPOS dan KELOKPOS)
6. PINTANDA
7. Peringkat kelompok 1
8. Daftar tunda dan skrip uji dengar-eja

---

## 1. Metode bersama, kontrol, dan hasil lintas nama

### Takeaway
Semua cek dijalankan 23 September 2026 antara 16.43 dan 19.30 UTC. Kelima nama tidak punya catatan RDAP di 30 domain
yang dicek (kontrol positif dan fiktif bekerja), tidak dipakai sebagai judul aplikasi di App Store (10 negara) maupun
Google Play, dan tidak punya merek identik di PDKI, USPTO, TMview, atau WIPO. Yang membedakan kelima nama adalah merek
dan aplikasi yang *mirip*, makna per bahasa, dan kekuatan merek. Beberapa sumber memblokir akses otomatis
(OpenCorporates, Etherscan, sebagian pencarian WIPO); celah itu dicatat di bawah dan di bagian 8.

### Cited Findings

#### 1.1 Domain (RDAP registri, bukan pencarian registrar)
- [FAKTA] Bootstrap IANA diambil 16.43.23 UTC (HTTP 200, publikasi "2026-09-16T19:00:03Z";
  [IANA dns.json](https://data.iana.org/rdap/dns.json)). Endpoint: .com `https://rdap.verisign.com/com/v1/`, .app
  `https://pubapi.registry.google/rdap/`, .id dan .co.id `https://rdap.pandi.id/rdap/`.
- [FAKTA] Kontrol positif (16.43.23 sampai 16.43.24 UTC) menjawab 200: verisign.com (terdaftar 1995-06-02), google.app
  (2018-03-29), pandi.id (2013-04-14), telkom.co.id (1995-05-18). Kontrol fiktif menjawab 404: qzxvkontrol7h2k.com,
  .app, .id, .co.id, getqzxvkontrol7h2k.com, dan qzxvkontrol7h2kapp.com (16.43.24 sampai 16.43.25 UTC).

| Nama | .com | .app | .id | .co.id | get+nama.com | nama+app.com | Jam UTC |
|---|---|---|---|---|---|---|---|
| STEPLARAS | 404 | 404 | 404 | 404 | 404 | 404 | 16.43.25 sampai 16.43.27 |
| BENDPIN | 404 | 404 | 404 | 404 | 404 | 404 | 16.43.26 sampai 16.43.28 |
| LIKUPOS | 404 | 404 | 404 | 404 | 404 | 404 | 16.43.27 sampai 16.43.28 |
| KELOKPOS | 404 | 404 | 404 | 404 | 404 | 404 | 16.43.28 sampai 16.43.29 |
| PINTANDA | 404 | 404 | 404 | 404 | 404 | 404 | 16.43.28 sampai 16.43.29 |

Pola URL: `https://rdap.verisign.com/com/v1/domain/steplaras.com`,
`https://pubapi.registry.google/rdap/domain/steplaras.app`, `https://rdap.pandi.id/rdap/domain/steplaras.id`,
`https://rdap.pandi.id/rdap/domain/steplaras.co.id` (nama lain mengikuti pola yang sama). 404 hanya berarti registri
tidak punya catatan; itu bukan bukti domain bisa didaftarkan, dan harga atau status premium tidak diperiksa.

#### 1.2 Toko aplikasi
- [FAKTA] App Store lewat iTunes Search API (`entity=software`, `limit=50`), 10 etalase (ID, US, GB, AU, SG, MY, DE, FR,
  NL, JP), 16 istilah (nama persis, bentuk terpisah, dan satu bentuk dekat per nama), 16.43.30 sampai 16.53.30 UTC.
  Contoh: [pintanda, ID](https://itunes.apple.com/search?term=pintanda&country=id&entity=software&limit=50).
  Dihitung "memuat nama" bila nama muncul di judul atau nama penjual setelah spasi dan tanda baca dibuang.

| Nama | Istilah yang dicek | Hasil memuat nama (10 etalase) | Catatan |
|---|---|---|---|
| STEPLARAS | steplaras, step laras, steplara | 0 | "steplaras" dan "steplara" memberi 0 hasil di semua etalase |
| BENDPIN | bendpin, bend pin, bentpin, bent pin | 0 | hasil US, GB, dan AU hanya aplikasi lain yang mirip ejaan |
| LIKUPOS | likupos, liku pos, likopos | 0 | etalase US, GB, dan AU mengembalikan 40 sampai 49 hasil tak terkait |
| KELOKPOS | kelokpos, kelok pos, kelok | 0 untuk kelokpos; "kelok" hanya cocok sebagai potongan kata pada "KeLoKe" | "KeLoKe" adalah aplikasi Social Networking dari pengembang perorangan, rilis 2026-07-17 ([App Store ID](https://apps.apple.com/id/app/keloke/id6789189426)) |
| PINTANDA | pintanda, pin tanda, pintada | 0 | "pintada" memberi 45 sampai 50 hasil per etalase, tidak satu pun berjudul "Pintada" |

- [FAKTA] Google Play dibaca dari DOM yang dirender peramban headless terisolasi (`hl=en&gl=US` dan `hl=id&gl=ID`),
  18.50.34 sampai 18.53.07 UTC, untuk nama persis dan bentuk terpisah. Tidak ada judul yang memuat kelima nama.
  Dua temuan sampingan:
  - kueri "bendpin" (US) menampilkan aplikasi **BandPin**
    ([Google Play](https://play.google.com/store/apps/details?id=com.spoonproductions.bandpin.app&hl=en&gl=US));
    lihat bagian 3;
  - kueri "liku pos" (ID) menampilkan aplikasi kasir: "Kledo POS", "Griyo Pos - Kasir dan Cashflow", "POS2U - Point of
    Sale", "Jubelio POS", dan "Moka POS - Aplikasi Kasir" (PT GoTo Gojek Tokopedia Tbk)
    ([pencarian Play](https://play.google.com/store/search?q=liku%20pos&c=apps&hl=id&gl=ID)); lihat bagian 4.

#### 1.3 Handle (tanpa login, dibanding kontrol)
Dibaca dari peramban headless terisolasi (profil sementara): TikTok dari data `webapp.user-detail`, Instagram dan X dari
judul halaman, YouTube dari status HTTP.

| Handle | TikTok | Instagram | X | YouTube | Jam UTC |
|---|---|---|---|---|---|
| kontrol ada @tiktok | ada (95.900.000 pengikut) | ada | ada | ada (200) | 16.43.56 sampai 16.44.09 |
| kontrol fiktif @qzxvkontrol7h2k | tidak ditemukan (10221) | "Profile isn't available" | "User Profile Not Found" | 404 | 16.44.24 sampai 16.44.34 |
| @steplaras | tidak ditemukan (10221) | "Profile isn't available" | "User Profile Not Found" | 404 | 16.44.47 sampai 16.44.58 |
| @bendpin | tidak ditemukan (10221) | **dipakai** (judul "bend pin (@bendpin)") | "User Profile Not Found" | **dipakai** (200) | 16.45.05 sampai 16.45.16 |
| @likupos | tidak ditemukan (10221) | "Profile isn't available" | "User Profile Not Found" | 404 | 16.45.23 sampai 16.45.34 |
| @kelokpos | tidak ditemukan (10221) | "Profile isn't available" | "User Profile Not Found" | 404 | 16.45.41 sampai 16.45.52 |
| @pintanda | tidak ditemukan (10221) | **dipakai** (judul "(@pintanda)") | "User Profile Not Found" | 404 | 16.46.05 sampai 16.46.17 |

"Tidak ditemukan" tanpa login bukan bukti handle bisa didaftarkan (K7 dilaporkan, tidak diskor).

#### 1.4 Basis data merek: cara cek dan cakupan
- [FAKTA] **PDKI** ([pencarian](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=steplaras&page=1)):
  peramban headless terisolasi tanpa akun, memakai aksi server milik halaman itu sendiri. Mode Normal memakai
  `match_phrase` pada `nama_merek` (sama dengan UI, 50 data per halaman, semua halaman dibaca); daftar "Pencarian Yang
  Mungkin Mirip" memakai mode `FONETIK`, lalu setiap tetangga fonetik dicari dan disaring ke kelas 9, 35, 39, 41, 42, 45.
  Jalan 18.14.09 sampai 18.16.39 UTC, cek lanjutan 18.17.53 sampai 18.18.10 UTC. Untuk "steplaras", hasil UI (16.44 UTC)
  dan hasil aksi server sama: 0 data dan daftar fonetik yang sama. PDKI menafikan: "Keputusan tidak boleh dibuat
  berdasarkan pencarian ini saja."
- [FAKTA] **WIPO Global Brand Database** ([Brand name search](https://branddb.wipo.int/en/quicksearch)): formulir halaman
  dengan strategi Embedded, Phonetic, dan Fuzzy; kartu hasil dibaca sampai 150 per kueri (urutan "Most relevant first").
  Jalan 18.06 sampai 18.48 UTC. Sejak sekitar 18.44 UTC situs menampilkan "I'm not a robot / Verification failed. Try
  again later.", sehingga sebagian kueri diulang pada putaran berikutnya (lihat tiap nama).
- [FAKTA] **USPTO** ([tmsearch](https://tmsearch.uspto.gov/search/search-information)): kueri dijalankan dari halaman itu
  sendiri ke API pencariannya (`/prod-stage-v1-0-0/tmsearch`) dengan bentuk kueri UI (`match_phrase` pada wordmark), filter
  **Live** (`LD=true`), dan kelas internasional; untuk stem dipakai kueri awalan (prefix) pada wordmark. Jalan 18.08.46
  sampai 18.10.00 dan 18.32.49 sampai 18.33.15 UTC. Untuk "steplaras", UI menampilkan "No results found" (18.06 UTC).
- [FAKTA] **TMview**
  ([results](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=C&basicSearch=steplaras)): API
  hasil milik halaman (`/tmview/api/search/results`) dengan kriteria C (contains), F (fuzzy), I (is), B (begins with),
  W (word), serta filter kantor, kelas Nice, dan status (Registered, Filed). Jalan 18.28.31 sampai 18.29.17 UTC. Daftar
  kantor pendukung di halaman TMview memuat EM (EUIPO), GB, AU, MY, US, dan WO, tetapi **tidak** memuat SG maupun ID;
  data Singapura dan Indonesia karena itu diambil dari WIPO Global Brand Database dan PDKI.

#### 1.5 Perusahaan, produk, kripto, dan web
| Sumber | STEPLARAS | BENDPIN | LIKUPOS | KELOKPOS | PINTANDA | Jam UTC |
|---|---|---|---|---|---|---|
| [Companies House](https://find-and-update.company-information.service.gov.uk/search/companies?q=pintanda) | 0 | 0 | 0 | 0 | PINTANDANIP LTD (SC650059), bubar 24 Juni 2025, SIC 56302 (pub dan bar) | 18.56.40 sampai 18.57.36 |
| [SEC EDGAR](https://www.sec.gov/cgi-bin/browse-edgar?company=steplaras&action=getcompany) | tidak ada | tidak ada | tidak ada | tidak ada | tidak ada | 18.56.40 sampai 18.57.46 |
| [GitHub API](https://api.github.com/search/repositories?q=bendpin+in:name), repositori dan pengguna | 0 dan 0 | 1 repo "BendPineapple" dan 1 akun pribadi | 0 dan 0 | 0 dan 0 | 0 dan 0 | 18.42.17 sampai 18.42.49 |
| [Product Hunt](https://www.producthunt.com/search?q=steplaras) | "No products found" | tidak ada produk bernama sama | "No products found" | "No products found" | "No products found" | 18.54.32 sampai 18.56.12 |
| [CoinGecko](https://api.coingecko.com/api/v3/search?query=pintanda), koin, bursa, NFT | 0 | 0 | 0 | 0 | 0 | 18.42.22 sampai 18.57.19 |
| [DexScreener](https://api.dexscreener.com/latest/dex/search?q=pintanda), pasangan token | 0 | 0 | 0 | 0 | 0 | 18.56.42 sampai 18.57.18 |

- [FAKTA] OpenCorporates menampilkan "HAProxy Challenge ... Please solve this Captcha" (18.54 sampai 18.56 UTC) dan
  Etherscan menampilkan verifikasi Cloudflare; keduanya tidak diselesaikan, jadi **UNVERIFIED**.
- [FAKTA] Pencarian web: kuota alat WebSearch sesi ini habis (200 dari 200). Pengganti yang berhasil: Yahoo! JAPAN lewat
  skrip yang membaca data halaman (15 kueri, 18.37.57 sampai 18.41.09 UTC) dan Brave lewat WebFetch (2 kueri sebelum
  HTTP 429). Bing lewat peramban headless (`cc=ID` dan `cc=US`) hanya memberi hasil yang tidak terkait, jadi tidak dipakai.
  Hasil per nama ada di bagian 2 sampai 6.

#### 1.6 Kamus dan sumber makna
- [FAKTA] Wiktionary REST API, Urban Dictionary API (hanya entri kata persis dihitung), cermin KBBI kbbi.web.id
  (UNVERIFIED terhadap KBBI VI), PRPM DBP (Kamus Dewan Edisi Keempat), KBJI (Jawa), BASAbali (Bali), dan DLE RAE
  (Spanyol), 18.34.19 sampai 18.59.11 UTC.
- [FAKTA] KBBI VI resmi menjawab "Moda Terbatas ... Silakan mendaftar atau masuk" (18.40 UTC,
  [kbbi.kemendikdasmen.go.id](https://kbbi.kemendikdasmen.go.id/entri/laras)); tidak ada akun dibuat, jadi KBBI resmi
  tidak dipakai.
- [FAKTA] Kamus Madura daring ditemukan lewat Yahoo! JAPAN (19.20 UTC): Kamus Bahasa Madura-Indonesia di
  [kata.web.id](https://kata.web.id/kamus/madura-indonesia) (sumber datanya tidak dijelaskan, jadi UNVERIFIED). Dicek
  19.21 UTC untuk 18 bentuk (kelima nama, bagian-bagiannya, "pintada", "ben", "tan"): hanya "tanda" punya entri (lihat
  6.3); sisanya 404. Kamus Sunda daring belum ditemukan (pencarian kedua dijawab HTTP 429).

### Inferences
- [INFERENSI] Domain dan handle tidak lagi membedakan kelima nama (semua K5 = 2). Keputusan ada di merek mirip, makna,
  dan kekuatan merek, sesuai bobot rubrik v3.
- [INFERENSI] Karena TMview tidak memuat SG dan ID, cek tiga lapis (PDKI, WIPO, TMview) tetap diperlukan; satu basis data
  saja tidak cukup.

### Gaps
- OpenCorporates dan Etherscan terblokir captcha; pengganti (Companies House, SEC EDGAR, CoinGecko, DexScreener) tidak
  setara cakupannya.
- Pencarian web umum tidak berorientasi Indonesia atau AS secara penuh: Yahoo! JAPAN berperingkat Jepang, dan Brave hanya
  memberi 2 kueri sebelum dibatasi.
- Kamus Sunda daring tidak ditemukan, dan kamus Madura yang ditemukan tidak jelas sumbernya; makna kedua bahasa itu
  PERLU PENUTUR.

---

## 2. STEPLARAS

### Takeaway
STEPLARAS bersih di domain, toko aplikasi, handle, dan basis data merek (tidak ada merek identik di mana pun), tetapi
skornya turun dari 12 (saringan) menjadi **8 dari 12 (v2)** dan **12 dari 26 (v3)**. Tiga penyebab: (1) "laras" juga
berarti laras senapan dalam bahasa Indonesia dan Melayu, dan DJKI sendiri pernah mencatat terjemahan "LARAS + Logo :
Senapan", sehingga K2 menjadi 1; (2) campuran Inggris-Indonesia membawa gugus "st" serta koda p dan s, sehingga K1a = 0
dan G4 berlaku sementara sampai uji dengar-eja; (3) merek mirip aktif di kelas 9 dan 42: SELARAS (perangkat lunak, kelas 9,
Indonesia), STEPLER (aplikasi langkah, kelas 9/35/41/42, AS), dan LARAS (kelas 42 di UE dan Inggris, kelas 9 di Malaysia).
**Putusan: TERTAHAN** (lolos v2; gugur sementara G4 pada v3, menunggu K1b), dengan risiko merek dasar di DJKI yang perlu
pendapat Konsultan KI.

### Cited Findings

#### 2.1 Makna "laras", "lara", dan "step" (K2, rescoring)
- [FAKTA] Cermin KBBI (UNVERIFIED): "la·ras 1 n 1 (tinggi rendah) nada ...; tala ...; 2 kesesuaian; kesamaan ..."
  ([kbbi.web.id/laras](https://kbbi.web.id/laras), 18.34 UTC). Halaman cermin tidak memuat homonim "laras" untuk senjata.
- [FAKTA] Wiktionary, Indonesia: "scale, key, tone", "harmony, concord", "straight; long and cylindrical", dan
  **"rifle barrel"**; Melayu: **"barrel (of a gun or firearm)"**, "pitch; key; scale", "register; style; level (of language)"
  ([laras](https://en.wiktionary.org/wiki/laras), 18.34 UTC).
- [FAKTA] PRPM (Kamus Dewan Edisi Keempat): "1. bulat lurus atau bulat panjang ...; 2. bahagian senjata api spt pistol,
  senapang dan meriam, yg berbentuk spt paip (pembuluh) tempat laluan keluar peluru ...: ... sepucuk senapang ~ dua"
  ([PRPM laras](https://prpm.dbp.gov.my/Cari1?keyword=laras), 18.39 UTC).
- [FAKTA] PDKI mencatat merek "LARAS" (kelas 16, kantong plastik, Didaftar, D002009033859, IDM000346523, berlaku sampai
  2029-10-21, pemilik perorangan) dengan kolom translasi **"LARAS + Logo : Senapan"** (19.10 UTC). Artinya bacaan
  "senapan" untuk "laras" tercatat di basis data DJKI sendiri.
- [FAKTA] Jawa (KBJI): "laras busur"; "laras 1 nada; 2 laras gamelan; 3 sesuai dg aturan"; "laras (raras) indah,
  menyenangkan"; "laras, nglaras mengembara mencari sesuatu"; laras senapan dalam bahasa Jawa adalah "wuluh"
  ([KBJI laras](https://kbji.kemendikdasmen.go.id/kata/laras), 18.40 UTC). Bali (BASAbali): "laras" = "body movement /
  gerak tubuh" ([BASAbali Laras](https://dictionary.basabali.org/w/index.php?title=Laras&uselang=en), 18.41 UTC).
- [FAKTA] Potongan "lara" di dalam STEPLARAS: cermin KBBI "la·ra kl a 1 sedih; susah hati; 2 sakit"
  ([kbbi.web.id/lara](https://kbbi.web.id/lara)); KBJI "lara susah, sedih ... lara sakit" dan "lara (rara) 1 perawan"
  ([KBJI lara](https://kbji.kemendikdasmen.go.id/kata/lara)); BASAbali "lara ... sick"
  ([BASAbali Lara](https://dictionary.basabali.org/w/index.php?title=Lara&uselang=en)); 18.34 sampai 18.41 UTC.
- [FAKTA] "step": cermin KBBI "step /stép/ n Olr langkah (dalam senam irama dan lompat jangkit)"
  ([kbbi.web.id/step](https://kbbi.web.id/step)); Wiktionary Indonesia juga mencatat "convulsion" (kejang), Belanda "kick
  scooter", Ceko, Polandia, dan Turki "steppe" ([step](https://en.wiktionary.org/wiki/step)); 18.34 sampai 18.39 UTC.
- [FAKTA] Urban Dictionary: 0 entri untuk "steplaras"; 3 entri "laras" berupa definisi nama orang tanpa muatan kasar
  ([UD laras](https://www.urbandictionary.com/define.php?term=laras), 18.34 UTC).
- [FAKTA] Mandarin: Wiktionary "拉拉" = "lesbian" dan "to chat" ([拉拉](https://en.wiktionary.org/wiki/%E6%8B%89%E6%8B%89),
  18.58 UTC); transliterasi alami "-laras" (拉拉斯) memuat 拉拉.

#### 2.2 Merek (kelas 9, 35, 39, 41, 42, 45)
- [FAKTA] **PDKI** (18.14.22 sampai 18.15.06 UTC): "steplaras" dan "step laras" 0 data. Daftar Fonetik: STELARA, Selaras,
  SELARAS, selaras, SEELARAS, STELLARIS, STELLAMAS, SDM SELARAS, Eco-Selaras, GOLD SELARAS. Yang aktif di kelas inti:
  - **SELARAS**, kelas 9, Didaftar, **PT. TEMAN CEO INDONESIA**, DID2021049460, IDM001000233, diajukan 2021-02-08, berlaku
    sampai 2031-07-27, uraian "Perangkat lunak cloud, Perangkat lunak untuk POS (Point Of Sale) Terminal, Aplikasi
    perangkat lunak komputer ..." (19.10 UTC);
  - SELARAS, kelas 9 (sambungan dan kabel listrik), Didaftar, pemilik perorangan, D002009035644, sampai 2029-11-04;
    SELARAS kelas 16 dan 9, pemilik perorangan, DID2026061713, masih "Pemeriksaan Substantif" (diajukan 2026-06-25);
  - di kelas lain: SELARAS CONSULTING (41), SDM SELARAS (39, PT. Sidomulyo Selaras, Tbk), Eco-Selaras (36, 39, 43,
    PT. BANGUN ECO SELARAS), STELLAMAS (35).
- [FAKTA] PDKI, unsur: "laras" 110 data, yang aktif di kelas 9/42 hanya "Tata Laras Ruang" (kelas 42, perorangan);
  "selaras" 170 data, 8 aktif di kelas 9/42 (antara lain ARCADEliving selaras, KINARYA SELARAS + LOGO, Selaras Lawang Sewu
  & Logo); "step" 297 data, **22 aktif di kelas 9/42**, antara lain STEP (STEP WEALTH PTE. LTD., kelas 9, 16, 35, 36, 41,
  42), STEP FX (Apple Inc., kelas 9), Step Message (LY Corporation, kelas 9, 38, 42), KAO STEP (Kao Kabushiki Kaisha, 9).
- [FAKTA] **WIPO** ([Brand name search](https://branddb.wipo.int/en/quicksearch)): Embedded "steplaras" "No results found"
  (18.12.39 UTC); Phonetic 9 hasil, tidak satu pun di kelas inti (18.12.54 UTC); Fuzzy 525 hasil, 150 teratas dibaca, 7
  aktif di kelas inti dan semuanya berbunyi STELLAR/STELLARIS (misalnya STELLARDS, TMSSOFTWARE.COM B.V., merek UE
  019011602, kelas 9, 35, 42; STELLARIS, Leica Microsystems CMS GmbH, UK00801505634, kelas 9, 42) (18.13.09 UTC).
- [FAKTA] **USPTO** (Live): "steplaras" 0 dan "steplara" 0 (18.08 UTC; juga 0 termasuk yang mati, 18.09 UTC). Kueri awalan
  pada kelas 9/42 yang masih hidup: kata berawalan "step" **422**, "stepl" 10, "stepla" 1 (18.09 UTC). Yang paling dekat:
  **STEPLER**, Stepler AB (Swedia), kelas 9, 35, 41, 42, Reg. 7341448 (terdaftar 2024-04-02, SN 79327077), "downloadable
  application software for wireless devices for use in personal health data tracking, displaying physical fitness
  information ..."; lainnya STEPLOGIC (Rockwell Automation, Inc.), STEPLITEX (BEKINA NV), STEPLIFY (pedometer).
- [FAKTA] **TMview** (18.28.31 sampai 18.29.09 UTC): "steplaras" contains 0; fuzzy aktif di EM, GB, AU, MY kelas inti 0;
  fuzzy aktif kelas 9/42 semua kantor 0; "stepla" begins-with aktif kelas inti 6 (Steplab Ltd di GB kelas 41, STEPLAND di
  ES kelas 9, STEPLABS di IN kelas 9, STEPLADDER di US kelas 42). "laras" (is) aktif kelas inti 24, antara lain **LARAS**
  milik National Academy of Recording Arts & Sciences, Inc. di **EM 002255727 (kelas 42)**, **GB UK00902255727 (kelas
  42)**, dan **MY 02001678 (kelas 9)**, serta LARAS milik Laras Architects Sdn Bhd di MY (kelas 35 dan 42).

#### 2.3 Web, perusahaan, dan penggunaan
- [FAKTA] Yahoo! JAPAN "steplaras" (18.37.57 UTC): 8 hasil, semuanya tentang gim "Stellaris"; tidak ada yang memuat nama
  ([kueri](https://search.yahoo.co.jp/search?p=%22steplaras%22)). Brave "steplaras" dikoreksi otomatis menjadi "staples"
  karena tidak ada hasil persis ([kueri](https://search.brave.com/search?q=%22steplaras%22&source=web), sekitar 18.38 UTC).
- [FAKTA] Yahoo! JAPAN "step laras" (18.40.16 UTC): hanya panduan koreografi TikTok ("step: Laras") dan satu video produk;
  tidak ada merek atau produk bernama Step Laras.
- [FAKTA] Yahoo! JAPAN "steplaras app" (18.39.05 UTC): hasil didominasi aplikasi langkah dan imbalan: StepsApp, "Step App |
  Move Earn Repeat | Move-to-Earn App", dan "Stepler, Walk for Rewards" ([steplerapp.com](https://www.steplerapp.com/)).
- [FAKTA] Tidak ada perusahaan, repositori, produk Product Hunt, token, atau aplikasi bernama STEPLARAS (tabel 1.5 dan 1.2).

#### 2.4 Aksara lain (K9)
- [INFERENSI] Katakana ステップララス (7 mora: su-te-p-pu-ra-ra-su), hangul 스텝라라스, transliterasi Mandarin alami
  斯特普拉拉斯 (sī tè pǔ lā lā sī) yang memuat 拉拉. R dan L menyatu dalam serapan Jepang (lanskap §2.1), tetapi di nama ini
  keduanya tidak berpasangan kontras, jadi tidak menimbulkan tertukar.

### Inferences
- [INFERENSI] **K2 dinilai ulang dari 2 menjadi 1.** Makna laras senapan umum di bahasa Indonesia dan Melayu (tier A), tetapi
  bukan makna kasar, vulgar, atau sakral; itu asosiasi ringan tier A. Potongan "lara" (sakit, sedih) di bahasa Jawa, Bali,
  dan Indonesia klasik juga asosiasi ringan. Menurut aturan tier, skornya 1, bukan 0.
- [INFERENSI] **Apakah campuran Inggris-Indonesia melemahkan K1a?** Ya. Separuh Inggris membawa gugus awal "st" dan koda p,
  separuh Indonesia membawa koda s; ditambah huruf e. Menurut daftar K1a v3 itu tiga jenis masalah, jadi K1a = 0 dan G4
  berlaku sementara. Dalam bahasa Jepang nama menjadi 7 mora. Penutur Inggris juga cenderung melemahkan vokal suku akhir
  ("-rəs"), sehingga ejaan dengar seperti "Steplarus" atau "Steplaris" mungkin muncul [HIPOTESIS, diuji di K1b].
- [INFERENSI] **Apakah campuran itu melemahkan K8?** Tidak banyak. Bagi penutur Inggris "laras" tak bermakna, dan bagi
  penutur Indonesia "step" kata asing, sehingga gabungannya bukan frasa di bahasa mana pun. Tetapi pengguna Indonesia yang
  paham keduanya membaca "langkah yang selaras atau sesuai", yang dekat dengan fungsi inti Route Fit. Karena itu nama ini
  **sugestif (K8 = 1)**, bukan deskriptif dan bukan fanciful.
- [INFERENSI] **Apakah "step laras" deskriptif dalam bahasa Indonesia?** Tidak ditemukan sebagai frasa baku (PDKI 0 data,
  web hanya tutorial tari). Maknanya memuji ("langkah yang selaras"), bukan menyebut jasa perencanaan jalur, jadi risiko G5
  rendah. Yang lebih nyata adalah risiko **persamaan pada pokoknya** dengan SELARAS di kelas 9, karena mesin fonetik PDKI
  sendiri menempatkan SELARAS sebagai tetangga terdekat dan kedua nama berbagi unsur "laras".
- [INFERENSI] **Merek berawalan STEP di kelas 9 dan 42 sangat padat** (422 merek hidup di AS, 22 aktif di PDKI). Unsur
  STEP karena itu lemah, dan daya pembeda STEPLARAS bertumpu pada "LARAS", yang justru terdaftar milik pihak lain di UE,
  Inggris, dan Malaysia (kelas 42 dan 9). Ini tidak identik atau sangat mirip, jadi G3 tidak berlaku, tetapi K6 = 1.
- [INFERENSI] **K3 = 1 (samar):** di toko aplikasi, "step" terbaca sebagai penghitung langkah dan aplikasi move-to-earn
  (Step App, Stepler), yang membawa kesan angka dan imbalan; produk ini tidak menampilkan skor numerik publik.

#### 2.5 Skor
| Kriteria v2 | Skor | Dasar |
|---|---|---|
| K1 ucap-eja | 2 | tidak ada huruf dari daftar v2; tidak mudah tertukar dengan kata yang ada |
| K2 makna | 1 | laras senapan (ID, MS); lara = sakit, sedih (Jawa, Bali) |
| K3 konotasi | 1 | "step" terbaca penghitung langkah dan move-to-earn |
| K4 konsep dan pembeda | 1 | menopang "kecocokan", tetapi unsur STEP padat dan LARAS terdaftar pihak lain |
| K5 domain | 2 | semua 404 |
| K6 konflik | 1 | SELARAS (ID, kelas 9), STEPLER (US, 9/42), LARAS (EM dan GB kelas 42, MY kelas 9) |
| **Total v2** | **8 dari 12** | tidak ada gerbang v2 |

| Kriteria v3 | Skor | Bobot | Nilai | Dasar |
|---|---|---|---|---|
| K6 konflik | 1 | ×3 | 3 | mirip, tidak identik; G3 tidak berlaku |
| K8 kekuatan merek | 1 | ×2 | 2 | sugestif di ID dan MS ("langkah selaras"); tidak deskriptif |
| K2 makna | 1 | ×2 | 2 | asosiasi ringan tier A |
| K1a ucap-eja meja | 0 | ×2 | 0 | e, gugus "st", koda p dan s |
| K3 konotasi | 1 | ×1 | 1 | lihat K3 v2 |
| K4a kecocokan konsep | 1 | ×1 | 1 | cocok dengan "jalur yang sesuai", lemah untuk "pahami alasannya", kepercayaan, dan logo |
| K9 aksara | 1 | ×1 | 1 | 7 mora; transliterasi Mandarin alami memuat 拉拉 |
| K5 domain | 2 | ×1 | 2 | semua 404 |
| **Total v3** | | | **12 dari 26** | G4 sementara (K1a = 0), menunggu K1b |

K7: keempat handle tampak belum dipakai (1.3).

**Putusan STEPLARAS:** TERTAHAN. Lolos v2 (8), gugur sementara G4 pada v3 (12). Bila lolos K1b, masalah berikutnya
adalah pendapat Konsultan KI soal SELARAS kelas 9 di DJKI, karena penolakan merek dasar di Indonesia menjatuhkan semua
penunjukan Madrid selama 5 tahun (lanskap §3.1).

### Gaps
- Apakah DJKI akan menilai STEPLARAS "memiliki persamaan pada pokoknya" dengan SELARAS (kelas 9, perangkat lunak) belum
  bisa dijawab dari basis data; perlu Konsultan KI (PERLU KLARIFIKASI).
- Asosiasi 拉拉 dan "lara" perlu dikonfirmasi penutur Mandarin dan Jawa. Kamus Sunda tidak ditemukan; kamus Madura daring
  (kata.web.id, UNVERIFIED) tidak memuat "laras", "lara", "step", atau "steplaras" (19.21 UTC).
- WIPO Embedded "step laras" mengembalikan 18.601 hasil (19.10 UTC) karena mencocokkan salah satu kata; kartu tidak dibaca,
  jadi bentuk terpisah hanya diperiksa lewat PDKI, USPTO, dan TMview.

---

## 3. BENDPIN

### Takeaway
BENDPIN paling cocok dengan logo "Jalur Berpos" (kelok yang berakhir di penanda), tetapi gagal di dua tempat yang sulit
diperbaiki. Pertama, string persis "bendpin" sudah dipakai di web sebagai label cacat perangkat keras ("bendpin/broken
chip") dan diucapkan hampir sama dengan istilah teknik "bent pin", sehingga K1 v2 = 0 dan K1a v3 = 0. Kedua, ada nama
yang hanya berbeda satu huruf dan aktif di kelas 9 dan 42: merek **BANDPIN** terdaftar di Norwegia (2026) dan aplikasi
**BandPin** (penemuan musik berbasis peta) aktif di App Store dan Google Play. Instagram dan YouTube @bendpin sudah
dipakai. Skor: **6 dari 12 (v2, gugur G3 dan G4)** dan **14 dari 26 (v3, gugur sementara G4)**. **Putusan: GUGUR.**

### Cited Findings

#### 3.1 Apakah penutur Inggris mendengar "bent pin"?
- [FAKTA] Wikipedia "Bent pin analysis": analisis yang "examines more common connector failure modes associated with
  connector contacts" ([Bent pin analysis](https://en.wikipedia.org/wiki/Bent_pin_analysis), cuplikan Yahoo! JAPAN 18.40.29 UTC).
  Hasil lain untuk kueri yang sama: "Connector Pin Inspection: Bent, Missing and Recessed Pins"
  ([overview.ai](https://www.overview.ai/applications/connector-pin-inspection/)) dan "Visual Inspection of Pin Connectors,
  Detect Bent Pins" ([INEA](https://www.inea.eu/inspect/bent-pin/)).
- [FAKTA] Yahoo! JAPAN "bendpin" (18.38.11 UTC): 9 dari 10 hasil memuat string itu. Beberapa adalah unggahan jual-beli
  komponen PC di grup Facebook berbahasa Tagalog-Inggris yang memakai "bendpin" sebagai label cacat ("bendpin/broken chip",
  "bendpin / history of repair"); satu adalah manual teknis radio Angkatan Laut AS yang berbunyi "Do not b e n d p i n s"
  ([kueri](https://search.yahoo.co.jp/search?p=%22bendpin%22)). Nama pemilik unggahan tidak dicantumkan.
- [FAKTA] Brave "bendpin" (sekitar 18.38 UTC): "L-Bend Pin" (perangkat gerbang pertanian), "Big Bend National Park Pin"
  (toko resmi America's National Parks), "Big Bend Pin" (Etsy), dan utas Reddit r/arduino "Bend Pins?" tentang kaki
  komponen yang bengkok ([kueri](https://search.brave.com/search?q=%22bendpin%22&source=web)).
- [FAKTA] Yahoo! JAPAN "bendpin app" (18.39.20 UTC): "Bend Pin Tool 60pcs Curved Safety Pins" dan "Bend pin on backside of
  motherboard : r/PcBuild"; tidak ada aplikasi bernama Bendpin.
- [FAKTA] Wiktionary dan Urban Dictionary tidak memuat "bendpin" maupun "bent pin" (18.34 sampai 18.35 UTC).

#### 3.2 Makna bagian nama (K2)
- [FAKTA] "bend" (Wiktionary, 18.34 UTC): Inggris "to cause (something) to change its shape into a curve", "a curve";
  Norwegia Nynorsk "a bend"; Portugis "bend" (nada gitar); Serbo-Kroasia "band" (grup musik); Albania "pond; idle words;
  servant"; Kurdi Utara "slave" (Kurdi tidak masuk tier A atau B) ([bend](https://en.wiktionary.org/wiki/bend)).
  Urban Dictionary mencatat "bend" antara lain sebagai kota Bend, Oregon, dan "to get drunk"
  ([UD bend](https://www.urbandictionary.com/define.php?term=bend)).
- [FAKTA] "pin" (Wiktionary, 18.34 UTC): Inggris jarum pentul dan pasak; Swedia **"embarrassing"**, **"pain, torment"**, dan
  "very, really"; Inggris Tengah "pain, torture"; Belanda "peg, pin" dan PIN; Spanyol "pin, lapel pin, badge" dan PIN;
  Vietnam "battery"; romanisasi tidak baku untuk Mandarin pīn, pín, pǐn ([pin](https://en.wiktionary.org/wiki/pin)).
  Cermin KBBI: "pin n cak jarum penyemat; peniti; pasak" ([kbbi.web.id/pin](https://kbbi.web.id/pin)).

#### 3.3 Merek dan aplikasi mirip
- [FAKTA] **BANDPIN, Norwegia, kelas 9 dan 42**: TMview mencatat "BANDPIN | NO | kelas 9, 42 | Registered | diajukan
  2026-02-27 | 202602602" (18.29.00 UTC); WIPO Fuzzy "bendpin" mencatat "BANDPIN ... Norway ... Registered (May 21, 2026)
  ... 341952" (18.15.07 UTC). Pemilik tercatat perorangan atau tidak jelas, jadi tidak dicantumkan. USPTO "bandpin" Live
  kelas 9/42: 0 (18.33.09 UTC); TMview tidak menemukan BANDPIN di kantor lain.
- [FAKTA] **Aplikasi BandPin**: Google Play "BandPin helps you discover music, events, bands, and music services around
  you ... map-based discovery", 50+ unduhan (18.53.49 UTC,
  [Google Play](https://play.google.com/store/apps/details?id=com.spoonproductions.bandpin.app&hl=en&gl=US)); App Store
  kategori Music, rilis 2026-05-06, tersedia di etalase US, NO, ID, dan GB (18.53.51 UTC,
  [App Store](https://apps.apple.com/us/app/bandpin/id6760811351)). Penjual di App Store perorangan, jadi tidak dicantumkan.
- [FAKTA] **PDKI** (18.15.07 sampai 18.15.35 UTC): "bendpin", "bend pin", "bentpin", dan "bent pin" 0 data. Daftar Fonetik:
  BENDIN, BENDINI, bendino, Bendina, BENDINO, BENDI, BENIN, BENAPI, BENDIX, BEDWIN. Yang aktif di kelas inti: BENDIX (kelas
  9, Garrett Transportation I Inc.), BENDIX/KING (kelas 9, HONEYWELL INTERNATIONAL INC), Bendi Creative (kelas 42,
  perorangan), Dasteran Bendino (kelas 35, perorangan). Unsur "bend": BEND STUDIO (Sony Interactive Entertainment LLC,
  kelas 9, 41, 42); unsur "pin": 8 aktif di kelas 9/42, termasuk PIN milik Pinterest, Inc. (kelas 9 dan 42).
- [FAKTA] **WIPO**: Embedded "bendpin" "No results found" (18.14.01 UTC); Phonetic 278 hasil, 4 aktif di kelas inti
  (BENTOBEN di Jepang dan Inggris, kelas 9; PANDPINN di AS, kelas 9, Reg. 6593392); Fuzzy 925 hasil, 150 teratas dibaca,
  2 aktif di kelas inti (BANDPIN, BENDINI kelas 39 di Brasil).
- [FAKTA] **USPTO** (Live): "bendpin" 0 dan "bentpin" 0 (18.08.53 dan 18.08.58 UTC); awalan "bend" di kelas 9/42 114 merek;
  "bendp" 1: BENDPRO (Current Tech Corporation, kelas 9, Reg. 4811984, sistem kontrol mesin tekuk pipa); "bandp" 3:
  BANDPAY (BandPay.Me, LLC, perangkat lunak kelas 9), BANDPANG, BANDPHONES (18.09 sampai 18.33 UTC).
- [FAKTA] **TMview** (18.28.36 sampai 18.29.10 UTC): "bendpin" contains 0; fuzzy aktif di EM, GB, AU, MY kelas inti 0;
  fuzzy aktif kelas 9/42 semua kantor 4 (BENDLIN dan BENDLIN & TALAVERA di Paraguay kelas 42, BANDPIN di Norwegia, ENDPIN
  MUSIC PUBLISHING di AS kelas 9 dan 16); "bend pin", "bent pin", dan "bentpin" 0; awalan "bendp" 4 (BENDPRO di US dan
  WO, BENDPLUS di India dan Kanada).

#### 3.4 Aksara lain (K9)
- [INFERENSI] Katakana ベンドピン (5 mora), hangul 벤드핀, transliterasi Mandarin alami 本德品 (běn dé pǐn: "akar, kebajikan,
  mutu"). Suku "ben" juga bisa ditulis 笨 (bèn, bodoh) atau 奔 (bēn, melaju), tetapi keduanya bisa dihindari dengan pilihan
  aksara (UNVERIFIED, perlu penutur Mandarin).

### Inferences
- [INFERENSI] **K1 v2 = 0 (tertukar dengan frasa yang ada).** Dalam ucapan Inggris yang wajar, [d] sebelum [p] sering tidak
  dilepas, sehingga "bendpin" dan "bent pin" hampir sama bunyinya. Preseden rubrik: BENDMARK diberi K1 = 0 karena tertukar
  dengan "benchmark" (saringan §3.2); "bendpin" bahkan lebih dekat, dan string persisnya sudah dipakai sebagai label cacat.
- [INFERENSI] **K1a v3 = 0**: tertukar dengan "bent pin", ditambah huruf e, gugus "ndp", dan koda d. G4 berlaku sementara
  sampai K1b, tetapi uji dengar-eja justru diperkirakan menguatkan masalah ini [HIPOTESIS].
- [INFERENSI] **K2 = 1**: "bent pin" adalah asosiasi cacat yang tidak kasar atau sakral (asosiasi ringan tier A); makna
  Swedia "pin" (malu, siksa) adalah makna tier B yang jarang dipakai. Untuk produk yang menjual kepercayaan, asosiasi
  "komponen rusak" tetap merugikan, sehingga K4a juga turun.
- [INFERENSI] **K6 v2 = 0 dan G3 v2 berlaku.** BANDPIN berbeda satu huruf, berbunyi hampir sama ("band-pin" dan
  "bend-pin"), aktif di kelas 9 dan 42, dan dipakai aplikasi berbasis peta. Rubrik v2 tidak membatasi wilayah.
- [INFERENSI] **K6 v3 = 1, G3 v3 tidak berlaku.** BANDPIN hanya terdaftar di Norwegia (bukan ID, US, atau UE), dan BandPin
  adalah aplikasi musik, bukan pesaing langsung. Tetapi pemiliknya bisa memperluas perlindungan lewat Madrid ke UE dalam
  masa prioritas; itu risiko yang harus dicek ulang sebelum pengajuan [HIPOTESIS].
- [INFERENSI] **K8 = 1**: bagi perangkat lunak peta, "pin" menyebut fitur (penanda peta) dan "bend" menyebut geometri rute;
  gabungannya sugestif.
- [INFERENSI] **K4a = 1**: kecocokan dengan logo dan pilihan rute kuat, tetapi tidak menyentuh "pahami alasannya", dan
  bacaan "komponen rusak" bertentangan dengan kepercayaan.

#### 3.5 Skor
| Kriteria v2 | Skor | Dasar |
|---|---|---|
| K1 ucap-eja | 0 | tertukar dengan "bent pin" |
| K2 makna | 1 | asosiasi cacat "bent pin"; Swedia "pin" |
| K3 konotasi | 2 | tidak ada kecepatan, kompetisi, penaklukan, atau jaminan |
| K4 konsep dan pembeda | 1 | cocok dengan logo; kepercayaan dirusak bacaan cacat |
| K5 domain | 2 | semua 404 |
| K6 konflik | 0 | BANDPIN kelas 9/42 (Norwegia) dan aplikasi BandPin |
| **Total v2** | **6 dari 12** | **gugur G3 dan G4** |

| Kriteria v3 | Skor | Bobot | Nilai | Dasar |
|---|---|---|---|---|
| K6 konflik | 1 | ×3 | 3 | sangat mirip, tetapi di luar ID, US, UE; bukan pesaing langsung |
| K8 kekuatan merek | 1 | ×2 | 2 | sugestif untuk perangkat lunak peta |
| K2 makna | 1 | ×2 | 2 | asosiasi ringan tier A |
| K1a ucap-eja meja | 0 | ×2 | 0 | tertukar "bent pin"; e, gugus "ndp", koda d |
| K3 konotasi | 2 | ×1 | 2 | tidak ada |
| K4a kecocokan konsep | 1 | ×1 | 1 | logo dan rute kuat; alasan dan kepercayaan lemah |
| K9 aksara | 2 | ×1 | 2 | 5 mora; transliterasi alami bermakna positif |
| K5 domain | 2 | ×1 | 2 | semua 404 |
| **Total v3** | | | **14 dari 26** | G4 sementara (K1a = 0) |

K7: Instagram dan YouTube @bendpin dipakai; TikTok dan X tampak belum dipakai (1.3).

**Putusan BENDPIN:** GUGUR. v2 gugur G3 dan G4; v3 gugur sementara G4 dengan dugaan kuat gagal di K1b. Skor v3 yang lebih
tinggi dari STEPLARAS tidak mengubah putusan, karena masalahnya ada di gerbang, bukan di total.

### Gaps
- Uraian barang dan jasa merek BANDPIN di Norwegia belum dibaca dari register Norwegia; hanya kelas 9 dan 42 yang diketahui.
- Belum ada uji dengar dengan penutur Inggris; bunyi "bent pin" adalah INFERENSI fonetik yang didukung bukti pemakaian.
- WIPO Embedded "bent pin" mengembalikan 7.981 hasil (19.12 UTC) karena mencocokkan salah satu kata; kartu tidak dibaca.

---

## 4. LIKUPOS

### Takeaway
LIKUPOS paling dekat dengan konsep logo (liku jalan gunung yang berakhir di pos), tetapi gugur di dua gerbang. **G1:**
"POS" adalah singkatan kasar yang umum dalam bahasa Inggris (tier A): Wiktionary mencatat "(vulgar, of a person or thing)
Initialism of piece of shit", dan tiga entri teratas Urban Dictionary mendefinisikannya begitu; saringan memberi K2 = 2
walau sudah mencatat ini, dan skor itu kini dikoreksi menjadi 0. **G3:** PDKI memuat **LAKUPOS & LOGO** milik PT NTT DATA
INDONESIA, Didaftar di **kelas 42** (dan 35) sampai 2027-12-21, yang hanya berbeda satu huruf. Selain itu, pola "... Pos"
terbaca sebagai nama koran, layanan pos, atau aplikasi kasir, dan "berliku-liku" berarti berbelit-belit. Skor: **7 dari
12 (v2)** dan **11 dari 26 (v3)**. **Putusan: GUGUR.**

### Cited Findings

#### 4.1 "POS" dan "pos" lintas bahasa (K2, rescoring)
- [FAKTA] Wiktionary "POS" (19.11 UTC): "(retail) Initialism of point of sale or point of service" dan "(vulgar, of a person
  or thing) Initialism of piece of shit. Alternative form: P.O.S.", dengan kutipan film Men in Black (1997)
  ([POS](https://en.wiktionary.org/wiki/POS)).
- [FAKTA] Urban Dictionary "pos" dan "POS" (18.35 UTC): tiga entri teratas mendefinisikan "Piece of Shit", disusul "Parent
  Over Shoulder" dan "Positively Outstanding Service" ([UD pos](https://www.urbandictionary.com/define.php?term=pos)).
- [FAKTA] Spanyol "pos": "synonym of pues", "after, behind", dan "only used in en pos de (in pursuit of)"; Portugis: bentuk
  tak baku "pros"; Belanda: ikan ruffe; Indonesia dan Melayu: pos surat dan pos jaga
  ([pos](https://en.wiktionary.org/wiki/pos), 18.35 UTC). Tidak ada makna kasar Spanyol atau Portugis yang ditemukan.
- [FAKTA] Jerman: "Po m (strong, genitive Pos, plural **Pos**) buttocks" ([Po](https://en.wiktionary.org/wiki/Po),
  19.11 UTC). "Pos" berarti "pantat" (jamak), kata sehari-hari yang tidak kasar.

#### 4.2 "liku" dan "berliku-liku"
- [FAKTA] Cermin KBBI (UNVERIFIED): "li·ku Mk n kelok; lekuk; lengkong: jalan di daerah pegunungan banyak -- nya;
  ber·li·ku-li·ku v 1 banyak tikungan; berkelok-kelok ...; 2 **ki berbelit-belit**: urusan itu menjadi panjang dan ~ sukar
  menyelesaikannya" ([kbbi.web.id/liku](https://kbbi.web.id/liku), 18.35 UTC).
- [FAKTA] PRPM (Kamus Dewan Edisi Keempat): "1. selekoh (pd jalan, sungai, dll), kelok, siku ...; 2. tempat terpencil,
  ceruk ...; ~-~ hidup = ~-~ kehidupan **dugaan dan kesengsaraan dlm hidup**"
  ([PRPM liku](https://prpm.dbp.gov.my/Cari1?keyword=liku), 18.39 UTC).
- [FAKTA] KBJI (Jawa): "liku, ngliku **ar membohongi, menyesatkan**" (ar = arkais)
  ([KBJI liku](https://kbji.kemendikdasmen.go.id/kata/liku), 18.40 UTC). BASAbali: "Liku" adalah "tokoh penjelas watak yang
  berperan sebagai sosok yang humoris, centil" ([BASAbali Liku](https://dictionary.basabali.org/w/index.php?title=Liku&uselang=en),
  18.41 UTC). Wiktionary Melayu: "bend of a river, road; corner, isolated area" ([liku](https://en.wiktionary.org/wiki/liku)).

#### 4.3 Pola "... Pos" di Indonesia
- [FAKTA] "Jawa Pos adalah surat kabar harian yang berpusat di Kota Surabaya ... surat kabar dengan oplah terbesar di
  Indonesia" ([id.wikipedia Jawa Pos](https://id.wikipedia.org/wiki/Jawa_Pos), 18.58 UTC); "PT Pos Indonesia (Persero) ...
  badan usaha milik negara Indonesia yang terutama menyediakan layanan pos"
  ([id.wikipedia Pos Indonesia](https://id.wikipedia.org/wiki/Pos_Indonesia), 18.59 UTC). Saringan juga mencatat media
  "Batu Pos" (batupos.com) dan "Nalar Pos" (nalarpos.id) (`kandidat-dan-saringan.md` §2.3).
- [FAKTA] Di perangkat lunak, "POS" berarti kasir. Google Play "liku pos" (ID) menampilkan Kledo POS, Griyo Pos, POS2U,
  Jubelio POS, dan Moka POS (18.52 UTC, tabel 1.2). PDKI mencatat LINKPOS (kelas 9 dan 42, "aplikasi tentang sistem kasir,
  Aplikasi mesin kasir online", Didaftar sampai 2036-03-05) dan JituPOS (kelas 9, Didaftar), sedangkan SakuPOS (kelas 9 dan
  42) berstatus Ditolak (18.17 sampai 19.10 UTC). Di USPTO, 176 merek hidup di kelas 9/42 cocok dengan "pos", dan 79 dari
  100 teratas berakhiran POS untuk sistem kasir, misalnya RETAILZ POS, SUNFIRE POS, dan ROC POS (18.33 UTC).

#### 4.4 Merek
- [FAKTA] **PDKI** (18.15.37 sampai 19.10.40 UTC): "likupos" dan "liku pos" 0 data. Daftar Fonetik: LINKPOS, LIKUO, LIPOS,
  IKUOS, Lukudos, SIKUPIS, LAKUPON, JituPOS, SakuPOS, **LAKUPOS & LOGO**. Rincian LAKUPOS & LOGO, semuanya milik
  **PT NTT DATA INDONESIA**, diajukan 2017-12-21:
  - kelas 42, **Didaftar**, J002017068396, IDM000745686, berlaku sampai **2027-12-21**, "Situs internet/web site (Hosting
    computer sites), Menyediakan pencarian melalui internet ...";
  - kelas 35, Didaftar, J002017068551, IDM000738661, sampai 2027-12-21;
  - kelas 9, **Ditolak**, D002017068398.
  Unsur "liku": 5 data, tidak ada di kelas inti (LIKU ID kelas 30, LIKA LIKU kelas 36, dan lainnya).
- [FAKTA] **USPTO** (Live): "likupos" 0 dan "likopos" 0 (18.09 UTC); awalan "liku" di kelas 9/42: **LIKU**, JKT APPTech
  Limited, kelas 9 (SN 98327211, "Downloadable computer application software for mobile phones ...", diajukan 2023-12-22,
  hidup, belum terdaftar) dan kelas 38 (SN 98327216), serta LIKUOFIW (casing ponsel) (18.32.49 UTC).
- [FAKTA] **TMview** (18.28.40 sampai 18.29.12 UTC): "likupos" contains 0; fuzzy aktif di EM, GB, AU, MY kelas inti 0; fuzzy
  aktif kelas 9/42 semua kantor 0; "liku pos" 0; awalan "liku" aktif kelas inti 37 (antara lain LIKU di US, LiKU di KR kelas
  9, LIKUM milik LIKUM Group GmbH di EM, LiKuCo milik Shenzhen Meibangda E-Business Co.,Ltd. di GB kelas 9).
- [FAKTA] **WIPO**: Embedded "likupos" "No results found" (18.15.59 UTC); Phonetic 653 hasil, 150 teratas dibaca, 8 aktif di
  kelas inti dan tidak ada yang berbunyi dekat (LYKHUBS, LOGBIZ milik Sato Holdings, LAKEBASE milik Databricks, Inc.)
  (18.18.44 UTC); Fuzzy 372 hasil, 150 teratas dibaca, 8 aktif di kelas inti, termasuk **LAKUPOS & LOGO** (PT NTT DATA INDONESIA, kelas 35
  "Registered (May 18, 2020)" IDM000738661 dan kelas 42 "Registered (May 19, 2020)" IDM000745686), JituPOS (Indonesia,
  kelas 9), PICKUPOS (Not Just Snacks, Inc., AS, kelas 9 dan 42, pending), dan LIKPOK (Ganzhou Jingmike Education Network
  Technology Co., Ltd., AS, kelas 9) (19.14.53 UTC, diulang setelah captcha).

#### 4.5 Web, toko, dan pemakaian
- [FAKTA] Yahoo! JAPAN "likupos" (18.38.24 UTC): 9 hasil, tidak satu pun memuat nama. "liku pos" (18.40.43 UTC): artikel
  "Lika-liku Pos Direktur Teknik Liverpool" (Kompasiana) dan blog pendakian Gunung Mekongga: "Jalur yang dilewati cukup
  menanjak dan berliku-liku. Pos VII berada pada sebuah bukit kecil"
  ([kueri](https://search.yahoo.co.jp/search?p=%22liku+pos%22); nama blog pribadi tidak dicantumkan).
- [FAKTA] Tidak ada aplikasi, perusahaan, repositori, produk, atau token bernama LIKUPOS (tabel 1.2 dan 1.5); keempat
  handle tampak belum dipakai (1.3).

#### 4.6 Aksara lain (K9)
- [INFERENSI] Katakana リクポス (4 mora), hangul 리쿠포스, transliterasi Mandarin 利酷波斯 (lì kù bō sī; 波斯 = Persia). Suku
  "ku" bisa ditulis 哭 (menangis), tetapi mudah dihindari (UNVERIFIED, perlu penutur Mandarin).

### Inferences
- [INFERENSI] **K2 = 0, G1 berlaku.** Aturan tier memberi 0 bila makna kasar umum di bahasa tier A, dan brief meminta
  bagian nama serta slang singkatan ikut dicek. "POS" adalah morfem yang sengaja dipakai (cerita merek "liku + pos"), dan
  bentuk kapital "LIKUPOS" menonjolkan bacaan singkatan. Satu-satunya argumen tandingan adalah bahwa nama diucapkan sebagai
  suku kata "pos", bukan huruf P-O-S; argumen itu tidak mengubah aturan tier dan hanya bisa dinilai lewat uji asosiasi.
- [INFERENSI] **K6 = 0, G3 berlaku (v2 dan v3).** LAKUPOS dan LIKUPOS berbeda satu huruf, berbunyi "la-ku-pos" dan
  "li-ku-pos", dan LAKUPOS terdaftar di kelas 42 di Indonesia, yaitu negara merek dasar Madrid. Walaupun LAKUPOS berlogo
  dan akan berakhir 2027-12-21 bila tidak diperpanjang, itu merek sangat mirip yang aktif hari ini.
- [INFERENSI] **K8 = 1 (sugestif, dekat deskriptif di bahasa Indonesia).** "Liku" dan "pos" adalah kosakata baku
  deskripsi jalur pendakian (lihat blog Mekongga di atas), sehingga untuk jasa informasi jalur gabungannya menyebut ciri
  jalur. Gabungannya tidak baku, jadi belum deskriptif murni; G5 tidak berlaku. Di kelas 9 dan 42, unsur "POS" generik
  (kasir), jadi daya pembeda bertumpu pada "LIKU", yang juga merek pihak lain di AS (kelas 9, masih permohonan).
- [INFERENSI] **K4a = 1:** kecocokan dengan logo dan rute kuat, tetapi "berliku-liku" (berbelit) melawan "Pahami
  alasannya", makna Jawa arkais "ngliku" (menyesatkan) melawan kepercayaan, dan bacaan "aplikasi kasir" mengaburkan
  kategori produk.
- [INFERENSI] **K1a = 1:** satu masalah (koda s). Tidak ada e, j, w, gugus, atau huruf labil v2.

#### 4.7 Skor
| Kriteria v2 | Skor | Dasar |
|---|---|---|
| K1 ucap-eja | 2 | tidak ada huruf dari daftar v2 |
| K2 makna | 0 | "POS" singkatan kasar umum dalam bahasa Inggris |
| K3 konotasi | 2 | tidak ada |
| K4 konsep dan pembeda | 1 | cocok dengan logo; pola "... Pos" terbaca koran, pos, atau kasir |
| K5 domain | 2 | semua 404 |
| K6 konflik | 0 | LAKUPOS & LOGO, kelas 42, Indonesia |
| **Total v2** | **7 dari 12** | **gugur G1 dan G3** |

| Kriteria v3 | Skor | Bobot | Nilai | Dasar |
|---|---|---|---|---|
| K6 konflik | 0 | ×3 | 0 | G3: LAKUPOS kelas 42 di ID |
| K8 kekuatan merek | 1 | ×2 | 2 | sugestif, dekat deskriptif untuk jasa jalur di ID |
| K2 makna | 0 | ×2 | 0 | G1: "POS" kasar (Inggris, tier A) |
| K1a ucap-eja meja | 1 | ×2 | 2 | koda s |
| K3 konotasi | 2 | ×1 | 2 | tidak ada |
| K4a kecocokan konsep | 1 | ×1 | 1 | logo kuat; "berbelit" dan "kasir" melawan konsep |
| K9 aksara | 2 | ×1 | 2 | 4 mora; transliterasi netral tersedia |
| K5 domain | 2 | ×1 | 2 | semua 404 |
| **Total v3** | | | **11 dari 26** | **gugur G1 dan G3** |

**Putusan LIKUPOS:** GUGUR (G1 dan G3, baik v2 maupun v3).

### Gaps
- Alasan penolakan LAKUPOS kelas 9 dan SakuPOS tidak terbaca dari PDKI (apakah karena "POS" deskriptif atau karena merek
  terdahulu); PERLU KLARIFIKASI lewat berkas DJKI atau konsultan.
- Seberapa kuat asosiasi "POS" kasar pada penutur Inggris ketika nama ditulis "Likupos" (huruf kecil) belum diuji.

---

## 5. KELOKPOS, dan rekomendasi untuk pasangan LIKUPOS dan KELOKPOS

### Takeaway
KELOKPOS tidak punya merek sangat mirip di kelas 9 atau 42 di ID, US, atau UE, jadi lebih bersih daripada LIKUPOS di sisi
konflik. Tetapi ia membawa masalah "POS" yang sama (G1), ditambah K1a = 0 (e, gugus "kp", koda k dan s) dan asosiasi
"Kelok 9" dan "Kelok 23" sebagai jalan raya, bukan jalur pendakian. Merek mirip terdekat adalah KELOKE (Guangzhou Haichuan
Technology Development Co., Ltd., kelas 9, terdaftar di AS dan UE sejak 2025 sampai 2026). Skor: **8 dari 12 (v2, gugur
G1)** dan **11 dari 26 (v3, gugur G1 dan sementara G4)**. **Putusan: GUGUR.** Dari pasangan ini, **tidak satu pun
direkomendasikan**; bila pemilik tetap ingin satu pembanding dari keluarga "... Pos", pilih KELOKPOS, bukan LIKUPOS.

### Cited Findings

#### 5.1 Makna "kelok" dan asosiasinya
- [FAKTA] Cermin KBBI (UNVERIFIED): "ke·lok /kélok/ n lengkung; belokan; tikungan; keluk; ... ber·ke·lok-ke·lok v banyak
  keloknya; berkeluk-keluk; berliku-liku: melalui jalan ~" ([kbbi.web.id/kelok](https://kbbi.web.id/kelok), 18.35 UTC).
- [FAKTA] PRPM: "(kélok) liku, selekoh (jalan) ...; berkelok-kelok 1. banyak liku, berselekoh-selekoh (jalan
  bengkang-bengkok)" ([PRPM kelok](https://prpm.dbp.gov.my/Cari1?keyword=kelok), 18.39 UTC).
- [FAKTA] KBJI (Jawa): "kelok (kalok) kondang, termasyhur; keloka termasyhur, terkenal"
  ([KBJI kelok](https://kbji.kemendikdasmen.go.id/kata/kelok), 18.40 UTC). BASAbali tidak punya entri "Kelok" (HTTP 404,
  18.40 UTC). Wiktionary: Indonesia "curve, bend; turn", Yurok "goose" ([kelok](https://en.wiktionary.org/wiki/kelok)).
  Urban Dictionary: 0 entri untuk "kelok" dan "kelokpos" (18.34 sampai 18.35 UTC).
- [FAKTA] "Kelok 9 atau Kelok Sembilan adalah ruas jalan berkelok yang terletak sekitar 30 km sebelah timur dari Kota
  Payakumbuh, Sumatera Barat menuju Provinsi Riau" ([id.wikipedia Kelok Sembilan](https://id.wikipedia.org/wiki/Kelok_Sembilan),
  18.59 UTC). "Kelok 23" adalah jalan baru Bantul-Gunungkidul yang diuji coba 14 sampai 20 September 2026
  ([Kompas](https://travel.kompas.com/read/2026/09/21/160400927/uji-coba-berakhir-kapan-jalur-kelok-23-yogyakarta-dibuka-lagi-),
  dikutip dari `kandidat-dan-saringan.md` §2.3).
- [FAKTA] PDKI "kelok" (18.16.14 dan 18.17.59 UTC): 6 data, tidak ada di kelas inti: KELOK (kelas 25, kedaluwarsa),
  KELOK SEMBILAN (25, kedaluwarsa), KELOK MAS (25, kedaluwarsa), **KELOK 9 (kelas 34, rokok, Didaftar)**, KELOK PANTA (29),
  DAPUR KELOK SARIANA (43). Nama tempat itu sudah dipakai sebagai merek barang konsumen.
- [FAKTA] "pos" dan "POS": sama dengan LIKUPOS (4.1 dan 4.3): Inggris "piece of shit" (vulgar) dan "point of sale";
  Jerman jamak "Pos" (pantat); Spanyol "pues" dan "en pos de"; Indonesia pos surat, pos jaga, dan nama media.

#### 5.2 Merek
- [FAKTA] **PDKI** (18.15.56 sampai 18.16.14 UTC): "kelokpos" dan "kelok pos" 0 data. Daftar Fonetik: Kelopo, HELOPOS,
  KELOROS, Kelompok Sardin, KELOMPOK BERSAUDARA, Sate Kelopo "CAK ONI', : KELOMPOK CASTLECASTLE, Kelompok Tani Lestari,
  KELOMPOK TI'I MAU, AYAM KELOPOE + LOGO; tidak satu pun aktif di kelas 9, 35, 39, 41, 42, atau 45.
- [FAKTA] **USPTO** (Live): "kelokpos" 0 (18.09.11 UTC); "kelok" 0 (18.09.16 UTC); awalan "kelok" di kelas 9/42: **KELOKE**,
  Guangzhou Haichuan Technology Development Co., Ltd., Reg. 8051606 (terdaftar 2025-12-02) dan SN 99808709 (permohonan
  2026-05-07), "Protective covers and cases for tablet computers; Bags adapted for laptops" (18.32.51 UTC).
- [FAKTA] **TMview** (18.28.45 sampai 18.29.14 UTC): "kelokpos" contains 0; fuzzy aktif di EM, GB, AU, MY kelas inti 0; fuzzy
  aktif kelas 9/42 semua kantor 0; "kelok pos" 0; awalan "kelok" aktif kelas inti 27, terutama KELOKE milik Guangzhou
  Haichuan Technology Development Co., Ltd. di **EM** (019355278 dan 019300431, kelas 9, Registered), **GB** (UK00004317679,
  kelas 9, Registered), DE, JP, MX (Registered), serta AU, CA, dan US (Filed). Ada juga KELOKE di EM 018858225 (kelas 39 dan
  41, Registered, pemilik perorangan atau tidak jelas).
- [FAKTA] **WIPO**: Embedded "kelokpos" "No results found" (18.44.21 UTC, diulang setelah captcha); Phonetic 535 hasil, 150
  teratas dibaca, 3 aktif di kelas inti, semuanya QUALICAPS (Qualicaps Co., Ltd.) (18.21.24 UTC); Fuzzy 711 hasil, 90 teratas
  dibaca, 0 di kelas inti (18.34.20 UTC).

#### 5.3 Web, toko, dan pemakaian
- [FAKTA] Yahoo! JAPAN "kelokpos" (18.38.38 UTC): 6 hasil, tidak ada yang memuat nama (hasil tentang aplikasi kesehatan
  Jepang KENPOS). "kelok pos" (18.40.56 UTC): blog pendakian Gunung Lawu, "Jalur dari Pos Bayangan menuju Pos 3 akan terasa
  jauh karena jalan yang monoton dan berkelok-kelok"
  ([blog](https://www.airmengalirsampaijauh.com/2011/06/gunung-lawu-3266-mdpl.html?m=1)).
- [FAKTA] App Store: tidak ada judul "kelokpos"; "kelok" hanya menangkap aplikasi "KeLoKe" (Social Networking). Google Play
  "kelok pos" (ID) juga menampilkan "KeLoKe" di urutan pertama (18.52.51 UTC). Keempat handle tampak belum dipakai (1.3).

#### 5.4 Aksara lain (K9)
- [INFERENSI] Katakana ケロックポス (6 mora; tanpa sokuon ケロクポス, 5 mora), hangul 켈록포스, transliterasi Mandarin
  克洛克波斯 (5 suku). Bahasa Jepang "kero" adalah tiruan suara katak; tidak ditemukan homofon negatif (UNVERIFIED).

### Inferences
- [INFERENSI] **K2 = 0, G1 berlaku**, dengan alasan yang sama dengan LIKUPOS (4.1 dan Inferences bagian 4).
- [INFERENSI] **K6 = 1, G3 tidak berlaku.** KELOKE berbagi lima huruf awal, aktif di kelas 9 di AS, UE, dan Inggris, tetapi
  akhirannya berbeda ("E" dan "POS") dan barangnya adalah casing dan tas perangkat, bukan perangkat lunak. Ini kemiripan
  sedang, bukan sangat mirip. Di kelas 39 dan 41 ada KELOKE lain di UE, yang relevan bila kelas itu diajukan.
- [INFERENSI] **K8 = 1 (sugestif, dekat deskriptif di bahasa Indonesia).** "Kelok" dan "pos" dipakai berdampingan untuk
  menggambarkan jalur pendakian (blog Gunung Lawu di atas), jadi untuk jasa informasi jalur gabungannya menyebut ciri jalur.
  Di kelas 9 dan 42, unsur "POS" generik (kasir), sehingga pembeda bertumpu pada "KELOK", yang di Indonesia juga nama tempat
  terkenal (Kelok 9) dan sudah menjadi merek rokok. Risiko DJKI menilai "KELOK POS" sebagai keterangan jalur untuk kelas 39
  dan 41 lebih tinggi daripada untuk kelas 9 dan 42 [HIPOTESIS].
- [INFERENSI] **K1a = 0**: huruf e (dibaca /é/, dan bisa dibaca pepet oleh yang tidak mengenal kata), gugus "kp" antar suku,
  serta koda k dan s. G4 berlaku sementara.
- [INFERENSI] **K4a = 1:** cocok dengan logo dan pilihan rute, tetapi "Kelok" lebih kuat terbaca jalan raya daripada jalur
  pendakian (R-001, R-002: Trail bukan jalan), dan "POS" terbaca kasir. Makna Jawa "kondang" netral sampai positif.

#### 5.5 Skor
| Kriteria v2 | Skor | Dasar |
|---|---|---|
| K1 ucap-eja | 2 | tidak ada huruf dari daftar v2 |
| K2 makna | 0 | "POS" singkatan kasar umum dalam bahasa Inggris |
| K3 konotasi | 2 | tidak ada |
| K4 konsep dan pembeda | 1 | cocok dengan logo; pola "... Pos" dan asosiasi jalan raya |
| K5 domain | 2 | semua 404 |
| K6 konflik | 1 | KELOKE kelas 9 (US, EM, GB), tidak sangat mirip |
| **Total v2** | **8 dari 12** | **gugur G1** |

| Kriteria v3 | Skor | Bobot | Nilai | Dasar |
|---|---|---|---|---|
| K6 konflik | 1 | ×3 | 3 | KELOKE, kemiripan sedang |
| K8 kekuatan merek | 1 | ×2 | 2 | sugestif, dekat deskriptif untuk jasa jalur di ID |
| K2 makna | 0 | ×2 | 0 | G1 |
| K1a ucap-eja meja | 0 | ×2 | 0 | e, gugus "kp", koda k dan s |
| K3 konotasi | 2 | ×1 | 2 | tidak ada |
| K4a kecocokan konsep | 1 | ×1 | 1 | logo kuat; jalan raya dan kasir |
| K9 aksara | 1 | ×1 | 1 | 6 mora |
| K5 domain | 2 | ×1 | 2 | semua 404 |
| **Total v3** | | | **11 dari 26** | **gugur G1**, dan G4 sementara |

**Putusan KELOKPOS:** GUGUR (G1).

#### 5.6 Rekomendasi pasangan LIKUPOS dan KELOKPOS
| Aspek | LIKUPOS | KELOKPOS |
|---|---|---|
| G1 ("POS") | gugur | gugur |
| G3 (sangat mirip di 9/42 di ID, US, UE) | **gugur**: LAKUPOS & LOGO kelas 42, ID | tidak |
| K1a | 1 (koda s) | 0 (e, "kp", koda k dan s) |
| Makna tambahan | berbelit-belit (ID), dugaan dan kesengsaraan (MS), menyesatkan (Jawa arkais) | kondang (Jawa); Kelok 9 dan Kelok 23 sebagai jalan raya |
| Skor v2 / v3 | 7 / 11 | 8 / 11 |

- [REKOMENDASI] Bawa **paling banyak satu**, dan sebaiknya **tidak satu pun**, ke tahap akhir. Bila pemilik tetap ingin
  satu pembanding dari keluarga "... Pos" (misalnya untuk menguji apakah penutur Inggris benar menangkap "POS"), pilih
  **KELOKPOS**, karena gerbangnya hanya G1, sedangkan LIKUPOS juga terkena G3 di negara merek dasar.
- [INFERENSI] Konsep logo "Jalur Berpos" tidak bergantung pada kata "pos" di nama; logo bisa tetap dipakai dengan nama lain.

### Gaps
- Seberapa kuat asosiasi "POS" kasar pada penutur Inggris untuk bentuk huruf kecil "Kelokpos" belum diuji.
- Uraian barang dan jasa KELOKE di EM kelas 39 dan 41 (pemilik perorangan atau tidak jelas) belum dibaca.

---

## 6. PINTANDA

### Takeaway
PINTANDA adalah satu-satunya nama kelompok ini yang lolos semua gerbang di kedua rubrik: **8 dari 12 (v2)** dan
**16 dari 26 (v3)**. Tidak ada merek identik, dan tidak ada merek PINTADA aktif di kelas inti di ID, AS, atau UE (merek
PINTADA yang ada terutama di Amerika Latin, kelas 35, 39, 41, 42). Risikonya tiga: (1) **TANDA** adalah merek aktif di
kelas 9 dan 42 di AS (Tanda Technologies, Inc.; EPI Capital Pty Ltd), UE (EUIPO 019237025), Inggris, dan Australia, dan
PINTANDA memuat kata itu utuh; (2) penutur Spanyol dan Portugis membacanya sebagai salah eja "pintada" atau "pintando",
dan "pinta" adalah slang penjara di Meksiko; (3) di Indonesia "pin tanda" lazim berarti lencana "pin tanda jasa" atau
"pin tanda jabatan". Instagram @pintanda sudah dipakai. **Putusan: LOLOS BERSYARAT**, dengan syarat pendapat Konsultan KI
soal TANDA dan uji dengar-eja.

### Cited Findings

#### 6.1 Salah eja "pintada" dan merek PINTADA
- [FAKTA] Yahoo! JAPAN "pintanda" (18.38.52 UTC): 10 dari 10 hasil memuat string itu, semuanya salah eja Portugis atau
  Spanyol untuk "pintada", misalnya "Saia de crochê para boneca pintanda", "Chinelo Feminino Kenner Ibiza Pintanda", dan
  "Chorro La Piedra Pintanda.. en camino para subir la India" (resor di Panama)
  ([kueri](https://search.yahoo.co.jp/search?p=%22pintanda%22)). Saringan (Brave, 16.16 UTC) mencatat pola yang sama:
  "Onça Pintanda" dan berita Infobae "la pintanda en el baño" (`kandidat-dan-saringan.md` §2.3).
- [FAKTA] RAE: "pintada 1 ... 1. f. Acción de pintar en las paredes letreros preferentemente de contenido político o social.
  2. f. Letrero o conjunto de letreros ..." ([DLE pintada](https://dle.rae.es/pintada), 18.59 UTC). Wiktionary: Portugis
  "guinea fowl" dan bentuk feminin "pintado" (dicat, berbintik); Spanyol "paint; painting (act of painting)"; Galisia
  "graffiti" ([pintada](https://en.wiktionary.org/wiki/pintada), 18.35 UTC).
- [FAKTA] Merek PINTADA di kelas inti: WIPO Embedded "pintada" 155 hasil, 150 dibaca, 6 aktif di kelas inti, semuanya di
  Amerika Latin: LA PINTADA (Meksiko, kelas 39), LA PINTADA SABORES DE BAJA (Meksiko, 39), ONÇA PINTADA IGUAÇU (Brasil, 35),
  CERTIFICADO ONÇA-PINTADA JAGUAR FRIENDLY (Brasil, 42, INSTITUTO ONÇA-PINTADA), TU FACHADA X UNA PINTADA (Peru, 41,
  CORPORACION PERUANA DE PRODUCTOS QUIMICOS S.A.), Unha Pintada (Brasil, 41) (18.30.56 UTC). USPTO Live: PINTADA kelas 3
  (Shenzhen Meifan Freight Forwarding Co., Ltd., Reg. 7840429) dan BANANA PINTADA kelas 25; awalan "pintada" di kelas inti 0
  (18.09.25 dan 18.33.03 UTC). TMview "pintada" (is) aktif kelas inti 0 (18.29.06 UTC). PDKI "pintada" 0 data (18.16.18 UTC).

#### 6.2 TANDA dan merek mirip lain
- [FAKTA] **USPTO** (Live, 18.09.21 sampai 18.33.05 UTC): "pintanda" 0; TANDA di kelas 9/42 5 merek hidup, antara lain
  **TANDA**, Tanda Technologies, Inc., kelas 9, 36, 41, 42, 45, Reg. 7845227 (terdaftar 2025-06-24); **TANDA**, EPI CAPITAL
  PTY LTD, kelas 9 dan 42, Reg. 5705835 ("Application software for management of human resources, rostering, timesheets
  ..."); TANDA, Processing Point, Inc. dan EPI Capital, kelas 9, Reg. 5321349; TANDA, Shenzhen Taihe 'an Electronic Commerce
  Co., LTD., kelas 9 (sistem alarm), Reg. 7905588.
- [FAKTA] **TMview** (18.29.07 UTC), "tanda" (is), aktif di kelas inti: 30 merek, 21 di kelas 9/42, termasuk **EM
  019237025 "tanda", kelas 9, 35, 41, 42, Registered** (diajukan 2025-08-25, pemilik perorangan atau tidak jelas), **GB
  UK00801391009 TANDA, kelas 9 dan 42** (EPI CAPITAL PTY LTD), AU 2568285 (EPI Capital Pty Ltd, kelas 9, 35, 36, 41, 45),
  PH (kelas 9 dan 42), NZ dan CA (Filed).
- [FAKTA] **PDKI** (18.16.15 sampai 19.10.47 UTC): "pintanda", "pin tanda", dan "pintada" 0 data. Daftar Fonetik: INTANA,
  PIANDA, Pinand, PINANA, Pintarnya, PILANDA, PENTANA, INTANZA, INTANIA, JINTANA. Yang aktif di kelas inti:
  - Pintarnya Cuan dan Pintarnya Siap Kerja, kelas 9, Didaftar sampai 2033, **PT Pintarnya Solusi Teknologi** (aplikasi
    lowongan kerja); "Pintarnya" kelas 9 sendiri berstatus "Ditolak KBM"; Pintarnya kelas 35 dan 41 Didaftar;
  - PIANDA (kelas 9, alat ukur tinggi dan timbangan, perorangan) dan PENTANA (kelas 9, perorangan);
  - unsur "tanda": 107 data, 6 aktif di kelas 9/42, antara lain **Tanda Link** (PT. Bank OCBC NISP Tbk, kelas 9 dan 42,
    sampai 2031-05-24).
- [FAKTA] **WIPO**: Embedded "pintanda" "No results found" (19.08.52 UTC, diulang setelah captcha); Phonetic 1.564 hasil,
  150 dibaca, 6 aktif di kelas inti, semuanya frasa "PINTANDO ..." di Meksiko dan Brasil, termasuk PINTANDO CON PALABRAS
  (CONSORCIO COMEX, S.A. DE C.V., kelas 9) (18.35.55 UTC); Fuzzy 1.368 hasil, 120 dibaca, 2 aktif di kelas inti
  (PINTANDO & GOZANDO kelas 41, CARA PINTADA kelas 35) (18.36.49 UTC).
- [FAKTA] **TMview** (18.28.49 sampai 18.29.15 UTC): "pintanda" contains 0; fuzzy aktif di EM, GB, AU, MY kelas inti 0; fuzzy
  aktif kelas 9/42 semua kantor 13, semuanya Amerika Latin (PINTANDO EL FUTURO di Argentina kelas 9 dan 42, PRODUCTORA
  QUIMICA LLANA Y CIA. S.A.; PINTANDO CON PALABRAS di Meksiko kelas 9 dan 42; merek Onça-Pintada di Brasil kelas 42).

#### 6.3 Makna bagian nama (K2) dan bacaan "pin tanda"
- [FAKTA] "pinta" (Wiktionary, 18.35 UTC): Spanyol "look, appearance", "small spot, dot or stain", dan **"jail"**; Portugis
  "naevus", "a small spot"; Inggris **"a human skin disease ... caused by infection with a spirochete"** dan "prison";
  Indonesia dan Melayu "request" ([pinta](https://en.wiktionary.org/wiki/pinta)). Urban Dictionary: "chicano slang for
  prision" dan "Mexican slang word for prison" ([UD pinta](https://www.urbandictionary.com/define.php?term=pinta)).
- [FAKTA] "tanda" (Wiktionary, 18.35 UTC): Indonesia "sign, mark", "symptom", "evidence, proof"; Melayu "sign, mark, signal";
  Tagalog "sign; mark", "marker", **"old person"**, "shift; turn"; Spanyol "time, instance", "duty, turn", "shift"; Inggris
  giliran dansa dalam milonga tango ([tanda](https://en.wiktionary.org/wiki/tanda)). Cermin KBBI: "tan·da n 1 yang menjadi
  alamat ...; 2 gejala; 3 bukti; 4 pengenal; lambang ...; 5 petunjuk" ([kbbi.web.id/tanda](https://kbbi.web.id/tanda)).
- [FAKTA] Yahoo! JAPAN "pin tanda" (18.41.09 UTC): hasil didominasi lencana, "Pin Tanda Jasa Bintang" (Lazada), "Pin Tanda
  Jabatan" (Blibli), "Pin Tanda Jabatan TNI AL" (Wikipedia), dan satu "Tanda Pin Peta Ilustrasi" (iStock)
  ([kueri](https://search.yahoo.co.jp/search?p=%22pin+tanda%22)).
- [FAKTA] Urban Dictionary "pintanda": 0 entri; Wiktionary dan cermin KBBI: tidak ada entri (18.34 UTC).
- [FAKTA] Madura (kata.web.id, UNVERIFIED): "tandâ' nomina/kata benda tandak (penari)"; "pinta", "pin", dan "pintanda"
  tidak punya entri ([tanda](https://kata.web.id/kamus/madura-indonesia/arti-kata/tanda), 19.21 UTC).

#### 6.4 Web, toko, perusahaan
- [FAKTA] Yahoo! JAPAN "pintanda app" (18.40.02 UTC): Pinta.Art (platform seni Amerika Latin), aplikasi gambar "Pinta",
  "Pint Pint", dan Lingopanda; tidak ada aplikasi bernama Pintanda. App Store dan Google Play: 0 (1.2).
- [FAKTA] Companies House: PINTANDANIP LTD (SC650059), dibubarkan 24 Juni 2025, SIC 56302 pub dan bar
  ([Companies House](https://find-and-update.company-information.service.gov.uk/company/SC650059), 18.57.36 UTC).
- [FAKTA] Instagram @pintanda dipakai (judul "(@pintanda)"); TikTok, X, dan YouTube tampak belum dipakai (1.3).

#### 6.5 Aksara lain (K9)
- [INFERENSI] Katakana ピンタンダ (5 mora), hangul 핀탄다, transliterasi Mandarin 品探达 (pǐn tàn dá; 探 = menjelajah).
  Suku "pin" dan "tan" juga bisa ditulis 贫 (miskin) dan 贪 (serakah), tetapi mudah dihindari. Dalam bahasa Korea, 탄다
  adalah bentuk kata kerja 타다 ("naik" atau "terbakar") [HIPOTESIS, perlu penutur Korea].

### Inferences
- [INFERENSI] **K1 v2 = 1 dan K1a = 1**: satu masalah, yaitu dekat kata Spanyol dan Portugis "pintada" dan "pintando".
  Tidak ada e, j, w, gugus dalam suku, atau koda selain n.
- [INFERENSI] **K2 dinilai ulang dari 2 menjadi 1.** "pinta" sebagai penjara (slang Meksiko dan Chicano) adalah makna tier B
  yang tidak umum dan tidak kasar; "pinta" sebagai penyakit kulit adalah kata Inggris yang jarang. Menurut aturan tier,
  itu 1, bukan 0.
- [INFERENSI] **K3 = 1 (samar):** bacaan Indonesia "pin tanda jasa" atau "pin tanda jabatan" memberi kesan lencana dan
  penghargaan; itu dekat dengan budaya koleksi puncak yang dihindari produk (R-015), tetapi hanya bila pembaca melengkapi
  frasanya.
- [INFERENSI] **K6 = 1, G3 PERLU KLARIFIKASI.** TANDA identik dengan separuh akhir nama dan aktif di kelas 9 dan 42 di AS,
  UE, dan Inggris. Bila pemeriksa atau pengadilan menganggap "PIN" unsur lemah (penanda peta) sehingga "TANDA" menjadi
  unsur dominan, kemiripannya bisa dinilai tinggi. Karena PINTANDA dibaca sebagai satu kata dan awalannya berbeda, penilaian
  peneliti adalah "mirip", bukan "sangat mirip"; G3 tidak diterapkan, tetapi keputusan akhirnya milik konsultan.
- [INFERENSI] **K8 = 1:** dalam bahasa Indonesia dan Melayu "pin tanda" berarti "pin penanda", yang menyebut fitur peta,
  jadi sugestif dan dekat deskriptif untuk perangkat lunak peta; dalam bahasa Inggris sugestif; dalam bahasa Spanyol dan
  Portugis arbitrer. G5 tidak berlaku.
- [INFERENSI] **K4a = 2:** menopang pilihan rute (penanda di peta), "pahami alasannya" dan kepercayaan (tanda = bukti,
  petunjuk, pengenal), serta logo (cincin pos sebagai penanda).

#### 6.6 Skor
| Kriteria v2 | Skor | Dasar |
|---|---|---|
| K1 ucap-eja | 1 | dekat "pintada" dan "pintando" |
| K2 makna | 1 | "pinta" = penjara (slang MX), penyakit kulit (EN, jarang) |
| K3 konotasi | 1 | "pin tanda jasa" (lencana) |
| K4 konsep dan pembeda | 2 | menopang penanda, alasan, dan kepercayaan; gabungan tidak dipakai merek identik |
| K5 domain | 2 | semua 404 |
| K6 konflik | 1 | TANDA (US, EM, GB, AU kelas 9/42), Pintarnya (ID kelas 9) |
| **Total v2** | **8 dari 12** | tidak ada gerbang |

| Kriteria v3 | Skor | Bobot | Nilai | Dasar |
|---|---|---|---|---|
| K6 konflik | 1 | ×3 | 3 | TANDA mirip; G3 perlu klarifikasi |
| K8 kekuatan merek | 1 | ×2 | 2 | sugestif di ID, MS, EN; arbitrer di ES, PT |
| K2 makna | 1 | ×2 | 2 | makna tier B jarang |
| K1a ucap-eja meja | 1 | ×2 | 2 | dekat "pintada" |
| K3 konotasi | 1 | ×1 | 1 | lencana |
| K4a kecocokan konsep | 2 | ×1 | 2 | rute, alasan, kepercayaan, logo |
| K9 aksara | 2 | ×1 | 2 | 5 mora; transliterasi positif tersedia |
| K5 domain | 2 | ×1 | 2 | semua 404 |
| **Total v3** | | | **16 dari 26** | tidak ada gerbang |

**Putusan PINTANDA:** LOLOS BERSYARAT. Syarat sebelum dipilih: pendapat tertulis Konsultan KI soal TANDA (AS, UE, Inggris,
Australia) dan Pintarnya (Indonesia); lulus K1b pada penutur Spanyol atau Portugis dan Indonesia; dan rencana handle
Instagram pengganti.

### Gaps
- Alasan penolakan "Pintarnya" kelas 9 oleh Komisi Banding Merek tidak terbaca; bila dasarnya kemiripan dengan merek lain
  berawalan "PINTA", itu relevan bagi PINTANDA (PERLU KLARIFIKASI).
- Arti 탄다 dan asosiasi 贫 atau 贪 belum dikonfirmasi penutur. Kamus Sunda tidak ditemukan; kamus Madura daring hanya
  memuat "tanda" (6.3).
- Brave menjawab HTTP 429 untuk "pintanda", "likupos", dan "kelokpos" pada putaran ini (sekitar 18.39 dan 19.17 UTC);
  temuan Brave untuk PINTANDA berasal dari saringan (16.16 UTC).

---

## 7. Peringkat kelompok 1

### Takeaway
Urutan: **1. PINTANDA** (lolos bersyarat), **2. STEPLARAS** (tertahan menunggu uji dengar-eja), **3. BENDPIN**,
**4. KELOKPOS**, **5. LIKUPOS** (ketiganya gugur). Semua skor turun dari saringan cepat, karena putaran ini menambahkan
basis data merek, kamus per tier, dan rubrik v3. Hanya PINTANDA yang layak dibawa ke tahap akhir tanpa menunggu uji
pengguna, dan itu pun bersyarat pendapat Konsultan KI soal TANDA.

### Cited Findings
- [FAKTA] Skor saringan cepat (v2, provisional, tanpa basis data merek): STEPLARAS 12, BENDPIN 11, LIKUPOS 11, KELOKPOS 11,
  PINTANDA 11 (`kandidat-dan-saringan.md` §3.3 dan §5.1).
- [FAKTA] Skor putaran ini ada di tabel skor bagian 2 sampai 6; buktinya bertanggal 23 September 2026, 16.43 sampai
  19.22 UTC.

| Nama | v2 saringan | v2 kini | Perubahan terbesar | v3 kini (maks 26) | Gerbang | Putusan |
|---|---|---|---|---|---|---|
| PINTANDA | 11 | **8** | K2 2 ke 1 (pinta = penjara, slang); K3 2 ke 1 (lencana); K6 2 ke 1 (TANDA) | **16** | tidak ada; G3 perlu klarifikasi | **LOLOS BERSYARAT** |
| STEPLARAS | 12 | **8** | K2 2 ke 1 (laras senapan); K3, K4, K6 turun (STEP padat, SELARAS, STEPLER, LARAS) | **12** | v3: G4 sementara (K1a = 0) | **TERTAHAN** |
| BENDPIN | 11 | **6** | K1 2 ke 0 ("bent pin"); K6 2 ke 0 (BANDPIN kelas 9/42 dan aplikasi BandPin) | **14** | v2: G3, G4; v3: G4 sementara | **GUGUR** |
| KELOKPOS | 11 | **8** | K2 2 ke 0 ("POS" kasar); K6 2 ke 1 (KELOKE) | **11** | G1 (v2 dan v3); v3: G4 sementara | **GUGUR** |
| LIKUPOS | 11 | **7** | K2 2 ke 0 ("POS" kasar); K6 2 ke 0 (LAKUPOS kelas 42, ID) | **11** | G1 dan G3 (v2 dan v3) | **GUGUR** |

### Inferences
- [INFERENSI] **Cara mengurutkan.** Pertama status gerbang (lolos, lalu gugur sementara yang menunggu K1b, lalu gugur tetap),
  baru kemudian total v3, lalu pemecah seri v3 (K8, tidak ada merek identik aktif di ID/US/UE, tidak ada merek mirip di kelas
  9/42, K1b, K5). Karena itu STEPLARAS (v3 12) berada di atas BENDPIN (v3 14): keduanya gugur sementara G4 di v3, tetapi
  BENDPIN juga gugur tetap di v2 (G3 dan G4), dan masalah "bent pin" justru hal yang akan diuji K1b.
- [INFERENSI] **KELOKPOS di atas LIKUPOS** walau total v3 sama (11): pemecah seri pertama (K8) seri, tidak ada merek identik
  untuk keduanya, tetapi pada pemecah seri ketiga LIKUPOS punya merek sangat mirip di kelas 42 (LAKUPOS), sedangkan KELOKPOS
  hanya mirip sedang (KELOKE).
- [INFERENSI] **Pola lintas nama.** Majemuk berbahasa Indonesia (LIKUPOS, KELOKPOS) paling cocok dengan logo tetapi kalah di
  makna ("POS") dan daya pembeda (kosakata deskripsi jalur). Majemuk berbahasa Inggris atau campuran (BENDPIN, STEPLARAS)
  kalah di ucap-eja meja (gugus konsonan dan koda) dan di kepadatan stem ("step", "bend", "pin"). PINTANDA unggul karena
  suku katanya terbuka dengan koda n saja, sesuai kerangka bunyi aman di lanskap §2.
- [REKOMENDASI] Bawa **PINTANDA** ke tahap akhir sebagai kandidat kelompok 1, dengan syarat di bagian 8. Simpan
  **STEPLARAS** sebagai cadangan yang hanya naik bila lulus K1b dan konsultan menilai risiko SELARAS rendah. Hentikan
  BENDPIN, KELOKPOS, dan LIKUPOS.
- [REKOMENDASI] Keputusan nama tetap milik pemilik dan butuh governance record (`PRD.md` §130 dan §146); berkas ini hanya
  bukti.

### Gaps
- K1b belum dijalankan, jadi status STEPLARAS (dan G4 sementara pada BENDPIN serta KELOKPOS) belum final.
- Perbandingan dengan kelompok kandidat lain (gaya A dan B) bukan bagian tugas ini.

---

## 8. Daftar tunda dan skrip uji dengar-eja

### Takeaway
Ada tujuh pekerjaan tersisa sebelum PINTANDA atau STEPLARAS bisa diputuskan. Yang paling menentukan adalah uji
dengar-eja K1b (skrip di 8.2, bisa dijalankan pemilik tanpa alat khusus) dan pendapat tertulis Konsultan KI tentang TANDA
dan SELARAS. Cek yang terblokir di putaran ini (OpenCorporates, Etherscan, pencarian web berorientasi Indonesia dan AS,
kamus Sunda dan Madura) juga harus ditutup, dan RDAP serta handle diulang tepat sebelum keputusan.

### Cited Findings
- [FAKTA] Protokol dan ambang lulus uji dengar-eja diambil dari `lanskap-dan-strategi.md` §2.4 (tiga kelompok, 8 orang per
  kelompok; eja persis paling banyak 1 salah dan tanpa salah eja kembar; ucap ulang dapat dikenali minimal 7 dari 8;
  ingatan 24 sampai 48 jam minimal 6 dari 8, ambang awal berstatus HIPOTESIS). Dengan aturan "paling banyak 1 salah dari 8",
  nama yang sebenarnya salah dieja 30% orang hanya lulus 25,5% kali, sedangkan nama yang salah dieja 10% orang lulus 81,3%
  kali (perhitungan binomial di berkas yang sama).
- [FAKTA] Selama 5 tahun, penolakan merek dasar di DJKI menjatuhkan semua penunjukan Madrid (Pasal 6(3) Protokol Madrid;
  `lanskap-dan-strategi.md` §3.1).
- [FAKTA] Cek yang terblokir hari ini: OpenCorporates (captcha HAProxy), Etherscan (verifikasi Cloudflare), alat WebSearch
  (kuota 200 dari 200 habis), Brave (HTTP 429), dan kamus daring Sunda serta Madura (tidak ditemukan) (bagian 1).

### Inferences

#### 8.1 Daftar tunda (urut prioritas)
| No | Pekerjaan | Untuk nama | Pemilik tugas | Kapan |
|---|---|---|---|---|
| 1 | Uji dengar-eja K1b (skrip 8.2) | PINTANDA, STEPLARAS (wajib); BENDPIN dan KELOKPOS hanya sebagai pembanding bila ingin | pemilik | sebelum shortlist akhir |
| 2 | Pendapat tertulis Konsultan KI: PINTANDA terhadap TANDA (US Reg. 7845227 dan 5705835, EM 019237025, UK00801391009, AU 2568285) dan terhadap Pintarnya (ID kelas 9); STEPLARAS terhadap SELARAS (DID2021049460, kelas 9), STEPLER (US Reg. 7341448), dan LARAS (EM 002255727, UK00902255727, MY 02001678) | PINTANDA, STEPLARAS | pemilik dan konsultan (daftar di [pdkki.dgip.go.id](https://pdkki.dgip.go.id/pages/index_)) | setelah K1b |
| 3 | Konfirmasi penutur asli: Sunda dan Madura (kelima nama); Mandarin (拉拉, 笨 atau 奔, 贫 atau 贪); Korea (탄다); Tagalog (pemakaian "bendpin", "tanda"); Spanyol dan Portugis ("pintanda", "pinta"); Jawa (potongan "lara"); Jerman ("Pos") | sesuai nama | pemilik | bersamaan dengan K1b |
| 4 | Cek manual di peramban biasa tanpa akun: OpenCorporates dan Etherscan untuk kelima nama; pencarian Google atau Bing berorientasi Indonesia dan AS untuk PINTANDA dan STEPLARAS | PINTANDA, STEPLARAS | pemilik atau peneliti | sebelum konsultan |
| 5 | Baca alasan penolakan DJKI yang relevan: "Pintarnya" kelas 9 (Ditolak KBM), LAKUPOS kelas 9, SakuPOS | PINTANDA (utama) | konsultan | bersama butir 2 |
| 6 | Ulang RDAP (dengan kontrol) dan handle tepat sebelum keputusan; jangan cek ketersediaan di situs registrar sebelum siap membeli, karena risiko domain front-running | nama terpilih | peneliti | hari keputusan |
| 7 | Governance record perubahan nama merek | nama terpilih | pemilik | sebelum dipakai |

Untuk BENDPIN, KELOKPOS, dan LIKUPOS tidak ada cek lanjutan yang disarankan, kecuali pemilik ingin memakai salah satunya
sebagai pembanding di K1b.

#### 8.2 Skrip uji dengar-eja K1b (siap dijalankan)
**Persiapan (sekali).**
1. Nama uji: PINTANDA, STEPLARAS, dan satu nama kontrol yang dikenal luas (misalnya "Spotify"). Bila ingin pembanding
   gagal, tambahkan BENDPIN.
2. Rekam setiap nama oleh dua suara, penutur Indonesia dan penutur Inggris, dalam kalimat netral:
   - "Saya cek jalurnya di ___."
   - "I checked the trail on ___."
   Setiap rekaman diputar dua kali. Jangan menekankan batas morfem (jangan "pin... tanda" atau "step... laras").
3. Buat urutan acak per peserta. Jangan menampilkan tulisan nama di mana pun sebelum tugas selesai.
4. Kelompok, masing-masing 8 orang yang belum pernah mendengar nama ini dan bukan anggota tim:
   - A: penutur Indonesia dari minimal tiga latar daerah (misalnya Jawa, Sunda, Sumatra atau Nusa Tenggara);
   - B: penutur Inggris (idealnya Australia, Inggris, Singapura, atau Malaysia);
   - C: penutur Spanyol atau Portugis (wajib untuk PINTANDA), ditambah bila bisa penutur Belanda, Jerman, atau Mandarin.
     Kelompok C boleh 5 orang, tetapi hasilnya hanya saringan.
5. Data yang dicatat hanya kode peserta, bahasa ibu, dan negara tinggal. Minta persetujuan rekam suara dan simpan rekaman
   sesuai `docs/PROTOKOL-UJI-KEGUNAAN.md`. Jangan memberi tahu nama mana yang disukai pemilik.

**Kalimat fasilitator (bacakan apa adanya).**
- Pembuka (ID): "Terima kasih. Anda akan mendengar beberapa nama produk. Tidak ada jawaban benar atau salah; yang diuji
  namanya, bukan Anda."
- Pembuka (EN): "Thank you. You will hear a few product names. There are no right or wrong answers; we are testing the
  names, not you."

**Tugas per nama (urutan tetap).**
1. Eja: "Tuliskan nama yang Anda dengar, sebagaimana Anda akan mengetiknya untuk mencarinya." / "Please write the name
   the way you would type it to search for it."
2. Ucap ulang (direkam): "Sekarang ucapkan nama itu." / "Now please say the name."
3. Asosiasi bebas: "Nama ini mengingatkan Anda pada apa?" / "What does this name remind you of?" Catat jawaban pertama
   apa adanya.
4. Hanya setelah tugas 3, satu pertanyaan tertutup:
   - PINTANDA: "Apakah nama ini terdengar seperti kata yang Anda kenal? Kata apa?" (kelompok C: "¿Le suena a alguna
     palabra? ¿Cuál?" atau "Lembra alguma palavra? Qual?");
   - STEPLARAS: "Apakah Anda mendengar kata di dalam nama ini? Kata apa?";
   - BENDPIN (bila dipakai): "Did you hear one word or two? Which words?".
5. Distraksi 10 menit (tugas lain yang tidak berkaitan), lalu: "Sebutkan nama-nama yang tadi Anda dengar." Jangan beri
   petunjuk.
6. Setelah 24 sampai 48 jam, lewat pesan: "Nama produk apa saja yang Anda ingat dari sesi kemarin? Tuliskan."

**Lembar skor per kelompok dan per nama.**
| Ukuran | Ambang lulus (n = 8) | Catatan |
|---|---|---|
| Eja persis | paling banyak 1 salah, dan tidak ada salah eja yang sama muncul dua kali | catat semua salah eja apa adanya |
| Ucap ulang dapat dikenali | minimal 7 dari 8 | dinilai dua orang dari rekaman |
| Ingatan tertunda 24 sampai 48 jam | minimal 6 dari 8 | ambang awal, kalibrasi dengan nama kontrol |
| Asosiasi | dicatat, bukan ambang | kode: "pintada/pintando", "lencana", "senapan", "lara/sakit", "Stellaris", "bent pin" |

**Aturan keputusan (ditetapkan sebelum uji).**
- K1b **lulus** hanya bila semua ambang lulus di kelompok A dan B, dan tidak ada salah eja kembar di kelompok C.
- PINTANDA: bila 2 orang atau lebih di kelompok C menulis "pintada" atau "pintando", itu salah eja kembar, dan K1b gagal.
- STEPLARAS: bila 2 orang atau lebih di kelompok B menulis ejaan yang sama yang berbeda (misalnya "Steplarus"), K1b gagal
  dan G4 v3 menjadi final.
- BENDPIN (pembanding): bila 2 orang atau lebih di kelompok B menulis "bent pin", "bentpin", atau "benpin", temuan meja
  terkonfirmasi.
- Hasil dilaporkan sebagai jumlah per kelompok (misalnya "7 dari 8"), bukan persentase populasi; uji ini formatif, bukan
  survei.

### Gaps
- Nama kontrol belum dikalibrasi; ambang ingatan 6 dari 8 masih HIPOTESIS (`lanskap-dan-strategi.md` §2.4).
- Belum ada anggaran atau jadwal uji; siapa yang merekam suara penutur Inggris belum ditentukan.
- Apakah bahasa Indonesia dianggap "common, modern language" oleh USPTO untuk doktrin padanan asing masih terbuka
  (`lanskap-dan-strategi.md` §3 dan §6); itu memengaruhi K8 PINTANDA dan STEPLARAS di AS.
