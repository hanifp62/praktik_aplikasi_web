import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { KeadaanLangkah, Langkah } from './types';

const tanda: Record<KeadaanLangkah, { simbol: string; teks: string; bulatan: string }> = {
    selesai: { simbol: '✓', teks: 'Selesai', bulatan: 'border-brand-700 bg-brand-700 text-white' },
    berikutnya: { simbol: '→', teks: 'Langkah berikutnya', bulatan: 'border-brand-700 bg-surface text-brand-ink' },
    belum: { simbol: '', teks: 'Belum', bulatan: 'border-control bg-surface text-muted' },
};

/**
 * Rencana sebagai langkah berurutan (features/04). Keadaan tiap langkah dibaca dari
 * simbol dan kata, bukan dari warna saja; langkah berikutnya diberi aria-current="step"
 * dan kata "Langkah berikutnya" yang terlihat.
 */
export function LangkahTrip({ langkah }: { langkah: Langkah[] }) {
    return (
        <ol className="space-y-0">
            {langkah.map((l, i) => {
                const t = tanda[l.keadaan];
                const terakhir = i === langkah.length - 1;

                return (
                    <li key={l.kunci} aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined} className="relative flex gap-4 pb-6 last:pb-0">
                        {!terakhir ? <span aria-hidden="true" className="absolute left-4 top-9 h-[calc(100%-2.25rem)] w-px bg-subtle" /> : null}
                        <span aria-hidden="true" className={kelas('relative flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold', t.bulatan)}>
                            {t.simbol || i + 1}
                        </span>
                        <div className="min-w-0 flex-1 pt-1">
                            <p className="flex flex-wrap items-baseline gap-x-2">
                                <span className="font-semibold text-primary">{l.judul}</span>
                                <span className={kelas('text-xs', l.keadaan === 'berikutnya' ? 'font-medium text-brand-ink' : 'text-muted')}>{t.teks}</span>
                            </p>
                            <p className={kelas('mt-0.5 text-sm', l.peringatan ? 'font-medium text-warn-900' : 'text-secondary')}>{l.keterangan}</p>
                            {l.catatan ? <p className="mt-1 text-xs text-secondary">{l.catatan}</p> : null}
                            {l.tautan ? (
                                <Tautan
                                    href={l.tautan.href}
                                    className="mt-1 inline-flex min-h-11 items-center text-sm text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
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
