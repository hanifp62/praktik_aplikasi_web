import { Button } from '@/components/ui/Button';
import { compare, saved } from '@/routes/trails';

/**
 * Baki simpanan: yang ditunjukkan namanya, bukan jumlahnya, karena yang ingin diketahui
 * pendaki "apa saja yang sudah saya simpan". Tautan Bandingkan TELANJANG (tanpa id)
 * supaya halaman perbandingan masuk mode timbangan-tersimpan yang menulis balik.
 *
 * Menempel tepat di atas navigasi bawah ponsel (--nav-bawah dari AppLayout); di ponsel
 * safe area sudah ditanggung navigasi itu, di layar lebar baki sendiri yang
 * menanggungnya.
 */
export function ConsiderationTray({ trails, batas }: { trails: Array<{ id: number; name: string }>; batas: number }) {
    if (trails.length === 0) {
        return null;
    }

    return (
        <div
            className="sticky bottom-[var(--nav-bawah,0px)] z-30 border-t border-subtle bg-surface/95 px-4 pb-3 pt-3 backdrop-blur sm:pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))]"
        >
            <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-2">
                <p className="text-sm text-secondary">
                    Tersimpan ({trails.length}/{batas}): <span className="font-medium text-primary">{trails.map((t) => t.name).join(', ')}</span>
                </p>
                <Button size="sm" variant="secondary" href={saved.url()}>Lihat daftar</Button>
                {trails.length >= 2 ? <Button size="sm" href={compare.url()}>Bandingkan {trails.length} jalur</Button> : null}
            </div>
        </div>
    );
}
