import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { ConsiderationTray } from '@/components/trails/ConsiderationTray';
import { FilterForm, saringanAktif, type Filter } from '@/components/trails/FilterForm';
import { Menunggu } from '@/components/trails/Menunggu';
import { PetaJelajah } from '@/components/trails/PetaJelajah';
import { TrailRow } from '@/components/trails/TrailRow';
import type { BarisJalur, FiturPeta, JalurMenunggu, TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    filter: Filter;
    technicalLevels: Array<{ value: string; label: string }>;
    trails: { data: BarisJalur[]; total: number; links: TautanHalaman[] };
    menunggu: JalurMenunggu[];
    ditimbang: Array<{ id: number; name: string }>;
    batasTimbangan: number;
    petaSebaran: FiturPeta[];
    pengumuman: string;
}

const kosong: Filter = { search: '', technical: '', region: '', distance: null, duration: null };

function geserKe(id: number) {
    const hemat = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    requestAnimationFrame(() => document.getElementById(`jalur-${id}`)?.scrollIntoView({ behavior: hemat ? 'auto' : 'smooth', block: 'center' }));
}

/**
 * Jelajah: peta dan daftar berdampingan di layar lebar, salah satu di ponsel
 * (features/01, ourplan "Map-First Pattern"). Saringan hidup di URL (dapat dibagikan,
 * tombol kembali bekerja); tiap perubahan memuat ulang data lewat Inertia dengan jeda,
 * tanpa memuat ulang halaman dan tanpa memindahkan peta.
 */
export default function Index() {
    const { auth, flash, filter, technicalLevels, trails, menunggu, ditimbang, batasTimbangan, petaSebaran, pengumuman } = usePage<Props>().props;
    const [isian, setIsian] = useState<Filter>(filter);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [sorot, setSorot] = useState<number | null>(null);
    const pertama = useRef(true);
    const adaPeta = petaSebaran.length > 0;
    const adaSaringan = saringanAktif(isian, technicalLevels).length > 0;

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const t = setTimeout(() => {
            const query = Object.fromEntries(Object.entries(isian).filter(([, v]) => v !== '' && v !== null)) as Record<string, string | number>;
            router.get(trailsIndex.url({ query }), {}, { preserveState: true, preserveScroll: true, replace: true, only: ['trails', 'menunggu', 'petaSebaran', 'filter'] });
        }, 400);
        return () => clearTimeout(t);
    }, [isian]);

    const ubah = (k: keyof Filter, v: string) => setIsian((s) => ({ ...s, [k]: k === 'distance' || k === 'duration' ? (v === '' ? null : Number(v)) : v }));

    const lihatDiDaftar = (id: number) => {
        setTampilan('daftar');
        setSorot(id);
        geserKe(id);
    };

    return (
        <div className="py-6 sm:py-8">
            <div className={kelas('mx-auto px-4 sm:px-6 lg:px-8', adaPeta ? 'max-w-7xl' : 'max-w-5xl')}>
                <PageHeader title="Jelajahi Jalur" description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />

                {auth.user && !auth.user.hasCompletedProfile ? (
                    <Card className="mb-6" title="Lengkapi profil untuk melihat kecocokan" subtitle="Tanpa data pengalaman Anda, daftar ini hanya katalog jalur. Dengan profil, tiap jalur menyebut cocok atau tidaknya untuk Anda beserta alasannya.">
                        <Button href={onboarding.url()}>Isi profil sekarang</Button>
                    </Card>
                ) : null}

                <FilterForm isian={isian} technicalLevels={technicalLevels} onUbah={ubah} onReset={() => setIsian(kosong)} />

                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <p aria-live="polite" className="text-sm text-muted">
                        {trails.total} jalur{adaSaringan ? ' sesuai saringan' : ''}
                    </p>
                    {adaPeta ? <PilihTampilan tampilan={tampilan} onPilih={setTampilan} /> : null}
                </div>

                <div className={kelas(adaPeta && 'lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-8')}>
                    <section aria-label="Daftar jalur" className={kelas(adaPeta && tampilan === 'peta' && 'hidden lg:block')}>
                        {trails.data.length === 0 && adaSaringan ? (
                            <EmptyState
                                jenis="tersaring"
                                title="Tidak ada jalur dengan saringan ini"
                                description="Saringan yang sedang aktif tidak menemukan jalur. Melonggarkan tingkat teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian."
                            >
                                <Button variant="secondary" size="sm" onClick={() => setIsian(kosong)}>Hapus semua saringan</Button>
                            </EmptyState>
                        ) : trails.data.length === 0 ? (
                            <EmptyState
                                jenis="menunggu"
                                title="Belum ada jalur yang terbit"
                                menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                                description="Jalur tampil di sini setelah datanya lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis rute di peta."
                            />
                        ) : (
                            <div className="border-t border-subtle">
                                {trails.data.map((t) => (
                                    <div
                                        key={t.id}
                                        id={`jalur-${t.id}`}
                                        onMouseEnter={() => setSorot(t.id)}
                                        onMouseLeave={() => setSorot((s) => (s === t.id ? null : s))}
                                        onFocusCapture={() => setSorot(t.id)}
                                        className={kelas('-mx-2 rounded-lg px-2 transition-colors', sorot === t.id && 'bg-surface-sunken')}
                                    >
                                        <TrailRow trail={t} />
                                    </div>
                                ))}
                            </div>
                        )}
                        <div className="mt-6">
                            <Pagination links={trails.links} />
                        </div>
                        {menunggu.length > 0 ? <Menunggu daftar={menunggu} /> : null}
                    </section>

                    {adaPeta ? (
                        <section aria-label="Peta jalur" className={kelas(tampilan === 'daftar' ? 'hidden lg:block' : 'block', 'lg:sticky lg:top-6 lg:self-start')}>
                            <PetaJelajah
                                fitur={petaSebaran}
                                baris={trails.data}
                                sorot={sorot}
                                onLihatDiDaftar={lihatDiDaftar}
                                className="h-[calc(100dvh-var(--nav-bawah,0px)-14rem)] min-h-[22rem] lg:h-[calc(100dvh-3rem)]"
                            />
                        </section>
                    ) : null}
                </div>

                {flash.timbanganPenuh ? <Alert variant="warning" className="mt-4">{flash.timbanganPenuh}</Alert> : null}

                <p role="status" className="sr-only">{pengumuman}</p>

                <ConsiderationTray trails={ditimbang} batas={batasTimbangan} />
            </div>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;

/** Ponsel: daftar atau peta, bukan keduanya bertumpuk. Layar lebar menampilkan keduanya. */
function PilihTampilan({ tampilan, onPilih }: { tampilan: 'daftar' | 'peta'; onPilih: (t: 'daftar' | 'peta') => void }) {
    return (
        <div role="group" aria-label="Tampilan hasil" className="inline-flex rounded-control border border-control p-0.5 lg:hidden">
            {(['daftar', 'peta'] as const).map((t) => (
                <button
                    key={t}
                    type="button"
                    aria-pressed={tampilan === t}
                    onClick={() => onPilih(t)}
                    className={kelas(
                        'min-h-11 rounded-control px-4 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
                        tampilan === t ? 'bg-brand-700 text-on-brand' : 'text-secondary hover:text-primary',
                    )}
                >
                    {t === 'daftar' ? 'Daftar' : 'Peta'}
                </button>
            ))}
        </div>
    );
}
