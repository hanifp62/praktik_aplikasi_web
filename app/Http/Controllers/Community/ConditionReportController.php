<?php

namespace App\Http\Controllers\Community;

use App\Enums\AnalyticsEvent;
use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConditionReportRequest;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Services\AnalyticsRecorder;
use App\Support\ImageSanitizer;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-13 laporan kondisi jalur dari komunitas. Setiap kiriman masuk moderasi dan tidak
 * pernah diperlakukan sebagai status resmi (PRD §49, §118, R-010).
 */
class ConditionReportController extends Controller
{
    public function create(Request $request): Response
    {
        $trailId = $request->integer('trail') ?: null;
        $jalur = $trailId !== null ? Trail::with('segments')->find($trailId) : null;

        return Inertia::render('Community/ConditionReport', [
            'title' => 'Laporan Kondisi Jalur',
            'trails' => Trail::published()->with('mountain')->orderBy('name')->get()
                ->map(fn (Trail $t) => ['value' => $t->id, 'label' => $t->name.' - '.$t->mountain?->name])
                ->values()
                ->all(),
            // Segmen mengikuti jalur yang dipilih; halaman memuat ulang prop ini saja
            // ketika pilihan jalurnya berubah.
            'segments' => ($jalur?->segments ?? collect())
                ->map(fn (TrailSegment $s) => ['value' => $s->id, 'label' => $s->sequence.'. '.$s->name])
                ->values()
                ->all(),
            'tags' => array_map(fn (ConditionTag $t) => ['value' => $t->value, 'label' => $t->label()], ConditionTag::cases()),
            'awal' => [
                'trail_id' => $trailId,
                'trip_plan_id' => $request->integer('trip') ?: null,
                'hike_date' => now()->toDateString(),
            ],
            'tanggalMaksimum' => Timezone::latestDateInIndonesia(),
        ]);
    }

    public function store(StoreConditionReportRequest $request, AnalyticsRecorder $analytics): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // PRD §100. Batas per pengguna, bukan global, supaya satu pengguna yang
        // berlebihan tidak membungkam pengguna lain; laporan masuk antrean moderasi
        // manusia, jadi batasnya menjaga moderator sekaligus penyimpanan.
        $kunci = 'report-submit:'.$user->id;
        $batas = (int) config('hiking.rate_limits.report_submissions_per_hour');

        if (RateLimiter::tooManyAttempts($kunci, $batas)) {
            return back()->withErrors(['form' => sprintf(
                'Anda sudah mengirim %d laporan dalam satu jam terakhir. Coba lagi dalam %d menit.',
                $batas,
                (int) ceil(RateLimiter::availableIn($kunci) / 60)
            )]);
        }

        RateLimiter::hit($kunci, 3600);

        $pathFoto = null;
        $fotoGagal = false;

        if ($request->hasFile('photo')) {
            // Nama berkas acak menjauhkan nama dari pengguna dari path penyimpanan.
            $disk = config('filesystems.report_photos_disk');
            $pathFoto = $request->file('photo')->store('condition-reports', $disk);

            // EXIF foto ponsel membawa koordinat GPS pendaki (PRD §82). Foto yang gagal
            // dibersihkan dibuang, tetapi laporannya tetap tersimpan.
            if (! ImageSanitizer::trySanitize(Storage::disk($disk)->path($pathFoto), (int) config('hiking.uploads.report_photo_max_dimension'))) {
                Storage::disk($disk)->delete($pathFoto);
                $pathFoto = null;
                $fotoGagal = true;
            }
        }

        $laporan = TrailConditionReport::create([
            'trail_id' => (int) $data['trail_id'],
            'trail_segment_id' => $data['trail_segment_id'] ?? null,
            'user_id' => $user->id,
            'hike_date' => $data['hike_date'],
            'condition_tags' => $data['condition_tags'],
            'photo_path' => $pathFoto,
            'note' => $data['note'] ?? null,
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);

        if (! empty($data['trip_plan_id'])) {
            HikingHistory::where('trip_plan_id', $data['trip_plan_id'])
                ->where('user_id', $user->id)
                ->update(['trail_condition_report_id' => $laporan->id]);
        }

        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, $user, ['trail_id' => (int) $data['trail_id']]);

        $respons = redirect()->route('history')
            ->with('status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.');

        // Kunci terpisah dari 'status'. Versi Livewire menulis keduanya ke 'status'
        // berturut-turut, sehingga kabar foto yang dibuang tertimpa dan tidak pernah
        // terbaca pengirimnya.
        return $fotoGagal
            ? $respons->with('foto-tidak-disertakan', 'Foto tidak dapat diproses dan tidak disertakan. Laporan tetap tersimpan.')
            : $respons;
    }
}
