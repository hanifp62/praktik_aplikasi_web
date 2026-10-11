<?php

namespace App\Http\Resources\Admin;

use App\Models\OfficialStatus;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris riwayat status resmi: tanggal sudah diformat di server, sumber dan kapan
 * terakhir diverifikasi ikut, karena status tanpa keduanya tidak dapat dinilai.
 *
 * Tanggal dari instan UTC ditulis sebagai tanggal kalender WIB (Timezone::DEFAULT, ruling FF-1): halaman admin
 * tidak berkonteks satu jalur, jadi tanggal UTC server tidak pernah tampil.
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
        $berlaku = $this->effective_at?->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y') ?? '-';

        if ($this->expires_at) {
            $berlaku .= ' – '.$this->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y');
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
            'diverifikasi' => $this->verified_at?->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y'),
            'berlaku' => $berlaku,
            'dicatatOleh' => $this->recordedBy?->name ?? 'Sistem',
        ];
    }
}
