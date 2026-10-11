import { useId } from 'react';
import { Icon } from '@/components/ui/Icon';
import { TombolIkon } from '@/components/ui/TombolIkon';

/**
 * Kotak pencarian Jelajah (FR-27): satu isian untuk nama gunung, nama jalur, atau kalimat
 * seperti "dekat Bogor untuk pemula". Namanya label tersembunyi yang dibacakan, bukan
 * placeholder saja; placeholder hanya contoh. Isian 16 px supaya Safari iOS tidak memperbesar
 * halaman saat difokus, dan tombol kosongkan 44 px menggantikan tombol bawaan peramban.
 */
export function KotakCari({ nilai, onUbah }: { nilai: string; onUbah: (nilai: string) => void }) {
    const id = useId();

    return (
        <form role="search" aria-label="Cari jalur" onSubmit={(e) => e.preventDefault()}>
            <label htmlFor={id} className="sr-only">
                Cari gunung, jalur, atau kalimat
            </label>
            <div className="relative">
                <Icon name="magnifying-glass" size={20} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-secondary" />
                <input
                    id={id}
                    type="search"
                    value={nilai}
                    onChange={(e) => onUbah(e.target.value)}
                    placeholder="Contoh: Bogor untuk pemula"
                    className="block min-h-12 w-full rounded-tombol border-control bg-surface pl-12 pr-14 text-isi text-primary placeholder:text-muted focus:border-brand-600 focus:ring-brand-600 [&::-webkit-search-cancel-button]:hidden"
                />
                {nilai !== '' ? <TombolIkon ikon="x" label="Kosongkan pencarian" varian="polos" onClick={() => onUbah('')} className="absolute right-1 top-1/2 -translate-y-1/2" /> : null}
            </div>
        </form>
    );
}
