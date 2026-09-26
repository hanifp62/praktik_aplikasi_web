import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Checkbox } from '@/components/ui/Field';
import { preference } from '@/routes/profile';

/**
 * Sakelar perekaman jejak. Persetujuan tanpa keterangan bukan persetujuan: keempat
 * kalimat menyebut apa yang direkam, di mana ia berada selama pendakian, kapan ia
 * berpindah ke server, bahwa ia dapat ditarik kembali, dan biayanya pada baterai.
 * Semuanya dibaca sebelum sakelarnya disentuh, bukan sesudah.
 */
export function FormRekamJejak({ rekamJejak, pesan }: { rekamJejak: boolean; pesan: string | null }) {
    const form = useForm({ record_track: rekamJejak });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(preference.url(), { preserveScroll: true });
    };

    return (
        <Card
            title="Perekaman jejak pendakian"
            subtitle="Aplikasi dapat merekam jejak perjalanan Anda selama mendaki, lalu menggambarnya di halaman hasil pendakian."
        >
            <ul className="max-w-xl space-y-2 text-sm text-secondary">
                <li className="flex gap-2">
                    <span aria-hidden="true">&middot;</span>
                    <span>Selama mendaki, titik-titiknya disimpan <strong>hanya di perangkat Anda</strong>. Tidak ada yang dikirim saat Anda masih di jalur.</span>
                </li>
                <li className="flex gap-2">
                    <span aria-hidden="true">&middot;</span>
                    <span>Jejaknya dikirim <strong>setelah Anda turun</strong> dan mendapat sinyal kembali.</span>
                </li>
                <li className="flex gap-2">
                    <span aria-hidden="true">&middot;</span>
                    <span>Jejak setiap pendakian <strong>dapat dihapus</strong> kapan saja dari halaman hasil pendakiannya, tanpa menghapus pendakiannya.</span>
                </li>
                <li className="flex gap-2">
                    <span aria-hidden="true">&middot;</span>
                    <span>Merekam posisi terus-menerus memakai baterai lebih cepat dari biasanya.</span>
                </li>
            </ul>

            <form onSubmit={kirim} className="mt-5 space-y-4">
                <label htmlFor="record_track" className="flex min-h-11 items-start gap-3">
                    <Checkbox
                        id="record_track"
                        name="record_track"
                        checked={form.data.record_track}
                        onChange={(e) => form.setData('record_track', e.target.checked)}
                        className="mt-0.5 h-5 w-5"
                    />
                    <span className="text-sm text-primary">Rekam jejak pendakian saya</span>
                </label>

                <div className="flex items-center gap-4">
                    <Button type="submit" processing={form.processing}>Simpan</Button>
                    <p role="status" className="text-sm text-secondary">{pesan ?? ''}</p>
                </div>
            </form>
        </Card>
    );
}
