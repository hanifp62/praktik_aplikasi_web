import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CuacaKartu, PerizinanKartu, StatusResmiKartu, SumberDataKartu } from '@/components/trails/rinci/Kondisi';
import { LaporanKomunitas } from '@/components/trails/rinci/LaporanKomunitas';
import { PetaJalur } from '@/components/trails/rinci/PetaJalur';
import type {
    AngkaBerlabel,
    Cuaca,
    FitRinci,
    IdentitasJalur,
    Komunitas,
    Perizinan,
    PetaJalurData,
    Segmen,
    StatusResmiRinci,
    SumberData,
} from '@/components/trails/rinci/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { CheckpointJourney, type PosPerjalanan } from '@/components/ui/CheckpointJourney';
import { ElevationProfile, type TitikElevasi } from '@/components/ui/ElevationProfile';
import { FactorBars } from '@/components/ui/FactorBars';
import { FitBadge } from '@/components/ui/FitBadge';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { follow } from '@/routes/mountains';
import { create as reportsCreate } from '@/routes/reports';
import { compare, index as trailsIndex, timbang } from '@/routes/trails';
import { create as tripsCreate } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trail: IdentitasJalur & { id: number; description: string | null };
    fit: FitRinci | null;
    angkaKunci: AngkaBerlabel[];
    rincian: AngkaBerlabel[];
    medan: string[];
    profilElevasi: TitikElevasi[] | null;
    petaJalur: PetaJalurData;
    pos: PosPerjalanan[];
    segmen: Segmen[];
    statusResmi: StatusResmiRinci;
    perizinan: Perizinan | null;
    cuaca: Cuaca;
    komunitas: Komunitas;
    sumberData: SumberData;
    mengikutiGunung: boolean;
    ditimbang: boolean;
    jumlahTersimpan: number;
    pengumuman: string;
}

const judulBagian = 'text-base font-semibold text-primary';

/**
 * Rincian satu jalur (FR-07), disusun bertahap supaya tidak menimbun: apa ini dan
 * cocokkah (label beserta sebabnya), angka kunci, bentuk jalurnya, mengapa, lalu kondisi
 * terkini yang masing-masing menyebut sumber dan waktunya (§89, §92). Satu kolom di
 * ponsel; mulai lg, kondisi bersumber berdiri di panel sebelah.
 */
export default function Show() {
    const {
        auth,
        flash,
        trail,
        fit,
        angkaKunci,
        rincian,
        medan,
        profilElevasi,
        petaJalur,
        pos,
        segmen,
        statusResmi,
        perizinan,
        cuaca,
        komunitas,
        sumberData,
        mengikutiGunung,
        ditimbang,
        jumlahTersimpan,
        pengumuman,
    } = usePage<Props>().props;
    const [mengikutiMemproses, setMengikutiMemproses] = useState(false);
    const [menyimpan, setMenyimpan] = useState(false);

    // Gunungnya yang diikuti, bukan jalurnya, dan tombolnya menyebutnya begitu:
    // penutupan hampir selalu diumumkan untuk kawasan.
    const ikuti = () => {
        setMengikutiMemproses(true);
        router.post(follow.url({ mountain: trail.mountainId }), { ya: !mengikutiGunung }, { preserveScroll: true, onFinish: () => setMengikutiMemproses(false) });
    };

    const simpan = () => {
        setMenyimpan(true);
        router.post(timbang.url({ trail: trail.id }), {}, { preserveScroll: true, onFinish: () => setMenyimpan(false) });
    };

    const adaPeta = petaJalur.geometry !== null || petaJalur.penanda.length > 0;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-6xl space-y-10 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name} description={trail.description ?? undefined}>
                    <p className="mt-1 text-sm text-secondary">{[trail.mountain, trail.province, trail.mdpl].filter(Boolean).join(' · ')}</p>

                    {fit ? (
                        <div className="mt-3">
                            <div className="flex flex-wrap items-center gap-3">
                                <FitBadge label={fit.label} text={fit.labelText} />
                                {fit.deskripsi ? <span className="text-sm text-secondary">{fit.deskripsi}</span> : null}
                            </div>
                            {/* Kalimat yang sama dengan FitLine di baris daftar, supaya penilaian dengan
                                dan tanpa rencana tidak terbaca seperti dua mesin yang tidak sepakat. */}
                            <p className="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                                <span>{fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
                                <a
                                    href="#mengapa-demikian"
                                    className="inline-flex min-h-11 items-center rounded-control text-sm text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    Lihat alasannya
                                </a>
                            </p>
                        </div>
                    ) : null}

                    <div className="mt-3">
                        <Button variant="secondary" size="sm" aria-pressed={mengikutiGunung} processing={mengikutiMemproses} onClick={ikuti}>
                            {mengikutiGunung ? `Berhenti mengikuti ${trail.mountain}` : `Ikuti kabar ${trail.mountain}`}
                        </Button>
                    </div>
                </PageHeader>

                <section aria-labelledby="judul-karakteristik">
                    <h2 id="judul-karakteristik" className={judulBagian}>Karakteristik jalur</h2>
                    <p className="mt-1 text-sm text-secondary">Kesulitan dinilai dari beberapa dimensi, bukan hanya ketinggian gunung.</p>

                    <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                        {angkaKunci.map((a) => (
                            <div key={a.label}>
                                <dt className="text-sm text-muted">{a.label}</dt>
                                <dd data-angka className={kelas('mt-0.5', a.ada ? 'text-xl font-semibold text-primary' : 'text-sm text-secondary')}>
                                    {a.nilai}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    <dl className="mt-5 grid grid-cols-2 gap-4 border-t border-subtle pt-4 text-sm sm:grid-cols-3 lg:grid-cols-5">
                        {rincian.map((r) => (
                            <div key={r.label}>
                                <dt className="text-muted">{r.label}</dt>
                                <dd className={r.ada ? 'font-medium text-primary' : 'text-secondary'}>{r.nilai}</dd>
                            </div>
                        ))}
                    </dl>

                    {medan.length > 0 ? (
                        <div className="mt-4">
                            <h3 className="text-sm font-medium text-secondary">Karakter medan</h3>
                            <ul className="mt-2 flex flex-wrap gap-2">
                                {medan.map((m) => (
                                    <li key={m} className="rounded-full bg-surface-sunken px-3 py-1 text-xs text-secondary">{m}</li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </section>

                <div className="grid gap-10 lg:grid-cols-3 lg:items-start lg:gap-8">
                    <div className="space-y-10 lg:col-span-2">
                        <section aria-labelledby="judul-peta" className="space-y-4">
                            <h2 id="judul-peta" className={judulBagian}>Peta jalur</h2>
                            {adaPeta ? (
                                <PetaJalur id="peta-jalur" data={petaJalur} label={`Peta jalur ${trail.name} di ${trail.mountain}`} />
                            ) : (
                                <Card>
                                    <p className="text-sm text-secondary">
                                        Garis jalur ini belum dimasukkan, jadi belum ada yang dapat digambar di peta. Garisnya dimasukkan pengelola kawasan atau
                                        pemandu bersertifikat yang disahkan untuk kawasan ini, biasanya dari rekaman GPS di jalurnya sendiri.
                                    </p>
                                </Card>
                            )}

                            {/* Profil elevasi menjawab yang tidak dijawab satu angka elevation gain: apakah
                                tanjakannya merata atau menumpuk di satu bagian. */}
                            <div>
                                <h3 className="text-sm font-medium text-secondary">Profil elevasi</h3>
                                {profilElevasi ? (
                                    <ElevationProfile profile={profilElevasi} className="mt-2" />
                                ) : (
                                    <p className="mt-1 text-sm text-secondary">Data ketinggian belum tersedia.</p>
                                )}
                            </div>
                        </section>

                        <section id="mengapa-demikian" aria-labelledby="judul-mengapa" className="scroll-mt-6">
                            <h2 id="judul-mengapa" className={judulBagian}>Mengapa demikian</h2>
                            {fit ? (
                                <>
                                    <FactorBars factors={fit.faktor} className="mt-3" />
                                    {/* Acuannya diri sendiri, bukan pendaki lain: konteks tambahan pada
                                        "Mengapa", kecil dan sekunder supaya tidak bersaing dengan labelnya. */}
                                    {fit.tangga ? <p className="mt-3 text-xs text-muted">{fit.tangga}</p> : null}
                                </>
                            ) : auth.user && !auth.user.hasCompletedProfile ? (
                                <div className="mt-2">
                                    <p className="max-w-prose text-sm text-secondary">
                                        Kecocokan jalur ini dengan Anda, beserta alasannya, muncul setelah profil pendakian Anda lengkap.
                                    </p>
                                    <Button href={onboarding.url()} className="mt-3">Isi profil sekarang</Button>
                                </div>
                            ) : null}
                        </section>
                    </div>

                    <div className="space-y-6">
                        <StatusResmiKartu status={statusResmi} />
                        <CuacaKartu cuaca={cuaca} />
                        <LaporanKomunitas komunitas={komunitas} />
                    </div>
                </div>

                <div className="grid gap-10 lg:grid-cols-3 lg:items-start lg:gap-8">
                    <div className="space-y-10 lg:col-span-2">
                        <section aria-labelledby="judul-pos">
                            <h2 id="judul-pos" className={judulBagian}>Pos</h2>
                            <div className="mt-4">
                                {pos.length === 0 ? (
                                    <p className="text-sm text-secondary">Daftar pos jalur ini belum dimasukkan.</p>
                                ) : (
                                    <CheckpointJourney checkpoints={pos} className="space-y-1" />
                                )}
                            </div>
                        </section>

                        {segmen.length > 0 ? (
                            <section aria-labelledby="judul-segmen">
                                <h2 id="judul-segmen" className={judulBagian}>Segmen jalur</h2>
                                <ol className="mt-4 space-y-2 text-sm">
                                    {segmen.map((s) => (
                                        <li key={s.id} className="rounded-control border border-subtle px-3 py-2">
                                            <span className="font-medium text-primary">{s.judul}</span>
                                            <span className="text-secondary"> &middot; {s.ringkas}</span>
                                            {s.description ? <p className="mt-1 text-secondary">{s.description}</p> : null}
                                        </li>
                                    ))}
                                </ol>
                            </section>
                        ) : null}
                    </div>

                    <div className="space-y-6">
                        {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
                        <SumberDataKartu sumber={sumberData} />
                    </div>
                </div>

                <div>
                    <div className="flex flex-wrap gap-3">
                        <Button href={tripsCreate.url({ query: { trail: trail.id } })}>Buat rencana trip</Button>
                        <Button variant="secondary" processing={menyimpan} onClick={simpan}>
                            {ditimbang ? (
                                <>
                                    <span aria-hidden="true">✓</span> Tersimpan
                                </>
                            ) : (
                                'Simpan rute'
                            )}
                        </Button>
                        {ditimbang && jumlahTersimpan >= 2 ? (
                            <Button variant="secondary" href={compare.url()}>Bandingkan {jumlahTersimpan} jalur</Button>
                        ) : null}
                        <Button variant="secondary" href={reportsCreate.url({ query: { trail: trail.id } })}>Laporkan kondisi jalur</Button>
                    </div>
                    {flash.timbanganPenuh ? <Alert variant="warning" className="mt-4">{flash.timbanganPenuh}</Alert> : null}
                </div>

                <p role="status" className="sr-only">{pengumuman}</p>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
