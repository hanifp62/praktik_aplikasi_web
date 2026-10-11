<?php

namespace App\Models;

use App\Enums\HikingSessionStatus;
use App\Models\Concerns\HasSpatialColumns;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'trip_plan_id', 'user_id', 'current_checkpoint_id', 'reached_checkpoint_sequence', 'status',
    'started_at', 'ended_at', 'paused_at', 'paused_seconds', 'location_updated_at',
])]
// BR-13: precise location stays private by default and is never serialised with the model.
#[Hidden(['last_known_location'])]
class HikingSession extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'status' => HikingSessionStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'last_resumed_at' => 'datetime',
            'location_updated_at' => 'datetime',
        ];
    }

    /**
     * Detik bergerak (ruling W6-R7): sejak mulai sampai selesai, atau sampai $sekarang selama
     * sesi berjalan, dikurangi jeda yang sudah selesai dan jeda yang sedang berjalan. Tidak
     * pernah negatif.
     */
    public function detikBergerak(?CarbonInterface $sekarang = null): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        $akhir = $this->ended_at ?? $sekarang ?? now();
        $jedaBerjalan = $this->paused_at === null ? 0 : max(0, (int) $this->paused_at->diffInSeconds($akhir, false));

        return max(0, (int) $this->started_at->diffInSeconds($akhir, false) - (int) $this->paused_seconds - $jedaBerjalan);
    }

    /**
     * Memulai jeda lewat UPDATE bersyarat (ruling W6-R6, W6-10f bagian a): hanya bila belum jeda,
     * dan hanya bila $waktu lebih baru dari lanjut terakhir. Jeda yang diputar ulang antrean
     * sesudah lanjutnya diterima (tab lain yang masih memegang pasangan yang sama) karena itu tidak
     * memulai jeda baru dengan waktu lama, sehingga lamanya tidak terhitung dua kali. Pemanggil
     * menjawab sama seperti aksi yang sudah diterapkan, tanpa galat.
     *
     * Batasnya: perbandingan memakai jam perangkat yang mengirim (`pada` dari antrean). Dua
     * perangkat yang jamnya meleset dapat membuat jeda sah dari perangkat yang jamnya mundur
     * diabaikan. Ketelitiannya detik, sama dengan kolomnya: jeda dalam detik yang sama dengan
     * lanjut terakhir juga diabaikan.
     *
     * Sesi yang sudah berakhir (ended_at terisi) tidak dijeda (ruling W6-FB): jeda yang tiba
     * sesudah TripLifecycle::complete() commit, dari tab atau antrean yang memuat trip sebelum
     * itu, tidak membalik status sesi COMPLETED menjadi PAUSED. Syaratnya di UPDATE yang sama,
     * jadi berlaku walau penjaga 409 controller membaca trip yang basi.
     *
     * @return bool true bila baris benar-benar berubah (jeda dimulai oleh pemanggilan ini)
     */
    public function mulaiJeda(CarbonInterface $waktu): bool
    {
        $waktu = CarbonImmutable::instance($waktu)->utc();

        return self::query()
            ->whereKey($this->id)
            ->whereNull('ended_at')
            ->whereNull('paused_at')
            ->where(fn ($q) => $q->whereNull('last_resumed_at')->orWhere('last_resumed_at', '<', $waktu))
            ->update([
                'paused_at' => $waktu,
                'status' => HikingSessionStatus::PAUSED->value,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Menutup jeda yang sedang berjalan lewat compare-and-swap (M-7/ruling W6-3m), diekstrak
     * dari controller supaya dapat dipanggil langsung dengan model basi di test: UPDATE hanya
     * berlaku bila paused_at di basis data masih sama dengan nilai pada model INI saat
     * dipanggil ($this->paused_at), bukan sekadar "masih terisi" (whereNotNull lama). Model
     * basi (dibaca sebelum tab lain mengubah baris) membuat UPDATE tidak mengenai baris apa
     * pun: jeda baru yang sudah berjalan di baris sungguhan tidak ikut ditutup, dan durasi
     * jeda lama tidak terhitung dua kali.
     *
     * Waktu lanjut ditulis ke last_resumed_at di UPDATE yang sama (ruling W6-10f bagian a), supaya
     * jeda yang diputar ulang sesudahnya dengan waktu lama diabaikan mulaiJeda().
     *
     * Lanjut yang waktunya tidak lebih baru dari lanjut terakhir tidak berbuat apa-apa (ruling
     * W6-10h, tinjauan Tugas 10 I-B), juga saat jeda yang LEBIH BARU sedang berjalan: tab basi yang
     * memutar ulang pasangan lama (jeda 01.00, lanjut 01.30) selagi jeda 02.00 berjalan tidak menutup
     * jeda 02.00, jadi lanjut sahnya kelak menghitung lama jeda itu. Syaratnya di UPDATE bersyarat yang
     * sama, dan pemanggil menjawab tanpa galat. Batasnya sama dengan mulaiJeda(): perbandingan memakai
     * jam perangkat yang mengirim, jadi lanjut sah dari perangkat yang jamnya mundur dapat diabaikan,
     * dan ketelitiannya detik, jadi lanjut dalam detik yang sama dengan lanjut terakhir juga diabaikan.
     *
     * Sesi yang sudah berakhir (ended_at terisi) tidak dilanjutkan (ruling W6-FB), di UPDATE yang
     * sama. Selama TripLifecycle::complete() mengosongkan paused_at saat menutup sesi, compare-and-
     * swap di atas sudah menolak lanjut basi; syarat ini menjaga kontrak metode ini sendiri untuk
     * baris berakhir mana pun, termasuk yang paused_at-nya masih terisi.
     *
     * @return bool true bila baris benar-benar berubah (jeda ditutup oleh pemanggilan ini)
     */
    public function lanjutkanJeda(CarbonInterface $waktu): bool
    {
        if ($this->paused_at === null) {
            return false;
        }

        $waktu = CarbonImmutable::instance($waktu)->utc();
        $detik = max(0, (int) $this->paused_at->diffInSeconds($waktu, false));

        return self::query()
            ->whereKey($this->id)
            ->whereNull('ended_at')
            ->where('paused_at', $this->paused_at)
            ->where(fn ($q) => $q->whereNull('last_resumed_at')->orWhere('last_resumed_at', '<', $waktu))
            ->update([
                'paused_at' => null,
                'paused_seconds' => DB::raw('paused_seconds + '.$detik),
                'last_resumed_at' => $waktu,
                'status' => HikingSessionStatus::ACTIVE->value,
                'updated_at' => now(),
            ]) > 0;
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentCheckpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class, 'current_checkpoint_id');
    }
}
