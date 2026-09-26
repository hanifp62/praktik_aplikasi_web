# Riset 01: Bahasa desain sistem operasi 2025-2026 dan terjemahannya untuk Memount

> **Status:** RISET, bukan keputusan. Tanggal 25 September 2026. Disusun agen peneliti UI/UX atas tugas koordinator.
> Tidak ada kode, token, atau dokumen lain yang diubah.
>
> **Pertanyaan riset:** apa yang dapat dipelajari Memount dari bahasa desain Apple, Google, Samsung, Huawei, Xiaomi,
> OPPO, dan Microsoft, dan bagian mana yang layak dibawa ke aplikasi web Laravel 13 + Inertia 3 + React 19 + Tailwind 3.4
> + MapLibre 4.7 yang dipakai di ponsel Android kelas bawah-menengah dengan sinyal gunung yang sering hilang.
>
> **Label bukti (R-023):** [FAKTA] didukung sumber yang dirujuk. [INFERENSI] simpulan peneliti dari fakta.
> [REKOMENDASI] usulan yang butuh keputusan. [BELUM TERVERIFIKASI] hanya dari ringkasan mesin pencari, satu sumber
> sekunder, atau halaman yang tidak dapat dibaca langsung. Rujukan [S1] dan seterusnya ada di §7; [I1] dan seterusnya
> adalah berkas repositori. Semua tautan diakses 25 September 2026.

| Metode | Isi |
|---|---|
| Skill yang dipakai | `ecc:deep-research` (sub-pertanyaan, banyak sumber, fakta dipisah dari inferensi); `ecc:search-first` (periksa repositori dan kanal lebih dulu); `modern-web-guidance` (panduan resmi GoogleChrome tentang dukungan Baseline dan fallback, lewat `npx modern-web-guidance@latest`, versi skill 2026_09_04); `apple-design` dan `ecc:liquid-glass-design` (pembanding, bukan sumber fakta); `antislop` + `antislop-copywriting` (saringan prosa). `anthropic-skills:deep-research` tidak dipakai karena bergantung pada subagen, dan sesi ini tidak memunculkan subagen tanpa permintaan pengguna. |
| Kanal | WebSearch dan WebFetch. Kanal firecrawl/exa yang diandalkan `ecc:deep-research` tidak terpasang di sesi ini, jadi cakupan tidak diklaim setara crawler. |
| Yang tidak terbaca | Halaman HIG Apple dan m3.material.io dirender JavaScript sehingga WebFetch hanya mendapat judul; isi Apple diambil dari halaman sesi WWDC. Samsung Newsroom time out; mi.com, 91mobiles, Notebookcheck, dan ACM DL membalas 403. Klaim yang hanya bersandar pada sumber seperti itu diberi [BELUM TERVERIFIKASI]. |
| Kode yang diperiksa | `DESIGN.md`, `tailwind.config.js`, `resources/css/app.css`, `resources/js/layouts/NavLinks.tsx`, `resources/js/components/trails/ConsiderationTray.tsx`, `resources/js/pages/Trails/Index.tsx`, `package.json` [I1] [I2] [I3] [I4] [I5] [I8]. |

---

## 1. Ringkasan eksekutif

1. **Kaca menjadi bahasa visual bersama 2025-2026, lalu pelopornya sendiri mundur dua kali.** Apple memperkenalkan
   Liquid Glass pada 9 Juni 2025 [S1], menambah pilihan Tinted di iOS 26.1 (November 2025) [S5], lalu di iOS 27
   (rilis 14 September 2026) menambah slider dari ultra-clear sampai fully tinted dan memperkuat keterbacaan bawaan
   [S7] [S9] [S11]. HarmonyOS 6 memberi tiga level intensitas [S40]. Untuk Memount: efek transparan harus bisa
   dikurangi pengguna, dan tampilan bawaan harus tetap terbaca tanpa efek itu.
2. **Biaya kaca diakui vendornya sendiri.** Microsoft menulis acrylic "GPU-intensive" dan menggantinya dengan warna
   solid saat Battery Saver dan di perangkat low-end [S52]. Xiaomi membatasi Soft Light Glass HyperOS 4 pada cipset
   flagship karena efeknya "needs further optimization" [S47]. Samsung dilaporkan memakai overlay abu-abu sebagai
   pengganti blur di tablet kelas bawah [S35]. Pengguna Memount mayoritas Android kelas bawah-menengah, jadi material
   bawaan harus solid dan kaca hanya peningkatan bersyarat.
3. **Bukti kegunaan paling kuat ada pada hierarki, bukan kaca.** Studi CHI 2026 (Google dan Ipsos, 48 peserta, 10
   aplikasi) mengukur tugas 20% lebih cepat, fiksasi ke elemen yang benar 33% lebih cepat, dan 59% lebih cepat pada
   usia 45+ dengan desain Material 3 Expressive [S18] [S19]. Catatannya: riset vendor dan paparan pertama. Untuk
   Memount: satu fokus per layar lewat ukuran, bobot huruf, dan containment.
4. **Navigasi yang menyusut dikritik.** NN/g (10 Oktober 2025) mencatat tab bar iOS 26 yang menyusut dan sesak, jarak
   target di bawah pedoman, dan navigasi yang kolaps [S6]. Tab bar Memount sebaiknya tetap utuh; yang boleh
   menghilang saat gulir hanya bilah atas di halaman bacaan panjang, dengan pola header partially persistent [S59].
5. **Status langsung untuk aktivitas berjalan ada di hampir semua platform:** aksesori tab bar Apple [S4], Now Bar
   Samsung [S32], Super Island Xiaomi [S44], dan Live Alerts OPPO (belum terverifikasi) [S51]. Ini pola paling
   bernilai untuk Track: satu strip "Pendakian aktif" di atas tab bar, terlihat di semua tab, berisi waktu, pos
   berikutnya, akurasi GPS, dan waktu sinkron terakhir.
6. **Gerak pegas menjadi bahasa bersama** (Apple: duration dan bounce [S13]; Material: token spatial dan effects
   [S20]). Di web, `linear()` sudah Baseline sejak Desember 2023 dan cukup untuk umpan balik serta transisi tanpa
   pustaka baru [S64] [S78]. Tetapi `DESIGN.md` menetapkan dial MOTION 1 [I1]; menaikkannya adalah keputusan pemilik.
7. **Kontinuitas antarhalaman kini murah di web.** View Transitions satu-dokumen Baseline sejak 14 Oktober 2025
   [S66] [S78], dan Inertia 3 menyediakan `viewTransition: true` dengan fallback otomatis [S68]. Satu momen yang
   layak: garis rute di kartu jalur berubah menjadi garis rute di halaman detail.
8. **Kendali transparansi ada di iOS 27, HarmonyOS 6, One UI, dan Windows** [S7] [S40] [S39] [S52], tetapi di
   web `prefers-reduced-transparency` hanya didukung Chromium, tidak Safari dan Firefox [S61] [S62]. Memount butuh
   pengaturan sendiri di Profil. Kode sekarang belum menangani `prefers-contrast`, `prefers-reduced-transparency`,
   maupun `forced-colors` [I4].
9. **Refraksi "liquid" tidak dapat dibawa ke web secara interoperabel.** `backdrop-filter: url(#filter-svg)` tidak
   jalan di WebKit dan belum ada standar refraksi latar [S75]. Yang dibawa adalah aturannya, bukan rupanya: kaca hanya
   di lapisan navigasi, tidak ditumpuk, tint hanya untuk aksi utama [S2].
10. **Aturan produk memotong beberapa pola ekspresif,** dan ada ketidaksesuaian dokumen. Cincin atau gelombang
    progres berpersentase untuk kesiapan melanggar R-005 dan R-014; hero foto di kartu jalur berbenturan dengan
    R-017; status OPEN bergaya hijau-centang melanggar R-012; UNKNOWN butuh gaya sendiri (R-006). IA pemilik (Home,
    Explore, Trips, Track, Progress) belum tercermin di `DESIGN.md` dan `NavLinks.tsx`, yang masih memakai Jelajah,
    Pertimbangkan, Perjalanan, Progres, Kabar [I1] [I2]. Ini OPEN ISSUE, bukan keputusan riset ini.

---

## 2. Temuan per brand

### 2.1 Apple: Liquid Glass di iOS 26 sampai iOS 27

**Linimasa** [FAKTA]
- 9 Juni 2025: Liquid Glass diumumkan untuk iOS 26, iPadOS 26, macOS Tahoe 26, watchOS 26, dan tvOS 26, sebagai
  material tembus pandang yang memantulkan dan membiaskan sekitarnya [S1]. iOS 26 rilis 15 September 2025 [S15].
- iOS 26.1 (November 2025): Settings > Display & Brightness > Liquid Glass berisi Clear atau Tinted; Tinted menaikkan
  opasitas dan kontras [S5].
- 8 Juni 2026 (WWDC26): "A new slider in Settings gives users the option to personalize Liquid Glass, adjusting it
  anywhere from ultra-clear to fully tinted" [S7]; halaman iOS 27 menyebut "more uniform refraction and improved
  contrast" [S8]. macOS 27 mengembalikan toolbar seragam di atas, sidebar tepi-ke-tepi, dan ikon sidebar berwarna [S7].
- 14 September 2026: iOS 27 rilis untuk iPhone 11 ke atas [S11].

**Material** [FAKTA]
- Dua varian. Regular adaptif dan "provides legibility regardless of context". Clear lebih transparan, butuh lapisan
  peredup, dan hanya boleh dipakai bila tiga syarat terpenuhi: di atas konten kaya media, lapisan konten tahan
  diredupkan, dan isi di atasnya tebal serta terang [S2].
- Kaca "best reserved for the navigation layer that floats above the content"; tabel dan daftar tetap di lapisan
  konten; "always avoid glass on glass"; tint "should only be used to bring emphasis to primary elements" [S2].
- Elemen kecil (navbar, tab bar) berganti terang atau gelap mengikuti latar di bawahnya; elemen besar (menu, sidebar)
  menyesuaikan tetapi tidak berganti [S2].
- Scroll edge effect menggantikan garis pemisah dengan blur halus, "not decorative", satu per tampilan, dan hanya bila
  ada UI yang melayang [S3].
- iOS 27: kaca lebih menyebarkan konten yang rumit, ada tepi gelap di sekitar elemen kaca, specular highlight lebih
  terang, dan toolbar seragam muncul di atas saat konten menggulir di bawah bar melayang [S9]. Pilihan Default di iOS
  27 lebih pekat daripada Clear di iOS 26 [S10].

**Navigasi dan bentuk** [FAKTA]
- Tab bar iPhone melayang di atas konten dan dapat menyusut saat gulir ke bawah lalu mengembang saat gulir ke atas
  (`tabBarMinimizeBehavior(.onScrollDown)`) [S4].
- Aksesori tab bar (`tabViewBottomAccessory`) untuk fitur persisten lintas aplikasi, contohnya pemutar musik [S4].
- Tab Pencarian khusus di bawah [S3] [S4]. Aksi khusus layar (misalnya tombol checkout) tidak boleh ditaruh di tab
  bar [S3]. Item bar dikelompokkan menurut fungsi dan frekuensi; simbol tidak dikelompokkan dengan teks; aksi utama
  terpisah dan diberi tint [S3].
- Sheet parsial tampil terangkat dengan kaca; saat penuh, latarnya berubah opak dan menempel ke tepi layar [S4].
- Tiga jenis bentuk: fixed, capsule (radius setengah tinggi), dan concentric (radius induk dikurangi padding) agar
  bentuk bersarang dengan rapi [S3]; SwiftUI menyediakan `.rect(corner: .containerConcentric)` [S4].
- iOS 27 dilaporkan menambah API minimisasi untuk navigation bar (`UIBarMinimization`) [S14] [BELUM TERVERIFIKASI].
  Klaim bahwa iOS 27 mengembalikan tab bar yang selalu penuh hanya muncul di sumber lemah [BELUM TERVERIFIKASI].

**Tipografi, ikon, gerak** [FAKTA]
- Tipografi "now bolder and left-aligned to improve readability in key moments like alerts and onboarding" [S3].
- Ikon toolbar monokrom; tint dipakai untuk makna seperti ajakan bertindak, bukan efek visual [S4].
- Pegas dikonfigurasi dengan duration dan bounce. Bounce 0 adalah bawaan serbaguna, sekitar 0,15 terasa lebih
  sigap, bounce dipakai di akhir gestur, dan nilai di atas sekitar 0,4 berisiko berlebihan [S13]. Pegas mempertahankan
  kecepatan saat animasi disela [S13]. Kaca `.interactive()` bereaksi dengan membesar, memantul, dan berkilau [S4].

**Aksesibilitas** [FAKTA]
- Reduce Transparency membuat kaca lebih buram. Increase Contrast membuat elemen dominan hitam atau putih dengan
  batas kontras. Reduce Motion mengurangi intensitas efek dan mematikan sifat elastis [S2].

**Prinsip WWDC26** [FAKTA]
- Delapan prinsip: Purpose, Agency, Responsibility, Familiarity, Flexibility, Simplicity, Craft, Delight [S12].
  Kutipan yang relevan: "When we say simple, we don't mean minimal"; "Delight isn't confetti or tacking on extra
  flourishes at the end"; "Things that look the same should behave the same"; "Consider removing features entirely,
  if the risks to people's safety outweigh the value" [S12].

**Kritik** [FAKTA]
- NN/g (Raluca Budiu, 10 Oktober 2025): kontrol tembus pandang menyatu dengan latar ramai; tab bar menyusut dan
  sesak karena tombol cari; jarak antar-target di bawah pedoman 0,4 cm dan area ketuk 1 x 1 cm; navigasi yang kolaps;
  animasi tanpa tujuan; pencarian yang pindah ke bawah memaksa pengguna belajar ulang [S6].
- [INFERENSI] Dua kali Apple menambah kendali pengguna (26.1 dan 27) dan memperkuat keterbacaan bawaan di 27. Ini
  pengakuan praktis bahwa versi awal terlalu transparan bagi sebagian pengguna.

**Pembanding skill** [INFERENSI]
- `ecc:liquid-glass-design` berisi API SwiftUI dan UIKit (`glassEffect`, `GlassEffectContainer`) yang tidak punya
  padanan di web. Aturan yang dapat dibawa sama dengan sesi WWDC: kaca untuk lapisan navigasi, jangan ditumpuk, jangan
  di semua tampilan. `apple-design` menerjemahkan prinsip gerak ke web (umpan balik saat pointer-down, animasi yang
  dapat disela, pegas) dan sejalan dengan [S13] [I7].

### 2.2 Google: Material 3 Expressive, Android 16 dan 17

**Linimasa** [FAKTA]
- Diumumkan 13 Mei 2025 [S16] [S29]; tiba di Pixel 6 ke atas dan Pixel Tablet lewat Android 16 QPR1 pada September
  2025 [S24].
  Android Developers menyebutnya "an expansion of Material Design 3, including research-backed updates to theming,
  components, motion, typography, and more" [S28].
- Android 17 stabil 16 Juni 2026 [S25]. Perubahan UI yang dicatat: tema gelap yang dapat membalik aplikasi terang,
  dan dialog izin lokasi yang membuat pilihan Precise dan Approximate lebih mudah dibedakan [S25].
- Google I/O 2026: sistem tata letak ekspresif, sistem spasi berbasis 8dp, daftar dan menu ekspresif, dan Material
  Android yang "Compose-first" [S26] [BELUM TERVERIFIKASI: halaman tidak terbaca, hanya ringkasan pencarian].

**Riset yang dipublikasikan** [FAKTA]
- Google Design: 46 studi, lebih dari 18.000 peserta, sejak 2022; metode eye tracking, survei, kelompok fokus.
  Peserta menemukan elemen kunci "up to four times faster" (tugas: tombol Kirim di aplikasi email yang diperbesar dan
  dikontraskan). Preferensi sampai 87% di usia 18-24. Pengguna 45+ menemukan elemen interaktif secepat pengguna muda
  [S17].
- Peringatan dari Google sendiri: "When basic interaction paradigms are broken, expressive design can lead to poor
  usability or negative sentiment" [S17].
- CHI 2026 (Frank Bentley dkk., Google dan Ipsos): 48 peserta di Chicago, 10 aplikasi Android (antara lain Gmail,
  Meet, Messages, Fitbit, Alarm). Waktu tugas 5,92 menjadi 4,75 detik (20% lebih cepat); fiksasi ke elemen yang benar
  2,40 menjadi 1,60 detik (33%); usia 45+ 4,11 menjadi 1,69 detik (59%); sentimen 3,52 menjadi 4,20 dari 5 [S18]
  [S19]. Catatan Jakob Nielsen: layar paparan pertama, yang diukur "seconds and smiles", bukan kerja nyata [S19].

**Gerak, bentuk, komponen** [FAKTA]
- Sistem pegas "motion physics" dengan enam token. Spatial untuk posisi dan boleh melampaui target; effects untuk
  warna dan opasitas tanpa melampaui. Nilai MDC-Android: FastSpatial redaman 0,9 kekakuan 1400; DefaultSpatial 0,9 dan
  700 (layar sebagian seperti bottom sheet); SlowSpatial 0,9 dan 300 (layar penuh); FastEffects 1 dan 3800;
  DefaultEffects 1 dan 1600; SlowEffects 1 dan 800 [S20].
- 35 bentuk baru dengan morphing (misalnya persegi ke squircle) dan 15 komponen baru atau diperbarui: button group,
  split button, toolbar, loading indicator, FAB menu [S24].
- Di Compose, promosi `LoadingIndicator` dan `MaterialShapes` ke stabil dibatalkan pada 1.5.0-alpha19 (6 Mei 2026),
  sedangkan FAB menu, toggle button, split button, button group, dan floating toolbar naik ke non-eksperimental [S27].
- Loading indicator untuk proses "under five seconds" dan menggantikan sebagian besar spinner indeterminate; bila
  proses dapat menjadi determinate, pakai progress indicator [S21].
- Navigation bar lama diganti flexible navigation bar yang lebih pendek. Navigation rail punya versi collapsed dan
  expanded; versi expanded menggantikan navigation drawer untuk jendela medium ke atas [S22].

**Tipografi, warna, material, haptik** [FAKTA]
- "Emphasized typography" [S16]; gaya teks lebih besar dan lebih tebal untuk hierarki; palet lebih kaya dengan
  pemisahan primary, secondary, tertiary [S24].
- Blur halus di latar shade notifikasi, Quick Settings, dan Recents; getaran saat notifikasi dilepas dari tumpukan
  [S16] [S29].

**Kritik** [FAKTA]
- 9to5Google (Abner Li, 27 Desember 2025) menyebut hasilnya "Material 3.5": pembaruan aplikasi Google hanya
  "straightforward component swaps", tidak lebih kohesif, sebagian container terasa padat, dan Google Drive kemudian
  melepas container-nya [S23].
- [INFERENSI] Google membedakan diri lewat hierarki (ukuran, warna, bentuk, containment) dan riset yang
  dipublikasikan, bukan lewat kaca. Blur hanya di permukaan sistem yang sementara.

### 2.3 Samsung One UI 8, 8.5, dan 9

**Prinsip** [FAKTA]
- Empat prinsip: fokus pada tugas, interaksi alami, nyaman dilihat, responsif. Layar dibagi "a viewing area at the
  top and an interaction area below" agar tombol terjangkau satu tangan [S30].

**Linimasa** [FAKTA, sekunder]
- One UI 8 (Android 16) stabil untuk Galaxy S25 mulai 15 September 2025 [S31]. One UI 8.5 hadir bersama Galaxy S26
  dan tersebar resmi mulai 6 Mei 2026 [S36] [BELUM TERVERIFIKASI: hanya judul rilis pers]. One UI 9 (Android 17):
  changelog 13 Mei 2026 [S37]; penyebaran ke Galaxy S26 pertengahan September 2026 [S38] [BELUM TERVERIFIKASI].

**Visual**
- One UI 8.5 (beta, 30 Oktober 2025): Quick Settings bergaya kaca, ubin bergaris tipis terutama saat nonaktif, latar
  sedikit kurang jenuh untuk keterbacaan [S33] [FAKTA].
- "Ambient Design" One UI 8.5: status bar dan navigation bar menyatu dengan layar; top app bar dan tombol navigasi
  bawah menghilang saat gulir dan kembali saat dibutuhkan [S34] [FAKTA, sekunder].
- Blur dinamis tidak merata: Galaxy Tab S10 FE mendapat overlay abu-abu, bukan blur; moderator Samsung: "developers
  didn't manage to apply blur properly on low-spec devices" [S35] [FAKTA, sekunder].
- Now Bar dan Live Notifications (One UI 7) dibuka untuk aplikasi pihak ketiga lewat Live Updates Android 16 di One
  UI 8 [S32] [FAKTA, sekunder].
- One UI 9: kustomisasi quick panel, Text Spotlight (teks yang diketuk diperbesar di jendela sendiri), indikator biru
  saat aplikasi mengakses lokasi, Select to speak [S37] [FAKTA].

**Aksesibilitas** [FAKTA]
- "Reduce transparency and blur" ("Reduce visual effects so menus and dialogs are easier to see"), "Reduce
  animations", "High contrast fonts" [S39].

### 2.4 Huawei HarmonyOS 5, 6, dan 7

- [FAKTA, sekunder] HarmonyOS 5 (merek HarmonyOS NEXT 5) diluncurkan 22 Oktober 2024 [S43]. HarmonyOS 6 diperkenalkan
  di HDC 20 Juni 2025 dan dirilis 22 Oktober 2025 [S42].
- [FAKTA] Pembaruan visual HarmonyOS 6 (firmware 6.0.0.328, mulai seri Mate dan Pura): efek semi-transparan dan kaca
  buram, navigation bar melayang di bawah "for a more immersive on-screen content", efek cahaya di layar kunci,
  beranda, panel kontrol, panel notifikasi, Galeri, Musik, dan Video; tiga level intensitas: kuat, seimbang, lemah
  [S40].
- [FAKTA] HarmonyOS 7 (HDC, 12 Juni 2026): efek 3D spasial di layar kunci; "sliders and buttons, are also getting the
  new immersive light effect, similar to Apple's liquid glass design"; klaim performa lebih dari 15% dibanding
  HarmonyOS 6.1 [S41].
- [INFERENSI] Relevansi pasar Indonesia rendah: Huawei tidak masuk lima besar pengapalan Q2 2026, dan lima besar itu
  menguasai 96% [S58]. Nilai belajarnya ada pada kendali intensitas tiga level. Portal desain resmi Huawei [S80] tidak
  dibaca di riset ini.

### 2.5 Xiaomi HyperOS 3 dan 4

- [FAKTA] HyperOS 3 (diumumkan di Tiongkok 28 Agustus 2025): ikon dan grid baru, Super Island untuk aktivitas langsung
  dengan lebih dari 70 layanan, lebih dari 100 animasi sistem disempurnakan, klaim peluncuran aplikasi 21% lebih cepat
  dan latensi sentuh 9% lebih rendah [S44].
- [BELUM TERVERIFIKASI] Versi global: HyperIsland menampilkan aktivitas langsung di bilah status, sampai tiga island,
  masing-masing dapat ditarik menjadi jendela melayang [S45].
- [FAKTA] HyperOS 4 (pertengahan Agustus 2026, Android 17): desain Soft Light Glass, "a glass-like transparent UI
  similar to Apple's Liquid Glass, but with a softer, less translucent tone" [S46].
- [FAKTA] Efek itu awalnya hanya untuk Snapdragon 8 Elite ke atas, Dimensity 9500 ke atas, dan XRING O1 ke atas.
  Alasan Xiaomi: "this UI element needs further optimization" [S47].
- [INFERENSI] Xiaomi memegang 20% pengapalan di Indonesia dan sedang menggeser volume ke rentang US$100-199 [S58].
  Sebagian besar pemilik Xiaomi di Indonesia tidak akan melihat kaca HyperOS 4 dalam waktu dekat.
- HyperOS 2 tidak diriset terpisah; riset ini berfokus pada versi 3 dan 4 yang lebih baru.

### 2.6 OPPO ColorOS 15 dan 16, ditambah vivo

- [BELUM TERVERIFIKASI] ColorOS 15 (Oktober 2024): Luminous Rendering Engine dengan animasi paralel yang dapat disela,
  lebih dari 800 animasi didesain ulang [S49]; Live Alerts berbentuk kapsul untuk aktivitas berjalan [S51].
- [FAKTA] ColorOS 16 (diperkenalkan 15 Oktober 2025): Luminous Rendering Engine "from a Parallel to a Unified Seamless
  Architecture"; fitur yang dinamai "Seamless animations" lintas sistem dan aplikasi; ikon Light Shadow; Luminous
  Motion Effects berupa "Particle and halo effects"; klaim 40% responsivitas ketuk pada aplikasi populer dan 52%
  stabilitas gulir pada aplikasi pihak ketiga dibanding ColorOS 15, dengan catatan "laboratory data" [S48].
- [BELUM TERVERIFIKASI] vivo OriginOS 6 (global mulai akhir 2025): Origin Design dengan panel tembus pandang, bayangan
  berlapis, Gradual Blur, Dynamic Glow, dan huruf vivo Sans [S50].
- [INFERENSI] Angka OPPO dan Xiaomi adalah data laboratorium vendor. Tidak dapat dipakai sebagai bukti kegunaan.

### 2.7 Microsoft Fluent 2 dan Windows 11

- [FAKTA] Prinsip Windows 11: Effortless, Calm, Personal, Familiar, Complete + Coherent. "Motion in Windows is
  reactive, direct, and context appropriate" [S54].
- [FAKTA] Material Fluent 2: Solid (paling umum, opak), Acrylic (tembus pandang, untuk permukaan sementara seperti menu
  dan popover), Mica (opak, lapisan dasar jendela), Smoke (peredup di bawah modal) [S56].
- [FAKTA] Biaya dan fallback: "Rendering acrylic surfaces is GPU-intensive, which can increase device power
  consumption and shorten battery life". Acrylic otomatis mati saat Battery Saver dan tampil sebagai warna solid bila
  Transparency effects dimatikan, Battery Saver aktif, atau aplikasi berjalan di perangkat low-end. Mode High Contrast
  memakai warna pilihan pengguna [S52].
- [FAKTA] Aturan keterbacaan: jangan menaruh teks berwarna aksen atau hyperlink di atas acrylic; jangan menumpuk atau
  menjajarkan panel acrylic karena muncul sambungan yang terlihat [S52]. Mica mengambil sampel wallpaper sekali saja
  demi performa [S53].
- [FAKTA] Prinsip gerak Fluent 2: functional, natural, consistent, appealing; sediakan pengaturan tanpa gerak, durasi
  pendek, tanpa kilatan [S55].
- [INFERENSI] Di antara sumber yang dibaca, hanya Microsoft yang mendokumentasikan secara terbuka kapan material tembus
  pandang diganti warna solid. Pola itu paling dekat dengan kebutuhan Memount.
- Tanggal rilis Fluent 2 dan versi Windows 11 terbaru tidak diverifikasi di riset ini; halaman Microsoft Learn yang
  dirujuk diperbarui Juli dan Agustus 2026 [S52] [S53] [S54].

### 2.8 Konteks pasar Indonesia

- [FAKTA] StatCounter Agustus 2026, pangsa lalu lintas web seluler (bukan jumlah perangkat): Android 77,07%, iOS 22,88%
  [S57].
- [FAKTA] Omdia Q2 2026, pengapalan: Transsion 26%, Xiaomi 20%, Samsung 18%, OPPO 17%, vivo 15%; kelimanya 96% [S58].
- [FAKTA] Rujukan perangkat uji 2026 dari Alex Russell: Samsung Galaxy A24 4G atau setara (Helio G99, Exynos 1330),
  jaringan 9 Mbps turun, 3 Mbps naik, RTT 100 ms; anggaran jalur kritis untuk halaman berat JavaScript dengan target 3
  detik: 1,2 MiB total dan 0,62 MiB JavaScript [S77].
- [INFERENSI] Hampir seperempat lalu lintas web berasal dari iOS, jadi batasan Safari tetap penting
  (`prefers-reduced-transparency` dan `navigator.vibrate` tidak didukung) [S62] [S73].
- Celah riset: skin Transsion (XOS, HiOS, itel OS), vendor nomor satu di Indonesia, tidak diriset di sini.

---

## 3. Tren lintas brand

| # | Tren | Dipakai oleh | Bukti kegunaan | Untuk Memount | Alasan |
|---|---|---|---|---|---|
| T1 | Material tembus pandang di lapisan navigasi | Apple [S1]; Huawei [S40] [S41]; Xiaomi [S46]; Samsung [S33]; vivo [S50]; Microsoft acrylic [S52]; Google hanya di shade [S16] | Campuran sampai negatif: kritik keterbacaan [S6]; Apple mundur dua kali [S5] [S7]; NN/g: pakai hemat, blur lebih kuat di latar ramai [S60] | Sebagian, bersyarat (P1) | Memisahkan navigasi dari konten itu berguna, tetapi biaya GPU nyata [S52] [S47] dan tidak cocok di atas kanvas peta |
| T2 | Kendali intensitas oleh pengguna | Apple [S5] [S7]; Huawei tiga level [S40]; Samsung [S39]; Windows [S52] | Respons langsung atas keluhan keterbacaan [S9]; NN/g menyarankan opsi kendali [S60] | Ya (P1, bersama T1) | Prinsip Agency [S12]; Safari tidak memberi sinyal media query [S62] |
| T3 | Bar yang menyusut atau hilang saat gulir | Apple tab bar [S4]; Samsung Ambient Design [S34] | Negatif untuk tab bar (ketukan tambahan, sesak) [S6]; netral-positif untuk header [S59] | Tidak untuk tab bar; boleh untuk bilah atas halaman panjang (P2) | Navigasi utama harus satu ketukan, dan di lapangan tangan pendaki sibuk |
| T4 | Gerak pegas yang dapat disela | Apple [S13]; Google [S20]; OPPO [S49]; Xiaomi [S44] | Prinsip kontinuitas kecepatan [S13]; tidak ada studi yang mengisolasinya | Ya, terbatas (P1) | Umpan balik dan kontinuitas; murah bila hanya `transform` dan `opacity` [S78] |
| T5 | Hierarki ekspresif: ukuran, warna, containment, huruf tebal | Google [S17] [S18]; Apple [S3] | Terkuat: 20%, 33%, 59% [S18] [S19]; tombol Kirim 4x [S17] | Ya (P0) | Satu keputusan utama per layar; membantu pengguna 45+ |
| T6 | Bentuk capsule, konsentris, squircle, morphing | Apple [S3]; Google 35 bentuk [S24]; Xiaomi ikon [S44] | Konsentris: kerapian tanpa studi. Morphing: tanpa bukti, API-nya dikembalikan ke eksperimental [S27] | Konsentris ya (P1); morphing tidak | Kerapian murah; morphing mahal dan dekoratif |
| T7 | Status langsung aktivitas berjalan | Apple [S4]; Samsung [S32]; Xiaomi [S44]; OPPO [S51] | Adopsi lintas platform; tidak ditemukan studi kegunaan | Ya, untuk Track (P0) | Pendaki perlu status tanpa membuka layar lain |
| T8 | Tepi gulir lembut menggantikan garis pemisah | Apple [S3]; Samsung [S34] | Pedoman Apple: fungsional, satu per tampilan [S3] | Ya, versi murah (P2) | `mask-image` Baseline, tanpa blur [S78] |
| T9 | Haptik yang terikat gestur | Google [S16]; Apple [S4] | Tidak ditemukan studi | Opsional (P2) | Di web hanya Chrome Android dan Samsung Internet [S73] |
| T10 | Efek dekoratif: partikel, halo, 3D spasial, wallpaper AI | OPPO [S48]; Huawei [S41]; Xiaomi [S44] | Tanpa bukti kegunaan; klaim performa vendor [S48] | Tidak | Mode Operate dan ENERGY 1 [I1]; biaya GPU |
| T11 | Efek digerbang kemampuan perangkat | Xiaomi [S47]; Samsung [S35]; Microsoft [S52] | Bukti biaya dari vendor sendiri | Ya, sebagai prinsip (P0) | Fungsi sama untuk semua; hiasan hanya bila perangkat mampu |
| T12 | Rail untuk layar lebar menggantikan drawer | Google [S22] | Pedoman platform | Ya, desktop dan tablet (P1) | Lima tujuan yang sama di semua ukuran layar |
| T13 | Pencarian dalam jangkauan ibu jari | Apple tab Pencarian [S3]; aplikasi Google [S23] | NN/g: pemindahan mendadak memaksa belajar ulang [S6] | Sudah ada: kotak cari Jelajah selalu terlihat [I1] | Jangan dijadikan tab keenam |

### 3.1 Pembeda dan daya saing

- [FAKTA untuk tanggal, INFERENSI untuk pola] Apple memimpin arah visual, dan pesaing Android mengikuti dalam hitungan
  bulan: HyperOS 3 (Agustus 2025) [S44], Quick Settings One UI 8.5 bergaya kaca (beta Oktober 2025) [S33], OriginOS 6
  (akhir 2025) [S50], efek cahaya HarmonyOS 7 (Juni 2026) [S41], Soft Light Glass HyperOS 4 (Agustus 2026) [S46].
- [INFERENSI] Google mengambil jalan lain: ekspresivitas dan fisika gerak dengan riset yang dipublikasikan [S17] [S18].
  Microsoft paling matang dalam aturan material dan fallback [S52] [S53]. Vendor Tiongkok bersaing lewat klaim
  kelancaran dan island status langsung [S44] [S48]. Samsung bersaing lewat kustomisasi dan jangkauan satu tangan
  [S30] [S37].
- [INFERENSI] Yang dilihat mayoritas pengguna Indonesia sehari-hari adalah skin Android di kelas harga menengah ke
  bawah, sering tanpa efek premium [S47] [S58]. Bagi mereka, "terasa seperti OS modern" lebih mungkin berarti hierarki
  jelas, respons cepat, dan bentuk rapi daripada kaca. Hipotesis ini belum diuji pada pengguna Memount.

### 3.2 Terbukti menaikkan kegunaan, atau sekadar estetika

| Kelompok | Elemen | Dasar |
|---|---|---|
| Terukur, dengan catatan vendor | Hierarki ekspresif untuk aksi dan informasi utama | [S17] [S18] [S19] |
| Pedoman mapan | Target sentuh dan jaraknya; kontras dan fallback material; header partially persistent; pegas untuk kontinuitas | [S6] [S52] [S60] [S59] [S13] |
| Estetika tanpa bukti kegunaan yang ditemukan | Refraksi, morphing bentuk, partikel dan halo, 3D spasial, adaptasi terang-gelap otomatis | [S24] [S41] [S48] |
| Bukti negatif | Tab bar yang menyusut dan sesak; kaca bening di atas latar ramai | [S6] |

---

## 4. Terjemahan ke web: dukungan, biaya di perangkat kelas bawah, fallback

| Pola OS | Teknik web | Dukungan per 25 Sep 2026 | Biaya di Android kelas bawah | Fallback dan aksesibilitas |
|---|---|---|---|---|
| Kaca navigasi (Liquid Glass Regular, acrylic) | `backdrop-filter: blur() saturate()` di atas latar semi-transparan | Sekitar 96% penggunaan global; Chrome 76+, Firefox 103+, Safari (prefiks `-webkit-` di versi lama) [S63] | Tinggi. Bug Firefox Android: gulir tersendat karena `backdrop-filter`, profilnya GPU-bound [S76]; Microsoft menyebutnya GPU-intensive [S52]. [INFERENSI] Di atas kanvas WebGL MapLibre, blur dihitung ulang setiap bingkai saat peta digeser | Bawaan solid. `prefers-reduced-transparency: reduce` hanya Chromium [S61] [S62]; `prefers-contrast: more` menjadi solid dengan batas tegas; `forced-colors: active` memakai warna sistem; pengaturan di Profil |
| Refraksi dan lensing | `backdrop-filter: url(#svg)` dengan `feDisplacementMap` | Tidak interoperabel; WebKit tidak mendukung [S75] | Sangat tinggi | Jangan dipakai |
| Tepi gulir lembut (scroll edge effect) | `mask-image: linear-gradient(...)` di tepi wadah gulir | Baseline luas sejak 7 Desember 2023 [S78] | Rendah | Tidak perlu; tanpa blur |
| Pegas | `transition-timing-function: linear(...)` dari generator | Baseline luas sejak 11 Desember 2023: Chrome 113, Firefox 112, Safari 17.2 [S64] [S78] | Rendah bila hanya `transform` dan `opacity` [S78] | Easing biasa ditulis sebelum `linear()`; `prefers-reduced-motion` sudah ditangani global [I4] |
| Gestur yang dapat disela (sheet ditarik) | Pointer Events dan pegas kecil di JavaScript yang meneruskan kecepatan lepas | Tidak diperiksa di riset ini | Sedang, bergantung skrip | Tombol buka dan tutup untuk keyboard dan pembaca layar |
| Kontinuitas halaman | View Transitions satu-dokumen lewat opsi `viewTransition` Inertia 3 | Baseline baru sejak 14 Oktober 2025: Chrome 111, Firefox 144, Safari 18 [S66] [S78]. Versi antar-dokumen belum merata dan menjadi fokus Interop 2026 [S67] | Rendah sampai sedang: snapshot diambil dari compositor tanpa layout ulang [S69]. Untuk transisi antar-dokumen (bukan kasus SPA Memount), data RUM mencatat LCP ponsel naik sekitar 70 ms [S70] | Inertia jatuh ke perpindahan biasa bila API tidak ada [S68]; bungkus dengan `prefers-reduced-motion` [S69] |
| Bar yang menyusut | Scroll-driven animations (`animation-timeline: scroll()`) | Chrome dan Edge 115+, Safari 26+; Firefox belum [S71] [S78] | Rendah (berjalan di compositor) | Deteksi dengan `@supports ((animation-timeline: scroll()) and (animation-range: 0% 100%))`; bila dekoratif, tanpa fallback [S78] |
| Sudut squircle | `corner-shape: squircle` | Chromium 139+, Samsung Internet 30+; Firefox dan Safari belum [S72] | Rendah | `border-radius` biasa |
| Radius konsentris | `border-radius: calc(var(--radius-luar) - var(--pad))` | CSS dasar | Nol | Tidak perlu |
| Haptik | `navigator.vibrate()` | Chrome Android, Samsung Internet; tidak Safari iOS, tidak Firefox Android [S73] | Rendah | Tidak pernah satu-satunya umpan balik; dapat dimatikan |
| Petunjuk kemampuan perangkat | `navigator.deviceMemory` atau Client Hint `Device-Memory` | Bukan Baseline; hanya konteks aman (HTTPS) [S74] | Nol | Hanya petunjuk; pilihan pengguna tetap menang |

### 4.1 Tangga material yang diusulkan

[REKOMENDASI] Satu kelas untuk chrome (tab bar, toolbar melayang, strip pendakian aktif). Bawaan solid. Kaca hanya
bila pengguna mengizinkan, perangkat lolos petunjuk kemampuan, dan elemen tidak berada di atas peta. Aturan penurun
ditulis sesudah aturan kaca dengan spesifisitas yang sama, jadi selalu menang. Sketsa ini belum diterapkan dan belum
diuji.

```css
.chrome {
  background: rgb(var(--surface));
  border-top: 1px solid rgb(var(--border-subtle));
}

@supports ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
  /* :where() menurunkan spesifisitas agar aturan penurun di bawah tetap menang */
  :where(:root[data-efek="penuh"]) .chrome:where(:not([data-di-atas-peta])) {
    background: rgb(var(--surface) / 0.78);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
    backdrop-filter: blur(16px) saturate(1.2);
  }
}

@media (prefers-reduced-transparency: reduce), (prefers-contrast: more), (forced-colors: active) {
  .chrome {
    background: rgb(var(--surface));
    -webkit-backdrop-filter: none;
    backdrop-filter: none;
  }
}

@media (prefers-contrast: more) {
  .chrome { border-top-color: rgb(var(--text-primary)); }
}
```

Pilihan pengguna disimpan di cookie dan dirender server sebagai atribut di `<html>`, sama seperti tema [I1], supaya
tidak berkedip saat halaman dimuat. Pilihan "Otomatis" baru menjadi `penuh` bila petunjuk kemampuan mendukung,
misalnya memori perangkat 4 GB atau lebih lewat Client Hint `Device-Memory` di server atau `navigator.deviceMemory`
di klien [S74]. Ambang 4 GB adalah usulan yang harus diukur, bukan angka bersumber. Urutan solid lalu kaca tidak
merugikan keterbacaan, sedangkan urutan sebaliknya bisa.

### 4.2 Aturan biaya

1. Animasikan hanya `transform` dan `opacity` [S78]. Jangan menganimasikan radius blur, lebar, atau tinggi.
   [INFERENSI untuk radius blur]
2. Tidak ada `backdrop-filter` di atas kanvas peta, dan tidak di atas daftar panjang pada perangkat yang tidak lolos
   petunjuk kemampuan [S52] [S76]. [INFERENSI]
3. Paling banyak dua elemen kaca per layar, sesuai batas antislop R-10 [I7], larangan kaca di atas kaca dari Apple
   [S2], dan larangan panel acrylic berjajar dari Microsoft [S52].
4. Tanpa pustaka animasi baru. Untuk halaman berat JavaScript, anggaran JavaScript target 3 detik adalah 0,62 MiB
   [S77]; [INFERENSI, belum diukur] React dan MapLibre sudah memakai sebagiannya [I5]. Setiap dependensi baru butuh
   persetujuan pemilik.
5. Ukur, jangan menebak: trace performa Chrome saat menggulir daftar dan menggeser peta di perangkat kelas Galaxy A24
   4G atau emulasi setara [S77], untuk varian solid dan kaca.
6. `prefers-reduced-motion` tetap dihormati sekali untuk seluruh aplikasi [I4]; di Android pengaturan "Remove
   animations" memicu preferensi ini [S79]. Bila perubahan keadaan perlu terlihat, gerak diganti pudar singkat, bukan
   dihapus [S78] [I7].

### 4.3 Temuan di kode sekarang [FAKTA]

- `ConsiderationTray.tsx` baris 20 memakai `bg-surface/95 ... backdrop-blur` pada baki yang menempel di atas navigasi
  bawah [I3]. [INFERENSI] Pada opasitas 95%, blur hampir tidak terlihat tetapi biayanya tetap dibayar setiap bingkai
  gulir. Usul: ukur, lalu hapus blur bila tidak ada beda yang terlihat.
- `app.css` baris 457-465 menurunkan durasi animasi dan transisi menjadi 1 ms saat `prefers-reduced-motion: reduce`
  [I4]; `Trails/Index.tsx` baris 35 membaca preferensi yang sama lewat `matchMedia` [I8].
- Tidak ada penanganan `prefers-contrast`, `prefers-reduced-transparency`, `forced-colors`, `navigator.vibrate`, atau
  `navigator.deviceMemory` di `resources/js` dan `resources/css` (hasil `grep`) [I4].
- `--radius-control` bernilai `0.375rem` (`app.css` baris 72) [I4].
- `package.json` memuat React ^19.3.0, `@inertiajs/react` ^3.7.1, `maplibre-gl` ^4.7.1, tanpa pustaka animasi [I5].

---

## 5. Rekomendasi untuk Memount

Semua butir di bagian ini [REKOMENDASI]. P0 = kerjakan dulu dan tidak mengubah arah produk. P1 = sesudah P0, sebagian
butuh persetujuan. P2 = coba dan uji dulu. Kolom Benturan menyebut aturan produk atau keputusan `DESIGN.md` yang
tersentuh.

### 5.1 Sistem desain

| Elemen | Rekomendasi | Prioritas | Dasar | Benturan |
|---|---|---|---|---|
| Material | Tiga lapis: kanvas dan konten solid; chrome (tab bar, toolbar peta, strip pendakian aktif) solid bawaan; kaca hanya peningkatan bersyarat untuk tab bar di luar tampilan peta (§4.1). Paling banyak dua elemen kaca per layar. Tidak pernah pada kartu, status resmi, laporan komunitas, atau modal | P0 untuk lapis solid dan fallback; P1 untuk kaca | [S2] [S47] [S52] [S60] [I7] | `DESIGN.md` menetapkan satu elevasi dan kartu bergaris rambut [I1]; kategori material baru butuh persetujuan |
| Warna | Pertahankan satu warna merek dan tiga semantik [I1]. Hijau terisi hanya untuk satu aksi utama per layar. Tidak ada teks tautan atau aksen di atas permukaan tembus pandang | P0 | [S2] [S52] | Tidak ada |
| Status domain | UNKNOWN punya gaya sendiri: garis putus-putus, ikon, dan label "Belum dinilai", bukan abu redup yang terbaca rendah. OPEN tampil sebagai chip netral dengan sumber dan waktu, tanpa hijau atau centang, disertai kalimat bahwa status buka bukan jaminan aman. Label kecocokan selalu berupa teks salah satu dari tiga label publik | P0 | R-004, R-006, R-007, R-012 | Garis jalur "belum dinilai" di peta sekarang berwarna abu-abu [I1]; periksa agar tidak terbaca sebagai nilai rendah |
| Bentuk | Skala radius konsentris: `--radius-luar` untuk sheet dan panel, radius dalam = luar dikurangi padding; capsule hanya untuk chip dan kontrol segmen; `rounded-control` tetap untuk isian dan tombol. `corner-shape: squircle` sebagai peningkatan progresif | P1 (konsentris); P2 (squircle) | [S3] [S72] | Nilai radius adalah keputusan merek [I1] |
| Gerak | Naikkan dial MOTION dari 1 ke 2 yang dibatasi daftar tertutup: umpan balik tekan; buka-tutup sheet; kartu jalur ke detail; pos tercapai di Track; butir persiapan (sudah ada). Token `--ease-pegas-cepat` dan `--ease-pegas-sedang` dibuat dengan generator `linear()` [S65], meniru redaman 0,9 untuk gerak posisi dan 1,0 untuk warna dan opasitas [S20]; bounce 0 sebagai bawaan, bounce kecil hanya sesudah gestur [S13]. Tetap tanpa animasi masuk bertahap, paralaks, dan animasi berulang | P0 untuk keputusan; P1 untuk implementasi | [S13] [S20] [S64] [S78] | Mengubah MOTION 1 di `DESIGN.md` [I1]; butuh persetujuan pemilik |
| Tipografi | Tambah peran "tegas": angka kunci (jarak, naik total, waktu tempuh, pos berikutnya) lebih besar dan lebih tebal dengan `tabular-nums`; satu judul tegas per layar; rata kiri untuk dialog dan onboarding. Tetap Plus Jakarta Sans dan Newsreader | P0 | [S3] [S16] [S18] [I1] | Tidak ada; tanpa huruf baru |
| Ikon | Phosphor regular tetap. Tab aktif memakai bobot fill, label, garis, dan `aria-current`, sehingga yang berubah adalah bentuk, bukan warna saja. Ikon toolbar monokrom; warna hanya untuk makna (komunitas, peringatan, bahaya) | P1 | [S4] [I1] | `DESIGN.md` menetapkan bobot regular [I1]; bobot fill untuk tab aktif perlu disetujui |
| Target sentuh | Kontrol utama minimal 44 x 44 px (aturan proyek); kontrol utama Track lebih besar lagi; tab bar tetap lima tujuan | P0 | [S6] | Tidak ada |
| Pemuatan | Proses di bawah lima detik: indikator ringan. Lebih lama: teks status yang jujur, misalnya "Menunggu sinyal" atau "Terakhir diperbarui 07.40" | P1 | [S21] | R-013 untuk cuaca |

### 5.2 Per area

**Home (dasbor personal)**
- P0. Satu fokus: kartu "Perjalanan berikutnya" berisi nama jalur (bukan hanya gunung), tanggal, label kecocokan,
  status resmi dengan sumber dan waktu, cuaca dengan sumber, waktu pembaruan, dan area rujukan, serta "yang masih
  kurang sebelum berangkat". Hierarki lewat ukuran dan containment (T5), bukan bento [I1]. (R-001, R-002, R-012, R-013)
- P0. Kesiapan ditulis sebagai hitungan, misalnya "5 dari 8 butir persiapan selesai", bukan persen atau cincin.
  (R-005, R-014)
- P1. Rekomendasi paling banyak tiga, masing-masing dengan label dan satu alasan. Jalur yang kekurangan data kritis
  tidak muncul. (R-004, R-008)
- P1. Ringkasan progres membandingkan pengguna dengan periode sebelumnya miliknya sendiri. (R-015)
- P1. Bila pendakian aktif, strip status langsung muncul di atas tab bar (lihat Track).
- Catatan: tab Kabar yang ada sekarang [I2] tidak muncul di IA pemilik. Letaknya, di Home atau di lapisan komunitas,
  adalah keputusan pemilik (§5.3).

**Explore (peta, jalur, gunung, saringan, tersimpan)**
- P0. Kontrol yang melayang di atas peta (lokasi saya, lapisan, saring) dibuat sebagai toolbar melayang yang solid,
  bukan kaca, karena berada di atas kanvas WebGL (T1, T11).
- P0. Visual utama kartu jalur adalah bentuk rute atau profil elevasi yang digambar dari data jalur, sejalan dengan
  tanda "Jalur Berpos" [I1], bukan foto komunitas (R-017). [INFERENSI] Cara ini juga menghemat unduhan gambar di
  jaringan 9 Mbps [S77].
- P1. View transition dari kartu ke detail: garis rute yang sama berubah ukuran dan posisi, dengan
  `view-transition-name` unik per jalur, dan mati saat reduced motion [S68] [S69].
- P1. Saringan di bottom sheet dengan tombol tutup yang jelas; chip saringan aktif tetap dapat dihapus satu per satu
  [I1].
- P1. Di tablet dan desktop, lima tujuan tampil sebagai rail di sisi kiri, bukan menu hamburger (T12) [S22].
- P2. Daftar sebagai sheet yang dapat ditarik di atas peta (intip, setengah, penuh), mengikuti pola sheet parsial
  [S4]. Uji kegunaan dulu, karena ini mengganti keputusan sakelar Daftar/Peta [I1].

**Trips (Route Fit, bandingkan, rencana trip, persiapan, cek kesiapan)**
- P0. Route Fit menampilkan label dan bagian "Mengapa label ini" berisi faktor yang cocok, yang perlu persiapan, dan
  yang datanya belum tersedia. Data yang hilang ditulis sebagai belum tersedia, bukan rendah. (R-003, R-006, R-007)
- P0. Bandingkan: kolom sejajar, perbedaan disorot netral per baris, tanpa mahkota, tanpa kata "terbaik", tanpa urutan
  bawaan berdasarkan skor. Urutan kolom mengikuti urutan pengguna menambahkan jalur. (R-016)
- P1. Button group untuk berganti sudut pandang perbandingan, misalnya Ringkas, Elevasi, Logistik [S24] [S27].
- P0. Persiapan: satu momen gerak yang sudah ada dipertahankan [I1]. Progres berupa segmen per butir, bukan gelombang
  atau persen. (R-005)
- P0. Cek kesiapan memakai bahasa pendukung keputusan, tidak pernah "aman" atau "layak berangkat". (R-014)

**Track (mulai mendaki, peta langsung, GPS, checkpoint, elevasi, selesai)**
- P0. Tanpa kaca sama sekali. Permukaan solid berkontras tinggi untuk terik matahari dan hemat baterai (T1) [S52].
- P0. Kontrol utama besar dan tidak pernah bersembunyi saat gulir (T3) [S6].
- P0. Strip "Pendakian aktif" di atas tab bar, terlihat di semua tab: waktu berjalan, pos berikutnya, akurasi GPS
  dalam meter, keadaan sinyal, waktu sinkron terakhir, dan satu ketukan kembali ke peta. Ini padanan web dari
  aksesori tab bar Apple [S4] dan status langsung Android (T7). Ia menyajikan fitur Track yang sudah ada, bukan fitur
  baru.
- P1. Saat pos tercapai: satu umpan balik visual, ditambah getaran pendek bila didukung dan diizinkan [S73]. Tanpa
  suara bawaan.
- P1. Cuaca di lapangan selalu disertai waktu pembaruan dan tanda basi saat offline. (R-013)
- P2. [INFERENSI] Android 17 membuat pilihan lokasi Precise dan Approximate lebih mencolok [S25]. Bila peramban hanya
  menerima lokasi perkiraan, Track perlu menampilkan akurasinya dan cara memperbaikinya. Perilaku ini harus diuji di
  perangkat nyata.

**Progress (riwayat, statistik, target, pencapaian)**
- P1. Perbandingan dengan diri sendiri: musim ini dibanding musim lalu, pendakian ini dibanding pendakian serupa
  sebelumnya; satu angka utama yang tegas per layar (T5). (R-015)
- P1. Pencapaian sebagai tonggak pribadi, tanpa papan peringkat dan tanpa klaim keahlian. (R-015, BR-11)
- P1. "Bagikan laporan" dari ringkasan pendakian masuk ke lapisan komunitas.

**Profile (pengalaman, preferensi, perlengkapan, pengaturan, privasi)**
- P1. Bagian Tampilan: Tema (sudah ada), Efek transparan (Otomatis atau Solid), Gerak (Ikuti sistem atau Kurangi),
  Getar (hanya muncul bila perangkat mendukung) (T2) [S7] [S39] [S40].
- P1. Privasi lokasi dijelaskan tepat di pengaturannya, sejalan dengan indikator lokasi yang kini ditampilkan OS [S37].

**Community (lapisan lintas halaman, bukan tab)**
- P0. Tata bahasa visual yang sama di semua titik masuk: warna `community`, ikon asal, label "Laporan pendaki", waktu,
  dan status verifikasi. Selalu terpisah dari keterangan resmi dan tidak pernah bergaya resmi. (R-009, R-010)
- P0. Titik masuk kontekstual sesuai IA pemilik: Explore → Jalur → Laporan komunitas; Progress → Pendakian → Bagikan
  laporan.
- P0. Foto komunitas tidak menjadi visual utama bawaan. (R-017)

### 5.3 Keputusan yang dibutuhkan pemilik (OPEN ISSUE)

1. **IA belum sinkron dengan dokumen dan kode.** `DESIGN.md` dan `NavLinks.tsx` baris 18-26 masih memakai Jelajah,
   Pertimbangkan, Perjalanan, Progres, Kabar [I1] [I2], sedangkan IA pemilik adalah Home, Explore, Trips, Track,
   Progress, dengan Profile terpisah dan Community sebagai lapisan. Pembaruan `PRD.md` §145 dan `DESIGN.md` perlu
   governance record (R-021, `PRD.md` §146). Nama tab dalam Bahasa Indonesia juga perlu diputuskan.
2. Dial MOTION dari 1 ke 2 terbatas (§5.1).
3. Kategori material chrome dan kaca bersyarat, yang menambah elevasi di luar `shadow-overlay` [I1].
4. Skala radius konsentris dan kemungkinan menaikkan `--radius-control` [I1] [I4].
5. Bobot ikon fill untuk tab aktif [I1].
6. Sheet daftar yang dapat ditarik di Explore sebagai pengganti sakelar Daftar/Peta (P2) [I1].
7. Riset ini tidak mengusulkan dependensi baru. Bila kelak dibutuhkan pustaka pegas untuk gestur, itu keputusan
   terpisah.

### 5.4 Cara membuktikan sebelum menyatakan berhasil

- Uji kegunaan sesuai `docs/PROTOKOL-UJI-KEGUNAAN.md` dengan tugas nyata: temukan jalur yang cocok; bandingkan dua
  jalur tanpa diminta memilih; mulai pendakian dan temukan pos berikutnya dalam keadaan offline. Catat waktu tugas dan
  kesalahan. Bila kaca dicoba, bandingkan varian solid dan kaca. Hasil tidak boleh diperkirakan.
- Ukur performa di perangkat kelas Galaxy A24 4G atau emulasi setara [S77]: gulir daftar dengan tab bar solid dan
  kaca, geser peta dengan dan tanpa elemen melayang.
- Perluas `ColourContrastTest` untuk permukaan chrome di kedua tema, termasuk keadaan kontras tinggi.

---

## 6. Yang tidak boleh ditiru

| # | Pola | Asal | Alasan |
|---|---|---|---|
| 1 | Tab bar yang menyusut menjadi satu ikon saat gulir | iOS 26 [S4] | Menambah satu ketukan untuk berpindah tab dan membuat bar sesak [S6] |
| 2 | Kaca bening (varian Clear) di atas konten atau peta; kaca di atas kaca; kaca pada kartu dan daftar | Apple [S2] | Apple sendiri membatasinya [S2]; kritik keterbacaan [S6]; biaya GPU [S52] |
| 3 | Refraksi lewat filter SVG di `backdrop-filter` | Demo web "liquid glass" | Tidak interoperabel, WebKit tidak mendukung [S75]; mahal |
| 4 | Menganimasikan radius blur untuk kesan material yang muncul | Liquid Glass; skill apple-design [I7] | [INFERENSI] Blur dihitung ulang setiap bingkai; di luar batas "hanya transform dan opacity" [S78] |
| 5 | Bar yang berganti terang dan gelap mengikuti konten di bawahnya | Apple [S2] | [INFERENSI] Kontras informasi keselamatan harus dapat diprediksi dan diuji `ColourContrastTest` [I1] |
| 6 | Partikel, halo, efek 3D spasial, wallpaper AI | ColorOS 16 [S48]; HarmonyOS 7 [S41]; HyperOS 3 [S44] | Dekoratif, mahal, tidak membantu keputusan; bertentangan dengan mode Operate [I1] |
| 7 | Morphing bentuk sebagai hiasan dan pustaka 35 bentuk | Material 3 Expressive [S24] | Tanpa bukti kegunaan; API-nya sendiri dikembalikan ke eksperimental [S27] |
| 8 | Ekspresivitas "wild and way-too-playful" | Temuan preferensi Google [S17] | Preferensi paparan pertama bukan kinerja jangka panjang [S19]; produk ini menyangkut keselamatan |
| 9 | Progres bergelombang atau cincin persen untuk kesiapan atau kecocokan | Material 3 Expressive [S21] | Melanggar R-005 dan R-014 |
| 10 | Foto layar penuh sebagai visual utama (layar kunci sinematik, wallpaper berkedalaman) | HyperOS 3 [S44]; HarmonyOS 7 [S41] | Bila fotonya dari komunitas, melanggar R-017; berat di jaringan lambat [S77] |
| 11 | Efek yang mengubah informasi atau fungsi menurut kelas perangkat | Gating HyperOS 4 [S47] | Di Memount hanya hiasan yang boleh berbeda; informasi dan fungsi sama untuk semua |
| 12 | Angka kelancaran vendor sebagai bukti atau teks pemasaran | OPPO [S48]; Xiaomi [S44] | Data laboratorium vendor; bertentangan dengan R-023 dan antislop R-36 bila dikutip sebagai fakta produk |
| 13 | Pencarian dijejalkan ke tab bar sebagai tab keenam | iOS 26 [S3] | Bar sesak dan pengguna harus belajar ulang [S6] |
| 14 | Getaran atau warna sebagai satu-satunya umpan balik | Umum | Getar tidak ada di Safari iOS [S73]; status tidak boleh hanya lewat warna (`CLAUDE.md` §6) |

---

## 7. Daftar sumber

Semua diakses 25 September 2026. Resmi = dokumen pemilik platform. Sekunder = media atau blog. "Ringkasan" = isi hanya
terbaca lewat ringkasan hasil pencarian, bukan halaman penuh.

**Apple**
- S1. Apple Newsroom, "Apple introduces a delightful and elegant new software design", 9 Jun 2025. Resmi, ringkasan. https://www.apple.com/newsroom/2025/06/apple-introduces-a-delightful-and-elegant-new-software-design/
- S2. Apple Developer, WWDC25 "Meet Liquid Glass" (sesi 219). Resmi. https://developer.apple.com/videos/play/wwdc2025/219/
- S3. Apple Developer, WWDC25 "Get to know the new design system" (sesi 356). Resmi. https://developer.apple.com/videos/play/wwdc2025/356/
- S4. Apple Developer, WWDC25 "Build a SwiftUI app with the new design" (sesi 323). Resmi. https://developer.apple.com/videos/play/wwdc2025/323/
- S5. TechCrunch, "iOS 26.1 lets you turn down Liquid Glass transparency", 4 Nov 2025. Sekunder, ringkasan. https://techcrunch.com/2025/11/04/ios-26-1-lets-you-turn-down-liquid-glass-transparency
- S6. Raluca Budiu, NN/g, "Liquid Glass Is Cracked, and Usability Suffers in iOS 26", 10 Okt 2025. https://www.nngroup.com/articles/liquid-glass/
- S7. Apple Newsroom, "Apple unveils next generation of Apple Intelligence, Siri AI, and more", 8 Jun 2026. Resmi. https://www.apple.com/newsroom/2026/06/apple-unveils-next-generation-of-apple-intelligence-siri-ai-and-more/
- S8. Apple, halaman iOS 27. Resmi. https://www.apple.com/os/ios/
- S9. Hartley Charlton, MacRumors, "Here's How Liquid Glass Is Changing in iOS 27", 10 Jun 2026. Sekunder. https://www.macrumors.com/2026/06/10/how-liquid-glass-is-changing-in-ios-27/
- S10. Federico Viticci, MacStories, "iOS and iPadOS 27: The MacStories Review", hlm. 2, Sep 2026. Sekunder. https://www.macstories.net/stories/ios-and-ipados-27-review/2/
- S11. MacRumors, "Apple Announces iOS 27 Release Date", 9 Sep 2026. Sekunder. https://www.macrumors.com/2026/09/09/apple-announces-ios-27-release-date/
- S12. Apple Developer, WWDC26 "Principles of great design" (sesi 250). Resmi. https://developer.apple.com/videos/play/wwdc2026/250/
- S13. Apple Developer, WWDC23 "Animate with springs" (sesi 10158). Resmi. https://developer.apple.com/videos/play/wwdc2023/10158/
- S14. Anton Gubarenko, "iOS 27: UIBarMinimization". Blog pengembang, ringkasan. https://antongubarenko.substack.com/p/ios-27-uibarminimization
- S15. Wikipedia, "iOS 26" (tanggal rilis). Sekunder, ringkasan. https://en.wikipedia.org/wiki/IOS_26

**Google**
- S16. Google Blog, peluncuran Material 3 Expressive untuk Android dan Wear OS, 13 Mei 2025. Resmi. https://blog.google/products-and-platforms/platforms/android/material-3-expressive-android-wearos-launch/
- S17. Google Design, "Expressive Design: Google's UX Research". Resmi. https://design.google/library/expressive-material-design-google-research
- S18. F. Bentley dkk., "Usability Hasn't Peaked: Exploring How Expressive Design Overcomes the Usability Plateau", CHI 2026. Halaman ACM membalas 403; angka dari S19. https://doi.org/10.1145/3772318.3790373
- S19. Jakob Nielsen, UX Tigers, "UX Roundup: Usability Still Improves", 31 Agu 2026. Sekunder. https://www.uxtigers.com/post/ux-roundup-20260831
- S20. material-components-android, dokumentasi "Motion" (token pegas). Resmi. https://github.com/material-components/material-components-android/blob/master/docs/theming/Motion.md
- S21. material-components-android, dokumentasi "Loading indicator". Resmi. https://github.com/material-components/material-components-android/blob/master/docs/components/LoadingIndicator.md
- S22. 9to5Google, "Material 3 Expressive drops navigation drawers, short bottom bars back", 14 Mei 2025. Sekunder. https://9to5google.com/2025/05/14/material-3-expressive-navigation/
- S23. Abner Li, 9to5Google, "Recap: Expressive Android, but Material '3.5' apps", 27 Des 2025. Sekunder. https://9to5google.com/2025/12/27/recap-material-3-expressive/
- S24. Android Authority, "Material 3 Expressive deep dive", diperbarui 16 Des 2025. Sekunder. https://www.androidauthority.com/google-material-3-expressive-features-changes-availability-supported-devices-3556392/
- S25. Android Authority, "Android 17 stable is officially rolling out to Google Pixels", 16 Jun 2026. Sekunder. https://www.androidauthority.com/android-17-stable-rollout-features-3675016/
- S26. Material Design Blog, "What's new at Google I/O 2026". Resmi; halaman tidak terbaca, ringkasan. https://m3.material.io/blog/whats-new-at-io26
- S27. Android Developers, catatan rilis Compose Material3 (seri 1.5.0 alpha, Mei 2026 dan sesudahnya). Resmi. https://developer.android.com/jetpack/androidx/releases/compose-material3
- S28. Android Developers, "Material Design 3 in Compose". Resmi. https://developer.android.com/develop/ui/compose/designsystems/material3
- S29. 9to5Google, "Google announces Android 16's Material 3 Expressive redesign", 13 Mei 2025. Sekunder, ringkasan. https://9to5google.com/2025/05/13/android-16-material-3-expressive-redesign/

**Samsung**
- S30. Samsung Developer, "One UI" (prinsip desain). Resmi. https://developer.samsung.com/one-ui/index.html
- S31. 9to5Google, "Samsung starts stable One UI 8 rollout, based on Android 16, for Galaxy S25 users", 15 Sep 2025. Sekunder, ringkasan. https://9to5google.com/2025/09/15/samsung-one-ui-8-galaxy-s25-stable-update/
- S32. Android Authority, "One UI 8 will let any app show a Live Notification in Samsung's Now Bar". Sekunder, ringkasan. https://www.androidauthority.com/one-ui-8-live-updates-support-3573794/
- S33. Android Authority, "Samsung's One UI 8.5 update makes quick settings pop with cleaner, glassier design", 30 Okt 2025. Sekunder. https://www.androidauthority.com/samsung-one-ui-8-5-quick-settings-refresh-3611574/
- S34. Sammy Fans, "Samsung's Ambient Design in One UI 8.5 - Explained", 26 Feb 2026. Sekunder. https://www.sammyfans.com/2026/02/26/samsung-one-ui-8-5-ambient-design/
- S35. Sammy Fans, "One UI 8 made Dynamic Blur a luxury, no hope in One UI 8.5", 16 Okt 2025. Sekunder. https://www.sammyfans.com/2025/10/16/one-ui-8-made-dynamic-blur-a-luxury-no-hope-in-one-ui-8-5/
- S36. Samsung Global Newsroom, "Samsung's One UI 8.5 Official Rollout Starts May 6". Resmi; time out, hanya judul. https://news.samsung.com/global/samsungs-one-ui-8-5-official-rollout-starts-may-6
- S37. 9to5Google, "Here's the changelog for Samsung's Android 17 update, One UI 9", 13 Mei 2026. Sekunder. https://9to5google.com/2026/05/13/samsung-one-ui-9-android-17-changelog/
- S38. 9to5Google, "Samsung starts rolling out Android 17 with One UI 9, confirms initial list of Galaxy devices", 16 Sep 2026. Sekunder, ringkasan. https://9to5google.com/2026/09/16/samsung-android-17-one-ui-9-update-list/
- S39. Samsung US Support, "Galaxy device Visibility enhancement features". Resmi. https://www.samsung.com/us/support/answer/ANS10002540/

**Huawei**
- S40. Huawei Central, "Huawei revamps HarmonyOS 6 with glass UI effects". Sekunder. https://www.huaweicentral.com/huawei-revamps-harmonyos-6-with-glass-ui-effects/
- S41. Huawei Central, "Huawei announces HarmonyOS 7 with smooth performance and Agentic AI upgrades", HDC 12 Jun 2026. Sekunder. https://www.huaweicentral.com/huawei-harmonyos-7/
- S42. TechNode, "Huawei rolls out HarmonyOS 6, ahead of next month's Mate 80 launch", 23 Okt 2025. Sekunder, ringkasan. https://technode.com/2025/10/23/huawei-rolls-out-harmonyos-6-ahead-of-next-months-mate-80-launch/
- S43. Wikipedia, "HarmonyOS 5". Sekunder, ringkasan. https://en.wikipedia.org/wiki/HarmonyOS_5
- S80. HUAWEI Developers, "HarmonyOS NEXT Design" (portal desain resmi; tidak dibaca). https://developer.huawei.com/consumer/en/design/

**Xiaomi**
- S44. Nokiamob, "HyperOS 3 Announced: New Features, Visual Redesign", 28 Agu 2025. Sekunder. https://nokiamob.net/2025/08/28/xiaomi-hyperos-3-official-ai-features-visual-overhaul-and-rollout-plans/
- S45. Xiaomi Global, halaman HyperOS 3. Resmi; 403, ringkasan. https://www.mi.com/global/hyperos/
- S46. Sportskeeda Tech, "Xiaomi HyperOS 4 announced: Features, design, supported devices, and more", 14 Agu 2026. Sekunder. https://tech.sportskeeda.com/mobiles/xiaomi-hyperos-4-announced-features-design-supported-devices
- S47. Gizmochina, "Xiaomi reveals which devices will receive the HyperOS 4 Soft Light Glass effect", 19 Agu 2026. Sekunder. https://www.gizmochina.com/2026/08/19/xiaomi-hyperos-4-soft-light-glass-effect-eligible-devices-list/

**OPPO dan vivo**
- S48. OPPO, "ColorOS 16". Resmi. https://www.oppo.com/en/coloros16/
- S49. OPPO, "ColorOS 15". Resmi, ringkasan. https://www.oppo.com/en/coloros15/
- S50. GSMArena, "vivo shows off the glassy new look of OriginOS 6". Sekunder, ringkasan. https://m.gsmarena.com/vivo_shows_off_the_glassy_new_look_of_originos_6-news-69857.php
- S51. OPPO Parts, "What is Live Alerts in ColorOS 15". Sekunder, ringkasan. https://oppo-parts.trackit.co.in/support/what-is-live-alerts-in-coloros-15

**Microsoft**
- S52. Microsoft Learn, "Acrylic material", diperbarui 29 Agu 2026. Resmi. https://learn.microsoft.com/en-us/windows/apps/design/style/acrylic
- S53. Microsoft Learn, "Mica material", diperbarui 17 Jul 2026. Resmi. https://learn.microsoft.com/en-us/windows/apps/design/style/mica
- S54. Microsoft Learn, "Design principles - Windows apps", diperbarui 14 Jul 2026. Resmi. https://learn.microsoft.com/en-us/windows/apps/design/design-principles
- S55. Fluent 2 Design System, "Motion". Resmi. https://fluent2.microsoft.design/motion
- S56. Fluent 2 Design System, "Material". Resmi. https://fluent2.microsoft.design/material

**Pasar Indonesia**
- S57. StatCounter, "Mobile Operating System Market Share Indonesia", data Agustus 2026. https://gs.statcounter.com/os-market-share/mobile/indonesia
- S58. TelecomLead (data Omdia), "Indonesia Smartphone Market Q2 2026: Transsion Leads with 26% as Xiaomi, Samsung, OPPO and vivo Battle Rising Prices", 27 Agu 2026. Sekunder. https://telecomlead.com/smart-phone/indonesia-smartphone-market-q2-2026-transsion-leads-with-26-as-xiaomi-samsung-oppo-and-vivo-battle-rising-prices-127494

**Kegunaan**
- S59. Page Laubheimer, NN/g, "Sticky Headers: 5 Ways to Make Them Better", 4 Apr 2021. https://www.nngroup.com/articles/sticky-headers/
- S60. Megan Brown, NN/g, "Glassmorphism: Definition and Best Practices", 7 Jun 2024. https://www.nngroup.com/articles/glassmorphism/

**Platform web**
- S61. MDN, "prefers-reduced-transparency", diubah 20 Apr 2026. https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-transparency
- S62. Can I use, "prefers-reduced-transparency". https://caniuse.com/mdn-css_at-rules_media_prefers-reduced-transparency
- S63. Can I use, "CSS Backdrop Filter". https://caniuse.com/css-backdrop-filter
- S64. MDN, "linear()". https://developer.mozilla.org/en-US/docs/Web/CSS/easing-function/linear
- S65. Chrome for Developers, "Create complex animation curves in CSS with the linear() easing function", 25 Mei 2023. https://developer.chrome.com/docs/css-ui/css-linear-easing-function
- S66. web.dev, "Same-document view transitions have become Baseline Newly available". Ringkasan; tanggal Baseline dikonfirmasi S78. https://web.dev/blog/same-document-view-transitions-are-now-baseline-newly-available
- S67. web.dev, "Interop 2026", 12 Feb 2026. https://web.dev/blog/interop-2026
- S68. Inertia.js v3, "View Transitions". https://inertiajs.com/docs/v3/the-basics/view-transitions
- S69. Bramus, Chrome for Developers, "Misconceptions about view transitions", 11 Jul 2024. https://developer.chrome.com/blog/view-transitions-misconceptions
- S70. Arjen Karel, CoreWebVitals.io, "The Impact of CSS View Transitions on Web Performance", diperbarui 27 Feb 2026. https://www.corewebvitals.io/pagespeed/view-transition-web-performance
- S71. Can I use, "animation-timeline: scroll()"; juga MDN "animation-timeline" (diubah 16 Sep 2026): https://developer.mozilla.org/en-US/docs/Web/CSS/animation-timeline. https://caniuse.com/mdn-css_properties_animation-timeline_scroll
- S72. MDN, "corner-shape", diubah 27 Agu 2026; juga Can I use: https://caniuse.com/mdn-css_properties_corner-shape. https://developer.mozilla.org/en-US/docs/Web/CSS/corner-shape
- S73. Can I use, "Vibration API"; juga MDN "Vibration API": https://developer.mozilla.org/en-US/docs/Web/API/Vibration_API. https://caniuse.com/vibration
- S74. MDN, "Device Memory API". https://developer.mozilla.org/en-US/docs/Web/API/Device_Memory_API
- S75. W3C SVG WG, issue #1142 "Filter Effects: define interoperable backdrop displacement/refraction for liquid glass UI", dibuka 25 Jun 2026. https://github.com/w3c/svgwg/issues/1142
- S76. Mozilla Bugzilla 1731965, "Janky scrolling due to backdrop-filter on Android on nos.nl". https://bugzilla.mozilla.org/show_bug.cgi?id=1731965
- S77. Alex Russell, "The Performance Inequality Gap, 2026", 24 Nov 2025. https://infrequently.org/2025/11/performance-inequality-gap-2026/
- S78. GoogleChrome modern-web-guidance, panduan `physics-based-easing`, `same-document-transitions`, `shrinking-header-on-scroll`, `soft-edge-content-fade`, diambil lewat CLI (versi skill 2026_09_04). https://github.com/GoogleChrome/modern-web-guidance
- S79. web.dev, "prefers-reduced-motion". https://web.dev/articles/prefers-reduced-motion

**Berkas repositori**
- I1. `DESIGN.md`: tiga dial, palet, tema gelap, radius, bayangan, navigasi dan Jelajah, "Yang tidak dikerjakan", R-015 sampai R-017.
- I2. `resources/js/layouts/NavLinks.tsx` baris 18-26.
- I3. `resources/js/components/trails/ConsiderationTray.tsx` baris 20.
- I4. `resources/css/app.css` baris 72 dan 457-465, ditambah hasil `grep` (kosong) untuk `prefers-contrast`, `prefers-reduced-transparency`, `forced-colors`, `navigator.vibrate`, `deviceMemory` di `resources/js` dan `resources/css`.
- I5. `package.json`.
- I7. Skill: `.claude/skills/apple-design/SKILL.md`, `.claude/skills/antislop/SKILL.md` (R-10 batas dosis glassmorphism), dan skill plugin `ecc:liquid-glass-design`.
- I8. `resources/js/pages/Trails/Index.tsx` baris 35.

[S1]: https://www.apple.com/newsroom/2025/06/apple-introduces-a-delightful-and-elegant-new-software-design/
[S2]: https://developer.apple.com/videos/play/wwdc2025/219/
[S3]: https://developer.apple.com/videos/play/wwdc2025/356/
[S4]: https://developer.apple.com/videos/play/wwdc2025/323/
[S5]: https://techcrunch.com/2025/11/04/ios-26-1-lets-you-turn-down-liquid-glass-transparency
[S6]: https://www.nngroup.com/articles/liquid-glass/
[S7]: https://www.apple.com/newsroom/2026/06/apple-unveils-next-generation-of-apple-intelligence-siri-ai-and-more/
[S8]: https://www.apple.com/os/ios/
[S9]: https://www.macrumors.com/2026/06/10/how-liquid-glass-is-changing-in-ios-27/
[S10]: https://www.macstories.net/stories/ios-and-ipados-27-review/2/
[S11]: https://www.macrumors.com/2026/09/09/apple-announces-ios-27-release-date/
[S12]: https://developer.apple.com/videos/play/wwdc2026/250/
[S13]: https://developer.apple.com/videos/play/wwdc2023/10158/
[S14]: https://antongubarenko.substack.com/p/ios-27-uibarminimization
[S15]: https://en.wikipedia.org/wiki/IOS_26
[S16]: https://blog.google/products-and-platforms/platforms/android/material-3-expressive-android-wearos-launch/
[S17]: https://design.google/library/expressive-material-design-google-research
[S18]: https://doi.org/10.1145/3772318.3790373
[S19]: https://www.uxtigers.com/post/ux-roundup-20260831
[S20]: https://github.com/material-components/material-components-android/blob/master/docs/theming/Motion.md
[S21]: https://github.com/material-components/material-components-android/blob/master/docs/components/LoadingIndicator.md
[S22]: https://9to5google.com/2025/05/14/material-3-expressive-navigation/
[S23]: https://9to5google.com/2025/12/27/recap-material-3-expressive/
[S24]: https://www.androidauthority.com/google-material-3-expressive-features-changes-availability-supported-devices-3556392/
[S25]: https://www.androidauthority.com/android-17-stable-rollout-features-3675016/
[S26]: https://m3.material.io/blog/whats-new-at-io26
[S27]: https://developer.android.com/jetpack/androidx/releases/compose-material3
[S28]: https://developer.android.com/develop/ui/compose/designsystems/material3
[S29]: https://9to5google.com/2025/05/13/android-16-material-3-expressive-redesign/
[S30]: https://developer.samsung.com/one-ui/index.html
[S31]: https://9to5google.com/2025/09/15/samsung-one-ui-8-galaxy-s25-stable-update/
[S32]: https://www.androidauthority.com/one-ui-8-live-updates-support-3573794/
[S33]: https://www.androidauthority.com/samsung-one-ui-8-5-quick-settings-refresh-3611574/
[S34]: https://www.sammyfans.com/2026/02/26/samsung-one-ui-8-5-ambient-design/
[S35]: https://www.sammyfans.com/2025/10/16/one-ui-8-made-dynamic-blur-a-luxury-no-hope-in-one-ui-8-5/
[S36]: https://news.samsung.com/global/samsungs-one-ui-8-5-official-rollout-starts-may-6
[S37]: https://9to5google.com/2026/05/13/samsung-one-ui-9-android-17-changelog/
[S38]: https://9to5google.com/2026/09/16/samsung-android-17-one-ui-9-update-list/
[S39]: https://www.samsung.com/us/support/answer/ANS10002540/
[S40]: https://www.huaweicentral.com/huawei-revamps-harmonyos-6-with-glass-ui-effects/
[S41]: https://www.huaweicentral.com/huawei-harmonyos-7/
[S42]: https://technode.com/2025/10/23/huawei-rolls-out-harmonyos-6-ahead-of-next-months-mate-80-launch/
[S43]: https://en.wikipedia.org/wiki/HarmonyOS_5
[S44]: https://nokiamob.net/2025/08/28/xiaomi-hyperos-3-official-ai-features-visual-overhaul-and-rollout-plans/
[S45]: https://www.mi.com/global/hyperos/
[S46]: https://tech.sportskeeda.com/mobiles/xiaomi-hyperos-4-announced-features-design-supported-devices
[S47]: https://www.gizmochina.com/2026/08/19/xiaomi-hyperos-4-soft-light-glass-effect-eligible-devices-list/
[S48]: https://www.oppo.com/en/coloros16/
[S49]: https://www.oppo.com/en/coloros15/
[S50]: https://m.gsmarena.com/vivo_shows_off_the_glassy_new_look_of_originos_6-news-69857.php
[S51]: https://oppo-parts.trackit.co.in/support/what-is-live-alerts-in-coloros-15
[S52]: https://learn.microsoft.com/en-us/windows/apps/design/style/acrylic
[S53]: https://learn.microsoft.com/en-us/windows/apps/design/style/mica
[S54]: https://learn.microsoft.com/en-us/windows/apps/design/design-principles
[S55]: https://fluent2.microsoft.design/motion
[S56]: https://fluent2.microsoft.design/material
[S57]: https://gs.statcounter.com/os-market-share/mobile/indonesia
[S58]: https://telecomlead.com/smart-phone/indonesia-smartphone-market-q2-2026-transsion-leads-with-26-as-xiaomi-samsung-oppo-and-vivo-battle-rising-prices-127494
[S59]: https://www.nngroup.com/articles/sticky-headers/
[S60]: https://www.nngroup.com/articles/glassmorphism/
[S61]: https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-transparency
[S62]: https://caniuse.com/mdn-css_at-rules_media_prefers-reduced-transparency
[S63]: https://caniuse.com/css-backdrop-filter
[S64]: https://developer.mozilla.org/en-US/docs/Web/CSS/easing-function/linear
[S65]: https://developer.chrome.com/docs/css-ui/css-linear-easing-function
[S66]: https://web.dev/blog/same-document-view-transitions-are-now-baseline-newly-available
[S67]: https://web.dev/blog/interop-2026
[S68]: https://inertiajs.com/docs/v3/the-basics/view-transitions
[S69]: https://developer.chrome.com/blog/view-transitions-misconceptions
[S70]: https://www.corewebvitals.io/pagespeed/view-transition-web-performance
[S71]: https://caniuse.com/mdn-css_properties_animation-timeline_scroll
[S72]: https://developer.mozilla.org/en-US/docs/Web/CSS/corner-shape
[S73]: https://caniuse.com/vibration
[S74]: https://developer.mozilla.org/en-US/docs/Web/API/Device_Memory_API
[S75]: https://github.com/w3c/svgwg/issues/1142
[S76]: https://bugzilla.mozilla.org/show_bug.cgi?id=1731965
[S77]: https://infrequently.org/2025/11/performance-inequality-gap-2026/
[S78]: https://github.com/GoogleChrome/modern-web-guidance
[S79]: https://web.dev/articles/prefers-reduced-motion
[S80]: https://developer.huawei.com/consumer/en/design/
[I1]: ../../../DESIGN.md
[I2]: ../../../resources/js/layouts/NavLinks.tsx
[I3]: ../../../resources/js/components/trails/ConsiderationTray.tsx
[I4]: ../../../resources/css/app.css
[I5]: ../../../package.json
[I7]: ../../../.claude/skills/apple-design/SKILL.md
[I8]: ../../../resources/js/pages/Trails/Index.tsx
