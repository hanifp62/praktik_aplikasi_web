@props(['name', 'label', 'options' => []])

<div>
    <span class="block text-sm font-medium text-gray-700">{{ $label }}</span>
    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        @foreach ($options as $value => $optionLabel)
            <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                <input type="checkbox" value="{{ $value }}" wire:model="{{ $name }}"
                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                {{ $optionLabel }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
