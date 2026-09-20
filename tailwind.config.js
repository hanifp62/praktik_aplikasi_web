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
             * JetBrains Mono khusus pengukuran: jarak, elevation gain, durasi, dan
             * koordinat. Angka adalah data, bukan prosa.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', ...defaultTheme.fontFamily.serif],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Batas kontrol interaktif, dipisahkan dari abu dekoratif karena
                // WCAG 1.4.11 hanya mengatur yang interaktif.
                control: token('control-border'),

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

            borderRadius: {
                control: 'var(--radius-control)',
            },
        },
    },

    plugins: [forms],
};
