import { useId } from 'react';
import { kelas } from '@/lib/kelas';
import { TombolIkon } from './TombolIkon';

interface StepperProps {
    label: string;
    keterangan?: string;
    nilai: number;
    min: number;
    max: number;
    onUbah: (nilai: number) => void;
    className?: string;
}

/** Tampilan tombol yang tidak tersedia, tanpa atribut disabled yang melepas fokus. */
const tombolBatas = 'aria-disabled:cursor-not-allowed aria-disabled:opacity-50 aria-disabled:active:scale-100';

/**
 * Stepper angka kecil (jumlah peserta, jumlah hari): dua tombol ikon bernama dan nilai yang
 * diumumkan pembaca layar ketika berubah. Tombol yang mencapai batas memakai aria-disabled
 * dan kliknya diabaikan, jadi nilainya tidak pernah keluar rentang. Atribut disabled tidak
 * dipakai di sini: ia melepas fokus dari tombol yang baru ditekan dan fokus jatuh ke body,
 * sedangkan aria-disabled mempertahankannya dan pembaca layar menyebut tombol itu tidak
 * tersedia.
 */
export function Stepper({ label, keterangan, nilai, min, max, onUbah, className }: StepperProps) {
    const id = useId();
    const nama = label.toLocaleLowerCase('id-ID');
    const diMin = nilai <= min;
    const diMax = nilai >= max;

    return (
        <div role="group" aria-labelledby={`${id}-label`} className={kelas('flex items-center justify-between gap-4', className)}>
            <div className="min-w-0">
                <p id={`${id}-label`} className="text-isi font-semibold text-primary">
                    {label}
                </p>
                {keterangan ? <p className="mt-0.5 text-kecil text-secondary">{keterangan}</p> : null}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <TombolIkon
                    ikon="minus"
                    label={`Kurangi ${nama}`}
                    varian="sumur"
                    bentuk="kotak"
                    aria-disabled={diMin || undefined}
                    className={tombolBatas}
                    onClick={() => {
                        if (!diMin) {
                            onUbah(Math.max(min, nilai - 1));
                        }
                    }}
                />
                <output aria-live="polite" className="min-w-8 text-center text-bagian font-extrabold text-primary">
                    {nilai}
                </output>
                <TombolIkon
                    ikon="plus"
                    label={`Tambah ${nama}`}
                    varian="sumur"
                    bentuk="kotak"
                    aria-disabled={diMax || undefined}
                    className={tombolBatas}
                    onClick={() => {
                        if (!diMax) {
                            onUbah(Math.min(max, nilai + 1));
                        }
                    }}
                />
            </div>
        </div>
    );
}
