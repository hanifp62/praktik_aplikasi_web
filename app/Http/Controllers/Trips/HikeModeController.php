<?php

namespace App\Http\Controllers\Trips;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\PauseHikeRequest;
use App\Http\Requests\Trips\RecordArrivalRequest;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Services\Trips\HalamanMendaki;
use App\Services\Trips\HikeCheckpointResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mode lapangan (FR-15; spec v3 bagian 6.12, prototype 11).
 *
 * Halaman membawa data jalur dan keadaan sesi; posisi, sisa jarak, naik lagi, dan tiba di pos
 * dihitung di perangkat (HalamanMendaki). Lokasi diminta di halaman ini dan tidak di tempat
 * lain (PRD §120), dan tidak dikirim ke server (PRD §82, §145.1): server hanya menerima urutan
 * pos yang dicapai dan waktu jeda. Ketiga aksi itu dikirim antrean perangkat sebagai JSON dan dapat
 * diputar ulang sesudah sinyal kembali tanpa mengubah hasilnya (ruling W6-R6): tiba memakai UPDATE
 * bersyarat, dan jeda atau lanjut yang waktunya tidak lebih baru dari lanjut terakhir diabaikan
 * (ruling W6-10f bagian a, W6-10h), dengan batas jam perangkat yang ditulis di HikingSession.
 */
class HikeModeController extends Controller
{
    public function show(Request $request, TripPlan $trip, HalamanMendaki $halaman): Response|RedirectResponse
    {
        $this->authorize('view', $trip);

        // Trip yang sudah usai tidak punya mode lapangan: ringkasannya, atau ruang kerjanya.
        if ($trip->status === TripStatus::COMPLETED) {
            return redirect()->route('history.summary', $trip);
        }

        if ($trip->status === TripStatus::CANCELLED) {
            return redirect()->route('trips.show', $trip);
        }

        return Inertia::render('Trips/Hike', [
            'title' => 'Mode lapangan',
            ...$halaman->untuk($trip, $request->user()),
        ]);
    }

    public function arrive(RecordArrivalRequest $request, TripPlan $trip, HikeCheckpointResolver $resolver): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);

        $resolver->tiba($sesi, $resolver->checkpoints($trip->trail), (int) $request->validated('urutan'));

        return response()->noContent();
    }

    /**
     * Jeda dimulai pada waktu perangkat bila dikirim dari antrean, dijepit di antara mulai sesi
     * dan sekarang. Jeda yang sudah berjalan tidak dimulai ulang, dan jeda yang waktunya tidak
     * lebih baru dari lanjut terakhir (diputar ulang sesudah pasangannya diterima) diabaikan
     * (ruling W6-10f bagian a, `HikingSession::mulaiJeda()`). Jawabannya sama dengan aksi yang
     * diterapkan, jadi antrean membuangnya tanpa pesan.
     */
    public function pause(PauseHikeRequest $request, TripPlan $trip): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);
        $sesi->mulaiJeda($this->waktu($request, $sesi));

        return response()->noContent();
    }

    /**
     * Lanjut menjumlahkan lama jeda ke paused_seconds lewat compare-and-swap (M-7/ruling
     * W6-3g), diekstrak ke `HikingSession::lanjutkanJeda()` (W6-3m) supaya CAS-nya dapat
     * dipanggil dan diuji langsung dengan model basi. Lanjut yang waktunya tidak lebih baru dari
     * lanjut terakhir diabaikan dan dijawab sama (ruling W6-10h).
     */
    public function resume(PauseHikeRequest $request, TripPlan $trip): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);
        $sesi->lanjutkanJeda($this->waktu($request, $sesi));

        return response()->noContent();
    }

    /**
     * Aksi lapangan hanya untuk pendakian yang sedang berlangsung dan bersesi. Selain itu 409:
     * antrean perangkat membuang aksinya dan menyebut alasannya.
     */
    private function sesiBerlangsung(TripPlan $trip): HikingSession
    {
        $trip->loadMissing('hikingSession', 'trail.checkpoints');

        abort_unless($trip->status === TripStatus::IN_PROGRESS && $trip->hikingSession !== null, 409, 'Pendakian ini tidak sedang berlangsung.');

        return $trip->hikingSession;
    }

    /**
     * `->utc()` sesudah parse (M-6/ruling W6-3g): CarbonImmutable::parse() sendirian
     * mempertahankan offset yang dikirim, dan binding PDO memformatnya sebagai jam dinding
     * tanpa konversi, sehingga `pada` berzona waktu selain UTC akan tersimpan pada jam yang
     * salah (offset jam bergeser penuh ke paused_at). Klien selalu mengirim toISOString() (Z),
     * jadi kasus ini laten pada klien yang patuh; ->utc() menutupnya untuk klien mana pun.
     */
    private function waktu(PauseHikeRequest $request, HikingSession $sesi): CarbonImmutable
    {
        $sekarang = CarbonImmutable::now();
        $pada = $request->validated('pada');

        if ($pada === null) {
            return $sekarang;
        }

        $waktu = CarbonImmutable::parse($pada)->utc();

        return $waktu->max($sesi->started_at)->min($sekarang);
    }
}
