import { useState, type InputHTMLAttributes } from 'react';
import { InputError, Label, TextInput } from '@/components/ui/Field';

interface IsianKataSandiProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'id'> {
    id: string;
    label: string;
    hint?: string;
    error?: string | null;
    labelTersembunyi?: boolean;
}

/**
 * Isian kata sandi dengan tombol Tampilkan/Sembunyikan. Salah ketik yang terlihat
 * diperbaiki sebelum dikirim, bukan sesudah ditolak. Nama tombol untuk pembaca layar
 * memuat teks yang terlihat ("Tampilkan kata sandi", WCAG 2.5.3); petunjuk dan galat
 * terikat lewat aria-describedby seperti FormField.
 */
export function IsianKataSandi({ id, label, hint, error, labelTersembunyi = false, className, ...rest }: IsianKataSandiProps) {
    const [terlihat, setTerlihat] = useState(false);
    const penjelas = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className={className}>
            <Label htmlFor={id} className={labelTersembunyi ? 'sr-only' : undefined}>{label}</Label>
            <div className="flex items-stretch gap-2">
                <TextInput
                    id={id}
                    type={terlihat ? 'text' : 'password'}
                    className="min-w-0 flex-1"
                    aria-describedby={penjelas}
                    aria-invalid={error ? true : undefined}
                    {...rest}
                />
                <button
                    type="button"
                    onClick={() => setTerlihat((v) => !v)}
                    aria-controls={id}
                    className="mt-1 inline-flex min-h-11 shrink-0 items-center rounded-control border border-control bg-surface px-3 text-sm font-medium text-primary transition hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                >
                    {terlihat ? 'Sembunyikan' : 'Tampilkan'}
                    <span className="sr-only"> kata sandi</span>
                </button>
            </div>
            {hint ? <p id={`${id}-hint`} className="mt-1 text-xs text-muted">{hint}</p> : null}
            <InputError id={`${id}-error`} messages={error} className="mt-2" />
        </div>
    );
}
