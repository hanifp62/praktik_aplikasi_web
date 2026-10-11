import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';
import { nadaKesegaran } from './Kesegaran';
import { StatusAkses } from './StatusAkses';
import type { StatusRingkas } from './types';

/**
 * Status akses dalam satu baris daftar: bentuk dan kata (DK-32), lalu dari mana dan kapan
 * ("Resmi, 12 Sep"). Kata jenis sumbernya datang dari server (R-009), jadi "Resmi" hanya
 * tertulis untuk catatan bersumber OFFICIAL. Status yang belum diketahui menyebut dirinya
 * tidak dianggap buka (§95), tidak dibiarkan terbaca netral. Berupa span berblok supaya dapat
 * duduk di dalam tautan baris tanpa menaruh paragraf di dalam elemen sebaris.
 *
 * P-2 (perbaikan 1 W3): dipakai lintas Jelajah, pratinjau peta, tautan bagikan, dan
 * Bandingkan (Tugas 4, 5, 8, 9), jadi kesegaran yang bukan "Terkini" wajib ikut tampil di
 * sini -- tanpa itu catatan yang perlu ditinjau atau sudah kedaluwarsa terbaca sama segarnya
 * dengan catatan yang baru diverifikasi di setiap tempat yang memakai komponen ini.
 */
export function BarisStatus({ status, className }: { status: StatusRingkas; className?: string }) {
    const asal = status.sumber ? [status.sumber.label ?? status.sumber.nama, status.waktu].filter(Boolean).join(', ') : null;

    return (
        <span className={kelas('flex flex-wrap items-center gap-x-2 gap-y-1', className)}>
            <StatusAkses nilai={status.status} label={status.label} cakupan={status.cakupan === 'Gunung' ? status.cakupan : null} />
            {asal ? <span className="break-words text-meta text-muted">{asal}</span> : null}
            {status.status === null ? <span className="text-meta text-muted">Tidak dianggap buka</span> : null}
            {status.kesegaran && status.kesegaran !== 'Terkini' ? <Lencana nada={nadaKesegaran(status.kesegaran)}>{status.kesegaran}</Lencana> : null}
        </span>
    );
}
