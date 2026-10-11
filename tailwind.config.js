import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Warna merek dibaca dari design token di resources/css/app.css.
 * Untuk mengubah palet aplikasi, sunting token di sana, jangan menambah
 * warna mentah di sini atau di view.
 */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    // Halaman React (resources/js) wajib ikut dipindai. Tanpa baris itu, setiap kelas
    // yang hanya dipakai di komponen .tsx tidak pernah dibuat, dan kegagalannya diam:
    // navigasi bawah kehilangan inset-x-0 dan melebar melewati layar, tanpa galat
    // apa pun di build maupun di peramban (ditemukan lewat pengukuran 25 Sep 2026).
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
    ],

    theme: {
        extend: {
            /*
             * Satu keluarga huruf, dihosting sendiri (spec v3 bagian 8.2, DK-38).
             *
             * Diukur sebelum diubah: 363 dari 426 pemakaian ukuran teks adalah text-sm
             * atau text-xs, dan fontnya Figtree bawaan scaffolding. Satu huruf generik
             * pada satu ukuran kecil membuat setiap layar terbaca seperti sel tabel,
             * berapa pun kecerdasan yang ada di belakangnya.
             *
             * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
             * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia
             * untuk produk tentang gunung-gunung Indonesia; alasan itu bertahan ketika
             * seleranya berubah, sedangkan "sedang populer" tidak. Sejak gelombang 1 v3
             * ia juga membawa judul pada bobot 800: serif judul (Newsreader) dan Bunny
             * Fonts dilepas, sehingga seluruh huruf datang dari asal aplikasi sendiri.
             *
             * Dua nama berikutnya adalah Roboto dan Arial yang metriknya disamakan di
             * app.css, supaya tata letak tidak melompat ketika huruf tiba.
             *
             * Huruf mono memakai tumpukan sistem, tanpa unduhan: font-mono hanya dipakai
             * halaman Kesehatan admin untuk nama migrasi dan kunci tugas penjadwal.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'PJS Cadangan Roboto', 'PJS Cadangan Arial', ...defaultTheme.fontFamily.sans],
                mono: defaultTheme.fontFamily.mono,
            },

            /*
             * Skala tipe bernama (spec v3 bagian 8.2). Nama menyebut peran, bukan ukuran:
             * text-judul untuk judul halaman, text-isi untuk badan teks. Lantainya 12 px
             * (text-label); tidak ada teks informatif di bawahnya (TypeFloorTest).
             */
            fontSize: {
                tampilan: ['1.875rem', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '800' }],
                judul: ['1.75rem', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '800' }],
                'judul-kartu': ['1.4375rem', { lineHeight: '1.25', letterSpacing: '-0.015em', fontWeight: '800' }],
                bagian: ['1.125rem', { lineHeight: '1.35', fontWeight: '700' }],
                subjudul: ['1.0625rem', { lineHeight: '1.4', fontWeight: '700' }],
                isi: ['1rem', { lineHeight: '1.5' }],
                kecil: ['0.875rem', { lineHeight: '1.45' }],
                meta: ['0.8125rem', { lineHeight: '1.4' }],
                label: ['0.75rem', { lineHeight: '1.35' }],
            },

            colors: {
                // Batas kontrol interaktif, dipisahkan dari abu dekoratif karena
                // WCAG 1.4.11 hanya mengatur yang interaktif. Juga isian sakelar mati.
                control: token('control-border'),

                /*
                 * Lapisan semantik. Namanya menyebut peran, bukan warna: text-primary
                 * bukan text-gray-900. View tidak lagi memilih abu sendiri-sendiri, dan
                 * mengubah rasa seluruh aplikasi cukup menyentuh nilainya di app.css.
                 */
                canvas: token('canvas'),
                surface: {
                    DEFAULT: token('surface'),
                    sunken: token('surface-sunken'),
                    // Permukaan kebalikan: toast urungkan.
                    inverse: token('surface-inverse'),
                },
                subtle: token('border-subtle'),
                primary: token('text-primary'),
                secondary: token('text-secondary'),
                muted: token('text-muted'),
                inverse: token('text-inverse'),

                // Teks di atas isian merek (tombol primer, langkah selesai): putih di
                // tema terang, gelap di tema gelap (spec v3 bagian 8.1).
                'on-brand': token('on-brand'),

                brand: {
                    // Hijau sebagai latar depan (tautan, penekanan, garis grafik).
                    // Terpisah dari 700 yang menjadi isian tombol, karena keduanya
                    // berpisah jalan di tema gelap; alasannya di app.css.
                    ink: token('brand-ink'),
                    50: token('brand-50'),
                    100: token('brand-100'),
                    200: token('brand-200'),
                    300: token('brand-300'),
                    500: token('brand-500'),
                    600: token('brand-600'),
                    700: token('brand-700'),
                    800: token('brand-800'),
                    900: token('brand-900'),
                },
                community: {
                    50: token('community-50'),
                    500: token('community-500'),
                    900: token('community-900'),
                },
                warn: {
                    50: token('warn-50'),
                    100: token('warn-100'),
                    300: token('warn-300'),
                    600: token('warn-600'),
                    900: token('warn-900'),
                },
                danger: {
                    50: token('danger-50'),
                    100: token('danger-100'),
                    300: token('danger-300'),
                    600: token('danger-600'),
                    700: token('danger-700'),
                    900: token('danger-900'),
                },

                // Tiga label kecocokan publik (R-004) dan keadaan Belum dinilai (R-006):
                // latar dan teksnya dihitung berpasangan di ColourContrastTest.
                fit: {
                    cocok: { DEFAULT: token('fit-cocok'), teks: token('fit-cocok-teks') },
                    perlu: { DEFAULT: token('fit-perlu'), teks: token('fit-perlu-teks') },
                    kurang: { DEFAULT: token('fit-kurang'), teks: token('fit-kurang-teks') },
                    belum: { DEFAULT: token('fit-belum'), teks: token('fit-belum-teks') },
                },

                // Status akses (DK-32): penanda non-teks, selalu bersama bentuk dan kata.
                status: {
                    buka: token('status-buka'),
                    dibatasi: token('status-dibatasi'),
                    tutup: token('status-tutup'),
                    belum: token('status-belum'),
                },

                // Satu aksen: kuning pos. Tidak pernah membawa arti sendirian.
                // teks adalah tinta nomor pos DI ATAS aksen ini (bukan tinta badan teks
                // biasa): dipaku gelap di semua tema karena aksen kuningnya sendiri tidak
                // berubah antar tema (lihat app.css, token accent-pos-ink).
                aksen: {
                    pos: { DEFAULT: token('accent-pos'), teks: token('accent-pos-ink') },
                },

                logo: {
                    mark: token('logo-mark'),
                    type: token('logo-type'),
                },
            },

            /*
             * Satu elevasi, untuk lapisan yang benar-benar melayang di atas halaman:
             * bilah tab, bar aksi, lembar bawah, dialog, toast, kartu pratinjau peta.
             * Warnanya token bayangan (tinta hangat), bukan hitam murni: bayangan hitam di
             * atas kanvas hangat terbaca kelabu dan bukan seperti bayangan.
             */
            boxShadow: {
                overlay: '0 12px 32px -8px rgb(var(--bayangan) / 0.18)',
            },

            // Skala radius spec v3 bagian 8.4. rounded-lg adalah peran kartu di DESIGN.md,
            // jadi ia membaca token kartu dan 34 kartu yang ada ikut tanpa disunting.
            borderRadius: {
                kecil: 'var(--radius-kecil)',
                control: 'var(--radius-control)',
                tombol: 'var(--radius-tombol)',
                lg: 'var(--radius-kartu)',
                kartu: 'var(--radius-kartu)',
                utama: 'var(--radius-utama)',
                lembar: 'var(--radius-lembar)',
                lencana: 'var(--radius-lencana)',
            },

            // Token gerak dari app.css (DK-26): durasi dan kurva pegas yang dihitung.
            transitionDuration: {
                sentuh: 'var(--dur-sentuh)',
                cepat: 'var(--dur-cepat)',
                sedang: 'var(--dur-sedang)',
                lambat: 'var(--dur-lambat)',
            },
            transitionTimingFunction: {
                gerak: 'var(--ease-gerak)',
                pudar: 'var(--ease-pudar)',
                lentur: 'var(--ease-lentur)',
            },
        },
    },

    plugins: [forms],
};
