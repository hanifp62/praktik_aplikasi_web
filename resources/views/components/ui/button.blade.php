@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'navigate' => true,
])

{{--
    Satu-satunya definisi gaya tombol aplikasi.

    min-h-11 memenuhi ukuran target sentuh WCAG 2.2 (PRD §87) dan
    focus-visible:ring memenuhi focus visibility. Mengubah bentuk tombol
    di seluruh aplikasi cukup dilakukan di berkas ini.

    Ukuran "sm" hanya mengecilkan tampilannya, tidak area sentuhnya: jempol tidak
    ikut mengecil ketika tombolnya terlihat lebih kecil.
--}}
@php
    $sizing = $size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm';

    $base = 'inline-flex items-center justify-center gap-2 rounded-control min-h-11 '
        .$sizing.' font-medium transition focus:outline-none focus-visible:ring-2 '
        .'focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $styles = match ($variant) {
        'secondary' => 'border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 focus-visible:ring-gray-500',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',
        'ghost' => 'text-brand-700 underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
        // brand-700, bukan brand-600: putih di atas brand-600 hanya 3.77:1 dan gagal AA
        // untuk teks normal. Diverifikasi dengan contrast-check.py, bukan dikira-kira.
        default => 'bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-600',
    };

    $classes = $base.' '.$styles;
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
