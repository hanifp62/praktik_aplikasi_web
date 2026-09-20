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
                    <p class="text-sm text-secondary">Item dikonfirmasi</p>
                    <p class="text-2xl font-semibold text-primary">{{ $completion }}%</p>
                </div>
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" size="sm" wire:click="regenerate">
                        Perbarui daftar</x-ui.button>
                    <x-ui.button href="{{ route('trips.readiness', $trip) }}">Cek kesiapan</x-ui.button>
                </div>
            </div>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-surface-sunken"
                role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100"
                aria-label="Kemajuan persiapan">
                <div class="h-full bg-brand-500" style="width: {{ $completion }}%"></div>
            </div>
            <p class="mt-3 text-xs text-muted">
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
                                <li class="rounded-md border border-subtle p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-primary">
                                                {{ $item->label }}
                                                @if ($item->is_critical)
                                                    <span class="ml-1 rounded bg-warn-100 px-1.5 py-0.5 text-xs text-warn-900">
                                                        Kritis
                                                    </span>
                                                @endif
                                            </p>
                                            @if ($item->description)
                                                <p class="mt-1 text-sm text-secondary">{{ $item->description }}</p>
                                            @endif
                                        </div>

                                        <fieldset class="flex gap-1">
                                            <legend class="sr-only">Status untuk {{ $item->label }}</legend>
                                            @foreach ($statuses as $status)
                                                <button type="button"
                                                    wire:click="setStatus({{ $item->id }}, '{{ $status->value }}')"
                                                    aria-pressed="{{ $item->status === $status ? 'true' : 'false' }}"
                                                    {{--
                                                        Satu-satunya momen gerak yang diarahkan di aplikasi ini,
                                                        dan ia dipasang di interaksi yang paling sering diulang:
                                                        mengonfirmasi satu per satu item persiapan.

                                                        Perpindahan warnanya diberi durasi supaya terlihat sebagai
                                                        perubahan yang terjadi, bukan sebagai layar yang mendadak
                                                        berbeda. Yang bergerak hanya warna dan batasnya; tidak ada
                                                        yang muncul dari ketiadaan, jadi kegagalan skrip tidak
                                                        pernah meninggalkan tombol yang tak terlihat.
                                                    --}}
                                                    @class([
                                                        'inline-flex min-h-11 items-center rounded-md border px-3 py-1 text-xs font-medium',
                                                        'transition-colors duration-150 ease-out motion-reduce:transition-none',
                                                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-1',
                                                        'border-brand-700 bg-brand-700 text-white' => $item->status === $status,
                                                        'border-control text-secondary hover:bg-surface-sunken' => $item->status !== $status,
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
