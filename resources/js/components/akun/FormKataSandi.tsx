import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { password } from '@/routes/profile';
import { fokusGalat } from './fokusGalat';
import { IsianKataSandi } from './IsianKataSandi';

/** Ganti kata sandi dengan kata sandi lama. Ketiga isian dikosongkan, berhasil maupun gagal. */
export function FormKataSandi({ petunjukSandi }: { petunjukSandi: string }) {
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(password.url(), {
            preserveScroll: true,
            onError: (g) =>
                fokusGalat(g, [
                    ['current_password', 'update_password_current_password'],
                    ['password', 'update_password_password'],
                    ['password_confirmation', 'update_password_password_confirmation'],
                ]),
            onFinish: () => form.reset(),
        });
    };

    return (
        <Card title="Perbarui Kata Sandi" subtitle="Pastikan akun Anda memakai kata sandi yang panjang dan acak agar tetap aman.">
            <form onSubmit={kirim} className="max-w-xl space-y-4" noValidate>
                <IsianKataSandi
                    id="update_password_current_password"
                    name="current_password"
                    label="Kata Sandi Saat Ini"
                    autoComplete="current-password"
                    value={form.data.current_password}
                    onChange={(e) => form.setData('current_password', e.target.value)}
                    error={form.errors.current_password}
                />

                <IsianKataSandi
                    id="update_password_password"
                    name="password"
                    label="Kata Sandi Baru"
                    hint={petunjukSandi}
                    autoComplete="new-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <IsianKataSandi
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    label="Konfirmasi Kata Sandi"
                    autoComplete="new-password"
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    error={form.errors.password_confirmation}
                />

                <div className="flex items-center gap-4">
                    <Button type="submit" processing={form.processing}>Simpan</Button>
                    <p role="status" className="text-sm text-secondary">{form.recentlySuccessful ? 'Tersimpan.' : ''}</p>
                </div>
            </form>
        </Card>
    );
}
