import type { NamaIkon } from '@/components/ui/Icon';
import { Lencana, type NadaLencana } from '@/components/ui/Lencana';

/** Jenis sumber (App\Enums\SourceType), dalam urutan otoritas R-009. */
export type JenisSumber = 'OFFICIAL' | 'ADMIN_VERIFIED' | 'ACCREDITED_EXPERT' | 'COMMUNITY' | 'AI_INTERPRETATION';

/** Sumber satu keterangan sebagaimana dikirim server: jenis dari data_sources, bukan tebakan. */
export interface SumberKeterangan {
    jenis: JenisSumber | null;
    label: string | null;
    nama: string | null;
}

/*
 * Glyph "resmi" hanya ada di baris OFFICIAL (R-009, ruling W1 Tugas 4): cap itu adalah
 * klaim otoritas, dan keterangan yang disalin admin, disumbang ahli, atau dilaporkan
 * komunitas tidak boleh meminjamnya. TrustGrammarTest menolak glyph ini di tempat lain.
 */
const rupa: Record<JenisSumber, { label: string; ikon: NamaIkon | null; nada: NadaLencana; garis: 'padat' | 'putus' }> = {
    OFFICIAL: { label: 'Resmi', ikon: 'resmi', nada: 'merek', garis: 'padat' },
    ADMIN_VERIFIED: { label: 'Terverifikasi admin', ikon: 'check', nada: 'netral', garis: 'padat' },
    ACCREDITED_EXPERT: { label: 'Ahli bersertifikat', ikon: 'user', nada: 'netral', garis: 'padat' },
    COMMUNITY: { label: 'Komunitas', ikon: 'user', nada: 'komunitas', garis: 'putus' },
    AI_INTERPRETATION: { label: 'Interpretasi sistem', ikon: null, nada: 'netral', garis: 'putus' },
};

/**
 * Label otoritas sumber (PRD §92, R-009, R-010): keterangan resmi dan masukan komunitas
 * terbedakan dengan kata, ikon, dan garis (padat untuk resmi, putus-putus untuk komunitas),
 * tidak dengan warna saja. Pasangan warnanya dihitung ColourContrastTest. Tanpa jenis
 * sumber yang tercatat tidak ada label yang digambar: menyebut "Resmi" tanpa dasar adalah
 * klaim yang tidak dimiliki sistem.
 */
export function LabelSumber({ jenis, className }: { jenis: JenisSumber | null; className?: string }) {
    if (jenis === null) {
        return null;
    }

    const r = rupa[jenis];

    return (
        <Lencana nada={r.nada} garis={r.garis} ikon={r.ikon ?? undefined} className={className}>
            {r.label}
        </Lencana>
    );
}

/** Laporan pendaki: selalu Komunitas, lolos moderasi atau tidak tampil sama sekali (§57). */
export function LabelKomunitas() {
    return <LabelSumber jenis="COMMUNITY" />;
}
