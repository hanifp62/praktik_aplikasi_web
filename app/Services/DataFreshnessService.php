<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Models\OfficialStatus;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Pemeriksaan data basi untuk status resmi (PRD §93, §98).
 *
 * Status yang kedaluwarsa diam-diam kembali menjadi UNKNOWN. Itu aman menurut §95,
 * tetapi senyap: jalur yang kemarin berstatus BUKA hari ini tidak punya status sama
 * sekali, dan tidak ada yang memberi tahu admin bahwa itu terjadi.
 *
 * Sejak W8 jadwal tinjaunya per jenis status (Q9; ruling W8-R13): Buka mengikuti ambang turun
 * status Buka paket luring, jenis lain `hiking.freshness.status_review_hours`. Catatan dari meja
 * kurator menyimpan jadwalnya di `next_review_at` (paling lambat tanggal berakhirnya); catatan
 * lama menghitungnya dari `verified_at`. Antrean admin, meja kurator, dan kata kesegaran untuk
 * pendaki membaca satu aturan yang sama.
 */
class DataFreshnessService
{
    /**
     * Sudah lewat masa berlakunya. Jalurnya sekarang berjalan tanpa status resmi.
     *
     * @param  (Closure(Builder): mixed)|null  $lingkup
     * @return Collection<int, OfficialStatus>
     */
    public function expired(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Akan kedaluwarsa dalam waktu dekat, jadi masih sempat diperpanjang sebelum jalurnya
     * kehilangan status.
     *
     * @param  (Closure(Builder): mixed)|null  $lingkup
     * @return Collection<int, OfficialStatus>
     */
    public function expiringSoon(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
            ->get();
    }

    /**
     * Sudah lewat jadwal tinjaunya, atau belum pernah diverifikasi sama sekali.
     *
     * @param  (Closure(Builder): mixed)|null  $lingkup
     * @return Collection<int, OfficialStatus>
     */
    public function stale(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->where(fn (Builder $q) => $this->lewatJadwal($q))
            ->get();
    }

    /**
     * Jumlah status yang perlu ditinjau, dalam satu query karena angka ini tampil di
     * setiap halaman admin.
     *
     * Kedaluwarsa, akan kedaluwarsa, dan basi dihitung sebagai kategori yang saling
     * lepas supaya satu status tidak terhitung dua kali.
     */
    public function reviewCount(): int
    {
        return $this->dasar()
            ->where(function (Builder $q) {
                $q->where('expires_at', '<', now())
                    ->orWhereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
                    ->orWhere(fn (Builder $basi) => $this->lewatJadwal($basi));
            })
            ->count();
    }

    /**
     * Antrean tinjau, untuk seluruh catatan atau, dengan $lingkup, hanya catatan di kawasan
     * seorang kurator (meja kurator W8).
     *
     * @param  (Closure(Builder): mixed)|null  $lingkup
     * @return array{kedaluwarsa: Collection<int, OfficialStatus>, segera: Collection<int, OfficialStatus>, basi: Collection<int, OfficialStatus>}
     */
    public function review(?Closure $lingkup = null): array
    {
        $kedaluwarsa = $this->expired($lingkup);

        return [
            'kedaluwarsa' => $kedaluwarsa,
            'segera' => $this->expiringSoon($lingkup),

            // Yang sudah kedaluwarsa tidak diulang di daftar basi; admin sudah melihatnya
            // di kategori yang lebih mendesak.
            'basi' => $this->stale($lingkup)->reject(fn (OfficialStatus $s) => $kedaluwarsa->contains('id', $s->id)),
        ];
    }

    /**
     * Keadaan satu catatan status untuk pendaki (spec v3 bagian 7): "Terkini" bila
     * diverifikasi dalam jadwal tinjaunya dan tidak segera kedaluwarsa, "Perlu ditinjau" bila
     * belum pernah diverifikasi, lewat jadwal tinjaunya, atau segera kedaluwarsa, dan
     * "Kedaluwarsa" bila masa berlakunya sudah lewat. Aturannya sama dengan antrean tinjau
     * admin di atas, jadi pendaki dan admin tidak pernah membaca dua penilaian.
     */
    public function keadaan(OfficialStatus $status): string
    {
        return $this->keadaanDari($status->verified_at, $status->expires_at, $status->status, $status->next_review_at);
    }

    /**
     * Keadaan kesegaran dari stempelnya saja, fungsi murni yang dipakai keadaan() dan rincian
     * jalur (I1 tinjauan akhir W3). Rincian membaca snapshot status yang bercache, jadi
     * kesegarannya dihitung dari stempel pada saat halaman dirender, bukan ikut dibekukan di
     * cache, dan kartu Jelajah serta rincian menyebut kata yang sama untuk catatan yang sama.
     */
    public function keadaanDari(?CarbonInterface $diverifikasi, ?CarbonInterface $kedaluwarsa, ?OfficialStatusValue $nilai = null, ?CarbonInterface $jadwal = null): string
    {
        if ($kedaluwarsa !== null && $kedaluwarsa->isPast()) {
            return 'Kedaluwarsa';
        }

        $batas = $jadwal ?? $diverifikasi?->copy()->addHours($this->jamTinjau($nilai ?? OfficialStatusValue::UNKNOWN));
        $basi = $batas === null || now()->greaterThan($batas);
        $segera = $kedaluwarsa !== null && $kedaluwarsa->lte(now()->addDays($this->hariPeringatan()));

        return $basi || $segera ? 'Perlu ditinjau' : 'Terkini';
    }

    /**
     * Jam sesudah verifikasi terakhir sampai sebuah jenis status perlu ditinjau ulang.
     */
    public function jamTinjau(OfficialStatusValue $nilai): int
    {
        if ($nilai === OfficialStatusValue::OPEN) {
            return (int) config('hiking.field_pack.open_status_stale_after_hours', 24);
        }

        return (int) (config('hiking.freshness.status_review_hours.'.$nilai->value) ?? 168);
    }

    /**
     * Jadwal tinjau berikutnya untuk catatan meja kurator: sesudah jam tinjau jenisnya, tetapi
     * tidak pernah sesudah tanggal berakhir yang disebut pengelola.
     */
    public function jadwalUntuk(OfficialStatusValue $nilai, CarbonInterface $diverifikasi, ?CarbonInterface $berakhir): Carbon
    {
        $jadwal = Carbon::instance($diverifikasi)->addHours($this->jamTinjau($nilai));

        return $berakhir !== null && $berakhir->lessThan($jadwal) ? Carbon::instance($berakhir) : $jadwal;
    }

    /**
     * Syarat "lewat jadwal tinjau" dalam SQL: jadwal tersimpan bila ada, selain itu verifikasi
     * terakhir ditambah jam tinjau jenisnya, atau belum pernah diverifikasi.
     */
    private function lewatJadwal(Builder $q): void
    {
        $kini = now();

        $q->where(fn (Builder $terjadwal) => $terjadwal->whereNotNull('next_review_at')->where('next_review_at', '<', $kini))
            ->orWhere(function (Builder $lama) use ($kini) {
                $lama->whereNull('next_review_at')->where(function (Builder $v) use ($kini) {
                    $v->whereNull('verified_at');

                    foreach (OfficialStatusValue::cases() as $nilai) {
                        $v->orWhere(fn (Builder $jenis) => $jenis
                            ->where('status', $nilai->value)
                            ->where('verified_at', '<', $kini->copy()->subHours($this->jamTinjau($nilai))));
                    }
                });
            });
    }

    /**
     * @param  (Closure(Builder): mixed)|null  $lingkup
     */
    private function dasar(?Closure $lingkup = null): Builder
    {
        $kini = now();

        // Antrean tinjau berkala hanya memuat catatan terbaru yang dibaca pendaki per subjek;
        // draf dan pembukaan yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8),
        // dan catatan lama yang sudah digantikan tidak diulang di antrean.
        return OfficialStatus::query()
            ->terlihatPendaki()
            ->whereNull('superseded_by_id')
            ->whereNotExists(function ($sub) use ($kini) {
                $sub->selectRaw('1')
                    ->from('official_statuses as lebih_baru')
                    ->whereColumn('lebih_baru.statusable_type', 'official_statuses.statusable_type')
                    ->whereColumn('lebih_baru.statusable_id', 'official_statuses.statusable_id')
                    ->where(function ($v) {
                        $v->whereNull('lebih_baru.verification_state')
                            ->orWhere('lebih_baru.verification_state', StatusVerificationState::VERIFIED->value)
                            ->orWhere(function ($m) {
                                $m->whereIn('lebih_baru.verification_state', [StatusVerificationState::PENDING->value, StatusVerificationState::DISPUTED->value])
                                    ->where('lebih_baru.visible_before_verification', true);
                            });
                    })
                    ->whereRaw('coalesce(lebih_baru.effective_at, lebih_baru.created_at) <= ?', [$kini])
                    ->where(function ($urut) {
                        $urut->whereRaw('coalesce(lebih_baru.effective_at, lebih_baru.created_at) > coalesce(official_statuses.effective_at, official_statuses.created_at)')
                            ->orWhere(function ($seri) {
                                $seri->whereRaw('coalesce(lebih_baru.effective_at, lebih_baru.created_at) = coalesce(official_statuses.effective_at, official_statuses.created_at)')
                                    ->whereColumn('lebih_baru.id', '>', 'official_statuses.id');
                            });
                    });
            })
            ->when($lingkup !== null, fn (Builder $q) => $q->where($lingkup))
            ->with('statusable');
    }

    private function hariPeringatan(): int
    {
        return (int) config('hiking.freshness.status_expiry_warning_days', 14);
    }
}
