import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export type KunciLangkah = 'jalur' | 'rencana' | 'persiapan' | 'kesiapan';

const urutan: ReadonlyArray<{ kunci: KunciLangkah; judul: string }> = [
    { kunci: 'jalur', judul: 'Jalur' },
    { kunci: 'rencana', judul: 'Rencana' },
    { kunci: 'persiapan', judul: 'Persiapan' },
    { kunci: 'kesiapan', judul: 'Cek' },
];

interface LangkahRingkasProps {
    /** Langkah halaman ini; diberi aria-current="step". */
    kini: KunciLangkah;
    /** Tujuan tiap langkah yang dapat dibuka dari halaman ini; tanpa tujuan, langkah hanya teks. */
    tautan?: Partial<Record<KunciLangkah, string>>;
}

/**
 * Empat langkah yang sama dengan ruang kerja trip, sebagai baris ringkas di atas halaman
 * Rencanakan, Persiapan, dan Cek (prototype 08 sampai 10). Langkah halaman ini ditandai bilah
 * merek di bawahnya, tebal huruf, dan aria-current; bukan warna saja. Langkah yang punya
 * tujuan menjadi tautan, sehingga pendaki dapat kembali ke langkah lain tanpa ke ruang kerja.
 */
export function LangkahRingkas({ kini, tautan = {} }: LangkahRingkasProps) {
    return (
        <nav aria-label="Langkah trip" className="mb-4">
            <ol className="flex gap-x-1">
                {urutan.map((l) => {
                    const aktif = l.kunci === kini;
                    const href = aktif ? undefined : tautan[l.kunci];
                    const dasar = 'flex min-h-11 items-center justify-center wrap-break-word border-b-2 px-1 text-center text-kecil';

                    return (
                        <li key={l.kunci} aria-current={aktif ? 'step' : undefined} className="min-w-0 flex-1">
                            {href ? (
                                // Langkah yang dapat dibuka tampak sebagai tautan saat diam, bukan hanya saat
                                // disorot (M9 tinjauan Tugas 3): warna tautan dan garis bawah.
                                <Tautan
                                    href={href}
                                    className={kelas(
                                        dasar,
                                        'border-transparent font-semibold text-brand-ink underline underline-offset-4 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600',
                                    )}
                                >
                                    {l.judul}
                                </Tautan>
                            ) : (
                                <span className={kelas(dasar, aktif ? 'border-brand-700 font-bold text-primary' : 'border-transparent font-semibold text-secondary')}>{l.judul}</span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
