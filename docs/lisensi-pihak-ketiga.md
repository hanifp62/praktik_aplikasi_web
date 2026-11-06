# Lisensi Pihak Ketiga

Karya pihak ketiga yang ikut dalam aplikasi atau repo, lisensinya, dan tempat teks
lisensinya. Diperbarui setiap kali dependensi runtime, huruf, ikon, data, atau aset
ditambah; penambahan dependensi sendiri butuh izin pemilik (DK-24). Lisensi paket diambil
dari metadata paket yang terpasang (`composer licenses --no-dev` dan bidang `license` di
package.json setiap paket), bukan dari ingatan. `ThirdPartyLicenseTest` memastikan setiap
dependensi runtime tercatat di sini.

## Aset yang ditanam di kode

| Karya | Dipakai di | Lisensi | Teks lisensi |
|---|---|---|---|
| Phosphor Icons (path bobot regular, bold, dan fill; repositori phosphor-icons/core 2.1.1) | `resources/js/components/ui/ikon/phosphor.ts` (registri), `resources/js/components/ui/Icon.tsx`, `resources/views/components/ui/icon.blade.php`; pemberitahuannya ikut ke bundel produksi lewat `build.rolldownOptions.output.comments` di `vite.config.js` | MIT, Copyright (c) 2023 Phosphor Icons | `resources/js/components/ui/LICENSE-phosphor.txt` |

## Prototype pemilik

`docs/desain/prototype/memount-prototype.html` adalah bundel prototype pemilik; aplikasi tidak memuatnya. Menurut
`docs/desain/prototype/README.md` bagian "Catatan hak":

| Karya | Lisensi | Catatan |
|---|---|---|
| Huruf Geist, Geist Mono, dan Newsreader yang ditanam di bundel | SIL Open Font License 1.1 | Teks OFL belum ikut di repo; wajib ikut bila prototype dipublikasikan (keputusan Q21) |
| Foto JPG di bundel | Belum tercatat | Sumber dan hak pakainya belum tercatat; keputusan Q21 |

## Huruf

| Karya | Dipakai di | Lisensi | Catatan |
|---|---|---|---|
| Plus Jakarta Sans, variabel (bobot 200 sampai 800), subset Latin dan Latin Extended | Dihosting sendiri di `public/fonts/plus-jakarta-sans/`, dimuat `@font-face` di `resources/css/app.css`, subset Latin dipramuat `resources/views/components/brand/head.blade.php` | SIL Open Font License 1.1, Copyright 2020 The Plus Jakarta Sans Project Authors | Teks lisensi `public/fonts/plus-jakarta-sans/OFL.txt`. Asal: paket npm `@fontsource-variable/plus-jakarta-sans` 5.3.0 lewat cdn.jsdelivr.net, dibangun Fontsource dari google/fonts (desain Tokotype), diunduh 26 September 2026; ukuran dan SHA-256 kedua berkas dipaku `TypographySystemTest`. Bukan rilis GitHub tokotype/PlusJakartaSans 2.7.1 karena rilis itu hanya berisi TTF (variabel tegak 182.180 bita) tanpa woff2, dan rencana W1 melarang perkakas huruf. |

Newsreader dan Bunny Fonts tidak dipakai lagi sejak gelombang 1 v3 (DK-38): judul memakai Plus Jakarta Sans bobot 800, dan
tidak ada huruf yang dimuat dari layanan luar.

## Data dan layanan

| Sumber | Dipakai untuk | Ketentuan | Tempat atribusi |
|---|---|---|---|
| OpenStreetMap (data di balik ubin peta) | Peta dasar | ODbL 1.0; atribusi "© OpenStreetMap contributors" wajib tampil | `config/hiking.php` kunci `map.attribution`, ditampilkan `WadahPeta` |
| OpenTopoMap (ubin peta bawaan) | Peta dasar online | CC-BY-SA menurut atribusi bawaan di `config/hiking.php`; pemakaian luring butuh izin tertulis (spec v3 Q8) | Sama |
| BMKG (prakiraan cuaca) | Prakiraan area sekitar jalur | Atribusi BMKG wajib tampil (`docs/ARCHITECTURE.md`, "Sumber data eksternal") | Halaman yang menampilkan prakiraan |

## Dependensi runtime PHP (`composer.json`, bagian require)

| Paket | Versi terpasang 26 September 2026 | Lisensi |
|---|---|---|
| `inertiajs/inertia-laravel` | 3.3.4 | MIT |
| `laravel/framework` | 13.31.0 | MIT |
| `laravel/tinker` | 3.0.2 | MIT |
| `laravel/wayfinder` | 0.1.21 | MIT |
| `league/flysystem-aws-s3-v3` | 3.35.3 | MIT |
| `livewire/livewire` | 3.8.9 | MIT; antarmuka Livewire sudah dipensiunkan, paketnya menunggu keputusan pemilik (P11) |
| `livewire/volt` | 1.11.2 | MIT; sama |

Daftar transitif lengkap: `composer licenses --no-dev`.

## Dependensi runtime JavaScript (`package.json`, bagian dependencies)

| Paket | Versi terpasang 26 September 2026 | Lisensi |
|---|---|---|
| `@inertiajs/react` | 3.7.1 | MIT |
| `@inertiajs/vite` | 3.7.1 | MIT |
| `framer-motion` | 14.0.0 (dipasang 6 Oktober 2026 atas arahan pemilik; belum diimpor kode mana pun, jadi belum masuk bundel) | MIT, Copyright (c) 2018 Framer B.V. |
| `maplibre-gl` | 6.14.0 | BSD-3-Clause |
| `react` | 19.3.0 | MIT |
| `react-dom` | 19.3.0 | MIT |

Lisensi paket transitif ada di bidang `license` pada package.json masing-masing paket di
`node_modules/`.
