import { router, usePage } from '@inertiajs/react';
import { useEffect, useSyncExternalStore } from 'react';
import type { PropsBersama } from '@/types';
import {
    aksiDiPaketDengan,
    bersihkanCache,
    bersihkanDengan,
    buatKeluar,
    hapusMenurutNama,
    hitungDengan,
    jalankanPenjaga,
    kosongkanOpfs,
    pandanganKeluar,
    pelacakRiwayat,
    type AlatBersih,
    type AlatHitung,
    type AsalKeluar,
    type HasilBersih,
} from './keluarMurni';
import { hapusBasisData, kunciTersimpan, kunciTertutup, lupakanKunci, tutupKunci } from './simpan';

/**
 * Data perangkat saat keluar, berganti akun, atau menghapus akun (spec v3 bagian 10.6; rencana
 * induk W6 butir 7; ruling W6-R10, W6-11a sampai W6-11e, W6-11g, dan W6-11h). Keputusan, kalimat,
 * keadaan tombol, dan pemilihan data yang dihapus ada di keluarMurni.ts dan diuji di Node; berkas
 * ini menyambungkannya ke Inertia dan penyimpanan peramban.
 *
 * Keluar (keluar()): bila ada titik jejak pengguna ini, titik basis data jejak lama, atau catatan
 * tiba atau jeda yang belum terkirim, pendaki ditanya lebih dulu. Sesudah itu semua data Memount
 * di perangkat ini dihapus, baru sesi diakhiri. Kegagalan rute keluar ditulis di wilayah status
 * tombol Keluar yang memulainya, dan fokus tetap di tombol itu (W6-11c). Respons server juga
 * mengirim Clear-Site-Data: "storage" sebagai lapis kedua, karena tidak semua peramban
 * mendukungnya.
 *
 * Ganti akun (usePenjagaPerangkat()): data perangkat milik kunci LAIN dihapus tanpa bertanya saat
 * halaman akun ini dibuka dengan props dari server (W6-R10, W6-11g), kecuali basis data jejak lama
 * tanpa pemilik (W6-11e). Props yang dipulihkan dari riwayat (tombol Kembali atau Maju) tidak
 * dipakai, karena dapat milik akun yang sudah keluar.
 *
 * Yang dihapus: basis data paket (memount-luring-*) dan jejak (jejak-pendakian-*), antrean aksi
 * (sessionStorage mendaki.antrean.*, dan memori modul antrean saat keluar), kunci perangkat di
 * localStorage, cache pack-*, penanda shell paket di cache shell-*, dan OPFS. Kunci yang
 * dibersihkan ditutup di tab ini sampai muat penuh (W6-11d).
 *
 * TataLetakPendakian memasang penjaga dari modul ini di mode lapangan dan shell luring, yang tidak
 * punya tombol Keluar. Karena itu modul antrean dan perekam jejak dimuat hanya saat dipakai, dan
 * rute keluar tidak diambil dari @/routes: impor itu menambah satu potongan ke pramuat mode lapangan
 * dan ke daftar shell paket (23 menjadi 24, tepat di batas ProgressiveWebAppTest), demi tombol yang
 * tidak ada di sana.
 */

/** Rute keluar (nama `logout`); LogoutClearsDeviceDataTest memeriksa nilainya sama dengan route('logout'). */
const RUTE_KELUAR = '/logout';

interface ModulJejak {
    jumlahTitik: (nama: string) => Promise<number | null>;
}

function muatAntrean() {
    return import('@/components/mendaki/antrean');
}

/** Nama dari indexedDB.databases(); null bila peramban tidak memilikinya. */
async function daftarAtauNull(): Promise<string[] | null> {
    if (typeof indexedDB === 'undefined' || typeof indexedDB.databases !== 'function') {
        return null;
    }

    return (await indexedDB.databases()).flatMap((d) => (d.name ? [d.name] : []));
}

function kunciSesi(): string[] {
    try {
        return Array.from({ length: sessionStorage.length }, (_, i) => sessionStorage.key(i)).filter((k): k is string => k !== null);
    } catch {
        return [];
    }
}

const alatBersih: AlatBersih = {
    daftarBasisData: async () => (await daftarAtauNull()) ?? [],
    kunciSesi,
    kunciTersimpan,
    tutupKunci,
    lupakanKunci,
    // Gagal dimuat berarti modul antrean belum pernah termuat di dokumen ini, jadi memorinya kosong.
    kosongkanAntrean: () => muatAntrean().then((m) => m.kosongkanAntrean(), () => undefined),
    hapusSesi: (k) => {
        try {
            sessionStorage.removeItem(k);
        } catch {
            // sessionStorage yang diblokir tidak menyimpan antrean apa pun.
        }
    },
    hapusPaket: (k) => hapusBasisData(k),
    hapusJejak: (nama) => hapusMenurutNama(nama),
    bersihkanCache: () => bersihkanCache(),
    kosongkanOpfs: () => kosongkanOpfs(),
};

/**
 * Menghapus data perangkat. Saat keluar (saatKeluar true): semua data Memount di perangkat ini,
 * milik kunci mana pun. Penjaga ganti akun (false): hanya milik kunci selain `kunci`.
 */
export async function bersihkanPerangkat(kunci: string | null, { saatKeluar }: { saatKeluar: boolean }): Promise<HasilBersih> {
    return bersihkanDengan(alatBersih, kunci, saatKeluar);
}

/** Membuka basis data yang sudah ada menurut namanya, tanpa versi dan tanpa membuat toko apa pun. */
function bukaMenurutNama(nama: string): Promise<IDBDatabase> {
    return new Promise((selesai, gagal) => {
        const permintaan = indexedDB.open(nama);

        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

async function alatHitung(): Promise<AlatHitung> {
    const [jejak, antrean] = await Promise.all([window.muatJejak() as Promise<ModulJejak>, muatAntrean()]);

    return {
        jumlahTitik: (nama) => jejak.jumlahTitik(nama),
        aksiDiPaket: (k) =>
            aksiDiPaketDengan(
                {
                    daftarBasisData: daftarAtauNull,
                    kunciTersimpan,
                    kunciTertutup,
                    bacaMenurutNama: async (nama) => {
                        const db = await bukaMenurutNama(nama);

                        try {
                            return await antrean.penyimpananIndexedDb(db).muat();
                        } finally {
                            db.close();
                        }
                    },
                },
                k,
            ),
        aksiDiTab: (k) => antrean.penyimpananSessionStorage(k).muat(),
        gabungAntrean: antrean.gabungAntrean,
    };
}

const pengendali = buatKeluar({
    router: {
        post: (url, data, opsi) => router.post(url, data, opsi),
        on: (nama, panggil) => router.on(nama, panggil),
    },
    rute: RUTE_KELUAR,
    alat: (kunci) => ({
        hitung: async () => hitungDengan(await alatHitung(), kunci),
        tanya: (kalimat) => window.confirm(kalimat),
        bersihkan: () => bersihkanPerangkat(kunci, { saatKeluar: true }),
    }),
});

/** Keluar akun dari tombol Keluar di `asal`; ketukan selama keluar berjalan diabaikan (PT11-10). */
export function keluar(kunci: string | null, asal: AsalKeluar): Promise<void> {
    return pengendali.keluar(kunci, asal);
}

/** Benar selama keluar berjalan: menu avatar tetap terbuka supaya kalimat dan fokus tetap di dekat tombol. */
export function keluarBerjalan(): boolean {
    return pengendali.berjalan();
}

/** aria-busy dan kalimat wilayah status satu tombol Keluar (m-4: hanya tombol yang memulai). */
export function useKeadaanKeluar(asal: AsalKeluar): { sibuk: boolean; pesan: string } {
    return pandanganKeluar(useSyncExternalStore(pengendali.dengar, pengendali.keadaan, pengendali.keadaan), asal);
}

/**
 * Asal props halaman (ruling W6-11g): dokumen yang dimuat lewat tombol Kembali atau Maju memakai
 * props dari history.state, dan popstate memulihkannya di dokumen yang sama. Kunjungan Inertia
 * berikutnya membawa props dari server lagi.
 */
const riwayat = pelacakRiwayat(
    typeof performance !== 'undefined' && (performance.getEntriesByType?.('navigation')[0] as PerformanceNavigationTiming | undefined)?.type === 'back_forward',
);

if (typeof window !== 'undefined') {
    window.addEventListener('popstate', () => riwayat.popstate());
    router.on('start', () => riwayat.mulaiKunjungan());
}

/**
 * Props halaman ini jawaban server, bukan pulihan riwayat (W6-11g). Gerbang yang sama dipakai
 * pengirim jejak (kirimJejak.ts, sisipan W6-P2), supaya kunci dan pendakianAktif dari halaman
 * yang dipulihkan tombol Kembali tidak memulai kiriman.
 */
export function propsDariServer(): boolean {
    return riwayat.boleh();
}

/**
 * Penjaga ganti akun: data perangkat akun lain di peramban ini dihapus sebelum dipakai (W6-R10),
 * hanya bila kunci halaman ini jawaban server (W6-11g). Kunci dari props pulihan riwayat dapat milik
 * akun yang sudah keluar; kunjungan berikutnya membawa kunci server, dan penjaga berjalan lagi bila
 * kunci itu berbeda.
 */
export function usePenjagaPerangkat(): void {
    const { perangkat } = usePage<PropsBersama>().props;
    const kunci = perangkat?.kunci ?? null;

    useEffect(() => {
        jalankanPenjaga(kunci, riwayat, (k) => void bersihkanPerangkat(k, { saatKeluar: false }));
    }, [kunci]);
}
