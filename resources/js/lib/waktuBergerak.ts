/**
 * Waktu bergerak pendakian: hitungan dan kalimatnya, satu sumber untuk kepala mode lapangan dan strip pendakian
 * (ruling W6-FD dan W6-FJ). Dipisah dari hitung.ts dan kalimatSumber.ts supaya strip, yang dipasang di setiap
 * halaman berkerangka, tidak menarik kedua modul mode lapangan itu ke potongan tata letak. hitung.ts dan
 * kalimatSumber.ts mengekspor ulang fungsi di sini, jadi pemanggil lama tidak berubah.
 *
 * Tanpa impor (ruling L3): FieldModeFiguresTest dan FieldPackDeviceTest menjalankannya di Node lewat ekspor ulang itu.
 */

/** Lama dalam jam dan menit, bentuk Durasi::panjang di server ("4 jam 12 menit"). */
export function teksLama(detik: number): string {
    const menit = Math.max(0, Math.floor(detik / 60));
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;

    if (jam === 0) {
        return `${sisa} menit`;
    }

    return sisa === 0 ? `${jam} jam` : `${jam} jam ${sisa} menit`;
}

/**
 * Detik bergerak sejak mulai: waktu sejak sesi dimulai, dikurangi jeda yang sudah selesai dan
 * jeda yang sedang berjalan. Jam perangkat yang tertinggal tidak menghasilkan waktu negatif.
 */
export function detikBergerak(mulaiMs: number, jedaDetik: number, jedaSejakMs: number | null, sekarangMs: number): number {
    const jedaBerjalan = jedaSejakMs === null ? 0 : Math.max(0, (sekarangMs - jedaSejakMs) / 1000);

    return Math.max(0, (sekarangMs - mulaiMs) / 1000 - jedaDetik - jedaBerjalan);
}

/**
 * Keterangan pendakian yang berlangsung: "Dijeda, {lama} bergerak" atau "Berjalan, {lama}
 * bergerak". Satu sumber untuk kepala mode lapangan dan strip pendakian (ruling W6-FD), supaya
 * keduanya tidak pernah berbeda kata saat dijeda.
 */
export function keteranganBerjalan(dijeda: boolean, lama: string): string {
    return `${dijeda ? 'Dijeda' : 'Berjalan'}, ${lama} bergerak`;
}
