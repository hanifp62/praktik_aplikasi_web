import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { FitRingkas } from './types';

/**
 * Label kecocokan beserta satu alasan, untuk tingkat daftar (§89: Apa ini, Cocokkah,
 * Mengapa). Label tidak pernah berdiri sendiri: "Kenapa?" membuka alasannya di tempat,
 * tanpa bolak-balik ke server, karena alasannya sudah datang bersama barisnya.
 */
export function FitLine({ fit, trailId }: { fit: FitRingkas; trailId: number }) {
    const [terbuka, setTerbuka] = useState(false);
    const idAlasan = `alasan-kecocokan-${trailId}`;

    return (
        <div className="relative z-10 mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
            <span data-fit-label className="shrink-0">
                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
            </span>
            <span className="text-xs text-muted">{fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
            <Button size="sm" variant="ghost" aria-controls={idAlasan} aria-expanded={terbuka} onClick={() => setTerbuka((v) => !v)}>
                {terbuka ? 'Sembunyikan alasan' : 'Kenapa?'}
            </Button>
            {terbuka ? (
                <p id={idAlasan} data-fit-reason className="w-full text-sm text-secondary">{fit.alasan}</p>
            ) : null}
        </div>
    );
}
