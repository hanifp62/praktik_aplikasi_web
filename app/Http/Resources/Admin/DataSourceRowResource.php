<?php

namespace App\Http\Resources\Admin;

use App\Models\DataSource;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris daftar sumber data di halaman admin.
 *
 * Tanggal dari instan UTC ditulis sebagai tanggal kalender WIB (Timezone::DEFAULT, ruling FF-1): halaman admin
 * tidak berkonteks satu jalur, jadi tanggal UTC server tidak pernah tampil.
 *
 * @mixin DataSource
 */
class DataSourceRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->source_name,
            'url' => $this->source_url,
            'type' => $this->source_type->label(),
            'verification' => $this->verification_status->label(),
            'verifiedAt' => $this->verified_at?->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('d M Y') ?? '-',
            'isian' => [
                'source_name' => $this->source_name,
                'source_type' => $this->source_type->value,
                'source_url' => $this->source_url ?? '',
                'source_owner' => $this->source_owner ?? '',
                'freshness_policy' => $this->freshness_policy ?? '',
                'notes' => $this->notes ?? '',
            ],
        ];
    }
}
