<div class="py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Rencana Pendakian"
            description="Isi rencana perjalanan Anda. Sistem memakai rencana ini bersama profil Anda untuk menilai kecocokan jalur." />

        <form wire:submit="save" class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.field name="target_date" type="date" label="Target tanggal (opsional)" />
                <x-form.field name="region" label="Wilayah (opsional)" placeholder="Jawa Tengah" />
            </div>

            <x-form.select name="trip_type" label="Tipe perjalanan" required placeholder="Pilih tipe perjalanan"
                :options="collect($tripTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />

            <x-form.field name="expected_duration_minutes" type="number" min="60"
                label="Perkiraan durasi yang Anda targetkan (menit)"
                hint="Contoh: 720 untuk rencana 12 jam." />

            <x-form.select name="preferred_challenge" label="Tingkat tantangan yang dicari (opsional)"
                placeholder="Tidak ada preferensi"
                :options="collect($challenges)->mapWithKeys(fn ($challenge) => [$challenge->value => $challenge->label()])->all()" />

            <x-form.field name="max_elevation_gain_m" type="number" min="0"
                label="Batas elevation gain (meter, opsional)"
                hint="Jalur dengan elevation gain di atas batas ini tidak akan masuk rekomendasi." />

            <div>
                <x-input-label for="notes" value="Catatan rencana (opsional)" />
                <textarea id="notes" wire:model="notes" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="border-t border-gray-100 pt-4">
                <button type="submit"
                    class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 sm:w-auto">
                    Cari jalur yang sesuai
                </button>
            </div>
        </form>
    </div>
</div>
