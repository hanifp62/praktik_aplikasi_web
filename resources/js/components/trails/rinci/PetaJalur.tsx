import { useMemo, useRef } from 'react';
import { kotakDari, tambahPenanda, usePetaDasar, type Titik } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import type { Penanda } from '@/components/trails/types';
import type { PetaJalurData } from './types';

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
 * Peta rinci jalur: garis jalur beserta titik awal dan akhirnya, dan penanda pos.
 * Padanan non-visualnya daftar penanda di bawah (sr-only) dan daftar pos di halaman yang
 * sama.
 */
export function PetaJalur({ id, data, label, cadangan = 'Keterangan jalur dan daftar pos di bawah tetap dapat dipakai.' }: PetaJalurProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const garis = titikGaris(data);
    const awal = garis[0];
    const akhir = garis.length > 1 ? garis[garis.length - 1] : undefined;

    const penanda: Array<Penanda & { ujung?: boolean }> = [
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
            const warna = warnaToken('--brand-ink', 'rgb(4, 120, 87)');

            if (data.geometry) {
                peta.addSource('jalur', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: data.geometry } });
                peta.addLayer({ id: 'garis-jalur', type: 'line', source: 'jalur', paint: { 'line-color': warna, 'line-width': 3 } });
            }

            // Dibingkai ke jalurnya, bukan dibiarkan pada titik tengah Indonesia.
            const semua = garis.length > 1 ? garis : penanda.map((p): Titik => [p.lng, p.lat]);
            if (semua.length > 1) {
                peta.fitBounds(kotakDari(maplibregl, semua), { padding: 40, duration: 0 });
            }

            for (const p of penanda) {
                tambahPenanda(peta, maplibregl, p, p.ujung ? warna : undefined);
            }
        },
    });

    return (
        <div className="space-y-1">
            <WadahPeta wadahRef={wadah} id={id} label={label} gagal={gagal} ukuran="h-96" cadangan={cadangan} />
            <span className="sr-only">{penanda.map((p) => p.label).join(', ')}</span>
        </div>
    );
}
