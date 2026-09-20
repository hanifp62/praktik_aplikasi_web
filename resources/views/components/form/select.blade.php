@props(['name', 'label', 'options' => [], 'placeholder' => 'Pilih salah satu', 'required' => false])

<div>
    <x-input-label :for="$name" :value="$label" />
    <select id="{{ $name }}" wire:model{{ $attributes->get('live') ? '.live' : '' }}="{{ $name }}"
        @required($required)
        class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600">
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
