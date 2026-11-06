import { useEffect, useRef, useState } from 'react';

/**
 * Sakelar layar tetap menyala (spec v3 bagian 6.12 dan 10.8): mati bawaan, karena layar yang
 * terus menyala menghabiskan baterai yang di jalur lebih berharga daripada kenyamanan. Peramban
 * melepas kunci layar setiap kali tab tersembunyi, jadi kunci diminta lagi saat terlihat.
 * Peramban tanpa Wake Lock API tidak mendapat sakelarnya (DK-40).
 */
export function useLayarMenyala(): { didukung: boolean; menyala: boolean; ubah: (nilai: boolean) => void } {
    const didukung = typeof navigator !== 'undefined' && 'wakeLock' in navigator;
    const [menyala, setMenyala] = useState(false);
    const kunci = useRef<WakeLockSentinel | null>(null);

    useEffect(() => {
        if (!didukung || !menyala) {
            return;
        }

        let batal = false;

        const minta = () => {
            if (document.visibilityState !== 'visible' || kunci.current !== null) {
                return;
            }

            navigator.wakeLock
                .request('screen')
                .then((sentinel) => {
                    if (batal) {
                        void sentinel.release();

                        return;
                    }

                    kunci.current = sentinel;
                    sentinel.addEventListener('release', () => {
                        kunci.current = null;
                    });
                })
                .catch(() => setMenyala(false));
        };

        minta();
        document.addEventListener('visibilitychange', minta);

        return () => {
            batal = true;
            document.removeEventListener('visibilitychange', minta);
            void kunci.current?.release();
            kunci.current = null;
        };
    }, [didukung, menyala]);

    return { didukung, menyala, ubah: setMenyala };
}
