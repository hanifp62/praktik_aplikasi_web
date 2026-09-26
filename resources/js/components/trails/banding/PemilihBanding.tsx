import { useState } from 'react';
import { Checkbox } from '@/components/ui/Field';
import { AksiPilihan } from './AksiPilihan';

export interface OpsiBanding {
    id: number;
    name: string;
    mountain: string | null;
    dipilih: boolean;
    tersimpan: boolean;
}

interface PemilihBandingProps {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    /** Id kolom yang sedang dibandingkan, dalam urutan kolomnya. */
    urutan: number[];
}

/**
 * Mengganti jalur yang dibandingkan tanpa kembali ke Simpanan. Pilihan datang dari server
 * (RouteComparisonController::opsi()): simpanan pembaca yang masih terbit, ditambah jalur
 * terpilih dari tautan lama yang bukan simpanannya, supaya tetap dapat dilepas. Terlipat
 * bila sudah ada yang dibandingkan, supaya tabel tetap isi utama halaman; terbuka bila
 * belum ada.
 */
export function PemilihBanding({ pilihan, urutan }: PemilihBandingProps) {
    const [dipilih, setDipilih] = useState<number[]>(() => urutan.filter((id) => pilihan.opsi.some((o) => o.id === id && o.dipilih)));
    const penuh = dipilih.length >= pilihan.maks;

    const ubah = (id: number) =>
        setDipilih((d) => (d.includes(id) ? d.filter((x) => x !== id) : d.length >= pilihan.maks ? d : [...d, id]));

    return (
        <details open={urutan.length === 0 ? true : undefined} className="rounded-lg border border-subtle bg-surface px-4">
            <summary className="list-item min-h-11 cursor-pointer rounded-sm py-2 text-sm font-medium text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                Pilih jalur yang dibandingkan
            </summary>

            <ul className="grid gap-x-6 sm:grid-cols-2">
                {pilihan.opsi.map((o) => (
                    <li key={o.id}>
                        <label htmlFor={`banding-${o.id}`} className="flex min-h-11 cursor-pointer items-center gap-3 text-sm">
                            <Checkbox
                                id={`banding-${o.id}`}
                                checked={dipilih.includes(o.id)}
                                disabled={penuh && !dipilih.includes(o.id)}
                                onChange={() => ubah(o.id)}
                            />
                            <span className="min-w-0 [overflow-wrap:anywhere]">
                                <span className="font-medium text-primary">{o.name}</span>
                                {o.mountain ? <span className="text-secondary">, {o.mountain}</span> : null}
                                {o.tersimpan ? null : <span className="block text-xs text-muted">Bukan simpanan Anda</span>}
                            </span>
                        </label>
                    </li>
                ))}
            </ul>

            <div className="py-3">
                <AksiPilihan dipilih={dipilih} maks={pilihan.maks} tersedia={pilihan.opsi.filter((o) => o.tersimpan).length} />
            </div>
        </details>
    );
}
