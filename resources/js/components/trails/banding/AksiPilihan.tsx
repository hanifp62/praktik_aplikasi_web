import { Button } from '@/components/ui/Button';
import { compare } from '@/routes/trails';

interface AksiPilihanProps {
    /** Id jalur yang dicentang, dalam urutan dicentang: urutan kolom di halaman Banding. */
    dipilih: number[];
    /** Batas banding dari server (hiking.saved_trails.compare_max, DK-05). */
    maks: number;
    /** Jalur terbit yang dibandingkan bila tidak ada yang dicentang. */
    tersedia: number;
    /** Pesan pendek sesudah pembaca mencoba melewati batas; null bila tidak ada. */
    pesan?: string | null;
}

/**
 * Baki banding Simpanan (DK-05, spec v3 bagian 6.6, prototype 06), satu baris ringkas di
 * dalam bar aksi. Baki Jelajah lama yang mendaftar semua nama tersimpan membuat lapisan bawah
 * menutup 40 sampai 57 persen layar 320 sampai 360 px (tinjauan akhir W2, W2-F1), jadi di
 * sini tidak ada nama: centangnya sudah terlihat di kartu. Isinya hitungan "Dipilih N dari 3"
 * dengan ruasnya, dan satu aksi; pesan batas yang pendek menggantikan hitungan itu sesaat,
 * supaya bakinya tidak bertambah tinggi. Dengan dua centang atau lebih, pilihannya dibawa di
 * ?trails= dalam urutan dicentang. Dengan satu centang, aksinya diganti petunjuk "Pilih
 * minimal 2 jalur". Tanpa centang, tautannya telanjang (`compare.url()`) dan halaman Banding
 * membandingkan simpanan terbaru yang masih terbit.
 *
 * Hitungan, petunjuk, dan pesannya ada di satu pengumuman sebaris <p role="status">, supaya
 * pembaca layar mendengar setiap perubahan sebelum batasnya ditabrak.
 */
export function AksiPilihan({ dipilih, maks, tersedia, pesan = null }: AksiPilihanProps) {
    const jumlah = dipilih.length;
    const petunjuk = jumlah === 1 ? 'Pilih minimal 2 jalur untuk membandingkan.' : jumlah >= maks ? 'Batas banding tercapai.' : null;

    return (
        <div className="flex w-full items-center gap-3">
            <p role="status" className="min-w-0 flex-1 text-kecil font-bold text-primary">
                {pesan ? (
                    pesan
                ) : (
                    <>
                        Dipilih {jumlah} dari {maks}.{petunjuk ? <span className="sr-only"> {petunjuk}</span> : null}
                    </>
                )}
                {/* Ruas hitungan: bentuk pembantu, hitungannya sudah tertulis di atas. */}
                <span aria-hidden="true" className="mt-1.5 flex gap-1">
                    {Array.from({ length: maks }, (_, i) => (
                        <span key={i} className={i < jumlah ? 'h-1.5 w-6 rounded-full bg-brand-ink' : 'h-1.5 w-6 rounded-full bg-surface-sunken ring-1 ring-inset ring-control'} />
                    ))}
                </span>
            </p>

            {jumlah >= 2 ? (
                <Button size="sm" href={compare.url({ query: { trails: dipilih.join(',') } })} className="shrink-0">
                    Bandingkan {jumlah} jalur
                </Button>
            ) : jumlah === 1 ? (
                <p aria-hidden="true" className="max-w-36 shrink-0 text-meta text-secondary">
                    Pilih minimal 2 jalur
                </p>
            ) : tersedia >= 2 ? (
                <Button size="sm" variant="secondary" href={compare.url()} className="shrink-0">
                    Bandingkan {Math.min(tersedia, maks)} terbaru
                </Button>
            ) : null}
        </div>
    );
}
