import { Head, usePage } from '@inertiajs/react';
import { Lockup } from '@/components/brand/Lockup';
import { BarisStatus } from '@/components/trails/BarisStatus';
import type { StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { login, register, welcome } from '@/routes';
import type { PropsBersama } from '@/types';

interface KolomJalur {
    id: number;
    nama: string;
    gunung: string | null;
    provinsi: string | null;
    status: StatusRingkas;
    sumber: string | null;
    /** Tanggal verifikasi sumber data jalur (data_sources.verified_at); null bila belum diverifikasi. */
    diverifikasi: string | null;
}

type Props = PropsBersama &
    (
        | { berlaku: false; hariBerlaku: number }
        | {
              berlaku: true;
              berlakuSampai: string;
              jalur: KolomJalur[];
              baris: Array<{ kunci: string; label: string; nilai: string[]; berbeda: boolean | null }>;
              ditarik: number;
              bandingUrl: string | null;
              tamu: boolean;
          }
    );

/**
 * Halaman publik tautan bagikan perbandingan (FR-23, PRD §148). Dibuka tanpa akun dan hanya
 * membaca: data publik jalur, status resmi dengan sumber dan waktunya, dan sumber data jalur.
 * Tanpa catatan pribadi, tanpa label kecocokan, dan tanpa nama pembuatnya; tanpa pemenang
 * (R-016). Tidak diindeks: server mengirim X-Robots-Tag, dan halaman ini meta robots.
 *
 * Tautan yang tidak berlaku (salah, kedaluwarsa, dicabut) dijawab sama, dengan cara
 * memperoleh tautan baru.
 */
export default function SharedComparison() {
    const props = usePage<Props>().props;

    return (
        <main id="konten" tabIndex={-1} className="min-h-dvh bg-canvas px-4 py-6 sm:px-6">
            <JudulHalaman />
            <Head>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <div className="mx-auto max-w-4xl space-y-6">
                <Lockup href={welcome.url()} />

                {props.berlaku ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="text-balance text-judul text-primary">Perbandingan jalur</h1>
                            <LencanaDataContoh />
                            <p className="text-kecil text-secondary">
                                Dibagikan lewat Memount, hanya-baca, berlaku sampai {props.berlakuSampai}. Label kecocokan tidak ikut karena dihitung dari profil pembuatnya.
                            </p>
                            {props.ditarik > 0 ? <p className="text-kecil text-secondary">{props.ditarik} jalur di tautan ini tidak lagi terbit, jadi tidak ditampilkan.</p> : null}
                        </div>

                        {props.jalur.length === 0 ? (
                            <p className="rounded-lg border-2 border-dashed border-control bg-surface px-4 py-3 text-isi text-secondary">
                                Semua jalur di tautan ini tidak lagi terbit, jadi tidak ada yang dapat ditampilkan. Minta tautan baru dari orang yang membagikannya.
                            </p>
                        ) : (
                            <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
                                <table className="min-w-full text-kecil">
                                    <caption className="sr-only">Perbandingan {props.jalur.length} jalur, tanpa pemenang</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" className="sr-only">
                                                Keterangan
                                            </th>
                                            {props.jalur.map((j) => (
                                                <th key={j.id} scope="col" className="min-w-[10rem] px-3 py-3 text-left align-top">
                                                    <span className="block text-subjudul text-primary">{j.nama}</span>
                                                    <span className="block text-meta font-normal text-secondary">{[j.gunung, j.provinsi].filter(Boolean).join(', ')}</span>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-subtle">
                                        <tr>
                                            <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                Status resmi
                                            </th>
                                            {props.jalur.map((j) => (
                                                <td key={j.id} className="px-3 py-3 align-top">
                                                    <BarisStatus status={j.status} />
                                                </td>
                                            ))}
                                        </tr>
                                        {props.baris.map((b) => (
                                            <tr key={b.kunci}>
                                                <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                    {b.label}
                                                </th>
                                                {b.nilai.map((n, i) => (
                                                    <td key={props.jalur[i]?.id ?? i} data-angka="" className="px-3 py-3 align-top text-primary">
                                                        {n}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                        <tr>
                                            <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                Sumber data jalur
                                            </th>
                                            {props.jalur.map((j) => (
                                                <td key={j.id} className="px-3 py-3 align-top text-secondary">
                                                    {j.sumber ?? 'Belum tercatat'}
                                                    {j.diverifikasi ? `, diverifikasi ${j.diverifikasi}` : null}
                                                </td>
                                            ))}
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <p className="text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>

                        <div className="space-y-2 rounded-lg border border-subtle bg-surface p-4">
                            <p className="text-kecil text-secondary">Cocok atau tidaknya jalur ini bergantung pada pengalaman Anda sendiri. Memount menilainya dengan aturan tertulis, tanpa skor angka.</p>
                            {/* "Masuk" menuju perbandingan yang sama (m8): middleware auth menyimpan
                                tujuannya, jadi sesudah masuk pembaca kembali ke sini, bukan ke dasbor. */}
                            <div className="flex flex-wrap gap-2">
                                {!props.tamu && props.bandingUrl ? (
                                    <Button href={props.bandingUrl}>Nilai kecocokannya untuk saya</Button>
                                ) : props.tamu ? (
                                    <>
                                        <Button href={register.url()}>Daftar untuk menilai kecocokannya</Button>
                                        {props.bandingUrl ? (
                                            <Button variant="secondary" href={props.bandingUrl}>
                                                Masuk
                                            </Button>
                                        ) : (
                                            <Button variant="secondary" href={login.url()}>
                                                Masuk
                                            </Button>
                                        )}
                                    </>
                                ) : null}
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="space-y-3">
                        <h1 className="text-balance text-judul text-primary">Tautan ini tidak berlaku lagi</h1>
                        <p className="text-isi text-secondary">Tautan bagikan berlaku {props.hariBerlaku} hari dan dapat dicabut pembuatnya. Minta tautan baru dari orang yang membagikannya.</p>
                        <Button variant="secondary" href={welcome.url()}>
                            Kenali Memount
                        </Button>
                    </div>
                )}
            </div>
        </main>
    );
}
