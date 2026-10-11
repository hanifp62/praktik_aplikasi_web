import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { Penilaian } from './types';

/**
 * Label penilaian beserta definisinya (spec v3 bagian 6.5). Tiga label yang mungkin disebut
 * bersama alasannya tanpa skor: angka seperti "82%" memberi kesan pasti yang tidak dimiliki
 * sistem (R-005). Jalur yang gagal syarat dasar tidak diberi label sama sekali; yang tampil
 * adalah syarat yang gagal, dalam kalimat, bukan kunci mesin.
 */
export function KepalaPenilaian({ penilaian }: { penilaian: Penilaian }) {
    return (
        <section aria-labelledby="judul-label" className="rounded-utama border border-subtle bg-surface p-5">
            <h2 id="judul-label" className="sr-only">
                Label kecocokan
            </h2>
            <div className="flex flex-wrap items-center gap-3">
                <LabelKecocokan nilai={penilaian.label} label={penilaian.labelText} />
                <span className="text-meta text-muted">{penilaian.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
            </div>
            {penilaian.definisi ? <p className="mt-3 text-isi text-primary">{penilaian.definisi}</p> : null}
            {!penilaian.layak ? (
                <div className="mt-3">
                    <p className="text-isi text-primary">Jalur ini tidak dinilai karena syarat dasarnya belum terpenuhi:</p>
                    <ul className="mt-2 list-disc space-y-1 pl-5 text-kecil text-secondary">
                        {penilaian.alasanTidakDinilai.map((a) => (
                            <li key={a}>{a}</li>
                        ))}
                    </ul>
                </div>
            ) : null}
            <p className="mt-4 rounded-control bg-warn-50 px-4 py-3 text-kecil text-warn-900">
                Tiga label yang mungkin: Cocok, Perlu persiapan, Kurang cocok. Tidak ada skor angka, karena angka seperti persentase memberi kesan pasti yang
                tidak kami miliki.
            </p>
        </section>
    );
}
