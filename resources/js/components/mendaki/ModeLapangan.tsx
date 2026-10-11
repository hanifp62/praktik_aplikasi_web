import { useLayoutEffect, useRef, type ReactNode } from 'react';
import { PetaPendakian } from '@/components/trips/PetaPendakian';
import { Alert } from '@/components/ui/Alert';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Sakelar } from '@/components/ui/Sakelar';
import { kelas } from '@/lib/kelas';
import { teksAntrean } from './antrean';
import { AKURASI_LEMAH_M, kalimatKartuLewat, teksAkurasi, teksJarak, teksNaik, teksUmur } from './hitung';
import { KartuDarurat } from './KartuDarurat';
import type { DataLapangan } from './types';
import type { ModeLapangan as KeadaanLapangan } from './useModeLapangan';
import { useLayarMenyala } from './useLayarMenyala';

const tombolLapangan = kelas('inline-flex min-h-[60px] flex-1 items-center justify-center gap-2 rounded-tombol px-4 py-4 text-bagian font-bold', fokusTerlihat, umpanTekan);
/** Tombol aksi utama suatu kartu (M-1): warna sendiri, tanpa bg-surface/text-primary bawaan
 * tombolKedua, yang sebelumnya menimpa bg-brand-700 saat keduanya digabung (urutan kelas
 * Tailwind di stylesheet, bukan urutan di atribut class, yang menentukan siapa menang). */
const tombolUtama = kelas('inline-flex min-h-12 items-center justify-center rounded-tombol bg-brand-700 px-4 py-3 text-isi font-bold text-on-brand', fokusTerlihat, umpanTekan);
const tombolKedua = kelas('inline-flex min-h-12 items-center justify-center rounded-tombol border border-control bg-surface px-4 py-3 text-isi font-bold text-primary', fokusTerlihat, umpanTekan);

/** Satu petak angka; tanpa angka, petaknya menyebut apa yang belum ada dengan huruf biasa. */
function Petak({ judul, nilai, keterangan }: { judul: string; nilai: string | null; keterangan: string }) {
    return (
        <div className="min-w-0 flex-1 rounded-kartu border border-subtle bg-surface p-3">
            <dt className="text-kecil text-secondary">{judul}</dt>
            <dd className={nilai === null ? 'mt-1 text-isi font-bold text-primary' : 'mt-1 break-words text-judul-kartu tabular-nums text-primary'}>{nilai ?? 'Belum ada'}</dd>
            <dd className="mt-1 text-meta text-secondary">{keterangan}</dd>
        </div>
    );
}

/**
 * Pembungkus kartu tiba dan kartu lewat (Q-3/ruling W6-3r). Kartu dapat lepas tanpa tombolnya
 * diketuk: pembaruan posisi, tiba otomatis, atau jeda. Bila fokus berada di dalam kartu saat ia
 * lepas, fokus dipindah ke onLepas (judul pos berikutnya), bukan jatuh ke body; fokus di luar
 * kartu tidak pernah dicuri. Tombol yang melepas kartunya sendiri tetap memindahkan fokus di
 * penangannya (W6-3o, W6-3o2), jadi saat kartu lepas fokus sudah di judul dan syarat di bawah
 * tidak terpenuhi.
 *
 * Fokus diperiksa saat lepas, di cleanup efek tata letak, bukan dicatat lewat onFocus dan
 * onBlur seperti saran ruling: React 19 menjalankan cleanup efek tata letak komponen yang
 * dilepas SEBELUM simpul DOM-nya dicabut (react-dom-client commitDeletionEffectsOnFiber), jadi
 * document.activeElement masih tombol di dalam kartu dan fokus pindah langsung ke judul tanpa
 * singgah di body. Dependensi kosong disengaja: efek yang dipasang ulang di setiap render akan
 * menjalankan cleanup-nya selagi kartu masih tampil dan memindahkan fokus dari tombol yang
 * sedang dipakai; onLepas dari render pertama cukup karena hanya membaca ref judul. Dijaga
 * asersi sumber HikeModeCheckpointTest::test_a_card_dropped_by_a_state_change_moves_focus_only_from_inside_it.
 */
function KartuJagaFokus({ onLepas, children }: { onLepas: () => void; children: ReactNode }) {
    const pembungkus = useRef<HTMLDivElement>(null);

    useLayoutEffect(() => {
        const kartu = pembungkus.current;

        return () => {
            if (kartu !== null && kartu.contains(document.activeElement)) {
                onLepas();
            }
        };
    }, []);

    return <div ref={pembungkus}>{children}</div>;
}

const kalimatLokasi: Record<string, string> = {
    mati: 'Lokasi berhenti diperbarui selama jeda.',
    menunggu: 'Menunggu posisi dari perangkat.',
    ditolak: 'Izin lokasi ditolak. Izinkan lokasi untuk peramban ini di pengaturan perangkat, atau tandai tiba di pos sendiri.',
    'tidak-didukung': 'Peramban ini tidak dapat membaca lokasi. Tandai tiba di pos sendiri.',
    galat: 'Posisi belum terbaca. Perangkat mencoba lagi.',
};

/**
 * Isi mode lapangan (spec v3 bagian 6.12, prototype 11): peta, pos berikutnya dengan namanya
 * besar, tiga petak (sisa jarak, naik lagi, perkiraan ruas), akurasi dan umur posisi, laporan
 * komunitas untuk segmen ini, layar tetap menyala, keadaan kiriman, lalu Jeda atau Lanjut dan
 * Selesai. Hanya yang dibutuhkan sambil bergerak (PRD §54); setiap angka menyebut sumber atau
 * caranya, dan yang tidak diketahui ditulis begitu (R-007). `pengaturan` dari halaman tampil di
 * bawah "Layar tetap menyala": sakelar "Pakai paket saja" dan kabar shell luring (W6 Tugas 10).
 */
export function ModeLapangan({ data, mode, selesai, pengaturan = null }: { data: DataLapangan; mode: KeadaanLapangan; selesai: ReactNode; pengaturan?: ReactNode }) {
    const layar = useLayarMenyala();
    const { tujuan, posisi, kartu } = mode;
    const kartuTiba = kartu.tiba;
    const kartuLewat = kartu.lewat;
    const laporan = tujuan ? data.laporan.find((l) => l.segmen !== null && l.segmen === tujuan.segmen) : undefined;
    const umur = posisi ? (Date.now() - posisi.waktu) / 1000 : null;
    const teksStatusAntrean = teksAntrean(mode.daring, mode.antrean);

    // Fokus tidak pernah jatuh ke body (W6-3o/W6-3o2, daftar periksa mutu butir 7): setiap tombol
    // yang melepas kartu tiba atau kartu lewat (Ya/Belum di kedua kartu), DAN tombol "Saya sudah
    // di {nama}" (yang ikut lenyap di pos terakhir karena tujuan menjadi null), memindahkan fokus
    // dengan sengaja ke judul "Pos berikutnya", satu h2 yang selalu terpasang di bawah. Dipanggil
    // SESUDAH aksinya sendiri di tombol yang sama: h2 sudah ada di DOM sebelum dan sesudah
    // elemennya dilepas, jadi fokus pindah ke sana sebelum peramban sempat menjatuhkannya ke body
    // karena elemen yang semula fokus hilang. Kartu yang lepas karena perubahan keadaan, bukan
    // karena tombolnya, ditangani KartuJagaFokus (W6-3r).
    const judulPosBerikutnya = useRef<HTMLHeadingElement>(null);
    const pindahFokusKeJudul = () => judulPosBerikutnya.current?.focus();

    return (
        <div className="space-y-4 pt-4">
            <PetaPendakian id="peta-lapangan" label={`Peta jalur ${data.trip.jalur}, pos, dan posisi Anda`} garis={data.garis} pos={data.pos} tercapai={mode.tercapai} dilaluiM={mode.dilaluiM} posisi={posisi} />

            <section aria-labelledby="judul-pos-berikutnya">
                {/* Satu h2 selalu terpasang (W6-3o): sebelumnya dua elemen h2 terpisah, satu per
                    cabang, sehingga tidak ada satu node DOM stabil untuk dituju fokus. Teksnya
                    saja yang bersyarat; ref, tabIndex={-1}, dan fokusTerlihat mengikuti pola
                    KabarDiikuti.tsx. */}
                <h2 id="judul-pos-berikutnya" ref={judulPosBerikutnya} tabIndex={-1} className={kelas('text-kecil font-bold text-secondary', fokusTerlihat)}>
                    {tujuan ? `Pos berikutnya, nomor ${tujuan.urutan}` : 'Pos berikutnya'}
                </h2>
                {tujuan ? (
                    <>
                        <p key={mode.tercapai} className={kelas('mt-1 break-words text-tampilan text-primary', mode.pengumuman.startsWith('Tiba') ? 'denyut-tiba' : null)}>
                            {tujuan.nama}
                        </p>
                        <p className="text-kecil text-secondary">{tujuan.jenis}</p>
                    </>
                ) : (
                    <p className="mt-1 text-judul-kartu text-primary">{data.pos.length === 0 ? 'Jalur ini belum punya pos tercatat.' : 'Semua pos jalur ini sudah dicapai.'}</p>
                )}
            </section>

            <p role="status" aria-atomic="true" className="text-isi font-bold text-primary">
                {mode.pengumuman}
            </p>

            {/* Pengumuman sebaris selalu terpasang sejak halaman tampil (M-2/ruling W6-3g):
                kartu Alert di bawahnya baru terpasang saat kondisinya benar, tetapi kalimat ini
                sudah ada sejak awal, jadi pembaca layar yang mendengarkan wilayah ini mendengar
                perubahannya. Satu kalimat lewat <p role="status">, bukan blok pesan yang
                dirakit sendiri (dikecualikan test_status_messages_go_through_the_alert_component).
                sr-only selagi kartunya tampil (N-9/ruling W6-3n): Alert di bawah sudah menunjukkan
                judul yang sama secara visual lewat title, dan wilayahStatus={false} di Alert
                menjaga hanya wilayah ini yang dibacakan, bukan dua wilayah untuk satu pertanyaan.
                Kartu tiba dan kartu lewat dirender dari mode.kartu, keluaran keadaanKartu di
                hitung.ts (W6-3p); komponen ini tidak menghitung gerbang apa pun. Yang menjaga:
                FieldModeFiguresTest::test_the_card_state_has_one_case_per_gate (satu kasus Node
                per gerbang), regex panggilan di hook pada
                HikeModeCheckpointTest::test_the_hook_hands_raw_values_to_one_card_function, dan
                regex gerbang Alert di sini (kartuTiba.jenis dan kartuLewat langsung di kondisinya)
                pada HikeModeCheckpointTest::test_the_page_offers_to_mark_passed_checkpoints_as_a_question. */}
            <p role="status" aria-atomic="true" className={kelas('text-kecil font-bold text-primary', kartuTiba?.jenis === 'tanya' && 'sr-only')}>
                {kartuTiba?.jenis === 'tanya'
                    ? `Sudah tiba di ${kartuTiba.nama}? Ketuk Ya atau Belum di bawah.`
                    : kartuTiba?.jenis === 'akurasi-kurang'
                      ? `Akurasi ${teksAkurasi(kartuTiba.akurasiM)} belum cukup untuk mencatat tiba otomatis.`
                      : ''}
            </p>

            {kartuTiba?.jenis === 'tanya' ? (
                <KartuJagaFokus onLepas={pindahFokusKeJudul}>
                    <Alert variant="info" title={`Sudah tiba di ${kartuTiba.nama}?`} wilayahStatus={false}>
                        <p>Posisi Anda mungkin sudah di pos ini, tetapi jaraknya belum cukup jelas untuk mencatatnya otomatis.</p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    mode.tiba(kartuTiba.urutan);
                                    pindahFokusKeJudul();
                                }}
                                className={tombolUtama}
                            >
                                Ya, sudah tiba
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    mode.tolakTiba();
                                    pindahFokusKeJudul();
                                }}
                                className={tombolKedua}
                            >
                                Belum
                            </button>
                        </div>
                    </Alert>
                </KartuJagaFokus>
            ) : null}

            {/* Pos yang sudah terlampaui di garis, dipulihkan sebagai pertanyaan, bukan kemajuan
                otomatis (I-6/ruling W6-3f). Kalimat status dan judul kartu dari satu fungsi murni
                kalimatKartuLewat (Q-7, Q-13/ruling W6-3v): bentuk tunggal bila dari === sampai, dan
                rentangnya persis yang dicatat "Ya", yang mengirim `sampai` (Q-8/ruling W6-3w).
                sr-only/wilayahStatus sama seperti kartu tiba di atas (N-9). */}
            <p role="status" aria-atomic="true" className={kelas('text-kecil font-bold text-primary', kartuLewat && 'sr-only')}>
                {kartuLewat ? kalimatKartuLewat(kartuLewat.dari, kartuLewat.sampai).status : ''}
            </p>

            {kartuLewat ? (
                <KartuJagaFokus onLepas={pindahFokusKeJudul}>
                    <Alert variant="info" title={kalimatKartuLewat(kartuLewat.dari, kartuLewat.sampai).judul} wilayahStatus={false}>
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    mode.tiba(kartuLewat.sampai);
                                    pindahFokusKeJudul();
                                }}
                                className={tombolUtama}
                            >
                                Ya, sudah dilewati
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    mode.tolakLewat();
                                    pindahFokusKeJudul();
                                }}
                                className={tombolKedua}
                            >
                                Belum
                            </button>
                        </div>
                    </Alert>
                </KartuJagaFokus>
            ) : null}

            {tujuan ? (
                <dl className="flex flex-col gap-2 min-[360px]:flex-row">
                    <Petak
                        judul="Sisa jarak"
                        nilai={mode.sisa ? teksJarak(mode.sisa.meter) : null}
                        keterangan={mode.sisa ? (mode.sisa.cara === 'jalur' ? 'menyusuri jalur' : 'garis lurus') : tujuan.lat === null ? 'pos tanpa koordinat' : 'menunggu posisi'}
                    />
                    <Petak judul="Naik lagi" nilai={mode.naik ? teksNaik(mode.naik.meter) : null} keterangan={mode.naik?.sumber ?? 'profil elevasi belum ada'} />
                    <Petak judul="Perkiraan" nilai={mode.perkiraan?.teks ?? null} keterangan={mode.perkiraan ? `ruas ini, ${mode.perkiraan.rekaman} rekaman` : 'belum ada data waktu tempuh'} />
                </dl>
            ) : null}

            <div className="space-y-1">
                {posisi ? (
                    <p className="text-kecil text-secondary">
                        <span className="tabular-nums">{teksAkurasi(posisi.akurasi)}</span> · {umur !== null ? teksUmur(umur) : null}
                    </p>
                ) : (
                    <p className="text-kecil text-secondary">{kalimatLokasi[mode.lokasi] ?? kalimatLokasi.menunggu}</p>
                )}
                {posisi && posisi.akurasi > AKURASI_LEMAH_M ? (
                    <p className="flex items-start gap-2 text-kecil font-bold text-primary">
                        <Icon name="warning" size={16} className="mt-0.5 shrink-0" />
                        Akurasi rendah: jarak dan tiba di pos kurang pasti.
                    </p>
                ) : null}
                <p className="text-meta text-secondary">
                    {data.rekamJejak
                        ? 'Lokasi diproses di perangkat ini. Rekam jejak menyala: titik disimpan di perangkat ini dan baru dikirim sesudah pendakian selesai atau dibatalkan, saat ada sinyal.'
                        : 'Lokasi diproses di perangkat ini dan tidak dikirim ke server.'}
                </p>
            </div>

            {laporan ? (
                <section aria-label="Laporan komunitas untuk segmen ini" className="rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    <p className="text-isi text-community-900">
                        <span className="font-bold">Segmen ini dilaporkan: {laporan.kondisi.toLowerCase()}.</span> Laporan komunitas, {laporan.waktu}.
                    </p>
                    {laporan.catatan ? <p className="mt-1 break-words text-kecil text-community-900">{laporan.catatan}</p> : null}
                </section>
            ) : null}

            {/* Survei tombol lain yang melepas elemennya sendiri (W6-3o butir 1): tombol ini
                tetap terpasang pada pos antara (tujuan berganti, bukan hilang); hanya pada pos
                TERAKHIR tujuan menjadi null dan tombolnya ikut lenyap bersama wrapper ini. Residu
                ini dilaporkan lalu diperbaiki lewat ruling W6-3o2: pindahFokusKeJudul dipanggil
                sesudah mode.tiba di SETIAP pemakaian tombol ini, bukan hanya pos terakhir, karena
                itu pemanggilan paling sederhana dan benar: fokus ke judul "Pos berikutnya" tetap
                masuk akal sesudah tiba di pos mana pun, dan satu baris tanpa percabangan "pos
                terakhir atau bukan" konsisten dengan keempat penangan lain serta lebih mudah
                diverifikasi. Jeda/Lanjut (di bawah) bertukar isi pada elemen <button> yang sama
                lewat percabangan if/else, bukan dilepas; Sakelar dan <summary> hanya mengganti
                keadaan, tidak pernah melepas dirinya sendiri. */}
            {tujuan && data.trip.berlangsung ? (
                <button
                    type="button"
                    onClick={() => {
                        mode.tiba(tujuan.urutan);
                        pindahFokusKeJudul();
                    }}
                    className={tombolKedua}
                >
                    Saya sudah di {tujuan.nama}
                </button>
            ) : null}

            {data.pos.length > 0 ? (
                <details className="rounded-kartu border border-subtle bg-surface">
                    <summary className={kelas('flex min-h-12 cursor-pointer items-center px-4 text-isi font-bold text-primary', fokusTerlihat)}>Semua pos ({data.pos.length})</summary>
                    <ol className="space-y-1 px-4 pb-4">
                        {data.pos.map((p) => (
                            <li key={p.id} aria-current={p.urutan === tujuan?.urutan ? 'step' : undefined} className="break-words text-isi text-primary">
                                <span className="tabular-nums">{p.urutan}.</span> {p.nama}
                                <span className="text-kecil text-secondary">
                                    {' '}
                                    · {p.urutan <= mode.tercapai ? 'sudah dicapai' : p.urutan === tujuan?.urutan ? 'berikutnya' : p.jenis}
                                </span>
                            </li>
                        ))}
                    </ol>
                </details>
            ) : null}

            {layar.didukung ? <Sakelar judul="Layar tetap menyala" keterangan="Mati bawaan: layar yang terus menyala menghabiskan baterai." nyala={layar.menyala} onUbah={layar.ubah} /> : null}
            {pengaturan}

            {/* role=status selalu terpasang (M-2/ruling W6-3g): lihat kartu tiba di atas. Kalimatnya
                dari satu fungsi murni teksAntrean di antrean.ts (Q-12/ruling W6-3z), diuji Node di
                setiap keadaan: "Luring." saat luring walau antrean kosong (W6-3z2), dan kosong hanya
                saat daring tanpa aksi menunggu dan tanpa pesan. Bingkai (border dan latar) tampil
                selama kalimatnya tidak kosong, jadi juga saat luring dengan antrean kosong. */}
            <p role="status" aria-atomic="true" className={kelas('text-kecil text-primary', teksStatusAntrean !== '' && 'rounded-kecil border border-subtle bg-surface-sunken px-3 py-2')}>
                {teksStatusAntrean}
            </p>

            {data.darurat ? <KartuDarurat darurat={data.darurat} data={data} posisi={posisi} lokasi={mode.lokasi} tercapai={mode.tercapai} /> : null}

            {data.trip.berlangsung ? (
                <div className="flex gap-3">
                    {mode.dijeda ? (
                        <button type="button" onClick={mode.lanjut} className={kelas(tombolLapangan, 'border border-control bg-surface text-primary')}>
                            Lanjut
                        </button>
                    ) : (
                        <button type="button" onClick={mode.jeda} className={kelas(tombolLapangan, 'border border-control bg-surface text-primary')}>
                            Jeda
                        </button>
                    )}
                    {selesai}
                </div>
            ) : null}
        </div>
    );
}

export { tombolLapangan };
