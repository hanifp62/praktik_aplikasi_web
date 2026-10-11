import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahRingkas, type KunciLangkah } from '@/components/trips/LangkahRingkas';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import type { KepalaTripData } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { readiness, show } from '@/routes/trips';
import { regenerate, update } from '@/routes/trips/preparation';
import type { PropsBersama } from '@/types';

type StatusButir = 'CONFIRMED' | 'NOT_CONFIRMED' | 'NOT_APPLICABLE';

interface Butir {
    id: number;
    label: string;
    keterangan: string | null;
    kritis: boolean;
    status: StatusButir;
    menunggu: string | null;
    tautan: { href: string; teks: string } | null;
}

/** Niat terakhir yang diinginkan pengguna untuk satu butir, sebelum terkirim ke server. */
interface Niat {
    item: Butir;
    status: StatusButir;
    papanKetik: boolean;
    /**
     * Kendali asal (checkbox atau tombol "Tidak berlaku") saat niat ini dibuat. Dibandingkan
     * dengan document.activeElement saat toast muncul, supaya fokus tidak pernah dipindah ke
     * Urungkan bila pengguna sudah berpindah fokus ke kendali lain (putaran 3 #5).
     */
    asal: HTMLElement | null;
    denganToast: boolean;
    /**
     * Status tersimpan sebelum rangkaian ketukan yang belum diakui (W4-4g): ketuk A, batal, A
     * dalam satu perjalanan pulang-pergi membuat respons kiriman pertama basi, dan kiriman
     * terakhir sendiri tidak mengubah apa pun. Status awal itu dibawa ke kiriman terakhir,
     * supaya toast dan Urungkan tetap muncul untuk keadaan akhirnya.
     */
    sebelum: StatusButir | null;
}

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    tautanLangkah: Partial<Record<KunciLangkah, string>>;
    /** Trip yang selesai atau batal: daftarnya catatan, tanpa centang dan tanpa aksi (W4-4d). */
    dapatDiubah: boolean;
    kategori: Array<{ kunci: string; nama: string; items: Butir[] }>;
    pengingat: PengingatHalaman;
}

const kataStatus: Record<StatusButir, string> = {
    CONFIRMED: 'Dikonfirmasi',
    NOT_CONFIRMED: 'Belum dikonfirmasi',
    NOT_APPLICABLE: 'Tidak berlaku',
};

const tautanTeks =
    'mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Hitungan dari status yang tampil: "Tidak berlaku" tidak dihitung, sama dengan TripSteps::hitungPersiapan(). */
function hitung(items: Butir[], statusKini: (item: Butir) => StatusButir): { dikonfirmasi: number; berlaku: number } {
    const berlaku = items.filter((item) => statusKini(item) !== 'NOT_APPLICABLE');

    return { dikonfirmasi: berlaku.filter((item) => statusKini(item) === 'CONFIRMED').length, berlaku: berlaku.length };
}

/** Kalimat yang sama dengan TripSteps::teksPersiapan() di ruang kerja dan Cek kesiapan. */
function teksKemajuan(dikonfirmasi: number, berlaku: number, jumlah: number): string {
    if (jumlah === 0) {
        return 'Belum ada butir persiapan';
    }

    return berlaku === 0 ? `Semua ${jumlah} butir ditandai Tidak berlaku` : `${dikonfirmasi} dari ${berlaku} butir dikonfirmasi`;
}

/**
 * Persiapan (spec v3 bagian 6.10, prototype 09). Keadaan persiapan sebagai hitungan butir
 * kritis dengan ruas per butir, lalu kelompok kategori. Centang optimis: pilihan langsung
 * terlihat, server menulis di latar, dan toast Urungkan milik halaman (W2-5i) muncul di
 * onSuccess memakai status sebelumnya yang benar-benar tersimpan menurut server, bukan
 * pandangan klien (tinjauan Tugas 4 I1); tidak ditawarkan bila statusnya tidak berubah (pola
 * W3-4b). Hitungan dihitung dari status yang tampil, jadi ruas dan angka tidak tertinggal dari
 * kotak yang baru dicentang (tinjauan Tugas 4 M4 d).
 *
 * Kiriman butir diserialkan PER HALAMAN, satu permintaan pada satu waktu (tinjauan Tugas 4
 * putaran 2): satu-satunya cara flash dan tas errors milik sesi tidak pernah tertukar antara
 * dua butir yang ditekan hampir bersamaan. Penolakan server tampil di baris butirnya sendiri
 * dan bertahan sampai butir itu dikirim lagi, bukan hilang oleh respons kiriman berikutnya
 * (W4-4g). "Belum dikonfirmasi" adalah pernyataan tentang catatan, bukan tuduhan (BR-10). Butir
 * cuaca menunggu BMKG sampai prakiraan tanggal trip terbit.
 *
 * Kunjungan GET sinkron biasa (misalnya "Lanjut ke cek kesiapan") ditahan selagi masih ada
 * kiriman butir berjalan atau antre, lalu diputar ulang begitu antreannya habis; tahanan
 * dibuang bila kiriman gagal atau dibatalkan, atau bila kunjungan sinkron lain yang lebih baru
 * lewat (tinjauan putaran 3 M-1 sampai M-3).
 */
export default function Preparation() {
    const { kepala, tautanLangkah, dapatDiubah, kategori, pengingat, flash, errors } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [sementara, setSementara] = useState<Record<number, StatusButir>>({});
    const [galat, setGalat] = useState<Record<number, string>>({});
    // Penolakan terakhir untuk pembaca layar, di wilayah role="alert" yang selalu terpasang
    // (konvensi DESIGN.md); teks yang sama tampil di baris butirnya.
    const [galatTerakhir, setGalatTerakhir] = useState('');
    const [hanyaBelum, setHanyaBelum] = useState(false);
    // Baris yang tetap tampil dalam mode saring sampai saringannya diterapkan ulang (tinjauan
    // Tugas 4 M5): baris yang baru dicentang tidak lenyap, jadi baris berikutnya tidak bergeser
    // ke bawah jari.
    const [tetapTampil, setTetapTampil] = useState<ReadonlySet<number>>(new Set());
    const [memperbarui, setMemperbarui] = useState(false);
    // Satu kiriman butir aktif untuk SELURUH halaman, bukan per butir: antrian menyimpan niat
    // TERAKHIR per butir menunggu gilirannya; butir yang sama menimpa niat lamanya (posisi
    // antrean tidak berubah), butir lain menunggu di belakangnya. terpasang berhenti mengirim
    // kiriman baru sesudah halaman ini dilepas.
    const sedangKirim = useRef(false);
    const antrian = useRef<Map<number, Niat>>(new Map());
    const terpasang = useRef(true);
    // Kunjungan GET sinkron biasa ditahan selagi masih ada kiriman berjalan atau antrean
    // berisi: tujuan terbaru menimpa yang lama. menahanNavigasi hanya untuk teks status;
    // tujuanTertahan membawa data kunjungan aslinya untuk diputar ulang.
    const tujuanTertahan = useRef<{ href: string; opsi: Parameters<typeof router.visit>[1] } | null>(null);
    const [menahanNavigasi, setMenahanNavigasi] = useState(false);
    // Kabar yang datang bersama perpindahan halaman (misalnya "Rencana trip tersimpan.") tampil
    // dekat kepala, tempat pendaki mendarat (W4-FK N1). Wilayahnya terpasang kosong lebih dulu
    // dan isinya ditulis sesudah halaman terpasang, supaya pembaca layar membacakannya
    // (konvensi wilayah yang selalu terpasang, DESIGN.md).
    const [kabarMasuk, setKabarMasuk] = useState('');

    useEffect(() => {
        setKabarMasuk(flash.status ?? '');
    }, [flash.status]);

    const lepasTahanan = () => {
        tujuanTertahan.current = null;
        setMenahanNavigasi(false);
    };

    useEffect(() => {
        terpasang.current = true;

        return () => {
            terpasang.current = false;
        };
    }, []);

    // Tautan "Konfirmasi" Cek kesiapan membuka baris butirnya (#butir-id); fokusnya ikut pindah
    // ke baris itu, bukan tertinggal di puncak halaman (W4-6h m8).
    useEffect(() => {
        const jangkar = window.location.hash.slice(1);

        if (jangkar.startsWith('butir-')) {
            document.getElementById(jangkar)?.focus();
        }
    }, []);

    useEffect(() => {
        const lepasSebelum = router.on('before', (event) => {
            const visit = event.detail.visit;
            // Kiriman butir (post async), Urungkan, Perbarui daftar, dan sakelar pengingat tidak
            // ikut tertahan: hanya kunjungan GET sinkron biasa yang ditahan, dan hanya selagi masih
            // ada kiriman butir yang belum terkirim. Syarat method dan async juga yang mencegah
            // kiriman butir sendiri tertahan dan membekukan antrean.
            const kirimanTertunda = sedangKirim.current || antrian.current.size > 0;
            const kunjunganSinkronBiasa =
                visit.method === 'get' && !visit.async && !visit.prefetch && visit.only.length === 0 && visit.except.length === 0 && visit.reset.length === 0;

            if (!kirimanTertunda || !kunjunganSinkronBiasa) {
                // M-3: kunjungan sinkron lain yang lewat tanpa ditahan adalah niat yang lebih
                // baru; memutar ulang tujuan lama sesudahnya akan memotongnya.
                if (!visit.async && tujuanTertahan.current !== null) {
                    lepasTahanan();
                }

                return;
            }

            tujuanTertahan.current = {
                href: visit.url.href,
                opsi: { replace: visit.replace, preserveScroll: visit.preserveScroll, preserveState: visit.preserveState },
            };
            setMenahanNavigasi(true);

            return false;
        });

        return lepasSebelum;
    }, []);

    const statusKini = (item: Butir): StatusButir => sementara[item.id] ?? item.status;

    // Mengirim niat butir paling lama di antrean, lalu memanggil dirinya sendiri lagi
    // sesudah selesai selama masih ada yang menunggu. preserveState dan preserveScroll
    // supaya halaman dan posisi gulir bertahan (konvensi W2).
    const prosesAntrian = () => {
        if (sedangKirim.current || !terpasang.current || antrian.current.size === 0) {
            return;
        }

        const itemId = antrian.current.keys().next().value as number;
        const niat = antrian.current.get(itemId) as Niat;
        antrian.current.delete(itemId);
        sedangKirim.current = true;
        let berhasil = false;

        router.post(
            update.url({ trip: kepala.id, item: itemId }),
            { status: niat.status },
            {
                async: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: (halaman) => {
                    berhasil = true;
                    setGalat((g) => {
                        const salinan = { ...g };
                        delete salinan[itemId];
                        return salinan;
                    });

                    const diubah = (halaman.props as unknown as PropsBersama).flash.butirDiubah;
                    const berikutnya = antrian.current.get(itemId);

                    // Respons basi: sudah ada niat baru untuk butir ini. Toast menunggu kiriman
                    // terakhir, tetapi status sebelum rangkaian ini dibawa ke sana (W4-4g).
                    if (berikutnya) {
                        if (diubah !== null && diubah.id === itemId) {
                            berikutnya.sebelum ??= niat.sebelum ?? (diubah.sebelum as StatusButir);
                        }

                        return;
                    }

                    // Satu-satunya penjaga, bukan lapis kedua: serialisasi di atas berlaku
                    // per tab, sedangkan flash ini per sesi, jadi dua tab dengan cookie yang
                    // sama tetap bisa bertukar payload. Cek id ini yang menolaknya.
                    if (!niat.denganToast || diubah === null || diubah.id !== itemId) {
                        return;
                    }

                    const sebelum = niat.sebelum ?? (diubah.sebelum as StatusButir);
                    const berubah = niat.sebelum !== null ? sebelum !== niat.status : diubah.berubah;

                    if (!berubah) {
                        return;
                    }

                    tampilkanToast(`${niat.item.label}: ${kataStatus[niat.status].toLowerCase()}.`, () => setel(niat.item, sebelum, false, null, false), {
                        fokus: niat.papanKetik && document.activeElement === niat.asal,
                    });
                },
                onError: (galatServer) => {
                    const pesan = galatServer.status ?? 'Pilihan ini belum tersimpan. Coba lagi.';
                    setGalat((g) => ({ ...g, [itemId]: pesan }));
                    setGalatTerakhir(`${niat.item.label}: ${pesan}`);
                },
                // M-2: kiriman yang dibatalkan (misalnya Back peramban) membuang tahanan secara
                // eksplisit, tidak bergantung pada urutan internal pembatalan Inertia.
                onCancel: lepasTahanan,
                onFinish: () => {
                    sedangKirim.current = false;

                    if (!antrian.current.has(itemId) && terpasang.current) {
                        setSementara((s) => {
                            const salinan = { ...s };
                            delete salinan[itemId];
                            return salinan;
                        });
                    }

                    // M-1: kiriman yang gagal (jaringan, ditolak, respons bukan Inertia) tidak
                    // memutar ulang kunjungan tertahan: pendaki tetap di sini bersama pesannya.
                    if (!berhasil && tujuanTertahan.current) {
                        lepasTahanan();
                    }

                    prosesAntrian();

                    // Antrean sudah habis dan tidak ada kiriman lain berjalan: kunjungan
                    // yang ditahan boleh diputar ulang. Tujuannya dikosongkan dulu, supaya
                    // kunjungan ulang ini sendiri tidak tertahan lagi oleh pendengar before.
                    if (!sedangKirim.current && antrian.current.size === 0 && terpasang.current && tujuanTertahan.current) {
                        const tujuan = tujuanTertahan.current;
                        lepasTahanan();
                        router.visit(tujuan.href, tujuan.opsi);
                    }
                },
            },
        );
    };

    const setel = (item: Butir, status: StatusButir, papanKetik: boolean, asal: HTMLElement | null = null, denganToast = true) => {
        setSementara((s) => ({ ...s, [item.id]: status }));
        setGalat((g) => {
            const salinan = { ...g };
            delete salinan[item.id];
            return salinan;
        });

        if (hanyaBelum) {
            setTetapTampil((t) => new Set(t).add(item.id));
        }

        antrian.current.set(item.id, { item, status, papanKetik, asal, denganToast, sebelum: antrian.current.get(item.id)?.sebelum ?? null });
        prosesAntrian();
    };

    const saring = () => {
        setTetapTampil(new Set());
        setHanyaBelum((v) => !v);
    };

    const perbarui = () => {
        setMemperbarui(true);
        router.post(regenerate.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMemperbarui(false) });
    };

    const semuaButir = kategori.flatMap((k) => k.items);
    const kritis = hitung(
        semuaButir.filter((item) => item.kritis),
        statusKini,
    );
    const seluruh = hitung(semuaButir, statusKini);
    const kritisBelum = semuaButir.filter((item) => item.kritis && statusKini(item) === 'NOT_CONFIRMED');
    const belum = seluruh.berlaku - seluruh.dikonfirmasi;
    const tampil = (item: Butir) => !hanyaBelum || statusKini(item) === 'NOT_CONFIRMED' || tetapTampil.has(item.id);
    const adaYangTampil = semuaButir.some(tampil);
    const gayaKotak = kritis.berlaku === 0 ? 'border-subtle bg-surface' : kritis.dikonfirmasi === kritis.berlaku ? 'border-brand-200 bg-brand-50' : 'border-warn-300 bg-warn-50';

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <div>
                    <PageHeader induk={{ href: show.url(kepala.id), label: 'Ruang kerja trip' }} title={kepala.nama}>
                        <KepalaTrip kepala={kepala} />
                    </PageHeader>
                    <LangkahRingkas kini="persiapan" tautan={tautanLangkah} />
                    <p role="status" className={kabarMasuk ? 'rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined}>
                        {kabarMasuk}
                    </p>
                </div>

                {!dapatDiubah ? (
                    <Alert variant="info">Trip ini sudah {kepala.keadaan?.toLowerCase()}. Daftar persiapannya disimpan sebagai catatan dan tidak dapat diubah lagi.</Alert>
                ) : null}

                <section aria-labelledby="keadaan-persiapan" className={kelas('rounded-utama border p-5', gayaKotak)}>
                    <h2 id="keadaan-persiapan" className="text-bagian text-primary">
                        Keadaan persiapan
                    </h2>
                    {errors.daftar ? (
                        <Alert variant="danger" className="mt-2">
                            {errors.daftar}
                        </Alert>
                    ) : null}
                    {kritis.berlaku > 0 ? (
                        <>
                            <p data-angka className="mt-2 text-isi font-bold text-primary">
                                {kritis.dikonfirmasi} dari {kritis.berlaku} butir kritis dikonfirmasi
                            </p>
                            <div aria-hidden="true" className="mt-3 flex gap-1">
                                {Array.from({ length: kritis.berlaku }, (_, i) => (
                                    <span key={i} className={kelas('h-2 flex-1 rounded-full', i < kritis.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                                ))}
                            </div>
                        </>
                    ) : (
                        <p data-angka className="mt-2 text-isi text-primary">
                            {teksKemajuan(seluruh.dikonfirmasi, seluruh.berlaku, semuaButir.length)}
                        </p>
                    )}
                    {kritisBelum.length > 0 ? (
                        <p className="mt-3 text-kecil text-primary">
                            Belum dikonfirmasi: {kritisBelum.map((item) => (item.menunggu ? `${item.label} (menunggu BMKG)` : item.label)).join(', ')}.
                        </p>
                    ) : null}
                    <p className="mt-2 text-kecil text-secondary">Butir yang belum dicentang bukan berarti tidak Anda miliki.</p>
                    {belum > 0 || hanyaBelum ? (
                        <Button variant="secondary" size="sm" className="mt-3" aria-pressed={hanyaBelum} onClick={saring}>
                            Hanya yang belum dikonfirmasi
                        </Button>
                    ) : null}
                </section>

                <p role="alert" className="sr-only">
                    {galatTerakhir}
                </p>

                {hanyaBelum && !adaYangTampil ? (
                    // Saringan aktif dan tidak ada butir yang tersisa (tinjauan Tugas 4 I3). Jalan
                    // kembalinya satu kendali, sakelar saringan di atas (Nit 6).
                    <EmptyState
                        jenis="tersaring"
                        title="Semua butir sudah dikonfirmasi."
                        description="Saringan ini menyembunyikan butir yang sudah dikonfirmasi atau Tidak berlaku. Matikan saringan di atas untuk melihat semuanya."
                    />
                ) : (
                    kategori.map((k) => {
                        const items = k.items.filter(tampil);
                        const hitungan = hitung(k.items, statusKini);

                        if (items.length === 0) {
                            return null;
                        }

                        return (
                            <section key={k.kunci} aria-labelledby={`kategori-${k.kunci}`}>
                                <div className="flex items-baseline justify-between gap-3">
                                    <h2 id={`kategori-${k.kunci}`} className="text-bagian text-primary">
                                        {k.nama}
                                    </h2>
                                    <span data-angka className="text-meta text-secondary">
                                        {hitungan.dikonfirmasi} dari {hitungan.berlaku} butir
                                    </span>
                                </div>
                                <ul className="mt-3 divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                                    {items.map((item) => {
                                        const aktif = statusKini(item);
                                        const lencana = item.kritis ? <Lencana nada="gelap">Kritis</Lencana> : undefined;
                                        const keterangan = [kataStatus[aktif], item.keterangan].filter(Boolean).join('. ');
                                        const pesanGalat = galat[item.id];

                                        return (
                                            <li
                                                key={item.id}
                                                id={`butir-${item.id}`}
                                                tabIndex={-1}
                                                className="scroll-mt-24 px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600"
                                            >
                                                <div className="flex items-start gap-2">
                                                    <div className="min-w-0 flex-1">
                                                        {!dapatDiubah ? (
                                                            // Catatan trip yang selesai atau batal: status sebagai kata, tanpa kotak.
                                                            <div className="min-h-11 py-2">
                                                                <span className="flex flex-wrap items-center gap-2">
                                                                    <span className="break-words text-isi font-semibold text-primary">{item.label}</span>
                                                                    {lencana}
                                                                </span>
                                                                <span className="mt-0.5 block text-kecil text-secondary">{keterangan}</span>
                                                            </div>
                                                        ) : item.menunggu && aktif === 'NOT_CONFIRMED' ? (
                                                            <div className="flex min-h-11 items-start gap-3 py-2">
                                                                <span aria-hidden="true" className="mt-0.5 size-7 shrink-0 rounded-kecil border-2 border-dashed border-control" />
                                                                <span className="min-w-0">
                                                                    <span className="flex flex-wrap items-center gap-2">
                                                                        <span className="break-words text-isi font-semibold text-primary">{item.label}</span>
                                                                        {lencana}
                                                                    </span>
                                                                    <span className="mt-0.5 block text-kecil text-secondary">{item.menunggu}</span>
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <KotakCentang
                                                                label={item.label}
                                                                keterangan={keterangan}
                                                                lencana={lencana}
                                                                penjelas={pesanGalat ? `galat-butir-${item.id}` : undefined}
                                                                checked={aktif === 'CONFIRMED'}
                                                                disabled={aktif === 'NOT_APPLICABLE'}
                                                                onChange={(e) =>
                                                                    setel(item, e.currentTarget.checked ? 'CONFIRMED' : 'NOT_CONFIRMED', (e.nativeEvent as UIEvent).detail === 0, e.currentTarget)
                                                                }
                                                                className={aktif === 'NOT_APPLICABLE' ? 'opacity-60' : undefined}
                                                            />
                                                        )}
                                                    </div>
                                                    {dapatDiubah ? (
                                                        <button
                                                            type="button"
                                                            onClick={(e) => setel(item, aktif === 'NOT_APPLICABLE' ? 'NOT_CONFIRMED' : 'NOT_APPLICABLE', e.detail === 0, e.currentTarget)}
                                                            className="inline-flex min-h-11 shrink-0 items-center px-1 text-meta font-semibold text-secondary underline underline-offset-2 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                                        >
                                                            {aktif === 'NOT_APPLICABLE' ? 'Berlaku lagi' : 'Tidak berlaku'}
                                                            <span className="sr-only">: {item.label}</span>
                                                        </button>
                                                    ) : null}
                                                </div>
                                                {pesanGalat ? (
                                                    <p id={`galat-butir-${item.id}`} className="ml-10 pb-2 text-kecil font-bold text-warn-900">
                                                        {pesanGalat}
                                                    </p>
                                                ) : null}
                                                {item.tautan ? (
                                                    <a href={item.tautan.href} target="_blank" rel="noopener noreferrer" className={kelas(tautanTeks, 'ml-10')}>
                                                        {item.tautan.teks}
                                                        <span className="sr-only"> (membuka tab baru)</span>
                                                    </a>
                                                ) : null}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </section>
                        );
                    })
                )}

                {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={kepala.id} pengingat={pengingat} kembali="persiapan" /> : null}

                {/* Satu wilayah status selalu terpasang tepat di bawah baris aksi (konvensi DESIGN.md;
                    M7 dan M-5): kabar aksi di halaman ini ("Perbarui daftar" dan sakelar pengingat
                    di atasnya) atau teks penahan navigasi. Kabar yang datang bersama perpindahan
                    halaman ada di wilayah dekat kepala (W4-FK N1). empty:!mt-0 menghapus jaraknya
                    saat kosong tanpa menyembunyikan wilayahnya. */}
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button href={readiness.url(kepala.id)}>Lanjut ke cek kesiapan</Button>
                        {dapatDiubah ? (
                            <Button variant="secondary" size="sm" onClick={perbarui} processing={memperbarui}>
                                Perbarui daftar
                            </Button>
                        ) : null}
                    </div>
                    <p role="status" className="empty:!mt-0 text-kecil text-secondary">
                        {menahanNavigasi ? 'Menyimpan pilihan terakhir, lalu pindah halaman.' : (flash.kabarAksi ?? '')}
                    </p>
                </div>
            </div>
        </div>
    );
}

Preparation.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
