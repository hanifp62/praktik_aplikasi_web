import { kelas } from '@/lib/kelas';
import type { AngkaBerlabel } from './types';

const tanda: Record<string, string> = { Tanjakan: '↑' };

/**
 * Angka sekali jalan naik (spec v3 bagian 6.4): jarak, tanjakan, dan lama naik, dari pos
 * pertama ke pos terakhir. Turunan tidak ditulis karena data turunannya adalah turunan pulang
 * (ruling W3). Angka yang belum ada ditulis "Belum tersedia" dengan huruf biasa, tidak
 * dikarang dan tidak dibiarkan kosong (PRD §91).
 */
export function AngkaNaik({ angka, ujung }: { angka: AngkaBerlabel[]; ujung: { awal: string; akhir: string } | null }) {
    return (
        <section aria-labelledby="judul-angka" className="space-y-3">
            <h2 id="judul-angka" className="sr-only">
                Angka jalur
            </h2>
            <p className="text-kecil text-secondary">
                {ujung ? `${ujung.awal} ke ${ujung.akhir}. ` : null}Angka di bawah untuk sekali jalan naik.
            </p>
            <dl className="grid grid-cols-2 gap-3 min-[360px]:grid-cols-3">
                {angka.map((a) => (
                    <div key={a.label} className="rounded-lg border border-subtle bg-surface px-3 py-2.5">
                        <dt className="text-meta text-secondary">{a.label}</dt>
                        <dd data-angka="" className={kelas('mt-0.5', a.ada ? 'text-subjudul text-primary min-[400px]:text-judul-kartu' : 'text-kecil text-secondary')}>
                            {a.ada && tanda[a.label] ? `${tanda[a.label]}${a.nilai}` : a.nilai}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
