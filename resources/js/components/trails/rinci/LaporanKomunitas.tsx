import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { thanks } from '@/routes/reports';
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import type { Komunitas } from './types';

/**
 * Laporan komunitas: informasi tambahan, tidak pernah status resmi (PRD §49, R-010).
 *
 * Pelapornya disebut: laporan bertanda tangan dinilai berbeda dari laporan anonim. Yang
 * dihargai kegunaan laporannya, bukan jumlahnya, dan pelapor tidak melihat tombolnya
 * pada laporannya sendiri. Foto tetap di dalam laporannya, bukan gambar utama halaman
 * (R-017). Laporan dipisah garis, bukan dibingkai kartu di dalam kartu (titipan W3 ke W5).
 */
export function LaporanKomunitas({ komunitas }: { komunitas: Komunitas }) {
    const [memproses, setMemproses] = useState<number | null>(null);

    const berterimaKasih = (id: number, ya: boolean) => {
        setMemproses(id);
        router.post(thanks.url({ report: id }), { ya }, { preserveScroll: true, onFinish: () => setMemproses(null) });
    };

    return (
        <Card
            title="Laporan kondisi dari komunitas"
            subtitle="Laporan pendaki lain bersifat informasi tambahan dan tidak mengubah status resmi."
        >
            <p className="mb-3">
                <LabelKomunitas />
            </p>
            {komunitas.available ? (
                <ul className="divide-y divide-subtle">
                    {komunitas.reports.map((r) => (
                        <li key={r.id} className="py-3 text-sm first:pt-0 last:pb-0">
                            <div className="flex flex-wrap gap-2">
                                {r.tags.map((tag) => (
                                    <span key={tag} className="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">
                                        {tag}
                                    </span>
                                ))}
                            </div>
                            {r.note ? <p className="mt-2 text-secondary">{r.note}</p> : null}

                            {r.photoUrl ? (
                                <img src={r.photoUrl} alt={r.photoAlt} loading="lazy" className="mt-2 max-h-56 rounded-control border border-subtle" />
                            ) : null}

                            {!r.milikSaya ? (
                                <div className="mt-2">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        aria-pressed={r.sudahTerimaKasih}
                                        processing={memproses === r.id}
                                        onClick={() => berterimaKasih(r.id, !r.sudahTerimaKasih)}
                                    >
                                        {r.sudahTerimaKasih ? 'Sudah berterima kasih' : 'Laporan ini menolong'}
                                        {r.thanksCount > 0 ? <span className="text-muted">&middot; {r.thanksCount}</span> : null}
                                    </Button>
                                </div>
                            ) : r.thanksCount > 0 ? (
                                <p className="mt-2 text-xs text-brand-900">{r.thanksCount} pendaki menyatakan laporan ini menolong mereka.</p>
                            ) : null}

                            <p className="mt-1 text-xs text-muted">
                                Dilaporkan <span className="font-medium text-secondary">{r.pelapor}</span> {r.dilaporkan} berdasarkan pendakian{' '}
                                {r.tanggalPendakian}
                                {r.segmen ? <> &middot; segmen {r.segmen}</> : null}
                                <span className="ml-1 rounded bg-surface-sunken px-1.5 py-0.5">{r.kesegaran}</span>
                            </p>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-secondary">{komunitas.message}</p>
            )}
        </Card>
    );
}
