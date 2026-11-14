<?php

namespace App\Models;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Exceptions\PublicationGateViolation;
use App\Services\OfficialStatusService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Catatan status resmi satu gunung, jalur, atau segmen (PRD §41, §42).
 *
 * Provenans meja kurator (W8, spec v3 bagian 10.7) yang menentukan siapa, atas nama siapa, dan
 * apakah catatan terlihat pendaki (`authority_id`, `verified_by`, `verification_state`,
 * `visible_before_verification`, `superseded_by_id`, arsip bukti, `next_review_at`) sengaja tidak
 * dapat diisi massal: layanan terbit menulisnya dari sesi dan Policy lewat forceFill (ruling
 * W8-R4).
 */
#[Fillable([
    'statusable_type', 'statusable_id', 'scope', 'status', 'data_source_id', 'source', 'source_url',
    'published_at', 'fetched_at', 'verified_at', 'effective_at', 'expires_at', 'reason', 'notes', 'recorded_by',
    'document_number', 'document_date', 'evidence_type', 'captured_at', 'end_condition', 'limit_description',
    'reason_category', 'legal_basis', 'official_excerpt', 'plain_summary',
])]
class OfficialStatus extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OfficialStatusValue::class,
            'scope' => StatusScope::class,
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'verified_at' => 'datetime',
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
            'document_date' => 'date',
            'evidence_type' => EvidenceType::class,
            'captured_at' => 'datetime',
            'end_condition' => StatusEndCondition::class,
            'reason_category' => StatusReasonCategory::class,
            'verification_state' => StatusVerificationState::class,
            'visible_before_verification' => 'boolean',
            'next_review_at' => 'datetime',
        ];
    }

    public function statusable(): MorphTo
    {
        return $this->morphTo();
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    /**
     * Catatan lama yang digantikan catatan ini (rantai penggantian, spec v3 bagian 10.7).
     */
    public function replaces(): HasOne
    {
        return $this->hasOne(self::class, 'superseded_by_id');
    }

    /**
     * Catatan yang boleh dibaca pendaki (ruling W8-R4): catatan lama tanpa keadaan verifikasi,
     * catatan terverifikasi orang kedua, dan catatan menunggu verifikasi atau dipersoalkan yang
     * oleh layanan terbit ditandai boleh terbit lebih dulu (pembatasan dengan bukti primer).
     * Draf dan pembukaan yang belum diverifikasi tidak pernah terlihat. Setiap pembaca status
     * untuk pendaki memakai scope ini; terlihatPendaki() adalah padanannya di PHP.
     */
    public function scopeTerlihatPendaki(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('verification_state')
            ->orWhere('verification_state', StatusVerificationState::VERIFIED->value)
            ->orWhere(fn (Builder $tertunda) => $tertunda
                ->whereIn('verification_state', [StatusVerificationState::PENDING->value, StatusVerificationState::DISPUTED->value])
                ->where('visible_before_verification', true)));
    }

    public function terlihatPendaki(): bool
    {
        return match ($this->verification_state) {
            null, StatusVerificationState::VERIFIED => true,
            StatusVerificationState::PENDING, StatusVerificationState::DISPUTED => (bool) $this->visible_before_verification,
            StatusVerificationState::DRAFT => false,
        };
    }

    /**
     * Terbit lebih dulu oleh satu kurator dan belum diverifikasi orang kedua: layar pendaki
     * menyebutnya, supaya pembatasan yang belum diperiksa dua orang tidak terbaca sama
     * pastinya dengan yang sudah.
     */
    public function menungguVerifikasiKedua(): bool
    {
        return $this->verification_state === StatusVerificationState::PENDING && (bool) $this->visible_before_verification;
    }

    public function scopeCurrentlyEffective(Builder $query): Builder
    {
        return $query->effectiveOn(Carbon::now());
    }

    /**
     * Status yang berlaku pada satu tanggal tertentu, bukan hanya hari ini.
     *
     * Penutupan besar di Indonesia bersifat tahunan dan diumumkan jauh hari: Rinjani
     * tutup 1 Januari sampai 31 Maret, Semeru pada periode yang sama ditambah sepanjang
     * Desember. Pendaki menyusun rencananya berbulan-bulan sebelumnya, jadi pertanyaan
     * yang menentukan adalah "tutup pada tanggal rencana saya", bukan "tutup hari ini".
     */
    public function scopeEffectiveOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('effective_at')->orWhere('effective_at', '<=', $date))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $date));
    }

    /**
     * Catatan terbaru lebih dulu, dengan urutan NULL yang sama di SQLite dan PostgreSQL
     * (I3 dan m6 tinjauan akhir W3, R-024; semantik diputus W8, ruling W8-R3).
     *
     * Waktu sebuah catatan adalah tanggal berlakunya, atau waktu dicatatnya bila tanggal
     * berlaku tidak disebut: `COALESCE(effective_at, created_at)`. Yang terbaru didahulukan,
     * seri diputus published_at (bertanggal dulu), lalu id. `ORDER BY ... DESC` menaruh NULL
     * terakhir di SQLite tetapi pertama di PostgreSQL, jadi urutan NULL ditulis eksplisit.
     * Sebelum W8 catatan bertanggal selalu didahulukan, sehingga catatan tanpa tanggal yang
     * lebih baru tidak pernah dapat menggantikannya; meja kurator kini mewajibkan tanggal
     * berlaku, jadi catatan tanpa tanggal hanya datang dari data lama.
     *
     * Satu-satunya tempat urutan ini ditulis: setiap pembaca "catatan terbaru per entitas"
     * (OfficialStatusService, StatusJalurRingkas) memakainya (OfficialStatusOrderTest).
     */
    public function scopeTerbaruDulu(Builder $query): Builder
    {
        return $query
            ->orderByRaw('coalesce(effective_at, created_at) is null')
            ->orderByRaw('coalesce(effective_at, created_at) desc')
            ->orderByRaw('published_at is null')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Catatan yang sudah mulai pada atau sebelum $waktu, dengan waktu mulai yang sama dengan urutan
     * scopeTerbaruDulu(): tanggal berlaku, atau waktu dicatat bila tidak disebut. Dipakai rantai
     * penggantian meja kurator (TerbitStatus).
     */
    public function scopeDimulaiPalingLambat(Builder $query, CarbonInterface $waktu): Builder
    {
        return $query->whereRaw('coalesce(effective_at, created_at) <= ?', [$waktu]);
    }

    /**
     * Stempel waktu yang dikutip layar untuk sebuah catatan, beserta kata awalannya (W3-2c dan
     * I1 tinjauan akhir W3).
     *
     * Verifikasi didahulukan, lalu terbit, lalu mulai berlaku, dan awalannya menyebut kolom
     * yang benar-benar dipakai: catatan yang belum pernah diverifikasi tidak boleh tertulis
     * "Diverifikasi" hanya karena ia punya tanggal terbit. Statis supaya dipakai bersama oleh
     * daftar (model) dan rincian (snapshot bercache berisi string ISO).
     *
     * @return array{waktu: CarbonInterface, awalan: string}|null
     */
    public static function waktuDikutip(?CarbonInterface $diverifikasi, ?CarbonInterface $diterbitkan, ?CarbonInterface $berlaku): ?array
    {
        return match (true) {
            $diverifikasi !== null => ['waktu' => $diverifikasi, 'awalan' => 'Diverifikasi'],
            $diterbitkan !== null => ['waktu' => $diterbitkan, 'awalan' => 'Diterbitkan'],
            $berlaku !== null => ['waktu' => $berlaku, 'awalan' => 'Berlaku sejak'],
            default => null,
        };
    }

    /**
     * Masih berlaku pada $waktu: belum berakhir (expires_at inklusif, sama dengan
     * scopeEffectiveOn). Pembaca status memilih catatan terakhir yang sudah dimulai lalu
     * memeriksa ini; catatan yang berakhir tidak digantikan catatan yang lebih lama (W8-R14).
     */
    public function berlakuPada(CarbonInterface $waktu): bool
    {
        return $this->expires_at === null || $this->expires_at->greaterThanOrEqualTo($waktu);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    protected static function booted(): void
    {
        // Dipasang pada model, bukan pada komponen admin, agar setiap jalur tulis
        // tercakup, penutupan jalur tidak boleh tertahan di cache sampai TTL habis.
        static::saved(fn (self $status) => $status->forgetAffectedTrailCaches());
        static::deleted(fn (self $status) => $status->forgetAffectedTrailCaches());

        // R-022 sebagai invariant keadaan: jalur terbit tidak boleh kehilangan catatan
        // status resmi terakhirnya. Penghapusan terjadi di model ini, bukan di Trail,
        // sehingga penjaga di sana buta terhadapnya.
        static::deleting(function (self $status): void {
            $trail = $status->statusable;

            if (! $trail instanceof Trail || ! $trail->is_published) {
                return;
            }

            // Draf dan catatan yang belum terlihat boleh dibuang; yang dijaga adalah catatan
            // terakhir yang dibaca pendaki (ruling W8-R5).
            if (! $status->terlihatPendaki() || $trail->statusTerlihat()->count() > 1) {
                return;
            }

            throw new PublicationGateViolation($trail, ['Status resmi belum pernah dicatat.']);
        });
    }

    /**
     * Membuang snapshot bercache setiap jalur yang terdampak catatan ini (kaskade §42): jalur
     * itu sendiri, semua jalur gunungnya, atau jalur pemilik segmennya. Publik supaya
     * perubahan sumber datanya (DataSource) dapat memakai aturan yang sama.
     */
    public function forgetAffectedTrailCaches(): void
    {
        $trailIds = match ($this->statusable_type) {
            (new Trail)->getMorphClass() => [$this->statusable_id],
            (new Mountain)->getMorphClass() => Trail::where('mountain_id', $this->statusable_id)->pluck('id')->all(),
            (new TrailSegment)->getMorphClass() => TrailSegment::where('id', $this->statusable_id)->pluck('trail_id')->all(),
            (new RestrictedArea)->getMorphClass() => $this->jalurTerdampakAreaTerbatas(),
            default => [],
        };

        foreach ($trailIds as $trailId) {
            OfficialStatusService::forgetCachedSnapshot((int) $trailId);
        }
    }

    /**
     * @return array<int, int>
     */
    private function jalurTerdampakAreaTerbatas(): array
    {
        $area = RestrictedArea::query()->find($this->statusable_id);

        if ($area === null) {
            return [];
        }

        if ($area->trail_id !== null) {
            return [(int) $area->trail_id];
        }

        return Trail::query()
            ->where('mountain_id', $area->mountain_id)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
