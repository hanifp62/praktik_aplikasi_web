<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Rencana Trip Saya"
            description="Semua rencana pendakian Anda beserta status kesiapannya." />

        @if ($trips->isEmpty())
            <x-ui.card>
                <p class="text-sm text-secondary">Belum ada rencana trip.</p>
                <x-ui.button href="{{ route('goals.create') }}">Mulai dari rencana pendakian</x-ui.button>
            </x-ui.card>
        @else
            <ul class="space-y-3">
                @foreach ($trips as $trip)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-primary">
                                        <a href="{{ route('trips.show', $trip) }}" wire:navigate
                                            class="hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            {{ $trip->name }}
                                        </a>
                                    </h2>
                                    <p class="text-sm text-secondary">
                                        {{ $trip->trail->name }} &middot; {{ $trip->trail->mountain->name }}
                                    </p>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $trip->planned_date->translatedFormat('d M Y') }} &middot; {{ $trip->trip_type->label() }}
                                    </p>

                                    @if (isset($ringkasanFit[$trip->trail_id]))
                                        <x-ui.fit-line :summary="$ringkasanFit[$trip->trail_id]" />
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="inline-block rounded-control bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">
                                        {{ $trip->status->label() }}
                                    </span>
                                    @if ($trip->trailIsWithdrawn())
                                        <p class="mt-1 text-xs font-medium text-warn-900">
                                            Jalur ditarik dari katalog
                                        </p>
                                    @elseif ($trip->readinessIsStale($statusJalur[$trip->trail_id] ?? null))
                                        <p class="mt-1 text-xs font-medium text-warn-900">
                                            Status jalur berubah, perlu dinilai ulang
                                        </p>
                                    @elseif ($trip->latestReadinessCheck)
                                        <p class="mt-1 text-xs text-secondary">
                                            Kesiapan: {{ $trip->latestReadinessCheck->computed_state->label() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $trips->links() }}</div>
        @endif
    </div>
</div>
