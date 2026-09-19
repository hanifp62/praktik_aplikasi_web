@props(['name', 'label', 'type' => 'text', 'hint' => null])

<div>
    <x-input-label :for="$name" :value="$label" />
    <x-text-input id="{{ $name }}" type="{{ $type }}" wire:model="{{ $name }}"
        class="mt-1 block w-full" {{ $attributes }} />
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
