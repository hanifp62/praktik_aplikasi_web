import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { nadaKesegaran } from '@/components/trails/Kesegaran';
import { LabelKecocokan, type LabelFit } from '@/components/trails/LabelKecocokan';
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import type { StatusRingkas } from '@/components/trails/types';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahRingkas, type KunciLangkah } from '@/components/trips/LangkahRingkas';
import type { KepalaTripData } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { show } from '@/routes/trips';
import { confirm, recompute } from '@/routes/trips/readiness';
import type { PropsBersama } from '@/types';

type NilaiKeadaan = 'READY' | 'NEEDS_PREPARATION' | 'NOT_RECOMMENDED';

interface Prakiraan {
    judul: string;
    keadaan: 'menunggu' | 'lewat' | 'berjalan' | 'tidakDiperiksa' | 'gagal' | 'belumTersedia' | 'tersedia';
    pesan: string | null;
    baris: Array<{ waktu: string; cuaca: string; suhu: string }>;
    sumber: string;
    area: string | null;
    diperbarui: string | null;
    kesegaran: string | null;
    /** Penanda zona gunung (WIB/WITA/WIT), untuk kepala kolom Jam (m3). */
    zona: string;
    /** Akhir data tersimpan jatuh pada tanggal trip sendiri (m5 "sebagian"); null bila penuh. */
    sebagian: string | null;
}

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    tautanLangkah: Partial<Record<KunciLangkah, string>>;
    keadaan: { nilai: NilaiKeadaan; label: string; kalimat: string; alasan: string[]; dihitung: string | null };
    kecocokan: {
        label: LabelFit;
        labelText: string;
        alasan: string;
        denganRencana: boolean;
        layak: boolean;
        tautan: string | null;
        tautanOnboarding: string | null;
        profilLengkap: boolean;
    };
    persiapan: {
        dikonfirmasi: number;
        berlaku: number;
        teks: string;
        tautan: string;
        /** `keterangan` hanya untuk butir izin yang jendelanya tidak mengajak memesan (M1 tinjauan akhir W4). */
        butir: Array<{ id: number; label: string; menunggu: string | null; keterangan: string | null; aksi: { label: string; href: string; luar: boolean } | null }>;
        /** Butir yang aksinya benar-benar dapat dibuka sekarang; butir menunggu BMKG tidak ikut (m1). */
        siapDiselesaikan: number;
        /** Kalimat ketika seluruh butir kritis yang tersisa menunggu BMKG; null bila tidak. */
        semuaMenungguBmkg: string | null;
        /** Butir kritis sudah habis ATAU seluruhnya menunggu BMKG, daftar penuh belum (I1,
         * diperluas MIN-4): tawarkan jalan ke sisa butir non-kritis yang bisa dikerjakan. */
        lanjutkan: boolean;
        /** Seluruh butir persiapan ditandai Tidak berlaku (W4-6a, probe R4-D): kasus sendiri. */
        semuaTidakBerlaku: boolean;
        /** Trip belum punya satu pun butir persiapan sama sekali (MIN-1, putaran perbaikan 3);
         * beda dari semuaTidakBerlaku (butir ADA, hanya ditandai tidak berlaku). */
        daftarBelumAda: boolean;
        /** Butir NON-KRITIS yang menunggu BMKG, dengan tanggal terbitnya (MIN-4, probe M2b);
         * null bila tidak ada. Butir ini tidak pernah muncul di `butir` (daftar itu khusus
         * kritis) sehingga tanpa field ini butir tersebut tidak disebut di mana pun. */
        menungguLain: string | null;
    };
    kondisi: {
        status: StatusRingkas;
        prakiraan: Prakiraan;
        laporan: { ringkas: string; segmen: string | null; kapan: string; kesegaran: string } | null;
        peringatan: string[];
    };
    sejakTerakhir: { tanggal: string | null; butir: Array<{ jenis: 'sama' | 'bertambah' | 'berubah'; teks: string }> } | null;
    hariKeberangkatan: string;
    /** Rencana masih di depan (belum dimulai, tanggal belum lewat): hanya saat itu "Selesaikan N butir" dan "Simpan hasil cek ini" ditawarkan (W4-4d). */
    tindakLanjut: boolean;
    bolehKonfirmasi: boolean;
    /** Alasan tinjauan tidak dapat dicatat sekarang, PERSIS kalimat yang akan diberikan POST
     * yang sama (I-1). Null HANYA ketika bolehKonfirmasi true. NIT-1 (putaran perbaikan 3):
     * sebelumnya komentar ini menulis "null bila ... atau sudah ditinjau", yang terbalik --
     * sejak MIN-5 method sumbernya (PreDepartureCheck::alasanPenolakan) memeriksa "sudah
     * ditinjau" lebih dulu dan mengembalikan STRING ("Tinjauan sudah tercatat ...") untuk
     * kasus itu, bukan null. Nilai itu sendiri tidak pernah tampil karena
     * preDeparture.dikonfirmasi diperiksa duluan di render di bawah. */
    alasanTanpaTinjauan: string | null;
    preDeparture: { dikonfirmasi: boolean; dicatat: string | null };
    penolakan: string | null;
}

const gayaKeadaan: Record<NilaiKeadaan, string> = {
    READY: 'border-brand-200 bg-brand-50',
    NEEDS_PREPARATION: 'border-warn-300 bg-warn-50',
    NOT_RECOMMENDED: 'border-danger-300 bg-danger-50',
};

const lencanaPrakiraan: Record<Prakiraan['keadaan'], string | null> = {
    menunggu: 'Belum terbit',
    lewat: 'Sudah lewat',
    berjalan: 'Pendakian berjalan',
    tidakDiperiksa: 'Tidak diperiksa',
    gagal: 'Tidak tersedia',
    belumTersedia: 'Belum ada',
    tersedia: null,
};

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Satu dari tiga hal yang diperiksa: nomor dalam cincin pos dan isinya. */
function HalDiperiksa({ nomor, judul, children }: { nomor: number; judul: string; children: ReactNode }) {
    return (
        <li className="flex gap-4 rounded-lg border border-subtle bg-surface p-5">
            <span aria-hidden="true" className="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-brand-700 text-kecil font-bold tabular-nums text-brand-ink">
                {nomor}
            </span>
            <div className="min-w-0 flex-1">
                <h3 className="text-subjudul text-primary">{judul}</h3>
                <div className="mt-2 space-y-3">{children}</div>
            </div>
        </li>
    );
}

/**
 * Cek kesiapan (spec v3 bagian 6.11, prototype 10): keadaan DK-48 dengan kalimatnya, tiga hal
 * yang diperiksa (kecocokan untuk rencana, persiapan, kondisi terkini), apa yang berubah sejak
 * cek terakhir, pesan hari keberangkatan, lalu satu aksi utama: menyelesaikan butir yang
 * kurang, atau menandai sudah meninjau semuanya. Dukungan keputusan, bukan izin medis, izin
 * hukum, atau jaminan keselamatan (R-014); tanpa skor (R-005).
 *
 * Putaran perbaikan 1 (ruling W4-6a sampai W4-6g): umpan balik "Simpan hasil cek ini" dan
 * "Saya sudah meninjau semuanya" pindah ke dua wilayah yang SELALU terpasang tepat di atas
 * grup aksi (role="status" untuk flash, role="alert" untuk penolakan), bukan alert di puncak
 * halaman yang luput dari gulir dan tidak andal diumumkan (I3).
 */
export default function Readiness() {
    const { kepala, tautanLangkah, keadaan, kecocokan, persiapan, kondisi, sejakTerakhir, hariKeberangkatan, tindakLanjut, bolehKonfirmasi, alasanTanpaTinjauan, preDeparture, penolakan, flash } =
        usePage<Props>().props;
    const [menyimpan, setMenyimpan] = useState(false);
    const [mengonfirmasi, setMengonfirmasi] = useState(false);

    const simpanCek = () => {
        setMenyimpan(true);
        router.post(recompute.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMenyimpan(false) });
    };

    const konfirmasi = () => {
        setMengonfirmasi(true);
        router.post(confirm.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMengonfirmasi(false) });
    };

    const prakiraan = kondisi.prakiraan;
    const lencana = lencanaPrakiraan[prakiraan.keadaan];
    const tidakDisarankan = keadaan.nilai === 'NOT_RECOMMENDED';

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <div>
                    <PageHeader induk={{ href: show.url(kepala.id), label: 'Ruang kerja trip' }} title={kepala.nama}>
                        <KepalaTrip kepala={kepala} />
                    </PageHeader>
                    <LangkahRingkas kini="kesiapan" tautan={tautanLangkah} />
                </div>

                {preDeparture.dikonfirmasi ? (
                    <Alert variant="success" title="Anda menandai sudah meninjau semuanya">
                        Dicatat {preDeparture.dicatat}. Kondisi di bawah tetap dihitung ulang setiap halaman dibuka, jadi periksa kembali sebelum berangkat.
                    </Alert>
                ) : null}

                <section aria-labelledby="keadaan-kesiapan" className={kelas('rounded-utama border p-5', gayaKeadaan[keadaan.nilai])}>
                    <p className="text-meta font-bold text-secondary">Keadaan kesiapan</p>
                    <h2 id="keadaan-kesiapan" className="mt-1 text-judul-kartu text-primary">
                        {keadaan.label}
                    </h2>
                    <p className="mt-2 text-isi text-primary">{keadaan.kalimat}</p>
                    {keadaan.alasan.length > 0 ? (
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-kecil text-primary">
                            {keadaan.alasan.map((a) => (
                                <li key={a}>{a}</li>
                            ))}
                        </ul>
                    ) : null}
                    <p className="mt-3 border-t border-subtle pt-3 text-kecil text-secondary">Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.</p>
                    {keadaan.dihitung ? <p className="mt-1 text-meta text-muted">Dihitung {keadaan.dihitung}</p> : null}
                </section>

                <section aria-labelledby="tiga-hal">
                    <h2 id="tiga-hal" className="text-bagian text-primary">
                        Tiga hal yang diperiksa
                    </h2>
                    <ol className="mt-3 space-y-3">
                        <HalDiperiksa nomor={1} judul="Kecocokan jalur">
                            <p className="flex flex-wrap items-center gap-2">
                                <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                                {/* I4: "Kecocokan dasar" adalah kata untuk label yang memang terukur tanpa
                                    rencana; label null (belum dinilai) tidak punya dasar apa pun untuk disebut. */}
                                {kecocokan.label !== null ? (
                                    <span className="text-meta text-secondary">{kecocokan.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
                                ) : null}
                            </p>
                            {!kecocokan.layak ? <p className="text-kecil font-bold text-primary">Tidak memenuhi syarat dasar untuk rencana ini.</p> : null}
                            <p className="text-kecil text-secondary">{kecocokan.alasan}</p>
                            {!kecocokan.profilLengkap && kecocokan.tautanOnboarding ? (
                                <Tautan href={kecocokan.tautanOnboarding} className={tautanTeks}>
                                    Isi profil pendaki
                                </Tautan>
                            ) : kecocokan.tautan ? (
                                <Tautan href={kecocokan.tautan} className={tautanTeks}>
                                    Lihat penilaian lengkap
                                </Tautan>
                            ) : null}
                        </HalDiperiksa>

                        <HalDiperiksa nomor={2} judul="Persiapan">
                            {persiapan.berlaku > 0 ? (
                                <>
                                    <p data-angka className="text-kecil font-bold text-primary">
                                        {persiapan.dikonfirmasi} dari {persiapan.berlaku} butir kritis dikonfirmasi
                                    </p>
                                    <div aria-hidden="true" className="flex gap-1">
                                        {Array.from({ length: persiapan.berlaku }, (_, i) => (
                                            <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                                        ))}
                                    </div>
                                </>
                            ) : null}
                            {/* I1: hitungan seluruh daftar selalu tampil, juga ketika ruas butir kritis di
                                atas sudah penuh -- butir non-kritis yang masih tersisa tidak boleh tersembunyi
                                di belakang ruas yang kebetulan sudah terisi semua. */}
                            <p className="text-kecil text-secondary">Seluruh daftar: {persiapan.teks}</p>
                            {/* NIT-B W4-6l: daftar kosong dan daftar yang seluruhnya Tidak berlaku
                                sudah disebut kalimat "Seluruh daftar" di atas, jadi tidak diulang. */}
                            {persiapan.butir.length === 0 ? (
                                !persiapan.daftarBelumAda && !persiapan.semuaTidakBerlaku ? (
                                    <p className="text-kecil text-secondary">Tidak ada butir kritis yang menunggu konfirmasi.</p>
                                ) : null
                            ) : (
                                <ul className="space-y-2">
                                    {persiapan.butir.map((b) => (
                                        <li key={b.id} className="flex items-center gap-3 rounded-tombol bg-surface-sunken px-4 py-1">
                                            <span className="min-w-0 flex-1 py-2">
                                                <span className="block break-words text-kecil font-bold text-primary">{b.label}</span>
                                                {b.menunggu ? <span className="mt-0.5 block text-meta text-secondary">{b.menunggu}</span> : null}
                                                {b.keterangan ? <span className="mt-0.5 block break-words text-meta text-secondary">{b.keterangan}</span> : null}
                                            </span>
                                            {b.aksi && b.aksi.luar ? (
                                                <a href={b.aksi.href} target="_blank" rel="noopener noreferrer" className={kelas(tautanTeks, 'shrink-0 gap-1')}>
                                                    {b.aksi.label}
                                                    <Icon name="arrow-square-out" size={16} />
                                                    <span className="sr-only"> {b.label} di sistem resmi (membuka tab baru)</span>
                                                </a>
                                            ) : null}
                                            {b.aksi && !b.aksi.luar ? (
                                                <Tautan href={b.aksi.href} className={kelas(tautanTeks, 'shrink-0')}>
                                                    {b.aksi.label}
                                                    <span className="sr-only"> {b.label}</span>
                                                </Tautan>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {/* MIN-4 (putaran perbaikan 3, probe M2b): butir cuaca NON-KRITIS yang
                                menunggu BMKG tidak pernah masuk daftar `butir` di atas (daftar itu
                                khusus kritis) -- tanpa kalimat ini butir itu hilang begitu saja dari
                                kartu, hanya terhitung diam-diam di "Seluruh daftar". Tidak digerbang
                                oleh panjang `butir`: tetap tampil baik daftar kritis kosong maupun
                                terisi. */}
                            {persiapan.menungguLain ? <p className="text-kecil text-secondary">{persiapan.menungguLain}</p> : null}
                            {/* Tautan lanjutan di luar percabangan `butir` (MIN-4): `lanjutkan` juga benar
                                ketika daftar kritis terisi tetapi seluruhnya menunggu BMKG. Daftar kosong
                                tidak bertautan di sini, karena "Buat daftar persiapan" menjadi aksi utama
                                grup aksi di bawah (R2 W4-6l); rencana yang tidak lagi di depan tidak
                                ditawari aksi persiapan (W4-4d). */}
                            {tindakLanjut && persiapan.semuaTidakBerlaku ? (
                                <Tautan href={persiapan.tautan} className={tautanTeks}>
                                    Tinjau daftar persiapan
                                </Tautan>
                            ) : tindakLanjut && persiapan.lanjutkan ? (
                                <Tautan href={persiapan.tautan} className={tautanTeks}>
                                    Lanjutkan persiapan
                                </Tautan>
                            ) : null}
                        </HalDiperiksa>

                        <HalDiperiksa nomor={3} judul="Kondisi terkini">
                            <div>
                                <p className="text-meta text-secondary">Status resmi</p>
                                <BarisStatus status={kondisi.status} className="mt-1" />
                                <p className="mt-1 text-meta text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                            </div>

                            <div className="border-t border-subtle pt-3">
                                <p className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="text-meta text-secondary">{prakiraan.judul}</span>
                                    {lencana ? (
                                        <Lencana garis="putus">{lencana}</Lencana>
                                    ) : prakiraan.kesegaran ? (
                                        <Lencana nada={nadaKesegaran(prakiraan.kesegaran)}>{prakiraan.kesegaran}</Lencana>
                                    ) : null}
                                </p>
                                {prakiraan.pesan ? <p className="mt-1 text-kecil text-primary">{prakiraan.pesan}</p> : null}
                                {prakiraan.sebagian ? <p className="mt-1 text-kecil text-secondary">{prakiraan.sebagian}</p> : null}
                                {prakiraan.baris.length > 0 ? (
                                    <table className="mt-2 w-full text-left text-kecil">
                                        <caption className="sr-only">{prakiraan.judul}</caption>
                                        <thead className="text-meta text-secondary">
                                            <tr>
                                                <th scope="col" className="py-1 font-semibold">Jam ({prakiraan.zona})</th>
                                                <th scope="col" className="py-1 font-semibold">Cuaca</th>
                                                <th scope="col" className="py-1 font-semibold">Suhu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {prakiraan.baris.map((b) => (
                                                <tr key={b.waktu} className="border-t border-subtle">
                                                    <td className="py-1.5 tabular-nums">{b.waktu}</td>
                                                    <td className="py-1.5">{b.cuaca}</td>
                                                    <td className="py-1.5 tabular-nums">{b.suhu}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : null}
                                <p className="mt-1 text-meta text-muted">
                                    Sumber: {prakiraan.sumber}
                                    {prakiraan.area ? `, area referensi ${prakiraan.area}` : ''}
                                    {prakiraan.diperbarui ? `, diperbarui ${prakiraan.diperbarui}` : ''}
                                </p>
                            </div>

                            {kondisi.laporan ? (
                                <div className="rounded-tombol border border-dashed border-community-500 bg-community-50 p-4">
                                    <p className="flex flex-wrap items-center justify-between gap-2">
                                        <LabelKomunitas />
                                        <Lencana>{kondisi.laporan.kesegaran}</Lencana>
                                    </p>
                                    <p className="mt-2 text-kecil font-bold text-primary">
                                        {kondisi.laporan.ringkas}
                                        {kondisi.laporan.segmen ? ` di ${kondisi.laporan.segmen}` : ''}
                                    </p>
                                    <p className="mt-1 text-meta text-secondary">{kondisi.laporan.kapan}</p>
                                </div>
                            ) : (
                                <p className="text-kecil text-secondary">Belum ada laporan pendaki yang lolos moderasi dalam 30 hari terakhir.</p>
                            )}

                            {kondisi.peringatan.length > 0 ? (
                                <ul className="list-disc space-y-1 pl-5 text-kecil text-primary">
                                    {kondisi.peringatan.map((p) => (
                                        <li key={p}>{p}</li>
                                    ))}
                                </ul>
                            ) : null}
                        </HalDiperiksa>
                    </ol>
                </section>

                <section aria-labelledby="sejak-cek">
                    <h2 id="sejak-cek" className="text-bagian text-primary">
                        Sejak cek terakhir {sejakTerakhir?.tanggal ? <span className="text-isi font-semibold text-secondary tabular-nums">{sejakTerakhir.tanggal}</span> : null}
                    </h2>
                    {sejakTerakhir ? (
                        <ul className="mt-3 space-y-2 rounded-lg border border-subtle bg-surface p-4">
                            {sejakTerakhir.butir.map((b) => (
                                <li key={b.teks} className="flex items-start gap-3 text-kecil text-primary">
                                    {b.jenis === 'berubah' ? (
                                        <Lencana nada="peringatan">Berubah</Lencana>
                                    ) : (
                                        <span aria-hidden="true" className="w-4 shrink-0 text-center font-bold text-secondary">
                                            {b.jenis === 'bertambah' ? '+' : '='}
                                        </span>
                                    )}
                                    <span className="min-w-0">{b.teks}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="mt-2 text-kecil text-secondary">
                            {tindakLanjut ? 'Belum ada hasil cek yang disimpan. Simpan hasil cek ini supaya cek berikutnya dapat menyebut apa yang berubah.' : 'Belum ada hasil cek yang disimpan.'}
                        </p>
                    )}
                </section>

                <section aria-labelledby="hari-berangkat" className="rounded-lg bg-surface-sunken p-5">
                    <h2 id="hari-berangkat" className="text-subjudul text-primary">
                        Di hari keberangkatan
                    </h2>
                    <p className="mt-1 text-kecil text-secondary">{hariKeberangkatan}</p>
                </section>

                <div className="space-y-3">
                    {/* m6: rencana yang tidak disarankan tidak ditolong dengan menyelesaikan butir
                        persiapan -- aksi utamanya mengubah rencana atau melihat penilaian lengkap,
                        dan "Selesaikan" turun jadi sekunder. Rencana yang tanggalnya sudah lewat
                        juga: tinjauannya ditolak sampai rencananya diubah (ruling W4-FK). */}
                    {tidakDisarankan || kepala.lewat ? (
                        <>
                            {kepala.ubah ? (
                                <Button href={kepala.ubah} className="w-full">
                                    Ubah rencana
                                </Button>
                            ) : kecocokan.tautan ? (
                                <Button href={kecocokan.tautan} className="w-full">
                                    Lihat penilaian lengkap
                                </Button>
                            ) : null}
                            {tindakLanjut && persiapan.siapDiselesaikan > 0 ? (
                                <Button variant="secondary" href={persiapan.tautan} className="w-full">
                                    Selesaikan {persiapan.siapDiselesaikan} butir
                                </Button>
                            ) : null}
                        </>
                    ) : tindakLanjut && persiapan.siapDiselesaikan > 0 ? (
                        <Button href={persiapan.tautan} className="w-full">
                            Selesaikan {persiapan.siapDiselesaikan} butir
                        </Button>
                    ) : tindakLanjut && persiapan.daftarBelumAda ? (
                        // R2 W4-6l: tanpa daftar, membuat daftar adalah aksi utama; tinjauan sekunder.
                        <Button href={persiapan.tautan} className="w-full">
                            Buat daftar persiapan
                        </Button>
                    ) : persiapan.semuaMenungguBmkg ? (
                        // m1: seluruh butir kritis yang tersisa menunggu BMKG; tombol "Selesaikan"
                        // hanya akan membawa ke daftar yang menolak konfirmasinya lebih awal.
                        <p className="text-kecil text-secondary">{persiapan.semuaMenungguBmkg}</p>
                    ) : null}

                    {preDeparture.dikonfirmasi ? (
                        // I2: tombol tidak ditawarkan lagi sesudah tinjauan tercatat -- menekannya
                        // kedua kali hanya akan ditolak server, dan tanda tinjauannya sendiri harus
                        // tetap terbaca di sini meski "Simpan hasil cek ini" menulis baris baru.
                        <p className="text-kecil font-bold text-primary">Tinjauan tercatat {preDeparture.dicatat}.</p>
                    ) : bolehKonfirmasi ? (
                        // m-1: tombol ini bukan aksi utama selama masih ada butir yang tersisa,
                        // termasuk yang menunggu BMKG -- naik jadi primer baru ketika benar-benar
                        // tidak ada lagi yang bisa diselesaikan. NIT-6 (putaran perbaikan 3):
                        // `!tidakDisarankan &&` yang sebelumnya ada di sini mati -- bolehKonfirmasi
                        // sudah false untuk rencana NOT_RECOMMENDED (alasanPenolakan menolaknya),
                        // jadi cabang tombol ini tidak pernah tercapai selagi tidakDisarankan benar;
                        // dihapus, bukan diam-diam dibiarkan seolah masih berfungsi. Diperluas MIN-4:
                        // `persiapan.butir` hanya memuat butir KRITIS, jadi butir cuaca non-kritis
                        // yang menunggu BMKG (menungguLain) harus ikut diperiksa, bukan hanya butir.
                        <Button
                            variant={persiapan.butir.length > 0 || persiapan.menungguLain !== null || persiapan.daftarBelumAda ? 'secondary' : 'primary'}
                            className="w-full"
                            onClick={konfirmasi}
                            processing={mengonfirmasi}
                        >
                            Saya sudah meninjau semuanya
                        </Button>
                    ) : (
                        // I-1: alasan dari server, PERSIS kalimat yang akan diberikan POST yang
                        // sama (PreDepartureCheck::alasanPenolakan) -- tidak lagi menuduh rencana
                        // "tidak disarankan" untuk trip yang sebenarnya hanya tidak dapat
                        // bertransisi ke Siap berangkat (IN_PROGRESS, COMPLETED, CANCELLED, DRAFT).
                        <p className="text-kecil text-secondary">{alasanTanpaTinjauan}</p>
                    )}

                    {/* I3: dua wilayah SELALU terpasang tepat di atas "Simpan hasil cek ini", supaya
                        umpan balik kedua aksi di atas tidak jatuh ke alert di puncak halaman yang
                        luput dari preserveScroll dan tidak andal diumumkan pembaca layar. empty:!mt-0
                        (n-1) menghapus margin space-y saat isinya kosong, tanpa menyembunyikan
                        wilayahnya -- disembunyikan lewat display akan membatalkan maksud "selalu
                        terpasang" di atas. */}
                    <p role="status" className="empty:!mt-0 text-kecil text-secondary">
                        {flash.status ?? ''}
                    </p>
                    <p role="alert" className="empty:!mt-0 text-kecil font-bold text-warn-900">
                        {penolakan ?? ''}
                    </p>

                    {tindakLanjut ? (
                        <Button variant="ghost" size="sm" onClick={simpanCek} processing={menyimpan}>
                            Simpan hasil cek ini
                        </Button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

Readiness.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
