@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

{{--
    Tombol v3 untuk permukaan Blade (halaman publik jalur; W1-F2), salinan kelas
    resources/js/components/ui/Button.tsx supaya halaman tanpa React memakai bentuk yang sama:
    radius 16 px, label tebal, target sentuh 44 px, fokus terlihat, dan tekan 0,98.
    ButtonBladeParityTest menjaga kedua salinan tetap sama.

    Batasan dibanding Button.tsx (ruling W3-10a): tanpa status sibuk (tidak ada prop
    processing, aria-busy, atau spinner), dan tanpa varian danger; varian yang tidak dikenal
    jatuh ke primary. Pemakai yang butuh status sibuk atau aksi destruktif memakai React.
    Cabang tautan hanya meneruskan class dan nama aksesibel (aria-label, aria-describedby,
    title), sama dengan cabang href Button.tsx; atribut tombol lain tidak berlaku pada tautan.
--}}
@php
    $ukuran = $size === 'sm' ? 'px-4 py-2 text-kecil' : 'px-5 py-3 text-isi';
    $semuaGaya = [
        'primary' => 'bg-brand-700 text-on-brand hover:bg-brand-800 focus-visible:ring-brand-600',
        'secondary' => 'border border-control bg-surface text-primary hover:bg-surface-sunken focus-visible:ring-brand-600',
        'ghost' => 'text-brand-ink underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
    ];
    $gaya = $semuaGaya[$variant] ?? $semuaGaya['primary'];
    $kelas = 'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak active:scale-[0.98] disabled:active:scale-100 '.$ukuran.' '.$gaya;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->only(['class', 'aria-label', 'aria-describedby', 'title'])->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
