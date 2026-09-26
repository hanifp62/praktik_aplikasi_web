<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Petunjuk panjang kata sandi yang dibaca dari aturan validasinya sendiri, supaya
 * kalimat di formulir tidak pernah menyebut angka yang berbeda dari yang ditolak server.
 */
class PetunjukKataSandi
{
    public static function teks(): string
    {
        return 'Minimal '.Password::defaults()->appliedRules()['min'].' karakter.';
    }
}
