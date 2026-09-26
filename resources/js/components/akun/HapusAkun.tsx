import { useForm } from '@inertiajs/react';
import { useRef, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { destroy } from '@/routes/profile';
import { IsianKataSandi } from './IsianKataSandi';

/**
 * Hapus akun dengan konfirmasi kata sandi di dalam <dialog> bawaan peramban: fokus
 * terkurung di dalamnya dan Esc menutupnya tanpa pustaka. Galat kata sandi membuat
 * dialog tetap terbuka, sama seperti modal Blade sebelumnya.
 */
export function HapusAkun() {
    const dialog = useRef<HTMLDialogElement>(null);
    const form = useForm({ password: '' });

    const buka = () => {
        dialog.current?.showModal();
        document.getElementById('hapus_password')?.focus();
    };

    const tutup = () => dialog.current?.close();

    const saatDitutup = () => {
        form.reset();
        form.clearErrors();
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.delete(destroy.url(), {
            preserveScroll: true,
            onError: () => document.getElementById('hapus_password')?.focus(),
            onFinish: () => form.reset(),
        });
    };

    return (
        <Card
            title="Hapus Akun"
            subtitle="Setelah akun dihapus, profil, rencana, dan trip Anda hilang permanen. Laporan kondisi jalur yang sudah disetujui tetap disimpan tanpa nama Anda, karena pendaki lain masih memakainya. Unduh dahulu data yang ingin Anda simpan."
        >
            <Button variant="danger" onClick={buka}>Hapus Akun</Button>

            <dialog
                ref={dialog}
                onClose={saatDitutup}
                aria-labelledby="hapus-akun-judul"
                className="w-[calc(100%-2rem)] max-w-lg rounded-lg border border-subtle bg-surface p-0 text-primary shadow-overlay backdrop:bg-muted backdrop:opacity-75"
            >
                <form onSubmit={kirim} className="p-6" noValidate>
                    <h2 id="hapus-akun-judul" className="text-lg font-medium text-primary">Yakin ingin menghapus akun Anda?</h2>
                    <p className="mt-1 text-sm text-secondary">
                        Setelah akun dihapus, profil, rencana, dan trip Anda hilang permanen dan tidak dapat dikembalikan. Masukkan kata sandi Anda untuk memastikan.
                    </p>

                    <IsianKataSandi
                        id="hapus_password"
                        name="password"
                        label="Kata Sandi"
                        labelTersembunyi
                        placeholder="Kata Sandi"
                        autoComplete="current-password"
                        className="mt-6"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        error={form.errors.password}
                    />

                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <Button variant="secondary" onClick={tutup}>Batal</Button>
                        <Button type="submit" variant="danger" processing={form.processing}>Hapus Akun</Button>
                    </div>
                </form>
            </dialog>
        </Card>
    );
}
