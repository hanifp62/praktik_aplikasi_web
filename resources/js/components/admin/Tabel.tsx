import type { ReactNode } from 'react';
import type { TautanHalaman } from '@/components/trails/types';
import { kelas } from '@/lib/kelas';

/** Bentuk paginasi dari App\Support\PaginasiInertia: baris sudah diformat server. */
export interface Paginasi<T> {
    data: T[];
    total: number;
    links: TautanHalaman[];
}

export interface KolomTabel<T> {
    judul: string;
    isi: (baris: T) => ReactNode;
    /** Kolom nama baris: dirender sebagai th scope="row", sehingga pembaca layar menyebut barisnya saat berpindah sel. */
    kepala?: boolean;
    className?: string;
}

interface BingkaiTabelProps {
    /** Keterangan tabel untuk pembaca layar (caption sr-only). */
    keterangan: string;
    kolom: string[];
    children: ReactNode;
}

/**
 * Bingkai tabel admin: judul kolom ber-scope, keterangan tersembunyi untuk pembaca layar,
 * dan gulir mendatar hanya di dalam tabel supaya halaman tidak ikut melebar di ponsel
 * (WCAG 1.4.10). Dipakai langsung oleh halaman yang barisnya bercabang (formulir sebaris,
 * baris rincian), dan oleh Tabel untuk yang cukup dengan daftar kolom.
 */
export function BingkaiTabel({ keterangan, kolom, children }: BingkaiTabelProps) {
    return (
        <div className="relative overflow-x-auto rounded-lg bg-surface">
            <table className="min-w-full text-sm">
                <caption className="sr-only">{keterangan}</caption>
                <thead>
                    <tr className="border-b border-subtle text-left text-muted">
                        {kolom.map((k) => (
                            <th key={k} scope="col" className="p-4 font-medium">{k}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>{children}</tbody>
            </table>
        </div>
    );
}

/** Baris kosong yang menjelaskan sebabnya, bukan sekadar "tidak ada data" (PRD §104). */
export function BarisKosong({ kolom, judul, penjelasan }: { kolom: number; judul: string; penjelasan: string }) {
    return (
        <tr>
            <td colSpan={kolom} className="p-4">
                <p className="font-medium text-primary">{judul}</p>
                <p className="mt-1 max-w-prose text-secondary">{penjelasan}</p>
            </td>
        </tr>
    );
}

interface TabelProps<T> {
    keterangan: string;
    kolom: Array<KolomTabel<T>>;
    baris: T[];
    kunci: (baris: T) => string | number;
    kosong: { judul: string; penjelasan: string };
}

/** Tabel data yang didefinisikan per kolom. Isi sel sudah teks siap tampil dari server. */
export function Tabel<T>({ keterangan, kolom, baris, kunci, kosong }: TabelProps<T>) {
    return (
        <BingkaiTabel keterangan={keterangan} kolom={kolom.map((k) => k.judul)}>
            {baris.length === 0 ? (
                <BarisKosong kolom={kolom.length} judul={kosong.judul} penjelasan={kosong.penjelasan} />
            ) : (
                baris.map((b) => (
                    <tr key={kunci(b)} className="border-b border-subtle align-top">
                        {kolom.map((k) =>
                            k.kepala ? (
                                <th key={k.judul} scope="row" className={kelas('p-4 text-left font-medium text-primary', k.className)}>{k.isi(b)}</th>
                            ) : (
                                <td key={k.judul} className={kelas('p-4 text-secondary', k.className)}>{k.isi(b)}</td>
                            ),
                        )}
                    </tr>
                ))
            )}
        </BingkaiTabel>
    );
}
