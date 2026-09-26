# Tipografi MEMOUNT: keluarga teks + strategi angka (riset 25 Sep 2026)

_Semua sumber diakses 2026-09-25. Label tingkat bukti mengikuti R-023: temuan di "Cited Findings" = FACT (terukur atau bersumber); "Inferences" memuat INFERENCE / RECOMMENDATION yang diberi label; "Gaps" = OPEN ISSUE. Angka byte, metrik, fitur, dan lebar string di bawah **diukur sendiri** (bukan dikutip) dengan skrip stdlib Python/Node di scratchpad sesi (tidak masuk repo): parsing tabel TTF langsung (head, OS/2, hhea, hmtx, cmap, fvar, GSUB, post), dekode WOFF2 dengan Brotli bawaan Node v24, dan pengunduhan file dari Google Fonts CSS2 API dengan User-Agent Chrome Android 14. Render visual memakai Chrome DevTools (Chrome desktop Windows, emulasi viewport mobile). Itu bukan rasterisasi Android asli._

## 1. Kandidat keluarga teks: asal, lisensi, sumbu, metrik, fitur angka, disambiguasi, cakupan glyph, ukuran

### Takeaway
Di antara 24 keluarga teks (plus 12 monospace) yang diukur, hanya **Plus Jakarta Sans** (Tokotype) dan **Gantari** (Lafontype) yang terverifikasi buatan foundry berbasis Indonesia. Gantari gugur untuk UI data karena tidak punya `tnum` (angka tidak bisa rata) dan tidak punya glyph ↑. Plus Jakarta Sans lolos semua syarat teknis inti: OFL, variable wght 200–800, x-height 0,536, `tnum`/`case`, glyph ↑, dan subset latin 27,3 KB. Dua kelemahannya: tidak ada nol bergaris, dan I/l polos secara default (baru dibedakan lewat `ss01`). Figtree adalah file variable terkecil (30,4 KB latin+ext) dan punya set disambiguasi `ss02`. IBM Plex Sans dan Atkinson Hyperlegible Next paling kuat membedakan I/l/1 dan O/0 secara default, tetapi Plex mentok di wght 700 dan Atkinson tidak punya glyph panah.

### Cited Findings

#### Metode pengukuran (sumber data)
- Fakta keluarga (desainer, lisensi, tanggal, sumbu) dibaca dari `METADATA.pb` tiap direktori di repo google/fonts. TTF variable upstream diparse langsung, misalnya `ofl/plusjakartasans/PlusJakartaSans[wght].ttf` — [google/fonts repo](https://github.com/google/fonts/tree/main/ofl).
- Rank popularitas dan tanggal tambah berasal dari JSON katalog resmi Google Fonts (1.946 keluarga; field `popularity`, `trending`, `dateAdded`, `isBrandFont`; rank kecil = lebih populer) — [fonts.google.com/metadata/fonts](https://fonts.google.com/metadata/fonts).
- Byte WOFF2 = ukuran file yang disajikan Google Fonts CSS2 API untuk subset `latin` dan `latin-ext`, misalnya [css2?family=Plus+Jakarta+Sans:wght@200..800](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200..800) dengan UA Chrome Android. Sumbu selain wght dipatok ke default (opsz/wdth tidak diminta). Unicode-range subset `latin` GF: `U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD` (termasuk ↑ U+2191 dan ↓ U+2193) — [respons CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400).
- Lebar string pada wght 400 dihitung dari advance width (hmtx, tanpa kerning) instance statis 400 yang disajikan [CSS2 API](https://fonts.googleapis.com/css2?family=Figtree:wght@400) ke UA lama (TTF). Lebar `tnum` dihitung dengan menerapkan lookup GSUB `tnum` (single substitution) pada digit.

#### Tabel A — identitas, lisensi, sumbu, popularitas
| Keluarga | Desainer / foundry (asal terverifikasi) | Lisensi | Sumbu (min–default–max) | Rank GF (trending) | Masuk GF | Asosiasi merek | Sumber |
|---|---|---|---|---|---|---|---|
| Plus Jakarta Sans | Tokotype / Gumpita Rahayu. Tokotype adalah "type foundry based in Indonesia… operated by Gumpita Rahayu since 2015". Dipesan 6616 Studio untuk identitas program Pemprov DKI "+Jakarta City of Collaboration" (2020). | OFL | wght 200–400–800 | #39 (#583) | 2022-03-23 | Identitas kota Jakarta | [DESCRIPTION](https://raw.githubusercontent.com/google/fonts/main/ofl/plusjakartasans/DESCRIPTION.en_us.html), [bio Tokotype](https://raw.githubusercontent.com/google/fonts/main/catalog/designers/tokotype/bio.html), [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/plusjakartasans/METADATA.pb) |
| Gantari | Lafontype / Anugrah Pasau. Lafontype adalah "type foundry based in Indonesia. Started in 2016 by Anugrah Pasau". | OFL | wght 100–400–900 | #514 | 2022-05-25 | — | [bio Lafontype](https://raw.githubusercontent.com/google/fonts/main/catalog/designers/lafontype/bio.html), [DESCRIPTION](https://raw.githubusercontent.com/google/fonts/main/ofl/gantari/DESCRIPTION.en_us.html) |
| Geist | Kredit GF: Andrés Briganti, Mateo Zaragoza, Guillermo Rauch, Evil Rabbit, José Rago, Facundo Santana (Vercel) | OFL | wght 100–400–900 | #107 (#573) | 2024-10-01 | Vercel: "a typeface specifically designed for developers and designers" | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/geist/METADATA.pb), [vercel.com/font](https://vercel.com/font) |
| Inter | Rasmus Andersson | OFL | opsz 14–14–32, wght 100–400–900 | #5 (#876) | 2020-01-24 | Default "AI slop" menurut skill Anthropic v1 (lihat §2) | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/inter/METADATA.pb), [rsms.me/inter](https://rsms.me/inter/) |
| Figtree | Erik Kennedy | OFL | wght 300–300–900 | #41 (#956) | 2022-07-21 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/figtree/METADATA.pb) |
| Manrope | Mikhail Sharanda | OFL | wght 200–200–800 | #31 (#717) | 2019-10-02 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/manrope/METADATA.pb) |
| Onest | Dmitri Voloshin, Andrey Kudryavtsev | OFL | wght 100–400–900 | #174 | 2023-09-05 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/onest/METADATA.pb) |
| Rethink Sans | Hans Thiessen | OFL | wght 400–400–800 | #197 | 2023-09-05 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/rethinksans/METADATA.pb) |
| Hanken Grotesk | Alfredo Marco Pradil, Hanken Design Co. | OFL | wght 100–400–900 | #147 | 2022-11-16 | — | [DESCRIPTION](https://raw.githubusercontent.com/google/fonts/main/ofl/hankengrotesk/DESCRIPTION.en_us.html) |
| Instrument Sans | Rodrigo Fuenzalida, Jordan Egstad | OFL | wdth 75–100, wght 400–400–**700** | #127 | 2023-05-08 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/instrumentsans/METADATA.pb) |
| Public Sans | USWDS, Dan Williams, Pablo Impallari, Rodrigo Fuenzalida | OFL | wght 100–100–900 | #81 | 2019-06-07 | Pemerintah AS (USWDS) | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/publicsans/METADATA.pb) |
| Atkinson Hyperlegible Next | Braille Institute, Applied Design Works, Elliott Scott, Megan Eiswerth, Letters From Sweden | OFL | wght 200–400–800 | #420 (**#142**, naik cepat) | 2025-01-06 | Braille Institute / aksesibilitas | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/atkinsonhyperlegiblenext/METADATA.pb), [Braille Institute](https://www.brailleinstitute.org/freefont/) |
| Source Sans 3 | Paul D. Hunt | OFL | wght 200–200–900 | #49 | 2021-09-17 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/sourcesans3/METADATA.pb) |
| IBM Plex Sans | Mike Abbink, Bold Monday | OFL | wght 100–400–**700**, wdth 75–100 | #53 | 2018-03-11 | IBM | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/ibmplexsans/METADATA.pb) |
| Noto Sans | Google | OFL | wght 100–900, wdth 62.5–100 | #20 | 2013-02-27 | Google (isBrandFont=true) | [katalog](https://fonts.google.com/metadata/fonts) |
| Roboto | Christian Robertson, ParaType, Font Bureau | OFL | wght 100–900, wdth 75–100 | #2 | 2013-01-08 | Google/Android (isBrandFont=true) | [katalog](https://fonts.google.com/metadata/fonts) |
| Google Sans | Google | OFL | wght 400–**700**, opsz 17–18, GRAD −50–200 | #4 | 2025-12-09 menurut katalog; METADATA menulis 2017-06-05 (konflik) | Merek Google (isBrandFont=true) | [katalog](https://fonts.google.com/metadata/fonts), [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/googlesans/METADATA.pb) |
| Reddit Sans | Stephen Hutchings, OrangeRed | OFL | wght 200–400–900 | #474 | 2023-10-10 | Reddit | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/redditsans/METADATA.pb) |
| DM Sans | Colophon Foundry | OFL | opsz 9–40, wght 100–1000 | #21 | 2019-06-11 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/dmsans/METADATA.pb) |
| Be Vietnam Pro | Lâm Bảo, Tony Le, ViệtAnh Nguyễn (asal Asia Tenggara, Vietnam) | OFL | **statis saja** 100–900 (9 file) | #176 | 2021-06-13 | — | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/bevietnampro/METADATA.pb) |
| Lexend | Bonnie Shaver-Troup, Thomas Jockin, Santiago Orozco, Héctor Gómez, Superunion | OFL | wght 100–400–900 | #99 | 2021-03-08 | Klaim keterbacaan | [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/lexend/METADATA.pb) |

- Keluarga lain di katalog GF yang desainernya cocok dengan foundry Indonesia: **Mohave** (Gumpita Rahayu, display condensed, rank #1012). Juga **Playwrite ID** (TypeTogether, primaryLanguage `id_Latn`, tulisan tangan model sekolah Indonesia, bukan desainer Indonesia). Keduanya bukan kandidat teks UI — [katalog GF](https://fonts.google.com/metadata/fonts).

#### Tabel B — metrik, angka, fitur, cakupan (TTF upstream; lebar digit dalam em)
| Keluarga | x-height/UPM | cap/UPM | Digit default | Lebar `tnum` | `tnum` | `zero` | `case` | Disambiguasi (fitur upstream, observasi render) | ↑ U+2191 | Glyph |
|---|---|---|---|---|---|---|---|---|---|---|
| Plus Jakarta Sans | 0,536 | 0,745 | proporsional (1=0,371; 0=0,732) | 0,600 | ✓ | ✗ | ✓ | Default: I dan l batang polos (beda tinggi saja), 0 lebih sempit dari O tanpa garis. `ss01` = I berserif + l berekor. `ss03` = l berekor. Tidak ada opsi nol bergaris. | ✓ | 1.188 |
| Gantari | 0,470 | 0,668 | proporsional (1=0,389; 0=0,642) | **tidak ada** | ✗ | ✗ | ✓ | Tidak ada alternatif | ✗ | 373 |
| Geist | 0,530 | 0,710 | proporsional (1=0,384; 0=0,663) | 0,600 | ✓ | ✗ | ✓ | Default l berekor. `ss05` I berserif. `ss09` nol bergaris. | ✓ | 975 |
| Inter | 0,546 | 0,728 | proporsional (1=0,407; 0=0,631) | 0,648 | ✓ | ✓ | ✓ | Default I/l polos. `cv05` l berekor, `cv08` I berserif, `zero` nol bergaris, `ss02` set disambiguasi. | ✓ | 2.933 |
| Figtree | 0,500 | 0,700 | proporsional | 0,623 | ✓ | ✗ | ✓ | Default I/l polos. `ss02` = I berserif + l berekor + nol bergaris. | ✓ | 459 |
| Manrope | 0,540 | 0,720 | proporsional | 0,620 | ✓ | ✗ | ✓ | Tidak ada alternatif I/l/0 | ✓ | 742 |
| Onest | 0,527 | 0,707 | proporsional | 0,672 | ✓ | ✗ | ✓ | Tidak terlihat | ✓ | 876 |
| Rethink Sans | 0,496 | 0,700 | **tabular default** (0,600), tanpa `pnum` | — | — | ✗ | ✓ | — | ✓ | 494 |
| Hanken Grotesk | 0,493 | 0,697 | tabular default (0,560), tanpa `pnum` | — | — | ✗ | ✓ | Default l berekor (render) | ✗ | 708 |
| Instrument Sans | 0,510 | 0,720 | proporsional | 0,600 | ✓ | ✗ | ✓ | ss01–ss12 ada, tidak diuji untuk I/l | ✓ | 501 |
| Public Sans | 0,517 | 0,723 | proporsional | **0,700** | ✓ | ✗ | ✗ | Default l berekor. `ss01` = l polos. | ✗ | 648 |
| Atkinson Hyperlegible Next | 0,496 | 0,668 | proporsional (1=0,407; 0=0,648) | 0,632 | ✓ | — | ✓ | Nol bergaris secara default. "1Iil: Clear uprights that are distinctly different". | ✗ | 392 |
| Source Sans 3 | 0,478 | 0,660 | tabular default (0,497 @400) | — | `pnum` | ✓ | ✓ | Default l berekor, `zero` | ✓ | 2.478 |
| IBM Plex Sans | 0,516 | 0,698 | tabular default (0,600), tanpa `pnum` | — | — | ✓ | ✗ | **Default I berserif + l berekor**, `zero` | ✓ | 1.025 |
| Noto Sans | 0,536 | 0,714 | tabular default (0,572) | ada `pnum` | ✓ | ✓ | ✓ | `zero` | ✗ | 4.515 |
| Roboto | 0,528 | 0,711 | tabular default (0,562) | — | ✓ | ✗ | ✗ | — | ✗ | 1.326 |
| Google Sans | 0,510 | 0,716 | proporsional | 0,600 | ✓ | ✓ | ✓ | `zero` | ✓ | 8.311 |
| Reddit Sans | 0,518 | 0,720 | proporsional | 0,608 | ✓ | ✓ | ✓ | Default l berekor. `ss03` I berserif, `zero`. | ✓ | 1.079 |
| DM Sans | 0,526 | 0,700 | proporsional (1=**0,312**; 0=0,684) | **tidak ada** | ✗ | ✗ | ✓ | — | ✓ | 486 |
| Be Vietnam Pro | 0,530 | 0,740 | proporsional | tidak ada | ✗ | ✗ | ✓ | — | ✗ | 527 |
| Lexend | 0,525 | 0,700 | proporsional | tidak ada | ✗ | ✓ | ✓ | `zero` | ✗ | 850 |

- Sumber Tabel B: TTF upstream per keluarga di [google/fonts/ofl](https://github.com/google/fonts/tree/main/ofl), diparse 2026-09-25. Nama fitur Inter: "tnum… Fixed-width numbers are useful for tabular data", "ss02 (Disambiguation)", "cv05 (Lower-case L with tail)", "cv08 (Upper-case i with serif)", "zero (Slashed Zero)" — [rsms.me/inter](https://rsms.me/inter/). Klaim diferensiasi Atkinson ("B8 O0", "1Iil", "ebgs… Large areas inside letters, called counters") — [Braille Institute](https://www.brailleinstitute.org/freefont/).
- Observasi render (upstream TTF, 24 px, Chrome desktop Windows, DPR 4) mengonfirmasi efek fitur di Tabel B. Contoh: Inter `cv05,cv08,zero` menampilkan I berserif, l berekor, dan nol bergaris. Plus Jakarta Sans `ss01` menampilkan I berserif dan l berekor. `ss02` Plus Jakarta Sans tidak mengubah I/l/0 — [file google/fonts yang dirender](https://github.com/google/fonts/tree/main/ofl).
- U+202F (narrow no-break space) tidak ada di Plus Jakarta Sans, Geist, Figtree, Manrope, Rethink, Hanken, Instrument, Public Sans, Atkinson Next, Gantari, DM Sans, Reddit Sans, Roboto, dan Google Sans. Glyph itu ada di Inter, Onest, Source Sans 3, IBM Plex Sans, dan Noto Sans. U+2009 (thin space) tidak ada di Geist, Figtree, Manrope, Hanken, Instrument, Public Sans, Rethink, Gantari, DM Sans, dan Be Vietnam Pro. Instrument Sans tidak punya ≈ ± ≤ ≥ — [TTF upstream](https://github.com/google/fonts/tree/main/ofl).
- Deskripsi desain Plus Jakarta Sans: terinspirasi "Neuzeit Grotesk, Futura, and 1930s grotesque sans serifs". X-height "slightly taller to provide clear spaces between caps and x-height". Dilengkapi "open counters and balanced spaces to preserve the legibility at a large range of sizes". Tiga stylistic set bernama Indonesia: "Lancip (Sharp), Lurus (Straight), and Lingkar (Swirl)" — [DESCRIPTION PJS](https://raw.githubusercontent.com/google/fonts/main/ofl/plusjakartasans/DESCRIPTION.en_us.html).
- Gantari: "originally designed for large sizes, but it also reads well at small sizes" — [DESCRIPTION Gantari](https://raw.githubusercontent.com/google/fonts/main/ofl/gantari/DESCRIPTION.en_us.html).
- Hinting (ada tabel `fpgm`): hanya IBM Plex Sans, Roboto, Be Vietnam Pro, IBM Plex Mono, dan DM Mono yang ber-hint. Semua variable font lain yang diukur tidak ber-hint — [TTF upstream](https://github.com/google/fonts/tree/main/ofl).

#### Tabel C — byte WOFF2 terukur (Google Fonts CSS2 API, upright, 2026-09-25)
| Keluarga | Variable `latin` | Variable `latin-ext` | Total | Statis 400 `latin` + `latin-ext` |
|---|---|---|---|---|
| Figtree | 20.184 | 10.228 | **30.412** | 11.396 + 6.148 |
| Manrope | 24.576 | 15.240 | 39.816 | 14.096 + 8.296 |
| Instrument Sans (wght 400–700) | 29.904 | 11.092 | 40.996 | 16.828 + 6.820 |
| Public Sans | 26.636 | 18.372 | 45.008 | 14.680 + 10.384 |
| Geist | 29.288 | 16.540 | 45.828 | 12.992 + 7.804 |
| **Plus Jakarta Sans** | **27.272** | 21.688 | 48.960 | 11.812 + 10.528 |
| Rethink Sans | 35.604 | 16.752 | 52.356 | 15.936 + 8.148 |
| Atkinson Hyperlegible Next | 34.024 | 19.076 | 53.100 | 12.056 + 7.320 |
| Hanken Grotesk | 34.664 | 19.572 | 54.236 | 13.416 + 8.956 |
| DM Sans | 36.980 | 18.192 | 55.172 | 14.092 + 7.316 |
| Google Sans (400–700) | 35.852 | 21.320 | 57.172 | 21.956 + 13.636 |
| Gantari | 40.560 | 17.764 | 58.324 | 10.636 + 5.732 |
| Roboto | 37.520 | 24.444 | 61.964 | 16.388 + 10.020 |
| Onest | 33.808 | 28.428 | 62.236 | 14.692 + 12.468 |
| IBM Plex Sans | 40.240 | 25.868 | 66.108 | 17.508 + 11.060 |
| Lexend | 39.692 | 34.548 | 74.240 | 14.496 + 13.052 |
| Reddit Sans | 41.744 | 34.192 | 75.936 | 17.072 + 14.248 |
| Source Sans 3 | 28.792 | 60.048 | 88.840 | 15.644 + 33.020 |
| Newsreader (serif judul saat ini, wght saja) | 58.152 | 36.328 | 94.480 | 22.464 + 14.208 |
| Inter (opsz dipatok) | 48.432 | 85.272 | 133.704 | 23.664 + 34.916 |
| Noto Sans | 35.856 | 168.144 | 204.000 | 13.092 + 55.576 |
| Be Vietnam Pro | — (statis saja) | — | — | 12.908 + 6.880 per bobot |

- Sumber: file WOFF2 yang dirujuk respons [Google Fonts CSS2 API](https://fonts.googleapis.com/css2?family=Figtree:wght@300..900), diunduh dan dihitung byte-nya 2026-09-25.
- Meminta rentang bobot lebih sempit, misalnya `wght@400..800`, **tidak** mengecilkan file untuk 26 dari 28 keluarga yang rentang penuhnya lebih lebar (byte identik). Pengecualian: Google Sans Flex (76.504 → 58.228) dan JetBrains Mono (55.684 → 42.936) — [CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800).
- Ukuran TTF variable upstream sebagai pembanding: Plus Jakarta Sans 176.288 B, Geist 169.056, Inter 876.576, Figtree 62.712, IBM Plex Sans 537.244, Noto Sans 2.049.096, Google Sans Flex 4.153.392 — [google/fonts](https://github.com/google/fonts/tree/main/ofl).

### Inferences
- [INFERENCE] Hanya dua kandidat yang lulus uji "buatan Indonesia": Plus Jakarta Sans (Tokotype) dan Gantari (Lafontype). Gantari tidak punya `tnum` dan tidak punya ↑, sehingga tidak cocok untuk UI yang padat pengukuran. Plus Jakarta Sans menjadi satu-satunya kandidat buatan Indonesia yang layak sebagai huruf UI+data.
- [INFERENCE] X-height Plus Jakarta Sans (0,536) setara Noto Sans dan hanya sedikit di bawah Inter (0,546). Nilainya di atas Figtree (0,500), Atkinson Next (0,496), Source Sans 3 (0,478), dan Gantari (0,470). Pada ukuran px yang sama, huruf kecil Plus Jakarta Sans tampil lebih besar daripada kebanyakan alternatif.
- [INFERENCE] Untuk hierarki kuat dengan wght 800, Instrument Sans, IBM Plex Sans, dan Google Sans gugur (maksimal 700). Plus Jakarta Sans (maks 800) persis menutup rentang 400–800 yang dipakai prototipe.
- [INFERENCE] DM Sans dan Gantari tidak bisa menyejajarkan angka. DM Sans sangat berisiko: "1" hanya 0,312 em, "0" 0,684 em. Keduanya tidak layak untuk kolom angka atau angka yang berubah hidup.
- [INFERENCE] Glyph ↑ hilang di Noto Sans, Roboto, Public Sans, Atkinson Next, Hanken, Gantari, dan Lexend. Di font-font itu, "↑1.650 m" dirender dengan glyph fallback sistem, sehingga bobot dan baseline berbeda.

### Gaps
- Kualitas render di Chrome Android pada perangkat low-DPI tidak diuji di perangkat nyata. Render di atas adalah Chrome desktop Windows (rasterizer berbeda). OPEN ISSUE: uji di satu HP Android kelas bawah (±720p) pada 14–16 px, luar ruangan.
- Aperture tidak diukur kuantitatif. Hanya ada klaim desainer ("open counters" PJS, "Large areas inside letters" Atkinson) dan render.
- Kebangsaan Rasmus Andersson, Erik Kennedy, Alfredo Marco Pradil, dan Hanken Design Co. tidak diverifikasi (tidak ditulis di sumber yang dibaca). Jangan dicantumkan.

## 2. Seberapa umum di UI buatan AI/templat, dan asosiasi merek (relevan untuk filter antislop)

### Takeaway
Ada bukti tertulis bahwa Inter, Roboto, Arial, dan Space Grotesk disebut Anthropic sendiri sebagai pilihan "AI-generated" yang overused. Geist dan Geist Mono adalah default templat `create-next-app` dan font situs shadcn/ui v4, serta dimiliki merek Vercel yang menyasar developer. Plus Jakarta Sans tidak ada di daftar tersebut, tetapi populer secara umum (rank GF #39, di atas Geist #107). "Tidak populer" bukan alasan merek yang bisa dipertahankan. Alasannya harus asal-usul dan karakter.

### Cited Findings
- Versi pertama skill `frontend-design` Anthropic (commit 62c3cbc, 2025-11-12) berbunyi: "Avoid generic fonts like Arial and Inter"; "NEVER use generic AI-generated aesthetics like overused font families (Inter, Roboto, Arial, system fonts)"; "NEVER converge on common choices (Space Grotesk, for example) across generations." — [SKILL.md @62c3cbc](https://raw.githubusercontent.com/anthropics/claude-code/62c3cbc47147ddbf666b210be13a91133e7d449e/plugins/frontend-design/skills/frontend-design/SKILL.md). Versi terkini (commit dbdd79c, 2026-09-02) tidak lagi menyebut nama keluarga. Isinya: "Choose your typefaces deliberately, not the default families you would reach for on any other project" — [SKILL.md main](https://raw.githubusercontent.com/anthropics/claude-code/main/plugins/frontend-design/skills/frontend-design/SKILL.md), [riwayat commit](https://api.github.com/repos/anthropics/claude-code/commits?path=plugins/frontend-design/skills/frontend-design/SKILL.md).
- Templat resmi `create-next-app` (branch canary) mengimpor `Geist` dan `Geist_Mono` di `app/layout.tsx` — [layout.tsx](https://raw.githubusercontent.com/vercel/next.js/canary/packages/create-next-app/templates/app-tw/ts/app/layout.tsx).
- Situs shadcn/ui v4 (`apps/v4/lib/fonts.ts`) memakai `Geist as FontSans`, `Geist` untuk heading, dan `Geist_Mono as FontMono` — [fonts.ts](https://raw.githubusercontent.com/shadcn-ui/ui/main/apps/v4/lib/fonts.ts).
- Vercel menyebut Geist "a typeface specifically designed for developers and designers". Vercel juga menulis bahwa mereka "began by creating a monospace version that prioritized readability and seamlessly integrated into coding environments" — [vercel.com/font](https://vercel.com/font).
- Web Almanac 2025 (terbit 15 Jan 2026): Inter dipakai 1,4–1,5% halaman dan disebut "one notable climber" karena "adoption by many modern frameworks and designers". Roboto 10–10,7%, Poppins 5,8–6,0%, Open Sans 5,0–5,6% — [Web Almanac 2025 Fonts](https://almanac.httparchive.org/en/2025/fonts).
- Rank popularitas katalog GF (2026-09-25): Roboto #2, Google Sans #4, Inter #5, Montserrat #7, Poppins #8, Noto Sans #20, DM Sans #21, Manrope #31, Plus Jakarta Sans #39, Figtree #41, JetBrains Mono #48, Space Grotesk #62, Geist #107, Geist Mono #148, Fira Code #207, Atkinson Hyperlegible Next #420, Gantari #514 — [katalog GF](https://fonts.google.com/metadata/fonts).
- Keluarga "brand font" di katalog GF (isBrandFont=true): Roboto, Noto Sans, Google Sans, Google Sans Flex, Roboto Mono, Google Sans Code — [katalog GF](https://fonts.google.com/metadata/fonts).

### Inferences
- [INFERENCE] Geist + Geist Mono memikul dua asosiasi sekaligus. Pertama, merek Vercel yang ditujukan ke developer. Kedua, default templat Next.js/shadcn yang menjadi basis banyak UI hasil generator AI. Keduanya bertentangan dengan karakter "platform pendakian Indonesia untuk pemula", dan pilihan ini sudah ditandai filter antislop proyek.
- [INFERENCE] Plus Jakarta Sans lolos filter antislop bukan karena jarang dipakai (ia lebih populer dari Geist). Ia lolos karena punya alasan karakter yang dapat ditulis: buatan foundry Indonesia, lahir dari identitas sipil Jakarta, dan stylistic set bernama Indonesia (Lancip/Lurus/Lingkar). Satu kelemahan naratif: asalnya urban (Jakarta), bukan pegunungan.
- [RECOMMENDATION] Tulis alasan merek berbasis fakta asal-usul dan fungsi (x-height tinggi, counter terbuka, `tnum`, glyph ↑), bukan klaim "unik".

### Gaps
- Tidak ditemukan data kuantitatif tentang frekuensi Plus Jakarta Sans, Figtree, atau Manrope di output generator AI (v0, Lovable, Bolt). Klaim "populer di templat" untuk keluarga itu tidak terverifikasi.

## 3. Strategi angka: `tnum` dalam keluarga teks vs monospace terpisah; kandidat monospace

### Takeaway
Tidak ada studi yang langsung membandingkan angka tabular-proporsional dengan digit monospace pada dashboard. Literatur membaca menunjukkan huruf proporsional lebih cepat pada ukuran normal, dan fixed-pitch hanya unggul mendekati batas ketajaman atau untuk low vision dan disleksia. Pengukuran kami: string "9,7 km · ↑1.650 m · 7–8 j" dalam monospace 33–37% lebih lebar daripada Plus Jakarta Sans/Geist ber-`tnum`. `tnum` sendiri hanya menambah lebar 1,6% (Plus Jakarta Sans) sampai 4,9% (Inter/Figtree); Manrope 5,3% dan Onest 7,7%. `tnum` tetap mencegah "jitter" pada angka yang berubah. `tnum` bertahan di file Google Fonts/Bunny. `zero`, `ss`, `cv`, dan `case` dibuang. Monospace hanya perlu untuk kode alfanumerik yang rawan tertukar.

### Cited Findings
- Arditi, Knoblauch, Grunwald (1990): "variable pitch to yield better performance at medium and large character sizes and fixed pitch to be superior for character sizes approaching the acuity limit". Keunggulan proporsional berasal dari "greater horizontal compression and consequently reduced eye-movement requirements" — [JOSA A, doi 10.1364/josaa.7.002011](https://doi.org/10.1364/josaa.7.002011).
- Mansfield, Legge, Bane (1996), MNREAD Times vs Courier, 50 subjek normal dan 42 low vision: "Maximum reading speeds for normal subjects were 5% faster with Times than with Courier". Pada low vision, "10% slower with Times than with Courier". Untuk cetakan lebih kecil dari critical print size, Times bisa "slower (by as much as 50%)" — [Psychophysics of Reading XV, via OpenAlex/PubMed](https://pubmed.ncbi.nlm.nih.gov/?term=Psychophysics+of+reading+XV+font+effects+in+normal+and+low+vision).
- Rello & Baeza-Yates (2013), 48 pembaca disleksia, eye-tracking, 12 font: "Sans serif, monospaced and roman font styles significantly improved the reading performance over serif, proportional and italic fonts" — [ASSETS '13, doi 10.1145/2513383.2513447](https://doi.org/10.1145/2513383.2513447).
- Beier, Bernard, Castet (2018), keterbacaan digit di penglihatan perifer dalam string tiga digit: "the digit '1' should have a narrow design without a crossbar at the bottom, the digits '3' and '9' should benefit from open apertures, and the digit '7' should have a straight leg" — [DRS 2018, doi 10.21606/drs.2018.246](https://doi.org/10.21606/drs.2018.246).
- GOV.UK Design System: "When comparing columns of numbers, align the numbers to the right in table cells" (kelas `govuk-table__cell--numeric`) — [GOV.UK Table](https://design-system.service.gov.uk/components/table/).
- Dokumentasi Inter: `tnum` "Fixed-width numbers are useful for tabular data, where comparing columns across rows is desired" — [rsms.me/inter](https://rsms.me/inter/).
- **Pengukuran lebar string pada wght 400, tanpa kerning** (em; px pada 15 px; persentase dari lebar konten 328 px di viewport 360 px dengan gutter 16 px). Sumber: instance statis 400 dari [CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400).

| Font | "9,7 km · ↑1.650 m · 7–8 j" proporsional | dengan `tnum` | px @15 | % dari 328 px |
|---|---|---|---|---|
| Plus Jakarta Sans | 10,748 | 10,921 | 163,8 | 49,9% |
| Instrument Sans | 10,587 | 10,901 | 163,5 | 49,8% |
| Google Sans | 10,424 | 10,878 | 163,2 | 49,8% |
| Manrope | 10,467 | 11,024 | 165,4 | 50,4% |
| Reddit Sans | 10,605 | 11,042 | 165,6 | 50,5% |
| Geist | 10,972 | 11,261 | 168,9 | 51,5% |
| Figtree | 10,826 | 11,361 | 170,4 | 52,0% |
| Onest | 11,113 | 11,970 | 179,6 | 54,8% |
| Inter | 11,620 | 12,186 | 182,8 | 55,7% |
| Geist Mono / IBM Plex Mono / Red Hat Mono | 15,000 | — | 225,0 | 68,6% |
| Atkinson Hyperlegible Mono (tanpa ↑) | 15,168 | — | 227,5 | 69,4% |
| Martian Mono (wdth default 112,5) | 18,750 | — | 281,3 | 85,8% |

- "15.06 WIB": Plus Jakarta Sans 4,705 em vs monospace 0,6 em/karakter 5,400 em (+15%) — [pengukuran, CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400).
- Uji DOM di Chrome dengan file WOFF2 dari Google Fonts (10×"1" pada 100 px) membuktikan `tnum` aktif. Lebar per digit berubah: Plus Jakarta Sans 0,371→0,600 em, Geist 0,348→0,600, Inter 0,407→0,648, Figtree 0,413→0,623, Public Sans 0,407→0,700, Onest 0,363→0,672, Atkinson Next 0,407→0,632 — [CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400).
- **Fitur yang dibuang penyaji font** (tabel GSUB dari WOFF2 `latin` statis 400 yang didekode). Inter menyisakan `calt, frac, pnum, tnum`: `case`, `zero`, ss01–ss08, dan cv01–cv14 hilang. Plus Jakarta Sans menyisakan `calt, frac, pnum, tnum`: `case` dan ss01–ss03 hilang. Geist menyisakan `frac, pnum, tnum`. IBM Plex Sans hanya `frac` (`zero` hilang). Red Hat Mono `calt, frac`, Geist Mono `frac`, Reddit Sans `frac, pnum, tnum`. Pola sama pada request `&text=` — [Google Fonts CSS2 API](https://fonts.googleapis.com/css2?family=Inter:wght@400).
- Bunny Fonts (CDN yang dipakai kode saat ini menurut brief; kode tidak diperiksa) menyajikan Plus Jakarta Sans sebagai **file statis per bobot**: `latin-400` 11.816 B, `latin-800` 11.896 B, tanpa `fvar`. GSUB-nya `calt, ccmp, dnom, frac, liga, locl, numr, pnum, tnum`, jadi `case` dan ss01–ss03 juga hilang. Newsreader `latin-600` 23.876 B — [fonts.bunny.net CSS](https://fonts.bunny.net/css?family=plus-jakarta-sans:400).

#### Tabel D — kandidat monospace (bukan default yang ditandai, plus pembanding)
| Mono | Desainer | Lisensi | Sumbu | Variable latin + latin-ext (B) | Statis 400 total (B) | Lebar/karakter | `zero` | ↑ | Rank GF |
|---|---|---|---|---|---|---|---|---|---|
| Atkinson Hyperlegible Mono | Braille Institute dkk. | OFL | wght 200–800 | 17.740 + 10.728 = **28.468** | 16.524 | 0,632 em | ✓ (nol bergaris default) | ✗ | #1205 |
| Red Hat Mono | MCKL | OFL | wght 300–700 | 22.392 + 11.736 = 34.128 | 15.396 | 0,600 | ✓ | ✓ (→ ≈ ≤ ≥ tidak ada) | #614 |
| Martian Mono | Roman Shamin, Evil Martians | OFL | wdth 75–112.5, wght 100–800 | 23.544 + 15.884 = 39.428 | 17.828 | 0,750 (x-height 0,600) | ✗ | ✓ | #772 |
| Azeret Mono | Displaay, Martin Vácha | OFL | wght 100–900 | 26.216 + 14.732 = 40.948 | 17.520 | 0,650 | ✓ | ✓ | #490 |
| IBM Plex Mono | Mike Abbink, Bold Monday | OFL | statis 100–700 (7 file) | — | 18.912 per bobot | 0,600 | ✓ | ✓ | #84 |
| DM Mono | Colophon Foundry | OFL | statis 300/400/500 | — | 14.388 per bobot | 0,600 | ✗ | ✗ | #154 |
| Ubuntu Sans Mono | Dalton Maag | **UFL 1.0** (bukan OFL) | wght 400–700 | 21.184 + 32.052 = 53.236 | 30.776 | 0,560 | ✗ | ✗ | #1518 |
| Reddit Mono | Stephen Hutchings, OrangeRed | OFL | wght 200–900 | 30.548 + 29.652 = 60.200 | 25.640 | 0,5625 | ✓ | ✓ | #1064 |
| Spline Sans Mono | Eben Sorkin, Mirko Velimirović | OFL | wght 300–700 | 36.528 + 20.748 = 57.276 | 21.336 | 0,600 | ✓ | ✗ | #621 |
| _Geist Mono (ditandai)_ | Vercel | OFL | wght 100–900 | 23.108 + 14.712 = 37.820 | 16.856 | 0,600 | ✗ (ss09) | ✓ | #148 |
| _JetBrains Mono (ditandai)_ | JetBrains dkk. | OFL | wght 100–800 | 40.480 + 15.204 = 55.684 | 28.456 | 0,600 | ✓ | ✓ | #48 |
| Roboto Mono | Christian Robertson | OFL | wght 100–700 | 32.752 + 22.868 = 55.620 | 22.276 | 0,600 | ✗ (tanpa GSUB) | ✗ | #16 |

- Sumber Tabel D: [google/fonts](https://github.com/google/fonts/tree/main/ofl) (Ubuntu Sans Mono di [ufl/](https://github.com/google/fonts/tree/main/ufl/ubuntusansmono)), [CSS2 API](https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Mono:wght@200..800), dan [katalog GF](https://fonts.google.com/metadata/fonts). Commit Mono tidak ada di katalog GF.
- Render: I berserif dan "1" berkaki pada IBM Plex Mono, Red Hat Mono, Geist Mono, dan Atkinson Hyperlegible Mono. Nol bergaris default pada Atkinson Hyperlegible Mono — [render dari file GF](https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Mono:wght@400).

### Inferences
- [INFERENCE] Untuk token angka pendek, seperti jarak, elevasi, waktu, dan tanggal dalam kalimat atau baris ringkas, bukti bacaan mengarah ke huruf proporsional: lebih rapat, fiksasi mata lebih sedikit (Arditi; Mansfield untuk pembaca normal). `tnum` sudah memberi apa yang dibutuhkan data, yaitu lebar digit seragam untuk kolom rata kanan dan angka yang berubah hidup, dengan biaya lebar hanya 1,6–4,9% pada Plus Jakarta Sans, Geist, Inter, dan Figtree (Onest 7,7%).
- [INFERENCE] Monospace untuk **semua** pengukuran (prototipe) menambah 33–37% lebar horizontal pada layar 360 px. Pada 15 px, string pengukuran memakan ±69% lebar konten, sehingga risiko wrap dan truncation naik saat teks diperbesar 200% (WCAG 1.4.4) atau reflow 320 px (WCAG 1.4.10). Pemisah Indonesia ("1.650", "9,7", "15.06") juga menjadi satu sel penuh sehingga tampak renggang.
- [INFERENCE] "1" berkaki yang lazim pada monospace bertentangan dengan temuan Beier dkk. (2018) bahwa "1" tanpa palang bawah lebih mudah dikenali saat berdesakan di penglihatan perifer. Relevansinya terbatas karena studinya soal perifer.
- [INFERENCE] Kesan "instrumen" bisa dicapai tanpa monospace: `font-variant-numeric: tabular-nums` pada semua angka ukur, satuan konsisten dengan spasi, label kapital kecil ber-tracking, rata kanan di tabel (GOV.UK), dan bobot angka sedikit lebih berat dari teks.
- [RECOMMENDATION] Pakai monospace hanya untuk kode alfanumerik yang wajib dibaca per karakter (kode booking/izin, koordinat). Pilih yang bukan default AI: **Atkinson Hyperlegible Mono** (nol bergaris default, I/l/1 dibedakan, OFL, 9.996 B untuk `latin` statis 400). Muat hanya di layar yang membutuhkannya. Alternatif: Red Hat Mono (punya ↑ dan `zero`, tetapi `zero` dibuang GF sehingga perlu subset upstream).
- [RECOMMENDATION] Jangan mengandalkan `zero`/`ss01`/`case` jika font diambil dari Google Fonts atau Bunny, karena fitur itu terbukti dibuang. Jika perlu (misalnya `ss01` Plus Jakarta Sans untuk I/l pada kode), subset sendiri dari TTF upstream dengan subsetter yang mempertahankan fitur (langkah build satu kali, bukan dependensi runtime; alat dan instalasinya perlu persetujuan pemilik).

### Gaps
- Tidak ditemukan studi empiris yang membandingkan kecepatan atau akurasi membaca digit tabular-proporsional dengan digit monospace di dashboard atau tabel data. Query OpenAlex untuk topik ini tidak relevan.
- Pedoman Apple/Material tentang "monospaced digits" untuk angka yang berubah tidak berhasil diambil (tidak ada WebSearch). Tidak dikutip.
- Commit Mono (OFL, GitHub) tidak diukur karena tidak ada di katalog GF.

## 4. Riset keterbacaan untuk konteks luar ruangan/mobile

### Takeaway
Bukti terkuat untuk "sekilas baca": huruf humanis dengan bentuk terbuka mengalahkan grotesk kotak, dan polaritas gelap-di-atas-terang lebih mudah dibaca (lebih kuat saat cahaya terang). Ambang keterbacaan naik linier dengan usia. Ketebalan huruf membantu pada sudut pandang kecil, tetapi bobot ekstrem (sangat tipis atau sangat tebal) menurunkan pengenalan. Font khusus disleksia tidak terbukti membantu, sedangkan jarak huruf lebar terbukti membantu. Klaim riset Atkinson Hyperlegible tidak ditemukan dalam publikasi peer-review.

### Cited Findings
- Legge & Bigelow (2011): rentang cetak fasih adalah "approximately 0.2° to 2°" (x-height). Pada jarak 40 cm itu setara x-height 1,4–14 mm — [J Vision, doi 10.1167/11.5.8](https://doi.org/10.1167/11.5.8).
- Definisi piksel referensi CSS: "about 0.0213 degrees" per px pada jarak lengan 28 inci; "1px thus corresponds to about 0.26 mm" — [CSS Values 4 (WD 12 Mar 2024)](https://www.w3.org/TR/css-values-4/).
- Reimer dkk. (2014), simulator mengemudi: tipe humanis "resulted in a 10.6% reduction in total glance time" (pria) dibanding grotesk kotak. "Error rates… were 3.1% lower for the humanist typeface" — [Ergonomics, doi 10.1080/00140139.2014.940000](https://doi.org/10.1080/00140139.2014.940000).
- Dobres, Chahine, Reimer, Gould (2016): "humanist-style type is significantly more legible than a square grotesque style, and that black-on-white text is significantly more legible than white-on-black". Tipe huruf "interacted significantly with age" — [Ergonomics, doi 10.1080/00140139.2015.1137637](https://doi.org/10.1080/00140139.2015.1137637).
- Dobres dkk. (2015): teks 3 mm kurang terbaca dibanding 4 mm, "especially pronounced for the square grotesque". "Legibility thresholds were also found to increase linearly with age, more than doubling across the age range studied" — [Driving Assessment 2015, doi 10.17077/drivingassessment.1605](https://doi.org/10.17077/drivingassessment.1605).
- Dobres, Chahine, Reimer (2017), iluminasi ambien: ada "positive polarity advantage". Ambang tertinggi (terburuk) pada polaritas negatif di iluminasi gelap. "Negative polarity is more sensitive to text size than positive polarity" — [Applied Ergonomics, PubMed 28166901](https://pubmed.ncbi.nlm.nih.gov/28166901/), [doi 10.1016/j.apergo.2016.11.001](https://doi.org/10.1016/j.apergo.2016.11.001).
- Dobres, Reimer, Chahine (2016), bobot huruf: "under suboptimal rendering, the lightest weight typeface renders poorly and is associated with markedly degraded legibility". Pada render optimal, bobot lebih ringan justru lebih baik — [AutomotiveUI '16, doi 10.1145/3003715.3005454](https://doi.org/10.1145/3003715.3005454).
- Beier & Oderkerk (2019): "a positive effect of boldness at small visual angles which did not occur at large visual angles". Ada "a drop-off at the lightest and the heaviest extremes at all tested font sizes" — [Acta Psychologica, doi 10.1016/j.actpsy.2019.102904](https://doi.org/10.1016/j.actpsy.2019.102904).
- Sawyer, Dobres, Chahine, Reimer (2017): ada "significant penalties for the smaller size". "Lowercase and condensed width text also decreased performance, especially when presented at a smaller size" — [HFES 2017, doi 10.1177/1541931213601698](https://doi.org/10.1177/1541931213601698).
- Minakata & Beier (2021): font Ultra Condensed mengubah pola gerak mata, tetapi "no reading-speed differences were observed" — [Applied Ergonomics, doi 10.1016/j.apergo.2021.103523](https://doi.org/10.1016/j.apergo.2021.103523).
- Wallace dkk. (2022), ratusan partisipan crowdsourced: kecepatan baca "increased by 35% when comparing fastest and slowest fonts". "One font does not fit all" — [ACM TOCHI, doi 10.1145/3502222](https://doi.org/10.1145/3502222).
- Beier & Larson (2010): "narrow letters benefit from being widened", dan huruf x-height "benefit from using more of the ascending and descending area" — [Information Design Journal, doi 10.1075/idj.18.2.03bei](https://doi.org/10.1075/idj.18.2.03bei).
- Beier (2009): paparan berulang segera memengaruhi kecepatan baca. Fitur huruf tak lazim hanya memengaruhi preferensi, bukan kecepatan — [RCA, Typeface Legibility: Towards defining familiarity](https://api.openalex.org/works?search=Typeface%20Legibility%20Towards%20defining%20familiarity).
- Pembaca lanjut usia: Bernard, Liao, Mills (2001) menemukan "14-point fonts were more legible to read than 12-point fonts" — [CHI '01 EA, doi 10.1145/634067.634173](https://doi.org/10.1145/634067.634173). Tinjauan sistematis Hou dkk. (2022): "older adults preferred larger font sizes. However, there exists a critical size at which readability declines" — [Frontiers in Psychology, doi 10.3389/fpsyg.2022.931646](https://doi.org/10.3389/fpsyg.2022.931646).
- Disleksia: jarak huruf ekstra lebar "substantially improved text reading performance" (Zorzi dkk. 2012) — [PNAS, doi 10.1073/pnas.1205566109](https://doi.org/10.1073/pnas.1205566109). OpenDyslexic menunjukkan "no improvement in reading rate or accuracy" (Wery & Diliberto) — [Annals of Dyslexia, doi 10.1007/s11881-016-0127-1](https://doi.org/10.1007/s11881-016-0127-1). Font Dyslexie "neither benefits nor impedes" (Kuster dkk. 2017) — [Annals of Dyslexia, doi 10.1007/s11881-017-0154-6](https://doi.org/10.1007/s11881-017-0154-6).
- Atkinson Hyperlegible: laman Braille Institute (Hyperlegible 2019, Next dan Mono 2025, 7 bobot + variable, 150+ bahasa) tidak memuat metodologi uji, peneliti, maupun hasil kuantitatif. Isinya hanya satu testimoni — [Braille Institute](https://www.brailleinstitute.org/freefont/). Query OpenAlex "Atkinson Hyperlegible font legibility" tidak menemukan studi yang menguji font ini — [OpenAlex](https://api.openalex.org/works?search=Atkinson%20Hyperlegible%20font%20legibility).
- WCAG 2.2 (Rekomendasi W3C, 12 Des 2024) mensyaratkan kontras 4,5:1, atau 3:1 untuk "large scale" = "at least 18 point or 14 point bold". Ketentuan lain: 1.4.12 (line-height 1,5×, paragraf 2×, letter-spacing 0,12×, word-spacing 0,16×), 1.4.4 (200%), dan 1.4.10 (320 CSS px) — [WCAG 2.2](https://www.w3.org/TR/WCAG22/).

### Inferences
- [INFERENCE, hitungan] Dengan piksel referensi 0,0213°/px, x-height Plus Jakarta Sans (0,536) = 0,160° pada 14 px, 0,183° pada 16 px, dan 0,206° pada 18 px. Geist: 0,158° dan 0,181°. Inter: 0,163° dan 0,186°. Figtree: 0,149° dan 0,170°. Atkinson Next: 0,148° dan 0,169°. Source Sans 3: 0,143° dan 0,163°. Artinya teks isi 14–16 px berada di bawah atau di tepi batas bawah rentang fasih Legge & Bigelow (0,2°). HP dipegang lebih dekat dari 28 inci sehingga sudut nyatanya lebih besar, tetapi silau, lelah, dan presbiopia menambah kebutuhan ukuran. Untuk teks isi, pakai ≥16 px. Hindari 12 px untuk informasi keputusan.
- [INFERENCE] Kandidat dengan x-height tinggi (Plus Jakarta Sans, Inter, Manrope, Noto Sans, Geist) memberi margin terbesar pada px yang sama. Atkinson Next, Figtree, Source Sans 3, dan Gantari perlu ±1 px lebih besar untuk x-height setara.
- [INFERENCE] Untuk luar ruangan terang: pertahankan teks gelap di atas latar terang sebagai mode utama (keunggulan polaritas positif), bobot isi 400–500, dan angka penting 600–700. Jangan pakai 200–300 untuk informasi apa pun (render buruk pada bobot tipis). Batasi 800 untuk judul besar, karena bobot ekstrem menurunkan pengenalan dan manfaat tebal hilang pada sudut pandang besar.
- [INFERENCE] Studi humanis vs grotesk kotak tidak langsung memeringkat kandidat di sini. Tidak ada kandidat bergaya grotesk kotak ala Eurostile, dan klasifikasi aperture tiap kandidat tidak diukur.
- [INFERENCE] Karena "one font does not fit all" (Wallace 2022) dan keakraban memengaruhi kecepatan (Beier 2009), klaim satu font "paling terbaca" untuk semua orang tidak dapat dipertahankan. Uji kegunaan dengan pengguna nyata lebih menentukan.

### Gaps
- Studi keterbacaan di bawah sinar matahari langsung pada layar ponsel (lux luar ruangan) tidak ditemukan. Dobres 2017 memakai iluminasi "daylight-like" di lab. Rincian lux dan nilai ms tidak terbaca karena PDF gagal dirender (tanpa poppler).
- Tidak ada data spesifik sarung tangan/kelelahan terhadap tipografi.
- Riset peer-review tentang Atkinson Hyperlegible (Next) tidak ditemukan. Klaim keterbacaannya berstatus klaim vendor.

## 5. Pertimbangan bahasa Indonesia dan pilihan tipe produk digital Indonesia

### Takeaway
Bahasa Indonesia hanya butuh 26 huruf dasar (tanpa diakritik wajib), sehingga subset `latin` GF sudah cukup dan `latin-ext` hanya terunduh bila ada nama asing. Format angka CLDR `id` (desimal koma, ribuan titik, jam "HH.mm") cocok dengan contoh brief. Kata panjang dan label kapital memakan lebar ±109–137 px pada 15 px. Merek konsumen besar Indonesia memakai typeface merek sendiri atau berlisensi (BCA Sans, Buka Sans, Godwit, Maison Neue, Open Sauce One). Tidak satu pun dari yang dipindai memakai Geist.

### Cited Findings
- CLDR `id`: exemplarCharacters `[a b c d e f g h i j k l m n o p q r s t u v w x y z]`. Auxiliary berisi `á à ă â å ä ã ā æ ç é è ĕ ê ë ē … ñ ó ò ŏ ô ö ø ō œ ú ù ŭ û ü ū ÿ` — [CLDR characters id](https://raw.githubusercontent.com/unicode-org/cldr-json/main/cldr-json/cldr-misc-full/main/id/characters.json).
- CLDR `id`: decimal `,`, group `.`, pola `#,##0.###`. timeFormats short `HH.mm`, medium `HH.mm.ss`. dateFormats short `dd/MM/yy`, medium `d MMM y` — [CLDR numbers id](https://raw.githubusercontent.com/unicode-org/cldr-json/main/cldr-json/cldr-numbers-full/main/id/numbers.json), [CLDR ca-gregorian id](https://raw.githubusercontent.com/unicode-org/cldr-json/main/cldr-json/cldr-dates-full/main/id/ca-gregorian.json).
- **Lebar string Indonesia pada wght 400, 15 px** (dihitung dari em, tanpa kerning dan tanpa tracking), dari [instance 400 CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400):

| Font | "Pemberitahuan" | "Kecocokan" | "PERLU PERSIAPAN" |
|---|---|---|---|
| Plus Jakarta Sans | 109,0 px | 82,1 px | 131,7 px |
| Geist | 106,5 | 77,4 | 135,1 |
| Inter | 107,0 | 79,5 | 135,6 |
| Figtree | 101,3 | 74,3 | 129,9 |
| Manrope | 106,5 | 76,7 | 126,4 |
| Public Sans | 106,5 | 77,4 | 137,3 |
| Atkinson Hyperlegible Next | 100,4 | 72,0 | 128,7 |
| Source Sans 3 | 97,7 | 69,3 | 116,3 |
| IBM Plex Sans | 103,5 | 74,1 | 129,7 |
| Be Vietnam Pro | 112,2 | 83,4 | 137,8 |
| Geist Mono / mono 0,6 em | 117,0 | 81,0 | 135,0 |

- Font yang dideklarasikan situs produk Indonesia (HTML + ≤10 CSS tertaut, UA Chrome Android, 2026-09-25):
  - Tokopedia: `Open Sauce One` (OpenSauceOne-Regular/ExtraBold.woff2) — [tokopedia.com](https://www.tokopedia.com/).
  - Gojek: `Maison Neue` dan `Maison Neue Extended` (plus `Fira Mono`) — [gojek.com/id-id](https://www.gojek.com/id-id).
  - Traveloka: `Godwit` (GodwitText Regular–ExtraBold) — [traveloka.com/id-id](https://www.traveloka.com/id-id).
  - BCA: `BCASans` — [bca.co.id](https://www.bca.co.id/id/individu).
  - Bukalapak: `Buka Sans Text` dan `Buka Sans Display` — [bukalapak.com](https://www.bukalapak.com/).
  - Kitabisa: `Rubik` — [kitabisa.com](https://kitabisa.com/).
  - Grab Indonesia (situs WordPress): meminta Google Fonts `Roboto` dan `Roboto Slab`, serta merujuk file `Inter-Regular.woff2` dan `InterDisplay-Medium.woff2` — [grab.com/id](https://www.grab.com/id/).
  - indonesia.go.id: stack diawali `Avenir` tanpa @font-face (jatuh ke font sistem di Android) — [indonesia.go.id](https://indonesia.go.id/).
  - Pembanding global pendakian: AllTrails memakai `AllTrailsAeonik` — [alltrails.com](https://www.alltrails.com/).
  - Blibli dan Jago mengembalikan HTTP 403; DANA timeout. Ketiganya tidak terverifikasi.

### Inferences
- [INFERENCE] Karena 26 huruf dasar dan seluruh auxiliary Latin-1 berada di subset `latin` GF, UI Indonesia hampir selalu hanya mengunduh file `latin`. `latin-ext` dengan `unicode-range` baru terunduh bila muncul ă/ā/ē/ō dan sejenisnya, misalnya pada nama. Biaya awal efektif Plus Jakarta Sans variable = 27,3 KB, bukan 49 KB.
- [INFERENCE] WCAG 1.4.12 (letter-spacing 0,12 em) menambah 13 × 1,8 px = 23,4 px pada "Pemberitahuan" (15 px), sehingga Plus Jakarta Sans menjadi ±132 px. Tab, chip, dan tombol dengan label Indonesia harus lulus uji ini tanpa terpotong.
- [INFERENCE] Label kapital pendek seperti "PERLU PERSIAPAN" (label fit publik R-004) sejalan dengan temuan Sawyer 2017 bahwa huruf kecil dan teks condensed menurunkan kinerja baca sekilas. Pakai kapital untuk label status pendek, bukan untuk kalimat.
- [INFERENCE] Pola industri lokal menunjukkan merek konsumen besar Indonesia berinvestasi pada typeface khas. Memakai Plus Jakarta Sans (asal Indonesia, sipil) memberi diferensiasi naratif murah. Memakai Geist membuat MEMOUNT terlihat seperti produk templat developer.
- [RECOMMENDATION] Format semua angka lewat `Intl.NumberFormat('id-ID')` dan `Intl.DateTimeFormat('id-ID')` (tanpa dependensi baru) agar pemisah konsisten dengan CLDR.

### Gaps
- Font aplikasi native (Android/iOS) produk-produk tersebut tidak diperiksa. Hanya situs web yang dipindai; CSS-in-JS dan bundel JS tidak dipindai.
- Desainer atau foundry Open Sauce One, Godwit, BCASans, dan Buka Sans tidak diverifikasi.
- Statistik panjang kata rata-rata bahasa Indonesia tidak ditemukan (tanpa WebSearch). Yang tersedia hanya lebar string contoh.

## 6. Performa: ukuran subset, font-display, preload, metrik fallback anti-CLS

### Takeaway
Satu file variable `latin` Plus Jakarta Sans (27,3 KB) lebih murah dari tiga bobot statis (±35 KB) dan jauh lebih murah dari kombinasi prototipe Geist + Geist Mono (52,4 KB `latin`). Menyempitkan rentang bobot lewat GF API tidak menghemat byte. Fallback dapat disetel dengan `size-adjust` ±104,6% dan `ascent-override` ±99%. Descriptor metrik didukung Chrome Android ≥87/92, tetapi `ascent-override` belum dirilis di Safari.

### Cited Findings
- Titik impas statis vs variable (Plus Jakarta Sans, `latin`): statis 400 = 11.812 B (GF) atau 11.816 B (Bunny) per bobot, variable 200–800 = 27.272 B. Variable lebih murah mulai 3 bobot — [CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200..800), [Bunny](https://fonts.bunny.net/css?family=plus-jakarta-sans:400).
- Kombinasi prototipe (`latin` saja): Geist 29.288 + Geist Mono 23.108 = 52.396 B. Plus Jakarta Sans saja 27.272 B (−48%) — [CSS2 API](https://fonts.googleapis.com/css2?family=Geist:wght@100..900).
- **Override fallback terhitung** (rata-rata lebar per karakter dari paragraf contoh Indonesia; fallback Roboto 400 = 0,4576 em/karakter, Arial = 0,4590). Rumus Chrome: `size-adjust = avgWidth(web)/avgWidth(fallback)`, `ascent-override = ascent/(UPM × size-adjust)`, dan seterusnya — [developer.chrome.com/blog/font-fallbacks (2023-02-10)](https://developer.chrome.com/blog/font-fallbacks):

| Web font | vs Roboto: size-adjust / ascent / descent / line-gap | vs Arial |
|---|---|---|
| Plus Jakarta Sans | 104,6% / 99,2% / 21,2% / 0% | 104,3% / 99,5% / 21,3% / 0% |
| Geist | 104,8% / 95,9% / 28,1% / 0% | 104,5% / 96,2% / 28,2% / 0% |
| Inter | 106,6% / 90,9% / 22,6% / 0% | 106,2% / 91,2% / 22,7% / 0% |
| Figtree | 99,6% / 95,4% / 25,1% / 0% | 99,3% / 95,7% / 25,2% / 0% |
| Atkinson Hyperlegible Next | 99,5% / 98,9% / 31,8% / 0% | 99,2% / 99,2% / 31,9% / 0% |
| Manrope | 102,9% / 103,6% / 29,2% / 0% | 102,5% / 104,0% / 29,3% / 0% |
| IBM Plex Sans | 101,2% / 101,3% / 27,2% / 0% | 100,9% / 101,6% / 27,3% / 0% |
| Source Sans 3 | 94,7% / 108,1% / 42,2% / 0% | 94,4% / 108,5% / 42,4% / 0% |

- Semua kandidat teks di atas menyetel USE_TYPO_METRICS dengan nilai typo = hhea (misalnya Plus Jakarta Sans 1038/−222/0 per 1000). Roboto tidak (typo 1536/−512/102, hhea 1900/−500/0 per 2048) — [TTF upstream](https://github.com/google/fonts/tree/main/ofl).
- Dukungan browser (MDN browser-compat-data, branch main): `size-adjust` Chrome/Android 92, Firefox 92, Safari/iOS 17. `ascent-override`/`descent-override`/`line-gap-override` Chrome/Android 87, Firefox 89, Safari "preview" (belum rilis). `font-display` Chrome 60, Safari 11.1, Samsung Internet 11.0. `font-variant-numeric` (`tabular-nums`, `slashed-zero`) Chrome 52, iOS Safari 9.1 — [BCD font-face.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/css/at-rules/font-face.json), [BCD font-variant-numeric.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/css/properties/font-variant-numeric.json).
- web.dev (diperbarui 2022-10-04): `font-display: optional` adalah "The most 'performant' approach", tetapi font web mungkin tidak tampil bila terlambat. `swap` "can be the preferred approach" dengan risiko pergeseran. Preload "is highly effective at making fonts discoverable early", tetapi "comes at the cost of taking away browser resources from the loading of other resources". Inline @font-face di `<head>` disarankan. WOFF2 "compresses 30% better than WOFF". Font variable menguntungkan situs yang butuh banyak bobot — [web.dev font best practices](https://web.dev/articles/font-best-practices).
- Web Almanac 2025: font self-host di 72% situs; WOFF2 ±65% request font; font variable di 41,3% situs mobile; `font-display: swap` 50,1% halaman mobile; `preload` ±12,0% halaman desktop; ±64% situs memakai minimal satu font OFL — [Web Almanac 2025 Fonts](https://almanac.httparchive.org/en/2025/fonts).

### Inferences
- [RECOMMENDATION] Self-host satu file variable per subset (`latin` wajib, `latin-ext` dengan `unicode-range` agar lazy). Preload hanya file `latin` teks utama. Deklarasikan @font-face inline di `<head>`.
- [RECOMMENDATION] Pilih `font-display: swap` + fallback `local('Roboto')`/`local('Arial')` dengan override di atas. Bila situs sudah berjalan sebagai PWA dengan cache service worker, `optional` layak dipertimbangkan (tanpa pergeseran pada kunjungan ulang). Nilai override adalah estimasi berbasis sampel teks Indonesia dan harus diverifikasi visual.
- [INFERENCE] File dari GF/Bunny aman untuk `tnum` tetapi tidak untuk `ss01`/`zero`/`case`. Jika fitur itu dibutuhkan, sumber file harus TTF upstream yang di-subset sendiri, dan ukurannya perlu diukur ulang.
- [INFERENCE] Menghapus Newsreader dari halaman yang tidak membutuhkannya menghemat hingga 58,2 KB `latin` (variable) atau 23,9 KB per bobot (Bunny statis). Ini keputusan desain (serif judul), bukan keputusan performa semata.

### Gaps
- Ukuran WOFF2 hasil subset upstream sendiri (dengan `ss01`/`case` dipertahankan) belum diukur: fontTools tidak terpasang dan pemasangan paket tidak diizinkan.
- Apakah `local('Roboto')` dapat di-resolve di semua Chrome Android (termasuk ROM OEM seperti Samsung) belum diverifikasi.
- Metrik fallback belum diuji visual (CLS) di halaman MEMOUNT nyata.

## 7. Pilihan akhir yang disarankan (keluarga teks + strategi angka, dengan alasan tertulis)

### Takeaway
[RECOMMENDATION] **Plus Jakarta Sans variable (400–800) sebagai satu-satunya keluarga UI.** Semua pengukuran, tanggal, dan waktu memakai `tabular-nums` dari keluarga yang sama, tanpa monospace untuk pengukuran. Monospace (Atkinson Hyperlegible Mono) hanya dipakai, dan hanya dimuat, untuk kode alfanumerik yang dibaca per karakter. Alternatif yang sah bila pemilik mengutamakan disambiguasi bawaan dan byte minimal adalah Figtree dengan `ss02`. Figtree tidak punya cerita asal Indonesia.

### Cited Findings
- Asal dan cerita Plus Jakarta Sans: Tokotype, foundry berbasis di Indonesia, untuk identitas "+Jakarta City of Collaboration" 2020. Stylistic set bernama Lancip/Lurus/Lingkar. Dirilis "for public use under the SIL Open Font License" — [DESCRIPTION PJS](https://raw.githubusercontent.com/google/fonts/main/ofl/plusjakartasans/DESCRIPTION.en_us.html), [bio Tokotype](https://raw.githubusercontent.com/google/fonts/main/catalog/designers/tokotype/bio.html).
- Ringkasan teknis terukur Plus Jakarta Sans: wght 200–800; x-height 0,536; `tnum` (0,600 em) bertahan di file GF/Bunny; ↑ U+2191 ada dan tercakup subset `latin`; `latin` variable 27.272 B; fallback vs Roboto size-adjust 104,6% — [TTF upstream](https://raw.githubusercontent.com/google/fonts/main/ofl/plusjakartasans/METADATA.pb), [CSS2 API](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200..800).
- Kelemahan terukur Plus Jakarta Sans: tidak ada `zero`. I/l polos secara default, dan baru dibedakan lewat `ss01`, yang dibuang GF/Bunny — [TTF upstream + dekode WOFF2](https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400).
- Figtree: variable terkecil (20.184 B `latin`, 30.412 B total); `ss02` = I berserif + l berekor + nol bergaris (upstream); `tnum` 0,623 em; x-height 0,500; wght 300–900; rank GF #41 — [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/figtree/METADATA.pb), [CSS2 API](https://fonts.googleapis.com/css2?family=Figtree:wght@300..900).
- Atkinson Hyperlegible Next: nol bergaris dan "1Iil" dibedakan secara default. Tidak ada glyph panah (↑ → ↓); x-height 0,496; rank #420, trending #142 — [Braille Institute](https://www.brailleinstitute.org/freefont/), [METADATA](https://raw.githubusercontent.com/google/fonts/main/ofl/atkinsonhyperlegiblenext/METADATA.pb).

### Inferences
- [RECOMMENDATION, alasan merek tertulis untuk antislop] "MEMOUNT memakai Plus Jakarta Sans karena typeface ini lahir dari foundry Indonesia (Tokotype) untuk identitas sipil kota. Ia adalah huruf milik publik Indonesia, bukan huruf merek perusahaan teknologi asing. Bentuk geometrisnya yang ber-x-height tinggi dan ber-counter terbuka tetap terbaca di layar kecil. Angka tabularnya membuat data jalur (jarak, elevasi, waktu) rata dan stabil tanpa kesan terminal." Klaim tentang Jakarta perlu dijaga jujur: asalnya kota, bukan gunung.
- [RECOMMENDATION, aturan angka] (1) `font-variant-numeric: tabular-nums` untuk semua angka ukur: jarak, elevasi, durasi, jam, tanggal, kolom tabel, dan angka yang berubah di mode pendakian. (2) Angka di dalam kalimat panjang boleh proporsional (default). (3) Rata kanan untuk kolom angka (GOV.UK). (4) Format lewat `Intl` `id-ID`. (5) Panah elevasi berupa ikon SVG dengan teks yang dapat dibaca pembaca layar (misalnya "naik 1.650 m"), bukan hanya glyph ↑, agar tidak bergantung pada cakupan glyph font atau fallback.
- [RECOMMENDATION, kode alfanumerik] Untuk kode booking/izin/koordinat, pilih salah satu: (a) Atkinson Hyperlegible Mono statis 400 (9.996 B `latin`), dimuat hanya di layar kode; (b) subset upstream Plus Jakarta Sans dengan `ss01` (I berserif + l berekor), tetapi tetap tanpa nol bergaris; atau (c) generator kode yang menghindari karakter ambigu (I/l/1, O/0).
- [INFERENCE, mengapa bukan Geist + Geist Mono] (1) Ditandai filter antislop dan terbukti menjadi default `create-next-app` dan situs shadcn. (2) Merek Vercel menyasar developer, dan Geist Mono lahir untuk "coding environments". (3) Monospace untuk semua pengukuran menambah 33–37% lebar dan membuat pemisah Indonesia renggang. (4) Pengunduhan `latin` 92% lebih besar dari Plus Jakarta Sans saja (52,4 vs 27,3 KB). (5) Bukti bacaan tidak mendukung fixed-pitch pada ukuran normal untuk pembaca umum.
- [INFERENCE, mengapa bukan Inter] Inter ditandai antislop dan disebut eksplisit oleh skill Anthropic v1. File `latin`-nya terberat di antara kandidat utama (48,4 KB variable; 23,7 KB statis 400), dan rank GF-nya #5. Keunggulan disambiguasinya (`cv05`/`cv08`/`zero`) juga dibuang oleh GF sehingga tetap perlu subset upstream.
- [INFERENCE, mengapa bukan Gantari meski buatan Indonesia] Gantari tidak punya `tnum` dan tidak punya panah, dan x-height-nya terkecil (0,470). Paling jauh ia hanya layak sebagai huruf display aksen, dan itu menambah byte.
- [OPEN ISSUE, butuh persetujuan pemilik] Perubahan tipografi adalah perubahan desain produk. Keputusan akhir, termasuk nasib Newsreader untuk judul dan monospace untuk kode, harus dicatat pemilik (DESIGN.md) sebelum implementasi.

### Gaps
- Belum ada uji kegunaan dengan pengguna Indonesia (pemula, 45+, luar ruangan) yang membandingkan Plus Jakarta Sans `tnum` dengan Geist Mono untuk membaca data jalur. Rekomendasi di atas berbasis literatur dan pengukuran, bukan hasil uji pengguna MEMOUNT (R-023: jangan klaim metrik UX).
- Render Android nyata dan ukuran subset upstream dengan fitur tambahan belum diukur (lihat §1 dan §6).
