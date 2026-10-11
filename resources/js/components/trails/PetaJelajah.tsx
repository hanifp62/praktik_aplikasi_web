import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { kotakDari, usePetaDasar, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import { adalahHalamanReact } from '@/lib/halamanReact';
import { kelas } from '@/lib/kelas';
import type { BarisJalur, FiturPeta, FitRingkas } from './types';

interface PetaJelajahProps {
    fitur: FiturPeta[];
    baris: BarisJalur[];
    sorot: number | null;
    onLihatDiDaftar: (id: number) => void;
    className?: string;
}

function warnaFit(label: FitRingkas['label'] | undefined): string {
    if (label === 'COCOK') return warnaToken('--brand-700', '#234f32');
    if (label === 'PERLU_PERSIAPAN') return warnaToken('--warn-600', '#b7791f');
    if (label === 'KURANG_COCOK') return warnaToken('--danger-600', '#a33b2b');

    return warnaToken('--text-secondary', '#4e5551');
}

const batasDari = (maplibregl: Pustaka, fitur: FiturPeta[]) => kotakDari(maplibregl, fitur.flatMap((f) => f.geometri.coordinates));

/** Ringkasan jalur di peta, disusun dengan textContent (bukan innerHTML). */
function ringkasan(b: BarisJalur, lihatDiDaftar: () => void): HTMLElement {
    const akar = document.createElement('div');
    akar.className = 'space-y-1 text-sm text-primary';

    const judul = document.createElement('a');
    judul.href = b.url;
    judul.textContent = b.name;
    judul.className = 'block font-semibold text-primary underline underline-offset-2';
    judul.addEventListener('click', (e) => {
        if (adalahHalamanReact(b.url)) {
            e.preventDefault();
            router.visit(b.url);
        }
    });
    akar.appendChild(judul);

    const baris = (teks: string, kelas: string) => {
        const p = document.createElement('p');
        p.className = kelas;
        p.textContent = teks;
        akar.appendChild(p);
    };

    baris([b.mountain, b.province].filter(Boolean).join(' · '), 'text-xs text-secondary');
    baris([b.durasi, b.jarak, b.technical].filter(Boolean).join(' · '), 'text-xs text-secondary');

    const fit = b.fit;
    if (fit) {
        baris(`${fit.labelText}${fit.denganRencana ? ' untuk rencana ini' : ' (kecocokan dasar)'}`, 'text-xs font-medium text-primary');
    }

    const ke = document.createElement('button');
    ke.type = 'button';
    ke.className = 'mt-1 text-xs font-medium text-brand-ink underline underline-offset-2';
    ke.textContent = 'Lihat di daftar';
    ke.addEventListener('click', lihatDiDaftar);
    akar.appendChild(ke);

    return akar;
}

/**
 * Peta sebaran Jelajah: bentuk jalur, bukan titik gunung (keputusan F2).
 *
 * Petanya dibuat sekali dan hanya datanya yang diganti ketika saringan berubah, jadi
 * posisi dan zoom pembaca tidak hilang (features/01). Garis diwarnai menurut kecocokan
 * dari token, dan labelnya selalu tertulis di ringkasan: warna tidak pernah menjadi
 * satu-satunya pembawa arti (WCAG 1.4.1). Ringkasan disusun lewat textContent karena
 * nama jalur berasal dari masukan pengguna.
 */
export function PetaJelajah({ fitur, baris, sorot, onLihatDiDaftar, className }: PetaJelajahProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const sudahDipaskan = useRef(false);
    const sorotLama = useRef<number | null>(null);
    const barisRef = useRef(baris);
    const lihatRef = useRef(onLihatDiDaftar);

    barisRef.current = baris;
    lihatRef.current = onLihatDiDaftar;

    const { peta: instansi, pustaka, siap, gagal } = usePetaDasar(wadah, {
        saatSiap: (peta, maplibregl) => {
            const kosong = { type: 'FeatureCollection' as const, features: [] };
            peta.addSource('jalur', { type: 'geojson', data: kosong, promoteId: 'id' });
            peta.addSource('awal', { type: 'geojson', data: kosong, promoteId: 'id' });
            const tepi = warnaToken('--surface', '#ffffff');
            const tebal = (biasa: number, disorot: number) => ['case', ['boolean', ['feature-state', 'sorot'], false], disorot, biasa] as unknown as number;
            peta.addLayer({ id: 'jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': tebal(7, 10) } });
            peta.addLayer({ id: 'jalur-garis', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': ['get', 'warna'], 'line-width': tebal(4, 6) } });
            peta.addLayer({ id: 'jalur-awal', type: 'circle', source: 'awal', paint: { 'circle-radius': 6, 'circle-color': ['get', 'warna'], 'circle-stroke-color': tepi, 'circle-stroke-width': 2 } });

            const buka = (e: import('maplibre-gl').MapLayerMouseEvent) => {
                const id = Number(e.features?.[0]?.properties?.id);
                const b = barisRef.current.find((r) => r.id === id);
                if (!b) return;
                new maplibregl.Popup({ offset: 12, maxWidth: '280px' }).setLngLat(e.lngLat).setDOMContent(ringkasan(b, () => lihatRef.current(b.id))).addTo(peta);
            };
            for (const lapisan of ['jalur-garis', 'jalur-awal']) {
                peta.on('click', lapisan, buka);
                peta.on('mouseenter', lapisan, () => (peta.getCanvas().style.cursor = 'pointer'));
                peta.on('mouseleave', lapisan, () => (peta.getCanvas().style.cursor = ''));
            }
        },
    });

    useEffect(() => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (!siap || !peta || !maplibregl) return;

        const warna = new Map(baris.map((b) => [b.id, warnaFit(b.fit?.label)]));
        const properti = (id: number) => ({ id, warna: warna.get(id) ?? warnaFit(null) });

        (peta.getSource('jalur') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: f.geometri })),
        });
        (peta.getSource('awal') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: { type: 'Point' as const, coordinates: f.awal } })),
        });

        // Dipaskan sekali saja. Sesudahnya saringan mengganti isi tanpa memindahkan
        // peta, supaya pembaca tidak kehilangan posisinya.
        if (!sudahDipaskan.current && fitur.length > 0) {
            peta.fitBounds(batasDari(maplibregl, fitur), { padding: 48, maxZoom: 13, duration: 0 });
            sudahDipaskan.current = true;
        }
    }, [fitur, baris, siap]);

    useEffect(() => {
        const peta = instansi.current;
        if (!siap || !peta) return;
        if (sorotLama.current !== null) {
            peta.setFeatureState({ source: 'jalur', id: sorotLama.current }, { sorot: false });
        }
        if (sorot !== null && fitur.some((f) => f.id === sorot)) {
            peta.setFeatureState({ source: 'jalur', id: sorot }, { sorot: true });
        }
        sorotLama.current = sorot;
    }, [sorot, siap, fitur]);

    const paskanUlang = () => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (peta && maplibregl && fitur.length > 0) {
            peta.fitBounds(batasDari(maplibregl, fitur), { padding: 48, maxZoom: 13 });
        }
    };

    return (
        <div className={kelas('flex flex-col gap-2', className)}>
            <WadahPeta
                wadahRef={wadah}
                label="Peta sebaran jalur"
                gagal={gagal}
                ukuran="min-h-0 flex-1"
                cadangan="Daftar jalur tetap dapat dipakai."
                keterangan="Warna garis mengikuti kecocokan: hijau cocok, kuning perlu persiapan, merah kurang cocok, abu-abu belum dinilai. Labelnya tertulis saat garis disentuh dan di daftar."
            >
                {siap && fitur.length > 0 ? (
                    <button
                        type="button"
                        onClick={paskanUlang}
                        className="absolute bottom-3 left-3 inline-flex min-h-11 items-center rounded-control border border-control bg-surface px-3 text-xs font-medium text-primary shadow-overlay focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        Tampilkan semua jalur
                    </button>
                ) : null}
            </WadahPeta>
            <p className="sr-only">Jalur yang sama tercantum di daftar beserta rinciannya.</p>
        </div>
    );
}
