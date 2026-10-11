import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FitTrip } from '@/components/trips/FitTrip';
import type { BarisTrip, TautanHalaman } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { create as goalsCreate } from '@/routes/goals';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trips: { data: BarisTrip[]; links: TautanHalaman[] };
}

/** Trip Saya: seluruh rencana pendakian beserta kemajuan dan status kesiapannya. */
export default function Index() {
    const { trips } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Trip" description="Semua rencana pendakian Anda beserta status kesiapannya." />

                {trips.data.length === 0 ? (
                    <Card>
                        <p className="text-sm text-secondary">Belum ada rencana trip.</p>
                        <Button href={goalsCreate.url()} className="mt-3">Mulai dari rencana pendakian</Button>
                    </Card>
                ) : (
                    <>
                        <ul className="space-y-3">
                            {trips.data.map((trip) => (
                                <li key={trip.id}>
                                    {/* Kartu tanpa kendali lain, jadi seluruh kartu menjadi sasaran
                                        tautan (pola yang sama dengan baris jalur di Jelajah). */}
                                    <Card className="group relative">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <h2 className="text-base font-semibold text-primary">
                                                    <Tautan href={trip.url} className="rounded-sm after:absolute after:inset-0 group-hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                                        {trip.name}
                                                    </Tautan>
                                                </h2>
                                                <p className="text-sm text-secondary">
                                                    {trip.trail} &middot; {trip.mountain}
                                                </p>
                                                <p className="mt-1 text-sm text-muted">
                                                    {trip.tanggal} &middot; {trip.tipe}
                                                </p>
                                                {trip.persiapan ? <p className="mt-1 text-sm text-secondary">Persiapan: {trip.persiapan}</p> : null}
                                                {trip.fit ? <FitTrip fit={trip.fit} /> : null}
                                            </div>
                                            <div className="text-right">
                                                <span className="inline-block rounded-control bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">{trip.status}</span>
                                                {trip.kesiapan ? (
                                                    <p className={trip.kesiapan.peringatan ? 'mt-1 text-xs font-medium text-warn-900' : 'mt-1 text-xs text-secondary'}>
                                                        {trip.kesiapan.teks}
                                                    </p>
                                                ) : null}
                                            </div>
                                        </div>
                                    </Card>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6">
                            <Pagination links={trips.links} />
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
