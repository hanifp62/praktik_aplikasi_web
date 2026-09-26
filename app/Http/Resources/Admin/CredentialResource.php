<?php

namespace App\Http\Resources\Admin;

use App\Models\ExpertCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kartu kredensial untuk halaman verifikasi. Sertifikat BNSP berlaku tiga tahun; yang
 * lewat masa berlakunya tidak memberi hak apa pun meski sudah diverifikasi, dan itu
 * ditandai di sini, bukan dihitung ulang di klien.
 *
 * @mixin ExpertCredential
 */
class CredentialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pemegang' => $this->user->name,
            'jenjang' => $this->level->label(),
            'skema' => $this->scheme,
            'nomor' => $this->certificate_number ?? 'tidak dicantumkan',
            'status' => $this->verification_status->value,
            'statusText' => $this->verification_status->label(),
            'kedaluwarsa' => $this->isExpired(),
            'penerbit' => $this->issuingAuthority->displayName(),
            'pengesah' => $this->endorsingAuthority?->displayName() ?? 'Belum disahkan balai mana pun',
            'berlaku' => $this->issued_at->translatedFormat('d M Y').' – '
                .($this->expires_at ? $this->expires_at->translatedFormat('d M Y') : 'tanpa batas tercatat'),
            'kawasan' => $this->mountains->pluck('name')->join(', ') ?: 'Belum ditetapkan',
        ];
    }
}
