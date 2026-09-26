import type { ReactNode } from 'react';

interface EmptyStateProps {
    title: string;
    description?: string;
    children?: ReactNode;
}

/** Keadaan kosong menjelaskan penyebab dan menawarkan langkah (PRD §104). */
export function EmptyState({ title, description, children }: EmptyStateProps) {
    return (
        <div className="rounded-control border border-dashed border-subtle bg-surface p-8 text-center">
            <p className="text-sm font-medium text-primary">{title}</p>
            {description ? <p className="mx-auto mt-2 max-w-prose text-sm text-secondary">{description}</p> : null}
            {children ? <div className="mt-4 flex flex-wrap justify-center gap-2">{children}</div> : null}
        </div>
    );
}
