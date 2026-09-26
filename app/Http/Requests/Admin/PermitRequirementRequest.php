<?php

namespace App\Http\Requests\Admin;

use App\Models\PermitRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aturan perizinan, untuk dicatat maupun diubah (PRD §36, §60, §61).
 */
class PermitRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', PermitRequirement::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scope = $this->input('scope');
        $opens = $this->input('booking_opens_days_before');

        return [
            'scope' => ['required', Rule::in(['trail', 'mountain'])],

            // Sasaran wajib diisi sesuai scope-nya; aturan tanpa sasaran tidak dapat
            // dicocokkan ke jalur mana pun dan hanya akan menjadi baris mati.
            'trail_id' => [Rule::requiredIf($scope === 'trail'), 'nullable', 'exists:trails,id'],
            'mountain_id' => [Rule::requiredIf($scope === 'mountain'), 'nullable', 'exists:mountains,id'],

            'authority' => ['required', 'string', 'max:160'],
            'booking_url' => ['nullable', 'url', 'max:500'],
            'daily_quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'booking_opens_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],

            // Perbandingan hanya berlaku bila keduanya diisi; banyak pengelola hanya
            // mengumumkan batas tutupnya saja.
            'booking_closes_days_before' => array_merge(
                ['nullable', 'integer', 'min:0', 'max:365'],
                $opens !== null && $opens !== '' ? ['lte:booking_opens_days_before'] : []
            ),

            'max_duration_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'guide_required' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trail_id.required' => 'Pilih jalur yang aturannya dicatat.',
            'mountain_id.required' => 'Pilih gunung yang aturannya dicatat.',
            'booking_closes_days_before.lte' => 'Penutupan pemesanan tidak boleh lebih awal daripada pembukaannya.',
        ];
    }

    /**
     * Atribut siap simpan: hanya satu sasaran yang terisi supaya pencarian aturan tidak
     * ambigu, dan menyimpan berarti kurator baru saja memeriksanya (PRD §60).
     *
     * @return array<string, mixed>
     */
    public function atribut(): array
    {
        $data = $this->validated();

        return [
            'trail_id' => $data['scope'] === 'trail' ? $data['trail_id'] : null,
            'mountain_id' => $data['scope'] === 'mountain' ? $data['mountain_id'] : null,
            'authority' => $data['authority'],
            'booking_url' => $data['booking_url'] ?? null,
            'daily_quota' => $data['daily_quota'] ?? null,
            'booking_opens_days_before' => $data['booking_opens_days_before'] ?? null,
            'booking_closes_days_before' => $data['booking_closes_days_before'] ?? null,
            'guide_required' => (bool) ($data['guide_required'] ?? false),
            'max_duration_days' => $data['max_duration_days'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => $data['source'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'verified_at' => now(),
        ];
    }
}
