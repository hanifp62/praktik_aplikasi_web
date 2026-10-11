import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { PosJalur } from '@/components/trails/rinci/types';
import type { DaruratLapangan } from '@/components/mendaki/types';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { PageHeader } from '@/components/ui/PageHeader';
import { show as tripShow } from '@/routes/trips';

interface Props {
    jalur: { nama: string; gunung: string | null; wilayah: string };
    rencana: { nama: string; tanggal: string; jamMulai: string | null; kembali: string | null; registrasi: string | null };
    sketsa: { garis: Array<[number, number]> | null; pos: Array<{ urutan: number; lng: number; lat: number }> };
    pos: PosJalur[];
    aturan: { ringkasan: string; catatan: string | null; dicek: string | null } | null;
    darurat: DaruratLapangan;
    pesan: string | null;
    dicetak: string;
    tripId: number;
    [key: string]: unknown;
}

const LEBAR = 1000;
const TINGGI_MAKS = 700;
const TEPI = 40;

/**
 * Titik [bujur, lintang] dipetakan ke kanvas sketsa dengan utara di atas. Bujur dikali kosinus
 * lintang tengah supaya bentuk rute tidak melebar ke timur-barat. Tanpa skala: sketsa ini
 * pengingat bentuk dan urutan pos, bukan peta navigasi.
 */
function proyeksi(titik: Array<[number, number]>) {
    const lintang = titik.map(([, lat]) => lat);
    const tengah = ((Math.min(...lintang) + Math.max(...lintang)) / 2) * (Math.PI / 180);
    const xs = titik.map(([lng]) => lng * Math.cos(tengah));
    const ys = titik.map(([, lat]) => -lat);
    const [x0, x1, y0, y1] = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
    const skala = Math.min((LEBAR - 2 * TEPI) / Math.max(x1 - x0, 1e-9), (TINGGI_MAKS - 2 * TEPI) / Math.max(y1 - y0, 1e-9));
    const tinggi = Math.round((y1 - y0) * skala + 2 * TEPI);

    return {
        tinggi,
        titik: ([lng, lat]: [number, number]): [number, number] => [TEPI + (lng * Math.cos(tengah) - x0) * skala, TEPI + (-lat - y0) * skala],
    };
}

/**
 * Versi cetak trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): sketsa rute,
 * tabel pos, aturan resmi bertanggal, kartu darurat, dan rencana, hitam di atas putih untuk
 * kertas A4. Cadangan untuk ponsel, bukan peta navigasi: tidak ada status terkini yang dapat
 * basi tanpa tanda, dan yang bertanggal menyebut tanggal ceknya (R-013).
 *
 * Label sesuai isinya (pra-tinjau Tugas 7 PT7-15, ruling W6-7a): baris ringkasan rencana
 * (jenis, tanggal, peserta) berlabel Ringkasan; aturan tanpa tanggal verifikasi disebut belum
 * diverifikasi, bukan "tanggal cek tidak tercatat"; kontak resmi disebut pengelola resmi di
 * gunung, karena kontaknya tingkat gunung, bukan jalur.
 */
export default function Print() {
    const { jalur, rencana, sketsa, pos, aturan, darurat, pesan, dicetak, tripId } = usePage<Props>().props;
    const garis = sketsa.garis ?? (sketsa.pos.length >= 2 ? sketsa.pos.map((p) => [p.lng, p.lat] as [number, number]) : null);
    const peta = garis ? proyeksi([...garis, ...sketsa.pos.map((p) => [p.lng, p.lat] as [number, number])]) : null;

    // Kertas selalu terang: selama mencetak (tombol atau Ctrl+P) tema gelap dilepas, lalu
    // dipulihkan. Token tema gelap berlaku di bawah :root:not([data-theme='light']).
    useEffect(() => {
        const akar = document.documentElement;
        let sebelum: string | undefined;
        const mulaiCetak = () => {
            sebelum = akar.dataset.theme;
            akar.dataset.theme = 'light';
        };
        const selesaiCetak = () => {
            if (sebelum === undefined) {
                delete akar.dataset.theme;
            } else {
                akar.dataset.theme = sebelum;
            }
        };

        window.addEventListener('beforeprint', mulaiCetak);
        window.addEventListener('afterprint', selesaiCetak);

        return () => {
            window.removeEventListener('beforeprint', mulaiCetak);
            window.removeEventListener('afterprint', selesaiCetak);
        };
    }, []);

    return (
        <main id="konten" tabIndex={-1} className="mx-auto max-w-3xl px-4 py-6 text-primary print:max-w-none print:p-0">
            <JudulHalaman />
            <div className="print:hidden">
                <PageHeader induk={{ href: tripShow.url(tripId), label: 'Trip' }} title="Versi cetak" description={rencana.nama}>
                    <Button className="mt-3" onClick={() => window.print()}>
                        Cetak halaman ini
                    </Button>
                </PageHeader>
            </div>

            <article className="space-y-6 print:space-y-4">
                <header className="border-b-2 border-current pb-3">
                    {/* Waktu server menyusun halaman, bukan waktu kertas dicetak (ruling W6-7i,
                        M-5): halaman yang dibiarkan terbuka dapat dicetak jauh sesudahnya. */}
                    <p className="text-kecil">Memount · rencana pendakian · data per {dicetak}</p>
                    {/* Di layar lencananya dibawa kepala halaman; di kertas kepala itu tidak tercetak. */}
                    <div className="hidden print:block">
                        <LencanaDataContoh />
                    </div>
                    <h2 className="mt-1 text-judul">{jalur.nama}</h2>
                    <p className="text-isi">{jalur.wilayah}</p>
                </header>

                <section aria-labelledby="cetak-rencana">
                    <h3 id="cetak-rencana" className="text-bagian">
                        Rencana
                    </h3>
                    <dl className="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-isi">
                        <dt className="font-bold">Trip</dt>
                        <dd>{rencana.nama}</dd>
                        <dt className="font-bold">Ringkasan</dt>
                        <dd>{rencana.tanggal}</dd>
                        <dt className="font-bold">Mulai</dt>
                        <dd>{rencana.jamMulai ?? 'Belum diisi'}</dd>
                        <dt className="font-bold">Kembali</dt>
                        <dd>{rencana.kembali ?? 'Belum diisi'}</dd>
                        <dt className="font-bold">Registrasi</dt>
                        <dd>{rencana.registrasi ?? 'Belum diisi'}</dd>
                    </dl>
                </section>

                <section aria-labelledby="cetak-darurat" className="break-inside-avoid rounded-kartu border-2 border-current p-4">
                    <h3 id="cetak-darurat" className="text-bagian">
                        Kartu darurat
                    </h3>
                    <p className="mt-2 text-judul-kartu tabular-nums">{darurat.nomor.map((n) => `${n.nomor} ${n.label}`).join(' · ')}</p>
                    <p className="mt-2 text-isi">
                        {darurat.pengelola ? `Pengelola resmi di ${jalur.gunung ?? 'gunung ini'}: ${darurat.pengelola.nama}, ${darurat.pengelola.kontak}` : 'Kontak pengelola resmi gunung ini belum tercatat di Memount.'}
                    </p>
                    <p className="mt-2 text-isi font-bold">{darurat.tetap}</p>
                    <p className="mt-3 text-isi">Posisi terakhir, pos terdekat, dan jam (isi saat dibutuhkan):</p>
                    <p aria-hidden="true" className="mt-6 border-b border-current" />
                </section>

                <section aria-labelledby="cetak-sketsa" className="break-inside-avoid">
                    <h3 id="cetak-sketsa" className="text-bagian">
                        Sketsa rute
                    </h3>
                    {garis && peta ? (
                        <>
                            <svg
                                viewBox={`0 0 ${LEBAR} ${peta.tinggi}`}
                                role="img"
                                aria-label={`Sketsa rute ${jalur.nama} dengan ${sketsa.pos.length} pos bernomor`}
                                className="mt-2 h-auto w-full border border-current"
                            >
                                <polyline
                                    points={garis.map((t) => peta.titik(t).join(',')).join(' ')}
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={5}
                                    strokeDasharray={sketsa.garis ? undefined : '14 10'}
                                    strokeLinejoin="round"
                                />
                                {sketsa.pos.length > 0
                                    ? sketsa.pos.map((p) => {
                                          const [x, y] = peta.titik([p.lng, p.lat]);

                                          return (
                                              <g key={p.urutan}>
                                                  <circle cx={x} cy={y} r={24} className="fill-surface" stroke="currentColor" strokeWidth={4} />
                                                  <text x={x} y={y + 10} textAnchor="middle" fontSize={28} fontWeight={700} fill="currentColor">
                                                      {p.urutan}
                                                  </text>
                                              </g>
                                          );
                                      })
                                    : null}
                                <text x={LEBAR - TEPI} y={TEPI} textAnchor="end" fontSize={30} fontWeight={700} fill="currentColor">
                                    U ↑
                                </text>
                            </svg>
                            <p className="mt-1 text-kecil">
                                {sketsa.garis ? 'Garis jalur dari data rute. ' : 'Garis lurus antarpos, bukan bentuk jalur sebenarnya. '}
                                {sketsa.pos.length === 0 ? 'Pos belum punya koordinat, jadi tidak digambar. ' : null}Tanpa skala; utara di atas. Bukan peta navigasi.
                            </p>
                        </>
                    ) : (
                        <p className="mt-2 text-isi">Sketsa tidak tersedia: jalur ini belum punya garis rute atau koordinat pos.</p>
                    )}
                </section>

                <section aria-labelledby="cetak-pos">
                    <h3 id="cetak-pos" className="text-bagian">
                        Pos
                    </h3>
                    {pos.length > 0 ? (
                        // Lima kolom tidak muat di layar 320 px: tabelnya bergulir di wadahnya sendiri,
                        // bukan halamannya (WCAG 1.4.10). Di kertas A4 lebarnya cukup.
                        <div className="relative mt-2 overflow-x-auto">
                            <table className="w-full border-collapse text-left text-kecil">
                                <thead>
                                    <tr className="border-b-2 border-current">
                                        <th scope="col" className="py-1 pr-2">
                                            No
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Pos
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Ketinggian
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Dari pos sebelumnya
                                        </th>
                                        <th scope="col" className="py-1">
                                            Waktu dari awal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pos.map((p) => (
                                        <tr key={p.id} className="break-inside-avoid border-b border-current align-top">
                                            <td className="py-1 pr-2 tabular-nums">{p.urutan}</td>
                                            <td className="py-1 pr-2">
                                                <span className="font-bold">{p.nama}</span> <span>({p.jenis})</span>
                                            </td>
                                            <td className="py-1 pr-2 tabular-nums">{p.elevasi ?? 'Belum diketahui'}</td>
                                            <td className="py-1 pr-2">{p.antara ?? ''}</td>
                                            <td className="py-1">{p.waktu ?? ''}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="mt-2 text-isi">Jalur ini belum punya pos tercatat.</p>
                    )}
                </section>

                <section aria-labelledby="cetak-aturan" className="break-inside-avoid">
                    <h3 id="cetak-aturan" className="text-bagian">
                        Aturan resmi
                    </h3>
                    {aturan ? (
                        <>
                            <p className="mt-2 text-isi">{aturan.ringkasan}</p>
                            {aturan.catatan ? <p className="mt-1 text-isi">{aturan.catatan}</p> : null}
                            <p className="mt-1 text-kecil">{aturan.dicek ? `Dicek Memount ${aturan.dicek}. Pastikan di pos registrasi.` : 'Belum diverifikasi Memount. Pastikan di pos registrasi.'}</p>
                        </>
                    ) : (
                        <p className="mt-2 text-isi">Aturan resmi jalur ini belum tercatat di Memount. Tanyakan di pos registrasi.</p>
                    )}
                </section>

                {pesan ? (
                    <section aria-labelledby="cetak-pesan" className="break-inside-avoid">
                        <h3 id="cetak-pesan" className="text-bagian">
                            Pesan untuk kontak
                        </h3>
                        <p className="mt-2 whitespace-pre-line break-words text-kecil">{pesan}</p>
                    </section>
                ) : null}

                <p className="border-t-2 border-current pt-3 text-kecil">Kertas ini cadangan untuk ponsel, bukan peta navigasi. Ikuti petunjuk petugas di jalur.</p>
            </article>
        </main>
    );
}
