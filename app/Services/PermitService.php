<?php

namespace App\Services;

use App\Models\PermitRequirement;
use App\Models\Trail;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aturan perizinan pendakian (PRD §36 Logistik).
 *
 * Sistem tidak memesan izin, tidak memverifikasi kuota, dan tidak menyimpan jatah
 * apa pun. Data internal tidak akan pernah sinkron dengan sistem resmi, dan
 * menampilkan sisa kuota yang keliru justru lebih berbahaya daripada tidak
 * menampilkannya sama sekali (PRD §43: kita bukan sumber otoritatif).
 *
 * Yang dikerjakan di sini hanya satu: memberi tahu pendaki bahwa rencananya
 * berbenturan dengan jendela pemesanan, dan menunjukkan ke mana harus mengurus.
 */
class PermitService
{
    /**
     * Aturan yang berlaku untuk sebuah jalur: aturan khusus jalur lebih dulu,
     * lalu jatuh ke aturan tingkat gunung.
     */
    public function requirementFor(Trail $trail): ?PermitRequirement
    {
        return PermitRequirement::query()
            ->where('trail_id', $trail->id)
            ->latest('verified_at')
            ->first()
            ?? PermitRequirement::query()
                ->whereNull('trail_id')
                ->where('mountain_id', $trail->mountain_id)
                ->latest('verified_at')
                ->first();
    }

    /**
     * Aturan perizinan untuk banyak jalur sekaligus, dalam satu query.
     *
     * Versi per-jalur membutuhkan dua query masing-masing; dipakai di dalam perulangan
     * kandidat, itu mengembalikan pertumbuhan linear yang baru saja dihapus dari mesin
     * rekomendasi.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, PermitRequirement|null> berkunci id jalur
     */
    public function requirementsForTrails(Collection $trails): array
    {
        $resolved = array_fill_keys($trails->pluck('id')->all(), null);

        if ($trails->isEmpty()) {
            return $resolved;
        }

        $rules = PermitRequirement::query()
            ->where(function ($query) use ($trails) {
                $query->whereIn('trail_id', $trails->pluck('id'))
                    ->orWhereIn('mountain_id', $trails->pluck('mountain_id')->filter()->unique());
            })
            ->orderByDesc('verified_at')
            ->get();

        foreach ($trails as $trail) {
            // Aturan khusus jalur lebih diutamakan daripada aturan tingkat gunung.
            $resolved[$trail->id] = $rules->firstWhere('trail_id', $trail->id)
                ?? $rules->first(fn (PermitRequirement $rule) => $rule->trail_id === null
                    && $rule->mountain_id === $trail->mountain_id);
        }

        return $resolved;
    }

    /**
     * Peringatan bila tanggal target jatuh di luar jendela pemesanan.
     *
     * Mengembalikan null ketika tidak ada aturan tercatat. Ketiadaan data bukan
     * alasan untuk mengarang peringatan — PRD §95 berlaku dua arah: tidak tahu
     * tidak sama dengan bermasalah.
     */
    public function bookingWarningFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?string {
        if ($targetDate === null) {
            return null;
        }

        // false berarti "belum dicari"; null berarti "sudah dicari, memang tidak ada".
        // Pembedaan ini yang memungkinkan hasil batch dioper tanpa query ulang.
        $requirement = $requirement === false ? $this->requirementFor($trail) : $requirement;

        if ($requirement === null) {
            return null;
        }

        $daysAhead = (int) now()->startOfDay()->diffInDays($targetDate->copy()->startOfDay(), false);

        if ($daysAhead < 0) {
            return null;
        }

        $closes = $requirement->booking_closes_days_before;
        $opens = $requirement->booking_opens_days_before;

        if ($closes !== null && $daysAhead < $closes) {
            return $this->message($requirement, sprintf(
                'Pemesanan izin untuk jalur ini ditutup H-%d, sedangkan tanggal rencana Anda tinggal %d hari lagi.',
                $closes,
                $daysAhead
            ));
        }

        if ($opens !== null && $daysAhead > $opens) {
            return $this->message($requirement, sprintf(
                'Pemesanan izin untuk jalur ini baru dibuka H-%d, sedangkan tanggal rencana Anda masih %d hari lagi.',
                $opens,
                $daysAhead
            ));
        }

        return null;
    }

    private function message(PermitRequirement $requirement, string $situation): string
    {
        $message = sprintf('%s Penyelenggara: %s.', $situation, $requirement->authority);

        if ($requirement->booking_url) {
            $message .= ' Pemesanan: '.$requirement->booking_url;
        }

        return $message;
    }
}
