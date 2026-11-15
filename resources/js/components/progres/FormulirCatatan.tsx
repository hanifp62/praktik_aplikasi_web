import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { PemilihJalur } from '@/components/trails/PemilihJalur';
import { Button } from '@/components/ui/Button';
import { InputError, Label, Textarea, TextInput } from '@/components/ui/Field';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { Dialog } from '@/components/ui/Lembar';
import type { FormulirCatatanData, IsianCatatan, UbahCatatan } from './types';

const kosong = (tanggal: string, jenis: string): IsianCatatan => ({
    trail_id: '',
    nama_jalur: '',
    tanggal,
    jenis,
    jarak_km: '',
    naik_m: '',
    jam: '',
    menit: '',
    tertinggi_m: '',
    catatan: '',
});

/**
 * Isi lembar "Catat pendakian di luar trip" dan "Ubah catatan pendakian" (FR-25, spec v3 bagian
 * 6.14). Jalurnya dari katalog atau ditulis sendiri; tanggal tidak boleh di masa depan; setiap
 * angka opsional dan yang dikosongkan tetap tidak diketahui (R-007). Pemilik dan asal catatan
 * ditentukan server. Hapus meminta konfirmasi yang menyebut akibatnya.
 */
export function FormulirCatatan({ formulir, ubah, onSelesai }: { formulir: FormulirCatatanData; ubah: UbahCatatan | null; onSelesai: () => void }) {
    const form = useForm<IsianCatatan>(ubah?.awal ?? kosong(formulir.tanggalMaks, formulir.jenis[0]?.nilai ?? 'TEKTOK'));
    const [jalur, setJalur] = useState<{ id: number; nama: string } | null>(ubah?.jalur ?? null);
    const [manual, setManual] = useState(ubah !== null && ubah.jalur === null);
    const [menghapus, setMenghapus] = useState(false);
    const [konfirmasiHapus, setKonfirmasiHapus] = useState(false);
    const galat = form.errors as Partial<Record<string, string>>;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            trail_id: manual || jalur === null ? '' : String(jalur.id),
            nama_jalur: manual ? d.nama_jalur : '',
            tanggal: d.tanggal,
            jenis: d.jenis,
            jarak_km: d.jarak_km,
            naik_m: d.naik_m,
            waktu_menit: d.jam === '' && d.menit === '' ? '' : String(Number(d.jam || 0) * 60 + Number(d.menit || 0)),
            tertinggi_m: d.tertinggi_m,
            catatan: d.catatan,
        }));

        const selesai = { preserveScroll: true, onSuccess: onSelesai };

        if (ubah) {
            form.put(ubah.simpan, selesai);
        } else {
            form.post(formulir.simpan, selesai);
        }
    };

    const hapus = () => {
        if (!ubah) {
            return;
        }

        router.delete(ubah.hapus, {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
            onSuccess: () => {
                setKonfirmasiHapus(false);
                onSelesai();
            },
        });
    };

    return (
        <form onSubmit={kirim} noValidate className="space-y-5">
            <PemilihJalur
                label="Jalur"
                dipilih={manual ? null : jalur}
                onPilih={(j) => setJalur(j === null ? null : { id: j.id, nama: j.nama })}
                manual={{
                    aktif: manual,
                    label: 'Jalurnya tidak ada di katalog',
                    nama: form.data.nama_jalur,
                    onAktif: setManual,
                    onNama: (nama) => form.setData('nama_jalur', nama),
                }}
                galat={galat.trail_id ?? galat.nama_jalur}
            />

            <div>
                <Label htmlFor="catatan-tanggal">Tanggal pendakian</Label>
                <TextInput
                    id="catatan-tanggal"
                    type="date"
                    max={formulir.tanggalMaks}
                    value={form.data.tanggal}
                    onChange={(e) => form.setData('tanggal', e.target.value)}
                    aria-invalid={galat.tanggal ? true : undefined}
                    aria-describedby={galat.tanggal ? 'catatan-tanggal-galat' : undefined}
                />
                <InputError id="catatan-tanggal-galat" messages={galat.tanggal} className="mt-2" />
            </div>

            <KontrolSegmen nama="catatan-jenis" label="Jenis pendakian" pilihan={formulir.jenis} nilai={form.data.jenis} onUbah={(v) => form.setData('jenis', v)} penjelas={galat.jenis ? 'catatan-jenis-galat' : undefined} />
            <InputError id="catatan-jenis-galat" messages={galat.jenis} className="-mt-3" />

            <fieldset className="space-y-3">
                <legend className="text-isi font-bold text-primary">Angka pendakian</legend>
                <p className="text-kecil text-secondary">Isi dari catatan Anda sendiri, misalnya jam tangan atau aplikasi lain. Kosongkan yang tidak Anda ketahui.</p>
                <div className="grid grid-cols-2 gap-3">
                    <IsianAngka id="catatan-jarak" label="Jarak (km)" nilai={form.data.jarak_km} onUbah={(v) => form.setData('jarak_km', v)} galat={galat.jarak_km} desimal />
                    <IsianAngka id="catatan-naik" label="Tanjakan (m)" nilai={form.data.naik_m} onUbah={(v) => form.setData('naik_m', v)} galat={galat.naik_m} />
                </div>
                <fieldset aria-describedby={galat.waktu_menit ? 'catatan-waktu-galat' : undefined}>
                    <legend className="text-kecil font-bold text-primary">Waktu bergerak</legend>
                    <div className="mt-1 grid grid-cols-2 gap-3">
                        <IsianAngka id="catatan-jam" label="Jam" nilai={form.data.jam} onUbah={(v) => form.setData('jam', v)} />
                        <IsianAngka id="catatan-menit" label="Menit" nilai={form.data.menit} onUbah={(v) => form.setData('menit', v)} />
                    </div>
                    <InputError id="catatan-waktu-galat" messages={galat.waktu_menit} className="mt-2" />
                </fieldset>
                <IsianAngka id="catatan-tertinggi" label="Titik tertinggi (m)" nilai={form.data.tertinggi_m} onUbah={(v) => form.setData('tertinggi_m', v)} galat={galat.tertinggi_m} />
            </fieldset>

            <div>
                <Label htmlFor="catatan-isi">Catatan (opsional)</Label>
                <Textarea
                    id="catatan-isi"
                    rows={3}
                    maxLength={1000}
                    value={form.data.catatan}
                    onChange={(e) => form.setData('catatan', e.target.value)}
                    aria-invalid={galat.catatan ? true : undefined}
                    aria-describedby={galat.catatan ? 'catatan-isi-galat' : undefined}
                />
                <InputError id="catatan-isi-galat" messages={galat.catatan} className="mt-2" />
            </div>

            <p className="text-kecil text-secondary">Catatan ini ditandai diisi sendiri dan tidak menaikkan tingkat pengalaman Anda.</p>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" processing={form.processing}>
                    {ubah ? 'Simpan perubahan' : 'Simpan catatan'}
                </Button>
                {ubah ? (
                    <Button variant="secondary" onClick={() => setKonfirmasiHapus(true)}>
                        Hapus catatan
                    </Button>
                ) : null}
            </div>

            {ubah ? (
                <Dialog
                    terbuka={konfirmasiHapus}
                    onTutup={() => setKonfirmasiHapus(false)}
                    judul="Hapus catatan pendakian ini?"
                    keterangan="Catatan dan angkanya hilang dari riwayat dan progres Anda, dan tidak dapat dikembalikan."
                    aksi={
                        <>
                            <Button variant="secondary" onClick={() => setKonfirmasiHapus(false)}>
                                Batal
                            </Button>
                            <Button variant="danger" processing={menghapus} onClick={hapus}>
                                Hapus catatan
                            </Button>
                        </>
                    }
                >
                    <p className="text-isi text-primary">{jalur?.nama ?? form.data.nama_jalur}</p>
                </Dialog>
            ) : null}
        </form>
    );
}

function IsianAngka({ id, label, nilai, onUbah, galat, desimal = false }: { id: string; label: string; nilai: string; onUbah: (nilai: string) => void; galat?: string; desimal?: boolean }) {
    return (
        <div className="min-w-0">
            <Label htmlFor={id}>{label}</Label>
            <TextInput
                id={id}
                inputMode={desimal ? 'decimal' : 'numeric'}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={galat ? true : undefined}
                aria-describedby={galat ? `${id}-galat` : undefined}
                className="tabular-nums"
            />
            <InputError id={`${id}-galat`} messages={galat} className="mt-2" />
        </div>
    );
}

