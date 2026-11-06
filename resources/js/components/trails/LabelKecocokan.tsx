import type { NamaIkon } from '@/components/ui/Icon';
import { Lencana, type NadaLencana } from '@/components/ui/Lencana';

export type LabelFit = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null;

const rupa: Record<'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM', { nada: NadaLencana; ikon: NamaIkon }> = {
    COCOK: { nada: 'cocok', ikon: 'check' },
    PERLU_PERSIAPAN: { nada: 'perlu', ikon: 'warning' },
    KURANG_COCOK: { nada: 'kurang', ikon: 'x' },
    BELUM: { nada: 'belum', ikon: 'question' },
};

interface LabelKecocokanProps {
    /** Nilai enum RouteFitLabel; null berarti belum dinilai (UNKNOWN), bukan label keempat. */
    nilai: LabelFit;
    /** Kata dari server (RouteFitLabel::textFor): satu sumber untuk ketiga label dan "Belum dinilai". */
    label: string;
    className?: string;
}

/**
 * Label kecocokan publik (R-004): tiga label, ditambah keadaan UNKNOWN yang tidak pernah
 * menjadi label keempat (R-006). Selalu ikon dan kata (spec v3 bagian 7), karena warna
 * saja tidak terbaca di forced-colors maupun oleh pembaca layar; ikonnya disembunyikan dari
 * pembaca layar supaya kata tidak dibacakan dua kali. Tidak pernah angka (R-005, BR-09).
 * Dipindah dari components/ui/FitBadge.tsx dan ditulis ulang W3.
 */
export function LabelKecocokan({ nilai, label, className }: LabelKecocokanProps) {
    const r = rupa[nilai ?? 'BELUM'];

    return (
        <Lencana nada={r.nada} bentuk="pil" ikon={r.ikon} className={className}>
            {label}
        </Lencana>
    );
}
