import { usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { TautanMenu, tombolMenu } from '@/layouts/NavLinks';
import { kelas } from '@/lib/kelas';
import { keluar, useKeadaanKeluar } from '@/luring/keluar';
import type { PropsBersama } from '@/types';

/**
 * Pintu konsol sesuai peran dan Keluar (spec v3 bagian 6.15). Kerangka W2 melepas menu
 * hamburger ponsel, dan di ponsel avatar langsung membuka Profil, jadi Moderasi, Admin,
 * dan Keluar tinggal di sini; di layar lebar menu avatar menyediakan hal yang sama.
 * Peran hanya menentukan apa yang ditampilkan: middleware role dan Policy tetap menolak
 * di server.
 *
 * Keluar lewat keluar() (ruling W6-R10): data perangkat dibersihkan dulu, lalu sesi diakhiri.
 * Selama itu tombolnya aria-busy; kalimat kegagalan ditulis di wilayah status yang selalu
 * terpasang di bawahnya, dan fokus tetap di tombol (W6-11c). Wilayah itu hanya memuat kalimat dari
 * keluar yang dimulai di kartu ini, bukan dari menu avatar (tinjauan Tugas 11 m-4).
 */
export function PintuAkun() {
    const { auth, perangkat } = usePage<PropsBersama>().props;
    const pengguna = auth.user;
    const keadaanKeluar = useKeadaanKeluar('kartu');

    if (!pengguna) {
        return null;
    }

    return (
        <Card title="Akun">
            <div className="-mx-2 flex flex-col">
                {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null, 'kartu')} aria-busy={keadaanKeluar.sibuk} className={tombolMenu}>
                    Keluar
                </button>
                <p role="status" className={kelas('px-3 text-kecil text-primary', keadaanKeluar.pesan ? 'pt-2' : '')}>{keadaanKeluar.pesan}</p>
            </div>
        </Card>
    );
}
