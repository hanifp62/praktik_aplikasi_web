import { useId, type InputHTMLAttributes, type ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { Checkbox } from './Field';

interface KotakCentangProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    keterangan?: string;
    /** Lencana keadaan di samping label, misalnya "Kritis". */
    lencana?: ReactNode;
}

/**
 * Baris kotak centang: seluruh baris adalah label yang terikat ke kotaknya, jadi menyentuh
 * teksnya ikut mencentang dan targetnya 44 px walau kotaknya 28 px. Keterangan ikut
 * dibacakan lewat aria-describedby.
 */
export function KotakCentang({ label, keterangan, lencana, id, className, ...rest }: KotakCentangProps) {
    const idBawaan = useId();
    const idKotak = id ?? idBawaan;

    return (
        <label htmlFor={idKotak} className={kelas('flex min-h-11 cursor-pointer items-start gap-3 py-2', className)}>
            <Checkbox id={idKotak} aria-describedby={keterangan ? `${idKotak}-keterangan` : undefined} className="mt-0.5" {...rest} />
            <span className="min-w-0">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="min-w-0 break-words text-isi font-semibold text-primary">{label}</span>
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
