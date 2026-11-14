<?php

namespace App\Models;

use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use App\Exceptions\PublicationGateViolation;
use App\Services\OfficialStatusService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source_name', 'source_type', 'source_url', 'source_owner', 'retrieved_at',
    'verified_at', 'freshness_policy', 'verification_status', 'notes',
])]
class DataSource extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source_type' => SourceType::class,
            'verification_status' => VerificationStatus::class,
            'retrieved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function officialStatuses(): HasMany
    {
        return $this->hasMany(OfficialStatus::class);
    }

    protected static function booted(): void
    {
        // Jenis sumber ikut tersimpan di snapshot status bercache (R-009, "Resmi" atau bukan), jadi
        // perubahannya membuang snapshot jalur yang terdampak seketika (titipan W3-1j). Dipasang
        // pada model supaya setiap jalur tulis tercakup, sama seperti OfficialStatus.
        static::saved(function (self $sumber): void {
            if ($sumber->wasChanged('source_type')) {
                OfficialStatusService::forgetSnapshotsForSource($sumber->id);
            }
        });

        // R-008 dan R-022: kolom trails.data_source_id memakai nullOnDelete di basis data,
        // sehingga tanpa penjaga ini penghapusan DataSource melepas sumber jalur terbit diam-diam.
        static::deleting(function (self $source): void {
            $jalurTerbit = Trail::query()
                ->where('data_source_id', $source->id)
                ->where('is_published', true)
                ->first();

            if ($jalurTerbit !== null) {
                throw new PublicationGateViolation($jalurTerbit, ['Sumber data belum ditetapkan.']);
            }
        });
    }
}
