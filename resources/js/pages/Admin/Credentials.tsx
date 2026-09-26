import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import type { Paginasi } from '@/components/admin/Tabel';
import type { Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { credentials } from '@/routes/admin';
import { decide } from '@/routes/admin/credentials';
import type { PropsBersama } from '@/types';

interface Kredensial {
    id: number;
    pemegang: string;
    jenjang: string;
    skema: string | null;
    nomor: string;
    status: string;
    statusText: string;
    kedaluwarsa: boolean;
    penerbit: string;
    pengesah: string;
    berlaku: string;
    kawasan: string;
}

interface Keputusan {
    value: string;
    label: string;
    konfirmasi: string | null;
}

interface Props extends PropsBersama {
    filter: string;
    statuses: Opsi[];
    keputusan: Keputusan[];
    kredensial: Paginasi<Kredensial>;
}

/**
 * Verifikasi kredensial ahli (§43, §61). Yang diverifikasi keberadaan sertifikatnya,
 * bukan kompetensinya. Mencabut dan menyengketakan membuang hak seseorang, jadi
 * keduanya bertanya dulu; teks pertanyaannya datang dari server.
 */
export default function Credentials() {
    const { flash, errors, filter, statuses, keputusan, kredensial } = usePage<Props>().props;
    const [catatan, setCatatan] = useState<Record<number, string>>({});
    const [sedang, setSedang] = useState<{ id: number; keputusan: string } | null>(null);
    // Galat catatan milik kartu yang terakhir dikirim, bukan semua kartu.
    const [terakhir, setTerakhir] = useState<number | null>(null);

    const saring = (nilai: string) =>
        router.get(credentials.url({ query: { filter: nilai } }), {}, { preserveState: true, preserveScroll: true, replace: true });

    const putuskan = (id: number, k: Keputusan) => {
        if (k.konfirmasi && !window.confirm(k.konfirmasi)) {
            return;
        }
        setSedang({ id, keputusan: k.value });
        setTerakhir(id);
        router.post(
            decide.url({ credential: id, keputusan: k.value }),
            { catatan: catatan[id] ?? '' },
            {
                preserveScroll: true,
                onSuccess: () =>
                    setCatatan((c) => {
                        const salin = { ...c };
                        delete salin[id];
                        return salin;
                    }),
                onFinish: () => setSedang(null),
            },
        );
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Verifikasi Kredensial Ahli"
                    description="Yang diverifikasi di sini bukan kompetensinya, melainkan keberadaan sertifikatnya. BNSP dan LSP yang menguji kompetensi."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="mb-4 max-w-xs">
                    <Label htmlFor="filter">Filter status</Label>
                    <Select id="filter" value={filter} onChange={(e) => saring(e.target.value)}>
                        <option value="">Semua</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>{s.label}</option>
                        ))}
                    </Select>
                </div>

                {kredensial.data.length === 0 ? (
                    <Card>
                        <p className="text-sm text-secondary">Tidak ada kredensial pada filter ini.</p>
                    </Card>
                ) : (
                    <>
                        <ul className="space-y-4">
                            {kredensial.data.map((k) => (
                                <li key={k.id}>
                                    <Card>
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h2 className="text-base font-semibold text-primary">{k.pemegang}</h2>
                                                <p className="text-sm text-secondary">{k.jenjang}</p>
                                                <p className="mt-1 text-xs text-muted">
                                                    {k.skema} &middot; nomor {k.nomor}
                                                </p>
                                            </div>

                                            <div className="text-right text-xs">
                                                <span className="rounded-control bg-surface-sunken px-2.5 py-1 font-medium text-secondary">{k.statusText}</span>
                                                {/* Sertifikat BNSP berlaku tiga tahun. Yang lewat masa berlakunya
                                                    tidak memberi hak apa pun, meski sudah diverifikasi. */}
                                                {k.kedaluwarsa ? <p className="mt-1 font-medium text-warn-900">Kedaluwarsa</p> : null}
                                            </div>
                                        </div>

                                        <dl className="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                            <div>
                                                <dt className="text-xs text-muted">Diterbitkan oleh</dt>
                                                <dd className="text-primary">{k.penerbit}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted">Disahkan untuk kawasan oleh</dt>
                                                <dd className="text-primary">{k.pengesah}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted">Berlaku</dt>
                                                <dd className="text-primary">{k.berlaku}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted">Kawasan</dt>
                                                <dd className="text-primary">{k.kawasan}</dd>
                                            </div>
                                        </dl>

                                        <div className="mt-4 space-y-2">
                                            <Label htmlFor={`catatan-${k.id}`}>Catatan pemeriksaan (opsional)</Label>
                                            <TextInput
                                                id={`catatan-${k.id}`}
                                                className="text-sm"
                                                value={catatan[k.id] ?? ''}
                                                onChange={(e) => setCatatan((c) => ({ ...c, [k.id]: e.target.value }))}
                                                aria-describedby={terakhir === k.id && errors.catatan ? `catatan-${k.id}-hint catatan-${k.id}-error` : `catatan-${k.id}-hint`}
                                                aria-invalid={terakhir === k.id && errors.catatan ? true : undefined}
                                            />
                                            {terakhir === k.id ? <InputError id={`catatan-${k.id}-error`} messages={errors.catatan} /> : null}

                                            <p id={`catatan-${k.id}-hint`} className="text-xs text-secondary">
                                                Verifikasi memberi pemegangnya hak menyunting data jalur di kawasan yang tercantum. Setiap perubahan tercatat di jejak audit.
                                            </p>

                                            <div className="flex flex-wrap gap-2">
                                                {keputusan.map((kp) => (
                                                    <Button
                                                        key={kp.value}
                                                        size="sm"
                                                        variant={kp.konfirmasi ? 'secondary' : 'primary'}
                                                        processing={sedang?.id === k.id && sedang.keputusan === kp.value}
                                                        disabled={sedang !== null}
                                                        onClick={() => putuskan(k.id, kp)}
                                                        aria-label={`${kp.label}: ${k.pemegang}`}
                                                    >
                                                        {kp.label}
                                                    </Button>
                                                ))}
                                            </div>
                                        </div>
                                    </Card>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6">
                            <Pagination links={kredensial.links} />
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

Credentials.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
