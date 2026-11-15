<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Models\OfficialStatus;
use App\Services\DataResmi\MejaKurator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9). Hanya kurator dengan keanggotaan
 * aktif di badan yang berwenang menyatakan status; admin dan moderator bukan kurator.
 */
class CuratorDeskController extends Controller
{
    public function index(Request $request, MejaKurator $meja): Response
    {
        $this->authorize('viewDesk', OfficialStatus::class);

        return Inertia::render('Curator/Desk', $meja->props($request->user()));
    }
}
