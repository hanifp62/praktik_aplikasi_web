<?php

namespace App\Livewire;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor yang mengikuti posisi pengguna dalam alur.
 *
 * Sebelumnya halaman ini statis: setelah profil lengkap, ia selamanya mengajak membuat
 * rencana baru. Pendaki yang tripnya berangkat tiga hari lagi tetap harus menempuh tiga
 * ketukan untuk sampai ke cek kesiapan, padahal itu satu-satunya hal yang menentukan
 * hari itu.
 */
#[Layout('layouts.app')]
#[Title('Dasbor')]
class Dashboard extends Component
{
    /**
     * Trip yang sedang berjalan mengalahkan apa pun: pendaki yang membuka aplikasi di
     * jalur butuh mode pendakian, bukan ajakan merencanakan trip lain.
     */
    private function tripBerjalan(): ?TripPlan
    {
        return $this->dasar()
            ->where('status', TripStatus::IN_PROGRESS->value)
            ->orderBy('planned_date')
            ->first();
    }

    /**
     * Trip terdekat yang belum lewat. Yang sudah selesai atau dibatalkan tidak ikut,
     * supaya dasbor tidak tersangkut pada perjalanan yang sudah berakhir.
     */
    private function tripTerdekat(): ?TripPlan
    {
        return $this->dasar()
            ->whereIn('status', [
                TripStatus::DRAFT->value,
                TripStatus::PLANNED->value,
                TripStatus::READY_FOR_DEPARTURE->value,
            ])
            ->whereDate('planned_date', '>=', now()->toDateString())
            ->orderBy('planned_date')
            ->first();
    }

    private function dasar()
    {
        return TripPlan::query()
            ->where('user_id', auth()->id())
            ->with('trail.mountain', 'latestReadinessCheck');
    }

    public function render()
    {
        $berjalan = $this->tripBerjalan();
        $trip = $berjalan ?? $this->tripTerdekat();

        return view('livewire.dashboard', [
            'trip' => $trip,
            'sedangBerjalan' => $berjalan !== null,
            'hitungMundur' => $trip ? $this->hitungMundur($trip) : null,
        ]);
    }

    /**
     * Jarak hari ke keberangkatan, terpisah antara angka dan katanya.
     *
     * Dulu ia satu kalimat utuh dan dipasang sebagai judul kartu, sehingga fakta paling
     * mendesak di halaman ini berukuran sama dengan setiap judul lain. Dipisah supaya
     * angkanya dapat dibuat sebesar perannya, sementara katanya tetap ada: angka mentah
     * memaksa pembacanya menghitung sendiri.
     *
     * Hari ini, besok, dan tanggal yang sudah lewat tidak punya angka yang pantas
     * dibesarkan. Ketiganya mengembalikan angka null dan kalimatnya utuh, karena
     * "0 hari lagi" bukan cara orang bicara.
     *
     * @return array{angka: ?int, kata: string}
     */
    private function hitungMundur(TripPlan $trip): array
    {
        $hari = (int) now()->startOfDay()->diffInDays($trip->planned_date->startOfDay(), false);

        return match (true) {
            $hari < 0 => ['angka' => null, 'kata' => 'Tanggalnya sudah lewat'],
            $hari === 0 => ['angka' => null, 'kata' => 'Berangkat hari ini'],
            $hari === 1 => ['angka' => null, 'kata' => 'Berangkat besok'],
            default => ['angka' => $hari, 'kata' => 'hari lagi'],
        };
    }
}
