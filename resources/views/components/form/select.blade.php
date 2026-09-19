@props(['name', 'label', 'options' => [], 'placeholder' => 'Pilih salah satu', 'required' => false])

<div>
    <x-input-label :for="$name" :value="$label" />
    <select id="{{ $name }}" wire:model{{ $attributes->get('live') ? '.live' : '' }}="{{ $name }}"
        @required($required)
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
