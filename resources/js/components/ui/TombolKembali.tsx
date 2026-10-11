import { kelas } from '@/lib/kelas';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Icon } from './Icon';
import { Tautan } from './Tautan';

interface TombolKembaliProps {
    /** Alamat induk logis halaman ini, bukan riwayat peramban. */
    href: string;
    /** Nama induknya, dibacakan "Kembali ke ...". */
    label: string;
}

/**
 * Tombol kembali halaman turunan (spec v3 bagian 5.1): lingkaran 44 px yang menuju induk
 * logisnya. Sengaja tautan biasa, bukan history.back(): pendaki yang membuka detail jalur
 * dari tautan bagikan, notifikasi surel, atau tab baru tidak punya riwayat, dan tombol
 * kembali yang tidak membawa ke mana pun adalah kontrol mati.
 */
export function TombolKembali({ href, label }: TombolKembaliProps) {
    return (
        <Tautan
            href={href}
            className={kelas(
                'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-subtle bg-surface text-primary hover:bg-surface-sunken',
                fokusTerlihat,
                umpanTekan,
            )}
        >
            <Icon name="arrow-left" size={20} />
            <span className="sr-only">Kembali ke {label}</span>
        </Tautan>
    );
}
