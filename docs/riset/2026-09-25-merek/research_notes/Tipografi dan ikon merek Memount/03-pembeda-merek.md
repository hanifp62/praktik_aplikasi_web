# Pembeda merek: homogenisasi UI, aset merek distingtif, dan tipografi/ikon rujukan untuk MEMOUNT

Semua sumber diakses 2026-09-25 kecuali disebut lain. "Runtime" berarti pemeriksaan HTML/CSS situs langsung lewat `curl` pada 2026-09-25 (nama `font-family`, nama berkas font, kelas ikon). Itu bukti apa yang dimuat versi web hari itu, bukan bukti isi aplikasi native. Label R-023: butir di "Cited Findings" adalah FAKTA dari sumber; butir di "Inferences" diberi tag [INFERENSI], [HIPOTESIS], atau [REKOMENDASI]. Kuota WebSearch sesi habis di tengah riset, jadi sisa verifikasi memakai fetch URL langsung dan pemeriksaan runtime.

## 1. Homogenisasi UI akibat alat AI dan kit komponen (2024–2026): bukti, default yang paling dikenali, dan cara tim melawannya

### Takeaway
Bukti terkuat datang dari pembuat alatnya sendiri: pencipta Tailwind (Agustus 2025), shadcn (Desember 2025), dan Anthropic (blog November 2025, skill yang diperbarui September 2026). Diskursus sisanya berupa esai praktisi, bukan studi terukur. Per September 2026, "tampilan AI" tidak lagi sebatas Inter dan gradien ungu. Skill resmi Anthropic kini mendaftar lima klaster, termasuk "latar krem hangat (~#F4F1EA) + display serif kontras tinggi + aksen terakota" dan "monospace untuk label data kecil". Keduanya bersinggungan langsung dengan prototipe MEMOUNT (kanvas #F6F3EC, data Geist Mono) dan kode sekarang (judul serif Newsreader).

### Cited Findings

#### Pengakuan dari pembuat alat (sumber primer)
- Adam Wathan (pencipta Tailwind) menulis: "I'd like to formally apologize for making every button in Tailwind UI `bg-indigo-500` five years ago, leading to every AI generated UI on earth also being indigo." ID status didekode ke 2025-08-07T17:37Z. Teks diambil dari judul halaman hasil pencarian; halaman X tidak di-fetch langsung. — [X/@adamwathan](https://x.com/adamwathan/status/1953510802159219096)
- Changelog shadcn/ui Desember 2025 ("npx shadcn create"): "somewhere along the way, all apps started looking the same. I guess the defaults were a little _too_ good." dan "It's time to build something that doesn't look like everything else." Konfigurasi baru "doesn't just change colors, it rewrites the component code to match your setup. Fonts, spacing, structure, even the libraries you use, everything adapts to your preferences." — [shadcn changelog 2025-12](https://ui.shadcn.com/docs/changelog/2025-12-shadcn-create)
- shadcn mengumumkan lima gaya visual "designed to help you build something unique": Vega ("The classic shadcn/ui look"), Nova (spasi rapat), Maia ("Soft and rounded, with generous spacing"), Lyra ("Boxy and sharp. Pairs well with mono fonts"), Mira (padat). ID post didekode ke 2025-12-12. — [X/@shadcn](https://x.com/shadcn/status/1999530419125981676?lang=en)
- Pilihan `baseColor` shadcn kini mencakup neutral, stone, zinc, mauve, olive, mist, dan taupe, jadi netral hangat pun sudah menjadi opsi template. — [shadcn components.json docs](https://ui.shadcn.com/docs/components-json)
- Blog Anthropic, 12 November 2025, menamai masalahnya "distributional convergence": "During sampling, models predict tokens based on statistical patterns in training data. Safe design choices–those that work universally and offend no one–dominate web training data." Default yang disebut: font Inter, Roboto, Open Sans, Lato, Arial, dan font sistem; "purple gradients on white backgrounds"; mikro-interaksi yang tersebar. Alternatif yang dianjurkan: JetBrains Mono, Fira Code, Space Grotesk (estetika kode); Playfair Display, Crimson Pro (editorial); IBM Plex, Source Sans 3 (teknis); Bricolage Grotesque, **Newsreader** ("Distinctive"). Panduan tipografinya: "Use extremes: 100/200 weight vs 800/900, not 400 vs 600. Size jumps of 3x+, not 1.5x." — [Claude blog: Improving frontend design through Skills](https://claude.com/blog/improving-frontend-design-through-skills)
- SKILL.md `frontend-design` versi terkini (commit terakhir 2026-09-03) menulis: "For calibration, AI-generated design right now clusters around some traits:" — [anthropics/skills SKILL.md](https://github.com/anthropics/skills/blob/main/skills/frontend-design/SKILL.md) · [riwayat commit](https://api.github.com/repos/anthropics/skills/commits?path=skills/frontend-design/SKILL.md)
  1. "a warm cream background (near #F4F1EA) with a high-contrast serif display and a terracotta or warm-clay accent (often near #D97757 — Anthropic's own Claude-interaction accent, so on a user's brief it reads as a tell);"
  2. "a near-black background with a single bright acid-green or vermilion accent;"
  3. "a broadsheet-style layout with hairline rules, zero border-radius, and dense newspaper-like columns;"
  4. "the SaaS-card kit: content chopped into identical rounded cards, one border-radius on everything regardless of hierarchy, the same soft grey shadow (rgba(0,0,0,.1)) under each, and gradient washes as decoration;"
  5. "template chrome that appears whatever the subject: a tracked-out ALL-CAPS eyebrow label above every heading; meta strings joined with middle dots ('A · B · C'); labels built as 'WORD — fragment' with a spaced em dash; tinted near-black (#0B0B0B, #111) standing in for black; a monospace face for small data labels; a '→' appended to link and button text."
- Skill yang sama melanjutkan: "All traits are legitimate for some briefs, but they are defaults rather than choices, and they appear regardless of subject." Perlakuan tipografi yang harus dihindari: menonjolkan satu kata di headline, "Using all caps for labels", dan "Adding unnecessary typographic labels above content". Penanda bernomor (01/02/03) hanya pantas "if the content actually is a sequence". Gerak "fade-and-slide-up entrances on each section and hover transitions on every card are the generic default and read as AI-generated". Sumber keterbedaan: "The subject's industry, subject matter, materials, and vernacular are where distinctive visual choices come from". Prinsipnya: "Spend your boldness in one place." — [anthropics/skills SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)
- Adopsi skill: sekitar 149 ribu bintang (kemungkinan bintang repo secara keseluruhan) dan lebih dari 277.000 instalasi per Maret 2026. Skill itu melarang Inter, Roboto, Arial, dan Space Grotesk, dan dipandang sebagai rujukan utama untuk kerja UI. Ini sumber sekunder (blog, 26 Juni 2026). Konflik yang tercatat: blog Anthropic November 2025 justru menganjurkan Space Grotesk. — [Ruoqi Jin, Juni 2026](https://ruoqijin.com/blog/frontend-design-skills-ai-agents)

#### Katalog "tells" dari praktisi (sekunder, opini)
- "The Linear Look" (Alex Trost, sekitar awal 2024) mendaftar 12 ciri: latar gelap, glow buram berwarna, grid bento, screenshot alih-alih foto, garis sangat tipis, grafik gaya sirkuit, pola grid latar, sorotan border, glassmorphism, heading gradien halus, sans-serif, dan kesan "modern tech". Contohnya Linear, Vercel, Supabase, Raycast, AuthKit, Resend, dan Clerk: "Dozens of sites have put their own spin on it and now it's a certified trend". — [Frontend Horse](https://frontend.horse/articles/the-linear-look/)
- Artikel 25 Maret 2026 mendaftar: Inter atau system-ui, latar putih atau abu terang, radius 0.5rem, bayangan halus beropasitas rendah, grid fitur tiga kolom dengan ikon, hero dengan teks gradien, dan tombol CTA ungu. — [DEV Community, Alan West](https://dev.to/alanwest/why-every-ai-built-website-looks-the-same-blame-tailwinds-indigo-500-3h2p)
- Artikel 23 September 2026 mendaftar: Inter ("The Inter font, used everywhere, is not wrong, it is just the safest possible answer."), gradien indigo ke ungu, indigo-500, tiga kartu membulat berbayang lembut dengan ikon garis tipis yang disebut "interchangeable", dan headline "weightless" seperti "Build faster. Ship smarter." — [925Studios](https://www.925studios.co/blog/ai-slop-design-tells)

#### Ukuran kuantitatif (proksi ubikuitas, bukan ukuran "AI tell")
- Unduhan npm pekan 2026-09-15 s.d. 2026-09-21 — [npm downloads API](https://api.npmjs.org/downloads/point/last-week/lucide-react):

  | Paket | Unduhan per pekan |
  |---|---|
  | lucide-react | 77.191.934 |
  | react-icons | 6.971.814 |
  | @phosphor-icons/react | 3.032.232 |
  | @heroicons/react | 2.951.848 |
  | @tabler/icons-react | 2.239.511 |
  | @hugeicons/react | 816.677 |
  | geist | 1.629.713 |
  | @fontsource/inter | 2.244.090 |
  | @fontsource-variable/plus-jakarta-sans | 250.998 |
  | @fontsource/plus-jakarta-sans | 190.042 |

- Lucide menggambarkan dirinya sebagai "Open-source project and a fork of Feather Icons" dengan lisensi ISC. — [lucide-icons/lucide README](https://github.com/lucide-icons/lucide)
- Template resmi `create-next-app` (cabang canary) memuat `import { Geist, Geist_Mono } from "next/font/google";`, sehingga Geist dan Geist Mono adalah font bawaan proyek Next.js baru. — [vercel/next.js template layout.tsx](https://github.com/vercel/next.js/blob/canary/packages/create-next-app/templates/app-tw/ts/app/layout.tsx)
- Halaman Geist milik Vercel mengaitkan font ini dengan "the renowned Swiss design movement", menyebutnya "specifically designed for developers and designers", dan berlisensi OFL. — [vercel.com/font](https://vercel.com/font). Menurut metadata Google Fonts, perancangnya Andrés Briganti, Mateo Zaragoza, Guillermo Rauch, Evil Rabbit, José Rago, dan Facundo Santana; Geist masuk Google Fonts 2024-10-01. — [Google Fonts metadata](https://fonts.google.com/metadata/fonts). Klaim "rilis Oktober 2023 bersama Basement Studio" hanya berasal dari ringkasan hasil pencarian, dan sumber spesifiknya tidak dipastikan. Salah satu hasil: [Peerlist](https://peerlist.io/blog/engineering/how-to-use-vercel-geist-font-in-nextjs).
- Metadata Google Fonts, field `popularity` (angka kecil berarti lebih populer) — [fonts.google.com/metadata/fonts](https://fonts.google.com/metadata/fonts):

  | Font | Peringkat | Ditambahkan |
  |---|---|---|
  | Roboto | #2 | |
  | Open Sans | #3 | |
  | Inter | #5 | |
  | Poppins | #8 | |
  | Lato | #9 | |
  | DM Sans | #21 | |
  | Plus Jakarta Sans | #39 | 2022-03-23 |
  | Bricolage Grotesque | #51 | |
  | Space Grotesk | #62 | |
  | Instrument Serif | #80 | |
  | Fraunces | #91 | |
  | Geist | #107 | 2024-10-01 |
  | Newsreader | #128 (Production Type) | |
  | Geist Mono | #148 | |

#### Cara tim melawan homogenisasi (bukti)
- Preset gaya yang menulis ulang kode komponen (shadcn create); lihat butir shadcn di atas.
- Proses dua lintasan pada skill Anthropic: rencana token (4–6 warna hex bernama, peran tipe, konsep layout, prinsip), ditinjau terhadap brief ("if any part of it reads like the generic default you would produce for any similar page ... revise that part"), baru kemudian membangun dan mengkritik. — [anthropics/skills SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)
- Tipe custom dan sistem ikon custom di merek rujukan (Patagonia, Strava, Traveloka, Tokotype). Buktinya ada di bagian 3.

### Inferences
- [INFERENSI] "Tampilan AI" adalah sasaran bergerak. Daftar alternatif yang dipublikasikan dan dipasang ratusan ribu kali ikut menjadi default baru. Space Grotesk bergeser dari dianjurkan (blog November 2025) menjadi dilarang (skill 2026). Skill September 2026 bahkan menamai klaster "krem hangat + serif + aksen tanah liat" sebagai tell, dan mencatat bahwa aksennya adalah warna interaksi Claude sendiri (#D97757). Jadi memilih font dari daftar anti-slop mana pun (Newsreader, Bricolage, IBM Plex, JetBrains Mono, Instrument Serif, Fraunces) tidak otomatis membuat MEMOUNT berbeda.
- [INFERENSI] Keseragaman dikenali dari kemunculan bersama banyak default: font, set ikon, radius tunggal, bayangan abu, grid kartu, gradien, eyebrow kapital, dan copy kosong. Satu elemen saja tidak cukup untuk dikenali. Mengganti satu font sambil mempertahankan sisa kit tidak menyelesaikan masalah. Sebaliknya, font umum di dalam sistem yang spesifik tidak otomatis salah ("Inter ... is not wrong").
- [INFERENSI] Paparan khusus MEMOUNT:
  - Kanvas prototipe #F6F3EC hanya berselisih 2/255 per kanal RGB dari #F4F1EA (246/243/236 lawan 244/241/234), praktis identik dengan jangkar klaster 1.
  - Judul Newsreader di kode sekarang adalah "high-contrast serif display" pada klaster 1.
  - Aksen bata #8E2F22 lebih gelap dan lebih merah daripada #D97757, tetapi sekeluarga "warm clay".
  - Geist Mono untuk label data adalah butir eksplisit klaster 5.
  - Bila prototipe dipakai apa adanya, ia mengenai klaster 1 dan 5 sekaligus.
- [INFERENSI] Phosphor (di kode sekarang) sekitar 25 kali lebih jarang diunduh daripada Lucide, jadi asosiasinya dengan kit default lebih lemah. Namun ia tetap set siap pakai yang tidak memberi keterbedaan kategori.
- [INFERENSI] Keterikatan Geist dengan "stack default" berasal dari posisinya sebagai bawaan create-next-app dan font merek Vercel, bukan dari popularitas absolutnya. Plus Jakarta Sans justru lebih populer di Google Fonts (#39 lawan #107) tetapi jauh lebih jarang dalam stack JS (fontsource ~0,44 juta lawan geist 1,63 juta per pekan).

### Gaps
- Tidak ditemukan studi kuantitatif atau peer-reviewed yang mengukur kemiripan UI hasil AI. Diskursusnya berupa pernyataan pembuat alat dan esai praktisi. NN/g, Smashing, talk Figma Config, dan UX Collective tidak sempat dijangkau karena kuota pencarian habis.
- Tidak ada sumber yang di-fetch yang menyebut Geist atau Lucide secara eksplisit sebagai "AI tell". Asosiasinya disimpulkan dari status default dan data npm. Default stack v0, Bolt, dan Lovable tidak diverifikasi di sesi ini.
- Nilai default `iconLibrary` shadcn tidak terverifikasi. Field-nya ada di [schema.json](https://ui.shadcn.com/schema.json), tetapi halaman dokumentasi tidak mencantumkannya.

## 2. Riset keterbedaan: aset merek distingtif, apa yang "dapat dimiliki" produk digital, dan tarik-ulur dengan konvensi kegunaan

### Takeaway
Menurut Ehrenberg-Bass (Romaniuk), aset distingtif adalah elemen non-nama yang memanggil merek di benak pembeli kategori. Kekuatannya diukur dengan fame dan uniqueness, lewat stimulus tanpa merek dan pertanyaan tanpa petunjuk. Memilih aset karena maknanya justru menggerus uniqueness, sebab pesaing memilih simbol yang sama dengan alasan yang sama. Untuk produk digital, identitas sebaiknya tinggal pada lapisan yang diulang di setiap layar tetapi tidak diatur konvensi (tata data, gramatika status, glyph, warna berperan, suara). Kendali interaksi tetap konvensional menurut hukum Jakob.

### Cited Findings
- Definisi yang diatribusikan ke Ehrenberg-Bass: distinctive brand assets adalah "non-brand name elements that can trigger the brand into memory for category buyers." — [Brandspace](https://www.brandspacehq.com/blog/distinctive-brand-assets/)
- Grid aset distingtif Romaniuk memetakan fame (sumbu Y) terhadap uniqueness (sumbu X) dengan ambang 50% untuk keduanya. Pengukurannya: survei pembeli kategori (bukan pelanggan setia atau karyawan), stimulus tanpa merek yang disajikan terpisah, dan pertanyaan terbuka "What brand, if any, comes to mind when you see/hear this?". Sumber asalnya buku *Building Distinctive Brand Assets* (OUP, 2018). Empat kuadrannya — [The Brand Algorithm](https://www.the-brand-algorithm.com/distinctive-asset-grid/) · [OUP](https://global.oup.com/academic/product/building-distinctive-brand-assets-9780190311506):
  - "Use or Lose": "feature in every relevant piece of work, consistently".
  - "Investment Potential": "build fame through consistent exposure, always linked to the brand name".
  - "Avoid Solo Use": "always pair with the brand name or a unique asset".
  - "Ignore or Test".
- Romaniuk mendefinisikan fame score sebagai "the proportion of category buyers who, when they experience an asset, think of the brand". Ia menulis "if you want your asset to act as a proxy for your brand name, your fame score needs to be as close to 100% as possible", dan memperingatkan bahwa pengukuran dengan petunjuk "gives you inaccurate, inflamed fame scores – up to about 20 percentage points – which is really dangerous." (Mumbrella360, 24 Mei 2024). — [jenniromaniuk.com](http://www.jenniromaniuk.com/blog/2024/5/24/mumbrella360-ehrenberg-bass-professor-says-youre-poorly-branding-if-youre-focusing-on-assets-over-your-brand)
- Romaniuk, "Leaning on meaning could undermine your distinctive assets" (Marketing Week, 9 September 2025) — [jenniromaniuk.com](http://www.jenniromaniuk.com/blog/2026/9/18/leaning-on-meaning-could-undermine-your-distinctive-assets):
  - "Leaning on meaning makes it easy to explain why you selected asset B over A and C", tetapi makna menurunkan uniqueness karena pesaing memilih aset serupa dengan alasan simbolik yang sama (contohnya hijau untuk keberlanjutan).
  - "a positive meaning can blind you to the asset's drawbacks, such as links to a competitor".
  - Asosiasi bawaan menimbulkan kompetisi mental sehingga "you fail to make or refresh the desired brand-asset link".
- Akademisi Ehrenberg-Bass menemukan marketer cenderung melebih-lebihkan fame dan meremehkan uniqueness aset mereknya sendiri. Ini hanya dari cuplikan hasil pencarian; halaman aslinya menolak akses (403). — [Marketing Week](https://www.marketingweek.com/marketers-judgement-strength-brand-assets-rarely-accurate/)
- Romaniuk tentang karakter merek (Marketing Week, 27 Mei 2021): "You simply can't tell a great story without a great character" dan "So where are all the B2B characters? There aren't many." Ini opini tanpa statistik pembanding antarjenis aset. — [jenniromaniuk.com](http://www.jenniromaniuk.com/blog/2022/8/30/brand-characters-could-be-the-most-undervalued-asset-in-b2b-marketing)
- Hukum Jakob: "Users spend most of their time on other sites. This means that users prefer your site to work the same way as all the other sites they already know." Butir takeaway: "Users will transfer expectations they have built around one familiar product to another that appears similar." — [Laws of UX](https://lawsofux.com/jakobs-law/)
- WCAG 2.2 SC 1.4.1 (Level A): "Color is not used as the only visual means of conveying information, indicating an action, prompting a response, or distinguishing a visual element." — [W3C Understanding 1.4.1](https://www.w3.org/WAI/WCAG22/Understanding/use-of-color.html)

#### Identitas di luar typeface dan ikon (contoh bersumber)
- **Komposisi.** Unigrid NPS (Massimo Vignelli, 1977) memberi "a consistent title treatment associated with a black band and standardized typography, contributing to a recognizable NPS brochure identity". Sistem ini menerima Presidential Design Award 1985 karena "standardizing routine decisions while supporting a wide range of subject matter". — [Wikipedia: Unigrids](https://en.wikipedia.org/wiki/Unigrids)
- **Semantik goresan ikon sebagai nilai merek.** Sistem ikon Strava 2024 memadukan "the soft rounded corners adding a friendliness...and the sharpness of the square end caps denoting competition, drive, and passion". — [Griff Designs](https://griffdesigns.com/strava)
- **Motif rute di logo pesaing.** Logo AllTrails (2023) mempertahankan gunung dan memadukan "looped design that could signify a mapped trail or an artistic take on the letter 'A.'" Ini sumber sekunder. — [Logos-World](https://logos-world.net/alltrails-launches-new-branding/). Red Antler memakai konsep jalur untuk membingkai cerita outdoor. — [Red Antler](https://www.redantler.com/work/all-trails)
- **Struktur visual sebagai informasi.** "Structural devices like outlines, borders, numbering, eyebrows, dividers, labels, etc., encode useful information about the content rather than decorate it." — [anthropics/skills SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)
- **Suara sebagai tell.** Headline "weightless" termasuk tanda desain AI. — [925Studios](https://www.925studios.co/blog/ai-slop-design-tells). Skill Anthropic: "Errors don't apologize, and they are never vague about what happened. An empty screen is an invitation to act." — [SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)

### Inferences
- [INFERENSI] Uniqueness bersifat relatif terhadap kategori. Plus Jakarta Sans dipakai Gaia GPS (runtime, bagian 3), dan motif garis jalur sudah dipakai AllTrails. Maka Plus Jakarta Sans maupun "garis rute" tidak unik di kategori aplikasi pendakian. Keunikan harus datang dari eksekusi spesifik: dua belokan, cincin pos, penomoran pos, gramatika kepercayaan. Pengulangannya pun harus konsisten.
- [INFERENSI] Peringatan Romaniuk soal makna berlaku langsung pada "Jalur Berpos". Alasan pemilihannya (rute + pos) adalah alasan yang sama yang membuat pesaing berkonvergensi pada tanda serupa. Uji uniqueness dengan stimulus tanpa merek pada pendaki Indonesia, tanpa petunjuk. Sebelum peluncuran, yang bisa diuji hanya uniqueness terhadap pesaing, karena fame butuh paparan.
- [INFERENSI] Di produk digital, aset yang paling mungkin dimiliki adalah yang muncul hampir di setiap layar dan tidak diatur konvensi:
  1. Gramatika visualisasi data (gaya sparkline elevasi, penanda pos).
  2. Gramatika otoritas (border solid lawan putus-putus).
  3. Peran warna.
  4. Tanda dan set glyph domain.
  5. Suara microcopy.

  Typeface hanya dapat dimiliki bila custom atau langka di kategorinya. Tombol, formulir, dan navigasi mengikuti konvensi (hukum Jakob), dan status tidak boleh hanya lewat warna (WCAG 1.4.1).
- [INFERENSI] "Spend your boldness in one place" sejalan dengan kuadran "Use or Lose": pilih satu atau dua aset pahlawan (tanda Jalur Berpos dan sparkline elevasi), ulangi tanpa kompromi, dan biarkan lapisan lain tenang.
- [HIPOTESIS] Ujung goresan membulat pada glyph MEMOUNT dapat menandai "mantap dan hati-hati, bukan kompetisi", sebagai kontras sadar terhadap ujung persegi Strava yang ditujukan untuk "competition". Belum ada bukti bahwa pengguna menangkap semantik ujung goresan; perlu uji pengguna.

### Gaps
- Tidak diperoleh data Ehrenberg-Bass yang membandingkan kekuatan jenis aset (warna lawan typeface lawan bentuk lawan karakter). Halaman OUP kosong saat di-fetch dan isi buku tidak diakses. Belum diketahui apakah typeface cenderung aset yang lemah.
- Tidak ditemukan riset aset distingtif khusus antarmuka perangkat lunak; riset yang ada berfokus pada kemasan dan iklan. Riset Ipsos dan Kantar tentang aset tidak terjangkau.

## 3. Tipografi dan sistem ikon merek rujukan (terverifikasi)

### Takeaway
Merek dengan identitas terkuat di kategorinya memiliki tipe sendiri:
- Patagonia: Ridgeway Sans, 2025.
- Strava: Boathouse, sekitar November 2024.
- Traveloka: Godwit oleh Tokotype, 2023.
- AllTrails: potongan Aeonik bernama merek.

Strava juga punya sistem ikon custom sejak 2024. Toko perlengkapan outdoor Indonesia (Consina, Arei) berjalan di font tema template dan Font Awesome. Eiger memakai Albert Sans plus Druk Text. Pemimpin digital Indonesia rutin memesan tipe custom dari Tokotype (14 entri custom di sitemap). Aplikasi utilitas memakai font siap pakai seperti Inter, Roboto, dan Plus Jakarta Sans. Pada sampel outdoor tidak ditemukan Geist (font) maupun signature kelas Lucide.

### Cited Findings

#### Merek outdoor
- **Patagonia.** "Patagonia's first custom typeface is live: Ridgeway Sans." ID aktivitas post didekode ke 2025-02-06. Kredit: Stephen Rockwood, Samantha Levy, Manu Garzaron, Michael Leon, E Chester. Menurut post lanjutan E Chester (diringkas oleh alat fetch), fontnya dirancang berdampingan dengan serif retail Copernicus sambil menggemakan huruf Georg Belwe yang menjadi dasar logotipe Patagonia. — [LinkedIn, Levon Sharrow](https://www.linkedin.com/posts/levonsharrow_thanks-e-chester-and-patagonia-for-placing-activity-7293413108348264449-eJG7) · [Instagram srockwood (cuplikan: "We're thrilled to introduce Ridgeway Sans, Patagonia's first ...")](https://www.instagram.com/srockwood/p/DGy4Nw-y4lf/)
  - Fonts In Use mencatat logo 1973 memakai Belwe; buku Boulder Guide 2019 memakai ABC Whyte Inktrap, Hermes, Stratos; kaus Draplin 2012 memakai Kabel Black dan ITC Avant Garde Gothic. — [Fonts In Use: Patagonia](https://fontsinuse.com/tags/1267/patagonia-brand)
  - Runtime: patagonia.com mengirim halaman antrean ("Hang Tight! Routing to checkout...") ke curl, jadi font situs live tidak terverifikasi. Referensi Avenir Next di halaman antrean itu tidak dipakai sebagai bukti. — [patagonia.com](https://www.patagonia.com/home/)
- **Arc'teryx (runtime).** CSS memuat keluarga `urw-din`, `urw-din-condensed`, dan `urw-din-semi-condensed`, serta variabel `--font-helvetica:"helvetica-now-display"` dan `--font-elan:"elan-itc-pro"`. Semuanya keluarga retail. — [arcteryx.com](https://arcteryx.com/us/en). Garis keturunan DIN: DIN 1451 adalah standar Jerman 1931 untuk aplikasi lalu lintas, administratif, dan teknis. — [Wikipedia: DIN 1451](https://en.wikipedia.org/wiki/DIN_1451)
- **The North Face.** Situs menolak curl (respons sekitar 380 byte). Fonts In Use hanya mencatat penggunaan kampanye: katalog 1974–75 memakai Bullion; "50 Years of Parkas" (2023) memakai Druk Wide, Futura Passata, Windsor; kolaborasi Jepang 2020 memakai Alternate Gothic, Clarendon, Chu Gothic BBB. — [Fonts In Use search](https://fontsinuse.com/search?terms=north+face)
- **Eiger Adventure (runtime).** Memakai `albertSans` dan `drukText` di atas variabel Mantine UI. — [eigeradventure.com](https://eigeradventure.com/). Albert Sans adalah font Google karya Andreas Rasmussen, ditambahkan 2022-06-08. — [Google Fonts metadata](https://fonts.google.com/metadata/fonts)
- **Consina (runtime).** Tipografi global Elementor dengan fallback Open Sans, Poppins, dan keluarga `porto` (nama tema WordPress Porto; kemungkinan font ikon tema). Judul halaman: "Toko Online Consina Official Store – The Outdoor Lifestyle". — [consina.com](https://www.consina.com/)
- **Arei Outdoor Gear (runtime).** Lato, Dancing Script, dan Font Awesome 5 Free/Brands; nama berkas font berpola Google Fonts. — [areioutdoorgear.co.id](https://areioutdoorgear.co.id/)

#### Aplikasi outdoor dan navigasi
- **AllTrails.**
  - Rebrand oleh Red Antler dicatat Brand New pada 3 Agustus 2023. — [Brand New](https://www.underconsideration.com/brandnew/archives/new_logo_and_identity_for_alltrails_by_red_antler.php)
  - Red Antler: "The outside is in all of us, but the outdoorsy world of hiking boots and mountain peaks can be intimidating." Direktur kreatif Dan Crawford: "We wanted the brand to entice and encourage everyone to get outdoors." — [Red Antler](https://www.redantler.com/work/all-trails)
  - Runtime: `--font-family-primary:AllTrailsAeonik` dan `--font-family-secondary:AllTrailsAeonik`. — [alltrails.com](https://www.alltrails.com/). Aeonik adalah karya Mark Bloom dan Joe Leadbeater (CoType Foundry), pembaruan terakhir Maret 2025 v3.003. — [CoType](https://cotypefoundry.com/our-fonts/aeonik/)
  - **Konflik.** Logos-World menyebut wordmark dipasangkan dengan Beatrice Standard dari Sharp Type. — [Logos-World](https://logos-world.net/alltrails-launches-new-branding/). Sebuah PDF "AllTrails Style Guide" yang di-host pihak ketiga menyematkan "BeatriceTrial" (versi *trial*), Calibri, dan Lato, sehingga asal-usulnya meragukan. — [PDF](https://assets-global.website-files.com/650d0d523ce1729e6e4e0626/650d47c95f79fc1f3665097c_AllTrails%20Style%20Guide.pdf). Situs live memakai Aeonik bernama merek.
- **Komoot (runtime).** Deklarasi `Satoshi` (40 kali), `Nohemi` (23), `Boyrun`, dan `Bw Stretch`. Tidak ada keluarga bernama merek. — [komoot.com](https://www.komoot.com/)
- **Strava.**
  - Runtime: `Boathouse` dan `BoathouseExtended` untuk merek, plus `Noto Sans` beserta lebar Condensed, SemiCondensed, dan ExtraCondensed. — [strava.com](https://www.strava.com/)
  - Atribusi: post pengguna X tertanggal 2024-11-04 berbunyi "ily boathouse, new font for strava designed by @grillitype ... rip maison neue". — [X/@jtenclay](https://x.com/jtenclay/status/1853497615503053021). Sensatype menyebut Boathouse karya Grilli Type menggantikan Maison Neue dan bahwa UI memakai Inter. — [Sensatype](https://sensatype.com/what-font-does-strava-use-in-2026)
  - **Konflik.** Runtime web menunjukkan Noto Sans, bukan Inter; UI aplikasi native tidak diperiksa. Halaman beranda, custom, dan about [Grilli Type](https://www.grillitype.com/) tidak menyebut Strava, jadi atribusi foundry PERLU KLARIFIKASI.
  - Ikon: Griff (Januari–Mei 2024) merancang ulang 360 konsep ikon dalam 4 ukuran, total 1.440 aset plus panduan gaya. Tebal goresan 1,25/2/2,5/3 px untuk ukuran 16/24/32/48 px ("This unique set of stroke widths allowed for the icons to have a semi-bold feel no matter what the scale"). Set lama dinilai "incomplete and disjointed". Nilai yang dijadikan jangkar: "brave, fierce, bold, passionate, inspiring, grit, and celebration". — [Griff Designs](https://griffdesigns.com/strava)
- **Gaia GPS (runtime).** `Plus Jakarta Sans` dan `Inter`. — [gaiagps.com](https://www.gaiagps.com/)
- **FarOut (runtime).** Roboto, Overpass, dan Font Awesome. — [faroutguides.com](https://faroutguides.com/). Overpass diturunkan dari Highway Gothic untuk layar, dirancang Delve Withrington dkk. pada 2011–2021. — [Wikipedia: Overpass](https://en.wikipedia.org/wiki/Overpass_(typeface))
- **PeakVisor (runtime).** `Neris` dan `Roboto`. — [peakvisor.com](https://peakvisor.com/)

#### Produk digital Indonesia
- **Gojek (runtime).** Maison Neue dan Maison Neue Extended (Book/Demi/Bold/ExtraBold) serta Fira Mono. — [gojek.com](https://www.gojek.com/id-id/)
- **Tokopedia (runtime).** `'Open Sauce One'` (berkas OpenSauceOne-Regular dan -ExtraBold.woff2). — [tokopedia.com](https://www.tokopedia.com/). Open Sauce adalah keluarga gratis berlisensi SIL OFL karya Alfredo Marco Pradil. Varian One disebut "Clean grotesque without ink traps", "Neutral, editorial-appropriate", 7 bobot, 358 glyph. — [Open-Sauce-Fonts README](https://github.com/marcologous/Open-Sauce-Fonts)
- **Traveloka.**
  - Runtime: Godwit (GodwitText Regular/Medium/SemiBold/ExtraBold) dengan fallback NotoSans dan NotoSansJP. — [traveloka.com](https://www.traveloka.com/id-id)
  - Tokotype: "Godwit Family is a geometric sans serif typeface derived from the main objective of Traveloka's newer brand refreshment, maintaining aspects such as inclusive yet unique, aspirational yet approachable, and energetic yet credible". Proporsi bagian atas dan bawah dilebih-lebihkan pada a, c, e, s, B, C, G, S; ujung crotch W dan M dipotong. Subfamili Display, Text, dan Thai. Dikerjakan bersama Traveloka Creative Lab. Teks ini dari cuplikan hasil pencarian karena halamannya dirender JS. — [Tokotype: Godwit](https://www.tokotype.com/custom-fonts/godwit)
  - Post Tokotype "Custom Fonts: Traveloka Godwit Family"; ID aktivitas didekode ke 2023-05-30. — [LinkedIn](https://www.linkedin.com/posts/tokotypefoundry_custom-fonts-traveloka-godwit-family-activity-7069320369005871105-yTXH)
- **Bibit dan Stockbit (runtime).** Keduanya memuat keluarga `BitSans`/`Bitsans` (bitsans-regular dan -bold.woff2), jadi satu keluarga dipakai lintas dua aplikasi. — [bibit.id](https://bibit.id/) · [stockbit.com](https://stockbit.com/)
- **Jenius (runtime).** `Jenius Sans`, Roboto, `Font Awesome 5 Pro`, dan "Sunday Morning". — [jenius.com](https://www.jenius.com/)
- **Halodoc (runtime).** Inter (Inter_400, 500, 600.woff2), Material Icons, dan Font Awesome. — [halodoc.com](https://www.halodoc.com/)
- **Portofolio custom Tokotype.** Sitemap memuat 14 entri `custom-fonts/`: jenius, gpn, thinkingroom, bukalapak, plusjakarta, tiket, goto, blu, godwit, gopay, jenius_think_unthinkable, bca-sans, xlsmart, indonesia-national-team. — [Tokotype sitemap](https://www.tokotype.com/sitemap.xml)

#### Ikon (runtime, sinyal lemah)
- Font ikon siap pakai terdeteksi di:
  - FarOut: Font Awesome — [faroutguides.com](https://faroutguides.com/)
  - Arei: FA5 Free — [areioutdoorgear.co.id](https://areioutdoorgear.co.id/)
  - Jenius: FA5 Pro — [jenius.com](https://www.jenius.com/)
  - Halodoc: Material Icons dan Font Awesome — [halodoc.com](https://www.halodoc.com/)
  - Basarnas: Font Awesome dan Flaticon — [basarnas.go.id](https://basarnas.go.id/)
- Tidak ditemukan signature kelas Lucide, Phosphor, Material, Font Awesome, Heroicons, Tabler, atau Remix di HTML hasil server-render AllTrails, Strava, Tokopedia, Bibit, Eiger, dan PeakVisor. Jumlah `<svg>` inline: Komoot 107, Traveloka 94, PeakVisor 54, Strava 32, AllTrails 17, Eiger 17. — [alltrails.com](https://www.alltrails.com/) · [komoot.com](https://www.komoot.com/) · [traveloka.com](https://www.traveloka.com/id-id)

### Inferences
- [INFERENSI] Polanya: merek yang dipimpin identitas memiliki tipe sendiri (custom atau potongan bernama merek), yaitu Patagonia, Strava, AllTrails, Traveloka, Jenius, dan klien Tokotype lainnya. Utilitas memakai font netral siap pakai (Inter, Roboto). Toko perlengkapan memakai default tema. Tipe custom mengekspresikan:
  - kesinambungan warisan (Ridgeway menggemakan Belwe),
  - energi (Strava),
  - keterbukaan (AllTrails, "The outside is in all of us"),
  - jangkauan regional (subfamili Thai pada Godwit).
- [INFERENSI] Di Indonesia, memesan tipe custom dari foundry lokal adalah praktik mapan pemimpin tech dan fintech (GoTo, GoPay, Traveloka, Jenius, blu, BCA, Bukalapak, tiket, XLSmart). "Tipe buatan Indonesia" adalah sinyal lokal yang kredibel dan wajar, bukan eksotis.
- [INFERENSI] Pada sampel outdoor dan navigasi tidak ditemukan Geist maupun signature kelas Lucide. Ini sinyal lemah untuk ikon, karena ikon bisa dibundel sebagai SVG tanpa nama kelas. Register "outdoor" berbeda dari register "Vercel/dev-tool". Geist membawa asosiasi kategori yang salah bagi produk pendakian.
- [INFERENSI] Semantik ikon Strava sengaja mengodekan kompetisi, bertentangan dengan komitmen MEMOUNT "tanpa kompetisi kecepatan". MEMOUNT sebaiknya tidak meminjam grammar ujung goresan itu.
- [INFERENSI] Skala Strava (1.440 aset) menunjukkan biaya sistem ikon custom penuh. Set glyph domain yang kecil (belasan) lebih realistis untuk MVP.

### Gaps
- Typeface UI digital The North Face tidak terverifikasi (bot protection).
- Foundry font Komoot (Satoshi, Nohemi, Boyrun, Bw Stretch), Druk Text di Eiger, dan Maison Neue di Gojek tidak diverifikasi dari halaman foundry di sesi ini.
- Asal-usul BitSans dan perancang Jenius Sans tidak diverifikasi. Hubungan Jenius Sans dengan Tokotype hanya disimpulkan dari entri sitemap.
- Konfirmasi Boathouse–Grilli Type dari Strava atau Grilli Type belum ada.
- Sistem ikon AllTrails, Komoot, Gojek (string "Asphalt" muncul di HTML, kemungkinan nama design system, belum terverifikasi), Tokopedia, dan Traveloka tidak terverifikasi.
- Aplikasi native tidak diperiksa.
- Halaman portofolio Kristopher Boyce tentang "Strava" (warna hyper green/electric blue/sand beige) tampaknya konsep pribadi dan tidak dipakai sebagai fakta. — [kristopherboyce.com](https://kristopherboyce.com/work/strava/)

## 4. Identitas Indonesia tanpa klise

### Takeaway
Praktik Indonesia yang terverifikasi memberi sinyal lokal lewat asal-usul dan cerita serta bahasa, bukan ornamen:
- Plus Jakarta Sans lahir dari komisi sipil Jakarta 2020 dan memiliki alternatif bernama Lancip, Lurus, Lingkar.
- Keluarga retail Tokotype bernama Indonesia (Gramatika, Frasa, Aksen, Leksikal, Sinar).
- Merek nasional memesan tipe custom lokal.

Jebakan yang terdokumentasi paling relevan untuk merek gunung adalah Mooi Indië: "trinitas suci" gunung, nyiur, dan sawah yang dikritik Sudjojono sebagai lukisan untuk orang asing dan turis.

### Cited Findings
- **Plus Jakarta Sans** — [tokotype/PlusJakartaSans README](https://github.com/tokotype/PlusJakartaSans):
  - "designed by Gumpita Rahayu from Tokotype, the fonts were originally commissioned by 6616 Studio for Jakarta Provincial Government program's +Jakarta City of Collaboration identity in 2020."
  - Inspirasi: "Neuzit Grotesk" (ejaan sesuai README), Futura, dan grotesk 1930-an.
  - "The beauty of diversity captured in typography."
  - Alternatif stilistik: "Lancip (Sharp), Lurus (Straight), and Lingkar (Swirl)".
  - Dirilis di bawah SIL OFL "As part of Plus Jakarta as a city of collaboration".
  - FONTLOG v2.600 (12 November 2021): "Adding Tabular Figures (.tf)".
- Plus Jakarta Sans masuk Google Fonts 2022-03-23, peringkat popularitas #39. — [Google Fonts metadata](https://fonts.google.com/metadata/fonts). Dipakai aplikasi navigasi outdoor Gaia GPS (runtime). — [gaiagps.com](https://www.gaiagps.com/)
- Keluarga retail Tokotype menurut sitemap: Gramatika, Makro (+Condensed/Expanded), Frasa (+Display), Maleo, Nomina, Aksen (+Condensed/Expanded), Fonetika, Leksikal Sans/Flare/Serif, Sinar Grotesk (+Condensed/Expanded). — [Tokotype sitemap](https://www.tokotype.com/sitemap.xml)
- Brief Godwit untuk Traveloka: "inclusive yet unique, aspirational yet approachable, and energetic yet credible", dengan subfamili Thai untuk pasar regional. — [Tokotype: Godwit](https://www.tokotype.com/custom-fonts/godwit)
- **Mooi Indië.** Gerakan lukis Hindia Belanda awal abad ke-20: lanskap gunung, sawah, nyiur, pantai, dan figur pedesaan. Laku sebagai suvenir bagi pejabat kolonial dan turis. S. Sudjojono mengkritiknya sebagai "very beautiful and romantic, paradisical, everything is very pleasing, calm, and peaceful. Such paintings carry only one meaning: the beautiful Indies ... for ... foreigners and tourists." Menurut ringkasan artikel, para pelukisnya hanya mengulang "holy trinity" gunung, nyiur, dan sawah. PERSAGI (1938) menolak gaya ini. — [Wikipedia: Mooi Indië](https://en.wikipedia.org/wiki/Mooi_Indi%C3%AB)
- **Register "resmi" di jalan raya Indonesia.** "Clearview has been adopted as the standard typeface for road signs in Indonesia since 2014." dan "The Ministry of Transportation promulgated a regulation in 2014 to introduce new road signs that would use the Clearview typeface." — [Wikipedia: Clearview](https://en.wikipedia.org/wiki/Clearview_(typeface))
- **Lembaga keselamatan Indonesia di web (runtime).**
  - BMKG memakai DM Sans (Colophon Foundry, Google Fonts #21). — [bmkg.go.id](https://www.bmkg.go.id/) · [Google Fonts metadata](https://fonts.google.com/metadata/fonts)
  - Basarnas memakai Chakra Petch (Cadson Demak), Poppins, Roboto, Raleway, FontAwesome, Font Awesome 5, dan Flaticon. — [basarnas.go.id](https://basarnas.go.id/)
- Skill Anthropic menegaskan bahwa sumber pilihan visual yang khas adalah "The subject's industry, subject matter, materials, and vernacular". — [SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)

### Inferences
- [INFERENSI] Sinyal lokal yang terbukti dipakai dalam praktik:
  1. Asal-usul (perancang atau foundry Indonesia).
  2. Penamaan berbahasa Indonesia (Lancip/Lurus/Lingkar; Gramatika, Frasa, Aksen).
  3. Cerita sipil atau fungsional (+Jakarta).
  4. Vernakular subjek.

  Untuk MEMOUNT, vernakular itu adalah kosakata jalur pendakian: pos, jalur, basecamp, penomoran pos. Tidak satu pun memerlukan ornamen batik atau wayang.
- [INFERENSI] Mooi Indië adalah peringatan tepat untuk citra MEMOUNT. Hero berupa panorama gunung berapi dengan sawah terasering dan matahari terbit mereproduksi tatapan turis yang dikritik Sudjojono. Ini sejalan dengan aturan konteks R-017 (foto komunitas bukan hero default). Alternatifnya: yang "paling khas dari dunia subjek" adalah data jalur itu sendiri (profil elevasi, pos).
- [INFERENSI] Lembaga keselamatan Indonesia belum menyediakan register tipografi digital yang khas untuk dipinjam. BMKG memakai DM Sans generik, Basarnas campuran font template. Ingatan tipografi "resmi" yang dimiliki orang Indonesia di luar ruang adalah rambu jalan: Clearview sejak 2014, sans humanis dengan x-height besar dan counter terbuka. Sans humanis bergaya rambu dapat memberi nada "infrastruktur" tanpa meniru identitas lembaga. MEMOUNT tidak boleh tampil seolah-olah lembaga resmi; konteks R-009 dan R-010.
- [INFERENSI] Cerita lokal Plus Jakarta Sans nyata, tetapi fontnya tidak langka (#39 Google Fonts, dipakai Gaia GPS). Nilainya bagi MEMOUNT ada pada cerita dan alternatif stilistik bernama Indonesia, bukan keunikan.

### Gaps
- Tidak diperoleh sumber kritik perancang Indonesia terhadap klise batik dan wayang di produk digital, maupun analisis "tampilan poster pariwisata" dalam branding Indonesia kontemporer. DGI, Kumparan, Tech in Asia, dan DailySocial tidak terjangkau setelah kuota pencarian habis.
- Biaya dan syarat lisensi keluarga retail maupun komisi custom Tokotype tidak diverifikasi.
- Tidak ada data apakah pengguna Indonesia mempersepsi Plus Jakarta Sans sebagai "lokal".
- Pedoman identitas visual BMKG dan Basarnas tidak ditemukan.

## 5. Nada keselamatan: bagaimana produk tepercaya dan kritis-keselamatan memakai tipografi dan ikonografi

### Takeaway
Keandalan diberi sinyal oleh mekanisme, bukan gaya:
- Keterbacaan yang direkayasa dan diuji untuk konteksnya: Transport, NPS Rawlinson, Frutiger/ASTRA, B612.
- Gramatika bentuk dan warna yang tertutup dan terbaca tanpa kata: ISO 7010, marka jalur Swiss.
- Sistem komposisi yang konsisten: pita hitam Unigrid NPS.
- Register "resmi" yang dibatasi untuk konten resmi: font GOV.UK hanya untuk domainnya.

Kisah Clearview mengingatkan bahwa klaim keterbacaan bisa terkontaminasi faktor lain. Uji dulu, baru klaim.

### Cited Findings
- **NPS Rawlinson Roadway** — [Wikipedia: NPS Rawlinson Roadway](https://en.wikipedia.org/wiki/NPS_Rawlinson_Roadway):
  - Serif gaya lama untuk rambu jalan National Park Service AS, dibuat tahun 2000 oleh Terminal Design (James Montalbano) untuk menggantikan Clarendon; dipengaruhi Plantin, Sabon, dan Garamond.
  - "approximately 10–15% more compact than its predecessor"; Pennsylvania Transportation Institute menemukan peningkatan keterbacaan 11%.
  - Frutiger dipakai berdampingan sebagai pasangan sans.
- **Unigrid NPS** — [Wikipedia: Unigrids](https://en.wikipedia.org/wiki/Unigrids):
  - Vignelli, 1977; brosur pertama 1978; panel sekitar 4 × 8¼ inci.
  - Pita hitam judul menjadi identitas; emblem arrowhead ditambahkan ke pita pada 1999.
  - Tipe awal Helvetica dan Times Roman; kini Frutiger dan NPS Rawlinson.
- **Frutiger** — [Wikipedia: Frutiger](https://en.wikipedia.org/wiki/Frutiger_(typeface)):
  - Dikomisikan 1970/71 untuk Bandara Charles de Gaulle demi "a modern appearance and legibility at various angles, sizes, and distances". Frutiger: "What was important, was total clarity – an absence of any kind of artistic addition."
  - ASTRA-Frutiger menjadi font rambu resmi Swiss sejak 2003, "based on Frutiger 57 Condensed, but with widening ascenders and descenders".
  - Michael Bierut soal ubikuitasnya: "seeing it now makes me feel that I'm about to get diagnosed with a brain tumor or miss the 7:00 to O'Hare."
- **Transport** — [Wikipedia: Transport](https://en.wikipedia.org/wiki/Transport_(typeface)):
  - Kinneir dan Calvert, 1957–1963, diuji di Preston bypass (1958).
  - "a more rounded typeface with distinctive tails to 'a', 't', and 'l', and bar-less fractions, all of which helped legibility."
  - GDS Transport (2012, Kubel dan Calvert) "has been selected as the sole font for all text" di GOV.UK.
- **GOV.UK.** "If your service is on the service.gov.uk subdomain you must use the GDS Transport font." dan "If your service is publicly available on a subdomain other than service.gov.uk, use an alternative typeface." — [GOV.UK Design System: Typeface](https://design-system.service.gov.uk/styles/typeface/)
- **Clearview** — [Wikipedia: Clearview](https://en.wikipedia.org/wiki/Clearview_(typeface)):
  - Uji awal: "two to eight percent more legible in both day- and night-time viewing ... with a six percent increase in legibility distance". Namun "these tests also compared new signs in Clearview to existing, weathered signs".
  - FHWA memberi interim approval 2 September 2004, mencabutnya Januari 2016 karena "the brightness of the retroreflective sheeting is the primary factor in nighttime legibility", lalu memulihkannya 28 Maret 2018 atas perintah Kongres.
  - Standar rambu Indonesia sejak 2014.
- **Highway Gothic.** Dirancang untuk "maximize legibility at long sight distances while traveling at road speeds". — [Wikipedia: Highway Gothic](https://en.wikipedia.org/wiki/Highway_Gothic)
- **B612** — [polarsys/b612 README](https://github.com/polarsys/b612):
  - Riset Airbus bersama ENAC dan Université de Toulouse III sejak 2010 untuk "define and validate an 'Aeronautical Font'"; Intactile DESIGN mengerjakan 8 varian mulai 2012.
  - Prinsip: "Maximize the distance between the forms of the characters", "Respect the primitives of the different letters", "Harmonize the forms and their spacing".
  - Lisensi SIL OFL 1.1 (plus EPL/EDL).
- **ISO 7010** — [Wikipedia: ISO 7010](https://en.wikipedia.org/wiki/ISO_7010):
  - Terbit Oktober 2003.
  - Gramatika: larangan = lingkaran merah dengan garis diagonal; wajib = lingkaran biru; peringatan = segitiga kuning; kondisi aman = persegi hijau; peralatan kebakaran = persegi merah.
  - Tujuan: "safety information that relies as little as possible on the use of words to achieve understanding".
- **Marka jalur** — [de.wikipedia: Wanderweg](https://de.wikipedia.org/wiki/Wanderweg) · [Wikipedia: Trail blazing](https://en.wikipedia.org/wiki/Trail_blazing):
  - Swiss: "Schweizer Wegweiser: Bergwege (weiss-rot-weiss), Alpinroute (weiss-blau-weiss)". Tingkat kesulitan memakai SAC-Wanderskala.
  - Austria menurunkan warnanya dari klasifikasi lintasan ski: "Blau steht für leicht, Rot für mittelschwierig, Schwarz für schwierig".
  - Jepang: "Red ribbons usually indicate an ascent route while yellow ribbon indicate a descent route."
- **Web lembaga keselamatan Indonesia (runtime):** BMKG memakai DM Sans; Basarnas memakai campuran Chakra Petch, Poppins, Roboto, Raleway, dan Font Awesome/Flaticon. — [bmkg.go.id](https://www.bmkg.go.id/) · [basarnas.go.id](https://basarnas.go.id/)

### Inferences
- [REKOMENDASI] **Mekanisme 1: angka dan label yang mengutamakan keterbacaan, diuji di kondisi lapangan.** Kondisinya: silau matahari, layar redup saat baterai hemat, tangan bersarung. Jangan menerbitkan klaim keterbacaan tanpa uji MEMOUNT sendiri; pelajaran Clearview adalah uji yang terkontaminasi.
- [REKOMENDASI] **Mekanisme 2: gramatika otoritas yang tertutup dan redundan** (bentuk atau border, warna, dan label teks) untuk OFFICIAL, ADMIN VERIFIED, COMMUNITY, dan AI, seperti ISO 7010 dan marka Swiss. Status tidak boleh hanya lewat warna (WCAG 1.4.1). Border solid lawan putus-putus di prototipe sudah sesuai mekanisme ini, dan skill Anthropic mengakui border sebagai informasi, bukan dekorasi.
- [REKOMENDASI] **Mekanisme 3: slot komposisi tetap** ala pita Unigrid di halaman Trail untuk status, sumber, dan kesegaran data. Ini dikenali sekaligus fungsional (R-013).
- [REKOMENDASI] **Mekanisme 4: register visual "resmi" dicadangkan eksklusif untuk konten bersumber OFFICIAL**, analog dengan GOV.UK yang membatasi GDS Transport pada domainnya. Ini sejalan dengan R-009, R-010, dan R-011 agar konten komunitas atau AI tidak bisa "meminjam" tampilan resmi.
- [INFERENSI] NPS Rawlinson menunjukkan serif gaya lama bisa membawa kepercayaan institusi taman nasional. Namun pada 2026, "high-contrast serif display" di atas krem hangat adalah klaster AI nomor satu. Bila serif dipakai, pilih serif teks berkontras rendah dengan peran fungsional (nama tempat atau pos, seperti rambu NPS), bukan perlakuan headline display.
- [INFERENSI] Ubikuitas Frutiger (kutipan Bierut) menunjukkan "tampilan wayfinding" pun bisa generik. Pinjam mekanismenya, bukan wajahnya.
- [INFERENSI] Sistem Austria yang meminjam warna lintasan ski adalah contoh hukum Jakob di dunia fisik: gramatika baru lebih cepat dipahami bila meminjam model mental yang sudah ada.

### Gaps
- Font UD Jepang, tipografi layanan cuaca (Met Office dan sejenisnya), aplikasi maritim, dan rambu taman nasional Indonesia (Balai TN) tidak terjangkau.
- NPS 2 Sans tidak terverifikasi.
- Tidak ada studi lapangan keterbacaan aplikasi outdoor yang diperoleh.

## 6. Sintesis opsi untuk identitas tipe dan ikon MEMOUNT

### Takeaway
Bukti paling mendukung varian Opsi B yang dijalankan dengan disiplin Opsi C3:
- Pertahankan font teks buatan Indonesia (Plus Jakarta Sans sekarang; keluarga Tokotype atau custom nanti).
- Beri angka "suara" lewat angka tabular di keluarga yang sama, bukan mono generik.
- Pakai set ikon dasar hanya untuk UI generik, lalu gambar set glyph domain kecil dari geometri Jalur Berpos.
- Hindari klaster AI September 2026: krem hangat + serif display + aksen tanah liat; mono untuk label data; eyebrow KAPITAL; meta bertitik tengah; panah di tombol.

Opsi A (Geist, Geist Mono, Lucide) hanya dapat dipertahankan dengan alasan tertulis. Ia tetap terbaca sebagai stack default Next/Vercel dan langsung mengenai klaster 5.

### Cited Findings
- Geist dan Geist Mono adalah bawaan create-next-app. — [template layout.tsx](https://github.com/vercel/next.js/blob/canary/packages/create-next-app/templates/app-tw/ts/app/layout.tsx)
- lucide-react diunduh sekitar 77,2 juta per pekan; @phosphor-icons/react sekitar 3,0 juta. — [npm API](https://api.npmjs.org/downloads/point/last-week/lucide-react) · [npm API Phosphor](https://api.npmjs.org/downloads/point/last-week/@phosphor-icons/react)
- Klaster 1 dan 5 skill Anthropic, termasuk "a monospace face for small data labels" dan "tracked-out ALL-CAPS eyebrow label". — [SKILL.md](https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md)
- Plus Jakarta Sans: OFL, karya Tokotype, alternatif Lancip/Lurus/Lingkar, angka tabular sejak v2.600. — [README](https://github.com/tokotype/PlusJakartaSans). Popularitas #39 Google Fonts. — [metadata](https://fonts.google.com/metadata/fonts). Dipakai Gaia GPS. — [gaiagps.com](https://www.gaiagps.com/)
- Newsreader ada di daftar "Distinctive" blog Anthropic. — [Claude blog](https://claude.com/blog/improving-frontend-design-through-skills)
- B612 berlisensi OFL, dirancang dan diuji untuk layar kokpit. — [polarsys/b612](https://github.com/polarsys/b612). Overpass adalah turunan Highway Gothic untuk layar. — [Wikipedia: Overpass](https://en.wikipedia.org/wiki/Overpass_(typeface))
- Portofolio custom Tokotype untuk merek Indonesia (14 entri). — [Tokotype sitemap](https://www.tokotype.com/sitemap.xml). Patagonia memasangkan Ridgeway Sans custom dengan serif retail. — [LinkedIn](https://www.linkedin.com/posts/levonsharrow_thanks-e-chester-and-patagonia-for-placing-activity-7293413108348264449-eJG7)
- Skala sistem ikon custom Strava: 360 ikon × 4 ukuran = 1.440 aset. — [Griff Designs](https://griffdesigns.com/strava)

### Inferences

#### Opsi A: pertahankan default prototipe (Geist + Geist Mono + Lucide) dengan alasan tertulis
- [INFERENSI] **Alasan yang mungkin:**
  - Geist berlisensi OFL, terbaca di layar, dan punya saudara mono.
  - Cakupan Lucide luas (ISC) dan paling mudah dirawat.
  - Paling murah untuk MVP.
- [INFERENSI] **Risiko:**
  - Terbaca sebagai stack default Next.js/Vercel.
  - Geist Mono untuk label data adalah butir klaster 5.
  - Lucide adalah set ikon paling umum (sekitar 77 juta per pekan), turunan Feather dengan gaya garis tipis yang oleh praktisi disebut "interchangeable".
  - Tidak ada cerita Indonesia maupun keunikan kategori; tidak satu pun rujukan outdoor memakainya.
  - Bertentangan dengan filter antislop proyek.
  - Tidak mengekspresikan komitmen kejujuran atau kepercayaan merek.
- [INFERENSI] **Kapan masuk akal:** hanya untuk alat internal atau admin yang tidak dilihat publik.

#### Opsi B: font teks Indonesia + suara numerik khas + set ikon dasar + glyph domain dari "Jalur Berpos"
- [INFERENSI] **Tiga jalur font teks:**
  - B1: Plus Jakarta Sans (sudah di kode; OFL; cerita sipil +Jakarta; perancang Indonesia). Keterbedaannya datang dari alternatif stilistik yang diaktifkan secara konsisten, misalnya Lurus atau Lancip, plus bobot dan tracking yang spesifik.
  - B2: keluarga retail Tokotype (Gramatika, Aksen, Leksikal, Sinar Grotesk, dan lainnya) untuk uniqueness lebih tinggi dengan lisensi komersial.
  - B3 (nanti): komisi custom Tokotype seperti Traveloka, GoTo, dan Jenius.
- [REKOMENDASI] **Suara numerik:**
  - Pakai angka tabular Plus Jakarta Sans untuk semua data lapangan (elevasi, jarak, pos, waktu), dengan hierarki besar dan tebal untuk angka dan kecil untuk satuan.
  - Jangan memakai mono untuk label data (klaster 5).
  - Bila tetap ingin wajah kedua untuk angka, pilih satu yang punya garis keturunan keterbacaan: B612 (kokpit, OFL) atau Overpass (turunan Highway Gothic). Batasi hanya pada data terukur, dan uji di lapangan.
- [REKOMENDASI] **Ikon:**
  - Pertahankan set dasar untuk UI generik. Phosphor sudah di kode dan jauh kurang ubikuit dibanding Lucide.
  - Tambahkan sekitar 10–20 glyph domain dari geometri Jalur Berpos (garis tunggal, dua belokan, cincin pos): pos atau checkpoint, jalur, basecamp, sumber air, shelter, puncak, penutupan, sumber OFFICIAL, laporan COMMUNITY, kesegaran cuaca, dan UNKNOWN.
  - Glyph memakai logika goresan, ujung, sambungan, dan sudut yang sama dengan set dasar.
  - [HIPOTESIS] Ujung goresan membulat dapat mengodekan "mantap" sebagai kontras terhadap ujung persegi Strava untuk "kompetisi"; perlu diuji.
- [INFERENSI] **Risiko:**
  - Plus Jakarta Sans tidak unik (#39, dipakai Gaia GPS), jadi keunikan harus dipikul glyph, gramatika data, dan gramatika kepercayaan.
  - Set glyph custom butuh desain dan perawatan.
  - Campuran set dasar dan custom bisa tidak konsisten.
  - Tanda yang dipilih karena maknanya berisiko menurut Romaniuk; uji uniqueness terhadap motif rute AllTrails dan Strava.
  - Alternatif stilistik (misalnya bentuk Lingkar) bisa menurunkan keterbacaan; perlu diuji.
  - Lisensi B2 dan B3 berbiaya.
  - File font tambahan membebani PWA offline; perlu subsetting.

#### Opsi C: kombinasi lain yang punya dasar bukti
- [INFERENSI] **C1, garis keturunan rambu (model NPS).** Sans humanis bergaya rambu untuk UI dan data (seperti Clearview; lisensi tidak diverifikasi) plus serif gaya lama berkontras rendah khusus nama tempat dan pos (model Rawlinson). Mengekspresikan kepercayaan infrastruktur dan taman nasional.
  - Risiko: serif di atas kanvas kertas hangat mendekati klaster 1.
  - Risiko: meniru register pemerintah dapat menyiratkan status resmi. Ini masalah etis dan menyentuh R-010/R-011; pembedaan yang jelas wajib.
- [INFERENSI] **C2, custom lebih dulu.** Komisi Tokotype untuk wordmark dan potongan display, dengan Plus Jakarta Sans untuk teks (analog Patagonia: Ridgeway custom plus serif retail). Kepemilikan tertinggi.
  - Risiko: biaya dan waktu, prematur untuk MVP.
  - Risiko: tetap tidak menyelesaikan ikon dan gramatika data.
- [INFERENSI] **C3, satu keluarga dan sistem maksimal.** Plus Jakarta Sans di seluruh UI dan data, tanpa serif display dan tanpa mono. Identitas dipikul sepenuhnya oleh tanda dan glyph Jalur Berpos, gramatika sparkline elevasi, border kepercayaan, dan peran warna, sesuai prinsip "Spend your boldness in one place".
  - Risiko: bisa terasa datar bila eksekusi aset pahlawan lemah.
- [REKOMENDASI] **Urutan kerja:**
  1. Sekarang: B1 dengan disiplin C3. Plus Jakarta Sans dengan angka tabular, Phosphor untuk UI generik, dan glyph domain custom. Lepas Geist dan Geist Mono. Tinjau ulang judul Newsreader, karena bersama kanvas #F6F3EC dan aksen bata ia membentuk klaster 1.
  2. Nanti: C2 atau B3 setelah fame mulai terbangun.
  3. Validasi: uji uniqueness tanda dan glyph dengan stimulus tanpa merek pada pendaki Indonesia (metode Romaniuk), dan uji keterbacaan angka di lapangan.

  Keputusan akhir tetap milik pemilik. DESIGN.md adalah pemilik aturan desain.
- [INFERENSI] **Butir "template chrome" yang perlu diperiksa di MEMOUNT:**
  - Badge KAPITAL (OFFICIAL/COMMUNITY; label fit COCOK / PERLU PERSIAPAN / KURANG COCOK) mengenai tell "all caps for labels". Opsi: render dalam sentence case sambil mempertahankan kata persis. Butuh keputusan pemilik karena R-004 mengunci kata label, bukan kapitalisasinya.
  - Meta jalur bergaya "A · B · C" (jarak · elevasi · durasi) adalah butir klaster 5. Alternatifnya grid data berlabel.
  - Aksen bata bila dipakai sebagai dekorasi termasuk klaster 1. Bila dicadangkan untuk peran semantik (misalnya penutupan atau peringatan), ia menjadi informasi.
- [INFERENSI] **Penomoran pos adalah urutan nyata** di sepanjang jalur. Skill Anthropic membolehkan penanda bernomor bila kontennya memang berurutan, jadi "Pos 1, Pos 2 ..." adalah vernakular subjek yang sah dan dapat dimiliki, bukan chrome template.

### Gaps
- Belum ada uji pengguna atau pengukuran fame dan uniqueness untuk tanda Jalur Berpos maupun kandidat font.
- Kemiripan visual Jalur Berpos dengan logo loop AllTrails belum dinilai berdampingan.
- Biaya lisensi Tokotype (retail dan custom), lisensi Clearview dan Overpass, dan anggaran performa font untuk PWA offline tidak diverifikasi.
- Keterbacaan lapangan angka Plus Jakarta Sans dibanding B612 atau Overpass belum diuji.
- Konvensi format angka Indonesia (pemisah desimal koma) tidak diverifikasi di sesi ini.
