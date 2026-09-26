import type { InputHTMLAttributes, LabelHTMLAttributes, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { kelas } from '@/lib/kelas';

export function Label({ className, ...rest }: LabelHTMLAttributes<HTMLLabelElement>) {
    return <label className={kelas('block text-sm font-medium text-secondary', className)} {...rest} />;
}

const kontrol = 'mt-1 block w-full rounded-control border-control focus:border-brand-600 focus:ring-brand-600';

/** Isian teks: batas control (3,52:1, WCAG 1.4.11), fokus hijau merek. */
export function TextInput({ className, type = 'text', ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type={type} className={kelas(kontrol, className)} {...rest} />;
}

export function Select({ className, ...rest }: SelectHTMLAttributes<HTMLSelectElement>) {
    return <select className={kelas(kontrol, className)} {...rest} />;
}

export function Textarea({ className, ...rest }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea className={kelas(kontrol, className)} {...rest} />;
}

/** Galat isian: daftar kalimat, warna danger dengan teks (bukan warna saja). */
export function InputError({ messages, id, className }: { messages?: string | string[] | null; id?: string; className?: string }) {
    const daftar = Array.isArray(messages) ? messages : messages ? [messages] : [];

    if (daftar.length === 0) {
        return null;
    }

    return (
        <ul id={id} className={kelas('space-y-1 text-sm text-danger-900', className)}>
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
            {hint ? <p id={`${name}-hint`} className="mt-1 text-xs text-muted">{hint}</p> : null}
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

export function Checkbox({ className, ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type="checkbox" className={kelas('rounded border-control text-brand-ink focus:ring-brand-600', className)} {...rest} />;
}
