import { Icon } from '@/components/ui/Icon';
import type { KeadaanSyarat, Syarat } from './types';

/*
 * Tiga keadaan (perbaikan P-1, R-006): status resmi UNKNOWN bukan Tutup, tetapi juga
 * bukan sesuatu yang sudah dipastikan terpenuhi. Menandainya "terpenuhi" membuat pembaca
 * layar mendengar "Terpenuhi" untuk sesuatu yang justru belum diketahui siapa pun, jadi
 * ia mendapat ikon dan warna sendiri, tanpa tanda centang.
 */
const rupa: Record<KeadaanSyarat, { ikon: 'check' | 'x' | 'question'; kelas: string; awalan: string }> = {
    terpenuhi: { ikon: 'check', kelas: 'text-brand-ink', awalan: 'Terpenuhi: ' },
    gagal: { ikon: 'x', kelas: 'text-danger-900', awalan: 'Tidak terpenuhi: ' },
    'tak-diketahui': { ikon: 'question', kelas: 'text-muted', awalan: 'Belum diketahui: ' },
};

/**
 * Lapis 1 Route Fit (PRD §26): syarat dasar yang menentukan boleh tidaknya jalur dijadikan
 * rekomendasi. Setiap keadaan dibedakan ikon dan kata tersembunyi untuk pembaca layar,
 * bukan warna saja.
 *
 * Syarat status resmi yang dicentang "Terpenuhi" menulis "sekarang Buka", bentuk yang paling
 * dekat dengan OPEN = SAFE, jadi catatan kakinya memuat kalimat R-012 (I6 tinjauan akhir W3,
 * ruling W3-S5).
 */
export function SyaratDasar({ syarat }: { syarat: Syarat[] }) {
    return (
        <div className="rounded-lg border border-subtle bg-surface p-4">
            <ul className="space-y-2">
                {syarat.map((s) => {
                    const r = rupa[s.keadaan];

                    return (
                        <li key={s.teks} className="flex items-start gap-2 text-kecil text-primary">
                            <span className={r.kelas}>
                                <Icon name={r.ikon} size={16} />
                            </span>
                            <span className="min-w-0">
                                <span className="sr-only">{r.awalan}</span>
                                {s.teks}
                            </span>
                        </li>
                    );
                })}
            </ul>
            <p className="mt-3 border-t border-subtle pt-3 text-meta text-secondary">
                Bila satu saja gagal, jalur tidak dijadikan rekomendasi. Jalur Dibatasi tetap tampil, dengan peringatan. Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.
            </p>
        </div>
    );
}
