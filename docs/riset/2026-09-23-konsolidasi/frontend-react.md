# Memadukan React ke Frontend: Opsi, Bukti, dan Rekomendasi

> **Status:** RISET dan USULAN KEPUTUSAN, 24 September 2026. Arahan pemilik (verbatim): *"frontend combine dengan
> react. jadi base nya tetap laravel namun, untuk menunjang ui/ux nya combine dengan react."* Ini perubahan arsitektur
> frontend (R-021, CLAUDE.md §3), jadi berkas ini menyatakan aturan sekarang, alasan, bukti, alternatif, dampak,
> risiko, rekomendasi, dan apa yang butuh persetujuan. Tidak ada dependensi yang dipasang sebelum pemilik memilih.
> **Label bukti (R-023):** [FAKTA], [INFERENSI], [REKOMENDASI]; UNVERIFIED = dari cuplikan atau artikel pihak ketiga.

## 1. Aturan dan keadaan sekarang

- [FAKTA, `composer.lock` dan `package-lock.json`, 24 Sep 2026] Laravel 13.31.0, Livewire 3.8.9, Volt 1.11.2, Breeze
  2.4.2, Vite 8.3.0, Tailwind 3.4.19, laravel-vite-plugin 3.2.0. React, Inertia, dan `@vitejs/plugin-react` **tidak**
  terpasang (`npm ls react` kosong). `vite.config.js` hanya memuat plugin Laravel dengan dua input (`app.css`,
  `app.js`).
- [FAKTA] Permukaan yang ada: 37 kelas Livewire di `app/Livewire`, 46 view Livewire, 972 test Unit+Feature yang
  sebagian besar menguji HTML hasil render Livewire, plus test keamanan (matriks akses rute, IDOR, RLS), aksesibilitas
  (target sentuh, keyboard, kontras), dan anggaran query.
- [FAKTA] DK-24 (disetujui pemilik 23 Sep 2026): "Nol dependensi runtime baru di fase 1". Arahan React 24 Sep 2026
  mengubah ini dan perlu governance record (PRD §146).
- [FAKTA] Halaman publik (`/pendakian/{slug}`, sitemap) dirender Blade di server untuk mesin pencari
  (`docs/ARCHITECTURE.md`, kontrak SEO).

## 2. Lanskap 2026 (bukti eksternal)

- [FAKTA] Laravel 13 starter kit React resmi memakai **Inertia 3, React 19, Tailwind 4, shadcn/ui** —
  [laravel.com/docs/13.x/starter-kits](https://laravel.com/docs/13.x/starter-kits) (cuplikan).
- [FAKTA] Inertia 3 stabil sejak Maret 2026: tanpa Axios, `createInertiaApp()` tanpa argumen, **SSR tanpa proses Node
  terpisah**, hook `useHttp`, optimistic update — [dev.to, 3 April 2026](https://dev.to/hafiz619/livewire-4-vs-inertiajs-3-which-laravel-frontend-stack-should-you-use-in-2026-47p4)
  (UNVERIFIED terhadap changelog resmi).
- [FAKTA] Livewire 4 rilis Januari 2026 dengan islands, komponen berkas tunggal, dan perubahan perilaku `wire:model`
  (breaking) — sumber sama. Proyek ini masih Livewire 3.8.9; naik versi adalah keputusan terpisah (R-020).
- [FAKTA] `@vitejs/plugin-react` v6 (Oxc, tanpa Babel) kompatibel dengan Vite 8; v5 juga masih jalan —
  [vite.dev/blog/announcing-vite8](https://vite.dev/blog/announcing-vite8), [npm](https://www.npmjs.com/package/@vitejs/plugin-react).
- [FAKTA] Setup server Inertia: `composer require inertiajs/inertia-laravel`, `php artisan inertia:middleware`,
  root template dengan `<x-inertia::head />` dan `<x-inertia::app />` — [inertiajs.com/server-side-setup](https://inertiajs.com/server-side-setup).
- [FAKTA] Spatie (8 orang, 1 frontend penuh waktu) memilih **per proyek**, bukan menjalankan Livewire dan Inertia
  berdampingan di satu aplikasi: Livewire untuk aplikasi padat form dan data dengan tim PHP; Inertia+React untuk
  sistem desain besar, TypeScript ujung-ke-ujung, dan widget kustom yang banyak — [spatie.be, 5 Mei 2025](https://spatie.be/blog/livewire-and-inertia-how-we-love-and-use-both).

## 3. Tiga cara memadukan React

| | A. Pulau React di halaman Blade/Livewire | B. Inertia 3 + React untuk permukaan pendaki, Livewire tetap untuk admin/auth | C. Migrasi penuh ke Inertia + React |
|---|---|---|---|
| Apa yang berubah | `@vitejs/plugin-react`, `react`, `react-dom`; satu runtime pemasang `data-react="Nama"` yang membaca props JSON; komponen React untuk permukaan interaktif tertentu | Tambah Inertia (server + klien), root template Inertia, layout dan navigasi ditulis ulang di React, halaman pendaki dipindah satu per satu | Semua 37 komponen Livewire ditulis ulang |
| Yang tetap utuh | Routing, auth, otorisasi, 972 test, SEO halaman publik, mode gelap dan token | Auth Breeze-Livewire, admin, halaman publik Blade; test untuk halaman yang belum pindah | Hanya backend |
| Di mana React menunjang UI/UX | Peta dan mode pendakian (MapLibre di React), banding jalur (tabel interaktif, sorting, sinkron), profil elevasi, daftar persiapan (urut-seret), pemilih tanggal/cuaca | Seluruh lima permukaan pendaki: Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar, dengan state klien lintas halaman | Semua |
| Biaya bundel | +~45 kB gzip React di halaman yang memakai pulau; Livewire+Alpine tetap ada | Dua runtime hidup berdampingan selama migrasi (Livewire+Alpine dan Inertia+React), dua layout, dua navigasi yang harus identik | Satu runtime pada akhirnya |
| Risiko utama | Dua sumber state di satu halaman (Livewire dan React) bila kontraknya tidak tegas | Duplikasi layout/nav dan drift antar keduanya; test HTML Livewire harus ditulis ulang menjadi `assertInertia`; SSR perlu keputusan bila halaman publik ikut pindah | Bulan kerja; 972 test hilang nilainya; regresi keamanan yang sudah ditutup (6 cacat produksi) bisa terbuka lagi |
| Cocok dengan "base tetap Laravel" | Ya, paling harfiah | Ya, Inertia memang "monolit modern" | Ya, tetapi seluruh view berpindah |
| Reversibel | Ya, per pulau | Sebagian, per halaman | Tidak praktis |

## 4. Rekomendasi [REKOMENDASI]

**Jalankan A sekarang, dan putuskan B setelah tiga pulau pertama hidup.** Alasannya:

1. Nilai UI/UX yang pemilik cari paling terasa di permukaan interaktif (peta, banding, persiapan), dan di sanalah
   Livewire paling lemah karena setiap interaksi bolak-balik ke server. Pulau React menyelesaikan persis itu tanpa
   menyentuh routing, otorisasi, dan 972 test yang sudah membayar dirinya.
2. A memberi kontrak yang tegas: **Livewire memegang data dan otorisasi, React memegang interaksi.** Props masuk
   sebagai JSON dari server; React tidak pernah memanggil API tanpa CSRF dan Policy yang sama. Ini menjaga
   "secure by design" dan "proper architecture" (HANDOVER §3 butir 9).
3. B tetap terbuka. Bila setelah tiga pulau kebutuhan state lintas halaman (misalnya baki pertimbangan yang hidup di
   semua permukaan) terbukti menuntut SPA, Inertia 3 ditambahkan untuk permukaan pendaki dengan bukti, bukan
   dugaan.
4. C ditolak: bertentangan dengan "base tetap Laravel" dalam praktik, dan membakar investasi test dan keamanan.

**Urutan pulau yang diusulkan** (masing-masing satu tugas, satu test, satu tinjauan):
1. **Peta mode pendakian** (`ui/map.blade.php` → komponen React `PetaJalur`): MapLibre sudah dimuat malas, jadi
   ini pulau termahal secara interaksi dan termurah secara risiko.
2. **Banding jalur** (`route-comparison`): tabel yang bisa diurutkan dan disorot per baris tanpa bolak-balik server;
   R-016 (tanpa pemenang) dijaga di komponen.
3. **Profil elevasi** interaktif dengan penunjuk pos.

**Yang butuh persetujuan pemilik sebelum satu baris pun dipasang:**
- tiga dependensi baru: `react`, `react-dom`, `@vitejs/plugin-react` (mengubah DK-24; governance record di PRD §146);
- TypeScript atau JavaScript untuk komponen React (rekomendasi: TypeScript, karena kontrak props dari server adalah
  tempat kesalahan paling mungkin);
- Tailwind tetap 3.4.19 (DK-24) atau naik ke 4 mengikuti starter kit resmi (keputusan terpisah, risiko regresi
  visual);
- Livewire tetap 3.8.9 atau naik ke 4 (R-020; islands Livewire 4 adalah alternatif non-React untuk sebagian kasus A).

## 5. Dampak pada dokumen kanonik bila A disetujui
- `docs/ARCHITECTURE.md`: lapisan baru "pulau React" dengan kontrak props dan larangan (tanpa fetch langsung ke
  model, tanpa state otorisasi di klien).
- `PRD.md` §146: governance record yang mengubah DK-24.
- `DESIGN.md`: token tetap satu-satunya sumber warna; komponen React memakai kelas Tailwind yang sama, tanpa CSS-in-JS.
- `HANDOVER.md` §3: kontrak Livewire-data/React-interaksi sebagai aturan.
