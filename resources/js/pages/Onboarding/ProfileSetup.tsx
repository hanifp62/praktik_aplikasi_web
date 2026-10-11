import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { CheckboxGroup, SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store } from '@/routes/onboarding';
import { create as goalsCreate } from '@/routes/goals';
import type { PropsBersama } from '@/types';

interface Nilai {
    experience_level: string;
    region_preference: string;
    bio: string;
    completed_hikes_count: number | string;
    terrain_experience: string[];
    navigation_experience: string;
    longest_hike_duration_minutes: number | string;
    highest_elevation_gain_m: number | string;
    preferred_duration: string;
    preferred_trip_type: string;
    preferred_challenge: string;
    max_elevation_gain_preference_m: number | string;
}

interface Props extends PropsBersama {
    nilai: Nilai;
    ringkasan: Array<{ label: string; nilai: string }> | null;
    pilihan: {
        experienceLevels: Opsi[];
        navigationLevels: Opsi[];
        terrainOptions: Opsi[];
        durations: Opsi[];
        tripTypes: Opsi[];
        challenges: Opsi[];
    };
}

const langkahLabel = ['Pengalaman', 'Rekam jejak', 'Preferensi'] as const;

/** Isian milik tiap langkah, untuk kembali ke langkah yang galatnya dilaporkan server. */
const isianLangkah: Record<number, string[]> = {
    1: ['experience_level', 'region_preference', 'bio'],
    2: ['completed_hikes_count', 'terrain_experience', 'navigation_experience', 'longest_hike_duration_minutes', 'highest_elevation_gain_m'],
    3: ['preferred_duration', 'preferred_trip_type', 'preferred_challenge', 'max_elevation_gain_preference_m'],
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
 * FR-02 profil pendaki. Pengalaman yang diisi adalah masukan rekomendasi, bukan
 * sertifikasi (PRD §22). Tiga langkah; setiap "Lanjut" diperiksa server dengan aturan
 * langkah itu saja, seperti komponen lama, sehingga pesan galatnya sama. Pendaki yang
 * profilnya sudah lengkap melihat ringkasannya lebih dulu dan membuka formulir bila
 * ingin mengubahnya.
 */
export default function ProfileSetup() {
    const { nilai, ringkasan, pilihan, flash } = usePage<Props>().props;
    const form = useForm<Nilai>(nilai);
    const [langkah, setLangkah] = useState(1);
    const [menyunting, setMenyunting] = useState(ringkasan === null);
    const bagian = useRef<HTMLFieldSetElement>(null);
    const pindah = useRef(false);

    // Fokus ke langkah yang baru supaya pembaca layar tahu isinya berganti.
    useEffect(() => {
        if (pindah.current) {
            bagian.current?.focus();
        }
        pindah.current = true;
    }, [langkah]);

    // Tombol "Ubah profil pendaki" hilang begitu formulir terbuka; fokus dipindah ke
    // formulirnya supaya pengguna keyboard tidak terlempar ke awal halaman.
    const bukaFormulir = () => {
        setMenyunting(true);
        requestAnimationFrame(() => bagian.current?.focus());
    };

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
        kirim(langkah < 3 ? langkah : null);
    };

    const galat = form.errors as Partial<Record<string, string>>;
    const galatMedan = Object.entries(galat).filter(([k]) => k.startsWith('terrain_experience')).map(([, v]) => v as string);

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Profil Pendaki"
                    description="Data ini dipakai untuk menilai kecocokan jalur dengan Anda. Pengalaman yang Anda isi adalah masukan untuk rekomendasi, bukan sertifikasi kemampuan."
                />

                {flash.status ? <Alert className="mb-6">{flash.status}</Alert> : null}

                {ringkasan !== null ? (
                    <section aria-labelledby="ringkasan-profil" className="mb-6 rounded-lg border border-subtle bg-surface p-6">
                        <h2 id="ringkasan-profil" className="text-lg font-semibold text-primary">Ringkasan profil Anda</h2>
                        <dl className="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            {ringkasan.map((r) => (
                                <div key={r.label}>
                                    <dt className="text-muted">{r.label}</dt>
                                    <dd className="font-medium text-primary">{r.nilai}</dd>
                                </div>
                            ))}
                        </dl>
                        {!menyunting ? (
                            <div className="mt-5 flex flex-wrap gap-3">
                                <Button href={goalsCreate.url()}>Buat rencana pendakian</Button>
                                <Button variant="secondary" onClick={bukaFormulir}>
                                    Ubah profil pendaki
                                </Button>
                            </div>
                        ) : null}
                    </section>
                ) : null}

                {menyunting ? (
                    <>
                        <nav aria-label="Langkah pengisian profil" className="mb-6">
                            <ol className="flex gap-2 text-sm">
                                {langkahLabel.map((label, i) => (
                                    <li key={label} className="flex-1">
                                        <span
                                            aria-current={langkah === i + 1 ? 'step' : undefined}
                                            className={kelas(
                                                'block rounded-control border px-3 py-2 text-center',
                                                langkah === i + 1 ? 'border-brand-600 bg-brand-50 font-medium text-brand-900' : 'border-subtle text-secondary',
                                            )}
                                        >
                                            {i + 1}. {label}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        </nav>

                        <form id="formulir-profil" onSubmit={simpan} noValidate className="space-y-6 rounded-lg bg-surface p-6">
                            {langkah === 1 ? (
                                <fieldset ref={bagian} tabIndex={-1} className="space-y-4 focus:outline-none">
                                    <legend className="text-base font-medium text-primary">Tingkat pengalaman</legend>

                                    <SelectField
                                        name="experience_level"
                                        label="Tingkat pengalaman"
                                        required
                                        placeholder="Pilih tingkat pengalaman"
                                        options={pilihan.experienceLevels}
                                        value={form.data.experience_level}
                                        onChange={(ev) => form.setData('experience_level', ev.target.value)}
                                        error={galat.experience_level}
                                    />
                                    <FormField
                                        name="region_preference"
                                        label="Preferensi wilayah (opsional)"
                                        placeholder="Jawa Tengah"
                                        value={form.data.region_preference}
                                        onChange={(ev) => form.setData('region_preference', ev.target.value)}
                                        error={galat.region_preference}
                                    />
                                    <TextareaField
                                        name="bio"
                                        label="Catatan singkat (opsional)"
                                        value={form.data.bio}
                                        onChange={(ev) => form.setData('bio', ev.target.value)}
                                        error={galat.bio}
                                    />
                                </fieldset>
                            ) : langkah === 2 ? (
                                <fieldset ref={bagian} tabIndex={-1} className="space-y-4 focus:outline-none">
                                    <legend className="text-base font-medium text-primary">Rekam jejak pendakian</legend>
                                    <p className="text-sm text-secondary">
                                        Riwayat pendakian menjadi konteks tambahan. Menyelesaikan satu jalur sulit tidak otomatis menaikkan tingkat pengalaman Anda.
                                    </p>

                                    <FormField
                                        name="completed_hikes_count"
                                        type="number"
                                        min={0}
                                        label="Jumlah pendakian yang pernah diselesaikan"
                                        value={form.data.completed_hikes_count}
                                        onChange={(ev) => form.setData('completed_hikes_count', ev.target.value)}
                                        error={galat.completed_hikes_count}
                                    />
                                    <CheckboxGroup
                                        name="terrain_experience"
                                        label="Medan yang pernah dilalui"
                                        options={pilihan.terrainOptions}
                                        value={form.data.terrain_experience}
                                        onChange={(v) => form.setData('terrain_experience', v)}
                                        error={galatMedan}
                                    />
                                    <SelectField
                                        name="navigation_experience"
                                        label="Pengalaman navigasi"
                                        required
                                        placeholder="Pilih pengalaman navigasi"
                                        options={pilihan.navigationLevels}
                                        value={form.data.navigation_experience}
                                        onChange={(ev) => form.setData('navigation_experience', ev.target.value)}
                                        error={galat.navigation_experience}
                                    />
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <FormField
                                            name="longest_hike_duration_minutes"
                                            type="number"
                                            min={0}
                                            label="Durasi pendakian terlama (menit)"
                                            value={form.data.longest_hike_duration_minutes}
                                            onChange={(ev) => form.setData('longest_hike_duration_minutes', ev.target.value)}
                                            error={galat.longest_hike_duration_minutes}
                                        />
                                        <FormField
                                            name="highest_elevation_gain_m"
                                            type="number"
                                            min={0}
                                            label="Elevation gain tertinggi (meter)"
                                            value={form.data.highest_elevation_gain_m}
                                            onChange={(ev) => form.setData('highest_elevation_gain_m', ev.target.value)}
                                            error={galat.highest_elevation_gain_m}
                                        />
                                    </div>
                                </fieldset>
                            ) : (
                                <fieldset ref={bagian} tabIndex={-1} className="space-y-4 focus:outline-none">
                                    <legend className="text-base font-medium text-primary">Preferensi perjalanan</legend>

                                    <SelectField
                                        name="preferred_duration"
                                        label="Durasi yang Anda sukai"
                                        required
                                        placeholder="Pilih durasi"
                                        options={pilihan.durations}
                                        value={form.data.preferred_duration}
                                        onChange={(ev) => form.setData('preferred_duration', ev.target.value)}
                                        error={galat.preferred_duration}
                                    />
                                    <SelectField
                                        name="preferred_trip_type"
                                        label="Tipe perjalanan"
                                        required
                                        placeholder="Pilih tipe perjalanan"
                                        options={pilihan.tripTypes}
                                        value={form.data.preferred_trip_type}
                                        onChange={(ev) => form.setData('preferred_trip_type', ev.target.value)}
                                        error={galat.preferred_trip_type}
                                    />
                                    <SelectField
                                        name="preferred_challenge"
                                        label="Tingkat tantangan yang dicari (opsional)"
                                        placeholder="Tidak ada preferensi"
                                        options={pilihan.challenges}
                                        value={form.data.preferred_challenge}
                                        onChange={(ev) => form.setData('preferred_challenge', ev.target.value)}
                                        error={galat.preferred_challenge}
                                    />
                                    <FormField
                                        name="max_elevation_gain_preference_m"
                                        type="number"
                                        min={0}
                                        label="Batas elevation gain yang nyaman (meter, opsional)"
                                        value={form.data.max_elevation_gain_preference_m}
                                        onChange={(ev) => form.setData('max_elevation_gain_preference_m', ev.target.value)}
                                        error={galat.max_elevation_gain_preference_m}
                                    />
                                </fieldset>
                            )}

                            <div className="flex items-center justify-between gap-3 border-t border-subtle pt-4">
                                <Button variant="secondary" disabled={langkah === 1 || form.processing} onClick={() => setLangkah((l) => Math.max(1, l - 1))}>
                                    Kembali
                                </Button>
                                <Button type="submit" processing={form.processing}>
                                    {langkah < 3 ? 'Lanjut' : 'Simpan profil'}
                                </Button>
                            </div>
                        </form>
                    </>
                ) : null}
            </div>
        </div>
    );
}

ProfileSetup.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
