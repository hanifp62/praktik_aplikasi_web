import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FormKontribusi, type IsianKontribusi, type PilihanKontribusi } from '@/components/community/FormKontribusi';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { contribute } from '@/routes';
import { checkpoints, geometry } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kredensial: Array<{ id: number; jenjang: string; penerbit: string | null; berlakuSampai: string | null; kawasan: string }>;
    trails: Array<{ id: number; nama: string; gunung: string | null; menunggu: string[]; terbit: boolean }>;
    isian: IsianKontribusi | null;
    pilihan: PilihanKontribusi;
}

/**
 * Pintu pemandu bersertifikat untuk mengisi data jalur di kawasannya (PRD §43, §109).
 * Menerbitkan tetap milik admin: gerbang §110 tidak berpindah tangan.
 */
export default function TrailContribution() {
    const { flash, kredensial, trails, isian, pilihan } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Kontribusi Data Jalur"
                    description="Anda mengisi data jalur di kawasan yang disahkan untuk sertifikat Anda. Admin yang memutuskan kapan data itu tayang untuk pendaki."
                />

                {flash.status ? <Alert variant="success" className="mb-6">{flash.status}</Alert> : null}

                <Card className="mb-6" title="Kredensial Anda">
                    <ul className="space-y-2 text-sm">
                        {kredensial.map((k) => (
                            <li key={k.id} className="flex flex-wrap items-baseline gap-x-2">
                                <span className="font-medium text-primary">{k.jenjang}</span>
                                <span className="text-secondary">
                                    diterbitkan {k.penerbit}
                                    {k.berlakuSampai ? <> &middot; berlaku sampai {k.berlakuSampai}</> : null}
                                </span>
                                <span className="w-full text-xs text-muted">Kawasan: {k.kawasan}</span>
                            </li>
                        ))}
                    </ul>
                    {/* Sertifikat BNSP berlaku tiga tahun, dan haknya berhenti bersamaan. */}
                    <p className="mt-3 text-xs text-secondary">Hak menyunting berhenti sendiri ketika sertifikat Anda habis masa berlakunya.</p>
                </Card>

                {isian ? <FormKontribusi key={isian.id} isian={isian} pilihan={pilihan} /> : null}

                <Card title="Jalur di kawasan Anda">
                    {trails.length === 0 ? (
                        <p className="text-sm text-secondary">Belum ada jalur tercatat di kawasan yang disahkan untuk Anda.</p>
                    ) : (
                        <ul className="divide-y divide-subtle">
                            {trails.map((t) => (
                                <li key={t.id} className="flex flex-wrap items-start justify-between gap-3 py-3">
                                    <div>
                                        <p className="font-medium text-primary">{t.nama}</p>
                                        <p className="text-sm text-secondary">{t.gunung}</p>
                                        {t.menunggu.length > 0 ? (
                                            <p className="mt-1 text-xs text-warn-900">Belum ada: {t.menunggu.join(', ')}</p>
                                        ) : (
                                            <p className="mt-1 text-xs text-muted">Data lengkap.</p>
                                        )}
                                        {!t.terbit ? <p className="mt-1 text-xs text-muted">Belum tayang untuk pendaki.</p> : null}
                                    </div>

                                    {/* Geometri dan koordinat pos paling mungkin dimiliki pemandu yang berjalan di jalurnya sambil membawa GPS. */}
                                    <div className="flex flex-wrap gap-2">
                                        <Button variant="secondary" size="sm" href={contribute.url({ query: { isi: t.id } })}>Isi data</Button>
                                        <Button variant="secondary" size="sm" href={geometry.url(t.id)}>Garis jalur</Button>
                                        <Button variant="secondary" size="sm" href={checkpoints.url(t.id)}>Pos</Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </div>
    );
}

TrailContribution.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
