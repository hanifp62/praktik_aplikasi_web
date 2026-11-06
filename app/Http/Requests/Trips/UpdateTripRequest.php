<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mengubah rencana trip (features/04: "Pengguna bisa mengubah atau menghapus
 * rencananya"). Otorisasi lebih dulu lewat Policy, lalu validasi.
 *
 * Jalur, pemilik, dan status sengaja tidak ada di aturan ini. Mengganti jalur berarti
 * rencana baru karena daftar persiapan dan penilaian kesiapan dibangun dari jalurnya;
 * pemilik dan status ditetapkan server.
 *
 * Tanggal mulai yang tidak diubah tidak diperiksa terhadap lantai tanggal: rencana yang
 * tanggalnya sudah lewat tetap dapat dirapikan nama atau catatannya. Yang ditolak hanya
 * memindahkan rencana ke tanggal yang sudah lewat.
 */
class UpdateTripRequest extends FormRequest
{
    use ValidatesTripPlanDetails;

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
        /** @var TripPlan $trip */
        $trip = $this->route('trip');
        $tanggal = ['required', 'date'];

        if ($this->input('planned_date') !== $trip->planned_date->toDateString()) {
            $tanggal[] = 'after_or_equal:'.Timezone::earliestDateInIndonesia();
        }

        return $this->planRules($tanggal);
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseEmptyPlanInput();
    }
}
