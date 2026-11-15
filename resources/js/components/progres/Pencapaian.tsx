import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { ButirPencapaian } from './types';

/**
 * Pencapaian (spec v3 bagian 6.14, prototype 13): tonggak tanjakan pertama dan laporan kondisi
 * pertama yang terbit, diturunkan dari riwayat pendaki ini saja (R-015). Tidak ada peringkat,
 * lencana kecepatan, atau perbandingan dengan pendaki lain; ikon hanya menemani judul yang
 * sudah menyebut jenisnya, jadi disembunyikan dari pembaca layar.
 */
export function Pencapaian({ pencapaian }: { pencapaian: ButirPencapaian[] }) {
    return (
        <section aria-labelledby="judul-pencapaian">
            <h2 id="judul-pencapaian" className="text-bagian text-primary">
                Pencapaian
            </h2>
            {pencapaian.length === 0 ? (
                <p className="mt-2 max-w-prose text-isi text-secondary">
                    Belum ada pencapaian. Tanjakan pertama di atas 500 m dan laporan kondisi pertama yang terbit akan tercatat di sini, dari pendakian Anda sendiri.
                </p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface px-4">
                    {pencapaian.map((p) => (
                        <li key={p.kunci} className="flex items-center gap-3 py-3">
                            <span
                                aria-hidden="true"
                                className={kelas(
                                    'inline-flex size-10 shrink-0 items-center justify-center rounded-control',
                                    p.jenis === 'laporan' ? 'bg-community-50 text-community-900' : 'bg-brand-100 text-brand-900',
                                )}
                            >
                                <Icon name={p.jenis === 'laporan' ? 'user' : 'tanjakan'} size={20} />
                            </span>
                            <span className="min-w-0">
                                <span className="block break-words text-kecil font-bold text-primary">{p.judul}</span>
                                <span className="block break-words text-meta text-secondary">{p.keterangan}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

