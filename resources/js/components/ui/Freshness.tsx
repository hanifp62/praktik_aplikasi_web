/**
 * Kesegaran data (PRD §93). `rendered` sudah diformat server dengan zona waktunya
 * ("24 Sep 2026 14:05 WIB"); klien tidak pernah mengonversi waktu sendiri.
 */
export function Freshness({ rendered, stateLabel, prefix = 'Diperbarui' }: { rendered: string | null; stateLabel?: string | null; prefix?: string }) {
    return (
        <p className="text-xs text-muted">
            {rendered ? `${prefix} ${rendered}` : 'Waktu pembaruan tidak tersedia'}
            {stateLabel ? <span className="ml-1 rounded bg-surface-sunken px-1.5 py-0.5">{stateLabel}</span> : null}
        </p>
    );
}
