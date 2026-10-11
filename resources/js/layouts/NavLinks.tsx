import { Icon, type NamaIkon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { dashboard, progress, track } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';

export const fokus = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Mode pendakian satu trip: `/trips/{id}/hike`. Milik tab Mendaki, bukan tab Trip. */
const modePendakian = (p: string) => /^\/trips\/[^/]+\/hike/.test(p);

/**
 * Lima tab arsitektur informasi v2 (keputusan pemilik 25 September 2026, DK-25): Home,
 * Explore, Trips, Track, Progress, berlabel Indonesia. Profil bukan tab; ia dibuka dari
 * menu akun. Community bukan tab; ia lapisan di dalam halaman (laporan di rincian jalur,
 * bagikan laporan di Progres, kabar gunung yang diikuti di Beranda). Tab aktif dihitung
 * dari path, satu tab per halaman.
 */
export const permukaan = [
    { label: 'Beranda', ikon: 'house' as const, href: dashboard.url(), aktif: (p: string) => p === '/dashboard' || p.startsWith('/kabar') },
    {
        label: 'Jelajah',
        ikon: 'map-trifold' as const,
        href: trailsIndex.url(),
        aktif: (p: string) => (p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare'),
    },
    {
        label: 'Trip',
        ikon: 'backpack' as const,
        href: tripsIndex.url(),
        aktif: (p: string) =>
            (p.startsWith('/trips') && !modePendakian(p)) || p.startsWith('/goals') || p.startsWith('/recommendations') || p.startsWith('/trails/compare'),
    },
    { label: 'Mendaki', ikon: 'person-simple-hike' as const, href: track.url(), aktif: (p: string) => p.startsWith('/mendaki') || modePendakian(p) },
    {
        label: 'Progres',
        ikon: 'chart-line-up' as const,
        href: progress.url(),
        aktif: (p: string) => p.startsWith('/progres') || p.startsWith('/history') || p.startsWith('/reports'),
    },
];

export function TautanTab({ href, aktif, children }: { href: string; aktif: boolean; children: ReactNode }) {
    return (
        <Tautan
            href={href}
            className={kelas(
                'inline-flex min-h-11 items-center rounded-sm border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition',
                fokus,
                aktif ? 'border-brand-600 text-primary' : 'border-transparent text-muted hover:border-control hover:text-secondary',
            )}
        >
            {children}
        </Tautan>
    );
}

/** Baris menu profil: 44 px, fokus ring di dalam karena barisnya selebar menu. */
export function TautanMenu({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Tautan href={href} className={kelas('flex min-h-11 w-full items-center px-4 py-2 text-start text-sm leading-5 text-secondary transition hover:bg-surface-sunken focus-visible:ring-inset', fokus)}>
            {children}
        </Tautan>
    );
}

export function TautanResponsif({ href, aktif = false, children }: { href: string; aktif?: boolean; children: ReactNode }) {
    return (
        <Tautan
            href={href}
            className={kelas(
                'flex min-h-11 w-full items-center border-l-4 py-2 pe-4 ps-3 text-start text-base font-medium transition focus-visible:ring-inset',
                fokus,
                aktif ? 'border-brand-600 bg-brand-50 text-brand-900' : 'border-transparent text-secondary hover:border-control hover:bg-surface-sunken hover:text-primary',
            )}
        >
            {children}
        </Tautan>
    );
}

export const tombolMenu = kelas('flex min-h-11 w-full items-center px-4 py-2 text-start text-sm leading-5 text-secondary transition hover:bg-surface-sunken focus-visible:ring-inset', fokus);

/**
 * Tab navigasi bawah ponsel: ikon di atas label pendek, seluruh kolom menjadi target
 * sentuh (tinggi 64 px). Tab aktif ditandai empat cara sekaligus, bukan warna saja:
 * ikon varian isi, garis di atas, tebal huruf, dan aria-current (WCAG 1.4.1).
 */
export function TabBawah({ href, ikon, aktif, children }: { href: string; ikon: NamaIkon; aktif: boolean; children: ReactNode }) {
    return (
        <Tautan
            href={href}
            aria-current={aktif ? 'page' : undefined}
            className={kelas(
                'flex h-16 min-w-0 flex-1 flex-col items-center justify-center gap-1 border-t-2 px-0.5 text-xs leading-tight transition focus-visible:ring-inset',
                fokus,
                aktif ? 'border-brand-600 font-semibold text-primary' : 'border-transparent font-medium text-muted hover:text-secondary',
            )}
        >
            <Icon name={ikon} size={22} isi={aktif} />
            <span className="max-w-full truncate">{children}</span>
        </Tautan>
    );
}
