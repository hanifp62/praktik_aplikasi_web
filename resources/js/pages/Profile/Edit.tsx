import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FormInformasiProfil } from '@/components/akun/FormInformasiProfil';
import { FormKataSandi } from '@/components/akun/FormKataSandi';
import { FormRekamJejak } from '@/components/akun/FormRekamJejak';
import { HapusAkun } from '@/components/akun/HapusAkun';
import { PintuAkun } from '@/components/akun/PintuAkun';
import { RingkasanProfilPendakian } from '@/components/akun/RingkasanProfilPendakian';
import type { ProfilPendakian } from '@/components/akun/types';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
    perluVerifikasi: boolean;
    rekamJejak: boolean;
    pesanJejak: string | null;
    petunjukSandi: string;
    profilPendakian: ProfilPendakian;
}

/**
 * Profil: akun lebih dulu (nama, email, kata sandi), lalu preferensi yang membentuk
 * pengalaman (profil pendakian, perekaman jejak, tampilan), dan penghapusan akun
 * terakhir, terpisah dari yang lain.
 */
export default function Edit() {
    const { status, perluVerifikasi, rekamJejak, pesanJejak, petunjukSandi, profilPendakian } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Profil" />

                <FormInformasiProfil perluVerifikasi={perluVerifikasi} status={status} />
                <FormKataSandi petunjukSandi={petunjukSandi} />

                <RingkasanProfilPendakian profil={profilPendakian} />
                <FormRekamJejak rekamJejak={rekamJejak} pesan={pesanJejak} />

                <Card
                    title="Tampilan"
                    subtitle={'Tema berlaku di peramban ini. "Ikuti sistem" mengikuti setelan terang atau gelap perangkat Anda, termasuk saat ia berganti sendiri.'}
                >
                    <ThemePicker />
                </Card>

                <PintuAkun />

                <div className="border-t border-subtle pt-6">
                    <HapusAkun />
                </div>
            </div>
        </div>
    );
}

Edit.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
