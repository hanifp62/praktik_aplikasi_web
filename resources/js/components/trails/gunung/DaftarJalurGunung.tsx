import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { JalurGunung } from './types';

interface DaftarJalurGunungProps {
    jalur: JalurGunung[];
    /** Kata UNKNOWN dari server, untuk pembaca yang profilnya belum lengkap. */
    kataBelumDinilai: string;
}

/**
 * Jalur terbit sebuah gunung (spec v3 bagian 6.3): satu gunung, beberapa jalur dengan
 * tantangan berbeda (R-001, R-002). Setiap baris adalah tautan ke rincian jalurnya dan
 * membawa label kecocokan beserta alasannya, penanda rencana, baris ukuran, dan status
 * aksesnya sendiri (R-003: Route Fit adalah differentiator inti, bukan label tanpa alasan).
 * Urutannya nama jalur, bukan kecocokan: halaman ini menyajikan pilihan, bukan peringkat.
 * Chevron menandai baris yang membuka halaman lain (spec v3 bagian 8.8).
 */
export function DaftarJalurGunung({ jalur, kataBelumDinilai }: DaftarJalurGunungProps) {
    return (
        <ul className="divide-y divide-subtle overflow-hidden rounded-lg border border-subtle bg-surface">
            {jalur.map((j) => (
                <li key={j.id}>
                    <Tautan href={j.url} className={kelas('flex items-center gap-3 px-4 py-4 hover:bg-surface-sunken', fokusTerlihat)}>
                        <span className="min-w-0 flex-1">
                            <span className="flex flex-wrap items-start justify-between gap-2">
                                <span className="min-w-0 wrap-break-word text-subjudul text-primary">{j.nama}</span>
                                <span className="flex shrink-0 flex-wrap items-center gap-1.5">
                                    <LabelKecocokan nilai={j.fit?.label ?? null} label={j.fit?.labelText ?? kataBelumDinilai} />
                                    {j.fit?.denganRencana ? <span className="text-meta text-muted">untuk rencana ini</span> : null}
                                </span>
                            </span>
                            {/* P-8 (perbaikan 1 W3, R-003): alasan dikirim server sejak Tugas 2 tetapi tidak dirender.
                                Route Fit adalah differentiator inti; label tanpa alasan terlihat mereduksinya jadi ranking. */}
                            {j.fit?.alasan ? <span className="mt-1 block wrap-break-word text-meta text-secondary">{j.fit.alasan}</span> : null}
                            <span data-angka="" className="mt-1 block text-kecil font-semibold text-primary">
                                {j.angka}
                            </span>
                            <BarisStatus status={j.status} className="mt-1" />
                            {j.medan ? <span className="mt-1 block wrap-break-word text-meta text-secondary">Medan: {j.medan}</span> : null}
                        </span>
                        <Icon name="caret-right" size={20} className="shrink-0 text-muted" />
                    </Tautan>
                </li>
            ))}
        </ul>
    );
}
