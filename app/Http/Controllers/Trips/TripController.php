<?php

namespace App\Http\Controllers\Trips;

use App\Enums\CompletionState;
use App\Enums\PreparationStatus;
use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\StoreTripRequest;
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Http\Resources\TripRowResource;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\TrailFitService;
use App\Services\Trips\TripLifecycle;
use App\Services\Trips\TripReminderScheduler;
use App\Services\Trips\TripSteps;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trip Saya, pembuatan trip (FR-08), halaman satu trip, serta mengubah dan menghapus
 * rencana (features/04, DK-07). Pengganti komponen Livewire TripIndex, TripForm, dan
 * TripShow; query, otorisasi, dan validasinya sama.
 */
class TripController extends Controller
{
    public function index(Request $request, OfficialStatusService $officialStatus, TrailFitService $trailFit): Response
    {
        $user = $request->user();

        $trips = $user->tripPlans()
            ->with('trail.mountain', 'latestReadinessCheck')
            ->withCount([
                'preparationItems as item_berlaku' => fn ($q) => $q->where('status', '!=', PreparationStatus::NOT_APPLICABLE->value),
                'preparationItems as item_dikonfirmasi' => fn ($q) => $q->where('status', PreparationStatus::CONFIRMED->value),
            ])
            ->orderByDesc('planned_date')
            ->paginate(10);

        // Bukan collect(): OfficialStatusService dan PermitService (lewat forTrails())
        // men-type-hint Eloquent\Collection secara ketat dan melempar TypeError pada
        // Support\Collection biasa.
        $jalurDitampilkan = EloquentCollection::make(
            $trips->pluck('trail')->filter()->unique('id')->values()->all()
        );

        // Kecocokan dasar, tanpa goal. Pengguna tanpa profil lengkap tidak dinilai sama
        // sekali: menilai tanpa profil menghasilkan label yang terlihat pasti dan
        // berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile()
            ? $trailFit->forTrails($user, $jalurDitampilkan)
            : [];

        // Dimuat sekali untuk seluruh halaman, bukan satu query status per trip.
        $statusJalur = $officialStatus->effectiveStatusesForTrails($jalurDitampilkan);

        return Inertia::render('Trips/Index', [
            'title' => 'Trip Saya',
            'trips' => [
                'data' => $trips->getCollection()
                    ->map(fn (TripPlan $trip) => (new TripRowResource(
                        $trip,
                        $ringkasanFit[$trip->trail_id] ?? null,
                        $statusJalur[$trip->trail_id] ?? null,
                    ))->resolve($request))
                    ->values()
                    ->all(),
                'links' => $trips->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
        ]);
    }

    /**
     * Isian awal dibawa dari jalur yang dipilih (?trail=) dan dari rencana pendakian
     * (?goal=, atau rencana terakhir), termasuk catatannya, supaya konteks yang diketik
     * pendaki pada rencana tidak hilang.
     */
    public function create(Request $request, TripReminderScheduler $pengingat): Response
    {
        $user = $request->user();
        $trail = $request->integer('trail') ?: null;
        $goal = $request->integer('goal') ?: null;

        $dipilih = $trail ? Trail::published()->find($trail) : null;

        $goalTerakhir = $goal
            ? $user->hikingGoals()->find($goal)
            : $user->hikingGoals()->latest()->first();

        return Inertia::render('Trips/Create', [
            'title' => 'Buat Rencana Trip',
            'awal' => [
                'trail_id' => $trail !== null ? (string) $trail : '',
                'hiking_goal_id' => $goalTerakhir?->id,
                'name' => $dipilih ? 'Pendakian '.$dipilih->name : '',
                'planned_date' => $goalTerakhir?->target_date?->toDateString() ?? '',
                'end_date' => '',
                'start_time' => '',
                'participants_count' => '',
                'trip_type' => $goalTerakhir?->trip_type?->value ?? $user->preference?->preferred_trip_type?->value ?? '',
                'notes' => $goalTerakhir?->notes ?? '',
                'reminders_enabled' => false,
            ],
            'trails' => Trail::published()->with('mountain')->orderBy('name')->get()
                ->map(fn (Trail $t) => ['value' => (string) $t->id, 'label' => $t->name.' - '.$t->mountain?->name])
                ->all(),
            'tripTypes' => self::tipePerjalanan(),
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
            'batas' => self::batasRencana(),
            'jadwalPengingat' => $pengingat->keteranganJadwal(),
        ]);
    }

    public function store(StoreTripRequest $request, TripLifecycle $lifecycle): RedirectResponse
    {
        $trip = $lifecycle->create($request->user(), $request->validated());

        return redirect()->route('trips.preparation', $trip);
    }

    /**
     * Formulir ubah rencana. Jalurnya ditampilkan, tidak dapat diganti: jalur lain
     * berarti rencana lain beserta daftar persiapannya sendiri.
     */
    public function edit(TripPlan $trip, TripLifecycle $lifecycle): Response|RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->editRefusal($trip)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $trip->load('trail.mountain');

        return Inertia::render('Trips/Edit', [
            'title' => 'Ubah Rencana Trip',
            'trip' => [
                'id' => $trip->id,
                'jalur' => $trip->trail->name.' - '.$trip->trail->mountain->name,
            ],
            'awal' => [
                'name' => $trip->name,
                'planned_date' => $trip->planned_date->toDateString(),
                'end_date' => $trip->end_date?->toDateString() ?? '',
                'start_time' => $trip->start_time ? Carbon::parse($trip->start_time)->format('H:i') : '',
                'participants_count' => $trip->participants_count !== null ? (string) $trip->participants_count : '',
                'trip_type' => $trip->trip_type->value,
                'notes' => $trip->notes ?? '',
            ],
            'tripTypes' => self::tipePerjalanan(),
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
            'batas' => self::batasRencana(),
        ]);
    }

    public function update(UpdateTripRequest $request, TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        if ($penolakan = $lifecycle->editRefusal($trip)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->update($trip, $request->validated());

        return redirect()->route('trips.show', $trip)->with('status', 'Rencana trip diperbarui.');
    }

    public function destroy(TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        $this->authorize('delete', $trip);

        if ($penolakan = $lifecycle->deleteRefusal($trip)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->delete($trip);

        return redirect()->route('trips.index')->with('status', 'Rencana trip dihapus.');
    }

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'history', 'hikingGoal', 'reminders');
        $user = $request->user();

        // Trip sudah punya goal, jadi kecocokannya yang tajam, bukan yang dasar.
        $fit = $user->hasCompletedProfile()
            ? ($trailFit->forTrails($user, EloquentCollection::make([$trip->trail]), $trip->hikingGoal)[$trip->trail->id] ?? null)
            : null;

        // Dihitung ulang setiap halaman dibuka, bukan disimpan: jawabannya berubah seiring
        // hari berjalan meskipun tidak ada satu pun data yang disunting.
        $jendela = $permits->bookingWindowFor($trip->trail, $trip->planned_date);
        $alasanHapus = $lifecycle->deleteRefusal($trip);

        return Inertia::render('Trips/Show', [
            'title' => $trip->name,
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'trail' => $trip->trail->name,
                'mountain' => $trip->trail->mountain->name,
                'tanggal' => $trip->planned_date->translatedFormat('d M Y'),
                'sampai' => $trip->end_date !== null && ! $trip->end_date->isSameDay($trip->planned_date)
                    ? $trip->end_date->translatedFormat('d M Y')
                    : null,
                'peserta' => $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
                // Jam ini rencana pengguna, bukan instan UTC, jadi tidak dikonversi.
                // Penandanya tetap disebut: "mulai 06:00" ambigu bagi pendaki Jakarta
                // yang merencanakan Rinjani.
                'mulai' => $trip->start_time
                    ? Carbon::parse($trip->start_time)->format('H:i').' '.Timezone::label(Timezone::forTrail($trip->trail))
                    : null,
                'tipe' => $trip->trip_type->label(),
                'status' => $trip->status->label(),
                'aktif' => $trip->status->isActive(),
                'berlangsung' => $trip->status === TripStatus::IN_PROGRESS,
                'catatan' => $trip->notes,
            ],
            'langkah' => $steps->for($trip),
            'fit' => $fit === null ? null : [
                'label' => $fit->label?->value,
                'labelText' => RouteFitLabel::textFor($fit->label),
                'alasan' => $fit->alasan,
                'denganRencana' => $fit->denganRencana,
            ],
            // Nadanya mengikuti §43: sistem menyebut aturan dan penyelenggaranya, tidak
            // pernah menyatakan izinnya sudah aman atau belum.
            'izin' => $jendela === null ? null : [
                'ditutup' => $jendela['state'] === PermitService::BOOKING_DITUTUP,
                'pesan' => $jendela['message'],
                'tautanPemesanan' => $jendela['requirement']->booking_url,
            ],
            'hasilPendakian' => $trip->history !== null ? route('history.summary', $trip) : null,
            'completionStates' => array_map(fn (CompletionState $s) => ['value' => $s->value, 'label' => $s->label()], CompletionState::cases()),
            'penolakan' => session('penolakan-trip'),
            'pengingat' => $pengingat->untukHalaman($trip),
            // Dijelaskan sebelum pendaki mencoba, bukan baru ditolak sesudahnya.
            'kelola' => [
                'bolehUbah' => $lifecycle->editRefusal($trip) === null,
                'bolehHapus' => $alasanHapus === null,
                'alasanTidakDapatDihapus' => $alasanHapus,
            ],
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function tipePerjalanan(): array
    {
        return array_map(fn (TripType $t) => ['value' => $t->value, 'label' => $t->label()], TripType::cases());
    }

    /**
     * Batas isian yang sama dengan aturan validasinya, supaya petunjuk di formulir tidak
     * pernah menyebut angka yang berbeda dari yang ditolak server.
     *
     * @return array{peserta: int, rentangHari: int}
     */
    private static function batasRencana(): array
    {
        return [
            'peserta' => (int) config('hiking.trips.max_participants'),
            'rentangHari' => (int) config('hiking.trips.max_span_days'),
        ];
    }
}
