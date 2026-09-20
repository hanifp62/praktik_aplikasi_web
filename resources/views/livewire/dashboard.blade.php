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
            {{--
                Pendakian berikutnya adalah hero halaman ini, bukan kartu ketiga.

                Dulu hitung mundurnya dipasang sebagai judul kartu, sehingga fakta paling
                mendesak di halaman ini berukuran sama dengan judul "Jalur" dan "Trip" di
                bawahnya. Angkanya sekarang dibuat sebesar perannya.

                Tanpa label kecil di atas judul dan tanpa huruf kapital bertarikan lebar:
                keduanya pola yang muncul di hampir setiap antarmuka hasil AI, dan
                tanggalnya sendiri sudah mengatakan apa yang dilihat pembaca.
            --}}
            <section class="rounded-lg border border-subtle bg-surface p-6">
                <p class="text-sm text-secondary">{{ $trip->planned_date->translatedFormat('l, d F Y') }}</p>

                <p class="mt-1 flex flex-wrap items-baseline gap-x-3">
                    @if ($hitungMundur['angka'] !== null)
                        <span data-angka class="text-4xl font-semibold leading-none text-primary">{{ $hitungMundur['angka'] }}</span>
                        <span class="text-lg text-secondary">{{ $hitungMundur['kata'] }}</span>
                    @else
                        <span class="text-3xl font-semibold leading-tight text-primary">{{ $hitungMundur['kata'] }}</span>
                    @endif
                </p>

                <h2 class="mt-4 text-lg font-semibold text-primary">{{ $trip->name }}</h2>
                <p class="text-sm text-secondary">
                    {{ $trip->trail?->name }}@if ($trip->trail?->mountain), {{ $trip->trail->mountain->name }}@endif
                </p>

                @if ($trip->trailIsWithdrawn())
                    <p class="mt-3 text-sm font-medium text-warn-900">Jalur ditarik dari katalog</p>
                @elseif ($trip->readinessIsStale())
                    <p class="mt-3 text-sm font-medium text-warn-900">Status jalur berubah, perlu dinilai ulang</p>
                @elseif ($trip->latestReadinessCheck)
                    <p class="mt-3 text-sm text-secondary">
                        Pemeriksaan terakhir:
                        <span class="font-medium text-primary">{{ $trip->latestReadinessCheck->computed_state->label() }}</span>
                    </p>
                @else
                    <p class="mt-3 text-sm text-secondary">Kesiapan trip ini belum pernah diperiksa.</p>
                @endif

                <div class="mt-5 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('trips.readiness', $trip) }}">Cek kesiapan</x-ui.button>
                    <x-ui.button variant="secondary" href="{{ route('trips.preparation', $trip) }}">Daftar persiapan</x-ui.button>
                </div>
            </section>
        @else
            <x-ui.card title="Mulai rencana pendakian"
                subtitle="Tentukan target perjalanan Anda untuk melihat jalur yang sesuai.">
                <x-ui.button href="{{ route('goals.create') }}">Buat rencana pendakian</x-ui.button>
            </x-ui.card>
        @endif

        {{--
            Tiga kartu penuh yang isinya masing-masing satu tautan, dan ketiga tautan itu
            sudah berdiri di menu utama tepat di atasnya. Kartu adalah permukaan untuk isi;
            memakainya untuk membungkus satu tautan menggambar bingkai di sekeliling
            sesuatu yang tidak membutuhkan bingkai, dan mengulang menu di badan halaman
            membuat halaman terlihat penuh tanpa menambah satu keterangan pun.

            Yang tersisa satu baris tautan biasa, untuk yang memang ingin melompat dari
            sini tanpa naik ke menu.
        --}}
        <nav aria-label="Lanjutkan ke" class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <a href="{{ route('trails.index') }}" wire:navigate class="text-brand-700 underline hover:text-brand-900">Jelajahi jalur</a>
            <a href="{{ route('trips.index') }}" wire:navigate class="text-brand-700 underline hover:text-brand-900">Rencana trip saya</a>
            <a href="{{ route('history') }}" wire:navigate class="text-brand-700 underline hover:text-brand-900">Riwayat pendakian</a>
        </nav>

        {{--
            Penafian, bukan isi. Dulu ia kartu, jadi bobot visualnya sama dengan pendakian
            yang sedang berlangsung. Ia tetap wajib ada dan tetap terbaca, hanya tidak lagi
            menuntut perhatian yang sama dengan hal yang menentukan keselamatan hari itu.
        --}}
        <p class="border-t border-subtle pt-4 text-sm text-muted">
            Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu
            gunung atau jalur aman, dan tidak menggantikan informasi resmi dari pengelola.
        </p>
    </div>
</div>
