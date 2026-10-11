import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { PintuPaket } from '@/components/mendaki/PintuPaket';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { create as goalsCreate } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface TripHariH {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    jenis: string;
    hari: string | null;
    lewat: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    cek: string;
    urls: { mulai: string; ruangKerja: string; cek: string; lapangan: string };
}

interface TripLain {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    tanggal: string;
    hari: string | null;
    lewat: string | null;
    urls: { mulai: string; ruangKerja: string };
}

interface Props extends PropsBersama {
    hariH: TripHariH | null;
    lain: TripLain[];
    lokasi: string;
    jejak: { menyala: boolean; kalimat: string; url: string };
    paket: { aktif: boolean; kalimat: string };
    potong: { terpotong: boolean; kalimat: string; url: string };
}

const tautanTeks = 'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Pintu tab Mendaki (spec v3 bagian 6.12; rencana induk W6 butir 1): pratinjau hari-H trip
 * terdekat yang dapat dimulai, dengan keadaan paket luring, cara lokasi dipakai, dan rekam
 * jejak, lalu satu tombol "Mulai pendakian" 66 px. Trip lain yang dapat dimulai di bawahnya.
 */
export default function TrackIndex() {
    const { hariH, lain, lokasi, jejak, paket, potong } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Mendaki" description="Mode lapangan untuk hari pendakian: peta jalur, pos berikutnya, dan angka yang dihitung di perangkat Anda." />

                {hariH === null ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada trip yang dapat dimulai."
                        description="Mode lapangan dibuka dari trip yang sudah direncanakan. Rencanakan trip dulu, lalu kembali ke sini pada hari keberangkatan."
                    >
                        <Button href={tripsIndex.url()}>Buka trip saya</Button>
                        <Button variant="secondary" href={goalsCreate.url()}>Cari jalur yang cocok</Button>
                    </EmptyState>
                ) : (
                    <section aria-labelledby="judul-hari-h" className="rounded-utama border border-subtle bg-surface p-5">
                        <h2 id="judul-hari-h" className="text-judul-kartu text-primary">
                            <Tautan href={hariH.urls.ruangKerja} className="underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 [overflow-wrap:anywhere]">
                                {hariH.nama}
                            </Tautan>
                        </h2>
                        <p className="text-isi text-secondary">
                            {hariH.jalur}, {hariH.gunung}
                        </p>
                        <p className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                            <span>{[hariH.hari, hariH.jenis, hariH.tanggal, hariH.jam ? `mulai ${hariH.jam}` : null, hariH.peserta].filter(Boolean).join(', ')}</span>
                            {hariH.lewat ? (
                                <Lencana nada="peringatan" ikon="warning">
                                    {hariH.lewat}
                                </Lencana>
                            ) : null}
                        </p>

                        <dl className="mt-4 space-y-3 border-t border-subtle pt-4">
                            <Butir judul="Paket luring">
                                {paket.kalimat}
                                {paket.aktif ? <PintuPaket tripId={hariH.id} /> : null}
                            </Butir>
                            <Butir judul="Lokasi">{lokasi}</Butir>
                            <Butir judul="Rekam jejak">
                                {jejak.kalimat}{' '}
                                <Tautan href={jejak.url} className={tautanTeks}>
                                    Atur di Profil
                                </Tautan>
                            </Butir>
                            <Butir judul="Cek kesiapan">
                                {hariH.cek}{' '}
                                <Tautan href={hariH.urls.cek} className={tautanTeks}>
                                    Buka cek kesiapan
                                </Tautan>
                            </Butir>
                        </dl>

                        <MulaiPendakian id={hariH.id} nama={hariH.nama} gunung={hariH.gunung} url={hariH.urls.mulai} besar />
                        <Tautan href={hariH.urls.lapangan} className={kelas(tautanTeks, 'mt-2')}>
                            Tinjau peta dan pos sebelum mulai
                        </Tautan>
                    </section>
                )}

                {lain.length > 0 ? (
                    <section aria-labelledby="judul-trip-lain" className="space-y-3">
                        <h2 id="judul-trip-lain" className="text-bagian text-primary">
                            Trip lain yang dapat dimulai
                        </h2>
                        <ul className="space-y-3">
                            {lain.map((trip) => (
                                <li key={trip.id} className="rounded-kartu border border-subtle bg-surface p-4">
                                    <Tautan href={trip.urls.ruangKerja} className="inline-flex min-h-11 items-center text-subjudul text-primary underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                        <span className="min-w-0 [overflow-wrap:anywhere]">{trip.nama}</span>
                                    </Tautan>
                                    <p className="text-kecil text-secondary">{trip.jalur}, {trip.gunung}</p>
                                    <p className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                                        <span>{[trip.tanggal, trip.hari].filter(Boolean).join(', ')}</span>
                                        {trip.lewat ? (
                                            <Lencana nada="peringatan" ikon="warning">
                                                {trip.lewat}
                                            </Lencana>
                                        ) : null}
                                    </p>
                                    <MulaiPendakian id={trip.id} nama={trip.nama} gunung={trip.gunung} url={trip.urls.mulai} />
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                {potong.terpotong ? (
                    <p className="text-kecil text-secondary">
                        {potong.kalimat}{' '}
                        <Tautan href={potong.url} className={tautanTeks}>
                            Buka tab Trip
                        </Tautan>
                    </p>
                ) : null}
            </div>
        </div>
    );
}

function Butir({ judul, children }: { judul: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-kecil font-bold text-primary">{judul}</dt>
            <dd className="text-kecil text-secondary">{children}</dd>
        </div>
    );
}

/**
 * Memulai tidak dapat dikembalikan ke rencana, jadi tombolnya meminta konfirmasi sebaris, bukan
 * dialog (T1/ruling W6-4e): "Mulai pendakian" hanya membuka konfirmasi lewat buka(), dan hanya
 * tombol konfirmasi berid idKonfirmasi yang memanggil mulai().
 *
 * Fokus tidak pernah jatuh ke body (DESIGN.md, pola W6-3o): buka() memindah fokus ke "Ya, mulai
 * sekarang" dan mengaitkan peringatannya lewat aria-describedby; batal() mengembalikannya ke
 * "Mulai pendakian" (putaran perbaikan 1 I-2/ruling W6-4c). Cabang galat jaringan juga
 * mengembalikan fokus ke "Ya, mulai sekarang" sesudah tombolnya aktif lagi (`memproses` kembali
 * false membuat Button tidak lagi `disabled`; memanggil fokus lebih awal, saat tombol masih
 * nonaktif, tidak berefek): tanpa ini tombol yang sempat `disabled` menjatuhkan fokus ke body
 * begitu `onFinish` berjalan, karena peramban tidak mengembalikan fokus sendiri ke elemen yang
 * baru aktif lagi (putaran perbaikan 2 R-1/ruling W6-4q). Ketiga tombol menyebut nama dan gunung
 * trip lewat sr-only berpemisah tekstual, supaya pembaca layar membedakan baris mana yang akan
 * dimulai saat beberapa konfirmasi dapat terbuka sekaligus, termasuk saat dua trip bernama sama
 * di gunung berbeda (putaran perbaikan 1 M-4/ruling W6-4i, putaran perbaikan 2 N-1); label yang
 * terlihat tidak berubah.
 *
 * Tanpa sinyal, permintaan gagal di jaringan sebelum jawaban server diterima: server mungkin
 * sudah memulai pendakiannya (`TripLifecycle::start()` commit lebih dulu), jadi kalimatnya tidak
 * lagi menyatakan "belum dimulai" begitu saja, melainkan mengajak membuka tab ini lagi, yang
 * langsung membuka mode lapangan bila pendakian memang sudah berjalan (`TrackController.php`;
 * putaran perbaikan 1 M-6/ruling W6-4k, titipan W2-5j). Wilayah status selalu terpasang (T4),
 * hanya isinya yang bersyarat.
 *
 * Galat HTTP tanpa header Inertia, misalnya 419 sesudah sesi berakhir, ditulis di wilayah status
 * yang sama lewat `onHttpException` yang mengembalikan false (ruling W6-FE, pola W6-11c), bukan
 * dialog galat bawaan Inertia, dan juga "belum pasti", karena server bisa sudah memulai.
 */
function MulaiPendakian({ id, nama, gunung, url, besar = false }: { id: number; nama: string; gunung: string; url: string; besar?: boolean }) {
    const [yakin, setYakin] = useState(false);
    const [memproses, setMemproses] = useState(false);
    const [pesan, setPesan] = useState<string | null>(null);
    const fokusKe = useFokusSesudahRender();

    const idMulai = `mulai-${id}`;
    const idKonfirmasi = `konfirmasi-${id}`;
    const idPeringatan = `peringatan-${id}`;

    const buka = () => {
        setYakin(true);
        fokusKe(idKonfirmasi);
    };

    const batal = () => {
        setYakin(false);
        fokusKe(idMulai);
    };

    const mulai = () => {
        setMemproses(true);
        setPesan(null);
        router.post(
            url,
            {},
            {
                onNetworkError: () => {
                    setPesan('Sambungan terputus sebelum jawaban server diterima, jadi belum pasti pendakian sudah dimulai. Buka lagi tab Mendaki saat ada sinyal; bila sudah dimulai, mode lapangan langsung terbuka.');

                    return false;
                },
                onHttpException: () => {
                    setPesan('Belum pasti dimulai: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa apakah pendakian sudah berjalan sebelum mencoba lagi.');

                    return false;
                },
                onFinish: () => {
                    setMemproses(false);
                    fokusKe(idKonfirmasi);
                },
            },
        );
    };

    const ukuran = besar ? 'mt-5 min-h-[66px] w-full py-4' : 'mt-3';

    return (
        <div>
            {yakin ? (
                <div className={kelas('flex flex-wrap items-center gap-2', besar ? 'mt-5' : 'mt-3')}>
                    <Button id={idKonfirmasi} aria-describedby={idPeringatan} onClick={mulai} processing={memproses} className={besar ? 'min-h-[66px] flex-1 py-4' : undefined}>
                        {besar ? <span className="text-bagian">Ya, mulai sekarang</span> : 'Ya, mulai sekarang'}
                        <span className="sr-only">, {nama}, {gunung}</span>
                    </Button>
                    <Button variant="secondary" onClick={batal} disabled={memproses}>
                        Batal
                        <span className="sr-only">, {nama}, {gunung}</span>
                    </Button>
                    <p id={idPeringatan} className="basis-full text-kecil text-secondary">Pendakian yang sudah dimulai tidak dapat dikembalikan menjadi rencana. Satu pendakian dapat berlangsung pada satu waktu.</p>
                </div>
            ) : (
                <Button id={idMulai} onClick={buka} className={ukuran} variant={besar ? 'primary' : 'secondary'}>
                    {besar ? <span className="text-bagian">Mulai pendakian</span> : 'Mulai pendakian'}
                    <span className="sr-only">, {nama}, {gunung}</span>
                </Button>
            )}
            <p role="status" className="mt-2 text-kecil font-bold text-primary">
                {pesan}
            </p>
        </div>
    );
}

TrackIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
