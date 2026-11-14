<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Models\OfficialStatus;
use App\Services\DataResmi\ArsipBukti;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arsip bukti satu catatan status resmi (rencana induk W8 butir 6; ruling W8-R9). Hanya kurator
 * yang berwenang atas kawasannya dan admin; berkas selalu diunduh (bukan ditampilkan sebagai
 * halaman), tanpa tebakan tipe, tanpa cache, dan hanya bila sidiknya cocok.
 */
class EvidenceController extends Controller
{
    public function show(OfficialStatus $status, ArsipBukti $arsip): Response
    {
        $this->authorize('viewEvidence', $status);

        $path = $status->evidence_archive_path;
        abort_if(! ArsipBukti::sah($path) || ! Storage::disk(ArsipBukti::disk())->exists($path), 404);
        abort_unless($arsip->utuh($status), 409, 'Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat.');

        return Storage::disk(ArsipBukti::disk())->download($path, 'bukti-status-'.$status->id.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
