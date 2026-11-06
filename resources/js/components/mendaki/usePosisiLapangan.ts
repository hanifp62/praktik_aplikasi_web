import { useEffect, useState } from 'react';
import type { PosisiPerangkat } from './types';

export type KeadaanLokasi = 'mati' | 'menunggu' | 'aktif' | 'ditolak' | 'tidak-didukung' | 'galat';

/**
 * Posisi perangkat untuk mode lapangan, satu-satunya tempat aplikasi meminta lokasi (PRD §120,
 * LocationPrivacyTest). Posisi tinggal di perangkat: tidak ada yang dikirim ke server dari sini
 * (PRD §82, §145.1).
 *
 * GPS hanya berjalan selama layar terlihat dan pendakian tidak dijeda (spec v3 bagian 10.8):
 * pengawas posisi dilepas saat tab tersembunyi atau layar mati, dan dipasang lagi saat terlihat.
 */
export function usePosisiLapangan(aktif: boolean): { posisi: PosisiPerangkat | null; keadaan: KeadaanLokasi } {
    const [posisi, setPosisi] = useState<PosisiPerangkat | null>(null);
    const [keadaan, setKeadaan] = useState<KeadaanLokasi>(aktif ? 'menunggu' : 'mati');

    useEffect(() => {
        if (!aktif) {
            setKeadaan('mati');

            return;
        }

        if (!('geolocation' in navigator)) {
            setKeadaan('tidak-didukung');

            return;
        }

        let pengawas: number | null = null;

        const pasang = () => {
            if (pengawas !== null || document.visibilityState !== 'visible') {
                return;
            }

            setKeadaan((lalu) => (lalu === 'aktif' ? lalu : 'menunggu'));
            pengawas = navigator.geolocation.watchPosition(
                (p) => {
                    setPosisi({ lng: p.coords.longitude, lat: p.coords.latitude, akurasi: p.coords.accuracy, waktu: p.timestamp });
                    setKeadaan('aktif');
                },
                (galat) => setKeadaan(galat.code === galat.PERMISSION_DENIED ? 'ditolak' : 'galat'),
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 30000 },
            );
        };

        const lepas = () => {
            if (pengawas !== null) {
                navigator.geolocation.clearWatch(pengawas);
                pengawas = null;
            }
        };

        const saatBerubah = () => (document.visibilityState === 'visible' ? pasang() : lepas());

        pasang();
        document.addEventListener('visibilitychange', saatBerubah);

        return () => {
            document.removeEventListener('visibilitychange', saatBerubah);
            lepas();
        };
    }, [aktif]);

    return { posisi, keadaan };
}
