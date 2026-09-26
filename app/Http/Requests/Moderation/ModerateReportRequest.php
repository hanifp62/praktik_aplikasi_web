<?php

namespace App\Http\Requests\Moderation;

use App\Enums\ModerationAction;
use App\Models\TrailConditionReport;
use Illuminate\Foundation\Http\FormRequest;
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
        ];
    }

    public function tindakan(): ModerationAction
    {
        return ModerationAction::from($this->validated('action'));
    }
}
