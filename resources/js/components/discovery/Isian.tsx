import type { SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { Checkbox, InputError, Label, Select, Textarea } from '@/components/ui/Field';

export interface Opsi {
    value: string;
    label: string;
}

interface SelectFieldProps extends SelectHTMLAttributes<HTMLSelectElement> {
    name: string;
    label: string;
    options: Opsi[];
    placeholder?: string;
    hint?: string;
    error?: string | string[] | null;
}

/**
 * Pilihan berlabel dengan petunjuk dan galat terikat lewat aria-describedby. Baris
 * pertama adalah placeholder bernilai kosong supaya
 * "belum memilih" tetap dapat dibedakan dari pilihan pertama.
 */
export function SelectField({ name, label, options, placeholder, hint, error, className, ...rest }: SelectFieldProps) {
    const penjelas = [hint ? `${name}-hint` : null, error ? `${name}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className={className}>
            <Label htmlFor={name}>{label}</Label>
            <Select id={name} name={name} aria-describedby={penjelas} aria-invalid={error ? true : undefined} {...rest}>
                {placeholder !== undefined ? <option value="">{placeholder}</option> : null}
                {options.map((o) => (
                    <option key={o.value} value={o.value}>{o.label}</option>
                ))}
            </Select>
            {hint ? <p id={`${name}-hint`} className="mt-1 text-xs text-muted">{hint}</p> : null}
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

interface TextareaFieldProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    name: string;
    label: string;
    error?: string | string[] | null;
}

export function TextareaField({ name, label, error, className, rows = 3, ...rest }: TextareaFieldProps) {
    return (
        <div className={className}>
            <Label htmlFor={name}>{label}</Label>
            <Textarea id={name} name={name} rows={rows} aria-describedby={error ? `${name}-error` : undefined} aria-invalid={error ? true : undefined} {...rest} />
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

interface CheckboxGroupProps {
    name: string;
    label: string;
    options: Opsi[];
    value: string[];
    onChange: (value: string[]) => void;
    error?: string | string[] | null;
}

/** Sekelompok kotak centang dalam fieldset berlegenda. */
export function CheckboxGroup({ name, label, options, value, onChange, error }: CheckboxGroupProps) {
    const ubah = (v: string, dicentang: boolean) => onChange(dicentang ? [...value, v] : value.filter((x) => x !== v));

    return (
        <fieldset aria-describedby={error ? `${name}-error` : undefined}>
            <legend className="text-sm font-medium text-secondary">{label}</legend>
            <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                {options.map((o) => {
                    const id = `${name}-${o.value}`;

                    return (
                        <label key={o.value} htmlFor={id} className="flex min-h-11 items-center gap-2 text-sm text-primary">
                            <Checkbox id={id} name={`${name}[]`} value={o.value} checked={value.includes(o.value)} onChange={(e) => ubah(o.value, e.target.checked)} />
                            {o.label}
                        </label>
                    );
                })}
            </div>
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </fieldset>
    );
}
