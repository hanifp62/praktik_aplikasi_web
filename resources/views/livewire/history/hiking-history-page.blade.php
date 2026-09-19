<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Riwayat Pendakian"
            description="Catatan perjalanan Anda, kelengkapan persiapan saat itu, dan laporan kondisi yang Anda kirim." />

        @if (session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($entries->isEmpty())
            <x-ui.card>
                <p class="text-sm text-gray-600">Belum ada pendakian yang tercatat selesai.</p>
            </x-ui.card>
        @else
            <ul class="space-y-3">
                @foreach ($entries as $entry)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">{{ $entry->trail->name }}</h2>
                                    <p class="text-sm text-gray-600">{{ $entry->trail->mountain->name }}</p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $entry->completed_at->translatedFormat('d M Y') }} &middot;
                                        {{ $entry->trip_type->label() }} &middot;
                                        {{ $entry->completion_state->label() }}
                                    </p>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="text-gray-500">Persiapan saat itu</p>
                                    <p class="font-semibold text-gray-900">{{ $entry->preparation_completion_percent }}%</p>
                                </div>
                            </div>

                            @if ($entry->personal_notes)
                                <p class="mt-3 text-sm text-gray-700">{{ $entry->personal_notes }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('trips.show', $entry->trip_plan_id) }}" wire:navigate
                                    class="text-emerald-700 underline">Lihat trip</a>
                                @if ($entry->conditionReport)
                                    <span class="text-gray-600">
                                        Laporan kondisi: {{ $entry->conditionReport->moderation_status->label() }}
                                    </span>
                                @else
                                    <a href="{{ route('reports.create', ['trail' => $entry->trail_id, 'trip' => $entry->trip_plan_id]) }}"
                                        wire:navigate class="text-emerald-700 underline">Kirim laporan kondisi</a>
                                @endif
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
