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
                <p class="text-sm text-gray-700">
                    Jalur ini sudah punya geometri dengan
                    {{ count($geometriTersimpan['coordinates'] ?? []) }} titik. Mengunggah berkas
                    baru akan menggantinya.
                </p>
            @else
                <p class="text-sm text-gray-700">
                    Jalur ini belum punya geometri. Tanpa itu peta tidak dapat menggambar jalurnya,
                    dan jalur belum memenuhi syarat publikasi.
                </p>
            @endif
        </x-ui.card>

        <x-ui.card class="mb-6" title="Unggah berkas GPX">
            <div class="space-y-4">
                <div>
                    <x-input-label for="berkas" value="Berkas GPX" />
                    <input id="berkas" type="file" wire:model="berkas" accept=".gpx,.xml"
                        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-control file:border-0
                               file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white
                               hover:file:bg-brand-800" />
                    <x-input-error :messages="$errors->get('berkas')" class="mt-2" />

                    <p class="mt-2 text-xs text-gray-600">
                        Ambil GPX dari sumber yang tercatat pada jalur ini, bukan dari jejak pribadi
                        yang belum diverifikasi. Maksimal
                        {{ number_format(config('hiking.uploads.gpx_max_points'), 0, ',', '.') }} titik.
                    </p>
                </div>

                <div wire:loading wire:target="berkas" class="text-sm text-gray-600">
                    Membaca berkas...
                </div>

                @if ($galat)
                    <x-ui.alert variant="warning">{{ $galat }}</x-ui.alert>
                @endif

                @if ($pratinjau !== [])
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
                        <p class="font-medium text-gray-900">Jejak terbaca</p>

                        <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs text-gray-500">Jumlah titik</dt>
                                <dd class="text-gray-900">{{ number_format(count($pratinjau), 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Panjang jejak</dt>
                                <dd class="text-gray-900">{{ number_format($panjangKm, 2, ',', '.') }} km</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Jarak tercatat</dt>
                                <dd class="text-gray-900">
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

                    <x-ui.button wire:click="simpan">Simpan geometri</x-ui.button>
                @endif
            </div>
        </x-ui.card>

        <x-ui.button variant="secondary" href="{{ route('admin.trails') }}">Kembali ke daftar jalur</x-ui.button>
    </div>
</div>
