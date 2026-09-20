<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Gunung"
            description="Tambah, ubah, atau arsipkan data gunung. Setiap perubahan tercatat di audit log." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="mb-4">
            <x-ui.button wire:click="create">Tambah gunung</x-ui.button>
        </div>

        @if ($showForm)
            <x-ui.card class="mb-6" :title="$editingId ? 'Ubah gunung' : 'Tambah gunung'">
                <form wire:submit="save" class="space-y-4">
                    <x-form.field name="name" label="Nama gunung" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.field name="province" label="Provinsi" />
                        <x-form.field name="region" label="Wilayah" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.field name="elevation_mdpl" type="number" label="Ketinggian (mdpl)" />
                        <x-form.field name="latitude" type="number" step="any" label="Latitude" />
                        <x-form.field name="longitude" type="number" step="any" label="Longitude" />
                    </div>

                    <x-form.select name="data_source_id" label="Sumber data" placeholder="Belum ditentukan"
                        :options="$sources->mapWithKeys(fn ($source) => [$source->id => $source->source_name])->all()" />

                    <div>
                        <x-input-label for="description" value="Deskripsi" />
                        <textarea id="description" wire:model="description" rows="3"
                            class="mt-1 block w-full rounded-md border-control shadow-sm focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-ui.button type="submit" target="save">Simpan</x-ui.button>
                        <x-ui.button variant="secondary" type="button" wire:click="$set('showForm', false)">Batal</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar gunung</caption>
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500">
                        <th scope="col" class="p-4">Nama</th>
                        <th scope="col" class="p-4">Provinsi</th>
                        <th scope="col" class="p-4">Ketinggian</th>
                        <th scope="col" class="p-4">Jalur</th>
                        <th scope="col" class="p-4">Status</th>
                        <th scope="col" class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mountains as $mountain)
                        <tr class="border-b border-gray-100">
                            <td class="p-4 font-medium text-gray-900">{{ $mountain->name }}</td>
                            <td class="p-4 text-gray-700">{{ $mountain->province ?? '-' }}</td>
                            <td class="p-4 text-gray-700">{{ $mountain->elevation_mdpl ?? '-' }} mdpl</td>
                            <td class="p-4 text-gray-700">{{ $mountain->trails_count }}</td>
                            <td class="p-4 text-gray-700">{{ $mountain->isArchived() ? 'Diarsipkan' : 'Aktif' }}</td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $mountain->id }})">Ubah</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm"
                                        wire:click="toggleArchive({{ $mountain->id }})"
                                        :confirm="$mountain->isArchived() ? null : 'Arsipkan gunung ini? Seluruh jalurnya ikut hilang dari pencarian pendaki.'">
                                        {{ $mountain->isArchived() ? 'Aktifkan' : 'Arsipkan' }}</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $mountains->links() }}</div>
    </div>
</div>
