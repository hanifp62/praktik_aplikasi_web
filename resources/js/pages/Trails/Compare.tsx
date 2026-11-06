import { usePage } from '@inertiajs/react';
import { useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { LembarBagikan, type TautanAktif } from '@/components/trails/banding/LembarBagikan';
import { PemilihBanding, type OpsiBanding } from '@/components/trails/banding/PemilihBanding';
import { TabelBanding, type BarisBanding, type KolomJalur } from '@/components/trails/banding/TabelBanding';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex, saved } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    jumlahTersimpan: number;
    pesan: string[];
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null; laporan: boolean | null; izin: boolean | null };
    profil: string | null;
    hariLaporan: number;
    kataBelumDinilai: string;
    tautanAktif: TautanAktif[];
    hariBerlakuTautan: number;
    pengumuman: string;
}

/**
 * Bandingkan (FR-06, DK-05, spec v3 bagian 6.7, prototype 07): dua sampai tiga jalur
 * berdampingan, tanpa pemenang (R-016). Halaman ini hanya membaca. Tanpa ?trails= yang
 * dibandingkan simpanan terbaru yang masih terbit; dengan ?trails= yang dibandingkan pilihan
 * pembaca. Yang tidak ikut dibandingkan disebut lewat `pesan`, tidak dibuang diam-diam.
 *
 * "Jadikan dasar rencana" berbobot setara untuk setiap jalur (DK-31): tombol yang sama rupanya,
 * dalam urutan kolom, jadi halaman tidak menyarankan jalur mana pun. Bagikan (FR-23) membuat
 * tautan hanya-baca untuk jalur yang sedang dibandingkan.
 *
 * Halaman ini milik tab Trip (DK-25, spec v3 bagian 5.3), jadi tombol kembalinya ke Trip dan
 * tidak memindah tab (tinjauan akhir W2, W2-F2). Jalan ke Simpanan, tempat pilihannya diubah,
 * tetap ada lewat "Lihat simpanan" di bawah judul.
 */
export default function Compare() {
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, profil, hariLaporan, kataBelumDinilai, tautanAktif, hariBerlakuTautan, pengumuman } = usePage<Props>().props;
    const [sorotBeda, setSorotBeda] = useState(false);
    const [bagikanTerbuka, setBagikanTerbuka] = useState(false);
    const pemicuBagikan = useRef<HTMLElement | null>(null);

    const bukaBagikan = (e: MouseEvent<HTMLElement>) => {
        pemicuBagikan.current = e.currentTarget;
        setBagikanTerbuka(true);
    };

    return (
        <div className="py-6">
            <div className="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
                    title={jalur.length >= 2 ? `Bandingkan ${jalur.length} jalur` : 'Bandingkan jalur'}
                    description="Perbandingan ini tidak memilih pemenang. Ia menunjukkan bedanya, Anda yang memutuskan."
                    aksi={
                        jalur.length >= 2 || tautanAktif.length > 0 ? (
                            <Button variant="secondary" size="sm" onClick={bukaBagikan}>
                                <Icon name="link-simple" size={20} />
                                Bagikan
                            </Button>
                        ) : undefined
                    }
                >
                    {jumlahTersimpan > 0 ? (
                        <div className="mt-3">
                            <Button variant="ghost" size="sm" className="-ml-4" href={saved.url()}>
                                Lihat simpanan ({jumlahTersimpan})
                            </Button>
                        </div>
                    ) : null}
                </PageHeader>

                {pesan.length > 0 ? (
                    <Alert variant="info">
                        <ul className="space-y-1">
                            {pesan.map((p) => (
                                <li key={p}>{p}</li>
                            ))}
                        </ul>
                    </Alert>
                ) : null}

                {pilihan.opsi.length >= 2 ? <PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} /> : null}

                {jalur.length === 0 ? (
                    pilihan.opsi.length >= 2 ? (
                        <EmptyState jenis="pertama" title="Belum ada jalur yang dipilih" description={`Centang sampai ${pilihan.maks} jalur, lalu tekan Bandingkan.`} />
                    ) : (
                        <EmptyState jenis="pertama" title="Belum ada yang dapat dibandingkan" description="Perbandingan memakai jalur terbit yang Anda simpan. Simpan minimal dua jalur dari Jelajah lebih dulu.">
                            <Button variant="secondary" href={trailsIndex.url()}>
                                Jelajahi jalur
                            </Button>
                        </EmptyState>
                    )
                ) : (
                    <>
                        {jalur.length >= 2 ? <Sakelar judul="Sorot yang berbeda" nyala={sorotBeda} onUbah={setSorotBeda} className="max-w-xs" /> : null}

                        <TabelBanding jalur={jalur} baris={baris} beda={beda} profil={profil} hariLaporan={hariLaporan} sorotBeda={sorotBeda} kataBelumDinilai={kataBelumDinilai} />

                        <p className="text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>

                        <section aria-labelledby="judul-dasar-rencana" className="space-y-3">
                            <h2 id="judul-dasar-rencana" className="text-bagian text-primary">
                                Jadikan dasar rencana
                            </h2>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {jalur.map((j) => (
                                    <Button key={j.id} variant="secondary" href={j.rencanaUrl} className="w-full">
                                        {/* Nama aksesibel berbeda dari tautan judul kolom yang bernama sama, dan
                                            tetap memuat label yang terlihat (T9-M8, WCAG 2.5.3). */}
                                        <span className="sr-only">Jadikan dasar rencana: </span>
                                        {j.name}
                                    </Button>
                                ))}
                            </div>
                            {jalur.every((j) => j.tersimpan) ? (
                                <p className="text-kecil text-secondary">Jalur yang tidak dipilih tetap ada di simpanan, jadi Anda bisa kembali tanpa mencari ulang.</p>
                            ) : null}
                        </section>
                    </>
                )}

                <LembarBagikan
                    terbuka={bagikanTerbuka}
                    onTutup={() => setBagikanTerbuka(false)}
                    pemicu={pemicuBagikan}
                    jalur={jalur.map((j) => ({ id: j.id, name: j.name }))}
                    tautanAktif={tautanAktif}
                    hariBerlaku={hariBerlakuTautan}
                />

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Compare.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
