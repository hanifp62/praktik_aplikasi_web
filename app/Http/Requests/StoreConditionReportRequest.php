<?php

namespace App\Http\Requests;

use App\Enums\ConditionTag;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Laporan kondisi jalur (FR-13). Aturannya sama dengan komponen Livewire yang
 * digantikannya; laporan selalu masuk moderasi dan tidak pernah menjadi status resmi
 * (PRD §49, §118).
 */
class StoreConditionReportRequest extends FormRequest
{
    /**
     * Setiap pendaki yang masuk boleh melapor; rute sudah dijaga middleware auth.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trail_id' => ['required', 'exists:trails,id'],
            'trail_segment_id' => ['nullable', Rule::exists('trail_segments', 'id')->where('trail_id', $this->input('trail_id'))],
            // Langit-langit memakai tanggal WIT supaya laporan pendakian hari ini tidak
            // pernah ditolak karena bagi UTC tanggalnya masih kemarin.
            'hike_date' => ['required', 'date', 'before_or_equal:'.Timezone::latestDateInIndonesia()],
            'condition_tags' => ['required', 'array', 'min:1'],
            'condition_tags.*' => [Rule::enum(ConditionTag::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            // PRD §81: MIME, ekstensi, dan ukuran dibatasi bersama.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Hanya dipakai untuk menautkan laporan ke riwayat MILIK pengirim; trip orang
            // lain tidak tersentuh karena pembaruannya disaring user_id.
            'trip_plan_id' => ['nullable', 'integer'],
        ];
    }
}
