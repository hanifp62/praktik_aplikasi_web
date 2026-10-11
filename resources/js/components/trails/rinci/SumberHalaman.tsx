import { LabelSumber } from '@/components/trails/LabelSumber';
import type { BarisSumber } from './types';

/**
 * "Sumber di halaman ini" (spec v3 bagian 6.4, prototype 04), dalam urutan kepercayaan:
 * setiap bagian menyebut jenis sumbernya dengan LabelSumber (glyph resmi hanya untuk sumber
 * OFFICIAL, R-009) dan nama sumbernya. Kalimat penutup menyebut urutannya dan bahwa yang lebih
 * rendah tidak pernah menimpa yang lebih tinggi (R-010, R-011).
 */
export function SumberHalaman({ sumber }: { sumber: BarisSumber[] }) {
    return (
        <section aria-labelledby="judul-sumber" className="space-y-3">
            <h2 id="judul-sumber" className="text-bagian text-primary">
                Sumber di halaman ini
            </h2>
            <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                {sumber.map((s) => (
                    <div key={s.bagian} className="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 px-4 py-3">
                        <dt className="text-kecil font-bold text-primary">{s.bagian}</dt>
                        <dd className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                            <LabelSumber jenis={s.jenis} />
                            {s.url ? (
                                <a href={s.url} target="_blank" rel="noopener noreferrer" className="py-1 text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                    {s.isi}
                                    <span className="sr-only"> (membuka tab baru)</span>
                                </a>
                            ) : (
                                <span>{s.isi}</span>
                            )}
                        </dd>
                    </div>
                ))}
            </dl>
            <p className="text-meta text-muted">Urutan kepercayaan: Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem. Yang lebih rendah tidak pernah menimpa yang lebih tinggi.</p>
        </section>
    );
}
