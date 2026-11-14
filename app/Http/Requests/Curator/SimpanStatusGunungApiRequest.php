<?php

namespace App\Http\Requests\Curator;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Isian status gunung api PVMBG (DK-43; ruling W8-R18): gunung dari daftar kawasan kurator, level,
 * rekomendasi radius, tautan laporan PVMBG, dan waktu terbitnya menurut jam gunung. Pencatat dan
 * waktu verifikasi tidak pernah dari isian.
 */
class SimpanStatusGunungApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', VolcanoStatus::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gunung' => ['required', 'integer'],
            'level' => ['required', new Enum(VolcanoLevel::class)],
            'rekomendasi' => ['required', 'string', 'min:10', 'max:500'],
            'tautan_laporan' => ['required', 'url:https', 'max:500'],
            'diterbitkan' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gunung.required' => 'Pilih gunung dari daftar.',
            'level.required' => 'Pilih level yang tertulis di laporan PVMBG.',
            'rekomendasi.required' => 'Salin rekomendasi PVMBG, termasuk radius yang disebutkan.',
            'tautan_laporan.required' => 'Tempel tautan laporan PVMBG yang Anda salin.',
            'tautan_laporan.url' => 'Tautan laporan harus alamat https.',
            'diterbitkan.required' => 'Tulis waktu laporan itu diterbitkan PVMBG.',
        ];
    }

    /**
     * Gunung harus ada dan berada di kawasan badan pengguna; waktu terbit tidak di masa depan menurut
     * jam gunungnya.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $gunung = $this->gunung();

            if ($gunung === null || ! $this->user()->can('create', [VolcanoStatus::class, $gunung])) {
                $validator->errors()->add('gunung', 'Gunung itu di luar kawasan badan Anda. Pilih dari daftar.');

                return;
            }

            if ($this->diterbitkanUtc($gunung)->isFuture()) {
                $validator->errors()->add('diterbitkan', 'Waktu terbit tidak boleh di masa depan.');
            }
        }];
    }

    public function gunung(): ?Mountain
    {
        return Mountain::find((int) $this->input('gunung'));
    }

    public function diterbitkanUtc(Mountain $gunung): Carbon
    {
        $zona = Timezone::isSupported($gunung->timezone) ? $gunung->timezone : Timezone::DEFAULT;

        return Carbon::parse((string) $this->input('diterbitkan'), $zona)->utc();
    }
}
