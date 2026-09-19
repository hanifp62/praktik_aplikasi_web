<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Sumber Data"
            description="Setiap data penting harus memiliki sumber, tipe sumber, waktu pengambilan, dan status verifikasi." />

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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <x-ui.button type="submit">Simpan sumber</x-ui.button>
            </form>
        </x-ui.card>

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar sumber data</caption>
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500">
                        <th scope="col" class="p-4">Sumber</th>
                        <th scope="col" class="p-4">Tipe</th>
                        <th scope="col" class="p-4">Verifikasi</th>
                        <th scope="col" class="p-4">Diverifikasi</th>
                        <th scope="col" class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sources as $source)
                        <tr class="border-b border-gray-100">
                            <td class="p-4">
                                <span class="font-medium text-gray-900">{{ $source->source_name }}</span>
                                @if ($source->source_url)
                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer"
                                        class="block text-xs text-brand-700 underline">{{ $source->source_url }}</a>
                                @endif
                            </td>
                            <td class="p-4 text-gray-700">{{ $source->source_type->label() }}</td>
                            <td class="p-4 text-gray-700">{{ $source->verification_status->label() }}</td>
                            <td class="p-4 text-gray-700">
                                {{ $source->verified_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="edit({{ $source->id }})"
                                        class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Ubah</button>
                                    <button wire:click="verify({{ $source->id }})"
                                        class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Verifikasi</button>
                                    <button wire:click="dispute({{ $source->id }})"
                                        class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Tandai diperdebatkan</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $sources->links() }}</div>
    </div>
</div>
