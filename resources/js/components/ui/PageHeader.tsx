import type { ReactNode } from 'react';

interface PageHeaderProps {
    title: string;
    description?: string;
    children?: ReactNode;
}

/** Judul halaman: serif lewat lapisan base (h1), deskripsi dibatasi lebar baca. */
export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="mb-8">
            <h1 className="text-balance text-3xl text-primary sm:text-4xl">{title}</h1>
            {description ? <p className="mt-2 max-w-prose text-base text-secondary">{description}</p> : null}
            {children}
        </div>
    );
}
