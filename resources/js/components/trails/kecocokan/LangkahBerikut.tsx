import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { Langkah } from './types';

/**
 * "Yang bisa Anda lakukan" (spec v3 bagian 6.5). Langkah yang punya tujuan menjadi baris
 * bertautan dengan chevron (membuka halaman lain, spec v3 bagian 8.8); saran tanpa tujuan
 * tetap teks biasa, tidak dibungkus kontrol yang tidak melakukan apa pun (DK-40).
 */
export function LangkahBerikut({ langkah }: { langkah: Langkah[] }) {
    return (
        <ul className="divide-y divide-subtle overflow-hidden rounded-lg border border-subtle bg-surface">
            {langkah.map((l) => (
                <li key={l.judul}>
                    {l.url ? (
                        <Tautan href={l.url} className={kelas('flex items-center gap-3 px-4 py-3 hover:bg-surface-sunken', fokusTerlihat)}>
                            <span className="min-w-0 flex-1">
                                <span className="block text-kecil font-bold text-primary">{l.judul}</span>
                                <span className="mt-0.5 block text-meta text-secondary">{l.keterangan}</span>
                            </span>
                            <Icon name="caret-right" size={20} className="shrink-0 text-muted" />
                        </Tautan>
                    ) : (
                        <div className="px-4 py-3">
                            <p className="text-kecil font-bold text-primary">{l.judul}</p>
                            <p className="mt-0.5 text-meta text-secondary">{l.keterangan}</p>
                        </div>
                    )}
                </li>
            ))}
        </ul>
    );
}
