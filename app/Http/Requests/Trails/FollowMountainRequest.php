<?php

namespace App\Http\Requests\Trails;

use Illuminate\Foundation\Http\FormRequest;

class FollowMountainRequest extends FormRequest
{
    /**
     * Setiap pendaki yang masuk boleh mengikuti gunung; barisnya selalu milik dirinya
     * sendiri karena user_id diambil dari sesi, tidak pernah dari masukan.
     */
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
            'ya' => ['required', 'boolean'],
        ];
    }
}
