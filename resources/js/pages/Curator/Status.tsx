import { router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type ReactNode } from 'react';
import { TextareaField } from '@/components/discovery/Isian';
import type { AksiCatatan, JejakCatatan, LangkahCatatan, RincianCatatan, Temuan } from '@/components/kurator/types';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { Dialog } from '@/components/ui/Lembar';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    catatan: RincianCatatan;
    arsip: { url: string; sidik: string; utuh: boolean } | null;
    langkah: LangkahCatatan[];
    temuan: Temuan[];
    rantai: { menggantikan: { id: number; url: string } | null; digantikan: { id: number; url: string } | null };
    jejak: JejakCatatan[];
    aksi: AksiCatatan;
    meja: string;
}

/**
 * Satu catatan meja kurator (spec v3 bagian 10.7, langkah 3 sampai 5): temuan pemeriksaan
 * konsistensi, verifikasi orang kedua, dan terbit, beserta isi catatan, arsip buktinya, rantai
 * penggantian, dan jejak audit. Tombol aksi hanya tampil bila Policy server mengizinkannya untuk
 * kurator sesi; server tetap menolak aksi yang tidak berhak. Mempersoalkan dan menghapus draf
 * meminta konfirmasi lewat dialog yang menyebut akibatnya.
 */
export default function Status() {
    const { flash, errors, catatan, arsip, langkah, temuan, rantai, jejak, aksi, meja } = usePage<Props>().props;
    const [dialog, setDialog] = useState<'hapus' | 'persoalkan' | null>(null);
    const [mengirim, setMengirim] = useState<string | null>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const soal = useForm({ alasan: '' });
    const blokir = temuan.filter((t) => t.tingkat === 'blokir');
    const peringatan = temuan.filter((t) => t.tingkat === 'peringatan');
    const galatAksi = [errors.konsistensi, errors.bukti, errors.evidence_type].filter(Boolean);

    const kirimAksi = (kunci: string, url: string) => {
        setMengirim(kunci);
        router.post(url, {}, { preserveScroll: true, onFinish: () => setMengirim(null) });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: meja, label: 'Meja kurator' }} title={catatan.sasaran} description={`Catatan status #${catatan.id}`} />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {galatAksi.length > 0 ? (
                    <Alert variant="danger" className="mb-4">
                        {galatAksi.join(' ')}
                    </Alert>
                ) : null}

                <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <StatusAkses nilai={catatan.status} label={catatan.statusLabel} ukuran="bagian" />
                    <Lencana nada={catatan.terlihatPendaki ? 'merek' : 'netral'}>{catatan.terlihatPendaki ? 'Terbaca pendaki' : 'Belum terbaca pendaki'}</Lencana>
                    <Lencana nada={catatan.menungguVerifikasiKedua ? 'peringatan' : 'netral'}>{catatan.keadaan}</Lencana>
                </div>
                {catatan.ringkasan ? <p className="mt-3 max-w-prose text-isi text-primary">{catatan.ringkasan}</p> : null}

                <section aria-labelledby="judul-langkah" className="mt-8">
                    <h2 id="judul-langkah" className="text-bagian text-primary">
                        Langkah
                    </h2>
                    <ol className="mt-3 space-y-2">
                        {langkah.map((l) => (
                            <li key={l.nomor} className="flex items-start gap-3">
                                <span className={l.selesai ? 'mt-0.5 text-brand-ink' : 'mt-0.5 text-muted'} aria-hidden="true">
                                    <Icon name={l.selesai ? 'check' : 'minus'} size={20} />
                                </span>
                                <span>
                                    <span className="font-bold text-primary">
                                        {l.nomor}. {l.nama}
                                    </span>{' '}
                                    <span className="text-kecil text-secondary">
                                        {l.selesai ? 'Selesai.' : 'Belum.'} {l.keterangan}
                                    </span>
                                </span>
                            </li>
                        ))}
                    </ol>
                </section>

                {temuan.length > 0 ? (
                    <section aria-labelledby="judul-temuan" className="mt-8 space-y-3">
                        <h2 id="judul-temuan" className="text-bagian text-primary">
                            Pemeriksaan konsistensi
                        </h2>
                        {blokir.length > 0 ? (
                            <Alert variant="danger" title="Menahan kiriman">
                                <ul className="list-disc space-y-1 pl-5">
                                    {blokir.map((t) => (
                                        <li key={t.kode + t.pesan}>{t.pesan}</li>
                                    ))}
                                </ul>
                            </Alert>
                        ) : null}
                        {peringatan.length > 0 ? (
                            <Alert variant="warning" title="Periksa sebelum melanjutkan">
                                <ul className="list-disc space-y-1 pl-5">
                                    {peringatan.map((t) => (
                                        <li key={t.kode + t.pesan}>{t.pesan}</li>
                                    ))}
                                </ul>
                            </Alert>
                        ) : null}
                    </section>
                ) : null}

                <section aria-label="Aksi catatan" className="mt-8 flex flex-wrap gap-3">
                    {aksi.kirim ? (
                        <Button onClick={() => kirimAksi('kirim', aksi.kirim as string)} processing={mengirim === 'kirim'} disabled={blokir.length > 0}>
                            Kirim untuk terbit
                        </Button>
                    ) : null}
                    {aksi.verifikasi ? (
                        <Button onClick={() => kirimAksi('verifikasi', aksi.verifikasi as string)} processing={mengirim === 'verifikasi'} disabled={blokir.length > 0}>
                            Verifikasi dan terbitkan
                        </Button>
                    ) : null}
                    {aksi.tinjauUlang ? (
                        <Button variant="secondary" onClick={() => kirimAksi('tinjau', aksi.tinjauUlang as string)} processing={mengirim === 'tinjau'}>
                            Sudah dicek ulang ke sumber
                        </Button>
                    ) : null}
                    {aksi.ubah ? (
                        <Button variant="secondary" href={aksi.ubah}>
                            Ubah draf
                        </Button>
                    ) : null}
                    {aksi.catatPengganti ? (
                        <Button variant="secondary" href={aksi.catatPengganti}>
                            Catat pengganti
                        </Button>
                    ) : null}
                    {aksi.persoalkan ? (
                        <Button
                            variant="secondary"
                            onClick={(e) => {
                                pemicu.current = e.currentTarget;
                                setDialog('persoalkan');
                            }}
                        >
                            Persoalkan
                        </Button>
                    ) : null}
                    {aksi.hapus ? (
                        <Button
                            variant="danger"
                            onClick={(e) => {
                                pemicu.current = e.currentTarget;
                                setDialog('hapus');
                            }}
                        >
                            Hapus draf
                        </Button>
                    ) : null}
                </section>

                <section aria-labelledby="judul-isi" className="mt-8">
                    <h2 id="judul-isi" className="text-bagian text-primary">
                        Isi catatan
                    </h2>
                    <dl className="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,12rem)_1fr]">
                        {(
                            [
                                ['Penerbit', catatan.badan],
                                ['Surat', catatan.surat],
                                ['Berlaku', catatan.berlaku],
                                ['Cara berakhir', catatan.caraBerakhir],
                                ['Batas', catatan.batas],
                                ['Alasan', catatan.alasan],
                                ['Dasar hukum', catatan.dasarHukum],
                                ['Kutipan resmi', catatan.kutipan],
                                ['Jenis bukti', catatan.jenisBukti],
                                ['Dibaca kurator', catatan.ditangkap],
                                ['Dicatat', catatan.pencatat],
                                ['Dicocokkan ke sumber', catatan.diverifikasi],
                                ['Pemeriksa kedua', catatan.pemeriksa],
                                ['Tinjau berikutnya', catatan.tinjauBerikutnya],
                            ] as Array<[string, string | null]>
                        )
                            .filter(([, nilai]) => nilai)
                            .map(([label, nilai]) => (
                                <div key={label} className="contents">
                                    <dt className="text-kecil font-bold text-secondary">{label}</dt>
                                    <dd className="break-words text-isi text-primary">{nilai}</dd>
                                </div>
                            ))}
                    </dl>
                    {catatan.urlSumber ? (
                        <p className="mt-3 text-kecil">
                            <a href={catatan.urlSumber} rel="noopener noreferrer" target="_blank" className="inline-flex min-h-11 items-center gap-1 break-all font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                Buka sumber
                                <Icon name="arrow-square-out" size={16} />
                                <span className="sr-only"> (membuka tab baru)</span>
                            </a>
                        </p>
                    ) : null}
                    {arsip ? (
                        <p className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-kecil text-secondary">
                            <a href={arsip.url} className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                Unduh arsip bukti
                            </a>
                            <span>
                                Sidik SHA-256 {arsip.sidik}, {arsip.utuh ? 'cocok dengan arsip.' : 'tidak lagi cocok dengan arsip.'}
                            </span>
                        </p>
                    ) : (
                        <p className="mt-3 text-kecil text-secondary">Belum ada salinan bukti.</p>
                    )}
                    {rantai.menggantikan || rantai.digantikan ? (
                        <p className="mt-3 text-kecil text-secondary">
                            {rantai.menggantikan ? (
                                <>
                                    Menggantikan{' '}
                                    <Tautan href={rantai.menggantikan.url} className="font-bold text-brand-ink underline underline-offset-2">
                                        catatan #{rantai.menggantikan.id}
                                    </Tautan>
                                    .{' '}
                                </>
                            ) : null}
                            {rantai.digantikan ? (
                                <>
                                    Digantikan{' '}
                                    <Tautan href={rantai.digantikan.url} className="font-bold text-brand-ink underline underline-offset-2">
                                        catatan #{rantai.digantikan.id}
                                    </Tautan>
                                    .
                                </>
                            ) : null}
                        </p>
                    ) : null}
                </section>

                <section aria-labelledby="judul-jejak" className="mt-8">
                    <h2 id="judul-jejak" className="text-bagian text-primary">
                        Jejak audit
                    </h2>
                    {jejak.length === 0 ? (
                        <p className="mt-2 text-kecil text-muted">Catatan lama ini dibuat sebelum meja kurator dan tidak punya jejak di sini.</p>
                    ) : (
                        <ul className="mt-3 space-y-2">
                            {jejak.map((j) => (
                                <li key={j.id} className="text-kecil text-secondary">
                                    <span className="font-bold text-primary">{j.tindakan}</span>, {j.oleh}
                                    {j.waktu ? `, ${j.waktu}` : ''}
                                    {j.alasan ? <span className="block text-primary">"{j.alasan}"</span> : null}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Dialog
                terbuka={dialog === 'persoalkan'}
                onTutup={() => setDialog(null)}
                judul="Persoalkan catatan ini?"
                keterangan="Pembukaan berhenti terbaca pendaki; pembatasan tetap terbaca sampai penggantinya terbit. Alasan Anda tercatat di jejak audit."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Batal
                        </Button>
                        <Button
                            processing={soal.processing}
                            onClick={() =>
                                aksi.persoalkan &&
                                soal.post(aksi.persoalkan, { preserveScroll: true, onSuccess: () => { setDialog(null); soal.reset(); } })
                            }
                        >
                            Persoalkan
                        </Button>
                    </>
                }
            >
                <TextareaField name="alasan" label="Apa yang tidak cocok dengan sumbernya?" rows={3} value={soal.data.alasan} onChange={(e) => soal.setData('alasan', e.target.value)} error={soal.errors.alasan} />
            </Dialog>

            <Dialog
                terbuka={dialog === 'hapus'}
                onTutup={() => setDialog(null)}
                judul="Hapus draf ini?"
                keterangan="Draf dan salinan buktinya dihapus permanen. Catatan yang sudah dikirim tidak dapat dihapus."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Batal
                        </Button>
                        <Button variant="danger" processing={mengirim === 'hapus'} onClick={() => aksi.hapus && (setMengirim('hapus'), router.delete(aksi.hapus, { onFinish: () => setMengirim(null) }))}>
                            Hapus draf
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{catatan.sasaran}</p>
            </Dialog>
        </div>
    );
}

Status.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
