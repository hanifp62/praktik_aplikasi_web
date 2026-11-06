import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { AngkaPendakian } from '@/components/community/AngkaPendakian';
import { BandingDiriSendiri } from '@/components/community/BandingDiriSendiri';
import { LaporanPendakian } from '@/components/community/LaporanPendakian';
import { PetaGaris } from '@/components/community/PetaGaris';
import type { AngkaRingkasan, LaporanRingkasan, TanggaRingkasan } from '@/components/community/ringkasan';
import type { GarisGeoJson } from '@/components/community/types';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import type { PosJalur, ProfilElevasiData } from '@/components/trails/rinci/types';
import type { Penanda } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { hapusJejakPerangkat, hitungJejakPerangkat, PERISTIWA_JEJAK, tandaiJejakDihapus, tungguPengirim, type KabarJejak } from '@/luring/kirimJejak';
import { hasilHapusJejak, kalimatJejakPerangkat, type HasilHapusJejak } from '@/luring/kirimJejakMurni';
import { progress } from '@/routes';
import { destroy } from '@/routes/hike/track';
import { show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    jalur: { nama: string; slug: string; wilayah: string };
    varian: 'selesai' | 'batal';
    tanggal: string;
    hasil: string;
    posTerjauh: string | null;
    sesiDimulai: boolean;
    angka: AngkaRingkasan | null;
    tangga: TanggaRingkasan | null;
    banding: string | null;
    pencapaian: string[];
    tingkat: { kalimat: string; url: string } | null;
    laporan: LaporanRingkasan | null;
    /** Peta jejak: hanya varian selesai dengan paling sedikit dua titik. */
    jejak: { geometri: GarisGeoJson } | null;
    /** Ada paling sedikit satu titik jejak tersimpan (ruling W6-6s); hanya id sesi, tanpa koordinat. */
    jejakTersimpan: { sesi: number } | null;
    /** Sesi mode lapangan pendakian ini, untuk titik yang masih di perangkat (ruling W6-P2h); null bila tidak pernah dimulai. */
    sesiId: number | null;
    /** Perekaman jejak pendaki menyala sekarang; memilih kalimat baris perangkat. */
    rekamJejak: boolean;
    garisJalur: GarisGeoJson | null;
    penandaPos: Penanda[];
    profil: ProfilElevasiData | null;
    pos: PosJalur[];
    catatan: string | null;
    peringatanFoto: string | null;
}

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13, prototype 12). Nadanya mencatat, bukan
 * menilai: pendakian yang dibatalkan karena cuaca adalah keputusan yang benar dan tidak boleh
 * terbaca seperti kegagalan. Dua varian (DK-50): selesai, dengan angka, banding diri sendiri,
 * dan laporan; dibatalkan, pendek, dengan alasannya.
 *
 * Jejak pendaki dan garis jalur resmi tidak pernah tertukar. Jejak yang mirip jalur resmi
 * membuat pendaki mengira aplikasi ini memverifikasi bahwa ia berjalan di jalur yang benar,
 * dan aplikasi ini tidak melakukan itu.
 *
 * Jejak yang tersimpan selalu dapat dihapus dari sini, di kedua varian, mulai dari satu titik
 * (ruling W6-6s): peta hanya untuk varian selesai dengan paling sedikit dua titik, dan tanpa
 * peta tampil kalimat tersimpan beserta tombolnya. Hapus melepas tombolnya sendiri, jadi fokus
 * pindah ke kabar halaman (#kabar-ringkasan, yang membacakan "Jejak pendakian dihapus.") di
 * onFinish; bila gagal, fokus kembali ke tombol yang sudah aktif lagi.
 *
 * Titik yang masih di perangkat (sisipan W6-P2, ruling W6-P2h): rekaman jejak baru dikirim
 * sesudah pendakian berakhir, jadi ringkasan pertama sering dirender sebelum unggahannya selesai.
 * Baris #jejak-perangkat (wilayah status yang selalu terpasang) menyebut titik sesi ini di
 * perangkat, dihitung tanpa memuat jejak.js bila perangkat tidak memegang jejak, dan berubah
 * mengikuti peristiwa pengirim. Sesudah semuanya diterima di halaman ini, "Tampilkan jejak" memuat
 * ulang peta dan angka, hanya di varian selesai, satu-satunya varian yang menggambar jejak (ruling
 * W6-P2p). "Hapus jejak ini" tampil bila perangkat ATAU Memount memegang titik: sesi ditandai
 * dihapus di pengirim, kiriman yang berjalan ditunggu, titik di kedua basis data perangkat dihapus,
 * lalu titik di Memount SELALU dihapus juga (ruling W6-P2o), karena jawaban unggahan yang hilang
 * tidak membuktikan server gagal menyimpan. Tombol yang lepas memindah fokus ke baris perangkat
 * (butir 7).
 */
export default function HikeSummary() {
    const { flash, jalur, varian, tanggal, hasil, posTerjauh, sesiDimulai, angka, tangga, banding, pencapaian, tingkat, laporan, jejak, jejakTersimpan, sesiId, rekamJejak, garisJalur, penandaPos, profil, pos, catatan, peringatanFoto, perangkat, pendakianAktif } =
        usePage<Props>().props;
    const kunci = perangkat?.kunci ?? null;
    const [menghapus, setMenghapus] = useState(false);
    const [memuatUlang, setMemuatUlang] = useState(false);
    const [jumlahPerangkat, setJumlahPerangkat] = useState<number | null>(null);
    const [diterimaDiHalaman, setDiterimaDiHalaman] = useState(false);
    const [dimuatUlang, setDimuatUlang] = useState(false);
    const [hasilHapus, setHasilHapus] = useState<HasilHapusJejak | null>(null);
    const [hapusSesiBerakhir, setHapusSesiBerakhir] = useState(false);
    const [gagalHapus, setGagalHapus] = useState(false);
    const kabarTerakhir = useRef(0);
    const fokusKe = useFokusSesudahRender();

    // Titik sesi ini di perangkat: dihitung sekali saat dibuka, lalu mengikuti kabar pengirim.
    // Hitungan awal yang selesai sesudah kabar pertama sudah basi, jadi tidak dipakai.
    useEffect(() => {
        if (kunci === null || sesiId === null) {
            return;
        }

        let batal = false;
        const versi = kabarTerakhir.current;

        void hitungJejakPerangkat(kunci, sesiId).then((jumlah) => {
            if (!batal && kabarTerakhir.current === versi) {
                setJumlahPerangkat(jumlah);
            }
        });

        const saatKabar = (peristiwa: Event) => {
            const kabar = (peristiwa as CustomEvent<KabarJejak>).detail;

            if (kabar.sesi !== sesiId) {
                return;
            }

            kabarTerakhir.current += 1;
            setJumlahPerangkat(kabar.sisa);

            if (kabar.diterima > 0) {
                setDiterimaDiHalaman(true);
            }
        };

        window.addEventListener(PERISTIWA_JEJAK, saatKabar);

        return () => {
            batal = true;
            window.removeEventListener(PERISTIWA_JEJAK, saatKabar);
        };
    }, [kunci, sesiId]);

    const adaDiPerangkat = (jumlahPerangkat ?? 0) > 0;
    const kalimatPerangkat = kalimatJejakPerangkat({ jumlah: jumlahPerangkat, rekamJejak, pendakianLain: pendakianAktif !== null, diterimaDiHalaman, dimuatUlang, jejakTampil: jejak !== null, hapus: hasilHapus, sesiBerakhir: hapusSesiBerakhir, gagalHapus });

    // Hak menghapus berada di tempat jejaknya disebut; rute dan policy-nya milik HikeTrackController.
    // Kalimat konfirmasinya benar di kedua varian: hapus hanya membuang titik jejak, sedangkan trip
    // dan sesi pendakiannya, juga yang dibatalkan, tetap tersimpan. Titik di perangkat dihapus lebih
    // dulu, sesudah pengirim berhenti mengirim sesi ini, supaya kiriman yang sedang berjalan tidak
    // mendarat sesudah titik di Memount dihapus. Bila penghapusan di perangkat ditolak, Memount
    // tidak disentuh dan pendaki dapat mencoba lagi.
    const hapusJejak = async () => {
        const sesiHapus = jejakTersimpan?.sesi ?? sesiId;

        if (sesiHapus === null || !window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')) {
            return;
        }

        setMenghapus(true);
        setGagalHapus(false);
        setHasilHapus(null);
        setHapusSesiBerakhir(false);

        let titikPerangkatDihapus = false;

        if (kunci !== null && sesiId !== null) {
            tandaiJejakDihapus(sesiId);
            await tungguPengirim();

            const sebelum = await hitungJejakPerangkat(kunci, sesiId);

            try {
                await hapusJejakPerangkat(kunci, sesiId);
            } catch {
                kabarTerakhir.current += 1;
                setJumlahPerangkat(await hitungJejakPerangkat(kunci, sesiId));
                setGagalHapus(true);
                setMenghapus(false);
                fokusKe('hapus-jejak-tombol');

                return;
            }

            titikPerangkatDihapus = (sebelum ?? 0) > 0;
            kabarTerakhir.current += 1;
            setJumlahPerangkat(0);
            setDimuatUlang(false);
        }

        // Memount selalu dihapus juga (ruling W6-P2o): kelompok yang jawabannya hilang dapat sudah
        // tersimpan walau halaman ini tidak pernah melihat 2xx, dan hapus di server idempoten. Galat
        // jaringan dan HTTP ditulis di baris perangkat, di dekat tombolnya, bukan di dialog galat
        // Inertia (pola W6-11c); tombol tetap ada untuk mencoba lagi. Status 401 dan 419 dibaca di
        // sini (ruling W6-FR3/N-3) supaya baris perangkat mengajak masuk lagi, bukan menunggu sinyal
        // yang sudah ada; kalimatnya sendiri dipilih kalimatJejakPerangkat lewat sesiBerakhir.
        let berhasil = false;
        let sesiBerakhir = false;
        router.delete(destroy.url(sesiHapus), {
            preserveScroll: true,
            onSuccess: () => {
                berhasil = true;
                setDiterimaDiHalaman(false);
            },
            onNetworkError: () => false,
            onHttpException: (gagal) => {
                sesiBerakhir = gagal.status === 401 || gagal.status === 419;

                return false;
            },
            onFinish: () => {
                setMenghapus(false);
                setHapusSesiBerakhir(sesiBerakhir);
                setHasilHapus(hasilHapusJejak(berhasil, titikPerangkatDihapus));
                fokusKe(berhasil ? 'kabar-ringkasan' : 'hapus-jejak-tombol');
            },
        });
    };

    // Peta dan angka dari rekaman yang baru diterima, tanpa memuat ulang seluruh halaman.
    const tampilkanJejak = () => {
        let berhasil = false;
        router.reload({
            only: ['jejak', 'jejakTersimpan', 'angka'],
            onStart: () => setMemuatUlang(true),
            onSuccess: () => {
                berhasil = true;
                setDimuatUlang(true);
            },
            onFinish: () => {
                setMemuatUlang(false);
                fokusKe(berhasil ? 'jejak-perangkat' : 'tampilkan-jejak-tombol');
            },
        });
    };

    const tombolHapusJejak = (
        <Button id="hapus-jejak-tombol" variant="secondary" size="sm" onClick={() => void hapusJejak()} processing={menghapus}>
            Hapus jejak ini
        </Button>
    );
    // Tombol hapus di baris perangkat hanya bila server tidak menyebut titik saat render: bila
    // menyebutnya, tombolnya sudah ada di kartu peta atau di kalimat jejak tersimpan. Hapus di Memount
    // yang belum pasti juga menyisakan tombol untuk mencoba lagi (ruling W6-P2o).
    const hapusBelumPasti = hasilHapus === 'gagal-sesudah-perangkat' || hasilHapus === 'gagal-tanpa-perangkat';
    const hapusDiBarisPerangkat = jejakTersimpan === null && (adaDiPerangkat || diterimaDiHalaman || gagalHapus || hapusBelumPasti);
    // Hanya varian selesai yang menggambar jejak (ruling W6-P2p). Peta yang sudah tampil saat render
    // (sebagian jejak) juga basi sesudah sisanya diterima di sini.
    const tawarkanTampilkan = varian === 'selesai' && diterimaDiHalaman && jumlahPerangkat === 0 && !dimuatUlang;

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title={jalur.nama} description={jalur.wilayah || undefined}>
                    <p className="mt-1 text-kecil text-secondary">{tanggal}</p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Lencana>{hasil}</Lencana>
                        {posTerjauh ? <span className="text-kecil text-secondary">Pos terjauh: {posTerjauh}</span> : null}
                    </div>
                    {/* Kabar dan peringatan dibacakan lewat wilayah yang terpasang sejak halaman dimuat
                        (DESIGN.md, umpan balik aksi); hanya isi dan bingkainya yang muncul. */}
                    {/* id dan tabIndex: sasaran fokus tetap sesudah aksi yang melepas tombolnya sendiri
                        (hapus jejak, ruling W6-6s), pola #kabar-trip di ruang kerja trip. */}
                    <p id="kabar-ringkasan" role="status" tabIndex={-1} className={kelas(flash.status ? 'mt-4 rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined, fokusTerlihat)}>
                        {flash.status ?? ''}
                    </p>
                    <p role="alert" className={peringatanFoto ? 'mt-4 rounded-kartu border border-warn-300 bg-warn-100 p-4 text-sm font-bold text-warn-900' : undefined}>
                        {peringatanFoto ?? ''}
                    </p>
                </PageHeader>

                {/* Ruling W6-6f (M-1): kalimat bercabang menurut apakah sesi pendakian pernah
                    dimulai. TripLifecycle::cancel() membatalkan trip yang sedang berjalan tanpa
                    menutup sesinya, jadi "sebelum pendakian dimulai" salah untuk trip itu. */}
                {varian === 'batal' ? (
                    <p className="max-w-prose text-isi text-primary">
                        {sesiDimulai
                            ? 'Pendakian ini dihentikan sesudah dimulai lalu dibatalkan, jadi jarak, waktu, dan pos yang dicapai tidak dihitung.'
                            : 'Trip ini dibatalkan sebelum pendakian dimulai.'}
                    </p>
                ) : null}

                {jejak ? (
                    <Card title="Jejak yang Anda tempuh" subtitle="Rekaman dari perangkat Anda, bukan penilaian apakah Anda berjalan di jalur yang benar.">
                        <PetaGaris id="peta-jejak" geometry={jejak.geometri} markers={penandaPos} label={`Jejak pendakian Anda di ${jalur.nama}`} height="h-80" />
                        <div className="mt-4">{tombolHapusJejak}</div>
                    </Card>
                ) : jejakTersimpan ? (
                    <div className="space-y-3">
                        <p className="max-w-prose text-isi text-primary">Jejak yang terekam selama pendakian ini masih tersimpan di Memount. Anda dapat menghapusnya kapan saja.</p>
                        {tombolHapusJejak}
                    </div>
                ) : null}

                <div className={kalimatPerangkat || hapusDiBarisPerangkat || tawarkanTampilkan ? 'space-y-3' : undefined}>
                    <p id="jejak-perangkat" role="status" tabIndex={-1} className={kelas(kalimatPerangkat ? 'max-w-prose text-isi text-primary' : undefined, fokusTerlihat)}>
                        {kalimatPerangkat}
                    </p>
                    {hapusDiBarisPerangkat || tawarkanTampilkan ? (
                        <div className="flex flex-wrap gap-3">
                            {tawarkanTampilkan ? (
                                <Button id="tampilkan-jejak-tombol" size="sm" onClick={tampilkanJejak} processing={memuatUlang}>
                                    Tampilkan jejak
                                </Button>
                            ) : null}
                            {hapusDiBarisPerangkat ? tombolHapusJejak : null}
                        </div>
                    ) : null}
                </div>

                {angka ? <AngkaPendakian angka={angka} /> : null}

                {varian === 'selesai' && tingkat ? <BandingDiriSendiri tangga={tangga} banding={banding} pencapaian={pencapaian} tingkat={tingkat} /> : null}

                {laporan ? <LaporanPendakian laporan={laporan} /> : null}

                {catatan ? (
                    <section aria-labelledby="judul-catatan">
                        <h2 id="judul-catatan" className="text-bagian text-primary">
                            Catatan Anda
                        </h2>
                        <p className="mt-1 max-w-prose text-isi text-secondary">{catatan}</p>
                    </section>
                ) : null}

                {varian === 'selesai' ? (
                    <>
                        {garisJalur ? (
                            <Card title="Jalur yang ditempuh" subtitle="Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.">
                                <PetaGaris id="peta-hasil" geometry={garisJalur} markers={penandaPos} label={`Peta jalur ${jalur.nama}`} />
                            </Card>
                        ) : penandaPos.length > 0 ? (
                            <Card title="Peta pos jalur ini">
                                <PetaGaris id="peta-hasil" markers={penandaPos} label={`Peta pos jalur ${jalur.nama}`} />
                            </Card>
                        ) : null}

                        {profil ? (
                            <Card title="Profil elevasi">
                                <ProfilElevasi profil={profil} />
                            </Card>
                        ) : null}

                        {pos.length > 0 ? (
                            <Card title="Daftar pos">
                                <DaftarPos pos={pos} />
                            </Card>
                        ) : null}
                    </>
                ) : null}

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Button href={progress.url()} className="w-full sm:w-auto">
                        Lihat progres
                    </Button>
                    <Button variant="secondary" href={trailShow.url({ trail: jalur.slug })} className="w-full sm:w-auto">
                        Buka halaman jalur
                    </Button>
                </div>
            </div>
        </div>
    );
}

HikeSummary.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
