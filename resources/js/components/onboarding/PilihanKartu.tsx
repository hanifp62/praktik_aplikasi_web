import { kelas } from '@/lib/kelas';
import { fokusDiDalam } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';

/** Satu pilihan dari server: nilai enum, label, dan keterangan opsional. */
export interface OpsiKartu {
    nilai: string;
    label: string;
    keterangan?: string;
}

interface PilihanKartuProps {
    /** Nama grup radio, unik di satu halaman. */
    nama: string;
    /** Legend grup. Disembunyikan bila judul yang sama sudah tertulis di atasnya. */
    label: string;
    labelTersembunyi?: boolean;
    /** Kartu bertumpuk dengan keterangan, atau chip sebaris untuk pilihan pendek tanpa keterangan. */
    bentuk?: 'kartu' | 'chip';
    pilihan: OpsiKartu[];
    nilai: string;
    onUbah: (nilai: string) => void;
    /** id galat yang menjelaskan grup ini. */
    penjelas?: string;
}

/**
 * Grup radio satu pilihan (prototype 00). Bentuk kartu: satu baris 44 px atau lebih dengan label
 * dan keterangannya. Bentuk chip: label pendek yang membungkus antarchip, tidak di tengah kata,
 * seperti ChipPilihan tetapi radio. Pilihan aktif ditandai titik radio atau centang, garis, dan
 * latar, bukan warna saja (WCAG 1.4.1); tombol panah berpindah pilihan karena ini radio asli.
 */
export function PilihanKartu({ nama, label, labelTersembunyi = false, bentuk = 'kartu', pilihan, nilai, onUbah, penjelas }: PilihanKartuProps) {
    return (
        <fieldset aria-describedby={penjelas}>
            <legend className={labelTersembunyi ? 'sr-only' : 'text-isi font-bold text-primary'}>{label}</legend>
            <div className={kelas(bentuk === 'chip' ? 'flex flex-wrap gap-x-1' : 'space-y-2', !labelTersembunyi && 'mt-2')}>
                {pilihan.map((p) =>
                    bentuk === 'chip' ? (
                        <label key={p.nilai} className="inline-flex min-h-11 min-w-11 max-w-full cursor-pointer items-center">
                            <input type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai} onChange={() => onUbah(p.nilai)} className="peer sr-only" />
                            <span
                                className={kelas(
                                    'inline-flex min-h-9 max-w-full items-center gap-1.5 rounded-full border px-3.5 py-1 text-kecil',
                                    'peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2',
                                    nilai === p.nilai ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary hover:bg-surface-sunken',
                                )}
                            >
                                {nilai === p.nilai ? <Icon name="check" size={16} /> : null}
                                <span className="min-w-0 break-words">{p.label}</span>
                            </span>
                        </label>
                    ) : (
                        <label
                            key={p.nilai}
                            className={kelas(
                                'flex min-h-11 cursor-pointer items-start gap-3 rounded-tombol border border-subtle bg-surface px-4 py-3',
                                'has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50',
                                fokusDiDalam,
                            )}
                        >
                            <input
                                type="radio"
                                name={nama}
                                value={p.nilai}
                                checked={nilai === p.nilai}
                                onChange={() => onUbah(p.nilai)}
                                className="mt-0.5 size-5 shrink-0 border-2 border-control text-brand-700 checked:border-transparent focus:outline-hidden focus:ring-0 focus:ring-offset-0"
                            />
                            <span className="min-w-0">
                                <span className="block break-words text-isi font-bold text-primary">{p.label}</span>
                                {p.keterangan ? <span className="mt-0.5 block text-kecil text-secondary">{p.keterangan}</span> : null}
                            </span>
                        </label>
                    ),
                )}
            </div>
        </fieldset>
    );
}
