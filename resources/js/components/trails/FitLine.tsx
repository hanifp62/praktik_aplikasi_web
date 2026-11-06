import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { FitRingkas } from './types';

/**
 * Label kecocokan beserta satu alasan, untuk sel tabel Bandingkan (§89: Apa ini, Cocokkah,
 * Mengapa). Label tidak pernah berdiri sendiri: "Kenapa?" membuka alasannya di tempat,
 * tanpa bolak-balik ke server, karena alasannya sudah datang bersama barisnya.
 *
 * Kata "Kecocokan dasar" sudah menjadi kepala barisnya, jadi tidak diulang di setiap sel, dan
 * tombolnya menyebut nama jalurnya untuk pembaca layar, seperti kartu Jelajah (titipan W3 ke
 * W5).
 */
export function FitLine({ fit, trailId, nama }: { fit: FitRingkas; trailId: number; nama: string }) {
    const [terbuka, setTerbuka] = useState(false);
    const idAlasan = `alasan-kecocokan-${trailId}`;

    return (
        <div className="relative z-10 mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
            <span data-fit-label className="shrink-0">
                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
            </span>
            <Button size="sm" variant="ghost" aria-controls={idAlasan} aria-expanded={terbuka} onClick={() => setTerbuka((v) => !v)}>
                {terbuka ? 'Sembunyikan alasan' : 'Kenapa?'}
                <span className="sr-only"> {nama}</span>
            </Button>
            {terbuka ? (
                <p id={idAlasan} data-fit-reason className="w-full text-sm text-secondary">{fit.alasan}</p>
            ) : null}
        </div>
    );
}
