import { useEffect, useRef, useState } from 'react';
import type { DataLapangan } from '@/components/mendaki/types';
import type { ModeLapangan } from '@/components/mendaki/useModeLapangan';
import { dataLapanganDariPaket, paketSiap, type DataPaket } from './baca';
import { sesiDari } from './rakit';
import { buka, tulisSesi } from './simpan';

/**
 * Data mode lapangan dari paket trip ini di perangkat, untuk sakelar "Pakai paket saja" (spec v3
 * bagian 10.2). Halaman memakai data jalur dari paket ini (dataPakaiPaketSaja) dan keadaan
 * pendakiannya sendiri, sementara peta dasar tetap dari konfigurasi halaman: jadi sakelar menguji
 * jalur, pos, profil, dan laporan dari paket, bukan seluruh tampilan luring (tinjauan Tugas 10 m-2,
 * m-3). Null bila sakelar paket mati, tanpa kunci perangkat, atau belum ada paket yang lolos;
 * basis data tidak dibuat untuk pengguna yang belum mengunduh (paketSiap, PT10-16).
 */
export function useDataPaket(kunci: string | null, aktif: boolean, tripId: number): DataPaket | null {
    const [data, setData] = useState<DataPaket | null>(null);

    useEffect(() => {
        if (!aktif || !kunci) {
            return;
        }

        let batal = false;

        paketSiap(kunci, tripId)
            .then((paket) => (paket ? dataLapanganDariPaket(kunci, paket) : null))
            .then((hasil) => {
                if (!batal) {
                    setData(hasil);
                }
            })
            .catch(() => undefined);

        return () => {
            batal = true;
        };
    }, [kunci, aktif, tripId]);

    return aktif && kunci ? data : null;
}

/**
 * Mode lapangan daring menyimpan keadaan sesinya ke paket trip yang sama di perangkat ini, juga
 * selama "Pakai paket saja" menyala (PT10-3), supaya mode lapangan dari paket melanjutkan dari pos
 * dan jeda terakhir, bukan dari keadaan saat paket diunduh. Hanya bila paket trip ini ada di
 * perangkat. Shell luring tidak menulis (aktif false di sana).
 *
 * Hanya tulisan terakhir yang dimulai yang menulis: dua perubahan berdekatan tidak dapat saling
 * mendahului, sehingga rekaman tidak mundur ke keadaan yang lebih lama. Tulisan terakhir tetap
 * berjalan walau halaman ditinggalkan sebelum selesai.
 */
export function useSimpanSesiPaket(kunci: string | null, aktif: boolean, data: DataLapangan, mode: ModeLapangan): void {
    const { tercapai, jedaSejak, jedaDetik } = mode;
    const putaran = useRef(0);

    useEffect(() => {
        if (!aktif || !kunci) {
            return;
        }

        const ini = ++putaran.current;
        const sekarang = { ...data, tercapai, trip: { ...data.trip, jedaSejak: jedaSejak === null ? null : new Date(jedaSejak).toISOString(), jedaDetik } };

        paketSiap(kunci, data.trip.id)
            .then(async (paket) => {
                if (paket && ini === putaran.current) {
                    await tulisSesi(await buka(kunci), sesiDari(sekarang, new Date()));
                }
            })
            .catch(() => undefined);
    }, [kunci, aktif, data, tercapai, jedaSejak, jedaDetik]);
}
