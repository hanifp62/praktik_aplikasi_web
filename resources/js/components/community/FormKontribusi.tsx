import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Checkbox, FormField, InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { contribute } from '@/routes';
import { store } from '@/routes/contribute';
import type { Opsi } from './types';

export interface IsianKontribusi {
    id: number;
    nama: string;
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
    description: string;
    catatan_sumber: string;
}

export interface PilihanKontribusi {
    teknis: Opsi[];
    navigasi: Opsi[];
    air: Opsi[];
    medan: Opsi[];
}

type DataFormulir = Omit<IsianKontribusi, 'id' | 'nama'>;

/**
 * Formulir data jalur untuk pemandu bersertifikat. Server mengotorisasi ulang saat
 * menyimpan (policy yang sama dengan pembukaan formulir), karena kredensial dapat
 * dicabut di antara membuka formulir dan menekan simpan.
 */
export function FormKontribusi({ isian, pilihan }: { isian: IsianKontribusi; pilihan: PilihanKontribusi }) {
    const { id, nama, ...awal } = isian;
    const form = useForm<DataFormulir>(awal);
    const galat = form.errors as Record<string, string | undefined>;
    const galatMedan = galat.terrain_character ?? Object.entries(galat).find(([k]) => k.startsWith('terrain_character.'))?.[1];

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(id), { preserveScroll: true });
    };

    const ubahMedan = (nilai: string, dipilih: boolean) =>
        form.setData('terrain_character', dipilih ? [...form.data.terrain_character, nilai] : form.data.terrain_character.filter((m) => m !== nilai));

    const pilih = (kunci: 'technical_demand' | 'navigation_complexity' | 'water_availability', label: string, opsi: Opsi[]) => (
        <div>
            <Label htmlFor={kunci}>{label}</Label>
            <Select
                id={kunci}
                required
                value={form.data[kunci]}
                onChange={(e) => form.setData(kunci, e.target.value)}
                aria-describedby={form.errors[kunci] ? `${kunci}-error` : undefined}
            >
                <option value="">Pilih</option>
                {opsi.map((o) => (
                    <option key={o.value} value={o.value}>{o.label}</option>
                ))}
            </Select>
            <InputError id={`${kunci}-error`} messages={form.errors[kunci]} className="mt-2" />
        </div>
    );

    return (
        <Card className="mb-6" title="Isi data jalur" subtitle={nama}>
            <form onSubmit={simpan} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <FormField name="distance_km" type="number" step="any" label="Jarak (km)" value={form.data.distance_km} onChange={(e) => form.setData('distance_km', e.target.value)} error={form.errors.distance_km} />
                    <FormField name="elevation_gain_m" type="number" label="Elevation gain (m)" value={form.data.elevation_gain_m} onChange={(e) => form.setData('elevation_gain_m', e.target.value)} error={form.errors.elevation_gain_m} />
                    <FormField name="elevation_loss_m" type="number" label="Elevation loss (m)" value={form.data.elevation_loss_m} onChange={(e) => form.setData('elevation_loss_m', e.target.value)} error={form.errors.elevation_loss_m} />
                </div>

                <FormField
                    name="estimated_duration_minutes"
                    type="number"
                    label="Estimasi durasi (menit)"
                    hint="Perkiraan waktu tempuh pendaki dengan kecepatan sedang, bukan waktu tercepat."
                    value={form.data.estimated_duration_minutes}
                    onChange={(e) => form.setData('estimated_duration_minutes', e.target.value)}
                    error={form.errors.estimated_duration_minutes}
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {pilih('technical_demand', 'Tingkat teknis', pilihan.teknis)}
                    {pilih('navigation_complexity', 'Kompleksitas navigasi', pilihan.navigasi)}
                    {pilih('water_availability', 'Ketersediaan air', pilihan.air)}
                </div>

                <fieldset aria-describedby={galatMedan ? 'terrain_character-error' : undefined}>
                    <legend className="block text-sm font-medium text-secondary">Karakter medan</legend>
                    <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {pilihan.medan.map((m) => (
                            <label key={m.value} className="flex min-h-11 items-center gap-2 rounded-control border border-subtle px-3 py-2 text-sm text-primary">
                                <Checkbox
                                    value={m.value}
                                    checked={form.data.terrain_character.includes(String(m.value))}
                                    onChange={(e) => ubahMedan(String(m.value), e.target.checked)}
                                />
                                {m.label}
                            </label>
                        ))}
                    </div>
                    <InputError id="terrain_character-error" messages={galatMedan} className="mt-2" />
                </fieldset>

                <label className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                    <Checkbox checked={form.data.camping_available} onChange={(e) => form.setData('camping_available', e.target.checked)} />
                    Tersedia area camping
                </label>

                <FormField name="starting_point" label="Titik awal" value={form.data.starting_point} onChange={(e) => form.setData('starting_point', e.target.value)} error={form.errors.starting_point} />

                <div>
                    <Label htmlFor="description">Deskripsi jalur</Label>
                    <Textarea
                        id="description"
                        rows={3}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        aria-describedby={form.errors.description ? 'description-error' : undefined}
                    />
                    <InputError id="description-error" messages={form.errors.description} className="mt-2" />
                </div>

                {/* Wajib: angka tanpa asal tidak dapat dinilai pembacanya (§60). */}
                <div>
                    <Label htmlFor="catatan_sumber">Dari mana data ini Anda ketahui</Label>
                    <Textarea
                        id="catatan_sumber"
                        rows={2}
                        required
                        placeholder="Misalnya: hasil pengukuran sendiri Agustus 2026, atau data resmi basecamp Cemoro Sewu."
                        value={form.data.catatan_sumber}
                        onChange={(e) => form.setData('catatan_sumber', e.target.value)}
                        aria-describedby={['catatan_sumber-hint', form.errors.catatan_sumber ? 'catatan_sumber-error' : null].filter(Boolean).join(' ')}
                    />
                    <InputError id="catatan_sumber-error" messages={form.errors.catatan_sumber} className="mt-2" />
                    <p id="catatan_sumber-hint" className="mt-1 text-xs text-secondary">Tersimpan bersama data dan terbaca admin saat meninjau.</p>
                </div>

                {galat.form ? <Alert variant="danger">{galat.form}</Alert> : null}

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" processing={form.processing}>Simpan data</Button>
                    <Button variant="secondary" href={contribute.url()}>Batal</Button>
                </div>
            </form>
        </Card>
    );
}
