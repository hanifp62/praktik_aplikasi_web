@props(['active'])

@php
/*
 * Penanda fokus, bukan sekadar perubahan warna.
 *
 * Versi Breeze mematikan outline lalu menggantinya dengan pergeseran warna teks dan
 * batas gray-300. Batas itu hanya 1.47:1 di atas putih, jadi penggantinya praktis tidak
 * terlihat, dan pengguna papan ketik menelusuri seluruh navigasi tanpa tahu di mana ia
 * berada (WCAG 2.4.7 dan 1.4.11).
 *
 * focus-visible, bukan focus: penanda ini untuk penelusuran papan ketik, dan cincin yang
 * ikut muncul setiap kali tautan diklik tetikus hanya menjadi kedipan tanpa guna.
 */
$fokus = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 rounded-sm';

$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand-600 text-sm font-medium leading-5 text-primary transition duration-150 ease-in-out '.$fokus
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-muted hover:text-secondary hover:border-control transition duration-150 ease-in-out '.$fokus;
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
