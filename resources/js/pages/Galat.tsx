import { useEffect, useRef } from 'react';
import { Lockup } from '@/components/brand/Lockup';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';

/** Isi satu halaman galat dari App\Support\HalamanGalat::isi(), sama dengan halaman Blade muat penuh. */
export interface IsiGalat {
    kode: number;
    label: string;
    judul: string;
    penjelasan: string[];
    tindakan: Array<{ label: string; href: string; utama: boolean }>;
}

/**
 * Halaman galat untuk kunjungan Inertia (ruling W4-6j, W9): 403, 404, 419, 429, 500, dan 503
 * yang dulu tampil sebagai dialog pengembang Inertia berisi halaman HTML di iframe, untuk 429
 * bahkan berbahasa Inggris. Tanpa kerangka dan bilah tab: props bersama kerangka membaca
 * basis data, sedangkan halaman ini dirender ketika sesuatu mungkin sedang rusak, jadi
 * server hanya mengirim isi galat, merek, dan tema. Setiap tombol membuka halaman yang
 * dapat dibuka dengan GET.
 */
export default function Galat({ galat }: { galat: IsiGalat }) {
    const judul = useRef<HTMLHeadingElement>(null);

    // Halaman ini menggantikan halaman asal tanpa muat penuh sesudah aksi gagal, dan tombol yang
    // baru ditekan ikut hilang. Fokus dipindah ke judul supaya pembaca layar menyebut apa yang
    // terjadi (audit aksesibilitas W9; ErrorPageFocusTest).
    useEffect(() => {
        judul.current?.focus({ preventScroll: true });
    }, [galat.kode]);

    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <main id="konten" tabIndex={-1} className="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-5 py-12">
                <Lockup href="/" className="self-start" />

                <p className="mt-10 text-meta font-bold text-muted">{galat.label}</p>
                <h1 ref={judul} tabIndex={-1} className="mt-2 text-judul text-primary">{galat.judul}</h1>

                <div className="mt-4 space-y-3 text-isi text-secondary">
                    {galat.penjelasan.map((kalimat) => (
                        <p key={kalimat}>{kalimat}</p>
                    ))}
                </div>

                <div className="mt-8 flex flex-wrap gap-3">
                    {galat.tindakan.map((tindakan) => (
                        <Button key={tindakan.label} href={tindakan.href} variant={tindakan.utama ? 'primary' : 'secondary'}>
                            {tindakan.label}
                        </Button>
                    ))}
                </div>
            </main>
        </div>
    );
}

