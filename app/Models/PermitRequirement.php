<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aturan perizinan pendakian yang berlaku untuk satu jalur atau satu gunung.
 *
 * Ini adalah catatan tentang aturan pihak lain, bukan sistem perizinan kita sendiri.
 * Sistem tidak pernah memesan, memverifikasi, atau melacak kuota. Ia hanya
 * memberi tahu pendaki apa yang perlu ia urus dan ke mana.
 */
#[Fillable([
    'trail_id', 'mountain_id', 'authority', 'booking_url', 'daily_quota',
    'booking_opens_days_before', 'booking_closes_days_before', 'guide_required',
    'max_duration_days', 'notes', 'data_source_id', 'source', 'source_url', 'verified_at',
])]
class PermitRequirement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'guide_required' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    /**
     * R-024: `latest('verified_at')` / `orderByDesc('verified_at')` menaruh baris NULL
     * pertama di PostgreSQL dan terakhir di SQLite -- urutan NULL bukan bagian dari standar
     * SQL dan berbeda antar mesin. Tanpa urutan eksplisit, aturan yang belum terverifikasi
     * bisa mengalahkan aturan yang sudah terverifikasi di Supabase (PostgreSQL) walau suite
     * SQLite tetap hijau, persis pola insiden R-024 (suite hijau, halaman salah).
     *
     * Aturan terverifikasi selalu didahulukan (verified_at NOT NULL sebelum NULL, ditulis
     * eksplisit lewat orderByRaw, bukan bergantung pada bawaan mesin), lalu di antara yang
     * terverifikasi, yang paling baru diverifikasi menang. id menjadi pemutus akhir supaya
     * urutannya deterministik ketika verified_at sama persis.
     */
    public function scopeTerverifikasiDulu(Builder $query): Builder
    {
        return $query
            ->orderByRaw('verified_at is null')
            ->orderByDesc('verified_at')
            ->orderByDesc('id');
    }

    /**
     * Ringkasan singkat untuk ditampilkan pada checklist persiapan.
     */
    public function summary(): string
    {
        $parts = [$this->authority];

        if ($this->daily_quota !== null) {
            $parts[] = sprintf('kuota %d pendaki/hari', $this->daily_quota);
        }

        if ($this->booking_closes_days_before !== null) {
            $parts[] = sprintf('pemesanan ditutup H-%d', $this->booking_closes_days_before);
        }

        if ($this->guide_required) {
            $parts[] = 'wajib pemandu terdaftar';
        }

        if ($this->max_duration_days !== null) {
            $parts[] = sprintf('maksimal %d hari', $this->max_duration_days);
        }

        return implode(' · ', $parts);
    }
}
