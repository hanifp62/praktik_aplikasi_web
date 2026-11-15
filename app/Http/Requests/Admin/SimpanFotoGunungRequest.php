<?php

namespace App\Http\Requests\Admin;

use App\Enums\PhotoLicense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Foto gunung berlisensi (spec v3 bagian 8.7; DK-37; ruling W8-R19). Kolom wajibnya pembuat,
 * lisensi, URL sumber (kecuali foto milik Memount), teks atribusi, dan teks alternatif. MIME dari
 * isi berkas, ekstensi, ukuran, dan dimensi dibatasi bersama (PRD §81); SVG tidak diterima.
 * Gunung dari rute dan pengunggah dari sesi.
 */
class SimpanFotoGunungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('mountain')) ?? false;
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
                'max:'.(int) config('hiking.uploads.mountain_photo_max_kb'),
                'dimensions:min_width=800,min_height=400,max_width=10000,max_height=10000',
            ],
            'creator' => ['required', 'string', 'max:160'],
            'license' => ['required', new Enum(PhotoLicense::class)],
            'source_url' => ['nullable', 'required_unless:license,'.PhotoLicense::OWN->value, 'url:https,http', 'max:500'],
            'attribution' => ['required', 'string', 'max:300'],
            'alt_text' => ['required', 'string', 'min:10', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih foto lebih dulu.',
            'foto.image' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.',
            'foto.mimes' => 'Pilih foto JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto paling besar '.((int) config('hiking.uploads.mountain_photo_max_kb') / 1024).' MB.',
            'foto.dimensions' => 'Foto paling kecil 800 x 400 piksel supaya tetap tajam di kepala halaman.',
            'creator.required' => 'Tulis nama pembuat foto.',
            'license.required' => 'Pilih lisensi foto.',
            'source_url.required_unless' => 'Tulis alamat sumber foto, kecuali foto milik Memount.',
            'attribution.required' => 'Tulis teks atribusi seperti yang diminta lisensinya.',
            'alt_text.required' => 'Tulis teks alternatif yang menggambarkan isi foto untuk pembaca layar.',
            'alt_text.min' => 'Teks alternatif paling sedikit 10 karakter: gambarkan isi fotonya.',
        ];
    }
}
