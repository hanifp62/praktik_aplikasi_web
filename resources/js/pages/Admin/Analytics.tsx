import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
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
                <PageHeader title="Analitik" description="Funnel perencanaan pendakian, dari profil sampai laporan kondisi." />

                <AdminNav />

                <Card className="mb-6" title="North star metric" subtitle="Persentase pemilihan jalur yang berlanjut sampai pre-departure check.">
                    <p className="text-3xl font-semibold text-primary">{northStar ?? 'Belum ada data'}</p>
                </Card>

                <Card title="Funnel">
                    <ol className="space-y-2">
                        {funnel.length === 0 ? (
                            <li>
                                <p className="font-medium text-primary">Belum ada peristiwa tercatat</p>
                                <p className="mt-1 max-w-prose text-secondary">Corong dihitung dari peristiwa yang benar-benar terjadi. Kosongnya berarti belum ada yang memakai alur ini, bukan alurnya rusak.</p>
                            </li>
                        ) : (
                            funnel.map((f) => (
                                <li key={f.event} className="flex items-center justify-between rounded-control border border-subtle px-3 py-2 text-sm">
                                    <span className="text-secondary">{f.label}</span>
                                    <span className="font-semibold text-primary">{f.count}</span>
                                </li>
                            ))
                        )}
                    </ol>
                </Card>
            </div>
        </div>
    );
}

Analytics.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
