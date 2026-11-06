<?php

namespace App\Http\Requests\Admin;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Catatan status resmi baru (FR-11). Status melekat pada gunung, jalur, atau segmen
 * (PRD §42) dan selalu membawa sumber serta waktunya (PRD §41).
 */
class StoreOfficialStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OfficialStatus::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', new Enum(StatusScope::class)],
            // Terikat pada tabel yang ditunjuk scope, bukan sekadar bilangan bulat.
            // Tanpa ini salah ketik satu angka dibalas halaman 404 dan seluruh isian
            // form terbuang (WCAG 3.3.1).
            'statusable_id' => [
                'required',
                'integer',
                Rule::exists(app($this->modelClassForScope())->getTable(), 'id'),
            ],
            'status' => ['required', new Enum(OfficialStatusValue::class)],
            'data_source_id' => ['nullable', Rule::exists('data_sources', 'id')],
            'source' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'effective_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:effective_at'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * tryFrom, bukan from: rules() memanggil ini sebelum scope sendiri tervalidasi, dan
     * scope yang tidak dikenal harus dilaporkan sebagai galat isian, bukan ValueError.
     *
     * @return class-string<Mountain|TrailSegment|Trail>
     */
    public function modelClassForScope(): string
    {
        return match (StatusScope::tryFrom((string) $this->input('scope'))) {
            StatusScope::MOUNTAIN => Mountain::class,
            StatusScope::SEGMENT => TrailSegment::class,
            default => Trail::class,
        };
    }
}
