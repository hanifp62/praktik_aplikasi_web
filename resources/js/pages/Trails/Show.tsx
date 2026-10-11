import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { AngkaNaik } from '@/components/trails/rinci/AngkaNaik';
import { CocokkahUntukSaya } from '@/components/trails/rinci/CocokkahUntukSaya';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import { CuacaKartu, PerizinanKartu, StatusResmiKartu } from '@/components/trails/rinci/Kondisi';
import { LaporanKomunitas } from '@/components/trails/rinci/LaporanKomunitas';
import { Persiapan } from '@/components/trails/rinci/Persiapan';
import { PetaJalur } from '@/components/trails/rinci/PetaJalur';
import { SumberHalaman } from '@/components/trails/rinci/SumberHalaman';
import { Tantangan } from '@/components/trails/rinci/Tantangan';
import type {
    AngkaBerlabel,
    BarisSumber,
    BarisTantangan,
    ButirPersiapan,
    Cuaca,
    IdentitasJalur,
    KecocokanRinci,
    Komunitas,
    Perizinan,
    PetaJalurData,
    PosJalur,
    ProfilElevasiData,
    Segmen,
    StatusResmiRinci,
} from '@/components/trails/rinci/types';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { follow } from '@/routes/mountains';
import { create as reportsCreate } from '@/routes/reports';
import { index as trailsIndex } from '@/routes/trails';
import { create as tripsCreate } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trail: IdentitasJalur & { id: number; description: string | null };
    kecocokan: KecocokanRinci | null;
    ujung: { awal: string; akhir: string } | null;
    angkaNaik: AngkaBerlabel[];
    tantangan: BarisTantangan[];
    profilElevasi: ProfilElevasiData | null;
    petaJalur: PetaJalurData;
    pos: PosJalur[];
    waktuDariRekaman: number | null;
    segmen: Segmen[];
    statusResmi: StatusResmiRinci;
    perizinan: Perizinan | null;
    cuaca: Cuaca;
    komunitas: Komunitas;
    persiapan: ButirPersiapan[];
    sumber: BarisSumber[];
    mengikutiGunung: boolean;
    ditimbang: boolean;
    adaCatatan: boolean;
    bandingUrl: string | null;
    pengumuman: string;
}

/**
 * Rincian satu jalur (FR-07, spec v3 bagian 6.4, prototype 04), dalam urutan pengungkapan
 * PRD §89: peta, angka sekali jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi
 * sekarang, laporan pendaki, yang perlu disiapkan, lalu sumber di halaman ini.
 *
 * Halaman tugas: bilah tab disembunyikan dan bar aksi tetap (Simpan, Banding bila ada simpanan
 * lain, Rencanakan trip) di ponsel; mulai 1024 px kecocokan dan aksinya berdiri di kolom kanan
 * yang lengket (spec v3 bagian 5.1 dan 5.2). Toast Urungkan simpan dimiliki halaman ini
 * (ruling W2-5i).
 */
export default function Show() {
    const {
        trail,
        kecocokan,
        ujung,
        angkaNaik,
        tantangan,
        profilElevasi,
        petaJalur,
        pos,
        waktuDariRekaman,
        segmen,
        statusResmi,
        perizinan,
        cuaca,
        komunitas,
        persiapan,
        sumber,
        mengikutiGunung,
        ditimbang,
        adaCatatan,
        bandingUrl,
        pengumuman,
    } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [mengikutiMemproses, setMengikutiMemproses] = useState(false);
    const adaPeta = petaJalur.geometry !== null || petaJalur.penanda.length > 0;
    const jalurSimpan = { id: trail.id, name: trail.name, ditimbang, adaCatatan };

    // Gunungnya yang diikuti, bukan jalurnya, dan tombolnya menyebutnya begitu: penutupan
    // hampir selalu diumumkan untuk kawasan.
    const ikuti = () => {
        setMengikutiMemproses(true);
        router.post(follow.url({ mountain: trail.mountainId }), { ya: !mengikutiGunung }, { preserveScroll: true, onFinish: () => setMengikutiMemproses(false) });
    };

    const rencanaUrl = tripsCreate.url({ query: { trail: trail.id } });

    const aksiLebar = (
        <>
            <TombolSimpan jalur={jalurSimpan} tampilkanToast={tampilkanToast} bentuk="tombol" />
            {bandingUrl ? (
                <Button variant="secondary" href={bandingUrl}>
                    Banding
                </Button>
            ) : null}
            <Button href={rencanaUrl}>Rencanakan trip</Button>
        </>
    );

    return (
        <div className="py-6">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name} description={trail.description ?? undefined}>
                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                        <p className="text-kecil text-secondary">
                            <Tautan href={trail.mountainUrl} className="font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                {trail.mountain}
                            </Tautan>
                            {[trail.mdpl, trail.province].filter(Boolean).map((t) => `, ${t}`).join('')}
                        </p>
                        <Button variant="secondary" size="sm" aria-pressed={mengikutiGunung} processing={mengikutiMemproses} onClick={ikuti}>
                            {mengikutiGunung ? `Berhenti mengikuti ${trail.mountain}` : `Ikuti kabar ${trail.mountain}`}
                        </Button>
                    </div>
                </PageHeader>

                <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8">
                    <div className="min-w-0 space-y-10">
                        <section aria-labelledby="judul-peta" className="space-y-2">
                            <h2 id="judul-peta" className="sr-only">
                                Peta jalur
                            </h2>
                            {adaPeta ? (
                                <PetaJalur id="peta-jalur" data={petaJalur} label={`Peta jalur ${trail.name} di ${trail.mountain}`} />
                            ) : (
                                <p data-kosong="menunggu" className="rounded-lg border-2 border-dashed border-control bg-surface px-4 py-3 text-kecil text-secondary">
                                    <span className="font-bold text-primary">Garis rute belum dimasukkan.</span> Menunggu kurator Memount memasukkannya dari rekaman di jalurnya sendiri;
                                    angka, pos, dan kondisi di bawah tetap dapat dipakai.
                                </p>
                            )}
                        </section>

                        <AngkaNaik angka={angkaNaik} ujung={ujung} />

                        <Tantangan baris={tantangan} segmen={segmen} />

                        <div className="lg:hidden">
                            <CocokkahUntukSaya kecocokan={kecocokan} idJudul="judul-cocok-ponsel" />
                        </div>

                        <section aria-labelledby="judul-profil" className="space-y-3">
                            <h2 id="judul-profil" className="text-bagian text-primary">
                                Profil elevasi
                            </h2>
                            {profilElevasi ? <ProfilElevasi profil={profilElevasi} /> : <p className="text-kecil text-secondary">Data ketinggian belum tersedia. Menunggu kurator Memount melengkapinya.</p>}
                        </section>

                        <section aria-labelledby="judul-pos" className="space-y-3">
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 id="judul-pos" className="text-bagian text-primary">
                                    Pos di jalur ini
                                </h2>
                                {waktuDariRekaman !== null ? <p className="text-meta text-muted">Waktu dari awal: rentang dari rekaman pendaki, paling sedikit {waktuDariRekaman} rekaman per ruas</p> : null}
                            </div>
                            {pos.length === 0 ? <p className="text-kecil text-secondary">Daftar pos jalur ini belum dimasukkan. Menunggu kurator Memount melengkapinya.</p> : <DaftarPos pos={pos} />}
                        </section>

                        <section aria-labelledby="judul-kondisi" className="space-y-4">
                            <h2 id="judul-kondisi" className="text-bagian text-primary">
                                Kondisi sekarang
                            </h2>
                            <StatusResmiKartu status={statusResmi} />
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
                            <CuacaKartu cuaca={cuaca} />
                        </section>

                        <section className="space-y-3">
                            <LaporanKomunitas komunitas={komunitas} />
                            <Tautan
                                href={reportsCreate.url({ query: { trail: trail.id } })}
                                className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Laporkan kondisi setelah mendaki
                            </Tautan>
                        </section>

                        <Persiapan butir={persiapan} />

                        <SumberHalaman sumber={sumber} />
                    </div>

                    {/* Aksi lebih dulu dan kolomnya bergulir sendiri: kolom lengket yang lebih tinggi dari
                        layar tidak boleh menyembunyikan tombolnya. */}
                    <aside aria-label="Kecocokan dan aksi" className="hidden lg:block">
                        <div className="sticky top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-3rem)] space-y-4 overflow-y-auto p-1">
                            <div className="flex flex-wrap gap-2">{aksiLebar}</div>
                            <CocokkahUntukSaya kecocokan={kecocokan} idJudul="judul-cocok-lebar" />
                        </div>
                    </aside>
                </div>

                <div className="lg:hidden">
                    <BarAksi label="Aksi jalur">
                        {/* Satu baris sampai 320 px (tinjauan akhir W2, W2-F5; prototype 04): simpan berupa
                            tombol ikon 44 px, Banding dan Rencanakan trip berukuran kecil, dan Rencanakan trip
                            mengisi sisa baris dengan label yang membungkus di dalam tombolnya sendiri. */}
                        <div className="flex w-full items-center gap-2">
                            <TombolSimpan jalur={jalurSimpan} tampilkanToast={tampilkanToast} bentuk="bar" />
                            {bandingUrl ? (
                                <Button variant="secondary" size="sm" href={bandingUrl} className="shrink-0">
                                    Banding
                                </Button>
                            ) : null}
                            <Button size="sm" href={rencanaUrl} className="min-w-0 flex-1">
                                Rencanakan trip
                            </Button>
                        </div>
                    </BarAksi>
                </div>

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
