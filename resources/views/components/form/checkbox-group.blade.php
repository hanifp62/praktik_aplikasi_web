@props(['name', 'label', 'options' => []])

<div>
    <span class="block text-sm font-medium text-secondary">{{ $label }}</span>
    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        @foreach ($options as $value => $optionLabel)
            <label class="flex items-center gap-2 rounded-control border border-subtle px-3 py-2 text-sm">
                <input type="checkbox" value="{{ $value }}" wire:model="{{ $name }}"
                    class="rounded border-control text-brand-700 focus:ring-brand-600">
                {{ $optionLabel }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
