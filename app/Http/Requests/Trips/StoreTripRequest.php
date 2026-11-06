<?php

namespace App\Http\Requests\Trips;

use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-08 pembuatan rencana trip, ditambah detail rencana DK-07 (tanggal selesai, jumlah
 * peserta) dan sakelar pengingat FR-24.
 *
 * Lantai tanggal memakai WIB, bukan "today" UTC (IndonesianDateFloorTest). Kepemilikan
 * goal diperiksa di TripLifecycle::create(), bukan di sini: goal milik orang lain tidak
 * ditolak, melainkan dilepas dari trip, sama seperti sebelumnya. Pemilik dan status
 * tidak ada di aturan ini sehingga tidak pernah ikut validated(); keduanya ditetapkan
 * server.
 */
class StoreTripRequest extends FormRequest
{
    use ValidatesTripPlanDetails;

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
            // Mesin route fit menolak jalur yang belum dipublikasikan sebagai hard
            // constraint; pembuatan trip tidak boleh menjadi jalan pintas melewatinya.
            'trail_id' => ['required', Rule::exists('trails', 'id')->where(
                fn ($query) => $query->where('is_published', true)->whereNull('archived_at')
            )],
            'hiking_goal_id' => ['nullable', 'exists:hiking_goals,id'],
            'reminders_enabled' => ['sometimes', 'boolean'],
            ...$this->planRules(['required', 'date', 'after_or_equal:'.Timezone::earliestDateInIndonesia()]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseEmptyPlanInput();

        if ($this->input('hiking_goal_id') === '') {
            $this->merge(['hiking_goal_id' => null]);
        }
    }
}
