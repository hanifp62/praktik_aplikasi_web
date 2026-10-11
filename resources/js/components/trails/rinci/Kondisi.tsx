import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Card } from '@/components/ui/Card';
import type { Cuaca, Perizinan, StatusResmiRinci } from './types';

/*
 * Aturan kartu di halaman rinci: kartu berarti blok ini punya sumber. Status resmi,
 * perizinan, prakiraan BMKG, dan laporan komunitas berasal dari pihak berbeda, dan §92
 * menuntut bedanya tetap terbaca: otoritasnya disebut dengan label kata di dekat
 * sumbernya (LabelSumber), bukan dengan garis aksen.
 */

export function StatusResmiKartu({ status }: { status: StatusResmiRinci }) {
    return (
        <Card title="Status resmi">
            <div className="flex flex-wrap items-center gap-3">
                <StatusAkses nilai={status.status} label={status.statusLabel} cakupan={status.scope} />
                <span className="inline-flex flex-wrap items-center gap-2 text-sm text-secondary">
                    <LabelSumber jenis={status.jenisSumber} />
                    Sumber: {status.source}
                </span>
            </div>
            {status.reason ? <p className="mt-2 text-sm text-secondary">{status.reason}</p> : null}
            <div className="mt-2">
                {/* Awalan dan kesegaran dari server (W3-2c, I1 tinjauan akhir W3), sama dengan kartu Jelajah. */}
                <Kesegaran waktu={status.waktu} awalan={status.awalanWaktu ?? undefined} keadaan={status.kesegaran} />
            </div>
            <p className="mt-2 text-xs text-muted">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
        </Card>
    );
}

/** PRD §36 Logistik. Sistem mencatat aturan pihak lain, tidak menggantikannya. */
export function PerizinanKartu({ izin }: { izin: Perizinan }) {
    const butir: Array<[string, string | null]> = [
        ['Penyelenggara', izin.authority],
        ['Kuota harian', izin.kuotaHarian],
        ['Batas pemesanan', izin.batasPemesanan],
        ['Durasi maksimum', izin.durasiMaksimum],
        ['Pemandu', izin.pemandu],
    ];

    return (
        <Card
            title="Perizinan pendakian"
            subtitle="Informasi aturan pengelola. Pemesanan tetap dilakukan melalui sistem resmi mereka."
        >
            <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-1">
                {butir
                    .filter(([, nilai]) => nilai !== null)
                    .map(([label, nilai]) => (
                        <div key={label}>
                            <dt className="text-muted">{label}</dt>
                            <dd className="font-medium text-primary">{nilai}</dd>
                        </div>
                    ))}
            </dl>

            {izin.notes ? <p className="mt-3 text-sm text-secondary">{izin.notes}</p> : null}

            {izin.bookingUrl ? (
                <a
                    href={izin.bookingUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-4 inline-flex min-h-11 items-center justify-center rounded-control border border-control bg-surface px-4 py-2 text-sm font-medium text-primary transition hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-muted focus-visible:ring-offset-2"
                >
                    Buka sistem pemesanan resmi
                    <span className="sr-only"> (membuka tab baru)</span>
                </a>
            ) : null}

            <div className="mt-3">
                <Kesegaran waktu={izin.diverifikasi} awalan="Aturan diverifikasi" />
            </div>
            <p className="mt-1 text-xs text-muted">
                <LabelSumber jenis={izin.jenisSumber} /> Sumber: {izin.source}. Aturan perizinan dapat berubah; periksa kembali ke penyelenggara sebelum berangkat.
            </p>
        </Card>
    );
}

/** Prakiraan per tiga jam. Kegagalan BMKG dinyatakan apa adanya, tidak pernah dikarang (R-013). */
export function CuacaKartu({ cuaca }: { cuaca: Cuaca }) {
    return (
        <Card title="Prakiraan cuaca area sekitar jalur" subtitle="Prakiraan berbasis wilayah administrasi, bukan kondisi puncak.">
            {cuaca.available ? (
                <>
                    <p className="text-sm text-secondary">Area referensi: {cuaca.referenceArea}</p>
                    {cuaca.message ? <p className="mt-2 rounded-control bg-warn-50 px-3 py-2 text-sm text-warn-900">{cuaca.message}</p> : null}
                    <div className="relative mt-3 overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <caption className="sr-only">Prakiraan cuaca per tiga jam</caption>
                            <thead>
                                <tr className="text-left text-muted">
                                    <th scope="col" className="py-2 pr-4">Waktu ({cuaca.timezoneLabel})</th>
                                    <th scope="col" className="py-2 pr-4">Cuaca</th>
                                    <th scope="col" className="py-2 pr-4">Suhu</th>
                                    <th scope="col" className="py-2 pr-4">Kelembapan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {cuaca.forecast.map((baris) => (
                                    <tr key={baris.id} className="border-t border-subtle">
                                        <td className="py-2 pr-4">{baris.waktu}</td>
                                        <td className="py-2 pr-4">{baris.cuaca}</td>
                                        <td className="py-2 pr-4">{baris.suhu}</td>
                                        <td className="py-2 pr-4">{baris.kelembapan}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="mt-3 text-xs text-muted">Sumber data cuaca: {cuaca.source}</p>
                    <Kesegaran waktu={cuaca.diperbarui} keadaan={cuaca.freshnessLabel} />
                </>
            ) : (
                <p className="text-sm text-secondary">{cuaca.message}</p>
            )}
            {cuaca.untukTrip ? (
                cuaca.untukTrip.status === 'tersedia' ? (
                    <div className="mt-3 rounded-control bg-surface-sunken px-3 py-2 text-kecil text-secondary">
                        <p className="font-bold text-primary">{cuaca.untukTrip.judul}</p>
                        <ul className="mt-1 space-y-0.5">
                            {cuaca.untukTrip.forecast.map((baris) => (
                                <li key={baris.id}>
                                    {baris.waktu}: {baris.cuaca}, {baris.suhu}, kelembapan {baris.kelembapan}
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <p className="mt-3 rounded-control bg-surface-sunken px-3 py-2 text-kecil text-secondary">{cuaca.untukTrip.pesan}</p>
                )
            ) : null}
        </Card>
    );
}
