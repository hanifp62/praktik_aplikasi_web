import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { adaBasisData, buka } from '@/luring/simpan';
import type { PropsBersama } from '@/types';
import { kirimAksi, pakaiPenyimpanan, pasangAntrean, payloadJeda, payloadTiba, penyimpananGabungan, penyimpananIndexedDb, penyimpananSessionStorage, putarAntrean, type KeadaanAntrean } from './antrean';
import {
    detikBergerak,
    keadaanKartu,
    keteranganNaikKosong,
    kmPadaProfil,
    naikDiantara,
    panjangGaris,
    posBerikutnya,
    proyeksikan,
    sepanjangPos,
    sisaJarak,
    type KartuLapangan,
    type KeputusanTiba,
    type Titik,
} from './hitung';
import type { DataLapangan, PosLapangan, PosisiPerangkat } from './types';
import { usePosisiLapangan, type KeadaanLokasi } from './usePosisiLapangan';

/** Pertanyaan "Sudah tiba?" yang dijawab "Belum" tidak diulang untuk pos yang sama selama ini. */
const JEDA_TANYA_MS = 120_000;

export interface ModeLapangan {
    tujuan: PosLapangan | null;
    tercapai: number;
    posisi: PosisiPerangkat | null;
    lokasi: KeadaanLokasi;
    sisa: { meter: number; cara: 'jalur' | 'garis lurus' } | null;
    naik: { meter: number; sumber: string } | null;
    /** Keterangan petak "Naik lagi" saat `naik` null, dari sebab nyata cabangnya (ruling AF-9). */
    naikKosong: string;
    perkiraan: { teks: string; rekaman: number } | null;
    keputusan: KeputusanTiba;
    /** Kartu lapangan yang pantas sekarang (W6-3m): satu keputusan dari hitung.ts, komponen hanya merender ini. */
    kartu: KartuLapangan;
    dijeda: boolean;
    /** Jeda yang sedang berjalan sejak (ms) dan detik jeda yang selesai; disimpan ke rekaman sesi paket. */
    jedaSejak: number | null;
    jedaDetik: number;
    detik: number;
    antrean: KeadaanAntrean;
    daring: boolean;
    pengumuman: string;
    /** Letak sepanjang garis yang sudah dilalui, untuk membelah garis di peta; null tanpa garis. */
    dilaluiM: number | null;
    tiba: (urutan: number) => void;
    tolakTiba: () => void;
    tolakLewat: () => void;
    jeda: () => void;
    lanjut: () => void;
}

/**
 * Keadaan mode lapangan di perangkat (spec v3 bagian 6.12): pos berikutnya, angka yang
 * tergantung posisi, tiba di pos, jeda, dan antrean kiriman. Server hanya tahu urutan pos yang
 * dicapai dan waktu jeda; keduanya diperbarui di layar lebih dulu lalu dikirim lewat antrean,
 * jadi tanpa sinyal pun pendaki melihat kemajuannya sendiri.
 */
export function useModeLapangan(data: DataLapangan): ModeLapangan {
    const [tercapai, setTercapai] = useState(data.tercapai);
    const [jedaSejak, setJedaSejak] = useState<number | null>(data.trip.jedaSejak ? Date.parse(data.trip.jedaSejak) : null);
    const [jedaDetik, setJedaDetik] = useState(data.trip.jedaDetik);
    const [sekarang, setSekarang] = useState(() => Date.now());
    const [antrean, setAntrean] = useState<KeadaanAntrean>({ jumlah: 0, pesan: null, mengirim: false, gagal: null });
    const [daring, setDaring] = useState(() => navigator.onLine);
    const [pengumuman, setPengumuman] = useState('');
    const ditanyaTolak = useRef<{ urutan: number; sampai: number } | null>(null);
    /** Rentang "lewat" tertinggi yang baru dijawab "Belum" (N-6/ruling W6-3n): angka, bukan waktu. */
    const ditanyaLewatTolak = useRef<number | null>(null);

    const dijeda = jedaSejak !== null;
    const { posisi, keadaan: lokasi } = usePosisiLapangan(data.trip.berlangsung && !dijeda);
    const kunci = usePage<PropsBersama>().props.perangkat?.kunci ?? null;

    useEffect(() => {
        // Antrean yang menunggu bertahan dari muat ulang penuh (I-1/ruling W6-3a), berkunci kunci
        // perangkat pengguna, bukan id (ruling W6-9a, PT11-3 bagian b). sessionStorage dipasang
        // segera; bila basis data paket pengguna ini sudah ada di perangkat, antrean naik ke
        // IndexedDB paket yang bertahan saat tab ditutup dan terbagi dengan shell luring, sambil
        // menyerap isi sessionStorage (M-9). Basis data tidak dibuat di sini (PT10-16). Shell luring
        // merakit props tanpa perangkat, jadi bagian ini dilewati di sana dan penyimpanannya tetap.
        let batal = false;

        if (kunci !== null) {
            pakaiPenyimpanan(penyimpananSessionStorage(kunci), kunci);
            void adaBasisData(kunci)
                .then(async (ada) => {
                    if (!ada || batal) {
                        return;
                    }

                    const db = await buka(kunci);

                    if (!batal) {
                        pakaiPenyimpanan(penyimpananGabungan(penyimpananIndexedDb(db), penyimpananSessionStorage(kunci)), kunci);
                    }
                })
                .catch(() => undefined);
        }

        const pewaktu = window.setInterval(() => setSekarang(Date.now()), 5_000);
        // Putaran 30 detik tetap ada: kalimat "Memount mencoba lagi otomatis." di teksAntrean
        // (Q-12/ruling W6-3z) benar karena putaran ini.
        const ulang = window.setInterval(() => void putarAntrean(), 30_000);
        const naik = () => {
            setDaring(true);
            void putarAntrean();
        };
        const turun = () => setDaring(false);
        // Mendengar, lalu memutar antrean sekali saat dipasang bila daring (W6-3z).
        const lepas = pasangAntrean(setAntrean);

        window.addEventListener('online', naik);
        window.addEventListener('offline', turun);

        return () => {
            window.clearInterval(pewaktu);
            window.clearInterval(ulang);
            window.removeEventListener('online', naik);
            window.removeEventListener('offline', turun);
            lepas();
            batal = true;
        };
    }, [kunci]);

    const garis = data.garis;
    const panjang = useMemo(() => (garis ? panjangGaris(garis) : 0), [garis]);
    const sepanjang = useMemo(() => new Map(data.pos.map((p) => [p.urutan, sepanjangPos(garis, p.lng, p.lat)])), [data.pos, garis]);
    const tujuan = posBerikutnya(data.pos, tercapai);
    const titikPosisi: Titik | null = posisi ? [posisi.lng, posisi.lat] : null;
    const letak = garis && titikPosisi ? proyeksikan(garis, titikPosisi) : null;
    const sepanjangTujuan = tujuan ? (sepanjang.get(tujuan.urutan) ?? null) : null;
    const sepanjangTercapai = tercapai > 0 ? (sepanjang.get(tercapai) ?? null) : garis ? 0 : null;

    const sisa = tujuan && titikPosisi ? sisaJarak(garis, titikPosisi, { lng: tujuan.lng, lat: tujuan.lat, sepanjangM: sepanjangTujuan }) : null;

    // Naik lagi dari profil elevasi: dari letak pendaki di garis bila ia dekat garis, selain itu
    // dari pos terakhir yang dicapai, sampai pos tujuan. Tanpa profil atau garis, dari ketinggian
    // tercatat pos terakhir dan pos tujuan (selisih bersih, jadi paling sedikit sebesar itu).
    // Ketinggian GPS tidak pernah dipakai.
    const dari = sisa?.cara === 'jalur' && letak ? letak.sepanjangM : sepanjangTercapai;
    const naikProfil =
        data.profil && tujuan && sepanjangTujuan !== null && dari !== null
            ? naikDiantara(data.profil, kmPadaProfil(dari, panjang, data.profil) ?? 0, kmPadaProfil(sepanjangTujuan, panjang, data.profil) ?? 0)
            : null;
    const posLalu = data.pos.find((p) => p.urutan === tercapai);
    const naik =
        naikProfil !== null
            ? { meter: naikProfil, sumber: 'dari profil elevasi' }
            : tujuan?.elevasi != null && posLalu?.elevasi != null
              ? { meter: Math.max(0, tujuan.elevasi - posLalu.elevasi), sumber: 'dari ketinggian pos' }
              : null;
    // Sebab angka kosong dari syarat yang sama dengan kedua cabang di atas (ruling AF-9).
    const naikKosong = keteranganNaikKosong({
        profil: (data.profil?.length ?? 0) >= 2,
        letakDiGaris: sepanjangTujuan !== null && dari !== null,
        tercapai,
        ketinggianPos: tujuan?.elevasi != null && posLalu?.elevasi != null,
    });

    const ruas = tujuan ? data.ruas.find((r) => r.ke === tujuan.urutan) : undefined;
    const perkiraan = ruas?.perkiraan ? { teks: ruas.perkiraan, rekaman: ruas.rekaman ?? 0 } : null;

    // Keputusan tiba dan kartu lapangan (tiba dan pos terlampaui), satu panggilan dengan nilai
    // mentah (Q-1/ruling W6-3p): jarak ke tujuan, keputusanTiba, posTerlampaui, dan argumen
    // kartuLapangan dirakit di keadaanKartu (hitung.ts), tidak di sini. Gerbangnya diuji Node
    // satu kasus per gerbang (FieldModeFiguresTest::test_the_card_state_has_one_case_per_gate),
    // dan blok panggilan ini dijaga satu regex yang menuntut nama argumen mentahnya persis
    // (HikeModeCheckpointTest::test_the_hook_hands_raw_values_to_one_card_function). Nilai
    // mentahnya sendiri (posisi, tujuan, letak, sepanjang) dihitung di atas dan tidak dijaga
    // kedua test itu.
    const { keputusan, kartu } = keadaanKartu({
        posisi,
        tujuan,
        dijeda,
        berlangsung: data.trip.berlangsung,
        radiusTiba: data.radiusTiba,
        pos: data.pos.map((p) => ({ urutan: p.urutan, sepanjangM: sepanjang.get(p.urutan) ?? null })),
        tercapai,
        letak,
        tolakTiba: ditanyaTolak.current,
        tolakLewatSampai: ditanyaLewatTolak.current,
        sekarang,
    });

    const tiba = (urutan: number) => {
        const sesudah = posBerikutnya(data.pos, urutan);
        const nama = data.pos.find((p) => p.urutan === urutan)?.nama ?? `pos ${urutan}`;

        setTercapai((lalu) => Math.max(lalu, urutan));
        setPengumuman(sesudah ? `Tiba di ${nama}. Berikutnya ${sesudah.nama}.` : `Tiba di ${nama}, pos terakhir jalur ini.`);

        // Getar menghormati setelan sistem DAN setelan Gerak aplikasi (M-4/ruling W6-3g):
        // data-gerak="kurangi" datang dari Profil/Tampilan, bukan hanya dari OS (app.css).
        const gerakDikurangi = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches || document.documentElement.dataset.gerak === 'kurangi';

        if (typeof navigator.vibrate === 'function' && !gerakDikurangi) {
            navigator.vibrate(200);
        }

        void kirimAksi(data.urls.tiba, payloadTiba(urutan));
    };

    // Tiba otomatis bila jarak ditambah akurasi masuk radius; sekali per pos.
    useEffect(() => {
        if (keputusan === 'tiba' && tujuan && data.trip.berlangsung) {
            tiba(tujuan.urutan);
        }
    }, [keputusan, tujuan?.urutan]);

    const jeda = () => {
        const waktu = Date.now();

        setJedaSejak(waktu);
        setPengumuman('Pendakian dijeda. Lokasi berhenti diperbarui sampai Anda melanjutkan.');
        void kirimAksi(data.urls.jeda, payloadJeda(new Date(waktu).toISOString()));
    };

    const lanjut = () => {
        const waktu = Date.now();

        if (jedaSejak !== null) {
            setJedaDetik((lalu) => lalu + Math.max(0, Math.round((waktu - jedaSejak) / 1000)));
        }

        setJedaSejak(null);
        setPengumuman('Pendakian dilanjutkan.');
        void kirimAksi(data.urls.lanjut, payloadJeda(new Date(waktu).toISOString()));
    };

    return {
        tujuan,
        tercapai,
        posisi,
        lokasi,
        sisa,
        naik,
        naikKosong,
        perkiraan,
        keputusan,
        kartu,
        dijeda,
        jedaSejak,
        jedaDetik,
        detik: data.trip.mulai ? detikBergerak(Date.parse(data.trip.mulai), jedaDetik, jedaSejak, sekarang) : 0,
        antrean,
        daring,
        pengumuman,
        dilaluiM: sisa?.cara === 'jalur' && letak ? letak.sepanjangM : sepanjangTercapai,
        tiba,
        tolakTiba: () => {
            if (tujuan) {
                ditanyaTolak.current = { urutan: tujuan.urutan, sampai: Date.now() + JEDA_TANYA_MS };
                setSekarang(Date.now());
            }
        },
        tolakLewat: () => {
            // Tombol "Belum" hanya ada selagi kartu.lewat tampil, jadi rentangnya sama dengan
            // keluaran posTerlampaui di keadaanKartu (rentang yang ditunda tidak pernah tampil).
            if (kartu.lewat) {
                ditanyaLewatTolak.current = kartu.lewat.sampai;
                setSekarang(Date.now());
            }
        },
        jeda,
        lanjut,
    };
}
