import { useLayoutEffect, useRef } from 'react';
import { Button } from '@/components/ui/Button';
import { compare, saved } from '@/routes/trails';

/**
 * Baki simpanan: yang ditunjukkan namanya, bukan jumlahnya, karena yang ingin diketahui
 * pendaki "apa saja yang sudah saya simpan". Tautan Bandingkan TELANJANG (tanpa id)
 * supaya halaman perbandingan masuk mode timbangan-tersimpan yang menulis balik.
 *
 * Menempel tepat di atas lapisan bawah kerangka (--nav-bawah: bilah tab ditambah strip
 * pendakian). Safe area tidak ditanggung baki sendiri, karena --nav-bawah selalu sudah
 * memuatnya: lewat tinggi bilah tab di bawah 1024 px, atau lewat --bilah-tab yang bernilai
 * safe area mulai 1024 px dan di halaman tanpa bilah tab (app.css).
 *
 * Nama jalur yang panjang membungkus, jadi tinggi baki tidak tetap. Selagi tampil, baki
 * mengukur dirinya dan menulis tingginya ke --baki-banding di :root (perbaikan akhir W2,
 * I-1a), pola yang sama dengan StripPendakian dan BarAksi: scroll-padding-bottom di app.css
 * membacanya supaya fokus papan ketik tidak berhenti di balik baki (WCAG 2.4.11), dan toast
 * duduk di atasnya. Variabelnya dihapus begitu baki hilang atau dilepas.
 */
export function ConsiderationTray({ trails, batas }: { trails: Array<{ id: number; name: string }>; batas: number }) {
    const wadah = useRef<HTMLDivElement>(null);
    const tampil = trails.length > 0;

    useLayoutEffect(() => {
        const elemen = wadah.current;
        const akar = document.documentElement;

        if (!elemen) {
            return;
        }

        const ukur = () => akar.style.setProperty('--baki-banding', `${elemen.offsetHeight}px`);
        const pengamat = new ResizeObserver(ukur);

        ukur();
        pengamat.observe(elemen);

        return () => {
            pengamat.disconnect();
            akar.style.removeProperty('--baki-banding');
        };
    }, [tampil]);

    if (!tampil) {
        return null;
    }

    return (
        <div ref={wadah} className="sticky bottom-[var(--nav-bawah,0px)] z-30 border-t border-subtle bg-surface/95 px-4 pb-3 pt-3 backdrop-blur">
            <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-2">
                <p className="text-sm text-secondary">
                    Tersimpan ({trails.length}/{batas}): <span className="font-medium text-primary">{trails.map((t) => t.name).join(', ')}</span>
                </p>
                <Button size="sm" variant="secondary" href={saved.url()}>Lihat daftar</Button>
                {trails.length >= 2 ? <Button size="sm" href={compare.url()}>Bandingkan {trails.length} jalur</Button> : null}
            </div>
        </div>
    );
}
