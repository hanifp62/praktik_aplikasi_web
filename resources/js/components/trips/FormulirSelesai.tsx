import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, Textarea } from '@/components/ui/Field';
import { fokusDiDalam, fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { kelas } from '@/lib/kelas';
import { tandaiSesiBerakhir } from '@/luring/simpan';
import type { PropsBersama } from '@/types';
import type { Opsi } from './types';

/** Kalimat dan tautan bila Selesai tertahan aksi lapangan yang belum terkirim (ruling W6-FC). */
export interface SelesaiTertahan {
    kalimat: string;
    tautan?: { href: string; label: string };
}

interface FormulirSelesaiProps {
    /** Trip yang diselesaikan; rekaman sesi paketnya di perangkat ini ditandai berakhir sesudah berhasil (ruling AF-6). */
    tripId: number;
    url: string;
    hasil: Opsi[];
    alasan: Opsi[];
    /** Awalan id supaya dua formulir di halaman yang berbeda tidak bertabrakan. */
    id: string;
    /**
     * Pemeriksaan sebelum kiriman (ruling W6-FC): kalimat bila aksi lapangan trip ini masih
     * menunggu di perangkat, null bila Selesai boleh dikirim. Tanpa prop ini Selesai langsung
     * dikirim; pemeriksaan yang melempar juga dianggap boleh (perilaku sebelum ruling).
     */
    tahan?: () => Promise<SelesaiTertahan | null>;
    /**
     * Pemantau keadaan tertahan (ruling AF-8): dipasang selama kalimat tertahan tampil, memanggil
     * `perbarui` dengan keputusan baru setiap kali antrean berubah atau terkirim, dan mengembalikan
     * fungsi pelepas. Hanya membaca antrean, tidak mengirim apa pun.
     */
    pantauTahan?: (perbarui: (hasil: SelesaiTertahan | null) => void) => () => void;
}

const tautanTeks = kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat);

/**
 * Menyelesaikan pendakian (spec v3 bagian 6.13, DK-50): hasilnya, alasan bila berbalik di
 * jalur, dan catatan pribadi. Alasan opsional dan hanya ditanyakan untuk "Dibatalkan di jalur":
 * berbalik adalah keputusan yang benar, bukan kegagalan yang perlu dijelaskan.
 *
 * Pilihan hasil bertumpuk satu kolom, bukan kontrol segmen tiga kolom (ruling W6-5d/M-2
 * putaran perbaikan 1): tiga labelnya ("Selesai sampai target", "Selesai sebagian",
 * "Dibatalkan di jalur") memotong kata di tengah pada 320 dan 360 px di dalam KontrolSegmen,
 * dan di ruang kerja kontrolnya menonjol keluar kartu. KontrolSegmen sendiri tidak diubah
 * (dipakai bersama; label flex-1 dan larangan min-w-0 di sana dijaga
 * DesignPrimitivesTest::test_the_segmented_control_label_locks_both_touch_target_axes), jadi
 * perbaikannya di sini saja: setiap pilihan satu baris penuh lewat radio asli di dalam
 * fieldset, sehingga label boleh membungkus ke baris kedua tetapi tidak pernah terpotong atau
 * menyempit.
 *
 * Galat jaringan saat Simpan (ruling W6-5h/I-A dan c1 putaran perbaikan 2). Kalimatnya pola
 * W6-4k ("Sambungan terputus..."), bukan "Belum tersimpan" (putaran 1): perangkat bisa saja
 * daring dan permintaannya sudah commit di server sebelum jawabannya hilang, jadi "belum
 * tersimpan" salah pada cabang itu; "belum pasti hasilnya tersimpan" benar di kedua cabang.
 * Pesan berdiri tepat di atas tombol Simpan, dalam blok yang sama, bukan di puncak formulir:
 * putaran 1 meletakkannya di sana dan keluar layar pada 320x720 dan 375x667 di mode lapangan
 * serta 360x800 di ruang kerja begitu isi lembar lebih tinggi dari wadahnya (I-A). Fokus
 * kembali ke tombol Simpan di `onFinish`, sesudah tombolnya aktif lagi (pola W6-4q2
 * Track/Index.tsx): memanggil fokus lebih awal, saat tombol masih `disabled` karena
 * `form.processing`, tidak berefek, dan `useFokusSesudahRender` menangguhkannya satu render
 * supaya DOM sempat melepas `disabled` dulu.
 *
 * Penangan galat jaringan TIDAK mengembalikan `false`: banner global PesanJaringan boleh
 * tetap tampil di ruang kerja (AppLayout memasangnya; mode lapangan memakai TataLetakPendakian
 * yang tidak memasangnya, jadi di sana banner global tidak pernah ada, dengan atau tanpa
 * `false`, dan menahannya tidak berguna). Keduanya dapat tampil bersamaan di ruang kerja,
 * tetapi kalimatnya berbeda (global: "Aksi terakhir Anda mungkin belum tercatat."; lokal:
 * kalimat lengkap di atas tombol Simpan), sehingga tidak ada dua wilayah yang membacakan
 * kalimat SAMA secara bersamaan (keputusan implementer, ruling meminta ditulis).
 *
 * Sesudah kiriman selesai, bila hasilnya penolakan bisnis (trip sudah berubah status di tab
 * lain), server tetap redirect ke trips.show dengan flash penolakan-trip, bukan galat
 * validasi: `onSuccess` membaca `page.props.penolakan` dari props halaman yang baru, lalu
 * `onFinish` memindah fokus satu kali ke wilayah penolakan (id "penolakan-trip" di Show.tsx)
 * lewat id, bukan efek saat Show dipasang (ruling W6-5i): efek macam itu hanya berjalan sekali
 * saat mount, dan `preserveState` bawaan Inertia membuat Show TIDAK dipasang ulang sesudah
 * redirect ke rute yang sama, jadi efek itu tidak pernah berjalan lagi untuk aksi di halaman
 * yang sama. Mekanisme berbasis id ini terbukti bekerja selama formulir ini masih di halaman
 * yang sama (ruang kerja; penolakan tidak mengubah trip.berlangsung, jadi kartu ini tetap
 * terpasang). Dari mode lapangan, penolakan selesai tetap me-redirect ke trips.show, pindah
 * komponen sepenuhnya (Hike.tsx ke Show.tsx); mekanisme ini tidak dijamin berjalan pada
 * peralihan itu karena instance yang memanggilnya ikut lenyap, di luar lingkup "aksi di
 * halaman yang sama" ruling ini.
 *
 * Selesai menunggu aksi lapangan trip ini (ruling W6-FC): bila `tahan` diberikan, ia dijalankan
 * lebih dulu, dan selama ia menyebut aksi yang belum terkirim, Selesai tidak dikirim. Kalimatnya
 * ditulis di wilayah status yang sama di atas tombol Simpan, dengan tautan bila ada, dan fokus
 * kembali ke tombol Simpan sesudah tombolnya aktif lagi. Satu pemeriksaan sekaligus: ketukan
 * selama pemeriksaan atau kiriman berjalan diabaikan.
 *
 * Galat HTTP tanpa header Inertia (ruling W6-FE, M-2 tinjauan akhir W6), misalnya 419 sesudah
 * sesi berakhir berjam-jam tanpa sinyal, ditulis di wilayah status yang sama lewat
 * `onHttpException` yang mengembalikan false, sehingga dialog galat bawaan Inertia tidak terbuka
 * (pola W6-11c). Kalimatnya "belum pasti", karena server bisa sudah menyimpan sebelum jawaban yang
 * tidak dapat dibaca itu (daftar periksa butir 2).
 *
 * Kalimat tertahan dinilai ulang tanpa menunggu tekan Simpan berikutnya (ruling AF-8, audit E2E
 * M-6): selama kalimat itu tampil, `pantauTahan` dari pemanggil dipasang dan setiap keputusan
 * barunya menggantikan kalimat itu, jadi kalimat hilang begitu aksi trip ini tidak lagi menunggu.
 * Pemantau dibaca lewat ref, sehingga fungsi baru di setiap render pemanggil tidak memasang ulang
 * pemantau; pemasangannya hanya mengikuti ada-tidaknya kalimat. Selama pemeriksaan Simpan sendiri
 * berjalan, keputusan pemantau diabaikan.
 *
 * Selesai yang berhasil (bukan penolakan) menandai rekaman sesi paket trip ini di perangkat ini
 * berakhir (ruling AF-6, tandaiSesiBerakhir), dari mode lapangan maupun ruang kerja, supaya mode
 * lapangan dari paket tidak lagi menulis pendakian ini sebagai berjalan. Kegagalan menandai tidak
 * mengubah apa pun yang sudah disimpan server.
 */
export function FormulirSelesai({ tripId, url, hasil, alasan, id, tahan, pantauTahan }: FormulirSelesaiProps) {
    const form = useForm<{ completion_state: string; alasan: string; personal_notes: string }>({ completion_state: 'COMPLETED', alasan: '', personal_notes: '' });
    const kunci = usePage<PropsBersama>().props.perangkat?.kunci ?? null;
    const berbalik = form.data.completion_state === 'ABANDONED';
    const [galatJaringan, setGalatJaringan] = useState(false);
    const [galatHttp, setGalatHttp] = useState(false);
    const [tertahan, setTertahan] = useState<SelesaiTertahan | null>(null);
    const [memeriksa, setMemeriksa] = useState(false);
    const memeriksaRef = useRef(false);
    const pantauTerakhir = useRef(pantauTahan);
    const fokusKe = useFokusSesudahRender();
    const idSimpan = `${id}-simpan`;
    const adaTertahan = tertahan !== null;

    useEffect(() => {
        pantauTerakhir.current = pantauTahan;
    });

    useEffect(() => {
        const pantau = pantauTerakhir.current;

        if (!adaTertahan || !pantau) {
            return;
        }

        let terpasang = true;
        const lepas = pantau((hasilBaru) => {
            if (terpasang && !memeriksaRef.current) {
                setTertahan(hasilBaru);
            }
        });

        return () => {
            terpasang = false;
            lepas();
        };
    }, [adaTertahan]);

    const kirim = async (e: FormEvent) => {
        e.preventDefault();

        if (memeriksaRef.current || form.processing) {
            return;
        }

        setGalatJaringan(false);
        setGalatHttp(false);
        setTertahan(null);

        if (tahan) {
            memeriksaRef.current = true;
            setMemeriksa(true);

            let hasilPeriksa: SelesaiTertahan | null = null;

            try {
                hasilPeriksa = await tahan();
            } catch {
                hasilPeriksa = null;
            }

            memeriksaRef.current = false;
            setMemeriksa(false);

            if (hasilPeriksa !== null) {
                setTertahan(hasilPeriksa);
                fokusKe(idSimpan);

                return;
            }
        }

        form.transform((data) => ({ ...data, alasan: berbalik && data.alasan !== '' ? data.alasan : null }));

        let ditolak = false;

        form.post(url, {
            preserveScroll: true,
            onNetworkError: () => {
                setGalatJaringan(true);
            },
            onHttpException: () => {
                setGalatHttp(true);

                return false;
            },
            onSuccess: (page) => {
                ditolak = Boolean(page.props.penolakan);

                if (!ditolak) {
                    void tandaiSesiBerakhir(kunci, tripId, { jenis: 'selesai', waktu: new Date().toISOString() }).catch(() => undefined);
                }
            },
            onFinish: () => {
                fokusKe(ditolak ? 'penolakan-trip' : idSimpan);
            },
        });
    };

    return (
        <form onSubmit={kirim} className="space-y-4">
            <fieldset aria-describedby={form.errors.completion_state ? `${id}-hasil-galat` : undefined}>
                <legend className="mb-2 text-isi font-bold text-primary">Hasil pendakian</legend>
                <div className="flex flex-col gap-1">
                    {hasil.map((h) => (
                        <label
                            key={h.value}
                            className={kelas(
                                'flex min-h-11 cursor-pointer items-center rounded-control border border-transparent px-3 py-2 text-isi font-semibold text-secondary',
                                'has-[:checked]:border-control has-[:checked]:bg-surface has-[:checked]:font-bold has-[:checked]:text-primary',
                                fokusDiDalam,
                            )}
                        >
                            <input
                                type="radio"
                                name={`${id}-hasil`}
                                value={h.value}
                                checked={form.data.completion_state === h.value}
                                onChange={() => form.setData('completion_state', h.value)}
                                className="sr-only"
                            />
                            <span className="min-w-0 break-words">{h.label}</span>
                        </label>
                    ))}
                </div>
                <InputError id={`${id}-hasil-galat`} messages={form.errors.completion_state} className="mt-2" />
            </fieldset>

            {berbalik ? (
                <fieldset aria-describedby={form.errors.alasan ? `${id}-alasan-galat` : undefined}>
                    <legend className="text-isi font-bold text-primary">Alasan berbalik (opsional)</legend>
                    <div className="mt-2 flex flex-wrap gap-1">
                        {alasan.map((a) => (
                            <ChipPilihan key={a.value} dipilih={form.data.alasan === a.value} onUbah={(dipilih) => form.setData('alasan', dipilih ? a.value : '')}>
                                {a.label}
                            </ChipPilihan>
                        ))}
                    </div>
                    <InputError id={`${id}-alasan-galat`} messages={form.errors.alasan} className="mt-2" />
                </fieldset>
            ) : null}

            <div>
                <Label htmlFor={`${id}-catatan`}>Catatan pribadi (opsional)</Label>
                <Textarea
                    id={`${id}-catatan`}
                    rows={3}
                    value={form.data.personal_notes}
                    onChange={(e) => form.setData('personal_notes', e.target.value)}
                    aria-invalid={form.errors.personal_notes ? true : undefined}
                    aria-describedby={form.errors.personal_notes ? `${id}-catatan-galat` : undefined}
                />
                <InputError id={`${id}-catatan-galat`} messages={form.errors.personal_notes} className="mt-2" />
            </div>

            {/* Pesan tepat di atas tombol Simpan (ruling W6-5h), bukan di puncak formulir:
                keduanya satu blok, jadi pesan ikut terlihat selama tombolnya terlihat. Wilayah
                status selalu terpasang; hanya isi dan kelas sr-only-nya yang bersyarat. */}
            <div>
                <p role="status" aria-atomic="true" className={kelas('mb-2 text-kecil font-bold text-warn-900', !galatJaringan && !galatHttp && tertahan === null && 'sr-only')}>
                    {galatJaringan ? 'Sambungan terputus sebelum jawaban server diterima, jadi belum pasti hasilnya tersimpan. Isian Anda tetap di sini; tekan Simpan lagi saat ada sinyal.' : galatHttp ? 'Belum pasti tersimpan: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa apakah pendakian sudah selesai sebelum menyimpan lagi.' : (tertahan?.kalimat ?? '')}
                </p>
                {tertahan?.tautan ? (
                    <p className="mb-2">
                        <Tautan href={tertahan.tautan.href} className={tautanTeks}>
                            {tertahan.tautan.label}
                        </Tautan>
                    </p>
                ) : null}
                <Button id={idSimpan} type="submit" processing={form.processing || memeriksa}>
                    Simpan hasil pendakian
                </Button>
            </div>
        </form>
    );
}
