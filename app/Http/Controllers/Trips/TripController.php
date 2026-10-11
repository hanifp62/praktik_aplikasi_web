<?php

namespace App\Http\Controllers\Trips;

use App\Enums\CompletionState;
use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\StoreTripRequest;
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Models\TripPlan;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\TrailFitService;
use App\Services\Trips\IndeksTrip;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\RencanaTrip;
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
    /**
     * Indeks trip (spec v3 bagian 6.9): kartu utama trip terdekat, lalu kelompok Berlangsung,
     * Mendatang, dan Selesai, disusun kelas halaman IndeksTrip.
     */
    public function index(Request $request, IndeksTrip $indeks): Response
    {
        return Inertia::render('Trips/Index', [
            'title' => 'Trip Saya',
            ...$indeks->props($request->user(), $request),
        ]);
    }

    /**
     * Rencanakan trip (spec v3 bagian 6.8): isian awal dari jalur yang dipilih (?trail=), dari
     * rencana pendakian (?goal=, atau rencana terakhir) termasuk catatannya, dan dari pilihan
     * jenis dan tanggal di URL, disusun kelas halaman RencanaTrip.
     */
    public function create(Request $request, RencanaTrip $rencana): Response
    {
        return Inertia::render('Trips/Create', [
            'title' => 'Rencanakan trip',
            ...$rencana->props($request->user(), $request),
        ]);
    }

    public function store(StoreTripRequest $request, TripLifecycle $lifecycle): RedirectResponse
    {
        $trip = $lifecycle->create($request->user(), $request->validated());

        // Konfirmasi sesudah pindah halaman datang dari flash halaman tujuan (konvensi W2; M6
        // tinjauan akhir W4): Persiapan merendernya di wilayah statusnya.
        return redirect()->route('trips.preparation', $trip)->with('status', 'Rencana trip tersimpan.');
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
            'batas' => RencanaTrip::batasIsian(),
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

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, OfficialStatusService $statusResmi, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat, KepalaTrip $kepala): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'latestConfirmedReadinessCheck', 'history', 'hikingGoal', 'reminders');
        $user = $request->user();
        $jalur = EloquentCollection::make([$trip->trail]);
        // Dibaca sekali untuk kecocokan dan langkah Cek (tinjauan Tugas 1 M7).
        $statusJalur = $statusResmi->effectiveStatusesForTrails($jalur);

        // Kecocokan untuk rencana ini (spec v3 bagian 6.4): jenis dan tanggal trip di atas
        // salinan tujuannya (TripPlan::tujuanPenilaian(), ruling W4-FA), sama dengan halaman
        // penilaian kecocokan dan Cek kesiapan.
        $fit = $user->hasCompletedProfile()
            ? ($trailFit->forTrails($user, $jalur, $trip->tujuanPenilaian(), $statusJalur)[$trip->trail->id] ?? null)
            : null;
        $jalurTerbit = $trip->trail->terbit();

        // Dihitung ulang setiap halaman dibuka, bukan disimpan: jawabannya berubah seiring
        // hari berjalan meskipun tidak ada satu pun data yang disunting. Trip yang selesai atau
        // batal tidak ditawari pemesanan izin (W4-4d).
        $jendela = $trip->status->isActive() ? $permits->bookingWindowFor($trip->trail, $trip->planned_date) : null;
        $alasanHapus = $lifecycle->deleteRefusal($trip);

        // Ruling W4-G1 (Delivery Gate): "Mulai pendakian" hanya tombol primer sejak hari
        // keberangkatan tiba di zona gunung, selain itu tombol penuh kartu utama indeks
        // sudah jadi satu-satunya titik fokus. Predikat harinya sama dengan
        // KepalaTrip::hariMenuju() dan TripPlan::tanggalRencanaLewat() (Timezone::hariSampai),
        // bukan hitungan hari baru: <= 0 berarti hari ini sudah tanggal rencana atau lewat.
        $hariBerangkat = Timezone::hariSampai($trip->planned_date, Timezone::forTrail($trip->trail)) <= 0;

        return Inertia::render('Trips/Show', [
            'title' => $trip->name,
            'kepala' => $kepala->untuk($trip),
            'trip' => [
                'id' => $trip->id,
                'aktif' => $trip->status->isActive(),
                'berlangsung' => $trip->status === TripStatus::IN_PROGRESS,
                'catatan' => $trip->notes,
                'hariBerangkat' => $hariBerangkat,
            ],
            'langkah' => $steps->for($trip, $statusJalur[$trip->trail->id] ?? null),
            'fit' => $fit === null ? null : [
                'label' => $fit->label?->value,
                'labelText' => RouteFitLabel::textFor($fit->label),
                'alasan' => $fit->alasan,
                'denganRencana' => $fit->denganRencana,
            ],
            // Penilaian lengkap hanya untuk jalur yang masih terbit (halaman itu menjawab 404
            // untuk jalur draf dan arsip).
            'penilaian' => $jalurTerbit ? route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]) : null,
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
}
