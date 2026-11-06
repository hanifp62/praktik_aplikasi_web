import { useEffect, useRef, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';

export interface Pilihan {
    value: string;
    label: string;
}

interface FormulirAdminProps {
    judul: string;
    onSubmit: () => void;
    processing: boolean;
    errors: Record<string, string | undefined>;
    labelSimpan?: string;
    /** Tombol Batal hanya muncul bila ada yang dapat dibatalkan. */
    onBatal?: () => void;
    /** Id isian yang difokuskan saat formulir dibuka, supaya pengguna keyboard tidak tertinggal di tabel. */
    fokusAwal?: string;
    className?: string;
    children: ReactNode;
}

/**
 * Kartu formulir admin: satu judul, isian, lalu Simpan dan Batal.
 *
 * Setelah server menolak isian, fokus pindah ke isian pertama yang salah. Galatnya sudah
 * terikat ke isiannya lewat aria-describedby, jadi pembaca layar langsung membacakan apa
 * yang harus dibetulkan (WCAG 3.3.1).
 */
export function FormulirAdmin({ judul, onSubmit, processing, errors, labelSimpan = 'Simpan', onBatal, fokusAwal, className, children }: FormulirAdminProps) {
    const formulir = useRef<HTMLFormElement>(null);
    // Tanda tangan berupa teks, bukan objek galat: fokus berpindah hanya ketika himpunan
    // galat berubah setelah dikirim, tidak pada setiap ketikan.
    const tandaGalat = Object.keys(errors).filter((k) => errors[k]).sort().join('|');

    useEffect(() => {
        if (fokusAwal) {
            document.getElementById(fokusAwal)?.focus();
        }
    }, [fokusAwal]);

    useEffect(() => {
        if (tandaGalat !== '') {
            formulir.current?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
        }
    }, [tandaGalat]);

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        onSubmit();
    };

    return (
        <Card className={className} title={judul}>
            <form ref={formulir} onSubmit={kirim} className="space-y-4">
                {children}
                <div className="flex flex-wrap gap-3">
                    <Button type="submit" processing={processing}>{labelSimpan}</Button>
                    {onBatal ? (
                        <Button variant="secondary" onClick={onBatal}>Batal</Button>
                    ) : null}
                </div>
            </form>
        </Card>
    );
}

interface PilihanAdminProps {
    name: string;
    label: string;
    value: string;
    onChange: (nilai: string) => void;
    options: Pilihan[];
    placeholder: string;
    error?: string;
    required?: boolean;
}

/** Select berlabel dengan galat terikat lewat aria-describedby. */
export function PilihanAdmin({ name, label, value, onChange, options, placeholder, error, required }: PilihanAdminProps) {
    return (
        <div>
            <Label htmlFor={name}>{label}</Label>
            <Select
                id={name}
                name={name}
                value={value}
                required={required}
                onChange={(e) => onChange(e.target.value)}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${name}-error` : undefined}
            >
                <option value="">{placeholder}</option>
                {options.map((o) => (
                    <option key={o.value} value={o.value}>{o.label}</option>
                ))}
            </Select>
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

interface TeksPanjangAdminProps {
    name: string;
    label: string;
    value: string;
    onChange: (nilai: string) => void;
    rows?: number;
    error?: string;
}

/** Textarea berlabel dengan galat terikat. */
export function TeksPanjangAdmin({ name, label, value, onChange, rows = 3, error }: TeksPanjangAdminProps) {
    return (
        <div>
            <Label htmlFor={name}>{label}</Label>
            <Textarea
                id={name}
                name={name}
                rows={rows}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${name}-error` : undefined}
            />
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

/** Aksi merusak ditanyakan dulu, dengan pesan yang menyebut akibatnya (seturut wire:confirm). */
export function konfirmasiLalu(pesan: string | null, aksi: () => void): void {
    if (pesan === null || window.confirm(pesan)) {
        aksi();
    }
}
