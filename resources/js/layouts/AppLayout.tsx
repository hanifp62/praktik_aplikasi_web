import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Lockup } from '@/components/brand/Lockup';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { kelas } from '@/lib/kelas';
import { dashboard, profile } from '@/routes';
import type { PropsBersama } from '@/types';
import { TabBawah, TautanMenu, TautanResponsif, TautanTab, fokus, permukaan, tombolMenu } from './NavLinks';

/** Layout aplikasi: navigasi lima permukaan, menu profil dengan peran, landmark utama. */
export function AppLayout({ header, children }: { header?: ReactNode; children: ReactNode }) {
    const { auth } = usePage<PropsBersama>().props;
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    const [terbuka, setTerbuka] = useState(false);
    const [menuProfil, setMenuProfil] = useState(false);
    const pengguna = auth.user;

    const keluar = () => router.post('/logout');

    return (
        /*
         * --nav-bawah: tinggi navigasi bawah ponsel (64 px + safe area), 0 mulai sm.
         * Elemen yang menempel di bawah layar (baki simpanan, bar aksi) memakai
         * `bottom: var(--nav-bawah)` supaya tidak tertutup, dan <main> memberi ruang
         * setinggi itu supaya isi terakhir tidak tersembunyi di belakangnya.
         */
        <div className="min-h-dvh bg-canvas [--nav-bawah:calc(4rem+env(safe-area-inset-bottom,0px))] sm:[--nav-bawah:0px]">
            <JudulHalaman />
            <nav className="border-b border-subtle bg-surface">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center gap-3">
                                <Lockup href={dashboard.url()} />
                                <LencanaDataContoh />
                            </div>
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {permukaan.map((p) => (
                                    <TautanTab key={p.label} href={p.href} aktif={p.aktif(path)}>{p.label}</TautanTab>
                                ))}
                            </div>
                        </div>

                        {pengguna ? (
                            <div className="relative hidden sm:ms-6 sm:flex sm:items-center">
                                <button
                                    type="button"
                                    onClick={() => setMenuProfil((v) => !v)}
                                    aria-expanded={menuProfil}
                                    aria-haspopup="menu"
                                    className={kelas('inline-flex min-h-11 items-center rounded-control px-3 py-2 text-sm font-medium leading-4 text-muted transition hover:text-secondary', fokus)}
                                >
                                    {pengguna.name}
                                    <svg className="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                    </svg>
                                </button>
                                {menuProfil ? (
                                    <div className="absolute end-0 top-full z-50 mt-2 w-48 rounded-control border border-subtle bg-surface py-1 shadow-overlay" role="menu">
                                        <TautanMenu href={profile.url()}>Profil</TautanMenu>
                                        {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                                        <button type="button" onClick={keluar} className={tombolMenu}>Keluar</button>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                type="button"
                                onClick={() => setTerbuka((v) => !v)}
                                aria-expanded={terbuka}
                                aria-label={terbuka ? 'Tutup menu akun' : 'Buka menu akun'}
                                className={kelas('inline-flex min-h-11 min-w-11 items-center justify-center rounded-control p-2 text-muted transition hover:bg-surface-sunken', fokus)}
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    {terbuka
                                        ? <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                        : <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />}
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {terbuka ? (
                    <div className="sm:hidden">
                        {/* Lima permukaan tinggal di navigasi bawah; menu ini untuk akun. */}
                        {pengguna ? (
                            <div className="pb-1 pt-4">
                                <div className="px-4">
                                    <div className="text-base font-medium text-primary">{pengguna.name}</div>
                                    <div className="text-sm font-medium text-muted">{pengguna.email}</div>
                                </div>
                                <div className="mt-3 space-y-1">
                                    <TautanResponsif href={profile.url()}>Profil</TautanResponsif>
                                    {pengguna.isModerator ? <TautanResponsif href="/moderation">Moderasi</TautanResponsif> : null}
                                    {pengguna.isAdmin ? <TautanResponsif href="/admin/trails">Admin</TautanResponsif> : null}
                                    <button type="button" onClick={keluar} className={kelas('flex min-h-11 w-full items-center border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-secondary hover:bg-surface-sunken', fokus)}>Keluar</button>
                                </div>
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </nav>

            {header ? (
                <header className="border-b border-subtle bg-surface">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            ) : null}

            <main id="konten" tabIndex={-1} className="pb-[var(--nav-bawah)]">{children}</main>

            {/*
             * Navigasi bawah ponsel: lima permukaan yang sama dengan menu atas, dalam
             * urutan yang sama (NavigationConsistencyTest). Ibu jari menjangkau bagian
             * bawah layar, bukan menu hamburger di pojok kanan atas; itu sebabnya
             * aplikasi rujukan (AllTrails, Strava, Traveloka) menaruh tab di sini.
             * Menu hamburger tetap ada untuk profil, peran, dan keluar.
             */}
            <nav aria-label="Navigasi utama" className="fixed inset-x-0 bottom-0 z-40 border-t border-subtle bg-surface sm:hidden" style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}>
                <div className="flex">
                    {permukaan.map((p) => (
                        <TabBawah key={p.label} href={p.href} ikon={p.ikon} aktif={p.aktif(path)}>{p.label}</TabBawah>
                    ))}
                </div>
            </nav>
        </div>
    );
}
