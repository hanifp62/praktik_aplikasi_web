<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class OfficialStatusService
{
    /**
     * Latest currently-effective official status record for any statusable entity.
     */
    public function currentFor(Model $statusable): ?OfficialStatus
    {
        return OfficialStatus::query()
            ->where('statusable_type', $statusable->getMorphClass())
            ->where('statusable_id', $statusable->getKey())
            ->currentlyEffective()
            ->terbaruDulu()
            ->first();
    }

    /**
     * BR-07: absence of information is reported as UNKNOWN, never assumed to be OPEN.
     */
    public function currentValueFor(Model $statusable): OfficialStatusValue
    {
        return $this->currentFor($statusable)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * PRD §42: a mountain being OPEN says nothing about its individual trails, so an OPEN
     * mountain never upgrades a trail. Explicit mountain-level restrictions do cascade down.
     */
    public function effectiveStatusForTrail(Trail $trail): OfficialStatusValue
    {
        $jalur = $this->currentFor($trail);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();
        $gunung = $mountain instanceof Mountain ? $this->currentFor($mountain) : null;

        return self::catatanPenentu($jalur, $gunung)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * Catatan yang menentukan status efektif sebuah jalur menurut kaskade §42 (R-009).
     *
     * Rincian jalur (`snapshotForTrail()`) dan daftar (`StatusJalurRingkas::untuk()`)
     * memakai fungsi yang sama, sehingga keduanya selalu mengutip sumber yang sama untuk
     * jalur yang sama: gunung CLOSED menentukan lewat catatan gunung, gunung RESTRICTED
     * (dan jalur belum CLOSED) juga lewat catatan gunung, selain itu catatan jalur sendiri
     * yang menentukan (boleh null bila tidak ada catatan yang sedang berlaku). Sebelum
     * fungsi ini ada, snapshotForTrail() mengutip catatan jalur untuk field deskriptif
     * (sumber, jenis sumber, cakupan, waktu) walau kaskade menentukan lewat gunung.
     */
    public static function catatanPenentu(?OfficialStatus $jalur, ?OfficialStatus $gunung): ?OfficialStatus
    {
        $nilaiGunung = $gunung?->status;
        $nilaiJalur = $jalur?->status ?? OfficialStatusValue::UNKNOWN;

        if ($nilaiGunung === OfficialStatusValue::CLOSED) {
            return $gunung;
        }

        if ($nilaiGunung === OfficialStatusValue::RESTRICTED && $nilaiJalur !== OfficialStatusValue::CLOSED) {
            return $gunung;
        }

        return $jalur;
    }

    /**
     * Status yang berlaku pada satu tanggal, bukan hari ini.
     *
     * Dipakai ketika yang dinilai adalah rencana, bukan keadaan sekarang. Penutupan
     * tahunan diumumkan jauh hari, jadi rencana untuk Januari harus dinilai terhadap
     * Januari.
     */
    public function statusOnDate(Model $statusable, CarbonInterface $date): OfficialStatusValue
    {
        return OfficialStatus::query()
            ->where('statusable_type', $statusable->getMorphClass())
            ->where('statusable_id', $statusable->getKey())
            ->effectiveOn($date)
            ->terbaruDulu()
            ->first()
            ?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * Status efektif sebuah jalur pada satu tanggal, lengkap dengan kaskade §42.
     *
     * Pembatasan turun dari gunung ke jalur; kelonggaran tidak. Aturannya sama dengan
     * versi "hari ini", hanya tanggalnya yang berbeda.
     */
    public function effectiveStatusForTrailOn(Trail $trail, CarbonInterface $date): OfficialStatusValue
    {
        $trailStatus = $this->statusOnDate($trail, $date);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();

        if (! $mountain instanceof Mountain) {
            return $trailStatus;
        }

        $mountainStatus = $this->statusOnDate($mountain, $date);

        if ($mountainStatus === OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::CLOSED;
        }

        if ($mountainStatus === OfficialStatusValue::RESTRICTED && $trailStatus !== OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::RESTRICTED;
        }

        return $trailStatus;
    }

    /**
     * Status efektif untuk banyak jalur sekaligus.
     *
     * Versi per-jalur membutuhkan dua query masing-masing, sehingga mesin rekomendasi
     * tumbuh linear terhadap jumlah kandidat. Di sini seluruh status jalur diambil
     * dalam satu query dan seluruh status gunung dalam satu query lagi, lalu aturan
     * kaskade §42 diterapkan di memori.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, OfficialStatusValue> berkunci id jalur
     */
    public function effectiveStatusesForTrails(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $trailStatuses = $this->latestStatusesFor((new Trail)->getMorphClass(), $trails->pluck('id'));
        $mountainIds = $trails->pluck('mountain_id')->filter()->unique();
        $mountainStatuses = $this->latestStatusesFor((new Mountain)->getMorphClass(), $mountainIds);

        $effective = [];

        foreach ($trails as $trail) {
            $trailStatus = $trailStatuses[$trail->id] ?? OfficialStatusValue::UNKNOWN;
            $mountainStatus = $mountainStatuses[$trail->mountain_id] ?? OfficialStatusValue::UNKNOWN;

            $effective[$trail->id] = $this->cascade($trailStatus, $mountainStatus);
        }

        return $effective;
    }

    /**
     * Pembatasan segmen untuk banyak jalur sekaligus, dalam dua query.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>>
     */
    public function segmentRestrictionsForTrails(Collection $trails): array
    {
        $restrictions = array_fill_keys($trails->pluck('id')->all(), []);

        if ($trails->isEmpty()) {
            return $restrictions;
        }

        $segments = TrailSegment::query()
            ->whereIn('trail_id', $trails->pluck('id'))
            ->get(['id', 'trail_id', 'name']);

        if ($segments->isEmpty()) {
            return $restrictions;
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        foreach ($segments as $segment) {
            $latest = $statuses->firstWhere('statusable_id', $segment->id);

            if ($latest === null || ! $this->isRestrictive($latest->status)) {
                continue;
            }

            $restrictions[$segment->trail_id][] = [
                'segment' => $segment->name,
                'status' => $latest->status,
                'reason' => $latest->reason,
            ];
        }

        return $restrictions;
    }

    /**
     * Status terbaru yang sedang berlaku untuk setiap entitas dari satu tipe.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     * @return array<int, OfficialStatusValue>
     */
    private function latestStatusesFor(string $morphClass, $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = OfficialStatus::query()
            ->where('statusable_type', $morphClass)
            ->whereIn('statusable_id', $ids)
            ->currentlyEffective()
            ->terbaruDulu()
            ->get(['statusable_id', 'status']);

        $latest = [];

        foreach ($rows as $row) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terbaru.
            $latest[$row->statusable_id] ??= $row->status;
        }

        return $latest;
    }

    /**
     * PRD §42: pembatasan turun dari gunung ke jalur; kelonggaran tidak pernah naik.
     */
    private function cascade(OfficialStatusValue $trailStatus, OfficialStatusValue $mountainStatus): OfficialStatusValue
    {
        if ($mountainStatus === OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::CLOSED;
        }

        if ($mountainStatus === OfficialStatusValue::RESTRICTED && $trailStatus !== OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::RESTRICTED;
        }

        return $trailStatus;
    }

    private function isRestrictive(OfficialStatusValue $status): bool
    {
        return in_array($status, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true);
    }

    /**
     * PRD §42: status dapat melekat pada SEGMENT, bukan hanya gunung dan jalur.
     *
     * Sebuah jalur boleh saja berstatus OPEN sementara salah satu segmennya ditutup:
     * kasus nyata Semeru 2026, ketika pendakian dibuka tetapi hanya sampai Ranu Kumbolo.
     * Pembatasan seperti ini tidak mengeksklusi jalur (PRD §26 memisahkan pembatasan dari
     * pengecualian), tetapi wajib terlihat oleh pendaki.
     *
     * Satu query untuk seluruh segmen, bukan per segmen.
     *
     * @return array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>
     */
    public function segmentRestrictionsForTrail(Trail $trail): array
    {
        $segments = $trail->segments()->get(['id', 'name']);

        if ($segments->isEmpty()) {
            return [];
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        $restrictions = [];

        foreach ($segments as $segment) {
            // Status paling akhir berlaku untuk tiap segmen; urutan sudah menurun.
            $latest = $statuses->firstWhere('statusable_id', $segment->id);

            if ($latest === null || ! in_array($latest->status, [
                OfficialStatusValue::CLOSED,
                OfficialStatusValue::RESTRICTED,
            ], true)) {
                continue;
            }

            $restrictions[] = [
                'segment' => $segment->name,
                'status' => $latest->status,
                'reason' => $latest->reason,
            ];
        }

        return $restrictions;
    }

    /**
     * PRD §97: hanya data publik yang boleh masuk cache bersama. Snapshot status resmi
     * adalah data publik yang sama untuk setiap pengguna, sehingga aman dibagikan.
     * Tidak ada apa pun yang terikat pengguna di dalamnya.
     */
    public function cachedSnapshotForTrail(Trail $trail): array
    {
        return Cache::remember(
            self::snapshotCacheKey($trail->id),
            (int) config('hiking.cache.public_ttl_seconds'),
            fn () => $this->snapshotForTrail($trail)
        );
    }

    /**
     * Dipanggil ketika admin mengubah status resmi, supaya perubahan yang menyangkut
     * pembatasan jalur tidak tertahan di cache sampai TTL-nya habis.
     */
    public static function forgetCachedSnapshot(int $trailId): void
    {
        Cache::forget(self::snapshotCacheKey($trailId));
    }

    private static function snapshotCacheKey(int $trailId): string
    {
        return "trail:{$trailId}:official-status-snapshot";
    }

    /**
     * @return array<string, mixed> snapshot used by readiness checks and condition aggregation
     */
    public function snapshotForTrail(Trail $trail): array
    {
        // Catatan jalur dan catatan gunung masing-masing diambil sekali (tanpa kueri
        // ganda); catatanPenentu() menentukan yang mana benar-benar berlaku (§42), dan
        // field deskriptif di bawah selalu mengutip catatan itu, bukan selalu catatan
        // jalur.
        $jalur = $this->currentFor($trail);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();
        $gunung = $mountain instanceof Mountain ? $this->currentFor($mountain) : null;
        $record = self::catatanPenentu($jalur, $gunung);
        $status = $record?->status ?? OfficialStatusValue::UNKNOWN;

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'scope' => $record?->scope?->value,
            'source' => $record?->source,
            // Jenis sumber dari data_sources (R-009): halaman menyebut "Resmi" hanya bila
            // catatannya memang bersumber OFFICIAL, bukan karena tersimpan di tabel ini.
            'source_type' => $record?->dataSource?->source_type?->value,
            'source_url' => $record?->source_url,
            'published_at' => $record?->published_at?->toIso8601String(),
            'verified_at' => $record?->verified_at?->toIso8601String(),
            'fetched_at' => $record?->fetched_at?->toIso8601String(),
            // Stempel untuk waktu yang dikutip beserta awalannya dan kesegaran di rincian
            // (I1 tinjauan akhir W3): kesegaran dihitung saat halaman dirender, bukan disimpan
            // di snapshot yang bercache.
            'effective_at' => $record?->effective_at?->toIso8601String(),
            'expires_at' => $record?->expires_at?->toIso8601String(),
            'reason' => $record?->reason,
            'notes' => $record?->notes,
        ];
    }
}
