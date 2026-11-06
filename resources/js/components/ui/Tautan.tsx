import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { adalahHalamanReact } from '@/lib/halamanReact';

interface TautanProps {
    href: string;
    children: ReactNode;
    className?: string;
    preserveScroll?: boolean;
    'aria-current'?: 'page' | undefined;
    'aria-label'?: string;
    'aria-describedby'?: string;
    title?: string;
}

/**
 * Tautan yang tahu dunia mana yang ditujunya: kunjungan Inertia untuk halaman React,
 * muat penuh untuk halaman Blade, berkas, dan situs lain. Pemanggil tidak perlu tahu
 * bedanya.
 */
export function Tautan({ href, children, preserveScroll, ...rest }: TautanProps) {
    if (adalahHalamanReact(href)) {
        return (
            <Link href={href} preserveScroll={preserveScroll} {...rest}>
                {children}
            </Link>
        );
    }

    return (
        <a href={href} {...rest}>
            {children}
        </a>
    );
}
