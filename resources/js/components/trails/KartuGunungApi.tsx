import { Kesegaran, nadaKesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import type { GunungApi } from '@/components/trails/types';
import { Icon } from '@/components/ui/Icon';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';

/** Kalimat tetap DK-43: level gunung api bukan status akses. */
export const KALIMAT_TERPISAH = 'Disimpan terpisah dari status akses, dan tidak pernah diterjemahkan menjadi Buka atau Tutup.';

/** Tautan laporan PVMBG yang dikutip, ke tab baru. */
export function TautanLaporan({ href }: { href: string }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className={kelas('inline-flex min-h-11 items-center gap-1 rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
        >
            Buka laporan PVMBG
            <Icon name="arrow-square-out" size={16} />
            <span className="sr-only"> (membuka tab baru)</span>
        </a>
    );
}

/**
 * Status gunung api PVMBG (DK-43; spec v3 bagian 6.3 dan 6.4, prototype 03 dan 04; ruling W8-R18):
 * level dan rekomendasi radius, atribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)", waktu
 * terbit, kesegaran, dan tautan laporannya. Tampil hanya bila ada; kalimat tetapnya menyatakan
 * bahwa level tidak pernah menjadi Buka atau Tutup.
 */
export function KartuGunungApi({ data }: { data: GunungApi }) {
    return (
        <section aria-labelledby="judul-gunung-api" className="rounded-lg border-[1.5px] border-brand-ink bg-surface p-5">
            <div className="flex flex-wrap items-center gap-2">
                <LabelSumber jenis="OFFICIAL" />
                <h3 id="judul-gunung-api" className="text-meta font-bold text-secondary">
                    Status gunung api (PVMBG)
                </h3>
            </div>
            <p className="mt-2 text-bagian text-primary">{data.label}</p>
            <p className="mt-1 max-w-prose break-words text-kecil text-primary">{data.rekomendasi}</p>
            <p className="mt-2 text-kecil text-secondary">{KALIMAT_TERPISAH}</p>
            <p className="mt-2 break-words text-meta text-muted">
                Sumber: {data.atribusi}. Diterbitkan {data.diterbitkan}.
            </p>
            <Kesegaran waktu={data.diverifikasi} awalan="Dicocokkan kurator" keadaan={data.kesegaran} className="mt-1" />
            <TautanLaporan href={data.tautan} />
        </section>
    );
}

/** Baris ringkas untuk "Kondisi terkini" Cek kesiapan (prototype 10; W4-R29). */
export function BarisGunungApi({ data }: { data: GunungApi }) {
    return (
        <div className="border-t border-subtle pt-3">
            <p className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-meta text-secondary">Gunung api</span>
                <Lencana nada={nadaKesegaran(data.kesegaran)}>{data.kesegaran}</Lencana>
            </p>
            <p className="mt-1 text-kecil font-bold text-primary">{data.label}</p>
            <p className="mt-1 break-words text-kecil text-secondary">{data.rekomendasi}</p>
            <p className="mt-1 text-meta text-secondary">{KALIMAT_TERPISAH}</p>
            <p className="mt-1 break-words text-meta text-muted">
                Sumber: {data.atribusi}, diterbitkan {data.diterbitkan}; dicocokkan kurator {data.diverifikasi}.
            </p>
            <TautanLaporan href={data.tautan} />
        </div>
    );
}
