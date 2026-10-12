/**
 * Kelas bersama primitif: fokus terlihat dan umpan balik tekan (spec v3 bagian 8.4 dan 8.6).
 *
 * Satu sumber supaya setiap kontrol menandai fokus dengan cincin yang sama (brand-600,
 * paling sedikit 3:1 terhadap kanvas dan kartu di kedua tema, ColourContrastTest) dan
 * menekan dengan gerak yang sama: menyusut ke 0,98 selama 120 ms. Setelan Gerak dan
 * prefers-reduced-motion memangkas durasinya lewat app.css, jadi di sini tidak ada cabang
 * gerak sendiri.
 */

/** Cincin fokus pada elemen yang difokus itu sendiri. */
export const fokusTerlihat = 'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2';

/** Cincin fokus pada anak di dalam elemen berkelas group: bentuk visual lebih kecil dari target sentuhnya. */
export const fokusKelompok = 'group-focus-visible:ring-2 group-focus-visible:ring-brand-600 group-focus-visible:ring-offset-2';

/** Cincin fokus pada label yang membungkus input tersembunyi (kontrol segmen). */
export const fokusDiDalam = 'has-focus-visible:ring-2 has-focus-visible:ring-brand-600 has-focus-visible:ring-offset-2';

/** Umpan balik tekan: hanya transform dan warna yang berpindah, tanpa gerak ketika nonaktif. */
export const umpanTekan = 'transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak active:scale-[0.98] disabled:active:scale-100';

/** Umpan balik tekan untuk anak di dalam elemen berkelas group. */
export const umpanTekanKelompok = 'transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak group-active:scale-[0.98]';
