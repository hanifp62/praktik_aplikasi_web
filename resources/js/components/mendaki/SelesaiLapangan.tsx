import { useRef, useState } from 'react';
import { FormulirSelesai, type SelesaiTertahan } from '@/components/trips/FormulirSelesai';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { antreanSaatIni, dengarAntrean, gagalTerakhir, putarAntrean, selesaiTertahan } from './antrean';
import { tombolLapangan } from './ModeLapangan';
import type { DataLapangan } from './types';

/**
 * Tombol Selesai mode lapangan (spec v3 bagian 6.12, prototype 11) dan lembarnya: hasil
 * pendakian, alasan bila berbalik (DK-50), dan catatan. Menyimpan butuh sinyal, jadi tombol ini
 * hanya dipasang halaman daring; paket luring menyebut bahwa pendakian diselesaikan saat ada
 * sinyal.
 *
 * Simpan memutar antrean lebih dulu dan menunggu hasilnya (ruling W6-FC, I-2 tinjauan akhir W6):
 * selama tiba, jeda, atau lanjut trip ini masih menunggu, Selesai tidak dikirim, karena Selesai
 * yang mendahuluinya menutup sesi dan aksi itu lalu dijawab 409 dengan angka ringkasan yang
 * salah. Luring, putaran berhenti tanpa mengirim dan aksinya tetap menunggu; putaran yang sedang
 * berjalan dari pewaktu mode lapangan tidak ditunggu, jadi aksinya dihitung menunggu sampai
 * Simpan berikutnya.
 *
 * Kalimat tertahan dinilai ulang setiap kali antrean berubah atau terkirim (ruling AF-8, audit E2E
 * M-6): pantauTahan mendengar antrean (dengarAntrean) dan menghitung keputusan yang sama dari
 * antrean saat itu, tanpa memutarnya, jadi kalimat hilang begitu aksi trip ini terkirim.
 */
export function SelesaiLapangan({ data }: { data: DataLapangan }) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);

    const tahan = async (): Promise<SelesaiTertahan | null> => {
        await putarAntrean();

        return selesaiTertahan(await antreanSaatIni(), data.urls, 'lapangan', gagalTerakhir() === 'sesi');
    };

    const pantauTahan = (perbarui: (hasil: SelesaiTertahan | null) => void) =>
        dengarAntrean(() => {
            void antreanSaatIni().then((aksi) => perbarui(selesaiTertahan(aksi, data.urls, 'lapangan', gagalTerakhir() === 'sesi')), () => undefined);
        });

    return (
        <>
            <button ref={pemicu} type="button" onClick={() => setTerbuka(true)} className={kelas(tombolLapangan, 'bg-brand-700 text-on-brand')}>
                Selesai
            </button>
            <LembarBawah
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                pemicu={pemicu}
                judul="Selesaikan pendakian"
                keterangan="Sesudah disimpan, hasilnya masuk ke riwayat Anda. Berbalik sebelum tujuan juga dicatat apa adanya."
            >
                <FormulirSelesai tripId={data.trip.id} url={data.urls.selesai} hasil={data.pilihan.hasil} alasan={data.pilihan.alasan} pantauTahan={pantauTahan} id="selesai-lapangan" tahan={tahan} />
            </LembarBawah>
        </>
    );
}
