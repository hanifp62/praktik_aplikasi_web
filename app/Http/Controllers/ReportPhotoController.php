<?php

namespace App\Http\Controllers;

use App\Models\TrailConditionReport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan foto laporan kondisi dari disk privat (PRD §67).
 *
 * Sebelumnya foto disimpan ke disk publik, sehingga foto pada laporan yang masih
 * menunggu moderasi maupun yang sudah ditolak dapat diakses siapa pun yang menebak
 * URL-nya. Menyajikannya lewat route memungkinkan aturan akses yang sama dengan
 * laporannya sendiri diterapkan pada berkasnya.
 */
class ReportPhotoController extends Controller
{
    public function __invoke(TrailConditionReport $report): StreamedResponse
    {
        $this->authorize('view', $report);

        abort_if(blank($report->photo_path), 404);

        $disk = Storage::disk(config('filesystems.report_photos_disk'));

        abort_unless($disk->exists($report->photo_path), 404);

        return $disk->response(
            $report->photo_path,
            null,
            // Foto privat tidak boleh disimpan proxy bersama.
            ['Cache-Control' => 'private, max-age=600']
        );
    }
}
