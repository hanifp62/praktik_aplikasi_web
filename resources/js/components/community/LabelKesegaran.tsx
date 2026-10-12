import { kelas } from '@/lib/kelas';

/**
 * Kesegaran satu keterangan (PRD §93). Label berasal dari FreshnessState di server;
 * keterangan yang sudah lama ditandai dengan simbol dan warna peringatan, tetapi
 * maknanya tetap terbaca dari katanya, tidak dari warna saja.
 */
export function LabelKesegaran({ label, lama }: { label: string; lama: boolean }) {
    return (
        <span className={kelas('inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-xs', lama ? 'bg-warn-100 text-warn-900' : 'bg-surface-sunken text-secondary')}>
            <span className="sr-only">Kesegaran data:</span>
            {lama ? <span aria-hidden="true">!</span> : null}
            {label}
        </span>
    );
}
