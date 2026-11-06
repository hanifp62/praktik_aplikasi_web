<?php

namespace App\Services\Dashboard;

use App\Models\HikingHistory;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\User;

/**
 * Posisi pendaki dalam siklus trip untuk Beranda (spec v3 bagian 6.1, DK-45), dihitung sekali
 * per permintaan oleh RingkasanDasbor::konteks() dan dibaca setiap bagian Beranda: kartu fase,
 * tenggat, kabar, jalur untuk Anda, dan tangga tanjakan.
 *
 * Relasi yang sudah dimuat: trip (B2) membawa trail.mountain, preparationItems,
 * latestReadinessCheck, latestConfirmedReadinessCheck (disyaratkan TripSteps::for(),
 * ruling W5-2c / M1), dan reminders yang masih akan datang (`TripReminder::akanDatang()`);
 * riwayat (B4) membawa tripPlan.trail.mountain; tripLewat membawa trail.mountain.
 */
final readonly class KonteksBeranda
{
    /**
     * @param  string  $fase  salah satu konstanta RingkasanDasbor::B0 sampai B4
     * @param  array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}|null  $pendakian  pendakian berlangsung (B3), dari PendakianAktif
     * @param  TripPlan|null  $trip  trip mendatang terdekat yang tanggalnya belum lewat di zona gunungnya (B2)
     * @param  PermitRequirement|null  $aturanIzin  aturan izin jalur trip itu, dicari sekali (PermitService::requirementFor)
     * @param  HikingHistory|null  $riwayat  pendakian selesai yang belum dilaporkan (B4)
     * @param  TripPlan|null  $tripLewat  rencana terbaru yang tanggalnya lewat tanpa dimulai (B0, B1, B4)
     */
    public function __construct(
        public string $fase,
        public User $pengguna,
        public bool $profilLengkap,
        public ?array $pendakian = null,
        public ?TripPlan $trip = null,
        public ?PermitRequirement $aturanIzin = null,
        public ?HikingHistory $riwayat = null,
        public ?TripPlan $tripLewat = null,
    ) {}
}
