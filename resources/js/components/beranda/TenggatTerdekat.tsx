import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { TenggatBeranda } from './types';

/**
 * Tenggat terdekat trip mendatang (spec v3 bagian 6.1, prototype 01): satu baris per tenggat,
 * berurutan waktu, dengan penanda kalender di kiri. Zona gunung disebut sekali di samping judul.
 * Penanda kalender disembunyikan dari pembaca layar, yang mendapat tanggal lengkapnya. Tenggat
 * izin yang lewat tanpa diurus ditandai ikon peringatan dan kalimatnya, bukan warna saja.
 */
export function TenggatTerdekat({ tenggat }: { tenggat: TenggatBeranda }) {
    return (
        <section aria-labelledby="tenggat-terdekat">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 id="tenggat-terdekat" className="text-bagian text-primary">
                    Tenggat terdekat
                </h2>
                <p className="text-meta text-secondary">Waktu gunung, {tenggat.zona}</p>
            </div>
            <ol className="mt-3 divide-y divide-subtle overflow-hidden rounded-kartu border border-subtle bg-surface">
                {tenggat.baris.map((b) => (
                    <li key={`${b.judul} ${b.kapan?.lengkap ?? ''}`} className="flex gap-3 p-4">
                        <span
                            aria-hidden="true"
                            className={kelas(
                                'flex w-12 shrink-0 flex-col items-center self-start rounded-kecil py-1.5',
                                b.peringatan ? 'bg-warn-50 text-warn-900' : 'bg-surface-sunken text-primary',
                            )}
                        >
                            {b.kapan ? (
                                <>
                                    <span data-angka className="text-bagian leading-none">
                                        {b.kapan.tanggal}
                                    </span>
                                    <span className="text-meta">{b.kapan.bulan}</span>
                                    {b.kapan.tahun ? (
                                        <span data-angka className="text-meta">
                                            {b.kapan.tahun}
                                        </span>
                                    ) : null}
                                </>
                            ) : (
                                <Icon name="question" size={20} />
                            )}
                        </span>
                        <div className="min-w-0">
                            {b.kapan ? <p className="sr-only">{b.kapan.lengkap}</p> : null}
                            <p className={kelas('flex items-start gap-1.5 break-words text-isi font-bold', b.peringatan ? 'text-warn-900' : 'text-primary')}>
                                {b.peringatan ? <Icon name="warning" size={20} className="mt-0.5 shrink-0" /> : null}
                                <span className="min-w-0">{b.judul}</span>
                            </p>
                            <p className="mt-0.5 break-words text-kecil text-secondary">{b.keterangan}</p>
                            {b.tautan ? (
                                <a
                                    href={b.tautan.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={kelas('mt-1 inline-flex min-h-11 items-center gap-1 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                                >
                                    {b.tautan.label}
                                    <Icon name="arrow-square-out" size={16} />
                                    <span className="sr-only"> (membuka tab baru)</span>
                                </a>
                            ) : null}
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
