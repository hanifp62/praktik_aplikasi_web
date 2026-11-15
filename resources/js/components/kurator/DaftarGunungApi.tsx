import { Kesegaran } from '@/components/trails/Kesegaran';
import { Button } from '@/components/ui/Button';
import { create } from '@/routes/curator/volcano';
import type { BarisGunungApi } from './types';

/**
 * Status gunung api PVMBG di kawasan kurator (DK-43; ruling W8-R18): level terakhir per gunung
 * yang sudah punya catatan, kapan PVMBG menerbitkannya, dan kesegaran pencocokannya. Level dicatat
 * terpisah dari status akses dan tidak pernah dipetakan ke Buka atau Tutup.
 */
export function DaftarGunungApi({ baris }: { baris: BarisGunungApi[] }) {
    return (
        <section aria-labelledby="antrean-gunung-api" className="border-t border-subtle pt-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="antrean-gunung-api" className="text-bagian text-primary">
                        Status gunung api (PVMBG) <span className="text-secondary tabular-nums">({baris.length})</span>
                    </h2>
                    <p className="mt-1 max-w-prose text-kecil text-secondary">
                        Salin level, rekomendasi radius, dan tautan laporan dari MAGMA Indonesia. Level tampil di halaman gunung, rincian jalur, dan Cek kesiapan, terpisah dari status akses.
                    </p>
                </div>
                <Button variant="secondary" size="sm" href={create.url()} className="shrink-0">
                    Catat status gunung api
                </Button>
            </div>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Belum ada status gunung api yang dicatat di kawasan Anda.</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((g) => (
                        <li key={g.id} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <p className="font-bold text-primary">{g.gunung}</p>
                                <p className="mt-1 text-kecil text-primary">{g.label}</p>
                                <p className="mt-1 text-meta text-muted">Diterbitkan PVMBG {g.diterbitkan}.</p>
                                <Kesegaran waktu={g.diverifikasi} awalan="Dicocokkan" keadaan={g.kesegaran} className="mt-1" />
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Button variant="secondary" size="sm" href={g.perbarui} aria-label={`Catat level terbaru ${g.gunung}`}>
                                    Catat level terbaru
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

