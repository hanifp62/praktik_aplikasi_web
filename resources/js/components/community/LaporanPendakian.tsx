import { useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { kelas } from '@/lib/kelas';
import type { LaporanRingkasan } from './ringkasan';

interface Isian {
    trail_id: number;
    trip_plan_id: number;
    hike_date: string;
    condition_tags: string[];
    trail_segment_id: string;
    note: string;
    photo: File | null;
}

const tanggalPendek = (iso: string) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${iso}T00:00:00Z`));

/**
 * Seperti useFokusSesudahRender, tetapi sasarannya unsur PERTAMA dari daftar id yang masih
 * terpasang sesudah render berikutnya (ruling W6-6r huruf c). Simpan perubahan yang ditolak
 * karena moderator sudah mulai meninjau menutup formulir ubah (tautan ubah dari server menjadi
 * null), sehingga tombol kirim tidak ada lagi; fokus lalu pindah ke kalimat penolakan di kotak
 * keadaan, bukan jatuh ke body. Penolakan lain membiarkan formulir terbuka, dan fokus kembali
 * ke tombol kirim.
 */
function useFokusPertamaSesudahRender() {
    const [ids, setIds] = useState<string[] | null>(null);

    useEffect(() => {
        if (ids === null) {
            return;
        }

        ids.map((id) => document.getElementById(id)).find((unsur) => unsur !== null)?.focus();
        setIds(null);
    }, [ids]);

    return setIds;
}

/**
 * "Bantu pendaki berikutnya" (spec v3 bagian 6.13, prototype 12): laporan kondisi langsung dari
 * ringkasan, dengan tanggal pendakian yang sudah diketahui. Tombol kirim aktif setelah satu
 * kondisi dipilih, dan kalimat di bawahnya menyebut sebabnya. Laporan selalu melewati moderasi
 * dan tidak pernah mengubah status resmi (R-010); yang terbit dan tidak terbit ditulis sebelum
 * pendaki mengirim. Sesudah terkirim, laporan dapat diubah selama belum dimoderasi.
 *
 * Fokus sesudah aksi (ruling W6-6e, I-3 putaran perbaikan 1): komponen ini tetap terpasang pada
 * aksi di halaman yang sama (Inertia 3 berbawaan preserveState untuk post/put), jadi efek saat
 * dipasang tidak berjalan lagi sesudah aksi kedua dan seterusnya. useFokusSesudahRender menunda
 * fokus satu render lewat efeknya sendiri; dipanggil di onSuccess/onFinish kunjungan atau
 * langsung di penangan klik, bukan di efek saat dipasang. Berhasil kirim atau simpan memindah
 * fokus ke kalimat keadaan; ditolak (batas laju, galat HTTP, atau galat jaringan, yang ketiganya
 * sama-sama tidak memanggil onSuccess) mengembalikan fokus ke tombol kirim yang sudah aktif lagi
 * di onFinish, yang selalu terpanggil apa pun hasilnya.
 *
 * Ruling W6-6r huruf (c): formulir ubah hanya terbuka selama server masih menawarkan tautan
 * ubah (modeUbah). Simpan yang ditolak karena moderator sudah mulai meninjau mengembalikan
 * halaman dengan tautan ubah null dan errors.form; formulirnya tertutup, kotak keadaan tampil
 * dengan kalimat penolakan di puncaknya, dan fokus pindah ke kalimat itu.
 */
export function LaporanPendakian({ laporan }: { laporan: LaporanRingkasan }) {
    const { formulir } = laporan;
    const [mengubah, setMengubah] = useState(false);
    const modeUbah = mengubah && formulir.ubah !== null;
    const fokusKe = useFokusSesudahRender();
    const fokusPertama = useFokusPertamaSesudahRender();
    const form = useForm<Isian>({
        trail_id: formulir.trailId,
        trip_plan_id: formulir.tripId,
        hike_date: formulir.tanggal,
        condition_tags: formulir.awal?.condition_tags ?? [],
        trail_segment_id: formulir.awal?.trail_segment_id ? String(formulir.awal.trail_segment_id) : '',
        note: formulir.awal?.note ?? '',
        photo: null,
    });

    // Galat per butir (condition_tags.0) dan galat batas laju tidak punya kunci di data formulir.
    const galat = form.errors as Record<string, string | undefined>;
    const galatKondisi = galat.condition_tags ?? Object.entries(galat).find(([k]) => k.startsWith('condition_tags.'))?.[1];
    const adaKondisi = form.data.condition_tags.length > 0;

    const ubahKondisi = (nilai: string, dipilih: boolean) =>
        form.setData('condition_tags', dipilih ? [...form.data.condition_tags, nilai] : form.data.condition_tags.filter((t) => t !== nilai));

    const bukaUbah = () => {
        setMengubah(true);
        fokusKe('laporan-ubah-legend');
    };

    // Tombol batal mode ubah (ruling W6-6m, M-8): isian kembali ke isi tersimpan, bukan ke isi
    // kosong, supaya pendaki yang berubah pikiran sesudah mengetik punya jalan keluar selain
    // menyimpan atau meninggalkan halaman (kendali dan kebebasan pengguna).
    const batalUbah = () => {
        form.setData({
            trail_id: formulir.trailId,
            trip_plan_id: formulir.tripId,
            hike_date: formulir.tanggal,
            condition_tags: formulir.awal?.condition_tags ?? [],
            trail_segment_id: formulir.awal?.trail_segment_id ? String(formulir.awal.trail_segment_id) : '',
            note: formulir.awal?.note ?? '',
            photo: null,
        });
        form.clearErrors();
        setMengubah(false);
        fokusKe('laporan-ubah-tombol');
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();

        if (mengubah && formulir.ubah) {
            let berhasil = false;
            form.transform((d) => ({ condition_tags: d.condition_tags, trail_segment_id: d.trail_segment_id, note: d.note }));
            form.put(formulir.ubah, {
                preserveScroll: true,
                onSuccess: () => {
                    berhasil = true;
                    setMengubah(false);
                },
                onFinish: () => (berhasil ? fokusKe('laporan-keadaan') : fokusPertama(['laporan-kirim-tombol', 'laporan-penolakan'])),
            });

            return;
        }

        let berhasil = false;
        form.post(formulir.kirim, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                berhasil = true;
            },
            onFinish: () => fokusKe(berhasil ? 'laporan-keadaan' : 'laporan-kirim-tombol'),
        });
    };

    return (
        <section aria-labelledby="judul-laporan">
            <h2 id="judul-laporan" className="text-bagian text-primary">
                Bantu pendaki berikutnya
            </h2>

            {laporan.keadaan !== 'belum' && !modeUbah ? (
                <div className="mt-3 rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    {/* Penolakan simpan perubahan (ruling W6-6r): kalimat persis dari server di
                        errors.form, tampil di tempat formulir tadi dan menjadi sasaran fokus. */}
                    {galat.form ? (
                        <div id="laporan-penolakan" tabIndex={-1} className={kelas('mb-3 rounded-kartu', fokusTerlihat)}>
                            <Alert variant="danger" wilayahStatus={false}>
                                {galat.form}
                            </Alert>
                        </div>
                    ) : null}
                    {/* Sasaran fokus tanpa role=status: kabar halaman (HikeSummary) sudah
                        mengumumkan hasilnya, dan dua wilayah status menggandakannya (ruling W6-6e). */}
                    <p id="laporan-keadaan" tabIndex={-1} className={kelas('rounded-kecil text-isi font-bold text-community-900', fokusTerlihat)}>
                        {laporan.keadaan === 'menunggu'
                            ? 'Laporan Anda terkirim dan menunggu moderasi.'
                            : laporan.keadaan === 'terbit'
                              ? 'Laporan Anda sudah terbit untuk pendaki lain.'
                              : 'Laporan Anda tidak diterbitkan.'}
                    </p>
                    {laporan.keadaan === 'ditolak' && laporan.alasanTolak ? <p className="mt-1 text-kecil text-primary">Alasan moderator: {laporan.alasanTolak}</p> : null}
                    {laporan.keadaan === 'menunggu' ? (
                        <p className="mt-1 text-kecil text-primary">Laporan komunitas tampil sebagai kabar pendaki, terpisah dari status resmi jalur.</p>
                    ) : null}
                    {formulir.ubah ? (
                        <Button id="laporan-ubah-tombol" variant="secondary" className="mt-3" onClick={bukaUbah}>
                            Ubah sebelum dimoderasi
                        </Button>
                    ) : null}
                </div>
            ) : (
                <form onSubmit={kirim} className="mt-3 space-y-5 rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4 sm:p-5">
                    <p className="flex flex-wrap items-baseline justify-between gap-x-4 text-kecil text-primary">
                        Tanggal pendakian
                        <span data-angka className="font-bold">
                            {tanggalPendek(formulir.tanggal)}
                        </span>
                    </p>

                    <fieldset aria-describedby={galatKondisi ? 'laporan-kondisi-galat' : 'laporan-kondisi-petunjuk'}>
                        <legend id="laporan-ubah-legend" tabIndex={-1} className={kelas('rounded-kecil text-isi font-bold text-primary', fokusTerlihat)}>
                            Kondisi yang Anda temui <span className="font-normal text-secondary">(pilih satu atau lebih)</span>
                        </legend>
                        <div className="mt-2 flex flex-wrap gap-x-1 gap-y-1">
                            {formulir.tags.map((t) => (
                                <ChipPilihan key={t.value} dipilih={form.data.condition_tags.includes(String(t.value))} onUbah={(dipilih) => ubahKondisi(String(t.value), dipilih)}>
                                    {t.label}
                                </ChipPilihan>
                            ))}
                        </div>
                        <InputError id="laporan-kondisi-galat" messages={galatKondisi} className="mt-2" />
                    </fieldset>

                    {formulir.segmen.length > 0 ? (
                        <div>
                            <Label htmlFor="laporan-segmen">
                                Segmen <span className="font-normal text-secondary">(opsional)</span>
                            </Label>
                            <Select
                                id="laporan-segmen"
                                value={form.data.trail_segment_id}
                                onChange={(e) => form.setData('trail_segment_id', e.target.value)}
                                aria-describedby={form.errors.trail_segment_id ? 'laporan-segmen-galat' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">Seluruh jalur</option>
                                {formulir.segmen.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </Select>
                            <InputError id="laporan-segmen-galat" messages={form.errors.trail_segment_id} className="mt-2" />
                        </div>
                    ) : null}

                    <div>
                        <Label htmlFor="laporan-catatan">
                            Catatan <span className="font-normal text-secondary">(opsional)</span>
                        </Label>
                        <Textarea
                            id="laporan-catatan"
                            rows={3}
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            aria-describedby={form.errors.note ? 'laporan-catatan-galat' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="laporan-catatan-galat" messages={form.errors.note} className="mt-2" />
                    </div>

                    {modeUbah ? null : (
                        <div>
                            <Label htmlFor="laporan-foto">
                                Foto <span className="font-normal text-secondary">(opsional, paling besar 4 MB)</span>
                            </Label>
                            <input
                                id="laporan-foto"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(e) => form.setData('photo', e.target.files?.[0] ?? null)}
                                aria-describedby={form.errors.photo ? 'laporan-foto-galat laporan-foto-gps' : 'laporan-foto-gps'}
                                className="mt-1 block w-full text-kecil text-secondary file:mr-3 file:min-h-11 file:rounded-control file:border file:border-control file:bg-surface file:px-4 file:py-2 file:text-kecil file:font-bold file:text-primary"
                            />
                            <p id="laporan-foto-gps" className="mt-1 text-meta text-secondary">
                                Lokasi GPS di dalam foto dihapus sebelum foto disimpan.
                            </p>
                            <p role="status" className="mt-1 text-meta text-secondary empty:mt-0!">
                                {form.progress ? 'Mengunggah foto.' : ''}
                            </p>
                            <InputError id="laporan-foto-galat" messages={form.errors.photo} className="mt-2" />
                        </div>
                    )}

                    <p className="flex gap-2 rounded-control bg-surface px-3 py-2.5 text-kecil text-primary">
                        <Icon name="lock-simple" size={16} className="mt-0.5 shrink-0 text-secondary" />
                        <span>{laporan.privasi}</span>
                    </p>

                    {/* Penolakan server (batas laju, laporan ganda) dibacakan lewat wilayah yang terpasang
                        sejak formulir tampil (DESIGN.md, umpan balik aksi). */}
                    <p role="alert" className={galat.form ? 'rounded-kartu border border-danger-300 bg-danger-100 p-4 text-sm font-bold text-danger-900' : 'empty:mt-0!'}>
                        {galat.form ?? ''}
                    </p>

                    <div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                id="laporan-kirim-tombol"
                                type="submit"
                                processing={form.processing}
                                disabled={!adaKondisi}
                                aria-describedby="laporan-kondisi-petunjuk"
                                className={modeUbah ? 'flex-1' : 'w-full'}
                            >
                                {modeUbah ? 'Simpan perubahan laporan' : 'Kirim untuk dimoderasi'}
                            </Button>
                            {modeUbah ? (
                                <Button type="button" variant="secondary" onClick={batalUbah} disabled={form.processing}>
                                    Batal ubah
                                </Button>
                            ) : null}
                        </div>
                        <p id="laporan-kondisi-petunjuk" className="mt-2 text-meta text-secondary">
                            {adaKondisi ? 'Laporan dibaca moderator sebelum tampil untuk pendaki lain.' : 'Pilih minimal satu kondisi untuk mengirim.'}
                        </p>
                    </div>
                </form>
            )}
        </section>
    );
}
