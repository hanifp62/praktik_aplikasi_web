import { useCallback, useEffect, useRef, useState, type RefObject } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { LembarBawah } from '@/components/ui/Lembar';
import { Skeleton } from '@/components/ui/Skeleton';
import { Tautan } from '@/components/ui/Tautan';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { index as tripsIndex } from '@/routes/trips';
import type { DaftarPengingat, PropsBersama } from '@/types';
import { muatUlangMilikKerangka, sedangMemuatPenuh, useKerangka } from './Kerangka';

type Keadaan = { jenis: 'memuat' } | { jenis: 'galat' } | { jenis: 'siap'; daftar: DaftarPengingat };

const baris = 'flex min-h-11 flex-col justify-center gap-0.5 rounded-control px-2 py-2 hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600';

/**
 * Lonceng Beranda (spec v3 bagian 5.1; DK-40): membuka daftar pengingat yang benar-benar
 * ada. Tanpa titik atau angka di atasnya: isinya baru dimuat saat lembar dibuka, dan lonceng
 * yang menjanjikan jumlah yang belum dihitung akan berbohong.
 */
export function TombolPengingat() {
    const { bukaPengingat } = useKerangka();

    return <TombolIkon ikon="bell" label="Pengingat" onClick={(e) => bukaPengingat(e.currentTarget)} />;
}

/**
 * Lembar Pengingat, dipasang sekali di AppLayout (rencana induk W2 butir 5 dan 6; ruling
 * W2-R2). Isinya props bersama opsional `daftarPengingat` yang diminta lewat muat ulang parsial
 * setiap kali lembar dibuka, jadi kunjungan biasa tidak pernah membayarnya dan isinya selalu
 * terbaru. Empat keadaan: memuat, galat (jaringan putus atau jawaban bukan halaman), kosong
 * yang menyebut apa yang akan muncul di sini, dan daftar. Galatnya ditangani di sini dan
 * tidak diteruskan ke pesan jaringan global, supaya satu kegagalan tidak dilaporkan dua kali.
 *
 * Wilayah isi membawa tabIndex={-1} (Ruling W2-6a #3): tombol "Coba lagi" hilang begitu
 * diklik, dan tanpa tujuan fokus pengganti, fokus jatuh ke body di dalam modal (WCAG 2.4.3).
 * Menutup lembar mengembalikan keadaan ke memuat sebelum lembar tampil lagi (Ruling W2-6a
 * #4), supaya pembukaan berikutnya tidak sekejap menampilkan daftar atau galat basi dari
 * sesi sebelumnya.
 *
 * Diperbarui Ruling W2-6e #3: bila lembar ditutup sebelum jawaban server tiba, jawaban
 * terlambat itu tidak boleh menimpa reset yang sudah dilakukan penutupan. terbukaRef
 * membawa nilai terbuka TERKINI (bukan closure basi dari saat muat() dipanggil), dan
 * onSuccess/onNetworkError/onHttpException memeriksanya sebelum mengubah keadaan.
 *
 * Diperbarui perbaikan akhir W2 (I-2): ada kunjungan yang berakhir tanpa memanggil satu pun
 * dari ketiga callback itu, yaitu konflik versi aset pada kunjungan async (Inertia 3
 * mengabaikannya; karena muat ulang ini ditandai muatUlangMilikKerangka, PesanJaringan lalu
 * memuat penuh halaman, Ruling W2-F6), kunjungan yang dibatalkan, dan jawaban bergalat
 * validasi. onFinish selalu dipanggil, jadi ia jaring pengamannya: bila
 * lembar masih terbuka dan kunjungan ini belum dijawab, keadaannya menjadi galat dengan
 * "Coba lagi", bukan kerangka memuat tanpa akhir. Penanda dijawab dibuat baru di setiap
 * panggilan muat(), supaya jawaban kunjungan lama tidak menandai kunjungan baru.
 *
 * Diperbarui Ruling W2-F7 (B-4): bila kunjungan itu berakhir karena halaman sedang dimuat penuh
 * akibat konflik versi (sedangMemuatPenuh() dari Kerangka.tsx), jaring ini tidak menyetel
 * galat. Pesan "Sambungan ke server terputus" keliru untuk kasus itu; kerangka memuat tetap
 * tampil sampai halaman berganti.
 *
 * Diperbarui Ruling W5-G1 (R-007): kalimat "Memount menautkan sistem resmi dan tidak menjual
 * izin." di bawah daftar Tenggat izin hanya ditulis bila paling sedikit satu butir daftar itu
 * sungguh menampilkan tautan bookingUrl; selain itu kalimatnya "Memount tidak memesan atau
 * menjual izin.", sama persis dengan KartuIzin.tsx.
 */
export function LembarPengingat({ terbuka, onTutup, pemicu }: { terbuka: boolean; onTutup: () => void; pemicu: RefObject<HTMLElement | null> }) {
    const [keadaan, setKeadaan] = useState<Keadaan>({ jenis: 'memuat' });
    const isi = useRef<HTMLDivElement>(null);
    const terbukaRef = useRef(terbuka);

    useEffect(() => {
        terbukaRef.current = terbuka;
    }, [terbuka]);

    const muat = useCallback(() => {
        let dijawab = false;

        setKeadaan({ jenis: 'memuat' });
        muatUlangMilikKerangka({
            only: ['daftarPengingat'],
            onSuccess: (halaman) => {
                dijawab = true;

                if (!terbukaRef.current) {
                    return;
                }

                const daftar = (halaman.props as Partial<PropsBersama>).daftarPengingat;
                setKeadaan(daftar ? { jenis: 'siap', daftar } : { jenis: 'galat' });
            },
            onNetworkError: () => {
                dijawab = true;

                if (terbukaRef.current) {
                    setKeadaan({ jenis: 'galat' });
                }

                return false;
            },
            onHttpException: () => {
                dijawab = true;

                if (terbukaRef.current) {
                    setKeadaan({ jenis: 'galat' });
                }

                return false;
            },
            onFinish: () => {
                if (terbukaRef.current && !dijawab && !sedangMemuatPenuh()) {
                    setKeadaan({ jenis: 'galat' });
                }
            },
        });
    }, []);

    useEffect(() => {
        if (terbuka) {
            muat();
        } else {
            // Disetel di sini, bukan menunggu useEffect sesudah lembar tampil lagi, supaya
            // pembukaan berikutnya langsung menampilkan kerangka memuat di frame pertama,
            // bukan daftar atau galat basi dari sesi sebelumnya (Ruling W2-6a #4).
            setKeadaan({ jenis: 'memuat' });
        }
    }, [terbuka, muat]);

    // Klaim "menautkan" di bawah daftar tenggat izin hanya benar ketika paling sedikit satu
    // tautan sistem resmi di daftar itu sungguh tampil (ruling W5-G1, R-007: tidak mengklaim
    // yang tidak ada).
    const adaTautanIzin = keadaan.jenis === 'siap' && keadaan.daftar.tenggatIzin.some((t) => t.bookingUrl);

    return (
        <LembarBawah terbuka={terbuka} onTutup={onTutup} pemicu={pemicu} judul="Pengingat" keterangan="Pengingat surel terjadwal dan tenggat izin untuk trip Anda. Memount belum mengirim notifikasi ke ponsel.">
            <div ref={isi} tabIndex={-1} className="focus:outline-none">
                {keadaan.jenis === 'memuat' ? (
                    <Skeleton rows={4} />
                ) : keadaan.jenis === 'galat' ? (
                    <Alert variant="warning" title="Pengingat belum dapat dimuat">
                        <p>Sambungan ke server terputus atau server tidak menjawab. Daftar ini tidak ditebak dari data lama.</p>
                        <Button
                            variant="secondary"
                            size="sm"
                            className="mt-3"
                            onClick={() => {
                                muat();
                                requestAnimationFrame(() => isi.current?.focus());
                            }}
                        >
                            Coba lagi
                        </Button>
                    </Alert>
                ) : keadaan.daftar.pengingat.length === 0 && keadaan.daftar.tenggatIzin.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada pengingat"
                        description="Pengingat surel H-3 dan H-1 untuk trip yang pengingatnya Anda nyalakan, dan tenggat pemesanan izin untuk trip mendatang, akan muncul di sini."
                    >
                        <Button variant="secondary" href={tripsIndex.url()}>
                            Lihat trip Anda
                        </Button>
                    </EmptyState>
                ) : (
                    <div className="space-y-6">
                        {keadaan.daftar.pengingat.length > 0 ? (
                            <section aria-labelledby="pengingat-surel">
                                <h3 id="pengingat-surel" className="text-subjudul text-primary">
                                    Pengingat surel terjadwal
                                </h3>
                                <ul className="mt-2 divide-y divide-subtle">
                                    {keadaan.daftar.pengingat.map((p) => (
                                        <li key={p.id}>
                                            <Tautan href={p.url} className={baris}>
                                                <span className="font-bold text-primary">{p.trip}</span>
                                                <span className="text-kecil text-secondary">
                                                    {p.jenis}: {p.waktu}
                                                </span>
                                            </Tautan>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}
                        {keadaan.daftar.tenggatIzin.length > 0 ? (
                            <section aria-labelledby="pengingat-izin">
                                <h3 id="pengingat-izin" className="text-subjudul text-primary">
                                    Tenggat izin
                                </h3>
                                <ul className="mt-2 divide-y divide-subtle">
                                    {keadaan.daftar.tenggatIzin.map((t) => (
                                        <li key={t.tripId}>
                                            <Tautan href={t.url} className={baris}>
                                                <span className="font-bold text-primary">{t.trip}</span>
                                                <span className="text-kecil text-primary">{t.kalimat}</span>
                                                <span className="text-kecil text-secondary">Pengelola: {t.pengelola}</span>
                                            </Tautan>
                                            {t.bookingUrl ? (
                                                <a
                                                    href={t.bookingUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="ms-2 inline-flex min-h-11 items-center px-2 text-kecil text-brand-ink underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                                >
                                                    Buka sistem pemesanan resmi
                                                    <span className="sr-only"> (membuka tab baru)</span>
                                                </a>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                                {/* Klaim "menautkan" hanya benar ketika tautannya sungguh tampil di atas (ruling
                                    W5-G1); tanpa tautan sama sekali, klaim yang tersisa adalah yang tidak pernah
                                    menjual atau memesan sendiri (R-007: tidak mengklaim yang tidak ada). */}
                                <p className="mt-2 text-kecil text-secondary">{adaTautanIzin ? 'Memount menautkan sistem resmi dan tidak menjual izin.' : 'Memount tidak memesan atau menjual izin.'}</p>
                            </section>
                        ) : null}
                    </div>
                )}
            </div>
        </LembarBawah>
    );
}
