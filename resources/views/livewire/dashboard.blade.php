<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Selamat datang, {{ auth()->user()->name }}"
            description="Alurnya: lengkapi profil, tentukan rencana, lihat kecocokan jalur, siapkan kebutuhan, lalu periksa kondisi sebelum berangkat." />

        {{--
            Urutannya adalah urutan kepentingan, bukan urutan alur. Yang paling menentukan
            hari ini muncul lebih dulu: pendakian yang sedang berjalan, lalu trip terdekat,
            baru ajakan memulai sesuatu yang baru.
        --}}
        @if (! auth()->user()->hasCompletedProfile())
            <x-ui.card title="Lengkapi profil pendaki"
                subtitle="Rekomendasi jalur membutuhkan data pengalaman dan preferensi Anda.">
                <x-ui.button href="{{ route('onboarding') }}">Isi profil sekarang</x-ui.button>
            </x-ui.card>
        @elseif ($sedangBerjalan)
            <x-ui.card title="Pendakian sedang berlangsung" :subtitle="$trip->name">
                <p class="mb-4 text-sm text-secondary">
                    {{ $trip->trail?->name }}@if ($trip->trail?->mountain), {{ $trip->trail->mountain->name }}@endif
                </p>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('trips.hike', $trip) }}">Buka mode pendakian</x-ui.button>
                    <x-ui.button variant="secondary" href="{{ route('trips.show', $trip) }}">Rincian trip</x-ui.button>
                </div>
            </x-ui.card>
        @elseif ($trip)
            <x-ui.card :title="$hitungMundur" :subtitle="$trip->name">
                <p class="text-sm text-secondary">
                    {{ $trip->trail?->name }}@if ($trip->trail?->mountain), {{ $trip->trail->mountain->name }}@endif
                    &middot; {{ $trip->planned_date->translatedFormat('l, d F Y') }}
                </p>

                @if ($trip->trailIsWithdrawn())
                    <p class="mt-2 text-sm font-medium text-warn-900">
                        Jalur ditarik dari katalog
                    </p>
                @elseif ($trip->readinessIsStale())
                    <p class="mt-2 text-sm font-medium text-warn-900">
                        Status jalur berubah, perlu dinilai ulang
                    </p>
                @elseif ($trip->latestReadinessCheck)
                    <p class="mt-2 text-sm text-secondary">
                        Pemeriksaan terakhir:
                        <span class="font-medium">{{ $trip->latestReadinessCheck->computed_state->label() }}</span>
                    </p>
                @else
                    <p class="mt-2 text-sm text-secondary">
                        Kesiapan trip ini belum pernah diperiksa.
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('trips.readiness', $trip) }}">Cek kesiapan</x-ui.button>
                    <x-ui.button variant="secondary" href="{{ route('trips.preparation', $trip) }}">Daftar persiapan</x-ui.button>
                </div>
            </x-ui.card>
        @else
            <x-ui.card title="Mulai rencana pendakian"
                subtitle="Tentukan target perjalanan Anda untuk melihat jalur yang sesuai.">
                <x-ui.button href="{{ route('goals.create') }}">Buat rencana pendakian</x-ui.button>
            </x-ui.card>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card title="Jalur">
                <a href="{{ route('trails.index') }}" wire:navigate class="text-sm text-brand-700 underline">
                    Jelajahi jalur
                </a>
            </x-ui.card>
            <x-ui.card title="Trip">
                <a href="{{ route('trips.index') }}" wire:navigate class="text-sm text-brand-700 underline">
                    Rencana trip saya
                </a>
            </x-ui.card>
            <x-ui.card title="Riwayat">
                <a href="{{ route('history') }}" wire:navigate class="text-sm text-brand-700 underline">
                    Riwayat pendakian
                </a>
            </x-ui.card>
        </div>

        <x-ui.card>
            <p class="text-sm text-secondary">
                Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu
                gunung atau jalur aman, dan tidak menggantikan informasi resmi dari pengelola.
            </p>
        </x-ui.card>
    </div>
</div>
