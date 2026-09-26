import { router } from '@inertiajs/react';
import { Tautan } from '@/components/ui/Tautan';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { timbang } from '@/routes/trails';
import { FitLine } from './FitLine';
import type { BarisJalur } from './types';

/**
 * Baris jalur, bukan baris basis data. Durasi mendapat bobot terbesar (angka yang
 * menentukan keputusan akhir pekan), lalu jarak, tanjakan, kecuraman. Seluruh baris
 * menjadi sasaran tautan lewat after:absolute; tombol simpan dan "Kenapa?" berdiri di
 * atas lapisan itu (relative z-10) supaya menyimpan tidak membuka jalurnya.
 */
export function TrailRow({ trail, aksiSimpan = true }: { trail: BarisJalur; aksiSimpan?: boolean }) {
    const [memproses, setMemproses] = useState(false);

    const simpan = () => {
        setMemproses(true);
        router.post(timbang.url({ trail: trail.id }), {}, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setMemproses(false),
        });
    };

    return (
        <article className="group relative border-b border-subtle py-5">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 className="text-lg font-semibold text-primary">
                    <Tautan
                        href={trail.url}
                        className="rounded-sm after:absolute after:inset-0 group-hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                    >
                        {trail.name}
                    </Tautan>
                </h2>
                {trail.technical ? (
                    <span className="shrink-0 rounded-control border border-subtle bg-surface-sunken px-2 py-0.5 text-xs font-medium text-secondary">{trail.technical}</span>
                ) : null}
            </div>

            <p className="mt-0.5 text-sm text-secondary">
                {trail.mountain} &middot; {trail.province}
            </p>

            <dl className="mt-3 flex flex-wrap items-baseline gap-x-6 gap-y-2">
                <div className="flex items-baseline gap-1.5">
                    <dt className="sr-only">Perkiraan durasi</dt>
                    <dd data-angka className="text-xl font-semibold leading-none text-primary">{trail.durasi}</dd>
                </div>
                <div className="flex items-baseline gap-1.5 text-sm">
                    <dt className="text-muted">Jarak</dt>
                    <dd data-angka className="font-medium text-primary">{trail.jarak}</dd>
                </div>
                <div className="flex items-baseline gap-1.5 text-sm">
                    <dt className="text-muted">Tanjakan</dt>
                    <dd data-angka className="font-medium text-primary">{trail.tanjakan}</dd>
                </div>
                {trail.curam ? (
                    <div className="flex items-baseline gap-1.5 text-sm">
                        <dt className="text-muted">Kecuraman</dt>
                        <dd data-angka className="font-medium text-primary">{trail.curam}</dd>
                    </div>
                ) : null}
            </dl>

            {trail.kemudahan.length > 0 ? <p className="mt-2 text-xs text-muted">{trail.kemudahan.join(' · ')}</p> : null}

            {trail.fit ? <FitLine fit={trail.fit} trailId={trail.id} /> : null}

            {aksiSimpan ? (
                <div className="relative z-10 mt-3">
                    <Button size="sm" variant="secondary" onClick={simpan} processing={memproses}>
                        {trail.ditimbang ? (
                            <>
                                <span aria-hidden="true">✓</span> Tersimpan
                            </>
                        ) : (
                            'Simpan rute'
                        )}
                    </Button>
                </div>
            ) : null}
        </article>
    );
}
