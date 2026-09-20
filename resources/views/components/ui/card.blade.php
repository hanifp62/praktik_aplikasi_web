@props(['title' => null, 'subtitle' => null])

{{--
    Satu garis rambut, bukan bayangan.

    Empat puluh delapan bayangan membuat setiap blok tampak melayang, dan halaman yang
    seluruh isinya melayang tidak punya bidang dasar. Garis memisahkan tanpa mengangkat.

    Padding naik dari p-5: ruang dalam yang sempit membuat isinya terbaca padat berapa
    pun ukuran hurufnya.
--}}
<section {{ $attributes->merge(['class' => 'rounded-lg border border-subtle bg-surface p-6']) }}>
    @if ($title)
        <h2 class="text-lg font-semibold text-primary">{{ $title }}</h2>
    @endif
    @if ($subtitle)
        <p class="mt-1 max-w-prose text-base text-secondary">{{ $subtitle }}</p>
    @endif
    <div @class(['mt-3' => $title || $subtitle])>{{ $slot }}</div>
</section>
