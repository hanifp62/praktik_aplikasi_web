<?php

namespace App\Http\Requests\Admin;

use App\Models\Mountain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Isian gunung untuk simpan maupun ubah. Otorisasinya mengikuti MountainPolicy persis
 * seperti komponen Livewire yang digantikannya: create() untuk yang baru, update()
 * untuk yang datang lewat parameter route.
 */
class MountainRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mountain = $this->route('mountain');

        return $mountain instanceof Mountain
            ? $this->user()->can('update', $mountain)
            : $this->user()->can('create', Mountain::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'elevation_mdpl' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Slug diturunkan dari nama dan unik di basis data. Tanpa pemeriksaan ini nama kembar
     * berakhir sebagai galat 500 dari indeks unik, bukan kalimat di samping isiannya.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $slug = Str::slug((string) $this->input('name'));
                $mountain = $this->route('mountain');

                $kembar = $slug !== '' && Mountain::query()
                    ->where('slug', $slug)
                    ->when($mountain instanceof Mountain, fn ($query) => $query->whereKeyNot($mountain->getKey()))
                    ->exists();

                if ($kembar) {
                    $validator->errors()->add('name', 'Gunung dengan nama ini sudah terdaftar.');
                }
            },
        ];
    }
}
