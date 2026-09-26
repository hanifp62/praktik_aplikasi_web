import { usePage } from '@inertiajs/react';
import { Tautan } from '@/components/ui/Tautan';
import type { PropsBersama } from '@/types';
import { Mark } from './Mark';

interface LockupProps {
    href?: string;
    size?: 'md' | 'lg';
    className?: string;
}

/**
 * Lockup: tanda lalu wordmark. Nama datang dari props bersama (config('app.name')),
 * satu-satunya tempat nama produk hidup. Sebagai tautan, tingginya 44 px.
 */
export function Lockup({ href, size = 'md', className }: LockupProps) {
    const { merek } = usePage<PropsBersama>().props;

    const [tanda, huruf, jarak] =
        size === 'lg'
            ? ['h-12 w-12', 'text-2xl tracking-[0.05em]', 'gap-3']
            : ['h-8 w-8', 'text-[1.0625rem] tracking-[0.06em]', 'gap-2.5'];

    const isi = (
        <>
            <Mark className={`${tanda} text-logo-mark`} />
            <span className={`font-sans font-bold uppercase leading-none text-logo-type ${huruf}`}>{merek.nama}</span>
        </>
    );

    const dasar = `inline-flex min-h-11 items-center ${jarak} ${className ?? ''}`;

    if (href) {
        return (
            <Tautan
                href={href}
                className={`${dasar} rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2`}
            >
                {isi}
            </Tautan>
        );
    }

    return <span className={dasar}>{isi}</span>;
}
