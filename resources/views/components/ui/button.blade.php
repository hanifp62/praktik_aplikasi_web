@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'navigate' => true,
    'target' => null,
    'confirm' => null,
])

{{--
    Satu-satunya definisi gaya dan perilaku tombol aplikasi.

    min-h-11 memenuhi ukuran target sentuh WCAG 2.2 (PRD §87) dan
    focus-visible:ring memenuhi focus visibility. Mengubah bentuk tombol
    di seluruh aplikasi cukup dilakukan di berkas ini.

    Ukuran "sm" hanya mengecilkan tampilannya, tidak area sentuhnya: jempol tidak
    ikut mengecil ketika tombolnya terlihat lebih kecil.

    Keadaan sedang memproses juga tinggal di sini, bukan ditambal di tiap halaman. Di
    jaringan tipis pengguna menekan tombol, tidak ada yang terlihat berubah, lalu ia
    menekannya lagi; tombol yang menonaktifkan dirinya sendiri mencegah aksi ganda itu
    sekaligus menjawab pertanyaan "apakah tadi tersimpan".
--}}
@php
    $sizing = $size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm';

    $base = 'inline-flex items-center justify-center gap-2 rounded-control min-h-11 '
        .$sizing.' font-medium transition focus:outline-none focus-visible:ring-2 '
        .'focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $styles = match ($variant) {
        'secondary' => 'border border-control bg-white text-primary hover:bg-surface-sunken focus-visible:ring-muted',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',
        'ghost' => 'text-brand-700 underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
        // brand-700, bukan brand-600: putih di atas brand-600 hanya 3.77:1 dan gagal AA
        // untuk teks normal. Dijaga ColourContrastTest, yang menghitung rasionya.
        default => 'bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-600',
    };

    $classes = $base.' '.$styles;

    // Tanpa wire:target, satu permintaan Livewire memutar spinner setiap tombol di
    // halaman itu, termasuk yang tidak ada hubungannya. Aksi pada wire:click sudah
    // menamai dirinya sendiri, jadi dipakai langsung; formulir menyebutkannya lewat
    // prop target karena aksinya ada di wire:submit milik form, bukan di tombol.
    $aksi = $target ?? $attributes->get('wire:click');

    $loading = [
        'wire:loading.attr' => 'disabled',
    ];

    if ($aksi) {
        $loading['wire:target'] = $aksi;
    }
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button
        @if ($confirm) wire:confirm="{{ $confirm }}" @endif
        {{ $attributes->merge(array_merge(['type' => 'button', 'class' => $classes], $loading)) }}
    >
        {{-- display:none dipasang di server, bukan diserahkan ke Livewire.
             Livewire baru menyembunyikannya setelah hidrasi, sehingga sebelum JS jalan
             setiap tombol menampilkan spinner dan membacakan "Memproses" kepada pembaca
             layar. Livewire tetap menampilkannya saat aksinya berjalan. --}}
        <span wire:loading @if ($aksi) wire:target="{{ $aksi }}" @endif
            style="display: none;" class="inline-flex items-center gap-2">
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
            </svg>
            <span class="sr-only">Memproses</span>
        </span>

        {{ $slot }}
    </button>
@endif
