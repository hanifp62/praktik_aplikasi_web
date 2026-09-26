import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianRencana, type BatasRencana, type IsianRencanaData } from '@/components/trips/rencana/IsianRencana';
import type { Opsi } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { show, update } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trip: { id: number; jalur: string };
    awal: IsianRencanaData;
    tripTypes: Opsi[];
    tanggalTerawal: string;
    batas: BatasRencana;
}

/**
 * Mengubah rencana yang belum dimulai (features/04). Jalurnya tetap: jalur lain berarti
 * rencana lain dengan daftar persiapannya sendiri. Perubahan langsung terlihat di
 * langkah perjalanan halaman trip.
 */
export default function Edit() {
    const { trip, awal, tripTypes, tanggalTerawal, batas } = usePage<Props>().props;
    const form = useForm<IsianRencanaData>(awal);

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(update.url(trip.id));
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Ubah Rencana Trip" description={trip.jalur} />

                <form onSubmit={kirim} className="space-y-5 rounded-lg bg-surface p-6">
                    <p className="text-sm text-secondary">Jalur tidak dapat diganti di sini. Untuk jalur lain, buat rencana trip baru.</p>

                    <IsianRencana
                        data={form.data}
                        errors={form.errors}
                        setField={(kunci, nilai) => form.setData(kunci, nilai)}
                        tripTypes={tripTypes}
                        tanggalTerawal={tanggalTerawal}
                        batas={batas}
                    />

                    <div className="flex flex-wrap gap-3 border-t border-subtle pt-4">
                        <Button type="submit" processing={form.processing}>Simpan perubahan</Button>
                        <Button variant="secondary" href={show.url(trip.id)}>Batal</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

Edit.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
