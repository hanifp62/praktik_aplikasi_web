import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FaktorKecocokan, type FaktorBaris } from '@/components/trails/FaktorKecocokan';
import { DinilaiDari } from '@/components/trails/kecocokan/DinilaiDari';
import { KepalaPenilaian } from '@/components/trails/kecocokan/KepalaPenilaian';
import { LangkahBerikut } from '@/components/trails/kecocokan/LangkahBerikut';
import { SyaratDasar } from '@/components/trails/kecocokan/SyaratDasar';
import type { DinilaiDari as DataDinilai, JalurDinilai, Langkah, Penilaian, Syarat } from '@/components/trails/kecocokan/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { onboarding } from '@/routes';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    jalur: JalurDinilai;
    aksi: { label: string; url: string };
    penilaian: Penilaian | null;
    dinilaiDari?: DataDinilai;
    syaratDasar?: Syarat[];
    /** Status Dibatasi/belum diketahui, pembatasan segmen, dan jendela izin (perbaikan P-2). */
    peringatan?: string[];
    faktor?: FaktorBaris[];
    hitungan?: { sesuai: number; perluPerhatian: number; belumDiketahui: number };
    kenapaBukanCocok?: string | null;
    yangBisaDilakukan?: Langkah[];
    dinilai?: string;
    aturan?: string;
}

const judulBagian = 'text-bagian text-primary';
const lapis = 'text-meta font-bold text-muted';

/**
 * Penilaian kecocokan satu jalur (spec v3 bagian 6.5, prototype 05): label dan definisinya,
 * bahan penilaiannya, tiga lapis mesin dalam kata, kenapa labelnya bukan Cocok, dan langkah
 * yang dapat diambil. Tanpa skor dan tanpa bobot angka (R-005, BR-09); bobot tampil sebagai
 * kata pengaruh. Halaman tugas dengan bar aksi, jadi bilah tab disembunyikan dan tombol
 * kembali menuju jalurnya (spec v3 bagian 5.1).
 */
export default function Fit() {
    const { jalur, aksi, penilaian, dinilaiDari, syaratDasar, peringatan, faktor, hitungan, kenapaBukanCocok, yangBisaDilakukan, dinilai, aturan } = usePage<Props>().props;

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <PageHeader induk={{ href: jalur.url, label: jalur.nama }} title="Penilaian kecocokan">
                    <p className="mt-1 text-kecil text-secondary">
                        {jalur.nama},{' '}
                        <Tautan href={jalur.gunungUrl} className="font-bold text-brand-ink underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                            {jalur.gunung}
                        </Tautan>
                    </p>
                </PageHeader>

                {penilaian === null ? (
                    <EmptyState
                        jenis="pertama"
                        title="Isi profil pendaki untuk dinilai"
                        description="Kecocokan dihitung dari pengalaman yang Anda isi sendiri. Tanpa profil, jalur ini belum dinilai, dan itu tidak berarti jalurnya mudah atau sulit."
                    >
                        <Button href={onboarding.url()}>Isi profil pendaki</Button>
                    </EmptyState>
                ) : (
                    <>
                        <KepalaPenilaian penilaian={penilaian} />

                        {dinilaiDari ? (
                            <section aria-labelledby="judul-dinilai" className="space-y-3">
                                <h2 id="judul-dinilai" className={judulBagian}>
                                    Dinilai dari
                                </h2>
                                <DinilaiDari data={dinilaiDari} />
                            </section>
                        ) : null}

                        {syaratDasar ? (
                            <section aria-labelledby="judul-syarat" className="space-y-3">
                                <div>
                                    <p className={lapis}>Lapis 1</p>
                                    <h2 id="judul-syarat" className={judulBagian}>
                                        Syarat dasar: {penilaian.layak ? 'terpenuhi' : 'belum terpenuhi'}
                                    </h2>
                                </div>
                                <SyaratDasar syarat={syaratDasar} />
                            </section>
                        ) : null}

                        {peringatan && peringatan.length > 0 ? (
                            <section aria-labelledby="judul-peringatan" className="space-y-3">
                                <h2 id="judul-peringatan" className={judulBagian}>
                                    Peringatan
                                </h2>
                                <ul className="space-y-2 rounded-lg border border-subtle bg-surface p-4">
                                    {peringatan.map((p) => (
                                        <li key={p} className="flex items-start gap-2 text-kecil text-primary">
                                            <Icon name="warning" size={16} className="mt-0.5 shrink-0 text-warn-900" />
                                            <span className="min-w-0">{p}</span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        {faktor && hitungan ? (
                            <section aria-labelledby="judul-faktor" className="space-y-3">
                                <div>
                                    <p className={lapis}>Lapis 2 dan 3</p>
                                    <h2 id="judul-faktor" className={judulBagian}>
                                        Tujuh faktor, dari yang paling berpengaruh
                                    </h2>
                                    <p className="mt-1 text-kecil text-secondary">
                                        <span data-angka="">{hitungan.sesuai}</span> sesuai, <span data-angka="">{hitungan.perluPerhatian}</span> perlu perhatian,{' '}
                                        <span data-angka="">{hitungan.belumDiketahui}</span> belum diketahui
                                    </p>
                                </div>
                                <div className="rounded-lg border border-subtle bg-surface px-4">
                                    <FaktorKecocokan faktor={faktor} />
                                </div>
                            </section>
                        ) : !penilaian.layak ? (
                            <section aria-labelledby="judul-faktor" className="space-y-2">
                                <div>
                                    <p className={lapis}>Lapis 2 dan 3</p>
                                    <h2 id="judul-faktor" className={judulBagian}>
                                        Tujuh faktor, dari yang paling berpengaruh
                                    </h2>
                                </div>
                                <p className="text-kecil text-secondary">Faktor kecocokan tidak dinilai karena syarat dasar belum terpenuhi.</p>
                            </section>
                        ) : null}

                        {kenapaBukanCocok ? (
                            <section aria-labelledby="judul-kenapa" className="space-y-2">
                                <h2 id="judul-kenapa" className={judulBagian}>
                                    Kenapa bukan Cocok?
                                </h2>
                                <p className="text-isi text-primary">{kenapaBukanCocok}</p>
                            </section>
                        ) : null}

                        {yangBisaDilakukan ? (
                            <section aria-labelledby="judul-langkah" className="space-y-3">
                                <h2 id="judul-langkah" className={judulBagian}>
                                    Yang bisa Anda lakukan
                                </h2>
                                <LangkahBerikut langkah={yangBisaDilakukan} />
                            </section>
                        ) : null}

                        <div className="space-y-2 text-meta text-secondary">
                            <p>Label tidak naik hanya karena Anda menyelesaikan pendakian. Bila pengalaman Anda sudah berubah, ubah sendiri di Profil.</p>
                            <p>
                                Dinilai {dinilai}, {aturan}. Dinilai dengan aturan tertulis, bukan AI. Bila profil, rencana, atau kondisi berubah, penilaian dibuat ulang.
                            </p>
                        </div>
                    </>
                )}

                <BarAksi label="Aksi penilaian kecocokan">
                    <Button variant="secondary" href={jalur.url}>
                        Ke jalur
                    </Button>
                    <Button href={aksi.url}>{aksi.label}</Button>
                </BarAksi>
            </div>
        </div>
    );
}

Fit.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
