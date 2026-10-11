import { router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { TautanMenu, tombolMenu } from '@/layouts/NavLinks';
import type { PropsBersama } from '@/types';

/**
 * Pintu konsol sesuai peran dan Keluar (spec v3 bagian 6.15). Kerangka W2 melepas menu
 * hamburger ponsel, dan di ponsel avatar langsung membuka Profil, jadi Moderasi, Admin,
 * dan Keluar tinggal di sini; di layar lebar menu avatar menyediakan hal yang sama.
 * Peran hanya menentukan apa yang ditampilkan: middleware role dan Policy tetap menolak
 * di server.
 */
export function PintuAkun() {
    const { auth } = usePage<PropsBersama>().props;
    const pengguna = auth.user;

    if (!pengguna) {
        return null;
    }

    return (
        <Card title="Akun">
            <div className="-mx-2 flex flex-col">
                {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => router.post('/logout')} className={tombolMenu}>
                    Keluar
                </button>
            </div>
        </Card>
    );
}
