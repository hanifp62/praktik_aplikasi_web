import { Lencana, type NadaLencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';

const nadaKeadaan: Record<string, NadaLencana> = {
    Terkini: 'merek',
    'Perlu ditinjau': 'peringatan',
    Kedaluwarsa: 'bahaya',
    // Kata cuaca (FreshnessState::label()), sejajar dengan tiga kata status resmi di atas.
    Terbaru: 'merek',
    'Mulai lama': 'peringatan',
    'Sudah lama': 'bahaya',
    'Tidak diketahui': 'netral',
};

/**
 * Nada lencana untuk sebuah keadaan kesegaran, dipakai `Kesegaran` sendiri dan komponen lain
 * yang perlu menandai kesegaran tanpa merender `Kesegaran` utuh (P-2, perbaikan 1 W3):
 * `BarisStatus` memakainya supaya catatan yang bukan "Terkini" tidak terbaca segar di
 * Jelajah, pratinjau peta, tautan bagikan, dan Bandingkan (Tugas 4, 5, 8, 9).
 */
export function nadaKesegaran(keadaan: string): NadaLencana {
    return nadaKeadaan[keadaan] ?? 'netral';
}

interface KesegaranProps {
    /** Waktu yang sudah diformat server dalam zona gunung beserta penandanya ("18 Sep 2026 14.00 WIB"). */
    waktu: string | null;
    awalan?: string;
    /** Keadaan kesegaran dari server (DataFreshnessService::keadaan atau FreshnessState). */
    keadaan?: string | null;
    className?: string;
}

/**
 * Kesegaran data (PRD §93, spec v3 bagian 7): kapan sebuah keterangan terakhir diperiksa,
 * dan apakah ia masih terkini. Klien tidak pernah mengonversi waktu sendiri; waktunya datang
 * sudah diformat dalam zona gunungnya. Ketiadaan waktu disebut, tidak dikosongkan.
 * Dipindah dari components/ui/Freshness.tsx dan ditulis ulang W3.
 */
export function Kesegaran({ waktu, awalan = 'Diperbarui', keadaan, className }: KesegaranProps) {
    return (
        <p className={kelas('flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-muted', className)}>
            <span>{waktu ? `${awalan} ${waktu}` : 'Waktu pembaruan tidak tersedia'}</span>
            {keadaan ? <Lencana nada={nadaKeadaan[keadaan] ?? 'netral'}>{keadaan}</Lencana> : null}
        </p>
    );
}
