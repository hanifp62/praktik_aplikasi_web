export type LabelFit = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null;

/** Tiga label publik (R-004); status tidak pernah lewat warna saja. */
export function FitBadge({ label, text }: { label: LabelFit; text: string }) {
    const gaya =
        label === 'COCOK'
            ? ['bg-brand-100 text-brand-900 border-brand-300', '✓']
            : label === 'PERLU_PERSIAPAN'
              ? ['bg-warn-100 text-warn-900 border-warn-300', '!']
              : label === 'KURANG_COCOK'
                ? ['bg-danger-100 text-danger-900 border-danger-300', '×']
                : ['bg-surface-sunken text-secondary border-subtle', '?'];

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium ${gaya[0]}`}>
            <span aria-hidden="true">{gaya[1]}</span>
            {text}
        </span>
    );
}
