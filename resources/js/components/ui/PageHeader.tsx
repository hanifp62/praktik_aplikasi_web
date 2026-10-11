import type { ReactNode } from 'react';

interface PageHeaderProps {
    title: string;
    description?: string;
    children?: ReactNode;
}

/**
 * Judul halaman: text-judul (28 px, bobot 800, satu keluarga dengan antarmuka) lewat
 * skala tipe bernama; deskripsi dibatasi lebar baca.
 */
export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="mb-6">
            <h1 className="text-balance text-judul text-primary">{title}</h1>
            {description ? <p className="mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children}
        </div>
    );
}
