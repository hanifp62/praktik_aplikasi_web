<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kesiapan Pendakian"
            :description="$trip->name.' - '.$trip->trail->name" />

        {{-- Penolakan tidak boleh memakai alert hijau: kotak berhasil yang berisi kabar
             gagal adalah pesan yang membantah dirinya sendiri. --}}
        @if ($penolakan)
            <x-ui.alert variant="warning">{{ $penolakan }}</x-ui.alert>
        @endif

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($preDepartureConfirmed)
            <x-ui.alert variant="success" title="Pre-departure check sudah dikonfirmasi">
                Dicatat {{ \App\Support\Timezone::display($preDepartureConfirmedAt, \App\Support\Timezone::forTrail($trip->trail)) ?? 'sebelumnya' }}.
                Kondisi di bawah tetap dihitung ulang setiap halaman dibuka, jadi periksa kembali sebelum berangkat.
            </x-ui.alert>
        @endif

        @php
            $state = $check?->computed_state;
            $stateClasses = match ($state?->value) {
                'READY' => 'border-brand-300 bg-brand-50 text-brand-900',
                'NEEDS_PREPARATION' => 'border-warn-300 bg-warn-50 text-warn-900',
                'NOT_RECOMMENDED' => 'border-danger-300 bg-danger-50 text-danger-900',
                default => 'border-gray-300 bg-gray-50 text-gray-900',
            };
        @endphp

        <section class="rounded-lg border p-5 {{ $stateClasses }}">
            <h2 class="text-lg font-semibold">{{ $state?->label() ?? 'Belum dinilai' }}</h2>
            <p class="mt-1 text-sm">
                Penilaian ini menggabungkan kecocokan jalur, kelengkapan persiapan, dan kondisi terkini.
                Sistem tidak menyatakan bahwa suatu pendakian aman.
            </p>
            @if ($check)
                <p class="mt-2 text-xs">Dihitung {{ $check->computed_at->translatedFormat('d M Y H:i') }}</p>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card title="Route fit">
                <p class="text-sm text-gray-900">
                    {{ \App\Enums\RouteFitLabel::tryFrom($check->route_fit_snapshot['label'] ?? '')?->label() ?? 'Tidak dinilai' }}
                </p>
            </x-ui.card>

            <x-ui.card title="Persiapan">
                <p class="text-sm text-gray-900">{{ $check->preparation_state['completion_percent'] ?? 0 }}% dikonfirmasi</p>
                <p class="mt-1 text-xs text-gray-600">
                    {{ count($check->preparation_state['critical_outstanding'] ?? []) }} item kritis belum dikonfirmasi
                </p>
            </x-ui.card>

            <x-ui.card title="Status resmi">
                <x-ui.status-badge
                    :status="\App\Enums\OfficialStatusValue::tryFrom($check->official_status_snapshot['status'] ?? 'UNKNOWN')" />
                <p class="mt-2 text-xs text-gray-600">
                    Sumber: {{ $check->official_status_snapshot['source'] ?? 'Belum tercatat' }}
                </p>
            </x-ui.card>
        </div>

        <x-ui.card title="Penjelasan">
            @foreach (['reasons' => 'Dasar penilaian', 'outstanding' => 'Yang belum selesai', 'condition_warnings' => 'Catatan kondisi'] as $key => $heading)
                @php $lines = $check->explanation[$key] ?? []; @endphp
                @if ($lines)
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $heading }}</h3>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-gray-700">
                            @foreach ($lines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </x-ui.card>

        @php $weather = $check->condition_snapshot['weather'] ?? []; @endphp
        <x-ui.card title="Cuaca area sekitar jalur">
            @if ($weather['available'] ?? false)
                <p class="text-sm text-gray-700">Area referensi: {{ $weather['reference_area'] ?? 'Tidak dicatat' }}</p>
                <p class="mt-1 text-xs text-gray-500">Sumber: {{ $weather['source'] ?? 'BMKG' }}</p>
                <x-ui.freshness :state="$weather['freshness'] ?? null" :timestamp="$weather['fetched_at'] ?? null" :timezone="$weather['timezone'] ?? null" />
            @else
                <p class="text-sm text-gray-600">{{ $weather['message'] ?? 'Data cuaca tidak tersedia.' }}</p>
            @endif
        </x-ui.card>

        <div class="flex flex-wrap gap-3">
            <x-ui.button variant="secondary" size="sm" wire:click="recompute">
                Hitung ulang</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('trips.preparation', $trip) }}">Kembali ke persiapan</x-ui.button>
            @if ($state !== \App\Enums\ReadinessState::NOT_RECOMMENDED)
                <x-ui.button wire:click="confirmPreDeparture">
                    {{ $preDepartureConfirmed ? 'Konfirmasi ulang pre-departure check' : 'Konfirmasi pre-departure check' }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" href="{{ route('trips.show', $trip) }}">Detail trip</x-ui.button>
        </div>
    </div>
</div>
