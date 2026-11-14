<?php

namespace App\Models;

use App\Enums\PhotoLicense;
use Database\Factories\MountainPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto gunung berlisensi (DK-37; ruling W8-R19). Gunung, berkas, ukuran, sidik, dan pengunggah
 * ditulis layanan FotoGunung lewat forceFill; isian hanya keterangan lisensinya.
 */
#[Fillable(['creator', 'license', 'source_url', 'attribution', 'alt_text'])]
class MountainPhoto extends Model
{
    /** @use HasFactory<MountainPhotoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'license' => PhotoLicense::class,
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
