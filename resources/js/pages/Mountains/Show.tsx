import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { DaftarJalurGunung } from '@/components/trails/gunung/DaftarJalurGunung';
import { KartuIzin } from '@/components/trails/gunung/KartuIzin';
import { KartuStatusGunung } from '@/components/trails/gunung/KartuStatusGunung';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import type { GunungRingkas, IzinGunung, JalurGunung, LaporanGunung, ProfilGunung, Provenans } from '@/components/trails/gunung/types';
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
import type { StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { follow } from '@/routes/mountains';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    gunung: GunungRingkas;
    mengikuti: boolean;
    bolehDiikuti: boolean;
    status: StatusRingkas;
    jalur: JalurGunung[];
    jumlahMenunggu: number;
    profil: ProfilGunung | null;
    izin: IzinGunung | null;
    kondisi: LaporanGunung[];
    /** Jendela laporan terbaru dalam hari (hiking.conditions.community_recent_days). */
    hariLaporan: number;
    provenans: Provenans;
    kataBelumDinilai: string;
    pengumuman: string;
}

const judulBagian = 'text-bagian text-primary';

/**
 * Halaman gunung (spec v3 bagian 6.3, DK-44, prototype 03). Gunung membuka jalurnya
 * (R-001): keputusan tetap per jalur (R-002), jadi halaman ini menyajikan status tingkat
 * gunung, pilihan jalur dengan label dan status masing-masing, izin, kondisi dari pendaki,
 * dan asal-usul datanya. Tanpa foto berlisensi (DK-37, W8) kepala halamannya visual data:
 * profil setiap jalur pada skala yang sama.
 */
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);

    const ikuti = () => {
        setMemproses(true);
        router.post(follow.url({ mountain: gunung.id }), { ya: !mengikuti }, { preserveScroll: true, onFinish: () => setMemproses(false) });
    };

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6">
                <PageHeader
                    induk={{ href: trailsIndex.url(), label: 'Jelajah' }}
                    title={gunung.nama}
                    aksi={
                        bolehDiikuti ? (
                            <Button variant={mengikuti ? 'primary' : 'secondary'} size="sm" aria-pressed={mengikuti} processing={memproses} onClick={ikuti}>
                                <Icon name="bell" size={16} />
                                {mengikuti ? 'Diikuti' : 'Ikuti kabar'}
                            </Button>
                        ) : undefined
                    }
                >
                    <p className="mt-1 text-kecil text-secondary">{[gunung.provinsi, `waktu dalam ${gunung.zona}`].filter(Boolean).join(', ')}</p>
                </PageHeader>

                {profil ? <VisualProfilGunung profil={profil} /> : null}

                <section aria-labelledby="judul-puncak" className="rounded-lg border border-subtle bg-surface p-5 sm:flex sm:items-center sm:gap-6">
                    <div className="shrink-0">
                        <h2 id="judul-puncak" className="text-meta font-bold text-secondary">
                            Puncak
                        </h2>
                        <p data-angka="" className="text-judul-kartu text-primary">
                            {gunung.puncak ?? 'Belum tercatat'}
                        </p>
                    </div>
                    <p className="mt-3 text-kecil text-secondary sm:mt-0 sm:border-l sm:border-subtle sm:pl-6">
                        Ketinggian hanya keterangan. Berat-ringannya pendakian dinilai per jalur dari jarak, tanjakan, durasi, medan, dan navigasi.
                    </p>
                </section>

                <section aria-labelledby="judul-status" className="space-y-3">
                    <h2 id="judul-status" className={judulBagian}>
                        Status saat ini
                    </h2>
                    <KartuStatusGunung status={status} />
                </section>

                <section aria-labelledby="judul-jalur" className="space-y-3">
                    <div>
                        <h2 id="judul-jalur" className={judulBagian}>
                            Pilih jalur <span data-angka="" className="text-muted">{jalur.length}</span>
                        </h2>
                        <p className="mt-1 text-kecil text-secondary">
                            Keputusan dibuat per jalur. Label menunjukkan kecocokan dasar dengan profil Anda, status tertulis untuk tiap jalur.
                        </p>
                    </div>
                    {jalur.length === 0 ? (
                        <EmptyState
                            jenis="menunggu"
                            title="Belum ada jalur terbit di gunung ini"
                            menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                            description="Jalur tampil di sini setelah sumber, jarak, tanjakan, durasi, pos, dan status resminya tercatat."
                        />
                    ) : (
                        <DaftarJalurGunung jalur={jalur} kataBelumDinilai={kataBelumDinilai} />
                    )}
                    {jumlahMenunggu > 0 && jalur.length > 0 ? (
                        <p className="text-kecil text-secondary">
                            <span data-angka="">{jumlahMenunggu}</span> jalur lain belum terbit. Menunggu kurator Memount.
                        </p>
                    ) : null}
                </section>

                <section aria-labelledby="judul-izin" className="space-y-3">
                    <h2 id="judul-izin" className={judulBagian}>
                        Izin pendakian
                    </h2>
                    {izin ? (
                        <KartuIzin izin={izin} />
                    ) : (
                        <EmptyState
                            jenis="menunggu"
                            title="Aturan izin tingkat gunung belum tercatat"
                            menunggu="Menunggu kurator Memount mencatat aturan pengelola"
                            description="Aturan tiap jalur ada di rinciannya."
                        />
                    )}
                </section>

                <section aria-labelledby="judul-kondisi" className="space-y-3">
                    <h2 id="judul-kondisi" className={judulBagian}>
                        Kondisi dari pendaki
                    </h2>
                    {kondisi.length === 0 ? (
                        <p className="text-kecil text-secondary">Belum ada laporan pendaki yang lolos moderasi dalam {hariLaporan} hari terakhir.</p>
                    ) : (
                        <KondisiPendaki laporan={kondisi} />
                    )}
                    <p className="text-meta text-secondary">Laporan komunitas melengkapi status resmi, tidak menggantikannya.</p>
                </section>

                <footer className="border-t border-subtle pt-4 text-meta text-muted">
                    <p className="wrap-break-word">
                        Data gunung: {provenans.sumber ? `${provenans.sumber.nama ?? 'sumber tanpa nama'}${provenans.sumber.label ? ` (${provenans.sumber.label.toLowerCase()})` : ''}` : 'sumber belum tercatat'}
                        {provenans.diverifikasi ? `, diverifikasi ${provenans.diverifikasi}` : null}.
                    </p>
                    <p className="mt-1">Foto: belum ada foto gunung berlisensi.{profil ? ' Visual di atas digambar dari profil elevasi jalurnya.' : null}</p>
                </footer>

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
