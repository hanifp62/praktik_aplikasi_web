<div class="py-8">
    <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Progres Pendakian"
            description="Catatan perjalanan Anda sendiri. Tidak dibandingkan dengan siapa pun." />

        {{--
            Pendakian dan laporan adalah dua sumbangan yang saling bebas. Seseorang dapat
            menulis laporan kondisi tanpa pernah menandai satu trip pun selesai, dan
            mengurung blok laporan di dalam syarat jumlah pendakian membuat sumbangannya
            tidak terlihat sama sekali.
        --}}
        @if ($angka['pendakian'] === 0 && $angka['laporan_terbit'] === 0)
            {{--
                Keadaan awal setiap pendaki, dan ia tidak boleh terbaca sebagai
                kegagalan. Nol di sini artinya belum mulai, bukan tertinggal.
            --}}
            <x-ui.card title="Belum ada pendakian yang tercatat">
                <p class="text-sm text-secondary">
                    Halaman ini terisi sendiri setelah Anda menyelesaikan pendakian pertama dan
                    menandainya selesai di halaman trip.
                </p>
                <x-ui.button variant="secondary" href="{{ route('trails.index') }}" class="mt-3">
                    Telusuri jalur
                </x-ui.button>
            </x-ui.card>
        @else
            {{--
                Peta didahulukan karena ia muatan utama halaman ini.
                Empat angka besar berlabel kecil adalah susunan bawaan hampir setiap
                dasbor, dan susunan itu tidak memilih apa pun: empat hal setara berarti
                tidak ada yang utama. Peta menunjukkan hal yang tidak dapat dikatakan
                angka, yaitu di mana saja orang ini pernah berada, dan angkanya
                menjelaskan peta itu alih-alih menggantikannya.
            --}}
            @if ($gunung !== [])
                <figure>
                    <x-ui.map id="peta-progres" :markers="$gunung"
                        label="Peta gunung yang sudah Anda daki" height="h-[26rem]" />

                    <figcaption class="mt-3 text-sm text-secondary">
                        {{ count($gunung) === 1
                            ? 'Satu gunung yang sudah Anda daki.'
                            : count($gunung).' gunung yang sudah Anda daki.' }}
                        @if ($angka['gunung'] > count($gunung))
                            {{-- Gunung tanpa koordinat tidak ditempatkan di tengah laut demi
                                 melengkapi peta, tetapi ketidakhadirannya disebut supaya
                                 hitungannya tidak terbaca bertentangan. --}}
                            <span class="text-muted">
                                {{ $angka['gunung'] - count($gunung) }} gunung lainnya belum berkoordinat,
                                jadi belum dapat digambar.
                            </span>
                        @endif
                    </figcaption>
                </figure>
            @endif

            {{-- Satu kelompok angka, bukan empat klaim setara. --}}
            @if ($angka['pendakian'] > 0)
                <dl class="grid grid-cols-2 gap-x-8 gap-y-6 border-y border-subtle py-6 sm:grid-cols-4">
                <div>
                    <dt class="text-sm text-muted">Pendakian</dt>
                    <dd data-angka class="mt-1 text-2xl font-semibold text-primary">{{ $angka['pendakian'] }}</dd>
                    @if ($angka['pendakian'] > $angka['tuntas'])
                        {{-- Pendakian yang dibatalkan disebut apa adanya, tanpa nada
                             menghukum. Membatalkan karena cuaca adalah keputusan yang
                             benar dan tetap merupakan pendakian. --}}
                        <p class="mt-1 text-xs text-secondary">{{ $angka['tuntas'] }} sampai puncak</p>
                    @endif
                </div>

                <div>
                    <dt class="text-sm text-muted">Gunung berbeda</dt>
                    <dd data-angka class="mt-1 text-2xl font-semibold text-primary">{{ $angka['gunung'] }}</dd>
                    <p class="mt-1 text-xs text-secondary">{{ $angka['jalur'] }} jalur</p>
                </div>

                <div>
                    <dt class="text-sm text-muted">Total elevation gain</dt>
                    <dd data-angka class="mt-1 text-2xl font-semibold text-primary">
                        {{ number_format($angka['elevasi_total_m'], 0, ',', '.') }} m
                    </dd>
                    @if ($angka['elevasi_belum_diketahui'] > 0)
                        {{-- Angkanya tidak boleh terbaca sebagai total yang lengkap
                             ketika sebagian jalurnya belum punya data (§95). --}}
                        <p class="mt-1 text-xs text-warn-900">
                            {{ $angka['elevasi_belum_diketahui'] }} jalur belum ada datanya
                        </p>
                    @endif
                </div>

                <div>
                    <dt class="text-sm text-muted">Tanjakan terbesar</dt>
                    <dd data-angka class="mt-1 text-2xl font-semibold text-primary">
                        {{ $angka['elevasi_tertinggi_m'] !== null
                            ? number_format($angka['elevasi_tertinggi_m'], 0, ',', '.').' m'
                            : 'belum ada' }}
                    </dd>
                </div>
            </dl>
            @endif

            {{--
                Dikalimatkan sebagai dampak pada orang lain, bukan sebagai skor.
                "2 pendaki menyatakan laporan Anda menolong" menyebut orang; "2 poin"
                menyebut angka. Yang pertama alasan menulis laporan berikutnya, yang
                kedua alasan menulis laporan sebanyak-banyaknya.
            --}}
            <section>
                <h2 class="text-base font-semibold text-primary">Laporan kondisi</h2>

                @if ($angka['laporan_terbit'] === 0)
                    {{-- Nol tidak ditampilkan sebagai angka: pendaki yang belum pernah
                         melaporkan tidak sedang tertinggal dari siapa pun. --}}
                    <p class="mt-2 text-sm text-secondary">
                        Belum ada laporan kondisi dari Anda. Setelah turun, keterangan tentang jalur
                        yang baru Anda lalui adalah hal yang paling dibutuhkan pendaki berikutnya.
                    </p>
                    <a href="{{ route('reports.create') }}" wire:navigate
                        class="mt-2 inline-block text-sm font-medium text-brand-700 underline">Tulis laporan kondisi</a>
                @else
                    <p class="mt-2 text-sm text-secondary">
                        <span data-angka class="font-semibold text-primary">{{ $angka['laporan_terbit'] }}</span>
                        laporan Anda sudah terbit dan dapat dibaca pendaki lain.
                    </p>

                    @if ($angka['terima_kasih'] > 0)
                        <p class="mt-1 text-sm text-brand-900">
                            {{ $angka['terima_kasih'] }} pendaki menyatakan laporan Anda menolong mereka.
                        </p>
                    @endif
                @endif
            </section>

            <p class="text-sm text-secondary">
                Tiap pendakian punya halaman hasilnya sendiri berisi jalur, durasi, dan catatan Anda.
                <a href="{{ route('history') }}" wire:navigate
                    class="font-medium text-brand-700 underline">Buka riwayat pendakian</a>
            </p>
        @endif
    </div>
</div>
