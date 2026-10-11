<?php

namespace App\Models;

use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Services\OfficialStatusService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date', 'end_date',
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
    'status', 'completed_at',
])]
class TripPlan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'end_date' => 'date',
            'participants_count' => 'integer',
            'trip_type' => TripType::class,
            'reminders_enabled' => 'boolean',
            'status' => TripStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function hikingGoal(): BelongsTo
    {
        return $this->belongsTo(HikingGoal::class);
    }

    /**
     * Tujuan yang dipakai menilai kecocokan "untuk rencana ini" (spec v3 bagian 6.4 dan 6.5):
     * tujuan tersimpan trip ini, atau untuk trip lama yang dibuat tanpa tujuan, jenis dan
     * tanggal tripnya sebagai tujuan sementara yang tidak pernah disimpan.
     */
    public function tujuanPenilaian(): HikingGoal
    {
        return $this->hikingGoal ?? new HikingGoal([
            'trip_type' => $this->trip_type,
            'target_date' => $this->planned_date,
        ]);
    }

    /**
     * Satu baris rencana untuk kalimat "untuk rencana ini": jenis trip, tanggal (rentang
     * bila lebih dari sehari), dan jumlah peserta bila diisi.
     *
     * Tanggal mulai menulis bulannya bila berbeda dari tanggal selesai, dan tahunnya bila
     * tahunnya berbeda (m3 tinjauan akhir W3): "30 sampai 2 Okt 2026" terbaca 30 Oktober.
     */
    public function ringkasan(): string
    {
        $mulai = $this->planned_date;
        $selesai = $this->end_date;
        $formatMulai = match (true) {
            $selesai === null => 'j M Y',
            $mulai->year !== $selesai->year => 'j M Y',
            $mulai->month !== $selesai->month => 'j M',
            default => 'j',
        };
        $tanggal = $selesai !== null && ! $selesai->isSameDay($mulai)
            ? $mulai->translatedFormat($formatMulai).' sampai '.$selesai->translatedFormat('j M Y')
            : $mulai->translatedFormat('j M Y');

        return implode(', ', array_filter([
            $this->trip_type->label(),
            $tanggal,
            $this->participants_count ? $this->participants_count.' orang' : null,
        ]));
    }

    public function preparationItems(): HasMany
    {
        return $this->hasMany(TripPreparationItem::class);
    }

    public function readinessChecks(): HasMany
    {
        return $this->hasMany(ReadinessCheck::class);
    }

    public function latestReadinessCheck(): HasOne
    {
        return $this->hasOne(ReadinessCheck::class)->latestOfMany();
    }

    /**
     * Jalur trip ini ditarik dari katalog setelah tripnya dibuat.
     *
     * Dibedakan dari readinessIsStale() karena saran yang benar berbeda. Vonis basi
     * diperbaiki dengan menilai ulang; penarikan tidak. Menilai ulang jalur yang sudah
     * tidak dikelola di sini hanya mengulang sesuatu yang hasilnya tidak akan berubah,
     * dan menyembunyikan bahwa aplikasi memang berhenti menjadi sumber untuk jalur itu.
     *
     * Pengarsipan tidak menulis baris status resmi, jadi pembanding status pada
     * readinessIsStale() memang tidak pernah melihatnya.
     */
    public function trailIsWithdrawn(): bool
    {
        return $this->trail?->archived_at !== null;
    }

    /**
     * Apakah vonis kesiapan tersimpan sudah didahului keadaan.
     *
     * Halaman kesiapan menghitung ulang setiap kali dibuka, jadi ia selalu benar.
     * Ringkasan di dasbor, daftar trip, dan halaman trip membaca baris tersimpan, dan
     * baris itu mencatat status resmi yang dipakainya. Ketika status jalur sekarang
     * berbeda dari yang tercatat, vonisnya dinilai atas keadaan yang sudah berlalu.
     *
     * Aplikasi ini sudah menolak pola yang sama di pintu lain: service worker tidak
     * pernah menyajikan halaman dari cache karena status "BUKA" yang basi lebih
     * berbahaya daripada halaman yang gagal terbuka (§94, §95).
     *
     * Status sekarang boleh dioper dari luar supaya daftar dapat memuatnya sekali untuk
     * seluruh baris, alih-alih satu query per trip.
     */
    public function readinessIsStale(?OfficialStatusValue $statusSekarang = null): bool
    {
        $dinilaiDengan = $this->latestReadinessCheck?->official_status_snapshot['status'] ?? null;

        if ($dinilaiDengan === null) {
            return false;
        }

        $statusSekarang ??= app(OfficialStatusService::class)->effectiveStatusForTrail($this->trail);

        return $dinilaiDengan !== $statusSekarang->value;
    }

    public function hikingSession(): HasOne
    {
        return $this->hasOne(HikingSession::class);
    }

    /**
     * Pengingat H-3 dan H-1 (FR-24), dijadwalkan App\Services\Trips\TripReminderScheduler.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TripReminder::class);
    }

    public function history(): HasOne
    {
        return $this->hasOne(HikingHistory::class);
    }

    public function preparationCompletionPercent(): int
    {
        $items = $this->preparationItems;
        $applicable = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        if ($applicable->isEmpty()) {
            return 0;
        }

        $confirmed = $applicable->where('status', PreparationStatus::CONFIRMED)->count();

        return (int) round($confirmed / $applicable->count() * 100);
    }

    public function hasUnconfirmedCriticalItems(): bool
    {
        return $this->preparationItems
            ->where('is_critical', true)
            ->contains(fn (TripPreparationItem $item) => $item->status === PreparationStatus::NOT_CONFIRMED);
    }
}
