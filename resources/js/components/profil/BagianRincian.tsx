import type { ReactNode } from 'react';
import { Tautan } from '@/components/ui/Tautan';
import type { RincianProfil } from './types';

interface BagianRincianProps {
    id: string;
    judul: string;
    rincian: RincianProfil[];
    /** Tautan "Ubah" ke langkah onboarding yang bersangkutan; tanpa tautan bila belum dapat diubah. */
    ubah?: string | null;
    children?: ReactNode;
}

/**
 * Satu bagian ringkasan Profil (prototype 14): judul dengan "Ubah" di kanannya dan baris label
 * dan nilai. Nilai yang belum diisi ditulis "Belum diisi" dengan warna redup, bukan
 * disembunyikan atau ditebak (R-007).
 */
export function BagianRincian({ id, judul, rincian, ubah = null, children }: BagianRincianProps) {
    return (
        <section aria-labelledby={id} className="rounded-lg border border-subtle bg-surface p-5">
            <div className="flex items-center justify-between gap-3">
                <h2 id={id} className="text-bagian text-primary">
                    {judul}
                </h2>
                {ubah ? (
                    <Tautan
                        href={ubah}
                        className="-mr-2 inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        Ubah<span className="sr-only"> {judul.toLocaleLowerCase('id-ID')}</span>
                    </Tautan>
                ) : null}
            </div>
            <dl className="mt-2 divide-y divide-subtle">
                {rincian.map((r) => (
                    <div key={r.label} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 py-2.5">
                        <dt className="text-kecil text-secondary">{r.label}</dt>
                        <dd className={r.nilai ? 'min-w-0 break-words text-right text-kecil font-semibold text-primary' : 'text-kecil text-muted'}>{r.nilai ?? 'Belum diisi'}</dd>
                    </div>
                ))}
            </dl>
            {children}
        </section>
    );
}

