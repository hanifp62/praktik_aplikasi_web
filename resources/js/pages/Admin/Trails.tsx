import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { konfirmasiLalu } from '@/components/admin/FormulirAdmin';
import { FormulirJalur, jalurKosong, type IsianJalur, type PilihanJalur } from '@/components/admin/FormulirJalur';
import { RingkasanKesehatan, type Temuan } from '@/components/admin/RingkasanKesehatan';
import { Tabel } from '@/components/admin/Tabel';
import type { TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { trails as daftarJalur } from '@/routes/admin';
import { archive, publish, store, update } from '@/routes/admin/trails';
import type { PropsBersama } from '@/types';

interface BarisJalurAdmin {
    id: number;
    name: string;
    mountain: string;
    checkpoints: string;
    publication: string;
    published: boolean;
    archived: boolean;
    kurang: string[];
    tautan: { checkpoint: string; geometri: string };
    isian: IsianJalur;
}

interface Props extends PropsBersama, PilihanJalur {
    trails: { data: BarisJalurAdmin[]; links: TautanHalaman[] };
    kesehatan: { temuan: Temuan[]; geometriDiperiksa: boolean; saringan: string | null; saringanLabel: string | null };
    penolakan: string | null;
}

/**
 * Kelola jalur: ringkasan apa yang perlu diurus, lalu daftar jalur dengan tindakannya.
 * Publikasi hanya lolos bila syarat §110 lengkap; server yang memutuskan, halaman ini
 * hanya meneruskan jawabannya.
 */
export default function Trails() {
    const props = usePage<Props>().props;
    const { flash, trails, kesehatan, penolakan, adminNav } = props;
    const [sunting, setSunting] = useState<number | 'baru' | null>(null);
    const form = useForm<IsianJalur>(jalurKosong);

    const buka = (baris?: BarisJalurAdmin) => {
        form.clearErrors();
        form.setData(baris ? baris.isian : jalurKosong);
        setSunting(baris ? baris.id : 'baru');
    };

    const simpan = () => {
        // Formulir tetap terbuka bila penjaga gerbang menolak perubahannya, supaya isian
        // kurator tidak hilang bersama penolakannya.
        const opsi = {
            preserveScroll: true,
            onSuccess: (halaman: { props: Record<string, unknown> }) => {
                if (!halaman.props.penolakan) {
                    setSunting(null);
                }
            },
        };

        if (sunting === 'baru') {
            form.post(store.url(), opsi);
        } else if (sunting !== null) {
            form.put(update.url({ trail: sunting }), opsi);
        }
    };

    // Konfirmasi hanya pada arah yang menghilangkan jalur dari hadapan pendaki.
    // Menerbitkan atau mengaktifkan kembali tidak perlu dihalangi.
    const ubahPublikasi = (j: BarisJalurAdmin) =>
        konfirmasiLalu(j.published ? 'Tarik jalur ini dari publikasi? Pendaki tidak akan menemukannya lagi di pencarian maupun rekomendasi.' : null, () =>
            router.post(publish.url({ trail: j.id }), {}, { preserveScroll: true }),
        );

    const ubahArsip = (j: BarisJalurAdmin) =>
        konfirmasiLalu(j.archived ? null : 'Arsipkan jalur ini? Jalur yang diarsipkan hilang dari pencarian dan tidak dapat dipilih untuk trip baru.', () =>
            router.post(archive.url({ trail: j.id }), {}, { preserveScroll: true }),
        );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Jalur" description="Jalur hanya dapat dipublikasikan jika sumber data, karakteristik, checkpoint, dan status resmi sudah lengkap." />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {penolakan ? <Alert variant="warning" className="mb-4">{penolakan}</Alert> : null}

                <RingkasanKesehatan temuan={kesehatan.temuan} geometriDiperiksa={kesehatan.geometriDiperiksa} saringan={kesehatan.saringan} perluDitinjau={adminNav?.perluDitinjau ?? 0} />

                <div className="mb-4">
                    <Button onClick={() => buka()}>Tambah jalur</Button>
                </div>

                {sunting !== null ? (
                    <FormulirJalur
                        key={String(sunting)}
                        judul={sunting === 'baru' ? 'Tambah jalur' : 'Ubah jalur'}
                        form={form}
                        pilihan={props}
                        onSubmit={simpan}
                        onBatal={() => setSunting(null)}
                    />
                ) : null}

                {kesehatan.saringanLabel ? (
                    <p className="mb-3 text-sm text-secondary">
                        Menampilkan jalur aktif dengan temuan: <span className="font-medium text-primary">{kesehatan.saringanLabel}</span>.{' '}
                        <Tautan href={daftarJalur.url()} className="text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-sm">
                            Tampilkan semua jalur
                        </Tautan>
                    </p>
                ) : null}

                <Tabel
                    keterangan="Daftar jalur"
                    baris={trails.data}
                    kunci={(j) => j.id}
                    kosong={
                        kesehatan.saringanLabel
                            ? { judul: 'Tidak ada jalur yang cocok dengan saringan', penjelasan: 'Kosongnya daftar ini bergantung pada saringan yang sedang aktif, bukan pada jumlah jalur di basis data.' }
                            : { judul: 'Belum ada jalur terdaftar', penjelasan: 'Jalur selalu dibuat di bawah gunung. Bila daftar gunung juga masih kosong, mulai dari halaman Kelola Gunung.' }
                    }
                    kolom={[
                        { judul: 'Jalur', kepala: true, isi: (j) => j.name },
                        { judul: 'Gunung', isi: (j) => j.mountain },
                        { judul: 'Checkpoint', isi: (j) => j.checkpoints },
                        {
                            judul: 'Publikasi',
                            isi: (j) => (
                                <>
                                    {j.publication}
                                    {j.archived ? <span className="ml-1 text-xs text-muted">(diarsipkan)</span> : null}
                                    {j.published && j.kurang.length > 0 ? <span className="ml-1 text-xs font-medium text-warn-900">(syarat belum lengkap)</span> : null}
                                </>
                            ),
                        },
                        { judul: 'Aksi', isi: (j) => <AksiJalur jalur={j} onUbah={() => buka(j)} onPublikasi={() => ubahPublikasi(j)} onArsip={() => ubahArsip(j)} /> },
                    ]}
                />

                <div className="mt-6">
                    <Pagination links={trails.links} />
                </div>
            </div>
        </div>
    );
}

function AksiJalur({ jalur, onUbah, onPublikasi, onArsip }: { jalur: BarisJalurAdmin; onUbah: () => void; onPublikasi: () => void; onArsip: () => void }) {
    const publikasi = jalur.published ? 'Tarik dari publikasi' : 'Publikasikan';
    const arsip = jalur.archived ? 'Aktifkan' : 'Arsipkan';

    return (
        <>
            <div className="flex flex-wrap gap-2">
                <Button variant="secondary" size="sm" onClick={onUbah} aria-label={`Ubah ${jalur.name}`}>Ubah</Button>
                <Button variant="secondary" size="sm" href={jalur.tautan.checkpoint} aria-label={`Checkpoint ${jalur.name}`}>Checkpoint</Button>
                <Button variant="secondary" size="sm" href={jalur.tautan.geometri} aria-label={`Geometri ${jalur.name}`}>Geometri</Button>
                <Button variant="secondary" size="sm" onClick={onPublikasi} aria-label={`${publikasi} ${jalur.name}`}>{publikasi}</Button>
                <Button variant="secondary" size="sm" onClick={onArsip} aria-label={`${arsip} ${jalur.name}`}>{arsip}</Button>
            </div>

            {/*
             * PRD §110: kurator harus tahu apa yang kurang, bukan sekadar ditolak. Daftar ini
             * juga tampil untuk jalur yang sudah tayang, karena baris lama bisa telanjur
             * tayang dalam keadaan tidak lengkap.
             */}
            {jalur.kurang.length > 0 ? (
                <>
                    <p className="mt-2 text-xs font-medium text-warn-900">{jalur.published ? 'Tayang padahal belum memenuhi syarat:' : 'Belum dapat dipublikasikan:'}</p>
                    <ul className="mt-1 list-disc space-y-0.5 pl-4 text-xs text-warn-900">
                        {jalur.kurang.map((k) => (
                            <li key={k}>{k}</li>
                        ))}
                    </ul>
                </>
            ) : null}
        </>
    );
}

Trails.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
