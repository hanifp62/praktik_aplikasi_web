<?php

namespace App\Http\Requests\Profile;

use App\Models\UserOwnedItem;
use App\Services\PreparationService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Satu barang perlengkapan pribadi (spec v3 bagian 6.15; ruling W7-R8), untuk menambah maupun
 * mengganti nama. Hanya `nama` yang dibaca, dirapikan spasinya, dan tidak boleh kembar dengan
 * barang lain milik pendaki yang sama tanpa memandang huruf besar. Pemilik selalu dari sesi;
 * barang orang lain ditolak Policy.
 */
class SaveOwnedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $barang = $this->route('item');

        if ($barang === null) {
            return $this->user() !== null;
        }

        return $barang instanceof UserOwnedItem && $this->user()?->can('update', $barang) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:60', function (string $atribut, mixed $nilai, Closure $gagal): void {
                $nama = mb_strtolower((string) $nilai);

                if ($nama === mb_strtolower(PreparationService::PERMIT_LABEL)) {
                    $gagal('Nama itu dipakai butir izin di daftar persiapan. Pakai nama lain.');

                    return;
                }

                $barang = $this->route('item');
                $kembar = UserOwnedItem::query()
                    ->where('user_id', $this->user()->id)
                    ->when($barang instanceof UserOwnedItem, fn ($q) => $q->whereKeyNot($barang->id))
                    ->whereRaw('lower(name) = ?', [$nama])
                    ->exists();

                if ($kembar) {
                    $gagal('"'.$nilai.'" sudah ada di perlengkapan pribadi Anda.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama barangnya.',
            'nama.min' => 'Nama barang paling sedikit 2 karakter.',
            'nama.max' => 'Nama barang paling banyak 60 karakter.',
        ];
    }

    /**
     * Batas daftar hanya berlaku untuk barang baru.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('item') === null && UserOwnedItem::query()->where('user_id', $this->user()->id)->count() >= UserOwnedItem::BATAS) {
                $validator->errors()->add('nama', 'Perlengkapan pribadi paling banyak '.UserOwnedItem::BATAS.' barang. Hapus yang tidak lagi Anda bawa.');
            }
        }];
    }

    public function nama(): string
    {
        return (string) $this->validated('nama');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nama'))) {
            $this->merge(['nama' => trim((string) preg_replace('/\s+/u', ' ', $this->input('nama')))]);
        }
    }
}
