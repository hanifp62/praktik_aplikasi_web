import { useId } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusKelompok } from './gaya';

interface SakelarProps {
    judul: string;
    keterangan?: string;
    nyala: boolean;
    onUbah: (nyala: boolean) => void;
    disabled?: boolean;
    className?: string;
}

/**
 * Sakelar: tombol berperan switch yang menyebut keadaannya (aria-checked) dan dinamai
 * judulnya. Keadaan dibawa posisi kenop dan isian jalurnya, tidak warna saja; target sentuh
 * 44 px walau jalurnya 28 px. Geser kenop 120 ms adalah umpan balik tekan sakelar itu
 * sendiri (daftar gerak spec v3 bagian 8.6). Judul dan keterangan membungkus bila panjang.
 */
export function Sakelar({ judul, keterangan, nyala, onUbah, disabled = false, className }: SakelarProps) {
    const id = useId();

    return (
        <div className={kelas('flex items-start justify-between gap-4', className)}>
            <div className="min-w-0 py-2">
                <p id={`${id}-judul`} className="text-isi font-semibold text-primary">
                    {judul}
                </p>
                {keterangan ? (
                    <p id={`${id}-keterangan`} className="mt-0.5 text-kecil text-secondary">
                        {keterangan}
                    </p>
                ) : null}
            </div>
            <button
                type="button"
                role="switch"
                aria-checked={nyala}
                aria-labelledby={`${id}-judul`}
                aria-describedby={keterangan ? `${id}-keterangan` : undefined}
                disabled={disabled}
                onClick={() => onUbah(!nyala)}
                className="group inline-flex h-11 w-14 shrink-0 items-center justify-center rounded-full focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span
                    aria-hidden="true"
                    className={kelas(
                        'relative inline-flex h-7 w-12 items-center rounded-full border-2 transition-colors duration-sentuh ease-gerak',
                        fokusKelompok,
                        nyala ? 'border-brand-700 bg-brand-700' : 'border-control bg-control',
                    )}
                >
                    <span className={kelas('inline-block size-5 rounded-full border border-subtle bg-surface transition-transform duration-sentuh ease-gerak', nyala ? 'translate-x-[1.375rem]' : 'translate-x-0.5')} />
                </span>
            </button>
        </div>
    );
}
