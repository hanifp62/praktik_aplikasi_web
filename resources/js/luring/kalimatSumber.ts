import type { DataLapangan } from '@/components/mendaki/types';
import { keteranganBerjalan } from '../lib/waktuBergerak.ts';
import type { DataPaket } from './rakit';

/**
 * Sumber data mode lapangan: kalimat setiap cabang sumber (ruling W6-9e; PT9-14 bagian b,
 * PT10-14, W6-10b) dan gabungan sumber untuk sakelar "Pakai paket saja" (PT10-3, PT10-5).
 *
 * Satu-satunya impor nilai adalah lib/waktuBergerak.ts lewat jalur relatif berekstensi (ruling W6-FJ), yang juga
 * tanpa impor (ruling L3): FieldPackDeviceTest tetap menjalankannya di Node dari PHPUnit.
 */

/** Bingkai sistem kedua untuk paket luring, tanpa janji keselamatan (R-012). */
export const BINGKAI_PAKET = 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.';

export interface KeadaanSumber {
    /** Shell luring (halaman tanpa server, props tanpa perangkat), bukan "Pakai paket saja" di halaman daring. */
    halamanLuring: boolean;
    sumber: DataLapangan['sumber'];
    berlangsung: boolean;
    dijeda: boolean;
    /** Lama bergerak, sudah ditulis (teksLama). */
    lama: string;
    /** Waktu rekaman sesi terakhir di perangkat, sudah ditulis dengan zona; null tanpa rekaman. */
    catatan: string | null;
    /**
     * Pendakian yang sudah diselesaikan atau dibatalkan di perangkat ini (ruling AF-6), dengan waktu
     * yang sudah ditulis berzona (null bila waktunya tidak terbaca); tidak ada atau null bila belum.
     */
    berakhir?: { jenis: 'selesai' | 'batal'; waktu: string | null } | null;
}

/**
 * Kalimat pendakian yang sudah berakhir di perangkat ini (ruling AF-6), untuk shell mode lapangan
 * dan daftar paket. Waktunya waktu jawaban berhasil diterima di perangkat ini; tanpa waktu yang
 * terbaca, "pada ..." tidak ditulis.
 */
export function kalimatBerakhir(jenis: 'selesai' | 'batal', waktu: string | null): string {
    const kata = jenis === 'selesai' ? 'diselesaikan' : 'dibatalkan';

    return waktu === null ? `Pendakian ini sudah ${kata} di perangkat ini.` : `Pendakian ini sudah ${kata} di perangkat ini pada ${waktu}.`;
}

/**
 * keteranganBerjalan tinggal di lib/waktuBergerak.ts (ruling W6-FJ), satu sumber untuk kepala mode lapangan dan
 * strip pendakian; diekspor ulang di sini untuk pemanggil lama. Ekstensi .ts ditulis karena Node menjalankan modul
 * ini langsung (allowImportingTsExtensions di tsconfig.json).
 */
export { keteranganBerjalan };

/**
 * Keterangan di bawah judul dan kalimat di atas mode lapangan, satu fungsi untuk setiap cabang:
 * 1. halaman daring berlangsung: tanpa kalimat;
 * 2. halaman daring belum berlangsung: pendakian belum dimulai;
 * 3. halaman daring dengan "Pakai paket saja": dari paket, dan cara menyelesaikan atau kembali;
 * 4. shell tanpa catatan sesi: keadaan belum tercatat, jadi tiba dan jeda belum dapat dicatat;
 * 5. shell dengan catatan, belum berlangsung: menurut catatan itu, belum dimulai;
 * 6. shell dengan catatan, berlangsung: asal dan waktu catatannya, dan bahwa aksi dari sini tidak
 *    diterima server bila pendakian sudah diselesaikan atau dibatalkan sesudah itu (W6-10b), dan
 *    bahwa jejak yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan
 *    tidak disimpan (ruling W6-FH, W6-FT, PenerimaanJejak::dalamPendakian). Sejak W6-FT,
 *    TripLifecycle::cancel() menutup sesi (ended_at) seperti complete(), jadi saringan servernya
 *    kini berlaku juga untuk pendakian yang dibatalkan;
 * 7. shell dengan rekaman berakhir (ruling AF-6): diselesaikan atau dibatalkan di perangkat ini
 *    pada waktunya, dan tiba di pos serta jeda tidak dicatat lagi. Didahulukan dari cabang 4
 *    sampai 6, dan keterangannya tanpa lama bergerak, jadi penghitung waktu tidak berjalan.
 */
export function teksKeadaanLapangan(k: KeadaanSumber): { keterangan: string; kalimat: string | null } {
    const berjalan = keteranganBerjalan(k.dijeda, k.lama);

    if (k.halamanLuring && k.berakhir) {
        return {
            keterangan: k.berakhir.jenis === 'selesai' ? 'Sudah diselesaikan' : 'Sudah dibatalkan',
            kalimat: `Dari paket luring. ${BINGKAI_PAKET} ${kalimatBerakhir(k.berakhir.jenis, k.berakhir.waktu)} Tiba di pos dan jeda tidak dicatat lagi.`,
        };
    }

    if (!k.halamanLuring) {
        const keterangan = k.berlangsung ? berjalan : 'Belum dimulai';

        if (k.sumber === 'paket') {
            return {
                keterangan,
                kalimat: `Dari paket di perangkat ini (Pakai paket saja). ${BINGKAI_PAKET} Matikan Pakai paket saja untuk menyelesaikan pendakian atau kembali ke ruang kerja.`,
            };
        }

        return {
            keterangan,
            kalimat: k.berlangsung ? null : 'Pendakian ini belum dimulai. Mulai dari tab Mendaki atau ruang kerja trip; sebelum itu peta dan pos dapat ditinjau di sini.',
        };
    }

    if (k.catatan === null) {
        return {
            keterangan: 'Keadaan belum tercatat',
            kalimat: `Dari paket luring. ${BINGKAI_PAKET} Keadaan pendakian ini belum tercatat di perangkat ini, jadi tiba di pos dan jeda belum dapat dicatat dari sini; pos dan peta dapat ditinjau. Bila pendakian sudah dimulai, buka mode lapangan sekali saat ada sinyal supaya keadaannya tercatat di perangkat ini.`,
        };
    }

    if (!k.berlangsung) {
        return {
            keterangan: 'Belum dimulai',
            kalimat: `Dari paket luring. ${BINGKAI_PAKET} Menurut catatan terakhir di perangkat ini (${k.catatan}), pendakian ini belum dimulai. Mulai dari tab Mendaki saat ada sinyal; sebelum itu pos dan peta dapat ditinjau di sini.`,
        };
    }

    return {
        keterangan: berjalan,
        kalimat: `Dari paket luring. ${BINGKAI_PAKET} Keadaan pendakian dari catatan terakhir di perangkat ini (${k.catatan}); bila pendakian sudah diselesaikan atau dibatalkan sesudah itu, tiba di pos dan jeda dari sini tidak diterima server, dan jejak yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan tidak disimpan. Tiba di pos, jeda, dan lanjut disimpan di perangkat sampai terkirim; selesaikan pendakian saat sinyal kembali.`,
    };
}

/**
 * Data mode lapangan selama "Pakai paket saja" di halaman daring (PT10-3, PT10-5): data jalur
 * (garis, pos, profil, ruas, laporan, kartu darurat, dan isi paket lain) dari paket, dan keadaan
 * pendakian di halaman ini (berlangsung, mulai, jeda, pos tercapai, sesi, dan rekam jejak) tetap.
 *
 * Bagian lapangan paket sengaja tanpa keadaan sesi (PaketLapangan::lapangan), dan rekaman sesi di
 * perangkat dapat belum ditulis saat paket dibaca. Tanpa gabungan ini sakelar menulis "Belum
 * dimulai" untuk pendakian yang berjalan, menghentikan perekam jejak, dan menimpa rekaman sesi di
 * perangkat dengan keadaan kosong. useModeLapangan memegang pos tercapai dan jeda sejak dipasang,
 * jadi berganti sumber hanya mengganti data jalur.
 */
export function dataPakaiPaketSaja(daring: DataLapangan, paket: DataPaket): DataPaket {
    return {
        ...paket,
        trip: { ...paket.trip, berlangsung: daring.trip.berlangsung, mulai: daring.trip.mulai, jedaSejak: daring.trip.jedaSejak, jedaDetik: daring.trip.jedaDetik },
        tercapai: daring.tercapai,
        sesiId: daring.sesiId,
        rekamJejak: daring.rekamJejak,
        urls: { ...paket.urls, jejak: daring.urls.jejak },
    };
}
