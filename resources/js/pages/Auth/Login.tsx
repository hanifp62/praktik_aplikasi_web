import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianKataSandi } from '@/components/akun/IsianKataSandi';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { judulTamu, tautanTeks } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { Checkbox, FormField } from '@/components/ui/Field';
import { Tautan } from '@/components/ui/Tautan';
import { GuestLayout } from '@/layouts/GuestLayout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
}

/**
 * Masuk dengan email dan kata sandi. Galat gagal masuk dan batas percobaan datang dari
 * server di kunci `email`, dengan satu pesan untuk semua sebab supaya tidak membocorkan
 * akun mana yang terdaftar.
 */
export default function Login() {
    const { status } = usePage<Props>().props;
    const form = useForm({ email: '', password: '', remember: false });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), { onError: (g) => fokusGalat(g, ['email', 'password']), onFinish: () => form.reset('password') });
    };

    return (
        <>
            <h1 className={judulTamu}>Masuk</h1>

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

                <IsianKataSandi
                    id="password"
                    name="password"
                    label="Kata Sandi"
                    required
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <label htmlFor="remember" className="inline-flex min-h-11 items-center gap-2">
                    <Checkbox
                        id="remember"
                        name="remember"
                        checked={form.data.remember}
                        onChange={(e) => form.setData('remember', e.target.checked)}
                    />
                    <span className="text-sm text-secondary">Ingat saya</span>
                </label>

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <Tautan href={request.url()} className={tautanTeks}>Lupa kata sandi?</Tautan>
                    <Button type="submit" processing={form.processing}>Masuk</Button>
                </div>
            </form>

            <p className="mt-4 border-t border-subtle pt-2 text-sm text-secondary">
                Belum punya akun? <Tautan href={register.url()} className={tautanTeks}>Daftar</Tautan>
            </p>
        </>
    );
}

Login.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
