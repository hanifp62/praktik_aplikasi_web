import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { KepalaTripData } from './types';

/**
 * Isi kepala satu trip di bawah judul halaman (prototype 09): jenis dan sisa hari sebagai
 * lencana, jalur dan gunungnya, lalu tanggal, jam, dan peserta sebagai butir terpisah. Tanpa
 * titik tengah: baris ini meta, bukan baris ukuran padat (spec v3 bagian 8.8). Yang belum
 * diisi pendaki tidak ditulis. "Ubah rencana" hanya tampil selama server mengizinkannya.
 * Rencana yang belum dimulai tetapi tanggalnya sudah lewat menyebut itu dengan kata dan ikon,
 * bukan warna saja, di tempat hitung mundurnya (ruling W4-1c).
 */
export function KepalaTrip({ kepala }: { kepala: KepalaTripData }) {
    return (
        <div className="mt-2 space-y-2">
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada="merek">{kepala.jenis}</Lencana>
                {kepala.hari ? (
                    <Lencana>
                        <span data-angka>{kepala.hari}</span>
                    </Lencana>
                ) : null}
                {kepala.lewat ? (
                    <Lencana nada="peringatan" ikon="warning">
                        {kepala.lewat}
                    </Lencana>
                ) : null}
                {kepala.keadaan ? <Lencana>{kepala.keadaan}</Lencana> : null}
            </p>
            <p className="text-isi text-secondary">
                {kepala.jalur}, {kepala.gunung}
            </p>
            <p className="flex flex-wrap gap-x-4 gap-y-1 text-kecil text-secondary">
                <span data-angka>{kepala.tanggal}</span>
                {kepala.jam ? <span data-angka>Mulai {kepala.jam}</span> : null}
                {kepala.peserta ? <span data-angka>{kepala.peserta}</span> : null}
            </p>
            {kepala.ubah ? (
                <Tautan
                    href={kepala.ubah}
                    className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    Ubah rencana
                </Tautan>
            ) : null}
        </div>
    );
}
