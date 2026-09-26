import { Link, usePage } from '@inertiajs/react';
import { Lockup } from '@/components/brand/Lockup';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { Button } from '@/components/ui/Button';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { dashboard, login, register } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

const langkah = [
    ['Ceritakan pengalaman Anda', 'Berapa kali mendaki, medan apa saja yang pernah dilalui, seberapa jauh kemampuan navigasi Anda.'],
    ['Lihat kecocokan jalurnya', 'Setiap jalur dinilai terhadap profil dan rencana Anda, lengkap dengan alasannya. Jalur yang tidak cocok tetap ditampilkan beserta sebabnya.'],
    ['Siapkan dan periksa', 'Daftar persiapan menyesuaikan jalur dan jenis trip, lalu diperiksa ulang sebelum hari keberangkatan.'],
] as const;

/**
 * Halaman depan: satu pertanyaan per layar. Apa ini, bagaimana cara kerjanya, dari
 * mana datanya. Meta description dirender server dari prop halaman ini; judul tab
 * dipasang JudulHalaman supaya klien tidak menimpanya dengan nama merek saja.
 */
export default function Welcome() {
    const { auth, merek } = usePage<PropsBersama>().props;

    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <header className="border-b border-subtle">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <Lockup href="/" />
                    {auth.user ? (
                        <Button size="sm" href={dashboard.url()}>Buka dasbor</Button>
                    ) : (
                        <Button variant="secondary" size="sm" href={login.url()}>Masuk</Button>
                    )}
                </div>
            </header>

            <main id="konten" tabIndex={-1}>
                <section className="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
                    <p className="text-sm font-medium uppercase tracking-[0.06em] text-brand-ink">{merek.tagline}</p>
                    <h1 className="mt-3 max-w-2xl text-3xl font-semibold leading-tight text-primary sm:text-4xl">
                        Cari tahu jalur mana yang sesuai kemampuan Anda, sebelum berangkat
                    </h1>
                    <p className="mt-4 max-w-2xl text-base text-secondary sm:text-lg">
                        Informasi pendakian tersebar di banyak tempat: karakteristik jalur, prakiraan cuaca, status resmi jalur, dan daftar
                        persiapan. Aplikasi ini menyatukannya, lalu mencocokkannya dengan pengalaman dan rencana Anda sendiri.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        {auth.user ? (
                            <>
                                <Button href={dashboard.url()}>Buka dasbor</Button>
                                <Button variant="secondary" href={trailsIndex.url()}>Telusuri jalur</Button>
                            </>
                        ) : (
                            <>
                                <Button href={register.url()}>Mulai, buat profil pendakian</Button>
                                <Button variant="secondary" href={login.url()}>Masuk</Button>
                            </>
                        )}
                    </div>
                    <p className="mt-4 text-sm text-secondary">Membuat profil memakan waktu beberapa menit dan menentukan seluruh rekomendasi berikutnya.</p>
                </section>

                <section className="border-t border-subtle bg-surface-sunken">
                    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                        <h2 className="text-xl font-semibold text-primary">Bagaimana cara kerjanya</h2>
                        <ol className="mt-6 grid gap-6 sm:grid-cols-3">
                            {langkah.map(([judul, penjelasan], i) => (
                                <li key={judul}>
                                    <span className="inline-flex size-8 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-white">{i + 1}</span>
                                    <h3 className="mt-3 font-medium text-primary">{judul}</h3>
                                    <p className="mt-1 text-sm text-secondary">{penjelasan}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>

                <section className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                    <h2 className="text-xl font-semibold text-primary">Dari mana datanya</h2>
                    <div className="mt-6 grid gap-6 sm:grid-cols-2">
                        <div>
                            <h3 className="font-medium text-primary">Prakiraan cuaca BMKG</h3>
                            <p className="mt-1 text-sm text-secondary">Prakiraan diambil untuk wilayah administrasi di sekitar jalur, bukan untuk titik puncaknya. Cuaca gunung dapat berbeda jauh dari area di bawahnya.</p>
                        </div>
                        <div>
                            <h3 className="font-medium text-primary">Status resmi jalur</h3>
                            <p className="mt-1 text-sm text-secondary">Status dicatat beserta sumber dan tanggalnya. Selama belum ada keterangan resmi, status ditampilkan sebagai belum diketahui, bukan ditebak sebagai terbuka.</p>
                        </div>
                    </div>
                    <p className="mt-8 rounded-lg border border-warn-300 bg-warn-50 p-4 text-sm text-warn-900">
                        Aplikasi ini membantu Anda memutuskan, tidak menggantikan keputusan Anda. Keterangan dari pengelola jalur dan basecamp tetap yang paling menentukan di lapangan.
                    </p>
                </section>
            </main>

            <footer className="border-t border-subtle">
                <div className="mx-auto flex max-w-5xl flex-wrap items-start justify-between gap-6 px-4 py-6 text-sm text-secondary sm:px-6">
                    <p>Sumber prakiraan cuaca: BMKG.</p>
                    <ThemePicker compact />
                </div>
            </footer>
        </div>
    );
}
