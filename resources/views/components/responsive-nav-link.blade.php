@props(['active'])

@php
/*
 * Sama dengan nav-link: outline dimatikan Breeze lalu diganti pergeseran warna dan
 * batas gray-300 yang hanya 1.47:1, sehingga penggantinya praktis tidak terlihat.
 *
 * Menu ini justru yang dipakai di ponsel, dan ponsel adalah platform utama (§88).
 * ring-inset dipakai karena barisnya selebar layar; cincin di luar tepinya akan
 * terpotong.
 */
$fokus = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600';

// min-h-11 (44px): standar produk ini sendiri (lihat x-ui.button), bukan sekadar batas
// WCAG 2.5.8 (24px). Menu ini satu-satunya jalan ke empat dari lima permukaan di
// platform utama (§88, Tugas 7), jadi py-2 saja (~40px) sudah cukup untuk lolos WCAG
// tetapi masih di bawah standar sendiri produk ini.
$classes = ($active ?? false)
            ? 'flex w-full min-h-11 items-center ps-3 pe-4 py-2 border-l-4 border-brand-600 text-start text-base font-medium text-brand-900 bg-brand-50 transition duration-150 ease-in-out '.$fokus
            : 'flex w-full min-h-11 items-center ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-secondary hover:text-primary hover:bg-surface-sunken hover:border-control transition duration-150 ease-in-out '.$fokus;
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
