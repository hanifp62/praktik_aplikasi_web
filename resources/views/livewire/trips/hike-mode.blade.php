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
                <x-ui.button variant="secondary" size="sm" type="button" x-on:click="requestPosition()">
                    Perbarui posisi</x-ui.button>
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

        {{-- Daftar checkpoint di bawah adalah padanan non-visual peta ini. --}}
        <x-ui.map id="hike-map" :geometry="$geometry"
            :markers="collect($checkpoints)
                ->filter(fn ($c) => $c['lat'] !== null)
                ->map(fn ($c) => ['lng' => $c['lng'], 'lat' => $c['lat'], 'label' => $c['sequence'].'. '.$c['name']])
                ->values()
                ->all()"
            :label="'Peta jalur '.$trip->trail->name.' dan posisi checkpoint'" />

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

        <x-ui.button variant="secondary" href="{{ route('trips.show', $trip) }}">Kembali ke detail trip</x-ui.button>
    </div>

    <script>
        function hikeMode() {
            return {
                statusMessage: 'Izin lokasi belum diminta.',
                hentikanPerekaman: null,

                /*
                 * Perekaman jejak berjalan sendiri selama halaman ini terbuka, dan
                 * hanya bila pendaki menyalakannya di profilnya.
                 *
                 * Tidak ada yang dikirim ke server selama pendakian. Titiknya menumpuk
                 * di perangkat, lalu berangkat ketika sinyal kembali: mencoba mengirim
                 * di jalur hanya menghabiskan baterai untuk permintaan yang gagal.
                 */
                init() {
                    @if ($merekamJejak)
                        if (typeof window.muatJejak !== 'function') {
                            return;
                        }

                        window.muatJejak().then((jejak) => {
                            this.hentikanPerekaman = jejak.mulaiMerekam(@json($sesiId));

                            const kirim = () => jejak.kirimJejak(
                                @json($sesiId),
                                @json(route('hike.track', $sesiId)),
                                document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                            ).catch(() => {});

                            // Dicoba saat peramban mengabarkan sinyal kembali, dan sekali
                            // saat halaman dibuka untuk jejak pendakian sebelumnya yang
                            // belum sempat terkirim.
                            window.addEventListener('online', kirim);
                            kirim();
                        }).catch(() => {});
                    @endif
                },

                destroy() {
                    this.hentikanPerekaman?.();
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
            };
        }
    </script>
</div>
