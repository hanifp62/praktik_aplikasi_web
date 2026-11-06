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
 * Pemilih tema: tiga radio berlabel teks, dikirim ke server yang menulis cookie. Setiap
 * pilihan adalah target 44 px.
 *
 * Tema berganti saat itu juga (ruling AF-1, audit E2E I-1): sesudah POST /tema berhasil,
 * window.terapkanTema (dasar.js) menyamakan data-theme pada <html> dengan cookie baru. Pendengar
 * navigate di app.tsx tidak cukup di sini, karena POST /tema kembali ke URL yang sama, dan
 * Inertia 3.7.1 menjadikan kunjungan ke URL yang sama sebagai replace yang tidak memancarkan
 * navigate. Berlaku di Profil dan di kaki beranda tamu, yang memakai komponen ini.
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
            onSuccess: () => window.terapkanTema(),
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
                                className="h-5 w-5 border-control text-brand-700 checked:border-transparent focus:ring-brand-600"
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
