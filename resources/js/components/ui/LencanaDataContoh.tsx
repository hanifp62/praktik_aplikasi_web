import { usePage } from '@inertiajs/react';
import type { PropsBersama } from '@/types';
import { Lencana } from './Lencana';

/**
 * Lencana "Data contoh" (DK-35, antislop R-38): tampil di setiap halaman ketika aplikasi
 * menyajikan data contoh, supaya data contoh tidak pernah terbaca sebagai data sungguhan.
 * Tanpa mode data contoh ia tidak menggambar apa pun.
 *
 * Prop `demo` dikirim `HandleInertiaRequests` dari `PenjagaDataContoh::sedangDisajikan()`
 * (tinjauan W1-9a #4), yang benar dari dua sumber independen: APP_DEMO eksplisit, atau
 * koneksi basis data default yang sedang benar-benar `demo` walau APP_DEMO belum
 * dinaikkan. Komponen ini sendiri hanya membaca boolean yang sudah digabung itu, sama
 * seperti lencana setara di halaman publik (resources/views/public/trail.blade.php).
 */
export function LencanaDataContoh() {
    const { demo } = usePage<PropsBersama>().props;

    if (!demo) {
        return null;
    }

    return (
        <Lencana nada="netral" garis="putus">
            Data contoh
            <span className="sr-only">: isi halaman ini bukan data sungguhan</span>
        </Lencana>
    );
}
