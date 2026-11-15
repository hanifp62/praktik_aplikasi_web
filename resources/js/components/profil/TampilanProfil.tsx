import { router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Sakelar } from '@/components/ui/Sakelar';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { simpan } from '@/routes/tampilan';
import type { PropsBersama } from '@/types';

/**
 * Tampilan di Profil (spec v3 bagian 6.15): tema, efek transparan, dan gerak, ketiganya cookie
 * milik peramban ini (rute tema.simpan dan tampilan.simpan). Pilihan bahasa tidak ditampilkan
 * sampai terjemahan Inggris ada (DK-40). Sakelar langsung menyimpan, dan satu sakelar tidak
 * mengembalikan yang lain karena server hanya menulis pilihan yang dikirim.
 */
export function TampilanProfil() {
    const { tampilan } = usePage<PropsBersama>().props;

    const ubah = (data: { efek: 'otomatis' | 'solid' } | { gerak: 'sistem' | 'kurangi' }) => router.post(simpan.url(), data, { preserveScroll: true });

    return (
        <Card title="Tampilan" subtitle="Berlaku di peramban ini.">
            <ThemePicker />
            <div className="mt-4 divide-y divide-subtle border-t border-subtle">
                <Sakelar
                    judul="Kurangi efek transparan"
                    keterangan="Permukaan yang melayang memakai warna penuh, lebih mudah dibaca di bawah matahari."
                    nyala={tampilan.efek === 'solid'}
                    onUbah={(nyala) => ubah({ efek: nyala ? 'solid' : 'otomatis' })}
                />
                <Sakelar
                    judul="Kurangi gerak"
                    keterangan="Animasi dipendekkan di aplikasi ini, apa pun setelan perangkat Anda."
                    nyala={tampilan.gerak === 'kurangi'}
                    onUbah={(nyala) => ubah({ gerak: nyala ? 'kurangi' : 'sistem' })}
                />
            </div>
            <p className="mt-3 text-kecil text-secondary">Jam trip ditampilkan menurut zona gunungnya, dengan penanda WIB, WITA, atau WIT.</p>
        </Card>
    );
}

