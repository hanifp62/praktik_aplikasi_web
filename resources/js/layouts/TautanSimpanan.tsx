import { usePage } from '@inertiajs/react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { saved } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface TautanSimpananProps {
    /**
     * Header layar lebar di bawah 1280 px: kata "Simpanan" hanya dibacakan, yang tampil ikon
     * dan hitungannya, supaya navigasi segmen tetap satu baris di 1024 px.
     */
    ringkas?: boolean;
    className?: string;
}

/**
 * Tautan Simpanan dengan hitungannya (spec v3 bagian 5.2; prototype 02 dan 15). Hitungan
 * datang dari props bersama jumlahSimpanan, jumlah yang sama dengan daftar di halaman
 * Simpanan. Nol tetap ditulis: nol adalah keterangan, bukan kekosongan.
 */
export function TautanSimpanan({ ringkas = false, className }: TautanSimpananProps) {
    const { jumlahSimpanan } = usePage<PropsBersama>().props;

    if (jumlahSimpanan === null) {
        return null;
    }

    return (
        <Tautan
            href={saved.url()}
            className={kelas(
                'inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full border border-subtle bg-surface px-3.5 text-kecil font-bold text-primary hover:bg-surface-sunken',
                fokusTerlihat,
                umpanTekan,
                className,
            )}
        >
            <Icon name="bookmark-simple" size={16} />
            <span className={kelas(ringkas && 'sr-only xl:not-sr-only')}>Simpanan</span>
            <span data-angka="">{jumlahSimpanan}</span>
            <span className="sr-only"> jalur tersimpan</span>
        </Tautan>
    );
}
