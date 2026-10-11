import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { store } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Nilai {
    target_date: string;
    region: string;
    trip_type: string;
    expected_duration_minutes: number | string;
    preferred_challenge: string;
    max_elevation_gain_m: number | string;
    notes: string;
}

interface Props extends PropsBersama {
    nilai: Nilai;
    pilihan: { tripTypes: Opsi[]; challenges: Opsi[] };
    tanggalTerawal: string;
}

/**
 * FR-03 rencana pendakian. Isian awal datang dari preferensi profil; menyimpan
 * menjalankan mesin Route Fit di server lalu mengantar ke halaman hasilnya.
 */
export default function Create() {
    const { nilai, pilihan, tanggalTerawal, flash } = usePage<Props>().props;
    const form = useForm<Nilai>(nilai);
    // Batas laju run rekomendasi dilaporkan server di kunci 'form', bukan di satu isian.
    const galatUmum = (form.errors as Partial<Record<string, string>>).form;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url());
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
                    title="Rencana Pendakian"
                    description="Isi rencana perjalanan Anda. Sistem memakai rencana ini bersama profil Anda untuk menilai kecocokan jalur."
                />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {galatUmum ? <Alert variant="warning" className="mb-4">{galatUmum}</Alert> : null}

                <form onSubmit={kirim} className="space-y-5 rounded-lg bg-surface p-6">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField
                            name="target_date"
                            type="date"
                            min={tanggalTerawal}
                            label="Target tanggal (opsional)"
                            hint="Diisi agar jendela pemesanan izin dan status jalur diperiksa untuk tanggal itu."
                            value={form.data.target_date}
                            onChange={(e) => form.setData('target_date', e.target.value)}
                            error={form.errors.target_date}
                        />
                        <FormField
                            name="region"
                            label="Wilayah (opsional)"
                            placeholder="Jawa Tengah"
                            value={form.data.region}
                            onChange={(e) => form.setData('region', e.target.value)}
                            error={form.errors.region}
                        />
                    </div>

                    <SelectField
                        name="trip_type"
                        label="Tipe perjalanan"
                        required
                        placeholder="Pilih tipe perjalanan"
                        options={pilihan.tripTypes}
                        value={form.data.trip_type}
                        onChange={(e) => form.setData('trip_type', e.target.value)}
                        error={form.errors.trip_type}
                    />

                    <FormField
                        name="expected_duration_minutes"
                        type="number"
                        min={60}
                        label="Perkiraan durasi yang Anda targetkan"
                        hint="Dalam menit. Satu hari penuh sekitar 720, setengah hari sekitar 360."
                        value={form.data.expected_duration_minutes}
                        onChange={(e) => form.setData('expected_duration_minutes', e.target.value)}
                        error={form.errors.expected_duration_minutes}
                    />

                    <SelectField
                        name="preferred_challenge"
                        label="Tingkat tantangan yang dicari (opsional)"
                        placeholder="Tidak ada preferensi"
                        options={pilihan.challenges}
                        value={form.data.preferred_challenge}
                        onChange={(e) => form.setData('preferred_challenge', e.target.value)}
                        error={form.errors.preferred_challenge}
                    />

                    <FormField
                        name="max_elevation_gain_m"
                        type="number"
                        min={0}
                        label="Batas tanjakan (meter, opsional)"
                        hint="Jalur dengan tanjakan di atas batas ini tidak akan masuk rekomendasi."
                        value={form.data.max_elevation_gain_m}
                        onChange={(e) => form.setData('max_elevation_gain_m', e.target.value)}
                        error={form.errors.max_elevation_gain_m}
                    />

                    <TextareaField
                        name="notes"
                        label="Catatan rencana (opsional)"
                        value={form.data.notes}
                        onChange={(e) => form.setData('notes', e.target.value)}
                        error={form.errors.notes}
                    />

                    <div className="border-t border-subtle pt-4">
                        <Button type="submit" processing={form.processing}>Cari jalur yang sesuai</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

Create.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
