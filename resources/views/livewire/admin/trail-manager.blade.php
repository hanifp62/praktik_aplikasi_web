<div class="py-8">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header title="Kelola Jalur"
            description="Jalur hanya dapat dipublikasikan jika sumber data, karakteristik, checkpoint, dan status resmi sudah lengkap." />

        <x-ui.admin-nav />

        @if (session('status'))
            <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="mb-4">
            <x-ui.button wire:click="create">Tambah jalur</x-ui.button>
        </div>

        @if ($showForm)
            <x-ui.card class="mb-6" :title="$editingId ? 'Ubah jalur' : 'Tambah jalur'">
                <form wire:submit="save" class="space-y-4">
                    <x-form.select name="mountain_id" label="Gunung" required placeholder="Pilih gunung"
                        :options="$mountains->mapWithKeys(fn ($mountain) => [$mountain->id => $mountain->name])->all()" />

                    <x-form.field name="name" label="Nama jalur" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.field name="distance_km" type="number" step="any" label="Jarak (km)" />
                        <x-form.field name="elevation_gain_m" type="number" label="Elevation gain (m)" />
                        <x-form.field name="elevation_loss_m" type="number" label="Elevation loss (m)" />
                    </div>

                    <x-form.field name="estimated_duration_minutes" type="number" label="Estimasi durasi (menit)" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-form.select name="technical_demand" label="Tingkat teknis" required placeholder="Pilih"
                            :options="collect($technicalLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />
                        <x-form.select name="navigation_complexity" label="Kompleksitas navigasi" required placeholder="Pilih"
                            :options="collect($navigationLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />
                        <x-form.select name="water_availability" label="Ketersediaan air" required placeholder="Pilih"
                            :options="collect($waterLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />
                    </div>

                    <x-form.checkbox-group name="terrain_character" label="Karakter medan"
                        :options="collect($terrainOptions)->mapWithKeys(fn ($terrain) => [$terrain->value => $terrain->label()])->all()" />

                    <label class="flex items-center gap-2 text-sm text-secondary">
                        <input type="checkbox" wire:model="camping_available"
                            class="rounded border-control text-brand-700 focus:ring-brand-600">
                        Tersedia area camping
                    </label>

                    <x-form.field name="starting_point" label="Titik awal" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.field name="weather_adm4_code" label="Kode wilayah BMKG (adm4)"
                            hint="Dipakai untuk mengambil prakiraan area sekitar jalur." />
                        <x-form.field name="weather_reference_area" label="Nama area referensi cuaca" />
                    </div>

                    <x-form.select name="data_source_id" label="Sumber data" placeholder="Belum ditentukan"
                        :options="$sources->mapWithKeys(fn ($source) => [$source->id => $source->source_name])->all()" />

                    <div>
                        <x-input-label for="description" value="Deskripsi" />
                        <textarea id="description" wire:model="description" rows="3"
                            class="mt-1 block w-full rounded-md border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-ui.button type="submit" target="save">Simpan</x-ui.button>
                        <x-ui.button variant="secondary" type="button" wire:click="$set('showForm', false)">Batal</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <div class="overflow-x-auto rounded-lg bg-white">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Daftar jalur</caption>
                <thead>
                    <tr class="border-b border-subtle text-left text-muted">
                        <th scope="col" class="p-4">Jalur</th>
                        <th scope="col" class="p-4">Gunung</th>
                        <th scope="col" class="p-4">Checkpoint</th>
                        <th scope="col" class="p-4">Publikasi</th>
                        <th scope="col" class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trails as $trail)
                        <tr class="border-b border-subtle">
                            <td class="p-4 font-medium text-primary">{{ $trail->name }}</td>
                            <td class="p-4 text-secondary">{{ $trail->mountain->name }}</td>
                            <td class="p-4 text-secondary">{{ $trail->checkpoints_count }}</td>
                            @php($missing = $trail->publishabilityReport())

                            <td class="p-4 text-secondary">
                                {{ $trail->is_published ? 'Tayang' : 'Draft' }}
                                @if ($trail->archived_at)
                                    <span class="ml-1 text-xs text-muted">(diarsipkan)</span>
                                @endif
                                @if ($trail->is_published && $missing !== [])
                                    <span class="ml-1 text-xs font-medium text-warn-900">(syarat belum lengkap)</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button variant="secondary" size="sm" wire:click="edit({{ $trail->id }})">Ubah</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" href="{{ route('trails.checkpoints', $trail) }}">Checkpoint</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" href="{{ route('trails.geometry', $trail) }}">Geometri</x-ui.button>
                                    {{--
                                        Konfirmasi hanya pada arah yang menghilangkan jalur dari
                                        hadapan pendaki. Menerbitkan atau mengaktifkan kembali tidak
                                        perlu dihalangi, dan pesannya menyebutkan akibatnya, bukan
                                        sekadar bertanya apakah yakin.
                                    --}}
                                    <x-ui.button variant="secondary" size="sm"
                                        wire:click="togglePublish({{ $trail->id }})"
                                        :confirm="$trail->is_published ? 'Tarik jalur ini dari publikasi? Pendaki tidak akan menemukannya lagi di pencarian maupun rekomendasi.' : null">
                                        {{ $trail->is_published ? 'Tarik dari publikasi' : 'Publikasikan' }}</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm"
                                        wire:click="toggleArchive({{ $trail->id }})"
                                        :confirm="$trail->archived_at ? null : 'Arsipkan jalur ini? Jalur yang diarsipkan hilang dari pencarian dan tidak dapat dipilih untuk trip baru.'">
                                        {{ $trail->archived_at ? 'Aktifkan' : 'Arsipkan' }}</x-ui.button>
                                </div>

                                {{--
                                    PRD §110: kurator harus tahu apa yang kurang, bukan sekadar ditolak.

                                    Daftar ini juga tampil untuk jalur yang sudah tayang. Gerbangnya hanya
                                    berjalan saat tombol publikasi ditekan, sehingga jalur yang syaratnya
                                    berubah atau datanya dihapus setelah terbit tidak pernah diperiksa ulang.
                                --}}
                                @if ($missing !== [])
                                    <p class="mt-2 text-xs font-medium text-warn-900">
                                        {{ $trail->is_published ? 'Tayang padahal belum memenuhi syarat:' : 'Belum dapat dipublikasikan:' }}
                                    </p>
                                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-warn-900">
                                        @foreach ($missing as $requirement)
                                            <li>{{ $requirement }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $trails->links() }}</div>
    </div>
</div>
