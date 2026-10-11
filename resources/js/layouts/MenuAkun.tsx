import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import { keluar, keluarBerjalan, useKeadaanKeluar } from '@/luring/keluar';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';
import { TautanMenu, tombolMenu } from './NavLinks';

/**
 * Menu avatar layar lebar (spec v3 bagian 5.2): Profil; Moderasi dan Admin sesuai peran;
 * Keluar. Popover asli (popover="auto"): peramban menutupnya dengan Escape dan dengan klik
 * di luar, mengembalikan fokus ke avatar, dan menaruhnya di top layer sehingga tidak
 * terpotong wadah mana pun. Sengaja tanpa peran ARIA menu: isinya tautan dan satu tombol
 * yang dijelajahi dengan Tab, bukan menu aplikasi yang dijelajahi dengan tombol panah.
 *
 * Elemen popover sendiri tidak diberi kelas display. Aturan peramban yang menyembunyikan
 * popover tertutup berasal dari lembar gaya peramban, dan kelas penulis seperti flex selalu
 * mengalahkannya, sehingga menu akan tampil terus. Tata letak isinya ada di anak.
 *
 * Keluar lewat keluar() (ruling W6-R10): data perangkat dibersihkan dulu, lalu sesi diakhiri.
 * Selama itu tombolnya aria-busy dan menu tetap terbuka, supaya kalimat kegagalan di wilayah status
 * di bawah tombol terbaca dan fokus tetap di tombol (W6-11c). Wilayah itu hanya memuat kalimat dari
 * keluar yang dimulai di menu ini, bukan dari kartu Akun di Profil (tinjauan Tugas 11 m-4).
 */
export function MenuAkun() {
    const { auth, perangkat } = usePage<PropsBersama>().props;
    const menu = useRef<HTMLDivElement>(null);
    const keadaanKeluar = useKeadaanKeluar('menu');
    const pengguna = auth.user;

    // Kunjungan Inertia tidak memuat ulang dokumen, jadi popover yang terbuka akan tetap
    // terbuka di halaman tujuan. Ia ditutup begitu kunjungan dimulai, kecuali selama keluar berjalan:
    // menutupnya memindah fokus ke avatar dan menyembunyikan kalimat bila keluar gagal, juga bila
    // tautan di menu ini membatalkan permintaan keluar (kalimat "halaman berpindah", m-3).
    useEffect(
        () =>
            router.on('start', () => {
                if (menu.current?.matches(':popover-open') && !keluarBerjalan()) {
                    menu.current.hidePopover();
                }
            }),
        [],
    );

    if (!pengguna) {
        return null;
    }

    return (
        <>
            <button type="button" popoverTarget="menu-akun" className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan)}>
                <Avatar nama={pengguna.name} ukuran={44} />
                <span className="sr-only">Menu akun {pengguna.name}</span>
            </button>
            <div ref={menu} id="menu-akun" popover="auto" className="inset-auto right-6 top-[4.5rem] m-0 w-72 rounded-kartu border border-subtle bg-surface p-2 text-primary shadow-overlay">
                <div className="flex flex-col">
                    <p className="px-3 pb-2 pt-1">
                        <span className="block font-bold">{pengguna.name}</span>
                        <span className="block break-all text-kecil text-secondary">{pengguna.email}</span>
                    </p>
                    <div className="flex flex-col border-t border-subtle pt-1">
                        <TautanMenu href={profile.url()}>Profil</TautanMenu>
                        {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null, 'menu')} aria-busy={keadaanKeluar.sibuk} className={tombolMenu}>
                            Keluar
                        </button>
                        <p role="status" className={kelas('px-3 text-kecil text-primary', keadaanKeluar.pesan ? 'pb-2 pt-1' : '')}>{keadaanKeluar.pesan}</p>
                    </div>
                </div>
            </div>
        </>
    );
}
