/** Kabar tulisan terakhir di halaman Progres, dibacakan di wilayah bagian asalnya. */
export interface KabarProgres {
    bagian: 'riwayat' | 'target';
    teks: string;
}

/** Ringkasan satu tahun (spec v3 bagian 6.14): hitungan pendakian, total tanjakan dan jarak. */
export interface RingkasanTahun {
    judul: string;
    pendakian: string;
    rincian: string;
    tanjakan: string | null;
    tanjakanCatatan: string | null;
    jarak: string | null;
    jarakCatatan: string | null;
    sumber: string;
}

/** Satu angka batang: nilai untuk panjang batang (null bila batal atau belum ada) dan teksnya. */
export interface NilaiBatang {
    nilai: number | null;
    teks: string;
}

export interface BatangPendakian {
    kunci: string;
    nama: string;
    bulan: string;
    keadaan: 'selesai' | 'sebagian' | 'batal';
    terbaru: boolean;
    tanjakan: NilaiBatang;
    jarak: NilaiBatang;
}

export interface GrafikPendakianData {
    batang: BatangPendakian[];
    keterangan: { tanjakan: string; jarak: string };
    catatan: string | null;
}

/** Isian formulir catatan pendakian di luar trip, sebagai teks seperti di isian. */
export interface IsianCatatan {
    trail_id: string;
    nama_jalur: string;
    tanggal: string;
    jenis: string;
    jarak_km: string;
    naik_m: string;
    jam: string;
    menit: string;
    tertinggi_m: string;
    catatan: string;
}

export interface UbahCatatan {
    simpan: string;
    hapus: string;
    jalur: { id: number; nama: string } | null;
    awal: IsianCatatan;
}

export interface ButirRiwayat {
    kunci: string;
    nama: string;
    tanggal: string;
    baris: string;
    keadaan: 'selesai' | 'sebagian' | 'batal';
    lencana: string;
    catatan: { teks: string; nada: 'komunitas' | 'redup' } | null;
    kalimatBatal: string | null;
    url: string | null;
    ubah: UbahCatatan | null;
}

export interface FormulirCatatanData {
    simpan: string;
    tanggalMaks: string;
    jenis: Array<{ nilai: string; label: string }>;
}

/** Satu bulan pada kotak target jumlah pendakian; bukan rentetan, tidak ada hitungan berturut-turut. */
export interface BulanTarget {
    nama: string;
    pendek: string;
    keadaan: 'ada' | 'batal' | 'kosong' | 'nanti';
    teks: string;
}

export interface ButirTarget {
    metrik: 'GAIN' | 'DISTANCE' | 'HIKES';
    judul: string;
    capaian: string;
    sasaran: string;
    tercapai: boolean;
    kalimat: string;
    /** 0 sampai 1, hanya untuk panjang bilah; tidak pernah ditulis sebagai persen. */
    rasio: number;
    catatan: string | null;
    bulan: BulanTarget[] | null;
    keterangan: string | null;
}

export interface IsianTarget {
    tanjakan_m: string;
    jarak_km: string;
    pendakian: string;
}

export interface TargetPribadiData {
    daftar: ButirTarget[];
    bulan: string;
    ubah: string;
    awal: IsianTarget;
    aturan: string;
}

/** Pencapaian yang diturunkan dari riwayat pendaki sendiri; tanpa peringkat atau pembanding. */
export interface ButirPencapaian {
    kunci: string;
    jenis: 'tanjakan' | 'laporan';
    judul: string;
    keterangan: string;
}
