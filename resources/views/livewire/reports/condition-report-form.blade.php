<div class="py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Laporan Kondisi Jalur"
            description="Laporan Anda menjadi informasi tambahan bagi pendaki berikutnya. Laporan komunitas tidak mengubah status resmi jalur." />

        <form wire:submit="save" class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
            <x-form.select name="trail_id" label="Jalur" required placeholder="Pilih jalur" live
                :options="$trails->mapWithKeys(fn ($trail) => [$trail->id => $trail->name.' - '.$trail->mountain->name])->all()" />

            @if ($segments->isNotEmpty())
                <x-form.select name="trail_segment_id" label="Segmen (opsional)" placeholder="Seluruh jalur"
                    :options="$segments->mapWithKeys(fn ($segment) => [$segment->id => $segment->sequence.'. '.$segment->name])->all()" />
            @endif

            <x-form.field name="hike_date" type="date" label="Tanggal pendakian"
                hint="Tanggal pendakian menentukan seberapa baru informasi ini bagi pengguna lain." />

            <x-form.checkbox-group name="condition_tags" label="Kondisi yang Anda temui"
                :options="collect($tags)->mapWithKeys(fn ($tag) => [$tag->value => $tag->label()])->all()" />

            <div>
                <x-input-label for="note" value="Catatan (opsional)" />
                <textarea id="note" wire:model="note" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                <x-input-error :messages="$errors->get('note')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="photo" value="Foto (opsional, maksimal 4 MB)" />
                <input id="photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp"
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-800">
                <div wire:loading wire:target="photo" class="mt-1 text-xs text-gray-500">Mengunggah foto...</div>
                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
            </div>

            <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-700">
                Laporan akan melalui moderasi sebelum ditampilkan kepada pengguna lain.
            </div>

            <x-ui.button type="submit">Kirim laporan</x-ui.button>
        </form>
    </div>
</div>
