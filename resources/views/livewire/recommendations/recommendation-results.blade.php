<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Rekomendasi Jalur"
            description="Label kecocokan menjelaskan hubungan antara profil Anda, rencana perjalanan, dan karakteristik jalur. Label ini bukan penilaian keselamatan.">
            <p class="mt-2 text-xs text-muted">
                Dihasilkan {{ \App\Support\Timezone::display($run->generated_at, \App\Support\Timezone::DEFAULT) }}
            </p>
        </x-ui.page-header>

        {{--
            Diletakkan sebelum daftar, bukan sesudahnya: kalimat ini mengubah cara
            seluruh label di bawahnya harus dibaca, jadi ia harus terbaca lebih dulu.
        --}}
        @if ($profilBerubah)
            <div class="mb-6 rounded-control border border-warn-300 bg-warn-50 p-4">
                <p class="text-sm font-medium text-warn-900">Profil Anda berubah sejak penilaian ini dibuat</p>
                <p class="mt-1 text-sm text-warn-900">
                    Label kecocokan di halaman ini dihitung terhadap profil Anda yang lama, dan
                    sengaja dibiarkan apa adanya sebagai catatan. Buat rencana baru untuk dinilai
                    dengan profil Anda sekarang.
                </p>
                <a href="{{ route('goals.create') }}" wire:navigate
                    class="mt-2 inline-block text-sm font-medium text-brand-700 underline">Buat rencana baru</a>
            </div>
        @endif

        @if ($eligible->isEmpty())
            {{--
                Tiga sebab, tiga saran. Menyuruh pendaki melonggarkan batas padahal tak
                satu pun jalur pernah diuji terhadap batas itu membuatnya mengubah hal
                yang tidak mungkin menolongnya, gagal lagi, lalu menyimpulkan sistemnya
                rusak.
            --}}
            <x-ui.card title="Belum ada jalur yang sesuai">
                @if ($penyebabKosong === 'katalog')
                    <p class="text-sm text-secondary">
                        Belum ada satu pun jalur berdata di sistem ini. Keterangan jalur belum dimasukkan
                        pengelola kawasan maupun pemandu bersertifikat yang disahkan untuk kawasannya.
                        Batasan rencana Anda tidak ada hubungannya dengan layar ini.
                    </p>
                @elseif ($penyebabKosong === 'wilayah')
                    <p class="text-sm text-secondary">
                        Belum ada jalur berdata di {{ $run->hikingGoal?->region }}. Jalur di wilayah itu
                        mungkin sudah dikenali sistem, tetapi keterangannya belum dimasukkan pihak yang
                        berwenang, jadi tidak ada yang dapat diuji terhadap rencana Anda. Mengubah durasi
                        atau batas elevation gain tidak akan mengubah hasil ini; kosongkan wilayahnya untuk
                        melihat jalur di daerah lain.
                    </p>
                @else
                    <p class="text-sm text-secondary">
                        Tidak ada jalur yang memenuhi batasan rencana Anda saat ini. Anda dapat melonggarkan
                        target durasi atau batas elevation gain, atau menelusuri jalur secara manual.
                    </p>
                @endif

                <x-ui.button variant="secondary" href="{{ route('trails.index') }}">Telusuri jalur manual</x-ui.button>
            </x-ui.card>
        @endif

        @if (count($comparison) >= 2)
            <div class="mb-4 flex items-center justify-between rounded-control border border-brand-200 bg-brand-50 px-4 py-3">
                <p class="text-sm text-brand-900">{{ count($comparison) }} jalur dipilih untuk dibandingkan.</p>
                <x-ui.button wire:click="compare">Bandingkan</x-ui.button>
            </div>
        @endif

        <ul class="space-y-4">
            @foreach ($eligible as $result)
                <li>
                    <x-ui.card>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                {{--
                                    Run punya URL permanen, jadi jalur dapat diarsipkan
                                    setelah run ini dibuat. Barisnya tetap ada karena run
                                    adalah catatan tentang apa yang dinilai saat itu, tetapi
                                    tautannya dicabut: halaman jalur terarsip menjawab 404.
                                --}}
                                <h2 class="text-lg font-semibold text-primary">
                                    @if ($result->trail->archived_at)
                                        {{ $result->trail->name }}
                                    @else
                                        <a href="{{ route('trails.show', $result->trail) }}" wire:navigate
                                            class="hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500">
                                            {{ $result->trail->name }}
                                        </a>
                                    @endif
                                </h2>
                                <p class="text-sm text-secondary">{{ $result->trail->mountain->name }}</p>
                                @if ($result->trail->archived_at)
                                    <p class="mt-1 text-sm font-medium text-warn-900">
                                        Jalur ini ditarik dari katalog setelah penilaian ini dibuat.
                                    </p>
                                @endif
                            </div>
                            <x-ui.fit-badge :label="$result->label" />
                        </div>

                        {{--
                            Angkanya diambil dari snapshot, yaitu jalur sebagaimana dipakai
                            menilai, bukan dari tabel jalur sekarang. Menampilkan angka
                            terkini di sebelah label historis membuat pendaki menyimpulkan
                            label itu dihitung dari angka yang sedang ia baca.
                        --}}
                        @php($angka = $result->trailFigures())
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <dt class="text-muted">Jarak</dt>
                                <dd class="font-medium text-primary">{{ $angka['distance_km'] ?? '-' }} km</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Elevation gain</dt>
                                <dd class="font-medium text-primary">{{ $angka['elevation_gain_m'] ?? '-' }} m</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Estimasi durasi</dt>
                                <dd class="font-medium text-primary">
                                    {{ $angka['estimated_duration_minutes'] ? round($angka['estimated_duration_minutes'] / 60, 1).' jam' : '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted">Tingkat teknis</dt>
                                <dd class="font-medium text-primary">
                                    {{ $angka['technical_demand']
                                        ? \App\Enums\TechnicalDemand::from($angka['technical_demand'])->label()
                                        : '-' }}
                                </dd>
                            </div>
                        </dl>

                        @if ($result->trailHasChangedSinceRun())
                            <p class="mt-3 rounded-control border border-warn-300 bg-warn-50 px-3 py-2 text-sm text-warn-900">
                                Data jalur ini berubah setelah penilaian dibuat. Angka di atas adalah
                                yang dipakai menilai; buka halaman jalur untuk keterangan terkini.
                            </p>
                        @endif

                        @if ($result->warnings)
                            <ul class="mt-3 space-y-1 text-sm text-warn-900">
                                @foreach ($result->warnings as $warning)
                                    <li class="rounded-control bg-warn-50 px-3 py-2">{{ $warning }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-ui.button variant="secondary" size="sm" wire:click="toggleExplanation({{ $result->id }})"
                                aria-expanded="{{ $expandedResultId === $result->id ? 'true' : 'false' }}">
                                {{ $result->eligible ? 'Mengapa jalur ini cocok?' : 'Mengapa kurang cocok?' }}</x-ui.button>
                            <x-ui.button variant="secondary" size="sm" wire:click="toggleComparison({{ $result->trail_id }})">
                                {{ in_array($result->trail_id, $comparison, true) ? 'Batal bandingkan' : 'Tambah ke perbandingan' }}</x-ui.button>
                            @unless ($result->trail->archived_at)
                                <x-ui.button wire:click="selectTrail({{ $result->trail_id }})">
                                    Pilih jalur ini</x-ui.button>
                            @endunless
                        </div>

                        @if ($expandedResultId === $result->id)
                            <div class="mt-4 space-y-3 border-t border-subtle pt-4">
                                {{--
                                    Batang faktor didahulukan di atas prosanya. Mesin ini
                                    menimbang delapan faktor, dan pertanyaan pertama pendaki
                                    adalah faktor mana yang menahan, bukan kalimat mana yang
                                    menjelaskannya. Prosa tetap ada di bawah untuk yang ingin
                                    membaca alasannya utuh.

                                    Dirender hanya ketika panelnya dibuka, jadi anggaran 120 kB
                                    halaman hasil tidak tersentuh sama sekali.
                                --}}
                                @if ($result->matched_factors)
                                    <div>
                                        <h3 class="text-sm font-semibold text-primary">Penilaian per faktor</h3>
                                        <x-ui.factor-bars :factors="$result->matched_factors" class="mt-2" />
                                    </div>
                                @endif

                                {{-- Judulnya mengikuti hasil penilaian. "Mengapa cocok" pada jalur
                                     yang justru tidak cocok membuat pendaki membaca dua hal yang
                                     bertentangan dalam satu kotak. --}}
                                @foreach ([
                                    'why_it_fits' => $result->eligible ? 'Mengapa cocok' : 'Dasar penilaiannya',
                                    'what_to_watch' => 'Yang perlu diperhatikan',
                                    'preparation_gap' => 'Persiapan yang belum selesai',
                                ] as $key => $heading)
                                    <div>
                                        <h3 class="text-sm font-semibold text-primary">{{ $heading }}</h3>
                                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-secondary">
                                            @forelse ($result->explanation[$key] ?? [] as $line)
                                                <li>{{ $line }}</li>
                                            @empty
                                                <li class="list-none text-muted">Tidak ada catatan.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.card>
                </li>
            @endforeach
        </ul>

        {{--
            Sisanya ditambahkan atas permintaan, tidak pernah dibuang: membatasi daftar
            tidak boleh berarti menyembunyikan jalur yang lolos penilaian. Jumlahnya
            disebut supaya pendaki tahu persis berapa yang belum dilihatnya.
        --}}
        {{-- Bentuk yang sudah terlihat memberi tahu apa yang sedang ditunggu. Empat baris
             karena itu kira-kira tinggi satu kartu jalur, jadi daftarnya tidak melompat
             ketika isinya tiba. --}}
        <x-ui.skeleton :rows="4" wire:loading wire:target="tampilkanLagi" class="mt-4" />

        @if ($sisaEligible > 0)
            <div class="mt-4">
                <x-ui.button variant="secondary" wire:click="tampilkanLagi">Tampilkan {{ $sisaEligible }} jalur lainnya</x-ui.button>
            </div>
        @endif

        @if ($excluded->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-base font-semibold text-primary">Tidak masuk rekomendasi</h2>
                <p class="mt-1 text-sm text-secondary">
                    Jalur berikut dikecualikan oleh batasan yang bersifat pasti, misalnya status resmi tutup
                    atau durasi yang tidak sesuai rencana.
                </p>
                <ul class="mt-3 space-y-2">
                    @foreach ($excluded as $result)
                        <li class="rounded-control border border-subtle bg-surface-sunken px-4 py-3 text-sm">
                            <span class="font-medium text-primary">{{ $result->trail->name }}</span>
                            <span class="text-secondary">&middot; {{ $result->trail->mountain->name }}</span>
                            <ul class="mt-1 list-disc pl-5 text-secondary">
                                @foreach ($result->failed_rules ?? [] as $rule)
                                    <li>{{ __('recommendation.'.$rule) }}</li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
