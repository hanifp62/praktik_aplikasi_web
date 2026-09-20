<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Verifikasi Kredensial Ahli"
            description="Yang diverifikasi di sini bukan kompetensinya, melainkan keberadaan sertifikatnya. BNSP dan LSP yang menguji kompetensi." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="mb-4 max-w-xs">
            <x-input-label for="filter" value="Filter status" />
            <select id="filter" wire:model.live="filter"
                class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if ($kredensial->isEmpty())
            <x-ui.card>
                <p class="text-sm text-gray-600">Tidak ada kredensial pada filter ini.</p>
            </x-ui.card>
        @else
            <ul class="space-y-4">
                @foreach ($kredensial as $k)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">{{ $k->user->name }}</h2>
                                    <p class="text-sm text-gray-600">{{ $k->level->label() }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $k->scheme }} &middot; nomor {{ $k->certificate_number ?? 'tidak dicantumkan' }}
                                    </p>
                                </div>

                                <div class="text-right text-xs">
                                    <span class="rounded-md bg-gray-100 px-2.5 py-1 font-medium text-gray-700">
                                        {{ $k->verification_status->label() }}
                                    </span>

                                    {{-- Sertifikat BNSP berlaku tiga tahun. Yang lewat masa berlakunya
                                         tidak memberi hak apa pun, meski sudah diverifikasi. --}}
                                    @if ($k->isExpired())
                                        <p class="mt-1 font-medium text-warn-900">Kedaluwarsa</p>
                                    @endif
                                </div>
                            </div>

                            <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-gray-500">Diterbitkan oleh</dt>
                                    <dd class="text-gray-900">{{ $k->issuingAuthority->displayName() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Disahkan untuk kawasan oleh</dt>
                                    <dd class="text-gray-900">{{ $k->endorsingAuthority?->displayName() ?? 'Belum disahkan balai mana pun' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Berlaku</dt>
                                    <dd class="text-gray-900">
                                        {{ $k->issued_at->translatedFormat('d M Y') }}
                                        @if ($k->expires_at)
                                            &ndash; {{ $k->expires_at->translatedFormat('d M Y') }}
                                        @else
                                            &ndash; tanpa batas tercatat
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Kawasan</dt>
                                    <dd class="text-gray-900">
                                        {{ $k->mountains->pluck('name')->join(', ') ?: 'Belum ditetapkan' }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 space-y-2">
                                <label class="block text-sm text-gray-700" for="catatan-{{ $k->id }}">
                                    Catatan pemeriksaan (opsional)
                                </label>
                                <input id="catatan-{{ $k->id }}" type="text" wire:model="catatan.{{ $k->id }}"
                                    class="block w-full rounded-md border-control text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">

                                <p class="text-xs text-gray-600">
                                    Verifikasi memberi pemegangnya hak menyunting data jalur di kawasan
                                    yang tercantum. Setiap perubahan tercatat di jejak audit.
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button size="sm" wire:click="verifikasi({{ $k->id }})">Verifikasi</x-ui.button>

                                    <x-ui.button variant="secondary" size="sm"
                                        wire:click="sengketakan({{ $k->id }})"
                                        confirm="Tandai kredensial ini bersengketa? Haknya menyunting data jalur dicabut sampai persoalannya selesai.">
                                        Tandai bersengketa
                                    </x-ui.button>

                                    <x-ui.button variant="secondary" size="sm"
                                        wire:click="cabut({{ $k->id }})"
                                        confirm="Cabut verifikasi kredensial ini? Haknya menyunting data jalur berhenti berlaku.">
                                        Cabut verifikasi
                                    </x-ui.button>
                                </div>
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $kredensial->links() }}</div>
        @endif
    </div>
</div>
