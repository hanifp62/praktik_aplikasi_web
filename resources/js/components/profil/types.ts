/** Kabar tulisan di Profil (flash `kabar-profil`): bagian mana yang menyebutnya. */
export interface KabarProfil {
    bagian: 'perlengkapan' | 'foto' | 'privasi';
    teks: string;
}

/** Satu baris ringkasan; nilai null tampil "Belum diisi" (R-007). */
export interface RincianProfil {
    label: string;
    nilai: string | null;
}

/** Isi App\Services\Profil\HalamanProfil::untuk(). */
export interface IdentitasProfilData {
    nama: string;
    tingkat: string | null;
    bergabung: string;
    foto: string | null;
}

export interface PengalamanProfil {
    lengkap: boolean;
    ubah: string;
    rincian: RincianProfil[];
}

export interface PreferensiProfil {
    ubah: string;
    rincian: RincianProfil[];
}

export interface PerlengkapanProfil {
    daftar: Array<{ id: number; nama: string }>;
    batas: number;
}

