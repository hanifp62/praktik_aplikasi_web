<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Jelajahi Jalur"
            description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />

        <form class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-3"
            role="search" aria-label="Filter jalur">
            <div>
                <x-input-label for="search" value="Cari jalur atau gunung" />
                <x-text-input id="search" wire:model.live.debounce.400ms="search" class="mt-1 block w-full"
                    placeholder="Merbabu" />
            </div>
            <div>
                <x-input-label for="technical" value="Tingkat teknis" />
                <select id="technical" wire:model.live="technical"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua</option>
                    @foreach ($technicalLevels as $level)
                        <option value="{{ $level->value }}">{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="region" value="Wilayah" />
                <x-text-input id="region" wire:model.live.debounce.400ms="region" class="mt-1 block w-full"
                    placeholder="Jawa Tengah" />
            </div>
        </form>

        @if ($trails->isEmpty())
            <x-ui.card>
                <p class="text-sm text-gray-600">Tidak ada jalur yang cocok dengan filter Anda.</p>
            </x-ui.card>
        @else
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($trails as $trail)
                    <li>
                        <x-ui.card>
                            <h2 class="text-base font-semibold text-gray-900">
                                <a href="{{ route('trails.show', $trail) }}" wire:navigate
                                    class="hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    {{ $trail->name }}
                                </a>
                            </h2>
                            <p class="text-sm text-gray-600">{{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }}</p>
                            <dl class="mt-3 grid grid-cols-3 gap-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Jarak</dt>
                                    <dd class="font-medium text-gray-900">{{ $trail->distance_km ?? '-' }} km</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Gain</dt>
                                    <dd class="font-medium text-gray-900">{{ $trail->elevation_gain_m ?? '-' }} m</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Teknis</dt>
                                    <dd class="font-medium text-gray-900">{{ $trail->technical_demand->label() }}</dd>
                                </div>
                            </dl>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $trails->links() }}</div>
        @endif
    </div>
</div>
