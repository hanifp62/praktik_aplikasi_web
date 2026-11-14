import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PetaGaris } from '@/components/community/PetaGaris';
import { GrafikPendakian } from '@/components/progres/GrafikPendakian';
import { Pencapaian } from '@/components/progres/Pencapaian';
import { RingkasanTahun } from '@/components/progres/RingkasanTahun';
import { RiwayatProgres } from '@/components/progres/RiwayatProgres';
import { TargetPribadi } from '@/components/progres/TargetPribadi';
import type { ButirPencapaian, ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan, TargetPribadiData } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
import type { Penanda } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { history, progress } from '@/routes';
import { create as reportsCreate } from '@/routes/reports';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kosong: boolean;
    tahun: { dipilih: number; semua: number[] };
    ringkasan: Ringkasan | null;
    grafik: GrafikPendakianData | null;
    /** Target pribadi bulan berjalan; null di tahun lain. */
    target: TargetPribadiData | null;
    pencapaian: ButirPencapaian[];
    riwayat: ButirRiwayat[];
    riwayatTerpotong: string | null;
    laporan: { terbit: number; menunggu: number; ditolak: number; terimaKasih: number };
    gunung: Penanda[];
    keteranganPeta: string;
    gunungTanpaKoordinat: string | null;
    formulirCatatan: FormulirCatatanData;
    kabar: KabarProgres | null;
    /** Hasil pencarian jalur katalog, hanya lewat muat ulang parsial; dibaca PemilihJalur (useCariJalur). */
    pilihanJalur?: JalurKatalog[];
}

/**
 * Progres (spec v3 bagian 6.14, prototype 13): satu tahun dibandingkan dengan diri sendiri
 * (R-015). Urutannya mengikuti pertanyaan "Bagaimana perkembangan saya?": ringkasan tahun,
 * grafik per pendakian, riwayat beserta catatan di luar trip, lalu dampak laporan dan peta
 * gunung yang sudah didaki. Tidak ada papan peringkat, catatan kecepatan, atau teguran.
 */
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, target, pencapaian, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
    const daftarTahun = tahun.semua.length > 1 ? tahun.semua : [];

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Progres" description="Dibandingkan dengan diri Anda sendiri. Tidak ada papan peringkat dan tidak ada catatan kecepatan.">
                    {daftarTahun.length > 0 ? (
                        <nav aria-label="Tahun" className="mt-4">
                            <ul className="flex flex-wrap gap-2">
                                {daftarTahun.map((t) => (
                                    <li key={t}>
                                        <Tautan
                                            href={progress.url({ query: { tahun: t } })}
                                            aria-current={t === tahun.dipilih ? 'page' : undefined}
                                            className={kelas(
                                                'inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border px-4 text-kecil tabular-nums focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600',
                                                t === tahun.dipilih ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary hover:bg-surface-sunken',
                                            )}
                                        >
                                            {t}
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    ) : null}
                </PageHeader>

                {kosong ? (
                    // Keadaan awal setiap pendaki: belum ada yang tercatat berarti belum mulai, bukan nilai rendah.
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada pendakian yang tercatat"
                        description="Halaman ini terisi sendiri setelah Anda menyelesaikan pendakian lewat trip. Pendakian yang sudah pernah Anda lakukan di luar Memount dapat dicatat di bagian riwayat di bawah."
                    >
                        <div className="flex flex-wrap justify-center gap-3">
                            <Button href={trailsIndex.url()}>Telusuri jalur</Button>
                            <Button variant="secondary" href={history.url()}>
                                Buka riwayat pendakian
                            </Button>
                        </div>
                    </EmptyState>
                ) : ringkasan ? (
                    <RingkasanTahun ringkasan={ringkasan} />
                ) : (
                    <p className="text-isi text-secondary">Belum ada pendakian tercatat di {tahun.dipilih}.</p>
                )}

                {grafik ? (
                    <GrafikPendakian grafik={grafik} />
                ) : riwayat.length === 1 ? (
                    <p className="max-w-prose rounded-kartu border border-dashed border-control bg-surface p-4 text-kecil text-secondary">
                        Grafik per pendakian muncul setelah ada dua pendakian di {tahun.dipilih}, karena tiap pendakian dibandingkan dengan pendakian Anda sendiri, bukan dengan pendaki lain.
                    </p>
                ) : null}

                {target ? <TargetPribadi target={target} kabar={kabar} /> : null}

                {kosong ? null : <Pencapaian pencapaian={pencapaian} />}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />

                {/* Dikalimatkan sebagai dampak pada orang lain, bukan sebagai skor. Laporan yang
                    masih menunggu moderasi bukan "belum ada laporan", dan ajakan menulis tidak
                    ditawarkan di cabang itu supaya tidak terkirim laporan ganda (ruling AF-3). */}
                <section aria-labelledby="progres-laporan">
                    <h2 id="progres-laporan" className="text-bagian text-primary">
                        Laporan kondisi
                    </h2>
                    {laporan.terbit === 0 && laporan.menunggu > 0 ? (
                        <p className="mt-2 max-w-prose text-isi text-secondary">
                            <span data-angka>{laporan.menunggu}</span> laporan kondisi Anda sedang menunggu moderasi. Laporan tampil untuk pendaki lain sesudah disetujui.
                        </p>
                    ) : laporan.terbit === 0 && laporan.menunggu === 0 && laporan.ditolak > 0 ? (
                        <>
                            <p className="mt-2 max-w-prose text-isi text-secondary">
                                Belum ada laporan Anda yang terbit. {laporan.ditolak} laporan ditolak atau dihapus moderator. Alasan moderator, bila ada, tercantum di{' '}
                                <Tautan href={history.url()} className="font-bold text-brand-ink underline">
                                    Riwayat pendakian
                                </Tautan>{' '}
                                untuk laporan yang terhubung ke sebuah pendakian.
                            </p>
                            <Tautan href={reportsCreate.url()} className="mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline">
                                Tulis laporan kondisi
                            </Tautan>
                        </>
                    ) : laporan.terbit === 0 ? (
                        <>
                            <p className="mt-2 max-w-prose text-isi text-secondary">
                                Belum ada laporan kondisi dari Anda. Setelah turun, keterangan tentang jalur yang baru Anda lalui adalah hal yang paling dibutuhkan pendaki
                                berikutnya.
                            </p>
                            <Tautan href={reportsCreate.url()} className="mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline">
                                Tulis laporan kondisi
                            </Tautan>
                        </>
                    ) : (
                        <>
                            <p className="mt-2 text-isi text-secondary">
                                <span data-angka className="font-bold text-primary">
                                    {laporan.terbit}
                                </span>{' '}
                                laporan Anda sudah terbit dan dapat dibaca pendaki lain.
                            </p>
                            {laporan.terimaKasih > 0 ? <p className="mt-1 text-isi text-brand-900">{laporan.terimaKasih} pendaki menyatakan laporan Anda menolong mereka.</p> : null}
                        </>
                    )}
                </section>

                {gunung.length > 0 ? (
                    <section aria-labelledby="progres-peta">
                        <h2 id="progres-peta" className="text-bagian text-primary">
                            Gunung yang sudah Anda daki
                        </h2>
                        <figure className="mt-3">
                            <PetaGaris id="peta-progres" markers={gunung} label="Peta gunung yang sudah Anda daki" height="h-72 sm:h-[26rem]" />
                            <figcaption className="mt-3 text-kecil text-secondary">
                                {keteranganPeta}
                                {gunungTanpaKoordinat ? <span className="text-muted"> {gunungTanpaKoordinat}</span> : null}
                            </figcaption>
                        </figure>
                    </section>
                ) : null}
            </div>
        </div>
    );
}

HikerProgress.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
