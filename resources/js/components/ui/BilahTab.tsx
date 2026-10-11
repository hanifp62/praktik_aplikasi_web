import { kelas } from '@/lib/kelas';
import { fokusTerlihat } from './gaya';
import { Icon, type NamaIkon } from './Icon';
import { Tautan } from './Tautan';

export interface ItemTab {
    label: string;
    href: string;
    ikon: NamaIkon;
    aktif: boolean;
}

interface BilahTabProps {
    item: ReadonlyArray<ItemTab>;
    /** Nama navigasi bagi pembaca layar. */
    label?: string;
    /** Bawaan true: melayang tetap di bawah layar ponsel. false untuk pameran primitif. */
    mengambang?: boolean;
}

/**
 * Bilah tab v3 (spec v3 bagian 5 dan 8.3): pil melayang berisi lima tab. Tab aktif
 * ditandai ikon varian isi, isian pil, tebal huruf, dan aria-current, bukan warna saja.
 * Label membungkus bila panjang dan tidak pernah di bawah 12 px. Dipasang AppLayout di
 * bawah 1024 px (DK-33); mulai 1024 px navigasi segmen di header layar lebar yang bekerja.
 * Atribut data-bilah-tab dibaca app.css untuk menghitung --nav-bawah, sehingga bar aksi,
 * toast, dan fokus papan ketik tidak pernah tertutup bilah ini.
 */
export function BilahTab({ item, label = 'Navigasi utama', mengambang = true }: BilahTabProps) {
    return (
        <nav
            aria-label={label}
            data-bilah-tab={mengambang ? '' : undefined}
            className={kelas(mengambang && 'fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] lg:hidden')}
        >
            <ul className="chrome mx-auto flex max-w-md items-stretch gap-1 rounded-full border p-1.5 shadow-overlay">
                {item.map((t) => (
                    <li key={t.label} className="min-w-11 flex-1">
                        <Tautan
                            href={t.href}
                            aria-current={t.aktif ? 'page' : undefined}
                            className={kelas(
                                'flex h-14 flex-col items-center justify-center gap-0.5 rounded-full px-1 text-center text-label leading-tight',
                                fokusTerlihat,
                                t.aktif ? 'bg-brand-100 font-bold text-brand-900' : 'font-medium text-secondary hover:bg-surface-sunken',
                            )}
                        >
                            <Icon name={t.ikon} size={22} isi={t.aktif} />
                            <span className="max-w-full break-words">{t.label}</span>
                        </Tautan>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
