import { router } from '@inertiajs/react';
import { useLayoutEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { kelas } from '@/lib/kelas';

/**
 * Wilayah alasan penolakan di ruang kerja trip (`<p role="alert" id="penolakan-trip">`,
 * Trips/Show.tsx). Penolakan mulai selalu dialihkan server ke ruang kerja trip itu
 * (TripLifecycleController::start()), dari tab Mendaki maupun dari ruang kerja sendiri. Wilayah
 * itu selalu terpasang di ruang kerja dan hanya isinya yang bersyarat, jadi sesudah penolakan
 * ia selalu ditemukan; tombol Mulai hanya cadangan untuk halaman tanpa wilayah itu (ruling EF-7).
 */
export const ID_PENOLAKAN_TRIP = 'penolakan-trip';

/**
 * Fokus belum dipindah pengguna: masih di body, tidak ada, atau di `#konten` (pola useFokusKembali).
 * Penjaga setiap pemindahan fokus sesudah penolakan (ruling CC-1 dan EF-5).
 */
function fokusBelumDipindah(): boolean {
    const aktif = document.activeElement;

    return aktif === null || aktif === document.body || aktif.id === 'konten';
}

interface MulaiPendakianProps {
    id: number;
    nama: string;
    gunung: string;
    url: string;
    /** Tombol 66 px selebar kartu untuk pratinjau hari-H tab Mendaki (T2/ruling W6-4e). */
    besar?: boolean;
    /**
     * Variant primary. Bawaannya mengikuti `besar`, seperti tab Mendaki sebelum komponen ini
     * dipindah. Ruang kerja trip mengirim `hariBerangkat` (ruling W4-G1, CF-3).
     */
    utama?: boolean;
    /** Tanpa jarak atas, untuk baris tombol yang sudah berjarak sendiri (kartu Tindakan ruang kerja). */
    rapat?: boolean;
    /**
     * Tanggal rencana, diisi pemanggil hanya bila hari ini di zona gunung belum mencapainya
     * (predikat yang sama dengan `hariBerangkat`, KepalaTrip::hariBerangkat()). Null sejak hari
     * berangkat, termasuk tanggal yang sudah lewat.
     */
    tanggalRencana?: string | null;
}

/**
 * Memulai tidak dapat dikembalikan ke rencana, jadi tombolnya meminta konfirmasi sebaris, bukan
 * dialog (T1/ruling W6-4e): "Mulai pendakian" hanya membuka konfirmasi lewat buka(), dan hanya
 * tombol konfirmasi berid idKonfirmasi yang memanggil mulai(). Ruling CF-3 (audit E2E N-4)
 * memberlakukan T1 di setiap pintu mulai, jadi komponen ini dipindah dari tab Mendaki
 * (Track/Index.tsx) dan dipakai juga oleh ruang kerja trip (Trips/Show.tsx), tanpa mengubah
 * perilakunya di tab Mendaki.
 *
 * Bila hari ini belum tanggal rencana trip, peringatan konfirmasi diawali "Tanggal rencana trip
 * ini {tanggal}." (ruling CF-3), supaya trip H-34 tidak dimulai tanpa disadari.
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
 * lagi menyatakan "belum dimulai" begitu saja, melainkan mengajak membuka tab Mendaki lagi, yang
 * langsung membuka mode lapangan bila pendakian memang sudah berjalan (`TrackController.php`;
 * putaran perbaikan 1 M-6/ruling W6-4k, titipan W2-5j). Kalimat itu benar juga dari ruang kerja,
 * karena tab Mendaki membuka pendakian yang berjalan dari mana pun ia dimulai. Wilayah status
 * selalu terpasang (T4), hanya isinya yang bersyarat.
 *
 * Galat HTTP tanpa header Inertia, misalnya 419 sesudah sesi berakhir, ditulis di wilayah status
 * yang sama lewat `onHttpException` yang mengembalikan false (ruling W6-FE, pola W6-11c), bukan
 * dialog galat bawaan Inertia, dan juga "belum pasti", karena server bisa sudah memulai.
 *
 * Penolakan server (ruling CC-1), misalnya pendakian lain masih berjalan atau trip tidak dapat
 * dimulai, mengalihkan ke ruang kerja trip itu dengan props `penolakan`. Sesudahnya fokus pindah
 * ke wilayah alasan penolakan (`#penolakan-trip`) bila ada, selain itu ke tombol "Mulai pendakian"
 * trip itu (id stabil `mulai-{id}`), dan tidak pernah tertinggal di body. Wilayah alasan
 * didahulukan karena ia berada di puncak ruang kerja: fokus menggulirnya ke layar, sedangkan fokus
 * di tombol Mulai di kartu Tindakan meninggalkan alasannya di luar layar bagi pengguna awas
 * (ruling EF-7, audit E2E akhir F-2). Fokus yang sudah dipindah pengguna selama jawaban server
 * ditunggu tidak direbut: pemindahan hanya terjadi selama fokus di body, tidak ada, atau di
 * `#konten` (ruling EF-5, pola useFokusKembali). Ada dua keadaan:
 * - Instans ini masih terpasang, yaitu ruang kerja yang menolak trip yang masih dapat dimulai.
 *   `router.post` Inertia v3 mempertahankan keadaan halaman, jadi konfirmasi masih terbuka.
 *   Konfirmasi ditutup karena mulainya tidak terjadi, lalu onFinish memindah fokus.
 * - Instans ini sudah lepas, yaitu penolakan dari tab Mendaki yang berpindah halaman, atau
 *   ruang kerja yang kini tidak lagi menawarkan mulai. `fokusKe` milik instans ini tidak lagi
 *   berefek. Halaman baru sudah dirender sebelum `onSuccess` (swap Inertia memakai flushSync),
 *   jadi di sana fokus dipindah langsung lewat id.
 */
export function MulaiPendakian({ id, nama, gunung, url, besar = false, utama = besar, rapat = false, tanggalRencana = null }: MulaiPendakianProps) {
    const [yakin, setYakin] = useState(false);
    const [memproses, setMemproses] = useState(false);
    const [pesan, setPesan] = useState<string | null>(null);
    const fokusKe = useFokusSesudahRender();
    // Layout effect: pembersihnya berjalan di dalam commit, jadi sudah false sebelum onSuccess
    // bila swap halaman (flushSync) melepas instans ini.
    const terpasang = useRef(true);

    useLayoutEffect(() => {
        terpasang.current = true;

        return () => {
            terpasang.current = false;
        };
    }, []);

    const idMulai = `mulai-${id}`;
    const idKonfirmasi = `konfirmasi-${id}`;
    const idPeringatan = `peringatan-${id}`;
    // Tujuan fokus sesudah penolakan (ruling EF-7): alasan penolakan lebih dulu, lalu "Mulai pendakian".
    const tujuanPenolakan = () => (document.getElementById(ID_PENOLAKAN_TRIP) ? ID_PENOLAKAN_TRIP : idMulai);

    const buka = () => {
        setYakin(true);
        fokusKe(idKonfirmasi);
    };

    const batal = () => {
        setYakin(false);
        fokusKe(idMulai);
    };

    const mulai = () => {
        let ditolak = false;

        setMemproses(true);
        setPesan(null);
        router.post(
            url,
            {},
            {
                onSuccess: (halaman) => {
                    if (!(halaman.props as { penolakan?: unknown }).penolakan) {
                        return;
                    }

                    ditolak = true;

                    if (terpasang.current) {
                        setYakin(false);

                        return;
                    }

                    if (fokusBelumDipindah()) {
                        document.getElementById(tujuanPenolakan())?.focus();
                    }
                },
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

                    if (!ditolak) {
                        fokusKe(idKonfirmasi);

                        return;
                    }

                    if (fokusBelumDipindah()) {
                        fokusKe(tujuanPenolakan());
                    }
                },
            },
        );
    };

    const ukuran = besar ? 'mt-5 min-h-[66px] w-full py-4' : rapat ? undefined : 'mt-3';

    return (
        <div>
            {yakin ? (
                <div className={kelas('flex flex-wrap items-center gap-2', besar ? 'mt-5' : !rapat && 'mt-3')}>
                    <Button id={idKonfirmasi} aria-describedby={idPeringatan} onClick={mulai} processing={memproses} className={besar ? 'min-h-[66px] flex-1 py-4' : undefined}>
                        {besar ? <span className="text-bagian">Ya, mulai sekarang</span> : 'Ya, mulai sekarang'}
                        <span className="sr-only">, {nama}, {gunung}</span>
                    </Button>
                    <Button variant="secondary" onClick={batal} disabled={memproses}>
                        Batal
                        <span className="sr-only">, {nama}, {gunung}</span>
                    </Button>
                    <p id={idPeringatan} className="basis-full text-kecil text-secondary">
                        {tanggalRencana ? `Tanggal rencana trip ini ${tanggalRencana}. ` : null}Pendakian yang sudah dimulai tidak dapat dikembalikan menjadi rencana. Satu pendakian dapat berlangsung pada satu waktu.
                    </p>
                </div>
            ) : (
                <Button id={idMulai} onClick={buka} className={ukuran} variant={utama ? 'primary' : 'secondary'}>
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
