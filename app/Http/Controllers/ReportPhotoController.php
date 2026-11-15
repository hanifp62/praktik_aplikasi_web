<?php

namespace App\Http\Controllers;

use App\Models\TrailConditionReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan foto laporan kondisi dari disk privat (PRD §67).
 *
 * Otorisasi selalu diperiksa lebih dulu sebelum ETag dibandingkan, sehingga pengamat
 * yang tidak berhak tidak pernah menerima jawaban 304 untuk foto laporan tertunda
 * atau ditolak.
 */
class ReportPhotoController extends Controller
{
    public function __invoke(Request $request, TrailConditionReport $report): Response|StreamedResponse
    {
        $this->authorize('view', $report);

        abort_if(blank($report->photo_path), 404);

        $disk = Storage::disk(config('filesystems.report_photos_disk'));

        abort_unless($disk->exists($report->photo_path), 404);

        $etag = '"'.sha1($report->photo_path.'|'.$disk->size($report->photo_path).'|'.$disk->lastModified($report->photo_path)).'"';
        $headers = [
            'Cache-Control' => 'private, max-age=3600',
            'ETag' => $etag,
        ];

        $ifNoneMatch = array_map(
            fn (string $nilai) => trim(trim($nilai), '"'),
            explode(',', (string) $request->header('If-None-Match')),
        );

        if ($request->headers->has('If-None-Match') && in_array(trim($etag, '"'), $ifNoneMatch, true)) {
            return response('', 304, $headers);
        }

        return $disk->response($report->photo_path, null, $headers);
    }
}
