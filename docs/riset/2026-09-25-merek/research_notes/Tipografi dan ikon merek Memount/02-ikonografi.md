# Ikonografi MEMOUNT: perbandingan sistem ikon dan rekomendasi (keadaan 25 September 2026)

Semua sumber diakses 25 September 2026 kecuali disebut lain. Label bukti mengikuti R-023: **[FAKTA]** terukur atau terkutip dari sumber, **[INFERENSI]** turunan dari fakta, **[HIPOTESIS]** perlu diuji, **[REKOMENDASI]** usulan untuk keputusan pemilik. OPEN ISSUE ada di bagian Gaps.

Metode ukur untuk angka yang tidak berasal dari halaman pemasaran:
- Daftar berkas paket npm versi `latest` diambil lewat API jsDelivr (`data.jsdelivr.com/v1/packages/npm/<paket>@<versi>?structure=flat`), lalu dihitung dengan regex nama berkas.
- Material Symbols dihitung dari berkas codepoints resmi Google.
- Tanggal rilis diambil dari registry npm, dan unduhan dari `api.npmjs.org` untuk periode 15–21 Sep 2026.
- Tebal goresan diukur dari path glyph "minus/remove/subtract": tebal batang sama dengan tebal goresan set itu.
- Kontras dihitung dengan rumus luminans relatif WCAG.
- Skrip ukur ada di scratchpad sesi (sementara, bukan bagian repo).

Catatan proses: kuota WebSearch sesi habis di tengah riset. Sisa bukti diambil dengan fetch langsung ke sumber primer dan API paket, tanpa mesin pencari.

## 1. Kandidat set ikon: lisensi, jumlah, grid dan goresan, varian isi, glyph domain pendakian, render 12–24 px, dan kelaziman di UI hasil AI

### Takeaway
Untuk ikon UI umum, set besar praktis setara: Phosphor, Lucide, Tabler, dan Material Symbols masing-masing menutup 36 dari 36 konsep prototipe dengan nama persis, jadi cakupan bukan pembeda. Pembeda nyata ada di lima hal:
- **Lisensi.** Remix berganti ke lisensi khusus pada Januari 2026. Font Awesome Free dan Streamline mewajibkan atribusi.
- **Varian isi untuk status aktif.** Lucide 0%, Tabler dan Iconoir sekitar 20%, sedangkan Phosphor, Remix, Heroicons, dan Fluent sekitar 100%. Material Symbols memakai sumbu FILL.
- **Tebal goresan relatif.** Phosphor Regular 6,25% kotak (1,5 px pada 24 px). Lucide dan Tabler 8,33% (2 px). Tanda merek 10,4% (2,5 px).
- **Pemeliharaan.** Katalog Phosphor tidak dirilis ulang sejak 29 Mar 2024.
- **Kelaziman.** lucide-react 77,2 juta unduhan/minggu, sekitar 73% dari 13 paket ikon React yang diukur. shadcn/ui memakai Lucide sebagai bawaan sejak Nov 2024.

Glyph domain pendakian paling kaya ada di Material Symbols, disusul Tabler, Hugeicons free, Font Awesome Free, dan Phosphor. Heroicons nyaris tidak punya glyph domain.

### Cited Findings

#### Ringkasan terukur per set [FAKTA]

| Set | Versi terakhir (tanggal rilis) | Lisensi | Glyph dasar / varian isi | Grid, goresan, ujung | Tebal goresan bila dirender 24 px | Sumber |
|---|---|---|---|---|---|---|
| Phosphor | core 2.1.1 (29-03-2024); react 2.1.10 (22-05-2025) | MIT | 1.512 regular / 1.512 fill; juga thin, light, bold, duotone | viewBox 256, "Designed at 16 x 16px", path terisi (goresan sudah dikonversi), ujung bulat | Thin 0,75; Light 1,125; Regular 1,5; Bold 2,25 | [listing](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat); [registry core](https://registry.npmjs.org/@phosphor-icons%2fcore); [registry react](https://registry.npmjs.org/@phosphor-icons%2freact); [minus regular](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/regular/minus.svg); [minus bold](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/bold/minus-bold.svg); [README](https://github.com/phosphor-icons/homepage) |
| Lucide | lucide-static 1.48.0 (24-09-2026) | ISC; 129 ikon turunan Feather berlisensi MIT | 2.118 / 0 | 24×24, stroke 2, round caps/joins, padding ≥1 px | 2,0 | [listing](https://data.jsdelivr.com/v1/packages/npm/lucide-static@1.48.0?structure=flat); [registry](https://registry.npmjs.org/lucide-static); [lisensi](https://lucide.dev/license); [panduan](https://lucide.dev/contribute/icon-design-guide) |
| Tabler | 3.48.0 (22-09-2026) | MIT | 5.166 outline / 1.054 filled | 24×24, stroke 2 (dapat diubah; webfont tersedia 1,5 dan 1), linecap round | 2,0 | [listing](https://data.jsdelivr.com/v1/packages/npm/@tabler/icons@3.48.0?structure=flat); [README](https://github.com/tabler/tabler-icons); [registry](https://registry.npmjs.org/@tabler%2ficons) |
| Material Symbols | codepoints master; npm `material-symbols` 0.47.5 | Apache-2.0 | 4.299 nama di codepoints (termasuk alias). Panduan resmi masih menulis "over 2,500" | Variable: FILL 0–1, wght 100–700 (bawaan 400), GRAD −50–200, opsz 20–48. Outlined berujung persegi | wght 300: 1,13; 400: 1,5; 500: 1,70 (paket `@material-symbols/svg-*`) | [codepoints](https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints); [panduan](https://developers.google.com/fonts/docs/material_symbols); [remove 400](https://cdn.jsdelivr.net/npm/@material-symbols/svg-400/outlined/remove.svg) |
| Iconoir | 7.12.1 (12-08-2026) | MIT | 1.383 regular / 288 solid | 24×24, stroke 1,5, linecap round | 1,5 | [listing](https://data.jsdelivr.com/v1/packages/npm/iconoir@7.12.1?structure=flat); [LICENSE](https://cdn.jsdelivr.net/npm/iconoir@7.12.1/LICENSE) |
| Remix Icon | 4.9.1 (29-01-2026) | "Remix Icon License v1.0", Januari 2026. `package.json` masih menulis "Apache-2.0" | 1.539 line / 1.539 fill | 24×24, path terisi, ujung persegi | 2,0 | [listing](https://data.jsdelivr.com/v1/packages/npm/remixicon@4.9.1?structure=flat); [License](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/License); [package.json](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/package.json) |
| Heroicons | 2.2.0 (18-11-2024) | MIT | 324 outline / 324 solid (ukuran 24); ada juga 20 mini dan 16 micro | 24×24, outline stroke 1,5, linecap round | 1,5 | [listing](https://data.jsdelivr.com/v1/packages/npm/heroicons@2.2.0?structure=flat); [registry](https://registry.npmjs.org/heroicons) |
| IBM Carbon | 11.89.0 (23-09-2026) | Apache-2.0 | 2.631 (folder svg/32) | viewBox 32, path terisi, ujung persegi | 1,5 (2/32) | [listing](https://data.jsdelivr.com/v1/packages/npm/@carbon/icons@11.89.0?structure=flat); [subtract 32](https://cdn.jsdelivr.net/npm/@carbon/icons@11.89.0/svg/32/subtract.svg) |
| Hugeicons (free) | core-free-icons 4.3.5 (21-09-2026) | MIT (© 2025 Hugeicons) | 6.065 ekspor. README: "6,000+ … Stroke Rounded"; Pro "over 60,000+ … 10 unique styles" | 24×24, stroke 1,5, satu gaya tanpa varian isi | 1,5 | [listing](https://data.jsdelivr.com/v1/packages/npm/@hugeicons/core-free-icons@4.3.5?structure=flat); [README](https://cdn.jsdelivr.net/npm/@hugeicons/core-free-icons@4.3.5/README.md); [LICENSE](https://cdn.jsdelivr.net/npm/@hugeicons/core-free-icons@4.3.5/LICENSE.md) |
| Fluent UI System Icons | svg-icons 1.1.342 (25-09-2026) | MIT | 2.618 regular / 2.654 filled (ukuran 24) | Master per ukuran (16, 20, 24, …), path terisi, ujung bulat | 1,5 (master 24); 1,0 pada master 20 px | [listing](https://data.jsdelivr.com/v1/packages/npm/@fluentui/svg-icons@1.1.342?structure=flat); [subtract 24](https://cdn.jsdelivr.net/npm/@fluentui/svg-icons@1.1.342/icons/subtract_24_regular.svg); [Fluent 2](https://fluent2.microsoft.design/iconography) |
| Font Awesome Free | 7.3.1 (15-07-2026) | Ikon CC BY 4.0; font OFL 1.1; kode MIT | 2.001 solid / 273 regular | viewBox 448–512 × 512, solid | sekitar 3,0 (minus solid) | [listing](https://data.jsdelivr.com/v1/packages/npm/@fortawesome/fontawesome-free@7.3.1?structure=flat); [LICENSE.txt](https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/LICENSE.txt); [minus](https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/svgs/solid/minus.svg) |
| Streamline (free) | — | Streamline Free License: atribusi wajib, paling banyak 50 ikon per proyek. Sebagian set CC BY 4.0; "Most free sets are not open-source" | tidak diukur | — | — | [Free License](https://help.streamlinehq.com/en/articles/5354376-streamline-free-license); [atribusi](https://help.streamlinehq.com/en/articles/5354403-creating-an-attribution-link) |
| Maki / Temaki (simbol peta) | 8.2.0 (27-02-2025) / 5.13.0 (20-03-2026) | CC0-1.0 | 215 / 557 | Maki viewBox 15×15, solid | — | [Maki](https://data.jsdelivr.com/v1/packages/npm/@mapbox/maki@8.2.0?structure=flat); [Temaki](https://data.jsdelivr.com/v1/packages/npm/@rapideditor/temaki@5.13.0?structure=flat); [Maki mountain](https://cdn.jsdelivr.net/npm/@mapbox/maki@8.2.0/icons/mountain.svg) |
| Octicons (pembanding set kustom milik produk) | 19.38.0 (18-09-2026) | MIT | 362 (24 px) / 396 (16 px) | 16 dan 24 digambar terpisah, stroke 1,5 | 1,5 | [listing](https://data.jsdelivr.com/v1/packages/npm/@primer/octicons@19.38.0?structure=flat); [panduan](https://primer.style/foundations/icons/design-guidelines) |

Konflik angka:
- **Phosphor.** README menulis "1,248 icons and counting", sedangkan paket 2.1.1 berisi 1.512 per bobot. README usang. — [README](https://github.com/phosphor-icons/homepage); [listing](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat)
- **Material Symbols.** Panduan Google menulis "over 2,500 icons", sedangkan berkas codepoints memuat 4.299 nama. Sebagian nama adalah alias, jadi angka pasti glyph unik tidak diverifikasi. — [panduan](https://developers.google.com/fonts/docs/material_symbols); [codepoints](https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints)

#### Lisensi, dari sumber primer [FAKTA]
- **Lucide.** Lisensi ISC, "Copyright (c) 2026 Lucide Icons and Contributors". Sebanyak 129 ikon turunan Feather berlisensi MIT, "Copyright (c) 2013-present Cole Bemis". — [lucide.dev/license](https://lucide.dev/license)
- **Remix Icon License v1.0, "Version 1.0 – January 2026".** — [License](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/License)
  - §2.4: "Attribution to Remix Icon is appreciated but not required."
  - §3.2 melarang "competing icon library … even if the Icons are modified with only minor or superficial changes (such as color, size, stroke width, corner radius, or layout)".
  - §3.3 melarang penggunaan sebagai "A logo, trademark, or brand identifier", termasuk "Using an Icon as a brand mark, app icon, or identity symbol".
- **Font Awesome Free.** "The Font Awesome Free download is licensed under a Creative Commons Attribution 4.0 International License and applies to all icons packaged as SVG and JS file types"; font OFL 1.1; kode MIT. CC BY 4.0 mewajibkan atribusi. — [LICENSE.txt](https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/LICENSE.txt); [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)
- **Streamline Free.** — [Free License](https://help.streamlinehq.com/en/articles/5354376-streamline-free-license); [atribusi](https://help.streamlinehq.com/en/articles/5354403-creating-an-attribution-link)
  - "Attribution is required for all assets used under the Streamline Free License" berupa tautan ke streamlinehq.com, dipasang di semua halaman web yang memakai aset (footer boleh).
  - Batas per proyek: "50 icons, 25 illustrations, 25 elements, 25 emojis".
  - Tanpa atribusi hanya untuk paket premium.
  - Artikel lisensinya berstatus "Updated today" pada 25-09-2026.
- **Hugeicons free.** Teks MIT penuh, "Copyright (c) 2025 Hugeicons". — [LICENSE.md](https://cdn.jsdelivr.net/npm/@hugeicons/core-free-icons@4.3.5/LICENSE.md)
- **Material Symbols.** "Apache License Version 2.0". — [panduan Google](https://developers.google.com/fonts/docs/material_symbols)
- **Fluent System Icons.** "open-source under the MIT License". — [Fluent 2](https://fluent2.microsoft.design/iconography)
- **Field `license` di `package.json`.**
  - Phosphor MIT: [package.json](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/package.json)
  - Tabler MIT: [package.json](https://cdn.jsdelivr.net/npm/@tabler/icons@3.48.0/package.json)
  - Heroicons MIT: [package.json](https://cdn.jsdelivr.net/npm/heroicons@2.2.0/package.json)
  - Carbon Apache-2.0: [package.json](https://cdn.jsdelivr.net/npm/@carbon/icons@11.89.0/package.json)
  - Fluent MIT: [package.json](https://cdn.jsdelivr.net/npm/@fluentui/svg-icons@1.1.342/package.json)
  - Maki CC0-1.0: [package.json](https://cdn.jsdelivr.net/npm/@mapbox/maki@8.2.0/package.json)
  - Temaki CC0-1.0: [package.json](https://cdn.jsdelivr.net/npm/@rapideditor/temaki@5.13.0/package.json)
  - Octicons MIT: [package.json](https://cdn.jsdelivr.net/npm/@primer/octicons@19.38.0/package.json)
- **Kewajiban MIT.** "The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software." Teks yang sama ada di LICENSE Iconoir dan Hugeicons. — [Iconoir LICENSE](https://cdn.jsdelivr.net/npm/iconoir@7.12.1/LICENSE)

#### Keadaan kode MEMOUNT sekarang [FAKTA]
- `Icon.tsx` menanam 18 path Phosphor: regular, ditambah varian `-fill` untuk lima ikon tab (house, map-trifold, backpack, person-simple-hike, chart-line-up). Tab aktif memakai `-fill` agar "yang berubah adalah bentuk, bukan warna saja (WCAG 1.4.1)". — [Icon.tsx:1-11](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)
- Elemen SVG-nya `<svg viewBox="0 0 256 256" fill="currentColor" …>`: berperan `role="img"` dengan `<title>` bila berlabel, dan `aria-hidden` bila tidak. — [Icon.tsx:42-43](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)
- Batang panah `arrow-up` lebarnya 16 unit (x 120–136 dari 256), cocok dengan batang `minus` Regular (16/256). — [Icon.tsx:14](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx); [minus regular](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/regular/minus.svg)
  - Status UNVERIFIED pada catatan logo sebelumnya kini terjawab: Regular = 16/256 = 6,25% kotak. — [identitas_visual_logo.md:125,211](file:///E:/praktik_aplikasi_web/docs/riset/2026-09-23-konsolidasi/identitas_visual_logo.md)
- Ikon tab dirender 24 px (`h-6 w-6`). Tab aktif juga ditandai garis di atas, huruf tebal, dan `aria-current`. — [NavLinks.tsx:89,95,102](file:///E:/praktik_aplikasi_web/resources/js/layouts/NavLinks.tsx)
- Panah naik/turun pada perjalanan pos dirender 12 px (`h-3 w-3`), di React maupun Blade. — [CheckpointJourney.tsx:31-32](file:///E:/praktik_aplikasi_web/resources/js/components/ui/CheckpointJourney.tsx)
- Tanda merek "Jalur Berpos" punya dua master. — [Mark.tsx:8-10,18-22](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx)
  - L: viewBox 48, `strokeWidth` 5 (10,4% kotak). S: viewBox 16, `strokeWidth` 2 (12,5%).
  - Keduanya memakai `strokeLinecap`/`strokeLinejoin` round dan `stroke="currentColor"`.
  - Komentar kodenya: "Dua master, bukan satu yang diskalakan: master S dihitung agar celah bersih tetap 2,4 px pada 16 px".
- `DESIGN.md` menetapkan: "Phosphor berat `regular`, ditanam sebagai path, tanpa dependensi npm. Bukan Lucide maupun Heroicons: keduanya pilihan bawaan hampir semua antarmuka hasil AI", dan "Ikon hanya di tempat yang membawa arti". — [DESIGN.md:169-174](file:///E:/praktik_aplikasi_web/DESIGN.md)
- Test menjaga agar `package.json` tidak memuat `lucide`, `feather`, `heroicons`, maupun `phosphor-icons`. — [IconSystemTest.php:58-66](file:///E:/praktik_aplikasi_web/tests/Feature/IconSystemTest.php)
- Pengecekan repo: tidak ditemukan berkas NOTICE/LICENSE pihak ketiga maupun teks MIT Phosphor, baik di root, `Icon.tsx`, maupun `icon.blade.php` (grep 25-09-2026). — [Icon.tsx](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)

#### Cakupan daftar glyph prototipe pemilik (36 konsep, dicek dengan nama persis) [FAKTA]
- Hasil cakupan: Phosphor 36/36, Lucide 36/36, Tabler 36/36, Material Symbols 36/36, Iconoir 33/36. Iconoir tidak punya `layers`/stack peta, tenda, dan gunung. Sinonim yang dicek dan tidak ada: hill, mount, tent, camp, backpack; yang ada hanya `bag`. — [Phosphor](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat); [Lucide](https://data.jsdelivr.com/v1/packages/npm/lucide-static@1.48.0?structure=flat); [Tabler](https://data.jsdelivr.com/v1/packages/npm/@tabler/icons@3.48.0?structure=flat); [Iconoir](https://data.jsdelivr.com/v1/packages/npm/iconoir@7.12.1?structure=flat); [Material codepoints](https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints)
- Pemetaan nama Phosphor untuk prototipe:

  | Konsep | Nama Phosphor |
  |---|---|
  | Home | `house` |
  | Explore | `compass` |
  | Trips | `backpack` |
  | Track | `path` |
  | Progress | `trend-up` |
  | Notifikasi | `bell` |
  | Bagikan | `share-network` |
  | Simpan | `bookmark-simple` |
  | Cari | `magnifying-glass` |
  | Filter | `sliders-horizontal` |
  | Peta | `map-trifold` |
  | Daftar | `list` |
  | Lapisan | `stack` |
  | Lokasi saya | `crosshair` |
  | OFFICIAL | `shield-check` |
  | COMMUNITY | `users` |
  | Peringatan | `warning` |
  | Info | `info` |
  | Waktu | `clock` |
  | Tanggal | `calendar` |
  | Tautan keluar | `arrow-square-out` |
  | Foto | `camera` |
  | Kemah | `tent` |
  | Air | `drop` |
  | Gunung | `mountains` |
  | Finish | `flag` |
  | Putar/jeda | `play`/`pause` |
  | Terang/gelap | `sun`/`moon` |
  | Catatan privat | `lock` |
  | Panah | `caret-right` |

  — [listing Phosphor](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat)

#### Glyph domain pendakian per set (nama terverifikasi di listing) [FAKTA]
- **Phosphor 2.1.1.**
  - Terrain dan kemah: `mountains`, `tent`, `campfire`, `drop`, `drop-half`.
  - Bendera: `flag`, `flag-checkered`, `flag-pennant`.
  - Pendaki: `person-simple-hike`, `footprints`, `boot`.
  - Arah dan jalur: `compass`, `compass-rose`, `path`, `signpost`.
  - Naik: `trend-up`, `chart-line-up`, `stairs`. Tidak ada glyph elevasi khusus.
  - Lapisan peta: `stack`.
  - Keselamatan dan izin: `first-aid`, `first-aid-kit`, `ticket`.
  - Cuaca: `cloud-sun`, `cloud-rain`, `thermometer`.
  - Perlengkapan dan alam: `backpack`, `binoculars`, `tree-evergreen`, `toilet`.
  - Lokasi: `crosshair`, `gps-fix`, `navigation-arrow`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat)
- **Lucide 1.48.0.**
  - Terrain dan kemah: `mountain`, `mountain-snow`, `tent`, `tent-tree`, `droplet`, `droplets`.
  - Bendera: `flag`, `flag-triangle-left/right`.
  - Pendaki: `footprints`, `sport-shoe`. Tidak ada figur pendaki.
  - Arah dan jalur: `compass`, `route`, `signpost`, `signpost-big`.
  - Naik: `trending-up`. Tidak ada glyph elevasi.
  - Lapisan peta: `layers`, `layers-2`, `layers-3`.
  - Keselamatan dan izin: `bandage`, `briefcase-medical`, `ticket` (beserta varian).
  - Cuaca: `cloud-sun`, `cloud-sun-rain`.
  - Perlengkapan dan alam: `backpack`, `binoculars`, `tree-pine`, `trees`, `toilet`.
  - Lokasi: `locate`, `locate-fixed`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/lucide-static@1.48.0?structure=flat)
- **Tabler 3.48.0.**
  - Terrain dan kemah: `mountain`, `volcano`, `tent`, `camper`, `campfire`, `droplet`.
  - Bendera: `flag`, `flag-2`, `flag-3`.
  - Pendaki: `trekking`, `walk`, `shoe`.
  - Arah dan jalur: `compass`, `route`, `route-2`, `map-route`, `directions`.
  - Naik: `trending-up`, `stairs-up`.
  - Lapisan peta: `stack-2`.
  - Keselamatan dan izin: `first-aid-kit`, `medical-cross`, `ticket`.
  - Cuaca: `cloud-rain`, `cloud-storm`.
  - Perlengkapan dan alam: `backpack`, `binoculars`, `trees`, `badge-wc`.
  - Lokasi: `current-location`, `gps`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@tabler/icons@3.48.0?structure=flat)
- **Material Symbols** (paling kaya untuk domain ini).
  - Terrain: `landscape`, `landscape_2`, `terrain`, `volcano`, `mountain_flag`.
  - Kemah dan tempat bermalam: `camping`, `cabin`, `night_shelter`.
  - Air: `water_drop`, `water_bottle`.
  - Pendaki: `hiking`, `footprint`, `nordic_walking`.
  - Arah dan jalur: `explore`, `route`, `alt_route`, `trail_length`, `trail_length_medium`, `trail_length_short`, `signpost`.
  - Elevasi: `elevation`, `altitude`.
  - Lapisan peta: `layers`.
  - Keselamatan: `medical_services`, `health_and_safety`, `emergency`.
  - Izin: `confirmation_number`.
  - Cuaca: `partly_cloudy_day`, `rainy`, `storm`, `humidity_percentage`, `air`.
  - Perlengkapan dan alam: `backpack`, `forest`, `wc`.
  - Lokasi: `my_location`, `gps_fixed`.

  — [codepoints](https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints)
- **Hugeicons free 4.3.5.**
  - Terrain dan kemah: `mountain`, `mountain-snow`, `tent`, `tent-tree`, `campfire`, `fire-pit`, `droplet`.
  - Pendaki: `hiking`, `footprints`, `running-shoes`.
  - Arah dan jalur: `compass`, `route01`–`03`, `signpost`, `path`.
  - Naik: `trending-up`, `stairs01`–`04`.
  - Lapisan peta: `layer`, `group-layers`.
  - Keselamatan dan izin: `first-aid-kit`, `ticket01`–`03`.
  - Perlengkapan dan alam: `backpack01`–`03`, `binoculars`, `pine-tree`, `hut`, `toilet01`.
  - Lokasi: `gps01`, `locate`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@hugeicons/core-free-icons@4.3.5?structure=flat)
- **Font Awesome Free 7.3.1.**
  - Terrain dan kemah: `mountain`, `mountain-sun`, `volcano`, `tent`, `tents`, `campground`, `droplet`, `bottle-water`.
  - Bendera: `flag-checkered`.
  - Pendaki: `person-hiking`, `shoe-prints`.
  - Arah dan jalur: `compass`, `route`, `directions`.
  - Naik: `arrow-trend-up`.
  - Lapisan peta: `layer-group`.
  - Keselamatan dan izin: `first-aid`, `ticket`.
  - Cuaca: `cloud-sun`.
  - Perlengkapan dan fasilitas: `binoculars`, `person-shelter`, `restroom`, `toilet-portable`. Tidak ada `backpack`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@fortawesome/fontawesome-free@7.3.1?structure=flat)
- **Fluent 1.1.342.**
  - Terrain dan kemah: `mountain_trail`, `mountain_location_top`, `mountain_location_bottom`, `tent`, `drop`.
  - Pendaki: `person_walking`.
  - Arah: `compass_true_north`, `directions`.
  - Lapisan peta: `layer`, `stack`.
  - Keselamatan dan izin: `briefcase_medical`, `ticket_diagonal`.
  - Cuaca: `weather_*` (cloudy, drizzle, fog, …).
  - Perlengkapan dan alam: `backpack`, `tree_deciduous`.
  - Lokasi: `my_location`.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@fluentui/svg-icons@1.1.342?structure=flat)
- **Carbon 11.89.0.**
  - Terrain dan kemah: `mountain`, `campsite`, `rain-drop`.
  - Bendera: `flag`, `flag--filled`.
  - Pendaki: `pedestrian`.
  - Arah: `compass`.
  - Lapisan peta: `layers`.
  - Keselamatan dan izin: `health-cross`, `ticket`.
  - Cuaca: `partly-cloudy`, `rain--heavy`.
  - Perlengkapan dan alam: `binoculars`, `tree`.
  - Tidak ada: ransel, toilet, dan rute (`router*` adalah jaringan).

  — [listing](https://data.jsdelivr.com/v1/packages/npm/@carbon/icons@11.89.0?structure=flat)
- **Remix 4.9.1.**
  - Kemah dan air: `tent` (kategori Buildings), `drop`.
  - Pendaki: `footprint`, `walk`.
  - Arah dan jalur: `compass`, `route`, `signpost`.
  - Bendera: `flag`.
  - Keselamatan dan izin: `first-aid-kit`, `ticket`, `pass-valid`.
  - Cuaca: `sun-cloudy`, `rainy`.
  - Alam: `binoculars`, `tree`.
  - `landscape` ada di kategori Media (metafora foto, bukan gunung). Tidak ada ransel.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/remixicon@4.9.1?structure=flat)
- **Iconoir 7.12.1.**
  - Air dan api: `droplet`, `bonfire`.
  - Bendera: `triangle-flag`.
  - Pendaki: `trekking`, `walking`.
  - Arah dan jalur: `compass`, `path-arrow`.
  - Keselamatan: `cube-bandage`.
  - Cuaca: `cloud-sunny`, `rain`.
  - Alam: `binocular`, `pine-tree`.
  - Lokasi: `gps`.
  - Tidak ada: gunung, tenda, ransel, lapisan peta.

  — [listing](https://data.jsdelivr.com/v1/packages/npm/iconoir@7.12.1?structure=flat)
- **Heroicons 2.2.0.** Hanya `flag`, `ticket`, `fire`, `arrow-trending-up`, dan `map` (outline terverifikasi). Tidak ada gunung, tenda, kompas, P3K, cuaca, ransel, maupun lokasi. — [listing](https://data.jsdelivr.com/v1/packages/npm/heroicons@2.2.0?structure=flat); [map.svg](https://cdn.jsdelivr.net/npm/heroicons@2.2.0/24/outline/map.svg)
- **Maki dan Temaki (simbol POI peta, CC0).**
  - Maki: `mountain`, `volcano`, `campsite`, `drinking-water`, `waterfall`, `viewpoint`, `shelter`, `toilet`, `emergency-phone`.
  - Temaki: `mountain_range`, `tents`, `campfire`, `water_tap_drinkable`, `climbing`, `hut`, `sleep_shelter`, `picnic_shelter`, `binoculars`, `tree_needleleaved`.

  — [Maki](https://data.jsdelivr.com/v1/packages/npm/@mapbox/maki@8.2.0?structure=flat); [Temaki](https://data.jsdelivr.com/v1/packages/npm/@rapideditor/temaki@5.13.0?structure=flat)

#### Tebal goresan terhadap ukuran render (dihitung dari path/atribut) [FAKTA]

| Set dan gaya | Rasio goresan terhadap kotak | 12 px | 16 px | 20 px | 24 px |
|---|---|---|---|---|---|
| Phosphor Thin | 8/256 | 0,375 | 0,5 | 0,625 | 0,75 |
| Phosphor Light | 12/256 | 0,56 | 0,75 | 0,94 | 1,125 |
| Phosphor Regular | 16/256 = 6,25% | 0,75 | 1,0 | 1,25 | 1,5 |
| Phosphor Bold | 24/256 = 9,375% | 1,125 | 1,5 | 1,875 | 2,25 |
| Lucide, Tabler, Remix line | 2/24 = 8,33% | 1,0 | 1,33 | 1,67 | 2,0 |
| Iconoir, Heroicons outline, Hugeicons free, Fluent 24 regular, Carbon, Material wght 400 | 6,25% | 0,75 | 1,0 | 1,25 | 1,5 |
| Font Awesome solid (batang minus) | 64/512 = 12,5% | 1,5 | 2,0 | 2,5 | 3,0 |
| Tanda merek L | 5/48 = 10,4% | — | — | — | 2,5 |
| Tanda merek S | 2/16 = 12,5% | — | 2,0 | — | — |

Sumber per baris:
- Phosphor: [thin](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/thin/minus-thin.svg), [light](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/light/minus-light.svg), [regular](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/regular/minus.svg), [bold](https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/bold/minus-bold.svg).
- Lucide, Tabler, Remix: [Lucide](https://cdn.jsdelivr.net/npm/lucide-static@1.48.0/icons/minus.svg), [Tabler](https://cdn.jsdelivr.net/npm/@tabler/icons@3.48.0/icons/outline/minus.svg), [Remix](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/icons/System/subtract-line.svg).
- Baris 6,25%: [Iconoir](https://cdn.jsdelivr.net/npm/iconoir@7.12.1/icons/regular/minus.svg), [Heroicons](https://cdn.jsdelivr.net/npm/heroicons@2.2.0/24/outline/minus.svg), [Hugeicons](https://cdn.jsdelivr.net/npm/@hugeicons/core-free-icons@4.3.5/dist/esm/MinusSignIcon.js), [Fluent](https://cdn.jsdelivr.net/npm/@fluentui/svg-icons@1.1.342/icons/subtract_24_regular.svg), [Carbon](https://cdn.jsdelivr.net/npm/@carbon/icons@11.89.0/svg/32/subtract.svg), [Material 400](https://cdn.jsdelivr.net/npm/@material-symbols/svg-400/outlined/remove.svg).
- Font Awesome: [FA](https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/svgs/solid/minus.svg).
- Tanda merek: [Mark.tsx](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx).

Bentuk ujung goresan:
- **Bulat:** Phosphor, Lucide, Tabler, Iconoir, Heroicons, Fluent, dan Font Awesome. Ujung Phosphor minus berbentuk busur `a8,8`.
- **Persegi:** Material Outlined, Carbon, dan Remix line. — [Material remove](https://cdn.jsdelivr.net/npm/@material-symbols/svg-400/outlined/remove.svg); [Carbon](https://cdn.jsdelivr.net/npm/@carbon/icons@11.89.0/svg/32/subtract.svg); [Remix](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/icons/System/subtract-line.svg)

#### Kelaziman di UI hasil AI dan template [FAKTA]
- **Unduhan npm 15–21 Sep 2026.** Semuanya dari endpoint yang sama, `https://api.npmjs.org/downloads/point/last-week/<paket>`.

  | Paket | Unduhan per minggu |
  |---|---|
  | [lucide-react](https://api.npmjs.org/downloads/point/last-week/lucide-react) | 77.191.934 |
  | react-icons | 6.971.814 |
  | @mui/icons-material | 5.472.593 |
  | @radix-ui/react-icons | 3.790.986 |
  | @phosphor-icons/react | 3.032.232 |
  | @heroicons/react | 2.951.848 |
  | @tabler/icons-react | 2.239.511 |
  | @fortawesome/react-fontawesome | 1.676.552 |
  | @hugeicons/react | 816.677 |
  | @remixicon/react | 692.392 |
  | @fluentui/react-icons | 668.305 |
  | @carbon/icons-react | 244.664 |
  | iconoir-react | 122.274 |

  Jumlah 13 paket 105.871.782, jadi porsi lucide-react 72,9%, sekitar 25,5 kali @phosphor-icons/react.
- **shadcn/ui.** "The new-york style now uses Lucide as the default icon set." (Nov 2024). — [shadcn changelog](https://ui.shadcn.com/docs/changelog/2024-11-icons)
- **Bolt, v0, Lovable belum terverifikasi.** Prompt sistem bolt.new yang open source tidak memuat kata "lucide" maupun "icon", dan halaman dukungan Bolt tentang project settings tidak menyebut Lucide. Klaim "Bolt default = Lucide" dari ringkasan mesin pencari tidak terverifikasi. — [prompts.ts](https://raw.githubusercontent.com/stackblitz/bolt.new/main/app/lib/.server/llm/prompts.ts); [Bolt project settings](https://support.bolt.new/building/using-bolt/project-settings)
- **Skill desain AI di repo ini sudah mengodifikasi Phosphor sebagai "pengganti Lucide":**
  - design-taste-frontend: "Allowed libraries (priority order): `@phosphor-icons/react`, `hugeicons-react`, `@radix-ui/react-icons`, `@tabler/icons-react`". — [SKILL.md:141](file:///E:/praktik_aplikasi_web/.agents/skills/design-taste-frontend/SKILL.md)
  - redesign-existing-projects: "Lucide or Feather icons exclusively. These are the 'default' AI icon choice. Use Phosphor, Heroicons, or a custom set for differentiation." — [SKILL.md:106](file:///E:/praktik_aplikasi_web/.agents/skills/redesign-existing-projects/SKILL.md)
  - high-end-visual-design: "Banned Icons: Standard thick-stroked Lucide, FontAwesome, or Material Icons. Use only … Phosphor Light, Remix Line". — [SKILL.md:16](file:///E:/praktik_aplikasi_web/.agents/skills/high-end-visual-design/SKILL.md)
- **Filter antislop proyek** menandai "an icon set chosen for its recognizable library look (Lucide-style thin rounded strokes) instead of relevance to the content". — [antislop SKILL.md:199,407,643](file:///E:/praktik_aplikasi_web/.claude/skills/antislop/SKILL.md)

### Inferences
- **Cakupan UI umum bukan alasan memilih.** Keempat set besar menutup semua konsep prototipe dengan nama persis. Alasan memilih harus datang dari lisensi, varian isi, bobot, pemeliharaan, kecocokan dengan tata bahasa merek, dan glyph domain.
- **"Bukan Lucide" tidak otomatis lolos kritik antislop.** Phosphor Regular (6,25%) justru lebih tipis daripada Lucide (8,33%), dan sama-sama berujung bulat. Lolos-tidaknya ditentukan relevansi yang tertulis.
- **Phosphor kini berkode "alternatif AI kedua".** Tiga skill AI di repo ini merekomendasikannya, dan unduhan @phosphor-icons/react tertinggi kelima di antara paket yang diukur. Pembeda MEMOUNT tidak bisa bersandar pada pemilihan pustaka saja.
- **Katalog Phosphor beku.** Tidak ada rilis ikon sejak 29-03-2024, jadi glyph domain yang belum ada (puncak, sumber air, pos, trailhead, status pos) tidak akan datang dari hulu. Kebutuhan itu mendorong ke model hibrida, bukan ke pergantian pustaka.
- **Material Symbols punya cakupan domain dan sumbu optik terbaik**, termasuk `mountain_flag`, `elevation`, `altitude`, `trail_length`, dan `opsz`. Tetapi ujung Outlined persegi, sedangkan tanda merek memakai ujung bulat, dan tampilannya identik dengan Android/Google. Kecocokan dengan tata bahasa merek lebih rendah daripada Phosphor.
- **Kandidat yang gugur dan alasannya:**
  - Lucide: bawaan AI, tanpa varian isi, ditolak `DESIGN.md`.
  - Remix: lisensi baru melarang pemakaian sebagai identitas merek dan metadata lisensinya tidak konsisten.
  - Font Awesome Free dan Streamline: atribusi wajib; Streamline juga dibatasi 50 ikon per proyek.
  - Heroicons: tanpa glyph domain.
  - Iconoir: tanpa gunung, tenda, dan lapisan peta.
  - Hugeicons free: tanpa varian isi untuk tab aktif.
  - Carbon dan Fluent: berkarakter perusahaan (IBM, Microsoft).
- **Kepatuhan lisensi.** Path Phosphor yang ditanam adalah "copies or substantial portions", jadi teks MIT beserta copyright-nya perlu ikut di repo. Pemeriksaan tidak menemukannya.
- **Render di layar kerapatan rendah (perhitungan geometri, belum uji perangkat).** Batang minus Phosphor Regular berada di y 120–136 dari 256:
  - Pada 16 px (16 unit per px), batang jatuh di 7,5–8,5 px, tepat mengangkangi batas piksel. Pada DPR 1 ia dirender sebagai dua baris berintensitas sekitar 50% (buram dan pudar). Pada DPR 2 menjadi 15–17 px perangkat, tajam.
  - Pada 12 px (0,75 px CSS), goresan tetap sub-piksel di DPR 2 (1,5 px perangkat). Ini titik terlemah kode sekarang: panah naik/turun 12 px di CheckpointJourney.
  - Lucide (2 px pada 24 px) tajam pada 24 px di DPR 1, tetapi buram pada 16 px.

  Setiap set hanya tajam pada ukuran desainnya. Itu alasan sistem matang menggambar master per ukuran.

### Gaps
- Uji render nyata pada ponsel Android murah (DPR 1,5–2, layar di bawah matahari) belum dilakukan. Semua klaim ketajaman di atas berasal dari geometri.
- Distribusi DPR perangkat Indonesia tidak ditemukan. StatCounter hanya memberi resolusi viewport CSS (lihat §2).
- Bawaan ikon v0, Bolt, dan Lovable tidak terverifikasi dari sumber primer. Template Lovable tidak ditemukan di GitHub (404).
- Aturan grid dan keyline resmi Carbon tidak terverifikasi karena halaman desainnya 404. Tebal 2/32 diukur dari path.
- Jumlah glyph unik Material Symbols dan Hugeicons mungkin lebih kecil daripada hitungan karena alias.
- Lisensi per set Streamline (mana yang CC BY 4.0) tidak dicek satu per satu.
- Halaman dokumentasi `components.json` shadcn tidak menyebut opsi `iconLibrary`, sehingga mekanisme pemilihan pustaka di shadcn terbaru tidak terverifikasi. — [components.json](https://ui.shadcn.com/docs/components-json)

## 2. Bukti kegunaan ikon: label, terisi atau garis untuk tab aktif, tebal goresan, pengenalan lintas budaya

### Takeaway
Bukti yang ada saling menguatkan:
- **Label teks selalu tampak.** NN/g 2014; Wiedenbeck 1999: ikon+label paling baik, ikon saja paling buruk di awal.
- **Ikon kemiripan (resemblance) paling mudah dikenali.** Nielsen 2014.
- **Bagi pemula, jarak semantik paling menentukan; bagi pengguna berpengalaman, keakraban.** Isherwood dkk. 2007: karakteristik ikon menjelaskan hingga 69% varians kinerja.
- **Status aktif tab ditandai varian terisi.** Apple HIG (tab bar diperbarui 8 Jun 2026), Fluent 2, dan sumbu FILL Material Symbols menganjurkannya. Satu studi (sumber sekunder) menemukan ikon solid umumnya lebih cepat dikenali, dengan pengecualian.

Studi pengenalan ikon khusus pengguna Indonesia tidak ditemukan.

### Cited Findings
- **NN/g, Aurora Harley, "Icon Usability" (27 Jul 2014).** — [NN/g](https://www.nngroup.com/articles/icon-usability/)
  - "There are a few icons that enjoy mostly universal recognition from users. The icons for home, print, and the magnifying glass for search are such instances."
  - "Icon labels should be visible at all times, without any interaction from the user."
  - Aturan 5 detik: "if it takes you more than 5 seconds to think of an appropriate icon for something, it is unlikely that an icon can effectively communicate that meaning".
  - Anjuran menguji pengenalan dengan bertanya pengguna, lalu menguji ingatan beberapa minggu kemudian.
- **NN/g, Jakob Nielsen, "Icon Classification" (17 Aug 2014).** — [NN/g](https://www.nngroup.com/articles/classifying-icons/)
  - Tiga jenis ikon: resemblance ("Depicting a physical object"), reference (analogi), arbitrary ("only have meaning by convention").
  - "Resemblance icons usually have the best usability". Nielsen memperingatkan terhadap ikon arbitrer baru.
- **Wiedenbeck (1999), Behaviour & Information Technology.** Membandingkan antarmuka label saja, ikon saja, dan ikon+label. — [DOI](https://doi.org/10.1080/014492999119129); abstrak via [OpenAlex](https://api.openalex.org/works/doi:10.1080/014492999119129)
  - "in the first session performance was best on the label-only and icon-label interfaces. Performance on the icon-only interface was much poorer in session 1"; retensi ikon-saja lebih buruk, tetapi "the effect was short-lived".
  - "Perceptions of ease of use were consistently better for the icon-label interface".
- **Isherwood, McDougall & Curry (2007), Human Factors.** 30 peserta. — [DOI](https://doi.org/10.1518/001872007X200102); abstrak via [OpenAlex](https://api.openalex.org/works/doi:10.1518/001872007X200102)
  - Karakteristik ikon "accounting for up to 69% of the variance".
  - "Semantic distance is crucial initially while icon-function relationships are learned, but familiarity is important later".
  - "icon concreteness may not be of primary importance".
- **McDougall, Curry & de Bruijn (1999).** Norma rating untuk 239 simbol pada lima dimensi: concreteness, complexity, meaningfulness, familiarity, semantic distance. — [DOI](https://doi.org/10.3758/BF03200730); abstrak via [Semantic Scholar](https://api.semanticscholar.org/graph/v1/paper/DOI:10.3758/BF03200730)
- **Apple HIG, Tab bars.** Change log: "June 8, 2026 Updated terminology and art. December 16, 2025 Updated guidance for Liquid Glass." — [HIG tab bars](https://developer.apple.com/design/human-interface-guidelines/tab-bars); [JSON](https://developer.apple.com/tutorials/data/design/human-interface-guidelines/tab-bars.json)
  - "Prefer filled symbols or icons for consistency with the platform."
  - "Include tab labels to help with navigation … Use single words whenever possible."
- **Apple HIG, SF Symbols** (change log terakhir 28 Jul 2025). "use the fill variant to indicate selection. Outline is the most common variant in SF Symbols. An outlined symbol has no solid areas, resembling the appearance of text." — [HIG SF Symbols](https://developer.apple.com/design/human-interface-guidelines/sf-symbols)
- **Apple HIG, Icons** (change log 9 Jun 2025). — [HIG icons](https://developer.apple.com/design/human-interface-guidelines/icons); [JSON](https://developer.apple.com/tutorials/data/design/human-interface-guidelines/icons.json)
  - "Create a recognizable, highly simplified design."
  - "all interface icons in your app need to use a consistent size, level of detail, stroke thickness (or weight), and perspective"; "use the same stroke weight in every icon. In general, match the weights of interface icons and adjacent text".
  - "Prefer depicting gender-neutral human figures and avoid images that might be hard to recognize across different cultures or languages."
  - "Provide a selected-state version of an interface icon only if necessary … The system updates the visual appearance of the selected state automatically."
- **Fluent 2.** Regular "used primarily as a wayfinding device"; Filled "used for highlighting selected states or for smaller moments that need a little more weight and readabililty" [sic]. Ikon dinamai menurut bentuk: "Shield, not security". — [Fluent 2 iconography](https://fluent2.microsoft.design/iconography)
- **Material Symbols (Google).** — [panduan](https://developers.google.com/fonts/docs/material_symbols)
  - "To convey a state transition, use the fill axis for animation or interaction."
  - Optical size: "the stroke weight (thickness) changes as the icon size scales".
  - Grade −25 "To reduce glare for a light symbol on a dark background".
- **Android Developers, navigation bar (Compose).** "Three to five destinations of equal importance"; parameter `label` bersifat "Optional". Halaman ini tidak memuat aturan terisi/garis. — [Android nav bar](https://developer.android.com/develop/ui/compose/components/navigation-bar)
- **UX Movement (20 Feb 2019)** meringkas makalah UNC "Filled-in vs. Outline Icons: The Impact of Icon Style on Usability". — [UX Movement](https://uxmovement.com/mobile/solid-vs-outline-icons-which-are-faster-to-recognize/); makalah asli [UNC CDR](https://cdr.lib.unc.edu/indexablecontent/uuid:e7ece0ee-c1ea-48c2-bb01-108122686e5c) (tidak dapat diakses, koneksi ditolak)
  - "Solid icons were generally faster to recognize than outline icons, but with a few exceptions".
  - Solid lebih cepat untuk thumb, scissors, phone, tools. Outline lebih cepat untuk comment bubble, trash can, key.
  - Tanpa beda untuk star, cart, flag.
  - Gembok paling sering salah dikenali, "over a quarter of all failures", karena tanpa lubang kunci.
- **WCAG 2.2 Understanding 1.4.11.** "Due to anti-aliasing, particularly thin lines and shapes of non-text elements may be rendered by user agents with a much fainter color than the actual color defined in the underlying CSS"; anjurannya "avoid particularly thin lines and shapes". — [Understanding 1.4.11](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html)
- **Pedoman set ikon soal piksel.**
  - Lucide: diagonal disejajarkan ke grid "to avoid blur on low-density displays". — [Lucide guide](https://lucide.dev/contribute/icon-design-guide)
  - Octicons: "Align the outer edge of shapes to pixel boundaries". — [Primer](https://primer.style/foundations/icons/design-guidelines)
  - Apple: pada ukuran kecil "use fewer lines and thicken them by aligning them to the reduced pixel grid. In the 16x16 px size, you might remove the lines altogether". — [HIG icons](https://developer.apple.com/design/human-interface-guidelines/icons)
- **StatCounter, viewport ponsel Indonesia, Agustus 2026.**
  - 360x800 (9,75%), 414x896 (6,01%), 393x873 (4,66%), 390x844 (4,24%), 385x854 (3,24%), 360x806 (3,19%).

  — [StatCounter](https://gs.statcounter.com/screen-resolution-stats/mobile/indonesia)
- **Keadaan sekarang.** Tab aktif = varian `-fill` + garis di atas + huruf tebal + `aria-current`; ikon tab 24 px. — [NavLinks.tsx:89-102](file:///E:/praktik_aplikasi_web/resources/js/layouts/NavLinks.tsx); [Icon.tsx:6-9](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)
- **Riset UI/UX saudara** mengusulkan "Phosphor regular tetap. Tab aktif memakai bobot fill, label, garis, dan `aria-current`". — [01-bahasa-desain-os.md:439](file:///E:/praktik_aplikasi_web/docs/riset/2026-09-25-uiux/01-bahasa-desain-os.md)

### Inferences
- **Pola tab aktif sekarang searah konsensus platform.** Apple, Fluent, Material FILL, dan studi UNC via UX Movement semua mendukung varian terisi, ditambah penanda non-warna untuk WCAG 1.4.1. Pola ini dipertahankan.
- **Konsekuensi desain untuk glyph kustom.** Glyph garis tunggal seperti rute tidak punya bentuk "terisi" alami, karena tidak ada ruang tertutup untuk diisi. Status aktifnya harus dirancang, misalnya cincin pos yang terisi dan goresan yang lebih tebal, dan tetap disertai label serta garis indikator.
- **Pengguna MEMOUNT kebanyakan pemula**, jadi berada di fase "semantic distance is crucial". Tab utama sebaiknya memakai ikon kemiripan berjarak semantik kecil (tas ransel untuk Trips, gunung/peta untuk Explore). Glyph kustom yang bersifat referensi atau arbitrer, seperti cincin pos, hanya aman bila selalu berlabel dan diuji. Keakraban akan terbentuk lewat pemakaian berulang (Isherwood 2007).
- **Label tab tetap wajib.** NN/g hanya menyebut home, print, dan search sebagai hampir universal. Empat dari lima tab MEMOUNT (Jelajah, Trip, Lacak, Progres) tidak termasuk kelompok itu. Label satu kata dalam Bahasa Indonesia mengikuti anjuran Apple.
- **Aturan produk menuntut kosakata ikon status yang jujur:**
  - UNKNOWN tidak boleh memakai segitiga peringatan atau glyph "rendah" (R-006, R-007). Usulan: glyph netral, misalnya cincin putus-putus, dengan label "Belum dinilai".
  - OPEN tidak boleh memakai centang atau perisai (R-012).
  - Label kecocokan publik tetap teks (R-004), tanpa ikon ala lampu lalu lintas yang menambah makna keempat.
- **[HIPOTESIS]** `shield-check` untuk sumber OFFICIAL bisa terbaca "aman/terlindungi", bukan "berasal dari otoritas". Ini risiko jarak semantik yang bersinggungan dengan R-012. Alternatif yang perlu diuji: glyph institusi atau cap/segel, selalu dengan label "Resmi" dan nama sumbernya.
- **Viewport 360×800 CSS** mendominasi, jadi lima tab × 72 px pas di lebar layar. Bila perangkatnya panel 720×1600, DPR = 2 dan ikon 24 px mendapat 48 piksel perangkat. Ini inferensi karena DPR tidak diukur.

### Gaps
- Aturan Material 3 soal "ikon terisi untuk tujuan aktif, garis untuk tidak aktif" tidak terverifikasi dari sumber primer. Halaman m3.material.io dirender JavaScript (HTML 61.746 byte tanpa kata "filled"), dan dokumen Android tidak memuatnya.
- Makalah UNC asli tidak dapat diakses, sehingga ukuran sampel dan metodenya tidak diketahui. Bukti terisi-vs-garis bersifat sekunder.
- Abstrak Burmistrov dkk. (2015, "Flat Design vs Traditional Design", INTERACT) tidak diperoleh. — [DOI](https://doi.org/10.1007/978-3-319-22668-2_10)
- Tidak ditemukan studi pengenalan ikon pada pengguna Indonesia, pendaki pemula, ataupun lansia Indonesia.
- Tidak ditemukan studi yang membandingkan goresan 1,5 px vs 2 px di bawah sinar matahari.

## 3. Cara produk outdoor dan navigasi menangani ikon

### Takeaway
Produk peta memisahkan dua bahasa ikon:
- **Ikon kontrol UI**, yang monokrom dan bergaris.
- **Simbol POI di peta**, berupa glyph solid di dalam wadah berwarna kategori. Contohnya Google Places (warna kategori ditambah mask glyph), Apple MapKit (penanda bertint dengan glyph), Maki (solid, 15×15), dan simbol NPS (solid, hitam/putih, digambar per ukuran 14/22/30 px).

Simbol informasi publik diuji pemahamannya lintas budaya: ISO 7001:2023 berisi 177 simbol, dan piktogram DOT/AIGA (50 simbol) berstatus domain publik.

Untuk Strava, Komoot, AllTrails, dan Gaia GPS, situs webnya tidak memuat jejak pustaka ikon publik mana pun. Ini indikasi set internal, tetapi pernyataan resmi tidak ditemukan. Konvensi penandaan jalur Indonesia tidak berhasil didokumentasikan.

### Cited Findings
- **Google Places.** "Each icon is defined by two response fields: The icon base URI contains the base URL for a non-colored icon … The icon background color contains the default HEX color code for the place's category." — [Google Places icons](https://developers.google.com/maps/documentation/places/web-service/icons)
  - Warna per kategori:

    | Kategori | Warna |
    |---|---|
    | Food and drink | #FF9E67 |
    | Retail | #4B96F3 |
    | Services | #909CE1 |
    | Entertainment | #13B5C7 |
    | Transportation | #10BDFF |
    | Municipal/generic/religious | #7B9EB0 |
    | Outdoor | #4DB546 |
    | Emergency | #F88181 |
- **Apple HIG, Maps** (change log terakhir 18 Dec 2024). — [HIG maps](https://developer.apple.com/design/human-interface-guidelines/maps)
  - "Use annotations that match the visual style of your app … The default annotation marker has a red tint and a white pin icon. You can change the tint to match the color scheme of your app. You can also change the icon to a string or image".
  - Untuk kontrol di atas peta: "Make sure there's enough contrast between custom controls and the map … Consider using a thin stroke or light drop shadow".
- **NPS symbol library** berisi "National Park Service map symbols optimized for the web". — [GitHub NPS](https://github.com/nationalparkservice/symbol-library); [tree API](https://api.github.com/repos/nationalparkservice/symbol-library/git/trees/gh-pages?recursive=1)
  - "Each symbol is designed at three pixel-perfect sizes (max dimensions 14, 22, and 30 pixels square)". Subset 94 simbol digambar pada 12, 18, dan 24 px, dalam versi hitam dan putih.
  - Hitungan dari tree repo: 1.735 SVG di `src/standalone`, 295 simbol berbeda. Contohnya trailhead, campground, campsite, campfire, drinking-water, first-aid, ranger-station, scenic-viewpoint, backcountry-camping-permit, sleeping-shelter, pit-toilet, stay-on-trail.
- **Lisensi repo NPS ambigu.** Isi `LICENSE.txt` adalah "BSD License Copyright© 2013, Mapbox, LLC", bukan pernyataan domain publik. — [LICENSE.txt](https://raw.githubusercontent.com/nationalparkservice/symbol-library/gh-pages/LICENSE.txt)
- **ISO 7001:2023** (Februari 2023) berisi 177 simbol dalam tujuh kategori, antara lain "Drinking water" (PF 007), "Camping" (TC 002), "Picnic area" (TC 004), "Information" (PF 001). Simbolnya "have met the criteria for comprehensibility" setelah "extensive testing in several countries and different cultures". — [Wikipedia ISO 7001](https://en.wikipedia.org/wiki/ISO_7001)
- **Piktogram DOT/AIGA.** 34 simbol pada November 1974 ditambah 16 pada 1979, jadi 50. Dirancang Roger Cook dan Don Shanosky. "As works of the United States government, the images are in the public domain". — [Wikipedia DOT pictograms](https://en.wikipedia.org/wiki/DOT_pictograms)
- **Maki dan Temaki.** Maki: viewBox 15×15, solid tanpa stroke, CC0, 215 ikon. Temaki (perluasan untuk editor OSM): 557 ikon, CC0. — [Maki mountain.svg](https://cdn.jsdelivr.net/npm/@mapbox/maki@8.2.0/icons/mountain.svg); [Maki listing](https://data.jsdelivr.com/v1/packages/npm/@mapbox/maki@8.2.0?structure=flat); [Temaki listing](https://data.jsdelivr.com/v1/packages/npm/@rapideditor/temaki@5.13.0?structure=flat)
- **Sidik jari pustaka ikon di halaman depan produk outdoor (diukur 25-09-2026).** Kata kunci yang dicari: lucide, phosphor, material-symbols/material icons, fontawesome, heroicons, tabler. Hasilnya nol untuk semua situs. — [Strava](https://www.strava.com/); [Komoot](https://www.komoot.com/); [AllTrails](https://www.alltrails.com/); [Gaia GPS](https://www.gaiagps.com/)

  | Situs | Ukuran HTML | Elemen `<svg` | Catatan |
  |---|---|---|---|
  | strava.com | 625.295 byte | 32 | nama kelas CSS-module seperti `Icon_bar`, `Icon_menuIcon` |
  | komoot.com | 778.009 byte | 107 | — |
  | alltrails.com | 318.156 byte | 17 | — |
  | gaiagps.com | 33.200 byte | 0 | satu kecocokan "tabler" ternyata `TableRow-….js` (positif palsu) |
  | wikiloc.com | 403 | — | tidak dapat diakses |
- **Registry npm.** Paket `@strava/icons`, `@strava/ui`, `@alltrails/icons`, `@komoot/icons`, `@garmin/icons`, `@gaiagps/icons`, `@onxmaps/icons`, dan `@wikiloc/icons` semuanya 404. — [contoh](https://registry.npmjs.org/@strava%2ficons)

### Inferences
- **Peta dan UI pakai dua bahasa visual [REKOMENDASI].** Di peta: glyph solid di dalam wadah (bentuk dan warna menurut kategori: pos, air, kemah, bahaya, fasilitas). Di chrome UI: Phosphor bergaris. Pola ini mengikuti Google, Apple, Maki, dan NPS. Kontras penanda terhadap peta dijaga lewat tepi atau bayangan tipis (Apple).
- **Glyph solid lebih tahan skala kecil.** NPS menggambarnya per ukuran; Maki memakai grid 15 px. Untuk penanda pos di peta berukuran 14–22 px, glyph solid lebih aman daripada goresan 1–1,5 px.
- **Metafora baku layak dipinjam untuk glyph domain.** Drinking water (ISO PF 007, NPS drinking-water, Maki drinking-water), camping (ISO TC 002), dan first aid sudah teruji lintas budaya.
  - [HIPOTESIS] Sumber air di jalur Indonesia umumnya mata air atau sungai, bukan keran. Glyph "tetes + garis tanah/aliran" mungkin lebih jujur daripada keran. Perlu uji pengenalan.
- **Pemimpin outdoor kemungkinan memakai set internal (inferensi lemah).** Tidak ada sidik jari pustaka publik dan tidak ada paket ikon publik, tetapi ini bukan bukti positif.
- **Weather tetap mengikuti R-013.** Glyph cuaca tidak bisa membawa sumber dan kesegaran data. Keadaan "data basi" atau "tidak tersedia" perlu glyph dan teks tersendiri, bukan ikon cuaca normal.

### Gaps
- Dokumentasi resmi sistem ikon AllTrails, Komoot, Strava, Gaia GPS, dan Garmin (design system, rebrand, legenda peta) tidak ditemukan dengan fetch langsung. Kuota pencarian habis.
- Konvensi penandaan jalur Indonesia tidak terdokumentasi dari sumber primer dalam sesi ini, misalnya penomoran "Pos 1…n", patok HM, penanda tali/pita, dan rambu taman nasional dari KLHK maupun SNI rambu wisata. Satu-satunya rujukan konteks adalah frasa "cincin pos" pada tanda merek sendiri. — [Mark.tsx](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx)
- Ambang uji pemahaman ISO 9186 tidak terverifikasi, karena artikel Wikipedia ISO 7001 tidak memuatnya.
- Status lisensi simbol NPS perlu dipastikan sebelum dipakai. Karya pemerintah AS umumnya domain publik, tetapi `LICENSE.txt` repo menulis BSD (Mapbox).

## 4. Pipeline glyph kustom: grid, keyline, pencocokan goresan dengan tanda merek, optimasi SVG, sprite atau komponen inline, estimasi usaha, konsistensi dengan pustaka dasar

### Takeaway
Tim yang memelihara set sendiri bekerja dengan aturan tertulis yang sempit:
- satu grid (16 atau 24);
- satu tebal goresan (1,5–2 px);
- ujung dan sambungan konsisten;
- jarak antar-elemen minimum 1–2 px;
- penyelarasan ke piksel;
- sering master per ukuran (Octicons 16+24, NPS 14/22/30, tanda merek MEMOUNT sendiri 16+48).

Untuk MEMOUNT, glyph kustom sebaiknya digambar di grid Phosphor (256 unit; 16 unit = goresan Regular) supaya bersanding mulus. Motif merek dipinjam (cincin pos, belokan rute, ujung bulat) tanpa meniru tebal logo.

Soal pengiriman: sprite `<use>` untuk banyak ikon, inline untuk sedikit ikon di atas lipatan. SVG-in-JS menambah biaya parse dan memori. Tolok ukur usaha dari sumber primer tidak ditemukan.

### Cited Findings
- **Lucide.** — [Lucide icon design guide](https://lucide.dev/contribute/icon-design-guide)
  - "Icons must be designed on a 24 × 24-pixel canvas", "at least 1 pixel of padding", "Strokes must be 2 pixels wide", round joins, round caps, "centered on their paths".
  - Radius 2 px untuk sudut 90° pada bentuk ≥8 px, dan 1 px untuk bentuk <8 px; "2.41 pixel radius (1+√2)" untuk diagonal yang bertemu siku.
  - "at least 2 pixels of visual spacing" antar-elemen; koordinat dan pusat busur di grid piksel.
- **Octicons (GitHub Primer).** — [Primer design guidelines](https://primer.style/foundations/icons/design-guidelines)
  - "Always design two versions of each icon: a 16px version and a 24px version."
  - "Use a consistent stroke width of 1.5px for both 16px and 24px icons."
  - Radius sudut 1 px (0,25 px untuk elemen terisi kecil); celah 1 px, atau 1,5 px untuk modifier; ujung dan sambungan bulat; tepi luar diselaraskan ke piksel.
- **GitLab Pajamas.** — [Pajamas iconography](https://design.gitlab.com/product-foundations/iconography)
  - Grid 16×16 dengan "14px live area surrounded on all sides by 1px"; grid 12×12 untuk ikon status.
  - "Nearly all UI icons use a 1.5px stroke weight. Lines use rounded caps, unless doing so would misrepresent the metaphor."
  - Sudut kelipatan 15°; radius bawaan 2 px; celah 1 px boleh, 2 px lebih disukai.
  - `gl-icon` "hidden from screen readers by default"; beri `aria-label` bila bermakna.
- **Atlassian.** — [Atlassian iconography](https://atlassian.design/foundations/iconography)
  - 16×16 dengan "1.5px stroke width with shapes that pair rounded corners with sharp interior corners and square line caps".
  - Ukuran Medium 16 px bawaan; Small 12 px dipakai hemat.
  - "Use text labels to support icons wherever possible, and avoid using icons where they aren't necessary."
  - Kontributor diminta memastikan ikon tidak duplikat dan menimbang apakah label teks lebih jelas.
- **Apple HIG Icons.** Konsistensi ukuran, detail, tebal, dan perspektif antara ikon kustom dan ikon sistem; penyesuaian optis ("adjust individual icon sizes as necessary"); optical centering untuk ikon asimetris. — [HIG icons](https://developer.apple.com/design/human-interface-guidelines/icons)
- **Fluent 2.** Ukuran 48 ke atas diskalakan dengan kelipatan empat demi ketajaman piksel; ikon dinamai menurut bentuk. — [Fluent 2 iconography](https://fluent2.microsoft.design/iconography)
- **Material Symbols.** Sumbu opsz 20–48 mengubah tebal goresan menurut ukuran. Ini versi variabel dari "master per ukuran". — [Material Symbols guide](https://developers.google.com/fonts/docs/material_symbols)
- **Jacob "Kurt" Groß, "Breaking Up with SVG-in-JS"** (30 Jun 2023, diperbarui 2 Jun 2025). — [kurtextrem.de](https://kurtextrem.de/posts/svg-in-js)
  - Mengutip Jason Miller: bundle sebuah situs besar "almost 50% SVG icons (250kb), and most are unused", dan SVG-sebagai-JSX "costs a minimum of 3x more than other techniques".
  - Kode JS yang sudah di-parse tetap tinggal di heap, dan ini berarti pada perangkat bermemori kecil seperti Samsung Galaxy A50 (4 GB).
  - Anjuran: sprite `<use>` untuk banyak ikon, inline untuk ikon di atas lipatan dengan anggaran sekitar 4 kB, `<img>` untuk warna statis.
- **Hugeicons README.** Impor statis "~2-3KB per icon". Ini angka vendor. — [README](https://cdn.jsdelivr.net/npm/@hugeicons/core-free-icons@4.3.5/README.md)
- **Implementasi sekarang.**
  - `Icon.tsx` menyimpan 18 string path Phosphor dalam objek JS (SVG-in-JS, hanya ikon yang dipakai), dengan `fill="currentColor"` pada viewBox 256. — [Icon.tsx:12-42](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)
  - Tanda merek dibuat dari goresan (`stroke`), tidak dikonversi ke path terisi, dan punya dua master. — [Mark.tsx:18-22](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx)
  - Ada komponen Blade yang setara. — [icon.blade.php](file:///E:/praktik_aplikasi_web/resources/views/components/ui/icon.blade.php)
- **Batas lisensi saat menurunkan glyph.** Remix §3.2 dan §3.3 melarang set pesaing hasil modifikasi kecil dan pemakaian sebagai identitas merek. MIT (Phosphor) mewajibkan pemberitahuan hak cipta ikut di salinan. — [Remix License](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/License); [MIT, contoh Iconoir](https://cdn.jsdelivr.net/npm/iconoir@7.12.1/LICENSE)

### Inferences
- **Spesifikasi glyph kustom MEMOUNT [REKOMENDASI]:**
  - Kanvas 256. Area hidup mengikuti Phosphor: path `minus` dan `house` membentang pada x 32–224, jadi margin sekitar 32 unit, atau 3 px pada 24 px.
  - Goresan 16 unit untuk bobot Regular. Bila bobot Bold disetujui untuk ≤16 px, goresan 24 unit.
  - Ujung dan sambungan bulat, sama dengan Phosphor dan tanda merek.
  - Radius sudut dan celah minimum mengikuti proporsi Lucide/Octicons yang diskalakan ke 256: celah ≥2 px pada 24 px sama dengan ≥21 unit.
  - Motif merek dipakai sebagai "tanda tangan" domain: cincin pos dan belokan rute. Tebal logo (5/48) tidak ditiru, karena logo sengaja lebih tebal supaya tidak terbaca sebagai ikon fungsi (lihat catatan logo).
- **Master per ukuran [REKOMENDASI].** Glyph yang dipakai ≤16 px (misalnya panah naik/turun di perjalanan pos) diberi master 16 px sendiri, seperti yang sudah dilakukan tanda merek (master S). Ini sejalan dengan Octicons, NPS, dan Apple.
- **Alur produksi [REKOMENDASI].**
  1. Spesifikasi makna glyph dan larangan maknanya (R-006, R-012).
  2. Gambar sebagai goresan di Figma atau Inkscape pada grid 256.
  3. Ubah goresan menjadi path terisi (expand) supaya cocok dengan komponen `Icon` yang memakai `fill="currentColor"`, atau tambahkan mode stroke ke komponen itu.
  4. Optimasi SVG (misalnya SVGO sebagai alat pengembangan, bukan dependensi runtime).
  5. Tanam path di `Icon.tsx` dan `icon.blade.php` secara berpasangan.
  6. Perluas `IconSystemTest` untuk nama baru.
  7. Uji pengenalan dengan pengguna.
- **Pengiriman.** Pada skala sekarang (18 path, kemungkinan tumbuh ke 40–60) biaya SVG-in-JS kecil. Bila jumlah ikon bertambah atau PWA perlu cache terpisah, sprite `<symbol>`/`<use>` statis lebih hemat menurut argumen Groß. [INFERENSI] Keputusan ini tidak mendesak.
- **[HIPOTESIS] Usaha.** Tidak ada tolok ukur primer. Cara jujur mengestimasi: pilot 3 glyph (misalnya pos, sumber air, puncak) dengan dua master, catat jam kerja termasuk revisi dan uji, lalu ekstrapolasikan ke 8–12 glyph.

### Gaps
- Tolok ukur usaha pembuatan set ikon kecil tidak ditemukan dari studi kasus primer.
- Konfigurasi SVGO yang dipakai Lucide/Tabler untuk menjaga presisi path tidak diambil.
- Halaman desain ikon Carbon 404, sehingga aturan keyline Carbon tidak terverifikasi.
- Belum dicek apakah `IconSystemTest` sudah memeriksa pasangan nama React/Blade untuk seluruh ikon.

## 5. Aksesibilitas: aria-hidden, nama aksesibel, target sentuh, kontras non-teks goresan tipis di atas kertas hangat, forced-colors

### Takeaway
Hasil hitung kontras terhadap kertas #F6F3EC:

| Warna | Rasio | Status terhadap 3:1 |
|---|---|---|
| Hijau #234F32 | 8,48:1 | lolos |
| Bata #8E2F22 | 7,35:1 | lolos |
| Biru #24508C | 7,29:1 | lolos |
| Amber #E8B04B | 1,76:1 | gagal |

Amber tidak boleh membawa makna ikon apa pun di atas kertas. Di atas hijau, amber mencapai 4,80:1.

Aturan lain yang berlaku:
- **Ikon yang menyertai label tampak:** `aria-hidden`.
- **Tombol ikon tanpa teks:** nama aksesibel ditaruh di tombolnya. Teks tersembunyi-visual lebih baik daripada `aria-label` karena `aria-label` tidak ikut diterjemahkan otomatis.
- **Target sentuh:** WCAG 2.2 AA minimum 24×24 CSS px, dan aturan proyek 44×44 px untuk kontrol utama.
- **Forced-colors:** mode ini menimpa `fill` dan `stroke` SVG, jadi status harus dibawa bentuk (isi atau garis), bukan warna. Kode sekarang sudah memakai `currentColor`.
- **Garis tipis:** risiko pudar akibat anti-aliasing paling besar pada ikon 12 px.

### Cited Findings
- **Kontras dihitung dengan rumus luminans relatif WCAG** [FAKTA, hitungan sendiri]. — [definisi contrast ratio WCAG 2.2](https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio)
  - Terhadap #F6F3EC: #234F32 8,48:1; #8E2F22 7,35:1; #24508C 7,29:1; #E8B04B 1,76:1.
  - Terhadap putih: 9,39:1; 8,14:1; 8,07:1; 1,95:1.
  - #E8B04B di atas #234F32: 4,80:1.
- **Understanding 1.4.11.** — [Understanding 1.4.11](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html)
  - "Visual information necessary to indicate state, such as whether a component is selected or focused must also ensure that the information used to identify the control in that state has a minimum 3:1 contrast ratio."
  - Perubahan warna antar-state tidak wajib 3:1 bila state tidak bersebelahan.
  - Pengecualian untuk grafik yang informasinya sudah disampaikan teks.
  - Contoh ikon panah dropdown dengan kontras 4,7:1.
  - Peringatan anti-aliasing garis tipis (lihat §2).
- **Understanding 2.5.8.** "The size of the target for pointer inputs is at least 24 by 24 CSS pixels", dengan pengecualian spasi berupa lingkaran berdiameter 24 CSS px yang tidak beririsan. Kriteria 2.5.5 (Enhanced) dianjurkan untuk kontrol penting. — [Understanding 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)
- **Aturan proyek.** Kendali ramah sentuh sekitar 44×44 px untuk kendali utama di lapangan; status tidak hanya lewat warna. — [CLAUDE.md §6](file:///E:/praktik_aplikasi_web/CLAUDE.md)
- **Sara Soueidan, "Accessible Icon Buttons"** (22 May 2019). — [sarasoueidan.com](https://www.sarasoueidan.com/blog/accessible-icon-buttons/)
  - Ikon dekoratif memakai `aria-hidden="true"` ditambah `focusable="false"`. `focusable` adalah perbaikan untuk IE.
  - Urutan teknik: (1) teks tersembunyi-visual, (2) teks `hidden` dengan `aria-labelledby`, (3) `aria-label` di tombol.
  - Label yang ditaruh di SVG itu sendiri (`aria-label` atau `aria-labelledby` pada `<svg>`) "have documented failures in certain browser/screen reader combinations".
  - `aria-label` tidak ikut diterjemahkan otomatis.
  - SVG lebih disukai daripada font ikon karena mendukung Windows High Contrast.
- **MDN `forced-colors`.** Properti yang ditimpa meliputi `color`, `background-color`, `border-color`, "SVG `fill` attribute", dan "SVG `stroke` attribute"; `box-shadow` dipaksa `none`. Contoh utamanya Windows High Contrast; fitur ini "available across browsers since September 2022". Ada `forced-color-adjust: none` untuk opt-out. — [MDN forced-colors](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/forced-colors)
- **Kode sekarang.**
  - `Icon.tsx`: `fill="currentColor"`; tanpa label → `aria-hidden`; dengan label → `role="img"` + `<title>`. — [Icon.tsx:42-43](file:///E:/praktik_aplikasi_web/resources/js/components/ui/Icon.tsx)
  - Tanda merek memakai `stroke="currentColor"`. — [Mark.tsx:18-22](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx)
  - Test menjaga ikon dekoratif tersembunyi dan ikon berdiri sendiri berlabel. — [IconSystemTest.php](file:///E:/praktik_aplikasi_web/tests/Feature/IconSystemTest.php)
  - Panah 12 px di perjalanan pos: goresan Phosphor Regular 0,75 CSS px pada ukuran itu (hitungan §1). — [CheckpointJourney.tsx:31-32](file:///E:/praktik_aplikasi_web/resources/js/components/ui/CheckpointJourney.tsx)

### Inferences
- **Kontras aman untuk warna utama, tetapi goresan tipis mengurangi rasio efektif.** Hijau, bata, dan biru memberi ruang lebih dari dua kali lipat di atas ambang 3:1, cukup untuk menahan pemudaran goresan 1–1,5 px. Titik rawan tetap ikon 12 px dengan goresan 0,75 px.
- **[REKOMENDASI] Panah 12 px.** Naikkan ke 16 px, atau pakai master kecil/bobot Bold, atau keduanya. Perubahan ini murah dan tidak mengubah arah desain.
- **[REKOMENDASI] Tombol ikon tanpa teks** (bagikan, simpan, lapisan, lokasi saya, zoom) diberi nama pada elemen `<button>` lewat teks tersembunyi-visual. SVG di dalamnya `aria-hidden`.
  - Pola `role="img"` + `<title>` sekarang cocok untuk ikon informatif yang berdiri sendiri, bukan pengganti nama tombol.
  - Karena MEMOUNT multibahasa, teks tersembunyi yang ikut sistem terjemahan lebih aman daripada `aria-label`.
  - `focusable="false"` hanya relevan untuk IE yang sudah pensiun, jadi tidak perlu.
- **Target sentuh.** Ikon 24 px ditaruh di area sentuh 44–48 px. Ukuran glyph tidak menentukan ukuran target.
- **Forced-colors.** Semua `fill`/`stroke` dipaksa ke warna sistem, jadi:
  - penanda tab aktif harus tetap berupa perubahan bentuk (fill vs garis) dan garis indikator;
  - aksen amber pada cincin logo akan hilang, dan itu dapat diterima karena logo bukan pembawa status;
  - penanda POI peta yang bergantung pada warna kategori harus punya bentuk glyph berbeda per kategori.
- **Amber hanya untuk aksen merek**, atau untuk status di atas latar hijau (4,80:1). Di atas kertas, amber tidak boleh menjadi warna ikon peringatan; bata #8E2F22 (7,35:1) lebih tepat.

### Gaps
- Dukungan forced-colors atau mode kontras tinggi di Chrome Android tidak terdokumentasi di MDN. Perilaku SVG di pengaturan kontras tinggi Android belum diuji.
- Kontras efektif goresan 1–1,5 px setelah anti-aliasing belum diukur di perangkat nyata. Pengujian TalkBack untuk tombol ikon belum dilakukan.
- Artikel Soueidan bertanggal 2019. Kegagalan kombinasi peramban dan pembaca layar untuk label di SVG mungkin sudah berubah; perlu uji ulang.

## 6. Sintesis: set yang ada, hibrida, atau sepenuhnya kustom

### Takeaway
[REKOMENDASI] Pilih **hibrida**:
- Pertahankan Phosphor sebagai satu-satunya keluarga dasar untuk ikon UI umum, dengan varian `fill` untuk status aktif.
- Tetapkan kebijakan bobot yang berpihak pada keterbacaan. Usulan: Regular pada ≥20 px, Bold atau master kecil pada ≤16 px (perlu persetujuan pemilik karena `DESIGN.md` menetapkan regular).
- Gambar sendiri 8–12 glyph domain di grid Phosphor dengan tata bahasa merek "Jalur Berpos".
- Pakai glyph solid berwadah untuk POI peta.
- Tuliskan catatan relevansinya (antislop R-04), dan uji pengenalannya pada pendaki pemula Indonesia sebelum dibekukan.

Set sepenuhnya kustom ditolak karena biaya, hilangnya keakraban ikon universal, dan beban pemeliharaan. Mengganti pustaka dasar juga ditolak: tidak ada kandidat yang lebih cocok dengan tata bahasa merek sekaligus bebas masalah lisensi. Material Symbols adalah alternatif terkuat bila pemilik mengutamakan cakupan domain daripada kecocokan merek.

### Cited Findings
- **Cakupan.** Keempat set besar 36/36; Iconoir 33/36. — [listing Phosphor](https://data.jsdelivr.com/v1/packages/npm/@phosphor-icons/core@2.1.1?structure=flat); [codepoints Material](https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints)
- **Pemeliharaan.** Phosphor core terakhir 29-03-2024. Lucide, Tabler, Hugeicons, Carbon, Fluent, dan Octicons semuanya rilis dalam tujuh hari terakhir sebelum 25-09-2026. — [registry Phosphor](https://registry.npmjs.org/@phosphor-icons%2fcore); [registry Lucide](https://registry.npmjs.org/lucide-static); [registry Tabler](https://registry.npmjs.org/@tabler%2ficons)
- **Kelaziman AI.** lucide-react 72,9% unduhan di antara 13 paket ikon React; bawaan shadcn. — [npm lucide-react](https://api.npmjs.org/downloads/point/last-week/lucide-react); [shadcn](https://ui.shadcn.com/docs/changelog/2024-11-icons)
- **Varian isi dan status aktif.** Apple "Prefer filled symbols"; Fluent "Filled … selected states"; Material "use the fill axis". — [HIG tab bars](https://developer.apple.com/design/human-interface-guidelines/tab-bars); [Fluent 2](https://fluent2.microsoft.design/iconography); [Material Symbols](https://developers.google.com/fonts/docs/material_symbols)
- **Label dan keakraban.** NN/g "visible at all times"; Wiedenbeck ikon+label paling mudah; Isherwood: jarak semantik di awal, keakraban kemudian. — [NN/g](https://www.nngroup.com/articles/icon-usability/); [Wiedenbeck](https://doi.org/10.1080/014492999119129); [Isherwood](https://doi.org/10.1518/001872007X200102)
- **Konsistensi.** Apple: "use the same stroke weight in every icon". — [HIG icons](https://developer.apple.com/design/human-interface-guidelines/icons)
- **Batas lisensi turunan merek.** Remix §3.3 melarang pemakaian sebagai tanda merek. — [Remix License](https://cdn.jsdelivr.net/npm/remixicon@4.9.1/License)
- **Tanda merek.** Goresan bulat 5/48 dan 2/16, dua master. — [Mark.tsx](file:///E:/praktik_aplikasi_web/resources/js/components/brand/Mark.tsx)

### Inferences

Matriks keputusan [INFERENSI; penilaian dari fakta di atas; ✓ baik, ~ sebagian, ✗ lemah]:

| Opsi | Kecocokan merek | Glyph domain | Varian aktif | Pemeliharaan | Lisensi | Risiko "tampilan AI" | Usaha | Keterbacaan kecil |
|---|---|---|---|---|---|---|---|---|
| A. Phosphor saja, apa adanya | ~ (ujung bulat cocok, tanpa motif merek) | ~ (tanpa pos, puncak, sumber air) | ✓ | ✗ (beku sejak 2024) | ✓ MIT | ~ (alternatif AI yang sudah dikodifikasi) | ✓ | ✗ pada 12 px |
| **B. Hibrida: Phosphor + 8–12 glyph turunan merek** | ✓ | ✓ | ✓ (glyph kustom butuh state aktif yang dirancang) | ✓ (domain dipelihara sendiri) | ✓ | ✓ (relevansi tertulis dan bentuk khas) | ~ | ✓ bila ada kebijakan bobot dan master kecil |
| C. Sepenuhnya kustom | ✓ | ✓ | ✓ | ✗ (semua ikon dipelihara sendiri) | ✓ | ✓ | ✗ | tergantung eksekusi |
| D1. Ganti ke Material Symbols | ✗ (ujung persegi, identitas Google) | ✓ (terkaya) | ✓ FILL | ✓ | ✓ Apache-2.0 | ~ | ~ (migrasi) | ✓ (opsz) |
| D2. Ganti ke Tabler | ~ | ✓ | ~ (±20% filled) | ✓ | ✓ | ✗ (tampilan setipe Lucide) | ~ | ~ |
| D3. Lucide, Hugeicons free, Remix, Font Awesome, Streamline, Heroicons, Iconoir | — | — | — | — | — | — | — | Gugur di §1 (bawaan AI; tanpa isi; lisensi atau atribusi; celah domain) |

- **[REKOMENDASI] Daftar awal glyph domain kustom** (8–12, semuanya berlabel):
  1. Tab Lacak: rute dengan satu belokan dan cincin pos. State aktif: cincin terisi.
  2. Pos (cincin, bisa bernomor).
  3. Titik mulai/basecamp.
  4. Puncak (gunung dengan cincin di puncak).
  5. Sumber air (tetes dengan garis tanah).
  6. Area kemah atau shelter; bisa tetap memakai `tent` Phosphor bila lolos uji.
  7. Naik dan turun di lereng, dengan master 16 px.
  8. Status pos atau jalur tanpa makna keselamatan: buka (netral), tutup, dan belum dinilai (cincin putus-putus; R-006, R-012).
  9. Opsional: penanda sumber resmi yang bukan perisai, sebagai hasil uji hipotesis §2.

  Glyph UI umum (cari, lonceng, bagikan, simpan, filter, peta/daftar, lapisan, lokasi, info, jam, kalender, tautan keluar, kamera, putar/jeda, terang/gelap, gembok, panah) tetap Phosphor. Mengganti ikon yang sudah akrab tidak menambah nilai, dan bertentangan dengan temuan keakraban Isherwood.
- **[REKOMENDASI] Draf catatan relevansi untuk `DESIGN.md`** (menjawab antislop R-04):
  > Ikon UI umum memakai Phosphor karena ujung bulatnya sejalan dengan goresan tanda merek, setiap glyph punya varian isi untuk tab aktif, lisensinya MIT, dan cakupannya penuh untuk daftar ikon produk. Ikon domain (pos, puncak, sumber air, jalur, status) digambar sendiri dari tata bahasa "Jalur Berpos" agar ikon membawa makna pendakian, bukan tampilan pustaka.
- **[REKOMENDASI] Peta.** POI memakai glyph solid di wadah berwarna kategori, terpisah dari ikon chrome yang bergaris. Pola ini mengikuti Google, Apple, Maki, dan NPS.
- **[REKOMENDASI] Kepatuhan.** Tambahkan teks lisensi MIT Phosphor (copyright dan permission notice) ke repo, dekat komponen ikon atau di berkas notice pihak ketiga.
- **[HIPOTESIS] Uji tab.** Tab Explore dengan `map-trifold` (kode) dibandingkan `compass` (prototipe), dan tab Track dengan `person-simple-hike` (kode) dibandingkan glyph rute turunan merek (prototipe). Keduanya diuji dengan pendaki pemula: tanya arti ikon tanpa label, lalu uji ingatan beberapa minggu kemudian, sesuai anjuran NN/g.

### Gaps
Keputusan yang perlu persetujuan pemilik (R-021 dan kepemilikan `DESIGN.md`):
- kebijakan bobot (Regular/Bold/master kecil) yang mengubah "Phosphor berat regular";
- penambahan glyph kustom;
- perubahan ikon tab;
- adopsi ikon terpisah untuk POI peta.

Pertanyaan yang belum terjawab:
- Estimasi usaha glyph kustom belum ada. Perlu pilot 3 glyph yang diukur.
- Belum ada data pengenalan ikon dari pengguna Indonesia. Rekomendasi hibrida bertumpu pada bukti umum (NN/g, Wiedenbeck, Isherwood) dan pedoman platform, bukan pada data MEMOUNT.
- Apakah motif cincin pos dibaca "pos/checkpoint" oleh pemula belum diketahui. Kaitannya dengan istilah lokal "Pos 1…n" belum terdokumentasi dari sumber primer (§3).
