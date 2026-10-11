import { useId, useState } from 'react';
import { Button } from './Button';
import { Label, Textarea, TextInput } from './Field';

interface TombolSalinProps {
    teks: string;
    label: string;
    berhasil?: string;
    /** Label isian salin-manual saat papan klip ditolak; bawaannya untuk tautan. */
    labelManual?: string;
    /**
     * Jumlah baris isian salin-manual. Tanpa nilai, isiannya satu baris (bawaan, untuk tautan);
     * dengan nilai, isiannya textarea baca-saja supaya pemisah baris teks tidak hilang.
     */
    baris?: number;
}

/**
 * Menyalin teks ke papan klip dengan satu klik; hasilnya diumumkan lewat role="status".
 * Papan klip dapat ditolak peramban (konteks tidak aman, izin), dan kegagalan itu tidak
 * didiamkan: teksnya ditampilkan di isian baca-saja untuk disalin sendiri.
 *
 * labelManual dan baris opsional (ruling W6-7c, pra-tinjau Tugas 7 PT7-16): pesan rencana
 * berbaris banyak, jadi salinan manualnya berlabel pesan dan memakai textarea. Tanpa keduanya
 * perilakunya sama dengan sebelumnya (LembarBagikan).
 */
export function TombolSalin({ teks, label, berhasil = 'Tautan disalin.', labelManual = 'Salin tautan ini secara manual', baris }: TombolSalinProps) {
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
                    <Label htmlFor={id}>{labelManual}</Label>
                    {baris === undefined ? (
                        <TextInput id={id} readOnly value={teks} onFocus={(e) => e.currentTarget.select()} />
                    ) : (
                        <Textarea id={id} readOnly rows={baris} value={teks} onFocus={(e) => e.currentTarget.select()} />
                    )}
                </div>
            ) : null}
        </div>
    );
}
