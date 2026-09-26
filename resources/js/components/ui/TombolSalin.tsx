import { useId, useState } from 'react';
import { Button } from './Button';
import { Label, TextInput } from './Field';

interface TombolSalinProps {
    teks: string;
    label: string;
    berhasil?: string;
}

/**
 * Menyalin teks ke papan klip dengan satu klik; hasilnya diumumkan lewat role="status".
 * Papan klip dapat ditolak peramban (konteks tidak aman, izin), dan kegagalan itu tidak
 * didiamkan: teksnya ditampilkan di isian baca-saja untuk disalin sendiri.
 */
export function TombolSalin({ teks, label, berhasil = 'Tautan disalin.' }: TombolSalinProps) {
    const [keadaan, setKeadaan] = useState<'diam' | 'berhasil' | 'gagal'>('diam');
    const id = useId();

    const salin = async () => {
        try {
            await navigator.clipboard.writeText(teks);
            setKeadaan('berhasil');
        } catch {
            setKeadaan('gagal');
        }
    };

    return (
        <div className="space-y-2">
            <Button variant="secondary" size="sm" onClick={salin}>
                {label}
            </Button>
            <p role="status" className="text-xs text-secondary">
                {keadaan === 'berhasil' ? berhasil : ''}
            </p>
            {keadaan === 'gagal' ? (
                <div>
                    <Label htmlFor={id}>Salin tautan ini secara manual</Label>
                    <TextInput id={id} readOnly value={teks} onFocus={(e) => e.currentTarget.select()} />
                </div>
            ) : null}
        </div>
    );
}
