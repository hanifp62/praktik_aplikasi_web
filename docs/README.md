# Dokumen Tim Memount

Indeks semua dokumen tim. Urutan baca untuk anggota baru, manusia maupun agen, ada di
[kesepakatan kerja tim](AGENTS.md), bagian "Cara memakai dokumen ini". Setiap jenis isi punya
satu pemilik; peta kepemilikannya ada di awal [PRD](PRD.md). Dokumen bertanggal (spec,
rencana, riset, audit, arsip) adalah catatan pada tanggalnya: jalur dan angka di dalamnya
benar pada hari itu dan tidak ditulis ulang.

## Mulai di sini

| Dokumen | Isi |
|---|---|
| [AGENTS.md](AGENTS.md) | Kesepakatan kerja tim: visi dan ekspektasi pemilik, izin, alur riset lebih dulu, skill, tingkat bukti, cara audit, definisi selesai, format rekap |
| [PRD.md](PRD.md) | Sumber kebenaran produk: visi, misi, aturan bisnis BR, persyaratan FR, arsitektur informasi, tata kelola dan log keputusan |
| [DESIGN.md](DESIGN.md) | Penunjuk ke `DESIGN.md` di root |
| [PRODUCT.md](PRODUCT.md) | Penunjuk ke `PRODUCT.md` di root |
| [../README.md](../README.md) | Pintu masuk repo: stack, cara menjalankan, perintah harian |
| [../CONTRIBUTING.md](../CONTRIBUTING.md) | Cara mengubah kode tanpa merusak yang lain; aturan yang ditegakkan test |
| [../SECURITY.md](../SECURITY.md) | Pelaporan kerentanan dan kebijakan rahasia |
| [../HANDOVER.md](../HANDOVER.md) | Keadaan terkini dan titik lanjut antarsesi |

## Aturan

| Dokumen | Isi |
|---|---|
| [../CLAUDE.md](../CLAUDE.md) | Kontrak operasi: R-018 sampai R-021, R-023, R-024 |
| [../AGENTS.md](../AGENTS.md) | Pintu masuk agen selain Claude: blok Laravel Boost dan penunjuk ke kesepakatan kerja |
| [../.claude/rules/mountain-core.md](../.claude/rules/mountain-core.md) | Aturan domain R-001 sampai R-014 dan R-022 |
| [../DESIGN.md](../DESIGN.md) | Arah desain pemilik, turunannya, R-015 sampai R-017 |
| [../PRODUCT.md](../PRODUCT.md) | Ringkasan produk untuk alat desain (bukan sumber kebenaran) |
| [KEBIJAKAN-DATA-PRIBADI.md](KEBIJAKAN-DATA-PRIBADI.md) | Kebijakan data pribadi dan penghapusan akun |

## Teknik

| Dokumen | Isi |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Peta kode, aturan yang tidak boleh dilanggar, struktur repo, kontrak SEO teknis, catatan operasional |
| [../database/README.md](../database/README.md) | Skema per domain, PostGIS, kebijakan migrasi |
| [MATRIKS-SKILL.md](MATRIKS-SKILL.md) | Skill terpasang, kelasnya, urutan wewenang, status pelacakan |
| [lisensi-pihak-ketiga.md](lisensi-pihak-ketiga.md) | Karya pihak ketiga dan lisensinya |
| [../.github/PULL_REQUEST_TEMPLATE.md](../.github/PULL_REQUEST_TEMPLATE.md) | Daftar periksa pull request |

## Mutu dan audit

| Dokumen | Isi |
|---|---|
| [AUDIT-PROTOCOL.md](AUDIT-PROTOCOL.md) | Protokol audit kelengkapan A sampai N, standar bukti, skor |
| [PROTOKOL-UJI-KEGUNAAN.md](PROTOKOL-UJI-KEGUNAAN.md) | Evaluasi heuristik, uji tugas, SUS, pembaca layar |
| [audit/](audit/) | Laporan audit bertanggal, termasuk audit antislop |

## Fitur, desain, dan riset

| Dokumen | Isi |
|---|---|
| [fitur/](fitur/) | Delapan spesifikasi fitur beserta kriteria penerimaan |
| [desain/prototype/](desain/prototype/) | Prototype pemilik 25 September 2026 dan 16 gambar acuan |
| [desain/tangkapan/](desain/tangkapan/) | Tangkapan pratinjau per gelombang dan perbandingannya dengan gambar acuan prototype |
| [riset/](riset/) | Riset asal keputusan, bertanggal |
| [superpowers/specs/](superpowers/specs/) | Spec desain per gelombang |
| [superpowers/plans/](superpowers/plans/) | Rencana implementasi per gelombang |

## Arsip

| Dokumen | Isi |
|---|---|
| [arsip/](arsip/) | Dokumen yang tidak lagi hidup, disimpan utuh sebagai bukti asal |
| [db/](db/) | Snapshot skema Supabase 19 September 2026 sebelum migrasi hardening (tanpa data) |
