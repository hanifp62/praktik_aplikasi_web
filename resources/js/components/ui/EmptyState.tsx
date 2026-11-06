import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';

interface IsiKosong {
    title: string;
    description?: string;
    children?: ReactNode;
}

/**
 * Tiga jenis keadaan kosong (PRD §104; spec v3 bagian 8.8; antislop R-27). Masing-masing
 * menjawab pertanyaan yang berbeda, jadi jenisnya wajib disebut pemanggil:
 * - pertama: pengguna belum pernah membuat apa pun di sini; tawarkan langkah pertama.
 * - tersaring: datanya ada, saringan yang menyembunyikannya; tawarkan cara melepasnya.
 * - menunggu: datanya belum ada karena pihak lain; sebut siapa yang ditunggu, misalnya
 *   "Menunggu BMKG" atau "Menunggu kurator Memount".
 */
type EmptyStateProps = (IsiKosong & { jenis: 'pertama' | 'tersaring' }) | (IsiKosong & { jenis: 'menunggu'; menunggu: string });

const bingkai: Record<EmptyStateProps['jenis'], string> = {
    pertama: 'border border-subtle bg-surface',
    tersaring: 'border border-subtle bg-surface-sunken',
    menunggu: 'border-2 border-dashed border-control bg-surface',
};

export function EmptyState(props: EmptyStateProps) {
    const { jenis, title, description, children } = props;

    return (
        <div data-kosong={jenis} className={kelas('rounded-lg p-6 text-center sm:p-8', bingkai[jenis])}>
            <p className="text-bagian text-primary">{title}</p>
            {props.jenis === 'menunggu' ? <p className="mx-auto mt-2 max-w-prose text-kecil font-bold text-secondary">{props.menunggu}</p> : null}
            {description ? <p className="mx-auto mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children ? <div className="mt-4 flex flex-wrap justify-center gap-2">{children}</div> : null}
        </div>
    );
}
