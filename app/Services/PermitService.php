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
     * kandidat, cara itu mengembalikan pertumbuhan linear yang baru saja dihapus dari mesin
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
     * alasan untuk mengarang peringatan, PRD §95 berlaku dua arah: tidak tahu
     * tidak sama dengan bermasalah.
     */
    public function bookingWarningFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?string {
        $jendela = $this->bookingWindowFor($trail, $targetDate, $requirement);

        // Jendela yang sedang terbuka bukan peringatan. Ia tetap penting, tetapi
        // tempatnya di halaman trip sebagai ajakan bertindak, bukan di daftar
        // peringatan yang dibaca sebagai daftar hambatan.
        return $jendela !== null && $jendela['state'] !== self::BOOKING_TERBUKA
            ? $jendela['message']
            : null;
    }

    public const BOOKING_BELUM_DIBUKA = 'BELUM_DIBUKA';

    public const BOOKING_TERBUKA = 'TERBUKA';

    public const BOOKING_DITUTUP = 'DITUTUP';

    /**
     * Posisi tanggal rencana terhadap jendela pemesanan izin.
     *
     * Aritmetikanya hanya ada di sini. Jendela ini bergerak relatif terhadap tanggal
     * yang sudah dipilih, jadi jawabannya berubah seiring hari berjalan meskipun tidak
     * ada satu pun data yang disunting: itulah sebabnya ia perlu dihitung ulang setiap
     * halaman trip dibuka, bukan disimpan.
     *
     * @return array{state: string, message: string, requirement: PermitRequirement}|null
     */
    public function bookingWindowFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?array {
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
            return [
                'state' => self::BOOKING_DITUTUP,
                'requirement' => $requirement,
                'message' => $this->message($requirement, sprintf(
                    'Pemesanan izin untuk jalur ini sudah ditutup H-%d, sedangkan tanggal rencana Anda tinggal %d hari lagi.',
                    $closes,
                    $daysAhead
                )),
            ];
        }

        if ($opens !== null && $daysAhead > $opens) {
            return [
                'state' => self::BOOKING_BELUM_DIBUKA,
                'requirement' => $requirement,
                'message' => $this->message($requirement, sprintf(
                    'Pemesanan izin untuk jalur ini belum dibuka, baru dibuka H-%d, sedangkan tanggal rencana Anda masih %d hari lagi.',
                    $opens,
                    $daysAhead
                )),
            ];
        }

        return [
            'state' => self::BOOKING_TERBUKA,
            'requirement' => $requirement,
            'message' => $this->message($requirement, $closes !== null
                ? sprintf(
                    'Pemesanan izin untuk jalur ini sedang dibuka dan ditutup H-%d, yaitu %d hari lagi.',
                    $closes,
                    max(0, $daysAhead - $closes)
                )
                : 'Pemesanan izin untuk jalur ini sedang dibuka.'),
        ];
    }

    /**
     * Pasangan bookingWarningFor untuk keadaan sebaliknya: rencana tanpa tanggal.
     *
     * Keduanya saling meniadakan, jadi jalur berizin selalu mendapat tepat satu
     * kalimat tentang pemesanan. Tanpa ini, tidak adanya peringatan pada rencana tak
     * bertanggal terbaca sebagai "izin aman", padahal artinya tidak ada yang bisa
     * dibandingkan dengan jendela pemesanan (PRD §95).
     */
    public function uncheckedWindowNoticeFor(
        Trail $trail,
        ?CarbonInterface $targetDate,
        PermitRequirement|false|null $requirement = false,
    ): ?string {
        if ($targetDate !== null) {
            return null;
        }

        $requirement = $requirement === false ? $this->requirementFor($trail) : $requirement;

        if ($requirement === null) {
            return null;
        }

        if ($requirement->booking_opens_days_before === null && $requirement->booking_closes_days_before === null) {
            return null;
        }

        return $this->message(
            $requirement,
            'Jalur ini mewajibkan izin, dan jendela pemesanannya belum diperiksa karena rencana Anda belum bertanggal.'
        );
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
