import { router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { simpan } from '@/routes/tema';
import type { PropsBersama } from '@/types';
import { Button } from './Button';
import { kelas } from '@/lib/kelas';

const pilihan = [
    ['system', 'Ikuti sistem'],
    ['light', 'Terang'],
    ['dark', 'Gelap'],
] as const;

/**
 * Pemilih tema: tiga radio berlabel teks, dikirim ke server yang menulis cookie dan
 * merender ulang halaman dengan data-theme baru. Setiap pilihan adalah target 44 px.
 */
export function ThemePicker({ compact = false, className }: { compact?: boolean; className?: string }) {
    const { tema } = usePage<PropsBersama>().props;
    const [nilai, setNilai] = useState<string>(tema);
    const [memproses, setMemproses] = useState(false);
    const id = `tema-${compact ? 'ringkas' : 'penuh'}`;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        setMemproses(true);
        router.post(simpan.url(), { tema: nilai }, {
            preserveScroll: true,
            // Atribut <html> disamakan dengan cookie baru oleh pendengar navigasi di app.tsx.
            onFinish: () => setMemproses(false),
        });
    };

    return (
        <form onSubmit={kirim} className={className}>
            <fieldset>
                <legend className={kelas('font-medium text-primary', compact ? 'text-sm' : 'text-base')}>Tema tampilan</legend>
                <div className={kelas('mt-2 flex', compact ? 'flex-wrap gap-x-4 gap-y-1' : 'flex-col')}>
                    {pilihan.map(([v, label]) => (
                        <label key={v} htmlFor={`${id}-${v}`} className="inline-flex min-h-11 cursor-pointer items-center gap-3 text-sm text-primary">
                            <input
                                type="radio"
                                name="tema"
                                id={`${id}-${v}`}
                                value={v}
                                checked={nilai === v}
                                onChange={() => setNilai(v)}
                                className="h-5 w-5 border-control text-brand-700 focus:ring-brand-600"
                            />
                            <span>{label}</span>
                        </label>
                    ))}
                </div>
            </fieldset>
            <div className="mt-2">
                <Button type="submit" variant="secondary" size="sm" processing={memproses}>Terapkan</Button>
            </div>
        </form>
    );
}
