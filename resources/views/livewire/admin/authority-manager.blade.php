<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Badan Resmi"
            description="Menentukan siapa yang berwenang atas sebuah kawasan. Dari sinilah halaman jalur tahu nama siapa yang disebut ketika datanya belum ada." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="mb-4">
            <x-ui.button wire:click="create">Tambah badan resmi</x-ui.button>
        </div>

        @if ($showForm)
            <x-ui.card class="mb-6" :title="$editingId ? 'Ubah badan resmi' : 'Tambah badan resmi'">
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.field name="name" label="Nama resmi" />
                        <x-form.select name="type" label="Jenis" required placeholder="Pilih jenis"
                            :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.field name="abbreviation" label="Singkatan" />
                        <x-form.field name="jurisdiction" label="Wilayah kewenangan" />
                        <x-form.field name="contact" label="Kontak" />
                    </div>

                    <x-form.field name="website" type="url" label="Situs resmi"
                        hint="Dipakai pada halaman jalur agar pendaki dapat memeriksa langsung ke sumbernya." />

                    {{--
                        Lembaga sertifikasi dan asosiasi profesi biasanya tidak perlu dikaitkan
                        ke gunung: mereka mengesahkan orang, bukan menyatakan keadaan jalur.
                        Pengaitannya tetap diizinkan karena balai taman nasional juga
                        menyelenggarakan sertifikasi untuk kawasannya sendiri.
                    --}}
                    <x-form.checkbox-group name="mountain_ids" label="Kawasan yang berada di bawahnya"
                        :options="$mountains->mapWithKeys(fn ($m) => [$m->id => $m->name])->all()" />

                    <div>
                        <x-input-label for="notes" value="Catatan" />
                        <textarea id="notes" wire:model="notes" rows="2"
                            class="mt-1 block w-full rounded-md border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-ui.button type="submit" target="save">Simpan</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('showForm', false)">Batal</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <div class="overflow-x-auto rounded-lg bg-white">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar badan resmi</caption>
                <thead>
                    <tr class="border-b border-subtle text-left text-muted">
                        <th scope="col" class="p-4">Nama</th>
                        <th scope="col" class="p-4">Jenis</th>
                        <th scope="col" class="p-4">Wilayah</th>
                        <th scope="col" class="p-4">Kawasan</th>
                        <th scope="col" class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($badan as $b)
                        <tr class="border-b border-subtle">
                            <td class="p-4 font-medium text-primary">{{ $b->displayName() }}</td>
                            <td class="p-4 text-secondary">{{ $b->type->label() }}</td>
                            <td class="p-4 text-secondary">{{ $b->jurisdiction ?? '-' }}</td>
                            <td class="p-4 text-secondary">
                                {{ $b->mountains_count }}
                                @if ($b->mountains_count === 0 && $b->type->mayDeclareTrailStatus())
                                    <span class="ml-1 text-xs text-warn-900">(belum dikaitkan)</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $b->id }})">Ubah</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $badan->links() }}</div>
    </div>
</div>
