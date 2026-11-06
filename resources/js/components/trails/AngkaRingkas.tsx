import { kelas } from '@/lib/kelas';
import type { BarisJalur } from './types';

type AngkaJalur = Pick<BarisJalur, 'jarak' | 'tanjakan' | 'durasi'>;

/**
 * Tiga angka ringkas satu jalur dalam satu baris teks (kartu Simpanan dan pratinjau peta
 * Jelajah; T7-M2 dan tinjauan akhir W3 Tugas 5). Angka yang belum ada (tanda strip dari
 * TrailRowResource) dibuang, bukan dicetak sebagai "-, -", dan setiap angka membawa namanya
 * untuk pembaca layar: "12,5 km" saja tidak menyebut apakah itu jarak (R-007, WCAG 1.3.1).
 * Panah tanjakan disembunyikan dari pembaca layar dan diganti kata "tanjakan".
 */
export function AngkaRingkas({ jalur, className }: { jalur: AngkaJalur; className?: string }) {
    const angka = [
        { kunci: 'jarak', nilai: jalur.jarak, isi: <><span className="sr-only">Jarak </span>{jalur.jarak}</> },
        { kunci: 'tanjakan', nilai: jalur.tanjakan, isi: <><span className="sr-only">tanjakan </span><span aria-hidden="true">↑</span>{jalur.tanjakan}</> },
        { kunci: 'naik', nilai: jalur.durasi, isi: <>naik {jalur.durasi}</> },
    ].filter((a) => a.nilai !== '-');

    return (
        <p data-angka="" className={kelas('text-kecil', className)}>
            {angka.length === 0
                ? 'Jarak, tanjakan, dan lama naik belum tersedia. Menunggu kurator Memount melengkapinya.'
                : angka.map((a, i) => (
                      <span key={a.kunci}>
                          {i > 0 ? ', ' : null}
                          {a.isi}
                      </span>
                  ))}
        </p>
    );
}
