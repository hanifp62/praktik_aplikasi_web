import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    northStar: string | null;
    funnel: Array<{ event: string; label: string; count: string }>;
}

/**
 * FR-20 corong perencanaan (§63). Angka agregat per peristiwa saja, sudah diformat
 * server; tidak ada data pribadi yang sampai ke halaman ini.
 */
export default function Analytics() {
    const { northStar, funnel } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Analitik" description="Corong perencanaan pendakian, dari profil sampai laporan kondisi." />

                <AdminNav />

                <Card className="mb-6" title="Ukuran utama" subtitle="Persentase pemilihan jalur yang berlanjut sampai cek kesiapan sebelum berangkat.">
                    <p className="text-3xl font-semibold text-primary">{northStar ?? 'Belum ada data'}</p>
                </Card>

                <Card title="Corong aktivasi">
                    {funnel.length === 0 ? (
                        <EmptyState
                            jenis="menunggu"
                            menunggu="aktivitas perencanaan pendaki"
                            title="Belum ada peristiwa tercatat"
                            description="Corong dihitung dari peristiwa yang benar-benar terjadi di alur utama."
                        />
                    ) : (
                        <ol className="space-y-2">
                            {funnel.map((f) => (
                                <li key={f.event} className="flex items-center justify-between rounded-control border border-subtle px-3 py-2 text-sm">
                                    <span className="text-secondary">{f.label}</span>
                                    <span className="font-semibold text-primary">{f.count}</span>
                                </li>
                            ))}
                        </ol>
                    )}
                </Card>
            </div>
        </div>
    );
}

Analytics.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
