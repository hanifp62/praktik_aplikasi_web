@props(['name', 'label' => null, 'class' => 'h-4 w-4'])

@php
    /*
     * Ikon Phosphor berat regular, ditanam sebagai path.
     *
     * Ditanam, bukan diimpor dari pustaka: yang dipakai aplikasi ini segelintir, dan
     * menambah dependensi demi lima bentuk menagih bita kepada setiap pengguna untuk
     * ikon yang tidak pernah ia lihat.
     *
     * Bukan Lucide maupun Heroicons. Keduanya pilihan bawaan hampir semua antarmuka
     * hasil AI, dan memakainya berarti mengulang tanda tangan yang sedang dihapus.
     *
     * Nama yang tidak dikenal menghasilkan kekosongan, bukan kotak rusak: ikon tidak
     * pernah menjadi satu-satunya pembawa arti di sini, jadi hilangnya satu bentuk tidak
     * boleh merusak barisnya.
     */
    $bentuk = [
        'arrow-up' => '<path d="M205.66,117.66a8,8,0,0,1-11.32,0L136,59.31V216a8,8,0,0,1-16,0V59.31L61.66,117.66a8,8,0,0,1-11.32-11.32l72-72a8,8,0,0,1,11.32,0l72,72A8,8,0,0,1,205.66,117.66Z"/>',
        'arrow-down' => '<path d="M205.66,149.66l-72,72a8,8,0,0,1-11.32,0l-72-72a8,8,0,0,1,11.32-11.32L120,196.69V40a8,8,0,0,1,16,0V196.69l58.34-58.35a8,8,0,0,1,11.32,11.32Z"/>',
        'path' => '<path d="M216,40H160a8,8,0,0,0,0,16h36.69L152,100.69,131.31,80a16,16,0,0,0-22.62,0l-64,64a8,8,0,0,0,11.31,11.31L120,91.31,140.69,112a16,16,0,0,0,22.62,0L208,67.31V104a8,8,0,0,0,16,0V48A8,8,0,0,0,216,40Z"/>',
        'mountains' => '<path d="M248,208H231.4L172.32,49.51a16,16,0,0,0-30.07,0l-20.9,56.05-27.4-47.46a16,16,0,0,0-27.71,0L12.72,188a16,16,0,0,0,13.86,24H248a8,8,0,0,0,0-16Z"/>',
        'shield-check' => '<path d="M208,40H48A16,16,0,0,0,32,56v58.77c0,89.62,75.82,119.34,91,124.39a15.53,15.53,0,0,0,10,0c15.2-5.05,91-34.77,91-124.39V56A16,16,0,0,0,208,40Zm-32.4,64.16-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L114,148.12l50.34-50.35a8,8,0,0,1,11.32,11.32Z"/>',
    ];

    $isi = $bentuk[$name] ?? null;
@endphp

@if ($isi)
    <svg viewBox="0 0 256 256" fill="currentColor" class="{{ $class }} inline-block shrink-0"
        @if ($label) role="img" @else aria-hidden="true" @endif>
        @if ($label)
            <title>{{ $label }}</title>
        @endif
        {!! $isi !!}
    </svg>
@endif
