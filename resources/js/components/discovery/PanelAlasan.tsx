import { FactorBars, type FaktorBar } from '@/components/ui/FactorBars';
import type { HasilRekomendasi } from './types';

const kelompok: Array<{ kunci: string; simbol: string; judul: string; masuk: (f: FaktorBar) => boolean }> = [
    // Kuat dan cukup sama-sama tidak menahan; kata di sebelah batangnya tetap membedakan.
    { kunci: 'sesuai', simbol: '✓', judul: 'Sesuai', masuk: (f) => f.kategori === 'kuat' || f.kategori === 'cukup' },
    // Sama dengan "Yang perlu diperhatikan" di bawah: mesin memakai ambang lemah yang sama.
    { kunci: 'perhatian', simbol: '⚠︎', judul: 'Perlu perhatian', masuk: (f) => f.kategori === 'lemah' },
    // Data yang belum ada bukan nilai rendah (R-006), jadi tidak dikelompokkan sebagai penahan.
    { kunci: 'belum', simbol: '?', judul: 'Belum diketahui', masuk: (f) => f.kategori === 'tak-diketahui' },
];

/**
 * Isi "Kenapa?" pada satu hasil: faktor dipisah menjadi yang sesuai (✓) dan yang
 * perlu perhatian (⚠) lebih dulu, karena pertanyaan pertama pendaki adalah faktor mana
 * yang menahan; prosa mesin menyusul untuk yang ingin membaca alasannya utuh.
 * Simbol hanya penanda visual, maknanya dibawa judul teksnya.
 */
export function PanelAlasan({ hasil, id }: { hasil: HasilRekomendasi; id: string }) {
    const bagian: Array<[keyof HasilRekomendasi['penjelasan'], string]> = [
        // Judulnya mengikuti hasil penilaian: "Mengapa cocok" pada jalur yang justru tidak
        // cocok membuat pendaki membaca dua hal yang bertentangan dalam satu kotak.
        ['why_it_fits', hasil.eligible ? 'Mengapa cocok' : 'Dasar penilaiannya'],
        ['what_to_watch', 'Yang perlu diperhatikan'],
        ['preparation_gap', 'Persiapan yang belum selesai'],
    ];

    return (
        <div id={id} data-panel-alasan className="mt-4 space-y-4 border-t border-subtle pt-4">
            {hasil.faktor.length > 0 ? (
                <div>
                    <h3 className="text-sm font-semibold text-primary">Penilaian per faktor</h3>
                    <div className="mt-2 grid gap-4 md:grid-cols-2">
                        {kelompok.map((k) => {
                            const isi = hasil.faktor.filter(k.masuk);

                            return isi.length > 0 ? (
                                <div key={k.kunci}>
                                    <h4 className="flex items-center gap-1.5 text-sm font-medium text-secondary">
                                        <span aria-hidden="true">{k.simbol}</span>
                                        {k.judul}
                                    </h4>
                                    <FactorBars factors={isi} className="mt-2" />
                                </div>
                            ) : null;
                        })}
                    </div>
                </div>
            ) : null}

            {bagian.map(([kunci, judul]) => (
                <div key={kunci}>
                    <h3 className="text-sm font-semibold text-primary">{judul}</h3>
                    <ul className="mt-1 list-disc space-y-1 pl-5 text-sm text-secondary">
                        {hasil.penjelasan[kunci].length > 0 ? (
                            hasil.penjelasan[kunci].map((baris) => <li key={baris}>{baris}</li>)
                        ) : (
                            <li className="list-none text-muted">Tidak ada catatan.</li>
                        )}
                    </ul>
                </div>
            ))}
        </div>
    );
}
