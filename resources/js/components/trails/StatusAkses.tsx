import { kelas } from '@/lib/kelas';

export type StatusResmi = 'OPEN' | 'RESTRICTED' | 'CLOSED' | null;

/**
 * Bentuk per status (DK-32): Buka lingkaran, Dibatasi belah ketupat, Tutup persegi, Belum
 * diketahui lingkaran putus-putus. Bentuk yang membawa arti, bukan warnanya: mode
 * forced-colors menimpa fill dan stroke, dan bentuknya tetap terbedakan.
 */
function Bentuk({ nilai }: { nilai: StatusResmi }) {
    return (
        <svg viewBox="0 0 16 16" aria-hidden="true" className="size-4 shrink-0">
            {nilai === 'OPEN' ? <circle cx="8" cy="8" r="5" className="fill-status-buka" /> : null}
            {nilai === 'RESTRICTED' ? <rect x="4" y="4" width="8" height="8" transform="rotate(45 8 8)" className="fill-status-dibatasi" /> : null}
            {nilai === 'CLOSED' ? <rect x="3.5" y="3.5" width="9" height="9" className="fill-status-tutup" /> : null}
            {nilai === null ? <circle cx="8" cy="8" r="5" fill="none" strokeWidth="2" strokeDasharray="2.5 2" className="stroke-status-belum" /> : null}
        </svg>
    );
}

interface StatusAksesProps {
    nilai: StatusResmi;
    /** Kata dari server (OfficialStatusValue::label). */
    label?: string | null;
    /** Cakupan catatan penentu, misalnya "Gunung" ketika status turun dari gunungnya (§42). */
    cakupan?: string | null;
    /** "bagian" untuk kartu status utama (18 px); bawaan 14 px untuk baris daftar. */
    ukuran?: 'kecil' | 'bagian';
    className?: string;
}

/**
 * Status akses resmi sebagai bentuk dan kata (DK-32, WCAG 1.4.1). OPEN bukan jaminan
 * keselamatan (R-012): komponen ini hanya menyebut keadaan operasionalnya, dan layar utama
 * yang memakainya menambahkan kalimat R-012. Status kosong adalah UNKNOWN, tidak pernah
 * Buka (§95). Dipindah dari components/ui/StatusBadge.tsx dan ditulis ulang W3.
 */
export function StatusAkses({ nilai, label, cakupan, ukuran = 'kecil', className }: StatusAksesProps) {
    return (
        <span
            data-status-akses={nilai ?? 'UNKNOWN'}
            className={kelas('inline-flex max-w-full items-center gap-1.5 font-bold text-primary', ukuran === 'bagian' ? 'text-bagian' : 'text-kecil', className)}
        >
            <Bentuk nilai={nilai} />
            <span className="sr-only">Status resmi:</span>
            <span className="min-w-0 break-words">
                {label ?? 'Belum diketahui'}
                {cakupan ? <span className="font-semibold text-secondary"> (cakupan {cakupan.toLowerCase()})</span> : null}
            </span>
        </span>
    );
}
