import { useState } from 'react';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { kelas } from '@/lib/kelas';
import type { BatangPendakian, GrafikPendakianData } from './types';

type Ukuran = 'tanjakan' | 'jarak';

const pilihanUkuran = [
    { nilai: 'tanjakan', label: 'Tanjakan' },
    { nilai: 'jarak', label: 'Jarak' },
] as const;

/**
 * Grafik per pendakian (spec v3 bagian 6.14, prototype 13), dibandingkan hanya dengan pendakian
 * pendaki ini sendiri (R-015). Satu baris per pendakian dari yang paling awal, dengan nilai
 * tertulis di setiap baris: dua belas kolom tegak seperti prototype tidak muat di 320 px bersama
 * nilainya (koreksi terukur, ruling W7-R5). Yang dibatalkan dan yang tanpa angka menyebut dirinya
 * dengan kata ("batal", "belum ada"), tidak lewat garis putus-putus atau warna saja, dan tidak
 * pernah digambar sebagai nol (R-007). Batang disembunyikan dari pembaca layar; tabel di
 * bawahnya membawa angka yang sama untuk kedua ukuran.
 */
export function GrafikPendakian({ grafik }: { grafik: GrafikPendakianData }) {
    const [ukuran, setUkuran] = useState<Ukuran>('tanjakan');
    const puncak = Math.max(1, ...grafik.batang.map((b) => b[ukuran].nilai ?? 0));

    return (
        <section aria-labelledby="judul-grafik-pendakian">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 id="judul-grafik-pendakian" className="text-bagian text-primary">
                    Per pendakian
                </h2>
                <KontrolSegmen nama="ukuran-grafik" label="Ukuran pada grafik" pilihan={pilihanUkuran} nilai={ukuran} onUbah={setUkuran} className="w-56 max-w-full" />
            </div>
            <figure className="relative mt-3 rounded-kartu border border-subtle bg-surface p-4">
                <ol aria-hidden="true" className="space-y-3">
                    {grafik.batang.map((b) => (
                        <Baris key={b.kunci} batang={b} ukuran={ukuran} puncak={puncak} />
                    ))}
                </ol>
                {/* Pembungkus sr-only, bukan tabelnya: kotak tabel tidak terpotong overflow, jadi
                    tabel ber-sr-only melebarkan halaman di layar sempit. */}
                <div className="sr-only">
                    <table>
                        <caption>Tanjakan dan jarak setiap pendakian, dari yang paling awal</caption>
                        <thead>
                            <tr>
                                <th scope="col">Pendakian</th>
                                <th scope="col">Bulan</th>
                                <th scope="col">Tanjakan</th>
                                <th scope="col">Jarak</th>
                            </tr>
                        </thead>
                        <tbody>
                            {grafik.batang.map((b) => (
                                <tr key={b.kunci}>
                                    <th scope="row">
                                        {b.nama}
                                        {b.terbaru ? ' (terbaru)' : ''}
                                    </th>
                                    <td>{b.bulan}</td>
                                    <td>{b.tanjakan.teks}</td>
                                    <td>{b.jarak.teks}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <figcaption className="mt-4 max-w-prose text-kecil text-primary">{grafik.keterangan[ukuran]}</figcaption>
                {grafik.catatan ? <p className="mt-2 text-meta text-muted">{grafik.catatan}</p> : null}
            </figure>
        </section>
    );
}

function Baris({ batang, ukuran, puncak }: { batang: BatangPendakian; ukuran: Ukuran; puncak: number }) {
    const angka = batang[ukuran];
    const lebar = angka.nilai === null ? 0 : Math.max(3, (angka.nilai / puncak) * 100);

    return (
        <li className="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-3 gap-y-1">
            <span className="min-w-0 break-words text-kecil text-primary">
                <span className={batang.terbaru ? 'font-bold' : 'font-semibold'}>{batang.nama}</span> <span className="text-meta text-muted">{batang.bulan}</span>
            </span>
            <span className={kelas('text-kecil tabular-nums', angka.nilai === null ? 'text-muted' : 'text-primary', batang.terbaru && 'font-bold')}>{angka.teks}</span>
            <span
                className={kelas(
                    'col-span-2 block h-3 overflow-hidden rounded-full',
                    batang.keadaan === 'batal' && angka.nilai === null ? 'border border-dashed border-control' : 'bg-surface-sunken',
                )}
            >
                {angka.nilai !== null ? <span className={kelas('block h-full rounded-full', batang.terbaru ? 'bg-brand-700' : 'bg-brand-500')} style={{ width: `${lebar}%` }} /> : null}
            </span>
        </li>
    );
}

