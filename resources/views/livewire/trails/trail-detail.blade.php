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

        @php
            $penandaPos = $trail->checkpoints
                ->filter(fn ($pos) => $pos->latitude !== null && $pos->longitude !== null)
                ->sortBy('sequence')
                ->map(fn ($pos) => [
                    'lng' => (float) $pos->longitude,
                    'lat' => (float) $pos->latitude,
                    'label' => $pos->sequence.'. '.$pos->name,
                ])
                ->values()
                ->all();
        @endphp

        {{--
            Peta didahulukan karena ia menjawab pertanyaan pertama pendaki, yaitu jalur
            ini bentuknya seperti apa, sebelum satu pun angka dibaca. Sebelumnya variabel
            $geometry sudah dioper ke view ini dan tidak pernah sekali pun dipakai.
        --}}
        @if ($geometry || $penandaPos !== [])
            <x-ui.map id="peta-jalur" :geometry="$geometry" :markers="$penandaPos"
                :label="'Peta jalur '.$trail->name.' di '.$trail->mountain->name" height="h-96" />
        @else
            {{-- Nadanya mengikuti halaman jalur menunggu: kekosongan ini tahapan, dan
                 yang ditunggu disebut namanya, bukan dibiarkan sebagai kotak kosong. --}}
            <x-ui.card>
                <p class="text-sm text-gray-700">
                    Garis jalur ini belum dimasukkan, jadi belum ada yang dapat digambar di peta.
                    Garisnya dimasukkan pengelola kawasan atau pemandu bersertifikat yang disahkan
                    untuk kawasan ini, biasanya dari rekaman GPS di jalurnya sendiri.
                </p>
            </x-ui.card>
        @endif

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

            {{--
                Profil elevasi menjawab pertanyaan yang tidak dijawab satu angka elevation
                gain: apakah tanjakannya merata atau menumpuk di satu bagian. Dua jalur
                dengan gain yang sama dapat terasa sangat berbeda karenanya.
            --}}
            @if ($trail->elevation_profile)
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-700">Profil elevasi</h3>
                    <x-ui.elevation-profile :profile="$trail->elevation_profile" class="mt-2" />
                </div>
            @endif

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

        {{-- PRD §36 Logistik. Sistem mencatat aturan pihak lain, tidak menggantikannya. --}}
        @if ($permit)
            <x-ui.card class="border-l-4 border-l-slate-700" title="Perizinan pendakian"
                subtitle="Informasi aturan pengelola. Pemesanan tetap dilakukan melalui sistem resmi mereka.">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">Penyelenggara</dt>
                        <dd class="font-medium text-gray-900">{{ $permit->authority }}</dd>
                    </div>
                    @if ($permit->daily_quota)
                        <div>
                            <dt class="text-gray-500">Kuota harian</dt>
                            <dd class="font-medium text-gray-900">{{ $permit->daily_quota }} pendaki</dd>
                        </div>
                    @endif
                    @if ($permit->booking_closes_days_before !== null)
                        <div>
                            <dt class="text-gray-500">Batas pemesanan</dt>
                            <dd class="font-medium text-gray-900">H-{{ $permit->booking_closes_days_before }}</dd>
                        </div>
                    @endif
                    @if ($permit->max_duration_days)
                        <div>
                            <dt class="text-gray-500">Durasi maksimum</dt>
                            <dd class="font-medium text-gray-900">{{ $permit->max_duration_days }} hari</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Pemandu</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $permit->guide_required ? 'Wajib pemandu terdaftar' : 'Tidak diwajibkan' }}
                        </dd>
                    </div>
                </dl>

                @if ($permit->notes)
                    <p class="mt-3 text-sm text-gray-700">{{ $permit->notes }}</p>
                @endif

                @if ($permit->booking_url)
                    <x-ui.button variant="secondary" class="mt-4" :href="$permit->booking_url"
                        :navigate="false" target="_blank" rel="noopener noreferrer">
                        Buka sistem pemesanan resmi</x-ui.button>
                @endif

                <x-ui.freshness :timestamp="$permit->verified_at?->toIso8601String()" prefix="Aturan diverifikasi"
                    :timezone="\App\Support\Timezone::forTrail($trail)" />
                <p class="mt-1 text-xs text-gray-500">
                    Sumber: {{ $permit->source ?? 'Belum dicatat' }}. Aturan perizinan dapat berubah;
                    periksa kembali ke penyelenggara sebelum berangkat.
                </p>
            </x-ui.card>
        @endif

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
        <x-ui.card class="border-l-4 border-l-community-500" title="Laporan kondisi dari komunitas"
            subtitle="Laporan pendaki lain bersifat informasi tambahan dan tidak mengubah status resmi.">
            @if ($conditions['community_context']['available'])
                <ul class="space-y-3">
                    @foreach ($conditions['community_context']['reports'] as $report)
                        <li class="rounded-md border border-gray-100 px-3 py-2 text-sm">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($report->tags() as $tag)
                                    <span class="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">{{ $tag->label() }}</span>
                                @endforeach
                            </div>
                            @if ($report->note)
                                <p class="mt-2 text-gray-700">{{ $report->note }}</p>
                            @endif

                            @if ($report->photo_path)
                                <img src="{{ route('reports.photo', $report) }}"
                                    alt="Foto kondisi jalur dari pendakian {{ $report->hike_date->translatedFormat('d M Y') }}"
                                    loading="lazy"
                                    class="mt-2 max-h-56 rounded-md border border-gray-200">
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
            <x-ui.button variant="secondary" href="{{ route('reports.create', ['trail' => $trail->id]) }}">Laporkan kondisi jalur</x-ui.button>
        </div>
    </div>
</div>
