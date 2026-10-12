import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { IzinGunung } from './types';

/**
 * Izin pendakian tingkat gunung (spec v3 bagian 6.3, PRD §153). Memount mencatat aturan
 * pengelola dan menautkan sistem resminya; ia tidak memesan, menjual, atau melacak kuota.
 * Kuota yang tidak dicatat aturannya ditulis "Tidak tercatat di aturan ini.", tidak ditebak
 * (R-007). Tautan keluar membawa ikon tautan keluar dan membuka tab baru yang disebutkan.
 */
export function KartuIzin({ izin }: { izin: IzinGunung }) {
    const butir: Array<[string, string]> = [
        ['Dikelola', izin.pengelola],
        ['Jendela pesan', izin.jendela ?? 'Belum tercatat. Periksa ke pengelola.'],
        // P-5 (perbaikan 1 W3): kuota kosong bukan berarti aturan ini tidak terverifikasi --
        // PermitRequirementRequest mewajibkan kuota minimal 1 atau kosong, jadi kosong
        // berarti aturan itu memang tidak mencatat kuota (R-007: tidak menebak angka).
        ['Kuota harian', izin.kuota ?? 'Tidak tercatat di aturan ini.'],
    ];

    if (izin.pemandu) {
        butir.push(['Pemandu', izin.pemandu]);
    }

    return (
        <div className="rounded-lg border border-subtle bg-surface p-5">
            <dl className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)] gap-x-4 gap-y-3 text-kecil">
                {butir.map(([label, nilai]) => (
                    <div key={label} className="contents">
                        <dt className="text-secondary">{label}</dt>
                        <dd className="wrap-break-word font-bold text-primary">{nilai}</dd>
                    </div>
                ))}
            </dl>
            {izin.catatan ? <p className="mt-3 wrap-break-word text-kecil text-secondary">{izin.catatan}</p> : null}
            {izin.bookingUrl ? (
                <a
                    href={izin.bookingUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={kelas('mt-4 flex min-h-11 w-full items-center justify-center gap-2 rounded-tombol border border-control bg-surface px-5 py-3 text-center text-isi font-bold text-primary hover:bg-surface-sunken', fokusTerlihat, umpanTekan)}
                >
                    Buka sistem pemesanan resmi
                    <Icon name="arrow-square-out" size={20} />
                    <span className="sr-only">(membuka tab baru)</span>
                </a>
            ) : null}
            {/* Klaim "menautkan" hanya benar ketika tautannya sungguh tampil di atas (temuan
                tangkapan W3 butir 11); tanpa tautan, klaim yang tersisa adalah yang tidak
                pernah menjual atau memesan sendiri (R-007: tidak mengklaim yang tidak ada). */}
            <p className="mt-3 text-meta text-secondary">{izin.bookingUrl ? 'Memount menautkan sistem resmi dan tidak menjual izin.' : 'Memount tidak memesan atau menjual izin.'}</p>
            <div className="mt-2 flex flex-wrap items-center gap-2">
                <LabelSumber jenis={izin.sumber.jenis} />
                <span className="wrap-break-word text-meta text-muted">Sumber: {izin.sumber.nama ?? 'belum tercatat'}</span>
            </div>
            <Kesegaran waktu={izin.diverifikasi} awalan="Aturan diverifikasi" className="mt-1" />
        </div>
    );
}
