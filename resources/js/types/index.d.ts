/*
 * Kontrak props yang dibagikan server ke setiap halaman (HandleInertiaRequests::share).
 * Bila server mengubah bentuknya, ubah di sini juga: TypeScript lalu menunjuk setiap
 * komponen yang masih membaca bentuk lama.
 */
export interface PenggunaBersama {
    id: number;
    name: string;
    email: string;
    isModerator: boolean;
    isAdmin: boolean;
    hasCompletedProfile: boolean;
}

/** Pendakian berlangsung milik pengguna, untuk strip di atas bilah tab. */
export interface PendakianAktif {
    id: number;
    jalur: string;
    /** Pos yang sedang dituju sesi mode lapangan; null bila posisi belum pernah dikirim. */
    posBerikutnya: string | null;
    /** Instan mulai sesi, ISO 8601; waktu berjalan dihitung peramban. */
    mulai: string | null;
    url: string;
}

/** Isi lembar Pengingat: pengingat surel terjadwal dan tenggat izin trip mendatang. */
export interface DaftarPengingat {
    pengingat: Array<{ id: number; trip: string; jenis: string; waktu: string; url: string }>;
    tenggatIzin: Array<{ tripId: number; trip: string; keadaan: 'BELUM_DIBUKA' | 'TERBUKA' | 'BUKA_BELUM_TERCATAT' | 'DITUTUP'; kalimat: string; pengelola: string; bookingUrl: string | null; url: string }>;
}

export interface PropsBersama {
    auth: { user: PenggunaBersama | null };
    merek: { nama: string; tagline: string };
    demo: boolean;
    /** Jalur tersimpan yang masih aktif; null untuk tamu dan di rute mode lapangan (`trips.hike`). */
    jumlahSimpanan: number | null;
    pendakianAktif: PendakianAktif | null;
    /**
     * Hanya ada sesudah muat ulang parsial only: ['daftarPengingat']; null untuk tamu. Bukan
     * `pengingat`: nama itu sudah prop halaman Trips/Show (kartu pengingat satu trip).
     */
    daftarPengingat?: DaftarPengingat | null;
    tema: 'system' | 'light' | 'dark';
    tampilan: { efek: 'otomatis' | 'solid'; gerak: 'sistem' | 'kurangi' };
    peta: { styleUrl: string | null; rasterTiles: string[]; attribution: string; maxZoom: number };
    flash: {
        /** Kabar yang datang bersama perpindahan halaman; halaman tujuan membacanya dekat kepalanya. */
        status: string | null;
        /** Kabar aksi di halaman yang sama (Perbarui daftar, sakelar pengingat); dibaca dekat aksinya. */
        kabarAksi: string | null;
        timbanganPenuh: string | null;
        /** Jalur yang baru dihapus dari simpanan dan catatannya, untuk Urungkan; satu kali. */
        simpananTerhapus: { trailId: number; nama: string; catatan: string | null } | null;
        /** Baris jalur ini sudah ada sebelum store() berjalan; Urungkan-sebagai-hapus tidak ditawarkan. */
        simpananSudahAda: { trailId: number } | null;
        /** Tautan bagikan yang baru dibuat; tokennya hanya ada di sini, sekali (FR-23). */
        tautanBagikan: { id: number; url: string; berlakuSampai: string } | null;
        /**
         * Butir persiapan yang baru diubah: id-nya, status tersimpan sebelum permintaan ini,
         * dan apakah benar berubah. Dicek di onSuccess Preparation.tsx supaya toast/Urungkan
         * tidak salah sasaran bila payload ini ternyata bukan milik butir yang baru dikirim.
         */
        butirDiubah: { id: number; sebelum: string; berubah: boolean } | null;
    };
    adminNav: { perluDitinjau: number } | null;
    errors: Record<string, string>;
    title?: string;
    [key: string]: unknown;
}

declare global {
    interface Window {
        muatPeta: () => Promise<typeof import('maplibre-gl')>;
        muatJejak: () => Promise<unknown>;
        temaGelapAktif: () => boolean;
        terapkanTema: () => void;
        terapkanTampilan: () => void;
    }
}
