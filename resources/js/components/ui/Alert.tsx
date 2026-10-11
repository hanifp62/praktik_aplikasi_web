import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';

type Varian = 'info' | 'warning' | 'danger' | 'success';

interface AlertProps {
    variant?: Varian;
    title?: string;
    /**
     * Matikan saat wilayah role=status atau role=alert lain yang sudah terpasang di dekatnya
     * mengumumkan isi yang sama (N-9): dua wilayah status untuk satu pertanyaan membuat
     * sebagian pembaca layar membacakannya dua kali. Bawaan tetap true di mana pun Alert
     * dipakai sendirian.
     */
    wilayahStatus?: boolean;
    className?: string;
    children: ReactNode;
}

/** Makna tidak pernah lewat warna saja: simbol dan awalan teks ikut dibacakan. */
export function Alert({ variant = 'info', title, wilayahStatus = true, className, children }: AlertProps) {
    const gaya: Record<Varian, [string, string, string]> = {
        warning: ['border-warn-300 bg-warn-100 text-warn-900', '!', 'Peringatan:'],
        danger: ['border-danger-300 bg-danger-100 text-danger-900', '×', 'Perhatian penting:'],
        success: ['border-brand-300 bg-brand-100 text-brand-900', '✓', 'Berhasil:'],
        info: ['border-subtle bg-surface-sunken text-primary', 'i', 'Informasi:'],
    };
    const [warna, simbol, awalan] = gaya[variant];
    // wilayahStatus dilipat ke nilai role itu sendiri (bukan di JSX) supaya DesignDirectionTest
    // tetap menemukan pola role={role} className={kelas('...rounded-kartu...' apa adanya.
    const role = wilayahStatus ? (variant === 'warning' || variant === 'danger' ? 'alert' : 'status') : undefined;

    return (
        <div role={role} className={kelas('flex gap-3 rounded-kartu border p-4 text-sm', warna, className)}>
            <span aria-hidden="true" className="font-semibold">{simbol}</span>
            <div className="space-y-1">
                <span className="sr-only">{awalan}</span>
                {title ? <p className="font-medium">{title}</p> : null}
                <div>{children}</div>
            </div>
        </div>
    );
}
