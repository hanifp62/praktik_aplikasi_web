import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Warna merek dibaca dari design token di resources/css/app.css.
 * Untuk mengubah palet aplikasi, sunting token di sana — jangan menambah
 * warna mentah di sini atau di view.
 */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            /*
             * Tiga suara, bukan satu.
             *
             * Diukur sebelum diubah: 363 dari 426 pemakaian ukuran teks adalah text-sm
             * atau text-xs, dan fontnya Figtree bawaan scaffolding. Satu huruf generik
             * pada satu ukuran kecil membuat setiap layar terbaca seperti sel tabel,
             * berapa pun kecerdasan yang ada di belakangnya.
             *
             * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
             * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia
             * untuk produk tentang gunung-gunung Indonesia; alasan itu bertahan ketika
             * seleranya berubah, sedangkan "sedang populer" tidak.
             *
             * Newsreader dipakai untuk judul halaman. Serif memberi bobot editorial yang
             * tidak dapat dicapai ketebalan huruf sans, dan membedakan judul dari
             * antarmuka tanpa membesarkannya sampai berteriak.
             *
             * Huruf mono memakai tumpukan sistem, tanpa unduhan.
             *
             * Komentar sebelumnya di sini menyebut JetBrains Mono "khusus pengukuran:
             * jarak, elevation gain, durasi, dan koordinat". Ketika akhirnya diukur,
             * tidak satu pun pengukuran memakainya: semuanya memakai tabular-nums pada
             * huruf sans, dan font-mono muncul tepat dua kali, keduanya pada kunci tugas
             * penjadwal di satu halaman admin.
             *
             * Jadi seluruh pengguna mengunduh dua bobot huruf untuk dua baris yang tidak
             * pernah mereka lihat. Pertanyaan R-06 "mengapa huruf ini" ternyata punya
             * jawaban yang lebih sederhana daripada mencari penggantinya: tidak perlu
             * huruf mono sama sekali. Kunci penjadwal seperti weather:refresh justru
             * persis yang pantas memakai huruf mono bawaan sistem.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', ...defaultTheme.fontFamily.serif],
                mono: defaultTheme.fontFamily.mono,
            },

            colors: {
                // Batas kontrol interaktif, dipisahkan dari abu dekoratif karena
                // WCAG 1.4.11 hanya mengatur yang interaktif.
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
                },
                subtle: token('border-subtle'),
                primary: token('text-primary'),
                secondary: token('text-secondary'),
                muted: token('text-muted'),

                brand: {
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
            },


            /*
             * Satu elevasi, untuk lapisan yang benar-benar melayang di atas halaman.
             *
             * Tailwind menyediakan lima tingkat dan setiap pemakaian memilih sendiri;
             * hasilnya tiga tingkat berbeda untuk tiga lapisan yang perannya sama.
             * Bayangannya berwarna netral hangat, bukan hitam murni: bayangan hitam di
             * atas kanvas hangat terbaca kelabu dan bukan seperti bayangan.
             */
            boxShadow: {
                overlay: '0 12px 32px -8px rgb(28 25 23 / 0.18)',
            },
            borderRadius: {
                control: 'var(--radius-control)',
            },
        },
    },

    plugins: [forms],
};
