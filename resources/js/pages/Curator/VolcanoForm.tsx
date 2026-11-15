import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { SelectField, TextareaField } from '@/components/discovery/Isian';
import { KALIMAT_TERPISAH } from '@/components/trails/KartuGunungApi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { desk } from '@/routes/curator';
import { store } from '@/routes/curator/volcano';
import type { PropsBersama } from '@/types';

interface PilihanGunung {
    value: string;
    label: string;
    terkini: string | null;
}

interface Props extends PropsBersama {
    title: string;
    gunung: PilihanGunung[];
    gunungAwal: string | null;
    level: Array<{ value: string; label: string }>;
    atribusi: string;
}

/**
 * Pencatatan status gunung api PVMBG (DK-43; spec v3 bagian 10.7; ruling W8-R18). Kurator menyalin
 * dari laporan MAGMA Indonesia; gunung hanya dari kawasan badannya, dan server memeriksanya ulang.
 * Pencatat dan waktu verifikasi tidak dikirim dari sini.
 */
export default function VolcanoForm() {
    const { title, gunung, gunungAwal, level, atribusi } = usePage<Props>().props;
    const form = useForm({ gunung: gunungAwal ?? '', level: '', rekomendasi: '', tautan_laporan: '', diterbitkan: '' });
    const terkini = gunung.find((g) => g.value === form.data.gunung)?.terkini ?? null;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), { preserveScroll: true });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: desk.url(), label: 'Meja kurator' }} title={title} description={`Salin dari laporan ${atribusi}. ${KALIMAT_TERPISAH}`} />

                {gunung.length === 0 ? (
                    <EmptyState
                        jenis="menunggu"
                        title="Belum ada gunung di kawasan Anda"
                        menunggu="Menunggu admin Memount mengaitkan gunung ke badan Anda."
                        description="Status gunung api hanya dicatat untuk gunung di kawasan badan tempat Anda menjadi anggota."
                    />
                ) : (
                    <form onSubmit={kirim} className="space-y-4" noValidate>
                        <SelectField
                            name="gunung"
                            label="Gunung"
                            required
                            placeholder="Pilih gunung"
                            options={gunung.map((g) => ({ value: g.value, label: g.label }))}
                            hint={terkini ? `Level tercatat sekarang: ${terkini}.` : undefined}
                            value={form.data.gunung}
                            onChange={(e) => form.setData('gunung', e.target.value)}
                            error={form.errors.gunung}
                        />
                        <SelectField
                            name="level"
                            label="Level menurut PVMBG"
                            required
                            placeholder="Pilih level"
                            options={level}
                            value={form.data.level}
                            onChange={(e) => form.setData('level', e.target.value)}
                            error={form.errors.level}
                        />
                        <TextareaField
                            name="rekomendasi"
                            label="Rekomendasi PVMBG, termasuk radiusnya"
                            required
                            rows={3}
                            value={form.data.rekomendasi}
                            onChange={(e) => form.setData('rekomendasi', e.target.value)}
                            error={form.errors.rekomendasi}
                        />
                        <FormField
                            name="tautan_laporan"
                            label="Tautan laporan PVMBG"
                            type="url"
                            required
                            hint="Alamat https laporan yang Anda salin."
                            value={form.data.tautan_laporan}
                            onChange={(e) => form.setData('tautan_laporan', e.target.value)}
                            error={form.errors.tautan_laporan}
                        />
                        <FormField
                            name="diterbitkan"
                            label="Waktu laporan diterbitkan"
                            type="datetime-local"
                            required
                            hint="Jam gunung, seperti tertulis di laporan."
                            value={form.data.diterbitkan}
                            onChange={(e) => form.setData('diterbitkan', e.target.value)}
                            error={form.errors.diterbitkan}
                        />
                        <div className="flex flex-wrap gap-3 pt-2">
                            <Button type="submit" processing={form.processing}>
                                Catat status gunung api
                            </Button>
                            <Button variant="secondary" href={desk.url()}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}

VolcanoForm.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;

