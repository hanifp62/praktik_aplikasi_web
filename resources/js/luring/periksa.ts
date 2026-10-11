/**
 * Pemeriksaan paket lapangan di perangkat dan kalimatnya (spec v3 bagian 10.2; rencana induk W6
 * butir 5; ruling W6-9b, W6-9g): "Siap luring" hanya bila setiap bagian manifest ada di
 * penyimpanan, hash bacaan ulangnya sama dengan manifest, dan shell luring tersimpan di cache
 * service worker. Setiap kegagalan disebut dengan sebabnya (PT10-7): sesi habis, batas laju,
 * galat server, ruang habis, penyimpanan diblokir, putus jaringan, atau shell yang belum tersimpan.
 *
 * Tanpa impor (ruling L3): FieldPackDeviceTest menjalankannya di Node dari PHPUnit, dan alur.ts
 * menerimanya lewat injeksi.
 */

/** Penanda shell paket yang ditulis service worker paling akhir (resources/views/sw.blade.php, ruling W6-9g). */
export const PENANDA_SHELL_PAKET = '/__memount-shell-paket';

/** Spasi tak terputus antara angka dan satuan (butir 7, PT10-17). */
const NBSP = '\u00A0';

const NAMA: Record<string, string> = { lapangan: 'jalur dan pos', rencana: 'rencana', dinamis: 'potret status, cuaca, dan laporan' };

/** Nama bagian yang belum utuh, dalam urutan manifest; kosong berarti paket utuh. */
export function bagianBelumUtuh(manifest: { bagian: Array<{ nama: string; sha256: string }> }, bacaan: Array<{ nama: string; sha256: string }>): string[] {
    const hash = new Map(bacaan.map((b) => [b.nama, b.sha256]));

    return manifest.bagian.filter((b) => hash.get(b.nama) !== b.sha256).map((b) => b.nama);
}

function daftarNama(nama: string[]): string {
    return nama.map((n) => NAMA[n] ?? n).join(', ');
}

/** Kiriman satu bagian yang rusak di jalan (hash berbeda dengan versi manifest yang sama, ruling W6-8d). */
export function kalimatBelumLengkap(nama: string[]): string {
    return `Belum lengkap: ${daftarNama(nama)}. Coba lagi selagi ada sinyal.`;
}

/**
 * Bagian yang dibaca ulang dari penyimpanan tidak cocok atau tidak ada (PT10-13). "Coba lagi"
 * mengunduhnya ulang, karena bagian yang dilewati juga di-hash ulang dari teks tersimpannya.
 */
export function kalimatTidakUtuh(nama: string[]): string {
    return `Belum lengkap: ${daftarNama(nama)} belum tersimpan utuh di perangkat ini. Coba lagi untuk mengunduh ulang.`;
}

/**
 * Ruang yang dibutuhkan: dua kali ukuran manifest, karena versi lama bertahan sampai versi baru
 * lolos. Null bila peramban tidak melaporkan kuota atau pemakaian: tidak diketahui bukan cukup
 * (PT10-6, R-006, R-007).
 */
export function ruangCukup(ukuranPaket: number, kuota: number | null, terpakai: number | null): boolean | null {
    if (kuota === null || terpakai === null) {
        return null;
    }

    return kuota - terpakai >= ukuranPaket * 2;
}

/** Kalimat ruang di ketiga cabang: cukup, kurang, dan tidak dilaporkan peramban (PT10-6). */
export function kalimatRuang(ukuranPaket: number, cukup: boolean | null): string {
    const ukuran = teksUkuran(ukuranPaket);

    if (cukup === true) {
        return `Ukuran paket ${ukuran}. Ruang perangkat cukup.`;
    }

    if (cukup === false) {
        return `Ukuran paket ${ukuran}, dan ruang perangkat tidak cukup. Kosongkan ruang dulu, lalu periksa lagi.`;
    }

    return `Ukuran paket ${ukuran}. Peramban ini tidak melaporkan sisa ruang, jadi cukup tidaknya belum dapat dipastikan; bila ruang habis saat mengunduh, Memount menyebutnya.`;
}

/**
 * Penilaian ruang dari navigator.storage.estimate() (null bila tidak tersedia): unduhan ditawarkan
 * kecuali ruang terbukti kurang.
 */
export function penilaianRuang(ukuranPaket: number, perkiraan: { quota?: number; usage?: number } | null): { boleh: boolean; kalimat: string } {
    const cukup = ruangCukup(ukuranPaket, perkiraan?.quota ?? null, perkiraan?.usage ?? null);

    return { boleh: cukup !== false, kalimat: kalimatRuang(ukuranPaket, cukup) };
}

/** Ukuran untuk dibaca: kB di bawah 1 MB dibulatkan, MB satu desimal; tanpa "1024 kB" (PT10-17). */
export function teksUkuran(bita: number): string {
    const kb = Math.max(1, Math.round(bita / 1024));

    return kb < 1024 ? `${kb}${NBSP}kB` : `${(bita / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 })}${NBSP}MB`;
}

/** Jawaban HTTP gagal untuk manifest atau bagian paket, satu kalimat per sebab (PT10-7). */
export function kalimatHttp(status: number): string {
    if (status === 401) {
        return 'Sesi Anda berakhir. Masuk lagi, lalu siapkan paket.';
    }

    if (status === 403) {
        return 'Trip ini tidak dapat dibuka dengan sesi sekarang. Muat ulang halaman ini.';
    }

    if (status === 404) {
        return 'Paket untuk trip ini tidak tersedia.';
    }

    if (status === 429) {
        return 'Terlalu banyak permintaan paket. Tunggu satu menit, lalu coba lagi.';
    }

    if (status >= 500) {
        return 'Server belum dapat menyiapkan paket. Coba lagi sebentar lagi.';
    }

    return 'Paket belum dapat disiapkan. Muat ulang halaman ini, lalu coba lagi.';
}

/**
 * Galat yang bukan jawaban HTTP (PT10-7 bagian 2): ruang penyimpanan habis saat menulis, putus
 * jaringan (fetch menolak dengan TypeError; kalimatnya dari pemanggil), dan penyimpanan yang tidak
 * dapat dibuka atau ditulis.
 */
export function kalimatGalatUnduh(galat: unknown, jaringan: string): string {
    if ((galat as { name?: unknown } | null)?.name === 'QuotaExceededError') {
        return 'Ruang penyimpanan peramban habis saat menyimpan paket. Kosongkan ruang, lalu coba lagi.';
    }

    if (galat instanceof TypeError) {
        return jaringan;
    }

    return 'Peramban ini belum dapat menyimpan paket, misalnya karena penyimpanan diblokir atau jendela privat.';
}

/**
 * Shell luring yang belum siap (PT10-7 bagian 3, ruling W6-10d dan W6-9n): tanpa service worker
 * (API tidak ada atau tidak siap dalam batasnya) lawan service worker yang belum menyimpan shell.
 * Konteks "unduh" menyuruh memuat ulang lalu mencoba lagi; konteks "paket-ada" (paket sudah di
 * perangkat, penyembuhan gagal) menyertai tindakan "Siapkan ulang".
 */
export function kalimatShell(hasil: 'tanpa-sw' | 'gagal', konteks: 'unduh' | 'paket-ada'): string {
    if (hasil === 'tanpa-sw') {
        return 'Peramban ini tidak menyimpan halaman luring (misalnya jendela privat), jadi paket tidak dapat dibuka tanpa sinyal di sini.';
    }

    return konteks === 'unduh'
        ? 'Halaman luring belum tersimpan. Muat ulang halaman ini selagi ada sinyal, lalu coba lagi.'
        : 'Halaman luring belum tersimpan, jadi paket di perangkat ini belum dapat dibuka tanpa sinyal. Siapkan ulang selagi ada sinyal.';
}

/**
 * iPhone dan iPad yang membuka dari tab peramban, bukan dari aplikasi di Layar Utama: penyimpanan
 * keduanya terpisah, jadi paket yang diunduh di tab tidak terbaca aplikasi yang dibuka di jalur.
 * Safari iPadOS 13 ke atas melapor UA Macintosh, jadi dikenali dari layar sentuhnya (PT10-18).
 */
export function perluLayarUtama(ua: string, titikSentuh: number, terpasang: boolean): boolean {
    const perangkatApple = /iP(hone|ad|od)/.test(ua) || (/Macintosh/.test(ua) && titikSentuh > 1);

    return perangkatApple && !terpasang;
}

/** Path skrip shell dari meta memount-shell-luring di /offline (ruling W6-9b); null bila tidak ada. */
export function skripShellDari(teks: string): string | null {
    const cocok = /<meta\s+name="memount-shell-luring"\s+content="(\/[^"]+)"/.exec(teks);

    return cocok?.[1] ?? null;
}

/**
 * Shell luring tersimpan di cache service worker (ruling W6-9b, W6-9g): /offline memuat penanda
 * shell, skrip yang dirujuknya tersimpan, dan penanda shell paket ada. Cache yang tidak ada atau
 * melempar (jendela privat, situs diblokir) berarti tidak tersimpan.
 */
export async function periksaShellLuring(tembolok: Pick<CacheStorage, 'match'> | undefined): Promise<boolean> {
    if (!tembolok) {
        return false;
    }

    try {
        const halaman = await tembolok.match('/offline');

        if (!halaman) {
            return false;
        }

        const skrip = skripShellDari(await halaman.text());

        if (skrip === null) {
            return false;
        }

        const [adaSkrip, adaPenanda] = await Promise.all([tembolok.match(skrip), tembolok.match(PENANDA_SHELL_PAKET)]);

        return adaSkrip !== undefined && adaPenanda !== undefined;
    } catch {
        return false;
    }
}
