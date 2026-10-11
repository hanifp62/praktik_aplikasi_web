<?php

namespace App\Support;

use App\Models\User;

/**
 * Kunci penyimpanan perangkat per pengguna (spec v3 bagian 10.4 dan 10.6; rencana induk W6 butir
 * 5 dan 7; ruling W6-R10): nama basis data paket lapangan `memount-luring-<kunci>` dan basis
 * data perekam jejak `jejak-pendakian-<kunci>`.
 *
 * HMAC id pengguna, bukan id itu sendiri: nama basis data terbaca oleh siapa pun yang memegang
 * perangkat, dan id berurutan menyebut berapa banyak pengguna yang ada. Stabil untuk pengguna yang
 * sama, berbeda untuk akun lain di peramban yang sama.
 *
 * Rahasianya hiking.device_key_secret (DEVICE_KEY_SECRET), dengan cadangan app.key bila kosong
 * (ruling W6-10c). Rahasia ini tidak diputar: kunci yang berganti membuat penjaga ganti akun
 * (W6 Tugas 11) melihat kunci lain pada kunjungan berikutnya, lalu menghapus paket lapangan dan
 * jejak yang belum terkirim di setiap perangkat. Dengan rahasia khusus, memutar APP_KEY tidak
 * lagi berakibat begitu; tanpa rahasia khusus, memutar APP_KEY berakibat sama.
 */
final class KunciPerangkat
{
    public static function untuk(User $pengguna): string
    {
        $rahasia = (string) (config('hiking.device_key_secret') ?: config('app.key'));

        return substr(hash_hmac('sha256', 'perangkat:'.$pengguna->getKey(), $rahasia), 0, 20);
    }
}
