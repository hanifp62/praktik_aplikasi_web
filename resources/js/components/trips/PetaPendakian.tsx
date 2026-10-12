import { usePage } from '@inertiajs/react';
import { useEffect, useRef, type ReactNode } from 'react';
import { potongGaris, type Titik } from '@/components/mendaki/hitung';
import type { PosLapangan, PosisiPerangkat } from '@/components/mendaki/types';
import { ikutkanTemaPetaDasar, kotakDari, usePetaDasar, type Peta, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

interface PetaPendakianProps {
    id: string;
    label: string;
    garis: Titik[] | null;
    pos: PosLapangan[];
    tercapai: number;
    /** Letak sepanjang garis yang sudah dilalui, meter; null bila tidak diketahui. */
    dilaluiM: number | null;
    posisi: PosisiPerangkat | null;
}

/** Kendali 48 px di atas peta, pengganti kendali bawaan MapLibre yang hanya 29 px. */
function TombolPeta({ label, onClick, className, children }: { label?: string; onClick: () => void; className?: string; children: ReactNode }) {
    return (
        <button
            type="button"
            aria-label={label}
            onClick={onClick}
            className={kelas('pointer-events-auto inline-flex min-h-12 min-w-12 items-center justify-center rounded-full border border-control bg-surface px-3 text-kecil font-bold text-primary shadow-overlay', fokusTerlihat, umpanTekan, className)}
        >
            {children}
        </button>
    );
}

/** Lingkaran akurasi sebagai poligon bermeter: lingkaran MapLibre berukuran piksel, bukan meter. */
function lingkaran(pusat: Titik, meter: number): Titik[] {
    const lintang = (pusat[1] * Math.PI) / 180;
    const derajatY = meter / 111_195;
    const derajatX = derajatY / Math.max(0.01, Math.cos(lintang));

    return Array.from({ length: 37 }, (_, i) => {
        const sudut = (i / 36) * 2 * Math.PI;

        return [pusat[0] + derajatX * Math.cos(sudut), pusat[1] + derajatY * Math.sin(sudut)] as Titik;
    });
}

/**
 * Peta mode lapangan (prototype 11): bagian jalur yang sudah dilalui bergaris utuh redup, sisa
 * jalur bergaris putus-putus merek, pos bernomor, dan posisi dengan lingkaran akurasinya.
 * Padanan non-visualnya adalah pos berikutnya dan angka di bawah peta.
 *
 * Kendali 48 px di atas peta (perbesar, perkecil, ke posisi saya) menggantikan kendali bawaan
 * MapLibre yang hanya 29 px. Tanpa peta dasar (paket luring tahap 1, spec v3 bagian 10.5),
 * kanvasnya polos dan diberi label, sehingga garis dan pos tidak terbaca sebagai peta lengkap.
 */
export function PetaPendakian({ id, label, garis, pos, tercapai, dilaluiM, posisi }: PetaPendakianProps) {
    const { peta: konfigurasi } = usePage<PropsBersama>().props;
    const tanpaPetaDasar = !konfigurasi.styleUrl && konfigurasi.rasterTiles.length === 0;
    const wadah = useRef<HTMLDivElement>(null);
    const penanda = useRef<Array<import('maplibre-gl').Marker>>([]);
    const tandaPosisi = useRef<import('maplibre-gl').Marker | null>(null);
    const terbaru = useRef({ garis, pos, tercapai, dilaluiM, posisi });

    terbaru.current = { garis, pos, tercapai, dilaluiM, posisi };

    const gambar = (map: Peta, maplibregl: Pustaka) => {
        const { garis: g, pos: p, tercapai: t, dilaluiM: d, posisi: s } = terbaru.current;

        if (g && g.length > 1) {
            const belah = potongGaris(g, d ?? 0);
            const dilalui = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: belah.dilalui.length > 1 ? belah.dilalui : [] } };
            const sisa = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: belah.sisa.length > 1 ? belah.sisa : [] } };
            const sumberDilalui = map.getSource('jalur-dilalui') as import('maplibre-gl').GeoJSONSource | undefined;
            const sumberSisa = map.getSource('jalur-sisa') as import('maplibre-gl').GeoJSONSource | undefined;

            if (sumberDilalui && sumberSisa) {
                sumberDilalui.setData(dilalui);
                sumberSisa.setData(sisa);
            }
        }

        penanda.current.forEach((tanda) => tanda.remove());
        penanda.current = p
            .filter((c) => c.lng !== null && c.lat !== null)
            .map((c) => {
                const el = document.createElement('div');
                const berikutnya = c.urutan === p.find((x) => x.urutan > t)?.urutan;

                el.className = kelas(
                    'flex size-7 items-center justify-center rounded-full border-2 text-meta font-bold tabular-nums',
                    berikutnya ? 'border-primary bg-aksen-pos text-aksen-pos-teks' : c.urutan <= t ? 'border-surface bg-brand-700 text-on-brand' : 'border-brand-700 bg-surface text-primary',
                );
                el.textContent = String(c.urutan);
                el.title = `${c.urutan}. ${c.nama}`;
                // MapLibre 6 tidak lagi memberi elemen penanda buatan nama dan peran (MapLibre 4
                // memberi semua penanda "Penanda peta"); peran gambar sama dengan penanda bawaan
                // MapLibre 6 tanpa popup.
                el.setAttribute('role', 'img');
                el.setAttribute('aria-label', el.title);

                return new maplibregl.Marker({ element: el }).setLngLat([c.lng as number, c.lat as number]).addTo(map);
            });

        const akurasi = map.getSource('akurasi') as import('maplibre-gl').GeoJSONSource | undefined;

        if (s) {
            akurasi?.setData({ type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [lingkaran([s.lng, s.lat], s.akurasi)] } });

            if (tandaPosisi.current) {
                tandaPosisi.current.setLngLat([s.lng, s.lat]);
            } else {
                const el = document.createElement('div');
                el.className = 'size-5 rounded-full border-4 border-surface bg-primary shadow-overlay';
                el.title = 'Posisi Anda';
                el.setAttribute('role', 'img');
                el.setAttribute('aria-label', el.title);
                tandaPosisi.current = new maplibregl.Marker({ element: el }).setLngLat([s.lng, s.lat]).addTo(map);
            }
        }
    };

    const pertama = pos.find((c) => c.lng !== null && c.lat !== null);
    const { peta, pustaka, siap, gagal } = usePetaDasar(wadah, {
        pusat: pertama ? [pertama.lng as number, pertama.lat as number] : posisi ? [posisi.lng, posisi.lat] : null,
        kendaliBawaan: false,
        saatDibuat: () => {
            tandaPosisi.current = null;
            penanda.current = [];
        },
        saatSiap: (map, maplibregl) => {
            const kosong = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: [] as Titik[] } };
            const tepi = warnaToken('--surface', '#ffffff');

            map.addSource('akurasi', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [] } } });
            map.addLayer({ id: 'akurasi-isi', type: 'fill', source: 'akurasi', paint: { 'fill-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'fill-opacity': 0.15 } });
            map.addSource('jalur', { type: 'geojson', data: { ...kosong, geometry: { type: 'LineString', coordinates: terbaru.current.garis ?? [] } } });
            map.addSource('jalur-dilalui', { type: 'geojson', data: kosong });
            map.addSource('jalur-sisa', { type: 'geojson', data: kosong });
            map.addLayer({ id: 'garis-jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': 8 } });
            map.addLayer({ id: 'garis-dilalui', type: 'line', source: 'jalur-dilalui', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--text-muted', 'rgb(98, 105, 101)'), 'line-width': 4 } });
            map.addLayer({ id: 'garis-sisa', type: 'line', source: 'jalur-sisa', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'line-width': 5, 'line-dasharray': [1.5, 1] } });

            const { garis: g, pos: p } = terbaru.current;
            const titik = [...(g ?? []), ...p.filter((c) => c.lng !== null && c.lat !== null).map((c) => [c.lng, c.lat] as Titik)];

            if (titik.length > 1) {
                map.fitBounds(kotakDari(maplibregl, titik), { padding: 48, duration: 0 });
            }

            gambar(map, maplibregl);
        },
    });

    useEffect(() => {
        if (siap && peta.current && pustaka.current) {
            gambar(peta.current, pustaka.current);
        }
    }, [siap, tercapai, dilaluiM, posisi?.lng, posisi?.lat, posisi?.akurasi]);

    // Tema lapangan dibalik sekali ketuk (DK-49) tanpa membangun ulang peta: peredupan peta
    // dasar dan warna lapisan dibaca ulang dari akar tema lapangan setiap kali atributnya
    // berubah. Penanda pos ikut sendiri karena kelasnya membaca token CSS.
    useEffect(() => {
        const akar = wadah.current?.closest<HTMLElement>('[data-tema-lapangan]');

        if (!siap || !akar) {
            return;
        }

        const ikutiTema = () => {
            const map = peta.current;

            if (!map) {
                return;
            }

            ikutkanTemaPetaDasar(map, akar.dataset.temaLapangan === 'gelap');
            map.setPaintProperty('akurasi-isi', 'fill-color', warnaToken('--brand-700', 'rgb(35, 79, 50)'));
            map.setPaintProperty('garis-jalur-tepi', 'line-color', warnaToken('--surface', '#ffffff'));
            map.setPaintProperty('garis-dilalui', 'line-color', warnaToken('--text-muted', 'rgb(98, 105, 101)'));
            map.setPaintProperty('garis-sisa', 'line-color', warnaToken('--brand-700', 'rgb(35, 79, 50)'));
        };

        ikutiTema();
        const pengamat = new MutationObserver(ikutiTema);
        pengamat.observe(akar, { attributes: true, attributeFilter: ['data-tema-lapangan'] });

        return () => pengamat.disconnect();
    }, [siap]);

    const keKeposisi = () => {
        if (peta.current && posisi) {
            peta.current.easeTo({ center: [posisi.lng, posisi.lat], zoom: Math.max(peta.current.getZoom(), 15), duration: 0 });
        }
    };

    return (
        <WadahPeta
            wadahRef={wadah}
            id={id}
            label={label}
            gagal={gagal}
            ukuran="h-[45dvh] min-h-64"
            keterangan={tanpaPetaDasar ? 'Peta dasar tidak tersedia luring; garis dan pos dari paket.' : undefined}
            cadangan="Pos berikutnya dan angka di bawah tetap dapat dipakai."
        >
            {tanpaPetaDasar ? <p className="pointer-events-none absolute left-3 top-3 rounded-kecil bg-surface px-2 py-1 text-meta font-bold text-secondary">Peta dasar tidak tersedia luring</p> : null}
            <div className="pointer-events-none absolute right-3 top-3 flex flex-col gap-2">
                <TombolPeta label="Perbesar peta" onClick={() => peta.current?.zoomIn({ duration: 0 })}>
                    <Icon name="plus" size={22} />
                </TombolPeta>
                <TombolPeta label="Perkecil peta" onClick={() => peta.current?.zoomOut({ duration: 0 })}>
                    <Icon name="minus" size={22} />
                </TombolPeta>
            </div>
            {posisi ? (
                <TombolPeta onClick={keKeposisi} className="absolute bottom-3 right-3">
                    Posisi saya
                </TombolPeta>
            ) : null}
        </WadahPeta>
    );
}
