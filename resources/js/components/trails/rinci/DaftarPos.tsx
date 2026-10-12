import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';
import type { PosJalur } from './types';

const kataAksen = { kemah: 'Kemah', tujuan: 'Tujuan' } as const;

/**
 * Pos di jalur ini (spec v3 bagian 6.4, prototype 04), dipakai rincian jalur dan ringkasan
 * pendakian. Menggantikan CheckpointJourney lama di components/ui (keputusan koordinator W3).
 *
 * Setiap pos: nomor dalam lingkaran, nama, jenisnya, lalu satu baris waktu dari awal
 * (perkiraan dari rekaman pendaki), naik atau turun dari pos sebelumnya, dan catatan pos;
 * ketinggian dan jarak dari awal di kanan. Pos berkemah dan tujuan membawa aksen kuning
 * bersama lencana kata "Kemah" atau "Tujuan" (DESIGN.md, satu aksen: tidak pernah warna
 * saja). Garis penghubung antarlingkaran hiasan, disembunyikan dari pembaca layar.
 */
export function DaftarPos({ pos, className }: { pos: PosJalur[]; className?: string }) {
    return (
        <ol className={kelas('relative', className)}>
            {pos.map((p, i) => {
                const keterangan = [p.waktu, p.antara, p.catatan].filter(Boolean).join(', ');

                return (
                    <li key={p.id} className="relative flex gap-3 pb-4 last:pb-0">
                        {i < pos.length - 1 ? <span aria-hidden="true" className="absolute left-3.75 top-8 h-[calc(100%-2rem)] w-0.5 bg-subtle" /> : null}
                        <span
                            aria-hidden="true"
                            data-angka=""
                            className={kelas(
                                'relative flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-kecil font-bold',
                                p.aksen ? 'border-primary bg-aksen-pos text-aksen-pos-teks' : 'border-brand-ink bg-surface text-brand-ink',
                            )}
                        >
                            {p.urutan}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span className="sr-only">Pos {p.urutan}: </span>
                                <span className="text-isi font-bold text-primary">{p.nama}</span>
                                <span className="text-meta text-muted">{p.jenis}</span>
                                {p.aksen ? (
                                    <Lencana nada="netral" bentuk="pil">
                                        {kataAksen[p.aksen]}
                                    </Lencana>
                                ) : null}
                            </p>
                            {keterangan ? <p className="mt-0.5 text-kecil text-secondary">{keterangan}</p> : null}
                        </div>
                        <div data-angka="" className="shrink-0 text-right">
                            <p className="text-kecil font-bold text-primary">{p.elevasi ?? 'Ketinggian belum tercatat'}</p>
                            {p.km ? <p className="text-meta text-muted">{p.km}</p> : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
