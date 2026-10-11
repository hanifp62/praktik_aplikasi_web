import type { JalurMenunggu } from '@/components/trails/types';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

/**
 * Jalur yang dikenal sistem tetapi datanya belum lengkap (spec v3 bagian 6.2): kartu bergaris
 * putus-putus "Belum bisa dinilai", tanpa angka, tidak dihitung dalam jumlah hasil, dan tidak
 * pernah direkomendasikan (R-008, R-022). Setiap kartu menyebut siapa yang ditunggu dan bahwa
 * status yang belum diketahui tidak dianggap buka (§95).
 */
export function KartuBelumDinilai({ daftar }: { daftar: JalurMenunggu[] }) {
    return (
        <ul className="space-y-3">
            {daftar.map((j) => (
                <li key={j.id}>
                    <Tautan href={j.url} className={kelas('block rounded-lg border-2 border-dashed border-control bg-surface p-4 hover:bg-surface-sunken', fokusTerlihat)}>
                        <span className="text-meta font-bold text-secondary">Belum bisa dinilai</span>
                        <span className="mt-1 block text-subjudul text-primary">{j.name}</span>
                        <span className="mt-0.5 block text-meta text-secondary">{[j.mountain, j.province].filter(Boolean).join(', ')}</span>
                        <span className="mt-2 block text-kecil text-secondary">Menunggu kurator Memount melengkapi datanya. Status resmi belum diketahui dan tidak dianggap buka.</span>
                    </Tautan>
                </li>
            ))}
        </ul>
    );
}
