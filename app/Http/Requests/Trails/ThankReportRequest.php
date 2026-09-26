<?php

namespace App\Http\Requests\Trails;

use App\Models\TrailConditionReport;
use Illuminate\Foundation\Http\FormRequest;

class ThankReportRequest extends FormRequest
{
    /**
     * Berterima kasih pada laporan sendiri adalah bentuk paling sederhana dari menaikkan
     * angka sendirian, jadi pelapornya ditolak.
     */
    public function authorize(): bool
    {
        $laporan = $this->route('report');

        return $laporan instanceof TrailConditionReport && $laporan->user_id !== $this->user()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ya' => ['required', 'boolean'],
        ];
    }
}
