import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
import { summary } from '@/routes/history';
import { create as reportsCreate } from '@/routes/reports';
import { index as tripsIndex, show as tripShow } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Entri {
    id: number;
    tripId: number;
    trailId: number;
    jalur: string | null;
    gunung: string | null;
    tanggal: string;
    jenisTrip: string | null;
    hasil: string | null;
    persiapan: string;
    jarak: string;
    durasi: string;
    catatan: string | null;
    laporan: { status: string; alasanDitolak: string | null } | null;
}

interface Props extends PropsBersama {
    entri: { data: Entri[]; links: TautanHalaman[] };
    peringatanFoto: string | null;
}

const tautanAksi = 'inline-flex min-h-11 items-center text-brand-ink underline';

/** FR-14: catatan perjalanan, kelengkapan persiapan saat itu, dan laporan kondisi yang dikirim. */
export default function HikingHistory() {
    const { flash, entri, peringatanFoto } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title="Riwayat Pendakian" description="Catatan perjalanan Anda, kelengkapan persiapan saat itu, dan laporan kondisi yang Anda kirim." />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {peringatanFoto ? <Alert variant="warning" className="mb-4">{peringatanFoto}</Alert> : null}

                {entri.data.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada pendakian yang tercatat selesai."
                        description="Trip tercatat di sini setelah Anda menandainya selesai di halaman trip. Trip yang masih direncanakan atau sedang berjalan ada di daftar trip."
                    >
                        <Button variant="secondary" href={tripsIndex.url()}>Buka daftar trip</Button>
                    </EmptyState>
                ) : (
                    <>
                        <ul className="space-y-3">
                            {entri.data.map((e) => (
                                <li key={e.id} className="rounded-lg border border-subtle bg-surface p-4 sm:p-6">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="text-base font-semibold text-primary">
                                                <Tautan href={summary.url(e.tripId)} className="rounded-control py-1 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                                    {e.jalur}
                                                </Tautan>
                                            </h2>
                                            <p className="text-sm text-secondary">{e.gunung}</p>
                                            <p className="mt-1 text-sm text-muted">
                                                {e.tanggal} &middot; {e.jenisTrip} &middot; {e.hasil}
                                            </p>
                                            <p className="mt-1 text-sm text-muted">
                                                Jarak jalur {e.jarak} &middot; Lama pendakian {e.durasi}
                                            </p>
                                        </div>
                                        {/* Hitungan butir dari server, bukan persen (ruling AF-4, R-005, R-007). */}
                                        <p className="text-sm text-secondary sm:max-w-[14rem] sm:text-right">{e.persiapan}</p>
                                    </div>

                                    {e.catatan ? <p className="mt-3 text-sm text-secondary">{e.catatan}</p> : null}

                                    <div className="mt-2 flex flex-wrap gap-x-4 text-sm">
                                        <Tautan href={tripShow.url(e.tripId)} className={tautanAksi}>Lihat trip</Tautan>
                                        {e.laporan ? (
                                            <span className="inline-flex min-h-11 items-center text-secondary">Laporan kondisi: {e.laporan.status}</span>
                                        ) : (
                                            <Tautan href={reportsCreate.url({ query: { trail: e.trailId, trip: e.tripId } })} className={tautanAksi}>
                                                Kirim laporan kondisi
                                            </Tautan>
                                        )}
                                    </div>

                                    {/* Pelapor berhak tahu apa yang salah, kalau tidak ia tidak dapat memperbaikinya. */}
                                    {e.laporan?.alasanDitolak ? (
                                        <p className="mt-2 rounded-control border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">Alasan moderator: {e.laporan.alasanDitolak}</p>
                                    ) : null}
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6">
                            <Pagination links={entri.links} />
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

HikingHistory.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
