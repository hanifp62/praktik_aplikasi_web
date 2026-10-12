import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { IdentitasJalur } from '@/components/trails/rinci/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trail: IdentitasJalur;
    menunggu: string[];
    badan: { nama: string; website: string | null } | null;
}

/**
 * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak yang berwenang.
 *
 * Nadanya sengaja bukan permintaan maaf: kekosongan ini tahapan, bukan kerusakan.
 * Menyebut siapa yang ditunggu mengubah layar kosong menjadi keterangan, dan jalan
 * menuju perencanaan ditutup karena datanya belum cukup untuk direncanakan.
 */
export default function Awaiting() {
    const { trail, menunggu, badan } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name}>
                    <p className="mt-1 text-sm text-secondary">{[trail.mountain, trail.province, trail.mdpl].filter(Boolean).join(' · ')}</p>
                </PageHeader>

                <Card title="Data jalur ini belum tersedia">
                    <p className="text-sm text-secondary">
                        Jalur ini sudah dikenali sistem, tetapi keterangannya belum dimasukkan{' '}
                        {badan ? (
                            <>
                                <span className="font-medium text-primary">{badan.nama}</span> atau pemandu bersertifikat yang disahkan untuk kawasan ini.
                            </>
                        ) : (
                            'pihak yang berwenang atas kawasan ini.'
                        )}
                    </p>

                    {menunggu.length > 0 ? (
                        <div className="mt-4">
                            <p className="text-sm font-medium text-primary">Yang belum tersedia:</p>
                            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-secondary">
                                {menunggu.map((butir) => (
                                    <li key={butir}>{butir}</li>
                                ))}
                            </ul>
                        </div>
                    ) : null}

                    {/* Bagian terpenting: pendaki yang sampai ke sini sedang mencari jawaban, dan
                        jawabannya memang ada, hanya bukan di sini. */}
                    <p className="mt-4 rounded-control border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                        Selama keterangannya belum ada, jangan menganggap jalur ini terbuka maupun tertutup. Tanyakan langsung ke{' '}
                        {badan?.nama ?? 'pengelola kawasan'} atau ke basecamp sebelum merencanakan keberangkatan.
                    </p>
                </Card>

                <Card title="Mengapa halaman ini ada">
                    <p className="text-sm text-secondary">
                        Sistem ini menampilkan jalur yang datanya belum lengkap alih-alih menyembunyikannya, supaya terlihat apa yang memang belum
                        diketahui. Jalur akan tampil utuh setelah sumber data, karakteristik, daftar pos, status resmi, dan garis jalurnya di peta
                        dilengkapi.
                    </p>

                    {badan?.website ? (
                        <p className="mt-3 text-sm text-secondary">
                            Kanal resmi pengelola:{' '}
                            <a
                                href={badan.website}
                                rel="noopener noreferrer"
                                target="_blank"
                                className="text-brand-ink underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                {badan.website}
                            </a>
                        </p>
                    ) : null}
                </Card>

                <div className="flex flex-wrap gap-3">
                    <Button variant="secondary" href={trailsIndex.url()}>Telusuri jalur lain</Button>
                </div>
            </div>
        </div>
    );
}

Awaiting.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
