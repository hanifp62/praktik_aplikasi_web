import { Button } from '@/components/ui/Button';
import { index as tripsIndex } from '@/routes/trips';
import type { TitikTanjakan } from './types';

/**
 * Tanjakan jalur tiap pendakian yang tuntas, dibandingkan dengan pendakian pendaki itu
 * sendiri (R-015): tanpa peringkat, tanpa pendaki lain, tanpa skor.
 *
 * Satu seri, jadi tanpa legenda; judul bagian yang menamainya. Setiap baris menulis
 * nilainya sebagai teks, sehingga daftar ini sekaligus tampilan tabelnya dan batangnya
 * disembunyikan dari pembaca layar. Nilai yang belum dicatat tidak digambar sebagai
 * batang nol (§95): barisnya tetap ada dan menyebutnya.
 */
export function GrafikTanjakan({ titik }: { titik: TitikTanjakan[] }) {
    const puncak = Math.max(1, ...titik.map((t) => t.m ?? 0));

    return (
        <section aria-labelledby="grafik-tanjakan">
            <h2 id="grafik-tanjakan" className="text-base font-semibold text-primary">Tanjakan tiap pendakian</h2>

            {titik.length < 2 ? (
                <div className="mt-2 rounded-lg border border-dashed border-subtle bg-surface p-4">
                    <p className="max-w-prose text-sm text-secondary">
                        Grafik ini muncul setelah ada dua pendakian yang selesai sampai target, karena tiap pendakian dibandingkan dengan pendakian Anda sebelumnya, bukan
                        dengan pendaki lain. Pendakian dicatat selesai dari halaman trip.
                    </p>
                    <Button variant="secondary" href={tripsIndex.url()} className="mt-3">
                        Buka daftar trip
                    </Button>
                </div>
            ) : (
                <>
                    <p className="mt-1 max-w-prose text-sm text-secondary">
                        Tanjakan jalur pada pendakian yang selesai sampai target, dari yang paling awal. Angkanya milik jalur, bukan rekaman GPS perjalanan Anda.
                    </p>
                    <ol className="mt-4 space-y-3">
                        {titik.map((t, i) => (
                            <li key={`${t.tanggal}-${i}`}>
                                <p className="text-sm">
                                    <span className="text-primary">{t.jalur}</span> <span className="text-xs text-muted">{t.tanggal}</span>
                                </p>
                                <div className="mt-1 flex items-center gap-3">
                                    <div className="h-3 flex-1" aria-hidden="true">
                                        {t.m !== null ? (
                                            <span className="block h-3 rounded-e bg-brand-600" style={{ width: `${Math.max(2, (t.m / puncak) * 100)}%` }} />
                                        ) : null}
                                    </div>
                                    <span className={`w-28 shrink-0 text-right text-sm tabular-nums ${t.m === null ? 'text-muted' : 'text-primary'}`}>{t.teks}</span>
                                </div>
                            </li>
                        ))}
                    </ol>
                </>
            )}
        </section>
    );
}
