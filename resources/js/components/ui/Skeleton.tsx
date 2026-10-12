/** Bentuk saat memuat: lebar baris tidak seragam, disembunyikan dari pembaca layar. */
export function Skeleton({ rows = 3, className }: { rows?: number; className?: string }) {
    const lebar = [100, 85, 92];

    return (
        <div className={className}>
            <div aria-hidden="true" className="space-y-3">
                {Array.from({ length: rows }, (_, i) => (
                    <div key={i} data-skeleton-row className="h-4 animate-pulse rounded-sm bg-surface-sunken" style={{ width: `${lebar[i % 3]}%` }} />
                ))}
            </div>
            <p aria-live="polite" className="sr-only">Memuat isi halaman.</p>
        </div>
    );
}
