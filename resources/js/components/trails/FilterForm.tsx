import { useState } from 'react';
import { Label, Select, TextInput } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';

export interface Filter {
    search: string;
    technical: string;
    region: string;
    distance: number | null;
    duration: number | null;
}

interface FilterFormProps {
    isian: Filter;
    technicalLevels: Array<{ value: string; label: string }>;
    onUbah: (kunci: keyof Filter, nilai: string) => void;
    onReset: () => void;
}

/** Saringan yang sedang berlaku, sebagai kalimat pendek yang dapat dihapus satu per satu. */
export function saringanAktif(isian: Filter, technicalLevels: FilterFormProps['technicalLevels']): Array<{ kunci: keyof Filter; teks: string }> {
    const aktif: Array<{ kunci: keyof Filter; teks: string }> = [];
    if (isian.search.trim() !== '') aktif.push({ kunci: 'search', teks: `“${isian.search.trim()}”` });
    if (isian.technical !== '') aktif.push({ kunci: 'technical', teks: `Teknis ${technicalLevels.find((t) => t.value === isian.technical)?.label.toLowerCase() ?? isian.technical}` });
    if (isian.distance !== null) aktif.push({ kunci: 'distance', teks: `Maks. ${String(isian.distance).replace('.', ',')} km` });
    if (isian.duration !== null) aktif.push({ kunci: 'duration', teks: `Maks. ${isian.duration} jam` });
    if (isian.region.trim() !== '') aktif.push({ kunci: 'region', teks: isian.region.trim() });

    return aktif;
}

/**
 * Saringan Jelajah.
 *
 * Kotak cari selalu terlihat. Saringan lain terlipat di ponsel di balik tombol yang
 * menyebut jumlah saringan aktif, karena lima isian bertumpuk mendorong daftar jalur
 * jauh di bawah layar pertama (temuan F6). Di layar lebar semuanya tampil. Saringan
 * yang berlaku selalu terlihat sebagai chip yang dapat dihapus satu per satu (pola
 * Baymard yang dirujuk ourplan: saringan aktif harus tampak, bukan diingat).
 */
export function FilterForm({ isian, technicalLevels, onUbah, onReset }: FilterFormProps) {
    const [terbuka, setTerbuka] = useState(false);
    const aktif = saringanAktif(isian, technicalLevels);
    const jumlahLain = aktif.filter((a) => a.kunci !== 'search').length;

    return (
        <form role="search" aria-label="Cari dan saring jalur" onSubmit={(e) => e.preventDefault()} className="mb-4 space-y-3">
            <div className="flex items-end gap-2">
                <div className="min-w-0 flex-1">
                    <Label htmlFor="search">Cari jalur atau gunung</Label>
                    <TextInput id="search" type="search" value={isian.search} onChange={(e) => onUbah('search', e.target.value)} placeholder="Merbabu" />
                </div>
                <button
                    type="button"
                    onClick={() => setTerbuka((v) => !v)}
                    aria-expanded={terbuka}
                    aria-controls="saringan-lain"
                    className="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-control border border-control bg-surface px-3 text-sm font-medium text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:hidden"
                >
                    Saring{jumlahLain > 0 ? ` (${jumlahLain})` : ''}
                </button>
            </div>

            <div id="saringan-lain" className={kelas('grid grid-cols-2 gap-3 rounded-lg border border-subtle bg-surface p-3 sm:grid sm:grid-cols-4', terbuka ? 'grid' : 'hidden')}>
                <div className="col-span-2 sm:col-span-1">
                    <Label htmlFor="technical">Tingkat teknis</Label>
                    <Select id="technical" value={isian.technical} onChange={(e) => onUbah('technical', e.target.value)}>
                        <option value="">Semua</option>
                        {technicalLevels.map((t) => (
                            <option key={t.value} value={t.value}>{t.label}</option>
                        ))}
                    </Select>
                </div>
                <div>
                    <Label htmlFor="distance">Maks. jarak (km)</Label>
                    <TextInput id="distance" type="number" inputMode="decimal" min={0} step={0.5} value={isian.distance ?? ''} onChange={(e) => onUbah('distance', e.target.value)} placeholder="12" />
                </div>
                <div>
                    <Label htmlFor="duration">Maks. waktu (jam)</Label>
                    <TextInput id="duration" type="number" inputMode="numeric" min={0} step={1} value={isian.duration ?? ''} onChange={(e) => onUbah('duration', e.target.value)} placeholder="6" />
                </div>
                <div className="col-span-2 sm:col-span-1">
                    <Label htmlFor="region">Wilayah</Label>
                    <TextInput id="region" value={isian.region} onChange={(e) => onUbah('region', e.target.value)} placeholder="Jawa Tengah" />
                </div>
            </div>

            {aktif.length > 0 ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs text-muted">Saringan:</span>
                    {aktif.map((a) => (
                        <button
                            key={a.kunci}
                            type="button"
                            onClick={() => onUbah(a.kunci, '')}
                            aria-label={`Hapus saringan ${a.teks}`}
                            className="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-subtle bg-surface-sunken px-3 text-xs font-medium text-primary hover:border-control focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        >
                            {a.teks}
                            <span aria-hidden="true">×</span>
                        </button>
                    ))}
                    {aktif.length > 1 ? (
                        <button type="button" onClick={onReset} className="inline-flex min-h-11 items-center px-2 text-xs font-medium text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                            Hapus semua
                        </button>
                    ) : null}
                </div>
            ) : null}
        </form>
    );
}
