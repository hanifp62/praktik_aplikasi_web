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
 */
class PendakianAktif
{
    /**
     * @return array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}|null
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
            ->select(['trip_plans.id', 'trails.name as jalur', 'checkpoints.name as pos_berikutnya', 'hiking_sessions.started_at as mulai'])
            ->first();

        if ($baris === null) {
            return null;
        }

        return [
            'id' => (int) $baris->id,
            'jalur' => (string) $baris->jalur,
            'posBerikutnya' => $baris->pos_berikutnya === null ? null : (string) $baris->pos_berikutnya,
            'mulai' => $baris->mulai === null ? null : CarbonImmutable::parse((string) $baris->mulai, 'UTC')->toIso8601String(),
            'url' => route('trips.hike', ['trip' => $baris->id], false),
        ];
    }
}
