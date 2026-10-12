import { useEffect, useRef, useState } from 'react';
import { kotakDari, usePetaDasar, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { FiturPeta, KartuJalur } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { kelas } from '@/lib/kelas';

type Pola = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM';

interface PetaJelajahProps {
    fitur: FiturPeta[];
    /** Kartu di halaman hasil yang sama; teks peta diambil dari sini, jadi angkanya sama dengan daftar. */
    baris: KartuJalur[];
    sorot: number | null;
    onLihatDiDaftar: (id: number) => void;
    /** Kata UNKNOWN dari server (RouteFitLabel), untuk legenda dan kartu pratinjau. */
    kataBelumDinilai: string;
    className?: string;
}

/**
 * Pola garis per label (spec v3 bagian 6.2, prototype 15): utuh, putus panjang, titik-titik,
 * dan titik-garis untuk yang belum dinilai. Bentuknya yang membedakan, warnanya menyertai,
 * jadi warna tidak pernah satu-satunya pembawa arti (WCAG 1.4.1). Satuannya kelipatan tebal
 * garis, sama dengan line-dasharray MapLibre. Pola BELUM memakai ujung bulat (line-cap
 * round di lapisan dan strokeLinecap round di legenda) karena ujung butt merender dash
 * dengan panjang persis dari array ini, membuat segmen 0,1 hampir tak terlihat; ujung bulat
 * memperlebar tiap ujung dash sebesar setengah lebar garis, sehingga titik dan celahnya
 * tetap tampak (tinjauan Tugas 5 temuan 2, ruling W3-5c).
 */
const POLA: Record<Pola, number[] | null> = {
    COCOK: null,
    PERLU_PERSIAPAN: [4, 2],
    KURANG_COCOK: [0.1, 2],
    BELUM: [2.5, 2.5, 0.1, 2.5],
};

const URUTAN_POLA: Pola[] = ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK', 'BELUM'];

/** Lapisan yang dapat disentuh untuk memilih jalur, dari yang tergambar paling atas. */
const LAPISAN_PILIH = ['jalur-awal', 'jalur-belum', 'jalur-kurang', 'jalur-perlu', 'jalur-cocok'];

function polaDari(b: KartuJalur | undefined): Pola {
    return b?.fit?.label ?? 'BELUM';
}

function warnaPola(pola: Pola): string {
    if (pola === 'COCOK') return warnaToken('--fit-cocok-teks', '#1e5e35');
    if (pola === 'PERLU_PERSIAPAN') return warnaToken('--fit-perlu-teks', '#7a4e0b');
    if (pola === 'KURANG_COCOK') return warnaToken('--fit-kurang-teks', '#8e2f22');

    return warnaToken('--fit-belum-teks', '#4e5551');
}

const batasDari = (maplibregl: Pustaka, fitur: FiturPeta[]) => kotakDari(maplibregl, fitur.flatMap((f) => f.geometri.coordinates));

/** Contoh garis untuk legenda: pola yang sama dengan di peta, digambar SVG. */
function ContohGaris({ pola }: { pola: Pola }) {
    const dash = POLA[pola];

    return (
        <svg aria-hidden="true" viewBox="0 0 28 8" className="h-2 w-7 shrink-0">
            <line
                x1="2"
                y1="4"
                x2="26"
                y2="4"
                strokeWidth="3"
                strokeLinecap={pola === 'KURANG_COCOK' || pola === 'BELUM' ? 'round' : 'butt'}
                strokeDasharray={dash ? dash.map((d) => d * 3).join(' ') : undefined}
                className={kelas(
                    pola === 'COCOK' && 'stroke-fit-cocok-teks',
                    pola === 'PERLU_PERSIAPAN' && 'stroke-fit-perlu-teks',
                    pola === 'KURANG_COCOK' && 'stroke-fit-kurang-teks',
                    pola === 'BELUM' && 'stroke-fit-belum-teks',
                )}
            />
        </svg>
    );
}

/**
 * Peta sebaran Jelajah: bentuk jalur, bukan titik gunung (keputusan F2), untuk kartu di
 * halaman hasil yang sama dengan daftar.
 *
 * Petanya dibuat sekali dan hanya datanya yang diganti ketika saringan berubah, jadi posisi
 * dan zoom pembaca tidak hilang. Setiap label punya lapisan sendiri; alasannya semula
 * line-dasharray MapLibre 4.7 yang tidak dapat berbeda per fitur. MapLibre 6 sudah dapat (sejak
 * 5.8), dan lapisan per label dipertahankan karena perilakunya teruji; semua garis berdiri di atas satu tepi --surface
 * yang lebih lebar. Jalur yang disorot dari daftar atau dipilih lewat chip dan garisnya
 * tergambar lebih tebal; jalur yang tampil lewat "Tampilkan juga" tergambar pudar.
 *
 * Memilih jalur (chip di ponsel, atau menyentuh garisnya) membuka kartu pratinjau React
 * dengan label, angka, status, dan "Lihat detail jalur": teks dari data, bukan HTML. Jalur
 * tanpa geometri tidak digambar dan jumlahnya disebut di atas peta; daftar tetap lengkap.
 * Pembukaan pratinjau diumumkan lewat role="status", Escape menutupnya, dan menutup dari
 * dalam kartu mengembalikan fokus ke pemicunya (temuan Tugas 5, tinjauan akhir W3). Saat
 * peta gagal dimuat, chip dan legenda tidak tampil: kalimat galat menjadi satu-satunya kabar.
 */
export function PetaJelajah({ fitur, baris, sorot, onLihatDiDaftar, kataBelumDinilai, className }: PetaJelajahProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const sudahDipaskan = useRef(false);
    const sorotLama = useRef<number | null>(null);
    const [terpilih, setTerpilih] = useState<number | null>(null);
    // Elemen yang membuka pratinjau (chip, atau kanvas peta), tempat fokus kembali saat ditutup.
    const pemicuPratinjau = useRef<HTMLElement | null>(null);
    const kartuPratinjau = useRef<HTMLDivElement>(null);

    const { peta: instansi, pustaka, siap, gagal } = usePetaDasar(wadah, {
        saatSiap: (peta) => {
            const kosong = { type: 'FeatureCollection' as const, features: [] };
            peta.addSource('jalur', { type: 'geojson', data: kosong, promoteId: 'id' });
            peta.addSource('awal', { type: 'geojson', data: kosong, promoteId: 'id' });
            const tepi = warnaToken('--surface', '#ffffff');
            const tebal = (biasa: number, disorot: number) => ['case', ['boolean', ['feature-state', 'sorot'], false], disorot, biasa] as unknown as number;
            const pudar = ['case', ['boolean', ['get', 'pudar'], false], 0.45, 1] as unknown as number;
            const hanya = (pola: Pola) => ['==', ['get', 'pola'], pola] as import('maplibre-gl').FilterSpecification;
            peta.addLayer({ id: 'jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': tebal(7, 10), 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-cocok', type: 'line', source: 'jalur', filter: hanya('COCOK'), layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaPola('COCOK'), 'line-width': tebal(4, 6), 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-perlu', type: 'line', source: 'jalur', filter: hanya('PERLU_PERSIAPAN'), layout: { 'line-cap': 'butt', 'line-join': 'round' }, paint: { 'line-color': warnaPola('PERLU_PERSIAPAN'), 'line-width': tebal(4, 6), 'line-dasharray': [4, 2], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-kurang', type: 'line', source: 'jalur', filter: hanya('KURANG_COCOK'), layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaPola('KURANG_COCOK'), 'line-width': tebal(4, 6), 'line-dasharray': [0.1, 2], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-belum', type: 'line', source: 'jalur', filter: hanya('BELUM'), layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaPola('BELUM'), 'line-width': tebal(4, 6), 'line-dasharray': [2.5, 2.5, 0.1, 2.5], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-awal', type: 'circle', source: 'awal', paint: { 'circle-radius': 6, 'circle-color': ['get', 'warna'], 'circle-stroke-color': tepi, 'circle-stroke-width': 2, 'circle-opacity': pudar, 'circle-stroke-opacity': pudar } });

            // Satu penangan klik untuk semua lapisan: garis yang bertumpuk tidak memicu beberapa
            // pilihan berturut-turut, yang teratas yang dipilih (queryRenderedFeatures
            // mengembalikan fitur teratas lebih dulu). setTerpilih stabil, jadi boleh dipakai di
            // penangan yang dipasang sekali ini.
            peta.on('click', (e) => {
                const teratas = peta.queryRenderedFeatures(e.point, { layers: LAPISAN_PILIH })[0];
                if (!teratas) return;
                pemicuPratinjau.current = peta.getCanvas();
                setTerpilih(Number(teratas.properties?.id));
            });

            for (const lapisan of LAPISAN_PILIH) {
                peta.on('mouseenter', lapisan, () => (peta.getCanvas().style.cursor = 'pointer'));
                peta.on('mouseleave', lapisan, () => (peta.getCanvas().style.cursor = ''));
            }
        },
    });

    useEffect(() => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (!siap || !peta || !maplibregl) return;

        const kartu = new Map(baris.map((b) => [b.id, b]));
        const properti = (id: number) => {
            const pola = polaDari(kartu.get(id));

            return { id, pola, warna: warnaPola(pola), pudar: (kartu.get(id)?.tersembunyiOleh ?? null) !== null };
        };

        (peta.getSource('jalur') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: f.geometri })),
        });
        (peta.getSource('awal') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: { type: 'Point' as const, coordinates: f.awal } })),
        });

        // Dipaskan sekali saja. Sesudahnya saringan mengganti isi tanpa memindahkan peta,
        // supaya pembaca tidak kehilangan posisinya.
        if (!sudahDipaskan.current && fitur.length > 0) {
            peta.fitBounds(batasDari(maplibregl, fitur), { padding: 48, maxZoom: 13, duration: 0 });
            sudahDipaskan.current = true;
        }
    }, [fitur, baris, siap]);

    // Jalur terpilih yang tidak lagi ada di halaman ini (saringan berubah) tidak dipratinjau.
    const pilihan = terpilih !== null && fitur.some((f) => f.id === terpilih) ? terpilih : null;
    // Pilihan pembaca (chip atau garis) menang atas sorot dari daftar, yang bisa basi.
    const tebalkan = pilihan ?? sorot;

    useEffect(() => {
        const peta = instansi.current;
        if (!siap || !peta) return;
        if (sorotLama.current !== null) {
            peta.setFeatureState({ source: 'jalur', id: sorotLama.current }, { sorot: false });
        }
        if (tebalkan !== null && fitur.some((f) => f.id === tebalkan)) {
            peta.setFeatureState({ source: 'jalur', id: tebalkan }, { sorot: true });
        }
        sorotLama.current = tebalkan;
    }, [tebalkan, siap, fitur]);

    const paskan = (daftar: FiturPeta[]) => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (peta && maplibregl && daftar.length > 0) {
            peta.fitBounds(batasDari(maplibregl, daftar), { padding: 48, maxZoom: 14 });
        }
    };

    const pilih = (id: number | null, pemicu: HTMLElement | null) => {
        pemicuPratinjau.current = pemicu;
        setTerpilih(id);
        const f = fitur.find((x) => x.id === id);
        if (f) paskan([f]);
    };

    // Menutup pratinjau mengembalikan fokus ke pemicunya bila fokus sedang di dalam pratinjau
    // (tombol tutup atau Escape), supaya pembaca papan ketik tidak kehilangan tempatnya.
    const tutupPratinjau = () => {
        const fokusDiDalam = kartuPratinjau.current?.contains(document.activeElement) ?? false;
        setTerpilih(null);
        if (fokusDiDalam && pemicuPratinjau.current?.isConnected) {
            pemicuPratinjau.current?.focus();
        }
    };

    const tergambar = fitur.map((f) => baris.find((b) => b.id === f.id)).filter((b): b is KartuJalur => b !== undefined);
    const tanpaGaris = baris.length - tergambar.length;
    const legenda = URUTAN_POLA.map((pola) => ({ pola, contoh: tergambar.find((b) => polaDari(b) === pola) })).filter(({ contoh }) => contoh !== undefined);
    const pratinjau = pilihan === null ? null : (tergambar.find((b) => b.id === pilihan) ?? null);

    return (
        // Escape menutup pratinjau dari mana pun fokus berada di dalam peta ini (chip, kanvas,
        // atau kartu pratinjau).
        <div
            className={kelas('flex flex-col gap-2', className)}
            onKeyDown={(e) => {
                if (e.key === 'Escape' && pratinjau) {
                    e.stopPropagation();
                    tutupPratinjau();
                }
            }}
        >
            {!gagal && tergambar.length > 1 ? (
                <div role="group" aria-label="Pilih jalur di peta" className="relative -mx-1 flex gap-1 overflow-x-auto px-1 xl:hidden">
                    {tergambar.map((b) => (
                        <ChipPilihan
                            key={b.id}
                            dipilih={pilihan === b.id}
                            onUbah={(dipilih) => pilih(dipilih ? b.id : null, document.activeElement instanceof HTMLElement ? document.activeElement : null)}
                            className="shrink-0"
                        >
                            {b.name}
                        </ChipPilihan>
                    ))}
                </div>
            ) : null}
            <WadahPeta wadahRef={wadah} label="Peta sebaran jalur" gagal={gagal} ukuran="min-h-0 flex-1" cadangan="Daftar jalur tetap dapat dipakai.">
                {!gagal ? (
                    // Lebar kalimat menyisakan kontrol zoom di kanan atas (sekitar 39 px dengan
                    // margin) pada kolom peta 288 px di layar 320 px.
                    <p className="pointer-events-none absolute left-3 top-3 z-10 max-w-[min(17rem,calc(100%-4.5rem))] rounded-control bg-surface/95 px-3 py-2 text-meta text-secondary shadow-overlay">
                        Hanya jalur bergeometri yang digambar.
                        {tanpaGaris > 0 ? ` ${tanpaGaris} jalur di halaman ini belum punya garis rute; semuanya tetap ada di daftar.` : null}
                    </p>
                ) : null}
                {/* Tombol dan legenda satu wadah flex-wrap, bukan dua elemen absolute di sudut
                    kiri dan kanan: di kolom peta sempit (ponsel dan xl) keduanya tidak cukup
                    tempat berdampingan, sehingga legenda yang belakangan di DOM menutup
                    sebagian tombol. Membungkus ke baris kedua saat sempit memperbaikinya tanpa
                    menunggu ukuran pasti (tinjauan Tugas 5 temuan 3, ruling W3-5d). */}
                <div className="pointer-events-none absolute inset-x-3 bottom-3 z-10 flex flex-wrap items-end justify-between gap-2">
                    {siap && fitur.length > 0 && pratinjau === null ? (
                        <Button variant="secondary" size="sm" onClick={() => paskan(fitur)} className="pointer-events-auto shadow-overlay">
                            Tampilkan semua jalur
                        </Button>
                    ) : null}
                    {!gagal && legenda.length > 0 ? (
                        <ul aria-label="Legenda garis jalur" className={kelas('ml-auto pointer-events-auto space-y-1 rounded-control bg-surface/95 px-3 py-2 shadow-overlay', pratinjau && 'hidden sm:block')}>
                            {legenda.map(({ pola, contoh }) => (
                                <li key={pola} className="flex items-center gap-2 text-meta text-secondary">
                                    <ContohGaris pola={pola} />
                                    {contoh?.fit?.labelText ?? kataBelumDinilai}
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </div>
                {pratinjau ? (
                    <div ref={kartuPratinjau} className="absolute inset-x-3 bottom-3 z-10 rounded-lg border border-subtle bg-surface p-4 shadow-overlay sm:right-auto sm:w-80">
                        <div className="flex items-start justify-between gap-2">
                            <div className="pt-2.5">
                                <LabelKecocokan nilai={pratinjau.fit?.label ?? null} label={pratinjau.fit?.labelText ?? kataBelumDinilai} />
                            </div>
                            <TombolIkon ikon="x" label="Tutup pratinjau jalur" varian="polos" onClick={tutupPratinjau} />
                        </div>
                        <p className="mt-1 text-subjudul text-primary">{pratinjau.name}</p>
                        <AngkaRingkas jalur={pratinjau} className="mt-0.5 text-secondary" />
                        <BarisStatus status={pratinjau.status} className="mt-2" />
                        <div className="mt-3 flex flex-wrap gap-2">
                            <Button href={pratinjau.url} size="sm">
                                Lihat detail jalur
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => onLihatDiDaftar(pratinjau.id)} className="xl:hidden">
                                Lihat di daftar
                            </Button>
                        </div>
                    </div>
                ) : null}
            </WadahPeta>
            <p className="sr-only">Jalur yang sama tercantum di daftar beserta rinciannya.</p>
            <p role="status" className="sr-only">
                {pratinjau ? `Pratinjau ${pratinjau.name} terbuka. Tekan Escape untuk menutup.` : ''}
            </p>
        </div>
    );
}
