import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { KartuHasil } from '@/components/discovery/KartuHasil';
import type { HasilRekomendasi, HasilTersingkir } from '@/components/discovery/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Skeleton } from '@/components/ui/Skeleton';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { create as goalsCreate } from '@/routes/goals';
import { pilih, show } from '@/routes/recommendations';
import { compare, index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    runId: number;
    dihasilkan: string | null;
    profilBerubah: boolean;
    penyebabKosong: 'katalog' | 'wilayah' | 'batasan' | null;
    wilayah: string | null;
    eligible: HasilRekomendasi[];
    sisaEligible: number;
    tampil: number;
    sekaliTampil: number;
    excluded: HasilTersingkir[];
}

/** Sama dengan batas komponen lama; halaman perbandingan sendiri menerima sampai lima. */
const BATAS_BANDING = 3;

/**
 * FR-05 hasil rekomendasi (PRD §30, §32). Hanya tiga label publik (R-004), tanpa skor
 * atau persentase (R-005, BR-09). Run ber-URL permanen dan tidak dihitung ulang; yang
 * ditambahkan halaman ini hanya pengakuan ketika profil atau jalurnya sudah berubah.
 */
export default function Show() {
    const { runId, dihasilkan, profilBerubah, penyebabKosong, wilayah, eligible, sisaEligible, tampil, sekaliTampil, excluded, errors } = usePage<Props>().props;
    const [dibandingkan, setDibandingkan] = useState<number[]>([]);
    const [pengumuman, setPengumuman] = useState('');
    const [memuat, setMemuat] = useState(false);
    const [memilih, setMemilih] = useState<number | null>(null);
    const [pilihanTerakhir, setPilihanTerakhir] = useState<number | null>(null);

    const bandingkan = (trailId: number) => {
        if (dibandingkan.includes(trailId)) {
            const sisa = dibandingkan.filter((id) => id !== trailId);
            setDibandingkan(sisa);
            setPengumuman(`${sisa.length} jalur dipilih untuk dibandingkan.`);
            return;
        }

        if (dibandingkan.length >= BATAS_BANDING) {
            setPengumuman(`Paling banyak ${BATAS_BANDING} jalur dibandingkan sekaligus.`);
            return;
        }

        const baru = [...dibandingkan, trailId];
        setDibandingkan(baru);
        setPengumuman(`${baru.length} jalur dipilih untuk dibandingkan.`);
    };

    // Sisanya ditambahkan atas permintaan, tidak pernah dibuang: membatasi daftar tidak
    // boleh berarti menyembunyikan jalur yang lolos penilaian.
    const tampilkanLagi = () =>
        router.get(
            show.url(runId, { query: { tampil: tampil + sekaliTampil } }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['eligible', 'sisaEligible', 'tampil'],
                onStart: () => setMemuat(true),
                onFinish: () => setMemuat(false),
            },
        );

    const pilihJalur = (trailId: number) => {
        setPilihanTerakhir(trailId);
        router.post(
            pilih.url(runId),
            { trail: trailId },
            { preserveScroll: true, preserveState: true, onStart: () => setMemilih(trailId), onFinish: () => setMemilih(null) },
        );
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Rekomendasi Jalur"
                    description="Label kecocokan menjelaskan hubungan antara profil Anda, rencana perjalanan, dan karakteristik jalur. Label ini bukan penilaian keselamatan."
                >
                    {dihasilkan ? <p className="mt-2 text-xs text-muted">Dihasilkan {dihasilkan}</p> : null}
                </PageHeader>

                {/* Sebelum daftar: kalimat ini mengubah cara seluruh label di bawahnya dibaca. */}
                {profilBerubah ? (
                    <div className="mb-6 rounded-control border border-warn-300 bg-warn-50 p-4">
                        <p className="text-sm font-medium text-warn-900">Profil Anda berubah sejak penilaian ini dibuat</p>
                        <p className="mt-1 text-sm text-warn-900">
                            Label kecocokan di halaman ini dihitung terhadap profil Anda yang lama, dan sengaja dibiarkan apa adanya sebagai catatan. Buat
                            rencana baru untuk dinilai dengan profil Anda sekarang.
                        </p>
                        <Tautan href={goalsCreate.url()} className="mt-2 inline-flex min-h-11 items-center text-sm font-medium text-brand-ink underline">
                            Buat rencana baru
                        </Tautan>
                    </div>
                ) : null}

                {eligible.length === 0 ? (
                    <Card title="Belum ada jalur yang sesuai" className="mb-6">
                        {/* Tiga sebab, tiga saran: saran yang salah lebih buruk daripada tidak ada saran. */}
                        {penyebabKosong === 'katalog' ? (
                            <p className="text-sm text-secondary">
                                Belum ada satu pun jalur berdata di sistem ini. Keterangan jalur belum dimasukkan pengelola kawasan maupun pemandu bersertifikat
                                yang disahkan untuk kawasannya. Batasan rencana Anda tidak ada hubungannya dengan layar ini.
                            </p>
                        ) : penyebabKosong === 'wilayah' ? (
                            <p className="text-sm text-secondary">
                                Belum ada jalur berdata di {wilayah}. Jalur di wilayah itu mungkin sudah dikenali sistem, tetapi keterangannya belum dimasukkan
                                pihak yang berwenang, jadi tidak ada yang dapat diuji terhadap rencana Anda. Mengubah durasi atau batas elevation gain tidak akan
                                mengubah hasil ini; kosongkan wilayahnya untuk melihat jalur di daerah lain.
                            </p>
                        ) : (
                            <p className="text-sm text-secondary">
                                Tidak ada jalur yang memenuhi batasan rencana Anda saat ini. Anda dapat melonggarkan target durasi atau batas elevation gain, atau
                                menelusuri jalur secara manual.
                            </p>
                        )}
                        <div className="mt-4">
                            <Button variant="secondary" href={trailsIndex.url()}>Telusuri jalur manual</Button>
                        </div>
                    </Card>
                ) : null}

                {dibandingkan.length >= 2 ? (
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-control border border-brand-200 bg-brand-50 px-4 py-3">
                        <p className="text-sm text-brand-900">{dibandingkan.length} jalur dipilih untuk dibandingkan.</p>
                        <Button href={compare.url({ query: { trails: dibandingkan.join(',') } })}>Bandingkan</Button>
                    </div>
                ) : null}

                <p role="status" className="sr-only">{pengumuman}</p>

                <ul className="space-y-4">
                    {eligible.map((hasil) => (
                        <li key={hasil.id}>
                            <KartuHasil
                                hasil={hasil}
                                dibandingkan={dibandingkan.includes(hasil.trailId)}
                                onBandingkan={() => bandingkan(hasil.trailId)}
                                onPilih={() => pilihJalur(hasil.trailId)}
                                memilih={memilih === hasil.trailId}
                                galatPilih={pilihanTerakhir === hasil.trailId ? (errors.trail ?? null) : null}
                            />
                        </li>
                    ))}
                </ul>

                {/* Empat baris kira-kira setinggi satu kartu, jadi daftarnya tidak melompat. */}
                {memuat ? <Skeleton rows={4} className="mt-4" /> : null}

                {sisaEligible > 0 ? (
                    <div className="mt-4">
                        <Button variant="secondary" onClick={tampilkanLagi} processing={memuat}>
                            Tampilkan {sisaEligible} jalur lainnya
                        </Button>
                    </div>
                ) : null}

                {excluded.length > 0 ? (
                    <section className="mt-8" aria-labelledby="tersingkir">
                        <h2 id="tersingkir" className="text-base font-semibold text-primary">Tidak masuk rekomendasi</h2>
                        <p className="mt-1 text-sm text-secondary">
                            Jalur berikut dikecualikan oleh batasan yang bersifat pasti, misalnya status resmi tutup atau durasi yang tidak sesuai rencana.
                        </p>
                        <ul className="mt-3 space-y-2">
                            {excluded.map((hasil) => (
                                <li key={hasil.id} className="rounded-control border border-subtle bg-surface-sunken px-4 py-3 text-sm">
                                    <span className="font-medium text-primary">{hasil.name}</span>
                                    {hasil.mountain ? <span className="text-secondary"> · {hasil.mountain}</span> : null}
                                    <ul className="mt-1 list-disc pl-5 text-secondary">
                                        {hasil.alasan.map((a) => (
                                            <li key={a}>{a}</li>
                                        ))}
                                    </ul>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
