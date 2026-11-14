import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { ButirKomunitas, ButirResmi, CuacaGunung } from '@/components/community/types';
import { KALIMAT_TERPISAH, TautanLaporan } from '@/components/trails/KartuGunungApi';
import { nadaKesegaran } from '@/components/trails/Kesegaran';
import { LabelKomunitas, LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import type { GunungApi } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
import { photo } from '@/routes/reports';
import { index as trailsIndex, show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    mengikuti: string | null;
    gunung: Array<{ id: number; nama: string; url: string }>;
    resmi: ButirResmi[];
    gunungApi: Array<GunungApi & { gunung: string }>;
    cuaca: CuacaGunung[];
    komunitas: ButirKomunitas[];
}

const judulBagian = 'scroll-mt-4 text-bagian text-primary';
const pengantar = 'mt-1 max-w-prose text-kecil text-secondary';
const kosong = 'mt-3 max-w-prose text-kecil text-secondary';
const tautanTeks = kelas('inline-flex min-h-11 items-center rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat);

/**
 * Kabar dari gunung yang diikuti (spec v3 bagian 5 dan 7; PRD §92; redesain W8, W5-C9), dalam
 * kelompok menurut asalnya: status resmi lebih dulu karena otoritasnya paling tinggi (R-009),
 * status gunung api PVMBG bila ada (DK-43), prakiraan BMKG, lalu laporan komunitas yang tidak
 * pernah menggantikan status resmi (R-010). Asal setiap butir terbaca dari label dan garisnya:
 * padat merek untuk catatan bersumber resmi, rambut untuk catatan lain, putus-putus untuk
 * komunitas, bukan dari warna saja. Setiap butir membawa sumber dan waktunya sendiri.
 */
export default function TrailNews() {
    const { gunung, resmi, gunungApi, cuaca, komunitas } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Kabar Jalur" description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />

                {gunung.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada gunung yang Anda ikuti"
                        description="Ikuti gunung dari halamannya, lalu perubahan status resmi dan laporan kondisi dari kawasan itu muncul di sini. Berguna justru ketika Anda sedang tidak merencanakan apa pun: penutupan jalur diumumkan tanpa aba-aba."
                    >
                        <Button variant="secondary" href={trailsIndex.url()}>
                            Telusuri jalur
                        </Button>
                    </EmptyState>
                ) : (
                    <>
                        <section aria-labelledby="kabar-diikuti" className="space-y-2">
                            <h2 id="kabar-diikuti" className="text-meta font-bold text-secondary">
                                Gunung yang Anda ikuti
                            </h2>
                            <ul className="flex flex-wrap gap-2">
                                {gunung.map((g) => (
                                    <li key={g.id}>
                                        <Tautan
                                            href={g.url}
                                            className={kelas('inline-flex min-h-11 items-center rounded-full border border-subtle bg-surface px-4 text-kecil font-bold text-primary hover:bg-surface-sunken', fokusTerlihat)}
                                        >
                                            {g.nama}
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                            <nav aria-label="Bagian kabar" className="flex flex-wrap gap-x-4">
                                <a href="#kabar-resmi" className={tautanTeks}>
                                    Status resmi ({resmi.length})
                                </a>
                                {gunungApi.length > 0 ? (
                                    <a href="#kabar-gunung-api" className={tautanTeks}>
                                        Gunung api ({gunungApi.length})
                                    </a>
                                ) : null}
                                <a href="#kabar-cuaca" className={tautanTeks}>
                                    Prakiraan cuaca
                                </a>
                                <a href="#kabar-komunitas" className={tautanTeks}>
                                    Laporan pendaki ({komunitas.length})
                                </a>
                            </nav>
                        </section>

                        <section aria-labelledby="kabar-resmi">
                            <h2 id="kabar-resmi" className={judulBagian}>
                                Status resmi
                            </h2>
                            <p className={pengantar}>
                                Dicatat beserta sumber dan tanggalnya. Selama belum ada keterangan resmi, status ditulis belum diketahui, bukan ditebak terbuka.
                            </p>
                            {resmi.length === 0 ? (
                                <p className={kosong}>Belum ada kabar status dari gunung yang Anda ikuti. Tidak adanya kabar bukan pernyataan bahwa jalurnya terbuka; periksa halaman jalurnya.</p>
                            ) : (
                                <>
                                    <ul className="mt-3 space-y-3">
                                        {resmi.map((b) => (
                                            <ButirStatus key={b.id} butir={b} />
                                        ))}
                                    </ul>
                                    <p className="mt-3 text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                                </>
                            )}
                        </section>

                        {gunungApi.length > 0 ? (
                            <section aria-labelledby="kabar-gunung-api">
                                <h2 id="kabar-gunung-api" className={judulBagian}>
                                    Status gunung api
                                </h2>
                                <p className={pengantar}>{KALIMAT_TERPISAH}</p>
                                <ul className="mt-3 space-y-3">
                                    {gunungApi.map((g) => (
                                        <li key={g.gunung} className="rounded-lg border-[1.5px] border-brand-ink bg-surface p-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <LabelSumber jenis="OFFICIAL" />
                                                <span className="text-meta font-bold text-secondary">{g.gunung}</span>
                                                <Lencana nada={nadaKesegaran(g.kesegaran)}>{g.kesegaran}</Lencana>
                                            </div>
                                            <p className="mt-2 text-kecil font-bold text-primary">{g.label}</p>
                                            <p className="mt-1 break-words text-kecil text-secondary">{g.rekomendasi}</p>
                                            <p className="mt-2 break-words text-meta text-muted">
                                                Sumber: {g.atribusi}, diterbitkan {g.diterbitkan}; dicocokkan kurator {g.diverifikasi}.
                                            </p>
                                            <TautanLaporan href={g.tautan} />
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        <section aria-labelledby="kabar-cuaca">
                            <h2 id="kabar-cuaca" className={judulBagian}>
                                Prakiraan cuaca
                            </h2>
                            <p className={pengantar}>Prakiraan BMKG untuk wilayah administrasi di sekitar jalur, bukan untuk puncaknya. Cuaca gunung dapat berbeda jauh dari area di bawahnya.</p>
                            <ul className="mt-3 space-y-3">
                                {cuaca.map((g) => (
                                    <li key={g.gunung} className="rounded-lg border border-subtle bg-surface p-4">
                                        <p className="text-kecil font-bold text-primary">{g.gunung}</p>
                                        {g.pesan ? (
                                            <p className="mt-1 text-kecil text-secondary">{g.pesan}</p>
                                        ) : (
                                            <ul className="mt-2 divide-y divide-subtle">
                                                {g.wilayah.map((w) => (
                                                    <li key={w.kode} className="py-3 first:pt-0 last:pb-0">
                                                        <p className="text-kecil text-secondary">
                                                            Wilayah acuan <span className="text-primary">{w.area}</span>, untuk {w.jalur}
                                                        </p>
                                                        {w.ringkasan ? (
                                                            <p className="mt-1 text-kecil text-primary">
                                                                <span className="text-secondary">{w.waktuPrakiraan}:</span> {w.ringkasan}
                                                            </p>
                                                        ) : null}
                                                        {w.pesan ? <p className="mt-1 text-kecil text-secondary">{w.pesan}</p> : null}
                                                        <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-muted">
                                                            <span>
                                                                Sumber: {w.sumber}
                                                                {w.diperbarui ? `, diperbarui ${w.diperbarui}` : ''}
                                                            </span>
                                                            {w.diperbarui ? <Lencana nada={w.kesegaranLama ? 'peringatan' : 'merek'}>{w.kesegaran}</Lencana> : null}
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
                            <h2 id="kabar-komunitas" className={judulBagian}>
                                Laporan pendaki
                            </h2>
                            <p className={pengantar}>Laporan komunitas tidak mengubah status resmi jalur. Yang tampil sudah lolos moderasi, dan kesegarannya dihitung dari tanggal pendakian.</p>
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

/** Garis padat merek hanya untuk catatan bersumber OFFICIAL; label sumber mengikuti jenisnya (R-009). */
function ButirStatus({ butir }: { butir: ButirResmi }) {
    return (
        <li className={kelas('rounded-lg bg-surface p-4', butir.jenisSumber === 'OFFICIAL' ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}>
            <div className="flex flex-wrap items-center gap-2">
                <LabelSumber jenis={butir.jenisSumber} />
                <span className="break-words text-meta text-secondary">{butir.sumber}</span>
            </div>
            <p className="mt-2 flex flex-wrap items-center gap-x-2">
                <StatusAkses nilai={butir.status} label={butir.statusLabel} cakupan={butir.cakupan} />
                <span className="break-words text-kecil text-primary">{butir.judul}</span>
            </p>
            {butir.alasan ? <p className="mt-1 break-words text-kecil text-secondary">{butir.alasan}</p> : null}
            <p className="mt-2 text-meta text-muted">
                Dicatat <time dateTime={butir.waktuIso}>{butir.waktu}</time>
            </p>
        </li>
    );
}

/** Masukan pendaki bergaris putus-putus komunitas; tidak pernah mengubah status resmi. Foto tidak pernah menjadi kepala halaman (R-017). */
function ButirLaporan({ butir }: { butir: ButirKomunitas }) {
    return (
        <li className="rounded-lg border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
            <div className="flex flex-wrap items-center gap-2">
                <LabelKomunitas />
                <span className="break-words text-meta font-bold text-community-900">{butir.judul}</span>
                <Lencana nada={butir.kesegaranLama ? 'peringatan' : 'netral'}>{butir.kesegaran}</Lencana>
            </div>
            {butir.tag.length > 0 ? (
                <ul aria-label="Kondisi yang dilaporkan" className="mt-2 flex flex-wrap gap-1.5">
                    {butir.tag.map((t) => (
                        <li key={t}>
                            <Lencana nada="komunitas" bentuk="pil">
                                {t}
                            </Lencana>
                        </li>
                    ))}
                </ul>
            ) : null}
            {butir.catatan ? <p className="mt-2 break-words text-kecil text-primary">{butir.catatan}</p> : null}
            {butir.foto ? (
                <a href={photo.url(butir.id)} className={kelas('mt-3 inline-block rounded-control', fokusTerlihat)}>
                    <img src={photo.url(butir.id)} alt={`Foto dari laporan kondisi ${butir.judul}`} loading="lazy" className="h-24 w-32 rounded-control border border-subtle object-cover" />
                </a>
            ) : null}
            <p className="mt-2 text-meta text-community-900">
                Dilaporkan {butir.pelapor}, <time dateTime={butir.waktuIso}>{butir.waktu}</time>
                {butir.tanggalPendakian ? `, dari pendakian ${butir.tanggalPendakian}` : ''}
                {butir.terimaKasih > 0 ? `. ${butir.terimaKasih} pendaki terbantu.` : '.'}
            </p>
            {butir.jalurSlug ? (
                <Tautan href={trailShow.url({ trail: butir.jalurSlug })} className={tautanTeks}>
                    Buka halaman jalur
                </Tautan>
            ) : null}
        </li>
    );
}

TrailNews.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
