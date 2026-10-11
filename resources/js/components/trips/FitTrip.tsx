import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { kelas } from '@/lib/kelas';
import type { FitRingkas } from './types';

/**
 * Label kecocokan beserta alasannya, untuk halaman trip dan daftar trip. Berbeda dari
 * FitLine di Jelajah, alasannya selalu tampil: di sini pendaki sudah memilih jalurnya,
 * dan label tanpa alasan tidak pernah berdiri sendiri (§89).
 */
export function FitTrip({ fit, className }: { fit: FitRingkas; className?: string }) {
    return (
        <div className={kelas('mt-3 flex flex-wrap items-center gap-x-2 gap-y-1', className)}>
            <span data-fit-label className="shrink-0">
                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
            </span>
            <span className="text-xs text-muted">{fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
            <p data-fit-reason className="w-full text-sm text-secondary">{fit.alasan}</p>
        </div>
    );
}
