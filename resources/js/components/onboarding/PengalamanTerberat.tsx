import { useEffect, useRef } from 'react';
import { PemilihJalur } from '@/components/trails/PemilihJalur';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Stepper } from '@/components/ui/Stepper';
import { PilihanKartu } from './PilihanKartu';
import type { CaraPengalaman, JalurTerberat, NilaiOnboarding, PilihanOnboarding } from './types';

interface PengalamanTerberatProps {
    data: NilaiOnboarding;
    ubah: (perubahan: Partial<NilaiOnboarding>) => void;
    jalur: JalurTerberat | null;
    setJalur: (jalur: JalurTerberat | null) => void;
    pilihan: PilihanOnboarding;
    galat: Partial<Record<string, string>>;
}

const kalimatCara: Record<CaraPengalaman, string> = {
    KATALOG: 'Cari jalur terberat yang pernah Anda selesaikan.',
    MANUAL: 'Isi pengalaman Anda sendiri.',
    BELUM: 'Anda belum pernah mendaki sampai selesai.',
};

/**
 * Langkah 2 onboarding (DK-41; spec v3 bagian 6.16): jalur terberat dari katalog dengan nilai
 * turunan untuk dikonfirmasi (tanjakan, durasi, medan, navigasi), isian sendiri bila jalurnya
 * tidak ada di daftar atau nilainya perlu diubah, atau "belum pernah mendaki". Nilai turunan
 * ditampilkan dari server; yang disimpan pun diturunkan server dari jalur yang sama. Berganti
 * cara memindah fokus ke kalimat pembuka cara itu, jadi pengguna papan ketik tidak kehilangan
 * tempat ketika tombol yang ditekannya hilang.
 */
export function PengalamanTerberat({ data, ubah, jalur, setJalur, pilihan, galat }: PengalamanTerberatProps) {
    const cara = data.cara_pengalaman;
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById('pengalaman-cara')?.focus();
    }, [cara]);

    const keCara = (baru: CaraPengalaman) => ubah({ cara_pengalaman: baru });

    const pilihJalur = (j: JalurTerberat | null) => {
        setJalur(j);

        if (j === null) {
            ubah({ jalur_terberat_id: '' });

            return;
        }

        const durasi = j.turunan.durasi_menit;
        ubah({
            jalur_terberat_id: j.id,
            durasi_terlama_jam: data.durasi_terlama_jam === '' && durasi !== null && durasi <= 24 * 60 ? String(Math.ceil(durasi / 60)) : data.durasi_terlama_jam,
        });
    };

    // "Ubah nilai": isian sendiri dimulai dari nilai turunan jalur yang dipilih.
    const ubahNilai = () => {
        if (jalur === null) {
            keCara('MANUAL');

            return;
        }

        ubah({
            cara_pengalaman: 'MANUAL',
            terrain_experience: jalur.turunan.medan,
            navigation_experience: jalur.turunan.navigasi ?? '',
            highest_elevation_gain_m: jalur.turunan.tanjakan_m ?? '',
        });
    };

    const pindahCara = (
        <div role="group" aria-label="Cara lain mengisi pengalaman" aria-describedby={galat.cara_pengalaman ? 'pengalaman-cara-galat' : undefined} className="flex flex-wrap gap-x-2">
            {cara !== 'KATALOG' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('KATALOG')}>
                    {cara === 'BELUM' ? 'Saya pernah mendaki' : 'Cari di katalog'}
                </Button>
            ) : null}
            {cara === 'KATALOG' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('MANUAL')}>
                    Jalurnya tidak ada di daftar
                </Button>
            ) : null}
            {cara !== 'BELUM' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('BELUM')}>
                    Saya belum pernah mendaki
                </Button>
            ) : null}
        </div>
    );

    return (
        <div className="space-y-5">
            <p id="pengalaman-cara" tabIndex={-1} className="text-isi font-semibold text-primary focus:outline-hidden">
                {kalimatCara[cara]}
            </p>
            <InputError id="pengalaman-cara-galat" messages={galat.cara_pengalaman} />

            {cara === 'KATALOG' ? (
                <PemilihJalur<JalurTerberat> label="Jalur terberat" dipilih={jalur} onPilih={pilihJalur} galat={galat.jalur_terberat_id}>
                    {jalur ? (
                        <>
                            <dl className="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 text-kecil min-[360px]:grid-cols-[auto_minmax(0,1fr)]">
                                <dt className="text-secondary">Tanjakan</dt>
                                <dd data-angka className="font-semibold text-primary">
                                    {jalur.tulisan.tanjakan}
                                </dd>
                                <dt className="text-secondary">Durasi</dt>
                                <dd data-angka className="font-semibold text-primary">
                                    {jalur.tulisan.durasi}
                                </dd>
                                <dt className="text-secondary">Medan</dt>
                                <dd className="font-semibold text-primary">{jalur.tulisan.medan}</dd>
                                <dt className="text-secondary">Navigasi</dt>
                                <dd className="font-semibold text-primary">{jalur.tulisan.navigasi}</dd>
                            </dl>
                            <p className="mt-2 text-kecil text-secondary">
                                Nilai ini dicatat sebagai pengalaman Anda. Bila tidak sesuai, misalnya karena Anda dipandu, ubah sendiri.
                            </p>
                            <Button variant="ghost" size="sm" className="-ml-4" onClick={ubahNilai}>
                                Ubah nilai
                            </Button>
                        </>
                    ) : null}
                </PemilihJalur>
            ) : null}

            {cara === 'MANUAL' ? (
                <div className="space-y-5">
                    <div>
                        <Label htmlFor="pengalaman-tanjakan">Tanjakan terbesar dalam satu pendakian (meter)</Label>
                        <TextInput
                            id="pengalaman-tanjakan"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={9000}
                            value={data.highest_elevation_gain_m}
                            onChange={(e) => ubah({ highest_elevation_gain_m: e.target.value })}
                            aria-invalid={galat.highest_elevation_gain_m ? true : undefined}
                            aria-describedby={galat.highest_elevation_gain_m ? 'pengalaman-tanjakan-hint pengalaman-tanjakan-galat' : 'pengalaman-tanjakan-hint'}
                        />
                        <p id="pengalaman-tanjakan-hint" className="mt-1 text-kecil text-secondary">
                            Boleh dikosongkan bila tidak tahu; yang kosong tidak dianggap kecil.
                        </p>
                        <InputError id="pengalaman-tanjakan-galat" messages={galat.highest_elevation_gain_m} className="mt-2" />
                    </div>

                    <fieldset aria-describedby={galat.terrain_experience ? 'pengalaman-medan-galat' : undefined}>
                        <legend className="text-isi font-bold text-primary">Medan yang pernah Anda lalui</legend>
                        <div className="mt-2 flex flex-wrap gap-x-1">
                            {pilihan.medan.map((m) => (
                                <ChipPilihan
                                    key={m.nilai}
                                    dipilih={data.terrain_experience.includes(m.nilai)}
                                    onUbah={(dipilih) =>
                                        ubah({ terrain_experience: dipilih ? [...data.terrain_experience, m.nilai] : data.terrain_experience.filter((v) => v !== m.nilai) })
                                    }
                                >
                                    {m.label}
                                </ChipPilihan>
                            ))}
                        </div>
                        <InputError id="pengalaman-medan-galat" messages={galat.terrain_experience} className="mt-2" />
                    </fieldset>

                    <div>
                        <PilihanKartu
                            nama="navigation_experience"
                            label="Pengalaman navigasi"
                            pilihan={pilihan.navigasi}
                            nilai={data.navigation_experience}
                            onUbah={(nilai) => ubah({ navigation_experience: nilai })}
                            penjelas={galat.navigation_experience ? 'pengalaman-navigasi-galat' : undefined}
                        />
                        <InputError id="pengalaman-navigasi-galat" messages={galat.navigation_experience} className="mt-2" />
                    </div>
                </div>
            ) : null}

            {cara === 'BELUM' ? (
                <div className="rounded-tombol border border-subtle bg-surface p-4 text-kecil text-primary">
                    <p>Kami mencatat nol pendakian dan pengalaman navigasi "Belum ada". Itu jawaban yang Anda berikan, bukan nilai yang ditebak.</p>
                    <p className="mt-2 text-secondary">Kecocokan jalur dinilai dari tingkat yang Anda pilih. Ubah kapan saja di Profil sesudah pendakian pertama Anda.</p>
                </div>
            ) : null}

            {cara !== 'BELUM' ? (
                <div className="space-y-5 border-t border-subtle pt-5">
                    <div>
                        <Stepper
                            label="Pendakian yang sudah selesai"
                            keterangan={cara === 'KATALOG' ? 'Termasuk jalur terberat itu.' : 'Kira-kira saja.'}
                            nilai={data.completed_hikes_count}
                            min={1}
                            max={2000}
                            onUbah={(nilai) => ubah({ completed_hikes_count: nilai })}
                            penjelas={galat.completed_hikes_count ? 'pengalaman-jumlah-galat' : undefined}
                        />
                        <InputError id="pengalaman-jumlah-galat" messages={galat.completed_hikes_count} className="mt-2" />
                    </div>
                    <div>
                        <Label htmlFor="pengalaman-durasi">Durasi mendaki terlama dalam satu hari (jam)</Label>
                        <TextInput
                            id="pengalaman-durasi"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={24}
                            value={data.durasi_terlama_jam}
                            onChange={(e) => ubah({ durasi_terlama_jam: e.target.value })}
                            aria-invalid={galat.durasi_terlama_jam ? true : undefined}
                            aria-describedby={galat.durasi_terlama_jam ? 'pengalaman-durasi-hint pengalaman-durasi-galat' : 'pengalaman-durasi-hint'}
                        />
                        <p id="pengalaman-durasi-hint" className="mt-1 text-kecil text-secondary">
                            Tanpa bermalam. Boleh dikosongkan.
                        </p>
                        <InputError id="pengalaman-durasi-galat" messages={galat.durasi_terlama_jam} className="mt-2" />
                    </div>
                </div>
            ) : null}

            {pindahCara}
        </div>
    );
}

