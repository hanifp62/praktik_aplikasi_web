import { Icon } from '@/components/ui/Icon';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import type { FotoGunungData } from './types';

const chip = 'absolute bottom-2 right-2 inline-flex min-h-11 max-w-[85%] items-center gap-1 rounded-control bg-surface px-3 py-1 text-meta text-primary shadow-overlay';

/**
 * Foto gunung berlisensi sebagai kepala halaman (spec v3 bagian 6.3 dan 8.7; DK-37; ruling W8-R19).
 * Atribusinya selalu di pojok foto, di atas keping permukaan buram supaya kontrasnya tidak
 * bergantung pada isi foto; bila sumbernya tercatat, keping itu menautkan ke sumber. Hanya foto
 * dari tabel foto gunung yang sampai ke sini: foto laporan komunitas tidak pernah menjadi kepala
 * halaman (R-017).
 */
export function FotoGunung({ foto }: { foto: FotoGunungData }) {
    return (
        <figure className="relative overflow-hidden rounded-utama border border-subtle bg-surface-sunken">
            <img src={foto.url} alt={foto.alt} width={foto.lebar} height={foto.tinggi} decoding="async" className="aspect-[16/9] h-auto w-full object-cover" />
            <figcaption>
                {foto.sumber ? (
                    <a href={foto.sumber} target="_blank" rel="noopener noreferrer" className={kelas(chip, 'underline-offset-2 hover:underline', fokusTerlihat)}>
                        <span className="break-words">{foto.atribusi}</span>
                        <Icon name="arrow-square-out" size={16} />
                        <span className="sr-only"> (sumber foto, membuka tab baru)</span>
                    </a>
                ) : (
                    <span className={chip}>
                        <span className="break-words">{foto.atribusi}</span>
                    </span>
                )}
            </figcaption>
        </figure>
    );
}

