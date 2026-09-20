<div class="py-8">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Bandingkan Jalur"
            description="Perbandingan ini membantu Anda melihat perbedaan antar jalur. Sistem tidak menentukan jalur mana yang terbaik." />

        @if ($trails->isEmpty())
            <x-ui.card>
                <p class="text-sm text-secondary">Belum ada jalur yang dipilih untuk dibandingkan.</p>
                <x-ui.button variant="secondary" href="{{ route('trails.index') }}">Jelajahi jalur</x-ui.button>
            </x-ui.card>
        @else
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <caption class="sr-only">Perbandingan karakteristik jalur</caption>
                    <thead>
                        <tr class="border-b border-subtle text-left">
                            <th scope="col" class="p-4 text-muted">Aspek</th>
                            @foreach ($trails as $trail)
                                <th scope="col" class="p-4">
                                    <span class="block font-semibold text-primary">{{ $trail->name }}</span>
                                    <span class="block text-xs font-normal text-secondary">{{ $trail->mountain->name }}</span>
                                    <button wire:click="removeTrail({{ $trail->id }})"
                                        class="mt-1 text-xs text-muted underline focus:outline-none focus:ring-2 focus:ring-brand-500">
                                        Hapus dari perbandingan
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rows = [
                                'Jarak' => fn ($trail) => ($trail->distance_km ?? '-').' km',
                                'Elevation gain' => fn ($trail) => ($trail->elevation_gain_m ?? '-').' m',
                                'Estimasi durasi' => fn ($trail) => $trail->estimated_duration_minutes
                                    ? round($trail->estimated_duration_minutes / 60, 1).' jam' : '-',
                                'Tingkat teknis' => fn ($trail) => $trail->technical_demand->label(),
                                'Navigasi' => fn ($trail) => $trail->navigation_complexity->label(),
                                'Sumber air' => fn ($trail) => $trail->water_availability->label(),
                                'Camping' => fn ($trail) => $trail->camping_available ? 'Tersedia' : 'Tidak tersedia',
                                'Laporan komunitas (30 hari)' => fn ($trail) => $trail->condition_reports_count.' laporan',
                            ];
                        @endphp

                        @foreach ($rows as $label => $resolver)
                            <tr class="border-b border-subtle">
                                <th scope="row" class="p-4 text-left font-medium text-muted">{{ $label }}</th>
                                @foreach ($trails as $trail)
                                    <td class="p-4 text-primary">{{ $resolver($trail) }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="border-b border-subtle">
                            <th scope="row" class="p-4 text-left font-medium text-muted">Medan</th>
                            @foreach ($trails as $trail)
                                <td class="p-4 text-primary">
                                    {{ collect($trail->terrainTypes())->map->label()->join(', ') ?: '-' }}
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <th scope="row" class="p-4 text-left font-medium text-muted">Status resmi</th>
                            @foreach ($trails as $trail)
                                <td class="p-4">
                                    <x-ui.status-badge :status="$statuses[$trail->id]" />
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
