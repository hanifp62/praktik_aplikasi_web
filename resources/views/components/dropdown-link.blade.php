{{--
    Latar abu-abu saat fokus bukan penanda fokus: ia sama persis dengan latar saat
    tetikus lewat, jadi pengguna papan ketik tidak dapat membedakan "saya di sini" dari
    "kursor kebetulan di sini" (WCAG 2.4.7). ring-inset karena barisnya memenuhi lebar
    menu dan cincin di luar tepinya akan terpotong.
--}}
<a {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none focus-visible:bg-gray-100 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600']) }}>{{ $slot }}</a>
