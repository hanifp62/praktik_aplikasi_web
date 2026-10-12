import { router, useForm, usePage } from '@inertiajs/react';
import type { LineString } from 'geojson';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { konfirmasiLalu } from '@/components/admin/FormulirAdmin';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { PetaJalur } from '@/components/trails/rinci/PetaJalur';
import { Card } from '@/components/ui/Card';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { AppLayout } from '@/layouts/AppLayout';
import { PageHeader } from '@/components/ui/PageHeader';
import { contribute } from '@/routes';
import { trails as daftarJalur } from '@/routes/admin';
import { geometry } from '@/routes/trails';
import { choose, elevation, preview, search, store } from '@/routes/trails/geometry';
import type { PropsBersama } from '@/types';

interface Pratinjau {
    asal: string | null;
    titik: string;
    panjang: string;
    jarakTercatat: string;
    selisih: string | null;
    tanjakan: { gain: string; gainTercatat: string; loss: string; lossTercatat: string } | null;
    garis: LineString;
}

interface Props extends PropsBersama {
    trail: { id: number; name: string; mountain: string | null };
    spasial: boolean;
    titikTersimpan: string | null;
    maksTitik: string;
    ambangKetinggian: string;
    kandidat: { jumlah: number; jumlahTeks: string; saringan: string; tampil: Array<{ id: number; nama: string; keterangan: string }> };
    pratinjau: Pratinjau | null;
    galat: string | null;
    galatOsm: string | null;
    penolakan: string | null;
}

type Aksi = 'cari' | 'pilih' | 'tanjakan' | 'simpan';

/**
 * PRD §65 dan §109: geometri jalur dari berkas GPX atau kandidat OpenStreetMap. Selalu
 * lewat pratinjau yang dilihat manusia; aplikasi tidak pernah memilih sendiri.
 */
export default function TrailGeometry() {
    const { auth, flash, trail, spasial, titikTersimpan, maksTitik, ambangKetinggian, kandidat, pratinjau, galat, galatOsm, penolakan } = usePage<Props>().props;
    const admin = auth.user?.isAdmin ?? false;
    const [sibuk, setSibuk] = useState<Aksi | null>(null);
    // Kandidat OSM yang sedang dipratinjau: hanya tombol Pratinjau miliknya yang `processing`,
    // supaya useFokusKembali di Button mengembalikan fokus ke tombol itu (ruling CF-5, TA-m7).
    const [dipratinjau, setDipratinjau] = useState<number | null>(null);
    const [saringan, setSaringan] = useState(kandidat.saringan);
    const pertama = useRef(true);
    const unggah = useForm<{ berkas: File | null }>({ berkas: null });

    // Berkas dibaca begitu dipilih, seperti sebelumnya: kurator melihat jumlah titik dan
    // panjang jejak dulu, dan tidak ada yang ditulis sebelum ia menekan Simpan.
    useEffect(() => {
        if (unggah.data.berkas) {
            unggah.post(preview.url({ trail: trail.id }), { forceFormData: true, preserveScroll: true });
        }
    }, [unggah.data.berkas]);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const t = setTimeout(() => {
            router.get(geometry.url({ trail: trail.id }, { query: saringan ? { saringan } : {} }), {}, { only: ['kandidat'], preserveState: true, preserveScroll: true, replace: true });
        }, 300);
        return () => clearTimeout(t);
    }, [saringan]);

    // Pencarian baru mengganti seluruh draf di server, jadi halaman dipasang ulang dari
    // props: saringan lama tidak boleh tertinggal di isian sementara daftarnya sudah baru.
    const kirim = (aksi: Aksi, url: string) =>
        router.post(url, {}, { preserveScroll: true, preserveState: aksi !== 'cari', onStart: () => setSibuk(aksi), onFinish: () => setSibuk(null) });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={admin ? { href: daftarJalur.url(), label: 'Kelola Jalur' } : { href: contribute.url(), label: 'Kontribusi Data Jalur' }} title="Impor Geometri Jalur" description={trail.name} />

                {admin ? <AdminNav /> : null}

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {penolakan ? <Alert variant="warning" className="mb-4">{penolakan}</Alert> : null}

                <Card className="mb-6" title="Keadaan sekarang">
                    {!spasial ? (
                        <Alert variant="warning">Koneksi basis data ini tidak mendukung PostGIS, jadi geometri tidak dapat dibaca maupun disimpan dari sini.</Alert>
                    ) : titikTersimpan ? (
                        <p className="text-sm text-secondary">Jalur ini sudah punya geometri dengan {titikTersimpan} titik. Mengunggah berkas baru akan menggantinya.</p>
                    ) : (
                        <p className="text-sm text-secondary">Jalur ini belum punya geometri. Tanpa itu peta tidak dapat menggambar jalurnya, dan jalur belum memenuhi syarat publikasi.</p>
                    )}
                </Card>

                <Card className="mb-6" title="Cari di OpenStreetMap">
                    <p className="text-sm text-secondary">
                        Mencari jalur kaki yang sudah terpetakan di sekitar {trail.mountain ?? 'gunung ini'}. Hasilnya adalah kandidat, bukan jawaban: tidak ada cara aplikasi
                        memastikan sebuah jalur di peta adalah {trail.name} dan bukan jalan setapak di sebelahnya. Anda yang memilih.
                    </p>
                    <p className="mt-2 text-xs text-secondary">Data OpenStreetMap adalah data komunitas, bukan data resmi pengelola jalur. Asalnya dicatat di jejak audit.</p>

                    <div className="mt-4">
                        <Button variant="secondary" processing={sibuk === 'cari'} onClick={() => kirim('cari', search.url({ trail: trail.id }))}>Cari kandidat</Button>
                    </div>

                    {galatOsm ? <Alert variant="warning" className="mt-4">{galatOsm}</Alert> : null}

                    {kandidat.jumlah > 0 ? (
                        <>
                            <div className="mt-4">
                                <Label htmlFor="saringan">Saring berdasarkan nama</Label>
                                <TextInput id="saringan" value={saringan} onChange={(e) => setSaringan(e.target.value)} placeholder="misalnya: Kledung" className="max-w-sm text-sm" />
                                <p className="mt-2 text-xs text-secondary" aria-live="polite">
                                    {kandidat.jumlahTeks} jalur kaki terpetakan di sekitar gunung ini. Menampilkan {kandidat.tampil.length} terpanjang
                                    {kandidat.saringan !== '' ? ` yang cocok "${kandidat.saringan}"` : ''}. Jalur pendakian hampir selalu lebih panjang daripada potongan jalan di sekitarnya.
                                </p>
                            </div>

                            <ul className="mt-4 space-y-2">
                                {kandidat.tampil.length === 0 ? (
                                    <li>
                                        <p className="font-medium text-primary">Tidak ada calon geometri yang cocok</p>
                                        <p className="mt-1 max-w-prose text-secondary">Pencarian tidak menemukan jalur yang mendekati. Menaikkan radius atau memeriksa koordinat gunungnya lebih mungkin menolong daripada mengulang pencarian yang sama.</p>
                                    </li>
                                ) : (
                                    kandidat.tampil.map((c) => (
                                        <li key={c.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-subtle p-3">
                                            <div className="text-sm">
                                                <p className="font-medium text-primary">{c.nama}</p>
                                                <p className="text-secondary">{c.keterangan}</p>
                                            </div>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                processing={sibuk === 'pilih' && dipratinjau === c.id}
                                                disabled={sibuk !== null}
                                                onClick={() => {
                                                    setDipratinjau(c.id);
                                                    kirim('pilih', choose.url({ trail: trail.id, osm: c.id }));
                                                }}
                                                aria-label={`Pratinjau ${c.nama}, OSM way ${c.id}`}
                                            >
                                                Pratinjau
                                            </Button>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </>
                    ) : null}
                </Card>

                <Card className="mb-6" title="Unggah berkas GPX">
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="berkas">Berkas GPX</Label>
                            <input
                                id="berkas"
                                type="file"
                                accept=".gpx,.xml"
                                onChange={(e) => unggah.setData('berkas', e.target.files?.[0] ?? null)}
                                aria-invalid={unggah.errors.berkas ? true : undefined}
                                aria-describedby={['berkas-hint', unggah.errors.berkas ? 'berkas-error' : null].filter(Boolean).join(' ')}
                                className="mt-1 block min-h-11 w-full text-sm text-secondary file:mr-4 file:min-h-11 file:rounded-control file:border-0 file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-on-brand hover:file:bg-brand-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                            />
                            <InputError id="berkas-error" messages={unggah.errors.berkas} className="mt-2" />
                            <p id="berkas-hint" className="mt-2 text-xs text-secondary">
                                Ambil GPX dari sumber yang tercatat pada jalur ini, bukan dari jejak pribadi yang belum diverifikasi. Maksimal {maksTitik} titik.
                            </p>
                        </div>

                        <p role="status" className="text-sm text-secondary">{unggah.processing ? 'Membaca berkas...' : ''}</p>

                        {galat ? <Alert variant="warning">{galat}</Alert> : null}

                        {pratinjau ? (
                            <PratinjauJejak
                                pratinjau={pratinjau}
                                namaJalur={trail.name}
                                ambangKetinggian={ambangKetinggian}
                                sibuk={sibuk}
                                onTerapkan={() =>
                                    konfirmasiLalu('Ganti tanjakan dan turunan jalur ini dengan taksiran dari berkas GPX?', () => kirim('tanjakan', elevation.url({ trail: trail.id })))
                                }
                                onSimpan={() => kirim('simpan', store.url({ trail: trail.id }))}
                            />
                        ) : null}
                    </div>
                </Card>

                {admin ? (
                    <Button variant="secondary" href={daftarJalur.url()}>Kembali ke daftar jalur</Button>
                ) : (
                    <Button variant="secondary" href={contribute.url()}>Kembali ke halaman kontribusi</Button>
                )}
            </div>
        </div>
    );
}

interface PratinjauJejakProps {
    pratinjau: Pratinjau;
    namaJalur: string;
    ambangKetinggian: string;
    sibuk: Aksi | null;
    onTerapkan: () => void;
    onSimpan: () => void;
}

function PratinjauJejak({ pratinjau, namaJalur, ambangKetinggian, sibuk, onTerapkan, onSimpan }: PratinjauJejakProps) {
    // Tetap objek yang sama selama garisnya sama, supaya mengetik di saringan kandidat
    // tidak membuat peta menghitung ulang kunci isinya.
    const petaPratinjau = useMemo(() => ({ geometry: pratinjau.garis, penanda: [] }), [pratinjau.garis]);

    return (
        <>
            <div className="rounded-lg border border-subtle bg-surface-sunken p-4 text-sm">
                <p className="font-medium text-primary">Jejak terbaca</p>
                {pratinjau.asal ? <p className="mt-1 text-xs text-secondary">Asal: {pratinjau.asal}</p> : null}

                <dl className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div>
                        <dt className="text-xs text-muted">Jumlah titik</dt>
                        <dd className="text-primary">{pratinjau.titik}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted">Panjang jejak</dt>
                        <dd className="text-primary">{pratinjau.panjang}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted">Jarak tercatat</dt>
                        <dd className="text-primary">{pratinjau.jarakTercatat}</dd>
                    </div>
                </dl>

                {/* Selisih besar hampir selalu berarti berkasnya milik jalur lain. Kurator
                    tetap yang memutuskan, jadi ini peringatan, bukan penolakan. */}
                {pratinjau.selisih ? (
                    <p className="mt-3 text-sm text-warn-900">
                        Panjang jejak berbeda {pratinjau.selisih}% dari jarak yang tercatat. Pastikan berkas ini memang milik jalur {namaJalur}.
                    </p>
                ) : null}
            </div>

            <div>
                <p className="text-sm font-medium text-primary">Pratinjau garis</p>
                <p className="mt-1 text-xs text-secondary">Belum tersimpan: garis ini baru menjadi geometri {namaJalur} setelah Anda menekan Simpan geometri.</p>
                <div className="mt-2">
                    <PetaJalur
                        id="pratinjau-geometri"
                        data={petaPratinjau}
                        label={`Pratinjau garis ${namaJalur}, belum tersimpan`}
                        cadangan="Jumlah titik dan panjang jejak di atas tetap dapat diperiksa."
                    />
                </div>
            </div>

            {/* Ketinggian ditawarkan, tidak ditulis diam-diam: ia taksiran yang bergantung
                pada ambang derau, dan angka dari pengelola tidak boleh tertimpa. */}
            {pratinjau.tanjakan ? (
                <div className="rounded-control border border-subtle p-3">
                    <p className="text-sm font-medium text-primary">Ketinggian terbaca dari berkas ini</p>
                    <dl className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            <dt className="text-xs text-muted">Taksiran tanjakan</dt>
                            <dd className="text-primary">
                                {pratinjau.tanjakan.gain} <span className="text-secondary">(tercatat: {pratinjau.tanjakan.gainTercatat})</span>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted">Taksiran turunan</dt>
                            <dd className="text-primary">
                                {pratinjau.tanjakan.loss} <span className="text-secondary">(tercatat: {pratinjau.tanjakan.lossTercatat})</span>
                            </dd>
                        </div>
                    </dl>
                    <p className="mt-2 text-xs text-muted">
                        Taksiran dari ketinggian GPS dengan ambang {ambangKetinggian} meter, dipakai agar derau perangkat tidak terhitung sebagai tanjakan. Angka resmi dari pengelola
                        kawasan lebih dapat dipercaya daripada ini.
                    </p>
                    <Button variant="secondary" size="sm" className="mt-3" processing={sibuk === 'tanjakan'} disabled={sibuk !== null} onClick={onTerapkan}>
                        Terapkan ke jalur
                    </Button>
                </div>
            ) : pratinjau.asal === null ? (
                <p className="text-sm text-secondary">Berkas ini tidak membawa data ketinggian, jadi profil elevasi dan taksiran tanjakan tidak dapat dihitung darinya.</p>
            ) : null}

            <Button processing={sibuk === 'simpan'} disabled={sibuk !== null} onClick={onSimpan}>Simpan geometri</Button>
        </>
    );
}

TrailGeometry.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
