import type { ReactNode } from 'react';
import { Lockup } from '@/components/brand/Lockup';
import { kelas } from '@/lib/kelas';
import { LencanaDataContoh } from './LencanaDataContoh';
import { TautanProfil } from './TautanProfil';
import { TombolKembali } from './TombolKembali';

/** Induk logis halaman turunan: tujuan tombol kembali dan nama yang dibacakan. */
export interface Induk {
    href: string;
    label: string;
}

interface DasarKepala {
    title: string;
    description?: string;
    /** Aksi kontekstual di kanan baris kepala, misalnya lonceng Pengingat atau tautan Simpanan. */
    aksi?: ReactNode;
    children?: ReactNode;
}

/**
 * Akar tab memberi `akar` (Beranda menambah `merek`), halaman turunan memberi `induk`.
 * Salah satunya wajib, jadi TypeScript menolak halaman baru yang lupa menyebut tempatnya
 * di peta navigasi (PageHeaderContractTest menjaga siapa yang boleh menjadi akar).
 */
type PageHeaderProps = DasarKepala & ({ akar: true; merek?: boolean; induk?: never } | { induk: Induk; akar?: never; merek?: never });

/**
 * Header per halaman (spec v3 bagian 5.1). Ponsel tidak punya bilah atas global, jadi
 * baris ini yang membawa tempat pendaki:
 * - akar tab: h1 (28 px, bobot 800), aksi kontekstual, avatar 44 px ke Profil;
 * - Beranda (`merek`): lockup, aksi, dan avatar di baris atas, h1 sapaan di bawahnya;
 * - halaman turunan: tombol kembali 44 px ke induk logisnya dan aksi, h1 di bawahnya.
 * Satu baris flex yang membungkus, dan h1 hanya satu elemen: posisinya diatur urutan flex,
 * tidak digandakan. Mulai 1024 px header layar lebar membawa lockup, avatar, dan lencana
 * Data contoh, jadi ketiganya disembunyikan di sini dan sapaan Beranda naik ke baris atas.
 * Lencana Data contoh duduk di bawah judul, bukan di atasnya: label di atas h1 terbaca
 * sebagai eyebrow, dan baris atas di 360 px tidak muat untuk judul, aksi, avatar, dan
 * lencana sekaligus.
 */
export function PageHeader({ title, description, aksi, children, ...tempat }: PageHeaderProps) {
    const letakJudul = tempat.induk ? 'order-last basis-full' : tempat.merek ? 'order-last basis-full lg:order-first lg:basis-0 lg:flex-1' : 'flex-1';

    return (
        <header className="mb-6">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-3">
                {tempat.induk ? <TombolKembali href={tempat.induk.href} label={tempat.induk.label} /> : null}
                {tempat.merek ? <Lockup className="lg:hidden" /> : null}
                <div className={kelas('min-w-0', letakJudul)}>
                    <h1 className="text-balance text-judul text-primary">{title}</h1>
                </div>
                {aksi || tempat.akar ? (
                    <div className="ms-auto flex shrink-0 items-center gap-2">
                        {aksi}
                        {tempat.akar ? <TautanProfil className="lg:hidden" /> : null}
                    </div>
                ) : null}
            </div>
            <div className="mt-2 empty:hidden lg:hidden">
                <LencanaDataContoh />
            </div>
            {description ? <p className="mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children}
        </header>
    );
}
