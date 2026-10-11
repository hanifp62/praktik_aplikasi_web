import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BarisKosong, BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { Label, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { audit } from '@/routes/admin';
import type { PropsBersama } from '@/types';

interface Jejak {
    id: number;
    waktu: string;
    aktor: string;
    aksi: string;
    entitas: string;
    perubahan: string | null;
}

interface Props extends PropsBersama {
    action: string;
    logs: Paginasi<Jejak>;
}

/**
 * FR-19 jejak audit, baca-saja. Saringan hidup di URL (dapat dibagikan, tombol kembali
 * bekerja) dan memuat ulang data tanpa memuat ulang halaman.
 */
export default function AuditLogs() {
    const { action, logs } = usePage<Props>().props;
    const [saringan, setSaringan] = useState(action);
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const t = setTimeout(() => {
            router.get(audit.url({ query: saringan ? { action: saringan } : {} }), {}, { preserveState: true, preserveScroll: true, replace: true, only: ['logs', 'action'] });
        }, 400);
        return () => clearTimeout(t);
    }, [saringan]);

    return (
        <div className="py-8">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Audit Log"
                    description="Catatan tindakan kritis: perubahan status resmi, data jalur dan gunung, moderasi, sumber data, dan peran pengguna."
                />

                <AdminNav />

                <div className="mb-4 max-w-sm">
                    <Label htmlFor="action">Filter aksi</Label>
                    <TextInput id="action" value={saringan} onChange={(e) => setSaringan(e.target.value)} placeholder="trail.published" />
                </div>

                <BingkaiTabel keterangan="Daftar audit log" kolom={['Waktu', 'Aktor', 'Aksi', 'Entitas', 'Perubahan']}>
                    {logs.data.length === 0 ? (
                        <BarisKosong
                            kolom={5}
                            judul="Belum ada jejak audit yang cocok"
                            penjelasan="Saringan yang dipasang tidak menemukan apa pun. Jejak audit tidak pernah dihapus, jadi daftar yang kosong berarti belum ada tindakan yang tercatat pada rentang ini, bukan ada yang hilang."
                        />
                    ) : (
                        logs.data.map((log) => (
                            <tr key={log.id} className="border-b border-subtle align-top">
                                <td className="p-4 text-secondary">{log.waktu}</td>
                                <td className="p-4 text-secondary">{log.aktor}</td>
                                <td className="p-4 font-medium text-primary">{log.aksi}</td>
                                <td className="p-4 text-secondary">{log.entitas}</td>
                                <td className="p-4 text-xs text-secondary">
                                    {log.perubahan ? (
                                        <details>
                                            <summary className="inline-flex min-h-11 cursor-pointer items-center rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">Lihat detail</summary>
                                            <pre className="relative mt-2 max-w-md overflow-x-auto rounded bg-surface-sunken p-2">{log.perubahan}</pre>
                                        </details>
                                    ) : (
                                        '-'
                                    )}
                                </td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>

                <div className="mt-6">
                    <Pagination links={logs.links} />
                </div>
            </div>
        </div>
    );
}

AuditLogs.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
