import { router } from '@inertiajs/react';
import { useRef, useState, type ChangeEvent } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { InputError } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { destroy as hapusFoto, store as simpanFoto } from '@/routes/profile/photo';
import type { IdentitasProfilData, KabarProfil } from './types';

/**
 * Identitas di kepala Profil (prototype 14): foto unggahan atau inisial, nama, tingkat yang
 * diisi sendiri, dan bulan bergabung. Foto dipilih lewat isian berkas asli yang tersembunyi di
 * balik labelnya, jadi papan ketik dan pembaca layar memakai kendali bawaan peramban; fokusnya
 * tampil di label. Server membuang metadata lokasi dan mengecilkan fotonya; galat dan kabarnya
 * dibacakan di wilayah status bagian ini.
 */
export function IdentitasProfil({ identitas, kabar }: { identitas: IdentitasProfilData; kabar: KabarProfil | null }) {
    const masukan = useRef<HTMLInputElement>(null);
    const [mengirim, setMengirim] = useState<'unggah' | 'hapus' | null>(null);
    const [galat, setGalat] = useState<string | null>(null);

    const unggah = (e: ChangeEvent<HTMLInputElement>) => {
        const berkas = e.target.files?.[0];

        if (!berkas) {
            return;
        }

        setGalat(null);
        setMengirim('unggah');
        router.post(
            simpanFoto.url(),
            { foto: berkas },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (g) => setGalat(g.foto ?? 'Foto tidak dapat disimpan.'),
                onFinish: () => {
                    setMengirim(null);

                    if (masukan.current) {
                        masukan.current.value = '';
                    }
                },
            },
        );
    };

    const hapus = () => {
        setGalat(null);
        setMengirim('hapus');
        router.delete(hapusFoto.url(), { preserveScroll: true, onFinish: () => setMengirim(null) });
    };

    const status = mengirim === 'unggah' ? 'Mengunggah foto.' : mengirim === 'hapus' ? 'Menghapus foto.' : kabar?.bagian === 'foto' ? kabar.teks : '';

    return (
        <section aria-label="Identitas" className="flex flex-wrap items-start gap-4">
            <Avatar nama={identitas.nama} foto={identitas.foto} ukuran={64} />
            <div className="min-w-0 flex-1">
                <p className="break-words text-judul-kartu text-primary">{identitas.nama}</p>
                <p className="mt-0.5 text-kecil text-secondary">{identitas.tingkat ?? 'Tingkat belum diisi'}</p>
                <p className="text-meta text-muted">{identitas.bergabung}</p>
                <div className="mt-2 flex flex-wrap items-center gap-x-2">
                    <input
                        ref={masukan}
                        id="foto-profil"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={unggah}
                        disabled={mengirim !== null}
                        aria-describedby={galat ? 'foto-profil-hint foto-profil-galat' : 'foto-profil-hint'}
                        className="peer sr-only"
                    />
                    <label
                        htmlFor="foto-profil"
                        className={kelas(
                            '-ml-1 inline-flex min-h-11 cursor-pointer items-center px-1 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900',
                            'peer-focus-visible:rounded-control peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600',
                        )}
                    >
                        {identitas.foto ? 'Ganti foto' : 'Pasang foto'}
                    </label>
                    {identitas.foto ? (
                        <Button variant="ghost" size="sm" className="px-2" processing={mengirim === 'hapus'} onClick={hapus}>
                            Hapus foto
                        </Button>
                    ) : null}
                </div>
                <p id="foto-profil-hint" className="text-meta text-muted">
                    JPG, PNG, atau WebP, paling besar 2 MB. Metadata lokasi di foto dibuang.
                </p>
                <InputError id="foto-profil-galat" messages={galat} className="mt-1" />
                <p role="status" className="mt-1 text-kecil text-secondary empty:!mt-0">
                    {status}
                </p>
            </div>
        </section>
    );
}

