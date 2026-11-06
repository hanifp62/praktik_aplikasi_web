import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import type { HasilShell } from '@/luring/alur';
import { kalimatShell } from '@/luring/periksa';
import { pantauShellPaketDiSini, pulihkanShellPaket } from '@/luring/shellPaket';

const SIAP = 'Halaman luring tersimpan di perangkat ini.';

/**
 * Pemeriksaan shell luring untuk muat daring yang melihat paket "Siap luring" (ruling W6-9n,
 * W6-10e): sekali saat paket terlihat, lalu lagi setiap kali service worker pengendali berganti
 * selama halaman terbuka (deploy yang berbalapan dengan penyimpanan shell paket menghapus cache
 * yang baru diperiksa). Shell yang hilang disiapkan sekali lalu diperiksa lagi. `siapkanUlang`
 * mengulang hal yang sama atas ketukan pendaki dan mengembalikan hasilnya; keadaan lama tetap tampil
 * sampai hasil baru datang.
 */
export function useShellPaket(aktif: boolean): { keadaan: HasilShell | null; siapkanUlang: () => Promise<HasilShell> } {
    const [keadaan, setKeadaan] = useState<HasilShell | null>(null);

    const siapkanUlang = useCallback(async () => {
        const hasil = await pulihkanShellPaket().catch((): HasilShell => 'gagal');

        setKeadaan(hasil);

        return hasil;
    }, []);

    useEffect(() => {
        if (!aktif) {
            return;
        }

        void siapkanUlang();

        return pantauShellPaketDiSini({ hasil: setKeadaan });
    }, [aktif, siapkanUlang]);

    return { keadaan: aktif ? keadaan : null, siapkanUlang };
}

/**
 * Kabar shell luring di mode lapangan daring untuk paket yang sudah di perangkat (ruling W6-9n,
 * W6-10e). Bila belum tersimpan, pendaki membaca kalimatnya dengan satu tindakan "Siapkan ulang".
 * Kalimat ada di paragraf yang dapat difokus; kabar sesudah ketukan, dan perubahan sesudah service
 * worker berganti, dibacakan lewat wilayah role="status" yang selalu terpasang. Hasil pertama saat
 * dipasang tidak dibacakan. Tombol yang lepas karena shell kembali tersimpan menyerahkan fokus ke
 * paragraf itu, hanya bila fokus sudah jatuh ke body (W6-3r).
 *
 * Dimuat malas oleh Trips/Hike (tinjauan Tugas 10 m-1), supaya pramuat mode lapangan dan shell
 * paket tidak membawa modul ini bersama pemeriksaan shell-nya.
 */
export function KabarShellPaket() {
    const { keadaan, siapkanUlang } = useShellPaket(true);
    const [kabar, setKabar] = useState('');
    const kalimat = useRef<HTMLParagraphElement>(null);
    const berjalan = useRef(false);
    const pernahBelum = useRef(false);
    const hasilPertama = useRef(true);
    const belum = keadaan === 'gagal' || keadaan === 'tanpa-sw' ? keadaan : null;
    const teks = belum ? kalimatShell(belum, 'paket-ada') : keadaan === 'siap' && pernahBelum.current ? SIAP : '';

    useLayoutEffect(() => {
        if (keadaan === null) {
            return;
        }

        if (keadaan === 'siap' && pernahBelum.current && (document.activeElement === null || document.activeElement === document.body)) {
            kalimat.current?.focus();
        }

        if (hasilPertama.current) {
            hasilPertama.current = false;
        } else if (!berjalan.current) {
            setKabar(teks);
        }

        if (keadaan !== 'siap') {
            pernahBelum.current = true;
        }
    }, [keadaan]);

    const ulang = async () => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;
        setKabar('Menyiapkan halaman luring di peramban ini.');

        try {
            const hasil = await siapkanUlang();

            setKabar(hasil === 'siap' ? SIAP : kalimatShell(hasil, 'paket-ada'));
        } finally {
            berjalan.current = false;
        }
    };

    return (
        <div className="space-y-2">
            <p ref={kalimat} tabIndex={-1} className={kelas('text-kecil text-primary', fokusTerlihat)}>
                {teks}
            </p>
            <p role="status" aria-atomic="true" className="sr-only">
                {kabar}
            </p>
            {belum ? (
                <Button variant="secondary" size="sm" onClick={() => void ulang()}>
                    Siapkan ulang
                </Button>
            ) : null}
        </div>
    );
}
