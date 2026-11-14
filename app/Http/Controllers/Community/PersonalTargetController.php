<?php

namespace App\Http\Controllers\Community;

use App\Enums\PersonalTargetMetric;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\UpdatePersonalTargetsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Target pribadi bulanan (FR-25, PRD §150; ruling W7-R7). Satu set untuk pendaki yang masuk:
 * tidak ada id target di rute maupun isian, jadi target orang lain tidak dapat dirujuk sama
 * sekali. Target tidak menyentuh profil, tingkat pengalaman, maupun penilaian kecocokan (BR-11).
 */
class PersonalTargetController extends Controller
{
    public function update(UpdatePersonalTargetsRequest $request): RedirectResponse
    {
        $pengguna = $request->user();

        DB::transaction(function () use ($pengguna, $request) {
            foreach ($request->nilai() as $ukuran => $nilai) {
                if ($nilai === null) {
                    $pengguna->personalTargets()->where('metric', $ukuran)->delete();

                    continue;
                }

                $pengguna->personalTargets()->updateOrCreate(['metric' => PersonalTargetMetric::from($ukuran)->value], ['monthly_value' => $nilai]);
            }
        });

        return redirect()->route('progress')->with('kabar-progres', ['bagian' => 'target', 'teks' => 'Target pribadi disimpan.']);
    }
}
