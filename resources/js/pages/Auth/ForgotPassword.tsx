import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { judulTamu, tautanTeks } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { Tautan } from '@/components/ui/Tautan';
import { GuestLayout } from '@/layouts/GuestLayout';
import { login } from '@/routes';
import { email } from '@/routes/password';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
}

export default function ForgotPassword() {
    const { status } = usePage<Props>().props;
    const form = useForm({ email: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(email.url(), { onError: (g) => fokusGalat(g, ['email']), onSuccess: () => form.reset('email') });
    };

    return (
        <>
            <h1 className={judulTamu}>Lupa kata sandi?</h1>
            <p className="mt-2 text-sm text-secondary">
                Tidak masalah. Beri tahu alamat email Anda, dan kami kirimkan tautan untuk memilih kata sandi baru.
            </p>

            {status ? <p role="status" className="mt-4 text-sm font-medium text-brand-ink">{status}</p> : null}

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

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <Tautan href={login.url()} className={tautanTeks}>Masuk</Tautan>
                    <Button type="submit" processing={form.processing}>Kirim Tautan Atur Ulang Kata Sandi</Button>
                </div>
            </form>
        </>
    );
}

ForgotPassword.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
