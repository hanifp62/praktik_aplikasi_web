<div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Checkpoint"
            :description="$trail->name" />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-6" :title="$editingId ? 'Ubah checkpoint' : 'Tambah checkpoint'">
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field name="name" label="Nama checkpoint" />
                    <x-form.field name="sequence" type="number" min="1" label="Urutan" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form.select name="checkpoint_type" label="Tipe" required placeholder="Pilih tipe"
                        :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    <x-form.field name="latitude" type="number" step="any" label="Latitude" />
                    <x-form.field name="longitude" type="number" step="any" label="Longitude" />
                </div>

                <x-form.field name="elevation_m" type="number" label="Ketinggian (mdpl)" />

                <div>
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" wire:model="notes" rows="2"
                        class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <x-ui.button type="submit" target="save">Simpan checkpoint</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Daftar checkpoint">
            @if ($checkpoints->isEmpty())
                <p class="text-sm text-secondary">Belum ada checkpoint.</p>
            @else
                <ol class="space-y-2">
                    @foreach ($checkpoints as $checkpoint)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-control border border-subtle px-3 py-2 text-sm">
                            <span>
                                <span class="font-medium text-primary">{{ $checkpoint->sequence }}. {{ $checkpoint->name }}</span>
                                <span class="text-secondary">&middot; {{ $checkpoint->checkpoint_type->label() }}</span>
                                @if ($checkpoint->elevation_m)
                                    <span class="text-secondary">&middot; {{ $checkpoint->elevation_m }} mdpl</span>
                                @endif
                            </span>
                            <span class="flex gap-2">
                                <x-ui.button variant="secondary" size="sm" wire:click="move({{ $checkpoint->id }}, -1)" aria-label="Naikkan urutan {{ $checkpoint->name }}">Naik</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" wire:click="move({{ $checkpoint->id }}, 1)" aria-label="Turunkan urutan {{ $checkpoint->name }}">Turun</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $checkpoint->id }})">Ubah</x-ui.button>
                                <x-ui.button variant="danger" size="sm" wire:click="delete({{ $checkpoint->id }})" wire:confirm="Hapus checkpoint ini?">Hapus</x-ui.button>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>

        <x-ui.button variant="secondary" href="{{ route('admin.trails') }}">Kembali ke daftar jalur</x-ui.button>
    </div>
</div>
