import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import type { StatusRingkas } from '@/components/trails/types';
import { kelas } from '@/lib/kelas';

/**
 * Kalimat cakupan per nilai status (P-4, perbaikan 1 W3, §42): "tidak berarti semua jalur
 * buka" hanya benar untuk Buka. Tutup dan Dibatasi justru berlaku untuk semua jalur lewat
 * kaskade §42 (OfficialStatusService::cascade()), jadi keduanya mendapat kalimat sendiri
 * yang tidak menyangkal kaskade itu.
 */
const kalimatCakupan: Record<'OPEN' | 'CLOSED' | 'RESTRICTED', string> = {
    OPEN: 'Status di tingkat gunung tidak berarti semua jalur buka. Status tiap jalur ada di bawah.',
    CLOSED: 'Penutupan tingkat gunung berlaku untuk semua jalurnya. Status tiap jalur ada di bawah.',
    RESTRICTED: 'Pembatasan tingkat gunung berlaku untuk semua jalurnya, kecuali jalur yang tutup. Status tiap jalur ada di bawah.',
};

/**
 * Status akses tingkat gunung (spec v3 bagian 6.3). Kaskade §42 selalu ikut lewat
 * `kalimatCakupan`: kalimatnya berbeda menurut nilai statusnya, bukan generik untuk setiap
 * catatan yang ada (P-4). Garis padat merek hanya untuk catatan bersumber resmi (spec v3
 * bagian 7); catatan lain bergaris rambut biasa. Tanpa catatan, kartunya menyebut bahwa
 * status belum diketahui dan tidak dianggap buka.
 *
 * Kalimat R-012 ("bukan jaminan keselamatan") tidak lagi menempel hanya pada cakupan Buka
 * (P-1, perbaikan 1 W3): ia dirender tanpa syarat di bawah, dengan rumusan yang sama persis
 * dengan rincian jalur (Kondisi.tsx), supaya tidak bergantung pada gunung ini punya catatan
 * status sendiri atau tidak -- jalur di bawahnya bisa saja Buka lewat catatannya sendiri
 * walau gunungnya tidak punya catatan apa pun.
 */
export function KartuStatusGunung({ status }: { status: StatusRingkas }) {
    const resmi = status.sumber?.jenis === 'OFFICIAL';

    return (
        <section aria-labelledby="judul-status-gunung" className={kelas('rounded-lg bg-surface p-5', resmi ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}>
            <div className="flex flex-wrap items-center gap-2">
                {status.sumber ? <LabelSumber jenis={status.sumber.jenis} /> : null}
                <h3 id="judul-status-gunung" className="text-meta font-bold text-secondary">
                    Status akses gunung
                </h3>
            </div>
            <div className="mt-2">
                <StatusAkses nilai={status.status} label={status.label} ukuran="bagian" />
            </div>
            <p className="mt-2 text-kecil text-secondary">
                {status.status === null
                    ? 'Belum ada status resmi yang berlaku untuk gunung ini, jadi statusnya tidak dianggap buka. Status tiap jalur ada di bawah.'
                    : kalimatCakupan[status.status]}
            </p>
            <p className="mt-2 text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
            {status.sumber ? (
                <p className="mt-2 break-words text-meta text-muted">
                    Sumber: {status.sumber.nama ?? 'belum tercatat'}
                    {status.cakupan ? `, cakupan ${status.cakupan.toLowerCase()}` : null}.
                </p>
            ) : null}
            {status.sumber ? <Kesegaran waktu={status.waktu} awalan={status.awalanWaktu ?? undefined} keadaan={status.kesegaran} className="mt-1" /> : null}
        </section>
    );
}
