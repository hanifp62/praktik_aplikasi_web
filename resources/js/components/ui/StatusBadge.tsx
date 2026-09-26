export type StatusResmi = 'OPEN' | 'RESTRICTED' | 'CLOSED' | null;

/**
 * Status resmi jalur. OPEN bukan jaminan aman (R-012): warnanya hanya menandai keadaan
 * operasional, dan katanya selalu tertulis. `text` adalah label dari enum server.
 */
export function StatusBadge({ status, text, scope }: { status: StatusResmi; text?: string | null; scope?: string | null }) {
    const warna =
        status === 'OPEN'
            ? 'bg-brand-100 text-brand-900 border-brand-300'
            : status === 'RESTRICTED'
              ? 'bg-warn-100 text-warn-900 border-warn-300'
              : status === 'CLOSED'
                ? 'bg-danger-100 text-danger-900 border-danger-300'
                : 'bg-surface-sunken text-secondary border-subtle';

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-control border px-2.5 py-1 text-sm font-medium ${warna}`}>
            <span className="sr-only">Status resmi:</span>
            {text ?? 'Belum diketahui'}
            {scope ? <span className="text-xs font-normal">({scope})</span> : null}
        </span>
    );
}
