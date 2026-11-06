import { usePage } from '@inertiajs/react';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

/**
 * Navigasi antar-halaman admin. Angka "perlu ditinjau" datang dari props bersama
 * (hanya untuk admin) supaya status kedaluwarsa terlihat dari halaman admin mana pun.
 */
const tautan: Array<[string, string]> = [
    ['/admin/mountains', 'Gunung'],
    ['/admin/trails', 'Jalur'],
    ['/admin/sources', 'Sumber Data'],
    ['/admin/official-statuses', 'Status Resmi'],
    ['/admin/permits', 'Perizinan'],
    ['/admin/authorities', 'Badan Resmi'],
    ['/admin/credentials', 'Kredensial Ahli'],
    ['/admin/audit-logs', 'Jejak Audit'],
    ['/admin/analytics', 'Analitik'],
    ['/admin/usability', 'Studi Kegunaan'],
    ['/admin/health', 'Kesehatan Sistem'],
];

export function AdminNav() {
    const { adminNav } = usePage<PropsBersama>().props;
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    const perluDitinjau = adminNav?.perluDitinjau ?? 0;

    return (
        <nav aria-label="Navigasi admin" className="mb-6 border-b border-subtle">
            <ul className="-mb-px flex flex-wrap gap-x-4 gap-y-1">
                {tautan.map(([href, label]) => {
                    const aktif = path === href || path.startsWith(href + '/');

                    return (
                        <li key={href}>
                            <Tautan
                                href={href}
                                aria-current={aktif ? 'page' : undefined}
                                className={kelas(
                                    'inline-flex min-h-11 items-center gap-1.5 border-b-2 px-1 text-sm font-medium focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 rounded-xs',
                                    aktif ? 'border-brand-600 text-primary' : 'border-transparent text-muted hover:border-control hover:text-secondary',
                                )}
                            >
                                {label}
                                {href === '/admin/official-statuses' && perluDitinjau > 0 ? (
                                    <span className="rounded-full bg-warn-100 px-2 py-0.5 text-xs font-medium text-warn-900">
                                        {perluDitinjau}
                                        <span className="sr-only"> status perlu ditinjau</span>
                                    </span>
                                ) : null}
                            </Tautan>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
