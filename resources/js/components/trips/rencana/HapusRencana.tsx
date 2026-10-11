import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { destroy } from '@/routes/trips';

/** Keputusan server: apa yang boleh dilakukan pada rencana ini, dan alasannya bila tidak. */
export interface KelolaRencana {
    bolehUbah: boolean;
    bolehHapus: boolean;
    alasanTidakDapatDihapus: string | null;
}

/**
 * Menghapus rencana, terpisah di bawah seperti penghapusan akun: tindakan yang tidak
 * dapat dibatalkan tidak diletakkan di samping tindakan sehari-hari. Trip yang tidak
 * boleh dihapus menyebut alasannya di tempat yang sama, sebelum pendaki mencoba.
 */
export function HapusRencana({ tripId, kelola }: { tripId: number; kelola: KelolaRencana }) {
    const [menghapus, setMenghapus] = useState(false);

    if (!kelola.bolehHapus && !kelola.alasanTidakDapatDihapus) {
        return null;
    }

    const hapus = () => {
        if (!window.confirm('Hapus rencana trip ini? Daftar persiapan, penilaian kesiapan, dan pengingatnya ikut terhapus dan tidak dapat dikembalikan.')) {
            return;
        }

        router.delete(destroy.url(tripId), {
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
        });
    };

    return (
        <section aria-labelledby="hapus-rencana" className="border-t border-subtle pt-6">
            <h2 id="hapus-rencana" className="text-base font-semibold text-primary">Hapus rencana</h2>
            {kelola.bolehHapus ? (
                <>
                    <p className="mt-1 max-w-prose text-sm text-secondary">
                        Rencana yang belum dimulai atau sudah dibatalkan dapat dihapus beserta daftar persiapan, penilaian kesiapan, dan pengingatnya.
                    </p>
                    <Button variant="secondary" className="mt-3" onClick={hapus} processing={menghapus}>
                        Hapus rencana
                    </Button>
                </>
            ) : (
                <p className="mt-1 max-w-prose text-sm text-secondary">{kelola.alasanTidakDapatDihapus}</p>
            )}
        </section>
    );
}
