# Verifikasi Mendalam Kelompok 3: TUMPALO, PAMARGA, dan PAMARGI (pembanding)

> **Status:** RISET, bukan keputusan. Nama merek adalah arah produk; keputusan dan governance record-nya milik pemilik
> (R-021, `PRD.md` §130 dan §146). Berkas ini ditulis bertahap: bagian tiap nama ditambahkan begitu buktinya lengkap.
> **Label bukti (R-023):** [FAKTA] hasil cek atau kutipan sumber; [INFERENSI] simpulan dari fakta; [HIPOTESIS] dugaan
> yang belum diuji; [REKOMENDASI] saran; [ISU TERBUKA] belum terjawab. UNVERIFIED berarti tidak diverifikasi langsung.
> **Batas klaim:** "404" pada RDAP berarti registri tidak punya catatan domain itu. Ini sinyal, bukan bukti domain bisa
> didaftarkan. "0 hasil" di basis data merek berarti tidak ada yang tampil di basis data itu pada jam cek, bukan bukti
> merek bebas. Tidak ada kalimat di berkas ini yang menyatakan domain tersedia atau merek bebas.
> **Cek baca saja:** tidak ada domain didaftarkan, akun dibuat, pihak lain dihubungi, atau formulir yang membuat rekaman
> dikirim. Tidak ada pencarian di halaman registrar.
> **Jendela cek:** 23 September 2026, 16.48 sampai 19.15 UTC. Rubrik v2 dan v3 diambil dari `kandidat-dan-saringan.md`
> §3.1 dan `lanskap-dan-strategi.md` §6.
>
> **Hasil singkat.**
> - Tidak satu pun dari ketiga nama punya merek identik di PDKI, WIPO (Embedded), TMview (Is), atau USPTO (frasa persis),
>   tidak ada yang dipakai aplikasi di 10 storefront App Store, dan semua domain wajib tanpa catatan RDAP.
> - Peringkat v3: **TUMPALO 17 dari 26**, **PAMARGA 16**, **PAMARGI 14 dengan G4 sementara**. Peringkat v2 terbalik:
>   PAMARGA 10, PAMARGI 9, TUMPALO 8.
> - TUMPALO: kuat sebagai merek, tetapi "Tupalo" (EUTM dan UK, kelas 35/38/41) beda satu huruf; Jawa "tumpal" juga
>   berarti "sampah"; bagian "palo" bermakna vulgar dalam frasa Spanyol (RAE); makna motif "penolak bala" tidak boleh
>   dipakai dalam cerita merek.
> - PAMARGA: ternyata kata Bali tingkat andap ("procedure; course, action, way"); akar "MARGA" dipakai JASA MARGA di kelas
>   9, 39, 42 dan permohonan MARGA di AS kelas 42; nama badan usaha di Indonesia (air minum) dan Spanyol.
> - PAMARGI: "gi" dibaca lunak ("Margie") dan koda r memicu vokal sisipan, jadi K1a = 0; MARGIE (AS, kelas 9/42) dekat
>   bunyinya; arti "perjalanan" sama dengan kata kategori produk, rawan deskriptif di merek dasar Indonesia kelas 39.
> - Keputusan akhir bergantung pada uji dengar-eja (skrip di 5.5), uji asosiasi penutur asli, dan konsultan KI (5.4).

---

## 1. Metode, kontrol, dan cakupan cek

### Takeaway
Semua cek dijalankan pada 23 September 2026, 16.48 sampai 19.15 UTC, dengan kontrol positif dan fiktif di setiap sumber
yang memungkinkannya. Basis data merek yang diminta berhasil dibuka semua: PDKI (Normal dan Fonetik), WIPO Global Brand
Database (Embedded, Phonetic, Fuzzy), USPTO (setelah sempat menolak dengan 403), dan TMview. Yang tidak tercakup: kantor
Singapura dan Indonesia tidak ada di TMview; OpenCorporates dan Etherscan memblokir dengan captcha; pencarian web umum
hanya lewat Yahoo! JAPAN karena kuota WebSearch habis; kamus Madura tidak ditemukan.

### Cited Findings

#### 1.1 Cara cek per sumber
| Sumber | Cara | Kontrol | Jam UTC |
|---|---|---|---|
| RDAP | Endpoint dari bootstrap IANA ("publication" 2026-09-16T19:00:03Z, diambil 16.48); .com dan .net Verisign, .app Google Registry, .id dan .co.id PANDI | verisign.com, google.app, pandi.id, telkom.co.id = 200; `qzxvkontrol7h2k` = 404 di keempat TLD | 16.48 sampai 16.54; tiga .id diulang dengan curl 18.02 (PANDI memutus koneksi) |
| PDKI | Peramban headless terisolasi (Chrome, profil sementara, tanpa akun). UI untuk mode Normal dan tab FONETIK; aksi pencarian halaman itu sendiri untuk tetangga fonetik | "margi" = 4 data (sama dengan riset sebelumnya), di awal dan akhir tiap batch | 18.04 sampai 18.15 |
| WIPO GBD | Formulir halaman di peramban headless; ALTCHA (proof-of-work) diselesaikan peramban sendiri setelah metadata user-agent diset sebagai Chrome biasa; beberapa percobaan gagal dan diulang | "vertica", Match exact = 76 hasil (sama dengan riset sebelumnya) | 18.32 sampai 18.48 |
| TMview | API pencarian yang dipakai halaman (`search/results`, `search/filter/offices`), kriteria I (Is), C (Contains), F (Fuzzy) | "vertica" Is = 83 hasil | 18.29 sampai 18.31 |
| USPTO | API pencarian tmsearch.uspto.gov (kueri baca, filter merek hidup) | "vertica" frasa persis = 8 hidup, 21 mati | 403 pada 18.26 sampai 18.27; berhasil 18.52 sampai 18.56 |
| App Store | iTunes Search API, `entity=software`, `limit=50`, 10 storefront, jeda 3,3 detik | "komoot" ditemukan di 10 storefront | 18.03 sampai 18.06; kontrol 18.15 sampai 18.16 |
| Google Play | Halaman pencarian US/en dan ID/id (upaya terbaik) | "komoot" tertangkap | 18.17 |
| Kamus | Wiktionary REST dan raw (sempat 429, diulang dengan jeda), Urban Dictionary API, cermin KBBI, KBJI, BASAbali, PRPM, SundaDigi (peramban headless), RAE (peramban headless), Behind the Name, wisdomlib | Entri yang diketahui ada (mis. "tumpal" di KBBI cermin) | 18.37 sampai 19.10 |
| Perusahaan dan kripto | GitHub API, CoinGecko API, Companies House UK, Wikipedia API, Product Hunt (headless), OpenCorporates dan Etherscan (diblokir) | Tidak ada kontrol khusus | 18.42 sampai 18.52 |
| Web | Yahoo! JAPAN lewat skrip yang membaca data halaman (`pageData.algos`); WebSearch habis (200 dari 200), Brave memblokir headless, Bing lewat WebFetch memberi hasil tak relevan | Tidak ada | 18.40 sampai 18.50; setelah itu 429 |
| Handle | Peramban headless tanpa login: TikTok `webapp.user-detail`, Instagram dan X dari judul, YouTube dari status HTTP | @tiktok (ada) dan @qzxvkontrol7h2k (tidak ada) | 18.03 sampai 18.06 |

#### 1.2 Hambatan yang memengaruhi bukti
- [FAKTA] PDKI mengembalikan 0 palsu setelah sekitar 26 kueri beruntun (18.10 UTC; "MARGI" yang punya 4 data ikut 0). Semua
  kueri sesudah titik itu diulang dengan jeda 4 detik dan kontrol "margi" di awal dan akhir (18.12 sampai 18.13 UTC).
- [FAKTA] Daftar hasil WIPO dirender bertahap; pengumpul yang menggulir membaca 68 dari 76 kartu kontrol (sekitar 89%).
- [FAKTA] Kantor dalam filter TMview untuk kueri luas "star" tidak memuat SG dan ID (18.31 UTC).
- [FAKTA] Tidak ada pencarian di halaman registrar, tidak ada akun dibuat, dan tidak ada formulir yang membuat rekaman.
  Semua skrip disimpan di scratchpad sesi (folder `verif3`), bukan di repositori.

### Inferences
- [INFERENSI] Kelengkapan per nama setara: ketiga nama diperiksa dengan sumber, strategi, dan jam yang sama, jadi
  perbandingannya adil. Kekurangan yang sama (Madura, Singapura, OpenCorporates) berlaku untuk ketiganya.
- [INFERENSI] "0 hasil" di keempat basis data merek adalah sinyal kuat, tetapi bukan bebas merek. Penafian PDKI tetap
  berlaku ("Keputusan tidak boleh dibuat berdasarkan pencarian ini saja").

### Gaps
- Singapura (IPOS) tidak diperiksa langsung; hanya tercakup sejauh WIPO GBD memuat datanya.
- Uji dengar-eja (K1b) tidak bisa dijalankan peneliti; skripnya ada di bagian 5.

---

## 2. TUMPALO

### Takeaway
TUMPALO tidak punya merek identik di PDKI, WIPO, TMview, maupun USPTO, tidak dipakai aplikasi di 10 storefront App
Store, dan keenam domain wajibnya tanpa catatan RDAP. Kekuatannya sebagai merek tinggi (kata ciptaan, K8 = 2), dan
bentuknya paling ramah aksara Jepang dan Korea di antara ketiga nama. Risikonya ada di tempat lain: "Tupalo" (EUTM dan
UK, kelas 35, 38, 41, masih terdaftar sampai 2029, situsnya aktif) hanya beda satu huruf dan bergerak di penemuan tempat;
"tumpal" dalam bahasa Jawa juga berarti "sampah" dan "numpal kèli" berarti "pergi tanpa tujuan"; bagian "palo" punya
makna vulgar yang dicatat RAE; dan makna budaya motif tumpal adalah "penolak bala", sehingga cerita merek tidak boleh
memakainya karena menyiratkan perlindungan. Skor: **v2 8 dari 12** (lolos semua gerbang) dan **v3 17 dari 26** (lolos
semua gerbang). Verdict: **lanjut bersyarat** ke uji dengar-eja dan konsultan KI.

### Cited Findings

#### 2.1 Domain (RDAP, bootstrap IANA diambil 16.48 UTC, publikasi "2026-09-16T19:00:03Z")
Kontrol pada run yang sama: verisign.com 200 (terdaftar 1995-06-02), google.app 200 (2018-03-29), pandi.id 200
(2013-04-14), telkom.co.id 200 (1995-05-18); kontrol fiktif `qzxvkontrol7h2k` 404 di .com, .app, .id, dan .co.id
([IANA dns.json](https://data.iana.org/rdap/dns.json)).

| Domain | Hasil | Jam UTC (23 Sep 2026) |
|---|---|---|
| [tumpalo.com](https://rdap.verisign.com/com/v1/domain/tumpalo.com) | 404 | 16.48 |
| [tumpalo.app](https://pubapi.registry.google/rdap/domain/tumpalo.app) | 404 | 16.48 |
| [tumpalo.id](https://rdap.pandi.id/rdap/domain/tumpalo.id) | 404 | 16.49 |
| [tumpalo.co.id](https://rdap.pandi.id/rdap/domain/tumpalo.co.id) | 404 | 16.49 |
| [gettumpalo.com](https://rdap.verisign.com/com/v1/domain/gettumpalo.com) | 404 | 16.48 |
| [tumpaloapp.com](https://rdap.verisign.com/com/v1/domain/tumpaloapp.com) | 404 | 16.48 |
| tumpalo.net, tumpalo.org, tumpalo.my.id, tumpalo.web.id | 404 | 16.48 sampai 16.49 |
| [tumpal.com](https://rdap.verisign.com/com/v1/domain/tumpal.com) (akar) | terdaftar 2018-12-12, kedaluwarsa 2026-12-12, NS afternic.com, registrar GoDaddy | 16.48 |
| tumpal.app / tumpal.id | 404 / 404 (tumpal.id diulang dengan curl karena koneksi PANDI terputus) | 16.48 / 18.02 |

- [FAKTA] Hasil ini sama dengan saringan cepat 15.27 UTC (`kandidat-dan-saringan.md` 2.2). Tidak ada perubahan.

#### 2.2 Merek dagang
**PDKI** (peramban headless terisolasi, profil sementara, tanpa akun; hasil UI dan aksi server halaman itu sendiri)
- [FAKTA] Mode Normal, 18.05 UTC: "Data tidak ditemukan untuk kata kunci "tumpalo"". Lewat aksi pencarian halaman
  (`match_phrase` pada `nama_merek`), 18.10 UTC: tumpalo, tumpal, tompalo, dan tumpallo masing-masing 0 data
  ([PDKI tumpalo](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=tumpalo&page=1)).
- [FAKTA] Daftar saran Fonetik (tab FONETIK aktif; aksi saran dipanggil dengan argumen "FONETIK"), 18.06 UTC: TUMPA-TUMPA,
  TUPAL, TUMPA, Umalo, TUMPAS, Tupado, TUPADO, TUMBAL, Cumalo, TOMPELO. Untuk "tumpal": TUMPA-TUMPA, TUPAL, TUMPA, TUMPAS,
  TUMBAL, TAMPALA, TUMPAS-PLUS, COMPAL, TAMPAH, FUNPAL.
- [FAKTA] Pemindaian kelas 9, 35, 39, 41, 42, 45 untuk tiap tetangga fonetik (18.10 sampai 18.15 UTC; kueri diulang
  dengan jeda 4 detik setelah server sempat mengembalikan 0 palsu, dengan kontrol "margi" = 4 data di awal dan akhir):
  - TUMBAL, (TM) Didaftar, kelas 9, PT. LYTO SUKSES BERSAMA, DID2024029624, IDM001269220, diajukan 2024-04-01, berlaku
    sampai 2034-04-02 ("DVD dan media perekaman digital lainnya ... film"); juga PAMALI TUMBAL kelas 9 (PT. LYTO SUKSES
    BERSAMA, DID2024094850, sampai 2034-09-20) dan TUMBAL KANJENG IBLIS kelas 9 dan 41 (PT. Visinema Pictures,
    DID2022104678, sampai 2032-12-16); TUMBAL kelas 41 (PT. SORAYA INTERCINE FILMS, JID2017024514, sampai 2027-05-31).
  - Umalo, (TM) Didaftar, kelas 7, 9, 10, 11, 12, 20, PT. Umalo Sedia Tekno, DID2024046570, IDM001271213, sampai 2034-05-30.
  - TUPADO, kelas 35, masa pengumuman, CV. Tetra Persada Engineering, JID2026095571, diajukan 2026-09-10.
  - COMPAL kelas 9 kedaluwarsa (2015); Tampah kelas 35 ditolak.

**WIPO Global Brand Database** (formulir halaman, peramban headless; cakupan tertera "76,769,388 records from 89 data
sources"; kontrol "vertica" Match exact = 76 hasil, sama dengan riset sebelumnya)
- [FAKTA] Embedded, 18.36 UTC: "No results found".
- [FAKTA] Phonetic, 18.36 UTC: "Displaying 1-30 of 10,752 results"; 85 kartu teratas terbaca. Paling mirip: TIMPALIO
  (Jepang, kelas 25, Expired), TUBALO (Viet Nam, kelas 18, Registered), TOMPALA (Boehringer Ingelheim, kelas 5, Expired
  di banyak negara termasuk Indonesia), TUBALOO (Chile, kelas 35, 38, 42, Ended 2009).
- [FAKTA] Fuzzy, 18.38 UTC: "Displaying 1-30 of 1,789 results"; 57 kartu terbaca. Paling mirip: **Tupalo, Tupalo
  Internetservices GmbH (Austria), kelas 35, 38, 41, UK, Registered (March 29, 2010), UK00908562563**. Merek aktif kelas 9
  atau 42 yang terbaca hanya berkemiripan rendah: JUMPALA (AS, kelas 9), TIMPANO (Uruguay dan Kanada, kelas 9, Pending),
  TEMPANO (Paraguay, kelas 42), Tuppato (Brasil, kelas 42) ([Global Brand Database](https://branddb.wipo.int/en/quicksearch)).

**TMview** (API pencarian halaman itu sendiri, 18.30 UTC; kontrol "vertica" Is = 83 hasil)
- [FAKTA] Is: 0. Contains: 1, "NT K TUMPALOVA", EUTM 019395625, Filed 2026-07-15, kelas 3, 16, 18, 21, 25, 33, 35, 43,
  pemohon "ВВВ - 8" ЕООД. Fuzzy: 15 (EM 2, KR 8, GB 2, US 3), antara lain Tupalo (EM dan GB), TUMALO (AS, Ended), dan
  투마로 milik LG Household & Health Care (KR, kelas 3).
- [FAKTA] Detail EUTM 008562563 "Tupalo" (18.31 UTC): merek kata, diajukan 2009-09-21, didaftar 2010-03-29, **berlaku
  sampai 2029-09-21**, status Registered. Kelas 35 "Advertising; business management ..."; kelas 38
  "Telecommunications"; kelas 41 "Education; providing of training; entertainment; sporting and cultural activities;
  providing online-electronic publications, namely, telephone directories"
  ([TMview](https://www.tmdn.org/tmview/#/tmview/detail/EM500000008562563)).
- [FAKTA] tupalo.com menjawab HTTP 200 dengan judul "Welcome to Tupalo" dan catatan "2026 Tupalo" (18.49 UTC); cuplikan
  Yahoo! JAPAN berbunyi "Explore the best spots in your surroundings through Tupalo" ([tupalo.com](https://tupalo.com/)).
  [UNVERIFIED, cuplikan] Tracxn menyebutnya perusahaan asal Wina yang didirikan 2007.
- [FAKTA] Kantor dalam daftar filter TMview untuk kueri luas "star" (18.31 UTC) mencakup EM, GB, US, AU, MY, BN, KH, PH,
  TH, VN, KR, JP, CN, IN, dan WO, tetapi **tidak ada SG dan ID**. Singapura dan Indonesia dalam kelompok ini hanya tercakup
  lewat WIPO GBD dan PDKI.

**USPTO** (API pencarian tmsearch.uspto.gov, kueri baca; filter "alive" untuk merek hidup)
- [FAKTA] Pada 18.26 sampai 18.27 UTC API dan halaman awal menjawab HTTP 403 CloudFront (headless, headful, dan curl).
  Pada 18.52 UTC keduanya kembali 200, dan semua kueri di bawah dijalankan 18.53 sampai 18.56 UTC. Kontrol "vertica"
  frasa persis: 8 hidup, 21 mati ([USPTO Trademark Search](https://tmsearch.uspto.gov/search/search-information)).
- [FAKTA] Frasa persis pada wordmark dan pseudo mark: tumpalo 0 hasil.
- [FAKTA] Fuzzy (jarak edit 2), hidup: 75 hasil. Paling mirip: TUMAO (kelas 28), CUPALO (kelas 35, Cupalo, LLC, RN
  7705510, rekrutmen TI), CUMPLO (kelas 36), TUPELO (kelas 18, 19, 22, 25).
- [FAKTA] Wildcard `*umpal*`, hidup: 6 hasil, termasuk **JUMPALA** (kelas 9, Yokereba LLC, SN 90295894, RN 6434108,
  terdaftar 2021-07-27, "Downloadable video game programs ...") dan **PUMPAL** (kelas 9, Concepts ETI, Inc., RN 2274346,
  terdaftar 1999-08-31, "software ... for use in the design and development of turbomachinery").

#### 2.3 Linguistik (K2)
**Tier A**
- [FAKTA] Indonesia: cermin KBBI "tum·pal n motif batik dengan lukisan tiga setrip yang berjajar (pada sarung dan
  sebagainya)"; "tumpalo" tidak ditemukan (18.37 UTC; [kbbi.web.id/tumpal](https://kbbi.web.id/tumpal), UNVERIFIED terhadap
  KBBI VI). Tetangga satu fonem: "tum·bal Jw n 1 sesuatu yang dipakai untuk menolak (penyakit dan sebagainya); tolak bala
  ...; 2 kurban (persembahan dan sebagainya)" ([kbbi.web.id/tumbal](https://kbbi.web.id/tumbal)); Wiktionary: "sacrifice,
  corban, victim" ([tumbal](https://en.wiktionary.org/wiki/tumbal), 18.50 UTC).
- [FAKTA] Jawa (KBJI resmi, 18.39 UTC): "1 tumpal KN corak kain batik, tumpal; 2 tumpal KN sampah; numpal kèli pergi
  tanpa tujuan" ([KBJI: tumpal](https://kbji.kemendikdasmen.go.id/kata/tumpal)). "tumpalo" bukan lema; pencarian cerdas
  menyarankan tambal, tambel, tampal ([KBJI: tumpalo](https://kbji.kemendikdasmen.go.id/kata/tumpalo)).
- [FAKTA] Sunda (SundaDigi, Kamus Utama 103.370 entri, 19.00 UTC): "tumpal sabagian tina sarung, biasana (upama keur
  dipaké) sok diteundeun di tukang"; padanan Indonesia "bagian kain sarung yang biasa ditempatkan di belakang (jika
  dipakai)". "tumpalo" dan "palo" bukan entri ([SundaDigi](https://sundadigi.com/kamus_utama)).
- [FAKTA] Bali: BASAbali tidak punya halaman "Tumpal", "Tumpalo", atau "Palo" (18.38 UTC)
  ([BASAbali: Tumpal](https://dictionary.basabali.org/w/index.php?title=Tumpal)).
- [FAKTA] Melayu (PRPM, 18.38 UTC): "tumpal" "Carian kata tiada di dalam kamus terkini"; "tumpalo" tidak ada, dengan saran
  tumpil, tumpul, timpal ([PRPM: tumpal](https://prpm.dbp.gov.my/Cari1?keyword=tumpal)).
- [ISU TERBUKA] Madura: tidak ditemukan kamus daring yang bisa dikueri (Labbineka tidak memuat kamus Madura; kueri web
  tertunda karena Yahoo! JAPAN menjawab 429). UNVERIFIED.
- [FAKTA] Inggris: Wiktionary tidak punya entri "tumpalo" atau "tumpal" (404, 18.37 UTC); Urban Dictionary tanpa entri
  kata persis untuk keduanya (18.37 UTC).
- [FAKTA] Mandarin: bunyi "pà" sama dengan 怕 "to fear; to be afraid of" dan "luò" sama dengan 落 "to fall; to drop"
  ([怕](https://en.wiktionary.org/wiki/%E6%80%95), [落](https://en.wiktionary.org/wiki/%E8%90%BD), 18.57 UTC). Lihat 2.4.
- [FAKTA] Prancis, Belanda, Jerman: Wiktionary tidak punya entri ketiga bahasa itu untuk "tumpalo", "tumpal", "tumpa",
  maupun "palo" (bahasa yang tercatat untuk "palo" antara lain Spanyol, Italia, Tagalog, Finlandia, Latin, dan
  Serbo-Kroasia; dicek 19.15 UTC). Ini sinyal lemah, bukan bukti tidak ada makna.

**Tier B**
- [FAKTA] Spanyol: Wiktionary "palo" antara lain "stake, stick, wood | ... hit, blow, strike | ... shot (a small serving of
  alcoholic spirits) | penis" ([palo](https://en.wiktionary.org/wiki/palo), 18.50 UTC). RAE, dibuka langsung lewat
  peramban headless (19.05 UTC): "m. vulg. coito. Echar un palo." ([RAE: palo](https://dle.rae.es/palo)). Urban Dictionary
  (entri tanpa suara): "mexican slang for a mans penis" ([UD: palo](https://www.urbandictionary.com/define.php?term=palo)).
  Cuplikan WordReference dan Reddit r/AskMexico menguatkan bahwa "echar un palo" berarti berhubungan seks di Meksiko.
- [FAKTA] Tagalog: "palo" = "stroke with the hand or a stick, usually as punishment; spank"; "tumpa" = "route; course;
  direction | destination"; "tumpak" = "correct; right | ... suited; fit"; "tampal" = tamparan
  ([tumpa](https://en.wiktionary.org/wiki/tumpa), [tumpak](https://en.wiktionary.org/wiki/tumpak), 18.37 sampai 18.51 UTC).
- [FAKTA] Italia "palo" = "stake, pole ... | lookout (person on watch)"; Finlandia "palo" = "fire" (Wiktionary, 18.50 UTC).
- [FAKTA] Portugis: Wiktionary tidak punya entri Portugis untuk "palo" (filter bahasa, 18.50 UTC). Bahasa Tier B lain
  (Arab, Thai, Vietnam, Rusia, Polandia, Ceko, Hungaria, Tamil, Kanton, Hokkien, Denmark, Swedia, Norwegia, Turki, Hindi,
  Urdu, Persia): tidak ada entri Wiktionary untuk "tumpalo" atau "tumpal". Cakupan Wiktionary terbatas, jadi UNVERIFIED.

#### 2.4 Aksara (K9)
- [FAKTA] Jepang: moraic nasal ン "before a plosive ... is pronounced as a nasal consonant assimilated to the place of the
  following consonant" ([Wikipedia: Japanese phonology](https://en.wikipedia.org/wiki/Japanese_phonology), 18.58 UTC).
  [INFERENSI] Jadi "tum-pa" menjadi トゥンパ tanpa vokal sisipan; TUMPALO ≈ トゥンパロ, 4 mora. パロ adalah "clipping of
  パロディ (parodi)" ([パロ](https://en.wiktionary.org/wiki/%E3%83%91%E3%83%AD)), netral.
- [FAKTA] Korea: aturan ejaan serapan "어중의 l이 모음 앞에 올 때는 'ㄹㄹ'로 적는다"
  ([외래어 표기법](https://ko.wikipedia.org/wiki/%EC%99%B8%EB%9E%98%EC%96%B4_%ED%91%9C%EA%B8%B0%EB%B2%95), 18.57 UTC).
  [INFERENSI] TUMPALO ≈ 툼팔로, 3 suku; 팔로우 = "follow" ([팔로우](https://en.wiktionary.org/wiki/%ED%8C%94%EB%A1%9C%EC%9A%B0)),
  netral.
- [FAKTA] Mandarin: Tampa (Florida) ditulis 坦帕 ([zh.wikipedia: 坦帕](https://zh.wikipedia.org/wiki/%E5%9D%A6%E5%B8%95),
  19.04 UTC), artinya "m" sebelum "p" dipetakan ke koda n tanpa suku sisipan. [INFERENSI] TUMPALO ≈ 通帕洛 atau 坦帕洛
  (3 suku). Rangkaian bunyi "pà luò" dapat terdengar seperti 怕落 ("takut jatuh"), plesetan yang kurang pas untuk produk
  gunung. Ini bukan kata leksikal dan bisa dihindari lewat pilihan aksara, tetapi perlu dicek penutur Mandarin.

#### 2.5 Toko aplikasi, perusahaan, web, kripto
- [FAKTA] App Store (iTunes Search API, `entity=software`, `limit=50`, 18.03 sampai 18.06 UTC): "tumpalo" dan "tumpal"
  tidak muncul sebagai judul atau nama penjual di ID, US, GB, AU, SG, MY, DE, FR, NL, maupun JP (jumlah hasil fuzzy:
  tumpalo US 6, GB 7, AU 7, lainnya 0). Kontrol "komoot" (18.15 sampai 18.16 UTC) menemukan "komoot - hike, bike & run"
  (komoot GmbH) di kesepuluh storefront, jadi 0 hasil adalah 0 kecocokan
  ([contoh ID](https://itunes.apple.com/search?term=tumpalo&country=id&entity=software&limit=50)).
- [FAKTA] Google Play (18.17 UTC, US/en dan ID/id): tidak ada judul yang memuat "tumpalo"; kontrol "komoot" tertangkap
  ([Play](https://play.google.com/store/search?q=tumpalo&c=apps&hl=en&gl=US)). Pengurai tidak lengkap, sinyal lemah.
- [FAKTA] GitHub API (18.42 UTC): 0 repositori bernama "tumpalo", 0 organisasi. CoinGecko: 0 koin. Companies House UK:
  tanpa perusahaan. Wikipedia en dan id: 0 hasil. Product Hunt: "No products found for "tumpalo"" (18.43 UTC).
- [FAKTA] OpenCorporates menampilkan "HAProxy Challenge" (captcha) dan Etherscan menampilkan verifikasi Cloudflare
  (18.43 UTC), baik lewat skrip maupun peramban headless. Keduanya UNVERIFIED.
- [FAKTA] Web: kuota WebSearch sesi habis (200 dari 200), Brave memblokir peramban headless, dan Bing lewat WebFetch
  hanya memberi halaman bantuan Google Maps yang tidak relevan. Yahoo! JAPAN (18.40 UTC): "tumpalo" 2 hasil, keduanya
  bukan merek (teks liturgi berbahasa Swahili dan hasil OCR koran Portugis); "tumpalo" app: 0 hasil
  ([Yahoo! JAPAN](https://search.yahoo.co.jp/search?p=%22tumpalo%22)).

#### 2.6 Handle (peramban headless tanpa login, 18.03 sampai 18.06 UTC)
| Handle | TikTok | Instagram | YouTube | X |
|---|---|---|---|---|
| kontrol ada @tiktok | ada (95.900.000 pengikut) | ada | 200 | ada |
| kontrol fiktif @qzxvkontrol7h2k | 10221 (tidak ditemukan) | "Profile isn't available" | 404 | "User Profile Not Found" |
| @tumpalo | ada (1 pengikut, 0 video) | dipakai akun pribadi | 404 | dipakai akun pribadi (terkunci) |
| @tumpaloapp | 10221 | "Profile isn't available" | 404 | "User Profile Not Found" |

#### 2.7 Cek khusus TUMPALO
- [FAKTA] Makna motif (dibuka langsung sekitar 19.01 UTC): Kompas.com (20 April 2021, bagian Skola): "Motif tumpal memiliki makna penolak bala atau penjauh
  bencana bagi yang memakainya" dan "memiliki arti magis yaitu keselarasan antara manusia, semesta, dan alam lain atau
  tuhan"; artikel tidak menyebut pembatasan pemakai
  ([Kompas](https://www.kompas.com/skola/read/2021/04/20/151303969/makna-motif-tumpal-dalam-batik-betawi)).
- [FAKTA] Kajian aksiologis (ICAAE-18, terbit 2019; dibuka sekitar 19.01 UTC): "The meaning in the tumpal triangle motif is interpreted as
  divinity, knowledge, and power" dan "The birth of the tumpal motif was originated in the kingdom"; tidak ada pernyataan
  larangan ritual ([Atlantis Press](https://www.atlantis-press.com/proceedings/icaae-18/125910467)).
- [FAKTA] Wikipedia "Batik keraton" (juga disebut batik larangan) tidak menyebut tumpal; motif larangan yang disebut di
  artikel "Batik Tiga Negeri" adalah "parang dan kawung" (19.01 UTC;
  [Batik keraton](https://id.wikipedia.org/wiki/Batik_keraton), [Batik Tiga Negeri](https://id.wikipedia.org/wiki/Batik_Tiga_Negeri)).
- [FAKTA, cuplikan] Pemakaian komersial luas: blog mode Zalora dan Knitto membahas "batik tumpal"; Dinas Kebudayaan
  Jakarta menyebut pucuk rebung sebagai adaptasi "tumpal pada batik" dalam batik Betawi (Yahoo! JAPAN, 18.43 sampai
  18.45 UTC).
- [FAKTA] Keterkenalan: "Motif ini serupa dengan motif tumpal yang dikenal umum di kepulauan Indonesia"
  ([Wikipedia: Pucuak rabuang](https://id.wikipedia.org/wiki/Pucuak_rabuang), 19.04 UTC); motif ini dijelaskan di kanal
  pendidikan Kompas Skola. Di luar Indonesia, rujukan yang ditemukan hanya glosarium tekstil
  ([Mandarin Mansion](https://www.mandarinmansion.com/glossary/tumpal)) dan kajian akademik.

### Inferences

#### 2.8 Skor rubrik v2 (0 sampai 2, maksimum 12)
| Kode | Skor | Dasar |
|---|---|---|
| K1 | 2 | Tidak ada huruf atau gugus di daftar v2; 3 suku kata |
| K2 | 1 | Jawa "tumpal" = sampah dan "numpal kèli" = pergi tanpa tujuan (makna ringan di Tier A); bagian "palo" bermakna vulgar dalam frasa Spanyol "echar un palo" (RAE); dekat bunyi "tumbal" (kurban) |
| K3 | 1 | Samar: makna budaya akar "tumpal" adalah penolak bala dan penjauh bencana (Kompas), yaitu perlindungan, dan segitiganya ditafsirkan sebagai gunung; nama sendiri tidak menyebut aman atau puncak |
| K4 | 1 | Menggemakan zig-zag logo, tetapi tidak menopang pilihan rute, pemahaman alasan, atau kepercayaan tanpa cerita merek |
| K5 | 2 | .com, .app, .id, .co.id, dan kedua fallback tanpa catatan RDAP |
| K6 | 1 | Tanpa merek identik; mirip di kelas lain atau bidang lain: Tupalo (EU/UK, 35/38/41), TUMBAL (ID, kelas 9, film), JUMPALA dan PUMPAL (AS, kelas 9) |
| **Total** | **8** | Lolos G1, G2, G3, G4. Turun dari 11 (provisional) karena temuan KBJI, RAE, makna budaya, dan basis data merek |

#### 2.9 Skor rubrik v3 (maksimum 26)
| Kode | Bobot | Skor | Nilai | Dasar |
|---|---|---|---|---|
| K6 | ×3 | 1 | 3 | Lihat v2 K6. Tidak ada merek identik atau sangat mirip aktif di kelas 9/42 di ID, AS, atau UE, dan tidak ada aplikasi pesaing langsung, jadi G3 tidak berlaku |
| K8 | ×2 | 2 | 4 | Kata ciptaan dari istilah tekstil: arbitrary atau fanciful untuk perangkat lunak dan jasa perjalanan dalam bahasa Indonesia, Melayu, Inggris, dan bahasa UE. Tagalog "tumpa" (rute) hanya bagian; "tumpalo" sendiri tidak bermakna |
| K2 | ×2 | 1 | 2 | Lihat v2 K2 |
| K1a | ×2 | 1 | 2 | Satu masalah: koda "m" (gugus "mp"). Tidak menyisipkan vokal di Jepang (ン), tetapi Mandarin hanya punya koda n dan ng |
| K3 | ×1 | 1 | 1 | Lihat v2 K3 |
| K4a | ×1 | 1 | 1 | Cocok dengan logo; tidak dengan pilihan rute, alasan, atau kepercayaan |
| K9 | ×1 | 2 | 2 | トゥンパロ (4 mora, tanpa sisipan), 툼팔로 (3 suku), 坦帕洛 atau 通帕洛 (3 suku); plesetan 怕落 bersifat dugaan |
| K5 | ×1 | 2 | 2 | Lihat v2 K5 |
| **Total** | | | **17** | Gerbang: G1 tidak (K2 = 1), G3 tidak, G4 sementara tidak (K1a = 1), G5 tidak (K8 = 2) |

- [INFERENSI] Sensitivitas: bila K3 dinilai hanya dari bunyi nama (2), total menjadi v2 9 dan v3 18. Bila penutur
  Spanyol Meksiko atau Karibia menilai "tumpalo" langsung memicu makna vulgar, K2 menjadi 0 dan nama gugur G1.
- [INFERENSI] Risiko hukum utama ada di UE dan UK, bukan di Indonesia. "Tupalo" beda satu huruf, masih berlaku sampai
  2029, dan bergerak di penemuan tempat. Penunjukan UE di kelas 9 dan 42 kemungkinan tidak terhalang langsung, tetapi
  keterkaitan jasa (platform penemuan) bisa dipakai dalam oposisi. Ini perlu pendapat konsultan KI Eropa.
- [INFERENSI] Makna "penolak bala" bertentangan dengan R-012 dan R-014 bila dipakai dalam cerita merek. Cerita yang aman
  hanya memakai bentuk visual (deret segitiga seperti garis zig-zag), bukan makna perlindungannya.

#### 2.10 Verdict TUMPALO
**Lanjut bersyarat.** Nama ini paling kuat sebagai merek dan paling bersih di basis data merek untuk kelas 9 dan 42,
tetapi syaratnya: (1) uji dengar-eja lulus, terutama pada penutur Prancis dan Belanda yang cenderung menulis "ou" atau
"oe" untuk bunyi u; (2) penutur Jawa menilai makna "sampah" tidak dominan; (3) penutur Spanyol Meksiko dan Karibia tidak
menangkap makna vulgar; (4) konsultan KI menilai risiko "Tupalo" di UE dan UK; (5) pemilik menerima bahwa handle persis
TikTok, Instagram, dan X sudah dipakai, dengan pola cadangan seperti @tumpaloapp.

### Gaps
- Madura belum diperiksa (tidak ada kamus daring yang bisa dikueri). Kanton dan Hokkien tidak diperiksa selain lewat
  Wiktionary.
- WIPO hanya membaca 57 sampai 85 kartu teratas dari ribuan hasil Phonetic dan Fuzzy, dan daftar hasilnya
  tervirtualisasi (kontrol membaca 68 dari 76 kartu). Merek mirip di urutan bawah bisa terlewat.
- Pencarian web umum hanya lewat Yahoo! JAPAN. OpenCorporates dan Etherscan tidak dapat dibuka.
- Semua penilaian K1a, K3, dan K9 adalah inferensi meja; K1b (uji dengar-eja) belum dijalankan.

---

## 3. PAMARGA

### Takeaway
PAMARGA juga tidak punya merek identik di PDKI, WIPO, TMview, atau USPTO, tidak dipakai aplikasi di 10 storefront, dan
semua domainnya tanpa catatan RDAP. Temuan baru yang mengubah gambaran: "pamarga" ternyata kata bahasa Bali tingkat
andap yang berarti "procedure; course, action, way" (BASAbali), sehingga nama ini bukan kata ciptaan murni. Akar "marga"
dipakai merek BUMN terkenal JASA MARGA di kelas 9, 39, dan 42, dan ada permohonan "MARGA" di AS kelas 42 (Juli 2026).
Nama PAMARGA sudah dipakai sebagai nama badan usaha: pabrik air minum dalam kemasan di Jawa Timur, sebuah PT di
Balikpapan, dan Pamarga Investments SL di Spanyol. Makna "marg" (kematian) dalam bahasa Persia tetap berlaku. Skor:
**v2 10 dari 12** dan **v3 16 dari 26**, keduanya lolos semua gerbang. Verdict: **cadangan kuat**, di bawah TUMPALO pada
rubrik v3 karena daya pembeda merek lebih rendah (K8 = 1) dan kepadatan merek "MARGA".

### Cited Findings

#### 3.1 Domain (RDAP; kontrol dan bootstrap sama dengan 2.1)
| Domain | Hasil | Jam UTC (23 Sep 2026) |
|---|---|---|
| [pamarga.com](https://rdap.verisign.com/com/v1/domain/pamarga.com) | 404 | 16.48 |
| [pamarga.app](https://pubapi.registry.google/rdap/domain/pamarga.app) | 404 | 16.48 |
| [pamarga.id](https://rdap.pandi.id/rdap/domain/pamarga.id) | 404 | 16.49 |
| [pamarga.co.id](https://rdap.pandi.id/rdap/domain/pamarga.co.id) | 404 | 16.49 |
| [getpamarga.com](https://rdap.verisign.com/com/v1/domain/getpamarga.com) | 404 | 16.48 |
| [pamargaapp.com](https://rdap.verisign.com/com/v1/domain/pamargaapp.com) | 404 | 16.48 |
| pamarga.net, pamarga.org, pamarga.my.id, pamarga.web.id | 404 | 16.48 sampai 16.49 |

- [FAKTA] Sama dengan saringan cepat 15.27 UTC.

#### 3.2 Merek dagang
**PDKI**
- [FAKTA] Mode Normal (UI 18.06 UTC; aksi halaman 18.10 UTC): pamarga 0 data
  ([PDKI pamarga](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=pamarga&page=1)).
- [FAKTA] Saran Fonetik (18.06 UTC): AMARGA, PALMARA, PADMARA, PIMTARGA, AZARGA, AMARTA, Damara, NAMARA, AMARIA, LAMARA.
- [FAKTA] Tetangga di kelas 9, 35, 39, 41, 42, 45 (18.10 sampai 18.15 UTC):
  - AMARTA (80 data): Didaftar di kelas 9 (DID2021083380, sampai 2031-11-29), 41 (JID2021083922), dan 39 (JID2024079033,
    sampai 2034-08-15), ketiganya milik pemilik perorangan; banyak AMARTA lain di kelas 35 sudah kedaluwarsa.
  - damara, (TM) Didaftar, kelas 9, PT NEW KORA TRADE INDONESIA, DID2023072831, sampai 2033-08-28 ("peralatan periferal
    komputer, ..."); STUDIO DAMARA + LOGO kelas 42, pemilik perorangan, sampai 2034-01-31.
  - LAMARA kelas 35 kedaluwarsa (PT SHAFCO MULTI TRADING); NAMARA kelas 35 (perorangan).
- [FAKTA] Akar "MARGA" (88 data, 18.12 sampai 18.15 UTC), yang aktif di kelas inti antara lain:
  - **JASA MARGA** milik PT Jasa Marga (Persero) Tbk: kelas 42 (J002012022977, "Menciptakan/membuat dan memelihara
    website", sampai 2032-05-15), kelas 9 (D002012022974, "Kartu bersandi magnetik (e-Toll) ..."), kelas 39
    (J002012022980); diperbarui 2025 lewat JASA MARGA + LOGO dan JASA MARGA CONNECTING INDONESIA + LOGO di kelas 9, 39,
    dan 42 (mis. JID2025120462, sampai 2035-11-11).
  - BAKMI TIGA MARGA, kelas 42, PT Tiga Marga Jaya, JID2025055352, sampai 2035-06-17.
  - Karya Marga Indonesia (K@MI) + LOGO, kelas 39, sampai 2029-05-27; PANCA MARGA, kelas 45; beberapa "PERKUMPULAN MARGA
    ..." (perkumpulan klan) di kelas 41 dan 45.

**WIPO Global Brand Database**
- [FAKTA] Embedded, 18.46 UTC: "No results found".
- [FAKTA] Phonetic, 18.39 UTC: 929 hasil; 82 kartu teratas terbaca. Paling mirip: Pamara (Jerman, Chal-Tec GmbH, kelas 8
  dan 35, Registered 2017-04-05, 302017006376; juga UK UK00917071044), PAMAURA ("DILON" Ltd., Bulgaria; kelas 3, 35, 44;
  AS Registered 2021-08-17, 6451053), Pomara (Indonesia, kelas 29).
- [FAKTA] Fuzzy, 18.46 UTC: 2.280 hasil; 58 kartu terbaca. Paling mirip: PAMCARGA (Brasil, GPSLOGÍSTICA E GERENCIAMENTO DE
  RISCOS, kelas 35 dan 36, Registered 2018), **APAMARGA** (Brasil, SM EMPREENDIMENTOS FARMACEUTICOS LTDA, kelas 1,
  Registered 2022-08-02), PIMARGA (Glaxo Group Limited, kelas 5, UK Expired 2020, AS Ended 2020), AMARGA (Indonesia,
  PT. DAPUR SOLO SUKSES SEJATI, kelas 43, IDM000597943; Spanyol kelas 32, Pending). Merek aktif kelas 42 yang terbaca:
  LA CAMARGA (Spanyol, TOT AUTENTIC, S.L., M2096433, Registered 1997-11-05; kelas 42 sebelum 2002 juga mencakup jasa
  restoran, sehingga bidang aslinya perlu dicek).

**TMview** (18.30 UTC)
- [FAKTA] Is: 0. Contains: 1, APAMARGA (Brasil, kelas 1). Fuzzy: 137 hasil (dua halaman), antara lain PAMCARGA (BR),
  Pamara (EUTM 017071044, GB, DE; Chal-Tec GmbH; kelas 8, 35), pamara (Turki, ABFEN FARMA, kelas 3, 5, 10, 35, 41, 42,
  Filed 2022), pamarta (Turki, kelas 42, Filed 2018).

**USPTO** (18.54 sampai 18.56 UTC)
- [FAKTA] Frasa persis: pamarga 0 hasil.
- [FAKTA] Fuzzy hidup: 263 hasil; paling mirip PAMARVA (kelas 19), PAMAURA (kelas 3, 35, 44), AMARA (kelas 42, Frontier
  Technology Inc., RN 7969040, terdaftar 2025-09-30). Yang mati: PARIMARGA (kelas 9 dan 42, dibatalkan 2023-09-29,
  "software for enabling easy search of local medical facilities").
- [FAKTA] Wildcard `*marga` hidup: 16 hasil, termasuk **MARGA** (SN 99930248, diajukan 2026-07-15, pemohon perorangan,
  belum terdaftar; kelas 42 "Software as a service (SAAS) services featuring software for market analysis ... in the
  medical aesthetics, dermatology, life sciences, and health technology industries", juga kelas 35 dan 41), ANANDA MARGA
  (organisasi spiritual; kelas 35, 36, 41, 45), MAVEN MARGA (kelas 16, 26), MARGA CIPTA (kelas 17)
  ([USPTO Trademark Search](https://tmsearch.uspto.gov/search/search-information)).

#### 3.3 Linguistik (K2)
**Tier A**
- [FAKTA] Bali (BASAbali, 18.39 UTC): **"Pamarga"**, tingkat tutur **Andap**; "Definitions: procedure; course, action,
  way"; contoh "Mahasiswa sane bareng penelitian nika nuutin pamarga manga polih hasil sane becik" (diterjemahkan
  "Mahasiswa yang ikut penelitian itu menuruti prosedur agar mendapatkan hasil yang baik")
  ([BASAbali: Pamarga](https://dictionary.basabali.org/w/index.php?title=Pamarga&uselang=en)). "Marga" (andap) = jalan
  ([BASAbali: Marga](https://dictionary.basabali.org/w/index.php?title=Marga&uselang=id)).
- [FAKTA] Indonesia: cermin KBBI "mar·ga 1 kl n binatang liar (tidak diternakkan atau dipelihara)"; "pamarga" tidak
  ditemukan (18.37 sampai 18.38 UTC; [kbbi.web.id/marga](https://kbbi.web.id/marga), UNVERIFIED terhadap KBBI VI).
  Wiktionary Indonesia "marga": "wild animal | surname; family name | clan | specifically, the marga, Batak clan | the
  subdivision in South Sumatra ... | genus ... | way, road, route, path" ([marga](https://en.wiktionary.org/wiki/marga),
  19.08 UTC).
- [FAKTA] Jawa (KBJI, 18.38 UTC): "marga jalan; marga sangka sebab, karena"; "pamarga" bukan lema (saran: amarga)
  ([KBJI: marga](https://kbji.kemendikdasmen.go.id/kata/marga)).
- [FAKTA] Sunda (SundaDigi, 19.00 UTC): "marga (Sskr.), = jalan, lantaran at. sabab"; "pamarga" bukan entri.
- [FAKTA] Melayu (PRPM, 18.38 sampai 18.39 UTC): "pamarga" tidak ada (saran: pemarkah). "marga I ... binatang liar;
  mergastua" dan "marga II 1. ... puak ... (di kalangan orang Batak); 2. bahagian daerah ... (Sumatera Selatan)" (Kamus
  Dewan Edisi Keempat) ([PRPM: marga](https://prpm.dbp.gov.my/Cari1?keyword=marga)).
- [ISU TERBUKA] Madura: UNVERIFIED (lihat 2.3).
- [FAKTA] Inggris: Wiktionary "marga" (Inggris) "Any of various paths or courses seen as leading to enlightenment" dan
  "Someone of undernourished figure"; Urban Dictionary tanpa entri "pamarga"; entri "marga" berupa definisi nama orang
  dan "someone that is skinny" (18.37 UTC).
- [FAKTA] Mandarin: transliterasi nama Indonesia berkoda r cenderung menghilangkan r (Jakarta = 雅加达, 19.04 UTC), jadi
  PAMARGA ≈ 帕马加 [INFERENSI]; 加 = "to add; to increase" ([加](https://en.wiktionary.org/wiki/%E5%8A%A0)).
- [FAKTA] Prancis, Belanda, Jerman: tidak ada entri Wiktionary untuk "pamarga", dan "marga" tidak punya entri dalam
  ketiga bahasa itu (bahasa yang tercatat: Bali, Inggris, Estonia, Fula, Indonesia, Jawa, Latin, Norse Kuno, Oromo,
  Polandia, Portugis, Spanyol; dicek 19.15 UTC).

**Tier B**
- [FAKTA] Persia dan Urdu: مرگ = "death"; Urdu juga "deer | antelope" ([مرگ](https://en.wiktionary.org/wiki/%D9%85%D8%B1%DA%AF),
  18.50 UTC).
- [FAKTA] Hindi: मार्ग = "path, road, route | way" ([मार्ग](https://en.wiktionary.org/wiki/%E0%A4%AE%E0%A4%BE%E0%A4%B0%E0%A5%8D%E0%A4%97)).
- [FAKTA] Sanskerta: "Apamārga (अपमार्ग): 1) A by-path, side-way; a bad way" (tanda baca kutipan disesuaikan), dan "Apāmārga (अपामार्ग) ... the plant
  Achyranthes Aspera" ([wisdomlib](https://www.wisdomlib.org/definition/apamarga), 18.52 UTC). Artikel PMC memakai
  "Pamarga" sebagai sebutan singkat bahan Ayurveda itu (cuplikan Yahoo! JAPAN, 18.50 UTC;
  [PMC3221078](https://pmc.ncbi.nlm.nih.gov/articles/PMC3221078/)).
- [FAKTA] Spanyol dan Portugis "marga" = "marl" (napal); Polandia "marga" = bentuk kata kerja "margać" (19.08 UTC).
- [FAKTA] Bahasa Tier B lain: tidak ada entri Wiktionary untuk "pamarga" (404). UNVERIFIED.

#### 3.4 Aksara (K9)
- [FAKTA] Nama Indonesia berkoda r mendapat vokal sisipan di Jepang dan Korea: Jakarta = ジャカルタ dan 자카르타
  (Wikipedia ja dan ko, 19.04 UTC). [INFERENSI] PAMARGA ≈ パマルガ (4 mora, satu sisipan) dan 파마르가 (4 suku, satu
  sisipan).
- [FAKTA] Korea 파마 = "a perm" (keriting rambut) ([파마](https://en.wiktionary.org/wiki/%ED%8C%8C%EB%A7%88), 18.50 UTC).
  [INFERENSI] 파마르가 diawali kata umum 파마, asosiasi yang mengganggu walau tidak negatif.

#### 3.5 Toko aplikasi, perusahaan, web, kripto
- [FAKTA] App Store (18.03 sampai 18.06 UTC): "pamarga" tidak muncul sebagai judul atau penjual di kesepuluh storefront
  (hasil fuzzy: US 6, GB 5, AU 5, lainnya 0; kontrol "komoot" lulus). Google Play (18.17 UTC): tidak ada judul yang memuat
  nama.
- [FAKTA] Badan usaha bernama Pamarga:
  - PT. PAMARGA JATIM (AIR MINERAL "ADES") tercantum sebagai perusahaan mitra klinik K3 Sentra Medika Surabaya (dibuka
    langsung 19.08 UTC; [Sentra Medika](https://sentramedikasby.com/id/doctor)).
  - [FAKTA, cuplikan] "PAMARGA INDO JATIM Merek: Osmo Alamat Pabrik: Jl. Raya Surabaya, Malang Km 59, Pasuruan Produk
    AMDK tgl. sert 7 Okt2003" dalam direktori pabrik air minum dalam kemasan bersertifikat SNI (Scribd, lewat Yahoo!
    JAPAN 18.50 UTC; [Scribd](https://id.scribd.com/document/359799910/PERUSAHAAN-AMDK)).
  - PT. Pamarga Indonesia, Balikpapan: hanya muncul di profil LinkedIn seorang karyawan (lewat Yahoo! JAPAN 18.42 UTC);
    bidang usahanya dan hubungannya dengan Pamarga Indo Jatim **tidak diketahui** (UNVERIFIED).
  - Pamarga Investments SL, Spanyol: BORME 4 Juli 2012 (dibuka langsung 19.08 UTC) mencatat "Constitución. Comienzo de
    operaciones: 18.06.12. Objeto social: LA COMPRA, VENTA, IMPORTACION, EXPORTACION, DISTRIBUCION Y COMERCIALIZACION DE
    TODO TIPO DE VEHICULOS A MOTOR ..." dengan domisili di Las Rozas de Madrid
    ([BORME-A-2012-126-28](https://www.boe.es/borme/dias/2012/07/04/pdfs/BORME-A-2012-126-28.pdf)). Konflik sumber:
    cuplikan eInforma menggolongkannya "CNAE 6811 - Compraventa de bienes inmobiliarios por cuenta propia"
    ([eInforma](https://www.einforma.com/informacion-empresa/pamarga-investments)); Empresite menyebut jual beli, impor,
    dan distribusi. Kegiatan terkini UNVERIFIED.
- [FAKTA] GitHub (18.42 UTC): 0 repositori, 0 organisasi. CoinGecko: 0. Companies House UK: hanya PAMARGAN PRODUCTS
  LIMITED (nama berbeda). Wikipedia en 0; id 7 hasil yang tidak memuat nama persis (antara lain SD Strada Dipamarga).
  Product Hunt: "No products found for "pamarga"" (18.44 UTC). OpenCorporates dan Etherscan diblokir captcha.
- [FAKTA] Yahoo! JAPAN (18.41 UTC): hasil didominasi nama pribadi, profil LinkedIn di atas, Pamarga Investments SL, dan
  artikel Ayurveda "Apamarga"; tidak ada aplikasi atau perangkat lunak bernama Pamarga
  ([Yahoo! JAPAN](https://search.yahoo.co.jp/search?p=%22pamarga%22)).

#### 3.6 Handle (18.04 sampai 18.06 UTC; kontrol sama dengan 2.6)
| Handle | TikTok | Instagram | YouTube | X |
|---|---|---|---|---|
| @pamarga | ada (0 pengikut, 0 video) | dipakai akun pribadi | 404 | dipakai akun pribadi |
| @pamargaapp | 10221 | "Profile isn't available" | 404 | "User Profile Not Found" |

### Inferences

#### 3.7 Skor rubrik v2 (maksimum 12)
| Kode | Skor | Dasar |
|---|---|---|
| K1 | 2 | Tidak ada huruf di daftar v2 (g sebelum a keras di bahasa acuan; g frikatif Belanda di luar daftar) |
| K2 | 1 | Bagian "marg" = kematian (Persia, Urdu); "marga" = klan Batak dan binatang liar (Indonesia, Melayu); tidak ada makna kasar, vulgar, atau sakral |
| K3 | 2 | Tidak ada konotasi kecepatan, kompetisi, penaklukan, puncak, atau jaminan aman |
| K4 | 2 | "marga" = jalan (Jawa, Sunda, Bali, Indonesia) dan sebab (Jawa, Sunda), sama dengan dua kalimat tagline; "pamarga" (Bali) = cara, jalannya tindakan; huruf M menggemakan zig-zag |
| K5 | 2 | Semua domain wajib dan fallback tanpa catatan RDAP |
| K6 | 1 | Tanpa merek identik; akar "MARGA" aktif di kelas inti (JASA MARGA di ID 9/39/42; permohonan MARGA di AS kelas 42); nama badan usaha di ID dan ES di bidang lain |
| **Total** | **10** | Lolos semua gerbang; sama dengan skor provisional |

#### 3.8 Skor rubrik v3 (maksimum 26)
| Kode | Bobot | Skor | Nilai | Dasar |
|---|---|---|---|---|
| K6 | ×3 | 1 | 3 | Lihat v2 K6. Bukan G3: JASA MARGA dan MARGA berbagi akar tetapi tidak identik atau sangat mirip dengan PAMARGA secara keseluruhan, dan bidangnya berbeda |
| K8 | ×2 | 1 | 2 | Sugestif: "pamarga" (Bali) = cara, jalannya tindakan; "marga" = jalan dalam bahasa Indonesia, Jawa, Sunda, dan Bali. Tidak deskriptif untuk perangkat lunak, tetapi DJKI mencatat terjemahan kata daerah, jadi merek dasar Madrid perlu uraian jasa yang hati-hati di kelas 39 |
| K2 | ×2 | 1 | 2 | Lihat v2 K2 |
| K1a | ×2 | 1 | 2 | Satu masalah: koda "r" (gugus "rg") yang memicu vokal sisipan di Jepang dan Korea |
| K3 | ×1 | 2 | 2 | Lihat v2 K3 |
| K4a | ×1 | 2 | 2 | Menopang pilihan rute (jalan) dan pemahaman alasan (sebab), serta logo |
| K9 | ×1 | 1 | 1 | パマルガ dan 파마르가 memakai vokal sisipan; 파마 = keriting rambut di Korea |
| K5 | ×1 | 2 | 2 | Lihat v2 K5 |
| **Total** | | | **16** | Gerbang: G1 tidak, G3 tidak, G4 sementara tidak (K1a = 1), G5 tidak (K8 = 1) |

- [INFERENSI] Perbaikan bunyi dari PAMARGI berhasil (g keras), tetapi harganya dua hal: nama kini berupa kata Bali yang
  bermakna (bukan kata ciptaan), dan akar "marga" jauh lebih padat di basis data merek Indonesia daripada "margi"
  (88 data MARGA lawan 4 data MARGI di PDKI).
- [INFERENSI] "Marga" sebagai klan Batak adalah identitas budaya yang dihormati. Nama PAMARGA tidak memakai kata itu
  secara utuh, jadi risikonya rendah, tetapi uji asosiasi pada penutur Batak tetap disarankan.
- [INFERENSI] Nama badan usaha "Pamarga" di bidang air minum (kelas 32) tidak menghalangi merek di kelas 9 dan 42,
  tetapi mengurangi keunikan hasil pencarian di Indonesia.

#### 3.9 Verdict PAMARGA
**Cadangan kuat, lanjut bersyarat.** Syaratnya: (1) uji dengar-eja lulus, termasuk penutur Inggris non-rotik yang
mungkin menulis "Pamaga"; (2) penutur Bali mengonfirmasi bahwa "pamarga" (andap) wajar dipakai sebagai merek; (3)
penutur Persia dan Urdu menilai asosiasi "marg" tidak dominan; (4) konsultan KI menilai posisi PAMARGA terhadap JASA
MARGA (merek BUMN yang terkenal) di kelas 39 dan 42 serta terhadap permohonan MARGA di AS; (5) pola handle cadangan
seperti @pamargaapp.

### Gaps
- Bidang usaha PT. Pamarga Indonesia (Balikpapan) tidak ditemukan; data AHU tidak dicek. Kegiatan terkini Pamarga
  Investments SL berbeda antar-sumber.
- Madura, Kanton, dan Hokkien belum diperiksa. Penutur Persia, Urdu, Batak, dan Bali belum ditanya.
- WIPO hanya membaca 58 sampai 82 kartu teratas. Merek LA CAMARGA (1997) belum dicek uraian jasanya.

---

## 4. PAMARGI (pembanding)

### Takeaway
Basis data yang belum pernah dipakai untuk PAMARGI (USPTO, TMview, serta strategi Phonetic dan Fuzzy di WIPO) tidak
menemukan merek identik, dan semua domain yang dicek riset sebelumnya masih tanpa catatan RDAP. Namun PAMARGI **tidak
bertahan baik secara internasional**. Di luar Indonesia, huruf "gi" dibaca lunak (bahasa Inggris membacanya seperti nama
"Margie", /ˈmɑːrdʒi/), dan koda "r" memicu vokal sisipan di Jepang dan Korea. Dengan daftar K1a v3, itu dua masalah,
jadi K1a = 0 dan **G4 berlaku sementara** sampai uji dengar-eja membuktikan sebaliknya. USPTO memuat MARGIE (kelas 9,
38, 42; AlertMD, Inc.), yang bunyinya dekat dengan bacaan Inggris "pa-MAR-jee". Arti Bali "perjalanan" sama persis
dengan kata kategori di kalimat merek pemilik ("platform perjalanan gunung"), sehingga merek dasar Indonesia rawan
dinilai deskriptif di kelas 39. Skor: **v2 9 dari 12** (turun dari 10) dan **v3 14 dari 26 dengan G4 sementara**.
Verdict: **tertahan**. PAMARGI tetap layak untuk nama yang hanya berfokus Indonesia, tetapi bukan pilihan pertama untuk
arah internasional.

### Cited Findings

#### 4.1 Domain: cek ulang dan perbandingan dengan temuan sebelumnya
| Domain | Riset sebelumnya | Hari ini | Jam UTC hari ini |
|---|---|---|---|
| [pamargi.com](https://rdap.verisign.com/com/v1/domain/pamargi.com), [.app](https://pubapi.registry.google/rdap/domain/pamargi.app), [.id](https://rdap.pandi.id/rdap/domain/pamargi.id) | 404 (13.15, 14.35, 15.27 UTC) | 404 | 16.48 sampai 16.49 |
| [pamargi.co.id](https://rdap.pandi.id/rdap/domain/pamargi.co.id), pamargi.my.id, pamargi.web.id | 404 (13.27 UTC) | 404 | 16.49 |
| [getpamargi.com](https://rdap.verisign.com/com/v1/domain/getpamargi.com), [pamargiapp.com](https://rdap.verisign.com/com/v1/domain/pamargiapp.com) | 404 (15.27 sampai 15.42 UTC) | 404 | 16.48 |
| pamargi.net, pamargi.org | 404 (13.45 UTC) | 404 | 16.48 |
| pamargigunung.id, pamargigunung.com | 404 (13.27 UTC) | 404 | 16.48 sampai 16.49 |
| pemargi.com, pemargi.app, pamargy.com, pamargy.app | 404 (13.45 UTC) | 404 | 16.48 |
| pemargi.id, pamargy.id | 404 (13.45 UTC) | 404 (diulang dengan curl setelah PANDI memutus koneksi) | 18.02 |

- [FAKTA] Tidak ada perubahan status sejak cek pertama riset sebelumnya. Kontrol sama dengan 2.1.

#### 4.2 Merek dagang
**PDKI**
- [FAKTA] Mode Normal (UI 18.06 UTC; aksi halaman 18.10 UTC): pamargi, pemargi, pamargy, dan "pa margi" masing-masing 0
  data ([PDKI pamargi](https://pdki-indonesia.dgip.go.id/search?type=trademark&keyword=pamargi&page=1)).
- [FAKTA] Saran Fonetik (18.06 UTC): AMARI, PAMAR, Amari, AMAGI, MARGI, QAMARI, PAHARI, PASARI, YAMARI, Jamari. Daftar ini
  berbeda dari daftar pukul 13.29 sampai 13.34 UTC di riset sebelumnya (MARGI, AMARI, PAMAR, AMAGI, PAMIRI, YAMAGI, Kamari,
  PAPARI, NAMARI); mesin saran PDKI tidak deterministik.
- [FAKTA] Tetangga di kelas relevan (18.12 sampai 18.15 UTC):
  - YAMARI, (TM) Didaftar, kelas 9, YAMARI INDUSTRIES, LIMITED (JP), R002007011461, sampai 2027-12-15.
  - Amari HomeKitchen, kelas 35, Didaftar, pemilik perorangan, JID2020067581, sampai 2030-11-05.
  - PAMAR kelas 41 dan 42, Sandvik Intellectual Property AB (SE), kedaluwarsa 2024-08-29; AMARI kelas 39 (kedaluwarsa
    2015); AMARI HOTELS AND RESORTS kelas 42 (ORCHID LODGE CO., LTD, kedaluwarsa 2005); QAMARI dan PASARI kelas 35
    kedaluwarsa. MARGI (4 data) hanya di kelas 3, 8, dan 30.

**WIPO Global Brand Database**
- [FAKTA] Embedded, 18.48 UTC: "No results found" (sama dengan riset sebelumnya pukul 13.35 UTC).
- [FAKTA] Phonetic, 18.41 UTC: 1.278 hasil; 82 kartu teratas terbaca. Paling mirip: PAMARI (AS, Twin-Star International,
  Inc., kelas 11 dan 20, Registered 2018-06-26, 5503773), Pamari dan PAMARI (Jerman, Expired), PaMaRIZ (Jerman, kelas 41,
  Expired), PhaMar(ie) (Jerman, kelas 43), Pamara (Jerman dan UK, kelas 8 dan 35).
- [FAKTA] Fuzzy, 18.42 UTC: 1.756 hasil; 89 kartu terbaca. Paling mirip: **AMARGI** (Jerman, kelas 24, 41, 43,
  Registered 2020-07-07, 302020108252, pemilik perorangan), CAMARGI (Italia, kelas 33), PAMAGRI (Meksiko, kelas 14),
  PAMANGI (Italia, kelas 3, 5, 18, 31, 35), PACMARGIN (Malaysia, kelas 38, Expired). Merek aktif kelas 9 atau 42 yang
  terbaca hanya berkemiripan rendah (TAMARAI, India, kelas 42; PHAMORE, Viet Nam).

**TMview** (18.30 UTC)
- [FAKTA] Is: 0. Contains: 0. Fuzzy: 16 hasil (BR 1, CN 1, DE 3, IT 3, MX 2, KR 1, ES 1, TR 2, US 2), antara lain amargi
  (Turki, kelas 37 dan 42, Filed 2024-09-15), AMARGI (Jerman, kelas 24, 41, 43), PAMARI (AS), PAMANGI (Italia).

**USPTO** (18.55 sampai 18.56 UTC)
- [FAKTA] Frasa persis: pamargi 0 hasil.
- [FAKTA] Fuzzy hidup: 111 hasil; paling mirip PAMARI (kelas 11 dan 20, RN 5503773; indeks menandai kelasnya
  "(CANCELLED)" walau status hidup, PERLU KLARIFIKASI di TSDR), AMARI (kelas 5, 20, 25, 36, 41), AMAGI (kelas 35, 38, 42,
  Amagi Corporation, RN 8184102, terdaftar 2026-03-24).
- [FAKTA] Wildcard `*margi*` hidup: 183 hasil, termasuk **MARGIE** (AlertMD, Inc., SN 88807995, RN 6264253, terdaftar
  2021-02-09; kelas 9 "Downloadable chatbot software for enabling patients to communicate with their healthcare
  providers ...", kelas 38, kelas 42 "Cloud computing featuring software for use in patient communication ...") dan MARGIN
  (kelas 9 dan 42, permohonan)
  ([USPTO Trademark Search](https://tmsearch.uspto.gov/search/search-information)).

#### 4.3 Linguistik (K2)
**Tier A**
- [FAKTA] Bali (BASAbali, 18.39 UTC): "Pamargi", **Alus mider**: "way; procession; travel; how to walk" dan "good day";
  padanan Indonesia "prosesi; perjalanan; cara berjalan" dan "hari baik"; akar "margi"; bentuk lain antara lain Mamargi
  ("walk, go on") dan Ngamargiang ("run; perform; execute"); padanan tingkat andap "pajalan"
  ([BASAbali: Pamargi](https://dictionary.basabali.org/w/index.php?title=Pamargi&uselang=en)).
- [FAKTA] Pemakaian nyata (cuplikan Yahoo! JAPAN, 18.41 sampai 18.45 UTC):
  - sekuler resmi: "Pamargi Bulan Bahasa Bali" di situs Desa Banjarasem, Buleleng; awig-awig desa adat "Pamargi-pamargi
    sane patut macihna swaran kulkul" di JDIH Pemprov Bali; Alkitab bahasa Bali Yeremia 26:13 "Ragane patut ngentosin
    pamargi miwah laksanan ragane" ([bible.com](https://www.bible.com/es/bible/302/JER.26.13));
  - upacara: "pamargi upacara" untuk ngaben, palebon, dan Usaba Dimel di Besakih
    ([Wikipedia ban: Usaba Dimel](https://ban.wikipedia.org/wiki/Usaba_Dimel)), serta "eedan pamargi" penobatan pedanda.
- [FAKTA] Jawa (KBJI, 18.38 UTC): "1 margi K jalan 2 margi K sebab"; "amargi K karena"; "pamargi" bukan lema
  ([KBJI: margi](https://kbji.kemendikdasmen.go.id/kata/margi)).
- [FAKTA] Sunda (SundaDigi, 19.00 UTC): "margi (l.), marga; nu mawi éta budak di rangkét, margi gaduh dosa" (margi = sebab,
  tingkat lemes) dan "margi (dial.Jtw) kpy jalan"; "pamargi" bukan entri. Wiktionary Sunda "margi" = "cause | because".
- [FAKTA] Indonesia (cermin KBBI) dan Melayu (PRPM): "pamargi" dan "margi" tidak ditemukan (18.38 UTC).
- [ISU TERBUKA] Madura: UNVERIFIED.
- [FAKTA] Inggris: "Margie" adalah bentuk akrab nama Margaret, dilafalkan "/ˈmɑɹ.d͡ʒi/ (American English) /ˈmɑː.d͡ʒi/
  (British English)" ([Behind the Name](https://www.behindthename.com/name/margie), 18.52 UTC). Urban Dictionary tanpa
  entri "pamargi"; entri "margi" berupa definisi nama orang (18.37 UTC).
- [FAKTA] Bunyi huruf g sebelum i: "[dʒ] in Italian ... [ʒ] in French and Portuguese ... [x] or [h] in Spanish"; Inggris
  "soft ⟨g⟩ is /dʒ/"; "In German, the g is mostly a hard g, also before e and i"; Belanda memakai /x/ dan /ɣ/. Penanda g
  keras sebelum e dan i adalah "gu" (Prancis, Spanyol) dan "gh" (Italia)
  ([Wikipedia: Hard and soft G](https://en.wikipedia.org/wiki/Hard_and_soft_G), 19.10 UTC).
- [FAKTA] Mandarin: Jakarta = 雅加达 (koda r hilang); 吉 = "auspicious; lucky" ([吉](https://en.wiktionary.org/wiki/%E5%90%89)).
  [INFERENSI] PAMARGI ≈ 帕马吉, bermakna netral sampai positif.

**Tier B**
- [FAKTA] Persia dan Urdu مرگ = "death" (18.50 UTC).
- [FAKTA] Urdu مرگی = "epilepsy" ([مرگی](https://en.wiktionary.org/wiki/%D9%85%D8%B1%DA%AF%DB%8C)); Hindi मिर्गी = "epilepsy"
  (18.50 UTC). [INFERENSI] Dalam aksara Urdu tanpa harakat, "margi" dan "mirgi" (epilepsi) ditulis sama, sehingga PAMARGI
  bila ditulis پامرگی memuat kata itu. Dalam aksara Latin risikonya kecil.
- [FAKTA] Hindi मार्ग = "path, road, route | way".
- [FAKTA] Bahasa Tier B lain: tidak ada entri Wiktionary untuk "pamargi" (404). UNVERIFIED.

#### 4.4 Aksara (K9)
- [FAKTA] Jakarta = ジャカルタ dan 자카르타 (19.04 UTC). [INFERENSI] PAMARGI ≈ パマルギ (4 mora, satu sisipan) dan 파마르기
  (4 suku, satu sisipan, diawali 파마 "a perm"); Mandarin ≈ 帕马吉.

#### 4.5 Toko aplikasi, perusahaan, web, kripto
- [FAKTA] App Store (18.03 sampai 18.06 UTC): "pamargi", "pamargy", dan "pemargi" tidak muncul sebagai judul atau penjual
  di kesepuluh storefront (hasil fuzzy pamargi: US 41, GB 39, AU 41, lainnya 0; kontrol lulus). Google Play (18.17 UTC):
  tidak ada judul yang memuat nama.
- [FAKTA] GitHub (18.42 UTC): 1 repositori bernama "pamargi" milik akun perorangan (tidak dicantumkan), 0 organisasi.
  CoinGecko (18.52 UTC): 0. Companies House UK: tanpa perusahaan. Wikipedia en 0; id 1 artikel yang memuat kata itu
  (biografi tokoh Bali). Product Hunt: "No products found for "pamargi"" (18.44 UTC). OpenCorporates dan Etherscan
  diblokir captcha.
- [FAKTA] Yahoo! JAPAN (18.41 sampai 18.42 UTC): kamus Bali, teks berbahasa Bali, dan nama pribadi; tidak ada aplikasi,
  perangkat lunak, atau badan usaha bernama Pamargi ([Yahoo! JAPAN](https://search.yahoo.co.jp/search?p=%22pamargi%22)).

#### 4.6 Handle (18.05 sampai 18.06 UTC; kontrol sama dengan 2.6)
| Handle | TikTok | Instagram | YouTube | X |
|---|---|---|---|---|
| @pamargi | ada, dorman (1 pengikut, 0 video, nama tampilan bawaan pola "user" + angka), sama dengan cek 13.37 UTC | "Profile isn't available" | 404 | "User Profile Not Found" |
| @pamargiapp | 10221 | "Profile isn't available" | 404 | "User Profile Not Found" |

### Inferences

#### 4.7 Skor rubrik v2 (maksimum 12)
| Kode | Skor | Dasar |
|---|---|---|
| K1 | 1 | g sebelum i dibaca lunak di Inggris, Prancis, Spanyol, Italia, Portugis ("Margie") |
| K2 | 1 | "marg" = kematian (Persia, Urdu); homograf Urdu "epilepsi"; register alus mider yang sering dipakai dalam teks upacara (perlu penutur Bali) |
| K3 | 2 | Tidak ada konotasi kecepatan, kompetisi, penaklukan, puncak, atau jaminan aman |
| K4 | 2 | Jalan dan sebab (Jawa krama, Sunda), perjalanan (Bali); menopang kedua kalimat tagline dan logo |
| K5 | 2 | Semua domain wajib dan fallback tanpa catatan RDAP |
| K6 | 1 | Tanpa merek identik; MARGIE (AS, kelas 9 dan 42) dekat dengan bacaan Inggris; AMARGI (Jerman, kelas 41); amargi (Turki, kelas 42, permohonan) |
| **Total** | **9** | Lolos semua gerbang v2. Turun dari 10 karena K6 (basis data baru) |

#### 4.8 Skor rubrik v3 (maksimum 26)
| Kode | Bobot | Skor | Nilai | Dasar |
|---|---|---|---|---|
| K6 | ×3 | 1 | 3 | Lihat v2 K6. Bukan G3: MARGIE berbeda secara keseluruhan dan bidangnya (chatbot pasien) jauh |
| K8 | ×2 | 1 | 2 | Sugestif di kelas 9 dan 42; di merek dasar Indonesia, terjemahan "pamargi (bahasa Bali) = perjalanan" sama dengan kata kategori produk dan rawan deskriptif di kelas 39. Bahasa Bali tidak dikenal pembeli AS, jadi fanciful di AS |
| K2 | ×2 | 1 | 2 | Lihat v2 K2 |
| K1a | ×2 | 0 | 0 | Dua masalah: g sebelum i, dan koda "r" (gugus "rg") yang memicu vokal sisipan |
| K3 | ×1 | 2 | 2 | Lihat v2 K3 |
| K4a | ×1 | 2 | 2 | Lihat v2 K4 |
| K9 | ×1 | 1 | 1 | パマルギ dan 파마르기 memakai vokal sisipan; 파마 = keriting rambut di Korea; 帕马吉 netral |
| K5 | ×1 | 2 | 2 | Lihat v2 K5 |
| **Total** | | | **14** | **G4 sementara** (K1a = 0, menunggu K1b). G1, G3, G5 tidak berlaku |

- [INFERENSI] Pada rubrik berfokus Indonesia, PAMARGI unggul karena pengguna Indonesia membaca "gi" keras. Pada rubrik
  internasional, dua sifat yang sama justru menjadi beban: ejaan yang dibaca lunak di hampir semua bahasa Roman dan di
  bahasa Inggris, serta makna "perjalanan" yang terlalu dekat dengan kategori produk di merek dasar Indonesia.
- [INFERENSI] Salah eja yang diperkirakan pada uji dengar-eja: "Pamargui" (Spanyol, Prancis), "Pamarghi" (Italia),
  "Pamargie" atau "Pamarji" (Inggris, bila penutur mendengar bacaan lunak). Munculnya salah eja kembar seperti ini
  menggugurkan nama menurut ambang protokol.
- [INFERENSI] Kepantasan register: bukti pemakaian menunjukkan "pamargi" dipakai di teks sekuler resmi (berita desa,
  awig-awig, Alkitab) dan juga dalam deskripsi upacara. Kata ini bukan istilah sakral, tetapi bernuansa hormat dan
  upacara. Kepantasannya untuk merek komersial tetap PERLU KLARIFIKASI penutur Bali.

#### 4.9 Verdict PAMARGI
**Tertahan (G4 sementara).** PAMARGI hanya bisa kembali bersaing bila uji dengar-eja pada penutur Inggris, Prancis,
Spanyol, dan Italia lulus tanpa salah eja kembar, dan konsultan KI menilai "perjalanan" tidak deskriptif untuk uraian
barang dan jasa yang akan diajukan. Untuk arah yang hanya Indonesia, kekuatan PAMARGI di riset sebelumnya tetap berlaku.

### Gaps
- Status "(CANCELLED)" pada kelas PAMARI di indeks USPTO belum dicek di TSDR.
- Madura belum diperiksa; penutur Bali, Persia, dan Urdu belum ditanya.
- Pendapat konsultan KI tentang daya deskriptif "pamargi" di kelas 9, 39, dan 42 belum ada.

---

## 5. Peringkat kelompok, checklist pending, dan skrip uji dengar-eja

### Takeaway
Dengan rubrik v3 (dasar penilaian untuk arah internasional), urutannya **TUMPALO 17, PAMARGA 16, PAMARGI 14 (G4
sementara)**. Dengan rubrik v2 urutannya terbalik: PAMARGA 10, PAMARGI 9, TUMPALO 8. Perbedaan itu datang dari bobot:
v3 memberi bobot besar pada kekuatan merek (K8) dan konflik (K6) serta menghitung koda konsonan, sedangkan v2 memberi
bobot sama pada kecocokan konsep, tempat PAMARGA dan PAMARGI unggul. Selisih TUMPALO dan PAMARGA hanya satu poin dan
bergantung pada dua hal yang belum diuji: tanggapan penutur Spanyol terhadap "palo", dan penilaian konsultan KI atas
kata Bali "pamarga". Tidak ada nama yang boleh dipakai untuk aset final sebelum checklist 5.4 butir 1 sampai 5 tuntas.

### Cited Findings

#### 5.1 Ringkasan skor dan gerbang
| Nama | v2 (maks 12) | v3 (maks 26) | K1a | K2 | K6 | K8 | Gerbang v3 | Verdict |
|---|---|---|---|---|---|---|---|---|
| TUMPALO | 8 | **17** | 1 | 1 | 1 | 2 | tidak ada | Lanjut bersyarat |
| PAMARGA | 10 | **16** | 1 | 1 | 1 | 1 | tidak ada | Cadangan kuat, lanjut bersyarat |
| PAMARGI | 9 | **14** | 0 | 1 | 1 | 1 | **G4 sementara** (menunggu K1b) | Tertahan |

- [FAKTA] Rincian dan bukti tiap skor ada di 2.8 sampai 2.9 (TUMPALO), 3.7 sampai 3.8 (PAMARGA), dan 4.7 sampai 4.8
  (PAMARGI). Skor provisional sebelumnya: TUMPALO 11, PAMARGA 10, PAMARGI 10 (`kandidat-dan-saringan.md` 3.3 dan 4.1).
- [FAKTA] Dasar bobot v3: "kegagalan hukum dan makna tidak bisa diperbaiki setelah peluncuran, sedangkan domain bisa
  diperbaiki lewat majemuk atau dibeli kemudian" (`lanskap-dan-strategi.md` §6), dan merek dasar Madrid di DJKI menjadi
  titik tunggal kegagalan selama 5 tahun (§3.1, Pasal 6(3) Protokol Madrid).
- [FAKTA] Ambang uji dengar-eja yang dipakai skrip 5.5 berasal dari `lanskap-dan-strategi.md` §2.4: per kelompok n = 8,
  eja persis paling banyak 1 salah dan tidak ada salah eja yang sama muncul dua kali; ucap ulang dapat dikenali minimal
  7 dari 8; ingatan tertunda 24 sampai 48 jam minimal 6 dari 8 (HIPOTESIS awal). Dengan aturan ini, nama yang sebenarnya
  salah dieja 30% orang hanya lulus 25,5% kali.

### Inferences

#### 5.2 Peringkat dan alasannya
1. **TUMPALO (v3 17).** Paling kuat sebagai merek (kata ciptaan, K8 = 2), paling bersih di kelas 9 dan 42, dan paling
   ramah aksara Jepang dan Korea. Kelemahannya ada di makna bagian nama ("palo" di Spanyol, "tumpal" = sampah di Jawa),
   makna budaya "penolak bala" yang tidak boleh dipakai dalam cerita merek, dan "Tupalo" di UE dan UK.
2. **PAMARGA (v3 16).** Paling kuat secara konsep (jalan dan sebab; "pamarga" Bali = cara, jalannya tindakan) dan lolos
   masalah g lunak. Tetapi ia kata Bali yang bermakna (daya pembeda turun), berbagi akar dengan JASA MARGA dan permohonan
   MARGA di AS, dan sudah dipakai sebagai nama badan usaha di Indonesia dan Spanyol.
3. **PAMARGI (v3 14, G4 sementara).** Tetap nama terbaik untuk pasar Indonesia saja, tetapi di pasar internasional
   bacaannya pecah (g lunak, "Margie"), ada MARGIE di kelas 9 dan 42 di AS, dan arti "perjalanan" terlalu dekat dengan
   kategori produk untuk merek dasar Indonesia.

- [INFERENSI] Pemecah seri v3 tidak diperlukan karena totalnya berbeda. Bila kelak seri, urutannya K8 (TUMPALO unggul),
  lalu tidak ada merek identik aktif di ID, AS, atau UE (ketiganya lolos), lalu tidak ada merek mirip di kelas 9 atau 42
  (TUMPALO: JUMPALA dan PUMPAL di AS; PAMARGA: JASA MARGA dan MARGA; PAMARGI: MARGIE), lalu K1b, lalu K5.
- [INFERENSI] Sensitivitas yang dapat membalik urutan 1 dan 2:
  - panel Spanyol menilai "tumpalo" memicu makna vulgar secara umum: K2 TUMPALO = 0, gugur G1, PAMARGA naik ke urutan 1;
  - konsultan KI menilai "pamarga" arbitrary (K8 = 2): PAMARGA 18, melewati TUMPALO 17;
  - K3 TUMPALO dinilai hanya dari bunyi nama (2): TUMPALO 18.
- [REKOMENDASI] Bawa TUMPALO dan PAMARGA bersama-sama ke uji dengar-eja dan ke konsultan KI. Sertakan PAMARGI di uji yang
  sama sebagai pembanding, karena hanya K1b yang bisa mencabut G4 sementara.

#### 5.3 Pembanding dengan ronde sebelumnya
- [INFERENSI] PAMARGI turun dari 12 (rubrik berfokus Indonesia) ke 10 (v2 saringan cepat) ke 9 (v2 terverifikasi) dan 14
  dari 26 (v3). Penyebabnya bukan temuan merek identik, melainkan pergeseran ukuran: rubrik internasional menghukum
  bacaan lunak "gi", koda "r", dan makna yang sama dengan kategori produk.

#### 5.4 Checklist pending
| No | Tindakan | Untuk nama | Menghalangi keputusan? | Pelaksana |
|---|---|---|---|---|
| 1 | Uji dengar-eja K1b sesuai skrip 5.5 | ketiganya | Ya (G4 PAMARGI; pemecah seri) | pemilik atau tim riset pengguna |
| 2 | Uji asosiasi penutur asli: Spanyol Meksiko dan Karibia ("palo"), Jawa ("tumpal" = sampah), Persia dan Urdu ("marg"), Bali (kepantasan "pamargi" alus mider dan "pamarga" andap), Batak ("marga"), Madura (ketiga nama), Mandarin (plesetan 怕落 dan pilihan aksara), Korea (파마) | sesuai kolom | Ya (K2, G1) | pemilik |
| 3 | Konsultan KI Indonesia: penelusuran PDKI menyeluruh termasuk penelusuran gambar logo "Jalur Berpos"; pendapat tertulis tentang TUMBAL (kelas 9) terhadap TUMPALO, JASA MARGA (kelas 9, 39, 42) terhadap PAMARGA, dan daya deskriptif "pamargi = perjalanan" serta "pamarga = prosedur" di kelas 9, 39, 42 | ketiganya | Ya (merek dasar Madrid) | konsultan KI terdaftar |
| 4 | Konsultan KI Eropa dan UK: "Tupalo" (EUTM 008562563, UK00908562563) terhadap TUMPALO; Pamara (EUTM 017071044) dan LA CAMARGA (ES M2096433) terhadap PAMARGA; AMARGI (DE 302020108252) terhadap PAMARGI | ketiganya | Ya bila UE atau UK masuk rencana Madrid | konsultan KI |
| 5 | Konsultan KI AS: MARGA (SN 99930248, masih permohonan) terhadap PAMARGA; MARGIE (RN 6264253) terhadap PAMARGI; JUMPALA (RN 6434108) dan PUMPAL (RN 2274346) terhadap TUMPALO; cek TSDR untuk status kelas PAMARI (RN 5503773) | ketiganya | Ya bila AS masuk rencana | konsultan KI AS |
| 6 | Penelusuran langsung di IPOS (Singapura) dan MyIPO (Malaysia); SG tidak ada di TMview | ketiganya | Ya bila SG atau MY ditunjuk | konsultan KI |
| 7 | Cek AHU untuk PT. Pamarga Indonesia dan PT. Pamarga Indo Jatim; cek OpenCorporates dan Etherscan di peramban biasa | PAMARGA (AHU), ketiganya | Tidak | peneliti |
| 8 | Pencarian web ulang dengan mesin yang disetel untuk Indonesia dan AS (google.co.id mode penyamaran): nama, nama + "app", nama + "gunung" | ketiganya | Tidak | peneliti |
| 9 | Setelah keputusan: konfirmasi harga dan ketersediaan domain di registrar terakreditasi **hanya saat siap langsung mendaftar** (hindari front-running); daftarkan .com, .app, .id, dan fallback | nama terpilih | Tidak | pemilik |
| 10 | Amankan pola handle @[nama]app, yang pada 18.05 sampai 18.06 UTC tidak ditemukan di TikTok, Instagram, YouTube, dan X untuk ketiga nama | nama terpilih | Tidak | pemilik |
| 11 | Pedoman cerita merek TUMPALO: pakai bentuk visual deret segitiga saja, jangan makna "penolak bala" atau perlindungan (R-012, R-014) | TUMPALO | Tidak, tetapi wajib bila dipilih | pemilik dan desainer |
| 12 | Governance record sesuai `PRD.md` §130, dicatat di §146 (R-021) sebelum nama dipakai | nama terpilih | Ya | pemilik |

#### 5.5 Skrip uji dengar-eja dan ingatan (K1b)
Skrip ini menerapkan protokol `lanskap-dan-strategi.md` §2.4 dan aturan fasilitator `docs/PROTOKOL-UJI-KEGUNAAN.md`
(kode peserta P__, rekaman hanya dengan izin, "yang diuji namanya, bukan Anda", jangan menuntun). Uji ini formatif:
hasilnya dipakai sebagai gerbang dan pembanding, bukan untuk melaporkan persentase (R-023).

**A. Peserta (ditetapkan sebelum uji)**
- Kelompok A, 8 penutur Indonesia dari minimal tiga latar daerah (misalnya Jawa, Sunda, Bali atau Nusa Tenggara,
  Sumatra).
- Kelompok B, 8 penutur Inggris (idealnya Australia, Inggris, Singapura, atau Malaysia; minimal 2 penutur non-rotik).
- Kelompok C, 8 penutur bahasa Roman dan Belanda: 2 Spanyol (utamakan Meksiko atau Karibia), 2 Prancis, 2 Italia atau
  Portugis, 2 Belanda. Kelompok ini menguji risiko khas kelompok 3 (g lunak, bunyi u, "palo").
- Bila sumber daya terbatas, kelompok C boleh 5 orang, tetapi hasilnya hanya saringan, bukan gerbang.
- Jangan libatkan tim atau orang yang sudah mengenal kandidat; jangan beri tahu nama yang disukai pemilik.

**B. Materi**
- Nama uji: TUMPALO, PAMARGA, PAMARGI. Kontrol: satu merek global yang dikenal (misalnya "Komoot") dan satu kata fiktif
  yang mudah (misalnya "Belano") untuk kalibrasi.
- Lafal yang dimaksud, dicatat sebelum merekam: TUMPALO [tum.pa.lo] dengan u seperti "tumpul"; PAMARGA [pa.mar.ga] dan
  PAMARGI [pa.mar.gi] dengan g keras seperti "gunung" dan r diucapkan.
- Rekaman dua suara (satu penutur Indonesia, satu penutur Inggris yang dilatih memakai lafal di atas), tiap nama dalam
  dua kalimat netral: "Saya cek jalurnya di ___." dan "I checked the trail on ___." Tiap klip diputar dua kali.
- Urutan nama diacak per peserta (misalnya bujur sangkar Latin), dengan kontrol disisipkan.

**C. Naskah fasilitator** (versi Inggris dipakai untuk kelompok B dan C)
1. Pembuka: "Terima kasih. Hari ini kami menguji beberapa nama produk. Yang diuji adalah namanya, bukan Anda. Tidak ada
   jawaban salah. Bolehkah suara Anda direkam untuk catatan kami? Rekaman hanya diberi kode peserta, tanpa nama."
2. Dengar lalu tulis: "Anda akan mendengar satu kalimat dua kali. Tulis nama yang Anda dengar sebagaimana menurut Anda
   ejaannya." Ulangi untuk semua nama. Jangan mengeja, jangan menunjukkan tulisan, jangan mengoreksi.
3. Ucap ulang: "Tolong ucapkan nama itu lagi." Rekam.
4. Asosiasi: "Nama ini mengingatkan Anda pada apa?" Lalu: "Dalam bahasa Anda, apakah nama ini atau sebagiannya punya arti
   lain, termasuk arti kasar, lucu, atau keagamaan?" Catat kata demi kata. Jangan menyebut arti yang dicurigai
   ("palo", "marg", "sampah", "Margie") kecuali peserta menyebutnya sendiri.
5. Baca lalu ucap: tunjukkan ketiga nama tertulis dalam urutan acak baru dan minta peserta membacanya keras-keras. Rekam.
   Langkah ini menangkap bacaan "gi" lunak dan bunyi u.
6. Distraksi 10 menit (tugas lain yang tidak berkaitan), lalu: "Nama apa saja yang tadi Anda dengar?" tanpa bantuan.
7. 24 sampai 48 jam kemudian, lewat pesan: "Tuliskan nama produk yang Anda ingat dari sesi kemarin."

**D. Lembar data per peserta**
`Kode P__ | Kelompok | Bahasa pertama | Nama | Ejaan tertulis | Cocok persis (Y/T) | Ucap ulang dikenali (Y/T, dua
penilai) | Bacaan dari tulisan (transkripsi kasar) | Asosiasi (kutipan) | Ingat 10 menit (Y/T) | Ingat 24 sampai 48 jam
(Y/T)`

**E. Aturan lulus per kelompok dan per nama (n = 8)**
- Eja persis: paling banyak 1 salah, dan tidak ada salah eja yang sama muncul dua kali.
- Ucap ulang dapat dikenali: minimal 7 dari 8.
- Ingatan tertunda: minimal 6 dari 8 (HIPOTESIS; kalibrasi dengan kontrol).
- Tambahan untuk PAMARGI [HIPOTESIS peneliti]: bila 2 atau lebih peserta kelompok B atau C membaca "gi" lunak pada langkah 5,
  masalah K1a terkonfirmasi walaupun ejaan dengar lulus.
- Asosiasi: satu saja kutipan makna kasar, vulgar, atau sakral dari penutur bahasa itu memicu pemeriksaan G1 dengan
  penutur tambahan.
- K1b lulus bila ketiga ambang pertama terpenuhi di kelompok A dan B, dan tidak gagal di kelompok C. G4 sementara PAMARGI
  dicabut hanya bila K1b lulus.

**F. Salah eja yang diperkirakan (didaftarkan sebelum uji agar tidak ditafsir belakangan)**
- TUMPALO: "Toumpalo" (Prancis), "Toempalo" (Belanda), "Tompalo", "Tumbalo", "Tumpallo".
- PAMARGA: "Pamaga" (penutur non-rotik), "Pamargha", "Pamarca", "Palmarga".
- PAMARGI: "Pamargui" (Spanyol, Prancis), "Pamarghi" (Italia), "Pamargie", "Pamarji", "Pamargy".
- Dasar: aturan huruf g dan penanda g keras ("gu", "gh") di [Wikipedia: Hard and soft G](https://en.wikipedia.org/wiki/Hard_and_soft_G)
  (19.10 UTC) dan lafal "Margie" di [Behind the Name](https://www.behindthename.com/name/margie) (18.52 UTC). Perkiraan
  bunyi u (Prancis "ou", Belanda "oe") adalah INFERENSI peneliti.

**G. Pencatatan**
- Simpan lembar jawaban dan rekaman sebagai bukti, dengan kode peserta saja. Laporkan hasil sebagai jumlah per kelompok
  (misalnya 7 dari 8), bukan persentase.

### Gaps
- Uji dengar-eja, uji asosiasi penutur, dan pendapat konsultan KI belum ada; ketiganya bisa mengubah urutan.
- Bobot v3 masih usulan (`lanskap-dan-strategi.md` §6 Gaps). Kelompok ini menunjukkan v2 dan v3 memberi urutan
  berbeda, jadi pilihan rubrik sendiri adalah keputusan pemilik.
- Tidak ada data harga domain, status premium, atau ketersediaan registrar (sengaja tidak dicek).
