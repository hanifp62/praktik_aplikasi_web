import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import type { Paginasi } from '@/components/admin/Tabel';
import type { Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { act, queue } from '@/routes/moderation';
import type { PropsBersama } from '@/types';

interface Laporan {
    id: number;
    jalur: string;
    gunung: string;
    segmen: string | null;
    pelapor: string;
    tanggalPendakian: string;
    dikirim: string;
    status: string;
    statusText: string;
    tags: string[];
    catatan: string | null;
    foto: { url: string; alt: string } | null;
}

interface Tindakan {
    value: string;
    label: string;
    konfirmasi: string | null;
}

interface Props extends PropsBersama {
    filter: string;
    statuses: Opsi[];
    actions: Tindakan[];
    reports: Paginasi<Laporan>;
}

/** Keputusan utama menonjol; menandai dan menghapus tetap ada tanpa bersaing dengannya. */
const varian: Record<string, 'primary' | 'danger'> = { APPROVE: 'primary', REJECT: 'danger' };

/**
 * FR-17 antrean moderasi. Laporan komunitas hanya tampil publik setelah disetujui dan
 * tidak pernah mengubah status resmi (R-010); keputusan dan otorisasinya di server.
 * Menolak atau menghapus membuang intel yang tidak dapat diambil ulang, jadi keduanya
 * bertanya dulu; menyetujui dan menandai dapat diubah lagi, jadi tidak dihalangi.
 */
export default function Queue() {
    const { flash, errors, filter, statuses, actions, reports } = usePage<Props>().props;
    const [alasan, setAlasan] = useState<Record<number, string>>({});
    const [sedang, setSedang] = useState<{ id: number; action: string } | null>(null);
    const [terakhir, setTerakhir] = useState<number | null>(null);

    const saring = (nilai: string) => router.get(queue.url({ query: { filter: nilai } }), {}, { preserveState: true, preserveScroll: true, replace: true });

    const tindak = (id: number, t: Tindakan) => {
        if (t.konfirmasi && !window.confirm(t.konfirmasi)) {
            return;
        }
        setSedang({ id, action: t.value });
        setTerakhir(id);
        router.post(
            act.url(id),
            { action: t.value, reason: alasan[id] ?? '' },
            {
                preserveScroll: true,
                onSuccess: () =>
                    setAlasan((a) => {
                        const salin = { ...a };
                        delete salin[id];
                        return salin;
                    }),
                onFinish: () => setSedang(null),
            },
        );
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Moderasi Laporan" description="Laporan komunitas hanya tampil untuk publik setelah disetujui. Laporan tidak pernah mengubah status resmi." />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="mb-4 max-w-xs">
                    <Label htmlFor="filter">Filter status</Label>
                    <Select id="filter" value={filter} onChange={(e) => saring(e.target.value)}>
                        <option value="">Semua</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>{s.label}</option>
                        ))}
                    </Select>
                </div>

                {reports.data.length === 0 ? (
                    <Card>
                        <p className="text-sm text-secondary">Tidak ada laporan pada filter ini.</p>
                    </Card>
                ) : (
                    <>
                        <p className="mb-2 text-sm text-muted">{reports.total} laporan</p>
                        <ul className="space-y-4">
                            {reports.data.map((r) => (
                                <li key={r.id}>
                                    <Card>
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h2 className="text-base font-semibold text-primary">{r.jalur}</h2>
                                                <p className="text-sm text-secondary">
                                                    {r.gunung}
                                                    {r.segmen ? <> &middot; segmen {r.segmen}</> : null}
                                                </p>
                                                <p className="mt-1 text-xs text-muted">
                                                    Dilaporkan oleh {r.pelapor} &middot; pendakian {r.tanggalPendakian} &middot; dikirim {r.dikirim}
                                                </p>
                                            </div>
                                            <span className="rounded-control bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">{r.statusText}</span>
                                        </div>

                                        {r.tags.length > 0 ? (
                                            <ul className="mt-3 flex flex-wrap gap-2" aria-label="Kondisi yang dilaporkan">
                                                {r.tags.map((tag) => (
                                                    <li key={tag} className="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">{tag}</li>
                                                ))}
                                            </ul>
                                        ) : null}

                                        {r.catatan ? <p className="mt-3 text-sm text-secondary">{r.catatan}</p> : null}

                                        {/* PRD §67: moderasi foto hanya mungkin bila fotonya terlihat. */}
                                        {r.foto ? (
                                            <figure className="mt-3">
                                                <img src={r.foto.url} alt={r.foto.alt} loading="lazy" className="max-h-64 rounded-control border border-subtle" />
                                                <figcaption className="mt-1 text-xs text-muted">Foto dari pelapor. Periksa sebelum menyetujui.</figcaption>
                                            </figure>
                                        ) : null}

                                        <div className="mt-4 space-y-2">
                                            <Label htmlFor={`reason-${r.id}`}>Alasan tindakan (opsional)</Label>
                                            <TextInput
                                                id={`reason-${r.id}`}
                                                className="text-sm"
                                                value={alasan[r.id] ?? ''}
                                                onChange={(e) => setAlasan((a) => ({ ...a, [r.id]: e.target.value }))}
                                                aria-describedby={terakhir === r.id && errors.reason ? `reason-${r.id}-hint reason-${r.id}-error` : `reason-${r.id}-hint`}
                                                aria-invalid={terakhir === r.id && errors.reason ? true : undefined}
                                            />
                                            {terakhir === r.id ? <InputError id={`reason-${r.id}-error`} messages={errors.reason} /> : null}
                                            <p id={`reason-${r.id}-hint`} className="text-xs text-secondary">
                                                Alasan pada penolakan dan penghapusan ditampilkan kepada pelapor di riwayatnya.
                                            </p>

                                            <div className="flex flex-wrap gap-2">
                                                {actions.map((t) => (
                                                    <Button
                                                        key={t.value}
                                                        size="sm"
                                                        variant={varian[t.value] ?? 'secondary'}
                                                        processing={sedang?.id === r.id && sedang.action === t.value}
                                                        disabled={sedang !== null}
                                                        onClick={() => tindak(r.id, t)}
                                                        aria-label={`${t.label}: laporan ${r.jalur}, pendakian ${r.tanggalPendakian}`}
                                                    >
                                                        {t.label}
                                                    </Button>
                                                ))}
                                            </div>
                                        </div>
                                    </Card>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6">
                            <Pagination links={reports.links} />
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

Queue.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
