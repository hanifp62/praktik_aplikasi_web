import { router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/Field';
import { update } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { PropsBersama } from '@/types';
import { fokusGalat } from './fokusGalat';
import { tautanTeks } from './gaya';

/** Nama dan email. Email yang berubah mengosongkan status verifikasinya di server. */
export function FormInformasiProfil({ perluVerifikasi, status }: { perluVerifikasi: boolean; status: string | null }) {
    const { auth } = usePage<PropsBersama>().props;
    const form = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.patch(update.url(), { preserveScroll: true, onError: (g) => fokusGalat(g, ['name', 'email']) });
    };

    return (
        <Card title="Informasi Profil" subtitle="Perbarui nama dan alamat email akun Anda.">
            <form onSubmit={kirim} className="max-w-xl space-y-4" noValidate>
                <FormField
                    name="name"
                    label="Nama"
                    required
                    autoComplete="name"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    error={form.errors.name}
                />

                <div>
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

                    {perluVerifikasi ? (
                        <div className="mt-2 text-sm text-primary">
                            <p>
                                Alamat email Anda belum diverifikasi.{' '}
                                <button type="button" onClick={() => router.post(send.url(), {}, { preserveScroll: true })} className={tautanTeks}>
                                    Klik di sini untuk mengirim ulang email verifikasi.
                                </button>
                            </p>
                            <p role="status" className="mt-2 font-medium text-brand-ink">
                                {status === 'verification-link-sent' ? 'Tautan verifikasi baru sudah dikirim ke alamat email Anda.' : ''}
                            </p>
                        </div>
                    ) : null}
                </div>

                <div className="flex items-center gap-4">
                    <Button type="submit" processing={form.processing}>Simpan</Button>
                    <p role="status" className="text-sm text-secondary">{form.recentlySuccessful ? 'Tersimpan.' : ''}</p>
                </div>
            </form>
        </Card>
    );
}
