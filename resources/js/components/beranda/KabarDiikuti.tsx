import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { news } from '@/routes';
import type { KabarBerandaData } from './types';

/**
 * Kabar gunung yang diikuti (spec v3 bagian 6.1, prototype 01). Status resmi dan laporan
 * komunitas tinggal di dua kelompok (R-009, R-010): catatan bersumber resmi bergaris padat
 * merek, catatan lain bergaris rambut, dan laporan komunitas bergaris putus-putus dengan kartu
 * yang sama seperti halaman gunung, jadi asalnya terbaca dari label dan garis, bukan warna
 * saja. Kalimat R-012 menyertai status resmi. Prakiraan cuaca dan kabar lengkap ada di halaman
 * Kabar.
 *
 * Tombol "Ikuti {gunung}" hilang begitu gunungnya diikuti, jadi sesudah berhasil fokus pindah ke
 * judul bagian ini, bukan jatuh ke body, dan hasilnya dibacakan wilayah status yang selalu
 * terpasang (ruling W5-FH): rute ikuti kembali tanpa kabar apa pun.
 */
export function KabarDiikuti({ kabar }: { kabar: KabarBerandaData }) {
    const [memproses, setMemproses] = useState(false);
    const [kabarIkuti, setKabarIkuti] = useState('');
    const judul = useRef<HTMLHeadingElement>(null);
    const tujuan = kabar.ikuti;

    const ikuti = (gunung: string, href: string) => {
        router.post(
            href,
            { ya: true },
            {
                preserveScroll: true,
                onStart: () => setMemproses(true),
                onFinish: () => setMemproses(false),
                onSuccess: () => {
                    setKabarIkuti(`Anda mengikuti ${gunung}.`);
                    judul.current?.focus();
                },
            },
        );
    };

    return (
        <section aria-labelledby="kabar-diikuti">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                <h2 id="kabar-diikuti" ref={judul} tabIndex={-1} className={kelas('rounded-kecil text-bagian text-primary', fokusTerlihat)}>
                    {kabar.judul}
                </h2>
                {kabar.diikuti ? (
                    <Tautan
                        href={news.url()}
                        className={kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                    >
                        Semua kabar
                    </Tautan>
                ) : null}
            </div>
            {kabar.mengikuti ? <p className="text-kecil text-secondary">Mengikuti {kabar.mengikuti}.</p> : null}

            {kabar.diikuti ? (
                <div className="mt-3 space-y-3">
                    {kabar.resmi.length === 0 ? (
                        <p className="max-w-prose text-kecil text-secondary">
                            Belum ada kabar status resmi dari gunung yang Anda ikuti. Tidak adanya kabar bukan pernyataan bahwa jalurnya terbuka.
                        </p>
                    ) : (
                        <>
                            <ul className="space-y-3">
                                {kabar.resmi.map((b) => (
                                    <li
                                        key={b.id}
                                        className={kelas('rounded-lg bg-surface p-4', b.jenisSumber === 'OFFICIAL' ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}
                                    >
                                        <div className="flex flex-wrap items-center gap-2">
                                            <LabelSumber jenis={b.jenisSumber} />
                                            <span className="wrap-break-word text-meta text-secondary">{b.sumber}</span>
                                        </div>
                                        <p className="mt-2 flex flex-wrap items-center gap-x-2">
                                            <StatusAkses nilai={b.status} label={b.statusLabel} cakupan={b.cakupan} />
                                            <span className="wrap-break-word text-kecil text-primary">{b.judul}</span>
                                        </p>
                                        {b.alasan ? <p className="mt-1 wrap-break-word text-kecil text-secondary">{b.alasan}</p> : null}
                                        <p className="mt-2 text-meta text-muted">
                                            Dicatat <time dateTime={b.waktuIso}>{b.waktu}</time>
                                        </p>
                                    </li>
                                ))}
                            </ul>
                            <p className="text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                        </>
                    )}

                    {kabar.komunitas.length === 0 ? (
                        <p className="max-w-prose text-kecil text-secondary">Belum ada laporan kondisi terbaru dari komunitas.</p>
                    ) : (
                        <KondisiPendaki laporan={kabar.komunitas} />
                    )}
                </div>
            ) : (
                <p className="mt-2 max-w-prose text-isi text-secondary">
                    Belum ada gunung yang Anda ikuti. Perubahan status resmi dan laporan kondisi dari gunung yang Anda ikuti muncul di sini, juga ketika Anda sedang
                    tidak merencanakan apa pun.
                </p>
            )}

            {tujuan ? (
                <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p className="text-kecil text-secondary">{tujuan.gunung}, tujuan trip Anda, belum Anda ikuti.</p>
                    <Button variant="secondary" processing={memproses} onClick={() => ikuti(tujuan.gunung, tujuan.href)}>
                        Ikuti {tujuan.gunung}
                    </Button>
                </div>
            ) : null}
            <p role="status" className="sr-only">
                {kabarIkuti}
            </p>
        </section>
    );
}
