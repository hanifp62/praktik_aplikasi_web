<?php

namespace App\Models;

use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'trail_id', 'trail_segment_id', 'user_id', 'hike_date', 'condition_tags',
    'photo_path', 'note', 'moderation_status', 'moderated_by', 'moderated_at',
])]
class TrailConditionReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hike_date' => 'date',
            'condition_tags' => 'array',
            'moderation_status' => ModerationStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(TrailSegment::class, 'trail_segment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Ucapan terima kasih dari pendaki lain yang laporan ini menolongnya.
     */
    public function thanks(): HasMany
    {
        return $this->hasMany(ReportThank::class);
    }

    public function moderationActions(): MorphMany
    {
        return $this->morphMany(ModerationAction::class, 'moderatable');
    }

    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->where('moderation_status', ModerationStatus::APPROVED->value);
    }

    /**
     * @return array<int, ConditionTag>
     */
    public function tags(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => ConditionTag::tryFrom($value),
            $this->condition_tags ?? []
        )));
    }

    /**
     * PRD §51: freshness is expressed relative to the hike date, not just created_at.
     */
    public function daysSinceHike(): int
    {
        return (int) $this->hike_date->diffInDays(now());
    }

    /**
     * PRD §84: pengguna yang menghapus akunnya kehilangan kaitan ke laporannya, tetapi
     * laporan yang sudah disetujui tetap berguna bagi pendaki lain. Label netral ini
     * dipakai di seluruh tampilan, bukan $report->user->name yang akan menjadi null.
     */
    public function authorLabel(): string
    {
        return $this->user?->name ?? 'Pendaki terdahulu';
    }

    /**
     * Alasan penolakan, untuk ditunjukkan kepada pelapornya (PRD §57).
     *
     * Moderator mengetik alasan pada setiap tindakan, tetapi sebelumnya alasan itu
     * tersimpan lalu tidak pernah dibaca siapa pun. Pelapor hanya melihat "Ditolak" dan
     * tidak dapat memperbaiki apa pun, sementara usaha moderator terbuang.
     *
     * Hanya untuk penolakan dan penghapusan. Alasan pada persetujuan adalah catatan
     * internal moderator, bukan kabar untuk pelapor.
     */
    public function rejectionReason(): ?string
    {
        if (! in_array($this->moderation_status, [ModerationStatus::REJECTED, ModerationStatus::REMOVED], true)) {
            return null;
        }

        return $this->moderationActions
            ->sortByDesc('created_at')
            ->firstWhere(fn (ModerationAction $action) => filled($action->reason))
            ?->reason;
    }

    protected static function booted(): void
    {
        // Berkas foto tidak ikut terhapus oleh penghapusan baris, sehingga tanpa ini
        // gambar milik laporan yang sudah hilang tetap tertinggal di penyimpanan.
        static::deleting(function (self $report) {
            if (blank($report->photo_path)) {
                return;
            }

            Storage::disk(config('filesystems.report_photos_disk'))->delete($report->photo_path);
        });
    }
}
