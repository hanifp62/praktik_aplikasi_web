<?php

namespace App\Http\Resources\Admin;

use App\Models\OfficialStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris riwayat status resmi: tanggal sudah diformat di server, sumber dan kapan
 * terakhir diverifikasi ikut, karena status tanpa keduanya tidak dapat dinilai.
 *
 * @mixin OfficialStatus
 */
class OfficialStatusRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $berlaku = $this->effective_at?->translatedFormat('d M Y') ?? '-';

        if ($this->expires_at) {
            $berlaku .= ' – '.$this->expires_at->translatedFormat('d M Y');
        }

        return [
            'id' => $this->id,
            'objek' => $this->statusable?->name ?? '-',
            'cakupan' => $this->scope->label(),
            'status' => $this->status->value,
            'statusText' => $this->status->label(),
            // Nama bebas bila diisi, jika tidak nama sumber terdaftarnya (§41).
            'sumber' => $this->source ?? $this->dataSource?->source_name ?? '-',
            'sumberUrl' => $this->source_url,
            'diverifikasi' => $this->verified_at?->translatedFormat('d M Y'),
            'berlaku' => $berlaku,
            'dicatatOleh' => $this->recordedBy?->name ?? 'Sistem',
        ];
    }
}
