import { Lockup } from '@/components/brand/Lockup';
import type { ItemTab } from '@/components/ui/BilahTab';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { dashboard } from '@/routes';
import { MenuAkun } from './MenuAkun';
import { TautanSimpanan } from './TautanSimpanan';

/**
 * Navigasi segmen layar lebar (spec v3 bagian 5.2): lima tab yang sama dengan bilah tab
 * ponsel, ikon dan label 14 px. Tab aktif ditandai permukaan terangkat, garis, tebal huruf,
 * ikon varian isi, dan aria-current, bukan warna saja (WCAG 1.4.1).
 */
function NavigasiSegmen({ item }: { item: ReadonlyArray<ItemTab> }) {
    return (
        <nav aria-label="Navigasi utama" className="shrink-0">
            <ul className="flex gap-1 rounded-tombol bg-canvas p-1">
                {item.map((t) => (
                    <li key={t.label}>
                        <Tautan
                            href={t.href}
                            aria-current={t.aktif ? 'page' : undefined}
                            className={kelas(
                                'flex min-h-11 items-center gap-2 rounded-control border px-3 text-kecil xl:px-4',
                                fokusTerlihat,
                                t.aktif ? 'border-control bg-surface font-bold text-brand-ink' : 'border-transparent font-semibold text-secondary hover:bg-surface hover:text-primary',
                            )}
                        >
                            <Icon name={t.ikon} size={20} isi={t.aktif} />
                            <span>{t.label}</span>
                        </Tautan>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

/**
 * Header layar lebar 68 px (DK-33; spec v3 bagian 5.2; prototype 15): lockup di kiri,
 * navigasi segmen di tengah, lalu lencana Data contoh, Simpanan dengan hitungannya, dan
 * menu avatar di kanan. Hanya mulai 1024 px; di bawahnya setiap halaman membawa kepalanya
 * sendiri dan navigasi pindah ke bilah tab. Menempel di atas, dan tingginya dibaca app.css
 * lewat data-kepala-lebar supaya jangkar dan fokus papan ketik tidak tertutup.
 */
export function KepalaLebar({ item }: { item: ReadonlyArray<ItemTab> }) {
    return (
        <header data-kepala-lebar="" className="sticky top-0 z-30 hidden h-17 items-center gap-6 border-b border-subtle bg-surface px-6 lg:flex">
            <div className="flex flex-1 items-center">
                <Lockup href={dashboard.url()} />
            </div>
            <NavigasiSegmen item={item} />
            <div className="flex flex-1 items-center justify-end gap-2.5">
                <LencanaDataContoh />
                <TautanSimpanan ringkas />
                <MenuAkun />
            </div>
        </header>
    );
}
