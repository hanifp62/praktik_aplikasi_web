import { usePage } from '@inertiajs/react';
import { PetaPendakian } from '@/components/trips/PetaPendakian';
import { TataLetakPendakian } from '@/components/trips/TataLetakPendakian';
import type { GeometriJalur, Penanda } from '@/components/trips/types';
import { useJejakPendakian, usePosisiPendakian } from '@/components/trips/usePendakian';
import { Button } from '@/components/ui/Button';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { kelas } from '@/lib/kelas';
import { track } from '@/routes/hike';
import { show } from '@/routes/trips';
import { position } from '@/routes/trips/hike';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trip: { id: number; jalur: string; gunung: string };
    checkpoints: Array<{ id: number; name: string; sequence: number }>;
    penanda: Penanda[];
    posBerkoordinat: number;
    geometri: GeometriJalur | null;
    posisi: { lat: number; lng: number; koordinat: string } | null;
    berikutnya: { id: number; name: string; sequence: number; jarak: string | null; antara: string | null; tempo: string | null } | null;
    merekamJejak: boolean;
    sesiId: number | null;
}

/**
 * FR-15 mode pendakian: hanya yang dibutuhkan sambil bergerak (PRD §54). Pos berikutnya
 * di atas, satu tombol besar untuk memperbarui posisi, lalu peta yang lebar. Lokasi
 * diminta di halaman ini dan tidak di tempat lain (PRD §120).
 */
export default function Hike() {
    const { trip, checkpoints, penanda, posBerkoordinat, geometri, posisi, berikutnya, merekamJejak, sesiId } = usePage<Props>().props;
    const { pesan, memproses, minta } = usePosisiPendakian(position.url(trip.id));

    useJejakPendakian(merekamJejak, sesiId, sesiId !== null ? track.url(sesiId) : null);

    const adaPeta = geometri !== null || penanda.length > 0 || posisi !== null;

    return (
        <TataLetakPendakian kembali={show.url(trip.id)}>
            <div className="mx-auto max-w-3xl space-y-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <h1 className="text-2xl text-primary">{trip.jalur}</h1>
                    <p className="text-sm text-secondary">{trip.gunung}</p>
                </div>

                <section aria-labelledby="judul-berikutnya" className="rounded-lg border border-subtle bg-surface p-4">
                    <h2 id="judul-berikutnya" className="text-sm font-medium text-muted">Checkpoint berikutnya</h2>
                    <p className="mt-1 text-2xl font-semibold text-primary">{berikutnya?.name ?? 'Belum ditentukan'}</p>
                    <p className="mt-1 text-base text-secondary">
                        {berikutnya?.jarak ? `Sekitar ${berikutnya.jarak} dari posisi Anda` : 'Jarak belum dapat dihitung'}
                    </p>
                    {berikutnya?.antara ? <p className="mt-1 text-sm text-secondary">Dari pos sebelumnya: {berikutnya.antara}</p> : null}
                    {berikutnya ? (
                        <p className="mt-1 text-sm text-secondary">{berikutnya.tempo ?? 'Perkiraan waktu tempuh bagian ini belum tersedia.'}</p>
                    ) : null}
                    {checkpoints.length > 0 && posBerkoordinat === 0 ? (
                        <p className="mt-2 text-xs text-muted">Koordinat pos jalur ini belum tercatat, jadi jarak ke pos belum dapat dihitung dari posisi Anda.</p>
                    ) : null}
                </section>

                <section aria-labelledby="judul-posisi" className="rounded-lg border border-subtle bg-surface p-4">
                    <h2 id="judul-posisi" className="text-sm font-medium text-muted">Posisi Anda</h2>
                    <Button onClick={minta} processing={memproses} className="mt-2 min-h-14 w-full text-base">
                        Perbarui posisi
                    </Button>
                    <p role="status" className="mt-2 text-sm text-secondary">{pesan}</p>
                    {posisi ? <p className="mt-1 text-xs text-muted">{posisi.koordinat}</p> : null}
                    <p className="mt-2 text-xs text-muted">Lokasi Anda hanya dipakai di halaman ini dan tidak dibagikan ke pengguna lain.</p>
                </section>

                {/* Daftar checkpoint di bawah adalah padanan non-visual peta ini. */}
                {adaPeta ? (
                    <PetaPendakian
                        id="hike-map"
                        label={`Peta jalur ${trip.jalur} dan posisi checkpoint`}
                        geometri={geometri}
                        penanda={penanda}
                        posisi={posisi}
                    />
                ) : (
                    <p className="rounded-lg border border-dashed border-subtle bg-surface p-4 text-sm text-secondary">
                        Peta jalur belum dapat digambar karena garis jalur dan koordinat pos belum tercatat. Tekan Perbarui posisi untuk melihat posisi Anda di peta dasar.
                    </p>
                )}

                <section aria-labelledby="judul-daftar" className="rounded-lg border border-subtle bg-surface p-4">
                    <h2 id="judul-daftar" className="text-sm font-medium text-muted">Daftar checkpoint</h2>
                    <ol className="mt-2 space-y-1 text-sm">
                        {checkpoints.length === 0 ? (
                            <li>
                                <p className="font-medium text-primary">Jalur ini belum punya checkpoint tercatat</p>
                                <p className="mt-1 max-w-prose text-secondary">
                                    Mode pendakian tetap berjalan tanpa checkpoint, hanya tanpa penanda posisi antarpos. Data pos berasal dari pengelola jalur, bukan dari perangkat Anda.
                                </p>
                            </li>
                        ) : (
                            checkpoints.map((c) => {
                                const dituju = berikutnya?.id === c.id;

                                return (
                                    <li
                                        key={c.id}
                                        aria-current={dituju ? 'step' : undefined}
                                        className={kelas('rounded px-2 py-1', dituju ? 'bg-brand-50 font-medium text-brand-900' : 'text-secondary')}
                                    >
                                        {c.sequence}. {c.name}
                                        {dituju ? <span className="ml-2 text-xs">(berikutnya)</span> : null}
                                    </li>
                                );
                            })
                        )}
                    </ol>
                </section>

                {/* Akses cepat tema di lapangan (DK-17): layar terang di bawah matahari,
                    gelap di tenda; keduanya sering berganti dalam satu pendakian. */}
                <ThemePicker compact className="rounded-lg border border-subtle bg-surface p-4" />
            </div>
        </TataLetakPendakian>
    );
}
