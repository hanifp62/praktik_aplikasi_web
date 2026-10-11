/**
 * Warna token tema dalam bentuk yang dipahami MapLibre: "35 79 50" menjadi
 * "rgb(35, 79, 50)".
 *
 * MapLibre tidak membaca kelas Tailwind maupun var(), jadi garis dan penanda mengambil
 * warnanya dari token yang sama dengan antarmuka, bukan dari warna mentah. Nilainya
 * dibaca saat peta menggambar, sehingga ikut tema yang sedang berlaku. Cadangan hanya
 * dipakai bila CSS belum terbaca.
 */
export function warnaToken(nama: string, cadangan: string): string {
    const nilai = getComputedStyle(document.documentElement).getPropertyValue(nama).trim().split(/\s+/);

    return nilai.length === 3 ? `rgb(${nilai.join(', ')})` : cadangan;
}
