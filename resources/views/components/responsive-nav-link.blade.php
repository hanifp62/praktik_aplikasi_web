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

$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-600 text-start text-base font-medium text-brand-900 bg-brand-50 transition duration-150 ease-in-out '.$fokus
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 transition duration-150 ease-in-out '.$fokus;
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
