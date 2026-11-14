import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { PengalamanTerberat } from '@/components/onboarding/PengalamanTerberat';
import { PilihanKartu } from '@/components/onboarding/PilihanKartu';
import type { JalurTerberat, NilaiOnboarding, PilihanOnboarding } from '@/components/onboarding/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store } from '@/routes/onboarding';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    sudahLengkap: boolean;
    langkahAwal: number;
    nilai: NilaiOnboarding;
    pilihan: PilihanOnboarding;
    pilihanJalur?: JalurTerberat[];
    selesai: { cocok: number } | null;
    tautan: { jelajah: string; cocok: string; profil: string };
}

const JUMLAH_LANGKAH = 3;

/** Judul langkah 1 sampai 3, berurutan (prototype 00). */
const JUDUL_LANGKAH = [
    {
        kecil: 'Kenali pengalaman Anda',
        judul: 'Seberapa jauh pengalaman mendaki Anda?',
        penjelas: 'Pilih yang paling mendekati. Ini membantu menjelaskan jalur mana yang realistis untuk Anda.',
    },
    {
        kecil: 'Pendakian terberat Anda',
        judul: 'Jalur mana yang paling menantang yang pernah Anda selesaikan?',
        penjelas: 'Kami memakai tanjakan dan durasinya, bukan tinggi puncaknya. Gunung 2.000 mdpl bisa lebih berat daripada 3.000 mdpl.',
    },
    {
        kecil: 'Rencana Anda',
        judul: 'Pendakian seperti apa yang ingin Anda lakukan?',
        penjelas: null,
    },
] as const;

/** Isian milik tiap langkah, untuk kembali ke langkah yang galatnya dilaporkan server. */
const isianLangkah: Record<number, string[]> = {
    1: ['experience_level'],
    2: ['cara_pengalaman', 'jalur_terberat_id', 'completed_hikes_count', 'terrain_experience', 'navigation_experience', 'highest_elevation_gain_m', 'durasi_terlama_jam'],
    3: ['preferred_trip_type', 'preferred_duration', 'interest_regions', 'max_elevation_gain_preference_m'],
};

function langkahGalat(galat: Record<string, string | undefined>): number | null {
    const kunci = Object.keys(galat).map((k) => k.split('.')[0]);

    for (const langkah of [1, 2, 3]) {
        if ((isianLangkah[langkah] ?? []).some((isian) => kunci.includes(isian))) {
            return langkah;
        }
    }

    return null;
}

/**
 * Onboarding profil pendaki (FR-02; DK-41, DK-42; spec v3 bagian 6.16; prototype 00), juga
 * penyunting pengalaman dan preferensi dari Profil (`?langkah=` membuka langkah tertentu).
 * Setiap "Lanjut" diperiksa server dengan aturan langkah itu saja; langkah terakhir menyimpan
 * dan halaman menyebut akibat langsungnya, "N jalur berlabel Cocok", dihitung server dengan
 * saringan Jelajah. "Nanti saja" menuju Jelajah tanpa menyimpan, jadi label tetap "Belum
 * dinilai" (R-006). Halaman tugas: bilah tab disembunyikan.
 */
export default function ProfileSetup() {
    const { sudahLengkap, langkahAwal, nilai, pilihan, selesai, tautan } = usePage<Props>().props;
    const form = useForm<NilaiOnboarding>(nilai);
    const [langkah, setLangkah] = useState(langkahAwal);
    const [jalur, setJalur] = useState<JalurTerberat | null>(null);
    const bagian = useRef<HTMLElement>(null);
    const pindah = useRef(false);

    // Fokus ke langkah yang baru, atau ke hasil sesudah menyimpan, supaya pembaca layar tahu
    // isinya berganti. Render pertama tidak mencuri fokus.
    useEffect(() => {
        if (pindah.current || selesai !== null) {
            bagian.current?.focus();
        }
        pindah.current = true;
    }, [langkah, selesai]);

    const ubah = (perubahan: Partial<NilaiOnboarding>) => form.setData((d) => ({ ...d, ...perubahan }));

    const kirim = (langkahDiperiksa: number | null) => {
        form.transform((data) => (langkahDiperiksa === null ? data : { ...data, langkah: langkahDiperiksa }));
        form.post(store.url(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (langkahDiperiksa !== null) {
                    setLangkah(langkahDiperiksa + 1);
                }
            },
            onError: (galat) => {
                const tujuan = langkahGalat(galat);
                if (tujuan !== null && tujuan !== langkah) {
                    setLangkah(tujuan);
                }
            },
        });
    };

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        kirim(langkah < JUMLAH_LANGKAH ? langkah : null);
    };

    const galat = form.errors as Partial<Record<string, string>>;
    const galatWilayah = Object.entries(galat).find(([k]) => k.startsWith('interest_regions'))?.[1];
    const judul = JUDUL_LANGKAH[langkah - 1] ?? JUDUL_LANGKAH[0];

    return (
        <div className="py-6">
            <div className="mx-auto max-w-xl px-4 sm:px-6">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Profil Pendaki"
                    aksi={
                        !sudahLengkap && selesai === null ? (
                            <Tautan
                                href={tautan.jelajah}
                                className="inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Nanti saja
                            </Tautan>
                        ) : undefined
                    }
                />

                {selesai !== null ? (
                    <section ref={bagian} tabIndex={-1} aria-labelledby="hasil-profil" className="space-y-4 rounded-utama border border-brand-200 bg-brand-50 p-5 focus:outline-hidden">
                        <h2 id="hasil-profil" className="text-bagian text-primary">
                            Profil pendaki tersimpan
                        </h2>
                        <p data-angka className="text-judul-kartu text-primary">
                            {selesai.cocok > 0 ? `Dengan jawaban ini, ${selesai.cocok} jalur berlabel Cocok.` : 'Dengan jawaban ini, belum ada jalur berlabel Cocok.'}
                        </p>
                        <p className="text-kecil text-secondary">
                            {selesai.cocok > 0
                                ? 'Label menilai kecocokan dasar dengan jawaban Anda. Status resmi dan kondisi jalur tetap diperiksa sebelum berangkat.'
                                : 'Jalur berlabel Perlu persiapan menyebut apa yang perlu disiapkan lebih dulu.'}
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Button href={selesai.cocok > 0 ? tautan.cocok : tautan.jelajah}>{selesai.cocok > 0 ? `Lihat ${selesai.cocok} jalur Cocok` : 'Buka Jelajah'}</Button>
                            <Button variant="secondary" href={tautan.profil}>
                                Ke Profil
                            </Button>
                        </div>
                    </section>
                ) : (
                    <form id="formulir-profil" onSubmit={simpan} noValidate>
                        <p data-angka className="text-meta font-bold text-secondary">
                            Langkah {langkah} dari {JUMLAH_LANGKAH}
                        </p>
                        <div aria-hidden="true" className="mt-2 flex gap-1">
                            {Array.from({ length: JUMLAH_LANGKAH }, (_, i) => (
                                <span key={i} className={kelas('h-1.5 flex-1 rounded-full', i < langkah ? 'bg-brand-700' : 'bg-surface-sunken')} />
                            ))}
                        </div>

                        <section ref={bagian} tabIndex={-1} aria-labelledby="judul-langkah" className="mt-6 space-y-5 focus:outline-hidden">
                            <div>
                                <p className="text-meta font-bold text-brand-ink">{judul.kecil}</p>
                                <h2 id="judul-langkah" className="mt-1 text-judul-kartu text-primary">
                                    {judul.judul}
                                </h2>
                                {judul.penjelas ? <p className="mt-2 text-kecil text-secondary">{judul.penjelas}</p> : null}
                            </div>

                            {langkah === 1 ? (
                                <div>
                                    <PilihanKartu
                                        nama="experience_level"
                                        label="Tingkat pengalaman"
                                        labelTersembunyi
                                        pilihan={pilihan.tingkat}
                                        nilai={form.data.experience_level}
                                        onUbah={(v) => ubah({ experience_level: v })}
                                        penjelas={galat.experience_level ? 'tingkat-galat' : undefined}
                                    />
                                    <InputError id="tingkat-galat" messages={galat.experience_level} className="mt-2" />
                                </div>
                            ) : null}

                            {langkah === 2 ? <PengalamanTerberat data={form.data} ubah={ubah} jalur={jalur} setJalur={setJalur} pilihan={pilihan} galat={galat} /> : null}

                            {langkah === 3 ? (
                                <div className="space-y-6">
                                    <div>
                                        <PilihanKartu
                                            nama="preferred_trip_type"
                                            label="Jenis trip yang paling sering Anda rencanakan"
                                            pilihan={pilihan.jenisTrip}
                                            nilai={form.data.preferred_trip_type}
                                            onUbah={(v) => ubah({ preferred_trip_type: v })}
                                            penjelas={galat.preferred_trip_type ? 'jenis-galat' : undefined}
                                        />
                                        <InputError id="jenis-galat" messages={galat.preferred_trip_type} className="mt-2" />
                                    </div>

                                    <div>
                                        <PilihanKartu
                                            nama="preferred_duration"
                                            label="Lama pendakian yang Anda sukai"
                                            bentuk="chip"
                                            pilihan={pilihan.durasi}
                                            nilai={form.data.preferred_duration}
                                            onUbah={(v) => ubah({ preferred_duration: v })}
                                            penjelas={galat.preferred_duration ? 'durasi-galat' : undefined}
                                        />
                                        <InputError id="durasi-galat" messages={galat.preferred_duration} className="mt-2" />
                                    </div>

                                    <fieldset aria-describedby={galatWilayah ? 'wilayah-hint wilayah-galat' : 'wilayah-hint'}>
                                        <legend className="text-isi font-bold text-primary">Wilayah yang Anda minati</legend>
                                        <p id="wilayah-hint" className="mt-1 text-kecil text-secondary">
                                            Boleh lebih dari satu, atau dilewati. Wilayah tidak mengubah label kecocokan.
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-x-1">
                                            {pilihan.wilayah.map((w) => (
                                                <ChipPilihan
                                                    key={w.nilai}
                                                    dipilih={form.data.interest_regions.includes(w.nilai)}
                                                    onUbah={(dipilih) =>
                                                        ubah({ interest_regions: dipilih ? [...form.data.interest_regions, w.nilai] : form.data.interest_regions.filter((v) => v !== w.nilai) })
                                                    }
                                                >
                                                    {w.label}
                                                </ChipPilihan>
                                            ))}
                                        </div>
                                        <InputError id="wilayah-galat" messages={galatWilayah} className="mt-2" />
                                    </fieldset>

                                    <div>
                                        <Label htmlFor="batas-tanjakan">Tanjakan terbesar yang masih nyaman untuk Anda (meter)</Label>
                                        <TextInput
                                            id="batas-tanjakan"
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            max={9000}
                                            value={form.data.max_elevation_gain_preference_m}
                                            onChange={(e) => ubah({ max_elevation_gain_preference_m: e.target.value })}
                                            aria-invalid={galat.max_elevation_gain_preference_m ? true : undefined}
                                            aria-describedby={galat.max_elevation_gain_preference_m ? 'batas-tanjakan-hint batas-tanjakan-galat' : 'batas-tanjakan-hint'}
                                        />
                                        <p id="batas-tanjakan-hint" className="mt-1 text-kecil text-secondary">
                                            Opsional. Dipakai menilai kecocokan bila tanjakan terbesar Anda belum diisi.
                                        </p>
                                        <InputError id="batas-tanjakan-galat" messages={galat.max_elevation_gain_preference_m} className="mt-2" />
                                    </div>
                                </div>
                            ) : null}
                        </section>

                        <p className="mt-8 text-kecil text-secondary">
                            Yang Anda isi adalah masukan, bukan sertifikasi. Riwayat pendakian tidak menaikkan tingkat Anda secara otomatis. Ubah kapan saja di Profil.
                        </p>

                        <BarAksi label="Langkah profil pendaki">
                            {langkah > 1 ? (
                                <Button variant="secondary" disabled={form.processing} onClick={() => setLangkah((l) => Math.max(1, l - 1))}>
                                    Kembali
                                </Button>
                            ) : null}
                            <Button type="submit" processing={form.processing} className="flex-1">
                                {langkah < JUMLAH_LANGKAH ? 'Lanjut' : 'Simpan profil'}
                            </Button>
                        </BarAksi>
                    </form>
                )}
            </div>
        </div>
    );
}

ProfileSetup.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
