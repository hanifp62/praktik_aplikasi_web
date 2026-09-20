<?php

namespace App\Models;

use App\Enums\UsabilitySessionKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu sesi pengamatan terhadap orang yang memakai aplikasi ini.
 *
 * Peserta disimpan sebagai kode, bukan nama: analisis hanya perlu membedakan satu
 * peserta dari yang lain, dan menyimpan nama membuat catatan penelitian menjadi data
 * pribadi tanpa menambah satu pun kemampuan analisis.
 */
#[Fillable([
    'participant_code', 'kind', 'experience_level', 'facilitator_id', 'conducted_at',
    'task_results', 'sus_answers', 'sus_score', 'notes',
])]
class UsabilitySession extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => UsabilitySessionKind::class,
            'conducted_at' => 'datetime',
            'task_results' => 'array',
            'sus_answers' => 'array',
            'sus_score' => 'float',
        ];
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function scopeOfKind(Builder $query, UsabilitySessionKind $kind): Builder
    {
        return $query->where('kind', $kind->value);
    }

    /**
     * Sesi yang benar-benar menyumbang angka SUS.
     *
     * Sesi tanpa jawaban SUS tetap berharga sebagai pengamatan tugas, tetapi ikut
     * menghitungnya sebagai responden akan mengencerkan rata-rata dengan nol.
     */
    public function scopeWithSus(Builder $query): Builder
    {
        return $query->whereNotNull('sus_score');
    }
}
