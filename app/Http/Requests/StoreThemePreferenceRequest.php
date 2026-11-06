<?php

namespace App\Http\Requests;

use App\Support\ThemePreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThemePreferenceRequest extends FormRequest
{
    /**
     * Tamu pun boleh memilih tema; preferensinya hanya menyentuh cookie miliknya sendiri.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tema' => ['required', 'string', Rule::in(ThemePreference::choices())],
        ];
    }
}
