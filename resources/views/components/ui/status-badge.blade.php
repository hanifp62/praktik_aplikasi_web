@props(['status', 'scope' => null])

@php
    $classes = match ($status?->value) {
        'OPEN' => 'bg-brand-100 text-brand-900 border-brand-300',
        'RESTRICTED' => 'bg-warn-100 text-warn-900 border-warn-300',
        'CLOSED' => 'bg-danger-100 text-danger-900 border-danger-300',
        default => 'bg-gray-100 text-gray-700 border-gray-300',
    };
@endphp

<span class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-sm font-medium {{ $classes }}">
    <span class="sr-only">Status resmi:</span>
    {{ $status?->label() ?? 'Belum diketahui' }}
    @if ($scope)
        <span class="text-xs font-normal">({{ $scope }})</span>
    @endif
</span>
