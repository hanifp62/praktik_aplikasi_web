import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { note } from '@/routes/trips/preparation';

/** Prop halaman Persiapan dari App\Services\Trips\CatatanButir::untukHalaman(). */
export interface CatatanButirData {
    dapatDiubah: boolean;
    bisa: number[];
    isi: Record<string, string>;
    batas: number;
}

/**
 * Catatan singkat satu butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9), misalnya siapa yang membawa barangnya. Hanya butir Perlengkapan dan Logistik yang
 * dapat diberi catatan, dan hanya selama trip belum selesai atau dibatalkan; catatan yang sudah
 * ada tetap terbaca sesudahnya. Catatan tidak mengubah status butir (BR-10).
 *
 * Fokus mengikuti pola catatan Simpanan (KartuSimpanan): membuka isian memindah fokus ke isian,
 * dan menutupnya (Batal, Escape, atau simpan sukses) mengembalikan fokus ke tombol pemicu, yang
 * namanya menyebut butirnya. Penolakan server tampil di bawah isian dan isian menerima fokus.
 */
export function CatatanButir({ tripId, itemId, label }: { tripId: number; itemId: number; label: string }) {
    const { catatanButir } = usePage<{ catatanButir: CatatanButirData }>().props;
    const catatan = catatanButir.isi[itemId] ?? null;
    const dapatDicatat = catatanButir.dapatDiubah && catatanButir.bisa.includes(itemId);
    const [menyunting, setMenyunting] = useState(false);
    const form = useForm({ catatan: catatan ?? '' });
    const pertama = useRef(true);

    const idIsian = `catatan-butir-${itemId}`;
    const idPemicu = `catatan-butir-${itemId}-pemicu`;
    const panjang = Array.from(form.data.catatan).length;

    // Render pertama tidak boleh mencuri fokus halaman.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById(menyunting ? idIsian : idPemicu)?.focus();
    }, [menyunting, idIsian, idPemicu]);

    if (!dapatDicatat && catatan === null) {
        return null;
    }

    const mulai = () => {
        form.setData('catatan', catatan ?? '');
        form.clearErrors();
        setMenyunting(true);
    };

    const batal = () => {
        form.clearErrors();
        setMenyunting(false);
    };

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.put(note.url({ trip: tripId, item: itemId }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onError: (galat) => fokusGalat(galat, [['catatan', idIsian]]),
        });
    };

    if (menyunting) {
        return (
            <form
                onSubmit={simpan}
                onKeyDown={(e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        batal();
                    }
                }}
                noValidate
                className="ml-10 space-y-2 pb-2"
            >
                <Label htmlFor={idIsian}>
                    Catatan<span className="sr-only"> untuk {label}</span>
                </Label>
                <TextInput
                    id={idIsian}
                    name="catatan"
                    autoComplete="off"
                    value={form.data.catatan}
                    onChange={(e) => form.setData('catatan', e.target.value)}
                    aria-invalid={form.errors.catatan ? true : undefined}
                    aria-describedby={form.errors.catatan ? `${idIsian}-hint ${idIsian}-galat` : `${idIsian}-hint`}
                />
                <p id={`${idIsian}-hint`} className={kelas('text-meta', panjang > catatanButir.batas ? 'font-bold text-danger-900' : 'text-secondary')}>
                    Misalnya siapa yang membawanya. {panjang} dari {catatanButir.batas} karakter.{catatan !== null ? ' Kosongkan lalu simpan untuk menghapus catatan.' : ''}
                </p>
                <InputError id={`${idIsian}-galat`} messages={form.errors.catatan} />
                <div className="flex flex-wrap gap-2">
                    <Button type="submit" size="sm" processing={form.processing}>
                        Simpan catatan
                    </Button>
                    <Button variant="ghost" size="sm" onClick={batal}>
                        Batal
                    </Button>
                </div>
            </form>
        );
    }

    // -mt-2 menarik baris ini ke bantalan bawah kotak centang di atasnya; target tombolnya tetap
    // 44 px.
    return (
        <div className="-mt-2 ml-10 flex flex-wrap items-center gap-x-2">
            {catatan !== null ? (
                <p className="min-w-0 py-1 text-kecil text-primary [overflow-wrap:anywhere]">
                    <span className="font-semibold">Catatan:</span> {catatan}
                </p>
            ) : null}
            {dapatDicatat ? (
                <button
                    id={idPemicu}
                    type="button"
                    onClick={mulai}
                    className="-ml-1 inline-flex min-h-11 min-w-11 items-center px-1 text-meta font-semibold text-secondary underline underline-offset-2 hover:text-primary focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    {catatan !== null ? 'Ubah' : 'Tambah catatan'}
                    <span className="sr-only">{catatan !== null ? ` catatan: ${label}` : `: ${label}`}</span>
                </button>
            ) : null}
        </div>
    );
}

