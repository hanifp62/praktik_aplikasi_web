/**
 * Sapaan waktu Beranda dari jam perangkat (spec v3 bagian 6.1): pagi sampai pukul 10.59, siang
 * sampai 14.59, sore sampai 17.59, malam sesudahnya sampai pukul 03.59. Jam perangkat, bukan zona
 * gunung: sapaan menyapa pendakinya di tempat ia membaca, sedangkan tanggal dan jam rencana
 * tetap memakai zona gunungnya dari server.
 */
export function sapaan(sekarang: Date): string {
    const jam = sekarang.getHours();

    if (jam >= 4 && jam < 11) {
        return 'Selamat pagi';
    }

    if (jam >= 11 && jam < 15) {
        return 'Selamat siang';
    }

    if (jam >= 15 && jam < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
}
