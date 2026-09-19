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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
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
