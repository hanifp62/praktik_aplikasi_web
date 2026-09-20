<div class="py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kabar Jalur"
            description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />

        @if ($gunung->isEmpty())
            {{--
                Bukan layar kosong, melainkan ajakan memilih beserta alasannya. Pendaki
                yang belum mengikuti apa pun tidak sedang kehilangan sesuatu; ia belum
                memberi tahu gunung mana yang ingin ia dengar kabarnya.
            --}}
            <x-ui.card title="Belum ada gunung yang Anda ikuti">
                <p class="text-sm text-gray-700">
                    Ikuti gunung dari halaman jalurnya, lalu perubahan status resmi dan laporan
                    kondisi dari kawasan itu muncul di sini. Berguna justru ketika Anda sedang
                    tidak merencanakan apa pun: penutupan jalur diumumkan tanpa aba-aba.
                </p>
                <x-ui.button variant="secondary" href="{{ route('trails.index') }}" class="mt-3">
                    Telusuri jalur
                </x-ui.button>
            </x-ui.card>
        @else
            <p class="text-sm text-gray-600">
                Mengikuti {{ $gunung->pluck('name')->join(', ', ' dan ') }}.
            </p>

            @if ($kabar->isEmpty())
                <x-ui.card>
                    <p class="text-sm text-gray-700">
                        Belum ada kabar dari gunung yang Anda ikuti. Tidak adanya kabar bukan
                        pernyataan bahwa jalurnya terbuka; periksa halaman jalurnya untuk status
                        resmi terkini.
                    </p>
                </x-ui.card>
            @else
                <ul class="space-y-4">
                    @foreach ($kabar as $butir)
                        <li>
                            @if ($butir['jenis'] === \App\Services\TrailNewsService::STATUS)
                                {{-- OFFICIAL: keterangan pengelola kawasan (§92). --}}
                                <x-ui.card class="border-l-4 border-l-slate-700">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-700">Status resmi</span>
                                        <x-ui.status-badge :status="$butir['status']" :scope="$butir['model']->scope?->value" />
                                    </div>

                                    <p class="mt-2 text-sm font-medium text-gray-900">{{ $butir['judul'] }}</p>

                                    @if ($butir['alasan'])
                                        <p class="mt-1 text-sm text-gray-700">{{ $butir['alasan'] }}</p>
                                    @endif

                                    <p class="mt-2 text-xs text-gray-500">
                                        Sumber: {{ $butir['sumber'] ?? 'belum tercatat' }} &middot;
                                        <time datetime="{{ $butir['waktu']->toIso8601String() }}">
                                            {{ $butir['waktu']->diffForHumans() }}
                                        </time>
                                    </p>
                                </x-ui.card>
                            @else
                                {{-- COMMUNITY: masukan pendaki, tidak pernah mengubah status resmi. --}}
                                <x-ui.card class="border-l-4 border-l-community-500">
                                    <span class="text-xs font-medium uppercase tracking-wide text-community-900">Laporan komunitas</span>

                                    <p class="mt-2 text-sm font-medium text-gray-900">{{ $butir['judul'] }}</p>

                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($butir['model']->tags() as $tag)
                                            <span class="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">{{ $tag->label() }}</span>
                                        @endforeach
                                    </div>

                                    @if ($butir['model']->note)
                                        <p class="mt-2 text-sm text-gray-700">{{ $butir['model']->note }}</p>
                                    @endif

                                    <p class="mt-2 text-xs text-gray-500">
                                        Dilaporkan
                                        <span class="font-medium text-gray-700">{{ $butir['model']->user?->name ?? 'pendaki yang akunnya sudah dihapus' }}</span>
                                        &middot;
                                        <time datetime="{{ $butir['waktu']->toIso8601String() }}">
                                            {{ $butir['waktu']->diffForHumans() }}
                                        </time>
                                        @if ($butir['model']->thanks_count > 0)
                                            &middot; {{ $butir['model']->thanks_count }} pendaki terbantu
                                        @endif
                                    </p>

                                    <a href="{{ route('trails.show', $butir['model']->trail) }}" wire:navigate
                                        class="mt-2 inline-block text-sm text-brand-700 underline">Buka halaman jalur</a>
                                </x-ui.card>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</div>
