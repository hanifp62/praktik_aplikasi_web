<?php

namespace App\Http\Requests\Moderation;

use App\Enums\ModerationAction;
use App\Enums\ModerationStatus;
use App\Models\TrailConditionReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Tindakan moderasi atas satu laporan komunitas (FR-17). Laporan tidak pernah mengubah
 * status resmi; yang diputuskan di sini hanya apakah ia tampil untuk pendaki lain.
 */
class ModerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('moderate', TrailConditionReport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', new Enum(ModerationAction::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
            // Keadaan laporan yang moderator baca di kartunya (ruling W6-6c dan W6-6q): sidik
            // isinya dan status yang tampil di kartu. ModerationQueueController::act()
            // mencocokkan keduanya dengan baris terkini sebelum setuju atau tolak diterapkan.
            // Hanya wajib untuk kedua tindakan itu; tandai dan hapus tidak memeriksanya.
            'sidik' => ['nullable', 'string', Rule::requiredIf(fn () => $this->memutusIsi())],
            'status_dibaca' => ['nullable', Rule::enum(ModerationStatus::class), Rule::requiredIf(fn () => $this->memutusIsi())],
        ];
    }

    /**
     * Setuju dan tolak memutus isi laporan, jadi keduanya membawa keadaan yang dibaca moderator.
     */
    private function memutusIsi(): bool
    {
        return in_array($this->input('action'), [ModerationAction::APPROVE->value, ModerationAction::REJECT->value], true);
    }

    public function tindakan(): ModerationAction
    {
        return ModerationAction::from($this->validated('action'));
    }
}
