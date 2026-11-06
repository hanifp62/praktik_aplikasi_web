import type { ReactNode } from 'react';
import { Lockup } from '@/components/brand/Lockup';
import { JudulHalaman } from '@/components/ui/JudulHalaman';

/** Layout tamu (halaman akun): lockup merek di atas satu kartu berlandmark utama. */
export function GuestLayout({ children }: { children: ReactNode }) {
    return (
        <main id="konten" tabIndex={-1} className="flex min-h-dvh flex-col items-center bg-canvas pt-6 sm:justify-center sm:pt-0">
            <JudulHalaman />
            <div>
                <Lockup href="/" size="lg" />
            </div>
            <div className="mt-6 w-full overflow-hidden border border-subtle bg-surface px-6 py-4 sm:max-w-md sm:rounded-lg">{children}</div>
        </main>
    );
}
