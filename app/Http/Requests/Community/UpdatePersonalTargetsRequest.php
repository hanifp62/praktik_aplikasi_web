<?php

namespace App\Http\Requests\Community;

use App\Enums\PersonalTargetMetric;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Target pribadi bulanan (FR-25, PRD §150; ruling W7-R7), dikirim sebagai satu set: setiap
 * ukuran wajib hadir, angka memasang targetnya, dan kosong mencopotnya. Isian yang tidak dikirim
 * sama sekali bukan perintah mencopot, jadi ditolak. Pemilik selalu dari sesi; tidak ada id
 * target atau pengguna yang dibaca dari isian.
 */
class UpdatePersonalTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $aturan = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            $aturan[$ukuran->isian()] = ['present', 'nullable', 'integer', 'min:1', 'max:'.$ukuran->maksimum()];
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanjakan_m' => 'Target tanjakan diisi dalam meter per bulan, 1 sampai 20.000, atau dikosongkan.',
            'jarak_km' => 'Target jarak diisi dalam kilometer bulat per bulan, 1 sampai 1.000, atau dikosongkan.',
            'pendakian' => 'Target pendakian diisi jumlah per bulan, 1 sampai 31, atau dikosongkan.',
            '*.present' => 'Isian target tidak terkirim lengkap. Muat ulang halaman lalu coba lagi.',
        ];
    }

    /**
     * Nilai target per ukuran; null berarti dicopot.
     *
     * @return array<string, int|null>
     */
    public function nilai(): array
    {
        $nilai = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            $isian = $this->validated($ukuran->isian());
            $nilai[$ukuran->value] = $isian === null ? null : (int) $isian;
        }

        return $nilai;
    }

    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => is_string($v) && trim($v) === '' ? null : $v;
        $isian = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            if ($this->exists($ukuran->isian())) {
                $isian[$ukuran->isian()] = $kosong($this->input($ukuran->isian()));
            }
        }

        $this->merge($isian);
    }
}
