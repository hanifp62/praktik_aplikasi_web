import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { statuses, trails as daftarJalur } from '@/routes/admin';

export interface Temuan {
    kunci: string;
    label: string;
    jumlah: number;
    jumlahTeks: string;
}

interface RingkasanKesehatanProps {
    temuan: Temuan[];
    geometriDiperiksa: boolean;
    saringan: string | null;
    perluDitinjau: number;
}

const baris = 'flex min-h-11 items-center justify-between gap-4 rounded-sm py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Apa yang perlu diurus lebih dulu, diurutkan dari yang paling mendesak. Setiap temuan
 * membuka daftar jalur yang dimaksud; angkanya dihitung ulang server pada setiap
 * kunjungan, jadi ikut turun begitu datanya dibenahi.
 *
 * Hanya temuan yang ada yang ditampilkan. Temuan bernilai nol bukan pekerjaan.
 */
export function RingkasanKesehatan({ temuan, geometriDiperiksa, saringan, perluDitinjau }: RingkasanKesehatanProps) {
    const ada = temuan.filter((t) => t.jumlah > 0);
    // Jalur tayang yang tidak lengkap sedang dibaca pendaki hari ini; status resmi yang
    // basi menyusul, baru kekurangan data jalur draf.
    const tayang = ada.filter((t) => t.kunci === 'tayang-belum-lengkap');
    const lain = ada.filter((t) => t.kunci !== 'tayang-belum-lengkap');

    return (
        <section aria-labelledby="perlu-diurus" className="mb-6 rounded-lg border border-subtle bg-surface p-4 sm:p-6">
            <h2 id="perlu-diurus" className="text-lg font-semibold text-primary">Perlu diurus lebih dulu</h2>

            {ada.length === 0 && perluDitinjau === 0 ? (
                <p className="mt-2 max-w-prose text-sm text-secondary">Semua jalur aktif memenuhi syarat publikasi yang dapat diperiksa di sini, dan tidak ada status resmi yang perlu ditinjau.</p>
            ) : (
                <ul className="mt-2 divide-y divide-subtle">
                    {tayang.map((t) => (
                        <li key={t.kunci}>
                            <BarisTemuan href={daftarJalur.url({ query: { perlu: t.kunci } })} aktif={saringan === t.kunci} label={t.label} jumlah={t.jumlahTeks} mendesak />
                        </li>
                    ))}
                    {perluDitinjau > 0 ? (
                        <li>
                            <BarisTemuan href={statuses.url()} aktif={false} label="Status resmi perlu ditinjau" jumlah={`${perluDitinjau} status`} mendesak />
                        </li>
                    ) : null}
                    {lain.map((t) => (
                        <li key={t.kunci}>
                            <BarisTemuan href={daftarJalur.url({ query: { perlu: t.kunci } })} aktif={saringan === t.kunci} label={t.label} jumlah={t.jumlahTeks} mendesak={false} />
                        </li>
                    ))}
                </ul>
            )}

            {!geometriDiperiksa ? (
                <p className="mt-3 max-w-prose text-xs text-muted">Geometri jalur tidak dapat diperiksa pada koneksi basis data ini, jadi tidak ikut dihitung. Tidak terhitung bukan berarti sudah ada.</p>
            ) : null}
        </section>
    );
}

function BarisTemuan({ href, aktif, label, jumlah, mendesak }: { href: string; aktif: boolean; label: string; jumlah: string; mendesak: boolean }) {
    return (
        <Tautan href={href} aria-current={aktif ? 'page' : undefined} className={kelas(baris, aktif ? 'font-medium text-primary' : 'text-secondary hover:text-primary')}>
            <span className="underline underline-offset-2">{label}</span>
            <span className={kelas('shrink-0 font-semibold', mendesak ? 'text-warn-900' : 'text-primary')}>{jumlah}</span>
        </Tautan>
    );
}
