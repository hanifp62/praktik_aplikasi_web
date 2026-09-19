@props(['label'])

@php
    $classes = match ($label?->value) {
        'COCOK' => 'bg-brand-100 text-brand-900 border-brand-300',
        'PERLU_PERSIAPAN' => 'bg-warn-100 text-warn-900 border-warn-300',
        'KURANG_COCOK' => 'bg-danger-100 text-danger-900 border-danger-300',
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
