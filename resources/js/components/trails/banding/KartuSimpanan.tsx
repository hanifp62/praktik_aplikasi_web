import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { TrailRow } from '@/components/trails/TrailRow';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Checkbox, InputError, Label, Textarea } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { remove } from '@/routes/trails/saved';
import { destroy as ruteHapusCatatan, update as ruteSimpanCatatan } from '@/routes/trails/saved/note';

interface KartuSimpananProps {
    jalur: JalurTersimpan;
    /** Batas panjang catatan dari server (TrailConsideration::CATATAN_MAKS). */
    batasCatatan: number;
    /** Keadaan centang banding, atau null bila simpanan belum cukup untuk dibandingkan. */
    pilihan: { dipilih: boolean; penuh: boolean } | null;
    onPilih: (id: number) => void;
}

/**
 * Satu jalur tersimpan: baris jalur, centang untuk banding, catatan pribadi (FR-22), dan
 * penghapusan dari simpanan.
 *
 * Catatan hanya milik pemiliknya: tampil di sini, tidak pernah ikut ke halaman Banding
 * maupun tautan bagikan, dan dirender sebagai teks. Kolom isi boleh menyempit sampai nol
 * (minmax(0,1fr)) dan catatannya memakai overflow-wrap: anywhere, jadi untaian tanpa
 * spasi tidak melebarkan halaman di layar 360 px.
 *
 * Jalur yang ditarik dari terbit tetap tampil supaya catatannya tidak hilang, tetapi tidak
 * dapat dicentang: halaman Banding hanya membandingkan jalur terbit (R-022). Garis bawah
 * TrailRow dinetralkan dari sini ([&>article]:border-b-0) supaya satu kartu hanya punya
 * satu garis pemisah; TrailRow sendiri tidak diubah.
 *
 * Fokus mengikuti status sunting (WCAG 2.4.3): membuka editor memindahkan fokus ke
 * textarea, dan menutupnya (batal, simpan sukses, atau hapus catatan sukses) mengembalikan
 * fokus ke tombol pemicu, supaya pengguna keyboard dan pembaca layar tidak kehilangan
 * posisi ketika tombol yang tadinya berfokus lenyap dari DOM. Menghapus jalur yang punya
 * catatan bertanya dulu, karena baris yang sama menyimpan keduanya
 * (ConsiderationService::remove() menghapus baris beserta note-nya).
 */
export function KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih }: KartuSimpananProps) {
    const [menyunting, setMenyunting] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const [menghapusCatatan, setMenghapusCatatan] = useState(false);
    const form = useForm({ catatan: jalur.catatan ?? '' });
    const pertama = useRef(true);

    const idPilih = `pilih-${jalur.id}`;
    const idCatatan = `catatan-${jalur.id}`;
    const idPemicu = `pemicu-catatan-${jalur.id}`;
    const panjang = Array.from(form.data.catatan).length;
    const alasanTidakDapatDipilih =
        pilihan === null
            ? null
            : !jalur.terbit
              ? 'Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.'
              : pilihan.penuh && !pilihan.dipilih
                ? 'Batas banding sudah tercapai. Lepas satu centang untuk memilih jalur ini.'
                : null;

    // Baru berjalan mulai perubahan kedua (pertama.current): render pertama tidak boleh
    // mencuri fokus halaman ketika Simpanan baru dibuka.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById(menyunting ? idCatatan : idPemicu)?.focus();
    }, [menyunting, idCatatan, idPemicu]);

    const hapus = () => {
        if (jalur.catatan && !window.confirm('Hapus jalur ini dari simpanan? Catatan pribadinya ikut terhapus dan tidak dapat dikembalikan.')) {
            return;
        }

        setMenghapus(true);
        router.delete(remove.url({ trail: jalur.id }), { preserveScroll: true, onFinish: () => setMenghapus(false) });
    };

    const mulaiSunting = () => {
        form.setData('catatan', jalur.catatan ?? '');
        form.clearErrors();
        setMenyunting(true);
    };

    const batalSunting = () => {
        form.setData('catatan', jalur.catatan ?? '');
        form.clearErrors();
        setMenyunting(false);
    };

    const simpanCatatan = (e: FormEvent) => {
        e.preventDefault();
        form.put(ruteSimpanCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onError: (galat) => fokusGalat(galat, [['catatan', idCatatan]]),
        });
    };

    const hapusCatatan = () => {
        setMenghapusCatatan(true);
        router.delete(ruteHapusCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onFinish: () => setMenghapusCatatan(false),
        });
    };

    return (
        <li className={kelas('grid border-b border-subtle', pilihan === null ? 'grid-cols-1' : 'grid-cols-[2.75rem_minmax(0,1fr)]')}>
            {pilihan === null ? null : (
                <div className="pt-4">
                    <label
                        htmlFor={idPilih}
                        className={kelas('inline-flex min-h-11 min-w-11 items-center justify-center', alasanTidakDapatDipilih === null && 'cursor-pointer')}
                    >
                        <Checkbox
                            id={idPilih}
                            checked={pilihan.dipilih}
                            disabled={alasanTidakDapatDipilih !== null}
                            onChange={() => onPilih(jalur.id)}
                            aria-describedby={alasanTidakDapatDipilih ? `pilih-${jalur.id}-hint` : undefined}
                        />
                        <span className="sr-only">Pilih {jalur.name} untuk dibandingkan</span>
                    </label>
                </div>
            )}

            <div className="min-w-0 pb-5 [&>article]:border-b-0 [&>article]:pb-3">
                <TrailRow trail={jalur} aksiSimpan={false} />

                {alasanTidakDapatDipilih ? (
                    <p id={`pilih-${jalur.id}-hint`} className="text-xs text-muted">
                        {alasanTidakDapatDipilih}
                    </p>
                ) : null}

                {menyunting ? (
                    <form onSubmit={simpanCatatan} className="mt-3 space-y-2" noValidate>
                        <Label htmlFor={idCatatan}>Catatan pribadi</Label>
                        <Textarea
                            id={idCatatan}
                            name="catatan"
                            rows={3}
                            value={form.data.catatan}
                            onChange={(e) => form.setData('catatan', e.target.value)}
                            aria-invalid={form.errors.catatan ? true : undefined}
                            aria-describedby={form.errors.catatan ? `catatan-${jalur.id}-hint catatan-${jalur.id}-error` : `catatan-${jalur.id}-hint`}
                        />
                        <p id={`catatan-${jalur.id}-hint`} className={kelas('text-xs', panjang > batasCatatan ? 'font-medium text-danger-900' : 'text-muted')}>
                            Hanya Anda yang dapat membaca catatan ini. {panjang} dari {batasCatatan} karakter.
                        </p>
                        <InputError id={`catatan-${jalur.id}-error`} messages={form.errors.catatan} />
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" size="sm" processing={form.processing}>
                                Simpan catatan
                            </Button>
                            {jalur.catatan ? (
                                <Button variant="ghost" size="sm" processing={menghapusCatatan} onClick={hapusCatatan}>
                                    Hapus catatan
                                </Button>
                            ) : null}
                            <Button variant="ghost" size="sm" onClick={batalSunting}>
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : jalur.catatan ? (
                    <div className="mt-2">
                        <p className="text-xs font-medium text-muted">Catatan pribadi, hanya terlihat oleh Anda</p>
                        <p className="mt-1 whitespace-pre-line text-sm text-primary [overflow-wrap:anywhere]">{jalur.catatan}</p>
                    </div>
                ) : null}

                <div className="mt-2 flex flex-wrap gap-x-2">
                    {menyunting ? null : (
                        <Button id={idPemicu} variant="ghost" size="sm" className="-ml-4" onClick={mulaiSunting}>
                            {jalur.catatan ? 'Ubah catatan' : 'Tulis catatan'}
                            <span className="sr-only"> untuk {jalur.name}</span>
                        </Button>
                    )}
                    <Button variant="ghost" size="sm" className={menyunting ? '-ml-4' : undefined} processing={menghapus} onClick={hapus}>
                        Hapus dari simpanan<span className="sr-only">: {jalur.name}</span>
                    </Button>
                </div>
            </div>
        </li>
    );
}
