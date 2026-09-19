<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Rencana Trip Saya"
            description="Semua rencana pendakian Anda beserta status kesiapannya." />

        @if ($trips->isEmpty())
            <x-ui.card>
                <p class="text-sm text-gray-600">Belum ada rencana trip.</p>
                <a href="{{ route('goals.create') }}" wire:navigate
                    class="mt-3 inline-block rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Mulai dari rencana pendakian
                </a>
            </x-ui.card>
        @else
            <ul class="space-y-3">
                @foreach ($trips as $trip)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">
                                        <a href="{{ route('trips.show', $trip) }}" wire:navigate
                                            class="hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            {{ $trip->name }}
                                        </a>
                                    </h2>
                                    <p class="text-sm text-gray-600">
                                        {{ $trip->trail->name }} &middot; {{ $trip->trail->mountain->name }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $trip->planned_date->translatedFormat('d M Y') }} &middot; {{ $trip->trip_type->label() }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $trip->status->label() }}
                                    </span>
                                    @if ($trip->latestReadinessCheck)
                                        <p class="mt-1 text-xs text-gray-600">
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
