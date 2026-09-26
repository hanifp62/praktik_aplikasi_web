import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface ModulJejak {
    mulaiMerekam: (sesi: number) => () => void;
    kirimJejak: (sesi: number, url: string, token: string) => Promise<number>;
}

/**
 * Posisi diambil hanya ketika pendaki memintanya, dan hanya di halaman ini (PRD §120).
 * getCurrentPosition, bukan watchPosition: mode pendakian tidak melacak di latar
 * belakang (PRD §53-54). Posisinya dikirim ke server yang menentukan pos berikutnya.
 */
export function usePosisiPendakian(urlKirim: string) {
    const [pesan, setPesan] = useState('Izin lokasi belum diminta.');
    const [memproses, setMemproses] = useState(false);

    const minta = () => {
        if (!navigator.geolocation) {
            setPesan('Browser Anda tidak mendukung layanan lokasi.');
            return;
        }

        setMemproses(true);
        setPesan('Mengambil posisi...');

        navigator.geolocation.getCurrentPosition(
            (posisi) => {
                router.post(
                    urlKirim,
                    { latitude: posisi.coords.latitude, longitude: posisi.coords.longitude },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => setPesan('Posisi diperbarui.'),
                        onError: (galat) => setPesan(galat.latitude ?? galat.longitude ?? 'Posisi tidak dapat disimpan.'),
                        // Di jalur sinyal sering hilang. Mengaku gagal lebih jujur daripada
                        // membiarkan jarak lama terbaca sebagai jarak sekarang.
                        onNetworkError: () => {
                            setPesan('Posisi belum terkirim karena tidak ada sinyal. Coba lagi saat sinyal kembali.');
                            return false;
                        },
                        onFinish: () => setMemproses(false),
                    },
                );
            },
            () => {
                setPesan('Posisi tidak dapat diambil. Periksa izin lokasi browser.');
                setMemproses(false);
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );
    };

    return { pesan, memproses, minta };
}

/**
 * Perekaman jejak berjalan sendiri selama halaman ini terbuka, dan hanya bila pendaki
 * menyalakannya di profilnya (penjagaan yang sama ada di server).
 *
 * Tidak ada yang dikirim selama pendakian. Titiknya menumpuk di perangkat (jejak.js),
 * lalu berangkat ketika sinyal kembali: saat peramban mengabarkan "online", dan sekali
 * saat halaman dibuka untuk jejak pendakian sebelumnya yang belum sempat terkirim.
 */
export function useJejakPendakian(aktif: boolean, sesiId: number | null, urlKirim: string | null) {
    useEffect(() => {
        if (!aktif || sesiId === null || urlKirim === null || typeof window.muatJejak !== 'function') {
            return;
        }

        let batal = false;
        let berhenti: (() => void) | null = null;
        let kirim: (() => void) | null = null;

        window
            .muatJejak()
            .then((modul) => {
                if (batal) {
                    return;
                }

                const jejak = modul as ModulJejak;
                berhenti = jejak.mulaiMerekam(sesiId);
                kirim = () => {
                    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
                    jejak.kirimJejak(sesiId, urlKirim, token).catch(() => {});
                };

                window.addEventListener('online', kirim);
                kirim();
            })
            .catch(() => {});

        return () => {
            batal = true;
            berhenti?.();

            if (kirim) {
                window.removeEventListener('online', kirim);
            }
        };
    }, [aktif, sesiId, urlKirim]);
}
