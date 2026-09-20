<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kontribusi Data Jalur"
            description="Anda mengisi data jalur di kawasan yang disahkan untuk sertifikat Anda. Admin yang memutuskan kapan data itu tayang untuk pendaki." />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-6" title="Kredensial Anda">
            <ul class="space-y-2 text-sm">
                @foreach ($kredensial as $k)
                    <li class="flex flex-wrap items-baseline gap-x-2">
                        <span class="font-medium text-primary">{{ $k->level->label() }}</span>
                        <span class="text-secondary">
                            diterbitkan {{ $k->issuingAuthority->displayName() }}
                            @if ($k->expires_at)
                                &middot; berlaku sampai {{ $k->expires_at->translatedFormat('d M Y') }}
                            @endif
                        </span>
                        <span class="w-full text-xs text-muted">
                            Kawasan: {{ $k->mountains->pluck('name')->join(', ') }}
                        </span>
                    </li>
                @endforeach
            </ul>

            {{-- Sertifikat BNSP berlaku tiga tahun, dan haknya berhenti bersamaan. --}}
            <p class="mt-3 text-xs text-secondary">
                Hak menyunting berhenti sendiri ketika sertifikat Anda habis masa berlakunya.
            </p>
        </x-ui.card>

        @if ($editingId)
            <x-ui.card class="mb-6" title="Isi data jalur">
                <form wire:submit="simpan" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.field name="distance_km" type="number" step="any" label="Jarak (km)" />
                        <x-form.field name="elevation_gain_m" type="number" label="Elevation gain (m)" />
                        <x-form.field name="elevation_loss_m" type="number" label="Elevation loss (m)" />
                    </div>

                    <x-form.field name="estimated_duration_minutes" type="number"
                        label="Estimasi durasi (menit)"
                        hint="Perkiraan waktu tempuh pendaki dengan kecepatan sedang, bukan waktu tercepat." />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.select name="technical_demand" label="Tingkat teknis" required placeholder="Pilih"
                            :options="collect($technicalLevels)->mapWithKeys(fn ($l) => [$l->value => $l->label()])->all()" />
                        <x-form.select name="navigation_complexity" label="Kompleksitas navigasi" required placeholder="Pilih"
                            :options="collect($navigationLevels)->mapWithKeys(fn ($l) => [$l->value => $l->label()])->all()" />
                        <x-form.select name="water_availability" label="Ketersediaan air" required placeholder="Pilih"
                            :options="collect($waterLevels)->mapWithKeys(fn ($l) => [$l->value => $l->label()])->all()" />
                    </div>

                    <x-form.checkbox-group name="terrain_character" label="Karakter medan"
                        :options="collect($terrainOptions)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />

                    <label class="flex items-center gap-2 text-sm text-secondary">
                        <input type="checkbox" wire:model="camping_available"
                            class="rounded border-control text-brand-700 focus:ring-brand-600">
                        Tersedia area camping
                    </label>

                    <x-form.field name="starting_point" label="Titik awal" />

                    <div>
                        <x-input-label for="description" value="Deskripsi jalur" />
                        <textarea id="description" wire:model="description" rows="3"
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    {{--
                        Wajib diisi. Angka tanpa asal tidak dapat dinilai pembacanya, dan di
                        sini asalnya adalah pengetahuan lapangan seseorang, bukan dokumen yang
                        dapat ditelusuri sendiri oleh pembaca (§60).
                    --}}
                    <div>
                        <x-input-label for="catatan_sumber" value="Dari mana data ini Anda ketahui" />
                        <textarea id="catatan_sumber" wire:model="catatan_sumber" rows="2"
                            placeholder="Misalnya: hasil pengukuran sendiri Agustus 2026, atau data resmi basecamp Cemoro Sewu."
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('catatan_sumber')" class="mt-2" />
                        <p class="mt-1 text-xs text-secondary">
                            Tersimpan bersama data dan terbaca admin saat meninjau.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <x-ui.button type="submit" target="simpan">Simpan data</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="batal">Batal</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Jalur di kawasan Anda">
            @if ($trails->isEmpty())
                <p class="text-sm text-secondary">Belum ada jalur tercatat di kawasan yang disahkan untuk Anda.</p>
            @else
                <ul class="divide-y divide-subtle">
                    @foreach ($trails as $trail)
                        @php($menunggu = $trail->awaitingData())

                        <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-primary">{{ $trail->name }}</p>
                                <p class="text-sm text-secondary">{{ $trail->mountain->name }}</p>

                                @if ($menunggu !== [])
                                    <p class="mt-1 text-xs text-warn-900">
                                        Belum ada: {{ implode(', ', $menunggu) }}
                                    </p>
                                @else
                                    <p class="mt-1 text-xs text-muted">Data lengkap.</p>
                                @endif

                                @if (! $trail->is_published)
                                    <p class="mt-1 text-xs text-muted">Belum tayang untuk pendaki.</p>
                                @endif
                            </div>

                            {{--
                                Geometri dan koordinat pos adalah data yang justru paling
                                mungkin dimiliki pemandu bersertifikat, karena merekalah yang
                                berjalan di jalurnya sambil membawa GPS. Pintunya dibuka di
                                sini karena halaman ini satu-satunya tempat mereka berada.
                            --}}
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $trail->id }})">
                                    Isi data
                                </x-ui.button>
                                <x-ui.button variant="secondary" size="sm"
                                    href="{{ route('trails.geometry', $trail) }}">Garis jalur</x-ui.button>
                                <x-ui.button variant="secondary" size="sm"
                                    href="{{ route('trails.checkpoints', $trail) }}">Pos</x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</div>
