import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Dialog } from '@/components/ui/Lembar';
import { destroy as hapusJejak } from '@/routes/profile/tracks';
import type { KabarProfil } from './types';

/**
 * Lokasi dan jejak di bagian privasi Profil (spec v3 bagian 6.15; PRD §82 sampai §84, BR-13):
 * kalimat tentang di mana lokasi diminta dan diproses, lalu "Hapus semua jejak saya" yang
 * menyebut akibatnya sebelum menghapus. Pendakian dan catatannya tetap ada; yang dicabut
 * hanya titik GPS-nya.
 */
export function DataLokasi({ kabar }: { kabar: KabarProfil | null }) {
    const [bertanya, setBertanya] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const pemicu = useRef<HTMLElement | null>(null);

    const hapus = () => {
        setMenghapus(true);
        router.delete(hapusJejak.url(), {
            preserveScroll: true,
            onFinish: () => {
                setMenghapus(false);
                setBertanya(false);
            },
        });
    };

    return (
        <Card title="Lokasi dan jejak">
            <p className="text-kecil text-secondary">
                Lokasi hanya diminta di mode lapangan Mendaki dan dihitung di perangkat Anda. Jejak dikirim ke server hanya bila perekaman jejak menyala, sesudah Anda turun.
            </p>
            <Button
                variant="secondary"
                size="sm"
                className="mt-3"
                onClick={(e) => {
                    pemicu.current = e.currentTarget;
                    setBertanya(true);
                }}
            >
                Hapus semua jejak saya
            </Button>
            <p role="status" className="mt-2 text-kecil text-secondary empty:!mt-0">
                {kabar?.bagian === 'privasi' ? kabar.teks : ''}
            </p>

            <Dialog
                terbuka={bertanya}
                onTutup={() => setBertanya(false)}
                judul="Hapus semua jejak?"
                keterangan="Seluruh titik GPS dari pendakian Anda dihapus permanen dan tidak dapat dikembalikan."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setBertanya(false)}>
                            Batal
                        </Button>
                        <Button variant="danger" processing={menghapus} onClick={hapus}>
                            Hapus jejak
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-primary">Pendakian, riwayat, laporan, dan catatannya tetap ada. Perekaman jejak untuk pendakian berikutnya tidak berubah.</p>
            </Dialog>
        </Card>
    );
}

