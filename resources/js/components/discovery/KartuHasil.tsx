import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { Tautan } from '@/components/ui/Tautan';
import { PanelAlasan } from './PanelAlasan';
import type { HasilRekomendasi } from './types';

interface KartuHasilProps {
    hasil: HasilRekomendasi;
    dibandingkan: boolean;
    onBandingkan: () => void;
    onPilih: () => void;
    memilih: boolean;
    galatPilih: string | null;
}

/**
 * Satu jalur di halaman hasil. Urutan bacanya: nama dan label, angka yang dipakai
 * menilai, peringatan, lalu aksi. Alasannya terlipat di balik "Kenapa?" dan sudah
 * datang bersama barisnya, jadi membukanya tidak menunggu server.
 */
export function KartuHasil({ hasil, dibandingkan, onBandingkan, onPilih, memilih, galatPilih }: KartuHasilProps) {
    const [terbuka, setTerbuka] = useState(false);
    const idJudul = `hasil-${hasil.id}`;
    const idPanel = `alasan-hasil-${hasil.id}`;

    return (
        <Card>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h2 id={idJudul} className="text-lg font-semibold text-primary">
                        {/* Jalur yang ditarik setelah run dibuat tetap tercatat, tetapi
                            tautannya dicabut: halaman jalur terarsip menjawab 404. */}
                        {hasil.url ? (
                            <Tautan href={hasil.url} className="rounded-sm py-1 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                {hasil.name}
                            </Tautan>
                        ) : (
                            hasil.name
                        )}
                    </h2>
                    {hasil.mountain ? <p className="text-sm text-secondary">{hasil.mountain}</p> : null}
                    {hasil.ditarik ? (
                        <p className="mt-1 text-sm font-medium text-warn-900">Jalur ini ditarik dari katalog setelah penilaian ini dibuat.</p>
                    ) : null}
                </div>
                <span data-fit-label className="shrink-0">
                    <LabelKecocokan nilai={hasil.label} label={hasil.labelText} />
                </span>
            </div>

            {/* Angka dari snapshot: jalur sebagaimana dipakai menilai, bukan tabel jalur sekarang. */}
            <dl className="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div>
                    <dt className="text-muted">Jarak</dt>
                    <dd className="font-medium text-primary">{hasil.angka.jarak}</dd>
                </div>
                <div>
                    <dt className="text-muted">Elevation gain</dt>
                    <dd className="font-medium text-primary">{hasil.angka.tanjakan}</dd>
                </div>
                <div>
                    <dt className="text-muted">Estimasi durasi</dt>
                    <dd className="font-medium text-primary">{hasil.angka.durasi}</dd>
                </div>
                <div>
                    <dt className="text-muted">Tingkat teknis</dt>
                    <dd className="font-medium text-primary">{hasil.angka.teknis}</dd>
                </div>
            </dl>

            {hasil.berubah ? (
                <p className="mt-3 rounded-control border border-warn-300 bg-warn-50 px-3 py-2 text-sm text-warn-900">
                    Data jalur ini berubah setelah penilaian dibuat. Angka di atas adalah yang dipakai menilai; buka halaman jalur untuk keterangan terkini.
                </p>
            ) : null}

            {hasil.warnings.length > 0 ? (
                <ul className="mt-3 space-y-1 text-sm text-warn-900">
                    {hasil.warnings.map((w) => (
                        <li key={w} className="rounded-control bg-warn-50 px-3 py-2">{w}</li>
                    ))}
                </ul>
            ) : null}

            <div className="mt-4 flex flex-wrap gap-2">
                <Button variant="secondary" size="sm" aria-expanded={terbuka} aria-controls={idPanel} onClick={() => setTerbuka((v) => !v)}>
                    {terbuka ? 'Sembunyikan alasan' : 'Kenapa?'}
                    <span className="sr-only"> Alasan penilaian {hasil.name}</span>
                </Button>
                <Button variant="secondary" size="sm" onClick={onBandingkan}>
                    {dibandingkan ? 'Batal bandingkan' : 'Tambah ke perbandingan'}
                </Button>
                {!hasil.ditarik ? (
                    <Button size="sm" onClick={onPilih} processing={memilih}>Pilih jalur ini</Button>
                ) : null}
            </div>

            {galatPilih ? (
                <p role="alert" className="mt-3 text-sm font-medium text-danger-900">{galatPilih}</p>
            ) : null}

            {terbuka ? <PanelAlasan hasil={hasil} id={idPanel} /> : null}
        </Card>
    );
}
