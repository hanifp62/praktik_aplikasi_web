<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Apakah satu sesi pendakian boleh menerima titik jejak sekarang (ruling W6-P2, W6-P2b, W6-P2c).
 * Satu predikat untuk pertanyaan pengirim (GET hike/{session}/track) dan unggahan (POST), supaya
 * janji persetujuan Profil ("Tidak ada yang dikirim saat Anda masih di jalur", "dikirim setelah
 * Anda turun") ditegakkan server untuk klien mana pun: bundel lama, tab basi, dan perangkat lain.
 *
 * Sesi menerima jejak hanya bila:
 * - perekaman jejak pengguna menyala;
 * - trip sesi itu COMPLETED atau CANCELLED. Daftar putih, bukan "bukan IN_PROGRESS" (butir 8):
 *   sesi yang tidak konsisten, misalnya trip belum dimulai tetapi bersesi, tidak menerima titik.
 *   Status trip yang dibaca, bukan status sesi, karena batal (TripLifecycle::cancel, ruling
 *   W6-FT) mengisi `ended_at` sesi tetapi TIDAK mengubah status sesi: sesi tetap ACTIVE atau
 *   PAUSED, tidak pernah menjadi COMPLETED, walau tripnya sudah CANCELLED;
 * - pengguna tidak punya trip lain yang sedang berlangsung, supaya tidak ada titik terkirim
 *   selama ia di jalur, termasuk titik pendakian lama.
 *
 * Semua nilai dibaca dari baris saat dipanggil, bukan dari model atau relasi yang sudah dimuat
 * (butir 4). Berakhir itu monoton (COMPLETED dan CANCELLED final, TripLifecycle::refusal), jadi
 * pembacaan yang basi hanya dapat menolak palsu, tidak pernah menerima palsu.
 */
final class PenerimaanJejak
{
    public const REKAM_MATI = 'rekam-mati';

    public const BELUM_BERAKHIR = 'belum-berakhir';

    public const PENDAKIAN_LAIN = 'pendakian-lain';

    /** Batas akurasi titik yang diterima; pengirim menyaring dengan nilai yang sama (W6-P2e). */
    public const AKURASI_MAKS_M = 10000;

    /** Rentang ketinggian yang diterima; di luar itu pengirim mengirim null (R-007, W6-P2e). */
    public const TINGGI_MIN_M = -500;

    public const TINGGI_MAKS_M = 9000;

    /**
     * Kelonggaran jam perangkat untuk titik sesudah pendakian diselesaikan (ruling W6-FH). `ended_at`
     * sesi ditulis dengan jam server saat Selesai diterima, sedangkan `recorded_at` titik adalah jam
     * perangkat yang merekamnya; jam ponsel dapat meleset beberapa menit dari server. Titik dalam
     * rentang ini masih dianggap bagian pendakian; titik sesudahnya direkam shell luring yang belum
     * tahu pendakian berakhir.
     */
    public const KELONGGARAN_JAM_PERANGKAT_MENIT = 10;

    /**
     * Null bila sesi boleh menerima jejak; selain itu alasannya: REKAM_MATI, BELUM_BERAKHIR, atau
     * PENDAKIAN_LAIN, diperiksa dalam urutan itu.
     */
    public function alasanTolak(HikingSession $sesi, User $pengguna): ?string
    {
        $merekam = UserPreference::query()->where('user_id', $pengguna->id)->value('record_track');

        if (! (bool) $merekam) {
            return self::REKAM_MATI;
        }

        $status = TripPlan::query()->whereKey($sesi->trip_plan_id)->value('status');
        $status = $status instanceof TripStatus ? $status : TripStatus::tryFrom((string) $status);

        if (! in_array($status, [TripStatus::COMPLETED, TripStatus::CANCELLED], true)) {
            return self::BELUM_BERAKHIR;
        }

        $lainBerlangsung = TripPlan::query()
            ->where('user_id', $pengguna->id)
            ->where('status', TripStatus::IN_PROGRESS->value)
            ->exists();

        return $lainBerlangsung ? self::PENDAKIAN_LAIN : null;
    }

    /**
     * Titik satu kiriman dengan kunci detik UTC (ruling W6-P2c): `recorded_at` diparse, `->utc()`
     * (butir 6), lalu dipotong ke detik, sama dengan kolom `timestamp` yang menyimpan tanpa
     * pecahan. Ganda pada detik yang sama dibuang dengan yang terakhir di kiriman menang, lalu urut
     * naik menurut waktu. Tanpa ini, satu `upsert` PostgreSQL yang memuat dua baris berkunci sama
     * ditolak seluruhnya ("ON CONFLICT DO UPDATE command cannot affect row a second time"), dan
     * perangkat mengulang kiriman yang sama selamanya; SQLite memprosesnya baris demi baris dan
     * lulus (R-024).
     *
     * @param  array<int, array{recorded_at: string, latitude: float|int|string, longitude: float|int|string, elevation_m?: int|string|null, accuracy_m?: int|string|null}>  $titik
     * @return list<array{recorded_at: CarbonImmutable, latitude: float|int|string, longitude: float|int|string, elevation_m: int|string|null, accuracy_m: int|string|null}>
     */
    public static function titikPerDetik(array $titik): array
    {
        $perDetik = [];

        foreach ($titik as $satu) {
            $waktu = CarbonImmutable::parse($satu['recorded_at'])->utc()->startOfSecond();
            $kunci = $waktu->format('Y-m-d H:i:s');

            // Kunci yang sudah ada dibuang dulu, supaya titik yang terakhir menang dan tidak
            // mewarisi letak titik pertama; urutannya ditetapkan ksort di bawah.
            unset($perDetik[$kunci]);
            $perDetik[$kunci] = [
                'recorded_at' => $waktu,
                'latitude' => $satu['latitude'],
                'longitude' => $satu['longitude'],
                'elevation_m' => $satu['elevation_m'] ?? null,
                'accuracy_m' => $satu['accuracy_m'] ?? null,
            ];
        }

        ksort($perDetik, SORT_STRING);

        return array_values($perDetik);
    }

    /**
     * Titik yang termasuk pendakian sesi ini (ruling W6-FH): tanpa titik yang direkam lebih dari
     * KELONGGARAN_JAM_PERANGKAT_MENIT sesudah `ended_at`. Shell luring memakai rekaman sesi di
     * perangkat yang masih berkata pendakian berlangsung sesudah Selesai atau Batal, jadi
     * perekamnya dapat terus berjalan; titik itu bukan bagian pendakian mana pun dan tidak
     * disimpan. Sejak TripLifecycle::cancel() menutup sesi seperti complete() (ruling W6-FT, pola
     * W6-FB), saringan ini berlaku juga untuk pendakian yang dibatalkan. Sesi tanpa `ended_at`
     * tidak disaring (R-007): dengan W6-FT, setiap sesi yang mencapai trip COMPLETED atau
     * CANCELLED sudah ditutup, jadi cabang ini defensif untuk data dari sebelum ruling ini atau
     * baris yang diubah langsung di luar TripLifecycle.
     *
     * @param  list<array{recorded_at: CarbonImmutable, latitude: float|int|string, longitude: float|int|string, elevation_m: int|string|null, accuracy_m: int|string|null}>  $titik  keluaran titikPerDetik()
     * @return list<array{recorded_at: CarbonImmutable, latitude: float|int|string, longitude: float|int|string, elevation_m: int|string|null, accuracy_m: int|string|null}>
     */
    public static function dalamPendakian(array $titik, ?CarbonInterface $berakhir): array
    {
        if ($berakhir === null) {
            return $titik;
        }

        $batas = CarbonImmutable::instance($berakhir)->utc()->addMinutes(self::KELONGGARAN_JAM_PERANGKAT_MENIT);

        return array_values(array_filter($titik, fn (array $satu): bool => $satu['recorded_at']->lessThanOrEqualTo($batas)));
    }
}
