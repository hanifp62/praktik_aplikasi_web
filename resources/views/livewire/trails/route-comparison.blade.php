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
            <div class="overflow-x-auto rounded-lg bg-white">
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
                        {{--
                            Barisnya dimensi ongkos, bukan daftar kolom basis data.

                            Tujuan pengguna yang sebenarnya, menurut riset AllTrails, adalah
                            memperkirakan berapa waktu dan tenaga yang harus ia keluarkan.
                            Urutannya mengikuti itu: yang paling menentukan keputusan lebih
                            dulu.
                        --}}
                        @php
                            $rows = [
                                'Waktu' => fn ($t) => \App\Support\Durasi::panjang($t->estimated_duration_minutes),
                                'Jarak' => fn ($t) => $t->distance_km !== null ? number_format((float) $t->distance_km, 1, ',', '.').' km' : '-',
                                'Tanjakan' => fn ($t) => $t->elevation_gain_m !== null ? number_format($t->elevation_gain_m, 0, ',', '.').' m' : '-',
                                'Kecuraman' => fn ($t) => ($t->distance_km && $t->elevation_gain_m)
                                    ? number_format(round($t->elevation_gain_m / (float) $t->distance_km), 0, ',', '.').' m/km'
                                    : '-',
                                'Tuntutan teknis' => fn ($t) => $t->technical_demand->label(),
                                'Kerumitan navigasi' => fn ($t) => $t->navigation_complexity->label(),
                                'Air' => fn ($t) => $t->water_availability->label(),
                                'Berkemah' => fn ($t) => $t->camping_available ? 'Bisa' : 'Tidak',
                            ];
                        @endphp

                        <tr class="border-b border-subtle">
                            <th scope="row" class="py-3 pr-4 text-left font-medium text-secondary">Kecocokan untuk Anda</th>
                            @foreach ($trails as $trail)
                                <td class="py-3 pr-4">
                                    @if (isset($ringkasanFit[$trail->id]))
                                        <x-ui.fit-line :summary="$ringkasanFit[$trail->id]" />
                                    @else
                                        <span class="text-sm text-muted">Belum dinilai</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        {{-- daftar tetap: pilihan yang ditetapkan di kode, tidak pernah kosong. --}}
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
