<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpertCredential;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Keputusan atas kredensial: verifikasi, sengketakan, atau cabut. Keputusannya ada di
 * segmen rute; badan permintaan hanya membawa catatan pemeriksaan.
 */
class CredentialDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('verifyCredentials', ExpertCredential::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
