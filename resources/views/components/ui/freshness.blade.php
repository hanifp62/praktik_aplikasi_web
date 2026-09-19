@props(['state' => null, 'timestamp' => null, 'prefix' => 'Diperbarui', 'timezone' => null])

@php
    $stateLabel = is_string($state) ? \App\Enums\FreshnessState::tryFrom($state)?->label() : $state?->label();

    // Waktu disimpan UTC. Tanpa konversi dan penanda zona, pengguna tidak punya cara
    // tahu jam mana yang dimaksud (PRD §93).
    $zone = $timezone ?: \App\Support\Timezone::DEFAULT;
    $rendered = $timestamp
        ? \App\Support\Timezone::display(\Illuminate\Support\Carbon::parse($timestamp), $zone, 'd M Y H:i')
        : null;
@endphp

<p class="text-xs text-gray-500">
    @if ($rendered)
        {{ $prefix }} {{ $rendered }}
    @else
        Waktu pembaruan tidak tersedia
    @endif
    @if ($stateLabel)
        <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5">{{ $stateLabel }}</span>
    @endif
</p>
