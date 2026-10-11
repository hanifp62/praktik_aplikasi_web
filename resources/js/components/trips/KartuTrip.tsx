import { FitTrip } from '@/components/trips/FitTrip';
import { Button } from '@/components/ui/Button';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { BarisTrip } from './types';

/**
 * Satu trip di indeks (spec v3 bagian 6.9). Bentuk `utama` untuk trip terdekat: judul kartu,
 * langkah berikutnya, kecocokan dasar, dan satu aksi utama selebar kartu di ponsel. Bentuk
 * bawaan untuk kelompok: nama sebagai tautan, satu baris langkah berikutnya, dan aksinya
 * sebagai tautan teks, supaya hanya kartu utama yang membawa tombol penuh. Peringatan kesiapan
 * (vonis basi atau jalur ditarik) tidak diulang bila langkah berikutnya sudah menyebutnya.
 *
 * Tingkat judul mengikuti tempat kartu (tinjauan Tugas 2 M9): kartu utama langsung di bawah
 * judul halaman memakai h2, kartu di dalam kelompok berjudul h2 memakai h3.
 */
export function KartuTrip({ kartu, utama = false }: { kartu: BarisTrip; utama?: boolean }) {
    const peringatanKesiapan = kartu.kesiapan && kartu.fase?.kunci !== 'kesiapan' ? kartu.kesiapan : null;
    const Judul = utama ? 'h2' : 'h3';

    return (
        <article className={kelas('border border-subtle bg-surface', utama ? 'rounded-utama p-5 sm:p-6' : 'rounded-lg p-4')}>
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada={utama ? 'merek' : 'netral'}>{kartu.jenis}</Lencana>
                {kartu.hari ? (
                    <Lencana>
                        <span data-angka>{kartu.hari}</span>
                    </Lencana>
                ) : null}
                {kartu.lewat ? (
                    <Lencana nada="peringatan" ikon="warning">
                        {kartu.lewat}
                    </Lencana>
                ) : null}
                {kartu.keadaan ? <Lencana>{kartu.keadaan}</Lencana> : null}
            </p>

            <Judul className={kelas('mt-2 break-words text-primary', utama ? 'text-judul-kartu' : 'text-subjudul')}>
                <Tautan href={kartu.url} className="inline-flex min-h-11 items-center rounded-sm underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    {kartu.nama}
                </Tautan>
            </Judul>
            <p className="mt-1 break-words text-kecil text-secondary">
                {kartu.jalur}, {kartu.gunung}
            </p>
            <p data-angka className="mt-0.5 text-kecil text-secondary">
                {kartu.tanggal}
            </p>

            {kartu.fase ? (
                <p className={kelas('mt-3 text-kecil', kartu.fase.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>
                    <span className="font-bold text-primary">Langkah berikutnya: {kartu.fase.judul}.</span> {kartu.fase.keterangan}
                </p>
            ) : null}
            {peringatanKesiapan ? (
                <p className={kelas('mt-2 text-kecil', peringatanKesiapan.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>{peringatanKesiapan.teks}</p>
            ) : null}

            {utama && kartu.fit ? <FitTrip fit={kartu.fit} /> : null}

            {kartu.aksi ? (
                utama ? (
                    <Button href={kartu.aksi.href} className="mt-4 w-full sm:w-auto">
                        {kartu.aksi.label}
                    </Button>
                ) : (
                    <Tautan
                        href={kartu.aksi.href}
                        className="mt-2 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        {kartu.aksi.label}
                    </Tautan>
                )
            ) : null}
        </article>
    );
}
