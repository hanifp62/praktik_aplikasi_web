<div class="py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Buat Rencana Trip"
            description="Rencana trip menghubungkan jalur yang Anda pilih dengan daftar persiapan dan pemeriksaan kondisi." />

        <form wire:submit="save" class="space-y-5 rounded-lg bg-white p-6">
            <x-form.select name="trail_id" label="Jalur" required placeholder="Pilih jalur"
                :options="$trails->mapWithKeys(fn ($trail) => [$trail->id => $trail->name.' - '.$trail->mountain->name])->all()" />

            <x-form.field name="name" label="Nama trip" placeholder="Pendakian akhir pekan" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.field name="planned_date" type="date" label="Tanggal pendakian" />
                <x-form.field name="start_time" type="time" label="Jam mulai (opsional)" />
            </div>

            <x-form.select name="trip_type" label="Tipe perjalanan" required placeholder="Pilih tipe perjalanan"
                :options="collect($tripTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />

            <div>
                <x-input-label for="notes" value="Catatan (opsional)" />
                <textarea id="notes" wire:model="notes" rows="3"
                    class="mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="border-t border-subtle pt-4">
                <x-ui.button type="submit" target="save">Simpan dan lanjut ke persiapan</x-ui.button>
            </div>
        </form>
    </div>
</div>
