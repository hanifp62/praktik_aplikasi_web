import type { FaktorBaris } from '@/components/trails/FaktorKecocokan';
import type { LabelFit } from '@/components/trails/LabelKecocokan';

/**
 * Satu hasil rekomendasi sebagaimana dikirim RecommendationResultResource: label publik,
 * kategori faktor, dan angka yang sudah diformat. Tidak ada skor atau bobot (BR-09).
 */
export interface HasilRekomendasi {
    id: number;
    trailId: number;
    name: string;
    mountain: string | null;
    url: string | null;
    ditarik: boolean;
    eligible: boolean;
    label: LabelFit;
    labelText: string;
    angka: { jarak: string; tanjakan: string; durasi: string; teknis: string };
    berubah: boolean;
    warnings: string[];
    faktor: FaktorBaris[];
    penjelasan: { why_it_fits: string[]; what_to_watch: string[]; preparation_gap: string[] };
}

export interface HasilTersingkir {
    id: number;
    name: string;
    mountain: string | null;
    alasan: string[];
}
