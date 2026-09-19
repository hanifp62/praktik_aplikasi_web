<div class="py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$trail->name" :description="$trail->description">
            <p class="mt-1 text-sm text-gray-600">
                {{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }} &middot;
                {{ $trail->mountain->elevation_mdpl }} mdpl
            </p>
            @if ($fit)
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <x-ui.fit-badge :label="$fit->label" />
                    <span class="text-sm text-gray-600">{{ $fit->label?->description() }}</span>
                </div>
            @endif
        </x-ui.page-header>

        <x-ui.card title="Karakteristik jalur"
            subtitle="Kesulitan dinilai dari beberapa dimensi, bukan hanya ketinggian gunung.">
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-gray-500">Jarak</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->distance_km ?? '-' }} km</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Elevation gain</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->elevation_gain_m ?? '-' }} m</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Elevation loss</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->elevation_loss_m ?? '-' }} m</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Estimasi durasi</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $trail->estimated_duration_minutes ? round($trail->estimated_duration_minutes / 60, 1).' jam' : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tingkat teknis</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->technical_demand->label() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Navigasi</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->navigation_complexity->label() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Sumber air</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->water_availability->label() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Camping</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->camping_available ? 'Tersedia' : 'Tidak tersedia' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Titik awal</dt>
                    <dd class="font-medium text-gray-900">{{ $trail->starting_point ?? '-' }}</dd>
                </div>
            </dl>

            @if ($trail->terrainTypes())
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-700">Karakter medan</h3>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($trail->terrainTypes() as $terrain)
                            <li class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">{{ $terrain->label() }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.card>

        {{-- OFFICIAL: authoritative block, visually distinct from community reports (PRD §92). --}}
        <x-ui.card class="border-l-4 border-l-slate-700" title="Status resmi">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.status-badge
                    :status="\App\Enums\OfficialStatusValue::from($conditions['official_status']['status'])"
                    :scope="$conditions['official_status']['scope']" />
                <span class="text-sm text-gray-600">
                    Sumber: {{ $conditions['official_status']['source'] ?? 'Belum tercatat' }}
                </span>
            </div>
            @if ($conditions['official_status']['reason'])
                <p class="mt-2 text-sm text-gray-700">{{ $conditions['official_status']['reason'] }}</p>
            @endif
            <x-ui.freshness :timestamp="$conditions['official_status']['verified_at'] ?? $conditions['official_status']['published_at']"
                prefix="Diverifikasi" />
            <p class="mt-2 text-xs text-gray-500">
                Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.
            </p>
        </x-ui.card>

        <x-ui.card title="Prakiraan cuaca area sekitar jalur"
            subtitle="Prakiraan berbasis wilayah administrasi, bukan kondisi puncak.">
            @if ($conditions['weather_context']['available'])
                <p class="text-sm text-gray-600">
                    Area referensi: {{ $conditions['weather_context']['reference_area'] ?? 'Tidak dicatat' }}
                </p>
                @if ($conditions['weather_context']['message'])
                    <p class="mt-2 rounded-md bg-warn-50 px-3 py-2 text-sm text-warn-900">
                        {{ $conditions['weather_context']['message'] }}
                    </p>
                @endif
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <caption class="sr-only">Prakiraan cuaca per tiga jam</caption>
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th scope="col" class="py-2 pr-4">
                                    Waktu ({{ $conditions['weather_context']['timezone_label'] ?? 'WIB' }})
                                </th>
                                <th scope="col" class="py-2 pr-4">Cuaca</th>
                                <th scope="col" class="py-2 pr-4">Suhu</th>
                                <th scope="col" class="py-2 pr-4">Kelembapan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($conditions['weather_context']['forecast'], 0, 8) as $snapshot)
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4">
                                        {{ $snapshot->forecast_at
                                            ->copy()
                                            ->setTimezone($conditions['weather_context']['timezone'] ?? \App\Support\Timezone::DEFAULT)
                                            ->translatedFormat('d M H:i') }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $snapshot->weather_description ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $snapshot->temperature_c ?? '-' }} &deg;C</td>
                                    <td class="py-2 pr-4">{{ $snapshot->humidity_percent ?? '-' }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Sumber data cuaca: {{ $conditions['weather_context']['source'] }}</p>
                <x-ui.freshness :state="$conditions['weather_context']['freshness']"
                    :timestamp="$conditions['weather_context']['fetched_at']"
                    :timezone="$conditions['weather_context']['timezone'] ?? null" />
            @else
                <p class="text-sm text-gray-600">{{ $conditions['weather_context']['message'] }}</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Checkpoint">
            @if ($trail->checkpoints->isEmpty())
                <p class="text-sm text-gray-600">Data checkpoint belum tersedia untuk jalur ini.</p>
            @else
                <ol class="space-y-2">
                    @foreach ($trail->checkpoints as $checkpoint)
                        <li class="flex items-start gap-3 rounded-md border border-gray-100 px-3 py-2 text-sm">
                            <span class="mt-0.5 rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                {{ $checkpoint->sequence }}
                            </span>
                            <span>
                                <span class="font-medium text-gray-900">{{ $checkpoint->name }}</span>
                                <span class="text-gray-600">&middot; {{ $checkpoint->checkpoint_type->label() }}</span>
                                @if ($checkpoint->elevation_m)
                                    <span class="text-gray-600">&middot; {{ $checkpoint->elevation_m }} mdpl</span>
                                @endif
                                @if ($checkpoint->notes)
                                    <span class="block text-gray-600">{{ $checkpoint->notes }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>

        @if ($trail->segments->isNotEmpty())
            <x-ui.card title="Segmen jalur">
                <ol class="space-y-2 text-sm">
                    @foreach ($trail->segments as $segment)
                        <li class="rounded-md border border-gray-100 px-3 py-2">
                            <span class="font-medium text-gray-900">{{ $segment->sequence }}. {{ $segment->name }}</span>
                            <span class="text-gray-600">
                                &middot; {{ $segment->distance_km ?? '-' }} km &middot; gain {{ $segment->elevation_gain_m ?? '-' }} m
                                &middot; teknis {{ $segment->technical_demand->label() }}
                            </span>
                            @if ($segment->description)
                                <p class="mt-1 text-gray-600">{{ $segment->description }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        @endif

        {{-- COMMUNITY: supplementary field intelligence, never official status (PRD §49). --}}
        <x-ui.card class="border-l-4 border-l-sky-500" title="Laporan kondisi dari komunitas"
            subtitle="Laporan pendaki lain bersifat informasi tambahan dan tidak mengubah status resmi.">
            @if ($conditions['community_context']['available'])
                <ul class="space-y-3">
                    @foreach ($conditions['community_context']['reports'] as $report)
                        <li class="rounded-md border border-gray-100 px-3 py-2 text-sm">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($report->tags() as $tag)
                                    <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs text-sky-900">{{ $tag->label() }}</span>
                                @endforeach
                            </div>
                            @if ($report->note)
                                <p class="mt-2 text-gray-700">{{ $report->note }}</p>
                            @endif
                            <p class="mt-1 text-xs text-gray-500">
                                Dilaporkan {{ $report->created_at->diffForHumans() }} berdasarkan pendakian
                                {{ $report->hike_date->translatedFormat('d M Y') }}
                                @if ($report->segment)
                                    &middot; segmen {{ $report->segment->name }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">{{ $conditions['community_context']['message'] }}</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Sumber data">
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500">Data jalur</dt>
                    <dd class="text-gray-900">
                        {{ $trail->dataSource?->source_name ?? 'Belum tercatat' }}
                        @if ($trail->dataSource?->source_url)
                            &middot; <a class="text-brand-700 underline" href="{{ $trail->dataSource->source_url }}"
                                rel="noopener noreferrer" target="_blank">tautan sumber</a>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Status resmi</dt>
                    <dd class="text-gray-900">{{ $conditions['official_status']['source'] ?? 'Belum tercatat' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Cuaca</dt>
                    <dd class="text-gray-900">{{ $conditions['weather_context']['source'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <div class="flex flex-wrap gap-3">
            <x-ui.button wire:click="createTrip">Buat rencana trip</x-ui.button>
            <a href="{{ route('reports.create', ['trail' => $trail->id]) }}" wire:navigate
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                Laporkan kondisi jalur
            </a>
        </div>
    </div>
</div>
