/*
 * Label otoritas sumber (PRD §92, R-009, R-010): keterangan resmi dan masukan komunitas
 * terbedakan dengan kata, bukan garis aksen atau warna saja. Pasangan warnanya sudah
 * dihitung ColourContrastTest di kedua tema.
 */

export function LabelResmi() {
    return <span className="inline-flex items-center rounded-control border border-control px-2 py-0.5 text-xs font-medium text-primary">Resmi</span>;
}

export function LabelKomunitas() {
    return <span className="inline-flex items-center rounded-control bg-community-50 px-2 py-0.5 text-xs font-medium text-community-900">Komunitas</span>;
}
