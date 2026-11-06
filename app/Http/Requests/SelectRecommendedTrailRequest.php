<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Memilih satu jalur dari halaman hasil rekomendasi. Kepemilikan run dan status jalur
 * diperiksa di controller; di sini hanya bentuk isiannya.
 */
class SelectRecommendedTrailRequest extends FormRequest
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
            'trail' => ['required', 'integer'],
        ];
    }
}
