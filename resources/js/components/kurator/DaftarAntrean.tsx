import type { ReactNode } from 'react';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { BarisAntrean } from './types';

interface DaftarAntreanProps {
    id: string;
    judul: string;
    keterangan: string;
    baris: BarisAntrean[];
    kosong: string;
    /** Aksi tambahan per baris, misalnya "Catat pengganti" di antrean tinjau. */
    aksi?: (b: BarisAntrean) => ReactNode;
}

/**
 * Satu antrean meja kurator: judul bagian, kalimat yang menyebut apa yang ditunggu, dan daftar
 * catatan. Setiap baris menyebut sasaran, status dengan bentuk dan kata (DK-32), keadaan
 * verifikasinya, badan, dan masa berlakunya; nama sasaran membuka rincian catatan. Antrean kosong
 * menulis kalimat, bukan ruang kosong.
 */
export function DaftarAntrean({ id, judul, keterangan, baris, kosong, aksi }: DaftarAntreanProps) {
    return (
        <section aria-labelledby={id} className="border-t border-subtle pt-6">
            <h2 id={id} className="text-bagian text-primary">
                {judul} <span className="text-secondary tabular-nums">({baris.length})</span>
            </h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">{keterangan}</p>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">{kosong}</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((b) => (
                        <li key={`${id}-${b.id}`} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <Tautan href={b.url} className="inline-flex min-h-11 items-center break-words font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:rounded-control focus-visible:ring-2 focus-visible:ring-brand-600">
                                    {b.sasaran}
                                </Tautan>
                                <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <StatusAkses nilai={b.status} label={b.statusLabel} />
                                    <Lencana nada={b.keadaan === 'Draf' ? 'netral' : 'merek'}>{b.keadaan}</Lencana>
                                    {b.alasanTinjau ? <Lencana nada="peringatan">{b.alasanTinjau}</Lencana> : null}
                                </div>
                                <p className="mt-1 text-meta text-muted">
                                    {[b.badan, b.berlaku, b.pencatat ? `Dicatat ${b.pencatat}` : null].filter(Boolean).join('. ')}
                                </p>
                            </div>
                            {aksi ? <div className="flex shrink-0 flex-wrap gap-2">{aksi(b)}</div> : null}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
