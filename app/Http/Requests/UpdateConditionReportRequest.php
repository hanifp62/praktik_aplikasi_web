<?php

namespace App\Http\Requests;

use App\Enums\ConditionTag;
use App\Models\TrailConditionReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Ubah sebelum dimoderasi" (spec v3 bagian 6.13): pemilik laporan, selama laporannya masih
 * menunggu moderasi. Sesudah dimoderasi, laporan dibaca pendaki lain apa adanya dan tidak dapat
 * diubah lagi. Aturan isiannya sama dengan laporan baru (StoreConditionReportRequest).
 *
 * Otorisasi memakai Policy (ruling W6-6k, M-6), bukan menulis ulang aturannya di sini. Sejak
 * ruling W6-6r huruf (b) yang diperiksa hanya kepemilikan, lewat
 * TrailConditionReportPolicy::submitUpdate(). Status "masih menunggu moderasi" diperiksa
 * UPDATE bersyarat di ConditionReportController::update(), pada saat tulisannya terjadi:
 * pemeriksaan status di sini hanya membaca keadaan sebelum moderator sempat bertindak, dan
 * pelapor yang menyimpan sesudah laporannya ditinjau menerima 403 HTML, bukan kalimat.
 */
class UpdateConditionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $laporan = $this->route('report');

        return $laporan instanceof TrailConditionReport && $this->user()?->can('submitUpdate', $laporan) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TrailConditionReport $laporan */
        $laporan = $this->route('report');

        return [
            'condition_tags' => ['required', 'array', 'min:1'],
            'condition_tags.*' => [Rule::enum(ConditionTag::class)],
            'trail_segment_id' => ['nullable', Rule::exists('trail_segments', 'id')->where('trail_id', $laporan->trail_id)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
