<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Riwayat Pendakian"
            description="Catatan perjalanan Anda, kelengkapan persiapan saat itu, dan laporan kondisi yang Anda kirim." />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($entries->isEmpty())
            <x-ui.card>
                <p class="text-sm text-secondary">Belum ada pendakian yang tercatat selesai.</p>
            </x-ui.card>
        @else
            <ul class="space-y-3">
                @foreach ($entries as $entry)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-primary">
                                        <a href="{{ route('history.summary', $entry->trip_plan_id) }}" wire:navigate
                                            class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                            {{ $entry->trail->name }}
                                        </a>
                                    </h2>
                                    <p class="text-sm text-secondary">{{ $entry->trail->mountain->name }}</p>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $entry->completed_at->translatedFormat('d M Y') }} &middot;
                                        {{ $entry->trip_type->label() }} &middot;
                                        {{ $entry->completion_state->label() }}
                                    </p>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="text-muted">Persiapan saat itu</p>
                                    <p class="font-semibold text-primary">{{ $entry->preparation_completion_percent }}%</p>
                                </div>
                            </div>

                            @if ($entry->personal_notes)
                                <p class="mt-3 text-sm text-secondary">{{ $entry->personal_notes }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('trips.show', $entry->trip_plan_id) }}" wire:navigate
                                    class="text-brand-700 underline">Lihat trip</a>
                                @if ($entry->conditionReport)
                                    <span class="text-secondary">
                                        Laporan kondisi: {{ $entry->conditionReport->moderation_status->label() }}
                                    </span>
                                @else
                                    <a href="{{ route('reports.create', ['trail' => $entry->trail_id, 'trip' => $entry->trip_plan_id]) }}"
                                        wire:navigate class="text-brand-700 underline">Kirim laporan kondisi</a>
                                @endif
                            </div>

                            {{-- Pelapor berhak tahu apa yang salah, kalau tidak ia tidak dapat
                                 memperbaikinya dan usaha moderator menulis alasan terbuang. --}}
                            @if ($entry->conditionReport?->rejectionReason())
                                <p class="mt-2 rounded-md border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                                    Alasan moderator: {{ $entry->conditionReport->rejectionReason() }}
                                </p>
                            @endif
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
