import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
import { LembarBagikan, type TautanAktif } from '@/components/trails/banding/LembarBagikan';
import type { JalurTersimpan } from '@/components/trails/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trails: JalurTersimpan[];
    batas: number;
    batasBanding: number;
    batasCatatan: number;
    kataBelumDinilai: string;
    tautanAktif: TautanAktif[];
    hariBerlakuTautan: number;
    pengumuman: string;
}

/**
 * Simpanan (spec v3 bagian 6.6, prototype 06; DK-05: simpan longgar, banding sempit), dari
 * simpanan di basis data: tetap ada setelah keluar dan masuk lagi, juga di perangkat lain.
 *
 * Setiap kartu membawa centang banding berlabel nama jalurnya, di dalam satu kelompok yang
 * menyebut batasnya. Baki banding melayang di atas bilah tab sebagai satu baris ringkas
 * (AksiPilihan): hitungan "Dipilih N dari 3", lalu "Bandingkan N jalur", atau tanpa centang
 * "Bandingkan N terbaru". Mencentang lebih dari batas tidak dimatikan diam-diam: bakinya
 * menyebut batasnya dengan kalimat pendek. Centang baru ditawarkan bila ada minimal dua
 * simpanan yang masih terbit (jalur yang ditarik dari terbit tidak dapat dicentang, jadi tidak
 * dihitung).
 *
 * Halaman ini pemilik toast Urungkan penghapusan (ruling W2-5i): kartunya hilang oleh
 * aksinya sendiri, jadi useKerangka dipanggil di sini dan diteruskan ke kartu.
 */
export default function Saved() {
    const { trails, batas, batasBanding, batasCatatan, kataBelumDinilai, tautanAktif, hariBerlakuTautan, pengumuman } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [dipilih, setDipilih] = useState<number[]>([]);
    const [pesan, setPesan] = useState<string | null>(null);
    const [bagikanTerbuka, setBagikanTerbuka] = useState(false);
    const pemicuBagikan = useRef<HTMLElement | null>(null);
    const tersedia = trails.filter((t) => t.terbit).length;
    const berlaku = (ids: number[]) => ids.filter((id) => trails.some((t) => t.id === id && t.terbit));
    // Jalur yang baru dihapus atau ditarik dari terbit tidak boleh tertinggal di pilihan.
    const pilihanBerlaku = berlaku(dipilih);

    // Yang dibagikan: pilihan banding bila ada dua atau lebih, selain itu simpanan terbaru
    // yang masih terbit, sama dengan pilihan bawaan halaman Banding.
    const dibagikan = (pilihanBerlaku.length >= 2 ? pilihanBerlaku.map((id) => trails.find((t) => t.id === id)) : trails.filter((t) => t.terbit).slice(0, batasBanding))
        .filter((t): t is JalurTersimpan => t !== undefined)
        .map((t) => ({ id: t.id, name: t.name }));

    const bukaBagikan = (e: MouseEvent<HTMLElement>) => {
        pemicuBagikan.current = e.currentTarget;
        setBagikanTerbuka(true);
    };

    // Pesan batas basi begitu pilihan berubah dari luar centang, misalnya jalur terpilih dihapus
    // atau ditarik dari terbit (T7-M1 tinjauan akhir W3).
    const kunciPilihan = pilihanBerlaku.join(',');
    useEffect(() => setPesan(null), [kunciPilihan]);

    // Teks yang sama dua kali tidak diumumkan ulang oleh wilayah role="status", jadi pengulangan
    // diberi pembeda tak terlihat (spasi tak-putus) supaya menekan centang keempat lagi tetap terdengar.
    const umumkan = (teks: string) => setPesan((lama) => (lama === teks ? teks + String.fromCharCode(0xa0) : teks));

    const pilih = (id: number) => {
        const d = berlaku(dipilih);

        if (d.includes(id)) {
            setDipilih(d.filter((x) => x !== id));
            setPesan(null);
        } else if (d.length >= batasBanding) {
            umumkan(`Maksimal ${batasBanding}. Lepas satu dulu.`);
        } else {
            setDipilih([...d, id]);
            setPesan(null);
        }
    };

    // Daftar kartu dipakai dengan atau tanpa kelompok centang (T7-M4); simpanan kosong punya
    // keadaan kosongnya sendiri di bawah.
    const daftar =
        trails.length > 0 ? (
            <ul className="space-y-3">
                {trails.map((t) => (
                    <KartuSimpanan
                        key={t.id}
                        jalur={t}
                        batasCatatan={batasCatatan}
                        pilihan={tersedia >= 2 ? { dipilih: pilihanBerlaku.includes(t.id) } : null}
                        onPilih={pilih}
                        kataBelumDinilai={kataBelumDinilai}
                        tampilkanToast={tampilkanToast}
                    />
                ))}
            </ul>
        ) : null;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: trailsIndex.url(), label: 'Jelajah' }}
                    title="Simpanan"
                    description={`${trails.length} jalur tersimpan, maksimal ${batas}.${tersedia >= 2 ? ` Pilih sampai ${batasBanding} untuk dibandingkan.` : ''}`}
                    aksi={
                        tersedia >= 2 || tautanAktif.length > 0 ? (
                            <Button variant="secondary" size="sm" onClick={bukaBagikan}>
                                <Icon name="link-simple" size={20} />
                                Bagikan
                            </Button>
                        ) : undefined
                    }
                />

                {trails.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada jalur tersimpan"
                        description="Tekan tombol simpan pada kartu jalur di Jelajah. Jalur yang Anda simpan terkumpul di sini, bisa diberi catatan pribadi, dan dibandingkan berdampingan."
                    >
                        <Button href={trailsIndex.url()}>Cari jalur di Jelajah</Button>
                    </EmptyState>
                ) : (
                    <>
                        {tersedia >= 2 ? null : <p className="mb-3 text-kecil text-secondary">Simpan satu jalur lagi untuk membandingkan.</p>}

                        {/* Kelompok centang hanya bila ada centang (T7-M4): tanpa itu fieldset dan
                            legend "Pilih sampai N" membungkus formulir catatan, bukan pilihan. */}
                        {tersedia >= 2 ? (
                            <fieldset>
                                <legend className="sr-only">Pilih sampai {batasBanding} jalur untuk dibandingkan</legend>
                                {daftar}
                            </fieldset>
                        ) : (
                            daftar
                        )}

                        {tersedia >= 2 ? (
                            <BarAksi label="Pilihan banding" lebarPenuh>
                                <AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} pesan={pesan} />
                            </BarAksi>
                        ) : null}
                    </>
                )}

                <LembarBagikan terbuka={bagikanTerbuka} onTutup={() => setBagikanTerbuka(false)} pemicu={pemicuBagikan} jalur={dibagikan} tautanAktif={tautanAktif} hariBerlaku={hariBerlakuTautan} />

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Saved.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
