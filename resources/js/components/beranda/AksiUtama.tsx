import { router } from '@inertiajs/react';
import { useId, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import type { AksiFase } from './types';

/**
 * Satu aksi utama fase Beranda (spec v3 bagian 6.1), selebar kartu di ponsel. Jenisnya dari
 * server: tautan di aplikasi, tautan ke sistem resmi di situs lain (dengan ikon tautan keluar,
 * DESIGN.md bagian Ikon), atau sakelar pengingat trip yang dikirim lewat rute yang sama dengan
 * kartu pengingat ruang kerja. Keterangannya terikat ke tombol lewat aria-describedby.
 *
 * Kabar "(membuka tab baru)" untuk pembaca layar datang dari cabang external Button, jadi tidak
 * ditulis lagi di sini: nama aksesibelnya "Pesan di sistem resmi (membuka tab baru)", setara
 * tautan resmi Tenggat terdekat (ruling W5-FR).
 */
export function AksiUtama({ aksi }: { aksi: AksiFase }) {
    const idKeterangan = useId();
    const [memproses, setMemproses] = useState(false);
    const keterangan = aksi.keterangan ? idKeterangan : undefined;

    const nyalakanPengingat = () => {
        router.put(aksi.href, { aktif: true }, { onStart: () => setMemproses(true), onFinish: () => setMemproses(false) });
    };

    return (
        <div className="mt-5">
            {aksi.jenis === 'pengingat' ? (
                <Button className="w-full sm:w-auto" processing={memproses} aria-describedby={keterangan} onClick={nyalakanPengingat}>
                    {aksi.label}
                </Button>
            ) : (
                <Button href={aksi.href} external={aksi.jenis === 'luar'} className="w-full sm:w-auto" aria-describedby={keterangan}>
                    {aksi.label}
                    {aksi.jenis === 'luar' ? <Icon name="arrow-square-out" size={20} /> : null}
                </Button>
            )}
            {aksi.keterangan ? (
                <p id={idKeterangan} className="mt-2 text-meta text-secondary">
                    {aksi.keterangan}
                </p>
            ) : null}
        </div>
    );
}
