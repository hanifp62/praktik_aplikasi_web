import type { ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';

/**
 * Tata letak minim untuk mode pendakian. Tanpa navigasi lima permukaan dan tanpa
 * navigasi bawah ponsel: di jalur, dengan sarung tangan dan layar berkeringat, setiap
 * tautan yang tidak dibutuhkan adalah sasaran salah sentuh. Yang tersisa hanya jalan
 * kembali ke trip dan landmark utama untuk tautan lewati di templat akar.
 */
export function TataLetakPendakian({ kembali, children }: { kembali: string; children: ReactNode }) {
    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <header className="sticky top-0 z-20 border-b border-subtle bg-surface">
                <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
                    <Button href={kembali} variant="secondary">
                        <Icon name="arrow-left" size={20} />
                        Kembali ke detail trip
                    </Button>
                    <LencanaDataContoh />
                </div>
            </header>
            <main id="konten" tabIndex={-1}>
                {children}
            </main>
        </div>
    );
}
