<?php

namespace App\Http\Requests\Admin;

use App\Enums\AuthorityType;
use App\Models\Authority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Badan resmi beserta kawasan yang berada di bawahnya (PRD §43).
 */
class AuthorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAuthorities', Authority::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', new Enum(AuthorityType::class)],
            'abbreviation' => ['nullable', 'string', 'max:24'],
            'website' => ['nullable', 'url', 'max:200'],
            'contact' => ['nullable', 'string', 'max:160'],
            'jurisdiction' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mountain_ids' => ['array'],
            'mountain_ids.*' => [Rule::exists('mountains', 'id')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function atribut(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'abbreviation' => $data['abbreviation'] ?? null,
            'website' => $data['website'] ?? null,
            'contact' => $data['contact'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function mountainIds(): array
    {
        return array_map('intval', $this->validated('mountain_ids', []));
    }
}
