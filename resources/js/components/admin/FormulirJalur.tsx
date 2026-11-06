import type { InertiaFormProps } from '@inertiajs/react';
import { FormulirAdmin, PilihanAdmin, TeksPanjangAdmin, type Pilihan } from '@/components/admin/FormulirAdmin';
import { Checkbox, FormField, InputError } from '@/components/ui/Field';

export interface IsianJalur {
    mountain_id: string;
    name: string;
    description: string;
    distance_km: string;
    elevation_gain_m: string;
    elevation_loss_m: string;
    estimated_duration_minutes: string;
    technical_demand: string;
    navigation_complexity: string;
    water_availability: string;
    terrain_character: string[];
    camping_available: boolean;
    starting_point: string;
    weather_adm4_code: string;
    weather_reference_area: string;
    data_source_id: string;
}

export const jalurKosong: IsianJalur = {
    mountain_id: '',
    name: '',
    description: '',
    distance_km: '',
    elevation_gain_m: '',
    elevation_loss_m: '',
    estimated_duration_minutes: '',
    technical_demand: 'MODERATE',
    navigation_complexity: 'MODERATE',
    water_availability: 'UNKNOWN',
    terrain_character: [],
    camping_available: false,
    starting_point: '',
    weather_adm4_code: '',
    weather_reference_area: '',
    data_source_id: '',
};

export interface PilihanJalur {
    mountains: Pilihan[];
    sources: Pilihan[];
    technicalLevels: Pilihan[];
    navigationLevels: Pilihan[];
    waterLevels: Pilihan[];
    terrainOptions: Pilihan[];
}

interface FormulirJalurProps {
    judul: string;
    form: InertiaFormProps<IsianJalur>;
    pilihan: PilihanJalur;
    onSubmit: () => void;
    onBatal: () => void;
}

/** Isian jalur, seturut formulir Livewire yang digantikannya; labelnya disalin apa adanya. */
export function FormulirJalur({ judul, form, pilihan, onSubmit, onBatal }: FormulirJalurProps) {
    const { data, setData, errors } = form;
    const teks = (kunci: keyof IsianJalur) => (e: { target: { value: string } }) => setData(kunci, e.target.value);

    const ubahMedan = (nilai: string, dipilih: boolean) =>
        setData('terrain_character', dipilih ? [...data.terrain_character, nilai] : data.terrain_character.filter((t) => t !== nilai));

    return (
        <FormulirAdmin className="mb-6" judul={judul} onSubmit={onSubmit} processing={form.processing} errors={errors} onBatal={onBatal} fokusAwal="mountain_id">
            <PilihanAdmin name="mountain_id" label="Gunung" required placeholder="Pilih gunung" options={pilihan.mountains} value={data.mountain_id} onChange={(v) => setData('mountain_id', v)} error={errors.mountain_id} />

            <FormField name="name" label="Nama jalur" value={data.name} onChange={teks('name')} error={errors.name} />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <FormField name="distance_km" type="number" step="any" label="Jarak (km)" value={data.distance_km} onChange={teks('distance_km')} error={errors.distance_km} />
                <FormField name="elevation_gain_m" type="number" label="Tanjakan (m)" value={data.elevation_gain_m} onChange={teks('elevation_gain_m')} error={errors.elevation_gain_m} />
                <FormField name="elevation_loss_m" type="number" label="Turunan (m)" value={data.elevation_loss_m} onChange={teks('elevation_loss_m')} error={errors.elevation_loss_m} />
            </div>

            <FormField name="estimated_duration_minutes" type="number" label="Estimasi durasi (menit)" value={data.estimated_duration_minutes} onChange={teks('estimated_duration_minutes')} error={errors.estimated_duration_minutes} />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <PilihanAdmin name="technical_demand" label="Tingkat teknis" required placeholder="Pilih" options={pilihan.technicalLevels} value={data.technical_demand} onChange={(v) => setData('technical_demand', v)} error={errors.technical_demand} />
                <PilihanAdmin name="navigation_complexity" label="Kompleksitas navigasi" required placeholder="Pilih" options={pilihan.navigationLevels} value={data.navigation_complexity} onChange={(v) => setData('navigation_complexity', v)} error={errors.navigation_complexity} />
                <PilihanAdmin name="water_availability" label="Ketersediaan air" required placeholder="Pilih" options={pilihan.waterLevels} value={data.water_availability} onChange={(v) => setData('water_availability', v)} error={errors.water_availability} />
            </div>

            <fieldset aria-describedby={errors.terrain_character ? 'terrain_character-error' : undefined}>
                <legend className="block text-sm font-medium text-secondary">Karakter medan</legend>
                <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    {pilihan.terrainOptions.map((o) => (
                        <label key={o.value} className="flex min-h-11 items-center gap-2 rounded-control border border-subtle px-3 py-2 text-sm">
                            <Checkbox value={o.value} checked={data.terrain_character.includes(o.value)} onChange={(e) => ubahMedan(o.value, e.target.checked)} />
                            {o.label}
                        </label>
                    ))}
                </div>
                <InputError id="terrain_character-error" messages={errors.terrain_character} className="mt-2" />
            </fieldset>

            <label className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                <Checkbox checked={data.camping_available} onChange={(e) => setData('camping_available', e.target.checked)} />
                Tersedia area camping
            </label>

            <FormField name="starting_point" label="Titik awal" value={data.starting_point} onChange={teks('starting_point')} error={errors.starting_point} />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField name="weather_adm4_code" label="Kode wilayah BMKG (adm4)" hint="Dipakai untuk mengambil prakiraan area sekitar jalur." value={data.weather_adm4_code} onChange={teks('weather_adm4_code')} error={errors.weather_adm4_code} />
                <FormField name="weather_reference_area" label="Nama area referensi cuaca" value={data.weather_reference_area} onChange={teks('weather_reference_area')} error={errors.weather_reference_area} />
            </div>

            <PilihanAdmin name="data_source_id" label="Sumber data" placeholder="Belum ditentukan" options={pilihan.sources} value={data.data_source_id} onChange={(v) => setData('data_source_id', v)} error={errors.data_source_id} />

            <TeksPanjangAdmin name="description" label="Deskripsi" value={data.description} onChange={(v) => setData('description', v)} error={errors.description} />
        </FormulirAdmin>
    );
}
