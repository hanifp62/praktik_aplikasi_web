import { router, usePage } from '@inertiajs/react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { TombolIkon } from '@/components/ui/TombolIkon';
import type { PropsBersama } from '@/types';
import { adaMuatUlangMilikKerangka, muatPenuhVersiBaru } from './Kerangka';

/**
 * Pesan jujur ketika kunjungan Inertia gagal karena jaringan (rencana induk W2 butir 6,
 * ruling W2-R3). Aplikasi tidak tahu apakah permintaan terakhir sempat sampai ke server:
 * yang putus bisa jawabannya, bukan permintaannya. Karena itu salinannya tidak menyuruh
 * mengirim ulang, sebab mengirim ulang formulir buat bisa menggandakan data; pesannya
 * hanya meminta memeriksa hasilnya lebih dulu (ruling W2-5d). Penawaran "Buka paket
 * luring" menyusul di W6.
 *
 * Pesan mengikuti kunjungan terakhir, bukan hanya yang berhasil (ruling W2-5b): ia
 * disembunyikan begitu kunjungan berikutnya DIMULAI (event start), apa pun hasilnya
 * nanti, dan baru tampil lagi bila kunjungan itu sendiri gagal karena jaringan
 * (networkError). Dengan begitu galat validasi, respons non-Inertia, dan keberhasilan
 * semuanya ikut menutup pesan lama; menekan "Muat ulang halaman" memberi umpan balik
 * langsung (pesan hilang), lalu pesan baru muncul lagi bila kunjungan ulang itu sendiri
 * masih luring. Mengembalikan false dari penangan networkError mencegah Inertia melempar
 * ulang galatnya sebagai janji yang ditolak tanpa penangan.
 *
 * Diperbarui Ruling W2-6a #7: hanya start yang TIDAK async yang menyembunyikan pesan ini.
 * Lembar Pengingat memuat isinya lewat muat ulang parsial async setiap kali dibuka; tanpa
 * pengecualian ini, membuka lembar itu akan ikut menyembunyikan pesan jaringan global yang
 * sedang tampil, padahal kunjungan yang menyebabkannya belum tentu selesai.
 *
 * Diperbarui Ruling W2-6e #5: pengecualian W2-6a #7 semula terlalu luas, mengecualikan
 * SEMUA kunjungan async, padahal aksi pengguna lain juga async: Trips/Preparation.tsx
 * (router.post dengan async: true, konfirmasi butir persiapan) dan Community/
 * ConditionReport.tsx (router.reload only: ['segments'], yang dipaksa async oleh doReload()
 * Inertia 3 sendiri, ruling W2-6d). Pesan bertahan sesudah aksi ITU berhasil, klaim palsu
 * yang sama (R-036) hanya lewat jalan lain. Pengecualiannya kini hanya untuk muat ulang
 * lembar Pengingat sendiri (async DAN only berisi 'daftarPengingat'); kunjungan async lain
 * tetap menyembunyikan pesan pada start seperti kunjungan biasa.
 *
 * Diperbarui Ruling W2-6d: router.reload() sendiri SELALU async di Inertia 3
 * (packages/core/src/router.ts doReload(), dibundel node_modules/@inertiajs/core/dist/index.js
 * sekitar baris 3111-3122, memberi `async: true` tanpa syarat), jadi start tidak lagi
 * menyembunyikan pesan ini sesudah W2-6a #7. Tombol "Muat ulang halaman" karena itu
 * menyembunyikan pesan sendiri sebelum memanggil reload(); bila kunjungan itu sendiri masih
 * luring, networkError memunculkannya lagi sebagai peringatan baru (ruling W2-5b).
 *
 * Lapisannya fixed dan dapat menutupi isi di puncak halaman (ruling W2-5a), jadi
 * tingginya diukur lewat ResizeObserver dan ditulis ke --pesan-jaringan di :root selama
 * pesan tampil, lalu dihapus begitu hilang atau dilepas; pola yang sama dengan
 * StripPendakian dan BarAksi. app.css membaca variabel itu untuk scroll-padding-top, dan
 * AppLayout memberi <main> padding-top senilai itu, supaya isi di puncak halaman dan
 * fokus papan ketik yang menuju ke sana tidak tertutup.
 *
 * Diperbarui Ruling W2-F6: pesan yang sama juga menjadi pemberitahuan versi baru. Sesudah
 * deploy, server menjawab kunjungan GET berversi lama dengan 409. Inertia 3 memuat penuh
 * halaman untuk kunjungan sinkron, tetapi mengabaikan konflik itu pada kunjungan async
 * (node_modules/@inertiajs/core/dist/index.js, locationVisit, baris 2584-2596) tanpa callback
 * apa pun. Penangan location di sini memisahkan tiga kasus. Tanpa versionChange, atau selagi
 * kunjungan sinkron berjalan (dilacak per id lewat start dan finish), ia diam, karena Inertia
 * sendiri memuat penuh untuk kunjungan sinkron dan tidak boleh ada dua navigasi. Muat ulang
 * async milik kerangka (muatUlangMilikKerangka di Kerangka.tsx) dimuat penuh. Kunjungan async
 * lain, misalnya muat ulang segmen laporan kondisi yang bisa berjalan saat ada isian yang belum
 * dikirim, hanya memunculkan pemberitahuan versi baru, dan pengguna yang memilih kapan memuat
 * ulang. Tombolnya memuat penuh halaman, karena muat ulang parsial akan terkena konflik yang
 * sama.
 *
 * Diperbarui Ruling W2-F7: milik kerangka kini hanya muat ulang lembar Pengingat (B-1). Tombol
 * "Muat ulang halaman" di pesan jaringan kembali memanggil router.reload() biasa: pesan itu
 * paling sering muncul sesudah formulir yang putus, jawaban 200 mempertahankan isiannya, dan
 * konflik versinya cukup memunculkan pemberitahuan. Muat penuh lewat muatPenuhVersiBaru()
 * (B-4).
 */
export function PesanJaringan() {
    const [tampil, setTampil] = useState(false);
    const [jenis, setJenis] = useState<'jaringan' | 'versi'>('jaringan');
    const wadah = useRef<HTMLDivElement>(null);
    const { merek } = usePage<PropsBersama>().props;

    useEffect(() => {
        const lepasMulai = router.on('start', (event) => {
            const muatUlangLembarPengingat = event.detail.visit.async && event.detail.visit.only?.includes('daftarPengingat');

            if (!muatUlangLembarPengingat) {
                setTampil(false);
            }
        });
        const lepasGalat = router.on('networkError', () => {
            setJenis('jaringan');
            setTampil(true);

            return false;
        });

        return () => {
            lepasMulai();
            lepasGalat();
        };
    }, []);

    useEffect(() => {
        const sinkron = new Set<string>();
        const lepasMulai = router.on('start', (event) => {
            if (!event.detail.visit.async) {
                sinkron.add(event.detail.visit.id);
            }
        });
        const lepasSelesai = router.on('finish', (event) => {
            sinkron.delete(event.detail.visit.id);
        });
        const lepasLokasi = router.on('location', (event) => {
            if (!event.detail.versionChange || sinkron.size > 0) {
                return;
            }

            if (adaMuatUlangMilikKerangka()) {
                muatPenuhVersiBaru();

                return;
            }

            setJenis('versi');
            setTampil(true);
        });

        return () => {
            lepasMulai();
            lepasSelesai();
            lepasLokasi();
        };
    }, []);

    useLayoutEffect(() => {
        const elemen = wadah.current;
        const akar = document.documentElement;

        if (!elemen) {
            return;
        }

        const ukur = () => akar.style.setProperty('--pesan-jaringan', `${elemen.offsetHeight}px`);
        const pengamat = new ResizeObserver(ukur);

        ukur();
        pengamat.observe(elemen);

        return () => {
            pengamat.disconnect();
            akar.style.removeProperty('--pesan-jaringan');
        };
    }, [tampil]);

    /**
     * Dipakai kedua tombol pesan (perbaikan akhir W2, M-1). Tombol yang sedang difokus ikut
     * dilepas bersama pesannya, jadi fokus lebih dulu dipindah ke #konten tanpa menggulir,
     * seperti toast; tanpa itu fokus jatuh ke body dan pembaca layar kehilangan posisinya.
     */
    const tutup = () => {
        document.getElementById('konten')?.focus({ preventScroll: true });
        setTampil(false);
    };

    if (!tampil) {
        return null;
    }

    return (
        <div ref={wadah} className="pointer-events-none fixed inset-x-0 top-[calc(var(--kepala-atas)+env(safe-area-inset-top,0px)+0.5rem)] z-40 px-4">
            <div className="pointer-events-auto relative mx-auto max-w-xl">
                {jenis === 'versi' ? (
                    <Alert variant="info" title={`Versi baru ${merek.nama} tersedia`} className="pe-14 shadow-overlay">
                        <p>Halaman ini masih memakai versi lama, jadi sebagian isinya tidak dapat diperbarui. Muat ulang halaman sesudah isian Anda tersimpan.</p>
                        <Button variant="secondary" size="sm" className="mt-3" onClick={() => muatPenuhVersiBaru()}>
                            Muat ulang halaman
                        </Button>
                    </Alert>
                ) : (
                    <Alert variant="warning" title="Tidak tersambung ke server" className="pe-14 shadow-overlay">
                        <p>Aksi terakhir Anda mungkin belum tercatat. Periksa hasilnya setelah sinyal kembali, sebelum mengirim ulang.</p>
                        <Button
                            variant="secondary"
                            size="sm"
                            className="mt-3"
                            onClick={() => {
                                tutup();
                                router.reload();
                            }}
                        >
                            Muat ulang halaman
                        </Button>
                    </Alert>
                )}
                <TombolIkon ikon="x" label="Tutup pesan" varian="polos" className="absolute end-1 top-1" onClick={tutup} />
            </div>
        </div>
    );
}
