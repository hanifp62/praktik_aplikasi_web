<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Progres Pendakian"
            description="Catatan perjalanan Anda sendiri. Tidak dibandingkan dengan siapa pun." />

        @if ($angka['pendakian'] === 0)
            {{--
                Keadaan awal setiap pendaki, dan ia tidak boleh terbaca sebagai
                kegagalan. Nol di sini artinya belum mulai, bukan tertinggal.
            --}}
            <x-ui.card title="Belum ada pendakian yang tercatat">
                <p class="text-sm text-gray-700">
                    Halaman ini terisi sendiri setelah Anda menyelesaikan pendakian pertama dan
                    menandainya selesai di halaman trip.
                </p>
                <x-ui.button variant="secondary" href="{{ route('trails.index') }}" class="mt-3">
                    Telusuri jalur
                </x-ui.button>
            </x-ui.card>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-ui.card>
                    <p class="text-xs text-gray-500">Pendakian</p>
                    <p data-angka class="mt-1 text-2xl font-semibold text-gray-900">{{ $angka['pendakian'] }}</p>
                    @if ($angka['pendakian'] > $angka['tuntas'])
                        {{-- Pendakian yang dibatalkan disebut apa adanya, tanpa nada
                             menghukum. Membatalkan karena cuaca adalah keputusan yang
                             benar dan tetap merupakan pendakian. --}}
                        <p class="mt-1 text-xs text-gray-600">{{ $angka['tuntas'] }} sampai puncak</p>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <p class="text-xs text-gray-500">Gunung berbeda</p>
                    <p data-angka class="mt-1 text-2xl font-semibold text-gray-900">{{ $angka['gunung'] }}</p>
                    <p class="mt-1 text-xs text-gray-600">{{ $angka['jalur'] }} jalur</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-xs text-gray-500">Total elevation gain</p>
                    <p data-angka class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ number_format($angka['elevasi_total_m'], 0, ',', '.') }} m
                    </p>
                    @if ($angka['elevasi_belum_diketahui'] > 0)
                        {{-- Angkanya tidak boleh terbaca sebagai total yang lengkap
                             ketika sebagian jalurnya belum punya data (§95). --}}
                        <p class="mt-1 text-xs text-warn-900">
                            {{ $angka['elevasi_belum_diketahui'] }} jalur belum ada datanya, jadi belum ikut terhitung
                        </p>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <p class="text-xs text-gray-500">Tanjakan terbesar</p>
                    <p data-angka class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ $angka['elevasi_tertinggi_m'] !== null
                            ? number_format($angka['elevasi_tertinggi_m'], 0, ',', '.').' m'
                            : 'belum ada' }}
                    </p>
                </x-ui.card>
            </div>

            @if ($gunung !== [])
                <x-ui.card title="Gunung yang sudah Anda daki">
                    <x-ui.map id="peta-progres" :markers="$gunung"
                        label="Peta gunung yang sudah Anda daki" height="h-96" class="mt-2" />
                </x-ui.card>
            @endif

            <x-ui.card title="Riwayat lengkap">
                <p class="text-sm text-gray-700">
                    Tiap pendakian punya halaman hasilnya sendiri berisi jalur, durasi, dan catatan Anda.
                </p>
                <x-ui.button variant="secondary" href="{{ route('history') }}" class="mt-3">
                    Buka riwayat pendakian
                </x-ui.button>
            </x-ui.card>
        @endif
    </div>
</div>
