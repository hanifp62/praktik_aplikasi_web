<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TripReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu pengingat perjalanan (FR-24): jenisnya ("H-3", "H-1"), instan UTC jadwalnya, dan
 * nasibnya. Terkirim dan dilewati sama-sama final; yang belum keduanya menunggu.
 *
 * @property Carbon $dijadwalkan_untuk
 * @property Carbon|null $sent_at
 * @property Carbon|null $skipped_at
 */
#[Fillable(['trip_plan_id', 'jenis', 'dijadwalkan_untuk', 'sent_at', 'skipped_at'])]
class TripReminder extends Model
{
    /** @use HasFactory<TripReminderFactory> */
    use HasFactory;

    public const MENUNGGU = 'menunggu';

    public const TERKIRIM = 'terkirim';

    public const DILEWATI = 'dilewati';

    protected function casts(): array
    {
        return [
            'dijadwalkan_untuk' => 'datetime',
            'sent_at' => 'datetime',
            'skipped_at' => 'datetime',
        ];
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function keadaan(): string
    {
        return match (true) {
            $this->sent_at !== null => self::TERKIRIM,
            $this->skipped_at !== null => self::DILEWATI,
            default => self::MENUNGGU,
        };
    }

    /**
     * Belum terkirim dan belum dilewati.
     *
     * @param  Builder<TripReminder>  $query
     * @return Builder<TripReminder>
     */
    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereNull('skipped_at');
    }

    /**
     * Pengingat yang masih akan datang: menunggu dan jadwalnya belum tiba. Satu predikat untuk
     * Tenggat terdekat Beranda dan lembar Pengingat (ruling W5-FE, M2 tinjauan akhir W5), supaya
     * baris yang jamnya sudah lewat tetapi belum sempat diproses penjadwal tidak disebut sebagai
     * tenggat di satu tempat dan hilang di tempat lain.
     *
     * @param  Builder<TripReminder>  $query
     * @return Builder<TripReminder>
     */
    public function scopeAkanDatang(Builder $query, ?CarbonInterface $sekarang = null): Builder
    {
        return $query->menunggu()->where('dijadwalkan_untuk', '>', $sekarang ?? Carbon::now());
    }
}
