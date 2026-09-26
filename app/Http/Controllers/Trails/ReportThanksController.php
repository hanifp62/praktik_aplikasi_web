<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\ThankReportRequest;
use App\Models\ReportThank;
use App\Models\TrailConditionReport;
use Illuminate\Http\RedirectResponse;

/**
 * Berterima kasih pada laporan kondisi yang menolong, atau menariknya kembali.
 *
 * Menggantikan papan peringkat kontribusi yang dibatalkan setelah risetnya: yang dihargai
 * kegunaannya, dan yang menilainya pendaki lain yang membacanya sebelum berangkat. Semua
 * penjagaan di sini menjaga satu hal: angkanya tidak boleh dapat dinaikkan sendirian.
 * Pelapor sendiri ditolak di ThankReportRequest.
 */
class ReportThanksController extends Controller
{
    public function store(ThankReportRequest $request, TrailConditionReport $report): RedirectResponse
    {
        // Laporan yang belum lolos moderasi belum terlihat siapa pun, jadi tidak ada yang
        // dapat menyatakan laporan itu menolongnya; laporan pada jalur terarsip juga tidak
        // lagi terlihat. 404, bukan 403: keberadaannya tidak dikonfirmasi.
        $terlihat = TrailConditionReport::query()
            ->visibleToPublic()
            ->whereKey($report->id)
            ->whereHas('trail', fn ($q) => $q->whereNull('archived_at'))
            ->exists();

        abort_unless($terlihat, 404);

        $kunci = ['trail_condition_report_id' => $report->id, 'user_id' => $request->user()->id];

        // createOrFirst, bukan firstOrCreate: klik ganda yang berbalapan menabrak indeks
        // unik report_thanks_unik, dan balapan itu tidak boleh berakhir sebagai galat 500.
        $request->boolean('ya')
            ? ReportThank::createOrFirst($kunci)
            : ReportThank::where($kunci)->delete();

        return back();
    }
}
