<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Foto profil (W7). MIME dari isi berkas, ekstensi, ukuran, dan dimensi dibatasi bersama
 * (PRD §81): berkas yang bukan gambar, termasuk skrip yang dinamai .jpg, ditolak di sini
 * sebelum disentuh. SVG tidak diterima karena dapat membawa skrip.
 */
class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('hiking.uploads.avatar_max_kb'),
                'dimensions:min_width=64,min_height=64,max_width=8000,max_height=8000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih foto lebih dulu.',
            'foto.file' => 'Foto tidak terunggah dengan benar. Coba lagi.',
            'foto.image' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.',
            'foto.mimes' => 'Pilih foto JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto paling besar '.((int) config('hiking.uploads.avatar_max_kb') / 1024).' MB.',
            'foto.dimensions' => 'Foto paling kecil 64 x 64 piksel dan paling besar 8000 piksel di sisi terpanjang.',
        ];
    }
}
