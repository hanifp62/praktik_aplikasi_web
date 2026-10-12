import { useId, type InputHTMLAttributes, type ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { Checkbox } from './Field';

interface KotakCentangProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    keterangan?: string;
    /** Lencana keadaan di samping label, misalnya "Kritis". */
    lencana?: ReactNode;
    /** Id elemen penjelas tambahan (misalnya galat baris), dibacakan bersama keterangan. */
    penjelas?: string;
}

/**
 * Baris kotak centang: seluruh baris adalah label yang terikat ke kotaknya, jadi menyentuh
 * teksnya ikut mencentang dan targetnya 44 px walau kotaknya 28 px. Keterangan dan penjelas
 * ikut dibacakan lewat aria-describedby. Baris nonaktif tidak memakai kursor penunjuk, karena
 * menyentuhnya tidak berbuat apa-apa (tinjauan Tugas 4 M8).
 */
export function KotakCentang({ label, keterangan, lencana, penjelas, id, className, disabled, ...rest }: KotakCentangProps) {
    const idBawaan = useId();
    const idKotak = id ?? idBawaan;
    const dijelaskan = [keterangan ? `${idKotak}-keterangan` : null, penjelas ?? null].filter(Boolean).join(' ') || undefined;

    return (
        <label htmlFor={idKotak} className={kelas('flex min-h-11 items-start gap-3 py-2', disabled ? 'cursor-default' : 'cursor-pointer', className)}>
            <Checkbox id={idKotak} aria-describedby={dijelaskan} disabled={disabled} className="mt-0.5" {...rest} />
            <span className="min-w-0">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="min-w-0 wrap-break-word text-isi font-semibold text-primary">{label}</span>
                    {lencana}
                </span>
                {keterangan ? (
                    <span id={`${idKotak}-keterangan`} className="mt-0.5 block text-kecil text-secondary">
                        {keterangan}
                    </span>
                ) : null}
            </span>
        </label>
    );
}
