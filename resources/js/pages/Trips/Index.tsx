import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { TautanHalaman } from '@/components/trails/types';
import { KartuTrip } from '@/components/trips/KartuTrip';
import type { BarisTrip, KelompokTrip } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { saved } from '@/routes/trails';
import { create, index as indeksTrip } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    utama: BarisTrip | null;
    /** "Trip berlangsung" atau "Trip berikutnya", menurut kartu utamanya (tinjauan Tugas 2 M9). */
    utamaLabel: string | null;
    kelompok: KelompokTrip[];
    /** `semua` hanya ada bila trip selesai dan batal melebihi yang tampil; `progres` hanya bila ada pendakian selesai. */
    selesai: { jumlah: number; semua: string | null; progres: string | null } | null;
    /** Kalimat batas pengaman indeks, bila trip yang belum selesai terpotong. */
    terpotong: string | null;
    /** Mode "Lihat semua" (?selesai=semua): seluruh trip selesai dan batal, berhalaman. */
    arsip: { data: BarisTrip[]; total: number; links: TautanHalaman[]; kembali: string } | null;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Indeks trip (spec v3 bagian 6.9; tidak ada di prototype): trip terdekat sebagai kartu
 * utama dengan langkah dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan
 * Selesai. Trip selesai dan batal di luar lima terakhir dibuka lewat "Lihat semua" di halaman
 * yang sama (ruling W4-Fpre b); riwayat pendakian ada di Progres. Pendaki tanpa trip mendapat
 * satu aksi utama, bukan halaman kosong.
 */
export default function Index() {
    const { utama, utamaLabel, kelompok, selesai, terpotong, arsip } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    akar
                    title="Trip"
                    aksi={
                        utama !== null || kelompok.length > 0 || arsip !== null ? (
                            <Button href={create.url()} size="sm" variant="secondary">
                                Rencanakan trip
                            </Button>
                        ) : null
                    }
                />

                {arsip ? (
                    <section aria-labelledby="semua-selesai" className="space-y-4">
                        <div>
                            <h2 id="semua-selesai" className="text-bagian text-primary">
                                Selesai dan dibatalkan
                            </h2>
                            <p data-angka className="mt-1 text-kecil text-secondary">
                                {arsip.total} trip, diurutkan dari yang paling akhir berakhir.
                            </p>
                        </div>
                        {arsip.total === 0 ? (
                            <EmptyState
                                jenis="pertama"
                                title="Belum ada trip yang selesai atau dibatalkan"
                                description="Trip yang sudah selesai atau Anda batalkan tercantum di sini, termasuk yang lebih lama dari lima terakhir."
                            />
                        ) : arsip.data.length === 0 ? (
                            // Nomor halaman di luar daftar (tautan lama, atau trip terakhir di halaman itu baru
                            // dihapus): daftarnya ada, hanya halaman ini yang kosong.
                            <div>
                                <p className="text-kecil text-secondary">Tidak ada trip di halaman ini.</p>
                                <Tautan href={indeksTrip.url({ query: { selesai: 'semua' } })} className={tautanTeks}>
                                    Buka halaman pertama
                                </Tautan>
                            </div>
                        ) : (
                            <ul className="space-y-3">
                                {arsip.data.map((t) => (
                                    <li key={t.id}>
                                        <KartuTrip kartu={t} />
                                    </li>
                                ))}
                            </ul>
                        )}
                        <Pagination links={arsip.links} />
                        <Tautan href={arsip.kembali} className={tautanTeks}>
                            Kembali ke semua trip
                        </Tautan>
                    </section>
                ) : utama === null && kelompok.length === 0 ? (
                    <EmptyState jenis="pertama" title="Belum ada trip" description="Mulai dari jalur yang Anda simpan atau dari Jelajah.">
                        <Button href={create.url()}>Rencanakan trip</Button>
                        <Button href={saved.url()} variant="secondary">
                            Lihat simpanan
                        </Button>
                    </EmptyState>
                ) : (
                    <div className="space-y-8">
                        {utama ? (
                            <section aria-label={utamaLabel ?? undefined}>
                                <KartuTrip kartu={utama} utama />
                            </section>
                        ) : null}

                        {kelompok.map((k) => (
                            <section key={k.kunci} aria-labelledby={`kelompok-${k.kunci}`}>
                                <h2 id={`kelompok-${k.kunci}`} className="text-bagian text-primary">
                                    {k.judul}
                                </h2>
                                <ul className="mt-3 space-y-3">
                                    {k.trip.map((t) => (
                                        <li key={t.id}>
                                            <KartuTrip kartu={t} />
                                        </li>
                                    ))}
                                </ul>
                                {k.kunci === 'mendatang' && terpotong ? <p className="mt-3 text-kecil text-secondary">{terpotong}</p> : null}
                                {k.kunci === 'selesai' && selesai ? (
                                    <div className="mt-3 flex flex-wrap gap-x-6">
                                        {selesai.semua ? (
                                            <Tautan href={selesai.semua} className={tautanTeks}>
                                                Lihat semua {selesai.jumlah} trip selesai dan dibatalkan
                                            </Tautan>
                                        ) : null}
                                        {selesai.progres ? (
                                            <Tautan href={selesai.progres} className={tautanTeks}>
                                                Riwayat pendakian di Progres
                                            </Tautan>
                                        ) : null}
                                    </div>
                                ) : null}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
