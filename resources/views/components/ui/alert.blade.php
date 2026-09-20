@props([
    'variant' => 'info',
    'title' => null,
])

{{--
    Pesan status, peringatan, dan kegagalan.

    Makna tidak pernah disampaikan lewat warna saja (WCAG 2.2, PRD §87):
    setiap varian membawa simbol dan awalan teks yang terbaca screen reader.
--}}
@php
    $styles = match ($variant) {
        'warning' => 'border-warn-300 bg-warn-100 text-warn-900',
        'danger' => 'border-danger-300 bg-danger-100 text-danger-900',
        'success' => 'border-brand-300 bg-brand-100 text-brand-900',
        default => 'border-subtle bg-surface-sunken text-primary',
    };

    $symbol = match ($variant) {
        'warning' => '!',
        'danger' => '×',
        'success' => '✓',
        default => 'i',
    };

    $prefix = match ($variant) {
        'warning' => 'Peringatan:',
        'danger' => 'Perhatian penting:',
        'success' => 'Berhasil:',
        default => 'Informasi:',
    };

    $role = in_array($variant, ['warning', 'danger'], true) ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => 'flex gap-3 rounded-control border p-4 text-sm '.$styles]) }}>
    <span aria-hidden="true" class="font-semibold">{{ $symbol }}</span>

    <div class="space-y-1">
        <span class="sr-only">{{ $prefix }}</span>

        @if ($title)
            <p class="font-medium">{{ $title }}</p>
        @endif

        <div>{{ $slot }}</div>
    </div>
</div>
