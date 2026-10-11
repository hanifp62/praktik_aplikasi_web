import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { GrafikTanjakan } from '@/components/community/GrafikTanjakan';
import { PetaGaris } from '@/components/community/PetaGaris';
import type { TitikTanjakan } from '@/components/community/types';
import type { Penanda } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { history } from '@/routes';
import { create as reportsCreate } from '@/routes/reports';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kosong: boolean;
    gunung: Penanda[];
    keteranganPeta: string;
    gunungTanpaKoordinat: string | null;
    angka: {
        pendakian: string;
        sampaiPuncak: string | null;
        gunung: string;
        jalur: string;
        elevasiTotal: string;
        elevasiBelumDiketahui: string | null;
        elevasiTertinggi: string;
    } | null;
    /**
     * `menunggu`: laporan pengguna ini yang masih menunggu moderasi, termasuk FLAGGED
     * (ruling AF-3). `ditolak`: REJECTED dan REMOVED (ruling DF-10); alasannya hanya ada
     * di Riwayat pendakian, tidak di halaman ini.
     */
    laporan: { terbit: number; menunggu: number; ditolak: number; terimaKasih: number };
    grafik: TitikTanjakan[];
}

/**
 * Progres pendaki, dibandingkan dengan dirinya sendiri (R-015). Peta didahulukan karena
 * ia muatan utama halaman ini: di mana saja orang ini pernah berada. Angkanya menjelaskan
 * peta itu dalam satu kelompok, bukan empat klaim setara.
 */
export default function HikerProgress() {
    const { kosong, gunung, keteranganPeta, gunungTanpaKoordinat, angka, laporan, grafik } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Progres" description="Catatan perjalanan Anda sendiri. Tidak dibandingkan dengan siapa pun." />

                {kosong ? (
                    // Keadaan awal setiap pendaki; nol artinya belum mulai, bukan tertinggal.
                    <Card title="Belum ada pendakian yang tercatat">
                        <p className="text-sm text-secondary">Halaman ini terisi sendiri setelah Anda menyelesaikan pendakian pertama dan menandainya selesai di halaman trip.</p>
                        <Button variant="secondary" href={trailsIndex.url()} className="mt-3">
                            Telusuri jalur
                        </Button>
                    </Card>
                ) : (
                    <>
                        {gunung.length > 0 ? (
                            <figure>
                                <PetaGaris id="peta-progres" markers={gunung} label="Peta gunung yang sudah Anda daki" height="h-72 sm:h-[26rem]" />
                                <figcaption className="mt-3 text-sm text-secondary">
                                    {keteranganPeta}
                                    {gunungTanpaKoordinat ? <span className="text-muted"> {gunungTanpaKoordinat}</span> : null}
                                </figcaption>
                            </figure>
                        ) : null}

                        {angka ? (
                            <dl className="grid grid-cols-2 gap-x-8 gap-y-6 border-y border-subtle py-6 sm:grid-cols-4">
                                <div>
                                    <dt className="text-sm text-muted">Pendakian</dt>
                                    <dd className="mt-1">
                                        <span data-angka className="block text-2xl font-semibold text-primary">{angka.pendakian}</span>
                                        {angka.sampaiPuncak ? <span className="mt-1 block text-xs text-secondary">{angka.sampaiPuncak}</span> : null}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-muted">Gunung berbeda</dt>
                                    <dd className="mt-1">
                                        <span data-angka className="block text-2xl font-semibold text-primary">{angka.gunung}</span>
                                        <span className="mt-1 block text-xs text-secondary">{angka.jalur}</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-muted">Total tanjakan</dt>
                                    <dd className="mt-1">
                                        <span data-angka className="block text-2xl font-semibold text-primary">{angka.elevasiTotal}</span>
                                        {angka.elevasiBelumDiketahui ? <span className="mt-1 block text-xs text-warn-900">{angka.elevasiBelumDiketahui}</span> : null}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-muted">Tanjakan terbesar</dt>
                                    <dd data-angka className="mt-1 text-2xl font-semibold text-primary">{angka.elevasiTertinggi}</dd>
                                </div>
                            </dl>
                        ) : null}

                        <GrafikTanjakan titik={grafik} />

                        {/* Dikalimatkan sebagai dampak pada orang lain, bukan sebagai skor. Laporan yang
                            masih menunggu moderasi bukan "belum ada laporan", dan ajakan menulis tidak
                            ditawarkan di cabang itu supaya tidak terkirim laporan ganda (ruling AF-3). */}
                        <section aria-labelledby="progres-laporan">
                            <h2 id="progres-laporan" className="text-base font-semibold text-primary">Laporan kondisi</h2>
                            {laporan.terbit === 0 && laporan.menunggu > 0 ? (
                                <p className="mt-2 text-sm text-secondary">
                                    <span data-angka>{laporan.menunggu}</span> laporan kondisi Anda sedang menunggu moderasi. Laporan tampil untuk pendaki lain sesudah disetujui.
                                </p>
                            ) : laporan.terbit === 0 && laporan.menunggu === 0 && laporan.ditolak > 0 ? (
                                <>
                                    <p className="mt-2 text-sm text-secondary">
                                        Belum ada laporan Anda yang terbit. {laporan.ditolak} laporan ditolak atau dihapus moderator; alasannya tercantum di{' '}
                                        <Tautan href={history.url()} className="font-medium text-brand-ink underline">
                                            Riwayat pendakian
                                        </Tautan>
                                        .
                                    </p>
                                    <Tautan href={reportsCreate.url()} className="mt-1 inline-flex min-h-11 items-center text-sm font-medium text-brand-ink underline">
                                        Tulis laporan kondisi
                                    </Tautan>
                                </>
                            ) : laporan.terbit === 0 ? (
                                <>
                                    <p className="mt-2 text-sm text-secondary">
                                        Belum ada laporan kondisi dari Anda. Setelah turun, keterangan tentang jalur yang baru Anda lalui adalah hal yang paling dibutuhkan pendaki
                                        berikutnya.
                                    </p>
                                    <Tautan href={reportsCreate.url()} className="mt-1 inline-flex min-h-11 items-center text-sm font-medium text-brand-ink underline">
                                        Tulis laporan kondisi
                                    </Tautan>
                                </>
                            ) : (
                                <>
                                    <p className="mt-2 text-sm text-secondary">
                                        <span data-angka className="font-semibold text-primary">{laporan.terbit}</span> laporan Anda sudah terbit dan dapat dibaca pendaki lain.
                                    </p>
                                    {laporan.terimaKasih > 0 ? (
                                        <p className="mt-1 text-sm text-brand-900">{laporan.terimaKasih} pendaki menyatakan laporan Anda menolong mereka.</p>
                                    ) : null}
                                </>
                            )}
                        </section>

                        <p className="text-sm text-secondary">
                            Tiap pendakian punya halaman hasilnya sendiri berisi jalur, durasi, dan catatan Anda.{' '}
                            <Tautan href={history.url()} className="font-medium text-brand-ink underline">
                                Buka riwayat pendakian
                            </Tautan>
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}

HikerProgress.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
