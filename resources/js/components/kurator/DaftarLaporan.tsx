import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { TextareaField } from '@/components/discovery/Isian';
import { Button } from '@/components/ui/Button';
import { Dialog } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { BarisLaporan } from './types';

const tautan = 'inline-flex min-h-11 items-center break-words font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:rounded-control focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Antrean cek ulang dari "Laporkan kesalahan data" (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Laporan tidak mengubah status: kurator membuka halaman yang dilaporkan, mengecek ulang ke sumber
 * resminya, lalu menandainya sudah dicek dengan hasilnya. Bila sumbernya memang berubah, kurator
 * mencatat status pengganti. Identitas pelapor tidak tampil.
 */
export function DaftarLaporan({ baris }: { baris: BarisLaporan[] }) {
    const [dipilih, setDipilih] = useState<BarisLaporan | null>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const form = useForm({ hasil: '' });

    const tutup = () => {
        setDipilih(null);
        form.reset();
        form.clearErrors();
    };

    return (
        <section aria-labelledby="antrean-laporan" className="border-t border-subtle pt-6">
            <h2 id="antrean-laporan" className="text-bagian text-primary">
                Laporan kesalahan data <span className="text-secondary tabular-nums">({baris.length})</span>
            </h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">
                Dari pendaki yang melihat data keliru di kawasan Anda. Laporan tidak mengubah status: cek ulang ke sumber resminya, lalu tandai sudah dicek. Bila sumbernya memang berubah, catat status penggantinya.
            </p>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Tidak ada laporan yang menunggu cek ulang.</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((l) => (
                        <li key={l.id} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <Tautan href={l.halaman} className={tautan}>
                                    {l.sasaran}
                                </Tautan>
                                <div className="mt-1">
                                    <Lencana nada="peringatan">{l.bagian}</Lencana>
                                </div>
                                <blockquote className="mt-2 max-w-prose break-words border-l-2 border-subtle pl-3 text-kecil text-primary">{l.isi}</blockquote>
                                <p className="mt-1 text-meta text-muted">Dilaporkan pendaki, {l.waktu}.</p>
                                {l.catatan ? (
                                    <Tautan href={l.catatan} className={tautan + ' text-kecil'}>
                                        Catatan status yang terbaca saat itu
                                    </Tautan>
                                ) : null}
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    aria-label={`Tandai sudah dicek: ${l.sasaran}`}
                                    onClick={(e) => {
                                        pemicu.current = e.currentTarget;
                                        setDipilih(l);
                                    }}
                                >
                                    Tandai sudah dicek
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Dialog
                terbuka={dipilih !== null}
                onTutup={tutup}
                judul="Tandai laporan sudah dicek?"
                keterangan="Tulis apa yang Anda periksa dan hasilnya. Hasil ini tercatat di jejak audit; status yang tampil tidak berubah karena langkah ini."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={tutup}>
                            Batal
                        </Button>
                        <Button processing={form.processing} onClick={() => dipilih && form.post(dipilih.cek, { preserveScroll: true, onSuccess: tutup })}>
                            Tandai sudah dicek
                        </Button>
                    </>
                }
            >
                {dipilih ? <p className="mb-3 text-kecil text-secondary">{dipilih.sasaran}</p> : null}
                <TextareaField name="hasil" label="Hasil cek ulang" rows={3} value={form.data.hasil} onChange={(e) => form.setData('hasil', e.target.value)} error={form.errors.hasil} />
            </Dialog>
        </section>
    );
}
