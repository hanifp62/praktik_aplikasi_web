<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExperienceLevel;
use App\Enums\TaskOutcome;
use App\Enums\UsabilitySessionKind;
use App\Models\UsabilitySession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Satu sesi pengamatan studi kegunaan: hasil tugas, jawaban SUS, catatan.
 */
class StoreUsabilitySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UsabilitySession::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'participant_code' => ['required', 'string', 'max:20'],
            'kind' => ['required', new Enum(UsabilitySessionKind::class)],
            'experience_level' => ['nullable', new Enum(ExperienceLevel::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tasks' => ['nullable', 'array'],
            'tasks.*.outcome' => ['nullable', Rule::enum(TaskOutcome::class)],
            'tasks.*.seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'tasks.*.note' => ['nullable', 'string', 'max:500'],
            // Jawaban SUS boleh kosong seluruhnya, tetapi yang diisi harus 1-5.
            // Lembar setengah terisi tidak menghasilkan skor, dan itu ditangani
            // susScore(), bukan dengan menolak simpanannya: catatan tugasnya tetap
            // berharga meski respondennya berhenti di tengah kuesioner.
            'sus' => ['nullable', 'array'],
            'sus.*' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * Hanya tugas yang benar-benar diamati yang disimpan.
     *
     * Tugas tanpa hasil bukan tugas yang gagal, melainkan tugas yang tidak sempat
     * dijalankan, dan menyimpannya sebagai baris kosong akan membuatnya ikut terhitung
     * sebagai pengamatan.
     *
     * @return array<string, array<string, mixed>>
     */
    public function hasilTugasTerisi(): array
    {
        return collect($this->validated('tasks', []))
            ->filter(fn (array $tugas) => ! empty($tugas['outcome']))
            ->map(fn (array $tugas) => array_filter([
                'outcome' => $tugas['outcome'],
                'seconds' => isset($tugas['seconds']) && $tugas['seconds'] !== '' ? (int) $tugas['seconds'] : null,
                'note' => ($tugas['note'] ?? null) ?: null,
            ], fn ($nilai) => $nilai !== null))
            ->all();
    }

    /**
     * @return array<int, int|string|null>
     */
    public function jawabanSus(): array
    {
        return $this->validated('sus', []) ?? [];
    }
}
