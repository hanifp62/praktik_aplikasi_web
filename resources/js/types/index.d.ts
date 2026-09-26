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

export interface PropsBersama {
    auth: { user: PenggunaBersama | null };
    merek: { nama: string; tagline: string };
    tema: 'system' | 'light' | 'dark';
    tampilan: { efek: 'otomatis' | 'solid'; gerak: 'sistem' | 'kurangi' };
    peta: { styleUrl: string | null; rasterTiles: string[]; attribution: string; maxZoom: number };
    flash: { status: string | null; timbanganPenuh: string | null };
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
