import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { InputError, Label, Textarea } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Tautan } from '@/components/ui/Tautan';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { kelas } from '@/lib/kelas';
import { destroy as ruteHapusCatatan, update as ruteSimpanCatatan } from '@/routes/trails/saved/note';

interface KartuSimpananProps {
    jalur: JalurTersimpan;
    /** Batas panjang catatan dari server (TrailConsideration::CATATAN_MAKS). */
    batasCatatan: number;
    /** Keadaan centang banding, atau null bila simpanan belum cukup untuk dibandingkan. */
    pilihan: { dipilih: boolean } | null;
    onPilih: (id: number) => void;
    /** Kata UNKNOWN dari server, untuk jalur yang kecocokannya tidak dihitung. */
    kataBelumDinilai: string;
    /** Toast Urungkan milik halaman Simpanan: kartunya hilang oleh penghapusannya sendiri (W2-5i). */
    tampilkanToast: LayananKerangka['tampilkanToast'];
}

/**
 * Satu jalur tersimpan (spec v3 bagian 6.6, prototype 06): centang banding berlabel nama
 * jalurnya (KotakCentang, bukan kotak 28 px tanpa label), label kecocokan, tiga angka,
 * catatan pribadi (FR-22), lalu aksi: tulis atau ubah catatan, lihat rincian, dan hapus dari
 * simpanan dengan urungkan.
 *
 * Catatan hanya milik pemiliknya: tampil di sini, tidak pernah ikut ke halaman Banding
 * maupun tautan bagikan, dan dirender sebagai teks. Kolom isi boleh menyempit sampai nol
 * (minmax(0,1fr)) dan catatannya memakai overflow-wrap: anywhere, jadi untaian tanpa spasi
 * tidak melebarkan halaman di layar 360 px.
 *
 * Jalur yang ditarik dari terbit tetap tampil supaya catatannya tidak hilang, tetapi tidak
 * dapat dicentang: halaman Banding hanya membandingkan jalur terbit (R-022). Ketika batas
 * banding tercapai, centang lain tidak dimatikan: menekannya menyebut batasnya di baki (halaman
 * yang memutuskan), karena kontrol yang mati tidak dapat menjelaskan dirinya.
 *
 * Fokus mengikuti status sunting (WCAG 2.4.3): membuka editor memindahkan fokus ke textarea,
 * dan menutupnya (batal, simpan sukses, atau hapus catatan sukses) mengembalikan fokus ke
 * tombol pemicu. Menghapus jalur yang bercatatan bertanya dulu (TombolSimpan), dan Urungkan
 * mengembalikan jalur beserta catatannya.
 */
export function KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih, kataBelumDinilai, tampilkanToast }: KartuSimpananProps) {
    const [menyunting, setMenyunting] = useState(false);
    const [menghapusCatatan, setMenghapusCatatan] = useState(false);
    const form = useForm({ catatan: jalur.catatan ?? '' });
    const pertama = useRef(true);

    const idCatatan = `catatan-${jalur.id}`;
    const idPemicu = `pemicu-catatan-${jalur.id}`;
    const idAlasan = `pilih-${jalur.id}-hint`;
    const panjang = Array.from(form.data.catatan).length;
    const wilayah = [jalur.mountain, jalur.province].filter(Boolean).join(', ');
    const alasanTidakDapatDipilih = pilihan === null ? null : !jalur.terbit ? 'Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.' : null;

    // Baru berjalan mulai perubahan kedua (pertama.current): render pertama tidak boleh
    // mencuri fokus halaman ketika Simpanan baru dibuka.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById(menyunting ? idCatatan : idPemicu)?.focus();
    }, [menyunting, idCatatan, idPemicu]);

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
        <li className={kelas('grid grid-cols-[minmax(0,1fr)] rounded-lg border bg-surface p-4', pilihan?.dipilih ? 'border-brand-ink' : 'border-subtle')}>
            <div className="min-w-0 space-y-2">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        {pilihan === null ? (
                            <>
                                <p className="text-subjudul text-primary">{jalur.name}</p>
                                <p className="text-meta text-secondary">{wilayah}</p>
                            </>
                        ) : (
                            <KotakCentang
                                label={jalur.name}
                                keterangan={wilayah}
                                checked={pilihan.dipilih}
                                disabled={!jalur.terbit}
                                onChange={() => onPilih(jalur.id)}
                                aria-describedby={alasanTidakDapatDipilih ? idAlasan : undefined}
                                className="py-0"
                            />
                        )}
                    </div>
                    <div className="shrink-0 pt-0.5">
                        <LabelKecocokan nilai={jalur.fit?.label ?? null} label={jalur.fit?.labelText ?? kataBelumDinilai} />
                    </div>
                </div>

                <AngkaRingkas jalur={jalur} className="text-primary" />

                {alasanTidakDapatDipilih ? (
                    <p id={idAlasan} className="text-meta text-muted">
                        {alasanTidakDapatDipilih}
                    </p>
                ) : null}

                {menyunting ? (
                    <form onSubmit={simpanCatatan} className="space-y-2" noValidate>
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
                        <p id={`catatan-${jalur.id}-hint`} className={kelas('text-meta', panjang > batasCatatan ? 'font-bold text-danger-900' : 'text-muted')}>
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
                    <div className="rounded-control bg-surface-sunken px-3 py-2">
                        <p className="flex items-center gap-1.5 text-meta font-bold text-secondary">
                            <Icon name="lock-simple" size={16} />
                            Catatan pribadi, hanya Anda
                        </p>
                        <p className="mt-1 whitespace-pre-line text-kecil text-primary wrap-anywhere">{jalur.catatan}</p>
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-x-2">
                    {menyunting ? null : (
                        <Button id={idPemicu} variant="ghost" size="sm" className="-ml-4" onClick={mulaiSunting}>
                            {jalur.catatan ? 'Ubah catatan' : 'Tambah catatan pribadi'}
                            <span className="sr-only"> untuk {jalur.name}</span>
                        </Button>
                    )}
                    <Tautan href={jalur.url} className="inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat rincian<span className="sr-only"> {jalur.name}</span>
                    </Tautan>
                    <TombolSimpan jalur={{ id: jalur.id, name: jalur.name, ditimbang: true, adaCatatan: jalur.catatan !== null }} tampilkanToast={tampilkanToast} bentuk="hapus" />
                </div>
            </div>
        </li>
    );
}
