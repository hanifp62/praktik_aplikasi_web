import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianKataSandi } from '@/components/akun/IsianKataSandi';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { judulTamu } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { GuestLayout } from '@/layouts/GuestLayout';
import { store } from '@/routes/password';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    token: string;
    email: string;
    petunjukSandi: string;
}

/** Token dan email datang dari tautan di email; tokennya ikut dikirim, tidak ditampilkan. */
export default function ResetPassword() {
    const { token, email, petunjukSandi } = usePage<Props>().props;
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), {
            onError: (g) => fokusGalat(g, ['email', 'password', 'password_confirmation']),
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <h1 className={judulTamu}>Buat kata sandi baru</h1>

            <form onSubmit={kirim} className="mt-4 space-y-4" noValidate>
                <FormField
                    name="email"
                    label="Email"
                    type="email"
                    required
                    autoFocus
                    autoComplete="username"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={form.errors.email}
                />

                <IsianKataSandi
                    id="password"
                    name="password"
                    label="Kata Sandi"
                    hint={petunjukSandi}
                    required
                    autoComplete="new-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <IsianKataSandi
                    id="password_confirmation"
                    name="password_confirmation"
                    label="Konfirmasi Kata Sandi"
                    required
                    autoComplete="new-password"
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    error={form.errors.password_confirmation}
                />

                <div className="flex justify-end">
                    <Button type="submit" processing={form.processing}>Atur Ulang Kata Sandi</Button>
                </div>
            </form>
        </>
    );
}

ResetPassword.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
