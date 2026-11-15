import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FormInformasiProfil } from '@/components/akun/FormInformasiProfil';
import { FormKataSandi } from '@/components/akun/FormKataSandi';
import { FormRekamJejak } from '@/components/akun/FormRekamJejak';
import { HapusAkun } from '@/components/akun/HapusAkun';
import { PintuAkun } from '@/components/akun/PintuAkun';
import { BagianRincian } from '@/components/profil/BagianRincian';
import { DataLokasi } from '@/components/profil/DataLokasi';
import { IdentitasProfil } from '@/components/profil/IdentitasProfil';
import { PerlengkapanPribadi } from '@/components/profil/PerlengkapanPribadi';
import { TampilanProfil } from '@/components/profil/TampilanProfil';
import type { IdentitasProfilData, KabarProfil, PengalamanProfil, PerlengkapanProfil, PreferensiProfil } from '@/components/profil/types';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
    perluVerifikasi: boolean;
    rekamJejak: boolean;
    pesanJejak: string | null;
    petunjukSandi: string;
    identitas: IdentitasProfilData;
    pengalaman: PengalamanProfil;
    preferensi: PreferensiProfil;
    perlengkapan: PerlengkapanProfil;
    kabar: KabarProfil | null;
}

/**
 * Profil (spec v3 bagian 6.15, prototype 14). Urutannya dari yang membentuk penilaian ke yang
 * jarang disentuh: identitas, pengalaman dan preferensi yang diisi sendiri, perlengkapan
 * pribadi, tampilan, privasi (perekaman jejak, lokasi, hapus jejak), akun (nama, email, kata
 * sandi), pintu konsol dan Keluar, lalu penghapusan akun terakhir, terpisah dari yang lain.
 * Pilihan bahasa tidak ditampilkan sampai terjemahan Inggris ada (DK-40).
 */
export default function Edit() {
    const { status, perluVerifikasi, rekamJejak, pesanJejak, petunjukSandi, identitas, pengalaman, preferensi, perlengkapan, kabar } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-6 px-4 sm:px-6">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Profil" />

                <IdentitasProfil identitas={identitas} kabar={kabar} />

                <BagianRincian id="judul-pengalaman" judul="Pengalaman" rincian={pengalaman.rincian} ubah={pengalaman.lengkap ? pengalaman.ubah : null}>
                    <p className="mt-3 text-kecil text-secondary">
                        Ini masukan, bukan sertifikasi. Riwayat dipakai sebagai konteks penilaian, tetapi tingkat Anda tidak naik otomatis. Ubah sendiri bila menurut Anda sudah berubah.
                    </p>
                    {pengalaman.lengkap ? null : (
                        <Button href={pengalaman.ubah} className="mt-3">
                            Isi profil pendaki
                        </Button>
                    )}
                </BagianRincian>

                <BagianRincian id="judul-preferensi" judul="Preferensi" rincian={preferensi.rincian} ubah={pengalaman.lengkap ? preferensi.ubah : null} />

                <PerlengkapanPribadi perlengkapan={perlengkapan} kabar={kabar} />

                <TampilanProfil />

                <FormRekamJejak rekamJejak={rekamJejak} pesan={pesanJejak} />
                <DataLokasi kabar={kabar} />

                <FormInformasiProfil perluVerifikasi={perluVerifikasi} status={status} />
                <FormKataSandi petunjukSandi={petunjukSandi} />

                <PintuAkun />

                <div className="border-t border-subtle pt-6">
                    <HapusAkun />
                </div>
            </div>
        </div>
    );
}

Edit.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
