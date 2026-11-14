import { Link, usePage } from '@inertiajs/react';
import { Lockup } from '@/components/brand/Lockup';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import { dashboard, login, register } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

type ContohJalurPublik = {
    slug: string;
    nama: string;
    gunung: string;
    provinsi: string;
    jarakKm: string | null;
    tanjakanM: string | null;
    durasi: string | null;
    url: string;
};

type WelcomeProps = PropsBersama & {
    contohJalur?: ContohJalurPublik[];
};

const langkah = [
    ['Ceritakan pengalaman Anda', 'Berapa kali mendaki, medan apa saja yang pernah dilalui, seberapa jauh kemampuan navigasi Anda.'],
    ['Lihat kecocokan jalurnya', 'Setiap jalur dinilai terhadap profil dan rencana Anda, lengkap dengan alasannya. Jalur yang tidak cocok tetap ditampilkan beserta sebabnya.'],
    ['Siapkan dan periksa', 'Daftar persiapan menyesuaikan jalur dan jenis trip, lalu diperiksa ulang sebelum hari keberangkatan.'],
] as const;

/**
 * Halaman depan: satu pertanyaan per layar. Apa ini, contoh jalur terbit yang dapat
 * dibaca tanpa masuk, bagaimana cara kerjanya, dan dari mana datanya.
 */
export default function Welcome() {
    const { auth, merek, contohJalur = [] } = usePage<WelcomeProps>().props;

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

                {contohJalur.length > 0 ? (
                    <section className="border-t border-subtle bg-surface">
                        <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                            <div className="max-w-2xl">
                                <h2 className="text-xl font-semibold text-primary">Contoh jalur terbit</h2>
                                <p className="mt-2 text-sm text-secondary">
                                    Ringkasan tetap jalur yang sudah lolos gerbang publikasi. Halaman publik sengaja tidak memuat status resmi
                                    atau prakiraan cuaca supaya salinan mesin pencari tidak menyajikan keadaan lama sebagai keadaan hari ini.
                                </p>
                            </div>
                            <ul className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {contohJalur.map((jalur) => {
                                    const angka = [
                                        jalur.jarakKm,
                                        jalur.tanjakanM ? `tanjakan ${jalur.tanjakanM}` : null,
                                        jalur.durasi,
                                    ].filter((nilai): nilai is string => nilai !== null);

                                    return (
                                        <li key={jalur.slug} className="flex">
                                            <Link
                                                href={jalur.url}
                                                className={kelas(
                                                    'flex min-h-11 w-full flex-col justify-between rounded-kartu border border-subtle bg-canvas p-4 transition-colors hover:border-control',
                                                    fokusTerlihat,
                                                )}
                                            >
                                                <div>
                                                    <p className="text-kecil text-secondary">
                                                        {jalur.gunung}{jalur.provinsi ? `, ${jalur.provinsi}` : ''}
                                                    </p>
                                                    <h3 className="mt-1 text-subjudul font-semibold text-primary">{jalur.nama}</h3>
                                                </div>
                                                <div className="mt-4 border-t border-subtle pt-3">
                                                    {angka.length > 0 ? (
                                                        <p data-angka="" className="text-kecil text-secondary">
                                                            {angka.join(' · ')}
                                                        </p>
                                                    ) : (
                                                        <p className="text-kecil text-muted">Ringkasan angka sedang dilengkapi</p>
                                                    )}
                                                    <p className="mt-2 text-kecil font-semibold text-brand-ink">Baca ringkasan jalur</p>
                                                </div>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    </section>
                ) : null}

                <section className="border-t border-subtle bg-surface-sunken">
                    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                        <h2 className="text-xl font-semibold text-primary">Bagaimana cara kerjanya</h2>
                        <ol className="mt-6 grid gap-6 sm:grid-cols-3">
                            {langkah.map(([judul, penjelasan], i) => (
                                <li key={judul}>
                                    <span className="inline-flex size-8 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-on-brand">{i + 1}</span>
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
