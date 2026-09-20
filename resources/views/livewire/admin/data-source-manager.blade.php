<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Sumber Data"
            description="Setiap data penting harus memiliki sumber, tipe sumber, waktu pengambilan, dan status verifikasi." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-6" :title="$editingId ? 'Ubah sumber data' : 'Daftarkan sumber data'">
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field name="source_name" label="Nama sumber" />
                    <x-form.select name="source_type" label="Tipe sumber" required placeholder="Pilih tipe"
                        :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field name="source_url" type="url" label="URL sumber" />
                    <x-form.field name="source_owner" label="Pemilik sumber" />
                </div>

                <x-form.field name="freshness_policy" label="Kebijakan kesegaran data"
                    hint="Contoh: mengikuti jadwal pembaruan BMKG, atau diverifikasi ulang setiap awal bulan." />

                <div>
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" wire:model="notes" rows="2"
                        class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <x-ui.button type="submit" target="save">Simpan sumber</x-ui.button>
            </form>
        </x-ui.card>

        <div class="overflow-x-auto rounded-lg bg-white">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar sumber data</caption>
                <thead>
                    <tr class="border-b border-subtle text-left text-muted">
                        <th scope="col" class="p-4">Sumber</th>
                        <th scope="col" class="p-4">Tipe</th>
                        <th scope="col" class="p-4">Verifikasi</th>
                        <th scope="col" class="p-4">Diverifikasi</th>
                        <th scope="col" class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr class="border-b border-subtle">
                            <td class="p-4">
                                <span class="font-medium text-primary">{{ $source->source_name }}</span>
                                @if ($source->source_url)
                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer"
                                        class="block text-xs text-brand-700 underline">{{ $source->source_url }}</a>
                                @endif
                            </td>
                            <td class="p-4 text-secondary">{{ $source->source_type->label() }}</td>
                            <td class="p-4 text-secondary">{{ $source->verification_status->label() }}</td>
                            <td class="p-4 text-secondary">
                                {{ $source->verified_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $source->id }})">Ubah</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" wire:click="verify({{ $source->id }})">Verifikasi</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" wire:click="dispute({{ $source->id }})">Tandai diperdebatkan</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4">
                                <p class="font-medium text-primary">Belum ada sumber data terdaftar</p>
                                <p class="mt-1 max-w-prose text-secondary">Sumber data menentukan asal setiap angka yang ditampilkan. Tanpa satu pun, keterangan yang terbit tidak dapat ditelusuri asalnya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $sources->links() }}</div>
    </div>
</div>
