import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { destroy as hapusBarang, store as simpanBarang, update as ubahBarang } from '@/routes/profile/items';
import type { KabarProfil, PerlengkapanProfil } from './types';

const chip =
    'inline-flex min-h-11 max-w-full items-center gap-1.5 rounded-full border px-4 text-kecil font-semibold focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2';

/**
 * Perlengkapan pribadi (spec v3 bagian 6.15; ruling W7-R8): barang milik pendaki sebagai chip,
 * masing-masing membuka lembar ubah, dan "Tambah barang". Daftar ini mengisi awal daftar
 * persiapan setiap trip baru sebagai butir yang tetap dikonfirmasi per trip; mengubahnya tidak
 * mengubah daftar trip yang sudah ada. Kabar tulisan dibacakan di wilayah status bagian ini.
 */
export function PerlengkapanPribadi({ perlengkapan, kabar }: { perlengkapan: PerlengkapanProfil; kabar: KabarProfil | null }) {
    const [sasaran, setSasaran] = useState<{ id: number | null; nama: string } | null>(null);
    const [menghapus, setMenghapus] = useState(false);
    const form = useForm({ nama: '' });
    const pemicu = useRef<HTMLElement | null>(null);
    const penuh = perlengkapan.daftar.length >= perlengkapan.batas;

    const buka = (barang: { id: number | null; nama: string }, tombol: HTMLElement) => {
        pemicu.current = tombol;
        form.setData('nama', barang.nama);
        form.clearErrors();
        setSasaran(barang);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();

        if (sasaran === null) {
            return;
        }

        const selesai = { preserveScroll: true, onSuccess: () => setSasaran(null), onError: () => document.getElementById('barang-nama')?.focus() };

        if (sasaran.id === null) {
            form.post(simpanBarang.url(), selesai);
        } else {
            form.put(ubahBarang.url(sasaran.id), selesai);
        }
    };

    const hapus = () => {
        if (sasaran?.id == null) {
            return;
        }

        setMenghapus(true);
        router.delete(hapusBarang.url(sasaran.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Chip yang dihapus sudah hilang: fokus kembali ke "Tambah barang", atau ke judul
                // bagian bila tombol itu tidak ada, bukan jatuh ke body.
                pemicu.current = document.getElementById('tambah-barang') ?? document.getElementById('judul-perlengkapan');
                setSasaran(null);
            },
            onFinish: () => setMenghapus(false),
        });
    };

    return (
        <section aria-labelledby="judul-perlengkapan" className="rounded-lg border border-subtle bg-surface p-5">
            <h2 id="judul-perlengkapan" tabIndex={-1} className="text-bagian text-primary focus:outline-hidden">
                Perlengkapan pribadi
            </h2>
            <p className="mt-1 text-kecil text-secondary">Dipakai untuk mengisi awal daftar persiapan setiap trip baru. Tetap Anda konfirmasi di setiap trip.</p>

            {perlengkapan.daftar.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Belum ada barang. Barang yang Anda tambahkan masuk ke daftar persiapan trip berikutnya, belum dikonfirmasi.</p>
            ) : (
                <ul className="mt-3 flex flex-wrap gap-2">
                    {perlengkapan.daftar.map((b) => (
                        <li key={b.id} className="max-w-full">
                            <button type="button" onClick={(e) => buka(b, e.currentTarget)} className={kelas(chip, 'border-control bg-surface text-primary hover:bg-surface-sunken')}>
                                <span className="min-w-0 break-words">{b.nama}</span>
                                <span className="sr-only">, ubah</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {penuh ? (
                <p className="mt-3 text-kecil text-secondary">Daftar sudah berisi {perlengkapan.batas} barang, batasnya. Hapus yang tidak lagi Anda bawa untuk menambah yang baru.</p>
            ) : (
                <button
                    id="tambah-barang"
                    type="button"
                    onClick={(e) => buka({ id: null, nama: '' }, e.currentTarget)}
                    className={kelas(chip, 'mt-3 border-dashed border-brand-700 font-bold text-brand-ink hover:bg-brand-50')}
                >
                    <Icon name="plus" size={16} />
                    Tambah barang
                </button>
            )}

            <p role="status" className="mt-2 text-kecil text-secondary empty:!mt-0">
                {kabar?.bagian === 'perlengkapan' ? kabar.teks : ''}
            </p>

            <LembarBawah
                terbuka={sasaran !== null}
                onTutup={() => setSasaran(null)}
                judul={sasaran?.id == null ? 'Tambah barang' : 'Ubah barang'}
                keterangan="Nama barang seperti yang Anda sebut sehari-hari, misalnya Headlamp atau Jas hujan."
                pemicu={pemicu}
                aksi={
                    <>
                        {sasaran?.id != null ? (
                            <Button variant="ghost" className="mr-auto text-danger-900" processing={menghapus} onClick={hapus}>
                                Hapus dari daftar
                            </Button>
                        ) : null}
                        <Button type="submit" form="formulir-barang" processing={form.processing}>
                            {sasaran?.id == null ? 'Tambah' : 'Simpan'}
                        </Button>
                    </>
                }
            >
                <form id="formulir-barang" onSubmit={kirim} noValidate>
                    <Label htmlFor="barang-nama">Nama barang</Label>
                    <TextInput
                        id="barang-nama"
                        value={form.data.nama}
                        onChange={(e) => form.setData('nama', e.target.value)}
                        maxLength={60}
                        autoComplete="off"
                        aria-invalid={form.errors.nama ? true : undefined}
                        aria-describedby={form.errors.nama ? 'barang-nama-galat' : undefined}
                    />
                    <InputError id="barang-nama-galat" messages={form.errors.nama} className="mt-2" />
                    {sasaran?.id != null ? <p className="mt-2 text-kecil text-secondary">Daftar persiapan trip yang sudah ada tidak berubah.</p> : null}
                </form>
            </LembarBawah>
        </section>
    );
}

