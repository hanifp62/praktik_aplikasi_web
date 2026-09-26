<?php

namespace App\Http\Requests\Trails;

use App\Models\TrailConsideration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22).
 *
 * Hanya `catatan` yang divalidasi dan dibaca, jadi `user_id`, `trail_id`, atau `note`
 * di badan permintaan tidak pernah sampai ke basis data. Otorisasi tingkat objek
 * terjadi di controller: barisnya dicari di simpanan pengguna yang masuk (404 bagi yang
 * lain), lalu TrailConsiderationPolicy menjaga lapis kedua.
 *
 * Kolomnya wajib hadir walau boleh kosong: kosong berarti menghapus, tetapi permintaan
 * yang tidak membawa kolom itu sama sekali bukan perintah menghapus.
 */
class UpdateSavedTrailNoteRequest extends FormRequest
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
            'catatan' => ['present', 'nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.present' => 'Catatan pribadi tidak terkirim. Muat ulang halaman lalu coba lagi.',
            'catatan.string' => 'Catatan pribadi harus berupa teks.',
            'catatan.max' => 'Catatan pribadi tidak boleh lebih dari '.TrailConsideration::CATATAN_MAKS.' karakter.',
        ];
    }
}
