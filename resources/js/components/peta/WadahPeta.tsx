import { usePage } from '@inertiajs/react';
import type { ReactNode, RefObject } from 'react';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

interface WadahPetaProps {
    /** Wadah yang diisi MapLibre; sama dengan yang diberikan ke usePetaDasar. */
    wadahRef: RefObject<HTMLDivElement | null>;
    id?: string;
    label: string;
    gagal: boolean;
    /** Kalimat sesudah "Peta gagal dimuat.": apa yang tetap dapat dipakai pembaca. */
    cadangan: string;
    /** Tinggi kotak, atau flex-1 bila tingginya diatur induk. Tanpa ini kotak mengikuti isinya. */
    ukuran?: string;
    /** Keterangan yang mendahului atribusi pada baris yang sama. */
    keterangan?: string;
    /** Kendali yang melayang di atas peta. */
    children?: ReactNode;
}

/**
 * Kotak peta beserta atribusinya.
 *
 * role="region", bukan "img": peta yang dapat digeser adalah wilayah interaktif. Peran
 * gambar menyembunyikan isinya dari pembaca layar dan menuntut teks alternatif yang
 * mustahil untuk peta; padanan non-visualnya adalah daftar yang menyertai setiap peta.
 * Atribusi bukan hiasan: syarat pemakaian sumber tile-nya.
 */
export function WadahPeta({ wadahRef, id, label, gagal, cadangan, ukuran, keterangan, children }: WadahPetaProps) {
    const { peta } = usePage<PropsBersama>().props;

    return (
        <>
            <div id={id} role="region" aria-label={label} className={kelas('relative overflow-hidden rounded-utama border border-subtle bg-surface-sunken', ukuran)}>
                <div ref={wadahRef} hidden={gagal} className="h-full w-full" />
                {gagal ? <p className="p-4 text-sm text-secondary">Peta gagal dimuat. {cadangan}</p> : null}
                {children}
            </div>
            <p className="text-xs text-muted">
                {keterangan ? `${keterangan} ` : null}Peta: {peta.attribution}
            </p>
        </>
    );
}
