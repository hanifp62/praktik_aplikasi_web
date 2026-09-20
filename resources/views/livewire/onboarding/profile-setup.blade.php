<div class="py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <x-ui.page-header
            title="Profil Pendaki"
            description="Data ini dipakai untuk menilai kecocokan jalur dengan Anda. Pengalaman yang Anda isi adalah masukan untuk rekomendasi, bukan sertifikasi kemampuan." />

        <nav aria-label="Langkah pengisian profil" class="mb-6">
            <ol class="flex gap-2 text-sm">
                @foreach (['Pengalaman', 'Rekam jejak', 'Preferensi'] as $index => $stepLabel)
                    <li class="flex-1">
                        <span @class([
                            'block rounded-md border px-3 py-2 text-center',
                            'border-brand-600 bg-brand-50 font-medium text-brand-900' => $step === $index + 1,
                            'border-subtle text-secondary' => $step !== $index + 1,
                        ]) @if ($step === $index + 1) aria-current="step" @endif>
                            {{ $index + 1 }}. {{ $stepLabel }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </nav>

        <form wire:submit="save" class="space-y-6 rounded-lg bg-white p-6">
            @if ($step === 1)
                <fieldset class="space-y-4">
                    <legend class="text-base font-medium text-primary">Tingkat pengalaman</legend>

                    <x-form.select name="experience_level" label="Tingkat pengalaman" required
                        placeholder="Pilih tingkat pengalaman"
                        :options="collect($experienceLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />

                    <x-form.field name="region_preference" label="Preferensi wilayah (opsional)"
                        placeholder="Jawa Tengah" />

                    <div>
                        <x-input-label for="bio" value="Catatan singkat (opsional)" />
                        <textarea id="bio" wire:model="bio" rows="3"
                            class="mt-1 block w-full rounded-md border-control focus:border-brand-600 focus:ring-brand-600"></textarea>
                        <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                    </div>
                </fieldset>
            @elseif ($step === 2)
                <fieldset class="space-y-4">
                    <legend class="text-base font-medium text-primary">Rekam jejak pendakian</legend>
                    <p class="text-sm text-secondary">
                        Riwayat pendakian menjadi konteks tambahan. Menyelesaikan satu jalur sulit tidak
                        otomatis menaikkan tingkat pengalaman Anda.
                    </p>

                    <x-form.field name="completed_hikes_count" type="number" min="0"
                        label="Jumlah pendakian yang pernah diselesaikan" />

                    <x-form.checkbox-group name="terrain_experience" label="Medan yang pernah dilalui"
                        :options="collect($terrainOptions)->mapWithKeys(fn ($terrain) => [$terrain->value => $terrain->label()])->all()" />

                    <x-form.select name="navigation_experience" label="Pengalaman navigasi" required
                        placeholder="Pilih pengalaman navigasi"
                        :options="collect($navigationLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.field name="longest_hike_duration_minutes" type="number" min="0"
                            label="Durasi pendakian terlama (menit)" />
                        <x-form.field name="highest_elevation_gain_m" type="number" min="0"
                            label="Elevation gain tertinggi (meter)" />
                    </div>
                </fieldset>
            @else
                <fieldset class="space-y-4">
                    <legend class="text-base font-medium text-primary">Preferensi perjalanan</legend>

                    <x-form.select name="preferred_duration" label="Durasi yang Anda sukai" required
                        placeholder="Pilih durasi"
                        :options="collect($durations)->mapWithKeys(fn ($duration) => [$duration->value => $duration->label()])->all()" />

                    <x-form.select name="preferred_trip_type" label="Tipe perjalanan" required
                        placeholder="Pilih tipe perjalanan"
                        :options="collect($tripTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />

                    <x-form.select name="preferred_challenge" label="Tingkat tantangan yang dicari (opsional)"
                        placeholder="Tidak ada preferensi"
                        :options="collect($challenges)->mapWithKeys(fn ($challenge) => [$challenge->value => $challenge->label()])->all()" />

                    <x-form.field name="max_elevation_gain_preference_m" type="number" min="0"
                        label="Batas elevation gain yang nyaman (meter, opsional)" />
                </fieldset>
            @endif

            <div class="flex items-center justify-between gap-3 border-t border-subtle pt-4">
                {{-- :disabled, bukan @disabled: direktif itu mengompilasi jadi PHP mentah
                     di dalam tag komponen dan parser komponen Blade tidak dapat membacanya. --}}
                <x-ui.button variant="secondary" type="button" wire:click="previousStep" :disabled="$step === 1">Kembali</x-ui.button>

                @if ($step < 3)
                    <x-ui.button type="button" wire:click="nextStep">Lanjut</x-ui.button>
                @else
                    <x-ui.button type="submit" target="save">Simpan profil</x-ui.button>
                @endif
            </div>
        </form>
    </div>
</div>
