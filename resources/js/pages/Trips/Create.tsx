import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianRencana, type BatasRencana, type IsianRencanaData } from '@/components/trips/rencana/IsianRencana';
import type { Opsi } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { Checkbox, InputError, Label, Select } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { store } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Isian extends IsianRencanaData {
    trail_id: string;
    hiking_goal_id: number | null;
    reminders_enabled: boolean;
}

interface Props extends PropsBersama {
    awal: Isian;
    trails: Opsi[];
    tripTypes: Opsi[];
    tanggalTerawal: string;
    batas: BatasRencana;
    jadwalPengingat: string;
}

/**
 * FR-08 pembuatan trip. Butir persiapan dibuat dari jalur yang dipilih begitu trip
 * disimpan, lalu pendaki diantar ke daftar persiapannya: langkah berikutnya di rencana.
 * Pengingat surel mati sampai pendaki mencentangnya sendiri.
 */
export default function Create() {
    const { awal, trails, tripTypes, tanggalTerawal, batas, jadwalPengingat } = usePage<Props>().props;
    const form = useForm<Isian>(awal);

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url());
    };

    const pilihan = (name: 'trail_id', label: string, placeholder: string, opsi: Opsi[]) => (
        <div>
            <Label htmlFor={name}>{label}</Label>
            <Select
                id={name}
                name={name}
                required
                value={form.data[name]}
                onChange={(e) => form.setData(name, e.target.value)}
                aria-invalid={form.errors[name] ? true : undefined}
                aria-describedby={form.errors[name] ? `${name}-error` : undefined}
            >
                <option value="">{placeholder}</option>
                {opsi.map((o) => (
                    <option key={o.value} value={o.value}>{o.label}</option>
                ))}
            </Select>
            <InputError id={`${name}-error`} messages={form.errors[name]} className="mt-2" />
        </div>
    );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Buat Rencana Trip"
                    description="Rencana trip menghubungkan jalur yang Anda pilih dengan daftar persiapan dan pemeriksaan kondisi."
                />

                <form onSubmit={kirim} className="space-y-5 rounded-lg bg-surface p-6">
                    {pilihan('trail_id', 'Jalur', 'Pilih jalur', trails)}

                    <IsianRencana
                        data={form.data}
                        errors={form.errors}
                        setField={(kunci, nilai) => form.setData(kunci, nilai)}
                        tripTypes={tripTypes}
                        tanggalTerawal={tanggalTerawal}
                        batas={batas}
                    />

                    <div>
                        <label htmlFor="reminders_enabled" className="flex min-h-11 items-start gap-3">
                            <Checkbox
                                id="reminders_enabled"
                                name="reminders_enabled"
                                checked={form.data.reminders_enabled}
                                onChange={(e) => form.setData('reminders_enabled', e.target.checked)}
                                aria-describedby="reminders_enabled-hint"
                                className="mt-0.5 h-5 w-5"
                            />
                            <span className="text-sm text-primary">Kirim pengingat ke email saya</span>
                        </label>
                        <p id="reminders_enabled-hint" className="ml-8 text-xs text-muted">
                            {jadwalPengingat} Dapat dimatikan kapan saja di halaman trip.
                        </p>
                    </div>

                    <div className="border-t border-subtle pt-4">
                        <Button type="submit" processing={form.processing}>Simpan dan lanjut ke persiapan</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

Create.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
