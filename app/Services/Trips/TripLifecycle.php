<?php

namespace App\Services\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\PreparationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Siklus hidup trip: dibuat, diubah, dimulai, diselesaikan, dibatalkan, dihapus.
 *
 * Dipindahkan dari komponen Livewire TripForm dan TripShow tanpa mengubah aturannya.
 * Otorisasi tetap di controller (Policy), bukan di sini. Setiap perubahan yang
 * menyentuh tanggal atau status ikut menyelaraskan pengingat perjalanan (FR-24).
 */
class TripLifecycle
{
    public function __construct(
        private readonly PreparationService $preparation,
        private readonly AnalyticsRecorder $analytics,
        private readonly TripReminderScheduler $reminders,
    ) {}

    /**
     * Butir persiapan dibuat saat itu juga dari jalur yang dipilih, supaya rencana tidak
     * pernah berupa daftar generik (PRD §36).
     *
     * @param  array<string, mixed>  $data  isian yang sudah divalidasi StoreTripRequest
     */
    public function create(User $user, array $data): TripPlan
    {
        $goalId = $data['hiking_goal_id'] ?? null;

        // Goal milik orang lain tidak ditolak, melainkan dilepas dari trip.
        if ($goalId !== null && ! $user->hikingGoals()->whereKey($goalId)->exists()) {
            $goalId = null;
        }

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $data['trail_id'],
            'hiking_goal_id' => $goalId,
            ...self::detailRencana($data),
            'reminders_enabled' => (bool) ($data['reminders_enabled'] ?? false),
            'status' => TripStatus::PLANNED->value,
        ]);

        $this->preparation->generateFor($trip);
        $this->reminders->sinkronkan($trip);

        $this->analytics->record(AnalyticsEvent::TRIP_CREATED, $user, ['trip_plan_id' => $trip->id]);
        $this->analytics->record(AnalyticsEvent::PREPARATION_STARTED, $user, ['trip_plan_id' => $trip->id]);

        return $trip;
    }

    /**
     * Mengubah isi rencana. Jalur, pemilik, dan status tidak ikut: ketiganya tidak ada
     * di isian yang divalidasi UpdateTripRequest dan tidak disentuh di sini.
     *
     * Tanggal naik yang pindah mengembalikan butir cuaca yang sudah dikonfirmasi menjadi belum
     * dikonfirmasi (R-013, W4-4d): prakiraan yang diperiksa untuk tanggal lama tidak berkata
     * apa pun tentang tanggal baru. Keduanya dalam satu transaksi, supaya tanggal baru tidak
     * pernah tersimpan bersama konfirmasi cuaca untuk tanggal lama.
     *
     * Dengan preseden yang sama, tanggal atau jam mulai yang pindah mengosongkan jam kembali
     * pesan rencana (ruling W6-7b, pra-tinjau Tugas 7 PT7-7c): jam kembali diisi untuk mulai
     * yang lama, dan eskalasi yang dihitung darinya dapat jatuh sebelum pendakian dimulai (trip
     * dimundurkan) atau berhari-hari sesudah perkiraan turun (trip dimajukan). Nomor registrasi
     * tidak ikut dikosongkan. Jam mulai dibandingkan sebagai jam dan menit, karena kolom time
     * PostgreSQL membaca '07:00:00' sementara formulir mengirim '07:00' (R-024): jam yang sama
     * bukan perpindahan.
     *
     * Pengosongan memakai UPDATE bersyarat pada baris sesudah save() (ruling W6-7g, I-1 tinjauan
     * Tugas 7, pola W6-5g), bukan nilai model yang dimuat di awal permintaan: jam kembali yang
     * commit dari tab lain sesudah pengikatan rute ikut dikosongkan, dan tanpa nilai yang
     * benar-benar dikosongkan tidak ada kabar W6-7b. Akibatnya kolom expected_return_at kini
     * disentuh setiap ubah rencana yang memindah mulai, walau sakelar alat keselamatan mati:
     * migrasi 2026_10_07_000500 wajib diterapkan sebelum kode ini terbit (K-2).
     *
     * @param  array<string, mixed>  $data  isian yang sudah divalidasi UpdateTripRequest
     * @return bool true bila jam kembali dikosongkan karena mulai trip berpindah
     */
    public function update(TripPlan $trip, array $data): bool
    {
        $jamKembaliDikosongkan = DB::transaction(function () use ($trip, $data): bool {
            $mulaiLama = self::mulai($trip);
            $trip->fill(self::detailRencana($data));
            $tanggalPindah = $trip->isDirty('planned_date');
            $mulaiPindah = self::mulai($trip) !== $mulaiLama;
            $trip->save();

            $kosongkanKembali = $mulaiPindah && TripPlan::query()->whereKey($trip->id)->whereNotNull('expected_return_at')->update(['expected_return_at' => null]) > 0;

            if ($kosongkanKembali) {
                $trip->expected_return_at = null;
                $trip->syncOriginalAttribute('expected_return_at');
            }

            if ($tanggalPindah) {
                $this->preparation->batalkanKonfirmasiCuaca($trip);
            }

            return $kosongkanKembali;
        });

        $this->reminders->sinkronkan($trip);

        return $jamKembaliDikosongkan;
    }

    /**
     * Mulai trip sebagai tanggal dan jam menit, untuk membandingkan sebelum dan sesudah ubah
     * rencana tanpa terkecoh bentuk kolom time ('07:00' lawan '07:00:00').
     */
    private static function mulai(TripPlan $trip): string
    {
        return $trip->planned_date->toDateString().' '.($trip->start_time === null ? '' : substr((string) $trip->start_time, 0, 5));
    }

    /**
     * Rencana hanya dapat diubah sebelum pendakian dimulai. Sesudahnya, tanggal dan
     * peserta adalah catatan tentang pendakian yang sudah terjadi atau dibatalkan.
     *
     * @return string|null kalimat penolakan, atau null bila rencana masih dapat diubah
     */
    public function editRefusal(TripPlan $trip): ?string
    {
        if ($trip->status->belumDimulai()) {
            return null;
        }

        return sprintf(
            'Rencana tidak dapat diubah lagi: trip berstatus %s. Rencana hanya dapat diubah sebelum pendakian dimulai.',
            $trip->status->label()
        );
    }

    /**
     * Trip yang belum dimulai atau yang dibatalkan boleh dihapus. Trip yang sedang
     * berlangsung atau sudah selesai tidak: riwayat pendakian, progres, dan tautan
     * laporan kondisi bergantung padanya, dan cascade akan ikut menghapus semuanya.
     *
     * @return string|null kalimat penolakan, atau null bila trip boleh dihapus
     */
    public function deleteRefusal(TripPlan $trip): ?string
    {
        if ($trip->status === TripStatus::IN_PROGRESS) {
            return 'Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau batalkan trip lebih dulu.';
        }

        $adaRiwayat = $trip->relationLoaded('history') ? $trip->history !== null : $trip->history()->exists();

        if ($trip->status === TripStatus::COMPLETED || $adaRiwayat) {
            return 'Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.';
        }

        return null;
    }

    /**
     * Butir persiapan, penilaian kesiapan, sesi, dan pengingat ikut terhapus lewat
     * foreign key cascade.
     *
     * DELETE bersyarat pada baris (ruling W6-FA, pola W6-5g): deleteRefusal() di controller
     * membaca model hasil pengikatan rute, yang dapat basi bila tab lain memulai atau
     * menyelesaikan trip ini sesudah model itu dimuat. Syarat di DELETE ini sama dengan
     * deleteRefusal(): bukan IN_PROGRESS, bukan COMPLETED, dan tanpa riwayat. Bila tidak ada
     * baris yang terhapus, kalimatnya deleteRefusal() atas baris saat ini, jadi pendakian yang
     * baru dimulai tab lain tidak ikut terhapus beserta sesinya lewat kaskade. Trip yang sudah
     * tidak ada (terhapus tab lain) tidak ditolak: hasil yang diminta sudah tercapai, sama dengan
     * perilaku sebelum ruling ini. Model tanpa event hapus (TripPlan tidak punya observer atau
     * SoftDeletes), jadi DELETE kueri setara dengan $trip->delete().
     *
     * @return string|null kalimat penolakan, atau null bila trip terhapus atau sudah tidak ada
     */
    public function delete(TripPlan $trip): ?string
    {
        $terhapus = TripPlan::query()
            ->whereKey($trip->id)
            ->whereNotIn('status', [TripStatus::IN_PROGRESS->value, TripStatus::COMPLETED->value])
            ->whereDoesntHave('history')
            ->delete();

        if ($terhapus > 0) {
            return null;
        }

        $sekarang = $trip->fresh();

        return $sekarang === null ? null : ($this->deleteRefusal($sekarang) ?? self::penolakanStatus($trip));
    }

    /**
     * PRD §35: COMPLETED dan CANCELLED bersifat final. Tanpa penjaga ini, trip yang
     * sudah dibatalkan masih dapat dimulai dan diselesaikan, dan trip yang sudah
     * selesai masih dapat dibatalkan surut.
     *
     * @return string|null kalimat penolakan, atau null bila transisinya sah
     */
    public function refusal(TripPlan $trip, TripStatus $target): ?string
    {
        if ($trip->status->canTransitionTo($target)) {
            return null;
        }

        return sprintf('Tindakan tidak dapat dilakukan: trip berstatus %s.', $trip->status->label());
    }

    /**
     * Memulai pendakian, satu pendakian berlangsung per pendaki (permintaan izin akhir butir
     * 8, ruling W6-R2). Pemeriksaan dan perubahan status berjalan di satu transaksi yang
     * lebih dulu mengunci baris pengguna, sehingga dua ketukan "Mulai" pada dua trip berbeda
     * tidak lolos bersamaan di PostgreSQL; SQLite menulis berurutan, jadi kuncinya tanpa efek
     * di sana. Sesi kedua untuk trip yang sama ditolak indeks unik hiking_sessions.trip_plan_id.
     *
     * Jaring pengaman reentrancy W6-1a/W6-1d (putaran tinjauan 1 dan 2, risiko R1, residu K1
     * task-1-fix1-review.md bagian C). Ini BUKAN bukti keselamatan konkurensi: pada
     * konkurensi PostgreSQL yang sungguhan, baik kunci baris di atas utuh maupun
     * diregresi/dilewati, UniqueConstraintViolationException tidak pernah benar-benar
     * sampai ke blok try/catch ini, karena firstOrCreate() milik Eloquent sendiri sudah
     * menyerap tabrakan itu lebih dulu: SELECT yang melihat baris yang baru commit bila
     * kunci utuh, atau fallback where()->first() yang menemukan baris koneksi lain yang
     * sudah commit bila kunci dilewati. Bukti keselamatan konkurensi tetap kunci baris di
     * atas (R1 asli) dan test dua koneksi sungguhan (R2, titipan W9), bukan blok ini.
     *
     * Blok ini menutup satu jalur sempit yang murni reentrancy dalam transaksi/koneksi yang
     * SAMA: bila baris pesaing sempat ada lalu ikut terhapus savepoint internal Eloquent
     * sendiri (withSavepointIfNeeded), firstOrCreate() dapat melempar ulang
     * UniqueConstraintViolationException ke kode kita walau tidak ada sesi tersisa sama
     * sekali. Satu percobaan ulang menutup kolisi tunggal itu. Bila percobaan ulang itu
     * SENDIRI juga berkolisi (kolisi ganda, secara praktik hampir mustahil), start()
     * mengembalikan kalimat penolakan, bukan membiarkan 500 tak tertangani naik lewat
     * DB::transaction(). Pengecualian lain tidak ditangkap di sini.
     *
     * Status ditulis lewat UPDATE bersyarat pada baris SEBELUM sesi dibuat (ruling W6-FA, I-1
     * tinjauan akhir W6, pola W6-5g complete() dan cancel()): refusal() di controller membaca
     * model hasil pengikatan rute, dan kunci baris pengguna di atas tidak menahan cancel() yang
     * tidak mengambilnya. Syaratnya status asal yang boleh bertransisi ke IN_PROGRESS menurut
     * TripStatus::allowedTransitions(). Bila tidak ada baris yang berubah, misalnya trip sudah
     * dibatalkan tab lain, kalimatnya penolakanStatus() dan tidak ada sesi yang dibuat; trip
     * yang dibatalkan tidak hidup lagi (PRD §35). Karena status kini ditulis lebih dulu, kolisi
     * ganda melempar ke luar transaksi supaya statusnya ikut dibatalkan, lalu baru menjadi
     * kalimat penolakan di sini.
     *
     * @return string|null kalimat penolakan, atau null bila pendakian dimulai
     */
    public function start(TripPlan $trip): ?string
    {
        $kolisiGanda = false;

        try {
            return DB::transaction(function () use ($trip, &$kolisiGanda): ?string {
                User::query()->whereKey($trip->user_id)->lockForUpdate()->first();

                $lain = TripPlan::query()
                    ->where('user_id', $trip->user_id)
                    ->where('status', TripStatus::IN_PROGRESS->value)
                    ->whereKeyNot($trip->id)
                    ->orderBy('planned_date')
                    ->orderBy('id')
                    ->first(['id', 'name']);

                if ($lain !== null) {
                    return sprintf('Pendakian "%s" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.', $lain->name);
                }

                $dimulai = TripPlan::query()
                    ->whereKey($trip->id)
                    ->whereIn('status', self::sumberMulai())
                    ->update(['status' => TripStatus::IN_PROGRESS->value]);

                if ($dimulai === 0) {
                    return self::penolakanStatus($trip);
                }

                $buatSesi = fn () => $trip->hikingSession()->firstOrCreate([], [
                    'user_id' => $trip->user_id,
                    'status' => HikingSessionStatus::ACTIVE->value,
                    'started_at' => now(),
                ]);

                try {
                    $buatSesi();
                } catch (UniqueConstraintViolationException) {
                    try {
                        $buatSesi();
                    } catch (UniqueConstraintViolationException $kolisi) {
                        $kolisiGanda = true;

                        throw $kolisi;
                    }
                }

                $trip->status = TripStatus::IN_PROGRESS;
                $this->reminders->sinkronkan($trip);

                return null;
            });
        } catch (UniqueConstraintViolationException $kolisi) {
            if (! $kolisiGanda) {
                throw $kolisi;
            }

            return 'Pendakian ini belum dapat dimulai. Muat ulang halaman lalu coba lagi.';
        }
    }

    /**
     * Status yang boleh bertransisi ke IN_PROGRESS, dibaca dari TripStatus::allowedTransitions()
     * supaya syarat UPDATE di start() tidak menjadi daftar kedua yang dapat berbeda.
     *
     * @return list<string>
     */
    private static function sumberMulai(): array
    {
        return array_values(array_map(
            fn (TripStatus $status): string => $status->value,
            array_filter(TripStatus::cases(), fn (TripStatus $status): bool => $status->canTransitionTo(TripStatus::IN_PROGRESS)),
        ));
    }

    /**
     * Alasan (DK-50) hanya disimpan untuk hasil "Dibatalkan di jalur"; pendakian yang sampai
     * tujuan atau sebagian tidak membawa alasan pembatalan.
     *
     * UPDATE bersyarat pada status asal di dalam transaksi (ruling W6-5g, K-2 putaran
     * perbaikan 1, task-5-review.md bagian 8). refusal() di controller membaca $trip yang
     * dimuat di awal permintaan dan dapat basi pada konkurensi PostgreSQL yang sungguhan:
     * antara pembacaan itu dan tulisan ini, trip yang sama bisa sudah diubah tab lain. Penjaga
     * yang sebenarnya karena itu adalah WHERE status di UPDATE ini, bukan refusal(). Bila baris
     * sudah berubah status sejak dimuat, affected rows = 0, dan metode ini mengembalikan
     * kalimat penolakan yang sama bentuknya dengan refusal() tanpa menulis riwayat, menutup
     * sesi, atau menjadwalkan ulang pengingat (tanpa efek ganda). Hanya IN_PROGRESS yang dapat
     * bertransisi ke COMPLETED (TripStatus::allowedTransitions), jadi syaratnya satu status.
     * Test dua koneksi sungguhan di PostgreSQL tetap titipan W9 (R2); SQLite menulis
     * berurutan sehingga di sini hanya pembacaan basi yang dibuktikan, lewat simulasi tulis
     * dari kueri lain sebelum metode ini dipanggil (pola test basi W6-3u,
     * HikeModeCheckpointTest::test_a_stale_model_cannot_close_a_newer_pause_via_cas).
     *
     * @return string|null kalimat penolakan, atau null bila transisi berhasil
     */
    public function complete(TripPlan $trip, CompletionState $state, ?string $personalNotes, User $user, ?CancellationReason $alasan = null): ?string
    {
        $trip->loadMissing('preparationItems', 'hikingSession');

        return DB::transaction(function () use ($trip, $state, $personalNotes, $user, $alasan): ?string {
            $terupdate = TripPlan::query()
                ->whereKey($trip->id)
                ->where('status', TripStatus::IN_PROGRESS->value)
                ->update([
                    'status' => TripStatus::COMPLETED->value,
                    'cancellation_reason' => $state === CompletionState::ABANDONED ? $alasan?->value : null,
                    'completed_at' => now(),
                ]);

            if ($terupdate === 0) {
                return self::penolakanStatus($trip);
            }

            $trip->status = TripStatus::COMPLETED;
            $trip->cancellation_reason = $state === CompletionState::ABANDONED ? $alasan : null;

            // Jeda yang masih berjalan ditutup lebih dulu, supaya waktu bergerak tidak ikut
            // menghitung jeda terakhir (ruling W6-R7), lewat tutupSesi() (ruling W6-FT, dipakai
            // bersama cancel()). Lihat docblock tutupSesi() untuk alasan lengkap pembacaan ulang
            // berkunci.
            self::tutupSesi($trip, ['status' => HikingSessionStatus::COMPLETED->value]);

            HikingHistory::updateOrCreate(
                ['trip_plan_id' => $trip->id],
                [
                    'user_id' => $trip->user_id,
                    'trail_id' => $trip->trail_id,
                    'trip_type' => $trip->trip_type->value,
                    'completion_state' => $state->value,
                    'preparation_completion_percent' => $trip->preparationCompletionPercent(),
                    'personal_notes' => $personalNotes,
                    'completed_at' => now(),
                ]
            );

            $this->reminders->sinkronkan($trip);

            $this->analytics->record(AnalyticsEvent::TRIP_COMPLETED, $user, ['trip_plan_id' => $trip->id]);

            return null;
        });
    }

    /**
     * UPDATE bersyarat pada status asal di dalam transaksi (ruling W6-5g, K-2), pola yang sama
     * dengan complete() di atas: lihat docblock-nya untuk alasan lengkap. CANCELLED dapat
     * dicapai dari setiap status aktif (DRAFT, PLANNED, READY_FOR_DEPARTURE, IN_PROGRESS;
     * TripStatus::allowedTransitions), jadi syaratnya "bukan status final", sama dengan
     * TripStatus::isActive().
     *
     * Sesi trip ini ditutup bila ada dan belum berakhir (ruling W6-FT, OPEN ISSUE 2 W6 gelombang
     * akhir): tanpa ini, titik jejak yang direkam shell luring lama sesudah batal tetap diterima,
     * karena saringan PenerimaanJejak::dalamPendakian hanya berlaku bila `ended_at` terisi. Trip
     * yang dibatalkan sebelum dimulai tidak punya sesi, jadi tutupSesi() tidak berbuat apa pun
     * (lihat docblocknya).
     *
     * @return string|null kalimat penolakan, atau null bila transisi berhasil
     */
    public function cancel(TripPlan $trip, ?CancellationReason $alasan = null): ?string
    {
        $trip->loadMissing('hikingSession');

        return DB::transaction(function () use ($trip, $alasan): ?string {
            $terupdate = TripPlan::query()
                ->whereKey($trip->id)
                ->whereNotIn('status', [TripStatus::COMPLETED->value, TripStatus::CANCELLED->value])
                ->update(['status' => TripStatus::CANCELLED->value, 'cancellation_reason' => $alasan?->value]);

            if ($terupdate === 0) {
                return self::penolakanStatus($trip);
            }

            $trip->status = TripStatus::CANCELLED;
            $trip->cancellation_reason = $alasan;

            self::tutupSesi($trip);

            $this->reminders->sinkronkan($trip);

            return null;
        });
    }

    /**
     * Menutup sesi trip ini di dalam transaksi pemanggil, dipakai bersama oleh complete() dan
     * cancel() (ruling W6-FT, I-1 dan W6-FB): baris sesi dibaca ulang dengan lockForUpdate() di
     * sini, bukan dari relasi yang sudah dimuat (loadMissing) sebelum transaksi dimulai, sehingga
     * lanjut sah dari tab lain yang commit di antara pemuatan dan transaksi tidak tertimpa
     * (paused_seconds dan paused_at dari baris saat menulis). Jeda yang masih berjalan ditutup
     * (paused_seconds bertambah, paused_at null) dan ended_at diisi now(); jeda atau lanjut yang
     * tiba sesudahnya menunggu kunci ini lalu melihat ended_at terisi (HikingSession::mulaiJeda
     * dan lanjutkanJeda).
     *
     * $kolomTambahan menulis kolom lain pada UPDATE yang sama: complete() mengisi status
     * COMPLETED di sini, sedangkan cancel() tidak mengirim apa pun, karena status sesi TIDAK
     * diubah oleh batal. Tanpa nilai batal di HikingSessionStatus, dan status sesi tidak dibaca
     * kode mana pun untuk keputusan (hanya ditulis, di sini dan di HikingSession::mulaiJeda/
     * lanjutkanJeda). Berakhirnya sesi dibaca dari `ended_at`
     * (PenerimaanJejak::dalamPendakian, HikingSession::mulaiJeda, lanjutkanJeda), dan jenis
     * akhirnya (selesai atau batal) dari status TRIP, bukan status sesi.
     *
     * Tanpa sesi (trip dibatalkan sebelum dimulai, atau trip diselesaikan/dibatalkan yang
     * baris sesinya entah bagaimana sudah tidak ada), $sesi null dan update() dilewati lewat
     * operator nullsafe, tanpa membuat sesi baru.
     *
     * @param  array<string, mixed>  $kolomTambahan
     */
    private static function tutupSesi(TripPlan $trip, array $kolomTambahan = []): void
    {
        $sesi = $trip->hikingSession()->lockForUpdate()->first();
        $trip->setRelation('hikingSession', $sesi);

        $sesi?->update([
            'ended_at' => now(),
            'paused_at' => null,
            'paused_seconds' => (int) $sesi->paused_seconds + ($sesi->paused_at === null ? 0 : max(0, (int) $sesi->paused_at->diffInSeconds(now(), false))),
            ...$kolomTambahan,
        ]);
    }

    /**
     * Kalimat penolakan sesudah UPDATE bersyarat gagal mengenai baris (ruling W6-5k/m4
     * putaran perbaikan 2). `$trip->fresh()` dapat berupa null bila baris itu terhapus di
     * antara pemuatan dan UPDATE ini, misalnya hapus dan batal hampir bersamaan pada trip yang
     * belum dimulai (probe peninjau p4): tanpa penjaga ini, `->status` pada null melempar
     * ErrorException (500), bertentangan dengan kalimat ruling "bukan 500". Jendelanya sempit
     * (trip berlangsung tidak dapat dihapus, TripLifecycle::deleteRefusal()), tetapi tetap
     * dijaga di sini karena keduanya menulis pada baris yang sama.
     */
    private static function penolakanStatus(TripPlan $trip): string
    {
        $sekarang = $trip->fresh();

        return $sekarang === null
            ? 'Trip ini sudah tidak ada.'
            : sprintf('Tindakan tidak dapat dilakukan: trip berstatus %s.', $sekarang->status->label());
    }

    /**
     * Isian rencana yang boleh ditulis pendaki, dengan kosong tetap null (R-007).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function detailRencana(array $data): array
    {
        return [
            'name' => $data['name'],
            'planned_date' => $data['planned_date'],
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'participants_count' => $data['participants_count'] ?? null,
            'trip_type' => $data['trip_type'],
            'notes' => $data['notes'] ?? null,
        ];
    }
}
