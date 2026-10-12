import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { BilahTab } from '@/components/ui/BilahTab';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { WadahToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import { usePenjagaPerangkat } from '@/luring/keluar';
import { usePengirimJejak } from '@/luring/kirimJejak';
import type { PropsBersama } from '@/types';
import { KepalaLebar } from './KepalaLebar';
import { KonteksKerangka, type NilaiKerangka } from './Kerangka';
import { itemTab } from './NavLinks';
import { LembarPengingat } from './Pengingat';
import { PesanJaringan } from './PesanJaringan';
import { StripPendakian } from './StripPendakian';

interface AppLayoutProps {
    /**
     * Bawaan true. Halaman tugas yang membawa bar aksi tetap (detail jalur, penilaian
     * kecocokan, rencanakan trip) memberi false: bilah tab disembunyikan dan tombol kembali
     * di kepala halaman menjadi jalan keluarnya (spec v3 bagian 5.1).
     */
    bilahTab?: boolean;
    children: ReactNode;
}

/**
 * Kerangka aplikasi (spec v3 bagian 5; DK-33). Di bawah 1024 px tidak ada bilah atas
 * global: setiap halaman membawa kepalanya sendiri (PageHeader) dan navigasi utama adalah
 * bilah tab pil di bawah. Mulai 1024 px header 68 px dengan navigasi segmen menggantikan
 * bilah tab. Keduanya digambar dari satu daftar (itemTab), dan tab aktif dihitung dari URL
 * halaman Inertia, bukan dari window, supaya sama di server, pratinjau, dan peramban.
 *
 * Layout ini persisten di antara kunjungan Inertia, jadi wadah toast, lembar Pengingat, dan
 * pesan jaringan dipasang di sini sekali: satu wilayah pengumuman untuk seluruh aplikasi, dan
 * halaman memintanya lewat useKerangka(), bukan memasang wadahnya sendiri.
 *
 * Urungkan berlaku selama komponen yang memanggil tampilkanToast masih terpasang (ruling
 * W2-5i). Layout hanya memegang keadaan toast; useKerangka mengikat setiap toast ke komponen
 * pemanggilnya dan menutupnya saat komponen itu dilepas, jadi layout tidak mendengarkan
 * event navigasi untuk menutup toast.
 *
 * Lembar Pengingat tetap <dialog> modal yang menutupi halaman (Ruling W2-6a #1): tautan di
 * dalamnya membawa pengguna ke halaman baru tanpa menutupnya lebih dulu, jadi kunjungan
 * SUNGGUHAN (bukan muat ulang lembar itu sendiri, yang async) menutupnya di sini.
 *
 * Diperbarui Ruling W2-6e #1: navigasi mundur/maju peramban (popstate) tidak memancarkan
 * start sama sekali, hanya navigate, jadi navigate ditambahkan sebagai penjaga kedua. Muat
 * ulang lembar ke URL yang sama diperlakukan Inertia sebagai replace dan tidak memancarkan
 * navigate, jadi penjaga ini tidak menutup lembar karena muat ulangnya sendiri.
 *
 * Konflik versi aset (perbaikan akhir W2, I-2; dibatasi Ruling W2-F6 dan W2-F7) ditangani
 * PesanJaringan, bukan layout ini. Inertia 3 memuat penuh halaman sendiri untuk kunjungan
 * sinkron, tetapi mengabaikan konflik itu pada kunjungan async. Hanya muat ulang lembar
 * Pengingat, yang ditandai muatUlangMilikKerangka di Kerangka.tsx, yang dimuat penuh. Kunjungan
 * async lain, termasuk tombol "Muat ulang halaman" di pesan jaringan, hanya memunculkan
 * pemberitahuan versi baru, supaya isian yang belum dikirim tidak hilang diam-diam.
 */
export function AppLayout({ bilahTab = true, children }: AppLayoutProps) {
    const { url, props } = usePage<PropsBersama>();
    const jalur = url.split('?')[0] ?? '';
    const tab = itemTab(jalur);
    const { toast, tutup, tampilkanMilik, tutupMilik } = useToastUrungkan();
    const [pengingatTerbuka, setPengingatTerbuka] = useState(false);
    const pemicuPengingat = useRef<HTMLElement | null>(null);
    const bukaPengingat = useCallback((pemicu: HTMLElement) => {
        pemicuPengingat.current = pemicu;
        setPengingatTerbuka(true);
    }, []);
    // Data perangkat akun lain di peramban ini dihapus sebelum dipakai (W6, ruling W6-R10).
    usePenjagaPerangkat();
    // Titik jejak dikirim sesudah pendakian berakhir, hanya dari halaman berkerangka ini, tidak
    // dari mode lapangan atau shell luring (ruling W6-P2a).
    usePengirimJejak();
    const layanan = useMemo<NilaiKerangka>(() => ({ tampilkanToastMilik: tampilkanMilik, tutupToastMilik: tutupMilik, bukaPengingat }), [tampilkanMilik, tutupMilik, bukaPengingat]);

    useEffect(() => {
        const lepasMulai = router.on('start', (event) => {
            if (!event.detail.visit.async) {
                setPengingatTerbuka(false);
            }
        });
        const lepasNavigasi = router.on('navigate', () => setPengingatTerbuka(false));

        return () => {
            lepasMulai();
            lepasNavigasi();
        };
    }, []);

    return (
        <KonteksKerangka value={layanan}>
            <div className="min-h-dvh bg-canvas">
                <JudulHalaman />
                <KepalaLebar item={tab} />
                <PesanJaringan />
                <main id="konten" tabIndex={-1} className="pt-(--pesan-jaringan,0px) pb-(--nav-bawah)">
                    {children}
                </main>
                {props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}
                {bilahTab ? <BilahTab item={tab} /> : null}
                <WadahToast toast={toast} onTutup={tutup} />
                <LembarPengingat terbuka={pengingatTerbuka} onTutup={() => setPengingatTerbuka(false)} pemicu={pemicuPengingat} />
            </div>
        </KonteksKerangka>
    );
}
