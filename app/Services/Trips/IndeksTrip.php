<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Http\Resources\TripRowResource;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use App\Support\PaginasiInertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * Kelas halaman indeks trip (spec v3 bagian 6.9, rencana induk W4 butir 1): kartu utama trip
 * terdekat dengan fase dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan Selesai.
 * Kelompok Selesai memuat beberapa yang terbaru; sisanya dibaca lewat "Lihat semua" di halaman
 * yang sama (?selesai=semua, berhalaman), supaya trip batal lama tetap dapat dibuka dan dihapus
 * pemiliknya (ruling W4-Fpre b). Riwayat pendakian lengkap tetap di Progres.
 *
 * Kueri tetap, tidak tumbuh bersama jumlah trip: satu untuk trip yang belum selesai (dibatasi
 * BATAS_BERJALAN), satu untuk beberapa trip selesai terbaru, satu hitungan, relasi dimuat
 * sekali untuk semua kartu, lalu status resmi dan kecocokan dasar dimuat dalam batch. Hanya
 * trip milik pembacanya (PRD §77).
 */
final class IndeksTrip
{
    /** Trip selesai atau batal yang tampil di indeks; sisanya lewat "Lihat semua". */
    public const BATAS_SELESAI = 5;

    /** Satu halaman "Lihat semua" trip selesai dan batal (ruling W4-Fpre b). */
    public const PER_HALAMAN_SELESAI = 20;

    /**
     * Batas pengaman trip yang belum selesai dalam satu muat (tinjauan Tugas 2 M6): rencana
     * yang tanggalnya lewat tidak pernah keluar sendiri dari Mendatang (ruling W4-Fpre a), jadi
     * tanpa batas satu akun dapat membesarkan muatan indeksnya sendiri tanpa ujung. Bila
     * terpotong, halaman menyebutnya, tidak diam-diam.
     */
    public const BATAS_BERJALAN = 100;

    /** @var array<int, TripStatus> */
    private const SELESAI = [TripStatus::COMPLETED, TripStatus::CANCELLED];

    public function __construct(
        private readonly TripSteps $steps,
        private readonly OfficialStatusService $status,
        private readonly TrailFitService $trailFit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(User $user, Request $request): array
    {
        if ($request->query('selesai') === 'semua') {
            return $this->semuaSelesai($user, $request);
        }

        $selesai = self::nilaiSelesai();

        $berjalan = $user->tripPlans()->whereNotIn('status', $selesai)->orderBy('planned_date')->orderBy('id')->limit(self::BATAS_BERJALAN + 1)->get();
        $terpotong = $berjalan->count() > self::BATAS_BERJALAN;
        $berjalan = EloquentCollection::make($berjalan->take(self::BATAS_BERJALAN)->all());
        $terbaru = self::urutanSelesai($user->tripPlans()->whereIn('status', $selesai))->limit(self::BATAS_SELESAI)->get();
        // Satu kueri untuk dua hitungan: semua trip selesai atau batal (tautan "Lihat semua"),
        // dan yang benar-benar selesai (tautan Progres, yang hanya memuat pendakian selesai).
        $hitungan = $user->tripPlans()->whereIn('status', $selesai)
            ->selectRaw('count(*) as semua')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as tuntas', [TripStatus::COMPLETED->value])
            ->toBase()
            ->first();
        $jumlahSelesai = (int) ($hitungan->semua ?? 0);

        $semua = self::muatRelasi(EloquentCollection::make($berjalan->concat($terbaru)->all()));
        $statusJalur = $this->status->effectiveStatusesForTrails(self::jalurUnik($semua));

        // Kecocokan dasar hanya untuk trip yang belum selesai, dan hanya bagi pendaki berprofil
        // lengkap: menilai tanpa profil menghasilkan label yang terlihat pasti tanpa dasar (§91).
        $jalurBerjalan = self::jalurUnik($berjalan);
        $fit = $user->hasCompletedProfile() ? $this->trailFit->forTrails($user, $jalurBerjalan, null, $statusJalur) : [];

        $kartu = fn (TripPlan $trip): array => (new TripRowResource(
            $trip,
            $this->steps->for($trip, $statusJalur[$trip->trail_id] ?? null),
            $trip->status->isActive() ? ($fit[$trip->trail_id] ?? null) : null,
        ))->resolve($request);

        $sedang = $berjalan->where('status', TripStatus::IN_PROGRESS)->values();
        $mendatang = $berjalan->where('status', '!=', TripStatus::IN_PROGRESS)->values();
        // Predikat yang sama dengan hitung mundur kartu (tinjauan Tugas 2 M3): hari ini di zona
        // gunungnya, bukan lantai WIB. Rencana yang tanggalnya lewat tetap paling atas di
        // Mendatang, tetapi tidak menjadi kartu utama.
        $utama = $sedang->first() ?? $mendatang->first(fn (TripPlan $trip) => ! $trip->tanggalRencanaLewat());

        $kelompok = [];

        foreach ([
            'berlangsung' => ['Berlangsung', $sedang],
            'mendatang' => ['Mendatang', $mendatang],
            'selesai' => ['Selesai', $terbaru],
        ] as $kunci => [$judul, $trip]) {
            $sisa = $trip->reject(fn (TripPlan $t) => $t->is($utama))->values();

            if ($sisa->isNotEmpty()) {
                $kelompok[] = ['kunci' => $kunci, 'judul' => $judul, 'trip' => $sisa->map($kartu)->all()];
            }
        }

        return [
            'utama' => $utama === null ? null : $kartu($utama),
            // Label wilayah kartu utama menurut kasusnya (tinjauan Tugas 2 M9).
            'utamaLabel' => match (true) {
                $utama === null => null,
                $utama->status === TripStatus::IN_PROGRESS => 'Trip berlangsung',
                default => 'Trip berikutnya',
            },
            'kelompok' => $kelompok,
            'selesai' => $jumlahSelesai === 0 ? null : [
                'jumlah' => $jumlahSelesai,
                'semua' => $jumlahSelesai > self::BATAS_SELESAI ? route('trips.index', ['selesai' => 'semua']) : null,
                'progres' => (int) ($hitungan->tuntas ?? 0) > 0 ? route('progress') : null,
            ],
            'terpotong' => $terpotong
                ? 'Hanya '.self::BATAS_BERJALAN.' trip yang belum selesai dengan tanggal paling awal yang tampil di sini.'
                : null,
            'arsip' => null,
        ];
    }

    /**
     * "Lihat semua": seluruh trip selesai dan batal milik pembaca, terbaru lebih dulu, 20 per
     * halaman. Kartu yang sama dengan indeks, jadi trip batal tetap dapat dibuka (dan dihapus
     * dari ruang kerjanya) walau sudah di luar lima terakhir.
     *
     * @return array<string, mixed>
     */
    private function semuaSelesai(User $user, Request $request): array
    {
        $halaman = self::urutanSelesai($user->tripPlans()->whereIn('status', self::nilaiSelesai()))
            ->paginate(self::PER_HALAMAN_SELESAI)
            ->withQueryString();

        $trip = self::muatRelasi(EloquentCollection::make($halaman->items()));
        $statusJalur = $this->status->effectiveStatusesForTrails(self::jalurUnik($trip));

        return [
            'utama' => null,
            'utamaLabel' => null,
            'kelompok' => [],
            'selesai' => null,
            'terpotong' => null,
            'arsip' => PaginasiInertia::props(
                $halaman,
                fn (TripPlan $t): array => (new TripRowResource($t, $this->steps->for($t, $statusJalur[$t->trail_id] ?? null)))->resolve($request),
            ) + ['kembali' => route('trips.index')],
        ];
    }

    /**
     * Selesai dan batal diurutkan menurut kapan trip itu berakhir, bukan tanggal rencananya
     * (tinjauan Tugas 2 M4): pembatalan hari ini untuk rencana Desember tidak boleh mendorong
     * pendakian yang selesai kemarin keluar dari lima terakhir. Pembatalan tidak mengisi
     * completed_at, jadi waktunya dibaca dari updated_at (trip batal tidak dapat diubah lagi).
     *
     * @param  HasMany<TripPlan, User>|Builder<TripPlan>  $kueri
     * @return HasMany<TripPlan, User>|Builder<TripPlan>
     */
    private static function urutanSelesai(HasMany|Builder $kueri): HasMany|Builder
    {
        return $kueri->orderByRaw('coalesce(completed_at, updated_at) desc')->orderByDesc('id');
    }

    /**
     * Relasi yang dibaca TripSteps dan TripRowResource, sekali untuk semua kartu. Butir persiapan
     * hanya kolom yang dibaca langkah (hitungan dan predikat menunggu BMKG), bukan label dan
     * keterangannya (tinjauan Tugas 2 M6).
     *
     * @param  EloquentCollection<int, TripPlan>  $trip
     * @return EloquentCollection<int, TripPlan>
     */
    private static function muatRelasi(EloquentCollection $trip): EloquentCollection
    {
        return $trip->load([
            'trail.mountain',
            'preparationItems:id,trip_plan_id,category,status',
            'latestReadinessCheck',
            'latestConfirmedReadinessCheck',
            'history',
        ]);
    }

    /**
     * Bukan collect(): OfficialStatusService dan TrailFitService men-type-hint
     * Eloquent\Collection secara ketat.
     *
     * @param  EloquentCollection<int, TripPlan>  $trip
     */
    private static function jalurUnik(EloquentCollection $trip): EloquentCollection
    {
        return EloquentCollection::make($trip->pluck('trail')->filter()->unique('id')->values()->all());
    }

    /**
     * @return array<int, string>
     */
    private static function nilaiSelesai(): array
    {
        return array_map(fn (TripStatus $s) => $s->value, self::SELESAI);
    }
}
