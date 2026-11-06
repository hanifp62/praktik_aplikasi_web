import { Head, usePage } from '@inertiajs/react';

/**
 * Judul tab dari prop `title` yang dikirim setiap controller.
 *
 * Tanpa ini klien Inertia menimpa judul yang dirender server dengan nama merek saja,
 * sehingga setiap halaman bertab "Memount": riwayat, penanda, dan pembaca layar tidak
 * dapat membedakannya (WCAG 2.4.2). Dipasang sekali di setiap pembungkus halaman,
 * bukan di tiap halaman, supaya halaman baru tidak mungkin lupa. Formatnya diatur
 * callback `title` di app.tsx dan sama dengan <title> dari server (inertia.blade.php).
 */
export function JudulHalaman() {
    const { title } = usePage<{ title?: string }>().props;

    return <Head title={title ?? ''} />;
}
