import type { InputHTMLAttributes, LabelHTMLAttributes, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { kelas } from '@/lib/kelas';

/** Label di atas isian, tebal dan berwarna teks utama: bukan placeholder yang hilang saat mengetik. */
export function Label({ className, ...rest }: LabelHTMLAttributes<HTMLLabelElement>) {
    return <label className={kelas('block text-kecil font-bold text-primary', className)} {...rest} />;
}

const kontrol = 'mt-1 block min-h-12 w-full rounded-tombol border-control pl-4 text-isi text-primary focus:border-brand-600 focus:ring-brand-600';

/**
 * Isian teks v3 (spec v3 bagian 8.4): tinggi 48 px, radius tombol 16 px, huruf isi 16 px
 * (di bawah 16 px Safari iOS memperbesar halaman saat isian difokus), batas control
 * (3,65:1 di atas kartu, WCAG 1.4.11), fokus hijau merek.
 */
export function TextInput({ className, type = 'text', ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type={type} className={kelas(kontrol, 'pr-4', className)} {...rest} />;
}

export function Select({ className, ...rest }: SelectHTMLAttributes<HTMLSelectElement>) {
    return <select className={kelas(kontrol, 'pr-10', className)} {...rest} />;
}

export function Textarea({ className, ...rest }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea className={kelas(kontrol, 'py-3 pr-4', className)} {...rest} />;
}

/** Galat isian: daftar kalimat, warna danger dengan teks (bukan warna saja). */
export function InputError({ messages, id, className }: { messages?: string | string[] | null; id?: string; className?: string }) {
    const daftar = Array.isArray(messages) ? messages : messages ? [messages] : [];

    if (daftar.length === 0) {
        return null;
    }

    return (
        <ul id={id} className={kelas('space-y-1 text-kecil text-danger-900', className)}>
            {daftar.map((m) => (
                <li key={m}>{m}</li>
            ))}
        </ul>
    );
}

interface FormFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    name: string;
    label: string;
    hint?: string;
    error?: string | string[] | null;
}

/**
 * Isian berlabel dengan petunjuk dan galat yang terikat lewat aria-describedby
 * (WCAG 1.3.1, 3.3.1). aria-invalid hanya dipasang ketika memang ada galat.
 */
export function FormField({ name, label, hint, error, className, ...rest }: FormFieldProps) {
    const penjelas = [hint ? `${name}-hint` : null, error ? `${name}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className={className}>
            <Label htmlFor={name}>{label}</Label>
            <TextInput id={name} name={name} aria-describedby={penjelas} aria-invalid={error ? true : undefined} {...rest} />
            {hint ? <p id={`${name}-hint`} className="mt-1 text-kecil text-muted">{hint}</p> : null}
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

/**
 * Kotak centang v3: 28 px, sudut kontrol kecil 10 px, garis 2 px berkontras 3:1, dan isian
 * brand-700 ketika tercentang. Tanda centangnya digelapkan di tema gelap (app.css).
 * Batasnya transparan ketika tercentang: di Tailwind 4 kelas border-control (lapisan utilities)
 * mengalahkan batas transparan @tailwindcss/forms (lapisan base), jadi ditulis sendiri.
 */
export function Checkbox({ className, ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type="checkbox" className={kelas('size-7 shrink-0 rounded-kecil border-2 border-control text-brand-700 checked:border-transparent focus:ring-brand-600', className)} {...rest} />;
}
