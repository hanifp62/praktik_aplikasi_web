import { usePage } from '@inertiajs/react';
import { useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import { buatToko, kabarPantauPintu, keadaanPaketDiperiksa, keadaanPintuLama, type HasilShell, type KeadaanPintu } from '@/luring/alur';
import { paketSiap } from '@/luring/baca';
import { ambilManifest, cekRuang, GagalPaket, mintaMenetap, pesanGalat, tabSafariIphone, unduhPaket } from '@/luring/paket';
import { kalimatShell } from '@/luring/periksa';
import { pantauShellPaketDiSini, pulihkanShellPaket } from '@/luring/shellPaket';
import type { ManifestPaket, RekamanPaket } from '@/luring/simpan';
import { pack } from '@/routes/trips';
import type { PropsBersama } from '@/types';

type Keadaan = KeadaanPintu;

/** Jam di perangkat ini dengan penanda zonanya (butir 6, PT10-17). */
const waktu = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' });

const ISI =
    'Isinya: garis jalur dan pos, profil elevasi, rencana dengan aturan resmi dan daftar persiapan, serta potret status, cuaca, dan laporan komunitas saat paket dibuat. Peta dasar tidak ikut: di jalur, garis tampil di atas kanvas polos.';
const TIDAK_TERBACA = 'Penyimpanan paket di perangkat ini belum dapat dibaca, misalnya karena penyimpanan peramban diblokir atau jendela privat.';
const SIAPKAN_PUTUS = 'Paket belum dapat disiapkan. Coba lagi selagi ada sinyal.';
const UNDUH_PUTUS = 'Unduhan terputus. Bagian yang sudah utuh disimpan; coba lagi selagi ada sinyal.';
const PERBARUI_PUTUS = 'Paket belum dapat diperbarui. Coba lagi selagi ada sinyal.';
/** Selama service worker disiapkan dan shell diperiksa (tinjauan Tugas 10 m-4). */
const SIAPKAN_SHELL = 'Menyiapkan halaman luring di peramban ini.';
/** Sesudah service worker pengendali berganti, sebelum pemeriksaan ulang selesai (ruling W6-10e). */
const PERIKSA_ULANG = 'Memeriksa ulang halaman luring sesudah aplikasi diperbarui.';

function teksDiperiksa(paket: RekamanPaket): string {
    return paket.diverifikasi ? waktu.format(new Date(paket.diverifikasi)) : 'tanpa waktu tercatat';
}

function kalimatSiap(paket: RekamanPaket): string {
    return `Siap luring. Diperiksa utuh di perangkat ini ${teksDiperiksa(paket)}.`;
}

function kalimatLama(lama: RekamanPaket): string {
    return `Paket versi ${teksDiperiksa(lama)} tetap siap luring di perangkat ini.`;
}

/** Kalimat keadaan, satu untuk setiap tahap; ditulis di judul pintu yang selalu terpasang. */
function kalimatKeadaan(keadaan: Keadaan): string {
    switch (keadaan.tahap) {
        case 'memeriksa':
            return 'Memeriksa paket di perangkat ini.';
        case 'kosong':
            return 'Belum ada paket untuk trip ini di perangkat ini.';
        case 'pratinjau':
            return keadaan.ruang.kalimat;
        case 'mengunduh':
            return keadaan.kemajuan.tahap === 'shell' ? SIAPKAN_SHELL : `Mengunduh dan memeriksa: ${keadaan.kemajuan.selesai} dari ${keadaan.kemajuan.jumlah} bagian utuh.`;
        case 'siap':
            return kalimatSiap(keadaan.paket);
        case 'belumLuring':
            return kalimatShell(keadaan.shell, 'paket-ada');
        case 'gagal':
            return keadaan.pesan;
    }
}

/** Kabar untuk wilayah status sesudah keadaan berganti (siap, belum luring, atau gagal). */
function kabarKeadaan(keadaan: Keadaan): string {
    switch (keadaan.tahap) {
        case 'siap':
            return keadaan.pesan ?? kalimatSiap(keadaan.paket);
        case 'belumLuring':
            return [keadaan.pesan, kalimatShell(keadaan.shell, 'paket-ada')].filter(Boolean).join(' ');
        case 'gagal':
            return keadaan.lama && keadaan.sebutLama ? `${keadaan.pesan} ${kalimatLama(keadaan.lama)}` : keadaan.pesan;
        default:
            return kalimatKeadaan(keadaan);
    }
}

/** Fokus sesudah keadaan berganti: judul bila siap, selain itu tombol utama keadaan baru (PT10-4). */
function fokusKeadaan(keadaan: Keadaan): 'utama' | 'judul' {
    return keadaan.tahap === 'siap' ? 'judul' : 'utama';
}

/**
 * Pintu unduh paket lapangan (spec v3 bagian 10.2; rencana induk W6 butir 5; ruling W6-R9), di
 * pratinjau hari-H dan ruang kerja trip. Sebelum mengunduh: isi, ukuran menurut manifest, sumber
 * datanya (atribusi manifest), dan sisa ruang. Unduhan berjalan di halaman, kemajuannya dihitung
 * dari bagian yang sudah terverifikasi, dan "Siap luring" baru ditulis setelah paket dibaca ulang
 * dan cocok serta shell luring tersimpan. Paket yang sudah ada diperiksa shell-nya setiap kali
 * pintu ini dimuat, dan disiapkan ulang sekali bila hilang (ruling W6-9n); selama pintu terbuka,
 * setiap service worker pengendali yang berganti (deploy) memicu pemeriksaan ulang, dan "Siap
 * luring" diturunkan bila shell tidak lagi tersimpan (ruling W6-10e). Keadaan siap yang ditulis
 * sesudah pengendali berganti di tengah aksi diperiksa ulang lebih dulu. "Siap luring" tidak pernah
 * tampil tanpa pemeriksaan yang lulus. Gagal disebut spesifik dengan "Coba lagi"; perbarui yang
 * gagal atau versi yang sama menyebut paket lama "tetap siap luring" atau "sudah versi terbaru"
 * hanya bila shell untuk paket itu lulus diperiksa SAAT itu, dan kegagalan shell saat unduh tidak
 * pernah ditulis begitu (PT10-8; tinjauan Tugas 10 I-2, keadaanPaketLama). Hanya tampil bila
 * sakelar paket menyala (Q10, Q14).
 *
 * Keputusan keadaannya ada di alur.ts dan diuji di Node (tinjauan Tugas 10 m-D): keadaan sesudah
 * unduh, saat dimuat, dan sesudah "Siapkan ulang" (keadaanPaketDiperiksa), paket lama sesudah
 * perbarui (keadaanPintuLama), dan pemeriksaan ulang sesudah pengendali berganti, termasuk "tetap
 * siap luring" (kabarPantauPintu, m-C). Setiap pemeriksaan menunggu pengendali baru selesai
 * diaktifkan (ruling W6-10g, lewat pulihkanShellPaket). Keadaan disimpan di satu toko yang dibaca
 * pemantau seketika dan dicerminkan ke render lewat langganan; komponen ini tidak menulis keadaan
 * selain lewat toko itu.
 *
 * Aksesibilitas (PT10-4, butir 7): kalimat keadaan ada di satu paragraf judul yang selalu
 * terpasang dan dapat difokus; kabar aksi dibacakan lewat wilayah role="status" terpisah yang hanya
 * diisi penangan, jadi muat halaman tidak membacakan apa pun. Setiap penangan memindah fokus ke
 * tombol utama keadaan baru (Unduh paket, Periksa ruang lagi, Coba lagi, Siapkan ulang) atau ke
 * judul bila tanpa tombol (mengunduh, siap), tetapi hanya bila fokus masih di pintu ini atau sudah
 * jatuh ke body (W6-3r). Ketukan kedua selama aksi berjalan diabaikan (PT10-12).
 */
export function PintuPaket({ tripId }: { tripId: number }) {
    const { perangkat, merek } = usePage<PropsBersama>().props;
    const kunci = perangkat?.kunci ?? null;
    const aktif = perangkat?.paket === true && kunci !== null;
    const [toko] = useState(() => buatToko<Keadaan>({ tahap: 'memeriksa' }));
    const [keadaan, setKeadaan] = useState<Keadaan>(toko.ambil);
    const [sibuk, setSibuk] = useState<string | null>(null);
    const [kabar, setKabar] = useState('');
    const berjalan = useRef(false);
    const fokusKe = useRef<'utama' | 'judul' | null>(null);
    const wadah = useRef<HTMLDivElement>(null);
    const judul = useRef<HTMLParagraphElement>(null);
    const idUtama = `${useId()}-utama`;
    /** Bertambah setiap service worker pengendali berganti (ruling W6-10e). */
    const gantiKe = useRef(0);
    const hitunganGanti = () => gantiKe.current;

    // Render mencerminkan toko; semua tulisan keadaan lewat toko.ubah (tinjauan Tugas 10 m-D).
    useEffect(() => {
        setKeadaan(toko.ambil());

        return toko.langganan(setKeadaan);
    }, [toko]);

    useEffect(() => {
        if (!aktif || !kunci) {
            return;
        }

        let batal = false;

        (async (): Promise<Keadaan> => {
            const paket = await paketSiap(kunci, tripId);

            if (!paket) {
                return { tahap: 'kosong' };
            }

            const awal = gantiKe.current;

            return keadaanPaketDiperiksa(paket, null, await pulihkanShellPaket(), awal, () => gantiKe.current, pulihkanShellPaket);
        })().then(
            (baru) => {
                if (!batal) {
                    toko.ubah(baru);
                }
            },
            () => {
                if (!batal) {
                    toko.ubah({ tahap: 'gagal', pesan: TIDAK_TERBACA, lama: null, sebutLama: false });
                }
            },
        );

        return () => {
            batal = true;
        };
    }, [aktif, kunci, tripId, toko]);

    // Pemeriksaan ulang setiap service worker pengendali berganti selama pintu terbuka (ruling
    // W6-10e, W6-10g, tinjauan Tugas 10 I-1, I-A, m-C): keputusannya kabarPantauPintu (alur.ts,
    // diuji Node). Fokus hanya pindah bila fokus ada di pintu ini atau sudah jatuh ke body (W6-3r,
    // lewat fokusKe).
    useEffect(() => {
        if (!aktif) {
            return;
        }

        return pantauShellPaketDiSini(
            kabarPantauPintu(toko, () => berjalan.current, {
                ganti: () => {
                    gantiKe.current += 1;
                },
                memeriksa: (sedang) => setSibuk(sedang ? PERIKSA_ULANG : null),
                ubah: (baru) => {
                    fokusKe.current = fokusKeadaan(baru);
                    setKabar(kabarKeadaan(baru));
                },
            }),
        );
    }, [aktif, toko]);

    useLayoutEffect(() => {
        const ke = fokusKe.current;

        if (ke === null) {
            return;
        }

        fokusKe.current = null;

        const sekarang = document.activeElement;

        if (sekarang !== null && sekarang !== document.body && !wadah.current?.contains(sekarang)) {
            return;
        }

        ((ke === 'utama' ? document.getElementById(idUtama) : null) ?? judul.current)?.focus();
    });

    if (!aktif || !kunci) {
        return null;
    }

    if (tabSafariIphone()) {
        return (
            <p className="mt-2 text-kecil text-primary">
                Di iPhone dan iPad, paket yang diunduh dari tab peramban tersimpan terpisah dari aplikasi di Layar Utama, dan aplikasi itulah yang Anda buka di jalur. Pasang{' '}
                {merek.nama} ke Layar Utama dulu (Bagikan, lalu Tambahkan ke Layar Utama), lalu unduh paket dari sana.
            </p>
        );
    }

    const ubah = (baru: Keadaan, pengumuman: string, fokus: 'utama' | 'judul') => {
        fokusKe.current = fokus;
        setSibuk(null);
        toko.ubah(baru);
        setKabar(pengumuman);
    };

    /** Keadaan siap, belum luring, atau gagal yang diputuskan alur.ts, dengan kabar dan fokusnya. */
    const tampilkan = (baru: Keadaan) => {
        ubah(baru, kabarKeadaan(baru), fokusKeadaan(baru));
    };

    const kabarkan = (teks: string) => {
        setSibuk(teks);
        setKabar(teks);
    };

    const gagal = (pesan: string, lama: RekamanPaket | null, sebutLama: boolean) => {
        tampilkan({ tahap: 'gagal', pesan, lama, sebutLama });
    };

    /** Paket lama sesudah perbarui tanpa versi baru (tinjauan Tugas 10 I-2, m-D): keadaanPintuLama. */
    const terapkanLama = async (galat: unknown, pesan: string, lama: RekamanPaket, menetap: boolean | null) => {
        kabarkan(SIAPKAN_SHELL);
        tampilkan(await keadaanPintuLama(galat, pesan, lama, menetap, hitunganGanti, pulihkanShellPaket));
    };

    const siapkan = async () => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;
        kabarkan('Membaca ukuran paket dan sisa ruang perangkat.');

        try {
            const manifest = await ambilManifest(pack.url(tripId));
            const ruang = await cekRuang(manifest);

            ubah({ tahap: 'pratinjau', manifest, ruang }, ruang.kalimat, 'utama');
        } catch (galat) {
            gagal(pesanGalat(galat, SIAPKAN_PUTUS), null, false);
        } finally {
            berjalan.current = false;
        }
    };

    const periksaRuangLagi = async (manifest: ManifestPaket) => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;

        try {
            const ruang = await cekRuang(manifest);

            ubah({ tahap: 'pratinjau', manifest, ruang }, ruang.kalimat, 'utama');
        } finally {
            berjalan.current = false;
        }
    };

    /**
     * Unduhan sesudah izin penyimpanan diminta; keadaan siap hanya dari unduhan yang lolos, dan
     * diperiksa ulang bila pengendali berganti selama unduhan (W6-10e). Kegagalan saat memperbarui
     * menyebut paket lama hanya lewat terapkanLama (I-2).
     */
    const unduhInti = async (manifest: ManifestPaket, lama: RekamanPaket | null, menetap: boolean) => {
        const awal = gantiKe.current;

        try {
            const paket = await unduhPaket(kunci, manifest, (kemajuan) => {
                toko.ubah({ tahap: 'mengunduh', kemajuan });

                if (kemajuan.tahap === 'shell') {
                    setKabar(SIAPKAN_SHELL);
                }
            });

            tampilkan(await keadaanPaketDiperiksa(paket, menetap, 'siap', awal, hitunganGanti, pulihkanShellPaket));
        } catch (galat) {
            if (lama) {
                await terapkanLama(galat, pesanGalat(galat, UNDUH_PUTUS), lama, menetap);
            } else {
                gagal(pesanGalat(galat, UNDUH_PUTUS), null, false);
            }
        }
    };

    const unduh = async (manifest: ManifestPaket) => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;

        try {
            // Keadaan mengunduh lebih dulu, jadi tombol Unduh sudah tidak ada selama peramban
            // menanyakan izin penyimpanan (PT10-12).
            ubah({ tahap: 'mengunduh', kemajuan: { selesai: 0, jumlah: manifest.bagian.length } }, 'Mengunduh paket.', 'judul');
            // persist() diminta pada ketukan ini: peramban mensyaratkan gestur pengguna.
            const menetap = await mintaMenetap();

            await unduhInti(manifest, null, menetap);
        } finally {
            berjalan.current = false;
        }
    };

    const perbarui = async (lama: RekamanPaket) => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;

        try {
            // Langkah pertama pada ketukan, sebelum menunggu jaringan (PT10-18 bagian b).
            const menetap = await mintaMenetap();
            let manifest: ManifestPaket;

            kabarkan('Memeriksa versi paket di server.');

            try {
                manifest = await ambilManifest(pack.url(tripId));
            } catch (galat) {
                if (galat instanceof GagalPaket && galat.status === 404) {
                    gagal(`Paket untuk trip ini tidak tersedia lagi dari server. Paket lama masih ada di perangkat ini dengan data per ${teksDiperiksa(lama)}.`, lama, false);
                } else {
                    await terapkanLama(galat, pesanGalat(galat, PERBARUI_PUTUS), lama, menetap);
                }

                return;
            }

            if (manifest.versi === lama.versi) {
                // Versi sama: shell paket lama diperiksa dan dipulihkan dulu (I-2, W6-9n).
                await terapkanLama(null, '', lama, menetap);

                return;
            }

            ubah({ tahap: 'mengunduh', kemajuan: { selesai: 0, jumlah: manifest.bagian.length } }, 'Mengunduh paket.', 'judul');
            await unduhInti(manifest, lama, menetap);
        } finally {
            berjalan.current = false;
        }
    };

    const siapkanUlang = async (paket: RekamanPaket) => {
        if (berjalan.current) {
            return;
        }

        berjalan.current = true;
        kabarkan(SIAPKAN_SHELL);

        try {
            const awal = gantiKe.current;
            const shell = await pulihkanShellPaket().catch((): HasilShell => 'gagal');

            tampilkan(await keadaanPaketDiperiksa(paket, null, shell, awal, hitunganGanti, pulihkanShellPaket));
        } finally {
            berjalan.current = false;
        }
    };

    return (
        <div ref={wadah} className="mt-2 space-y-2">
            <p ref={judul} tabIndex={-1} className={kelas('text-kecil font-bold text-primary', fokusTerlihat)}>
                {sibuk ?? kalimatKeadaan(keadaan)}
            </p>

            {keadaan.tahap === 'kosong' ? <p className="text-kecil text-secondary">{ISI}</p> : null}

            {keadaan.tahap === 'pratinjau' && keadaan.manifest.atribusi.length > 0 ? (
                <ul className="space-y-1 text-kecil text-secondary [overflow-wrap:anywhere]">
                    {keadaan.manifest.atribusi.map((baris, i) => (
                        <li key={`${i}-${baris}`}>{baris}</li>
                    ))}
                </ul>
            ) : null}

            {keadaan.tahap === 'siap' && keadaan.menetap === false ? (
                <p className="text-kecil text-secondary">Peramban belum menjamin menyimpan paket ini; bila ruang menipis, peramban boleh menghapusnya. Periksa lagi di halaman ini sebelum berangkat.</p>
            ) : null}
            {keadaan.tahap === 'siap' && keadaan.pesan ? <p className="text-kecil text-secondary">{keadaan.pesan}</p> : null}
            {keadaan.tahap === 'belumLuring' && keadaan.pesan ? <p className="text-kecil text-secondary">{keadaan.pesan}</p> : null}
            {keadaan.tahap === 'gagal' && keadaan.lama && keadaan.sebutLama ? <p className="text-kecil text-secondary">{kalimatLama(keadaan.lama)}</p> : null}

            <p role="status" aria-atomic="true" className="sr-only">
                {kabar}
            </p>

            {keadaan.tahap === 'kosong' ? (
                <Button key="siapkan" id={idUtama} variant="secondary" size="sm" onClick={() => void siapkan()}>
                    Siapkan paket luring
                </Button>
            ) : null}

            {keadaan.tahap === 'pratinjau' ? (
                keadaan.ruang.boleh ? (
                    <Button key="unduh" id={idUtama} size="sm" onClick={() => void unduh(keadaan.manifest)}>
                        Unduh paket
                    </Button>
                ) : (
                    <Button key="ruang" id={idUtama} variant="secondary" size="sm" onClick={() => void periksaRuangLagi(keadaan.manifest)}>
                        Periksa ruang lagi
                    </Button>
                )
            ) : null}

            {keadaan.tahap === 'siap' ? (
                <Button key="perbarui" variant="secondary" size="sm" onClick={() => void perbarui(keadaan.paket)}>
                    Perbarui paket
                </Button>
            ) : null}

            {keadaan.tahap === 'belumLuring' ? (
                <Button key="ulang" id={idUtama} variant="secondary" size="sm" onClick={() => void siapkanUlang(keadaan.paket)}>
                    Siapkan ulang
                </Button>
            ) : null}

            {keadaan.tahap === 'gagal' ? (
                <Button key="coba" id={idUtama} variant="secondary" size="sm" onClick={() => void (keadaan.lama ? perbarui(keadaan.lama) : siapkan())}>
                    Coba lagi
                </Button>
            ) : null}
        </div>
    );
}

