import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { GarisProfil } from '@/components/trails/GarisProfil';
import type { KeadaanLangkah } from '@/components/trips/types';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AksiUtama } from './AksiUtama';
import type { KartuPerjalananData } from './types';

const keadaanLangkah: Record<KeadaanLangkah, { bilah: string; kata: string }> = {
    selesai: { bilah: 'bg-brand-700', kata: 'selesai' },
    berikutnya: { bilah: 'bg-aksen-pos', kata: 'langkah berikutnya' },
    belum: { bilah: 'bg-surface-sunken', kata: 'belum' },
};

/**
 * Kartu perjalanan berikutnya (fase B2, prototype 01). Tingginya mengikuti isinya (DK-31): tanpa
 * tinggi tetap dan tanpa foto, karena foto gunung berlisensi menunggu W8 (DK-37); visual datanya
 * garis profil jalur. Empat langkah memakai bilah per langkah: langkah selesai bilah merek dan
 * centang sesudah namanya, langkah berikutnya bilah kuning pos (satu aksen) dan huruf tebal
 * dengan aria-current, jadi keadaannya tidak dibawa warna saja. Setiap langkah selebar paling
 * sedikit namanya (min-w-max); bilahnya membungkus (flex-wrap) bila keempatnya tidak muat
 * sebaris, misalnya saat semua selesai (dengan centang) atau dengan spasi teks WCAG 1.4.12,
 * supaya kartu tidak meluap ke padding dan halaman tidak menggulir menyamping di 320 px
 * (ruling W5-2c / M2). Persiapan ditulis sebagai hitungan butir kritis dengan ruas per butir,
 * tanpa persen (R-005); daftar belum dibuat, seluruh butir Tidak berlaku, dan butir yang
 * menunggu BMKG masing-masing punya kalimatnya sendiri (ruling W5-2c / M3, W5-2a / I1).
 */
export function KartuPerjalanan({ kartu }: { kartu: KartuPerjalananData }) {
    const { persiapan } = kartu;

    return (
        <article aria-labelledby={`perjalanan-${kartu.id}`} className="rounded-utama border border-subtle bg-surface p-5 sm:p-6">
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada="merek">{kartu.jenisTrip}</Lencana>
                {kartu.hari ? (
                    <Lencana>
                        <span data-angka>{kartu.hari}</span>
                    </Lencana>
                ) : null}
            </p>

            <h2 id={`perjalanan-${kartu.id}`} className="mt-2 wrap-break-word text-judul-kartu text-primary">
                <Tautan href={kartu.url} className="rounded-xs underline-offset-2 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                    {kartu.nama}
                </Tautan>
            </h2>
            <p className="mt-1 wrap-break-word text-kecil text-secondary">
                {kartu.jalur}, {kartu.gunung}
            </p>
            <p data-angka className="mt-0.5 flex flex-wrap gap-x-3 text-kecil text-secondary">
                <span>{kartu.tanggal}</span>
                {kartu.jam ? <span>Mulai {kartu.jam}</span> : null}
                {kartu.peserta ? <span>{kartu.peserta}</span> : null}
            </p>

            {kartu.garis ? <GarisProfil titik={kartu.garis} label={null} className="mt-4" /> : null}
            <AngkaRingkas jalur={kartu.angka} className="mt-2 text-secondary" />

            <ol aria-label="Langkah trip" className="mt-4 flex flex-wrap gap-x-1.5 gap-y-2">
                {kartu.langkah.map((l) => (
                    <li key={l.kunci} aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined} className="min-w-max flex-1">
                        <span aria-hidden="true" className={kelas('block h-1.5 rounded-full', keadaanLangkah[l.keadaan].bilah)} />
                        <span className={kelas('mt-1.5 flex items-center gap-0.5 text-meta', l.keadaan === 'belum' ? 'font-semibold text-secondary' : 'font-bold text-primary')}>
                            {l.judul}
                            {l.keadaan === 'selesai' ? <Icon name="check" size={16} className="text-brand-ink" /> : null}
                        </span>
                        <span className="sr-only">, {keadaanLangkah[l.keadaan].kata}</span>
                    </li>
                ))}
            </ol>

            {kartu.peringatan ? <p className="mt-3 text-kecil font-bold text-warn-900">{kartu.peringatan}</p> : null}

            <section aria-labelledby={`persiapan-${kartu.id}`} className="mt-4 border-t border-subtle pt-4">
                <h3 id={`persiapan-${kartu.id}`} className="text-kecil font-bold text-primary">
                    Persiapan kritis
                </h3>
                {persiapan.jumlah === 0 ? (
                    <p className="mt-1 text-kecil text-secondary">Daftar persiapan belum dibuat. Daftarnya disusun dari jalur ini saat halaman persiapan dibuka.</p>
                ) : persiapan.berlaku === 0 ? (
                    // Ruling W5-2c / M3: seluruh butir kritis ditandai Tidak berlaku adalah
                    // keadaan lain dari "daftar belum dibuat" (TripSteps::teksPersiapan).
                    <p className="mt-1 text-kecil text-secondary">Semua {persiapan.jumlah} butir kritis ditandai Tidak berlaku.</p>
                ) : (
                    <>
                        <p data-angka className="mt-1 text-isi font-bold text-primary">
                            {persiapan.dikonfirmasi} dari {persiapan.berlaku} butir kritis dikonfirmasi
                        </p>
                        <div aria-hidden="true" className="mt-2 flex gap-1">
                            {Array.from({ length: persiapan.berlaku }, (_, i) => (
                                <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                            ))}
                        </div>
                        {persiapan.belum.length > 0 ? (
                            <p className="mt-2 wrap-break-word text-kecil text-primary">
                                Belum dikonfirmasi: {persiapan.belum.join(', ')}
                                {persiapan.sisa > 0 ? `, dan ${persiapan.sisa} lainnya` : null}.
                            </p>
                        ) : null}
                        {/* Ruling W5-2a: butir kritis yang menunggu BMKG disebut, bukan diam */}
                        {persiapan.menungguBmkg ? <p className="mt-2 text-kecil text-secondary">{persiapan.menungguBmkg}</p> : null}
                    </>
                )}
            </section>

            <AksiUtama aksi={kartu.aksi} />
        </article>
    );
}
