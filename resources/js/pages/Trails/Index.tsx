import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { KartuBelumDinilai } from '@/components/trails/jelajah/KartuBelumDinilai';
import { KartuJalur } from '@/components/trails/jelajah/KartuJalur';
import { KotakCari } from '@/components/trails/jelajah/KotakCari';
import { jumlahTersembunyi as hitungTersembunyi, kalimatRingkasan, saringanPenyembunyi, type SaringanTersembunyi } from '@/components/trails/jelajah/ringkasan';
import { drafDari, drafKosong, hitungLolos, kueriDari, STATUS_BAWAAN, type AtributJalur, type DrafSaringan, type FilterJelajah } from '@/components/trails/jelajah/saringan';
import { SaringanJelajah, type OpsiSaringan } from '@/components/trails/jelajah/SaringanJelajah';
import { TafsiranKalimat, type Tafsiran } from '@/components/trails/jelajah/TafsiranKalimat';
import { PetaJelajah } from '@/components/trails/PetaJelajah';
import type { FiturPeta, JalurMenunggu, KartuJalur as DataKartu, TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { LembarBawah } from '@/components/ui/Lembar';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Skeleton } from '@/components/ui/Skeleton';
import { fokusTerlihat } from '@/components/ui/gaya';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { TautanSimpanan } from '@/layouts/TautanSimpanan';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    filter: FilterJelajah;
    tafsiran: Tafsiran;
    trails: { data: DataKartu[]; total: number; links: TautanHalaman[] };
    ringkasan: {
        jumlah: number;
        tersembunyi: SaringanTersembunyi[];
        terpotong: number | null;
        urutan: 'kecocokan' | 'nama';
    };
    saringan: OpsiSaringan;
    atribut: AtributJalur[];
    dinilai: boolean;
    /** Ringkasan profil yang menjadi dasar label, misalnya "Pemula, tanjakan tertinggi 700 m". */
    dasarPenilaian: string | null;
    jumlahTerbit: number;
    menunggu: JalurMenunggu[];
    petaSebaran: FiturPeta[];
    kataBelumDinilai: string;
    pengumuman: string;
}

const pilihanTampilan = [
    { nilai: 'daftar' as const, label: 'Daftar', ikon: 'list' as const },
    { nilai: 'peta' as const, label: 'Peta', ikon: 'map-trifold' as const },
];

/** Kalimat ringkasan di atas daftar, tujuan fokus sesudah keadaan kosong lepas (sapuan EF-6). */
const ID_RINGKASAN = 'ringkasan-jelajah';

/**
 * Apakah kueri media cocok, mengikuti perubahan ukuran layar. Dipakai supaya peta Jelajah
 * hanya dipasang saat terlihat (I4 tinjauan akhir W3).
 */
function useCocokMedia(kueri: string): boolean {
    const [cocok, setCocok] = useState(() => typeof window !== 'undefined' && window.matchMedia(kueri).matches);

    useEffect(() => {
        const media = window.matchMedia(kueri);
        const ubah = () => setCocok(media.matches);
        ubah();
        media.addEventListener('change', ubah);

        return () => media.removeEventListener('change', ubah);
    }, [kueri]);

    return cocok;
}

/** Jumlah kelompok saringan yang sedang menyempitkan hasil, untuk tombol Saring. */
function jumlahSaringan(filter: FilterJelajah, teknisAktif: boolean): number {
    return [
        filter.kecocokan !== null,
        filter.jenis !== null,
        teknisAktif,
        [...filter.status].sort().join(',') !== [...STATUS_BAWAAN].sort().join(','),
        filter.naikDari !== null || filter.naikSampai !== null,
    ].filter(Boolean).length;
}

/**
 * Jelajah (spec v3 bagian 6.2, prototype 02 dan 15). Ponsel: pencarian kalimat dengan
 * tafsirannya, tombol Saring yang membuka lembar bawah berhitungan langsung, lalu daftar atau
 * peta. Layar lebar: saringan di panel kiri, daftar di tengah, dan mulai 1280 px peta di
 * kanan. Saringan hidup di URL (dapat dibagikan, tombol kembali bekerja) dan setiap perubahan
 * memuat ulang data lewat Inertia tanpa memuat ulang halaman.
 *
 * Keadaan kosong diperiksa berurutan (W1-F1): belum ada jalur terbit sama sekali menyebut
 * siapa yang ditunggu apa pun saringannya; baru sesudah itu hasil kosong disebut akibat
 * saringan, beserta yang disembunyikannya dan cara melepasnya.
 */
export default function Index() {
    const { filter, tafsiran, trails, ringkasan, saringan, atribut, dinilai, dasarPenilaian, jumlahTerbit, menunggu, petaSebaran, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    // Pemilik toast Urungkan adalah halaman ini, yang bertahan selama saringan dan simpan
    // berjalan dengan preserveState (ruling W2-5i); kartu dan chip hanya meneruskannya.
    const { tampilkanToast } = useKerangka();
    const tafsiranTeknis = tafsiran.chip.some((c) => c.kunci === 'teknis');
    const [cari, setCari] = useState(filter.search);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [sorot, setSorot] = useState<number | null>(null);
    const [lembarTerbuka, setLembarTerbuka] = useState(false);
    const [draf, setDraf] = useState<DrafSaringan>(() => drafDari(filter, saringan.teknisRendah.aktif));
    const [memuat, setMemuat] = useState(false);
    const pemicuLembar = useRef<HTMLElement | null>(null);
    const pertama = useRef(true);
    const tundaNaik = useRef<ReturnType<typeof setTimeout> | null>(null);
    const adaPeta = petaSebaran.length > 0;
    // Peta dipasang hanya saat terlihat: tampilan Peta dipilih, atau mulai 1280 px tempat kolom
    // peta selalu tampil. Di section yang tersembunyi MapLibre dibingkai pada kanvas 0x0 dan
    // dimuat walau Peta tidak pernah dibuka (I4 tinjauan akhir W3, spec v3 bagian 9.4).
    const layarLebar = useCocokMedia('(min-width: 1280px)');
    const pasangPeta = tampilan === 'peta' || layarLebar;
    // Kolom ketiga di layar lebar ada selama ada hasil: peta, atau kalimat menunggu bila belum
    // satu pun jalur di hasil ini bergaris rute (data pemilik hari ini).
    const kolomPeta = trails.data.length > 0;
    const nSaringan = jumlahSaringan(filter, saringan.teknisRendah.aktif);
    const pratinjau = hitungLolos(atribut, draf, dinilai);

    const kunjungi = (kueri: Record<string, string | number>, onSuccess?: () => void) =>
        router.get(trailsIndex.url({ query: kueri }), {}, { preserveState: true, preserveScroll: true, replace: true, onSuccess });

    const terapkan = (d: DrafSaringan, semua = filter.tampilkanSemua, onSuccess?: () => void) => kunjungi(kueriDari(filter, d, tafsiranTeknis, semua), onSuccess);

    // Tombol di keadaan kosong lepas bersama keadaan kosongnya begitu saringan berubah, jadi fokus
    // pindah ke kalimat ringkasan yang baru, hanya bila fokus jatuh ke body atau #konten (sapuan EF-6).
    const fokusRingkasan = () => {
        const aktif = document.activeElement;

        if (aktif === null || aktif === document.body || aktif.id === 'konten') {
            document.getElementById(ID_RINGKASAN)?.focus();
        }
    };

    // Panel layar lebar menerapkan saringan langsung. Isian tanjakan berubah setiap ketukan dan
    // setiap kunjungan menilai ulang sampai 500 kandidat, jadi perubahan tanjakan ditunda 400 ms
    // seperti kotak cari; saringan lain berlaku seketika dan membatalkan tundaan (T4-M4).
    const ubahPanel = (d: DrafSaringan) => {
        const tanjakanBerubah = d.naikDari !== draf.naikDari || d.naikSampai !== draf.naikSampai;
        setDraf(d);

        if (tundaNaik.current !== null) {
            clearTimeout(tundaNaik.current);
            tundaNaik.current = null;
        }

        if (tanjakanBerubah) {
            tundaNaik.current = setTimeout(() => terapkan(d), 400);
        } else {
            terapkan(d);
        }
    };

    useEffect(
        () => () => {
            if (tundaNaik.current !== null) clearTimeout(tundaNaik.current);
        },
        [],
    );

    useEffect(() => setDraf(drafDari(filter, saringan.teknisRendah.aktif)), [filter, saringan.teknisRendah.aktif]);

    useEffect(() => {
        // Hanya kunjungan GET yang tidak async menyalakan skeleton: POST/DELETE dari
        // TombolSimpan (simpan, hapus) memakai preserveState dan tidak boleh melepas
        // kartu beserta dialog, fokus, dan panel Kenapa? di dalamnya (tinjauan Tugas 4
        // temuan 1; ruling W3-4a, pola AppLayout.tsx).
        const mulai = router.on('start', (event) => {
            if (event.detail.visit.method === 'get' && !event.detail.visit.async) {
                setMemuat(true);
            }
        });
        const selesai = router.on('finish', () => setMemuat(false));

        return () => {
            mulai();
            selesai();
        };
    }, []);

    // Kalimat baru membaca tafsirannya sendiri, jadi tafsiran yang dilepas dan tuntutan teknis
    // dari kalimat lama tidak ikut; saringan lain tetap berlaku.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const t = setTimeout(() => kunjungi(kueriDari({ ...filter, search: cari, abaikan: [] }, drafDari(filter, filter.teknisRendah), false)), 400);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cari]);

    const bukaLembar = (e: MouseEvent<HTMLElement>) => {
        pemicuLembar.current = e.currentTarget;
        setDraf(drafDari(filter, saringan.teknisRendah.aktif));
        setLembarTerbuka(true);
    };

    const lihatDiDaftar = (id: number) => {
        setTampilan('daftar');
        setSorot(id);
        requestAnimationFrame(() => document.getElementById(`jalur-${id}`)?.scrollIntoView({ block: 'center' }));
    };

    const olehSaringan = saringanPenyembunyi(ringkasan.tersembunyi);
    const jumlahTersembunyi = hitungTersembunyi(ringkasan.tersembunyi);
    // Ruling CF-1: angka depan sama dengan jumlah kartu yang tampil (trails.total) di setiap keadaan.
    const kalimat = kalimatRingkasan(ringkasan, filter.tampilkanSemua);

    return (
        <div className="py-6">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Jelajah" aksi={<TautanSimpanan className="lg:hidden" />} />

                <div
                    className={kelas(
                        'lg:grid lg:gap-8',
                        adaPeta ? 'lg:grid-cols-[17rem_minmax(0,1fr)]' : 'lg:grid-cols-[17rem_minmax(0,40rem)]',
                        kolomPeta && 'xl:grid-cols-[18.75rem_32.5rem_minmax(0,1fr)]',
                    )}
                >
                    {/* Pada 1024-1279 px dengan Peta dipilih, grid di atas hanya dua kolom, jadi
                        auto-placement CSS Grid menjatuhkan tiga anak (saringan, daftar, peta) ke
                        (baris 1 kolom 1), (1, 2), (2, 1): peta jatuh selebar kolom saringan,
                        di bawahnya, bukan di sampingnya. Saringan dibuat membentang dua baris
                        supaya peta (lg:col-start-2 di bawah) duduk tepat di kolom kedua baris
                        kedua, di samping saringan. Mulai 1280 px grid sudah tiga kolom dalam satu
                        baris, jadi keduanya dikembalikan ke penempatan otomatis (tinjauan Tugas 5
                        temuan 1, ruling W3-5b). */}
                    {/* Panel lengket tidak lebih tinggi dari ruang di antara header dan lapisan bawah
                        (strip pendakian juga tampil mulai 1024 px), jadi isinya bergulir sendiri. 6rem
                        menyisakan juga kepala halaman di atas panel pada posisi gulir awal. */}
                    <aside aria-label="Saringan jalur" className={kelas('hidden lg:block', adaPeta && tampilan === 'peta' && 'lg:row-span-2 xl:row-span-1')}>
                        <div className="space-y-6 lg:sticky lg:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] lg:-m-1 lg:max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-6rem)] lg:overflow-y-auto lg:p-1">
                            <TafsiranKalimat awalanId="panel" tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan, onSuccess) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false), onSuccess)} />
                            <SaringanJelajah opsi={saringan} draf={draf} onUbah={ubahPanel} />
                        </div>
                    </aside>

                    <section aria-label="Daftar jalur" className="min-w-0 space-y-4">
                        <KotakCari nilai={cari} onUbah={setCari} />

                        <div className="lg:hidden">
                            <TafsiranKalimat awalanId="ponsel" tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan, onSuccess) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false), onSuccess)} />
                        </div>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <Button variant="secondary" size="sm" className="lg:hidden" onClick={bukaLembar}>
                                <Icon name="sliders-horizontal" size={20} />
                                <span>
                                    Saring{nSaringan > 0 ? <span data-angka=""> ({nSaringan})</span> : null}
                                </span>
                            </Button>
                            <p
                                id={ID_RINGKASAN}
                                tabIndex={-1}
                                aria-live="polite"
                                className={kelas('order-last min-w-0 basis-full rounded-kecil text-kecil text-secondary sm:order-none sm:flex-1 sm:basis-auto', fokusTerlihat)}
                            >
                                <span data-angka="" className="font-bold text-primary">
                                    {kalimat.angka}
                                </span>
                                {kalimat.sisa}
                            </p>
                            <p className="hidden text-kecil text-secondary sm:block">
                                Urut: <span className="font-bold text-primary">{ringkasan.urutan}</span>
                            </p>
                            {adaPeta ? (
                                <KontrolSegmen nama="tampilan-jelajah" label="Tampilan hasil" pilihan={pilihanTampilan} nilai={tampilan} onUbah={setTampilan} className="ml-auto w-56 xl:hidden" />
                            ) : null}
                        </div>

                        {kolomPeta && !adaPeta ? <p className="text-meta text-secondary xl:hidden">Peta belum tersedia. Menunggu kurator Memount menggambar garis rute jalur di hasil ini.</p> : null}

                        {/* Dasar label dan kalimat R-012 (ruling W3-S5) berada di luar wadah daftar, jadi
                            tetap terbaca saat tampilan Peta dipilih: pratinjau peta juga menulis status. */}
                        {dinilai ? (
                            <p className="text-meta text-secondary">
                                Label menunjukkan kecocokan dasar dengan profil Anda{dasarPenilaian ? ` (${dasarPenilaian})` : ''}.{' '}
                                {/* Klaim tentang kartu: ikut tersembunyi saat tampilan Peta menyembunyikan kartu
                                    di bawah 1280 px (N4 tinjauan akhir W3, ruling W3-F6), dan tidak dirender sama
                                    sekali tanpa satu kartu pun (temuan tangkapan W3 butir 4, ruling W3-G1 butir c). */}
                                {trails.data.length > 0 ? (
                                    <span className={kelas(adaPeta && tampilan === 'peta' && 'hidden xl:inline')}>Profil elevasi memakai skala yang sama di semua kartu.</span>
                                ) : null}
                            </p>
                        ) : (
                            <div className={kelas('rounded-lg border border-subtle bg-surface p-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>
                                <p className="text-kecil font-bold text-primary">Jalur di sini belum dinilai untuk Anda</p>
                                <p className="mt-1 text-kecil text-secondary">Isi pengalaman pendakian Anda, lalu setiap jalur menyebut cocok atau tidaknya beserta alasannya.</p>
                                <Button href={onboarding.url()} size="sm" className="mt-3">
                                    Isi profil pendaki
                                </Button>
                            </div>
                        )}
                        {trails.data.length > 0 ? <p className="text-meta text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p> : null}

                        {/* Tampilan Peta di ponsel menyembunyikan isi daftar, bukan pencarian dan pemilih tampilannya. */}
                        <div className={kelas('space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>

                            {ringkasan.terpotong !== null ? (
                                <Alert variant="info">Menampilkan {ringkasan.terpotong} jalur pertama menurut nama. Persempit dengan kalimat atau wilayah untuk melihat sisanya.</Alert>
                            ) : null}

                            {jumlahTerbit === 0 ? (
                                <EmptyState
                                    jenis="menunggu"
                                    title="Belum ada jalur yang terbit"
                                    menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                                    description="Jalur tampil di sini setelah datanya lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis rute di peta."
                                />
                            ) : trails.data.length === 0 ? (
                                <EmptyState
                                    jenis="tersaring"
                                    title="Tidak ada jalur dengan saringan ini"
                                    description={jumlahTersembunyi > 0 ? `${jumlahTersembunyi} jalur disembunyikan ${olehSaringan}. Lepas salah satu saringan, atau tampilkan juga jalur yang disembunyikan.` : 'Coba kalimat atau kata kunci lain.'}
                                >
                                    {jumlahTersembunyi > 0 ? (
                                        <Button variant="secondary" size="sm" onClick={() => terapkan(draf, true, fokusRingkasan)}>
                                            Tampilkan juga
                                        </Button>
                                    ) : null}
                                    <Button variant="secondary" size="sm" onClick={() => kunjungi({}, fokusRingkasan)}>
                                        Hapus semua saringan
                                    </Button>
                                </EmptyState>
                            ) : memuat ? (
                                <div aria-busy="true" className="space-y-3">
                                    {[0, 1, 2].map((i) => (
                                        <Skeleton key={i} rows={4} className="rounded-lg border border-subtle bg-surface p-5" />
                                    ))}
                                </div>
                            ) : (
                                <ul className="space-y-3">
                                    {trails.data.map((t) => (
                                        <li key={t.id} onMouseEnter={() => setSorot(t.id)} onMouseLeave={() => setSorot((s) => (s === t.id ? null : s))} onFocusCapture={() => setSorot(t.id)}>
                                            <KartuJalur jalur={t} kataBelumDinilai={kataBelumDinilai} disorot={sorot === t.id} tampilkanToast={tampilkanToast} />
                                        </li>
                                    ))}
                                </ul>
                            )}

                            {jumlahTersembunyi > 0 && trails.data.length > 0 ? (
                                <div className="flex flex-wrap items-center gap-2 rounded-lg bg-surface-sunken p-4">
                                    <p className="min-w-0 flex-1 text-kecil text-secondary">
                                        <span data-angka="">{jumlahTersembunyi}</span> jalur{' '}
                                        {filter.tampilkanSemua ? 'tampil karena Anda memilih Tampilkan juga, meski tidak lolos' : 'disembunyikan'} {olehSaringan}.
                                    </p>
                                    <Button variant="secondary" size="sm" onClick={() => terapkan(draf, !filter.tampilkanSemua)}>
                                        {filter.tampilkanSemua ? 'Sembunyikan lagi' : 'Tampilkan juga'}
                                    </Button>
                                </div>
                            ) : null}

                            <Pagination links={trails.links} />

                            {menunggu.length > 0 ? (
                                <section aria-labelledby="judul-menunggu" className="space-y-3 pt-2">
                                    <h2 id="judul-menunggu" className="text-bagian text-primary">
                                        Belum bisa dinilai
                                    </h2>
                                    <p className="text-kecil text-secondary">Jalur berikut sudah dikenali sistem, tetapi datanya belum lengkap. Tidak dihitung dan tidak direkomendasikan.</p>
                                    <KartuBelumDinilai daftar={menunggu} />
                                </section>
                            ) : null}
                        </div>
                    </section>

                    {/* Tinggi peta menyisakan pesan jaringan dan lapisan bawah (bilah tab ditambah strip
                        pendakian), dan di panel lengket layar lebar juga header serta kepala halaman
                        pada posisi gulir awal, supaya tepi peta dan tombolnya tidak tertutup. */}
                    {adaPeta ? (
                        <section
                            aria-label="Peta jalur"
                            className={kelas(
                                tampilan === 'daftar' ? 'hidden xl:block' : 'block',
                                'mt-4 lg:col-start-2 xl:col-start-auto xl:sticky xl:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] xl:mt-0 xl:self-start',
                            )}
                        >
                            {pasangPeta ? (
                                <PetaJelajah
                                    fitur={petaSebaran}
                                    baris={trails.data}
                                    sorot={sorot}
                                    onLihatDiDaftar={lihatDiDaftar}
                                    kataBelumDinilai={kataBelumDinilai}
                                    className="h-[calc(100dvh-var(--nav-bawah,0px)-var(--pesan-jaringan,0px)-18rem)] min-h-[20rem] xl:h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-6rem)]"
                                />
                            ) : null}
                        </section>
                    ) : kolomPeta ? (
                        <section aria-label="Peta jalur" className="hidden xl:sticky xl:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] xl:block xl:self-start">
                            <EmptyState
                                jenis="menunggu"
                                title="Peta menunggu garis rute"
                                menunggu="Menunggu kurator Memount menggambar garis rute jalur di hasil ini"
                                description="Hanya jalur bergeometri yang digambar di peta. Daftar di samping memuat semua jalur hasil pencarian."
                            />
                        </section>
                    ) : null}
                </div>
            </div>

            <LembarBawah
                terbuka={lembarTerbuka}
                onTutup={() => {
                    setLembarTerbuka(false);
                    setDraf(drafDari(filter, saringan.teknisRendah.aktif));
                }}
                judul="Saring jalur"
                pemicu={pemicuLembar}
                aksi={
                    <>
                        <Button variant="ghost" onClick={() => setDraf(drafKosong)}>
                            Hapus semua
                        </Button>
                        <Button
                            disabled={pratinjau === 0}
                            onClick={() => {
                                terapkan(draf);
                                setLembarTerbuka(false);
                            }}
                        >
                            <span>
                                Tampilkan <span data-angka="">{pratinjau}</span> jalur
                            </span>
                        </Button>
                    </>
                }
            >
                <p aria-live="polite" className="sr-only">
                    {pratinjau} jalur cocok dengan saringan ini.
                </p>
                <SaringanJelajah opsi={saringan} draf={draf} onUbah={setDraf} />
            </LembarBawah>

            <p role="status" className="sr-only">
                {pengumuman}
            </p>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
