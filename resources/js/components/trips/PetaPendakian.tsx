import { useEffect, useRef } from 'react';
import { kotakDari, tambahPenanda, usePetaDasar, type Peta, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import type { GeometriJalur, Penanda } from './types';

interface PetaPendakianProps {
    id: string;
    label: string;
    geometri: GeometriJalur | null;
    penanda: Penanda[];
    posisi: { lat: number; lng: number } | null;
}

/**
 * Peta mode pendakian: garis jalur, pos, dan penanda posisi pendaki. Padanan
 * non-visualnya adalah daftar pos yang selalu menyertai peta ini.
 *
 * Geometri dan pos tidak berubah selama halaman terbuka, jadi peta dibangun sekali.
 * Setiap posisi baru hanya memindahkan penandanya; tampilan tidak digeser otomatis
 * supaya tetap fokus pada jalur, kecuali jalurnya sendiri belum punya apa pun untuk
 * dibingkai.
 */
export function PetaPendakian({ id, label, geometri, penanda, posisi }: PetaPendakianProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const tandaPosisi = useRef<import('maplibre-gl').Marker | null>(null);
    const posisiTerbaru = useRef(posisi);
    const awal = useRef({ geometri, penanda });

    posisiTerbaru.current = posisi;

    const pasangPosisi = (map: Peta, maplibregl: Pustaka) => {
        const p = posisiTerbaru.current;

        if (!p) {
            return;
        }

        if (tandaPosisi.current) {
            tandaPosisi.current.setLngLat([p.lng, p.lat]);
        } else {
            const el = document.createElement('div');
            el.className = 'size-5 rounded-full border-4 border-surface bg-brand-700 shadow-overlay';
            el.title = 'Posisi Anda';
            tandaPosisi.current = new maplibregl.Marker({ element: el }).setLngLat([p.lng, p.lat]).addTo(map);
        }

        if (!awal.current.geometri && awal.current.penanda.length === 0) {
            map.jumpTo({ center: [p.lng, p.lat], zoom: 14 });
        }
    };

    const pertama = penanda[0];
    const { peta, pustaka, gagal } = usePetaDasar(wadah, {
        pusat: pertama ? [pertama.lng, pertama.lat] : null,
        // Posisi dipasang begitu peta ada, tidak menunggu tile: di sinyal tipis basemap
        // bisa lama, sedangkan posisi pendaki sudah diketahui. Penanda lama ikut hilang
        // bersama peta sebelumnya.
        saatDibuat: (map, maplibregl) => {
            tandaPosisi.current = null;
            pasangPosisi(map, maplibregl);
        },
        saatSiap: (map, maplibregl) => {
            const { geometri: garis, penanda: pos } = awal.current;

            if (garis && garis.coordinates.length > 1) {
                map.addSource('jalur', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: garis.coordinates } } });

                // Tepi --surface di bawah garis, seperti Jelajah: basemap tetap terang di tema
                // gelap, dan garis hijau muda tanpa tepi hampir tak terlihat di atasnya.
                const tepi = warnaToken('--surface', '#ffffff');
                map.addLayer({ id: 'garis-jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': 7 } });
                map.addLayer({ id: 'garis-jalur', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'line-width': 4 } });

                // Dibingkai ke jalurnya, bukan dibiarkan pada titik tengah Indonesia.
                map.fitBounds(kotakDari(maplibregl, garis.coordinates), { padding: 40, duration: 0 });
            }

            for (const titik of pos) {
                tambahPenanda(map, maplibregl, titik);
            }
        },
    });

    useEffect(() => {
        if (peta.current && pustaka.current) {
            pasangPosisi(peta.current, pustaka.current);
        }
    }, [posisi?.lat, posisi?.lng]);

    // Bila peta gagal, kotak setinggi setengah layar tidak dipertahankan: di lapangan
    // daftar checkpoint di bawahnya yang dipakai, dan ia tidak boleh terdorong menjauh.
    const ukuran = gagal ? undefined : 'h-[55dvh] min-h-72';

    return (
        <div className="space-y-1">
            <WadahPeta wadahRef={wadah} id={id} label={label} gagal={gagal} ukuran={ukuran} cadangan="Keterangan jalur dan daftar pos di bawah tetap dapat dipakai." />
            <span className="sr-only">{penanda.map((p) => p.label).join(', ')}</span>
        </div>
    );
}
