@props(['title', 'description' => null])

{{--
    Judul memakai serif lewat lapisan base, dan ukurannya naik dua tingkat. text-2xl
    hanya dua tingkat di atas badan teks, sehingga halaman tidak punya titik masuk.

    Deskripsi naik ke text-base dan dibatasi lebar baca: justru kalimat inilah yang
    menentukan apakah pembaca meneruskan.
--}}
<div class="mb-8">
    <h1 class="text-balance text-3xl text-primary sm:text-4xl">{{ $title }}</h1>

    @if ($description)
        <p class="mt-2 max-w-prose text-base text-secondary">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
