# Matriks Skill

Inventaris skill yang terpasang untuk proyek ini, klasifikasinya (CLAUDE.md bagian 7), cara
menggabungkannya, dan status pelacakannya di git. Diperbarui 26 September 2026 atas arahan
pemilik (spec v3 bagian 17.1): "install dan selalu terapkan skill berikut untuk menunjang
pengembangan app terutama ui/ux compare dan combine skill berikut [...] terapkan combine
dengan skill" lainnya seperti impactcable dll." ("impactcable" adalah `impeccable`.) Arahan
25 September 2026 tetap berlaku: "pastikan untuk selalu menerapkan semua skill disini compare
dan combine semua skill disini".

**Klasifikasi.** INTI: dipakai setiap kali area itu disentuh. PENDUKUNG: dipakai untuk
tugas tertentu di area itu. LINTAS: berlaku di semua area. TIDAK RELEVAN: disebut
alasannya, supaya tidak dijalankan demi formalitas.

## Urutan wewenang ketika skill bertentangan (DK-57)

1. **Arahan pemilik dan aturan kanonis**: keputusan dan kata-kata pemilik (log keputusan `docs/PRD.md`, bagian Arah `DESIGN.md`), aturan R-001 sampai R-024 (`CLAUDE.md`, `.claude/rules/mountain-core.md`, `DESIGN.md`), serta BR-01 sampai BR-16.
2. **`DESIGN.md`**: token dan turunannya (warna, tipografi DK-38, ikon DK-39, radius, gerak).
3. **Saringan antislop**: menyaring, tidak pernah menciptakan arah.
4. **Saran skill**: `ui-ux-pro-max`, `design-taste-frontend`, `impeccable`, `apple-design`, `emil-design-eng`, dan lainnya; diambil mekanismenya, bukan rupanya (prinsip ATM di PRD).

Tiga aturan penggabungan:

- Skill tidak menciptakan arah baru. Palet dan pasangan huruf dari basis data `ui-ux-pro-max` dipakai sebagai pembanding dan pemeriksa, bukan pengganti keputusan DK-38 atau token `resources/css/app.css`.
- Langkah "Persist Design System" `ui-ux-pro-max` ditulis ke `DESIGN.md`, bukan folder `design-system/` baru di root; `RepoHygieneTest` menolak entri root yang belum diputuskan.
- Skill pihak ketiga ditinjau sebelum dipasang atau diperbarui: hasil pemindai keamanan yang ditampilkan `npx skills add` (misalnya Gen, Socket, Snyk) dan isi berkas SKILL.md-nya dibaca lebih dulu; versi yang terpasang tercatat di `skills-lock.json`.

## Menggabungkan skill UI/UX dalam satu pekerjaan

| Langkah | Skill | Hasil yang diambil |
|---|---|---|
| Arah | `DESIGN.md`, `impeccable` (membaca `PRODUCT.md` dan `DESIGN.md` di root) | Batas visual dan nada yang tidak boleh dilanggar |
| Pembanding pola | `ui-ux-pro-max` | Pola UX, daftar periksa aksesibilitas, pembanding palet dan huruf |
| Bentuk, kritik, audit, poles | `impeccable`, `redesign-existing-projects`, `design-taste-frontend` (perombakan layar dan halaman publik) | Susunan layar dan kritik yang bersumber |
| Gerak dan rasa | `emil-design-eng`, `apple-design`, `animate` dan keluarganya, `mobile-native` | Keputusan gerak dan interaksi sentuh di dalam dial dan daftar tertutup `DESIGN.md` |
| Implementasi | `tailwindcss-development`, `laravel-best-practices`, `testing-best-practices` | Kelas Tailwind bertoken, kode server dan test yang benar |
| Saringan | `antislop` dengan `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | Delivery Gate PASS dengan bukti per butir |

Bila saran dua skill bertentangan, tulis keduanya beserta alasannya di rekap, lalu putuskan
dengan urutan wewenang di atas.

## Skill proyek (`.agents/skills`, `.claude/skills`)

| Skill | Kelas | Dipakai untuk | Catatan gabungan |
|---|---|---|---|
| `antislop` | LINTAS | Saringan akhir setiap perubahan UI, teks, dan komentar; Delivery Gate | Dimuat bersama skill desain mana pun; menang atas resep gaya |
| `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | LINTAS | Saringan per area: antarmuka, tata letak seluler, teks, kode, suara manusia | Dimuat bersama `antislop` |
| `impeccable` | INTI desain | Bentuk, kritik, audit, poles, adaptasi | Membaca `PRODUCT.md` dan `DESIGN.md` di root; penanda skema dipertahankan |
| `ui-ux-pro-max` | INTI desain (pembanding) | Pola UX, aksesibilitas, palet dan pasangan huruf sebagai pembanding | "Persist Design System" ditulis ke `DESIGN.md`, bukan folder `design-system/`; tidak mengganti DK-38 |
| `design-system` | PENDUKUNG | Memeriksa arsitektur token tiga lapis terhadap `resources/css/app.css` | Bagian pembuatan slide tidak dipakai |
| `ui-styling` | PENDUKUNG terbatas | Pola gaya Tailwind yang aksesibel | Resep shadcn/ui dan Radix butuh dependensi baru; tidak dipasang tanpa izin (DK-24) |
| `brand` | PENDUKUNG | Konsistensi suara dan identitas | Kontrak merek di `docs/PRD.md` §156 dan `DESIGN.md` menang |
| `design`, `banner-design`, `slides` | TIDAK RELEVAN saat ini | Logo lewat pembangkit gambar, banner iklan, presentasi | Aset logo final menunggu gerbang DK-16; dipakai bila pemilik meminta materi pemasaran |
| `redesign-existing-projects` | INTI desain | Merombak halaman yang ada tanpa merusak fungsi | Audit dulu, lalu ubah per halaman |
| `emil-design-eng` | INTI gerak | Filosofi detail dan gerak (Emil Kowalski) | Bersama `animate`, `improve-animations`, `review-animations` |
| `animate`, `improve-animations`, `find-animation-opportunities`, `review-animations`, `animation-vocabulary` | INTI gerak | Memutuskan, membangun, mengaudit, dan meninjau gerak | Setiap gerak harus menjelaskan; `prefers-reduced-motion` wajib |
| `apple-design` | PENDUKUNG | Gestur, pegas, seret, lembar bawah, material | Kaca paling banyak dua elemen per layar (rencana induk v3) |
| `mobile-native` | INTI seluler | Rasa terpasang di ponsel: area aman, sentuh, meta | PWA sudah ada; lengkapi, jangan ganti |
| `tailwindcss-development` | INTI implementasi | Kelas Tailwind 3.4 dan token | Tanpa `dark:` dan warna palet mentah; token dari `app.css` |
| `design-taste-frontend` | INTI desain (arahan pemilik 26 September 2026: selalu diterapkan dan digabung) | Perombakan layar yang ada dengan audit lebih dulu (cakupannya sendiri: "landing pages, portfolios, and redesigns"), halaman publik, dan pemeriksaan pra-kirim anti-generik | Menurut cakupannya bukan untuk dasbor, tabel data, dan alur bertahap; di layar itu yang diambil hanya pemeriksaan anti-generik dan kontras formulirnya. Rekomendasi pustakanya (Motion, GSAP, Tailwind v4) tidak dipakai (DK-24, R-020); huruf, warna, dan geraknya kalah oleh `DESIGN.md` |
| `design-taste-frontend-v1`, `high-end-visual-design`, `stitch-design-taste`, `minimalist-ui` | PENDUKUNG | Rujukan tata letak dan standar anti-generik tambahan | Font, bayangan, dan warna resepnya kalah oleh `DESIGN.md` |
| `gpt-taste` | TIDAK RELEVAN untuk layar aplikasi | Mewajibkan GSAP dan struktur AIDA pemasaran | Bertentangan dengan DK-24 dan mode kerja aplikasi |
| `industrial-brutalist-ui` | TIDAK RELEVAN | Estetika terminal militer | Tidak sejalan dengan merek dan konteks keselamatan |
| `prototype` | PENDUKUNG | Membandingkan beberapa versi layar kunci | Dipakai bila ada waktu untuk varian |
| `pick-ui-library`, `ask-sonner` | PENDUKUNG keputusan | Tabel pembanding pustaka | Pemasangan tetap butuh izin pemilik |
| `brandkit`, `imagegen-frontend-mobile`, `imagegen-frontend-web`, `image-to-code` | TIDAK RELEVAN saat ini | Butuh pembangkit gambar | Dipakai bila pemilik meminta papan identitas |
| `laravel-best-practices` | INTI backend | Controller, model, migrasi, request, policy | Bersama `testing-best-practices` |
| `testing-best-practices` | INTI test | Cakupan, nama, struktur, isolasi | Test bukan bukti PostgreSQL (R-024) |
| `infer-conventions` | PENDUKUNG | Merekam konvensi ke `.ai/rules` | Hanya bila pemilik meminta aturan dicatat |
| `ngodingpakeai` | LINTAS (memori proyek) | Sinkron ringkasan kode dan tugas | Jangan `plan get` di root (menulis berkas PRD versi server dan `features/`) |
| `full-output-enforcement` | LINTAS | Kode lengkap, tanpa potongan dan placeholder | |
| `livewire-development`, `volt-development` | TIDAK RELEVAN | Livewire sudah dipensiunkan (jalur C) | Paket masih terpasang sampai pemilik memutuskan (P11) |
| `deploying-to-cloud` | TIDAK RELEVAN | Laravel Cloud | Sasaran deploy adalah Vercel (DK-12) |
| `animate-expo`, `write-swift` | TIDAK RELEVAN | React Native dan Swift | Tidak ada aplikasi native |

## Status pelacakan di git (Q19, menunggu keputusan pemilik)

Keadaan 26 September 2026 menurut `git ls-files`:

- Dilacak, milik proyek: `antislop` dan lima turunannya di `.claude/skills/`.
- Dilacak, salinan pihak ketiga di `.agents/skills/`: 26 skill yang tercatat di `skills-lock.json` ditambah `impeccable` (dipasang lewat `npx impeccable install`; biner mesinnya diabaikan git).
- Belum dilacak: tujuh skill dari `uipro init` (`ui-ux-pro-max`, `design-system`, `ui-styling`, `brand`, `design`, `banner-design`, `slides`) dan tujuh skill Laravel Boost (`laravel-best-practices`, `testing-best-practices`, `tailwindcss-development`, `livewire-development`, `volt-development`, `deploying-to-cloud`, `infer-conventions`).
- Diabaikan git: salinan di `.claude/skills/` selain keluarga antislop, termasuk `ngodingpakeai` yang dibangkitkan CLI-nya. Salinan `impeccable` di `.github/skills/` juga diabaikan git.

Usulan Q19 (spec v3 bagian 15): skill milik proyek dan `skills-lock.json` dilacak; salinan
pihak ketiga dipulihkan lewat `npx skills experimental_install`. Sampai pemilik memutuskan,
folder yang belum dilacak tidak di-stage.

## Skill proses (superpowers)

Skill di bagian ini dan dua bagian sesudahnya adalah plugin Claude Code yang dipasang per pengguna (superpowers, ECC,
claude-mem, dan skill pengguna), bukan isi repo, sehingga tidak ada di `.agents/skills` atau `.claude/skills`. Bila
plugin itu tidak ada, prosedur yang ditulis di `docs/AGENTS.md` tetap berlaku.

| Skill | Kelas | Dipakai untuk |
|---|---|---|
| brainstorming | INTI (awal) | Mengubah arahan menjadi spec |
| writing-plans, executing-plans | INTI | Rencana implementasi bertahap dan pelaksanaannya |
| subagent-driven-development, dispatching-parallel-agents | INTI | Pembagian kerja ke subagen dengan batas berkas yang jelas |
| test-driven-development | INTI | Test merah dulu, lalu implementasi |
| systematic-debugging | PENDUKUNG | Setiap cacat: bukti, hipotesis, uji |
| verification-before-completion | LINTAS | Tidak ada klaim selesai tanpa bukti (R-023) |
| requesting-code-review, receiving-code-review | PENDUKUNG | Tinjauan sebelum diserahkan ke pemilik |
| finishing-a-development-branch | PENDUKUNG | Menutup branch setelah pemilik menyetujui commit |
| using-git-worktrees, writing-skills | TIDAK RELEVAN saat ini | Subagen berbagi satu tree dengan batas berkas; tidak menulis skill baru |

## Skill plugin ECC yang relevan

| Area | Skill | Kelas |
|---|---|---|
| Riset | deep-research, search-first, research-ops, market-research, competitive-platform-analysis, competitive-report-structure | INTI untuk riset |
| Produk | product-lens, product-capability | PENDUKUNG |
| Desain dan gerak | frontend-design-direction, design-system (plugin), liquid-glass-design, make-interfaces-feel-better, motion-foundations, motion-patterns, motion-advanced, dashboard-builder | INTI atau PENDUKUNG sesuai layar |
| Aksesibilitas | accessibility, frontend-a11y | INTI |
| Frontend | react-patterns, react-performance, react-testing, vite-patterns, frontend-patterns | INTI implementasi |
| Backend dan data | laravel-patterns, laravel-security, laravel-tdd, laravel-verification, postgres-patterns, database-migrations | INTI backend |
| Mutu dan rilis | security-review, security-scan, verification-loop, delivery-gate, production-audit, browser-qa, click-path-audit, e2e-testing | PENDUKUNG |
| Dokumen dan repo | codebase-onboarding, living-docs-governance, architecture-decision-records, repo-scan, workspace-surface-audit | PENDUKUNG |
| Lain-lain (sekitar 250 skill: domain kesehatan, keuangan, jaringan, bahasa lain, video) | TIDAK RELEVAN | Di luar domain produk |

## Skill pengguna dan claude-mem

| Skill | Kelas |
|---|---|
| investigate-first, safe-refactor, surgical-patch, verify-and-stop, lean-build, migration | PENDUKUNG (prinsip kerja yang sejalan dengan CLAUDE.md) |
| browser-use | PENDUKUNG (bukti peramban; alternatifnya skrip CDP di scratchpad) |
| caveman dan turunannya | TIDAK RELEVAN (gaya jawaban ringkas bertentangan dengan rekap berbahasa Indonesia yang diminta pemilik) |
| mem-search, smart-explore, make-plan, learn-codebase (claude-mem) | LINTAS (mengingat sesi sebelumnya) |
