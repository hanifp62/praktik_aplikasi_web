<?php

namespace App\Services;

use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kabar dari gunung yang diikuti pendaki.
 *
 * Isinya dua jenis yang tidak boleh tertukar: perubahan status resmi dari pengelola
 * kawasan, dan laporan kondisi dari sesama pendaki (§92). Keduanya digabung dalam satu
 * urutan waktu karena begitulah keduanya sampai ke pendaki di dunia nyata, tetapi
 * masing-masing membawa penanda jenisnya dan sumbernya.
 *
 * Yang tidak ada di sini disengaja: tidak ada aktivitas pendaki lain, tidak ada siapa
 * mendaki apa, tidak ada peringkat. Umpan Strava berisi orang; umpan ini berisi keadaan
 * gunung.
 */
class TrailNewsService
{
    public const STATUS = 'STATUS';

    public const LAPORAN = 'LAPORAN';

    /**
     * @return Collection<int, Mountain>
     */
    public function followedMountains(User $user): Collection
    {
        return Mountain::query()
            ->whereIn('id', MountainFollow::where('user_id', $user->id)->select('mountain_id'))
            ->orderBy('name')
            ->get();
    }

    public function follows(User $user, Mountain $mountain): bool
    {
        return MountainFollow::where('user_id', $user->id)
            ->where('mountain_id', $mountain->id)
            ->exists();
    }

    /**
     * Kabar terbaru, terurut waktu, dari gunung yang diikuti.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 30): Collection
    {
        $gunung = MountainFollow::where('user_id', $user->id)->pluck('mountain_id');

        if ($gunung->isEmpty()) {
            return collect();
        }

        $jalur = Trail::query()->whereIn('mountain_id', $gunung)->pluck('id');

        return $this->status($gunung, $jalur, $limit)
            ->concat($this->laporan($jalur, $limit))
            ->sortByDesc('waktu')
            ->take($limit)
            ->values();
    }

    /**
     * Perubahan status resmi pada gunung yang diikuti maupun jalur dan segmen di
     * bawahnya, karena penutupan diumumkan pada salah satu dari ketiganya.
     *
     * @param  Collection<int, int>  $gunung
     * @param  Collection<int, int>  $jalur
     * @return Collection<int, array<string, mixed>>
     */
    private function status(Collection $gunung, Collection $jalur, int $limit): Collection
    {
        $segmen = TrailSegment::query()->whereIn('trail_id', $jalur)->pluck('id');

        return OfficialStatus::query()
            ->with('statusable')
            ->where(fn ($q) => $q
                ->orWhere(fn ($w) => $w->where('statusable_type', (new Mountain)->getMorphClass())->whereIn('statusable_id', $gunung))
                ->orWhere(fn ($w) => $w->where('statusable_type', (new Trail)->getMorphClass())->whereIn('statusable_id', $jalur))
                ->orWhere(fn ($w) => $w->where('statusable_type', (new TrailSegment)->getMorphClass())->whereIn('statusable_id', $segmen))
            )
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (OfficialStatus $s) => [
                'jenis' => self::STATUS,
                'waktu' => $s->created_at,
                'status' => $s->status,
                'judul' => $s->statusable?->name ?? 'Kawasan',
                'alasan' => $s->reason,
                'sumber' => $s->source,
                'model' => $s,
            ]);
    }

    /**
     * Laporan kondisi yang sudah lolos moderasi.
     *
     * Yang tertunda dan ditolak tidak pernah masuk kabar. Kabar adalah pintu yang paling
     * ramai, dan laporan yang belum diperiksa bocor lewat sini akan menghapus seluruh
     * guna moderasinya.
     *
     * @param  Collection<int, int>  $jalur
     * @return Collection<int, array<string, mixed>>
     */
    private function laporan(Collection $jalur, int $limit): Collection
    {
        return TrailConditionReport::query()
            ->visibleToPublic()
            ->whereIn('trail_id', $jalur)
            ->with('trail:id,name,slug,mountain_id', 'trail.mountain:id,name', 'user:id,name')
            ->withCount('thanks')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (TrailConditionReport $r) => [
                'jenis' => self::LAPORAN,
                'waktu' => $r->created_at,
                'judul' => $r->trail?->name ?? 'Jalur',
                'model' => $r,
            ]);
    }
}
