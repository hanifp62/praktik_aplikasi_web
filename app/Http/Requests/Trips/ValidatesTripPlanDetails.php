<?php

namespace App\Http\Requests\Trips;

use App\Enums\TripType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Aturan isian rencana yang sama untuk membuat dan mengubah trip, supaya batas tanggal
 * dan peserta tidak pernah berbeda di dua formulir.
 *
 * Isian kosong menjadi null sebelum divalidasi: "tidak diisi" tetap tidak diisi, bukan
 * nol peserta atau tanggal selesai yang sama dengan tanggal mulai (R-007).
 */
trait ValidatesTripPlanDetails
{
    /**
     * @param  array<int, mixed>  $plannedDateRules
     * @return array<string, mixed>
     */
    protected function planRules(array $plannedDateRules): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'planned_date' => $plannedDateRules,
            'end_date' => ['nullable', 'date', 'after_or_equal:planned_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'participants_count' => ['nullable', 'integer', 'between:1,'.$this->maxParticipants()],
            'trip_type' => ['required', new Enum(TripType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $peserta = sprintf('Jumlah peserta harus antara 1 dan %d orang.', $this->maxParticipants());

        return [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai pendakian.',
            'participants_count.integer' => $peserta,
            'participants_count.between' => $peserta,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'end_date' => 'tanggal selesai',
            'participants_count' => 'jumlah peserta',
            'reminders_enabled' => 'pengingat',
        ];
    }

    /**
     * Rentang yang terlalu panjang diperiksa sesudah aturan dasar lolos, karena
     * batasnya relatif terhadap tanggal mulai yang harus sah lebih dulu.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['planned_date', 'end_date']) || $this->input('end_date') === null) {
                    return;
                }

                $hari = (int) config('hiking.trips.max_span_days');
                $batas = Carbon::parse($this->input('planned_date'))->addDays($hari);

                if (Carbon::parse($this->input('end_date'))->greaterThan($batas)) {
                    $validator->errors()->add('end_date', sprintf('Tanggal selesai paling lambat %d hari setelah tanggal mulai.', $hari));
                }
            },
        ];
    }

    protected function normaliseEmptyPlanInput(): void
    {
        $kosong = fn (mixed $v): mixed => $v === '' ? null : $v;

        $this->merge([
            'end_date' => $kosong($this->input('end_date')),
            'start_time' => $kosong($this->input('start_time')),
            'participants_count' => $kosong($this->input('participants_count')),
            'notes' => $kosong($this->input('notes')),
        ]);
    }

    private function maxParticipants(): int
    {
        return (int) config('hiking.trips.max_participants');
    }
}
