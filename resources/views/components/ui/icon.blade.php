@props(['name', 'label' => null, 'size' => 16, 'class' => ''])

@php
    /*
     * Ikon Phosphor (phosphor-icons/core 2.1.1), ditanam sebagai path.
     * Lisensi: MIT, Copyright (c) 2023 Phosphor Icons; teks lengkap di resources/js/components/ui/LICENSE-phosphor.txt.
     *
     * Ditanam, bukan diimpor dari pustaka: halaman Blade publik hanya memakai panah naik
     * dan turun, dan menambah dependensi demi dua bentuk menagih bita kepada setiap
     * pengguna untuk ikon yang tidak pernah ia lihat. Registri lengkap untuk halaman React
     * ada di resources/js/components/ui/ikon/phosphor.ts.
     *
     * Bobot mengikuti ukuran render seperti komponen React (spec v3 bagian 8.3, DK-39): Bold
     * di bawah 20 px, Regular mulai 20 px. Ukuran di bawah 16 px ditolak, karena ikon
     * informatif yang lebih kecil dari itu tidak terbaca.
     *
     * Nama yang tidak dikenal menghasilkan kekosongan, bukan kotak rusak: ikon tidak
     * pernah menjadi satu-satunya pembawa arti di sini, jadi hilangnya satu bentuk tidak
     * boleh merusak barisnya.
     */
    $ukuran = (int) $size;

    if (! in_array($ukuran, [16, 20, 24], true)) {
        throw new \InvalidArgumentException("Ukuran ikon {$ukuran} px tidak didukung: ikon informatif paling kecil 16 px, dan ukuran yang tersedia 16, 20, atau 24 (DK-39).");
    }

    $bentuk = [
        'arrow-up' => [
            'regular' => 'M205.66,117.66a8,8,0,0,1-11.32,0L136,59.31V216a8,8,0,0,1-16,0V59.31L61.66,117.66a8,8,0,0,1-11.32-11.32l72-72a8,8,0,0,1,11.32,0l72,72A8,8,0,0,1,205.66,117.66Z',
            'bold' => 'M208.49,120.49a12,12,0,0,1-17,0L140,69V216a12,12,0,0,1-24,0V69L64.49,120.49a12,12,0,0,1-17-17l72-72a12,12,0,0,1,17,0l72,72A12,12,0,0,1,208.49,120.49Z',
        ],
        'arrow-down' => [
            'regular' => 'M205.66,149.66l-72,72a8,8,0,0,1-11.32,0l-72-72a8,8,0,0,1,11.32-11.32L120,196.69V40a8,8,0,0,1,16,0V196.69l58.34-58.35a8,8,0,0,1,11.32,11.32Z',
            'bold' => 'M208.49,152.49l-72,72a12,12,0,0,1-17,0l-72-72a12,12,0,0,1,17-17L116,187V40a12,12,0,0,1,24,0V187l51.51-51.52a12,12,0,0,1,17,17Z',
        ],
    ];

    $isi = $bentuk[$name][$ukuran < 20 ? 'bold' : 'regular'] ?? null;
    $kelasUkuran = [16 => 'size-4', 20 => 'size-5', 24 => 'size-6'][$ukuran];
@endphp

@if ($isi)
    <svg viewBox="0 0 256 256" fill="currentColor" class="{{ trim($kelasUkuran.' inline-block shrink-0 '.$class) }}"
        @if ($label) role="img" @else aria-hidden="true" @endif>
        @if ($label)
            <title>{{ $label }}</title>
        @endif
        <path d="{{ $isi }}"/>
    </svg>
@endif
