import { useMemo, useRef, useState } from 'react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { kalimatPosisiDarurat, kalimatPosisiLama, ketinggianDiPosisi, posTerdekatDarurat, teksAkurasi, teksKetinggian, teksKoordinat, teksSekitar, teksUmur } from './hitung';
import type { DataLapangan, DaruratLapangan, PosisiPerangkat } from './types';
import type { KeadaanLokasi } from './usePosisiLapangan';

interface KartuDaruratProps {
    darurat: DaruratLapangan;
    data: DataLapangan;
    posisi: PosisiPerangkat | null;
    /** Keadaan lokasi perangkat (usePosisiLapangan): alasan bila tidak ada posisi baru. */
    lokasi: KeadaanLokasi;
    tercapai: number;
}

/**
 * Kartu darurat (spec v3 bagian 10.2 dan 10.8; ruling W6-R4): satu ketukan dari mode lapangan.
 * Nomor 112 dan Basarnas 115 sebagai tautan telepon 66 px, kontak pengelola resmi gunung bila
 * diketahui (ruling W6-7a), kalimat tetap di tempat, dan blok lokasi untuk dibacakan kepada
 * petugas.
 *
 * Blok lokasi dihitung di perangkat; kartu ini tidak mengirim posisi ke mana pun. Rekam jejak
 * yang opsional mengirim titiknya sendiri sesudah pendakian selesai atau dibatalkan, saat ada
 * sinyal, bukan lewat kartu ini, sesuai persetujuan Profil (ruling W6-P2); karena itu kalimat di bawah judul blok menyebut bahwa Memount tidak meneruskan posisi
 * ini, dan pendaki membacakannya sendiri (pra-tinjau Tugas 7 PT7-5). Impor runtime dibatasi
 * primitif tampilan dan hitungan murni, dijaga EmergencyCardTest (PT7-6).
 *
 * Isinya: koordinat; jam pengukuran dalam zona gunung dengan penandanya, umurnya, dan
 * akurasinya (PT7-14, PT7-3b); pos terdekat garis lurus dengan nomornya, hanya bila akurasi
 * cukup tajam (PT7-3c dan d); ketinggian dari profil jalur di letak posisi itu sendiri, hanya
 * bila pendaki di jalur (PT7-1); dan pos terakhir yang dicapai. Tanpa posisi, kartu menyebut
 * alasannya per keadaan lokasi (PT7-3a). Bila posisi terakhir masih tampil tetapi perangkat
 * tidak memberi posisi baru, keterangannya menyebut bahwa koordinat itu posisi terakhir yang
 * terukur (kalimatPosisiLama, ruling W6-7h). Tidak ada janji keselamatan (R-012, R-014).
 */
export function KartuDarurat({ darurat, data, posisi, lokasi, tercapai }: KartuDaruratProps) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);
    const jam = useMemo(() => new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: darurat.zona }), [darurat.zona]);
    const posTercapai = data.pos.find((p) => p.urutan === tercapai) ?? null;
    const terdekat = posisi ? posTerdekatDarurat(data.pos, posisi) : null;
    const ketinggian = ketinggianDiPosisi(data.garis, data.profil, posisi);

    return (
        <>
            <button
                ref={pemicu}
                type="button"
                onClick={() => setTerbuka(true)}
                className={kelas('inline-flex min-h-[60px] w-full items-center justify-center gap-2 rounded-tombol border-2 border-danger-600 bg-surface px-4 py-3 text-bagian font-bold text-danger-900', fokusTerlihat, umpanTekan)}
            >
                <Icon name="warning" size={20} />
                Kartu darurat
            </button>

            <LembarBawah terbuka={terbuka} onTutup={() => setTerbuka(false)} pemicu={pemicu} judul="Kartu darurat" keterangan={darurat.tetap}>
                <div className="space-y-5">
                    {/* text-inverse di atas danger-900: 7:1 lebih di kedua tema lapangan (ruling
                        W6-7d, ColourContrastTest::fieldPairs); putih di atas danger-600 tidak. */}
                    <ul className="grid grid-cols-2 gap-3">
                        {darurat.nomor.map((n) => (
                            <li key={n.nomor}>
                                <a
                                    href={`tel:${n.nomor}`}
                                    aria-label={`Telepon ${n.nomor}, ${n.label}`}
                                    className={kelas('flex min-h-[66px] flex-col items-center justify-center rounded-tombol bg-danger-900 px-3 py-2 text-inverse', fokusTerlihat, umpanTekan)}
                                >
                                    <span className="text-judul tabular-nums">{n.nomor}</span>
                                    <span className="text-kecil font-bold">{n.label}</span>
                                </a>
                            </li>
                        ))}
                    </ul>

                    <p className="text-isi text-primary">
                        {darurat.pengelola ? (
                            <>
                                Pengelola resmi di {data.trip.gunung}: {darurat.pengelola.nama},{' '}
                                {darurat.pengelola.telepon ? (
                                    <a
                                        href={`tel:${darurat.pengelola.telepon}`}
                                        aria-label={`Telepon ${darurat.pengelola.nama}, ${darurat.pengelola.kontak}`}
                                        className={kelas('inline-flex min-h-12 items-center font-bold text-brand-ink underline underline-offset-2', fokusTerlihat)}
                                    >
                                        {darurat.pengelola.kontak}
                                    </a>
                                ) : (
                                    <span className="break-words font-bold">{darurat.pengelola.kontak}</span>
                                )}
                            </>
                        ) : (
                            'Kontak pengelola resmi gunung ini belum tercatat di Memount.'
                        )}
                    </p>

                    <section aria-labelledby="judul-lokasi-darurat" className="rounded-kartu border border-subtle bg-surface-sunken p-4">
                        <h3 id="judul-lokasi-darurat" className="text-subjudul text-primary">
                            Bacakan kepada petugas
                        </h3>
                        <p className="mt-1 text-kecil text-secondary">Dihitung di perangkat ini. Memount tidak meneruskan posisi ini ke petugas atau ke kontak Anda; bacakan sendiri saat menelepon.</p>
                        <dl className="mt-3 space-y-2 text-isi text-primary">
                            <div>
                                <dt className="text-kecil text-secondary">Jalur</dt>
                                <dd className="font-bold">
                                    {data.trip.jalur}, {data.trip.gunung}
                                </dd>
                            </div>
                            {posisi ? (
                                <>
                                    <div>
                                        <dt className="text-kecil text-secondary">Koordinat</dt>
                                        <dd className="font-bold tabular-nums">{teksKoordinat(posisi.lat, posisi.lng)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-kecil text-secondary">Diukur</dt>
                                        <dd className="tabular-nums">
                                            pukul {jam.format(posisi.waktu)} {darurat.labelZona}, {teksUmur((Date.now() - posisi.waktu) / 1000)},{' '}
                                            <span className="whitespace-nowrap">akurasi {teksAkurasi(posisi.akurasi)}</span>
                                        </dd>
                                        {lokasi !== 'aktif' ? <dd className="mt-1 text-kecil text-secondary">{kalimatPosisiLama(lokasi)}</dd> : null}
                                    </div>
                                    {terdekat ? (
                                        <div>
                                            <dt className="text-kecil text-secondary">Pos terdekat (garis lurus)</dt>
                                            <dd>
                                                nomor {terdekat.urutan}, {terdekat.nama}, {teksSekitar(terdekat.meter)}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {ketinggian !== null ? (
                                        <div>
                                            <dt className="text-kecil text-secondary">Ketinggian menurut profil jalur</dt>
                                            <dd className="tabular-nums">sekitar {teksKetinggian(ketinggian)}</dd>
                                        </div>
                                    ) : null}
                                </>
                            ) : (
                                <div>
                                    <dt className="text-kecil text-secondary">Posisi</dt>
                                    <dd>{kalimatPosisiDarurat(lokasi)}</dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-kecil text-secondary">Pos terakhir yang dicapai</dt>
                                <dd>{posTercapai ? `nomor ${posTercapai.urutan}, ${posTercapai.nama}` : 'Belum ada pos yang tercatat dicapai.'}</dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </LembarBawah>
        </>
    );
}
