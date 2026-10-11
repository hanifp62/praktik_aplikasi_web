<?php

namespace App\Http\Requests\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Menyelesaikan trip. Otorisasi lebih dulu, lalu validasi, urutan yang sama dengan
 * TripShow::complete() lama.
 */
class CompleteTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof TripPlan && $this->user()?->can('update', $trip) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Kolomnya di-cast ke enum, jadi nilai asing yang lolos validasi akan
            // menghasilkan error 500 alih-alih pesan yang dapat dibaca pengguna.
            'completion_state' => ['required', new Enum(CompletionState::class)],
            'personal_notes' => ['nullable', 'string', 'max:1000'],
            // DK-50: alasan berbalik di jalur, opsional; disimpan hanya bila hasilnya
            // "Dibatalkan di jalur" (TripLifecycle::complete).
            'alasan' => ['nullable', new Enum(CancellationReason::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('personal_notes') === '') {
            $this->merge(['personal_notes' => null]);
        }
    }
}
