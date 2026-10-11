import { useMemo, useRef } from 'react';
import { kotakDari, tambahPenanda, usePetaDasar, type Titik } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import type { Penanda } from '@/components/trails/types';
import type { GarisGeoJson } from './types';

interface PetaGarisProps {
    id: string;
    label: string;
    markers: Penanda[];
    geometry?: GarisGeoJson | null;
    height?: string;
}

/**
 * Peta dengan garis (jalur resmi atau jejak pendaki) dan penanda pos, dibingkai ke
 * isinya. Padanan non-visualnya adalah daftar pos yang selalu menyertainya. Tanpa garis
 * maupun penanda tidak ada yang digambar: wadah kosong terbaca sebagai peta yang gagal.
 */
export function PetaGaris({ id, label, markers, geometry = null, height = 'h-80' }: PetaGarisProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const adaIsi = geometry !== null || markers.length > 0;
    const titik: Titik[] = [...(geometry?.coordinates ?? []), ...markers.map((m): Titik => [m.lng, m.lat])];
    const kunci = useMemo(() => JSON.stringify([geometry, markers]), [geometry, markers]);

    const { gagal } = usePetaDasar(wadah, {
        pusat: titik[0],
        kunci,
        saatSiap: (peta, maplibregl) => {
            if (geometry) {
                peta.addSource('garis', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry } });

                // Tepi --surface di bawah garis, seperti Jelajah: basemap tetap terang di tema
                // gelap, dan garis hijau muda tanpa tepi hampir tak terlihat di atasnya.
                const tepi = warnaToken('--surface', '#ffffff');
                peta.addLayer({ id: 'garis-tepi', type: 'line', source: 'garis', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': 7 } });
                peta.addLayer({ id: 'garis', type: 'line', source: 'garis', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'line-width': 4 } });
            }

            for (const p of markers) {
                tambahPenanda(peta, maplibregl, p);
            }

            // Dibingkai ke isinya: peta yang terbuka di laut Jawa memaksa pembaca mencari
            // jalurnya sendiri sebelum dapat membacanya.
            if (titik.length > 1) {
                peta.fitBounds(kotakDari(maplibregl, titik), { padding: 40, duration: 0, maxZoom: 14 });
            }
        },
    });

    if (!adaIsi) {
        return null;
    }

    return (
        <div className="space-y-1">
            <WadahPeta wadahRef={wadah} id={id} label={label} gagal={gagal} ukuran={height} cadangan="Keterangan jalur dan daftar pos di bawah tetap dapat dipakai." />
            <span className="sr-only">{markers.map((m) => m.label).filter(Boolean).join(', ')}</span>
        </div>
    );
}
