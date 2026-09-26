<?php

namespace App\Http\Resources\Admin;

use App\Models\PermitRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Aturan perizinan untuk daftar admin: teks tampilan sudah jadi, plus nilai mentah
 * isian supaya form ubah dapat diisi dari baris tanpa kunjungan server.
 *
 * @mixin PermitRequirement
 */
class PermitRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sasaran' => $this->trail ? $this->trail->name : ($this->mountain?->name ?? 'Tidak tertaut'),
            'sasaranKeterangan' => $this->trail ? $this->trail->mountain->name : 'Seluruh jalur',
            'authority' => $this->authority,
            'ringkasan' => $this->summary(),
            'diverifikasi' => $this->verified_at?->translatedFormat('d M Y') ?? 'Belum',
            'sumber' => $this->source ?? 'Sumber belum dicatat',
            'isian' => [
                'scope' => $this->trail_id !== null ? 'trail' : 'mountain',
                'trail_id' => $this->trail_id,
                'mountain_id' => $this->mountain_id,
                'authority' => $this->authority,
                'booking_url' => $this->booking_url,
                'daily_quota' => $this->daily_quota,
                'booking_opens_days_before' => $this->booking_opens_days_before,
                'booking_closes_days_before' => $this->booking_closes_days_before,
                'guide_required' => (bool) $this->guide_required,
                'max_duration_days' => $this->max_duration_days,
                'notes' => $this->notes,
                'source' => $this->source,
                'source_url' => $this->source_url,
            ],
        ];
    }
}
