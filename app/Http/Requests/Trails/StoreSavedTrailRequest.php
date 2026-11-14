<?php

namespace App\Http\Requests\Trails;

use App\Models\TrailConsideration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan satu jalur (spec v3 bagian 6.2 dan 9.1), juga Urungkan sesudah "Hapus dari simpanan"
 * yang mengirim kembali catatan pribadi yang ikut terhapus.
 *
 * Hanya `catatan` yang dibaca. Jalur datang dari rute dan pemilik dari sesi, jadi `user_id`
 * atau `trail_id` di badan permintaan tidak pernah sampai ke basis data. Catatan boleh
 * kosong (simpan biasa, atau jalur yang memang tidak bercatatan) dan dibatasi seperti
 * catatan biasa. `dengan_toast` hanya memberi tahu bahwa klien akan menampilkan toast Urungkan,
 * supaya halaman tidak mengumumkan kejadian yang sama untuk kedua kalinya (W9).
 */
class StoreSavedTrailRequest extends FormRequest
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
            'catatan' => ['nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
            'dengan_toast' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.string' => 'Catatan pribadi harus berupa teks.',
            'catatan.max' => 'Catatan pribadi tidak boleh lebih dari '.TrailConsideration::CATATAN_MAKS.' karakter.',
        ];
    }
}
