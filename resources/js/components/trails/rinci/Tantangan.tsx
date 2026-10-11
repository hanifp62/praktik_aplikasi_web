import { kelas } from '@/lib/kelas';
import type { BarisTantangan, Segmen } from './types';

/**
 * "Apa tantangannya?" (spec v3 bagian 6.4). Tuntutan fisik, teknis, dan navigasi masing-masing
 * dengan tiga ruas dan kata: kata membawa artinya, ruas hanya bentuk pembantu, jadi pembaca
 * layar mendengar katanya saja. Baris tanpa ruas (belum diketahui, atau kata saja seperti medan)
 * tidak digambar ruas kosong yang terbaca "rendah" (R-006). Bagian jalur dari data segmen
 * menyusul bila ada.
 */
export function Tantangan({ baris, segmen }: { baris: BarisTantangan[]; segmen: Segmen[] }) {
    return (
        <section aria-labelledby="judul-tantangan" className="space-y-3">
            <h2 id="judul-tantangan" className="text-bagian text-primary">
                Apa tantangannya?
            </h2>
            <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                {baris.map((b) => (
                    <div key={b.judul} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
                        <dt className="min-w-0 flex-1 text-kecil text-secondary">{b.judul}</dt>
                        <dd className="flex items-center gap-3 text-kecil font-bold text-primary">
                            {b.ruas !== null ? (
                                <span aria-hidden="true" className="flex gap-1">
                                    {[1, 2, 3].map((r) => (
                                        <span key={r} className={kelas('h-2 w-5 rounded-full', r <= (b.ruas ?? 0) ? 'bg-brand-ink' : 'bg-surface-sunken ring-1 ring-inset ring-control')} />
                                    ))}
                                </span>
                            ) : null}
                            <span className="text-right">{b.kata}</span>
                        </dd>
                    </div>
                ))}
            </dl>
            {segmen.length > 0 ? (
                <div className="space-y-2">
                    <h3 className="text-subjudul text-primary">Bagian jalur</h3>
                    <ol className="space-y-2">
                        {segmen.map((s) => (
                            <li key={s.id} className="rounded-lg border border-subtle bg-surface px-4 py-3 text-kecil">
                                <span className="font-bold text-primary">{s.judul}</span>
                                <span className="text-secondary">, {s.ringkas}</span>
                                {s.description ? <p className="mt-1 text-secondary">{s.description}</p> : null}
                            </li>
                        ))}
                    </ol>
                </div>
            ) : null}
        </section>
    );
}
