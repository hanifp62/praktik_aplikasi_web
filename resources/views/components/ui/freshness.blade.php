@props(['state' => null, 'timestamp' => null, 'prefix' => 'Diperbarui'])

@php
    $stateLabel = is_string($state) ? \App\Enums\FreshnessState::tryFrom($state)?->label() : $state?->label();
@endphp

<p class="text-xs text-gray-500">
    @if ($timestamp)
        {{ $prefix }} {{ \Illuminate\Support\Carbon::parse($timestamp)->translatedFormat('d M Y H:i') }}
    @else
        Waktu pembaruan tidak tersedia
    @endif
    @if ($stateLabel)
        <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5">{{ $stateLabel }}</span>
    @endif
</p>
