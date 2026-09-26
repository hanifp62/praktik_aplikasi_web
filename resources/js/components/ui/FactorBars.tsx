export type KategoriFaktor = 'kuat' | 'cukup' | 'lemah' | 'tak-diketahui';

export interface FaktorBar {
    label: string;
    detail: string | null;
    kategori: KategoriFaktor;
}

const kata: Record<KategoriFaktor, string> = { kuat: 'Mendukung', cukup: 'Cukup', lemah: 'Menahan', 'tak-diketahui': 'Belum diketahui' };
const lebar: Record<KategoriFaktor, string> = { kuat: 'w-full', cukup: 'w-2/3', lemah: 'w-1/3', 'tak-diketahui': 'w-full' };
const teks: Record<KategoriFaktor, string> = { kuat: 'text-brand-900', cukup: 'text-secondary', lemah: 'text-warn-900', 'tak-diketahui': 'text-muted' };
const isi: Record<KategoriFaktor, string> = { kuat: 'bg-brand-700', cukup: 'bg-muted', lemah: 'bg-warn-600', 'tak-diketahui': 'bg-subtle' };

/**
 * Tiga kategori, bukan persentase (BR-09): batang selebar 73% dapat dibaca balik
 * menjadi angka. Kategorinya dihitung server dari ambang config yang dipakai mesin;
 * klien hanya menggambar. Faktor tanpa data tidak digambar pendek (§95).
 */
export function FactorBars({ factors, className }: { factors: FaktorBar[]; className?: string }) {
    if (factors.length === 0) {
        return null;
    }

    return (
        <ul className={['space-y-3', className].filter(Boolean).join(' ')}>
            {factors.map((f) => (
                <li key={f.label}>
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <span className="text-sm font-medium text-primary">{f.label}</span>
                        <span className={`text-xs font-medium ${teks[f.kategori]}`}>{kata[f.kategori]}</span>
                    </div>
                    <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-subtle" aria-hidden="true">
                        <div className={`h-full rounded-full ${lebar[f.kategori]} ${isi[f.kategori]}`} />
                    </div>
                    {f.detail ? <p className="mt-1 text-xs text-secondary">{f.detail}</p> : null}
                </li>
            ))}
        </ul>
    );
}
