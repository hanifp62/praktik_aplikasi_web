/**
 * Glyph domain pilot dari tanda merek "Jalur Berpos" (spec v3 bagian 8.3, DK-39, keputusan
 * pemilik D4).
 *
 * Tata bahasanya sama dengan tanda logo: garis rute monoline berujung bulat, belokan
 * bersudut, dan cincin pos. Digambar sebagai goresan pada kisi 256 unit yang sama dengan
 * ikon lain, dengan titik di dalam area hidup 32 sampai 224, sehingga tebalnya mengikuti
 * bobot per ukuran: goresan 24 unit di bawah 20 px, 16 unit mulai 20 px.
 *
 * Hanya tiga glyph pilot (ruling W1-R3). Set lengkap 8 sampai 12 glyph menunggu uji
 * pengenalan dengan 5 sampai 8 pendaki (spec v3 bagian 15, Q15).
 *
 * - pos: rute zig-zag tiga ruas yang naik dari kiri bawah ke cincin pos di kanan atas,
 *   sudut dalam 60 sampai 100 derajat di tiap belokan (82,0 dan 63,0 derajat; DK-16:
 *   "garis rute zig-zag berujung cincin pos"). Direvisi ruling W1-4a: geometri semula satu garis lurus miring berujung
 *   cincin terbaca sebagai kaca pembesar, melanggar kriteria penerimaan brief sendiri.
 * - tanjakan: jalur datar lalu naik 60 derajat, mata panah di ujung tanjakan.
 * - resmi: cap bergagang di atas alasnya, pengganti perisai bercentang. Hanya untuk
 *   keterangan bersumber OFFICIAL (R-009), tidak pernah untuk ADMIN VERIFIED atau komunitas.
 */
export interface GlyphDomain {
    garis: readonly string[];
    cincin: ReadonlyArray<{ cx: number; cy: number; r: number }>;
}

export type NamaGlyph = 'pos' | 'tanjakan' | 'resmi';

export const glyphDomain: Record<NamaGlyph, GlyphDomain> = {
    pos: {
        garis: ['M40 208 L100 176 L68 132 L152 118.6'],
        cincin: [{ cx: 172, cy: 84, r: 40 }],
    },
    tanjakan: {
        garis: ['M40 200 L96 200 L168 76', 'M126.4 100 L168 76 L168 124'],
        cincin: [],
    },
    resmi: {
        garis: ['M112 108.2 L104 148', 'M144 108.2 L152 148', 'M48 148 L208 148 L208 184 L48 184 Z', 'M64 216 L192 216'],
        cincin: [{ cx: 128, cy: 76, r: 36 }],
    },
};
