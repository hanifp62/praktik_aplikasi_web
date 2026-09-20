@props([
    'id' => 'peta',
    'geometry' => null,
    'markers' => [],
    'label' => 'Peta jalur',
    'height' => 'h-80',
])

@php
    $penanda = collect($markers)
        ->filter(fn ($m) => isset($m['lng'], $m['lat']))
        ->map(fn ($m) => [
            'lng' => (float) $m['lng'],
            'lat' => (float) $m['lat'],
            'label' => (string) ($m['label'] ?? ''),
        ])
        ->values();

    $adaIsi = $geometry !== null || $penanda->isNotEmpty();

    // Konfigurasi dibaca di sini, bukan dioper tiap pemanggil. Tanpa ini setiap komponen
    // Livewire yang memakai peta harus mengulang blok konfigurasi yang sama, dan yang
    // lupa mengulangnya menghasilkan peta kosong tanpa satu pun pesan galat.
    $konfigurasi = [
        'styleUrl' => config('hiking.map.style_url'),
        'rasterTiles' => array_values(array_filter((array) config('hiking.map.raster_tiles'))),
        'attribution' => config('hiking.map.attribution'),
        'maxZoom' => (int) config('hiking.map.max_zoom'),
    ];
@endphp

@if ($adaIsi)
    <div {{ $attributes->merge(['class' => 'space-y-1']) }}>
        <div class="overflow-hidden rounded-lg border border-gray-200">
            {{--
                Peta adalah wilayah interaktif, bukan gambar. role="img" menuntut teks
                alternatif yang tidak mungkin diberikan untuk peta yang dapat digeser,
                dan menyembunyikan isinya dari pembaca layar. Padanan non-visualnya
                adalah daftar pos yang selalu menyertai peta ini di setiap halaman.
            --}}
            <div id="{{ $id }}" class="{{ $height }} w-full bg-gray-100" role="region"
                aria-label="{{ $label }}"></div>
        </div>

        {{-- Atribusi bukan hiasan: syarat pemakaian sumber tile-nya. --}}
        <p class="text-xs text-gray-500">Peta: {{ $konfigurasi['attribution'] }}</p>

        <script>
            (() => {
                const wadah = document.getElementById(@json($id));

                if (!wadah || typeof window.muatPeta !== 'function') {
                    return;
                }

                const konfigurasi = @json($konfigurasi);
                const geometri = @json($geometry);
                const penanda = @json($penanda);

                // Kegagalan memuat peta diakui, bukan didiamkan. Layar ini dibaca justru
                // ketika sinyalnya paling tipis, dan wadah abu-abu yang diam terbaca
                // sebagai aplikasi rusak. Menyebut bahwa daftar pos di bawah tetap
                // dapat dipakai mengubah kebuntuan menjadi keterangan.
                const gagal = () => {
                    wadah.innerHTML =
                        '<p class="p-4 text-sm text-gray-700">Peta gagal dimuat. '
                        + 'Keterangan jalur dan daftar pos di bawah tetap dapat dipakai.</p>';
                };

                // Dimuat malas. Memuat MapLibre di setiap halaman mengembalikan beban
                // 900 kB yang sudah pernah ditekan ke 57 kB per halaman.
                window.muatPeta().catch(() => null).then((maplibregl) => {
                    if (!maplibregl) {
                        gagal();

                        return;
                    }

                    const gaya = konfigurasi.styleUrl || {
                        version: 8,
                        sources: {
                            basemap: {
                                type: 'raster',
                                tiles: konfigurasi.rasterTiles,
                                tileSize: 256,
                                maxzoom: konfigurasi.maxZoom,
                                attribution: konfigurasi.attribution,
                            },
                        },
                        layers: [{ id: 'basemap', type: 'raster', source: 'basemap' }],
                    };

                    const peta = new maplibregl.Map({
                        container: @json($id),
                        style: gaya,
                        center: penanda.length ? [penanda[0].lng, penanda[0].lat] : [113, -2],
                        zoom: penanda.length ? 12 : 4,
                        maxZoom: konfigurasi.maxZoom,
                    });

                    peta.addControl(new maplibregl.NavigationControl(), 'top-right');

                    peta.on('load', () => {
                        if (geometri) {
                            peta.addSource('jalur', {
                                type: 'geojson',
                                data: { type: 'Feature', geometry: geometri },
                            });
                            peta.addLayer({
                                id: 'garis-jalur',
                                type: 'line',
                                source: 'jalur',
                                paint: { 'line-color': '#047857', 'line-width': 3 },
                            });

                            // Dibingkai ke jalurnya, bukan dibiarkan pada titik tengah
                            // Indonesia: peta yang terbuka di laut Jawa memaksa pendaki
                            // mencari jalurnya sendiri sebelum dapat membacanya.
                            const kotak = geometri.coordinates.reduce(
                                (batas, titik) => batas.extend(titik),
                                new maplibregl.LngLatBounds(geometri.coordinates[0], geometri.coordinates[0])
                            );

                            peta.fitBounds(kotak, { padding: 40, duration: 0 });
                        }

                        penanda.forEach((titik) => {
                            const tanda = new maplibregl.Marker().setLngLat([titik.lng, titik.lat]);

                            if (titik.label) {
                                tanda.setPopup(new maplibregl.Popup().setText(titik.label));
                            }

                            tanda.addTo(peta);
                        });
                    });
                });
            })();
        </script>

        {{-- Nama penanda ikut tertulis agar pembaca layar dan mesin pencari mendapat
             isinya tanpa harus menjalankan peta. --}}
        <span class="sr-only">{{ $penanda->pluck('label')->filter()->implode(', ') }}</span>
    </div>
@endif
