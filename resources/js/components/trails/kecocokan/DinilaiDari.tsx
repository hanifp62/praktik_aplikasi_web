import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { DinilaiDari as DataDinilai } from './types';

const tautanUbah = 'inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-control px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Bahan penilaian (spec v3 bagian 6.5): profil yang diisi sendiri dan rencana trip, masing-
 * masing dengan jalan untuk mengubahnya. Pengalaman yang diisi sendiri adalah masukan, bukan
 * sertifikasi (PRD §22), jadi pembaca diberi tempat untuk membetulkannya.
 */
export function DinilaiDari({ data }: { data: DataDinilai }) {
    const baris: Array<{ judul: string; isi: string; url: string; nama: string }> = [
        { judul: 'Profil Anda (diisi sendiri)', isi: data.profil, url: data.profilUrl, nama: 'profil' },
    ];

    if (data.rencana !== null && data.rencanaUrl !== null) {
        baris.push({ judul: 'Rencana trip', isi: data.rencana, url: data.rencanaUrl, nama: 'rencana trip' });
    }

    return (
        <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
            {baris.map((b) => (
                <div key={b.judul} className="flex items-start justify-between gap-3 px-4 py-3">
                    <div className="min-w-0">
                        <dt className="text-meta text-secondary">{b.judul}</dt>
                        <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{b.isi}</dd>
                    </div>
                    <Tautan href={b.url} className={kelas(tautanUbah)}>
                        Ubah<span className="sr-only"> {b.nama}</span>
                    </Tautan>
                </div>
            ))}
        </dl>
    );
}
