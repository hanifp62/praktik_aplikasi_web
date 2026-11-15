import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Dialog } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';

/** Alamat kiriman dan pilihan bagian (DataErrorReportController, DataErrorCategory::pilihan()). */
export interface LaporKesalahan {
    url: string;
    kategori: Opsi[];
}

/**
 * Tautan "Laporkan kesalahan data" di kartu status jalur dan gunung (spec v3 bagian 10.7; ruling
 * W8-R17). Laporan masuk antrean cek ulang kurator kawasan itu dan tidak mengubah status yang
 * tampil (R-010); kalimat itu disebut sebelum dan sesudah mengirim. Pelapor dari sesi dan
 * sasarannya dari alamat kiriman, bukan dari isian.
 */
export function LaporkanKesalahan({ lapor, sasaran }: { lapor: LaporKesalahan; sasaran: string }) {
    const [terbuka, setTerbuka] = useState(false);
    const [terkirim, setTerkirim] = useState(false);
    const pemicu = useRef<HTMLElement | null>(null);
    const form = useForm({ category: '', note: '' });

    const tutup = () => {
        setTerbuka(false);
        form.clearErrors();
    };

    return (
        <div className="mt-2">
            <button
                type="button"
                className={kelas('inline-flex min-h-11 items-center rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                onClick={(e) => {
                    pemicu.current = e.currentTarget;
                    setTerkirim(false);
                    setTerbuka(true);
                }}
            >
                Laporkan kesalahan data
            </button>
            {terkirim ? <p className="text-meta text-secondary">Laporan terkirim. Kurator Memount mengecek ulang ke sumber resminya.</p> : null}

            <Dialog
                terbuka={terbuka}
                onTutup={tutup}
                judul="Laporkan kesalahan data"
                keterangan={`${sasaran}. Laporan Anda tidak mengubah status yang tampil; kurator Memount mengecek ulang ke sumber resminya.`}
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={tutup}>
                            Batal
                        </Button>
                        <Button
                            processing={form.processing}
                            onClick={() =>
                                form.post(lapor.url, {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        form.reset();
                                        setTerkirim(true);
                                        tutup();
                                    },
                                })
                            }
                        >
                            Kirim laporan
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <SelectField
                        name="category"
                        label="Bagian yang keliru"
                        required
                        placeholder="Pilih bagian"
                        options={lapor.kategori}
                        value={form.data.category}
                        onChange={(e) => form.setData('category', e.target.value)}
                        error={form.errors.category}
                    />
                    <div>
                        <TextareaField
                            name="note"
                            label="Apa yang keliru?"
                            required
                            rows={4}
                            maxLength={1000}
                            aria-describedby={form.errors.note ? 'note-petunjuk note-error' : 'note-petunjuk'}
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            error={form.errors.note}
                        />
                        <p id="note-petunjuk" className="mt-1 text-meta text-muted">
                            Sebut sumber yang Anda lihat bila ada, misalnya pengumuman balai bertanggal.
                        </p>
                    </div>
                </div>
            </Dialog>
        </div>
    );
}
