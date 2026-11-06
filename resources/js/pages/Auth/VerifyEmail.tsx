import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { judulTamu, tautanTeks } from '@/components/akun/gaya';
import { Button } from '@/components/ui/Button';
import { GuestLayout } from '@/layouts/GuestLayout';
import { keluar, useKeadaanKeluar } from '@/luring/keluar';
import { send } from '@/routes/verification';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    status: string | null;
}

/**
 * Verifikasi surel. Keluar dari sini juga lewat keluar() (PT11-12): pertanyaan untuk data yang
 * belum terkirim dan pembersihan data perangkat sama dengan pintu Keluar lain, dan kalimat
 * kegagalannya ditulis di wilayah status di bawah tombol.
 */
export default function VerifyEmail() {
    const { status, perangkat } = usePage<Props>().props;
    const form = useForm({});
    const keadaanKeluar = useKeadaanKeluar('verifikasi');

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
                <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null, 'verifikasi')} aria-busy={keadaanKeluar.sibuk} className={tautanTeks}>Keluar</button>
            </form>
            <p role="status" className="mt-2 text-sm text-primary">{keadaanKeluar.pesan}</p>
        </>
    );
}

VerifyEmail.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
