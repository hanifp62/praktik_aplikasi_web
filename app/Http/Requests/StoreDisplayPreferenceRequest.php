<?php

namespace App\Http\Requests;

use App\Support\DisplayPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisplayPreferenceRequest extends FormRequest
{
    /**
     * Tamu pun boleh memilih; preferensinya hanya menyentuh cookie miliknya sendiri.
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
            'efek' => ['required_without:gerak', 'string', Rule::in(DisplayPreference::efekChoices())],
            'gerak' => ['required_without:efek', 'string', Rule::in(DisplayPreference::gerakChoices())],
        ];
    }
}
