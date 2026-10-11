import { kelas } from '@/lib/kelas';
import { fokusDiDalam } from './gaya';
import { Icon, type NamaIkon } from './Icon';

export interface PilihanSegmen<T extends string> {
    nilai: T;
    label: string;
    keterangan?: string;
    ikon?: NamaIkon;
}

interface KontrolSegmenProps<T extends string> {
    /** Nama grup radio, unik di satu halaman. */
    nama: string;
    /** Nama grup bagi pembaca layar (legend). */
    label: string;
    pilihan: ReadonlyArray<PilihanSegmen<T>>;
    nilai: T;
    onUbah: (nilai: T) => void;
    /** id unsur yang menjelaskan grup ini, misalnya galat validasinya (aria-describedby). */
    penjelas?: string;
    className?: string;
}

/**
 * Kontrol segmen: grup radio asli di dalam fieldset, bukan tombol berperan tab. Tombol
 * panah berpindah pilihan tanpa kode tambahan dan pembaca layar menyebut posisinya. Segmen
 * terpilih ditandai permukaan terangkat, garis, dan tebal huruf, bukan warna saja; label
 * dan keterangan panjang membungkus di dalam segmennya.
 */
export function KontrolSegmen<T extends string>({ nama, label, pilihan, nilai, onUbah, penjelas, className }: KontrolSegmenProps<T>) {
    return (
        <fieldset className={className} aria-describedby={penjelas}>
            <legend className="sr-only">{label}</legend>
            <div className="flex gap-1 rounded-tombol bg-surface-sunken p-1">
                {pilihan.map((p) => (
                    <label
                        key={p.nilai}
                        className={kelas(
                            'flex min-h-11 min-w-11 flex-1 cursor-pointer flex-col items-center justify-center rounded-control border border-transparent px-3 py-1.5 text-center text-kecil font-semibold text-secondary',
                            'has-[:checked]:border-control has-[:checked]:bg-surface has-[:checked]:font-bold has-[:checked]:text-primary',
                            fokusDiDalam,
                        )}
                    >
                        <input type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai} onChange={() => onUbah(p.nilai)} className="sr-only" />
                        <span className="inline-flex max-w-full items-center gap-1.5">
                            {p.ikon ? <Icon name={p.ikon} size={20} /> : null}
                            <span className="min-w-0 break-words">{p.label}</span>
                        </span>
                        {p.keterangan ? <span className="text-meta font-medium text-muted">{p.keterangan}</span> : null}
                    </label>
                ))}
            </div>
        </fieldset>
    );
}
