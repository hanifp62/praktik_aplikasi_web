import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { KeadaanLangkah, Langkah } from './types';

const tanda: Record<KeadaanLangkah, { teks: string; cincin: string }> = {
    selesai: { teks: 'Selesai', cincin: 'border-brand-700 bg-brand-700 text-on-brand' },
    berikutnya: { teks: 'Langkah berikutnya', cincin: 'border-brand-700 bg-surface text-brand-ink' },
    belum: { teks: 'Belum', cincin: 'border-control bg-surface text-muted' },
};

/**
 * Empat langkah rencana sebagai garis rute berujung cincin pos (DESIGN.md bagian Motif
 * identitas): satu garis menurun yang menyambung cincin bernomor, dan cincin langkah yang
 * selesai terisi dengan centang. Keadaan tiap langkah dibaca dari bentuk dan kata, bukan dari
 * warna saja; langkah berikutnya diberi aria-current="step" dan kata "Langkah berikutnya".
 */
export function LangkahTrip({ langkah }: { langkah: Langkah[] }) {
    return (
        <ol>
            {langkah.map((l, i) => {
                const t = tanda[l.keadaan];
                const terakhir = i === langkah.length - 1;

                return (
                    <li key={l.kunci} aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined} className="relative flex gap-4 pb-6 last:pb-0">
                        {!terakhir ? <span aria-hidden="true" className="absolute left-4 top-9 h-[calc(100%-2.25rem)] w-0.5 bg-brand-ink" /> : null}
                        <span aria-hidden="true" className={kelas('relative flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-kecil font-bold tabular-nums', t.cincin)}>
                            {l.keadaan === 'selesai' ? <Icon name="check" size={16} /> : i + 1}
                        </span>
                        <div className="min-w-0 flex-1 pt-1">
                            <p className="flex flex-wrap items-baseline gap-x-2">
                                <span className="text-isi font-bold text-primary">{l.judul}</span>
                                <span className={kelas('text-meta', l.keadaan === 'berikutnya' ? 'font-bold text-brand-ink' : 'text-muted')}>{t.teks}</span>
                            </p>
                            <p className={kelas('mt-0.5 wrap-break-word text-kecil', l.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>{l.keterangan}</p>
                            {l.catatan ? <p className="mt-1 text-meta text-secondary">{l.catatan}</p> : null}
                            {l.tautan ? (
                                <Tautan
                                    href={l.tautan.href}
                                    className="mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {l.tautan.teks}
                                </Tautan>
                            ) : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
