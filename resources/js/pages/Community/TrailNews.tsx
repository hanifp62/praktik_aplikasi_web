import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LabelKesegaran } from '@/components/community/LabelKesegaran';
import type { ButirKomunitas, ButirResmi, CuacaGunung } from '@/components/community/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
import { photo } from '@/routes/reports';
import { index as trailsIndex, show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    mengikuti: string | null;
    resmi: ButirResmi[];
    cuaca: CuacaGunung[];
    komunitas: ButirKomunitas[];
}

const tautanBagian =
    'inline-flex min-h-11 items-center rounded-control border border-subtle bg-surface px-3 text-sm text-secondary hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

const kosong = 'mt-3 rounded-lg border border-subtle bg-surface p-4 text-sm text-secondary';

/**
 * Kabar dari gunung yang diikuti, dalam tiga kelompok menurut asalnya (PRD §92):
 * status resmi lebih dulu karena otoritasnya paling tinggi (R-009), lalu prakiraan
 * BMKG, lalu laporan komunitas yang tidak pernah menggantikan status resmi (R-010).
 * Setiap butir membawa sumber dan waktunya sendiri.
 */
export default function TrailNews() {
    const { mengikuti, resmi, cuaca, komunitas } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Kabar Jalur" description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />

                {mengikuti === null ? (
                    // Bukan layar kosong, melainkan ajakan memilih beserta alasannya.
                    <Card title="Belum ada gunung yang Anda ikuti">
                        <p className="text-sm text-secondary">
                            Ikuti gunung dari halaman jalurnya, lalu perubahan status resmi dan laporan kondisi dari kawasan itu muncul di sini. Berguna justru ketika Anda
                            sedang tidak merencanakan apa pun: penutupan jalur diumumkan tanpa aba-aba.
                        </p>
                        <Button variant="secondary" href={trailsIndex.url()} className="mt-3">
                            Telusuri jalur
                        </Button>
                    </Card>
                ) : (
                    <>
                        <div className="space-y-3">
                            <p className="text-sm text-secondary">Mengikuti {mengikuti}.</p>
                            <nav aria-label="Bagian kabar" className="flex flex-wrap gap-2">
                                <a href="#kabar-resmi" className={tautanBagian}>Status resmi ({resmi.length})</a>
                                <a href="#kabar-cuaca" className={tautanBagian}>Prakiraan cuaca</a>
                                <a href="#kabar-komunitas" className={tautanBagian}>Laporan komunitas ({komunitas.length})</a>
                            </nav>
                        </div>

                        <section aria-labelledby="kabar-resmi">
                            <h2 id="kabar-resmi" className="scroll-mt-4 text-lg font-semibold text-primary">Status resmi</h2>
                            <p className="mt-1 max-w-prose text-sm text-secondary">
                                Status dicatat beserta sumber dan tanggalnya. Selama belum ada keterangan resmi, status ditampilkan sebagai belum diketahui, bukan ditebak sebagai terbuka. Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.
                            </p>
                            {resmi.length === 0 ? (
                                <p className={kosong}>
                                    Belum ada kabar dari gunung yang Anda ikuti. Tidak adanya kabar bukan pernyataan bahwa jalurnya terbuka; periksa halaman jalurnya untuk status resmi terkini.
                                </p>
                            ) : (
                                <ul className="mt-3 space-y-3">
                                    {resmi.map((b) => (
                                        <ButirStatus key={b.id} butir={b} />
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section aria-labelledby="kabar-cuaca">
                            <h2 id="kabar-cuaca" className="scroll-mt-4 text-lg font-semibold text-primary">Prakiraan cuaca</h2>
                            <p className="mt-1 max-w-prose text-sm text-secondary">
                                Prakiraan diambil untuk wilayah administrasi di sekitar jalur, bukan untuk titik puncaknya. Cuaca gunung dapat berbeda jauh dari area di bawahnya.
                            </p>
                            <ul className="mt-3 space-y-3">
                                {cuaca.map((g) => (
                                    <li key={g.gunung} className="rounded-lg border border-subtle bg-surface p-4">
                                        <p className="text-sm font-medium text-primary">{g.gunung}</p>
                                        {g.pesan ? (
                                            <p className="mt-1 text-sm text-secondary">{g.pesan}</p>
                                        ) : (
                                            <ul className="mt-2 divide-y divide-subtle">
                                                {g.wilayah.map((w) => (
                                                    <li key={w.kode} className="py-3 first:pt-0 last:pb-0">
                                                        <p className="text-sm text-secondary">
                                                            Wilayah acuan: <span className="text-primary">{w.area}</span>
                                                        </p>
                                                        <p className="text-xs text-muted">Untuk {w.jalur}</p>
                                                        {w.ringkasan ? (
                                                            <p className="mt-2 text-sm text-primary">
                                                                <span className="text-secondary">{w.waktuPrakiraan}:</span> {w.ringkasan}
                                                            </p>
                                                        ) : null}
                                                        {w.pesan ? <p className="mt-2 text-sm text-secondary">{w.pesan}</p> : null}
                                                        <p className="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                                                            <span>
                                                                Sumber: {w.sumber}
                                                                {w.diperbarui ? <> &middot; diperbarui {w.diperbarui}</> : null}
                                                            </span>
                                                            {w.diperbarui ? <LabelKesegaran label={w.kesegaran} lama={w.kesegaranLama} /> : null}
                                                        </p>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <section aria-labelledby="kabar-komunitas">
                            <h2 id="kabar-komunitas" className="scroll-mt-4 text-lg font-semibold text-primary">Laporan komunitas</h2>
                            <p className="mt-1 max-w-prose text-sm text-secondary">
                                Laporan komunitas tidak mengubah status resmi jalur. Yang tampil di sini sudah melewati moderasi.
                            </p>
                            {komunitas.length === 0 ? (
                                <p className={kosong}>Belum ada laporan kondisi terbaru dari komunitas.</p>
                            ) : (
                                <ul className="mt-3 space-y-3">
                                    {komunitas.map((b) => (
                                        <ButirLaporan key={b.id} butir={b} />
                                    ))}
                                </ul>
                            )}
                        </section>
                    </>
                )}
            </div>
        </div>
    );
}

/** Label sumber mengikuti jenis catatannya (R-009): "Resmi" hanya untuk OFFICIAL. */
function ButirStatus({ butir }: { butir: ButirResmi }) {
    return (
        <li className="rounded-lg border border-subtle bg-surface p-4">
            <div className="flex flex-wrap items-center gap-3">
                <LabelSumber jenis={butir.jenisSumber} />
                <StatusAkses nilai={butir.status} label={butir.statusLabel} cakupan={butir.cakupan} />
            </div>
            <p className="mt-2 text-sm font-medium text-primary">{butir.judul}</p>
            {butir.alasan ? <p className="mt-1 text-sm text-secondary">{butir.alasan}</p> : null}
            <p className="mt-2 text-xs text-muted">
                Sumber: {butir.sumber} &middot; <time dateTime={butir.waktuIso}>{butir.waktu}</time>
            </p>
        </li>
    );
}

/** COMMUNITY: masukan pendaki, tidak pernah mengubah status resmi. Foto tidak pernah menjadi hero (R-017). */
function ButirLaporan({ butir }: { butir: ButirKomunitas }) {
    return (
        <li className="rounded-lg border border-subtle bg-surface p-4">
            <span className="inline-flex items-center rounded-control bg-community-50 px-2 py-0.5 text-xs font-medium text-community-900">Laporan komunitas</span>
            <p className="mt-2 text-sm font-medium text-primary">{butir.judul}</p>

            {butir.tag.length > 0 ? (
                <ul aria-label="Kondisi yang dilaporkan" className="mt-2 flex flex-wrap gap-2">
                    {butir.tag.map((t) => (
                        <li key={t} className="rounded-full bg-community-50 px-2.5 py-0.5 text-xs text-community-900">{t}</li>
                    ))}
                </ul>
            ) : null}

            {butir.catatan ? <p className="mt-2 text-sm text-secondary">{butir.catatan}</p> : null}

            {butir.foto ? (
                <a
                    href={photo.url(butir.id)}
                    className="mt-3 inline-block rounded-control focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    <img src={photo.url(butir.id)} alt={`Foto dari laporan kondisi ${butir.judul}`} loading="lazy" className="h-24 w-32 rounded-control border border-subtle object-cover" />
                </a>
            ) : null}

            <p className="mt-2 text-xs text-muted">
                Dilaporkan <span className="font-medium text-secondary">{butir.pelapor}</span> &middot; <time dateTime={butir.waktuIso}>{butir.waktu}</time>
                {butir.terimaKasih > 0 ? <> &middot; {butir.terimaKasih} pendaki terbantu</> : null}
            </p>

            {butir.tanggalPendakian ? (
                <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                    <span>Tanggal pendakian {butir.tanggalPendakian}</span>
                    <LabelKesegaran label={butir.kesegaran} lama={butir.kesegaranLama} />
                </p>
            ) : null}

            {butir.jalurSlug ? (
                <Tautan href={trailShow.url({ trail: butir.jalurSlug })} className="mt-1 inline-flex min-h-11 items-center text-sm text-brand-ink underline">
                    Buka halaman jalur
                </Tautan>
            ) : null}
        </li>
    );
}

TrailNews.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
