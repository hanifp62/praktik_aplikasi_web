<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\ReadinessService;
use App\Services\Trips\HalamanKesiapan;
use App\Services\Trips\PreDepartureCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-10 kesiapan: route fit + persiapan + kondisi terkini (PRD §38-39), disusun kelas halaman
 * HalamanKesiapan (spec v3 bagian 6.11). Dukungan keputusan, bukan izin keselamatan (R-014).
 *
 * Membuka halaman hanya menghitung, tidak menyimpan. Baris ReadinessCheck baru ditulis
 * ketika pengguna meminta perhitungan ulang atau mengonfirmasi tinjauan.
 */
class ReadinessController extends Controller
{
    public function show(Request $request, TripPlan $trip, ReadinessService $readiness, HalamanKesiapan $halaman, PreDepartureCheck $preDepartureCheck): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems', 'latestConfirmedReadinessCheck');

        $assessment = $readiness->compute($trip);
        // "Sejak cek terakhir" membandingkan dengan kunjungan tersimpan terakhir apa pun.
        $terakhir = $readiness->latestCheck($trip);
        // Satu predikat dan satu baris untuk tanda, alasan, dan waktu tinjauan (ruling W4-FB;
        // Nit A W4-6l: relasinya dimuat sekali per muat, bukan empat kueri lepas).
        $tinjauan = $trip->tinjauanTercatat();
        // Satu sumber dengan PreDepartureCheck::confirm(): halaman tidak pernah menulis alasan
        // yang berbeda dari yang akan diberikan POST yang sama. Null berarti boleh meninjau.
        // Tinjauan ulang sengaja tidak ditawarkan di W4 (keputusan pemilik).
        $alasanTanpaTinjauan = $preDepartureCheck->alasanPenolakan($trip, $assessment);

        return Inertia::render('Trips/Readiness', [
            'title' => 'Kesiapan Pendakian',
            ...$halaman->props($trip, $request->user(), $assessment, $terakhir),
            'bolehKonfirmasi' => $alasanTanpaTinjauan === null,
            'alasanTanpaTinjauan' => $alasanTanpaTinjauan,
            'preDeparture' => [
                'dikonfirmasi' => $tinjauan !== null,
                'dicatat' => $tinjauan !== null ? PreDepartureCheck::waktuTinjauan($trip, $tinjauan) : null,
            ],
            'penolakan' => session('penolakan-kesiapan'),
        ]);
    }

    /**
     * Menyimpan hasil cek sebagai pembanding "Sejak cek terakhir"; membuka halaman tidak
     * pernah menulis (ReadinessDashboardTest). Hanya untuk rencana yang masih di depan
     * (TripPlan::masihDirencanakan(), predikat yang sama dengan tombolnya, W4-4d): pembanding
     * untuk trip yang batal, selesai, berlangsung, atau sudah lewat tanggalnya tidak dibaca siapa
     * pun.
     */
    public function recompute(TripPlan $trip, ReadinessService $readiness): RedirectResponse
    {
        $this->authorize('update', $trip);

        $trip->load('trail.mountain');

        if (! $trip->masihDirencanakan()) {
            return redirect()->route('trips.readiness', $trip)
                ->with('penolakan-kesiapan', 'Hasil cek hanya disimpan untuk rencana yang belum dimulai dan tanggalnya belum lewat.');
        }

        $readiness->record($trip);

        return redirect()->route('trips.readiness', $trip)->with('status', 'Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini.');
    }

    public function confirm(Request $request, TripPlan $trip, PreDepartureCheck $check): RedirectResponse
    {
        $this->authorize('update', $trip);

        $trip->load('trail.mountain');
        $penolakan = $check->confirm($trip, $request->user());

        // Penolakan tidak memakai flash 'status' (alert hijau): kotak berhasil yang
        // berisi kabar gagal adalah pesan yang membantah dirinya sendiri.
        return $penolakan !== null
            ? redirect()->route('trips.readiness', $trip)->with('penolakan-kesiapan', $penolakan)
            : redirect()->route('trips.readiness', $trip)->with('status', 'Tinjauan Anda tercatat.');
    }
}
