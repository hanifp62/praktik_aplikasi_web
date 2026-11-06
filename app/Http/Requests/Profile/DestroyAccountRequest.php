<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Penghapusan akun menuntut kata sandi yang berlaku, bukan sekadar sesi yang masih
 * terbuka: sesi di perangkat pinjaman atau yang lupa ditutup tidak cukup untuk
 * menghapus akun seseorang.
 */
class DestroyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
