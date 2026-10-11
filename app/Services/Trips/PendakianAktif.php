<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pendakian yang sedang berlangsung milik seorang pendaki, untuk strip di atas bilah tab
 * (spec v3 bagian 5.1; rencana induk W2 butir 4).
 *
 * Satu kueri: trip IN_PROGRESS milik pengguna (indeks trip_plans.user_id dan
 * trip_plans.status), digabung ke jalurnya, sesi mode lapangannya, dan pos yang sedang dituju
 * sesi itu (hiking_sessions.current_checkpoint_id, ditulis HikeCheckpointResolver). Semua
 * gabungan lewat kunci utama atau kunci asing yang berindeks, jadi biayanya tetap berapa pun
 * jumlah trip, pos, dan sesi. Waktu mulai dikirim sebagai instan ISO 8601; waktu berjalan
 * dihitung peramban, sehingga tidak ada zona waktu yang ditebak di sini.
 *
 * Keadaan jeda sesi (paused_at dan paused_seconds) ikut dipilih di kueri yang sama (ruling W6-FD,
 * M-1 tinjauan akhir W6), supaya strip menghitung waktu bergerak seperti mode lapangan dan
 * angkanya berhenti selama dijeda. Tanpa sesi keduanya null, bukan nol (R-007).
 *
 * Satu-satunya pemilih pendakian aktif (titipan W2-4b, ruling W6-R2): tab Mendaki, strip, dan
 * Beranda memanggil kelas ini. Sejak W6 hanya satu trip per pendaki yang dapat berlangsung
 * (TripLifecycle::start); urutan tanggal rencana lalu id menjaga data lama yang masih memuat
 * dua pendakian berlangsung tetap terpilih sama di ketiga tempat.
 */
class PendakianAktif
{
    /**
     * @return array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, jedaSejak: string|null, jedaDetik: int|null, url: string}|null
     */
    public function untuk(User $user): ?array
    {
        $baris = DB::table('trip_plans')
            ->join('trails', 'trails.id', '=', 'trip_plans.trail_id')
            ->leftJoin('hiking_sessions', 'hiking_sessions.trip_plan_id', '=', 'trip_plans.id')
            ->leftJoin('checkpoints', 'checkpoints.id', '=', 'hiking_sessions.current_checkpoint_id')
            ->where('trip_plans.user_id', $user->id)
            ->where('trip_plans.status', TripStatus::IN_PROGRESS->value)
            ->orderBy('trip_plans.planned_date')
            ->orderBy('trip_plans.id')
            ->select(['trip_plans.id', 'trails.name as jalur', 'checkpoints.name as pos_berikutnya', 'hiking_sessions.started_at as mulai', 'hiking_sessions.paused_at as jeda_sejak', 'hiking_sessions.paused_seconds as jeda_detik'])
            ->first();

        if ($baris === null) {
            return null;
        }

        return [
            'id' => (int) $baris->id,
            'jalur' => (string) $baris->jalur,
            'posBerikutnya' => $baris->pos_berikutnya === null ? null : (string) $baris->pos_berikutnya,
            'mulai' => self::instan($baris->mulai),
            'jedaSejak' => self::instan($baris->jeda_sejak),
            'jedaDetik' => $baris->jeda_detik === null ? null : (int) $baris->jeda_detik,
            'url' => route('trips.hike', ['trip' => $baris->id], false),
        ];
    }

    /** Kolom timestamp sesi (disimpan UTC) sebagai instan ISO 8601; null tetap null. */
    private static function instan(mixed $nilai): ?string
    {
        return $nilai === null ? null : CarbonImmutable::parse((string) $nilai, 'UTC')->toIso8601String();
    }
}
