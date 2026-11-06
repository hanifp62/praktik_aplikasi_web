/*! Ikon Phosphor (phosphor-icons/core 2.1.1), MIT License, Copyright (c) 2023 Phosphor Icons. Teks lisensi: resources/js/components/ui/LICENSE-phosphor.txt */
import { kelas } from '@/lib/kelas';
import { glyphDomain, type NamaGlyph } from './ikon/glyphDomain';
import { phosphor, type NamaPhosphor, type VarianPhosphor } from './ikon/phosphor';

export type NamaIkon = NamaPhosphor | NamaGlyph;

/** Ukuran render dalam px. Tidak ada ikon informatif di bawah 16 px (DK-39). */
export type UkuranIkon = 16 | 20 | 22 | 24 | 28 | 32;

const kelasUkuran: Record<UkuranIkon, string> = {
    16: 'size-4',
    20: 'size-5',
    22: 'size-[22px]',
    24: 'size-6',
    28: 'size-7',
    32: 'size-8',
};

/**
 * Bobot garis menurut ukuran render (spec v3 bagian 8.3): Bold di bawah 20 px supaya garis
 * tidak menipis menjadi rambut di layar ponsel, Regular mulai 20 px.
 */
export function bobotUntuk(ukuran: UkuranIkon): 'bold' | 'regular' {
    return ukuran < 20 ? 'bold' : 'regular';
}

function adalahGlyph(nama: NamaIkon): nama is NamaGlyph {
    return nama in glyphDomain;
}

interface IconProps {
    name: NamaIkon;
    size?: UkuranIkon;
    /**
     * Varian isi, hanya untuk tab aktif: yang berubah adalah bentuk, bukan warna saja (WCAG 1.4.1).
     * Tidak berlaku untuk glyph domain, yang tidak punya varian isi.
     */
    isi?: boolean;
    /** Nama untuk ikon yang berdiri sendiri; tanpa label ikon disembunyikan dari pembaca layar. */
    label?: string;
    className?: string;
}

/**
 * Ikon Phosphor dan glyph domain, hanya di tempat yang membawa arti: arah naik dan turun,
 * penanda tab, jenis pos, dan aksi yang namanya sudah dibawa label. Glyph domain digambar
 * sebagai goresan dengan tebal Phosphor pada bobot yang sama (24 unit Bold, 16 unit
 * Regular). Nama yang tidak dikenal menghasilkan kekosongan, bukan kotak rusak.
 */
export function Icon({ name, size = 16, isi = false, label, className }: IconProps) {
    const kelasSvg = kelas(kelasUkuran[size], 'inline-block shrink-0', className);

    if (adalahGlyph(name)) {
        const glyph = glyphDomain[name];

        return (
            <svg
                viewBox="0 0 256 256"
                fill="none"
                stroke="currentColor"
                strokeWidth={bobotUntuk(size) === 'bold' ? 24 : 16}
                strokeLinecap="round"
                strokeLinejoin="round"
                className={kelasSvg}
                role={label ? 'img' : undefined}
                aria-hidden={label ? undefined : true}
            >
                {label ? <title>{label}</title> : null}
                {glyph.garis.map((d) => (
                    <path key={d} d={d} />
                ))}
                {glyph.cincin.map((c) => (
                    <circle key={`${c.cx}-${c.cy}`} cx={c.cx} cy={c.cy} r={c.r} />
                ))}
            </svg>
        );
    }

    const varian: VarianPhosphor | undefined = phosphor[name];
    const d = varian ? (isi && varian.fill ? varian.fill : varian[bobotUntuk(size)]) : undefined;

    if (!d) {
        return null;
    }

    return (
        <svg
            viewBox="0 0 256 256"
            fill="currentColor"
            className={kelasSvg}
            role={label ? 'img' : undefined}
            aria-hidden={label ? undefined : true}
        >
            {label ? <title>{label}</title> : null}
            <path d={d} />
        </svg>
    );
}
