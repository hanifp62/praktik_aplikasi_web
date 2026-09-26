<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermitRequirementRequest;
use App\Http\Resources\Admin\PermitRuleResource;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Services\AuditLogService;
use App\Support\PaginasiInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengelolaan aturan perizinan pendakian (PRD §36 Logistik, §60, §61).
 *
 * Yang dikelola di sini adalah catatan tentang aturan pihak lain: tiap baris membawa
 * sumber dan waktu verifikasi, dan setiap perubahannya masuk jejak audit. Sistem tidak
 * memesan izin dan tidak melacak kuota; PermitService yang membacanya untuk pendaki
 * tidak disentuh.
 */
class PermitController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermitRequirement::class);

        return Inertia::render('Admin/Permits', [
            'title' => 'Kelola Perizinan',
            'rules' => PaginasiInertia::props(
                PermitRequirement::query()->with('trail.mountain', 'mountain')->orderBy('authority')->paginate(10)->withQueryString(),
                fn (PermitRequirement $r) => (new PermitRuleResource($r))->resolve($request),
            ),
            'trails' => Trail::query()->with('mountain')->orderBy('name')->get()
                ->map(fn (Trail $t) => ['value' => $t->id, 'label' => $t->name.' · '.$t->mountain->name])->values()->all(),
            'mountains' => Mountain::query()->orderBy('name')->get()
                ->map(fn (Mountain $m) => ['value' => $m->id, 'label' => $m->name])->values()->all(),
        ]);
    }

    public function store(PermitRequirementRequest $request, AuditLogService $audit): RedirectResponse
    {
        $atribut = $request->atribut();
        $rule = PermitRequirement::create($atribut);
        $audit->record($request->user(), 'permit_requirement.created', $rule, null, $atribut);

        return redirect()->route('admin.permits')->with('status', 'Aturan perizinan tersimpan.');
    }

    public function update(PermitRequirementRequest $request, PermitRequirement $permit, AuditLogService $audit): RedirectResponse
    {
        $permit->update($request->atribut());
        $audit->recordChange($request->user(), 'permit_requirement.updated', $permit);

        return redirect()->route('admin.permits')->with('status', 'Aturan perizinan tersimpan.');
    }

    public function destroy(Request $request, PermitRequirement $permit, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('delete', PermitRequirement::class);

        // Isi baris disalin sebelum dihapus supaya log menyebut apa yang hilang. Jejaknya
        // ditulis setelah penghapusan berhasil, sama dengan CheckpointController::destroy.
        $sebelum = $permit->attributesToArray();
        $permit->delete();
        $audit->record($request->user(), 'permit_requirement.deleted', $permit, $sebelum, null);

        return redirect()->route('admin.permits')->with('status', 'Aturan perizinan dihapus.');
    }
}
