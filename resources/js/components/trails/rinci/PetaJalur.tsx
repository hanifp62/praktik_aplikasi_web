import { useMemo, useRef } from 'react';
import { kotakDari, tambahPenanda, usePetaDasar, type Titik } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import type { Penanda } from '@/components/trails/types';
import type { AksenPos, PetaJalurData } from './types';

/**
 * Penanda pos bernomor, disusun lewat textContent. Pos berkemah dan tujuan membawa aksen
 * kuning (DESIGN.md, satu aksen), selalu bersama nomornya dan nama pos di keterangan penanda.
 *
 * Nama dan perannya ditulis sendiri: MapLibre 6 hanya memberi aria-label dan peran kepada penanda
 * bawaannya, bukan kepada elemen buatan seperti ini (MapLibre 4 memberi semua penanda "Penanda
 * peta"). Penanda ini membuka keterangan lewat Enter, jadi perannya tombol, sama dengan penanda
 * bawaan MapLibre 6 yang berpopup, dan namanya label pos yang juga ditulis di keterangannya.
 */
function elemenPos(urutan: number, aksen: AksenPos, label: string): HTMLElement {
    const el = document.createElement('span');
    el.setAttribute('role', 'button');
    el.setAttribute('aria-label', label);
    el.className = aksen
        ? 'flex size-7 items-center justify-center rounded-full border-2 border-primary bg-aksen-pos text-meta font-bold text-aksen-pos-teks shadow-overlay'
        : 'flex size-7 items-center justify-center rounded-full border-2 border-brand-ink bg-surface text-meta font-bold text-brand-ink shadow-overlay';
    el.textContent = String(urutan);

    return el;
}

/** Titik-titik garis dalam urutan jalannya; MultiLineString disambung per bagian. */
function titikGaris(data: PetaJalurData): Titik[] {
    const g = data.geometry;

    if (!g) {
        return [];
    }

    return (g.type === 'LineString' ? g.coordinates : g.coordinates.flat()).map((t) => [t[0], t[1]] as Titik);
}

interface PetaJalurProps {
    id: string;
    data: PetaJalurData;
    label: string;
    /** Apa yang tetap dapat dipakai bila peta gagal dimuat, menurut halaman pemakainya. */
    cadangan?: string;
}

/**
 * Peta rinci jalur: garis jalur beserta titik awal dan akhirnya, dan penanda pos bernomor
 * yang sama dengan daftar pos (pos berkemah dan tujuan beraksen). Padanan non-visualnya
 * daftar penanda di bawah (sr-only) dan daftar pos di halaman yang sama.
 */
export function PetaJalur({ id, data, label, cadangan = 'Keterangan jalur dan daftar pos di bawah tetap dapat dipakai.' }: PetaJalurProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const garis = titikGaris(data);
    const awal = garis[0];
    const akhir = garis.length > 1 ? garis[garis.length - 1] : undefined;

    const penanda: Array<Penanda & { ujung?: boolean; urutan?: number; aksen?: AksenPos }> = [
        ...(awal ? [{ lng: awal[0], lat: awal[1], label: 'Awal jalur', ujung: true }] : []),
        ...data.penanda,
        ...(akhir ? [{ lng: akhir[0], lat: akhir[1], label: 'Akhir jalur', ujung: true }] : []),
    ];

    const pertama = penanda[0];
    const kunci = useMemo(() => JSON.stringify(data), [data]);

    const { gagal } = usePetaDasar(wadah, {
        pusat: pertama ? [pertama.lng, pertama.lat] : null,
        kunci,
        saatSiap: (peta, maplibregl) => {
            const warna = warnaToken('--brand-ink', 'rgb(35, 79, 50)');

            if (data.geometry) {
                peta.addSource('jalur', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: data.geometry } });

                // Tepi --surface di bawah garis, seperti Jelajah: basemap tetap terang di tema
                // gelap, dan garis hijau muda tanpa tepi hampir tak terlihat di atasnya.
                const tepi = warnaToken('--surface', '#ffffff');
                peta.addLayer({ id: 'garis-jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': 7 } });
                peta.addLayer({ id: 'garis-jalur', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warna, 'line-width': 4 } });
            }

            // Dibingkai ke jalurnya, bukan dibiarkan pada titik tengah Indonesia.
            const semua = garis.length > 1 ? garis : penanda.map((p): Titik => [p.lng, p.lat]);
            if (semua.length > 1) {
                peta.fitBounds(kotakDari(maplibregl, semua), { padding: 40, duration: 0 });
            }

            for (const p of penanda) {
                if (p.urutan !== undefined) {
                    new maplibregl.Marker({ element: elemenPos(p.urutan, p.aksen ?? null, p.label) })
                        .setLngLat([p.lng, p.lat])
                        .setPopup(new maplibregl.Popup({ offset: 18 }).setText(p.label))
                        .addTo(peta);
                } else {
                    tambahPenanda(peta, maplibregl, p, p.ujung ? warna : undefined);
                }
            }
        },
    });

    return (
        <div className="space-y-1">
            <WadahPeta wadahRef={wadah} id={id} label={label} gagal={gagal} ukuran="h-96" cadangan={cadangan} />
            {/* Pos berkoordinat tanpa garis: peta hanya menandai posnya, dan menunggu garis disebut,
                bukan dibiarkan terbaca seolah rutenya sudah tergambar (T6-M3, spec v3 bagian 6.4). */}
            {data.geometry ? null : (
                <p className="text-meta text-secondary">
                    <span className="font-bold text-primary">Garis rute belum dimasukkan.</span> Peta hanya menandai pos yang berkoordinat; menunggu kurator Memount memasukkan garisnya.
                </p>
            )}
            <span className="sr-only">{penanda.map((p) => p.label).join(', ')}</span>
        </div>
    );
}
