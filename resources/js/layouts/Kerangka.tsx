import { router } from '@inertiajs/react';
import { createContext, useContext, useEffect, useMemo, useRef, type RefObject } from 'react';
import type { PemilikToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';

type OpsiMuatUlang = NonNullable<Parameters<typeof router.reload>[0]>;

let muatUlangKerangkaBerjalan = 0;
let memuatPenuh = false;

/**
 * Muat ulang async milik kerangka (Ruling W2-F6, dipersempit W2-F7): hanya muat ulang lembar
 * Pengingat. Lembar itu meminta isi terbaru atas permintaan pengguna di Beranda dan tidak
 * membawa isian, jadi konflik versi aset yang menjawabnya boleh dimuat penuh oleh
 * PesanJaringan. Tombol "Muat ulang halaman" di pesan jaringan sengaja tidak memakainya: pesan
 * itu paling sering muncul sesudah formulir yang putus, dan konfliknya cukup memunculkan
 * pemberitahuan versi baru supaya isian yang belum dikirim tidak hilang.
 *
 * Penghitung naik di onStart kunjungan itu dan turun di onFinish, hanya bila onStart sudah
 * terjadi, jadi keduanya selalu berpasangan. Menaikkannya sebelum router.reload() membuatnya
 * bocor: kunjungan yang dibatalkan onBefore atau event before
 * (node_modules/@inertiajs/core/dist/index.js baris 3204) tidak pernah sampai ke onStart maupun
 * onFinish. onStart terpancar di awal kiriman (baris 2914-2915), sebelum event location pada
 * jawabannya (baris 2591), dan onFinish sesudahnya (baris 2971-2972, juga untuk kunjungan
 * batal). Callback pemanggil tetap berjalan.
 *
 * Muat ulang milik halaman tetap memanggil router.reload(), sehingga konflik versinya hanya
 * memunculkan pemberitahuan versi baru (ShellFeedbackTest).
 */
export function muatUlangMilikKerangka(opsi: OpsiMuatUlang = {}): void {
    let dimulai = false;

    router.reload({
        ...opsi,
        onStart: (kunjungan) => {
            dimulai = true;
            muatUlangKerangkaBerjalan += 1;
            opsi.onStart?.(kunjungan);
        },
        onFinish: (kunjungan) => {
            if (dimulai) {
                muatUlangKerangkaBerjalan -= 1;
            }

            opsi.onFinish?.(kunjungan);
        },
    });
}

/** Benar selagi setidaknya satu muat ulang milik kerangka sedang berjalan. */
export function adaMuatUlangMilikKerangka(): boolean {
    return muatUlangKerangkaBerjalan > 0;
}

/**
 * Muat penuh karena versi aset baru, satu-satunya jalan kerangka memanggil
 * window.location.reload(): dari konflik versi yang menjawab muat ulang milik kerangka, atau
 * dari tombol pemberitahuan versi baru atas pilihan pengguna (Ruling W2-F7, B-4). Penandanya
 * menyala sampai dokumen berganti, supaya jaring onFinish lembar Pengingat, yang berjalan
 * sesudah event location, tidak menampilkan dan mengumumkan galat jaringan yang keliru.
 */
export function muatPenuhVersiBaru(): void {
    memuatPenuh = true;
    window.location.reload();
}

/** Benar sejak muatPenuhVersiBaru() dipanggil sampai dokumen berganti. */
export function sedangMemuatPenuh(): boolean {
    return memuatPenuh;
}

/**
 * Layanan kerangka yang dipasang sekali di AppLayout dan dipakai halaman (rencana induk W2
 * butir 5 dan 6): toast urungkan dengan satu wilayah pengumuman untuk seluruh aplikasi, dan
 * lembar Pengingat milik kerangka. Lembar bawah milik halaman tidak perlu wadah: LembarBawah
 * adalah dialog asli yang tinggal di top layer peramban.
 */
export interface LayananKerangka {
    /**
     * Toast urungkan primitif W1 (bertahan 8 detik, berhenti selama disentuh atau difokus,
     * W1-K7). Tanda tangannya diambil dari useToastUrungkan, jadi opsi yang ditambah W1 ikut
     * tanpa disalin di sini.
     *
     * Urungkan berlaku selama komponen yang memanggil tampilkanToast masih terpasang (ruling
     * W2-5i); lihat useKerangka. Karena itu panggil useKerangka di komponen yang bertahan
     * selama Urungkan masuk akal, biasanya halaman, bukan di baris atau kartu yang ikut hilang
     * oleh aksinya: toast milik baris yang dilepas ikut tertutup.
     */
    tampilkanToast: ReturnType<typeof useToastUrungkan>['tampilkan'];
    /**
     * Buka lembar Pengingat; isinya dimuat saat itu juga. Pemanggil memberi tombol pemicunya
     * supaya fokus kembali ke sana saat lembar ditutup, juga di Safari yang tidak memfokus
     * tombol yang diklik.
     */
    bukaPengingat: (pemicu: HTMLElement) => void;
}

/**
 * Nilai KonteksKerangka yang dipasang AppLayout: layanan kerangka yang sama untuk semua
 * komponen, kecuali toast, yang di sini selalu membawa pemilik. Hanya useKerangka yang
 * membacanya, jadi halaman tidak pernah memunculkan toast tanpa pemilik.
 */
export interface NilaiKerangka extends Omit<LayananKerangka, 'tampilkanToast'> {
    tampilkanToastMilik: ReturnType<typeof useToastUrungkan>['tampilkanMilik'];
    tutupToastMilik: ReturnType<typeof useToastUrungkan>['tutupMilik'];
}

export const KonteksKerangka = createContext<NilaiKerangka | null>(null);

/**
 * Hanya di halaman yang memakai AppLayout. Halaman tamu dan mode lapangan tidak punya
 * kerangka ini, dan galat yang menyebutnya lebih jujur daripada toast yang diam-diam hilang.
 *
 * Setiap komponen pemanggil mendapat satu pemilik toast (ruling W2-5i), dan saat komponen itu
 * dilepas toast miliknya ditutup; toast milik komponen lain tidak disentuh. Pindah halaman
 * dan kunjungan tanpa preserveState, termasuk kunjungan ulang ke URL yang sama dan kunjungan
 * replace, melepas halaman lama, jadi toast-nya tertutup. Muat ulang parsial dan kunjungan
 * preserveState ke komponen yang sama tidak melepasnya (router.post, put, patch, dan delete
 * memakai preserveState secara bawaan), jadi toast-nya bertahan, termasuk toast yang
 * dimunculkan dari onSuccess kunjungan itu. Ikatan ini mengikuti umur komponen, bukan event
 * router: Inertia tidak memancarkan navigate untuk kunjungan replace, termasuk kunjungan
 * ulang ke URL yang sama, walau halamannya dipasang ulang.
 *
 * Inertia menjalankan onSuccess sesudah halaman baru terpasang, jadi toast yang diminta dari
 * onSuccess kunjungan yang melepas pemanggilnya datang dari komponen yang sudah tidak ada.
 * Toast itu tidak dimunculkan, dan Urungkan yang ditekan sesudah pemiliknya dilepas tidak
 * menjalankan apa pun, karena keduanya hanya dapat memanggil closure milik instance lama.
 * Toast untuk aksi yang memindah halaman dimunculkan halaman tujuannya.
 */
export function useKerangka(): LayananKerangka {
    const kerangka = useContext(KonteksKerangka);
    const pemilik = useRef<PemilikToast>({ terpasang: true });
    const tutupToastMilik = kerangka?.tutupToastMilik;

    useEffect(() => {
        const milik = pemilik.current;
        milik.terpasang = true;

        return () => {
            milik.terpasang = false;
            tutupToastMilik?.(milik);
        };
    }, [tutupToastMilik]);

    const layanan = useMemo(() => (kerangka === null ? null : ikatKePemilik(kerangka, pemilik)), [kerangka]);

    if (layanan === null) {
        throw new Error('useKerangka hanya dapat dipakai di halaman yang memakai AppLayout.');
    }

    return layanan;
}

/**
 * Layanan kerangka untuk satu komponen: toast diikat ke pemiliknya, layanan lain diteruskan
 * apa adanya. Pemilik dibaca saat toast diminta, bukan saat render.
 */
function ikatKePemilik({ tampilkanToastMilik, tutupToastMilik: _tutupToastMilik, ...lainnya }: NilaiKerangka, pemilik: RefObject<PemilikToast>): LayananKerangka {
    return {
        ...lainnya,
        tampilkanToast: (pesan, onUrungkan, opsi) => {
            const milik = pemilik.current;

            if (!milik.terpasang) {
                if (import.meta.env.DEV) {
                    console.warn('Toast diminta sesudah komponen pemiliknya dilepas, jadi tidak dimunculkan. Panggil useKerangka di komponen yang bertahan, atau munculkan toast dari halaman tujuan lewat data flash.');
                }

                return;
            }

            tampilkanToastMilik(
                milik,
                pesan,
                () => {
                    if (milik.terpasang) {
                        onUrungkan();
                    }
                },
                opsi,
            );
        },
    };
}
