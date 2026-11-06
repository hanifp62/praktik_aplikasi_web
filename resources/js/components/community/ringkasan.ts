import type { Opsi } from './types';

/** Angka ringkasan pendakian dari App\Services\Community\HasilPendakian; null berarti belum ada. */
export interface AngkaRingkasan {
    jarak: string | null;
    naik: string | null;
    waktu: string | null;
    tertinggi: string | null;
    sumber: 'jejak' | 'jalur' | 'pendaki' | 'kosong';
    keterangan: string;
    awal: { jarak_km: number | null; naik_m: number | null; waktu_menit: number | null; tertinggi_m: number | null };
    ubah: string;
}

/**
 * Tangga tanjakan riwayat sendiri (R-015): pendakian ini ditandai `ini`. `id` dan `gunung`
 * mendisambiguasi jalur bernama sama lintas gunung (ruling W6-6a, konsisten dengan ruling W5-6a
 * TanggaTanjakan Beranda).
 */
export interface TanggaRingkasan {
    baris: Array<{ id: number; nama: string; gunung: string; m: number; teks: string; ini: boolean }>;
    kalimat: string;
}

/** Laporan kondisi dari ringkasan dan keadaannya. */
export interface LaporanRingkasan {
    keadaan: 'belum' | 'menunggu' | 'terbit' | 'ditolak';
    alasanTolak: string | null;
    privasi: string;
    formulir: {
        kirim: string;
        ubah: string | null;
        tripId: number;
        trailId: number;
        tanggal: string;
        tags: Opsi[];
        segmen: Opsi[];
        awal: { condition_tags: string[]; trail_segment_id: number | null; note: string | null } | null;
    };
}
