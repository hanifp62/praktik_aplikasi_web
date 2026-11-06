import { useEffect, useRef, useState, type ReactNode } from 'react';
import { LabelKomunitas, LabelSumber, type JenisSumber } from '@/components/trails/LabelSumber';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';
import { kalimatBerakhir } from './kalimatSumber';
import { kalimatAreaTerbatas, kalimatPembatasan, kalimatStatusLuring } from './kesegaran';
import { barisCuacaPaket, kalimatLaporanPaket, kalimatPersiapanPaket, labelPaket, urutDaftarPaket, type DinamisPaket, type RencanaPaket } from './rakit';
import type { RekamanPaket, SesiBerakhir } from './simpan';

export interface PaketDiPerangkat {
    rekaman: RekamanPaket;
    rencana: RencanaPaket;
    dinamis: DinamisPaket;
    /** Pendakian trip ini sudah diselesaikan atau dibatalkan di perangkat ini (ruling AF-6); null bila belum. */
    berakhir?: SesiBerakhir | null;
}

/** Kegagalan membuka mode lapangan dari satu paket, ditulis di kartu paket itu (tinjauan m-1). */
export interface GalatPaket {
    tripId: number;
    versi: string;
    pesan: string;
}

interface DaftarPaketProps {
    paket: PaketDiPerangkat[];
    /** Waktu awal; komponen memperbaruinya sendiri tiap menit dan saat halaman terlihat lagi. */
    sekarangMs: number;
    onBuka: (paket: PaketDiPerangkat) => void;
    /** Kegagalan membuka yang terakhir; fokus pindah ke wilayah galat kartu paketnya. */
    galat?: GalatPaket | null;
}

const JENIS_SUMBER: JenisSumber[] = ['OFFICIAL', 'ADMIN_VERIFIED', 'ACCREDITED_EXPERT', 'COMMUNITY', 'AI_INTERPRETATION'];

/** Jenis sumber status dari paket; nilai yang tidak dikenal tidak menggambar label (LabelSumber). */
function jenisSumber(jenis: string | null): JenisSumber | null {
    return JENIS_SUMBER.find((j) => j === jenis) ?? null;
}

/** Jam di perangkat ini, dengan penanda zonanya (butir 6). */
const waktuPerangkat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' });

/** Waktu berakhir di perangkat ini, ditulis berzona; null bila tidak terbaca (Intl melempar untuk tanggal tidak sah). */
function waktuBerakhir(iso: string): string | null {
    const ms = Date.parse(iso);

    return Number.isFinite(ms) ? waktuPerangkat.format(ms) : null;
}

function kunciPaket(p: PaketDiPerangkat): string {
    return `${p.rekaman.tripId}-${p.rekaman.versi}`;
}

function kunciGalat(g: GalatPaket): string {
    return `${g.tripId}-${g.versi}`;
}

/**
 * Satu fakta yang terakhir diketahui saat paket dibuat: ikon jam, garis putus-putus, dan
 * kata-katanya sendiri, bukan warna saja (spec v3 bagian 10.3).
 */
function TerakhirDiketahui({ jenis, judul, label, turun = false, children }: { jenis: string; judul: string; label?: ReactNode; turun?: boolean; children: ReactNode }) {
    return (
        <div className={kelas('mt-3 rounded-kecil border-[1.5px] border-dashed p-3', turun ? 'border-warn-600 bg-warn-50' : 'border-control bg-surface-sunken')}>
            <div className="flex flex-wrap items-center gap-2">
                <p className={kelas('text-meta font-bold', turun ? 'text-warn-900' : 'text-secondary')}>{jenis}</p>
                {label}
            </div>
            <p className={kelas('mt-1 flex items-center gap-2 text-isi font-bold', turun ? 'text-warn-900' : 'text-primary')}>
                <Icon name="clock" size={20} />
                <span className="min-w-0 wrap-anywhere">{judul}</span>
            </p>
            <div className={kelas('mt-1 space-y-1 text-kecil', turun ? 'text-warn-900' : 'text-secondary')}>{children}</div>
        </div>
    );
}

function RencanaDanAturan({ rencana }: { rencana: RencanaPaket }) {
    const { rencana: r, aturan, persiapan } = rencana;

    return (
        <details className="group mt-3 rounded-kecil border border-subtle">
            <summary className={kelas('flex min-h-11 cursor-pointer items-center px-3 text-isi font-bold text-primary', fokusTerlihat)}>Rencana dan aturan</summary>
            <div className="space-y-4 px-3 pb-3 text-kecil text-primary">
                <div>
                    <h4 className="text-meta font-bold text-secondary">Rencana</h4>
                    <dl className="mt-1 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1">
                        <dt className="font-bold">Mulai</dt>
                        <dd>{r.jamMulai ?? 'Belum diisi'}</dd>
                        <dt className="font-bold">Perkiraan kembali</dt>
                        <dd className="wrap-anywhere">{r.kembali ?? 'Belum diisi'}</dd>
                        {r.registrasi ? (
                            <>
                                <dt className="font-bold">Registrasi</dt>
                                <dd className="wrap-anywhere">{r.registrasi}</dd>
                            </>
                        ) : null}
                    </dl>
                </div>

                <div>
                    <h4 className="text-meta font-bold text-secondary">Aturan resmi</h4>
                    {aturan ? (
                        <>
                            <p className="mt-1">{aturan.ringkasan}</p>
                            {aturan.catatan ? <p className="mt-1">{aturan.catatan}</p> : null}
                            <p className="mt-1 text-secondary">{aturan.dicek ? `Dicek ${aturan.dicek}. Pastikan di pos registrasi.` : 'Belum diverifikasi Memount. Pastikan di pos registrasi.'}</p>
                        </>
                    ) : (
                        <p className="mt-1">Belum diketahui. Pastikan di pos registrasi.</p>
                    )}
                </div>

                <div>
                    <h4 className="text-meta font-bold text-secondary">Persiapan</h4>
                    <p className="mt-1">{kalimatPersiapanPaket(persiapan)}</p>
                    {persiapan.butir.length > 0 ? (
                        <ul className="mt-1 space-y-1">
                            {persiapan.butir.map((b, i) => (
                                <li key={`${i}-${b.nama}`} className="wrap-anywhere">
                                    {b.nama}
                                    {b.kritis ? ' (kritis)' : ''}: {b.keadaan}
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </div>
            </div>
        </details>
    );
}

/**
 * Paket lapangan di perangkat ini (spec v3 bagian 10.2, 10.3, dan 10.8), tampil di shell luring.
 *
 * Setiap paket membawa potret dinamisnya sebagai tiga fakta "terakhir diketahui" yang terpisah
 * (PT9-12): status resmi dengan label jenis sumbernya beserta pembatasan segmen dan area
 * terbatas, prakiraan cuaca setiap tanggal trip dengan jam berzona, dan hitungan laporan
 * komunitas berlabel Komunitas (R-009, R-010). Status Buka yang sudah melewati ambangnya tampil
 * sebagai belum dapat dipastikan; waktu sekarang diperbarui tiap menit dan saat halaman terlihat
 * lagi, jadi shell yang dibiarkan terbuka tidak terus menyebut Buka (PT9-4, R-012, R-013).
 * Rencana, aturan, dan persiapan ada di <details> tertutup (W6-9c). Setiap kartu punya wilayah
 * role="alert" yang selalu terpasang tepat di bawah tombol bukanya: kegagalan membuka paket itu
 * ditulis di sana dan fokus pindah ke sana (tinjauan m-1). Label paket menyebut tanggal trip,
 * jadi dua paket di jalur yang sama terbedakan. Bingkai sistem kedua ditulis sekali di sini, tanpa
 * janji keselamatan.
 *
 * Paket trip yang pendakiannya sudah diselesaikan atau dibatalkan di perangkat ini menyebut keadaan
 * itu di bawah tanggalnya (ruling AF-6), dengan kalimat yang sama dengan shell mode lapangan
 * (kalimatBerakhir). Paketnya tetap dapat dibuka untuk meninjau pos dan peta.
 */
export function DaftarPaket({ paket, sekarangMs, onBuka, galat = null }: DaftarPaketProps) {
    const [sekarang, setSekarang] = useState(sekarangMs);
    const wilayahGalat = useRef(new Map<string, HTMLParagraphElement>());

    // Buka yang gagal: kalimatnya di kartu paket yang diketuk, dan fokus pindah ke sana (butir 7).
    useEffect(() => {
        if (galat) {
            wilayahGalat.current.get(kunciGalat(galat))?.focus();
        }
    }, [galat]);

    useEffect(() => {
        const perbarui = () => setSekarang(Date.now());
        const saatTerlihat = () => {
            if (document.visibilityState === 'visible') {
                perbarui();
            }
        };
        const pewaktu = window.setInterval(perbarui, 60_000);

        document.addEventListener('visibilitychange', saatTerlihat);

        return () => {
            window.clearInterval(pewaktu);
            document.removeEventListener('visibilitychange', saatTerlihat);
        };
    }, []);

    return (
        <section aria-labelledby="judul-paket-luring" className="mt-8 space-y-4">
            <h2 id="judul-paket-luring" className="text-bagian text-primary">
                Paket lapangan di perangkat ini
            </h2>
            <p className="text-isi text-primary">Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.</p>
            <ul className="space-y-4">
                {urutDaftarPaket(paket).map((p) => {
                    const { trip } = p.rekaman.manifest;
                    const status = kalimatStatusLuring(p.dinamis.status, sekarang);
                    const { pembatasan, areaTerbatas } = p.dinamis.status;
                    const galatIni = galat !== null && kunciGalat(galat) === kunciPaket(p) ? galat.pesan : '';

                    return (
                        <li key={kunciPaket(p)} className="rounded-kartu border border-subtle bg-surface p-4">
                            <h3 className="min-w-0 wrap-anywhere text-subjudul text-primary">{trip.nama}</h3>
                            <p className="min-w-0 wrap-anywhere text-kecil text-secondary">{[trip.jalur, trip.gunung].filter(Boolean).join(', ')}</p>
                            <p className="text-kecil text-secondary wrap-anywhere">{p.rencana.rencana.tanggal}</p>
                            {p.berakhir ? <p className="mt-2 text-isi font-bold text-primary">{kalimatBerakhir(p.berakhir.jenis, waktuBerakhir(p.berakhir.waktu))}</p> : null}
                            {p.rekaman.manifest.dataContoh ? (
                                <Lencana nada="netral" garis="putus" className="mt-2">
                                    Data contoh
                                    <span className="sr-only">: isi paket ini bukan data sungguhan</span>
                                </Lencana>
                            ) : null}

                            <TerakhirDiketahui jenis="Status resmi" judul={status.judul} label={<LabelSumber jenis={jenisSumber(p.dinamis.status.jenisSumber)} />} turun={status.turun}>
                                <p>{status.kalimat}</p>
                                {pembatasan.map((b) => (
                                    <p key={`segmen-${b.segmenId}`}>{kalimatPembatasan(b)}</p>
                                ))}
                                {areaTerbatas.map((a) => (
                                    <p key={`area-${a.id}`}>{kalimatAreaTerbatas(a)}</p>
                                ))}
                            </TerakhirDiketahui>

                            <TerakhirDiketahui jenis="Prakiraan cuaca" judul="Terakhir diketahui saat paket dibuat">
                                <p>{p.dinamis.cuaca.kalimat}</p>
                                {barisCuacaPaket(p.dinamis.cuaca).map((h) => (
                                    <div key={h.tanggal}>
                                        <p className="font-bold text-primary">{h.label}</p>
                                        {h.baris.length > 0 ? (
                                            <ul>
                                                {h.baris.map((b) => (
                                                    <li key={b}>{b}</li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p>{h.kalimat}</p>
                                        )}
                                    </div>
                                ))}
                            </TerakhirDiketahui>

                            <TerakhirDiketahui jenis="Laporan pendaki" judul="Terakhir diketahui saat paket dibuat" label={<LabelKomunitas />}>
                                <p>{kalimatLaporanPaket(p.dinamis.laporan)}</p>
                            </TerakhirDiketahui>

                            <RencanaDanAturan rencana={p.rencana} />

                            <p className="mt-3 text-meta text-secondary">
                                Diperiksa utuh di perangkat ini {p.rekaman.diverifikasi ? waktuPerangkat.format(new Date(p.rekaman.diverifikasi)) : 'tanpa waktu tercatat'}.
                            </p>

                            <button
                                type="button"
                                aria-label={`Buka mode lapangan: ${labelPaket(p.rekaman.manifest.trip)}`}
                                onClick={() => onBuka(p)}
                                className={kelas('mt-3 inline-flex min-h-[66px] w-full items-center justify-center rounded-tombol bg-brand-700 px-4 py-4 text-bagian font-bold text-on-brand', fokusTerlihat, umpanTekan)}
                            >
                                Buka mode lapangan
                            </button>
                            <p
                                id={`luring-galat-${kunciPaket(p)}`}
                                role="alert"
                                tabIndex={-1}
                                ref={(el) => {
                                    if (el) {
                                        wilayahGalat.current.set(kunciPaket(p), el);
                                    } else {
                                        wilayahGalat.current.delete(kunciPaket(p));
                                    }
                                }}
                                className="mt-3 rounded-control border border-danger-300 bg-danger-50 p-3 text-kecil text-danger-900 empty:mt-0 empty:border-0 empty:p-0 focus:outline-hidden focus:ring-2 focus:ring-brand-600 focus:ring-offset-2"
                            >
                                {galatIni}
                            </p>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
