import { usePage } from '@inertiajs/react';
import { kelas } from '@/lib/kelas';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';
import { Avatar } from './Avatar';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Tautan } from './Tautan';

/**
 * Avatar 44 px menuju Profil (spec v3 bagian 5.1, ruling W2-R1): Profil dibuka dari avatar
 * di setiap akar tab, bukan dari tab keenam. Avatar sendiri hiasan; nama tautannya dibawa
 * teks pembaca layar.
 */
export function TautanProfil({ className }: { className?: string }) {
    const { auth } = usePage<PropsBersama>().props;

    if (!auth.user) {
        return null;
    }

    return (
        <Tautan href={profile.url()} className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan, className)}>
            <Avatar nama={auth.user.name} ukuran={44} />
            <span className="sr-only">Profil {auth.user.name}</span>
        </Tautan>
    );
}
