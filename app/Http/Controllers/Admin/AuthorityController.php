<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuthorityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorityRequest;
use App\Models\Authority;
use App\Models\Mountain;
use App\Services\AuditLogService;
use App\Support\PaginasiInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mendaftarkan badan resmi dan kawasan yang berada di bawahnya (PRD §43).
 *
 * Daftarnya berubah: balai berganti nama, pengelola basecamp bertambah, dan setiap
 * gunung baru butuh disebutkan siapa yang berwenang atasnya sebelum halaman "menunggu
 * masukan" dapat menyebut nama.
 */
class AuthorityController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manageAuthorities', Authority::class);

        return Inertia::render('Admin/Authorities', [
            'title' => 'Kelola Badan Resmi',
            'badan' => PaginasiInertia::props(
                Authority::query()->withCount('mountains')->with('mountains:mountains.id')->orderBy('name')->paginate(10)->withQueryString(),
                fn (Authority $b) => [
                    'id' => $b->id,
                    'nama' => $b->displayName(),
                    'jenis' => $b->type->label(),
                    'wilayah' => $b->jurisdiction ?? '-',
                    'jumlahKawasan' => $b->mountains_count,
                    'belumDikaitkan' => $b->mountains_count === 0 && $b->type->mayDeclareTrailStatus(),
                    'isian' => [
                        'name' => $b->name,
                        'type' => $b->type->value,
                        'abbreviation' => $b->abbreviation,
                        'website' => $b->website,
                        'contact' => $b->contact,
                        'jurisdiction' => $b->jurisdiction,
                        'notes' => $b->notes,
                        'mountain_ids' => $b->mountains->pluck('id')->all(),
                    ],
                ],
            ),
            'types' => array_map(fn (AuthorityType $t) => ['value' => $t->value, 'label' => $t->label()], AuthorityType::cases()),
            'mountains' => Mountain::active()->orderBy('name')->get()
                ->map(fn (Mountain $m) => ['value' => $m->id, 'label' => $m->name])->values()->all(),
        ]);
    }

    public function store(AuthorityRequest $request, AuditLogService $audit): RedirectResponse
    {
        $atribut = $request->atribut();

        $badan = Authority::create($atribut + [
            'slug' => Str::slug($atribut['name']),
            'verified_at' => now(),
        ]);
        $audit->record($request->user(), 'authority.created', $badan, null, $atribut);

        $badan->mountains()->sync($request->mountainIds());

        return redirect()->route('admin.authorities')->with('status', 'Badan resmi tersimpan.');
    }

    public function update(AuthorityRequest $request, Authority $authority, AuditLogService $audit): RedirectResponse
    {
        $authority->update($request->atribut());
        $audit->recordChange($request->user(), 'authority.updated', $authority);

        $authority->mountains()->sync($request->mountainIds());

        return redirect()->route('admin.authorities')->with('status', 'Badan resmi tersimpan.');
    }
}
