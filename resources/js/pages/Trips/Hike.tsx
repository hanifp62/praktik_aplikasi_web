import { usePage } from '@inertiajs/react';
import { lazy, Suspense, useMemo, useState, type ComponentType, type ReactNode } from 'react';
import { teksLama } from '@/components/mendaki/hitung';
import { ModeLapangan } from '@/components/mendaki/ModeLapangan';
import { SelesaiLapangan } from '@/components/mendaki/SelesaiLapangan';
import { useModeLapangan } from '@/components/mendaki/useModeLapangan';
import { TataLetakPendakian } from '@/components/trips/TataLetakPendakian';
import { useJejakPendakian } from '@/components/trips/usePendakian';
import { Sakelar } from '@/components/ui/Sakelar';
import { useDataPaket, useSimpanSesiPaket } from '@/luring/useLapanganPaket';
import { kelas } from '@/lib/kelas';
import { dataPakaiPaketSaja, teksKeadaanLapangan } from '@/luring/kalimatSumber';
import type { DataPaket } from '@/luring/rakit';
import type { PropsBersama } from '@/types';

type Props = PropsBersama & DataPaket;

/**
 * Kabar shell luring dimuat malas (tinjauan Tugas 10 m-1): hanya dibutuhkan bila paket trip ini ada
 * di perangkat, jadi pramuat mode lapangan dan shell paket tidak membawa pemeriksaan shell dan
 * modulnya. Muatan yang gagal (sinyal hilang tepat saat itu) tidak menampilkan apa pun, bukan
 * menjatuhkan halaman.
 */
const KabarShellPaket = lazy(
    (): Promise<{ default: ComponentType }> =>
        import('@/components/mendaki/KabarShellPaket').then(
            (m) => ({ default: m.KabarShellPaket }),
            () => ({ default: () => null }),
        ),
);

/**
 * Mode lapangan (FR-15; spec v3 bagian 6.12, prototype 11). Satu pohon komponen untuk dua
 * sumber data: props server saat daring dan isi paket lapangan saat luring (`sumber`). Lokasi
 * diminta di halaman ini dan tidak di tempat lain (PRD §120); hitungannya di perangkat.
 *
 * Bila paket trip ini ada di perangkat (sakelar paket menyala), mode lapangan daring menyimpan
 * keadaan sesinya ke paket, juga selama "Pakai paket saja" menyala, dan sakelar itu membaca data
 * jalur hanya dari paket (spec v3 bagian 10.2). Mengganti sumber TIDAK memasang ulang halaman
 * (PT10-3, PT10-5): useModeLapangan memegang pos yang dicapai dan jeda sejak dipasang, dan
 * dataPakaiPaketSaja menjaga keadaan pendakian halaman ini, jadi yang berganti hanya data jalur.
 * Sakelar yang baru ditekan tetap terpasang dan memegang fokus; tema lapangan dan perekam jejak
 * tidak dimulai ulang. Peta dasar tetap dari konfigurasi halaman, jadi sakelar tidak menguji
 * kanvas polos di jalur (batas spec 10.2, tinjauan Tugas 10 m-3; keterangannya menyebutnya).
 * Paket yang terlihat juga memeriksa shell luring-nya saat dipasang dan setiap service worker
 * berganti (KabarShellPaket, ruling W6-9n dan W6-10e). Shell luring merakit props tanpa perangkat
 * (propsHalamanLuring), jadi `halamanLuring` membedakan halaman tanpa server dari "Pakai paket
 * saja" di halaman daring (PT10-14); kalimat setiap cabang dari teksKeadaanLapangan (W6-9e).
 */
export default function Hike() {
    const props = usePage<Props>().props;
    const kunci = props.perangkat?.kunci ?? null;
    const paketAktif = props.sumber === 'daring' && props.perangkat?.paket === true;
    const halamanLuring = props.perangkat == null;
    const dataPaket = useDataPaket(kunci, paketAktif, props.trip.id);
    const [hanyaPaket, setHanyaPaket] = useState(false);
    const data: DataPaket = useMemo(() => (hanyaPaket && dataPaket ? dataPakaiPaketSaja(props, dataPaket) : props), [hanyaPaket, dataPaket, props]);

    return (
        <Lapangan
            data={data}
            kunci={kunci}
            halamanLuring={halamanLuring}
            simpanSesi={paketAktif}
            pengaturan={
                dataPaket ? (
                    <div className="space-y-2">
                        <Sakelar
                            judul="Pakai paket saja"
                            keterangan="Jalur, pos, dan laporan dibaca hanya dari paket di perangkat ini, ada sinyal atau tidak; cara menguji paket sebelum berangkat. Peta dasar tidak ikut paket; selama ada sinyal, peta dasar tetap dimuat dari jaringan."
                            nyala={hanyaPaket}
                            onUbah={setHanyaPaket}
                        />
                        <Suspense fallback={null}>
                            <KabarShellPaket />
                        </Suspense>
                    </div>
                ) : null
            }
        />
    );
}

/** Jam rekaman sesi di perangkat ini, dengan penanda zonanya (butir 6). */
const waktuCatatan = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' });

function teksWaktu(iso: string | undefined): string | null {
    const ms = iso === undefined ? Number.NaN : Date.parse(iso);

    return Number.isFinite(ms) ? waktuCatatan.format(ms) : null;
}

function Lapangan({ data, kunci, halamanLuring, simpanSesi, pengaturan }: { data: DataPaket; kunci: string | null; halamanLuring: boolean; simpanSesi: boolean; pengaturan: ReactNode }) {
    const mode = useModeLapangan(data);
    const daring = data.sumber === 'daring';

    useSimpanSesiPaket(kunci, simpanSesi, data, mode);
    // Rekam jejak berhenti saat jeda (M-3/ruling W6-3g): tanpa !mode.dijeda, watchPosition
    // milik jejak.js tetap menyala walau layar sudah berkata lokasi berhenti diperbarui. Basis data
    // jejak terikat kunci perangkat pengguna (ruling W6-R10); tanpa kunci tidak ada yang direkam.
    // Halaman ini hanya merekam; titiknya dikirim sesudah pendakian berakhir (ruling W6-P2a).
    useJejakPendakian(data.rekamJejak && data.trip.berlangsung && !mode.dijeda, data.sesiId, kunci);

    // Rekaman sesi yang berakhir di perangkat ini (ruling AF-6) didahulukan teksKeadaanLapangan.
    const { keterangan, kalimat } = teksKeadaanLapangan({
        halamanLuring,
        sumber: data.sumber,
        berlangsung: data.trip.berlangsung,
        dijeda: mode.dijeda,
        lama: teksLama(mode.detik),
        catatan: teksWaktu(data.catatanSesi?.diperbarui),
        berakhir: data.berakhir ? { jenis: data.berakhir.jenis, waktu: teksWaktu(data.berakhir.waktu) } : null,
    });

    return (
        <TataLetakPendakian kembali={daring ? data.urls.ruangKerja : null} berlangsung={data.trip.berlangsung} judul={<h1 className="break-words text-bagian text-primary">{data.trip.jalur}</h1>} keterangan={keterangan}>
            {kalimat ? (
                <p className={kelas('mt-4 rounded-kartu bg-surface p-4 text-isi text-primary', daring ? 'border border-subtle' : 'border-[1.5px] border-dashed border-control')}>{kalimat}</p>
            ) : null}
            <ModeLapangan data={data} mode={mode} selesai={daring ? <SelesaiLapangan data={data} /> : null} pengaturan={pengaturan} />
        </TataLetakPendakian>
    );
}
