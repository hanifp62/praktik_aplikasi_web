import { LabelKomunitas } from '@/components/trails/LabelSumber';
import { Lencana } from '@/components/ui/Lencana';
import type { LaporanGunung } from './types';

/**
 * Kondisi terbaru dari pendaki di jalur-jalur gunung ini (spec v3 bagian 6.3): lapisan
 * komunitas yang melengkapi status resmi, tidak menggantikannya (R-010). Setiap kartu
 * bergaris putus-putus komunitas, menyebut jalurnya, dan menghitung kesegarannya dari tanggal
 * pendakian, bukan tanggal kirim (§51).
 */
export function KondisiPendaki({ laporan }: { laporan: LaporanGunung[] }) {
    return (
        <ul className="space-y-3">
            {laporan.map((l) => (
                <li key={l.id} className="rounded-lg border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <LabelKomunitas />
                        <span className="break-words text-meta font-bold text-community-900">{l.jalur}</span>
                        <Lencana nada="netral">{l.kesegaran}</Lencana>
                    </div>
                    {l.tags.length > 0 ? (
                        <ul className="mt-2 flex flex-wrap gap-1.5">
                            {l.tags.map((t) => (
                                <li key={t}>
                                    <Lencana nada="komunitas" bentuk="pil">
                                        {t}
                                    </Lencana>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                    {l.catatan ? <p className="mt-2 break-words text-kecil text-primary">{l.catatan}</p> : null}
                    <p className="mt-2 text-meta text-community-900">
                        Dilaporkan {l.dilaporkan}, dari pendakian {l.tanggalPendakian}, lolos moderasi.
                    </p>
                </li>
            ))}
        </ul>
    );
}
