import type { ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { FitLine } from '@/components/trails/FitLine';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { GarisProfil } from '@/components/trails/GarisProfil';
import type { FitRingkas, StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export interface KolomJalur {
    id: number;
    name: string;
    mountain: string | null;
    url: string;
    fit: FitRingkas | null;
    /** Status akses beserta sumber dan waktunya (StatusJalurRingkas). */
    status: StatusRingkas;
    /** Laporan pendaki termoderasi terbaru dalam jendela terbaru (hariLaporan), atau null. */
    laporan: { tags: string[]; kapan: string } | null;
    izin: string;
    /** Pecahan dari tanjakan terbesar di tabel ini, untuk bilah pada skala yang sama; null tanpa tanjakan. */
    rasioTanjakan: number | null;
    /** Garis profil pada skala tinggi yang sama dengan kolom lain; null tanpa profil. */
    garis: Array<[number, number]> | null;
    rencanaUrl: string;
    /** Jalur ini ada di simpanan pembaca; tautan lama dapat membawa jalur yang tidak ia simpan. */
    tersimpan: boolean;
    /** Tautan GET ke perbandingan yang sama tanpa kolom ini. Tidak pernah menghapus simpanan. */
    hapus: string;
}

export interface BarisBanding {
    /** Kunci tetap baris (RouteComparisonTable::baris), untuk mencocokkan baris tanpa membaca labelnya. */
    kunci: string;
    label: string;
    nilai: string[];
    berbeda: boolean | null;
}

interface TabelBandingProps {
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null; laporan: boolean | null; izin: boolean | null };
    /** Kalimat profil pada skala yang sama (RouteComparisonTable), atau null. */
    profil: string | null;
    /** Jendela laporan terbaru dalam hari (hiking.conditions.community_recent_days). */
    hariLaporan: number;
    sorotBeda: boolean;
    /** Kata UNKNOWN dari server (RouteFitLabel::UNRATED_TEXT), untuk pembaca tanpa profil. */
    kataBelumDinilai: string;
}

const sel = 'p-4 align-top';
// Label aspek menempel di kiri ketika tabel digeser di layar sempit, supaya pembaca tidak
// kehilangan baris mana yang sedang ia baca.
const kepalaBaris = 'sticky left-0 z-20 bg-surface p-4 text-left align-top font-medium text-muted';

/**
 * Jalur berdampingan tanpa pemenang (R-016, spec v3 bagian 6.7, prototype 07): yang ditandai
 * hanya apakah sebuah aspek berbeda antarjalur, tidak pernah nilai mana yang lebih baik.
 * Aspek yang sama diberi tanda "sama". "Sorot yang berbeda" menebalkan baris yang berbeda dan
 * memberinya garis tepi, tanpa menyembunyikan apa pun dan tanpa warna yang bermakna baik atau
 * buruk.
 *
 * Tanjakan diberi bilah pada skala yang sama dan profil elevasi digambar pada skala tinggi yang
 * sama, dengan kalimatnya. Laporan pendaki ditulis dengan warna komunitas (lapisan komunitas,
 * R-010). "Hapus dari perbandingan" adalah tautan GET ke perbandingan tanpa kolom itu; halaman
 * ini tidak pernah menulis ke simpanan.
 */
export function TabelBanding({ jalur, baris, beda, profil, hariLaporan, sorotBeda, kataBelumDinilai }: TabelBandingProps) {
    const barisSorot = (berbeda: boolean | null) => kelas('border-b border-subtle', sorotBeda && berbeda === true && 'bg-surface-sunken *:font-bold');

    const kepala = (label: string, berbeda: boolean | null): ReactNode => (
        <th scope="row" className={kelas(kepalaBaris, sorotBeda && berbeda === true && 'border-l-4 border-l-brand-ink bg-surface-sunken')}>
            {label}
            {berbeda === false ? <span className="text-meta font-normal text-secondary">, sama</span> : null}
        </th>
    );

    return (
        <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
            <table className="min-w-full text-kecil">
                <caption className="sr-only">Perbandingan karakteristik jalur, tanpa pemenang</caption>
                <thead>
                    <tr className="border-b border-subtle text-left">
                        <th scope="col" className={kelas(kepalaBaris, 'font-normal')}>
                            Aspek
                        </th>
                        {jalur.map((j) => (
                            <th key={j.id} scope="col" className={kelas(sel, 'min-w-44')}>
                                <Tautan
                                    href={j.url}
                                    className="block rounded-xs py-1 text-subjudul text-primary underline-offset-2 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {j.name}
                                </Tautan>
                                <span className="block text-meta font-normal text-secondary">{j.mountain}</span>
                                {j.tersimpan ? null : <span className="block text-meta font-normal text-muted">Bukan simpanan Anda</span>}
                                <Button variant="ghost" size="sm" className="-ml-4 font-normal" href={j.hapus}>
                                    Hapus dari perbandingan<span className="sr-only">: {j.name}</span>
                                </Button>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    <tr className={barisSorot(beda.fit)}>
                        {kepala('Kecocokan dasar', beda.fit)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.fit ? <FitLine fit={j.fit} trailId={j.id} nama={j.name} /> : <LabelKecocokan nilai={null} label={kataBelumDinilai} />}
                            </td>
                        ))}
                    </tr>

                    {baris.map((b) => (
                        <tr key={b.kunci} className={barisSorot(b.berbeda)}>
                            {kepala(b.label, b.berbeda)}
                            {b.nilai.map((nilai, i) => {
                                const kolom = jalur[i];

                                return (
                                    <td key={kolom?.id ?? i} data-angka="" className={kelas(sel, 'text-primary')}>
                                        {nilai}
                                        {b.kunci === 'tanjakan' && kolom?.rasioTanjakan != null ? (
                                            <span aria-hidden="true" className="mt-2 block h-1.5 w-full rounded-full bg-surface-sunken">
                                                <span className="block h-1.5 rounded-full bg-brand-ink" style={{ width: `${Math.round(kolom.rasioTanjakan * 100)}%` }} />
                                            </span>
                                        ) : null}
                                    </td>
                                );
                            })}
                        </tr>
                    ))}

                    <tr className={barisSorot(beda.status)}>
                        {kepala('Status resmi', beda.status)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                <BarisStatus status={j.status} />
                            </td>
                        ))}
                    </tr>

                    <tr className={barisSorot(beda.laporan)}>
                        {kepala('Laporan terbaru', beda.laporan)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.laporan ? (
                                    <>
                                        <span className="block font-bold text-community-900">{j.laporan.tags.join(', ') || 'Tanpa tanda kondisi'}</span>
                                        <span className="block text-meta text-muted">Komunitas, {j.laporan.kapan}</span>
                                    </>
                                ) : (
                                    <span className="text-secondary">Belum ada dalam {hariLaporan} hari</span>
                                )}
                            </td>
                        ))}
                    </tr>

                    <tr className={barisSorot(beda.izin)}>
                        {kepala('Izin', beda.izin)}
                        {jalur.map((j) => (
                            <td key={j.id} className={kelas(sel, 'text-primary')}>
                                {j.izin}
                            </td>
                        ))}
                    </tr>

                    <tr>
                        <th scope="row" className={kepalaBaris}>
                            Profil elevasi, skala sama
                        </th>
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.garis ? (
                                    <>
                                        {/* Garisnya aria-hidden; padanan teksnya kalimat di bawah tabel (T9-M8). */}
                                        <span className="sr-only">
                                            Garis profil {j.name} tergambar.{profil ? ' Keterangannya di bawah tabel.' : ''}
                                        </span>
                                        <GarisProfil titik={j.garis} label={j.fit?.label ?? null} />
                                    </>
                                ) : (
                                    <span className="text-secondary">Belum tersedia</span>
                                )}
                            </td>
                        ))}
                    </tr>
                </tbody>
            </table>
            {profil ? <p className="border-t border-subtle px-4 py-3 text-kecil text-secondary">{profil}</p> : null}
        </div>
    );
}
