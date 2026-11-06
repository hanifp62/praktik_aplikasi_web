import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { MulaiPendakian } from '@/components/mendaki/MulaiPendakian';
import { BatalkanTrip } from '@/components/trips/BatalkanTrip';
import { FitTrip } from '@/components/trips/FitTrip';
import { FormulirSelesai, type SelesaiTertahan } from '@/components/trips/FormulirSelesai';
import { PintuPaket } from '@/components/mendaki/PintuPaket';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { KeselamatanTrip, type KeselamatanHalaman } from '@/components/trips/KeselamatanTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import { HapusRencana, type KelolaRencana } from '@/components/trips/rencana/HapusRencana';
import type { FitRingkas, KepalaTripData, Langkah, Opsi } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { cancel, complete, hike, index as tripsIndex, start } from '@/routes/trips';
import { arrive, pause, resume } from '@/routes/trips/hike';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    trip: {
        id: number;
        aktif: boolean;
        berlangsung: boolean;
        catatan: string | null;
        /** Hari ini di zona gunung >= tanggal rencana (ruling W4-G1): hanya di sinilah
         *  "Mulai pendakian" memakai variant primary; selain itu secondary. */
        hariBerangkat: boolean;
        /** Tanggal mulai rencana, misalnya "14 Nov 2026", untuk peringatan konfirmasi mulai (ruling CF-3). */
        tanggalRencana: string;
    };
    langkah: Langkah[];
    fit: FitRingkas | null;
    penilaian: string | null;
    izin: { ditutup: boolean; pesan: string; tautanPemesanan: string | null } | null;
    hasilPendakian: { url: string; label: string } | null;
    /** Ringkasan trip yang dibatalkan sesudah pendakiannya dimulai (ruling W6-6s (b)); null selain itu. */
    ringkasanBatal: { url: string; label: string } | null;
    completionStates: Opsi[];
    alasanBatal: Opsi[];
    penolakan: string | null;
    pengingat: PengingatHalaman;
    /** Rencana untuk kontak dan versi cetak (ruling W6-R4); null selama menunggu Q14. */
    keselamatan: KeselamatanHalaman | null;
    kelola: KelolaRencana;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Ruang kerja satu trip (spec v3 bagian 6.9): empat langkah prototype di kolom kiri mulai
 * 1024 px dan di atas isi di ponsel, lalu kecocokan untuk rencana ini, izin, pengingat, dan
 * aksi yang mengubah status trip. Langkah memandu, tidak mengunci.
 */
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, keselamatan, kelola, flash, perangkat } = usePage<Props>().props;
    const fokusKe = useFokusSesudahRender();
    // Dibaca terpisah dari destrukturisasi di atas, yang menjadi jangkar Tugas 10.
    const { ringkasanBatal } = usePage<Props>().props;

    /**
     * Selesai di ruang kerja menunggu aksi lapangan trip ini yang masih di perangkat ini (ruling
     * W6-FC): antrean dibaca tanpa membuat basis data dan tanpa mengirim apa pun; mode lapangan,
     * yang memutar antrean, ditautkan. Tanpa kunci perangkat, atau bila modul atau penyimpanannya
     * tidak dapat dibaca, Selesai dikirim seperti sebelum ruling ini. Modul antrean dan simpan
     * dimuat saat Simpan ditekan, bukan bersama halaman.
     */
    const tahanSelesai = async (): Promise<SelesaiTertahan | null> => {
        const kunci = perangkat?.kunci;

        if (!kunci) {
            return null;
        }

        const [antrean, simpan] = await Promise.all([import('@/components/mendaki/antrean'), import('@/luring/simpan')]);

        return antrean.selesaiTertahan(
            await antrean.aksiTersimpanDiPerangkat(kunci, simpan.namaBasisData(kunci)),
            { tiba: arrive.url(trip.id), jeda: pause.url(trip.id), lanjut: resume.url(trip.id) },
            'ruang-kerja',
            false,
            { href: hike.url(trip.id), label: 'Buka mode lapangan' },
        );
    };

    /**
     * Pemantau kalimat Selesai tertahan di ruang kerja (ruling AF-8). Tab ini tidak memutar antrean,
     * jadi antreannya hanya berubah oleh mode lapangan di tab lain; keputusan dibaca ulang lewat
     * tahanSelesai (baca saja, tanpa mengirim) setiap kali tab ini kembali terlihat atau difokus.
     */
    const pantauTahanSelesai = (perbarui: (hasil: SelesaiTertahan | null) => void) => {
        const periksa = () => {
            if (document.visibilityState === 'visible') {
                void tahanSelesai().then(perbarui, () => undefined);
            }
        };

        window.addEventListener('focus', periksa);
        document.addEventListener('visibilitychange', periksa);

        return () => {
            window.removeEventListener('focus', periksa);
            document.removeEventListener('visibilitychange', periksa);
        };
    };

    /**
     * Fokus sesudah batal berhasil atau ditolak (ruling W6-5o putaran perbaikan 3): Show
     * memegang `useFokusSesudahRender` sendiri dan meneruskan fungsi biasa ini ke BatalkanTrip
     * lewat prop `onHasil`, supaya target fokusnya tetap tercapai walau BatalkanTrip sendiri
     * sudah lepas dari DOM (batal berhasil membuat trip.aktif false, melepas kartu Tindakan
     * SEBELUM callback aksi Inertia berefek). "Berhasil" memakai wilayah kabar yang sudah
     * dipakai sejak putaran 1 (konsisten, bukan judul halaman); "ditolak" memakai wilayah
     * penolakan yang sama dengan FormulirSelesai.
     */
    const kabarBatal = (hasil: 'berhasil' | 'ditolak') => {
        fokusKe(hasil === 'ditolak' ? 'penolakan-trip' : 'kabar-trip');
    };

    return (
        <div className="py-6">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: tripsIndex.url(), label: 'Trip' }} title={kepala.nama}>
                    <KepalaTrip kepala={kepala} />
                </PageHeader>

                {/* M7 tinjauan akhir W4 (konvensi DESIGN.md "Umpan balik aksi di wilayah yang selalu
                    terpasang"): kabar dan penolakan aksi ruang kerja dibacakan lewat dua wilayah yang
                    terpasang sejak halaman dimuat; hanya isi dan bingkainya yang muncul. id dan
                    tabIndex={-1} adalah target fokus sesudah aksi batal atau selesai, bukan Show
                    sendiri lewat efek saat dipasang (preserveState bawaan Inertia membuat Show tidak
                    dipasang ulang untuk aksi di halaman yang sama). FormulirSelesai memindah fokus
                    lewat id di onFinish miliknya sendiri (ruling W6-5i putaran perbaikan 2, masih
                    berlaku: kartunya tidak lepas sesudah penolakan). BatalkanTrip TIDAK lagi memegang
                    fokusnya sendiri (ruling W6-5o putaran perbaikan 3): kabarBatal di atas, milik Show,
                    dipanggil lewat prop onHasil, supaya tetap tercapai walau BatalkanTrip sudah lepas
                    dari DOM (batal berhasil melepas kartu Tindakan sebelum callback Inertia berefek). */}
                <p role="status" className={flash.status ? 'mb-6 rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined} id="kabar-trip" tabIndex={-1}>
                    {flash.status ?? ''}
                </p>
                <p role="alert" className={penolakan ? 'mb-6 rounded-kartu border border-warn-300 bg-warn-100 p-4 text-sm font-bold text-warn-900' : undefined} id="penolakan-trip" tabIndex={-1}>
                    {penolakan ?? ''}
                </p>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
                    <section aria-labelledby="langkah-trip" className="rounded-lg border border-subtle bg-surface p-5">
                        <h2 id="langkah-trip" className="text-bagian text-primary">
                            Langkah
                        </h2>
                        <div className="mt-4">
                            <LangkahTrip langkah={langkah} />
                        </div>
                    </section>

                    <div className="min-w-0 space-y-6">
                        {fit ? (
                            <Card title="Kecocokan jalur">
                                <FitTrip fit={fit} className="mb-6" />
                                {penilaian ? (
                                    <Tautan href={penilaian} className={tautanTeks}>
                                        Lihat penilaian lengkap
                                    </Tautan>
                                ) : null}
                            </Card>
                        ) : null}

                        {/*
                            Jendela pemesanan bergerak relatif terhadap tanggal yang sudah dipilih.
                            Nadanya mengikuti §43: menyebut aturan dan penyelenggaranya, tidak pernah
                            menyatakan izinnya sudah beres atau belum.
                        */}
                        {izin ? (
                            <Card title="Izin pendakian">
                                <p className={izin.ditutup ? 'text-isi font-bold text-warn-900' : 'text-isi text-secondary'}>{izin.pesan}</p>
                                {izin.tautanPemesanan ? (
                                    <a href={izin.tautanPemesanan} rel="noopener noreferrer" target="_blank" className={tautanTeks}>
                                        Pesan di sistem resmi
                                    </a>
                                ) : null}
                            </Card>
                        ) : null}

                        {/* FR-24. Trip yang sudah lewat tanpa pengingat apa pun tidak mendapat kartu:
                            sakelarnya pasti ditolak server dan daftarnya kosong. */}
                        {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                        {keselamatan ? <KeselamatanTrip keselamatan={keselamatan} /> : null}

                        {trip.aktif && perangkat?.paket ? (
                            <Card title="Paket lapangan luring" subtitle="Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.">
                                <PintuPaket tripId={trip.id} />
                            </Card>
                        ) : null}

                        {trip.catatan ? (
                            <Card title="Catatan">
                                <p className="whitespace-pre-line wrap-break-word text-isi text-secondary">{trip.catatan}</p>
                            </Card>
                        ) : null}

                        {/* "Sudah selesai" hanya benar untuk COMPLETED (konvensi W5 M10);
                            "Dibatalkan di jalur" dan "Selesai sebagian" bukan kegagalan yang
                            perlu disamarkan, tetapi juga bukan "selesai" dalam arti itu.
                            Kalimat per keadaan dari label hasil yang sama dengan formulir
                            Selesai (ruling W6-5e/M-3 putaran perbaikan 1). */}
                        {hasilPendakian ? (
                            <Card title={`Pendakian ini tercatat: ${hasilPendakian.label}.`}>
                                <Button href={hasilPendakian.url}>Lihat hasil pendakian</Button>
                            </Card>
                        ) : null}

                        {/* Ruling W6-6s (b): trip batal yang pendakiannya sempat dimulai menautkan
                            ringkasannya (tempat jejak tersimpan dapat dihapus), tanpa menyebutnya
                            selesai dan tanpa "hasil pendakian" yang memang tidak ada. */}
                        {ringkasanBatal ? (
                            <Card title={`Trip ini tercatat: ${ringkasanBatal.label}.`} subtitle="Pendakiannya sempat dimulai sebelum trip dibatalkan.">
                                <Button href={ringkasanBatal.url}>Lihat ringkasan trip</Button>
                            </Card>
                        ) : null}

                        {/* Ruling CF-3 (audit E2E N-4): ruling T1 berlaku juga di sini, jadi memulai lewat
                            konfirmasi sebaris MulaiPendakian yang sama dengan tab Mendaki, termasuk
                            kalimat galat jaringan dan HTTP serta pengembalian fokusnya. Variant primary
                            hanya sejak hari berangkat (ruling W4-G1); sebelum itu peringatannya menyebut
                            tanggal rencana. items-start selama belum dimulai: wilayah status komponen
                            yang selalu terpasang tidak meregangkan tombol Batalkan trip di sebelahnya. */}
                        {trip.aktif ? (
                            <Card title="Tindakan">
                                <div className={kelas('flex flex-wrap gap-3', !trip.berlangsung && 'items-start')}>
                                    {trip.berlangsung ? (
                                        <Button href={hike.url(trip.id)}>Buka mode lapangan</Button>
                                    ) : (
                                        <MulaiPendakian
                                            id={trip.id}
                                            nama={kepala.nama}
                                            gunung={kepala.gunung}
                                            url={start.url(trip.id)}
                                            utama={trip.hariBerangkat}
                                            tanggalRencana={trip.hariBerangkat ? null : trip.tanggalRencana}
                                            rapat
                                        />
                                    )}
                                    <BatalkanTrip tripId={trip.id} url={cancel.url(trip.id)} alasan={alasanBatal} berlangsung={trip.berlangsung} onHasil={kabarBatal} />
                                </div>
                            </Card>
                        ) : null}

                        {/* Server hanya mengizinkan transisi ke selesai dari trip yang
                            berlangsung (TripStatus::allowedTransitions); menampilkan formulir
                            ini untuk trip yang belum dimulai menawarkan aksi yang pasti ditolak,
                            dan pada trip Siap berangkat penolakannya menyebut status yang
                            sengaja disembunyikan kepala trip (R-014). */}
                        {trip.berlangsung ? (
                            <Card title="Selesaikan trip" subtitle="Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.">
                                <FormulirSelesai tripId={trip.id} url={complete.url(trip.id)} hasil={completionStates} alasan={alasanBatal} pantauTahan={pantauTahanSelesai} id="selesai-ruang-kerja" tahan={tahanSelesai} />
                            </Card>
                        ) : null}

                        <HapusRencana tripId={trip.id} kelola={kelola} />
                    </div>
                </div>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
