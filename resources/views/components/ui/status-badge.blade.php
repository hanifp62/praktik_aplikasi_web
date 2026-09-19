@props(['status', 'scope' => null])

@php
    $classes = match ($status?->value) {
        'OPEN' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
        'RESTRICTED' => 'bg-amber-100 text-amber-900 border-amber-300',
        'CLOSED' => 'bg-rose-100 text-rose-900 border-rose-300',
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
