import type { SVGProps } from 'react';

interface MarkProps extends Omit<SVGProps<SVGSVGElement>, 'viewBox'> {
    size?: 'l' | 's';
    label?: string;
}

/**
 * Tanda merek "Jalur Berpos": satu garis rute yang mendaki lewat dua belokan dan
 * berakhir pada cincin pos. Dua master, bukan satu yang diskalakan: master S dihitung
 * agar celah bersih tetap 2,4 px pada 16 px.
 */
export function Mark({ size = 'l', label, className, ...rest }: MarkProps) {
    const kecil = size === 's';

    return (
        <svg
            viewBox={kecil ? '0 0 16 16' : '0 0 48 48'}
            fill="none"
            stroke="currentColor"
            strokeWidth={kecil ? 2 : 5}
            strokeLinecap="round"
            strokeLinejoin="round"
            role={label ? 'img' : undefined}
            aria-hidden={label ? undefined : true}
            className={['shrink-0', className].filter(Boolean).join(' ')}
            {...rest}
        >
            {label ? <title>{label}</title> : null}
            {kecil ? (
                <>
                    <path d="M2.5 13.5 L9 10.5 L5 7.5 L10 5" />
                    <circle cx="12" cy="4" r="2.25" />
                </>
            ) : (
                <>
                    <path d="M7.25 41 L27.25 32 L14.25 23 L31.25 14.3" />
                    <circle cx="35.75" cy="12" r="5" />
                </>
            )}
        </svg>
    );
}
