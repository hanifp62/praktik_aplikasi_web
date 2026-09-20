@props(['name', 'label', 'type' => 'text', 'hint' => null])

{{--
    Petunjuk dan galat dikaitkan ke isiannya lewat aria-describedby (WCAG 1.3.1, 3.3.1).

    Tanpa itu, pengguna pembaca layar yang berpindah antar isian mendengar labelnya saja.
    Petunjuk "Dalam menit, sehari penuh sekitar 720" dan pesan galatnya berada di elemen
    lain yang tidak pernah disebut, padahal keduanya justru yang menjelaskan cara
    mengisinya dengan benar.
--}}
@php
    // ?? karena $errors hanya dibagikan pada konteks request; komponennya tetap harus
    // dapat dirender di luar itu tanpa meledak.
    $galat = ($errors ?? null)?->get($name) ?? [];

    $penjelas = array_values(array_filter([
        $hint ? $name.'-hint' : null,
        $galat ? $name.'-error' : null,
    ]));
@endphp

<div>
    <x-input-label :for="$name" :value="$label" />

    <x-text-input id="{{ $name }}" type="{{ $type }}" wire:model="{{ $name }}"
        class="mt-1 block w-full"
        @if ($penjelas !== []) aria-describedby="{{ implode(' ', $penjelas) }}" @endif
        @if ($galat) aria-invalid="true" @endif
        {{ $attributes }} />

    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$galat" id="{{ $name }}-error" class="mt-2" />
</div>
