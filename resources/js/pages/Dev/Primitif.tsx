import { Head } from '@inertiajs/react';
import { useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { BarAksi } from '@/components/ui/BarAksi';
import { BilahTab, type ItemTab } from '@/components/ui/BilahTab';
import { Button } from '@/components/ui/Button';
import { ChipLepas, ChipPilihan } from '@/components/ui/Chip';
import { EmptyState } from '@/components/ui/EmptyState';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon, bobotUntuk } from '@/components/ui/Icon';
import { KontrolSegmen, type PilihanSegmen } from '@/components/ui/KontrolSegmen';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Dialog, LembarBawah } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { Stepper } from '@/components/ui/Stepper';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { permukaan } from '@/layouts/NavLinks';
import { dashboard } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';

const teksPanjang = 'Pemberitahuan penutupan sementara jalur pendakian Selabintana karena perbaikan jembatan';

const pilihanChip = ['Tektok', 'Camping', 'Ada sumber air', 'Ramah pemula'];
const tafsiranAwal = ['Wilayah: Bogor dan sekitarnya', 'Tuntutan teknis: rendah', 'Durasi: sampai 8 jam'];
const simpananAwal = ['Gede via Cibodas', 'Gede via Gunung Putri', 'Papandayan via Camp David'];

/** Id tetap per contoh, supaya unsur yang dikembalikan Urungkan dapat difokus lagi. */
const idTafsiran = (label: string) => `tafsiran-${tafsiranAwal.indexOf(label)}`;
const idSimpanan = (nama: string) => `hapus-simpanan-${simpananAwal.indexOf(nama)}`;

const pilihanTampilan: ReadonlyArray<PilihanSegmen<'daftar' | 'peta'>> = [
    { nilai: 'daftar', label: 'Daftar', ikon: 'list' },
    { nilai: 'peta', label: 'Peta', ikon: 'map-trifold' },
];

const pilihanJenisTrip: ReadonlyArray<PilihanSegmen<'tektok' | 'camping' | 'multi'>> = [
    { nilai: 'tektok', label: 'Tektok', keterangan: '1 hari' },
    { nilai: 'camping', label: 'Camping', keterangan: '2 hari' },
    { nilai: 'multi', label: 'Multi-hari', keterangan: '3 hari atau lebih' },
];

const pilihanSegmenPanjang: ReadonlyArray<PilihanSegmen<'a' | 'b'>> = [
    { nilai: 'a', label: teksPanjang },
    { nilai: 'b', label: 'Jalur alternatif lewat Gunung Putri' },
];

const ukuranIkon = [16, 20, 24, 32] as const;
const contohIkon = ['arrow-up', 'check', 'bell', 'x'] as const;
const ikonTab = ['house', 'map-trifold', 'backpack', 'person-simple-hike', 'chart-line-up'] as const;
const glyphPilot = ['pos', 'tanjakan', 'resmi'] as const;

const skalaTipe = [
    { kelas: 'text-tampilan', nama: 'Tampilan, 30 px, 800', contoh: 'Selamat sore, Budi.' },
    { kelas: 'text-judul', nama: 'Judul halaman, 28 px, 800', contoh: 'Gede via Cibodas' },
    { kelas: 'text-judul-kartu', nama: 'Judul kartu utama, 23 px, 800', contoh: 'Gede via Cibodas tinggal 15 hari lagi' },
    { kelas: 'text-bagian', nama: 'Judul bagian, 18 px, 700', contoh: 'Yang masih perlu disiapkan' },
    { kelas: 'text-subjudul', nama: 'Subjudul, 17 px, 700', contoh: 'Pos 5 Kandang Badak' },
    { kelas: 'text-isi', nama: 'Isi, 16 px', contoh: 'Tanjakannya lebih dari dua kali riwayat tertinggi Anda.' },
    { kelas: 'text-kecil', nama: 'Isi kecil, 14 px', contoh: 'Diperbarui dua jam lalu dari BMKG.' },
    { kelas: 'text-meta', nama: 'Meta, 13 px', contoh: 'Cianjur, Jawa Barat' },
    { kelas: 'text-label', nama: 'Label, 12 px, lantai', contoh: 'Jelajah' },
] as const;

const contohTab: ItemTab[] = permukaan.map((p) => ({ label: p.label, href: p.href, ikon: p.ikon, aktif: p.label === 'Jelajah' }));

function Bagian({ id, judul, children }: { id: string; judul: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="space-y-4 border-t border-subtle pt-6">
            <h2 id={id} className="text-bagian text-primary">
                {judul}
            </h2>
            {children}
        </section>
    );
}

/**
 * Pameran primitif (ruling W1-R5): hanya ada di APP_ENV=local. Setiap kontrol di sini
 * berfungsi (antislop R-26) dengan contoh isi yang mirip data sungguhan, supaya perubahan
 * token atau komponen dapat diperiksa di 390 px dan 1440 px, terang dan gelap, sebelum
 * dipakai halaman lain. Blok teks panjang menguji pembungkusan di lebar 360 px.
 */
export default function Primitif() {
    const [dipilihChip, setDipilihChip] = useState<string[]>(['Camping']);
    const [tafsiran, setTafsiran] = useState<string[]>(tafsiranAwal);
    const [simpanan, setSimpanan] = useState<string[]>(simpananAwal);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [jenisTrip, setJenisTrip] = useState<'tektok' | 'camping' | 'multi'>('camping');
    const [segmenPanjang, setSegmenPanjang] = useState<'a' | 'b'>('a');
    const [pengingat, setPengingat] = useState(true);
    const [barTampil, setBarTampil] = useState(false);
    const [sakelarPanjang, setSakelarPanjang] = useState(false);
    const [headlamp, setHeadlamp] = useState(false);
    const [jasHujan, setJasHujan] = useState(true);
    const [hanyaSumberAir, setHanyaSumberAir] = useState(false);
    const [kotakPanjang, setKotakPanjang] = useState(false);
    const [chipPanjang, setChipPanjang] = useState(false);
    const [peserta, setPeserta] = useState(3);
    const [tekanPanjang, setTekanPanjang] = useState(0);
    const [lembarTerbuka, setLembarTerbuka] = useState(false);
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const { tampilkanToast: tampilkan } = useKerangka();
    const fokusSesudahRender = useFokusSesudahRender();
    const pemicuLembar = useRef<HTMLElement | null>(null);
    const pemicuDialog = useRef<HTMLElement | null>(null);
    const simpananPertama = simpanan[0];

    const gantiChip = (label: string, dipilih: boolean) => setDipilihChip((daftar) => (dipilih ? [...daftar, label] : daftar.filter((l) => l !== label)));

    // Aksi dari papan ketik (event.detail === 0) memindah fokus ke Urungkan; sesudah
    // Urungkan, fokus ke unsur yang dikembalikan, bukan ke body.
    const lepasTafsiran = (label: string, dariPapanKetik: boolean) => {
        const sebelum = tafsiran;
        setTafsiran(tafsiran.filter((t) => t !== label));
        tampilkan(
            `Saringan "${label}" dilepas.`,
            () => {
                setTafsiran(sebelum);
                fokusSesudahRender(idTafsiran(label));
            },
            { fokus: dariPapanKetik },
        );
    };

    const hapusSimpanan = (nama: string, dariPapanKetik: boolean) => {
        const sebelum = simpanan;
        setSimpanan(simpanan.filter((s) => s !== nama));
        tampilkan(
            `${nama} dihapus dari simpanan.`,
            () => {
                setSimpanan(sebelum);
                fokusSesudahRender(idSimpanan(nama));
            },
            { fokus: dariPapanKetik },
        );
    };

    // Pemicu dicatat saat diklik, supaya fokus kembali ke tombol itu juga di Safari, yang
    // tidak memfokus tombol yang diklik.
    const bukaLembar = (e: MouseEvent<HTMLElement>) => {
        pemicuLembar.current = e.currentTarget;
        setLembarTerbuka(true);
    };

    const bukaDialog = (e: MouseEvent<HTMLElement>) => {
        pemicuDialog.current = e.currentTarget;
        setDialogTerbuka(true);
    };

    return (
        <div className="py-6 sm:py-8">
            <Head>
                <meta name="robots" content="noindex" />
            </Head>
            <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: dashboard.url(), label: 'Beranda' }}
                    title="Pameran primitif"
                    description="Setiap primitif desain v3 dalam satu halaman, berfungsi dan berisi contoh. Hanya ada di lingkungan lokal; lihat di 390 px dan 1440 px, tema terang dan gelap."
                />

                <Bagian id="bagian-tombol" judul="Tombol">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button onClick={() => setTekanPanjang((n) => n + 1)}>Rencanakan trip</Button>
                        <Button variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Simpan jalur
                        </Button>
                        <Button variant="danger" onClick={bukaDialog}>
                            Hapus trip
                        </Button>
                        <Button variant="ghost" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Lihat alasannya
                        </Button>
                        <Button size="sm" variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Pesan di sistem resmi
                        </Button>
                        <Button processing>Menyimpan rencana</Button>
                        <Button variant="secondary" disabled>
                            Mulai mendaki
                        </Button>
                    </div>
                    <p aria-live="polite" className="text-kecil text-muted">
                        Tombol contoh ditekan {tekanPanjang} kali.
                    </p>
                </Bagian>

                <Bagian id="bagian-tombol-ikon" judul="Tombol ikon">
                    <div className="flex flex-wrap items-center gap-3">
                        <TombolIkon ikon="bell" label="Pemberitahuan" onClick={() => setPengingat((v) => !v)} />
                        <TombolIkon ikon="list" label="Tampilkan daftar" varian="sumur" onClick={() => setTampilan('daftar')} />
                        <TombolIkon ikon="map-trifold" label="Tampilkan peta" varian="sumur" bentuk="kotak" onClick={() => setTampilan('peta')} />
                        <TombolIkon ikon="plus" label="Tambah peserta" besar onClick={() => setPeserta((n) => Math.min(12, n + 1))} />
                        <TombolIkon ikon="x" label="Tutup contoh" varian="polos" onClick={() => setBarTampil(false)} />
                    </div>
                </Bagian>

                <Bagian id="bagian-chip" judul="Chip">
                    <div className="flex flex-wrap gap-2">
                        {pilihanChip.map((label) => (
                            <ChipPilihan key={label} dipilih={dipilihChip.includes(label)} onUbah={(dipilih) => gantiChip(label, dipilih)}>
                                {label}
                            </ChipPilihan>
                        ))}
                    </div>
                    <p id="tafsiran-keterangan" className="text-kecil text-secondary">
                        Tafsiran kalimat pencarian, dapat dilepas satu per satu:
                    </p>
                    {/* Wadah grup tetap ada sesudah chip terakhir dilepas: di sanalah fokus mendarat. */}
                    <div role="group" aria-labelledby="tafsiran-keterangan" className={`flex flex-wrap gap-2 rounded-kartu ${fokusTerlihat}`}>
                        {tafsiran.length === 0 ? (
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => {
                                    setTafsiran(tafsiranAwal);
                                    fokusSesudahRender(idTafsiran(tafsiranAwal[0] ?? ''));
                                }}
                            >
                                Kembalikan tafsiran kalimat
                            </Button>
                        ) : (
                            tafsiran.map((label) => (
                                <ChipLepas key={label} id={idTafsiran(label)} onLepas={(e) => lepasTafsiran(label, e.detail === 0)}>
                                    {label}
                                </ChipLepas>
                            ))
                        )}
                    </div>
                </Bagian>

                <Bagian id="bagian-segmen" judul="Kontrol segmen">
                    <KontrolSegmen nama="tampilan" label="Tampilan hasil" pilihan={pilihanTampilan} nilai={tampilan} onUbah={setTampilan} className="max-w-xs" />
                    <KontrolSegmen nama="jenis-trip" label="Jenis trip" pilihan={pilihanJenisTrip} nilai={jenisTrip} onUbah={setJenisTrip} className="max-w-md" />
                </Bagian>

                <Bagian id="bagian-sakelar" judul="Sakelar, kotak centang, dan stepper">
                    <div className="max-w-md space-y-2">
                        <Sakelar judul="Ingatkan saya" keterangan="Pengingat dikirim tiga hari sebelum berangkat." nyala={pengingat} onUbah={setPengingat} />
                        <Sakelar judul="Tampilkan bar aksi" keterangan="Bar melayang di atas navigasi bawah." nyala={barTampil} onUbah={setBarTampil} />
                        <KotakCentang
                            label="Headlamp dan baterai cadangan"
                            keterangan="Jalur Cibodas masuk hutan lebat, dan gelap turun sekitar pukul 18.00."
                            lencana={<Lencana nada="bahaya">Kritis</Lencana>}
                            checked={headlamp}
                            onChange={(e) => setHeadlamp(e.target.checked)}
                        />
                        <KotakCentang label="Jas hujan" checked={jasHujan} onChange={(e) => setJasHujan(e.target.checked)} />
                        <Stepper label="Peserta" keterangan="Termasuk Anda." nilai={peserta} min={1} max={12} onUbah={setPeserta} />
                    </div>
                </Bagian>

                <Bagian id="bagian-lencana" judul="Lencana dan avatar">
                    <div className="flex flex-wrap items-center gap-2">
                        <Lencana nada="cocok" bentuk="pil">
                            Cocok
                        </Lencana>
                        <Lencana nada="perlu" bentuk="pil">
                            Perlu persiapan
                        </Lencana>
                        <Lencana nada="kurang" bentuk="pil">
                            Kurang cocok
                        </Lencana>
                        <Lencana nada="belum" bentuk="pil">
                            Belum dinilai
                        </Lencana>
                        <Lencana nada="merek">Terkini</Lencana>
                        <Lencana nada="peringatan">Dibatasi</Lencana>
                        <Lencana nada="bahaya">Kritis</Lencana>
                        <Lencana nada="komunitas" garis="putus">
                            Lolos moderasi
                        </Lencana>
                        <Lencana nada="netral" garis="putus">
                            Data contoh
                        </Lencana>
                    </div>
                    <div className="flex flex-wrap items-center gap-4">
                        <Avatar nama="Budi Setiawan" ukuran={64} hiasan={false} />
                        <span className="inline-flex items-center gap-2 text-isi text-primary">
                            <Avatar nama="Budi Setiawan" />
                            Budi Setiawan
                        </span>
                        <span className="inline-flex items-center gap-2 text-kecil text-secondary">
                            <Avatar nama="Sari Wulandari" ukuran={32} />
                            Sari W.
                        </span>
                    </div>
                </Bagian>

                <Bagian id="bagian-kosong" judul="Keadaan kosong">
                    <div className="grid gap-4 lg:grid-cols-3">
                        <EmptyState
                            jenis="pertama"
                            title="Belum ada rute tersimpan"
                            description="Tekan Simpan pada jalur di Jelajah. Jalur yang Anda simpan terkumpul di sini dan dapat dibandingkan."
                        >
                            <Button href={trailsIndex.url()}>Buka Jelajah</Button>
                        </EmptyState>
                        <EmptyState
                            jenis="tersaring"
                            title="Tidak ada jalur dengan saringan ini"
                            description="Melonggarkan tuntutan teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian."
                        >
                            <Button variant="secondary" size="sm" onClick={() => setTafsiran(tafsiranAwal)}>
                                Hapus semua saringan
                            </Button>
                        </EmptyState>
                        <EmptyState
                            jenis="menunggu"
                            title="Prakiraan cuaca belum tersedia"
                            menunggu="Menunggu BMKG memperbarui prakiraan area Cibodas"
                            description="Prakiraan terakhir berumur lebih dari 12 jam, jadi tidak ditampilkan sebagai kondisi kini."
                        />
                    </div>
                </Bagian>

                <Bagian id="bagian-lapisan" judul="Lembar, dialog, dan toast urungkan">
                    <div className="flex flex-wrap gap-3">
                        <Button variant="secondary" onClick={bukaLembar}>
                            Buka saringan
                        </Button>
                        <Button variant="secondary" onClick={bukaDialog}>
                            Buka dialog konfirmasi
                        </Button>
                    </div>
                    {simpanan.length === 0 ? (
                        <EmptyState
                            jenis="pertama"
                            title="Simpanan contoh kosong"
                            description="Semua jalur contoh sudah dihapus. Kembalikan untuk mencoba toast urungkan lagi."
                        >
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => {
                                    setSimpanan(simpananAwal);
                                    fokusSesudahRender(idSimpanan(simpananAwal[0] ?? ''));
                                }}
                            >
                                Kembalikan simpanan contoh
                            </Button>
                        </EmptyState>
                    ) : (
                        <ul className="max-w-md divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                            {simpanan.map((nama) => (
                                <li key={nama} className="flex items-center justify-between gap-3 py-1 pl-4 pr-1">
                                    <span className="min-w-0 break-words text-isi text-primary">{nama}</span>
                                    <TombolIkon id={idSimpanan(nama)} ikon="x" label={`Hapus ${nama} dari simpanan`} varian="polos" onClick={(e) => hapusSimpanan(nama, e.detail === 0)} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Bagian>

                <Bagian id="bagian-tab" judul="Bilah tab">
                    <BilahTab item={contohTab} label="Contoh bilah tab" mengambang={false} />
                </Bagian>

                <Bagian id="bagian-ikon" judul="Ikon, bobot per ukuran, dan glyph pilot">
                    <table className="text-kecil text-secondary">
                        <caption className="sr-only">Ikon pada empat ukuran render beserta bobotnya</caption>
                        <thead>
                            <tr>
                                <th scope="col" className="pb-2 pr-4 text-left font-semibold">
                                    Nama
                                </th>
                                {ukuranIkon.map((u) => (
                                    <th key={u} scope="col" className="pb-2 pr-4 text-left font-semibold">
                                        {u} px, {bobotUntuk(u) === 'bold' ? 'Bold' : 'Regular'}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {contohIkon.map((nama) => (
                                <tr key={nama}>
                                    <th scope="row" className="py-1 pr-4 text-left font-medium">
                                        {nama}
                                    </th>
                                    {ukuranIkon.map((u) => (
                                        <td key={u} className="py-1 pr-4 text-primary">
                                            <Icon name={nama} size={u} />
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <p className="text-kecil text-secondary">Ikon tab, garis lalu isi untuk tab aktif:</p>
                    <div className="flex flex-wrap gap-4 text-primary">
                        {ikonTab.map((nama) => (
                            <span key={nama} className="inline-flex items-center gap-1">
                                <Icon name={nama} size={22} />
                                <Icon name={nama} size={22} isi />
                            </span>
                        ))}
                    </div>
                    <p className="text-kecil text-secondary">Glyph domain pilot: pos, tanjakan, resmi.</p>
                    <div className="flex flex-wrap items-end gap-6 text-primary">
                        {glyphPilot.map((nama) => (
                            <span key={nama} className="inline-flex items-end gap-2">
                                {ukuranIkon.map((u) => (
                                    <Icon key={u} name={nama} size={u} label={u === 32 ? `Glyph ${nama}` : undefined} />
                                ))}
                            </span>
                        ))}
                    </div>
                </Bagian>

                <Bagian id="bagian-tipe" judul="Skala tipe dan angka">
                    <div className="space-y-3">
                        {skalaTipe.map((s) => (
                            <div key={s.kelas}>
                                <p className={`${s.kelas} text-primary`}>{s.contoh}</p>
                                <p className="text-meta text-muted">{s.nama}</p>
                            </div>
                        ))}
                    </div>
                    <dl className="flex flex-wrap gap-x-8 gap-y-3">
                        <div>
                            <dt className="text-meta text-muted">Jarak</dt>
                            <dd className="text-bagian text-primary">9,7 km</dd>
                        </div>
                        <div>
                            <dt className="text-meta text-muted">Tanjakan</dt>
                            <dd className="inline-flex items-center gap-1 text-bagian text-primary">
                                <Icon name="arrow-up" size={20} />
                                <span className="sr-only">naik </span>1.650 m
                            </dd>
                        </div>
                        <div>
                            <dt className="text-meta text-muted">Waktu tempuh</dt>
                            <dd className="text-bagian text-primary">7–8 j</dd>
                        </div>
                    </dl>
                </Bagian>

                <section aria-labelledby="bagian-teks-panjang" data-uji="teks-panjang" className="max-w-[360px] space-y-4 rounded-lg border border-dashed border-control p-4">
                    <h2 id="bagian-teks-panjang" className="text-bagian text-primary">
                        Teks panjang di 360 px
                    </h2>
                    <Button variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                        {teksPanjang}
                    </Button>
                    <ChipPilihan dipilih={chipPanjang} onUbah={setChipPanjang}>
                        {teksPanjang}
                    </ChipPilihan>
                    <Lencana nada="peringatan">{teksPanjang}</Lencana>
                    <KontrolSegmen nama="segmen-panjang" label="Contoh segmen berlabel panjang" pilihan={pilihanSegmenPanjang} nilai={segmenPanjang} onUbah={setSegmenPanjang} />
                    <Sakelar judul={teksPanjang} nyala={sakelarPanjang} onUbah={setSakelarPanjang} />
                    <KotakCentang label={teksPanjang} checked={kotakPanjang} onChange={(e) => setKotakPanjang(e.target.checked)} />
                    <EmptyState jenis="menunggu" title={teksPanjang} menunggu="Menunggu pengelola Taman Nasional Gunung Gede Pangrango" description="Status resmi terakhir tercatat delapan hari lalu." />
                </section>
            </div>

            {barTampil ? (
                <BarAksi label="Aksi contoh" ringkasan={`${simpanan.length} jalur di simpanan contoh`}>
                    <Button onClick={bukaDialog}>Hapus yang dipilih</Button>
                    <Button variant="secondary" onClick={() => setBarTampil(false)}>
                        Sembunyikan bar
                    </Button>
                </BarAksi>
            ) : null}

            <LembarBawah
                terbuka={lembarTerbuka}
                onTutup={() => setLembarTerbuka(false)}
                pemicu={pemicuLembar}
                judul="Saring jalur"
                keterangan="Saringan berlaku untuk daftar dan peta."
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setLembarTerbuka(false)}>
                            Batal
                        </Button>
                        <Button onClick={() => setLembarTerbuka(false)}>Terapkan</Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <KontrolSegmen nama="jenis-trip-lembar" label="Jenis trip" pilihan={pilihanJenisTrip} nilai={jenisTrip} onUbah={setJenisTrip} />
                    <KotakCentang label="Hanya jalur dengan sumber air" checked={hanyaSumberAir} onChange={(e) => setHanyaSumberAir(e.target.checked)} />
                    <Stepper label="Peserta" nilai={peserta} min={1} max={12} onUbah={setPeserta} />
                </div>
            </LembarBawah>

            <Dialog
                terbuka={dialogTerbuka}
                onTutup={() => setDialogTerbuka(false)}
                pemicu={pemicuDialog}
                judul={simpananPertama ? `Hapus ${simpananPertama} dari simpanan contoh?` : 'Simpanan contoh sudah kosong'}
                keterangan="Catatan pribadi untuk jalur ini ikut terhapus."
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialogTerbuka(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            disabled={!simpananPertama}
                            onClick={() => {
                                setDialogTerbuka(false);
                                // Tanpa opsi fokus: dialog yang ditutup mengembalikan fokus ke
                                // pemicunya sendiri, dan toast tidak merebutnya.
                                if (simpananPertama) {
                                    hapusSimpanan(simpananPertama, false);
                                }
                            }}
                        >
                            Hapus
                        </Button>
                    </>
                }
            >
                <p className="text-isi text-secondary">Sesudah penghapusan muncul toast urungkan; menekan Urungkan mengembalikan jalurnya.</p>
            </Dialog>
        </div>
    );
}

Primitif.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
