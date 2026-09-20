@props([
    'title',
    'description' => null,
])

{{--
    Keadaan kosong.

    PRD §104 menuntut sistem menjelaskan penyebab, bukan sekadar menampilkan
    daftar kosong. Slot bawaan diisi tindakan lanjutan yang bisa diambil pengguna.
--}}
<div {{ $attributes->merge(['class' => 'rounded-control border border-dashed border-subtle bg-white p-8 text-center']) }}>
    <p class="text-sm font-medium text-primary">{{ $title }}</p>

    @if ($description)
        <p class="mx-auto mt-2 max-w-prose text-sm text-secondary">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
