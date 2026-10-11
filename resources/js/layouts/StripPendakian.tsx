import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { PendakianAktif } from '@/types';

/**
 * Lama berjalan dalam jam dan menit, bentuk yang sama dengan Durasi::panjang di server
 * ("2 jam 15 menit"). Jam perangkat yang tertinggal dari server tidak pernah menghasilkan
 * waktu negatif.
 */
export function lamaBerjalan(mulai: string, sekarang: number): string {
    const menit = Math.max(0, Math.floor((sekarang - Date.parse(mulai)) / 60_000));
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;

    if (jam === 0) {
        return `${sisa} menit`;
    }

    return sisa === 0 ? `${jam} jam` : `${jam} jam ${sisa} menit`;
}

/**
 * Strip pendakian aktif (spec v3 bagian 5.1; riset D4): selama trip berlangsung, strip
 * 48 px di atas bilah tab di setiap halaman berkerangka membawa pendaki kembali ke mode
 * lapangan dengan satu ketukan. Seluruh strip satu tautan; "Buka" hanya penanda bentuknya.
 * Isian merek yang solid, bukan kaca: bilah tab dan bar aksi sudah memakai dua jatah kaca
 * per layar. Waktu berjalan diperbarui tiap menit tanpa diumumkan, supaya pembaca layar
 * tidak diganggu setiap menit.
 *
 * Nama jalur dan pos yang panjang membungkus, jadi tinggi strip tidak tetap. Strip mengukur
 * dirinya dan menulis tingginya (ditambah jarak 8 px) ke --strip-pendakian di :root, sehingga
 * bar aksi, baki banding, dan toast selalu duduk di atasnya; nilai 3,5rem di app.css hanya
 * cadangan sebelum skrip berjalan.
 */
export function StripPendakian({ pendakian }: { pendakian: PendakianAktif }) {
    const [sekarang, setSekarang] = useState(() => Date.now());
    const wadah = useRef<HTMLElement>(null);

    useLayoutEffect(() => {
        const elemen = wadah.current;
        const akar = document.documentElement;

        if (!elemen) {
            return;
        }

        const ukur = () => akar.style.setProperty('--strip-pendakian', `${elemen.offsetHeight + 8}px`);
        const pengamat = new ResizeObserver(ukur);

        ukur();
        pengamat.observe(elemen);

        return () => {
            pengamat.disconnect();
            akar.style.removeProperty('--strip-pendakian');
        };
    }, []);

    useEffect(() => {
        const pewaktu = window.setInterval(() => setSekarang(Date.now()), 60_000);

        return () => window.clearInterval(pewaktu);
    }, []);

    const keterangan = [
        pendakian.posBerikutnya ? `Menuju ${pendakian.posBerikutnya}` : 'Pos berikutnya belum tercatat',
        pendakian.mulai ? `berjalan ${lamaBerjalan(pendakian.mulai, sekarang)}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <aside ref={wadah} data-strip-pendakian="" aria-label="Pendakian berlangsung" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--bilah-tab)+0.5rem)] z-30 px-4">
            <Tautan
                href={pendakian.url}
                className={kelas('pointer-events-auto mx-auto flex min-h-12 max-w-md items-center gap-3 rounded-tombol bg-brand-700 px-4 py-1.5 text-on-brand shadow-overlay', fokusTerlihat)}
            >
                <Icon name="person-simple-hike" size={22} />
                <span className="min-w-0 flex-1">
                    <span className="sr-only">Pendakian berlangsung: </span>
                    <span className="block break-words text-kecil font-bold">{pendakian.jalur}</span>
                    <span className="block text-meta">{keterangan}</span>
                </span>
                <span className="shrink-0 text-kecil font-bold">Buka</span>
            </Tautan>
        </aside>
    );
}
