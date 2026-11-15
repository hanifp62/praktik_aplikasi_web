import { useState, type ReactNode } from 'react';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { FormulirCatatan } from './FormulirCatatan';
import type { ButirRiwayat, FormulirCatatanData, KabarProgres, UbahCatatan } from './types';

const kartu = 'block w-full rounded-kartu border bg-surface p-3.5 text-left focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Riwayat satu tahun (spec v3 bagian 6.14, prototype 13): setiap pendakian dengan keadaannya,
 * alasan pembatalan bila disebut (DK-50), dan keadaan laporan kondisinya. Pendakian dari trip
 * membuka ringkasannya; catatan manual membuka lembar ubah. Pendakian yang dibatalkan bergaris
 * putus-putus dan menyebut dirinya dengan kata, bukan garis saja. Kabar tulisan dibacakan di
 * wilayah status yang selalu terpasang di bagian ini (konvensi umpan balik aksi, DESIGN.md).
 */
export function RiwayatProgres({ tahun, riwayat, terpotong, formulir, kabar }: { tahun: number; riwayat: ButirRiwayat[]; terpotong: string | null; formulir: FormulirCatatanData; kabar: KabarProgres | null }) {
    const [sasaran, setSasaran] = useState<{ ubah: UbahCatatan | null } | null>(null);

    return (
        <section aria-labelledby="judul-riwayat">
            <div className="flex items-baseline justify-between gap-3">
                <h2 id="judul-riwayat" className="text-bagian text-primary">
                    Riwayat
                </h2>
                <span className="text-meta tabular-nums text-muted">{tahun}</span>
            </div>
            <p role="status" className={kabar?.bagian === 'riwayat' ? 'mt-2 rounded-kartu border border-brand-300 bg-brand-100 p-3 text-kecil text-brand-900' : undefined}>
                {kabar?.bagian === 'riwayat' ? kabar.teks : ''}
            </p>

            {riwayat.length === 0 ? (
                <p className="mt-2 text-isi text-secondary">Belum ada pendakian tercatat di {tahun}.</p>
            ) : (
                <ul className="mt-3 space-y-2">
                    {riwayat.map((b) => (
                        <li key={b.kunci}>
                            {b.url !== null ? (
                                <Tautan href={b.url} className={kelas(kartu, b.keadaan === 'batal' ? 'border-dashed border-control' : 'border-subtle', 'hover:bg-surface-sunken')}>
                                    <IsiButir butir={b} />
                                </Tautan>
                            ) : b.ubah !== null ? (
                                <button type="button" onClick={() => setSasaran({ ubah: b.ubah })} className={kelas(kartu, 'border-subtle hover:bg-surface-sunken')}>
                                    <IsiButir butir={b} />
                                    <span className="sr-only">, ubah catatan</span>
                                </button>
                            ) : (
                                <div className={kelas(kartu, 'border-subtle')}>
                                    <IsiButir butir={b} />
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            {terpotong ? <p className="mt-2 text-meta text-muted">{terpotong}</p> : null}

            <button
                type="button"
                onClick={() => setSasaran({ ubah: null })}
                className="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-tombol border-2 border-dashed border-brand-700 px-4 text-isi font-bold text-brand-ink hover:bg-brand-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
            >
                <Icon name="plus" size={20} />
                Catat pendakian di luar trip
            </button>

            <LembarBawah
                terbuka={sasaran !== null}
                onTutup={() => setSasaran(null)}
                judul={sasaran?.ubah ? 'Ubah catatan pendakian' : 'Catat pendakian di luar trip'}
                keterangan="Pendakian yang tidak direncanakan lewat trip di Memount."
            >
                {sasaran !== null ? <FormulirCatatan key={sasaran.ubah?.simpan ?? 'baru'} formulir={formulir} ubah={sasaran.ubah} onSelesai={() => setSasaran(null)} /> : null}
            </LembarBawah>
        </section>
    );
}

function IsiButir({ butir }: { butir: ButirRiwayat }): ReactNode {
    return (
        <>
            <span className="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                <span className="min-w-0 break-words text-subjudul text-primary">{butir.nama}</span>
                <Lencana nada={butir.keadaan === 'selesai' ? 'merek' : 'netral'}>{butir.lencana}</Lencana>
            </span>
            <span className="mt-1 block text-meta tabular-nums text-secondary">{[butir.tanggal, butir.baris].filter(Boolean).join(' · ')}</span>
            {butir.catatan ? <span className={kelas('mt-1 block text-meta', butir.catatan.nada === 'komunitas' ? 'text-community-900' : 'text-muted')}>{butir.catatan.teks}</span> : null}
            {butir.kalimatBatal ? <span className="mt-1 block text-meta text-primary">{butir.kalimatBatal}</span> : null}
        </>
    );
}

