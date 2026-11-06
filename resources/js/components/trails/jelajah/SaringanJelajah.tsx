import { useId, type ReactNode } from 'react';
import { Label, TextInput } from '@/components/ui/Field';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Sakelar } from '@/components/ui/Sakelar';
import { alihPilihan, type DrafSaringan } from './saringan';

export interface PilihanSaringan {
    nilai: string;
    label: string;
    jumlah: number;
}

/** Pilihan dan hitungannya dari server (HalamanJelajah::saringan). */
export interface OpsiSaringan {
    kecocokan: PilihanSaringan[] | null;
    jenis: PilihanSaringan[];
    teknisRendah: { aktif: boolean; jumlah: number };
    status: PilihanSaringan[];
}

interface SaringanJelajahProps {
    opsi: OpsiSaringan;
    draf: DrafSaringan;
    onUbah: (draf: DrafSaringan) => void;
}

function Kelompok({ judul, keterangan, children }: { judul: string; keterangan?: string; children: ReactNode }) {
    return (
        <fieldset className="space-y-1">
            <legend className="text-subjudul text-primary">{judul}</legend>
            {children}
            {keterangan ? <p className="text-meta text-secondary">{keterangan}</p> : null}
        </fieldset>
    );
}

function Hitungan({ jumlah }: { jumlah: number }) {
    return (
        <span data-angka="" className="text-meta font-semibold text-muted">
            {jumlah}
            <span className="sr-only"> jalur</span>
        </span>
    );
}

/**
 * Isi saringan Jelajah (spec v3 bagian 6.2, prototype 02 dan 15), dipakai lembar bawah di
 * ponsel dan panel kiri di layar lebar. Setiap pilihan menyebut hitungannya. Catatan yang
 * menjaga aturan ikut tertulis: tuntutan teknis yang belum diketahui tidak dianggap rendah
 * (R-006) dan jalur Tutup tidak pernah menjadi rekomendasi (BR-04). Rentang tanjakan diketik,
 * tidak diseret (WCAG 2.5.7), dan tanjakan lebih menentukan daripada mdpl (BR-02).
 */
export function SaringanJelajah({ opsi, draf, onUbah }: SaringanJelajahProps) {
    const id = useId();
    const semuaKecocokan = opsi.kecocokan?.map((p) => p.nilai) ?? [];
    const semuaJenis = opsi.jenis.map((p) => p.nilai);
    const meter = (teks: string): number | null => (teks.trim() === '' || Number.isNaN(Number(teks)) ? null : Math.max(0, Math.round(Number(teks))));

    return (
        <div className="space-y-6">
            {opsi.kecocokan ? (
                <Kelompok judul="Kecocokan dasar">
                    {opsi.kecocokan.map((p) => (
                        <KotakCentang
                            key={p.nilai}
                            label={p.label}
                            lencana={<Hitungan jumlah={p.jumlah} />}
                            checked={draf.kecocokan === null || draf.kecocokan.includes(p.nilai)}
                            onChange={(e) => onUbah({ ...draf, kecocokan: alihPilihan(draf.kecocokan, semuaKecocokan, p.nilai, e.target.checked) })}
                        />
                    ))}
                </Kelompok>
            ) : null}

            <Kelompok judul="Jenis trip">
                {opsi.jenis.map((p) => (
                    <KotakCentang
                        key={p.nilai}
                        label={p.label}
                        lencana={<Hitungan jumlah={p.jumlah} />}
                        checked={draf.jenis === null || draf.jenis.includes(p.nilai)}
                        onChange={(e) => onUbah({ ...draf, jenis: alihPilihan(draf.jenis, semuaJenis, p.nilai, e.target.checked) })}
                    />
                ))}
            </Kelompok>

            <Kelompok judul="Tuntutan teknis" keterangan="Jalur yang tuntutan teknisnya belum diketahui tidak dianggap rendah.">
                <Sakelar judul={`Hanya rendah (${opsi.teknisRendah.jumlah} jalur)`} nyala={draf.teknisRendah} onUbah={(nyala) => onUbah({ ...draf, teknisRendah: nyala })} />
            </Kelompok>

            <Kelompok judul="Tanjakan" keterangan="Diketik, tidak harus diseret. Tanjakan lebih menentukan daripada ketinggian puncak; jalur yang tanjakannya belum diketahui tidak ikut rentang ini.">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <Label htmlFor={`${id}-dari`}>Dari (m)</Label>
                        <TextInput id={`${id}-dari`} type="number" inputMode="numeric" min={0} step={50} value={draf.naikDari ?? ''} onChange={(e) => onUbah({ ...draf, naikDari: meter(e.target.value) })} />
                    </div>
                    <div>
                        <Label htmlFor={`${id}-sampai`}>Sampai (m)</Label>
                        <TextInput id={`${id}-sampai`} type="number" inputMode="numeric" min={0} step={50} value={draf.naikSampai ?? ''} onChange={(e) => onUbah({ ...draf, naikSampai: meter(e.target.value) })} />
                    </div>
                </div>
            </Kelompok>

            <Kelompok judul="Status resmi" keterangan="Jalur berstatus Tutup tidak pernah muncul sebagai rekomendasi.">
                {opsi.status.map((p) => (
                    <KotakCentang
                        key={p.nilai}
                        label={p.label}
                        lencana={<Hitungan jumlah={p.jumlah} />}
                        checked={draf.status.includes(p.nilai)}
                        onChange={(e) => onUbah({ ...draf, status: e.target.checked ? [...draf.status, p.nilai] : draf.status.filter((n) => n !== p.nilai) })}
                    />
                ))}
            </Kelompok>
        </div>
    );
}
