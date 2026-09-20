@props(['summary'])

{{--
    Label kecocokan beserta satu alasan, untuk tingkat daftar.

    §89 menyusun urutan informasi: Apa ini, lalu Cocokkah untuk saya, baru Mengapa. Baris
    daftar menjawab dua pertanyaan pertama dan menyerahkan lapisan penuhnya ke halaman
    detail; membawa tiga lapis faktor ke dalam baris akan menjawab pertanyaan yang belum
    diajukan pembacanya.

    Label tidak pernah berdiri sendiri. Label tanpa alasan adalah vonis.
--}}
<div {{ $attributes->merge(['class' => 'mt-3 flex flex-wrap items-center gap-x-2 gap-y-1']) }}>
    <span data-fit-label class="shrink-0">
        <x-ui.fit-badge :label="$summary->label" />
    </span>

    <span class="text-xs text-muted">
        {{ $summary->denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar' }}
    </span>

    <p data-fit-reason class="w-full text-sm text-secondary">{{ $summary->alasan }}</p>
</div>
