@props(['label'])

@php
    $classes = match ($label?->value) {
        'COCOK' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
        'PERLU_PERSIAPAN' => 'bg-amber-100 text-amber-900 border-amber-300',
        'KURANG_COCOK' => 'bg-rose-100 text-rose-900 border-rose-300',
        default => 'bg-gray-100 text-gray-700 border-gray-300',
    };
    $symbol = match ($label?->value) {
        'COCOK' => '✓',
        'PERLU_PERSIAPAN' => '!',
        'KURANG_COCOK' => '×',
        default => '?',
    };
@endphp

{{-- Status is never communicated by colour alone (WCAG 2.2, PRD §87). --}}
<span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium {{ $classes }}">
    <span aria-hidden="true">{{ $symbol }}</span>
    {{ $label?->label() ?? 'Tidak dinilai' }}
</span>
