import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { TombolSalin } from '@/components/ui/TombolSalin';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { kelas } from '@/lib/kelas';

export interface KeselamatanHalaman {
    pesan: string | null;
    kurang: string | null;
    kembali: { tanggal: string | null; jam: string | null };
    registrasi: string | null;
    zona: string;
    urls: { simpan: string; cetak: string };
}

/**
 * Rencana untuk kontak (spec v3 bagian 10.8; ruling W6-R4): jam kembali dan nomor registrasi,
 * lalu pesan siap kirim lewat WhatsApp atau SMS dengan instruksi eskalasi, dan versi cetak.
 * Pesannya disusun server (App\Services\Trips\PesanRencana) dan dikirim pendaki sendiri; ini
 * bukan berbagi lokasi, dan Memount tidak memantau perjalanan.
 *
 * Fokus dan galat jaringan Simpan (pra-tinjau Tugas 7 PT7-2, pola W6-4q2 dan W6-5h seperti
 * FormulirSelesai): aksi di halaman yang sama memakai preserveState, jadi ruang kerja tidak
 * dipasang ulang, dan tombol yang `disabled` selama memproses kehilangan fokusnya. Fokus kembali
 * ke tombol Simpan di onFinish, sesudah berhasil, galat isian, atau galat jaringan. Galat
 * jaringan tidak membuktikan server gagal menyimpan, jadi kalimatnya "belum pasti ... tersimpan"
 * di wilayah status yang selalu terpasang tepat di atas tombol. Bila jawaban server menolak
 * (trip sudah selesai atau dibatalkan di tab lain, PT7-8) atau kartu ini lepas karena prop
 * keselamatan hilang, fokus pindah ke wilayah penolakan atau kabar ruang kerja (Show.tsx), yang
 * selalu terpasang; kartu yang lepas tidak dapat memindah fokus lewat state miliknya (W6-5o).
 * Galat HTTP tanpa header Inertia (ruling W6-FE), misalnya 419 sesudah sesi berakhir, ditulis di
 * wilayah status yang sama lewat `onHttpException` yang mengembalikan false, bukan dialog galat
 * bawaan Inertia; kalimatnya juga "belum pasti".
 *
 * SMS memakai tautan biasa tanpa tab baru (PT7-16): `sms:` membuka aplikasi pesan, jadi
 * keterangan "membuka tab baru" salah di sana. Salinan manual pesan berlabel pesan dan berbaris
 * supaya pemisah barisnya tidak hilang (ruling W6-7c).
 */
export function KeselamatanTrip({ keselamatan }: { keselamatan: KeselamatanHalaman }) {
    const form = useForm({
        kembali_tanggal: keselamatan.kembali.tanggal ?? '',
        kembali_jam: keselamatan.kembali.jam ?? '',
        registrasi: keselamatan.registrasi ?? '',
    });
    const [galatJaringan, setGalatJaringan] = useState(false);
    const [galatHttp, setGalatHttp] = useState(false);
    const fokusKe = useFokusSesudahRender();

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        setGalatJaringan(false);
        setGalatHttp(false);

        let ditolak = false;
        let kartuLepas = false;

        form.put(keselamatan.urls.simpan, {
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
                kartuLepas = !page.props.keselamatan;
            },
            onFinish: () => {
                if (ditolak || kartuLepas) {
                    // Kedua tujuan selalu terpasang di Show.tsx, jadi fokus tidak bergantung pada
                    // kartu ini masih ada sesudah halaman baru dirender.
                    const tujuan = ditolak ? 'penolakan-trip' : 'kabar-trip';
                    window.requestAnimationFrame(() => document.getElementById(tujuan)?.focus());
                } else {
                    fokusKe('kembali-simpan');
                }
            },
        });
    };

    return (
        <Card title="Rencana untuk kontak" subtitle="Kirim rencana ini ke orang yang tidak ikut mendaki. Memount tidak memantau perjalanan dan tidak membagikan lokasi Anda.">
            <form onSubmit={simpan} className="space-y-4">
                <fieldset>
                    <legend className="text-isi font-bold text-primary">Perkiraan kembali ({keselamatan.zona})</legend>
                    <div className="mt-2 flex flex-wrap gap-3">
                        <div className="min-w-40 flex-1">
                            <Label htmlFor="kembali-tanggal">Tanggal</Label>
                            <TextInput
                                id="kembali-tanggal"
                                type="date"
                                value={form.data.kembali_tanggal}
                                onChange={(e) => form.setData('kembali_tanggal', e.target.value)}
                                aria-invalid={form.errors.kembali_tanggal ? true : undefined}
                                aria-describedby={form.errors.kembali_tanggal ? 'kembali-tanggal-galat' : undefined}
                                className="mt-1 w-full"
                            />
                            <InputError id="kembali-tanggal-galat" messages={form.errors.kembali_tanggal} className="mt-2" />
                        </div>
                        <div className="min-w-32 flex-1">
                            <Label htmlFor="kembali-jam">Jam</Label>
                            <TextInput
                                id="kembali-jam"
                                type="time"
                                value={form.data.kembali_jam}
                                onChange={(e) => form.setData('kembali_jam', e.target.value)}
                                aria-invalid={form.errors.kembali_jam ? true : undefined}
                                aria-describedby={form.errors.kembali_jam ? 'kembali-jam-galat' : undefined}
                                className="mt-1 w-full"
                            />
                            <InputError id="kembali-jam-galat" messages={form.errors.kembali_jam} className="mt-2" />
                        </div>
                    </div>
                </fieldset>
                <div>
                    <Label htmlFor="kembali-registrasi">
                        Nomor registrasi <span className="font-normal text-secondary">(opsional, dari tiket atau SIMAKSI)</span>
                    </Label>
                    <TextInput
                        id="kembali-registrasi"
                        value={form.data.registrasi}
                        onChange={(e) => form.setData('registrasi', e.target.value)}
                        aria-invalid={form.errors.registrasi ? true : undefined}
                        aria-describedby={form.errors.registrasi ? 'kembali-registrasi-galat' : undefined}
                        className="mt-1 w-full"
                    />
                    <InputError id="kembali-registrasi-galat" messages={form.errors.registrasi} className="mt-2" />
                </div>
                {/* Pesan tepat di atas tombol Simpan (pola W6-5h): satu blok, jadi pesan ikut
                    terlihat selama tombolnya terlihat. Wilayah status selalu terpasang; hanya isi
                    dan kelas sr-only-nya yang bersyarat. */}
                <div>
                    <p role="status" aria-atomic="true" className={kelas('mb-2 text-kecil font-bold text-warn-900', !galatJaringan && !galatHttp && 'sr-only')}>
                        {galatJaringan ? 'Sambungan terputus sebelum jawaban server diterima, jadi belum pasti jam kembali tersimpan. Isian Anda tetap di sini; tekan Simpan jam kembali lagi saat ada sinyal.' : galatHttp ? 'Belum pasti tersimpan: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa jam kembali sebelum menyimpan lagi.' : ''}
                    </p>
                    <Button id="kembali-simpan" type="submit" variant="secondary" processing={form.processing}>
                        Simpan jam kembali
                    </Button>
                </div>
            </form>

            <div className="mt-6 border-t border-subtle pt-5">
                {keselamatan.pesan ? (
                    <>
                        <p className="text-kecil font-bold text-secondary">Pesan yang akan dikirim</p>
                        <p className="mt-2 whitespace-pre-line wrap-break-word rounded-control bg-surface-sunken p-4 text-kecil text-primary">{keselamatan.pesan}</p>
                        <div className="mt-4 flex flex-wrap items-start gap-3">
                            <Button href={`https://wa.me/?text=${encodeURIComponent(keselamatan.pesan)}`} external>
                                Kirim lewat WhatsApp
                            </Button>
                            <Button variant="secondary" href={`sms:?&body=${encodeURIComponent(keselamatan.pesan)}`}>
                                Kirim lewat SMS
                            </Button>
                            <TombolSalin teks={keselamatan.pesan} label="Salin pesan" berhasil="Pesan disalin." labelManual="Salin pesan ini secara manual" baris={10} />
                        </div>
                    </>
                ) : (
                    <p className="text-isi text-secondary">{keselamatan.kurang}</p>
                )}
                <p className="mt-4">
                    <Button variant="ghost" href={keselamatan.urls.cetak}>
                        Buka versi cetak
                    </Button>
                </p>
            </div>
        </Card>
    );
}
