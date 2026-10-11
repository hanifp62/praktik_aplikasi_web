<?php

namespace App\Http\Resources\Moderation;

use App\Enums\ConditionTag;
use App\Models\TrailConditionReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kartu laporan untuk antrean moderasi. Yang dikirim adalah apa yang perlu dibaca
 * moderator untuk memutuskan; tanggal dan label sudah jadi.
 *
 * @mixin TrailConditionReport
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tanggalPendakian = $this->hike_date->translatedFormat('d M Y');

        return [
            'id' => $this->id,
            'jalur' => $this->trail->name,
            'gunung' => $this->trail->mountain->name,
            'segmen' => $this->segment?->name,
            'pelapor' => $this->authorLabel(),
            'tanggalPendakian' => $tanggalPendakian,
            'dikirim' => $this->created_at->diffForHumans(),
            // Ruling W6-6q: status ini juga yang dikirim balik sebagai status_dibaca oleh setuju
            // dan tolak, supaya server menolak tindakan atas status yang sudah berubah.
            'status' => $this->moderation_status->value,
            'statusText' => $this->moderation_status->label(),
            'tags' => array_map(fn (ConditionTag $t) => $t->label(), $this->tags()),
            'catatan' => $this->note,
            // Ruling W6-6c (I-1): sidik isi yang moderator baca sekarang, dikirim balik oleh
            // tindakan setuju/tolak supaya server dapat menolaknya bila isinya sudah berubah.
            'sidik' => $this->sidik(),
            // PRD §67: moderasi foto hanya mungkin bila fotonya terlihat.
            'foto' => $this->photo_path ? [
                'url' => route('reports.photo', $this->resource),
                'alt' => 'Foto kondisi jalur yang dilampirkan pada laporan tanggal '.$tanggalPendakian,
            ] : null,
        ];
    }
}
