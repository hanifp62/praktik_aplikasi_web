/**
 * Kalimat ringkasan di atas daftar Jelajah (ruling CF-1, audit E2E N-2, tinjauan TA-I1).
 *
 * Modul murni tanpa impor: TrailIndexHiddenSummaryTextTest menjalankannya di Node dengan props
 * `ringkasan` dari GET /trails sungguhan, jadi kalimatnya diuji sebagai perilaku, bukan sebagai
 * teks sumber.
 *
 * N adalah jumlah jalur yang tidak lolos saringan, dihitung dari `ringkasan.tersembunyi`.
 * `ringkasan.jumlah` hanya menghitung jalur yang lolos. Saat "Tampilkan juga" aktif, jalur yang
 * tidak lolos ikut tampil sesudah hasil, jadi angka depan adalah jumlah + N. Dengan begitu angka
 * depan di setiap keadaan sama dengan jumlah kartu yang tampil di semua halaman daftar, yaitu
 * `trails.total`:
 * - N = 0: "{jumlah} jalur";
 * - N > 0 tanpa Tampilkan juga: "{jumlah} jalur, {N} disembunyikan {olehSaringan}";
 * - N > 0 dengan Tampilkan juga: "{jumlah + N} jalur tampil: {jumlah} lolos saringan, {N} tampil
 *   karena Tampilkan juga meski tidak lolos {olehSaringan}".
 */

/** Satu saringan yang menyembunyikan jalur, bentuk `ringkasan.tersembunyi` dari HalamanJelajah. */
export interface SaringanTersembunyi {
    kunci: string;
    label: string;
    jumlah: number;
}

export interface RingkasanJelajah {
    jumlah: number;
    tersembunyi: SaringanTersembunyi[];
}

/** Angka depan, yang dirender tebal dalam span data-angka, dan sisa kalimat sesudahnya. */
export interface KalimatRingkasan {
    angka: number;
    sisa: string;
}

/** "saringan status resmi", atau "saringan: status resmi (1), tanjakan (2)" bila lebih dari satu. */
export function saringanPenyembunyi(tersembunyi: SaringanTersembunyi[]): string {
    if (tersembunyi.length === 1) {
        return `saringan ${tersembunyi[0]?.label ?? ''}`;
    }

    return `saringan: ${tersembunyi.map((s) => `${s.label} (${s.jumlah})`).join(', ')}`;
}

/** N: jumlah jalur yang tidak lolos saringan. */
export function jumlahTersembunyi(tersembunyi: SaringanTersembunyi[]): number {
    return tersembunyi.reduce((n, s) => n + s.jumlah, 0);
}

export function kalimatRingkasan(ringkasan: RingkasanJelajah, tampilkanSemua: boolean): KalimatRingkasan {
    const n = jumlahTersembunyi(ringkasan.tersembunyi);

    if (n === 0) {
        return { angka: ringkasan.jumlah, sisa: ' jalur' };
    }

    const olehSaringan = saringanPenyembunyi(ringkasan.tersembunyi);

    if (tampilkanSemua) {
        return {
            angka: ringkasan.jumlah + n,
            sisa: ` jalur tampil: ${ringkasan.jumlah} lolos saringan, ${n} tampil karena Tampilkan juga meski tidak lolos ${olehSaringan}`,
        };
    }

    return { angka: ringkasan.jumlah, sisa: ` jalur, ${n} disembunyikan ${olehSaringan}` };
}
