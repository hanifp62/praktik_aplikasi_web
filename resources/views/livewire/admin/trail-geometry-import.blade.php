<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Impor Geometri Jalur" :description="$trail->name" />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-6" title="Keadaan sekarang">
            @if (! \App\Models\Trail::spatialSupported())
                <x-ui.alert variant="warning">
                    Koneksi basis data ini tidak mendukung PostGIS, jadi geometri tidak dapat dibaca
                    maupun disimpan dari sini.
                </x-ui.alert>
            @elseif ($geometriTersimpan)
                <p class="text-sm text-secondary">
                    Jalur ini sudah punya geometri dengan
                    {{ count($geometriTersimpan['coordinates'] ?? []) }} titik. Mengunggah berkas
                    baru akan menggantinya.
                </p>
            @else
                <p class="text-sm text-secondary">
                    Jalur ini belum punya geometri. Tanpa itu peta tidak dapat menggambar jalurnya,
                    dan jalur belum memenuhi syarat publikasi.
                </p>
            @endif
        </x-ui.card>

        <x-ui.card class="mb-6" title="Cari di OpenStreetMap">
            <p class="text-sm text-secondary">
                Mencari jalur kaki yang sudah terpetakan di sekitar
                {{ $trail->mountain?->name ?? 'gunung ini' }}. Hasilnya adalah kandidat, bukan
                jawaban: tidak ada cara aplikasi memastikan sebuah jalur di peta adalah
                {{ $trail->name }} dan bukan jalan setapak di sebelahnya. Anda yang memilih.
            </p>

            <p class="mt-2 text-xs text-secondary">
                Data OpenStreetMap adalah data komunitas, bukan data resmi pengelola jalur.
                Asalnya dicatat di jejak audit.
            </p>

            <div class="mt-4">
                <x-ui.button variant="secondary" wire:click="cariDiOsm">Cari kandidat</x-ui.button>
            </div>

            @if ($kandidat !== [])
                <div class="mt-4">
                    <x-input-label for="saringan" value="Saring berdasarkan nama" />
                    <input id="saringan" type="text" wire:model.live.debounce.300ms="saringan"
                        placeholder="misalnya: Kledung"
                        class="mt-1 block w-full max-w-sm rounded-md border-control text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">

                    <p class="mt-2 text-xs text-secondary">
                        {{ number_format(count($kandidat), 0, ',', '.') }} jalur kaki terpetakan di sekitar
                        gunung ini. Menampilkan {{ count($tampil) }} terpanjang
                        @if ($saringan !== '') yang cocok "{{ $saringan }}" @endif.
                        Jalur pendakian hampir selalu lebih panjang daripada potongan jalan di sekitarnya.
                    </p>
                </div>

                <ul class="mt-4 space-y-2">
                    @foreach ($tampil as $calon)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-subtle p-3">
                            <div class="text-sm">
                                <p class="font-medium text-primary">{{ $calon['nama'] }}</p>
                                <p class="text-secondary">
                                    {{ number_format($calon['panjang_km'], 2, ',', '.') }} km &middot;
                                    {{ number_format($calon['titik'], 0, ',', '.') }} titik &middot;
                                    OSM way {{ $calon['id'] }}
                                </p>
                            </div>

                            <x-ui.button variant="secondary" size="sm"
                                wire:click="pilihKandidat({{ $calon['id'] }})">
                                Pratinjau
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card class="mb-6" title="Unggah berkas GPX">
            <div class="space-y-4">
                <div>
                    <x-input-label for="berkas" value="Berkas GPX" />
                    <input id="berkas" type="file" wire:model="berkas" accept=".gpx,.xml"
                        class="mt-1 block w-full text-sm text-secondary file:mr-4 file:rounded-control file:border-0
                               file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white
                               hover:file:bg-brand-800" />
                    <x-input-error :messages="$errors->get('berkas')" class="mt-2" />

                    <p class="mt-2 text-xs text-secondary">
                        Ambil GPX dari sumber yang tercatat pada jalur ini, bukan dari jejak pribadi
                        yang belum diverifikasi. Maksimal
                        {{ number_format(config('hiking.uploads.gpx_max_points'), 0, ',', '.') }} titik.
                    </p>
                </div>

                <div wire:loading wire:target="berkas" class="text-sm text-secondary">
                    Membaca berkas...
                </div>

                @if ($galat)
                    <x-ui.alert variant="warning">{{ $galat }}</x-ui.alert>
                @endif

                @if ($pratinjau !== [])
                    <div class="rounded-lg border border-subtle bg-surface-sunken p-4 text-sm">
                        <p class="font-medium text-primary">Jejak terbaca</p>

                        @if ($asal)
                            <p class="mt-1 text-xs text-secondary">Asal: {{ $asal }}</p>
                        @endif

                        <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs text-muted">Jumlah titik</dt>
                                <dd class="text-primary">{{ number_format(count($pratinjau), 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">Panjang jejak</dt>
                                <dd class="text-primary">{{ number_format($panjangKm, 2, ',', '.') }} km</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">Jarak tercatat</dt>
                                <dd class="text-primary">
                                    {{ $trail->distance_km !== null
                                        ? number_format((float) $trail->distance_km, 2, ',', '.').' km'
                                        : 'belum diisi' }}
                                </dd>
                            </div>
                        </dl>

                        {{--
                            Selisih besar hampir selalu berarti berkasnya milik jalur lain, dan itu
                            kesalahan yang menempatkan pendaki di gunung yang salah. Kurator tetap
                            yang memutuskan, jadi ini peringatan, bukan penolakan.
                        --}}
                        @if ($selisih !== null && $selisih > 30)
                            <p class="mt-3 text-sm text-warn-900">
                                Panjang jejak berbeda {{ number_format($selisih, 1, ',', '.') }}% dari jarak
                                yang tercatat. Pastikan berkas ini memang milik jalur
                                {{ $trail->name }}.
                            </p>
                        @endif
                    </div>

                    {{--
                        Ketinggian berasal dari berkas yang sama dan sebelumnya dibuang tepat
                        di titik ia masuk. Angkanya ditawarkan, tidak ditulis diam-diam:
                        ia taksiran yang bergantung pada ambang derau, dan jalur yang sudah
                        punya angka dari pengelola tidak boleh tertimpa hitungan satu berkas.
                    --}}
                    @if ($tanjakan)
                        <div class="rounded-md border border-subtle p-3">
                            <p class="text-sm font-medium text-primary">Ketinggian terbaca dari berkas ini</p>

                            <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-muted">Taksiran elevation gain</dt>
                                    <dd class="text-primary">
                                        {{ number_format($tanjakan['gain'], 0, ',', '.') }} m
                                        <span class="text-secondary">(tercatat:
                                            {{ $trail->elevation_gain_m !== null
                                                ? number_format($trail->elevation_gain_m, 0, ',', '.').' m'
                                                : 'belum diisi' }})</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted">Taksiran elevation loss</dt>
                                    <dd class="text-primary">
                                        {{ number_format($tanjakan['loss'], 0, ',', '.') }} m
                                        <span class="text-secondary">(tercatat:
                                            {{ $trail->elevation_loss_m !== null
                                                ? number_format($trail->elevation_loss_m, 0, ',', '.').' m'
                                                : 'belum diisi' }})</span>
                                    </dd>
                                </div>
                            </dl>

                            <p class="mt-2 text-xs text-muted">
                                Taksiran dari ketinggian GPS dengan ambang
                                {{ config('hiking.uploads.gpx_elevation_threshold_m') }} meter, dipakai agar
                                derau perangkat tidak terhitung sebagai tanjakan. Angka resmi dari pengelola
                                kawasan lebih dapat dipercaya daripada ini.
                            </p>

                            <x-ui.button variant="secondary" size="sm" class="mt-3"
                                wire:click="terapkanTanjakan"
                                confirm="Ganti elevation gain dan loss jalur ini dengan taksiran dari berkas GPX?">
                                Terapkan ke jalur
                            </x-ui.button>
                        </div>
                    @elseif ($pratinjau !== [])
                        <p class="text-sm text-secondary">
                            Berkas ini tidak membawa data ketinggian, jadi profil elevasi dan taksiran
                            elevation gain tidak dapat dihitung darinya.
                        </p>
                    @endif

                    <x-ui.button wire:click="simpan">Simpan geometri</x-ui.button>
                @endif
            </div>
        </x-ui.card>

        <x-ui.button variant="secondary" href="{{ route('admin.trails') }}">Kembali ke daftar jalur</x-ui.button>
    </div>
</div>
