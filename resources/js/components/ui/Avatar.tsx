import { kelas } from '@/lib/kelas';

/** Inisial dari huruf pertama kata pertama dan kata terakhir: "Budi Setiawan" menjadi "BS". */
export function inisial(nama: string): string {
    const kata = nama.trim().split(/\s+/).filter(Boolean);
    const pertama = kata[0] ?? '';
    const terakhir = kata.length > 1 ? (kata[kata.length - 1] ?? '') : '';

    return (pertama.charAt(0) + terakhir.charAt(0)).toLocaleUpperCase('id-ID');
}

const kelasUkuran = {
    32: 'size-8 text-label',
    44: 'size-11 text-kecil',
    64: 'size-16 text-bagian',
} as const;

interface AvatarProps {
    nama: string;
    foto?: string | null;
    ukuran?: keyof typeof kelasUkuran;
    /** Bawaan true: avatar di samping nama yang tertulis adalah pengulangan bagi pembaca layar. */
    hiasan?: boolean;
    className?: string;
}

/**
 * Avatar: foto unggahan pengguna atau inisial (spec v3 bagian 8.7). Data contoh memakai
 * inisial, tidak pernah wajah stok (antislop R-23).
 */
export function Avatar({ nama, foto, ukuran = 44, hiasan = true, className }: AvatarProps) {
    const dasar = kelas('inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 font-bold text-brand-900', kelasUkuran[ukuran], className);

    if (foto) {
        return <img src={foto} alt={hiasan ? '' : nama} width={ukuran} height={ukuran} className={kelas(dasar, 'object-cover')} />;
    }

    return hiasan ? (
        <span aria-hidden="true" className={dasar}>
            {inisial(nama)}
        </span>
    ) : (
        <span role="img" aria-label={nama} className={dasar}>
            {inisial(nama)}
        </span>
    );
}
