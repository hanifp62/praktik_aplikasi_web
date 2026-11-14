import type { Opsi } from '@/components/discovery/Isian';
import type { StatusResmi } from '@/components/trails/StatusAkses';
import type { GunungApi } from '@/components/trails/types';

/** Satu baris antrean meja kurator (MejaKurator::baris). */
export interface BarisAntrean {
    id: number;
    sasaran: string;
    status: StatusResmi;
    statusLabel: string;
    keadaan: string;
    pencatat: string | null;
    badan: string | null;
    berlaku: string;
    alasanTinjau: string | null;
    sasaranKunci: string | null;
    url: string;
}

/** Level gunung api terakhir per gunung di kawasan kurator (MejaKurator::gunungApi). */
export interface BarisGunungApi extends GunungApi {
    id: number;
    gunung: string;
    perbarui: string;
}

export interface BadanKurator {
    nama: string;
    peran: string;
    berlakuSampai: string | null;
}

/** Satu laporan kesalahan data yang menunggu cek ulang (MejaKurator::barisLaporan), tanpa identitas pelapor. */
export interface BarisLaporan {
    id: number;
    sasaran: string;
    halaman: string;
    bagian: string;
    isi: string;
    waktu: string;
    catatan: string | null;
    cek: string;
}

export interface AntreanKurator {
    verifikasi: BarisAntrean[];
    tinjau: BarisAntrean[];
    laporan: BarisLaporan[];
    dipersoalkan: BarisAntrean[];
    draf: BarisAntrean[];
}

/** Temuan pemeriksaan konsistensi (PemeriksaanKonsistensi::periksa). */
export interface Temuan {
    kode: string;
    tingkat: 'blokir' | 'peringatan';
    pesan: string;
}

export interface LangkahCatatan {
    nomor: number;
    nama: string;
    selesai: boolean;
    keterangan: string;
}

/** Isian formulir langkah 1 dan 2 (SimpanCatatanStatusRequest). */
export interface IsianStatus {
    sasaran: string;
    otoritas: string;
    status: string;
    jenis_bukti: string;
    url_sumber: string;
    waktu_tangkap: string;
    bukti: File | null;
    nomor_surat: string;
    tanggal_surat: string;
    mulai_berlaku: string;
    cara_berakhir: string;
    berlaku_sampai: string;
    batas: string;
    golongan_alasan: string;
    alasan: string;
    dasar_hukum: string;
    kutipan: string;
    ringkasan: string;
}

export interface KelompokSasaran {
    gunung: string;
    badan: Array<{ value: number; label: string }>;
    pilihan: Opsi[];
}

export interface PilihanFormulir {
    status: Opsi[];
    jenisBukti: Opsi[];
    caraBerakhir: Opsi[];
    golonganAlasan: Opsi[];
}

export interface DrafKurator {
    id: number;
    sasaran: string;
    badan: string | null;
    isian: Omit<IsianStatus, 'sasaran' | 'otoritas' | 'bukti'>;
    adaArsip: boolean;
    url: string;
}

export interface RincianCatatan {
    id: number;
    sasaran: string;
    status: StatusResmi;
    statusLabel: string;
    keadaan: string;
    terlihatPendaki: boolean;
    menungguVerifikasiKedua: boolean;
    badan: string | null;
    surat: string | null;
    jenisBukti: string | null;
    urlSumber: string | null;
    ditangkap: string | null;
    berlaku: string;
    caraBerakhir: string | null;
    batas: string | null;
    alasan: string | null;
    dasarHukum: string | null;
    kutipan: string | null;
    ringkasan: string | null;
    pencatat: string | null;
    pemeriksa: string | null;
    diverifikasi: string | null;
    tinjauBerikutnya: string | null;
}

export interface AksiCatatan {
    ubah: string | null;
    kirim: string | null;
    hapus: string | null;
    verifikasi: string | null;
    persoalkan: string | null;
    tinjauUlang: string | null;
    catatPengganti: string | null;
}

export interface JejakCatatan {
    id: number;
    tindakan: string;
    oleh: string;
    waktu: string | null;
    alasan: string | null;
}
