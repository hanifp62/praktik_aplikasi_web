import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { progress } from '@/routes';
import type { TanggaData } from './types';

/**
 * Tangga tanjakan (spec v3 bagian 6.1, prototype 01): satu seri, tanjakan jalur dari yang
 * terendah, dibandingkan dengan pendakian Anda sendiri (R-015). Satu seri, jadi tanpa legenda;
 * nilainya tertulis di setiap baris, sehingga daftar ini sekaligus tabelnya dan batangnya
 * disembunyikan dari pembaca layar. Anak tangga rencana bergaris putus-putus dan bertuliskan
 * "rencana", jadi bedanya tidak dibawa warna saja.
 *
 * Kunci baris memakai id jalur, bukan namanya (ruling W5-6a): trails.name tidak unik, dan nama
 * gunung tampil di baris kedua label untuk disambiguasi, konsisten dengan JalurUntukAnda.
 */
export function TanggaTanjakan({ tangga }: { tangga: TanggaData }) {
    const puncak = Math.max(1, ...tangga.anakTangga.map((a) => a.m));

    return (
        <section aria-labelledby="tangga-tanjakan">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                <h2 id="tangga-tanjakan" className="text-bagian text-primary">
                    Tangga tanjakan Anda
                </h2>
                <Tautan
                    href={progress.url()}
                    className={kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                >
                    Lihat progres
                </Tautan>
            </div>
            <div className="mt-2 rounded-lg border border-subtle bg-surface p-4">
                <ol className="space-y-2.5">
                    {tangga.anakTangga.map((a) => (
                        <li key={`${a.id}-${a.rencana ? 'rencana' : 'tuntas'}`} className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)_auto] items-center gap-3">
                            <span className="min-w-0">
                                <span className={kelas('break-words text-kecil', a.rencana ? 'font-bold text-primary' : 'text-secondary')}>
                                    {a.jalur}
                                    {a.rencana ? <span className="font-semibold text-secondary">, rencana</span> : null}
                                </span>
                                <span className="block break-words text-meta text-secondary"><span className="sr-only">, </span>{a.gunung}</span>
                            </span>
                            <span aria-hidden="true" className="block h-2.5">
                                <span
                                    className={kelas('block h-full rounded-e', a.rencana ? 'border-2 border-dashed border-brand-700' : 'bg-brand-700')}
                                    style={{ width: `${Math.max(4, (a.m / puncak) * 100)}%` }}
                                />
                            </span>
                            <span data-angka className={kelas('text-right text-kecil text-primary', a.rencana && 'font-bold')}>
                                {a.teks}
                            </span>
                        </li>
                    ))}
                </ol>
                <p className="mt-3 border-t border-subtle pt-3 text-kecil text-secondary">{tangga.kalimat}</p>
            </div>
        </section>
    );
}
