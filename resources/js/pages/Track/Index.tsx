import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { create as goalsCreate } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface TripSiapMulai {
    id: number;
    name: string;
    trail: string | null;
    mountain: string | null;
    tanggal: string;
    status: string;
    siap: boolean;
    kesiapan: string | null;
    urls: { start: string; show: string; readiness: string };
}

interface Props extends PropsBersama {
    trips: TripSiapMulai[];
}

/**
 * Tab Mendaki tanpa pendakian berjalan: trip yang dapat dimulai. Memulai tidak dapat
 * dikembalikan ke rencana, jadi tombolnya meminta konfirmasi sebaris, bukan dialog.
 */
export default function TrackIndex() {
    const { trips } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    akar
                    title="Mendaki"
                    description="Mode lapangan: peta jalur, pos berikutnya, dan rekam jejak di perangkat. Buka sebelum sinyal hilang dan biarkan tetap terbuka selama pendakian."
                />

                {trips.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada trip yang dapat dimulai."
                        description="Mode pendakian dibuka dari trip yang sudah direncanakan. Rencanakan trip dulu, lalu kembali ke sini pada hari keberangkatan."
                    >
                        <Button href={tripsIndex.url()}>Buka trip saya</Button>
                        <Button variant="secondary" href={goalsCreate.url()}>Cari jalur yang cocok</Button>
                    </EmptyState>
                ) : (
                    <ul className="space-y-3">
                        {trips.map((trip) => (
                            <li key={trip.id}>
                                <KartuMulai trip={trip} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}

function KartuMulai({ trip }: { trip: TripSiapMulai }) {
    const [yakin, setYakin] = useState(false);
    const [memproses, setMemproses] = useState(false);

    const mulai = () => {
        setMemproses(true);
        router.post(trip.urls.start, {}, { onFinish: () => setMemproses(false) });
    };

    return (
        <section aria-labelledby={`trip-${trip.id}`} className="rounded-lg border border-subtle bg-surface p-5">
            <h2 id={`trip-${trip.id}`} className="text-lg font-semibold text-primary">{trip.name}</h2>
            <p className="text-sm text-secondary">
                {trip.trail}
                {trip.mountain ? ` · ${trip.mountain}` : null}
            </p>
            <p className="mt-1 text-sm text-muted">{trip.tanggal} · {trip.status}</p>
            <p className="mt-2 text-sm text-secondary">
                {trip.kesiapan ? `Cek kesiapan terakhir: ${trip.kesiapan}.` : 'Cek kesiapan belum pernah dilakukan untuk trip ini.'}
            </p>

            <div className="mt-4 flex flex-wrap items-center gap-2">
                {yakin ? (
                    <>
                        <Button onClick={mulai} processing={memproses}>Ya, mulai sekarang</Button>
                        <Button variant="secondary" onClick={() => setYakin(false)} disabled={memproses}>Batal</Button>
                        <p role="status" className="basis-full text-sm text-secondary">
                            Pendakian yang sudah dimulai tidak dapat dikembalikan menjadi rencana.
                        </p>
                    </>
                ) : (
                    <>
                        <Button onClick={() => setYakin(true)}>Mulai mendaki</Button>
                        <Button variant="secondary" href={trip.urls.readiness}>Cek kesiapan</Button>
                    </>
                )}
            </div>
        </section>
    );
}

TrackIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
