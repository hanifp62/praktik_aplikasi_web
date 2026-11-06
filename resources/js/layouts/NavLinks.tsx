import type { ItemTab } from '@/components/ui/BilahTab';
import { Tautan } from '@/components/ui/Tautan';
import type { ReactNode } from 'react';
import { dashboard, progress, track } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';

/** Mode pendakian satu trip: `/trips/{id}/hike`. Milik tab Mendaki, bukan tab Trip. */
const modePendakian = (p: string) => /^\/trips\/[^/]+\/hike/.test(p);

/**
 * Lima tab arsitektur informasi v2 (keputusan pemilik 25 September 2026, DK-25): Home,
 * Explore, Trips, Track, Progress, berlabel Indonesia. Profil bukan tab; ia dibuka dari
 * avatar (ruling W2-R1). Community bukan tab; ia lapisan di dalam halaman (laporan di
 * rincian jalur, bagikan laporan di Progres, kabar gunung yang diikuti di Beranda). Tab
 * aktif dihitung dari path, satu tab per halaman. Halaman gunung milik Jelajah (spec v3
 * bagian 5.3): path-nya di luar /trails, jadi disebut sendiri.
 */
export const permukaan = [
    { label: 'Beranda', ikon: 'house' as const, href: dashboard.url(), aktif: (p: string) => p === '/dashboard' || p.startsWith('/kabar') },
    {
        label: 'Jelajah',
        ikon: 'map-trifold' as const,
        href: trailsIndex.url(),
        aktif: (p: string) => ((p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare')) || p.startsWith('/mountains/'),
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

/**
 * Satu sumber untuk bilah tab ponsel dan navigasi segmen layar lebar (spec v3 bagian 5):
 * daftar, urutan, dan tab aktif yang sama di kedua ukuran, jadi pendaki yang berpindah dari
 * ponsel ke laptop menemukan tujuannya di tempat yang sama.
 */
export function itemTab(jalur: string): ItemTab[] {
    return permukaan.map((p) => ({ label: p.label, href: p.href, ikon: p.ikon, aktif: p.aktif(jalur) }));
}

/** Baris menu akun: 44 px, cincin fokus di dalam karena barisnya selebar menu. */
export const tombolMenu =
    'flex min-h-11 w-full items-center rounded-control px-3 py-2 text-start text-kecil font-semibold text-primary hover:bg-surface-sunken focus:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600';

export function TautanMenu({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Tautan href={href} className={tombolMenu}>
            {children}
        </Tautan>
    );
}
