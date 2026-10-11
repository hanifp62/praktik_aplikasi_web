import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { Dialog } from '@/components/ui/Lembar';
import { tandaiSesiBerakhir } from '@/luring/simpan';
import type { PropsBersama } from '@/types';
import type { Opsi } from './types';

/**
 * Membatalkan trip (PRD §35, DK-50): final, jadi ditanyakan dulu dalam dialog, dengan alasan
 * opsional dari daftar supaya Progres dapat menulis "Dibatalkan · Cuaca". Membatalkan karena
 * cuaca atau kondisi tubuh adalah keputusan yang benar; kalimatnya tidak menghakimi.
 *
 * Jalur batal tetap ada untuk trip yang sedang berlangsung, sebagai jalan keluar bila
 * pendakian dimulai tanpa sengaja (ruling W6-5f, M-4 dan K-3 putaran perbaikan 1, keputusan
 * produk koordinator). Keterangannya bercabang menurut `berlangsung`: trip yang belum dimulai
 * menjadi CANCELLED yang final, bukan "kembali ke rencana" (ruling W6-5m/m6 putaran perbaikan
 * 2 memperbaiki kalimat docblock ini sendiri, yang sebelumnya salah begitu). Trip yang sedang
 * berjalan dihentikan tanpa riwayat sama sekali (probe peninjau p06), berbeda dari "Dibatalkan
 * di jalur" di formulir Selesai yang justru mencatat riwayat ABANDONED; kalimat cabang itu
 * menyebut kartu "Selesaikan trip" dengan nama sebenarnya (ruling W6-5j/c2 putaran perbaikan
 * 2): komponen ini hanya tampil di ruang kerja (Show.tsx, satu-satunya pemanggil), tempat
 * kartu itu bernama begitu dan formulirnya bertombol "Simpan hasil pendakian", bukan "Selesai"
 * yang hanya ada di mode lapangan. Bila komponen ini kelak dipasang di mode lapangan juga,
 * kalimat ini perlu bercabang lagi menurut tempat tampil.
 *
 * Fokus sesudah batal berhasil atau ditolak kini dipegang PEMANGGIL (Show.tsx) lewat prop
 * `onHasil`, bukan `useFokusSesudahRender` milik komponen ini sendiri (ruling W6-5o/I-2
 * putaran perbaikan 3; tinjauan ulang putaran 2 membuktikan di peramban bahwa mekanisme
 * sebelumnya gagal di dua dari tiga cabang). Akar masalahnya: batal BERHASIL membuat
 * `trip.aktif` menjadi false, melepas seluruh kartu Tindakan termasuk komponen ini SEBELUM
 * `onFinish` berefek (Inertia memanggil `setPage()`, memicu unmount, SEBELUM memanggil
 * `onSuccess`/`onFinish`); `fokusKe` milik komponen yang sudah lepas menjadi no-op karena
 * `setState`-nya tidak lagi berefek. `onHasil` adalah fungsi BIASA yang diteruskan dari Show
 * lewat props; memanggilnya di `onSuccess` tetap bekerja walau komponen ini sudah lepas sesaat
 * sesudahnya, karena closure JavaScript yang memanggilnya tidak bergantung pada status pasang
 * komponen, dan fungsi itu sendiri mengubah state Show (yang TIDAK lepas) lewat
 * `useFokusSesudahRender` milik Show.
 *
 * Batal DITOLAK juga terbukti gagal sebelumnya oleh race lain: `setTerbuka(false)` di
 * `onSuccess` memicu `useDialogAsli` menutup `<dialog>`, dan event `close` sinkron men-refokus
 * pemicu lewat `Lembar.tsx` `saatTutup()` TANPA SYARAT, menimpa fokus yang baru dipindah ke
 * wilayah penolakan. Ruling W6-5p memperbaikinya di `Lembar.tsx` sendiri (lihat docblock di
 * sana): `saatTutup()` kini hanya merebut fokus bila fokus memang akan hilang, bukan ketika
 * sudah dipindah dengan sengaja ke luar dialog. Kedua perbaikan (W6-5o di sini, W6-5p di
 * `Lembar.tsx`) saling melengkapi dan tidak saling bergantung pada urutan efek yang pasti.
 *
 * Batal yang berhasil menandai rekaman sesi paket trip ini di perangkat ini berakhir (ruling AF-6,
 * tandaiSesiBerakhir), supaya mode lapangan dari paket tidak lagi menulis pendakian ini sebagai
 * berjalan. Kunci perangkat dibaca saat render; closure-nya tetap berjalan walau komponen ini sudah
 * lepas sesudah batal berhasil.
 */
export function BatalkanTrip({ tripId, url, alasan, berlangsung, onHasil }: { tripId: number; url: string; alasan: Opsi[]; berlangsung: boolean; onHasil: (hasil: 'berhasil' | 'ditolak') => void }) {
    const kunci = usePage<PropsBersama>().props.perangkat?.kunci ?? null;
    const [terbuka, setTerbuka] = useState(false);
    const [pilihan, setPilihan] = useState('');
    const [memproses, setMemproses] = useState(false);

    const batalkan = () => {
        setMemproses(true);

        router.post(
            url,
            { alasan: pilihan === '' ? null : pilihan },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    setTerbuka(false);

                    if (page.props.penolakan) {
                        onHasil('ditolak');
                    } else {
                        onHasil('berhasil');
                        void tandaiSesiBerakhir(kunci, tripId, { jenis: 'batal', waktu: new Date().toISOString() }).catch(() => undefined);
                    }
                },
                onFinish: () => {
                    setMemproses(false);
                },
            },
        );
    };

    return (
        <>
            <Button variant="secondary" size="sm" onClick={() => setTerbuka(true)}>
                Batalkan trip
            </Button>
            <Dialog
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                judul="Batalkan trip ini?"
                // Kalimat cabang berlangsung diperbaiki ruling W6-6f putaran perbaikan 1 W6
                // Tugas 6: halaman ringkasan-batal memang ada untuk trip ini (HasilPendakian.php,
                // varian batal), jadi klaim lama bahwa trip ini tidak punya hasil apa pun sudah
                // tidak benar.
                keterangan={
                    berlangsung
                        ? 'Pendakian yang sedang berjalan akan dihentikan; jarak, waktu, dan pos yang dicapai tidak dihitung. Bila Anda sudah berjalan lalu berbalik, pilih Selesaikan trip lalu Dibatalkan di jalur supaya perjalanannya tercatat.'
                        : 'Trip yang dibatalkan tidak dapat dimulai lagi. Alasannya opsional dan hanya untuk catatan Anda sendiri.'
                }
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setTerbuka(false)} disabled={memproses}>
                            Kembali
                        </Button>
                        <Button variant="danger" onClick={batalkan} processing={memproses}>
                            Batalkan trip
                        </Button>
                    </>
                }
            >
                <fieldset>
                    <legend className="text-isi font-bold text-primary">Alasan (opsional)</legend>
                    <div className="mt-2 flex flex-wrap gap-1">
                        {alasan.map((a) => (
                            <ChipPilihan key={a.value} dipilih={pilihan === a.value} onUbah={(dipilih) => setPilihan(dipilih ? a.value : '')}>
                                {a.label}
                            </ChipPilihan>
                        ))}
                    </div>
                </fieldset>
            </Dialog>
        </>
    );
}
