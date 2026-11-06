<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Jam kembali dan nomor registrasi untuk pesan rencana (spec v3 bagian 10.8; ruling W6-R4).
 * Tanggal dan jam dibaca dalam zona gunung; tanggal kembali tidak boleh sebelum tanggal mulai.
 * Pada hari mulai, jam kembali harus sesudah jam mulai, atau sesudah 00.00 bila jam mulai tidak
 * tercatat (pra-tinjau PT7-7b, ruling W6-7i M-3): eskalasi yang jatuh sebelum pendakian dimulai
 * membuat kontak bertindak tanpa alasan.
 */
class UpdateTripReturnRequest extends FormRequest
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
        /** @var TripPlan $trip */
        $trip = $this->route('trip');

        return [
            'kembali_tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$trip->planned_date->toDateString()],
            'kembali_jam' => ['required', 'date_format:H:i'],
            'registrasi' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kembali_tanggal.required' => 'Isi tanggal perkiraan kembali.',
            'kembali_tanggal.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal mulai trip.',
            'kembali_tanggal.date_format' => 'Tulis tanggal kembali sebagai tanggal kalender.',
            'kembali_jam.required' => 'Isi jam perkiraan kembali.',
            'kembali_jam.date_format' => 'Tulis jam kembali sebagai jam dan menit, misalnya 15:00.',
            'registrasi.max' => 'Nomor registrasi paling panjang 60 karakter.',
        ];
    }

    /**
     * Jam kembali pada tanggal mulai dibandingkan dengan jam mulai, sesudah aturan dasar lolos.
     * Trip tanpa jam mulai tercatat memakai 00.00 tanggal mulai, sama dengan predikat
     * PesanRencana::kembaliTidakSesudahMulai (ruling W6-7i, M-3 tinjauan Tugas 7): tanpa itu jam
     * kembali 00.00 lolos di sini dengan kabar "Pesan rencana sudah memakai jam ini." lalu ditolak
     * pesan rencana di layar yang sama. Kalimat galatnya tidak menyebut jam mulai yang tidak ada.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var TripPlan $trip */
                $trip = $this->route('trip');

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $mulai = $trip->start_time === null ? '00:00' : substr((string) $trip->start_time, 0, 5);

                if ($this->input('kembali_tanggal') === $trip->planned_date->toDateString() && $this->input('kembali_jam') <= $mulai) {
                    $validator->errors()->add('kembali_jam', $trip->start_time === null
                        ? 'Jam kembali harus sesudah pukul 00.00 pada tanggal mulai trip.'
                        : 'Jam kembali harus sesudah jam mulai trip, '.str_replace(':', '.', $mulai).'.');
                }
            },
        ];
    }
}
