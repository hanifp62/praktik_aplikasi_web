import { Lencana } from '@/components/ui/Lencana';
import type { ButirPersiapan } from './types';

/**
 * "Yang perlu disiapkan" khusus jalur ini (spec v3 bagian 6.4): izin bila jalurnya punya
 * aturan tercatat, lalu butir templat jalur ini. Butir yang sama masuk ke daftar persiapan
 * trip. Tanpa butir khusus, kalimatnya jujur: daftar umum dipakai saat trip direncanakan.
 */
export function Persiapan({ butir }: { butir: ButirPersiapan[] }) {
    return (
        <section aria-labelledby="judul-persiapan" className="space-y-3">
            <h2 id="judul-persiapan" className="text-bagian text-primary">
                Yang perlu disiapkan
            </h2>
            {butir.length === 0 ? (
                <p className="text-kecil text-secondary">Belum ada butir khusus jalur ini. Daftar persiapan umum dipakai saat Anda merencanakan trip.</p>
            ) : (
                <>
                    <p className="text-kecil text-secondary">Khusus jalur ini. Butir yang sama masuk ke daftar persiapan trip Anda.</p>
                    <ul className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                        {butir.map((b, i) => (
                            <li key={`${i}-${b.judul}`} className="px-4 py-3">
                                <p className="flex flex-wrap items-center gap-2 text-isi font-bold text-primary">
                                    {b.judul}
                                    {b.wajib ? (
                                        <Lencana nada="peringatan" bentuk="pil">
                                            Wajib
                                        </Lencana>
                                    ) : null}
                                </p>
                                {b.keterangan ? <p className="mt-0.5 text-kecil text-secondary">{b.keterangan}</p> : null}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
