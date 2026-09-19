<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Persiapan Pendakian"
            :description="$trip->name.' - '.$trip->trail->name.', '.$trip->trail->mountain->name" />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-600">Item dikonfirmasi</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $completion }}%</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="regenerate"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        Perbarui daftar
                    </button>
                    <x-ui.button href="{{ route('trips.readiness', $trip) }}">Cek kesiapan</x-ui.button>
                </div>
            </div>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100"
                role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100"
                aria-label="Kemajuan persiapan">
                <div class="h-full bg-brand-500" style="width: {{ $completion }}%"></div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
                Status &ldquo;Belum dikonfirmasi&rdquo; berarti item tersebut belum Anda pastikan, bukan berarti
                perlengkapannya tidak Anda miliki.
            </p>
        </x-ui.card>

        <div class="space-y-6">
            @foreach ($categories as $category)
                @php $items = $grouped[$category->value] ?? collect(); @endphp

                @if ($items->isNotEmpty())
                    <x-ui.card :title="$category->label()">
                        <ul class="space-y-3">
                            @foreach ($items as $item)
                                <li class="rounded-md border border-gray-100 p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $item->label }}
                                                @if ($item->is_critical)
                                                    <span class="ml-1 rounded bg-warn-100 px-1.5 py-0.5 text-xs text-warn-900">
                                                        Kritis
                                                    </span>
                                                @endif
                                            </p>
                                            @if ($item->description)
                                                <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>
                                            @endif
                                        </div>

                                        <fieldset class="flex gap-1">
                                            <legend class="sr-only">Status untuk {{ $item->label }}</legend>
                                            @foreach ($statuses as $status)
                                                <button type="button"
                                                    wire:click="setStatus({{ $item->id }}, '{{ $status->value }}')"
                                                    aria-pressed="{{ $item->status === $status ? 'true' : 'false' }}"
                                                    @class([
                                                        'rounded-md border px-2.5 py-1 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-brand-500',
                                                        'border-brand-600 bg-brand-600 text-white' => $item->status === $status,
                                                        'border-gray-300 text-gray-700 hover:bg-gray-50' => $item->status !== $status,
                                                    ])>
                                                    {{ $status->label() }}
                                                </button>
                                            @endforeach
                                        </fieldset>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif
            @endforeach
        </div>
    </div>
</div>
