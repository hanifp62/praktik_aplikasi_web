import { usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trails: JalurTersimpan[];
    batas: number;
    batasBanding: number;
    batasCatatan: number;
    pengumuman: string;
}

/**
 * Daftar rute tersimpan (DK-05: simpan longgar, banding sempit), dari simpanan di basis
 * data: tetap ada setelah keluar dan masuk lagi, juga di perangkat lain.
 *
 * Tombol simpan tidak diulang di baris: setiap baris di sini sudah tersimpan. Yang ada
 * adalah centang untuk memilih sampai batasBanding jalur yang dibandingkan, catatan
 * pribadi (FR-22), dan "Hapus dari simpanan" yang menyebut dirinya penghapusan. Tanpa
 * centang, aksi banding membandingkan simpanan terbaru yang masih terbit. Centang baru
 * ditawarkan bila ada minimal dua simpanan yang masih terbit, supaya tidak ada kontrol
 * yang tidak dapat menghasilkan banding (jalur yang ditarik dari terbit tidak dapat
 * dicentang, jadi tidak dihitung).
 */
export default function Saved() {
    const { trails, batas, batasBanding, batasCatatan, pengumuman } = usePage<Props>().props;
    const [dipilih, setDipilih] = useState<number[]>([]);
    const tersedia = trails.filter((t) => t.terbit).length;
    const berlaku = (ids: number[]) => ids.filter((id) => trails.some((t) => t.id === id && t.terbit));
    // Jalur yang baru dihapus atau ditarik dari terbit tidak boleh tertinggal di pilihan.
    const pilihanBerlaku = berlaku(dipilih);

    const pilih = (id: number) =>
        setDipilih((sebelum) => {
            const d = berlaku(sebelum);

            return d.includes(id) ? d.filter((x) => x !== id) : d.length >= batasBanding ? d : [...d, id];
        });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Rute Tersimpan"
                    description="Jalur yang Anda simpan dari Jelajah. Tersimpan di akun Anda, jadi tetap ada setelah keluar dan masuk lagi, juga di perangkat lain."
                />

                {trails.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada rute tersimpan"
                        description="Tekan “Simpan rute” pada jalur di halaman Jelajah. Jalur yang Anda simpan terkumpul di sini dan bisa dibandingkan berdampingan."
                    >
                        <Button href={trailsIndex.url()}>Cari jalur untuk disimpan</Button>
                    </EmptyState>
                ) : (
                    <>
                        <div className="mb-3 space-y-2">
                            <p className="text-sm text-muted">
                                {trails.length} rute tersimpan, maksimal {batas}.
                            </p>

                            {tersedia >= 2 ? (
                                <AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} />
                            ) : (
                                <p className="text-sm text-secondary">Simpan satu jalur lagi untuk membandingkan.</p>
                            )}
                        </div>

                        <ul className="border-t border-subtle">
                            {trails.map((t) => (
                                <KartuSimpanan
                                    key={t.id}
                                    jalur={t}
                                    batasCatatan={batasCatatan}
                                    pilihan={tersedia >= 2 ? { dipilih: pilihanBerlaku.includes(t.id), penuh: pilihanBerlaku.length >= batasBanding } : null}
                                    onPilih={pilih}
                                />
                            ))}
                        </ul>
                    </>
                )}

                <p role="status" className="sr-only">{pengumuman}</p>
            </div>
        </div>
    );
}

Saved.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
