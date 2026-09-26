import { Link } from '@inertiajs/react';
import { kelas } from '@/lib/kelas';
import type { TautanHalaman } from '@/components/trails/types';

/** Paginasi dari tautan yang dibangun server; label HTML entitas dari Laravel dibersihkan. */
export function Pagination({ links }: { links: TautanHalaman[] }) {
    if (links.length <= 3) {
        return null;
    }

    const teks = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»').replace(/<[^>]+>/g, '');

    return (
        <nav aria-label="Paginasi" className="flex flex-wrap gap-1">
            {links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        preserveScroll
                        aria-current={l.active ? 'page' : undefined}
                        className={kelas(
                            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-control border px-3 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
                            l.active ? 'border-brand-600 bg-brand-50 text-brand-900' : 'border-subtle bg-surface text-secondary hover:bg-surface-sunken',
                        )}
                    >
                        {teks(l.label)}
                    </Link>
                ) : (
                    <span key={i} className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-control border border-subtle px-3 text-sm text-muted">{teks(l.label)}</span>
                ),
            )}
        </nav>
    );
}
