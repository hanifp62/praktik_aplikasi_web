import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';

interface CardProps {
    title?: string;
    subtitle?: string;
    className?: string;
    children?: ReactNode;
}

/** Satu garis rambut, bukan bayangan: kartu memisahkan tanpa mengangkat. */
export function Card({ title, subtitle, className, children }: CardProps) {
    return (
        <section className={kelas('rounded-lg border border-subtle bg-surface p-6', className)}>
            {title ? <h2 className="text-lg font-semibold text-primary">{title}</h2> : null}
            {subtitle ? <p className="mt-1 max-w-prose text-base text-secondary">{subtitle}</p> : null}
            <div className={kelas((title || subtitle) && 'mt-3')}>{children}</div>
        </section>
    );
}
