<div class="py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Perizinan"
            description="Catatan aturan pengelola. Sistem tidak memesan izin dan tidak melacak kuota; yang disimpan di sini hanya apa yang perlu diurus pendaki dan ke mana." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card :title="$editingId ? 'Ubah aturan perizinan' : 'Catat aturan perizinan'">
            <form wire:submit="save" class="space-y-4">
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">Aturan berlaku untuk</legend>
                    <div class="mt-2 flex flex-wrap gap-4">
                        <label for="scope-mountain" class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="scope-mountain" type="radio" value="mountain" wire:model.live="scope"
                                class="border-control text-brand-700 focus:ring-brand-600">
                            Seluruh gunung
                        </label>
                        <label for="scope-trail" class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="scope-trail" type="radio" value="trail" wire:model.live="scope"
                                class="border-control text-brand-700 focus:ring-brand-600">
                            Satu jalur tertentu
                        </label>
                    </div>
                </fieldset>

                @if ($scope === 'mountain')
                    <div>
                        <x-input-label for="mountain_id" value="Gunung" />
                        <select id="mountain_id" wire:model="mountain_id"
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600">
                            <option value="">Pilih gunung</option>
                            @foreach ($mountains as $mountain)
                                <option value="{{ $mountain->id }}">{{ $mountain->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('mountain_id')" class="mt-1" />
                    </div>
                @else
                    <div>
                        <x-input-label for="trail_id" value="Jalur" />
                        <select id="trail_id" wire:model="trail_id"
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600">
                            <option value="">Pilih jalur</option>
                            @foreach ($trails as $trail)
                                <option value="{{ $trail->id }}">{{ $trail->name }} &middot; {{ $trail->mountain->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('trail_id')" class="mt-1" />
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="authority" value="Penyelenggara" />
                        <x-text-input id="authority" wire:model="authority" class="mt-1 block w-full"
                            placeholder="TN Bromo Tengger Semeru" />
                        <x-input-error :messages="$errors->get('authority')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="booking_url" value="URL pemesanan resmi" />
                        <x-text-input id="booking_url" wire:model="booking_url" class="mt-1 block w-full"
                            placeholder="https://" />
                        <x-input-error :messages="$errors->get('booking_url')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="daily_quota" value="Kuota harian (orang)" />
                        <x-text-input id="daily_quota" type="number" wire:model="daily_quota" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('daily_quota')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="max_duration_days" value="Durasi maksimum (hari)" />
                        <x-text-input id="max_duration_days" type="number" wire:model="max_duration_days" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('max_duration_days')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="booking_opens_days_before" value="Pemesanan dibuka H-" />
                        <x-text-input id="booking_opens_days_before" type="number"
                            wire:model="booking_opens_days_before" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('booking_opens_days_before')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="booking_closes_days_before" value="Pemesanan ditutup H-" />
                        <x-text-input id="booking_closes_days_before" type="number"
                            wire:model="booking_closes_days_before" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('booking_closes_days_before')" class="mt-1" />
                    </div>
                </div>

                <label for="guide_required" class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="guide_required" type="checkbox" wire:model="guide_required"
                        class="rounded border-control text-brand-700 focus:ring-brand-600">
                    Wajib didampingi pemandu terdaftar
                </label>

                <div>
                    <x-input-label for="notes" value="Catatan tambahan" />
                    <textarea id="notes" wire:model="notes" rows="3"
                        class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                {{-- PRD §60: data penting membawa sumbernya. --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="source" value="Sumber informasi" />
                        <x-text-input id="source" wire:model="source" class="mt-1 block w-full"
                            placeholder="Situs resmi pengelola" />
                        <x-input-error :messages="$errors->get('source')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="source_url" value="URL sumber" />
                        <x-text-input id="source_url" wire:model="source_url" class="mt-1 block w-full"
                            placeholder="https://" />
                        <x-input-error :messages="$errors->get('source_url')" class="mt-1" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" target="save">{{ $editingId ? 'Simpan perubahan' : 'Catat aturan' }}</x-ui.button>
                    @if ($editingId)
                        <x-ui.button variant="secondary" wire:click="cancel" type="button">Batal</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        @if ($rules->isEmpty())
            <x-ui.empty-state title="Belum ada aturan perizinan tercatat"
                description="Jalur tanpa aturan tercatat tidak akan memunculkan peringatan perizinan apa pun. Ketiadaan data tidak dianggap sebagai masalah." />
        @else
            <x-ui.card title="Aturan tercatat">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <caption class="sr-only">Daftar aturan perizinan pendakian</caption>
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th scope="col" class="py-2 pr-4">Berlaku untuk</th>
                                <th scope="col" class="py-2 pr-4">Penyelenggara</th>
                                <th scope="col" class="py-2 pr-4">Ketentuan</th>
                                <th scope="col" class="py-2 pr-4">Diverifikasi</th>
                                <th scope="col" class="py-2">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rules as $rule)
                                <tr class="border-t border-gray-100 align-top">
                                    <td class="py-3 pr-4">
                                        @if ($rule->trail)
                                            {{ $rule->trail->name }}
                                            <span class="block text-xs text-gray-500">{{ $rule->trail->mountain->name }}</span>
                                        @else
                                            {{ $rule->mountain?->name ?? 'Tidak tertaut' }}
                                            <span class="block text-xs text-gray-500">Seluruh jalur</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">{{ $rule->authority }}</td>
                                    <td class="py-3 pr-4 text-gray-700">{{ $rule->summary() }}</td>
                                    <td class="py-3 pr-4 text-gray-600">
                                        {{ $rule->verified_at?->translatedFormat('d M Y') ?? 'Belum' }}
                                        <span class="block text-xs text-gray-500">{{ $rule->source ?? 'Sumber belum dicatat' }}</span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $rule->id }})">Ubah</x-ui.button>
                                            <x-ui.button variant="danger" size="sm" wire:click="delete({{ $rule->id }})" wire:confirm="Hapus aturan perizinan ini?">Hapus</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $rules->links() }}</div>
            </x-ui.card>
        @endif
    </div>
</div>
