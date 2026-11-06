# Verifikasi Mendalam Kelompok 2 (gaya A): LINTAMO, PORANTI, TALAMPI, TANLIMO, TOLAPI

> **Status:** RISET, bukan keputusan. Nama merek adalah arah produk, jadi keputusan dan governance record-nya milik
> pemilik (R-021, `PRD.md` §130 dan §146).
> **Label bukti (R-023):** [FAKTA] hasil cek atau kutipan sumber; [INFERENSI] simpulan dari fakta; [HIPOTESIS] dugaan
> yang belum diuji; [REKOMENDASI] saran; [ISU TERBUKA] belum terjawab. UNVERIFIED berarti tidak diverifikasi langsung.
> **Batas klaim:** "404" pada RDAP berarti registri tidak punya catatan domain itu. Ini sinyal, bukan bukti domain bisa
> didaftarkan. Tidak ada kalimat di berkas ini yang menyatakan domain tersedia atau merek bebas.
> **Cara kerja:** hanya cek baca. Tidak ada domain didaftarkan, akun dibuat, pihak lain dihubungi, atau formulir yang
> membuat rekaman dikirim. Tidak ada pencarian di situs registrar. Semua jam dalam UTC.
> **Waktu cek:** 23 September 2026, 16.47 sampai 19.28 UTC. Semua bagian selesai.
>
> **Hasil singkat.**
> - Tidak satu pun dari lima nama punya merek identik di PDKI, WIPO Global Brand Database, USPTO, atau TMview, dan
>   keenam domain tiap nama (.com, .app, .id, .co.id, get+nama.com, nama+app.com) tanpa catatan RDAP.
> - Peringkat v3: TALAMPI 21, LINTAMO 20, TANLIMO 19, PORANTI 16, TOLAPI 14. Peringkat v2: TALAMPI 10, LINTAMO 9,
>   TANLIMO 9, PORANTI 8, TOLAPI 8.
> - TOLAPI gugur G3 (provisional) karena EUTM "Tohapi" (018349074) yang aktif di kelas 9 (aplikasi) dan 39
>   (perencanaan perjalanan). Keempat nama lain lolos gerbang; K1b (uji dengar-eja) untuk semua nama masih pending.
> - Temuan makna baru: Tagalog "linta" = lintah atau lintah darat ("linta mo" = "lintahmu"); Tagalog "talampí" =
>   menampar (jarang dipakai); "limo" = lumpur (RAE, Treccani, Priberam); Ceko, Polandia, Rusia "poranit/poranić/поранить"
>   = melukai; Portugis "tola" = bodoh. Kode TikTok 10202 berarti `UserNotExist` menurut JavaScript TikTok sendiri.

---

## 0. Metode, kontrol, dan batas bukti

### Takeaway
Semua cek dijalankan pada 23 September 2026 antara 16.47 dan 19.28 UTC. Setiap sumber yang memungkinkan diberi kontrol
positif (nama yang pasti ada) dan kontrol fiktif. Basis data merek (PDKI, WIPO Global Brand Database, USPTO, TMview)
dibuka lewat peramban Chrome headless terisolasi (profil sementara, tanpa login) dan memakai pencarian milik halaman itu
sendiri. Beberapa sumber tidak bisa dipakai hari ini (Etherscan dan OpenCorporates karena tantangan anti-bot, kuota
WebSearch habis, Yahoo! JAPAN dan Brave menjawab 429), sehingga penggantinya dan batasnya dicatat di bawah.

### Cited Findings
- **RDAP.** Endpoint diambil dari [bootstrap IANA](https://data.iana.org/rdap/dns.json) berpublikasi
  "2026-09-16T19:00:03Z": .com `https://rdap.verisign.com/com/v1/`, .app `https://pubapi.registry.google/rdap/`, .id dan
  .co.id `https://rdap.pandi.id/rdap/`. [FAKTA] Kontrol sebelum dan sesudah (16.47.29 sampai 16.47.43 UTC): verisign.com
  200 (terdaftar 1995-06-02), google.app 200 (2018-03-29), pandi.id 200 (2013-04-14), telkom.co.id 200 (1995-05-18);
  `qzxvkontrol7h2k` di .com, .app, .id, .co.id, serta getqzxvkontrol7h2k.com dan qzxvkontrol7h2kapp.com menjawab 404.
  Tidak ada situs registrar yang dibuka.
- **App Store.** [iTunes Search API](https://itunes.apple.com/search?term=lintamo&country=id&entity=software&limit=50),
  `entity=software`, `limit=50`, etalase ID, US, GB, AU, SG, MY, DE, FR, NL, JP, untuk nama persis dan dua varian dekat per
  nama, 16.47.59 sampai 16.57.04 UTC (150 permintaan, semua HTTP 200). Dihitung "memuat nama" bila nama ada di judul atau
  nama penjual, atau ada token dengan jarak edit paling banyak 2. [FAKTA] Kontrol positif "komoot" (19.20.05 sampai
  19.20.40 UTC) memberi 8 sampai 9 hasil di kesepuluh etalase, jadi angka 0 di etalase non-Inggris bukan kegagalan API.
- **Google Play.** Halaman pencarian `gl=US&hl=en` dan `gl=ID&hl=id` dibaca di peramban terisolasi, 19.11.57 sampai
  19.13.42 UTC. Kontrol "alltrails" menemukan `com.alltrails.alltrails`. Hanya sinyal lemah (upaya terbaik).
- **PDKI.** Halaman [pencarian PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=lintamo&page=1)
  memanggil dua aksi server miliknya sendiri: (a) mode Normal, kueri `match_phrase` pada `nama_merek` yang sama dengan UI;
  (b) daftar saran dengan mode `FONETIK`. Setiap tetangga fonetik lalu dicari dengan mode Normal dan dipindai untuk
  kelas 9, 35, 39, 41, 42, 45.
  - [FAKTA] PDKI diam-diam mengembalikan 0 setelah jawaban besar (misalnya ALAMI, 499 data), jadi semua hasil 0
    diverifikasi ulang. Pesan UI "Data tidak ditemukan untuk kata kunci ..." dibaca untuk kelima nama (18.13.49 sampai
    18.14.52 UTC). Kueri 0 untuk nama dan varian diapit kontrol positif "parwata" (1 data) sebelum dan sesudahnya
    (18.15.43 sampai 18.15.50 UTC dan 18.16.55 sampai 18.17.53 UTC).
  - [FAKTA] Penafian PDKI: "Keputusan tidak boleh dibuat berdasarkan pencarian ini saja"
    ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=tolapi&page=1)).
- **WIPO Global Brand Database.** Formulir [quick search](https://branddb.wipo.int/en/quicksearch) diisi di peramban
  terisolasi setelah tantangan ALTCHA lolos. Strategi yang dipakai: Embedded (kode URL `Simple`), Phonetic, dan Fuzzy,
  ditambah Match exact untuk LINTAMO.
  - [FAKTA] Kontrol positif "parwata" (Embedded, 18.42.38 UTC): 1 hasil, "Parwata Simple Taste Great Memory + Logo",
    Indonesia, IDM001507553. Kontrol fiktif huruf saja "qzxvkontrolhk" (18.42.53 UTC): "No results found!".
  - [FAKTA] Kontrol fiktif yang mengandung angka (`qzxvkontrol7h2k`) dipecah mesin WIPO menjadi potongan seperti "H2K"
    dan "K2-7" (93.246 hasil), jadi tidak dipakai. Membuka URL hasil secara langsung tidak menerapkan kueri (kontrol
    "parwata" menampilkan 76.861.347 rekaman), jadi hanya jalur formulir yang dipakai.
  - Phonetic dan Fuzzy menghasilkan ratusan sampai ratusan ribu rekaman berurut relevansi. Yang dibaca adalah halaman
    pertama (30 rekaman teratas) setelah jendela diperbesar agar semua kartu terender.
- **USPTO.** UI [tmsearch.uspto.gov](https://tmsearch.uspto.gov/search/search-results) memanggil endpoint
  `/prod-stage-v1-0-0/tmsearch` (Elasticsearch). Kueri UI yang persis (`match_phrase` WM boost 5, `match` WM,
  `match_phrase` PM) diputar ulang dari konteks halaman untuk tiap nama. Kueri fuzzy (fuzziness 2) dan wildcard pada WM
  dan PM ditambahkan sebagai pelengkap dan diberi label begitu. "Live" berarti `alive=true`. [FAKTA] Kontrol "komoot":
  2 merek hidup (18.23.58 dan 18.25.04 UTC); kontrol fiktif: 0 (18.24.00 UTC).
- **TMview.** UI [TMview](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=C&basicSearch=lintamo)
  memanggil `/tmview/api/search/results`. Kode kriteria dari literal TMview: C Contains, I Is, F Fuzzy search, E Ends
  with, W Word, B Begins with; tidak ada mode fonetik. [FAKTA] Kontrol "komoot": Contains 11, Fuzzy 526; kontrol fiktif
  "qzxvkontrolhk": 0 di semua kriteria (18.34.16 UTC). Daftar kantor (18.39.22 UTC): 81 kantor, termasuk EM, WO, US, GB,
  AU, MY, JP, KR, CN, PH, TH, VN, BN, KH, IN, tetapi **tanpa SG dan ID**. Rincian barang dan status diambil dari endpoint
  detail `/tmview/api/trademark/detail/{ST13}` (18.36.57 sampai 18.38.40 UTC).
- **Makna.** [Wiktionary REST API](https://en.wiktionary.org/api/rest_v1/page/definition/linta) (18.42.40 sampai 19.28.12
  UTC); cermin KBBI [kbbi.web.id](https://kbbi.web.id/lintah) (UNVERIFIED terhadap KBBI VI); PRPM DBP untuk Melayu
  ([PRPM](https://prpm.dbp.gov.my/Cari1?keyword=lintamo)); KBJI untuk Jawa
  ([KBJI](https://kbji.kemendikdasmen.go.id/kata/lintamo)); BASAbali Wiki untuk Bali
  ([BASAbali](https://dictionary.basabali.org/Limo)); kamussunda.net untuk Sunda (kamus daring tidak resmi,
  [kamussunda.net](https://www.kamussunda.net/kamus?teks=lintamo&bahasa=bahasa)); RAE, Treccani, Priberam, Tagalog.com,
  kamus musik UP Diliman (Iskomunidad), dan API Urban Dictionary. Kamus daring bahasa Madura tidak ditemukan.
- **Web.** [FAKTA] Kuota alat WebSearch sesi ini habis (200 dari 200). Brave lewat WebFetch dan Yahoo! JAPAN lewat skrip
  menjawab 429; Bing lewat peramban hanya memberi hasil tak relevan; Yandex menampilkan captcha. Google lewat peramban
  terisolasi berhasil untuk enam kueri (LINTAMO, PORANTI, TALAMPI; `gl=us` dan `gl=id`, 19.05.39 sampai 19.07.04 UTC),
  lalu menampilkan captcha. TANLIMO dan TOLAPI memakai Ecosia (19.09.48 dan 19.10.04 UTC). Hasil web putaran sebelumnya
  (23 September 2026, 15.28 sampai 16.30 UTC, `kandidat-dan-saringan.md` bagian 2.3) dipakai ulang sebagai pelengkap.
- **Perusahaan, produk, kripto.** API GitHub (repositori dan akun organisasi), Companies House Inggris, Product Hunt
  (peramban), [CoinGecko](https://api.coingecko.com/api/v3/search?query=lintamo),
  [Blockscout Ethereum](https://eth.blockscout.com/api/v2/search?q=lintamo), dan
  [DexScreener](https://api.dexscreener.com/latest/dex/search?q=lintamo), 18.53.08 sampai 18.57.09 UTC. Kontrol
  "uniswap" memberi 50 butir di Blockscout dan 30 pasangan di DexScreener. [FAKTA] Etherscan menjawab 403 dengan halaman
  "Just a moment..." (Cloudflare), API OpenCorporates menjawab 401, dan situs OpenCorporates menampilkan captcha
  HAProxy. Keduanya UNVERIFIED.
- **Handle.** Dibaca tanpa login lewat Chrome headless berprofil sementara, 18.58.10 sampai 19.02.55 UTC: TikTok dari data
  `webapp.user-detail`, Instagram dari judul halaman, YouTube dari status HTTP, X dari penanda halaman. Kontrol @tiktok
  terbaca "dipakai" di keempat platform; kontrol fiktif "qzxvkontrolhk" terbaca "tidak ditemukan" di keempatnya (TikTok
  kode 10221).
- **Arti kode TikTok.** [FAKTA] Berkas JavaScript web TikTok yang dimuat halaman
  [tiktok.com/@qzxvkontrolhk](https://www.tiktok.com/@qzxvkontrolhk) (dibaca 18.58.25 UTC; `webapp-desktop.adbedbe8.js`
  dan `biz.common.lib.66503841.js`) memuat enum `UserNotExist=10202`, `UserBan=10221`, `UserPrivate=10222`, serta
  pesan galat `"target user does not exist",10202`.

### Inferences
Aturan penerapan rubrik di putaran ini, ditetapkan sebelum penskoran agar kelima nama dinilai sama:
- **K1a.** Daftar K1 v2 ditambah J, W, E, gugus konsonan, dan koda selain n. "Gugus" dihitung bila dua konsonan berada
  dalam satu suku kata atau memaksa vokal sisipan dalam bahasa Jepang. Rangkaian nasal lalu konsonan yang melintasi batas
  suku kata (n.t, n.l, m.p) tidak dihitung sebagai gugus, karena bahasa Jepang menuliskannya dengan ン tanpa vokal sisipan
  dan bahasa Mandarin mengenal koda n ([Tofugu](https://www.tofugu.com/japanese/japanese-loanword-phonology/);
  `lanskap-dan-strategi.md` §2.1). "Koda selain n" dihitung apa adanya. Bacaan ketat (setiap rangkaian konsonan
  dihitung) dipakai sebagai uji sensitivitas di bagian 6.
- **K2.** 0 bila makna kasar, vulgar, atau sakral umum di Tier A, atau umum di Tier B; 1 bila makna tak diinginkan yang
  ringan, makna Tier B yang jarang, atau asosiasi ringan di Tier A; 2 bila tidak ditemukan di sumber yang dicek.
- **K6.** 0 (G3) bila ada merek identik, atau merek aktif yang berbeda satu huruf pada unsur pembedanya dengan barang
  atau jasa yang tumpang tindih (perangkat lunak, aplikasi, perjalanan) di kelas 9 atau 42 di ID, US, atau UE, atau ada
  aplikasi pesaing langsung. 1 bila tidak ada yang identik atau sangat mirip, tetapi ada merek aktif berjarak satu huruf
  di kelas inti (9, 35, 39, 41, 42, 45) di kantor mana pun yang dicek, atau ada pemakaian lain yang relevan. 2 bila tidak
  keduanya. Merek berjarak dua huruf hanya dicatat.
- **K8.** 0 bila nama itu sendiri kata deskriptif atau generik, atau salah eja yang dikenal dari kata itu, dalam bahasa
  yang dipahami pasar; 1 bila nama memuat, atau berjarak satu huruf dari, kata Tier A atau bahasa UE yang berkaitan
  dengan barang atau jasa (jalur, rute, alat, perangkat lunak); 2 bila tidak.
- **K4a.** Kata ciptaan diberi 1: berdaya pembeda tetapi butuh cerita atau deskriptor. **K9.** 2 bila bentuk katakana dan
  hangul stabil tanpa homofon negatif yang ditemukan; 1 bila ada satu masalah; 0 bila ada dua masalah serius.
- Semua skor adalah INFERENSI peneliti dari FAKTA di atas, bukan pendapat hukum. K1b (uji dengar-eja) belum dijalankan.

### Gaps
- Etherscan dan OpenCorporates tidak bisa dibaca (UNVERIFIED). Basis data perseroan Indonesia (AHU) tidak dicek karena
  memakai captcha.
- TMview tidak memuat Singapura dan Indonesia. Indonesia sudah tercakup lewat PDKI. Rekaman Singapura hanya terlihat bila
  muncul di WIPO GBD, jadi register IPOS belum dicek langsung.
- Google hanya dipakai untuk tiga nama sebelum captcha muncul. TANLIMO dan TOLAPI memakai Ecosia, yang tidak disetel ke
  pasar Indonesia.
- Tidak ada kamus daring bahasa Madura yang ditemukan; kamus Sunda yang dipakai tidak resmi.
- Status "tidak ditemukan" pada handle dan RDAP 404 adalah sinyal, bukan bukti bisa didaftarkan.

---

## 1. LINTAMO

### Takeaway
Tidak ada merek identik di PDKI, WIPO, USPTO, maupun TMview; keenam domain tanpa catatan RDAP; tidak ada aplikasi,
perusahaan, atau token bernama Lintamo. Kelemahannya ada di makna dan asosiasi: dalam bahasa Tagalog "linta" berarti
lintah atau lintah darat dan "mo" berarti "-mu", sehingga "linta mo" terbaca "lintahmu"; bagi telinga Indonesia dan
Melayu "linta-" dekat dengan "lintah". Awalan "lin-" tidak terbukti membangkitkan garis rute. Skor v2 9 dari 12, v3 20
dari 26, tanpa gerbang yang terpicu. K1b masih pending.

### Cited Findings
**Merek**
- [FAKTA] PDKI mode Normal "lintamo": 0 data; UI menulis "Data tidak ditemukan untuk kata kunci "lintamo"" (18.13.49
  UTC), dan kueri diapit kontrol "parwata" (18.15.43 dan 18.15.50 UTC)
  ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=lintamo&page=1)). Varian "lintama", "lentamo",
  dan "linta": 0 data (18.16.59 sampai 18.17.06 UTC, diapit kontrol).
- [FAKTA] Saran Fonetik PDKI (18.10.08 UTC): BINTAMOX, LintasGO, INTACO, GINTAM, JINTAM, LINTAU, LITACO, LITAZO, MINAMO,
  INTAKO. Yang aktif di kelas inti hanya dua:
  - "LintasGO", kelas 39, (TM) Didaftar, JID2024064269 / IDM001310929, 2024-07-15 sampai 2034-07-15, pemilik perorangan;
  - "MINAMO SOLAR SYSTEM", kelas 9 dan 19, (TM) Didaftar, KYORAKU CO., LTD. (JP), M0020191466483 / IDM000817568, sampai
    2029-02-20.
  LINTAU punya 17 data, dan satu-satunya yang di kelas inti sudah kedaluwarsa (kelas 35, 2014).
- [FAKTA] WIPO GBD: Embedded "No results found!" (18.44.34 UTC) dan Match exact "No results found!" (18.40.10 UTC).
  Phonetic 493 hasil (19.03.43 UTC) dan Fuzzy 926 hasil (19.04.03 UTC); rekaman terdekat di 30 teratas:
  - LINTAO, AS, kelas 9, Registered 2025-03-18, 7730389 (satu huruf);
  - LINTAM, Filipina, kelas 5, SUHITAS PHARMACEUTICALS INC., PH4202200502718;
  - Lintem, Inggris, kelas 9 dan 11, Shenzhen Laiteng Technology Co., Ltd., UK00003739146;
  - VINTAMO, Jerman, kelas 35, 37, 38, 41, 42, sudah Ended dan Expired;
  - Lentiamo, EUTM 011888633, kelas 5, 9, 35, 38, 44; LINGARO, IR 1650293, Lingaro Sp. z o.o., kelas 9, 35, 36, 38, 42, 45
    (keduanya dua huruf).
  Sumber: [WIPO Global Brand Database](https://branddb.wipo.int/en/quicksearch) lewat formulir.
- [FAKTA] USPTO (18.24.02 UTC): kueri UI persis 0 hasil. Wildcard `lintam*` 0 dan `lint?mo` 0; `*intamo` 1, yaitu
  SAINTAMO (Saintamo Co., Ltd., kelas 17, abandoned 2022-10-07). Kueri fuzzy (283 hasil) memberi satu merek hidup
  berjarak satu huruf: LINTAO, sn 98597788, reg. 7730389, kelas 9 "Charging appliances for rechargeable equipment;
  Computer keypa[ds] ...", pemilik perorangan
  ([TSDR 98597788](https://tsdr.uspto.gov/#caseNumber=98597788&caseSearchType=US_APPLICATION&caseType=DEFAULT&searchType=statusSearch)).
  Merek hidup berjarak dua huruf di kelas 9 atau 42 antara lain KINTAVO (Productive Technologies, LLC), INTOMO (INTOMO,
  LLC, diajukan 2026-07-06), LINGARO, NINJAMO, dan VANTAMO.
- [FAKTA] TMview (18.34.25 sampai 18.34.32 UTC): Contains 1, yaitu SALINTAMOL (AU, kelas 5, Expired, Glaxo Group Limited,
  573818); Is 0; Word 0; Ends with 0; Fuzzy 34, termasuk LINTAO (US kelas 9; CN berbagai kelas termasuk 35, 39, 41),
  LINTIMO (MX, IN), Litamo dan Linamo (DE), LINDAMO (CN), serta INTAMO (KR, Ended)
  ([TMview Contains](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=C&basicSearch=lintamo);
  [TMview Fuzzy](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=F&basicSearch=lintamo)).

**Domain (RDAP, 16.47.33 sampai 16.47.34 UTC)**
- [FAKTA] Tanpa catatan (404): [lintamo.com](https://rdap.verisign.com/com/v1/domain/lintamo.com),
  [lintamo.app](https://pubapi.registry.google/rdap/domain/lintamo.app), [lintamo.id](https://rdap.pandi.id/rdap/domain/lintamo.id),
  [lintamo.co.id](https://rdap.pandi.id/rdap/domain/lintamo.co.id),
  [getlintamo.com](https://rdap.verisign.com/com/v1/domain/getlintamo.com),
  [lintamoapp.com](https://rdap.verisign.com/com/v1/domain/lintamoapp.com).

**Toko aplikasi, perusahaan, produk, kripto**
- [FAKTA] App Store "lintamo": 0 hasil di kesepuluh etalase (16.47.59 sampai 16.48.30 UTC). Varian "lintama" (US 8, GB 7,
  AU 8) dan "lentamo" (US 23, GB 24, AU 22) tidak memunculkan judul atau penjual berjarak dua huruf
  ([contoh US](https://itunes.apple.com/search?term=lintamo&country=us&entity=software&limit=50)).
- [FAKTA] Google Play (19.11.57 dan 19.12.06 UTC): tidak ada judul atau paket yang memuat "lintamo"
  ([Play US](https://play.google.com/store/search?q=lintamo&c=apps&hl=en&gl=US)).
- [FAKTA] GitHub (18.53.08 UTC): 1 repositori "Lintamol" milik akun pribadi dan 3 akun pengguna, tanpa organisasi
  ([GitHub API](https://api.github.com/search/repositories?q=lintamo+in:name)). Companies House: 0 perusahaan
  ([Companies House](https://find-and-update.company-information.service.gov.uk/search/companies?q=lintamo)). Product
  Hunt: "No products found for "lintamo"" (18.55.47 UTC; [Product Hunt](https://www.producthunt.com/search?q=lintamo)).
  CoinGecko, Blockscout, dan DexScreener: 0 (18.53.08 dan 18.56.55 UTC).

**Web dan tokoh publik (Pasal 21 UU 20/2016)**
- [FAKTA] Google `gl=us` (19.05.39 UTC) dan `gl=id` (19.05.56 UTC): 8 dari 8 hasil tentang orang bernama keluarga
  Lintamo, tidak ada perusahaan atau produk ([Google US](https://www.google.com/search?q=%22lintamo%22&gl=us&hl=en);
  [Google ID](https://www.google.com/search?q=%22lintamo%22&gl=id&hl=id)). Tokohnya:
  - Alpo Lintamo, "Finnish footballer ... 13 appearances for the Finland national team from 1955 to 1958"
    ([Wikipedia](https://en.wikipedia.org/wiki/Alpo_Lintamo), dibaca 19.16.49 UTC);
  - Nuutti Lintamo, "Finnish footballer ... 23 appearances for the Finland national team from 1930 to 1939"
    ([Wikipedia](https://en.wikipedia.org/wiki/Nuutti_Lintamo), 19.16.50 UTC);
  - seorang pemengaruh media sosial di AS (nama perorangan sengaja tidak dicantumkan, sesuai aturan privasi riset ini).
- [FAKTA] Pasal 21 UU 20/2016 menolak merek yang memuat "nama atau singkatan nama orang terkenal"
  ([Ipindo](https://www.ipindo.com/merek-yang-tidak-dapat-didaftar-dan-yang-ditolak);
  [JDIH DJKI](https://jdih.dgip.go.id/produk_hukum/view/id/24/t/undangundang+nomor+20+tahun+2016+tentang+merek+dan+indikasi+geografis)),
  sebagaimana dikutip riset sebelumnya (`riset-nama-merek.md` bagian 1).

**Makna, Tier A**
- [FAKTA] Tanpa entri untuk "lintamo": Wiktionary (404), cermin KBBI, PRPM (0 definisi, 19.22.49 UTC), KBJI (tidak ada
  lema; pencarian bunyi hanya memberi landhéyan, lantun, lemantun), kamussunda.net ("Belum ada terjemahan"), dan BASAbali
  (halaman tidak ada).
- [FAKTA] Cermin KBBI "lintah": "hewan air ... alat untuk mengisap darah; ... -- darat ki orang yang meminjamkan uang"
  ([kbbi.web.id/lintah](https://kbbi.web.id/lintah)); PRPM "lintah": binatang kelas Hirudinea (Kamus Dewan,
  [PRPM](https://prpm.dbp.gov.my/Cari1?keyword=lintah)). "linta" sendiri tidak ada di cermin KBBI, PRPM, dan KBJI.
- [FAKTA] Wiktionary "lintas": Indonesia "route, path; pass, cross", Melayu "to cross over"
  ([lintas](https://en.wiktionary.org/wiki/lintas)); Inggris "lint" = serat halus yang menempel di kain, Belanda "lint" =
  pita ([lint](https://en.wiktionary.org/wiki/lint)). Urban Dictionary: tidak ada entri kata persis "lintamo"
  ([UD](https://www.urbandictionary.com/define.php?term=lintamo)).

**Makna, Tier B**
- [FAKTA] Tagalog "linta" = "(river) leech" dan "bloodsucker; usurer (people who preys on others)"; Bikol Tengah
  "leech" ([linta](https://en.wiktionary.org/wiki/linta), 18.42.48 UTC). Tagalog "mo" = "your; of you; by you"
  ([mo](https://en.wiktionary.org/wiki/mo), 18.47.04 UTC).
- [FAKTA] Spanyol "tamo": "Pelusa que se desprende del lino, del algodón o de la lana" dan "Pelusilla que se cría debajo
  de las camas ... por falta de aseo" ([RAE](https://dle.rae.es/tamo), 19.15.14 UTC); Portugis "tamo" = ejaan lisan
  "estamos"; Serbo-Kroasia "tamo" = di sana ([tamo](https://en.wiktionary.org/wiki/tamo)). Italia "amo" = kail atau
  "(aku) cinta"; Spanyol "amo" = majikan, pemilik ([amo](https://en.wiktionary.org/wiki/amo)).

**Aksara (K9)**
- [INFERENSI] Katakana リンタモ (ri-n-ta-mo, 4 mora; L menjadi R dan n akhir menjadi ン, sesuai
  [Tofugu](https://www.tofugu.com/japanese/japanese-loanword-phonology/)); hangul 린타모. [FAKTA] Untuk kata serapan
  Inggris, ㄹ di awal kata "commonly [ɾ]" ([Korean phonology](https://en.wikipedia.org/wiki/Korean_phonology)). Tidak ada
  homofon negatif yang ditemukan untuk suku pinyin lin, ta, mo di sumber yang dicek.

**Handle (K7, 18.59.19 UTC)**
- [FAKTA] TikTok @lintamo dipakai (197 pengikut, 43 video); Instagram dipakai; YouTube 404; X dipakai (akun pribadi).
  @lintamoapp tidak ditemukan di keempat platform (19.01.26 UTC).

### Inferences
- [INFERENSI] **Apakah "lin-" membangkitkan garis rute?** Tidak terbukti. Dalam bahasa Inggris "lin" dibaca /lɪn/, bukan
  "line" /laɪn/. Bagi penutur Indonesia dan Melayu, bentuk "linta" satu huruf dari "lintas" (rute) dan juga satu huruf
  dari "lintah" (hewan pengisap darah; kiasan lintah darat). Dalam bahasa Tagalog "linta" justru kata yang ada, yaitu
  lintah. Jadi asosiasi rute bergantung pada cerita merek, dan asosiasi lintah perlu diuji (tugas (c) di bagian 7).
- [INFERENSI] **Pasal 21.** Yang dipakai hanya nama keluarga, bukan nama lengkap tokoh. Tokohnya pesepak bola Finlandia
  era 1930-an sampai 1950-an dan pemengaruh ceruk di AS. Risikonya rendah, tetapi tetap perlu pendapat Konsultan KI.

| Kriteria | v2 | v3 (bobot) | Dasar |
|---|---|---|---|
| K1 / K1a | 2 | 2 (×2 = 4) | Tiga suku kata terbuka dan tertutup n; tanpa huruf labil, J, W, E, gugus, atau koda selain n |
| K1b | pending | pending | Uji dengar-eja belum dijalankan |
| K2 | 1 | 1 (×2 = 2) | Tagalog "linta mo" = "lintahmu"; gema "lintah" bagi penutur Indonesia dan Melayu (HIPOTESIS, perlu penutur) |
| K3 | 2 | 2 (×1 = 2) | Tidak ada kecepatan, kompetisi, penaklukan, puncak, adrenalin, atau klaim aman |
| K4 / K4a | 1 | 1 (×1 = 1) | Kata ciptaan; butuh deskriptor, misalnya "Lintamo, perencana jalur gunung" |
| K5 | 2 | 2 (×1 = 2) | Enam domain tanpa catatan RDAP |
| K6 | 1 | 1 (×3 = 3) | Tanpa merek identik; LINTAO (AS, kelas 9, berjarak satu huruf, barang perangkat keras); LintasGO (ID, kelas 39); nama keluarga tokoh publik |
| K8 | (tidak ada) | 2 (×2 = 4) | Fanciful; "lintas" berjarak dua huruf, tidak dimuat |
| K9 | (tidak ada) | 2 (×1 = 2) | Katakana dan hangul stabil; tidak ada homofon negatif yang ditemukan |
| **Total** | **9 / 12** | **20 / 26** | Tidak ada gerbang terpicu (G1, G2, G3, G4, G5) |

- [INFERENSI] **Vonis: lolos dengan catatan.** Nilai terkuatnya daya pembeda (K8) dan kebersihan konflik; kelemahannya
  makna "lintah" di Tagalog dan kemungkinan gema serupa di Indonesia, serta tiga handle utama yang sudah dipakai.

### Gaps
- Penutur Tagalog dan Indonesia belum ditanya apakah "Lintamo" terdengar sebagai "linta mo" atau "lintah".
- Penutur Mandarin belum menilai bunyi "lin-ta-mo" (misalnya kedekatan dengan ungkapan kasar "他妈" tā mā; HIPOTESIS).
- Kelas 9 LINTAO hanya dibaca dari ringkasan barang di data USPTO; penilaian kemiripan resmi butuh Konsultan KI.

---

## 2. PORANTI

### Takeaway
Tidak ada merek identik di keempat basis data, keenam domain tanpa catatan RDAP, dan tidak ada aplikasi, perusahaan,
atau token bernama Poranti. Tetapi temuan hari ini menurunkan nama ini: (1) di Indonesia "PORANTI" sudah dipakai sebagai
akronim "Pekan Olahraga Antar Tingkat" di lingkungan kampus UNS, yang membawa nuansa kompetisi; (2) nama ini berjarak
satu huruf dari kata Indonesia "peranti" (alat, perkakas; "peranti lunak" = perangkat lunak), sehingga daya pembedanya
di Indonesia, tempat merek dasar Madrid diajukan, lebih lemah; (3) dalam bahasa Ceko, Polandia, dan Rusia bentuk
terdekatnya berarti "melukai". Kode TikTok 10202 terbukti berarti `UserNotExist`. Skor v2 8 dari 12, v3 16 dari 26,
tanpa gerbang yang terpicu. K1b masih pending.

### Cited Findings
**Merek**
- [FAKTA] PDKI mode Normal "poranti": 0 data; UI "Data tidak ditemukan untuk kata kunci "poranti"" (18.14.05 UTC); kueri
  diapit kontrol (18.15.45 UTC) ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=poranti&page=1)).
  "poranty" dan "poran": 0 (18.17.13 dan 18.17.17 UTC, diapit kontrol). "paranti": 4 data, tidak ada di kelas inti.
- [FAKTA] Saran Fonetik PDKI (18.10.25 UTC): PIRANTI, MORANTI, PARANTI, Piranti, PRIANTI, PHIRANTI, PURWANTI, PIRANTIX,
  JOBRANTI, BRANTI. Yang aktif di kelas inti:
  - "PIRANTI", kelas 35, (TM) Didaftar, JID2017003358 / IDM000610473, 2017-01-20 sampai 2027-01-20, pemilik perorangan;
  - "PIRANTI BAKE", kelas 35, (TM) Pemeriksaan Substantif, JID2026028514 (diajukan 2026-03-31), pemilik perorangan.
  Yang sudah mati: "PIRANTI ARTHA UPAYA" (kelas 35, PT PIRANTI ARTHA UPAYA, berakhir 2013) dan "FASTINDO PIRANTI KABEL +
  LOGO" (kelas 9, PT FASTINDOPIRANTI KABEL, berakhir 2017). Unsur "ranti": "RANTI" kelas 35 aktif (PT. Citra Busana
  Indonesia, JID2019081099, sampai 2029-12-30).
- [FAKTA] WIPO GBD: Embedded "No results found!" (18.48.12 UTC). Phonetic 129.987 hasil (19.04.23 UTC), Fuzzy 1.701 hasil
  (19.04.43 UTC). Rekaman berjarak satu huruf di 30 teratas antara lain:
  - PARANTI, Indonesia: kelas 7 (PT PARANTI ALAM SEJAHTERA, IDM001043820), kelas 30 (IDM000365777), dan "PARANTI
    NGEMIL" kelas 43;
  - X PERANTI (Viet Nam, kelas 9) dan "xperanti peranti" (Singapura, kelas 9, Registered 2018-08-30, 40201803887V);
  - PIRANTI (Indonesia, kelas 35, IDM000610473); MORANTI (AS kelas 16, Wuxi CRTL Trade Co. Ltd.); Poranto (Selandia
    Baru, kelas 18, Chuzhou Star Gifts Trading Co., Ltd.); PORANTIM (Brasil, kelas 43, PEIXARIA PORANTIM LTDA,
    928713920).
- [FAKTA] USPTO (18.24.14 UTC): kueri UI persis 0. Wildcard `poran*` 17 hasil, yang hidup hanya PORANDIA (kelas 21,
  Guangzhou Simahui E-commerce Co., Ltd., reg. 8406362); `*oranti` 5 hasil, yang hidup hanya MORANTI (kelas 16);
  `por?nti` 0. Kueri fuzzy (420 hasil): merek hidup berjarak satu huruf hanya MORANTI (16) dan PA POLANTI (14); yang
  berjarak dua huruf di kelas inti antara lain ARRANTI (35, 42), PERENTI (39, 42; PERENTI GLOBAL LIMITED), ORVANTI,
  DORANTE, dan PUBANTI (kelas 9, pemilik perorangan).
- [FAKTA] TMview (18.34.34 sampai 18.34.41 UTC): Contains 7, antara lain PORANTIM (BR, kelas 43, Registered) dan
  Luporantino (BR, kelas 35 dan 43, Filed, LUPO NERO RESTAURANTE LTDA.); Is 0; Word 0. Fuzzy 122; merek aktif berjarak
  satu huruf di kelas inti: LORANTI (DE, 3, 5, 14, 35), ORANTI (RU, termasuk 35, 41, 42), PORATI (VN, 41), MORANTI (CN,
  9), X PERANTI (VN, 9), dan V VORANTI
  ([TMview Fuzzy](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=F&basicSearch=poranti)).
- [FAKTA] Rincian V VORANTI (EUTM 019376266, dibaca 18.38.31 UTC): diajukan 2026-06-05, status Registered bertanggal
  2026-09-23, kelas 9 dan 11; barang kelas 9 "Uninterruptible power supply apparatus [battery]; electric power supply
  units; batteries; rechargeable batteries; battery chargers; ..."
  ([eSearch EUIPO](https://euipo.europa.eu/eSearch/#details/trademarks/019376266)).

**Domain (RDAP, 16.47.34 sampai 16.47.35 UTC)**
- [FAKTA] Tanpa catatan: [poranti.com](https://rdap.verisign.com/com/v1/domain/poranti.com),
  [poranti.app](https://pubapi.registry.google/rdap/domain/poranti.app), [poranti.id](https://rdap.pandi.id/rdap/domain/poranti.id),
  [poranti.co.id](https://rdap.pandi.id/rdap/domain/poranti.co.id),
  [getporanti.com](https://rdap.verisign.com/com/v1/domain/getporanti.com),
  [porantiapp.com](https://rdap.verisign.com/com/v1/domain/porantiapp.com).

**Toko aplikasi, perusahaan, produk, kripto**
- [FAKTA] App Store "poranti" (16.49.47 sampai 16.51.30 UTC): US 7, GB 7, AU 7, tujuh etalase lain 0; tidak ada judul
  atau penjual yang memuat nama. Varian "poranty" dan "paranti" juga tanpa kecocokan
  ([contoh US](https://itunes.apple.com/search?term=poranti&country=us&entity=software&limit=50)). Google Play US (30
  aplikasi) dan ID (9 aplikasi): tidak ada yang memuat nama (19.12.16 dan 19.12.25 UTC).
- [FAKTA] GitHub: 0 repositori, 1 akun pengguna, tanpa organisasi; Companies House 0; Product Hunt tidak memuat produk
  bernama Poranti; CoinGecko, Blockscout, DexScreener 0 (18.53.18 sampai 18.56.58 UTC).

**Web**
- [FAKTA] Google `gl=id` (19.06.30 UTC): "PORANTI" adalah nama acara olahraga kampus di Universitas Sebelas Maret,
  misalnya "OPEN RECRUITMENT PANITIA PORANTI 2026" (Instagram FIB UNS), "PRESS RELEASE PORANTI 2024", "Rundown Futsal
  Poranti Day 7" (Scribd), dan video "PORANTI 2025" dengan deskripsi "Pekan Olahraga Antar Tingkat"
  ([Google ID](https://www.google.com/search?q=%22poranti%22&gl=id&hl=id)).
- [FAKTA] Google `gl=us` (19.06.13 UTC): akun Instagram pribadi @poranti; nama diri seseorang di TikTok dan Facebook;
  "Dairy Bull - PORANTI" di katalog genetika sapi stgen.com.br; "poranti" di Glosbe; peta "Poranti" di gim Angels Fall
  First (diskusi Steam) ([Google US](https://www.google.com/search?q=%22poranti%22&gl=us&hl=en)).
- [FAKTA] Glosbe: "cognac is the translation of "poranti" into English" untuk bahasa Sotho Utara, bersumber Dbnary
  (19.25.00 UTC; [Glosbe](https://glosbe.com/nso/en/poranti)).

**Makna, Tier A**
- [FAKTA] Tanpa entri "poranti": Wiktionary (404), cermin KBBI, PRPM (0 definisi), KBJI (tidak ada lema persis),
  kamussunda.net ("Belum ada terjemahan"), BASAbali (tidak ada halaman).
- [FAKTA] Indonesia "peranti": "1 alat; perkakas ... 2 syarat; 3 bahan" ([kbbi.web.id/peranti](https://kbbi.web.id/peranti));
  Melayu "peranti": "alat, perkakas, pesawat" ([PRPM](https://prpm.dbp.gov.my/Cari1?keyword=peranti), 19.23.15 UTC);
  "piranti" = "nonstandard form of peranti" ([piranti](https://en.wiktionary.org/wiki/piranti)); "peranti lunak" =
  "software" ([peranti lunak](https://en.wiktionary.org/wiki/peranti_lunak), 19.25.30 UTC).
- [FAKTA] Jawa (KBJI): "pora" dialek "biar, biarlah" dan arkais "kawula"; "poran" dialek "biar, biarlah"
  ([KBJI pora](https://kbji.kemendikdasmen.go.id/kata/pora)). Sunda: "paranti" diarahkan ke "paragi"
  ([kamussunda.net](https://www.kamussunda.net/kamus?teks=paranti&bahasa=bahasa)). Inggris: "rant" = "To speak or shout
  at length in uncontrollable anger" ([rant](https://en.wiktionary.org/wiki/rant)).

**Makna, Tier B**
- [FAKTA] Ceko "poranit" = "to hurt, injure (physically)" ([poranit](https://en.wiktionary.org/wiki/poranit), 18.44.49
  UTC); Polandia "poranić" = "to wound (to inflict injuries many times or in many places)"
  ([poranić](https://en.wiktionary.org/wiki/poranić), 18.45.09 UTC); Rusia "поранить" = "to wound, to injure, to hurt"
  ([поранить](https://en.wiktionary.org/wiki/поранить), 18.43.01 UTC); Serbo-Kroasia "poraniti" = "to get up early"
  ([poraniti](https://en.wiktionary.org/wiki/poraniti)).
- [FAKTA] Italia, Portugis, Spanyol: tidak ditemukan entri "poranti"; RAE: "La palabra poranti no está en el
  Diccionario" (19.19.29 UTC; [RAE](https://dle.rae.es/poranti)). Finlandia "poran" = genitif "pora" (bor), Polandia
  "pora" = waktu ([pora](https://en.wiktionary.org/wiki/pora)). Urban Dictionary: tanpa entri kata persis "poranti".

**Aksara (K9)**
- [FAKTA] Mandarin 破爛 (pòlàn) = "dilapidated; battered; tattered; ragged" dan "useless junk; rubbish"
  ([破爛](https://en.wiktionary.org/wiki/破爛)); 波蘭 = Polandia ([波蘭](https://en.wiktionary.org/wiki/波蘭)); Jepang
  ボランティア = "volunteer" ([ボランティア](https://en.wiktionary.org/wiki/ボランティア)).
- [INFERENSI] Katakana ポランティ (po-ra-n-ti, 4 mora) berbunyi dekat ボランティア; transliterasi Han yang lazim untuk
  "po-lan" adalah 波兰, yang sama dengan nama Polandia; tanpa nada, "po lan" sama dengan 破烂 (rongsokan). Hangul
  포란티.

**Handle (K7, 18.59.46 UTC)**
- [FAKTA] TikTok @poranti: kode `webapp.user-detail` 10202, yang di JavaScript TikTok bernama `UserNotExist` ("target user
  does not exist"). Kontrol fiktif memberi 10221 (`UserBan`), jadi kedua kode sama-sama berarti tidak ada akun publik
  aktif. Instagram dipakai; YouTube 200 (dipakai); X tidak ditemukan. @porantiapp tidak ditemukan di keempat platform
  (19.01.48 UTC).

### Inferences
- [INFERENSI] **Arti 10202.** Menurut enum TikTok, 10202 berarti pengguna tidak ada. Mengapa handle fiktif justru memberi
  10221 belum jelas; salah satu kemungkinan (HIPOTESIS) adalah @poranti pernah dipakai lalu dihapus atau diganti. Apa pun
  sebabnya, tanpa login tidak bisa dipastikan handle itu bisa didaftarkan.
- [INFERENSI] **Nuansa kompetisi di Indonesia.** Akronim "POR..." lazim untuk pekan olahraga, dan "PORANTI" sudah dipakai
  untuk pekan olahraga antarangkatan. Pemakaiannya ceruk (satu lingkungan fakultas), jadi K3 diberi 1, bukan 0.
- [INFERENSI] **Daya pembeda di Indonesia.** "Poranti" berjarak satu huruf dari "peranti" dan dari bentuk tidak baku
  "piranti", dan mesin fonetik PDKI sendiri menaruh PIRANTI di urutan pertama. Untuk barang kelas 9 (perangkat lunak),
  pemeriksa bisa menilai nama ini sugestif, atau dalam skenario terburuk salah eja kata deskriptif. Ini risiko bagi
  merek dasar Madrid, jadi K8 diberi 1.

| Kriteria | v2 | v3 (bobot) | Dasar |
|---|---|---|---|
| K1 / K1a | 2 | 2 (×2 = 4) | po-ran-ti; tanpa huruf labil, J, W, E, gugus, atau koda selain n |
| K1b | pending | pending | Risiko: o tak bertekanan dibaca lemah oleh penutur Inggris, sehingga tertulis "paranti" atau "puranti" (HIPOTESIS) |
| K2 | 1 | 1 (×2 = 2) | Bentuk terdekat berarti "melukai" di Ceko, Polandia, Rusia; "rant" terlihat di tengah; Sotho Utara "cognac" |
| K3 | 1 | 1 (×1 = 1) | Akronim "Pekan Olahraga Antar Tingkat" di UNS |
| K4 / K4a | 1 | 1 (×1 = 1) | Kata ciptaan tanpa cerita bawaan; butuh deskriptor |
| K5 | 2 | 2 (×1 = 2) | Enam domain tanpa catatan RDAP |
| K6 | 1 | 1 (×3 = 3) | Tanpa merek identik; PIRANTI (ID, 35), V VORANTI (UE, 9, catu daya), MORANTI (CN, 9), X PERANTI (VN, 9), "xperanti peranti" (SG, 9) |
| K8 | (tidak ada) | 1 (×2 = 2) | Satu huruf dari "peranti" (alat) di bahasa Indonesia |
| K9 | (tidak ada) | 1 (×1 = 1) | 破烂 (rongsokan) dan 波兰 (Polandia) pada transliterasi Han |
| **Total** | **8 / 12** | **16 / 26** | Tidak ada gerbang terpicu |

Catatan hitung v3: K6 3 + K8 2 + K2 2 + K1a 4 + K3 1 + K4a 1 + K9 1 + K5 2 = 16.

- [INFERENSI] **Vonis: lolos gerbang, tetapi peringkat bawah.** Tiga kelemahan ada di Indonesia sendiri (akronim acara
  olahraga, kedekatan dengan "peranti", dan PIRANTI di kelas 35), padahal Indonesia adalah pasar pertama dan basis Madrid.

### Gaps
- Penutur Ceko, Polandia, dan Rusia belum ditanya apakah "poranti" langsung terdengar seperti "poranit".
- Seberapa luas akronim PORANTI dipakai di luar UNS tidak diketahui; hanya hasil Google halaman pertama yang dibaca.
- Makna bahasa Sotho Utara hanya dari Glosbe (data turunan Wiktionary), tidak diverifikasi di kamus primer.

---

## 3. TALAMPI

### Takeaway
TALAMPI paling bersih di sisi merek: tidak ada merek identik di keempat basis data, dan tidak ada merek aktif berjarak
satu huruf di kelas inti. Yang dekat hanyalah merek farmasi kelas 5 (TALAMPIN, TALAMPIL) dari awalan antibiotik
talampicillin. Temuan baru: "talampí" adalah kata Tagalog yang jarang dipakai, berarti "memukul atau menampar dengan
tangan", dan dalam kamus musik UP Diliman berarti sepasang tepukan bambu. Gema "lamp" netral sampai positif (Inggris
lamp, Italia "lampi" kilat, Finlandia "lampi" kolam). Skor v2 10 dari 12, v3 21 dari 26, tanpa gerbang terpicu, tetapi
K1a turun ke 1 karena koda m. K1b masih pending.

### Cited Findings
**Merek**
- [FAKTA] PDKI mode Normal "talampi": 0 data; UI "Data tidak ditemukan untuk kata kunci "talampi"" (18.14.21 UTC); kueri
  diapit kontrol (18.15.47 UTC) ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=talampi&page=1)).
  "talampy", "tolampi", dan "lampi": 0 (18.17.20 sampai 18.17.31 UTC, diapit kontrol).
- [FAKTA] Saran Fonetik PDKI: talami, ALAMI, Tampi, TALAM, TALAKOPI, HALAMI, SALAMI, ALAMRI, NALAMI, TAKAMI. Pemindaian
  pertama terganggu pembatasan PDKI, lalu diulang pada 18.12.23 sampai 18.13.06 UTC. Yang aktif di kelas inti:
  - ALAMI, kelas 39 (pemilik perorangan); "KETAN TALAM DURIAN", kelas 35 (pemilik perorangan);
  - "TALAKOPI", kelas 35 dan 43, PT. TALA BUMI INDONESIA, JID2025054433, sampai 2035-06-13;
  - "SALAMI & HURUF KANJI", kelas 35, PT. Wallace Food Chain Group; "TAKAMI", kelas 35, PT Dekoruma Niaga Sejahtera,
    dan TAKAMI kelas 3, 5, 9, 10, 11, 44 (pemilik perorangan, Jepang).
  Semuanya berjarak dua huruf atau lebih dari TALAMPI.
- [FAKTA] WIPO GBD: Embedded "No results found!" (18.50.23 UTC). Phonetic 458 hasil (19.05.04 UTC), Fuzzy 1.405 hasil
  (19.05.24 UTC). Rekaman terdekat: TALAMPIL (Paraguay, kelas 5, Registered 1986, Farmacéutica Paraguaya S.A.),
  TALAMPIN (India, kelas 5, Registered 1978, Vitro Pharma Products Limited), KALAMPI (Prancis, kelas 5, Expired 2013,
  Laboratoire TVM), TALAMP (Yordania, Expired), talami (Indonesia, kelas 30), serta TARAMPS (Brasil, kelas 9 dan 35,
  TARAMPS ELECTRONICS LTDA.) dan TALABI ENGENHARIA (Brasil, kelas 42, Pending), keduanya berjarak dua huruf.
- [FAKTA] USPTO (18.24.27 UTC): kueri UI persis 0. Wildcard `talamp*` 1, yaitu TALAMPAYA (mati, dibatalkan 2020, Larex
  AB, kelas 29, 32, 33); `*alampi` 0; `tal?mpi` 0. Kueri fuzzy (310 hasil): tidak ada merek hidup berjarak satu huruf.
  Yang berjarak dua huruf di kelas inti: CALAMP dan CALAMP TRACKER (CalAmp Corp., kelas 9, 35, 38, 42, 45), TALAMEX
  (Cortes Deco, Inc., kelas 9, 19, 20, 21), TALAMUS (kelas 35 dan 41), TALARI (Talari Networks, Inc., kelas 9).
- [FAKTA] TMview (18.34.43 sampai 18.34.50 UTC): Contains 3, yaitu TALAMPIN (IN, kelas 5, Registered), TALAMPIN (KR,
  Ended), TALAMPIL (PY, kelas 5, Registered); Is 0; Word 0; Fuzzy 15 tanpa merek aktif berjarak dua huruf di kelas inti
  ([TMview Contains](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=C&basicSearch=talampi)).

**Domain (RDAP, 16.47.35 sampai 16.47.36 UTC)**
- [FAKTA] Tanpa catatan: [talampi.com](https://rdap.verisign.com/com/v1/domain/talampi.com),
  [talampi.app](https://pubapi.registry.google/rdap/domain/talampi.app), [talampi.id](https://rdap.pandi.id/rdap/domain/talampi.id),
  [talampi.co.id](https://rdap.pandi.id/rdap/domain/talampi.co.id),
  [gettalampi.com](https://rdap.verisign.com/com/v1/domain/gettalampi.com),
  [talampiapp.com](https://rdap.verisign.com/com/v1/domain/talampiapp.com).

**Toko aplikasi, perusahaan, produk, kripto**
- [FAKTA] App Store "talampi" (16.51.34 sampai 16.53.19 UTC): US 17, GB 17, AU 16, tujuh etalase lain 0; tidak ada yang
  memuat nama; varian "talampy" dan "tolampi" juga tidak
  ([contoh US](https://itunes.apple.com/search?term=talampi&country=us&entity=software&limit=50)). Google Play US 0
  aplikasi, ID 50 aplikasi tanpa yang memuat nama (19.12.35 dan 19.12.45 UTC).
- [FAKTA] GitHub: 0 repositori, 2 akun pengguna, tanpa organisasi; Companies House 0; Product Hunt "No products found for
  "talampi""; CoinGecko, Blockscout, DexScreener 0 (18.53.27 sampai 18.57.01 UTC).

**Web**
- [FAKTA] Google `gl=us` (19.06.47 UTC) dan `gl=id` (19.07.04 UTC) ([Google US](https://www.google.com/search?q=%22talampi%22&gl=us&hl=en)):
  - entri kamus Tagalog "talampí" di Tagalog.com dan entri "Talampi" di kamus istilah musik Filipina (Iskomunidad UP);
  - desa "Talampi C-179-180", Himachal Pradesh (AccuWeather, Interia, Online Patrakar Sangh);
  - akun pribadi di Pinterest, X, Facebook, dan YouTube, serta satu unggahan Facebook berbahasa Sunda yang memakai kata
    "talampi" dalam konteks burung merpati (maknanya tidak jelas).
- [FAKTA] Putaran sebelumnya mencatat desa Talampi di Kinnaur
  ([villagecodes](https://villagecodes.in/himachal-pradesh/village/27023-d-p-f-c-179-180-talampi-49/)) dan antibiotik
  talampicillin ([J-STAGE](https://www.jstage.jst.go.jp/article/kansenshogakuzasshi1970/49/11/49_11_485/_pdf)).

**Makna, Tier A**
- [FAKTA] Tanpa entri "talampi": Wiktionary (404), cermin KBBI, PRPM (0 definisi), KBJI (tidak ada lema persis;
  pencarian bunyi memberi tlémpé, tlimpé, tlompé, tlumpah), kamussunda.net ("Belum ada terjemahan"), BASAbali (tidak ada
  halaman).
- [FAKTA] "talam": cermin KBBI "dulang yang tidak berkaki; -- dua muka, pb bermuka dua; mendua hati"
  ([kbbi.web.id/talam](https://kbbi.web.id/talam)); PRPM "sj dulang yg tidak berkaki"
  ([PRPM](https://prpm.dbp.gov.my/Cari1?keyword=talam)); KBJI "talam dl KN tembor, baki"
  ([KBJI](https://kbji.kemendikdasmen.go.id/kata/talam)); Wiktionary Indonesia dan Melayu "tray"
  ([talam](https://en.wiktionary.org/wiki/talam)).

**Makna, Tier B**
- [FAKTA] Tagalog "talampí": "striking or slapping using the hands • slap • smack • hit • strike", akar "lampi", "Usage:
  Not Frequent" ([Tagalog.com](https://www.tagalog.com/dictionary/talampi), 19.09.10 UTC). Kamus istilah musik Filipina:
  "Talampi ... A pair of bamboo clappers, sticks. Also called katupi" (tanda pisah asli diganti koma), kelompok
  etnolinguistik Tagalog
  ([Iskomunidad UP Diliman](https://iskomunidad.upd.edu.ph/index.php/MUSDIKS:talampi), 19.09.12 UTC).
- [FAKTA] Tagalog "talampas" = "plateau; mesa; tableland" ([talampas](https://en.wiktionary.org/wiki/talampas));
  "talampakan" = telapak kaki, juga satuan kaki ([talampakan](https://en.wiktionary.org/wiki/talampakan)).
- [FAKTA] Finlandia "lampi" = kolam alami kecil; Italia "lampi" = jamak "lampo" (kilat); Karelia "lampi" = danau hutan
  atau jalan ([lampi](https://en.wiktionary.org/wiki/lampi)). Urban Dictionary: tanpa entri kata persis "talampi" dan
  "lampi".

**Aksara (K9)**
- [INFERENSI] Katakana タランピ (ta-ra-n-pi, 4 mora; m sebelum p menjadi ン, tanpa vokal sisipan); hangul 탈람피;
  transliterasi Han kira-kira 塔兰皮. Tidak ada homofon negatif yang ditemukan. Akhiran "pi" tanpa nada bisa bertemu 屁
  (kentut), tetapi ini berlaku untuk nama apa pun yang berakhiran "-pi" dan tidak dihitung.

**Handle (K7, 19.00.12 UTC)**
- [FAKTA] TikTok dipakai (46 pengikut, 0 video); Instagram dipakai; YouTube 404; X tidak ditemukan. @talampiapp tidak
  ditemukan di keempat platform (19.02.13 UTC).

### Inferences
- [INFERENSI] **Makna Tagalog.** "Talampí" (menampar) adalah makna Tier B yang ditandai jarang dipakai, jadi K2 = 1,
  bukan 0. Makna kedua (alat musik tepuk bambu) netral. Filipina bukan pasar prioritas di strategi merek, tetapi penutur
  Tagalog adalah bagian dari pengunjung Asia Tenggara, jadi perlu satu penutur untuk menilai kesan pertamanya.
- [INFERENSI] **Desa dan antibiotik.** Nama desa di Kinnaur tidak menghalangi pendaftaran di kelas perangkat lunak, dan
  merek TALAMPIN atau TALAMPIL ada di kelas 5 (farmasi), di luar kelas inti. Keduanya cukup dicatat.
- [INFERENSI] **Cerita merek yang mungkin:** "lampu" atau "pelita" yang menerangi alasan di balik pilihan jalur, sejalan
  dengan "Pahami alasannya". Cerita ini perlu diuji; gema "slap" di Tagalog dan "talam dua muka" di Indonesia sebaiknya
  masuk tugas (c) uji dengar-eja.

| Kriteria | v2 | v3 (bobot) | Dasar |
|---|---|---|---|
| K1 / K1a | 2 | 1 (×2 = 2) | v2 tidak memuat koda; di v3 koda m pada "lam" dihitung satu masalah. Dari bentuk katakana (ン) atau Han, pendengar Jepang dan Tionghoa bisa menulis "talanpi" |
| K1b | pending | pending | Juga uji apakah penutur Inggris menulis "talampy" |
| K2 | 1 | 1 (×2 = 2) | Tagalog "talampí" = menampar (jarang dipakai) |
| K3 | 2 | 2 (×1 = 2) | Tidak ada kecepatan, kompetisi, penaklukan, puncak, adrenalin, atau klaim aman |
| K4 / K4a | 1 | 1 (×1 = 1) | Kata ciptaan; cerita "lampu yang menerangi alasan" perlu dibangun |
| K5 | 2 | 2 (×1 = 2) | Enam domain tanpa catatan RDAP |
| K6 | 2 | 2 (×3 = 6) | Tanpa merek identik dan tanpa merek aktif berjarak satu huruf di kelas inti; kelas 5 dan jarak dua huruf hanya dicatat |
| K8 | (tidak ada) | 2 (×2 = 4) | Fanciful; "lamp" dan "talampas" tidak deskriptif untuk perencanaan jalur |
| K9 | (tidak ada) | 2 (×1 = 2) | Katakana dan hangul stabil; tidak ada homofon negatif yang ditemukan |
| **Total** | **10 / 12** | **21 / 26** | Tidak ada gerbang terpicu |

- [INFERENSI] **Vonis: lolos, peringkat pertama kelompok ini** (lihat bagian 6 untuk kepekaan skor).

### Gaps
- Penutur Tagalog belum menilai seberapa kuat "talampi" terdengar sebagai "tampar".
- Arti "talampi" dalam unggahan berbahasa Sunda belum diketahui; kamus Sunda yang dicek tidak memuatnya.
- Kelas 9 CalAmp dan TALAMEX hanya dibaca dari data kelas USPTO; penilaian kemiripan resmi butuh Konsultan KI.

---

## 4. TANLIMO

### Takeaway
Tidak ada merek identik, keenam domain tanpa catatan RDAP, dan tidak ada aplikasi, perusahaan, atau token bernama
Tanlimo. Kamus primer mengonfirmasi bahwa "limo" berarti lumpur atau endapan dalam bahasa Spanyol (RAE), Italia
(Treccani), dan Portugis (Priberam), selain singkatan "limousine" dalam bahasa Inggris dan "Limonade" dalam bahasa Jerman.
Merek terdekat adalah TANSIMO ROBOTIC (robot humanoid, kelas 7, 9, 10) yang aktif di AS, UE, Inggris, dan Australia,
berjarak satu huruf dari unsur pembedanya. Bagi pembaca Asia Tenggara, "Tan Limo" juga mudah terbaca sebagai nama orang
bermarga Tan. Skor v2 9 dari 12, v3 19 dari 26, tanpa gerbang terpicu. K1b masih pending.

### Cited Findings
**Merek**
- [FAKTA] PDKI mode Normal "tanlimo": 0 data; UI "Data tidak ditemukan untuk kata kunci "tanlimo"" (18.14.36 UTC); kueri
  diapit kontrol (18.15.48 UTC) ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=tanlimo&page=1)).
  "tan limo" dan "tanlima": 0 (18.17.35 dan 18.17.42 UTC, diapit kontrol).
- [FAKTA] Unsur "limo" di PDKI: 92 data. Yang aktif di kelas inti:
  - "LIMO", kelas 9, (TM) Pemeriksaan Substantif, DID2026048491 (diajukan 2026-05-22), pemilik perorangan;
  - "LIMO + Logo", kelas 35, Didaftar, J002018065742, sampai 2028-12-14, pemilik perorangan;
  - "Limo Green", kelas 4, 11, 12, 35, 37, 42, Didaftar, Vingroup Joint Stock Company (VN), M0020251855237, sampai
    2035-03-12.
- [FAKTA] Saran Fonetik PDKI (18.10.58 UTC): TANLIM, TANIMOS, TASLIM, TANITO, YANIMO, TANAMO, ANTIMO, TAN'IM, Tanamo,
  MANLIO. Yang aktif di kelas inti: beberapa merek TASLIM di kelas 35 (nama orang dan kantor hukum; pemilik perorangan)
  dan "Tanamo", kelas 35, (TM) Pemeriksaan Substantif, PT Panen Jaya Sentosa, JID2026003013 (diajukan 2026-01-12).
  TANLIM (satu huruf) ada di kelas 29, di luar kelas inti.
- [FAKTA] WIPO GBD: Embedded "No results found!" (18.51.08 UTC). Phonetic 359 hasil (19.05.44 UTC), Fuzzy 768 hasil
  (19.06.04 UTC). Rekaman terdekat:
  - satu huruf: Tann-Limo (Jerman, kelas 32, Expired 1992), TANLIM (Indonesia, kelas 29, IDM000819682), Tanlipo (Jepang,
    kelas 10), TANSIMO ROBOTIC (Australia, kelas 7, 9, 10, Registered 2025-09-24, 2589376);
  - dua huruf di kelas 9 atau 42: TONLIMA (AS, kelas 9, 6552594), TanLIVE (EUTM 018924230, TENCENT HOLDINGS LIMITED,
    kelas 35 dan 42), Tantino (Jerman, kelas 9, Hitex Development Tools GmbH), TANGIAMO (AS, kelas 9, 28, 42, Tangiamo
    Touch Technology AB), TANTINO (AS, kelas 9).
- [FAKTA] USPTO (18.24.39 UTC): kueri UI persis 0. Wildcard `tanlim*` 0; `*anlimo` 3 (VANLIMO dan BARON VANLIMO, jasa
  limusin kelas 39, mati; VOIANLIMO kelas 25, hidup); `tan?imo` 1, yaitu TANSIMO ROBOTIC, hidup, kelas 7, 9, 10,
  Tansimo Robotic Manufacturing Inc. (Taiwan), sn 99444485, reg. 8398541, terdaftar 2026-08-18
  ([TSDR 99444485](https://tsdr.uspto.gov/#caseNumber=99444485&caseSearchType=US_APPLICATION&caseType=DEFAULT&searchType=statusSearch)).
  Kueri fuzzy (298 hasil) memberi merek hidup berjarak dua huruf di kelas inti seperti TANLINK (Hangzhou Tanlink
  Technology Co., Ltd., kelas 9), TANLIFE (kelas 9), dan TANLIVE (Tencent Holdings Limited, kelas 35 dan 42).
- [FAKTA] TMview (18.34.52 sampai 18.34.59 UTC): Contains 0, Is 0, Word 0. Fuzzy 15: TANSIMO ROBOTIC di GB
  (UK00004267638, Registered), AU (2589376, Filed), US (99444485, Registered), dan EM (019250216, Registered), semua
  kelas 7, 9, 10; 坦力格 TANLIGO (CN, kelas 9)
  ([TMview Fuzzy](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=F&basicSearch=tanlimo)).
- [FAKTA] Rincian EUTM 019250216 (18.38.34 UTC): diajukan 2025-09-22, Registered 2026-02-16, berlaku sampai 2035-09-22;
  barang kelas 9 "Humanoid robots with artificial intelligence for assisting humans in concierge duties and tasks; ...
  trade fairs; ... household chores ..." ([eSearch EUIPO](https://euipo.europa.eu/eSearch/#details/trademarks/019250216)).

**Domain (RDAP, 16.47.36 sampai 16.47.38 UTC)**
- [FAKTA] Tanpa catatan: [tanlimo.com](https://rdap.verisign.com/com/v1/domain/tanlimo.com),
  [tanlimo.app](https://pubapi.registry.google/rdap/domain/tanlimo.app), [tanlimo.id](https://rdap.pandi.id/rdap/domain/tanlimo.id),
  [tanlimo.co.id](https://rdap.pandi.id/rdap/domain/tanlimo.co.id),
  [gettanlimo.com](https://rdap.verisign.com/com/v1/domain/gettanlimo.com),
  [tanlimoapp.com](https://rdap.verisign.com/com/v1/domain/tanlimoapp.com).

**Toko aplikasi, perusahaan, produk, kripto**
- [FAKTA] App Store "tanlimo" (16.53.22 sampai 16.55.09 UTC): US 12, GB 11, AU 11, tujuh etalase lain 0; tidak ada yang
  memuat nama. Kueri "tan limo" hanya memunculkan "TALIXO - Taxi & Limo Booking" (Public In Motion GmbH, Travel; AU dan
  GB) di saringan jarak dua huruf, dan "tanlima" tanpa kecocokan
  ([contoh US](https://itunes.apple.com/search?term=tanlimo&country=us&entity=software&limit=50)). Google Play US dan ID:
  0 aplikasi (19.12.54 dan 19.13.04 UTC).
- [FAKTA] GitHub: 0 repositori, 1 akun pengguna, tanpa organisasi; Companies House 0; Product Hunt "No products found for
  "tanlimo""; CoinGecko, Blockscout, DexScreener 0 (18.53.37 sampai 18.57.04 UTC).

**Web**
- [FAKTA] Ecosia (19.09.48 dan 19.11.13 UTC): hanya akun pribadi (Pinterest, Instagram), nama orang "Tan Limo" di
  Facebook, dan nama pengguna di forum keamanan Tiongkok tahun 2008; tidak ada perusahaan atau produk
  ([Ecosia](https://www.ecosia.org/search?q=%22tanlimo%22)). Google menampilkan captcha untuk kueri ini (19.07.22 UTC).
  Putaran sebelumnya hanya menemukan satu nama pengguna GitHub pribadi.

**Makna, Tier A**
- [FAKTA] Tanpa entri "tanlimo": Wiktionary (404), cermin KBBI, PRPM (0 definisi), KBJI ("Kata tanlimo tidak ada dalam
  KBJI", 18.50.16 UTC), kamussunda.net ("Belum ada terjemahan"), BASAbali (tidak ada halaman).
- [FAKTA] "limo": Jawa "limo jeruk" ([KBJI](https://kbji.kemendikdasmen.go.id/kata/limo)); Bali "musk lime; Citrus
  microcarpa" ([BASAbali](https://dictionary.basabali.org/Limo)); Inggris "Clipping of limousine"; Belanda singkatan
  limousine dan limonade; Jerman "Limo" = singkatan Limonade atau Limousine
  ([limo](https://en.wiktionary.org/wiki/limo); [Limo](https://en.wiktionary.org/wiki/Limo)).
- [FAKTA] "Tan": "A Chinese surname from Hokkien"; Indonesia "a Chinese Indonesian surname from Hokkien"
  ([Tan](https://en.wiktionary.org/wiki/Tan), 19.28.12 UTC). Mandarin 贪利 = "to seek wealth; to covet riches"
  ([貪利](https://en.wiktionary.org/wiki/貪利)); 探路 = "to explore the way" ([探路](https://en.wiktionary.org/wiki/探路)).
- [FAKTA] Urban Dictionary "limo": selain limusin, ada entri akronim "Liberal Moron" bernada menghina
  ([UD limo](https://www.urbandictionary.com/define.php?term=limo), 19.01.33 UTC); "tanlimo" dan "tan limo": tanpa entri.

**Makna, Tier B (kamus primer)**
- [FAKTA] Spanyol "limo": "Del lat. limus. 1. m. Lodo, cieno" ([RAE](https://dle.rae.es/limo), 19.15.13 UTC).
- [FAKTA] Italia "limo": "1. letter. Fango, mota, acqua fangosa in genere" ([Treccani](https://www.treccani.it/vocabolario/limo/),
  19.15.53 UTC).
- [FAKTA] Portugis "limo": "Vegetação composta de algas, em lugares húmidos ou de águas paradas" dan "Mistura de água,
  argila e substâncias orgânicas. = BARRO" ([Priberam](https://dicionario.priberam.org/limo), 19.15.56 UTC).
- [FAKTA] Galisia "limo" = "mucus, especially the mucus of a cow in heat" dan "slime"; Turki "tan" = fajar; Vietnam "tan"
  = meleleh, larut ([limo](https://en.wiktionary.org/wiki/limo); [tan](https://en.wiktionary.org/wiki/tan)).

**Aksara (K9)**
- [FAKTA] Bahasa Korea: "both ⫽nl⫽ and ⫽ln⫽ are pronounced [lː]" ([Korean phonology](https://en.wikipedia.org/wiki/Korean_phonology)).
- [INFERENSI] Hangul 탄리모 akan dibaca [탈리모] ("tallimo"), sehingga ejaan dan ucapan berbeda. Katakana タンリモ
  (ta-n-ri-mo, 4 mora). Pinyin "tan li mo" bisa terdengar sebagai 贪利 (serakah harta); sebaliknya 探 (menjelajah) bisa
  dipakai bila kelak butuh nama aksara Han.

**Handle (K7, 19.00.36 UTC)**
- [FAKTA] TikTok kode 10221, sama dengan kontrol fiktif (tidak ada akun publik aktif); Instagram dipakai; YouTube 404; X
  dipakai (akun pribadi). @tanlimoapp tidak ditemukan di keempat platform (19.02.33 UTC).

### Inferences
- [INFERENSI] **"limo" di Spanyol, Italia, dan Portugis.** Maknanya lumpur atau endapan, bukan kata kasar, jadi tidak
  memicu G1. Untuk produk luar ruang, citra "lumpur" dan "limusin mewah" sama-sama tidak membantu; K2 = 1.
- [INFERENSI] **TANSIMO ROBOTIC.** Unsur pembedanya berjarak satu huruf dan terdaftar di kelas 9 di AS dan UE, tetapi
  barangnya robot humanoid, bukan perangkat lunak perencanaan perjalanan. Karena itu K6 = 1, bukan G3.
- [INFERENSI] **Salah pisah kata.** Pendengar berbahasa Inggris bisa menulis "Tan Limo" dua kata; pembaca Indonesia,
  Malaysia, Singapura, dan Filipina bisa mengira nama orang bermarga Tan. Ini diuji di K1b, belum dihitung di K1a.

| Kriteria | v2 | v3 (bobot) | Dasar |
|---|---|---|---|
| K1 / K1a | 2 | 2 (×2 = 4) | tan-li-mo; rangkaian n.l melintasi batas suku kata, tidak dihitung sebagai gugus |
| K1b | pending | pending | Risiko ditulis "Tan Limo" (dua kata) |
| K2 | 1 | 1 (×2 = 2) | "limo" = lumpur (ES, IT, PT), lendir (Galisia), limusin (EN, DE, NL); akronim UD "Liberal Moron" |
| K3 | 2 | 2 (×1 = 2) | Tidak ada kecepatan, kompetisi, penaklukan, puncak, adrenalin, atau klaim aman |
| K4 / K4a | 1 | 1 (×1 = 1) | Kata ciptaan tanpa cerita bawaan; "limo" menarik ke limusin |
| K5 | 2 | 2 (×1 = 2) | Enam domain tanpa catatan RDAP |
| K6 | 1 | 1 (×3 = 3) | TANSIMO ROBOTIC (US, EU, GB, AU; kelas 9) berjarak satu huruf; "LIMO" kelas 9 sedang diperiksa di Indonesia |
| K8 | (tidak ada) | 2 (×2 = 4) | Fanciful untuk perencanaan jalur; catatan: di kelas 39 unsur "limo" dekat dengan jasa limusin |
| K9 | (tidak ada) | 1 (×1 = 1) | Hangul dibaca [탈리모]; 贪利 dalam pinyin; terbaca nama orang bermarga Tan |
| **Total** | **9 / 12** | **19 / 26** | Tidak ada gerbang terpicu |

- [INFERENSI] **Vonis: lolos dengan catatan, peringkat ketiga.** Konfliknya ringan, tetapi makna "limo" di tiga bahasa
  Roman dan citra nama orang menurunkan kecocokan untuk merek internasional.

### Gaps
- Penutur Spanyol, Italia, dan Portugis belum ditanya apakah "tanlimo" memunculkan "limo" (lumpur) atau "limusin".
- Barang kelas 9 TANSIMO ROBOTIC di AS belum dibaca lengkap; penilaian kemiripan resmi butuh Konsultan KI.
- Google tidak bisa dipakai untuk nama ini (captcha); Ecosia tidak disetel ke pasar Indonesia.

---

## 5. TOLAPI

### Takeaway
TOLAPI **gugur G3 (provisional)**. Tidak ada merek identik, tetapi merek UE "Tohapi" (EUTM 018349074) aktif dan hanya
berbeda satu huruf; barang dan jasanya mencakup "applications logicielles téléchargeables", aplikasi seluler, dan
"planification de voyages" di kelas 9 dan 39. Itu wilayah yang sama dengan platform perencanaan perjalanan gunung.
Di sekitarnya ada pula TONAPI (UE dan Inggris, kelas 9 dan 42), Tokapi (UE, kelas 42), dan TOLAPS (Australia, kelas 9 dan
39). Keempat handle utama sudah dipakai. Skor v2 8 dari 12 dan v3 14 dari 26, keduanya dengan G3.

### Cited Findings
**Merek**
- [FAKTA] PDKI mode Normal "tolapi": 0 data; UI "Data tidak ditemukan untuk kata kunci "tolapi"" (18.14.52 UTC); kueri
  diapit kontrol (18.15.49 UTC) ([PDKI](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=tolapi&page=1)).
  "tolapy" dan "tilapi": 0 (18.17.46 dan 18.17.49 UTC, diapit kontrol).
- [FAKTA] Saran Fonetik PDKI (18.11.14 UTC): OTOLAPI, Tolabi, TULAPI, OLAPIN, TILAPIA, TILAPIN, KOPI TOLAKI, MOLAGI,
  JOLALI, TOBAMI. Yang aktif di kelas inti hanya "ATI ASOSIASI TILAPIA INDONESIA" (kelas 41, Didaftar, AKUAKULTUR
  TILAPIA INDONESIA, JID2025100238, sampai 2035-09-26). Unsur "lapi": "LAPI" dan "LOGO LAPI" kelas 35 dan 42, Didaftar,
  PT. LAPI LABORATORIES (JID2026011156 dan lainnya, diajukan 2026-02-03).
- [FAKTA] WIPO GBD: Embedded "No results found!" (18.51.47 UTC). Phonetic 12.306 hasil (19.06.24 UTC), Fuzzy 1.601 hasil
  (19.06.45 UTC). Rekaman berjarak satu huruf yang aktif di kelas inti:
  - Tohapi / TOHAPI: EUTM 017940595 (Registered 2019-01-15, VILLAGE CENTER, Prancis), EUTM 018349074 (Registered, termasuk
    kelas 9), IR 1261926, dan Prancis 4115174; TOHAPI Inggris UK00003638963 (VACANCESELECT GROUP SAS) sudah Ended;
  - TONAPI, EUTM 019254083, Registered 2026-02-04, kelas 9, 35, 36, 38, 42, TON APPS INCORPORATED (UEA); TonAPI (UEA,
    kelas 42);
  - DOLAPI (Brasil, kelas 35, WMS SUPERMERCADOS DO BRASIL LTDA.).
  Yang berjarak satu huruf tetapi mati: TELAPI (AS, kelas 42), TOLAPA (AS, kelas 9), SOLAPI (Korea, kelas 9), トラピス
  TOLAPIS (Jepang). TULAPI (Indonesia, kelas 16 dan 28, IDM001323103) aktif tetapi di luar kelas inti.
- [FAKTA] USPTO (18.24.52 UTC): kueri UI persis 0. Wildcard `tolap*` 2 (TOLAPHEN kelas 5, mati; TOLAPA kelas 9, Tolapa,
  Inc., dibatalkan 2009); `*olapi` 2 (OLAPI kelas 38 dan 42, OLAPI, L.L.C., abandoned 2002; ZOLAPI kelas 20, hidup);
  `tol?pi` 0. Kueri fuzzy (521 hasil): merek hidup berjarak satu huruf hanya TOLANI (18, 25) dan ZOLAPI (20); yang
  berjarak dua huruf di kelas 42 antara lain POLYAPI (Poly API Corporation) dan KOLAPP (Kolapp LLC, diajukan 2026).
- [FAKTA] TMview (18.35.01 sampai 18.35.09 UTC): Contains 7, antara lain CartolaPix (BR, kelas 42 Ended dan 35 Filed),
  トラピス＼ＴＯＬＡＰＩＳ (JP, Expired), FOTOLAPIDE MAXIFOTOCERAMICA (IT, kelas 16, ITER-NET S.R.L.); Is 0; Word 0.
  Fuzzy 414; merek aktif berjarak satu huruf di kelas inti: TOHAPI dan CAMPINGS TOHAPI (GB, EM, FR, WO; VILLAGE CENTER dan
  VACANCESELECT GROUP SAS; kelas 12, 35 sampai 39, 41, 43, 44, sebagian dengan kelas 9), TOLAPS (AU 711775, kelas 9 dan
  39, Klinge Holdings Pty Ltd), TONAPI (GB UK00004266887 dan EM 019254083), Tokapi (EM 018580498, kelas 35, 36, 42),
  TOLANI (EM dan GB, kelas 35), SOLAPI dan TILAPI (KR, kelas 35)
  ([TMview Fuzzy](https://www.tmdn.org/tmview/#/tmview/results?page=1&pageSize=30&criteria=F&basicSearch=tolapi)).
- [FAKTA] Rincian EUTM 018349074 "Tohapi" (18.38.25 UTC): Registered 2021-05-03, diajukan 2020-12-04, berlaku sampai
  2030-12-04, kelas 9, 12, 35, 37, 39, 41, 43. Kelas 9: "Bases de données; logiciels; applications logicielles
  téléchargeables; logiciels et applications pour dispositifs mobiles; ...". Kelas 39: "Transport; services de voyages;
  ... services d'organisation et de réservation de voyages; planification de voyages; ...". Kelas 41: "... services de
  camps de vacances ..." ([eSearch EUIPO](https://euipo.europa.eu/eSearch/#details/trademarks/018349074)).
- [FAKTA] Rincian EUTM 019254083 "TONAPI" (18.38.28 UTC): Registered 2026-02-05, kelas 9 "Non-downloadable software for
  blockchain technology; Platform software; Computer software; ..." dan kelas 42 "Data authentication via blockchain; ...
  Programming of software for Internet platforms; Hosting of platforms on the Internet; ..."
  ([eSearch EUIPO](https://euipo.europa.eu/eSearch/#details/trademarks/019254083)). EUTM 018580498 "Tokapi"
  (18.38.38 UTC): Registered 2022-02-14, kelas 35, 36, 42 ("IT-Dienstleistungen").

**Domain (RDAP, 16.47.36 sampai 16.47.38 UTC)**
- [FAKTA] Tanpa catatan: [tolapi.com](https://rdap.verisign.com/com/v1/domain/tolapi.com),
  [tolapi.app](https://pubapi.registry.google/rdap/domain/tolapi.app), [tolapi.id](https://rdap.pandi.id/rdap/domain/tolapi.id),
  [tolapi.co.id](https://rdap.pandi.id/rdap/domain/tolapi.co.id),
  [gettolapi.com](https://rdap.verisign.com/com/v1/domain/gettolapi.com),
  [tolapiapp.com](https://rdap.verisign.com/com/v1/domain/tolapiapp.com).

**Toko aplikasi, perusahaan, produk, kripto**
- [FAKTA] App Store "tolapi" (16.55.13 sampai 16.57.01 UTC): US 17, GB 18, AU 18, tujuh etalase lain 0; tidak ada yang
  memuat nama. Kueri "tilapi" memunculkan "Tilapia Welfare App" (FAI FARMS LIMITED, Productivity; GB dan US), yang
  menunjukkan gema tilapia di toko aplikasi
  ([contoh US](https://itunes.apple.com/search?term=tilapi&country=us&entity=software&limit=50)). Google Play US 1 dan ID
  8 aplikasi, tidak ada yang memuat nama (19.13.13 dan 19.13.23 UTC).
- [FAKTA] GitHub: 14 repositori milik akun pribadi (beberapa nama repositori memuat nama orang bermarga Tolapi), 6 akun
  pengguna, tanpa organisasi ([GitHub API](https://api.github.com/search/repositories?q=tolapi+in:name)); Companies House
  0; Product Hunt tidak memuat produk bernama Tolapi; CoinGecko, Blockscout, DexScreener 0 (18.53.47 sampai 18.57.06 UTC).

**Web**
- [FAKTA] Ecosia (19.10.04 UTC; [Ecosia](https://www.ecosia.org/search?q=%22tolapi%22)):
  - "20+ professionals named "Tolapi"" di [LinkedIn](https://www.linkedin.com/pub/dir/+/Tolapi) (nama keluarga);
  - lokalitas Tolapi di Bolivia ([freemeteo](https://freemeteo.bz/weather/tolapi/maps/precipitation/?gid=3902730&language=english&country=bolivia))
    dan di Srikakulam, India ([housing.com](https://housing.com/in/buy/srikakulam/tolapi-gid/));
  - cuplikan data sekolah yang beralamat "Jl.Tolapi Dusun Balabatu", Desa Sampa, Kec. Bajo (halaman asalnya tidak dibuka,
    UNVERIFIED);
  - satu terjemahan Al-Qur'an berbahasa Lingala yang memuat kata "Tolapi" (maknanya UNVERIFIED).
  Google menampilkan captcha untuk kueri ini (19.07.56 UTC).

**Makna, Tier A**
- [FAKTA] Tanpa entri "tolapi": Wiktionary (404), cermin KBBI, PRPM (0 definisi), KBJI (tidak ada lema persis; pencarian
  bunyi memberi talab, talib, talub, tlobeh, anapi), kamussunda.net ("Belum ada terjemahan"), BASAbali (tidak ada
  halaman).
- [FAKTA] "api": Indonesia "fire"; Jawa "steam"; Bali "fire" ([api](https://en.wiktionary.org/wiki/api);
  [BASAbali](https://dictionary.basabali.org/Api)); Sunda "api" diterjemahkan "pura-pura"
  ([kamussunda.net](https://www.kamussunda.net/kamus?teks=api&bahasa=bahasa)). Cermin KBBI "tolap": "ber·to·lap v
  bersulam; bertekat" ([kbbi.web.id/tolap](https://kbbi.web.id/tolap)). Inggris "tilapia" = ikan konsumsi, nila
  ([tilapia](https://en.wiktionary.org/wiki/tilapia)); Inggris "tola" = satuan massa di India
  ([tola](https://en.wiktionary.org/wiki/tola)).

**Makna, Tier B**
- [FAKTA] Portugis "tola" (tolo): "Que ou quem revela falta de inteligência" dan "simplório ou ingénuo"
  ([Priberam](https://dicionario.priberam.org/tola), 19.16.00 UTC).
- [FAKTA] Tagalog "api" = "oppressed; maltreated; aggrieved; taken advantage of" ([api](https://en.wiktionary.org/wiki/api),
  18.47.11 UTC); Tagalog "lapi" = imbuhan ([lapi](https://en.wiktionary.org/wiki/lapi)). Urban Dictionary menyebut
  "lapi" sinonim "amaw" (Bisaya) dan "gago" (Filipino) ([UD lapi](https://www.urbandictionary.com/define.php?term=lapi));
  sumbernya buatan pengguna, jadi UNVERIFIED.
- [FAKTA] Finlandia "tola" = "a track or trail, especially a skiing track" ([tola](https://en.wiktionary.org/wiki/tola),
  18.46.55 UTC).

**Aksara (K9)**
- [FAKTA] Mandarin 拖拉 = "to delay; to put off; to procrastinate ..." dan "slow; sluggish"
  ([拖拉](https://en.wiktionary.org/wiki/拖拉)); 拉皮 = "to have a facelift procedure" dan hidangan mi dingin
  ([拉皮](https://en.wiktionary.org/wiki/拉皮)).
- [INFERENSI] Katakana トラピ (to-ra-pi, 3 mora; paling pendek di kelompok ini); hangul 톨라피. Transliterasi Han yang
  lazim (托拉皮) membawa bunyi "tuo la" dan "la pi" yang dekat dengan 拖拉 (lamban) dan 拉皮 (operasi kencang wajah).

**Handle (K7, 19.01.00 UTC)**
- [FAKTA] TikTok dipakai (5 pengikut, 0 video); Instagram dipakai; YouTube 200 (dipakai); X dipakai (akun pribadi).
  @tolapiapp tidak ditemukan di keempat platform (19.02.55 UTC).

### Inferences
- [INFERENSI] **Mengapa G3.** "TOLAPI" dan "TOHAPI" sama panjang, sama awal ("TO") dan akhir ("API"), dan hanya berbeda
  di huruf ketiga. Dalam bahasa Prancis h tidak dibaca, sehingga "Tohapi" terdengar "to-a-pi". Barang dan jasanya
  tumpang tindih langsung: aplikasi seluler yang bisa diunduh (kelas 9) dan perencanaan perjalanan (kelas 39). Menurut
  aturan G3 di bagian 0, ini merek aktif yang sangat mirip di kelas 9 di UE. Statusnya provisional sampai ada pendapat
  Konsultan KI, karena penilaian kemiripan resmi mempertimbangkan kesan keseluruhan dan pasar.
- [INFERENSI] **Tilapia.** Mesin fonetik PDKI menaruh TILAPIA dan TILAPIN di daftar sarannya, dan toko aplikasi memunculkan
  "Tilapia Welfare App". Gema ikan nila nyata, tetapi tidak menggugurkan nama; itu masuk K2 = 1.
- [INFERENSI] **K8 = 1.** Nama memuat kata Finlandia "tola" (jalur, lintasan ski), bahasa resmi UE, sehingga sugestif
  untuk jasa jalur.

| Kriteria | v2 | v3 (bobot) | Dasar |
|---|---|---|---|
| K1 / K1a | 2 | 2 (×2 = 4) | to-la-pi; tanpa huruf labil, J, W, E, gugus, atau koda |
| K1b | pending | pending | Risiko ditulis "tolapy" atau dikira "tilapia" |
| K2 | 1 | 1 (×2 = 2) | Portugis "tola" = bodoh (perempuan); Tagalog "api" = tertindas; Sunda "api" = pura-pura; gema tilapia |
| K3 | 2 | 2 (×1 = 2) | Tidak ada kecepatan, kompetisi, penaklukan, puncak, adrenalin, atau klaim aman |
| K4 / K4a | 1 | 1 (×1 = 1) | Kata ciptaan; cerita "tola" (Finlandia) tidak dikenal di pasar utama |
| K5 | 2 | 2 (×1 = 2) | Enam domain tanpa catatan RDAP |
| K6 | **0** | **0** (×3 = 0) | Tohapi (EUTM 018349074, kelas 9 aplikasi dan kelas 39 perencanaan perjalanan) berjarak satu huruf; TONAPI (UE, 9 dan 42); TOLAPS (AU, 9 dan 39); Tokapi (UE, 42) |
| K8 | (tidak ada) | 1 (×2 = 2) | Memuat "tola" (Finlandia: jalur) |
| K9 | (tidak ada) | 1 (×1 = 1) | 拖拉 (lamban) dan 拉皮 (operasi wajah) pada transliterasi Han |
| **Total** | **8 / 12, gugur G3** | **14 / 26, gugur G3** | G3 provisional |

- [INFERENSI] **Vonis: gugur (G3 provisional).** Bila Konsultan KI menilai risiko Tohapi rendah, skor tanpa gerbang tetap
  paling rendah di kelompok ini, dan keempat handle utama sudah dipakai.

### Gaps
- Pendapat Konsultan KI tentang kemiripan TOLAPI dan TOHAPI belum ada; status G3 masih provisional.
- Arti "tolapi" dalam bahasa Lingala dan makna "lapi" dalam slang Bisaya belum diverifikasi di kamus primer.
- Google tidak bisa dipakai untuk nama ini (captcha); Ecosia tidak disetel ke pasar Indonesia.

---

## 6. Skor v2 dan v3, gerbang, dan peringkat kelompok 2

### Takeaway
Peringkat v3: **TALAMPI 21**, **LINTAMO 20**, TANLIMO 19, PORANTI 16, dan TOLAPI 14 yang gugur G3 (provisional).
Peringkat v2 sama urutannya: TALAMPI 10, LINTAMO 9, TANLIMO 9, PORANTI 8, TOLAPI 8 (gugur G3). Kelima nama turun dari skor
provisional 11 karena makna baru (K2), konflik dari basis data merek (K6), dan nuansa kompetisi (K3, PORANTI). Selisih
TALAMPI dan LINTAMO hanya satu poin dan berbalik pada beberapa asumsi yang wajar, jadi urutan keduanya harus diputuskan
oleh uji dengar-eja (K1b) dan pengecekan penutur asli, bukan oleh skor meja ini.

### Cited Findings
- [FAKTA] Skor provisional putaran sebelumnya untuk kelima nama adalah 11 dari 12, dengan K2 = 2 dan K6 = 2 (pada saat
  itu basis data merek belum dicek) (`kandidat-dan-saringan.md` bagian 5.1).
- [FAKTA] Dasar setiap skor ada di bagian 1 sampai 5 berkas ini, dengan sumber dan jam UTC; aturan penerapan rubrik ada di
  bagian 0.

### Inferences
**Tabel skor (v2 maksimum 12; v3 berbobot, maksimum 26)**

| Nama | K1 v2 | K1a | K2 | K3 | K4 / K4a | K5 | K6 | K8 | K9 | **v2** | **v3** | Gerbang | K1b |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| TALAMPI | 2 | 1 | 1 | 2 | 1 | 2 | 2 | 2 | 2 | **10** | **21** | tidak ada | pending |
| LINTAMO | 2 | 2 | 1 | 2 | 1 | 2 | 1 | 2 | 2 | **9** | **20** | tidak ada | pending |
| TANLIMO | 2 | 2 | 1 | 2 | 1 | 2 | 1 | 2 | 1 | **9** | **19** | tidak ada | pending |
| PORANTI | 2 | 2 | 1 | 1 | 1 | 2 | 1 | 1 | 1 | **8** | **16** | tidak ada | pending |
| TOLAPI | 2 | 2 | 1 | 2 | 1 | 2 | 0 | 1 | 1 | **8** | **14** | **G3 (provisional)** | pending |

Rumus v3: K6×3 + K8×2 + K2×2 + K1a×2 + K3 + K4a + K9 + K5. Contoh TALAMPI: 6 + 4 + 2 + 2 + 2 + 1 + 2 + 2 = 21.

**Gerbang**
- [INFERENSI] G1 (K2 = 0): tidak ada. Semua makna yang ditemukan tidak kasar, tidak vulgar, dan tidak sakral.
- [INFERENSI] G2 (v2, K4 = 0) dan G5 (v3, K8 = 0): tidak ada. Kelimanya kata ciptaan; yang paling dekat ke deskriptif
  adalah PORANTI (satu huruf dari "peranti").
- [INFERENSI] G3: hanya TOLAPI, karena EUTM "Tohapi" (kelas 9 aplikasi dan kelas 39 perencanaan perjalanan).
- [INFERENSI] G4: tidak diterapkan secara provisional, karena tidak ada nama dengan K1a = 0 pada aturan utama.

**Peringkat dan pemecah seri**
1. **TALAMPI** (v3 21, v2 10). Konflik paling bersih; kelemahan: makna Tagalog "menampar" (jarang dipakai) dan koda m.
2. **LINTAMO** (v3 20, v2 9). Daya pembeda dan aksara paling baik; kelemahan: "linta mo" (Tagalog) dan gema "lintah",
   serta LINTAO di kelas 9 AS.
3. **TANLIMO** (v3 19, v2 9). Seri v2 dengan LINTAMO tetap seri setelah pemecah seri v2 (keduanya tanpa merek identik,
   keduanya punya merek mirip di kelas 9, K1 dan K5 sama), lalu dipecah oleh v3.
4. **PORANTI** (v3 16, v2 8). Lolos gerbang, tetapi tiga kelemahannya ada di Indonesia.
5. **TOLAPI** (v3 14, v2 8). Gugur G3 provisional.

**Uji kepekaan (v3)**

| Skenario | Perubahan | LINTAMO | PORANTI | TALAMPI | TANLIMO | Urutan teratas |
|---|---|---|---|---|---|---|
| Dasar | aturan bagian 0 | 20 | 16 | 21 | 19 | TALAMPI, LINTAMO, TANLIMO |
| S1 | K1a ketat: setiap rangkaian konsonan dihitung gugus | 18 | 14 | 19, tetapi K1a = 0 sehingga G4 provisional | 17 | LINTAMO, TANLIMO |
| S2 | Gema "lintas" dianggap sugestif (K8 LINTAMO = 1) | 18 | 16 | 21 | 19 | TALAMPI, TANLIMO, LINTAMO |
| S3 | CALAMP dan TALAMEX (jarak dua huruf) dihitung (K6 TALAMPI = 1) | 20 | 16 | 18 | 19 | LINTAMO, TANLIMO, TALAMPI |
| S4 | Penutur Tagalog dan Indonesia tidak menangkap "lintah" (K2 LINTAMO = 2) | 22 | 16 | 21 | 19 | LINTAMO, TALAMPI |
| S5 | Penutur Tagalog menilai "talampí" tidak dikenal (K2 TALAMPI = 2) | 20 | 16 | 23 | 19 | TALAMPI, LINTAMO |

- [INFERENSI] TALAMPI dan LINTAMO sama-sama berada di dua besar pada skenario dasar, S4, dan S5. Pada S1, S2, dan S3 salah
  satunya digeser TANLIMO ke posisi ketiga (S1 hanya berlaku bila rubrik sengaja dibaca paling ketat). Jadi TANLIMO adalah
  cadangan yang nyata, sedangkan PORANTI dan TOLAPI tetap di bawah di semua skenario.
- [INFERENSI] Untuk merek dasar Madrid di DJKI, semua nama kecuali PORANTI tampak aman dari sisi daya pembeda, karena
  tidak ada yang bermakna deskriptif dalam bahasa Indonesia atau Melayu. PORANTI berjarak satu huruf dari "peranti".
- [REKOMENDASI] Bawa **TALAMPI** dan **LINTAMO** ke uji dengar-eja dan pengecekan penutur asli (bagian 7), dengan
  **TANLIMO** sebagai cadangan. PORANTI tidak disarankan untuk dilanjutkan. TOLAPI hanya dibuka lagi bila Konsultan KI
  menilai risiko Tohapi rendah, dan itu pun di bawah tiga nama lainnya.

### Gaps
- Skor K1a, K2, K8, dan K9 adalah inferensi meja; K1b belum ada sehingga G4 belum bisa diterapkan dengan sah.
- K6 memakai jarak ejaan sebagai pendekatan; penilaian "persamaan pada pokoknya" (Penjelasan Pasal 21 ayat (1) UU
  20/2016, termasuk "persamaan bunyi ucapan", [Hukumonline](https://www.hukumonline.com/klinik/a/arti-persamaan-pada-pokoknya-dalam-uu-merek-dan-indikasi-geografis-lt560aad4d30945/))
  adalah wewenang pemeriksa dan pendapat Konsultan KI.
- Bobot v3 adalah usulan riset sebelumnya yang belum diputuskan pemilik (`lanskap-dan-strategi.md` bagian 6).

---

## 7. Checklist pending dan skrip uji dengar-eja

### Takeaway
Sebelum nama dipilih, masih ada tiga hal yang menentukan: uji dengar-eja K1b, pengecekan penutur asli untuk makna yang
ditemukan (terutama Tagalog dan Indonesia), dan pendapat Konsultan KI (termasuk TOLAPI melawan Tohapi). Skrip uji di bawah
mengikuti protokol `lanskap-dan-strategi.md` bagian 2: tiga kelompok, masing-masing 8 orang, dengan ambang lulus yang
ditetapkan sebelum uji.

### Cited Findings
- [FAKTA] Protokol dan ambang berasal dari `lanskap-dan-strategi.md` bagian 2.4: kelompok A (Indonesia, minimal tiga latar
  daerah), B (penutur Inggris), C (Prancis, Belanda, Jerman, atau Mandarin; boleh 5 orang sebagai saringan); eja persis
  paling banyak 1 salah dan tanpa salah eja kembar, ucap ulang minimal 7 dari 8, ingatan tertunda minimal 6 dari 8.
- [FAKTA] Dasar ambang di berkas yang sama: dengan aturan paling banyak 1 salah dari 8, nama yang salah dieja 30% orang
  hanya lulus 25,5% kali, sedangkan nama dengan salah 10% lulus 81,3% kali; hasil 8/8 hanya memberi batas bawah 95%
  sekitar 63%, jadi uji ini formatif ([NN/g](https://www.nngroup.com/articles/5-test-users-qual-quant/)).
- [FAKTA] Rekaman suara peserta adalah data pribadi; kebijakannya diatur di `docs/KEBIJAKAN-DATA-PRIBADI.md` (canonical
  menurut `CLAUDE.md` §4).

### Inferences
**A. Checklist pending**

| No | Pekerjaan | Nama | Pelaksana | Bukti yang dihasilkan | Menentukan keputusan? |
|---|---|---|---|---|---|
| 1 | Uji dengar-eja K1b (skrip B di bawah) | TALAMPI, LINTAMO, TANLIMO; PORANTI opsional sebagai pembanding | pemilik atau tim riset pengguna | lembar jawaban dan rekaman per peserta | Ya: G4 dan pemecah seri keempat |
| 2 | Penutur Tagalog: kesan "Lintamo" ("linta mo") dan "Talampi" ("talampí", tampar) | LINTAMO, TALAMPI | pemilik | catatan wawancara bertanggal | Ya: skenario S4 dan S5 |
| 3 | Penutur Indonesia dan Melayu: gema "lintah" dan "talam dua muka" (lewat tugas (c) kelompok A) | LINTAMO, TALAMPI | pemilik | hitungan asosiasi spontan | Ya |
| 4 | Penutur Spanyol, Italia, Portugis: "limo" (lumpur) atau "limusin" | TANLIMO | pemilik | catatan wawancara | Bila TANLIMO dibawa |
| 5 | Penutur Mandarin, Kanton, atau Hokkien; Korea; Jepang: bentuk aksara dan homofon (他妈, 贪利, [탈리모], ボランティア) | semua yang dibawa | pemilik | catatan wawancara | Tidak, kecuali Tiongkok, Korea, atau Jepang masuk rencana |
| 6 | Penutur Madura dan Sunda (tidak ada kamus Madura daring; arti "talampi" di unggahan Sunda belum jelas) | semua yang dibawa | pemilik | catatan wawancara | Tidak |
| 7 | Konsultan KI: penelusuran PDKI menyeluruh dan pendapat tertulis; Pasal 21 (nama keluarga Lintamo), Pasal 20 (PORANTI dan "peranti"), TOLAPI melawan EUTM 018349074 | dua nama teratas dan cadangan | Konsultan KI terdaftar (daftar di [pdkki.dgip.go.id](https://pdkki.dgip.go.id/pages/index_)) | pendapat tertulis | Ya: G3 final |
| 8 | Register yang belum dicek langsung: IPOS Singapura, CNIPA dan nama aksara Han, J-PlatPat (katakana), KIPRIS (hangul), MyIPO langsung | dua nama teratas | Konsultan KI | hasil penelusuran bertanggal | Bila pasar itu masuk rencana |
| 9 | Sumber yang terblokir: Etherscan, OpenCorporates, AHU (perseroan Indonesia) | semua yang dibawa | pemilik, lewat peramban biasa | tangkapan layar bertanggal | Tidak |
| 10 | Pencarian web ulang dengan Google untuk TANLIMO dan TOLAPI, dan halaman kedua untuk nama lain | semua yang dibawa | peneliti | daftar hasil bertanggal | Tidak |
| 11 | Domain: cek harga dan status premium di registrar tepercaya hanya saat siap membeli, lalu segera daftar; RDAP diulang di hari yang sama | nama terpilih | pemilik | bukti pembelian | Tidak (sinyal RDAP sudah bersih) |
| 12 | Handle: nama persis sudah dipakai di sebagian besar platform; `{nama}app` tampak belum dipakai di keempat platform untuk kelima nama (UNVERIFIED) | nama terpilih | pemilik | akun yang dibuat pemilik | Tidak |
| 13 | Governance record keputusan nama (`PRD.md` §130 dan §146) | nama terpilih | pemilik | entri §146 | Ya, sebelum nama dipakai publik |

**B. Skrip uji dengar-eja (K1b), siap dijalankan pemilik**

*Persiapan*
1. **Nama yang diuji:** TALAMPI, LINTAMO, TANLIMO, ditambah satu nama kontrol global yang dikenal (misalnya "Strava").
   PORANTI boleh ditambahkan sebagai pembanding. Beri kode acak (N1, N2, ...) agar fasilitator tidak menyebut nama.
2. **Rekaman:** dua suara, satu penutur Indonesia dan satu penutur Inggris. Setiap nama diucapkan dalam kalimat netral,
   tanpa dieja dan tanpa tekanan khusus:
   - Indonesia: "Saya cek jalurnya di ___."
   - Inggris: "I checked the trail on ___."
   Simpan sebagai berkas terpisah per nama per suara, dengan kecepatan bicara yang sama.
3. **Peserta:** kelompok A 8 penutur Indonesia dari minimal tiga latar daerah (misalnya Jawa, Sunda, Sumatra atau Nusa
   Tenggara); kelompok B 8 penutur Inggris (Australia, Inggris, Singapura, atau Malaysia); kelompok C 8 penutur Prancis,
   Belanda, Jerman, atau Mandarin (5 orang bila hanya saringan). Jangan libatkan orang yang sudah mengenal kandidat nama,
   dan jangan beri tahu nama mana yang disukai pemilik.
4. **Persetujuan dan privasi:** minta persetujuan perekaman, pakai kode peserta (bukan nama), dan simpan rekaman sesuai
   `docs/KEBIJAKAN-DATA-PRIBADI.md`.
5. **Urutan:** acak urutan nama per peserta. Setiap nama diputar dua kali: kelompok A mendengar suara Indonesia lalu
   Inggris; kelompok B dan C mendengar suara Inggris lalu Indonesia.

*Naskah sesi (sekitar 15 menit)*
1. Pembuka: "Terima kasih sudah ikut. Saya akan memutar beberapa nama aplikasi. Tidak ada jawaban benar atau salah; yang
   diuji namanya, bukan Anda." (Inggris: "Thank you for joining. I will play a few app names. There are no right or
   wrong answers; we are testing the names, not you.")
2. Untuk setiap nama, setelah diputar dua kali:
   - (a) "Tolong tulis nama aplikasinya seperti yang Anda dengar." ("Please write the app name the way you heard it.")
     Pakai kertas atau formulir tanpa koreksi otomatis.
   - (b) "Tolong ucapkan nama itu." ("Please say the name.") Rekam jawabannya.
   - (c) "Nama ini mengingatkan Anda pada apa? Kata, benda, atau perasaan apa saja." ("What does this name remind you
     of? Any word, thing, or feeling.") Tulis jawabannya kata demi kata. Bila kosong, tanyakan sekali: "Apakah terdengar
     seperti kata dalam bahasa yang Anda kenal?"
3. Distraksi 10 menit: tugas yang tidak berkaitan (misalnya kuesioner singkat tentang kebiasaan mendaki), tanpa
   menampilkan nama apa pun.
4. (d) "Sebutkan nama-nama aplikasi yang tadi Anda dengar, sebanyak yang Anda ingat." ("Please write down all the app
   names you heard earlier, as many as you remember.")
5. Penutup: jelaskan tujuan uji, dan minta peserta tidak menyebarkan nama-nama itu.
6. (e) Setelah 24 sampai 48 jam, kirim pesan: "Nama aplikasi apa saja yang Anda ingat dari sesi kemarin? Tulis saja
   sebisanya, tanpa mencari." ("Which app names do you remember from yesterday's session? Please write what you can,
   without looking anything up.")

*Lembar data (satu baris per peserta per nama)*
kode peserta; kelompok; latar bahasa atau daerah; kode nama; posisi urutan; urutan suara; ejaan tertulis persis; eja
benar (Y/T); bentuk salah eja; ucap ulang dapat dikenali (Y/T); asosiasi (kata demi kata); bendera asosiasi; ingatan 10
menit (Y/T dan ejaan); ingatan 24 sampai 48 jam (Y/T dan ejaan); catatan.

*Bendera asosiasi yang dihitung pada tugas (c)*
- LINTAMO: lintah atau leech, lintas atau rute, line.
- TALAMPI: tampar atau slap, lampu atau lamp, talam atau nampan.
- TANLIMO: limo atau limusin, lumpur atau mud, nama orang (marga Tan).
- PORANTI: peranti atau alat, pekan olahraga, rant, melukai (penutur Slavia).
- Kontrol: catat juga asosiasi untuk nama kontrol sebagai pembanding.

*Salah eja yang perlu diawasi (HIPOTESIS, dari bagian 1 sampai 4)*
TALAMPI: talampy, talampee, talanpi, tolampi. LINTAMO: lentamo, lintammo. TANLIMO: "tan limo", tanleemo. PORANTI:
paranti, poranty, puranti.

*Aturan keputusan per kelompok (n = 8), ditetapkan sebelum uji*
- Eja: lulus bila paling banyak 1 salah **dan** tidak ada salah eja yang sama muncul dua kali.
- Ucap ulang: lulus bila minimal 7 dari 8 dapat dikenali.
- Ingatan tertunda 24 sampai 48 jam: minimal 6 dari 8 (HIPOTESIS; kalibrasi dengan nama kontrol).
- K1b "lulus" bila kelompok A dan B lulus ketiga ambang; kelompok C dipakai sebagai saringan. K1b "gagal" pada eja di
  kelompok A atau B berarti G4.
- Makna: bila minimal 3 dari 8 orang di satu kelompok menyebut bendera negatif secara spontan, K2 = 1 dikonfirmasi (atau
  0 bila maknanya kasar); bila 0 dari 8, K2 boleh dinaikkan ke 2 seperti skenario S4 dan S5 di bagian 6.
- Laporkan hasil sebagai hitungan (misalnya 7/8), bukan persentase, karena uji ini formatif.

### Gaps
- Ambang ingatan tertunda (6 dari 8) masih HIPOTESIS dan perlu dikalibrasi dengan nama kontrol.
- Skrip ini belum diuji coba (pilot); satu sesi pilot dengan 1 sampai 2 orang di luar sampel disarankan untuk mengecek
  durasi dan kejelasan instruksi.
