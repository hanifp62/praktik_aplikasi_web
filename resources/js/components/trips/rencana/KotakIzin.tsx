import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';

/** Jendela izin untuk tanggal yang dipilih, dari App\Services\Trips\RencanaTrip::izin(). */
export interface IzinRencana {
    keadaan: 'terbuka' | 'belum' | 'tutup' | 'bukaBelumTercatat' | 'tanpaTanggal' | 'tanpaJendela';
    judul: string;
    keterangan: string;
    /** Dari penentu ajakan pemesanan yang sama dengan Persiapan dan Cek (PermitService::pemesanan): null untuk jendela tutup atau belum dibuka. */
    tautan: { href: string; teks: string } | null;
    kuota: string;
}

/**
 * Kotak jendela pemesanan izin (prototype 08): judul yang menyebut tanggal buka atau tutup,
 * pengelola dan aturannya, dan tautan ke sistem resmi bila jendelanya mengizinkan. Teks
 * tautannya dari server ("Pesan di sistem resmi" atau "Periksa di sistem resmi"), jadi kotak
 * ini tidak pernah mengajak memesan di bawah judul "sudah tutup" atau "dibuka mulai". Jendela
 * yang sudah tutup memakai nada dan ikon peringatan, dan judulnya berkata "sudah tutup", jadi
 * bedanya terbaca dari ikon dan kata, bukan warna saja. Memount tidak memesan izin.
 */
export function KotakIzin({ izin, className }: { izin: IzinRencana; className?: string }) {
    const tutup = izin.keadaan === 'tutup';

    return (
        <div className={kelas('rounded-lg border p-4', tutup ? 'border-warn-300 bg-warn-50' : 'border-brand-200 bg-brand-50', className)}>
            <div className="flex gap-3">
                {tutup ? <Icon name="warning" size={20} className="mt-0.5 text-warn-900" /> : null}
                <div className="min-w-0">
                    <p className={kelas('text-isi font-bold', tutup ? 'text-warn-900' : 'text-primary')}>{izin.judul}</p>
                    <p className="mt-1 wrap-break-word text-kecil text-secondary">{izin.keterangan}</p>
                    {izin.tautan ? (
                        <a
                            href={izin.tautan.href}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={kelas('mt-2 inline-flex min-h-11 items-center gap-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat, umpanTekan)}
                        >
                            {izin.tautan.teks}
                            <Icon name="arrow-square-out" size={16} />
                            <span className="sr-only">(membuka tab baru)</span>
                        </a>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
