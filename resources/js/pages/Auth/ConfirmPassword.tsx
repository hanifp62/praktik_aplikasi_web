import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianKataSandi } from '@/components/akun/IsianKataSandi';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { judulTamu } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { GuestLayout } from '@/layouts/GuestLayout';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), { onError: (g) => fokusGalat(g, ['password']), onFinish: () => form.reset('password') });
    };

    return (
        <>
            <h1 className={judulTamu}>Konfirmasi kata sandi</h1>
            <p className="mt-2 text-sm text-secondary">Ini area aman aplikasi. Konfirmasi kata sandi Anda sebelum melanjutkan.</p>

            <form onSubmit={kirim} className="mt-4 space-y-4" noValidate>
                <IsianKataSandi
                    id="password"
                    name="password"
                    label="Kata Sandi"
                    required
                    autoFocus
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <div className="flex justify-end">
                    <Button type="submit" processing={form.processing}>Konfirmasi</Button>
                </div>
            </form>
        </>
    );
}

ConfirmPassword.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
