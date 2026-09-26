import { Button } from '@/components/ui/Button';
import { compare } from '@/routes/trails';

interface AksiPilihanProps {
    /** Id jalur yang dicentang, dalam urutan dicentang: urutan kolom di halaman Banding. */
    dipilih: number[];
    /** Batas banding dari server (hiking.saved_trails.compare_max, DK-05). */
    maks: number;
    /** Jalur terbit yang dibandingkan bila tidak ada yang dicentang. */
    tersedia: number;
}

/**
 * Aksi banding untuk sebuah pilihan (DK-05). Tanpa centang, tautannya telanjang
 * (`compare.url()`) dan halaman Banding membandingkan simpanan terbaru yang masih terbit;
 * dengan centang, pilihannya dibawa di ?trails= dalam urutan dicentang. Hitungan terhadap
 * batas disebut sebelum batasnya ditabrak, lewat wilayah role="status" supaya pembaca
 * layar mendengar setiap perubahan.
 */
export function AksiPilihan({ dipilih, maks, tersedia }: AksiPilihanProps) {
    const jumlah = dipilih.length;

    return (
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
            <p role="status" className="text-sm text-secondary">
                {jumlah === 0 ? (
                    <>Centang sampai {maks} jalur untuk memilih yang dibandingkan.</>
                ) : (
                    <>
                        Dipilih {jumlah} dari {maks}.{' '}
                        {jumlah < 2 ? 'Pilih minimal 2 jalur untuk membandingkan.' : jumlah >= maks ? 'Batas banding tercapai.' : null}
                    </>
                )}
            </p>

            {jumlah >= 2 ? (
                <Button size="sm" href={compare.url({ query: { trails: dipilih.join(',') } })}>
                    Bandingkan {jumlah} jalur
                </Button>
            ) : jumlah === 0 && tersedia >= 2 ? (
                <Button size="sm" variant="secondary" href={compare.url()}>
                    Bandingkan {Math.min(tersedia, maks)} simpanan terbaru
                </Button>
            ) : null}
        </div>
    );
}
