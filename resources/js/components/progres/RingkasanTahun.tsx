import type { RingkasanTahun as Ringkasan } from './types';

/**
 * Ringkasan satu tahun (spec v3 bagian 6.14, prototype 13): tiga petak dalam satu daftar, jadi
 * dibacakan sebagai satu kelompok, bukan tiga klaim setara. Angka yang tidak diketahui ditulis
 * "Belum ada", dan pendakian tanpa angka disebut jumlahnya, supaya total tidak terbaca lengkap
 * padahal tidak (R-007).
 */
export function RingkasanTahun({ ringkasan }: { ringkasan: Ringkasan }) {
    const petak = [
        { judul: ringkasan.judul, nilai: ringkasan.pendakian, satuan: ringkasan.rincian, catatan: null },
        { judul: 'Tanjakan', nilai: ringkasan.tanjakan, satuan: 'meter', catatan: ringkasan.tanjakanCatatan },
        { judul: 'Jarak', nilai: ringkasan.jarak, satuan: 'kilometer', catatan: ringkasan.jarakCatatan },
    ];

    return (
        <section aria-labelledby="judul-ringkasan-tahun">
            <h2 id="judul-ringkasan-tahun" className="sr-only">
                Ringkasan {ringkasan.judul.toLocaleLowerCase('id-ID')}
            </h2>
            <dl className="grid grid-cols-1 gap-2 min-[360px]:grid-cols-3">
                {petak.map((p) => (
                    <div key={p.judul} className="flex min-w-0 flex-col rounded-kartu border border-subtle bg-surface p-2.5 sm:p-3">
                        <dt className="text-label font-semibold text-secondary">{p.judul}</dt>
                        <dd className={p.nilai === null ? 'mt-1 text-kecil font-bold text-primary' : 'mt-0.5 break-words text-bagian font-extrabold text-primary'}>{p.nilai ?? 'Belum ada'}</dd>
                        {p.nilai === null ? null : <dd className="text-label text-secondary">{p.satuan}</dd>}
                        {p.catatan ? <dd className="mt-1 text-label text-muted">{p.catatan}</dd> : null}
                    </div>
                ))}
            </dl>
            <p className="mt-2 text-meta text-muted">{ringkasan.sumber}</p>
        </section>
    );
}

