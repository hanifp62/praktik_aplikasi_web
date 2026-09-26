import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { IsianKataSandi } from '@/components/akun/IsianKataSandi';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { judulTamu, tautanTeks } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { Tautan } from '@/components/ui/Tautan';
import { GuestLayout } from '@/layouts/GuestLayout';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    petunjukSandi: string;
}

/** Daftar: empat isian, aturan panjang kata sandi disebut sebelum ditolak. */
export default function Register() {
    const { petunjukSandi } = usePage<Props>().props;
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), {
            onError: (g) => fokusGalat(g, ['name', 'email', 'password', 'password_confirmation']),
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <h1 className={judulTamu}>Daftar</h1>

            <form onSubmit={kirim} className="mt-4 space-y-4" noValidate>
                <FormField
                    name="name"
                    label="Nama"
                    required
                    autoFocus
                    autoComplete="name"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    error={form.errors.name}
                />

                <FormField
                    name="email"
                    label="Email"
                    type="email"
                    required
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

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <Tautan href={login.url()} className={tautanTeks}>Sudah punya akun?</Tautan>
                    <Button type="submit" processing={form.processing}>Daftar</Button>
                </div>
            </form>
        </>
    );
}

Register.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
