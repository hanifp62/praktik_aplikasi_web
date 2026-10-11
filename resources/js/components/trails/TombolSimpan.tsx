import { router } from '@inertiajs/react';
import { useRef, useState, type MouseEvent } from 'react';
import { Button, useFokusKembali } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Dialog } from '@/components/ui/Lembar';
import { TombolIkon } from '@/components/ui/TombolIkon';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { saved } from '@/routes/trails';
import { remove, store } from '@/routes/trails/saved';
import type { PropsBersama } from '@/types';

export interface JalurSimpan {
    id: number;
    name: string;
    ditimbang: boolean;
    adaCatatan: boolean;
}

interface TombolSimpanProps {
    jalur: JalurSimpan;
    /**
     * tampilkanToast milik halaman atau daftar yang bertahan (useKerangka di sana, ruling
     * W2-5i), bukan milik tombol ini: kartu yang hilang oleh aksinya sendiri, misalnya di
     * Simpanan, ikut menutup toast yang dimilikinya.
     */
    tampilkanToast: LayananKerangka['tampilkanToast'];
    /**
     * "ikon" untuk kartu Jelajah (44 px), "bar" untuk bar aksi ponsel rincian jalur (tombol
     * ikon 44 px bergaris, supaya barnya tetap satu baris), "tombol" untuk kolom kanan layar
     * lebar, "hapus" untuk kartu Simpanan (setiap jalur di sana sudah tersimpan, jadi aksinya
     * menyebut dirinya penghapusan).
     */
    bentuk?: 'ikon' | 'bar' | 'tombol' | 'hapus';
}

const opsi = { preserveScroll: true, preserveState: true } as const;

/**
 * Simpan dan lepas simpanan dengan urungkan (spec v3 bagian 6.2 dan 9.1). Keduanya idempoten:
 * simpan memakai POST trails/saved/{trail} dan lepas memakai DELETE-nya, jadi halaman yang
 * basi tidak pernah membalik keadaan. Urungkan mengirim aksi kebalikannya; melepas simpanan
 * mengembalikan catatan pribadinya lewat flash simpananTerhapus, jadi Urungkan memulihkannya
 * utuh. Simpanan yang bercatatan bertanya dulu, karena catatan hilang bila toastnya terlewat.
 * Simpanan penuh dijawab dialog yang menyebut batasnya dan jalan ke Simpanan, di tempat
 * tombol ditekan, bukan pesan di ujung halaman.
 *
 * Keadaannya dibawa bentuk ikon (isi) dan aria-pressed, bukan warna saja. Aksi dari papan
 * ketik (event.detail === 0) memindah fokus ke Urungkan supaya terjangkau sebelum toast
 * hilang (W1-F4, WCAG 2.2.1).
 *
 * Bila flash.simpananSudahAda menyebut jalur ini, toast Urungkan tidak ditawarkan: baris itu
 * sudah ada sebelum tombol ini ditekan (tab atau perangkat lain), sehingga
 * Urungkan-sebagai-hapus akan menghapus baris dan catatan yang tidak dibuat tombol ini (ruling
 * W3-4b). Flash simpanan dan penghapusan dibaca hanya bila id jalurnya cocok (pola W4-4e).
 *
 * Bentuk ikon dan bar memakai TombolIkon yang `disabled` selama memproses, jadi fokusnya
 * dikembalikan lewat useFokusKembali yang sama dengan Button (ruling AF-2, audit E2E I-2): klik
 * tetikus yang memfokus tombol tidak lagi meninggalkan fokus di body. Bentuk tombol dan hapus
 * memakai Button, yang sudah melakukannya sendiri.
 */
export function TombolSimpan({ jalur, tampilkanToast, bentuk = 'ikon' }: TombolSimpanProps) {
    const [memproses, setMemproses] = useState(false);
    const [tanya, setTanya] = useState(false);
    const [penuh, setPenuh] = useState<string | null>(null);
    const papanKetik = useRef(false);
    const pemicu = useRef<HTMLButtonElement | null>(null);
    const fokusKembali = useFokusKembali(memproses);

    const simpan = (catatan: string | null = null, urungkan = true) => {
        setMemproses(true);
        router.post(store.url({ trail: jalur.id }), { catatan }, {
            ...opsi,
            onFinish: () => setMemproses(false),
            onSuccess: (halaman) => {
                const flash = (halaman.props as unknown as PropsBersama).flash;
                const ditolak = flash.timbanganPenuh;
                // Berkunci id jalur (pola W4-4e): jawaban milik jalur lain bukan jawaban tombol ini.
                const sudahAda = flash.simpananSudahAda?.trailId === jalur.id;

                if (ditolak) {
                    setPenuh(ditolak);
                } else if (sudahAda) {
                    // Baris sudah ada sebelum simpan() ini, jadi tidak ditawarkan Urungkan:
                    // pengumuman role="status" halaman dan tombol tertekan dari props yang
                    // dimuat ulang sudah cukup (ruling W3-4b).
                } else if (urungkan) {
                    tampilkanToast(`${jalur.name} disimpan.`, () => router.delete(remove.url({ trail: jalur.id }), opsi), { fokus: papanKetik.current });
                }
            },
        });
    };

    const lepas = () => {
        setMemproses(true);
        router.delete(remove.url({ trail: jalur.id }), {
            ...opsi,
            onFinish: () => setMemproses(false),
            onSuccess: (halaman) => {
                const terhapus = (halaman.props as unknown as PropsBersama).flash.simpananTerhapus;

                if (!terhapus || terhapus.trailId !== jalur.id) {
                    // Tidak ada baris yang terhapus (sudah dihapus di tab atau perangkat lain), jadi
                    // tidak ada yang dapat diurungkan; pengumuman role="status" halaman menyebutnya.
                    return;
                }

                // Urungkan menyimpan lagi lewat tombol ini, yang `disabled` selama kirimannya, jadi
                // pengembalian fokus toast ke tombol itu gagal dan jatuh ke body. Tanda dicatat di
                // sini supaya fokus kembali ke tombol sesudah kiriman selesai (ruling AF-2).
                // Bentuk hapus (kartu Simpanan) lepas bersama kartunya, jadi fokus pindah ke Urungkan
                // juga dari tetikus: tombolnya tidak ada lagi untuk menerima fokus kembali.
                tampilkanToast(
                    `${jalur.name} dihapus dari simpanan.`,
                    () => {
                        fokusKembali.tandai();
                        simpan(terhapus?.catatan ?? null, false);
                    },
                    { fokus: papanKetik.current || bentuk === 'hapus' },
                );
            },
        });
    };

    const tekan = (e: MouseEvent<HTMLButtonElement>) => {
        papanKetik.current = e.detail === 0;
        pemicu.current = e.currentTarget;
        fokusKembali.tandai(e);

        if (!jalur.ditimbang) {
            simpan();
        } else if (jalur.adaCatatan) {
            setTanya(true);
        } else {
            lepas();
        }
    };

    return (
        <>
            {bentuk === 'hapus' ? (
                <Button variant="ghost" size="sm" processing={memproses} onClick={tekan}>
                    Hapus dari simpanan<span className="sr-only">: {jalur.name}</span>
                </Button>
            ) : bentuk === 'ikon' ? (
                <TombolIkon
                    ikon="bookmark-simple"
                    isi={jalur.ditimbang}
                    label={`Simpan ${jalur.name}`}
                    aria-pressed={jalur.ditimbang}
                    disabled={memproses}
                    onClick={tekan}
                    varian="polos"
                    className="relative z-10 text-brand-ink"
                />
            ) : bentuk === 'bar' ? (
                <TombolIkon
                    ikon="bookmark-simple"
                    isi={jalur.ditimbang}
                    label={`Simpan ${jalur.name}`}
                    aria-pressed={jalur.ditimbang}
                    disabled={memproses}
                    onClick={tekan}
                    bentuk="kotak"
                    className="text-brand-ink"
                />
            ) : (
                <Button variant="secondary" aria-pressed={jalur.ditimbang} processing={memproses} onClick={tekan}>
                    <Icon name="bookmark-simple" size={20} isi={jalur.ditimbang} />
                    {jalur.ditimbang ? 'Tersimpan' : 'Simpan'}
                </Button>
            )}
            <Dialog
                terbuka={tanya}
                onTutup={() => setTanya(false)}
                judul="Hapus dari simpanan?"
                keterangan="Catatan pribadi pada jalur ini ikut terhapus. Anda masih dapat mengurungkannya sesaat setelah ini."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setTanya(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                setTanya(false);
                                lepas();
                            }}
                        >
                            Hapus
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{jalur.name}</p>
            </Dialog>
            <Dialog
                terbuka={penuh !== null}
                onTutup={() => setPenuh(null)}
                judul="Simpanan sudah penuh"
                keterangan={penuh ?? undefined}
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setPenuh(null)}>
                            Tutup
                        </Button>
                        <Button href={saved.url()}>Buka Simpanan</Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{jalur.name} belum disimpan.</p>
            </Dialog>
        </>
    );
}
