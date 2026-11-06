import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { onboarding } from '@/routes';
import type { ProfilPendakian } from './types';

/**
 * Ringkasan isian profil pendakian, dapat dilihat kapan saja, dengan jalan ke formulir
 * pengisiannya. Isian yang kosong disebut belum diisi, bukan disembunyikan atau ditebak.
 */
export function RingkasanProfilPendakian({ profil }: { profil: ProfilPendakian }) {
    return (
        <Card title="Profil pendakian" subtitle="Dipakai untuk menilai kecocokan jalur dengan pengalaman dan rencana Anda.">
            <dl className="max-w-xl divide-y divide-subtle border-y border-subtle">
                {profil.rincian.map((r) => (
                    <div key={r.label} className="flex flex-wrap justify-between gap-x-4 gap-y-1 py-2 text-sm">
                        <dt className="text-secondary">{r.label}</dt>
                        <dd className={r.nilai ? 'font-medium text-primary' : 'text-muted'}>{r.nilai ?? 'Belum diisi'}</dd>
                    </div>
                ))}
            </dl>
            <div className="mt-4">
                <Button variant={profil.lengkap ? 'secondary' : 'primary'} href={onboarding.url()}>
                    {profil.lengkap ? 'Ubah profil pendakian' : 'Isi profil sekarang'}
                </Button>
            </div>
        </Card>
    );
}
