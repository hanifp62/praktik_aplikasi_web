<?php

namespace App\Services;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\User;

/**
 * Menempatkan sebuah jalur terhadap yang sudah didaki pembacanya.
 *
 * Mekanik Strava adalah perbandingan yang dipersempit sampai menang terasa mungkin, dan
 * modifikasi terpentingnya di sini: acuannya diri sendiri, bukan pendaki lain.
 *
 * Kalimatnya menerangkan, tidak pernah menghalangi. Yang berbunyi seperti izin mengubah
 * §90 dari penjelasan menjadi penjaga gerbang, dan produk ini membantu keputusan, bukan
 * memberi restu.
 */
class ProgressLadderService
{
    /**
     * Selisih elevation gain yang masih terbaca sebagai "setara", dalam meter.
     *
     * Bukan diukur dari mdpl puncak (BR-02, §18: mdpl bukan penentu kesulitan), melainkan
     * dari elevation gain, penentu beban yang sesungguhnya. Ambangnya diturunkan dari pita
     * kesulitan yang sudah dipakai CompatibilityScorer (600, 1000, 1600 m): jarak antar
     * pita itu 400 m dan 600 m, dan yang terkecil adalah 400 m. Setengah dari 400 m adalah
     * 200 m -- selisih di bawah itu belum cukup besar untuk melompati satu pita kesulitan,
     * jadi belum pantas disebut tingkat baru.
     */
    private const SETARA_METER = 200;

    public function bandingkanDenganRiwayat(User $user, Trail $trail): ?string
    {
        // Yang menentukan beban sebuah pendakian adalah elevation gain-nya, bukan mdpl
        // puncak yang dituju (BR-02, §18). Gunung 3000 mdpl yang didaki dari 2500 mdpl
        // lebih ringan daripada gunung 2000 mdpl yang didaki dari permukaan laut, jadi
        // acuannya diambil dari trails.elevation_gain_m, satu join dari riwayat.
        //
        // Hanya pendakian yang SELESAI yang menjadi acuan. Pendakian yang dibatalkan di
        // tengah jalan tidak membuktikan tanjakannya tertuntaskan, dan memakainya sebagai
        // acuan akan memberi tahu pendaki bahwa ia sudah pernah menuntaskan tanjakan yang
        // justru membuatnya berbalik.
        $tanjakanTertinggi = HikingHistory::query()
            ->where('user_id', $user->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->max('trails.elevation_gain_m');

        $tanjakanJalur = $trail->elevation_gain_m;

        if ($tanjakanTertinggi === null || $tanjakanJalur === null) {
            return null;
        }

        $selisih = $tanjakanJalur - $tanjakanTertinggi;

        return match (true) {
            $selisih > self::SETARA_METER * 3 => 'Jauh di atas tanjakan terberat yang pernah Anda tuntaskan.',
            $selisih > self::SETARA_METER => 'Satu tingkat di atas tanjakan terberat yang pernah Anda tuntaskan.',
            $selisih < -self::SETARA_METER => 'Di bawah tanjakan terberat yang pernah Anda tuntaskan.',
            default => 'Tanjakan yang setara dengan yang terberat sudah pernah Anda tuntaskan.',
        };
    }
}
