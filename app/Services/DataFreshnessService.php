<?php

namespace App\Services;

use App\Models\OfficialStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pemeriksaan data basi untuk status resmi (PRD §93, §98).
 *
 * Status yang kedaluwarsa diam-diam kembali menjadi UNKNOWN. Itu aman menurut §95,
 * tetapi senyap: jalur yang kemarin berstatus BUKA hari ini tidak punya status sama
 * sekali, dan tidak ada yang memberi tahu admin bahwa itu terjadi.
 */
class DataFreshnessService
{
    /**
     * Sudah lewat masa berlakunya. Jalurnya sekarang berjalan tanpa status resmi.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expired(): Collection
    {
        return $this->dasar()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Akan kedaluwarsa dalam waktu dekat, jadi masih sempat diperpanjang sebelum jalurnya
     * kehilangan status.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expiringSoon(): Collection
    {
        return $this->dasar()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
            ->get();
    }

    /**
     * Terlalu lama tidak diverifikasi ulang, atau belum pernah diverifikasi sama sekali.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function stale(): Collection
    {
        return $this->dasar()
            ->where(fn (Builder $q) => $q
                ->whereNull('verified_at')
                ->orWhere('verified_at', '<', now()->subDays($this->hariTinjau())))
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
                    ->orWhereNull('verified_at')
                    ->orWhere('verified_at', '<', now()->subDays($this->hariTinjau()));
            })
            ->count();
    }

    /**
     * @return array{kedaluwarsa: Collection<int, OfficialStatus>, segera: Collection<int, OfficialStatus>, basi: Collection<int, OfficialStatus>}
     */
    public function review(): array
    {
        $kedaluwarsa = $this->expired();

        return [
            'kedaluwarsa' => $kedaluwarsa,
            'segera' => $this->expiringSoon(),

            // Yang sudah kedaluwarsa tidak diulang di daftar basi; admin sudah melihatnya
            // di kategori yang lebih mendesak.
            'basi' => $this->stale()->reject(fn (OfficialStatus $s) => $kedaluwarsa->contains('id', $s->id)),
        ];
    }

    /**
     * Keadaan satu catatan status untuk pendaki (spec v3 bagian 7): "Terkini" bila
     * diverifikasi dalam masa tinjau dan tidak segera kedaluwarsa, "Perlu ditinjau" bila
     * belum pernah diverifikasi, terlalu lama tidak diverifikasi, atau segera kedaluwarsa,
     * dan "Kedaluwarsa" bila masa berlakunya sudah lewat. Ambangnya sama dengan antrean
     * tinjau admin di atas, jadi pendaki dan admin tidak pernah membaca dua penilaian.
     */
    public function keadaan(OfficialStatus $status): string
    {
        return $this->keadaanDari($status->verified_at, $status->expires_at);
    }

    /**
     * Keadaan kesegaran dari dua stempelnya saja, fungsi murni yang dipakai keadaan() dan
     * rincian jalur (I1 tinjauan akhir W3). Rincian membaca snapshot status yang bercache,
     * jadi kesegarannya dihitung dari stempel pada saat halaman dirender, bukan ikut dibekukan
     * di cache, dan kartu Jelajah serta rincian menyebut kata yang sama untuk catatan yang sama.
     */
    public function keadaanDari(?CarbonInterface $diverifikasi, ?CarbonInterface $kedaluwarsa): string
    {
        if ($kedaluwarsa !== null && $kedaluwarsa->isPast()) {
            return 'Kedaluwarsa';
        }

        $basi = $diverifikasi === null || $diverifikasi->lt(now()->subDays($this->hariTinjau()));
        $segera = $kedaluwarsa !== null && $kedaluwarsa->lte(now()->addDays($this->hariPeringatan()));

        return $basi || $segera ? 'Perlu ditinjau' : 'Terkini';
    }

    private function dasar(): Builder
    {
        return OfficialStatus::query()->with('statusable');
    }

    private function hariTinjau(): int
    {
        return (int) config('hiking.freshness.status_review_days', 90);
    }

    private function hariPeringatan(): int
    {
        return (int) config('hiking.freshness.status_expiry_warning_days', 14);
    }
}
