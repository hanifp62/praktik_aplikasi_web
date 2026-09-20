{{--
    Latar abu-abu saat fokus bukan penanda fokus: ia sama persis dengan latar saat
    tetikus lewat, jadi pengguna papan ketik tidak dapat membedakan "saya di sini" dari
    "kursor kebetulan di sini" (WCAG 2.4.7). ring-inset karena barisnya memenuhi lebar
    menu dan cincin di luar tepinya akan terpotong.

    min-h-11 (44px): standar produk ini sendiri (lihat x-ui.button), bukan sekadar batas
    WCAG 2.5.8 (24px). py-2 leading-5 saja (~36px) lolos WCAG tetapi masih di bawahnya --
    dan ini jalan satu-satunya ke Profil, Moderasi, Admin, dan Keluar.
--}}
<a {{ $attributes->merge(['class' => 'flex w-full min-h-11 items-center px-4 py-2 text-start text-sm leading-5 text-secondary transition duration-150 ease-in-out hover:bg-surface-sunken focus:outline-none focus-visible:bg-surface-sunken focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600']) }}>{{ $slot }}</a>
