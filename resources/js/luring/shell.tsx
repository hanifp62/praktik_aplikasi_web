import '../dasar.js';
import { createInertiaApp } from '@inertiajs/react';
import { useLayoutEffect, type ComponentType, type ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { pakaiPenyimpanan, penyimpananGabungan, penyimpananIndexedDb, penyimpananSessionStorage } from '@/components/mendaki/antrean';
import { dataLapanganDariPaket, teksBagian, terbaruPerTrip } from './baca';
import { DaftarPaket, type GalatPaket, type PaketDiPerangkat } from './DaftarPaket';
import { kalimatGagalBuka, labelPaket, propsHalamanLuring } from './rakit';
import { bacaBagian, buka, kunciTersimpan, semuaPaket, type RekamanPaket } from './simpan';

/*
 * Shell luring (spec v3 bagian 10.4; rencana induk W6 butir 3; ruling W6-R9). Dibuka service
 * worker dari cache ketika dokumen gagal dimuat, jadi isinya tidak boleh berasal dari server:
 * yang dibaca hanya paket yang diunduh pendaki sendiri, dari IndexedDB perangkat ini, dan
 * hanya paket yang sudah lolos pemeriksaan utuh ("Siap luring").
 *
 * Mode lapangan dari paket adalah halaman Trips/Hike yang sama dengan versi daring, dengan props
 * dari satu fungsi, propsHalamanLuring di rakit.ts (PT9-1, PT10-1), dan data dari
 * dataLapanganDariPaket di baca.ts, sumber yang sama dengan sakelar "Pakai paket saja" (W6 Tugas
 * 10). Halamannya dimuat malas saat paket dibuka (ruling W6-9l): impor statis membuat kunci
 * halaman itu hilang dari manifest build, dan pramuat mode lapangan daring jatuh ke entri dasar.
 * Halaman Inertia rakitan memakai alamat yang sedang dibuka (ruling W6-9k), jadi muat ulang
 * dengan sinyal kembali ke halaman asal, dan tanpa sinyal ke shell ini lagi lewat service worker.
 *
 * Teks pengakuan luring baru disembunyikan sesudah mode lapangan terpasang, lalu fokus pindah ke
 * #konten (PT9-2). Kegagalan membuka satu paket disebut di kartu paket itu, yang menerima fokus
 * (tinjauan m-1); kabar keadaan daftar di #luring-kabar, yang selalu terpasang di
 * offline.blade.php (PT9-3). Halaman tidak pernah kosong tanpa kalimat. Antrean aksi memakai
 * IndexedDB paket dan menyerap antrean sessionStorage tab yang sama (ruling W6-9a); penyimpanan
 * itu dipasang lebih dulu, karena dataLapanganDariPaket menerapkan aksi yang masih menunggu ke
 * keadaan sesi (ruling W6-9d).
 */

const TANPA_PAKET = 'Belum ada paket lapangan yang siap di perangkat ini.';
const TIDAK_TERBACA = 'Paket lapangan di perangkat ini tidak dapat dibaca. Unduh ulang selagi ada sinyal.';

/** Label yang sama dengan daftar paket: trip, jalur, dan gunung tanpa pengulangan, dan tanggal trip. */
function labelTrip(paket: RekamanPaket): string {
    return labelPaket(paket.manifest.trip);
}

function kabar(teks: string): void {
    const wilayah = document.getElementById('luring-kabar');

    if (wilayah) {
        wilayah.textContent = teks;
    }
}

let membuka = false;

/** Menggambar ulang daftar paket dengan kegagalan terakhir; null sampai daftar tergambar. */
let tampilkanDaftar: ((galat: GalatPaket | null) => void) | null = null;

function gagalBuka(paket: RekamanPaket): void {
    membuka = false;
    document.getElementById('luring-pesan')?.classList.remove('hidden');

    if (tampilkanDaftar) {
        tampilkanDaftar({ tripId: paket.tripId, versi: paket.versi, pesan: kalimatGagalBuka(labelTrip(paket)) });

        return;
    }

    const wilayah = document.getElementById('luring-galat');

    if (wilayah) {
        wilayah.textContent = kalimatGagalBuka(labelTrip(paket));
        wilayah.focus();
    }
}

function namaMerek(): string {
    return document.querySelector('meta[name="application-name"]')?.getAttribute('content') ?? 'Memount';
}

/** Pesan luring disembunyikan dan fokus dipindah hanya sesudah mode lapangan benar-benar terpasang. */
function SesudahPasang({ children }: { children: ReactNode }) {
    useLayoutEffect(() => {
        document.getElementById('luring-pesan')?.classList.add('hidden');
        // Tombol buka ada di bawah daftar; tanpa ini pendaki mendarat di tengah mode lapangan,
        // dengan pos berikutnya dan peta di atas layar. preventScroll: fokus pada #konten tidak
        // menggulirnya ke bawah kepala yang menempel.
        window.scrollTo(0, 0);
        document.getElementById('konten')?.focus({ preventScroll: true });
    }, []);

    return children;
}

async function bukaLapangan(kunci: string, db: IDBDatabase, paket: RekamanPaket): Promise<void> {
    // Ketukan ganda (sarung tangan, layar basah) tidak memasang dua aplikasi pada #app (PT9-23).
    if (membuka) {
        return;
    }

    membuka = true;

    try {
        // Penyimpanan dipasang lebih dulu: dataLapanganDariPaket membaca antrean yang menunggu.
        pakaiPenyimpanan(penyimpananGabungan(penyimpananIndexedDb(db), penyimpananSessionStorage(kunci)), kunci);

        const data = await dataLapanganDariPaket(kunci, paket);
        const props = propsHalamanLuring(data, paket.manifest, namaMerek());
        const { default: Hike } = (await import('@/pages/Trips/Hike')) as { default: ComponentType };

        await createInertiaApp({
            id: 'app',
            page: {
                component: 'Trips/Hike',
                url: window.location.pathname + window.location.search,
                version: null,
                rescuedProps: [],
                flash: {},
                rememberedState: {},
                props,
            },
            resolve: () => Hike,
            setup: ({ el, App, props: awal }) => {
                if (!el) {
                    throw new Error('Wadah mode lapangan tidak ada.');
                }

                createRoot(el, { onUncaughtError: () => gagalBuka(paket) }).render(
                    <SesudahPasang>
                        <App {...awal} />
                    </SesudahPasang>,
                );
            },
        });
    } catch {
        gagalBuka(paket);
    }
}

async function mulai(): Promise<void> {
    // /offline yang tersimpan membawa data-theme dari saat pemasangan; tema sekarang dari cookie (PT9-19).
    window.terapkanTema?.();

    const wadah = document.getElementById('paket-luring');
    const kunci = kunciTersimpan();

    if (!wadah) {
        return;
    }

    if (!kunci) {
        kabar(TANPA_PAKET);

        return;
    }

    const db = await buka(kunci);
    const rekaman = terbaruPerTrip((await semuaPaket(db)).filter((p) => p.siap));
    const hasil = await Promise.allSettled(
        rekaman.map(async (r): Promise<PaketDiPerangkat> => {
            const bagian = await bacaBagian(db, r.tripId, r.versi);

            return { rekaman: r, rencana: JSON.parse(teksBagian(bagian, 'rencana')), dinamis: JSON.parse(teksBagian(bagian, 'dinamis')) };
        }),
    );
    const paket = hasil.flatMap((h) => (h.status === 'fulfilled' ? [h.value] : []));
    const gagal = rekaman
        .filter((_, i) => hasil[i]?.status === 'rejected')
        .map((r) => `Paket ${labelTrip(r)} tidak dapat dibaca di perangkat ini. Unduh ulang selagi ada sinyal.`);

    if (paket.length > 0) {
        const akar = createRoot(wadah);
        const sekarangMs = Date.now();

        tampilkanDaftar = (galat) => akar.render(<DaftarPaket paket={paket} sekarangMs={sekarangMs} galat={galat} onBuka={(p) => void bukaLapangan(kunci, db, p.rekaman)} />);
        tampilkanDaftar(null);
    }

    kabar([paket.length === 0 && gagal.length === 0 ? TANPA_PAKET : '', ...gagal].filter((k) => k !== '').join(' '));
}

mulai().catch(() => kabar(TIDAK_TERBACA));
