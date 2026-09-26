import type { ReactNode } from 'react';
import { FitLine } from '@/components/trails/FitLine';
import type { FitRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export interface KolomJalur {
    id: number;
    name: string;
    mountain: string | null;
    url: string;
    fit: FitRingkas | null;
    status: { status: StatusResmi; label: string | null };
    /** Jalur ini ada di simpanan pembaca; tautan lama dapat membawa jalur yang tidak ia simpan. */
    tersimpan: boolean;
    /** Tautan GET ke perbandingan yang sama tanpa kolom ini. Tidak pernah menghapus simpanan. */
    hapus: string;
}

export interface BarisBanding {
    label: string;
    nilai: string[];
    berbeda: boolean | null;
}

interface TabelBandingProps {
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null };
    sembunyikanSama: boolean;
}

const sel = 'p-4 align-top';
// Label aspek menempel di kiri ketika tabel digeser di layar sempit, supaya pembaca
// tidak kehilangan baris mana yang sedang ia baca.
const kepalaBaris = 'sticky left-0 z-20 bg-surface p-4 text-left align-top font-medium text-muted';

/**
 * Jalur berdampingan tanpa pemenang (R-016): yang ditandai hanya apakah sebuah aspek
 * berbeda antarjalur, tidak pernah nilai mana yang lebih baik. Aspek yang sama
 * diredupkan dan diberi label "Sama", sehingga perbedaannya yang terbaca lebih dulu.
 *
 * "Hapus dari perbandingan" adalah tautan GET ke perbandingan tanpa kolom itu
 * (RouteComparisonController::tautan()); halaman ini tidak pernah menulis ke simpanan.
 */
export function TabelBanding({ jalur, baris, beda, sembunyikanSama }: TabelBandingProps) {
    const tampil = (berbeda: boolean | null) => !sembunyikanSama || berbeda !== false;
    const barisTampil = baris.filter((b) => tampil(b.berbeda));
    const adaYangTampil = tampil(beda.fit) || tampil(beda.status) || barisTampil.length > 0;

    const kepala = (label: string, berbeda: boolean | null): ReactNode => (
        <th scope="row" className={kepalaBaris}>
            {label}
            {berbeda === false ? <span className="ml-2 rounded bg-surface-sunken px-1.5 py-0.5 text-xs font-normal text-secondary">Sama</span> : null}
        </th>
    );

    return (
        <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
            <table className="min-w-full text-sm">
                <caption className="sr-only">Perbandingan karakteristik jalur</caption>
                <thead>
                    <tr className="border-b border-subtle text-left">
                        <th scope="col" className={kelas(kepalaBaris, 'font-normal')}>Aspek</th>
                        {jalur.map((j) => (
                            <th key={j.id} scope="col" className={kelas(sel, 'min-w-44')}>
                                <Tautan
                                    href={j.url}
                                    className="block rounded-sm py-1 font-semibold text-primary underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {j.name}
                                </Tautan>
                                <span className="block text-xs font-normal text-secondary">{j.mountain}</span>
                                {j.tersimpan ? null : <span className="block text-xs font-normal text-muted">Bukan simpanan Anda</span>}
                                <Button variant="ghost" size="sm" className="-ml-3 font-normal" href={j.hapus}>
                                    Hapus dari perbandingan<span className="sr-only">: {j.name}</span>
                                </Button>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {tampil(beda.fit) ? (
                        <tr className="border-b border-subtle">
                            {kepala('Kecocokan untuk Anda', beda.fit)}
                            {jalur.map((j) => (
                                <td key={j.id} className={sel}>
                                    {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <span className="text-sm text-muted">Belum dinilai</span>}
                                </td>
                            ))}
                        </tr>
                    ) : null}

                    {barisTampil.map((b) => (
                        <tr key={b.label} className="border-b border-subtle">
                            {kepala(b.label, b.berbeda)}
                            {b.nilai.map((nilai, i) => (
                                <td key={jalur[i]?.id ?? i} className={kelas(sel, b.berbeda === false ? 'text-secondary' : 'font-medium text-primary')}>
                                    {nilai}
                                </td>
                            ))}
                        </tr>
                    ))}

                    {tampil(beda.status) ? (
                        <tr>
                            {kepala('Status resmi', beda.status)}
                            {jalur.map((j) => (
                                <td key={j.id} className={sel}>
                                    <StatusBadge status={j.status.status} text={j.status.label} />
                                </td>
                            ))}
                        </tr>
                    ) : null}

                    {!adaYangTampil ? (
                        <tr>
                            <td colSpan={jalur.length + 1} className="p-4 text-secondary">
                                Semua aspek yang dibandingkan bernilai sama.
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}
