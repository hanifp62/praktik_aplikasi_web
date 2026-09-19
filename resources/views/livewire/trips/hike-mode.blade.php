<div class="py-6" x-data="hikeMode()" x-init="init()">
    <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
        {{-- Hike mode shows the minimum needed while moving (PRD §54). --}}
        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h1 class="text-lg font-semibold text-gray-900">{{ $trip->trail->name }}</h1>
            <p class="text-sm text-gray-600">{{ $trip->trail->mountain->name }}</p>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="text-sm font-medium text-gray-500">Checkpoint berikutnya</h2>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                {{ $nextCheckpoint['name'] ?? 'Belum ditentukan' }}
            </p>
            <p class="mt-1 text-sm text-gray-700">
                @if ($distanceToNextMeters !== null)
                    Sekitar {{ $distanceToNextMeters >= 1000 ? round($distanceToNextMeters / 1000, 2).' km' : $distanceToNextMeters.' m' }}
                    dari posisi Anda
                @else
                    Jarak belum dapat dihitung
                @endif
            </p>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-gray-500">Posisi Anda</h2>
                <button type="button" x-on:click="requestPosition()"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    Perbarui posisi
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-700" x-text="statusMessage"></p>
            @if ($latitude !== null)
                <p class="mt-1 text-xs text-gray-500">
                    {{ number_format($latitude, 5) }}, {{ number_format($longitude, 5) }}
                </p>
            @endif
            <p class="mt-2 text-xs text-gray-500">
                Lokasi Anda hanya dipakai di halaman ini dan tidak dibagikan ke pengguna lain.
            </p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div id="hike-map" class="h-80 w-full" role="img"
                aria-label="Peta jalur {{ $trip->trail->name }} dan posisi checkpoint"></div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="text-sm font-medium text-gray-500">Daftar checkpoint</h2>
            <ol class="mt-2 space-y-1 text-sm">
                @foreach ($checkpoints as $checkpoint)
                    <li @class([
                        'rounded px-2 py-1',
                        'bg-brand-50 font-medium text-brand-900' => ($nextCheckpoint['id'] ?? null) === $checkpoint['id'],
                        'text-gray-700' => ($nextCheckpoint['id'] ?? null) !== $checkpoint['id'],
                    ])>
                        {{ $checkpoint['sequence'] }}. {{ $checkpoint['name'] }}
                    </li>
                @endforeach
            </ol>
        </div>

        <a href="{{ route('trips.show', $trip) }}" wire:navigate
            class="inline-block rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Kembali ke detail trip
        </a>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/maplibre-gl.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/maplibre-gl.js"></script>
    @endpush

    <script>
        function hikeMode() {
            return {
                statusMessage: 'Izin lokasi belum diminta.',
                init() {
                    this.renderMap();
                },
                requestPosition() {
                    if (!navigator.geolocation) {
                        this.statusMessage = 'Browser Anda tidak mendukung layanan lokasi.';
                        return;
                    }

                    this.statusMessage = 'Mengambil posisi...';

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.statusMessage = 'Posisi diperbarui.';
                            @this.call('updatePosition', position.coords.latitude, position.coords.longitude);
                        },
                        () => {
                            this.statusMessage = 'Posisi tidak dapat diambil. Periksa izin lokasi browser.';
                        },
                        { enableHighAccuracy: true, timeout: 15000 }
                    );
                },
                renderMap() {
                    const container = document.getElementById('hike-map');

                    if (!container || typeof maplibregl === 'undefined') {
                        return;
                    }

                    const checkpoints = @json(collect($checkpoints)->filter(fn ($c) => $c['lat'] !== null)->values());
                    const geometry = @json($geometry);

                    if (checkpoints.length === 0 && !geometry) {
                        container.innerHTML = '<p class="p-4 text-sm text-gray-600">Data peta jalur belum tersedia.</p>';
                        return;
                    }

                    const center = checkpoints.length
                        ? [checkpoints[0].lng, checkpoints[0].lat]
                        : geometry.coordinates[0];

                    const map = new maplibregl.Map({
                        container: 'hike-map',
                        style: 'https://demotiles.maplibre.org/style.json',
                        center: center,
                        zoom: 12,
                    });

                    map.on('load', () => {
                        if (geometry) {
                            map.addSource('trail', { type: 'geojson', data: { type: 'Feature', geometry: geometry } });
                            map.addLayer({
                                id: 'trail-line',
                                type: 'line',
                                source: 'trail',
                                paint: { 'line-color': '#047857', 'line-width': 3 },
                            });
                        }

                        checkpoints.forEach((checkpoint) => {
                            new maplibregl.Marker()
                                .setLngLat([checkpoint.lng, checkpoint.lat])
                                .setPopup(new maplibregl.Popup().setText(checkpoint.sequence + '. ' + checkpoint.name))
                                .addTo(map);
                        });
                    });
                },
            };
        }
    </script>
</div>
