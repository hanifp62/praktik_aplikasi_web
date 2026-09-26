import { router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { judulTamu, tautanTeks } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { GuestLayout } from '@/layouts/GuestLayout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
}

export default function VerifyEmail() {
    const { status } = usePage<Props>().props;
    const form = useForm({});

    const kirimUlang = (e: FormEvent) => {
        e.preventDefault();
        form.post(send.url());
    };

    return (
        <>
            <h1 className={judulTamu}>Verifikasi email</h1>
            <p className="mt-2 text-sm text-secondary">
                Terima kasih sudah mendaftar. Sebelum mulai, buka tautan verifikasi yang baru kami kirim ke email Anda. Bila email itu
                belum sampai, kami dapat mengirimkannya lagi.
            </p>

            <p role="status" className="mt-4 text-sm font-medium text-brand-ink">
                {status === 'verification-link-sent'
                    ? 'Tautan verifikasi baru sudah dikirim ke alamat email yang Anda masukkan saat mendaftar.'
                    : ''}
            </p>

            <form onSubmit={kirimUlang} className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <Button type="submit" processing={form.processing}>Kirim Ulang Email Verifikasi</Button>
                <button type="button" onClick={() => router.post(logout.url())} className={tautanTeks}>Keluar</button>
            </form>
        </>
    );
}

VerifyEmail.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
