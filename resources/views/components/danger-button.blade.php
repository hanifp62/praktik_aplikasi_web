{{--
    Memakai token danger, bukan palet mentah Tailwind.

    Sisa Breeze sebelumnya memakai bg-red-600 dengan hover:bg-red-500, yaitu hover yang
    MENERANG. Putih di atas red-500 hanya 3.76:1 dan gagal AA: tombolnya justru paling
    sulit dibaca tepat saat jari menunjuknya. Token danger menggelap saat hover.
--}}
<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-control bg-danger-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-danger-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
]) }}>
    {{ $slot }}
</button>
