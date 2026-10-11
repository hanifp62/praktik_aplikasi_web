import { router, usePage } from '@inertiajs/react';
import { useState, type RefObject } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { InputError } from '@/components/ui/Field';
import { LembarBawah } from '@/components/ui/Lembar';
import { TombolSalin } from '@/components/ui/TombolSalin';
import { destroy, store } from '@/routes/shares';
import type { PropsBersama } from '@/types';

export interface TautanAktif {
    id: number;
    jumlahJalur: number;
    dibuat: string;
    berlakuSampai: string;
}

interface LembarBagikanProps {
    terbuka: boolean;
    onTutup: () => void;
    pemicu: RefObject<HTMLElement | null>;
    /** Jalur yang akan dibagikan, dalam urutan kolom: pilihan banding, atau simpanan terbaru. */
    jalur: Array<{ id: number; name: string }>;
    tautanAktif: TautanAktif[];
    /** Masa berlaku tautan baru dalam hari, dari config hiking.share_links.ttl_days (T8-M7). */
    hariBerlaku: number;
}

const opsi = { preserveScroll: true, preserveState: true } as const;

/**
 * Bagikan perbandingan (FR-23, spec v3 bagian 6.6 dan 9.3, prototype 06). Lembar ini menyebut
 * apa yang ikut dan apa yang tidak sebelum tautan dibuat: data jalur beserta sumber dan
 * waktunya ikut; catatan pribadi dan label kecocokan tidak, karena label dihitung dari
 * profil pembuatnya. Tautan baru ditunjukkan sekali bersama tombol salin; tautan lain yang
 * masih berlaku dapat dicabut kapan saja.
 *
 * Membuat dan mencabut adalah kiriman dari dalam dialog modal, jadi galat jaringan ditulis di
 * dalam lembar ini (ruling W2-5e): pesan jaringan kerangka menjadi inert di bawah dialog.
 */
export function LembarBagikan({ terbuka, onTutup, pemicu, jalur, tautanAktif, hariBerlaku }: LembarBagikanProps) {
    const { flash, errors } = usePage<PropsBersama>().props;
    const [memproses, setMemproses] = useState(false);
    const [galatJaringan, setGalatJaringan] = useState(false);
    const baru = flash.tautanBagikan;

    const kirim = (aksi: () => void) => {
        setGalatJaringan(false);
        setMemproses(true);
        aksi();
    };

    const selesai = {
        onFinish: () => setMemproses(false),
        onNetworkError: () => {
            setGalatJaringan(true);

            return false;
        },
    };

    const buat = () => kirim(() => router.post(store.url(), { trails: jalur.map((j) => j.id) }, { ...opsi, ...selesai }));

    const cabut = (id: number) => kirim(() => router.delete(destroy.url({ share: id }), { ...opsi, ...selesai }));

    return (
        <LembarBawah
            terbuka={terbuka}
            onTutup={onTutup}
            judul="Bagikan perbandingan"
            keterangan={`Tautan hanya-baca, berlaku ${hariBerlaku} hari. Penerima melihat data jalur beserta sumber dan waktunya. Catatan pribadi dan label kecocokan Anda tidak ikut, karena label itu dihitung dari profil Anda.`}
            pemicu={pemicu}
        >
            <div className="space-y-4">
                {galatJaringan ? (
                    <Alert variant="warning">Koneksi terputus sebelum jawaban server sampai. Periksa daftar tautan di bawah sebelum mencoba lagi, supaya tidak terbuat dua.</Alert>
                ) : null}

                {baru ? (
                    <div className="space-y-2 rounded-lg border border-subtle bg-surface-sunken p-3">
                        <p className="text-kecil font-bold text-primary">Tautan baru, berlaku sampai {baru.berlakuSampai}</p>
                        {/* URL terlihat sebagai teks yang mudah dipilih, bukan hanya bila papan klip gagal (T8-M13). */}
                        <p className="select-all break-all rounded-control border border-subtle bg-surface px-3 py-2 text-kecil text-primary">{baru.url}</p>
                        <TombolSalin teks={baru.url} label="Salin tautan" />
                        <p className="text-meta text-muted">Tautan ini hanya ditunjukkan sekarang. Simpan atau kirim sebelum menutup lembar ini.</p>
                    </div>
                ) : jalur.length >= 2 ? (
                    <div className="space-y-2">
                        <p className="text-kecil text-secondary">Yang dibagikan: {jalur.map((j) => j.name).join(', ')}.</p>
                        <Button onClick={buat} processing={memproses} aria-describedby={errors.trails ? 'galat-bagikan' : undefined}>
                            Buat tautan
                        </Button>
                        <InputError id="galat-bagikan" messages={errors.trails} />
                    </div>
                ) : (
                    <p className="text-kecil text-secondary">Pilih minimal 2 jalur yang masih terbit untuk dibagikan sebagai perbandingan.</p>
                )}

                {tautanAktif.length > 0 ? (
                    <section aria-labelledby="judul-tautan-aktif" className="space-y-2">
                        <h3 id="judul-tautan-aktif" className="text-subjudul text-primary">
                            Tautan yang masih berlaku
                        </h3>
                        <ul className="divide-y divide-subtle rounded-lg border border-subtle">
                            {tautanAktif.map((t) => (
                                <li key={t.id} className="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <span className="text-kecil text-secondary">
                                        {t.jumlahJalur} jalur, dibuat {t.dibuat}, berlaku sampai {t.berlakuSampai}
                                    </span>
                                    <Button variant="ghost" size="sm" processing={memproses} onClick={() => cabut(t.id)}>
                                        Cabut<span className="sr-only"> tautan yang dibuat {t.dibuat}</span>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </LembarBawah>
    );
}
