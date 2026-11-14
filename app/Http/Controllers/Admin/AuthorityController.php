<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuthorityMemberRole;
use App\Enums\AuthorityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorityRequest;
use App\Models\Authority;
use App\Models\AuthorityMember;
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

            // Anggota meja kurator yang belum dicabut (W8, ruling W8-R7), satu kueri.
            'anggota' => AuthorityMember::query()
                ->whereNull('revoked_at')
                ->with(['user:id,name,email', 'authority:id,name,abbreviation'])
                ->orderBy('authority_id')
                ->orderBy('id')
                ->limit(self::BATAS_ANGGOTA)
                ->get()
                ->map(fn (AuthorityMember $a) => [
                    'id' => $a->id,
                    'badanId' => $a->authority_id,
                    'nama' => $a->user->name,
                    'email' => $a->user->email,
                    'badan' => $a->authority->displayName(),
                    'peran' => $a->role->label(),
                    'surat' => $a->appointment_reference,
                    'berlaku' => $a->valid_until === null
                        ? 'Sejak '.$a->valid_from->translatedFormat('j M Y')
                        : $a->valid_from->translatedFormat('j M Y').' sampai '.$a->valid_until->translatedFormat('j M Y'),
                ])->values()->all(),
            'badanBerwenang' => Authority::query()
                ->whereIn('type', array_map(fn (AuthorityType $t) => $t->value, array_filter(AuthorityType::cases(), fn (AuthorityType $t) => $t->mayDeclareTrailStatus())))
                ->orderBy('name')
                ->get()
                ->map(fn (Authority $b) => ['value' => $b->id, 'label' => $b->displayName()])->values()->all(),
            'peranAnggota' => array_map(fn (AuthorityMemberRole $p) => ['value' => $p->value, 'label' => $p->label()], AuthorityMemberRole::cases()),
        ]);
    }

    /**
     * Daftar anggota di halaman ini; pemilik memberi peran satu per satu, jadi seratus cukup jauh
     * di atas kebutuhan tahap 1.
     */
    private const BATAS_ANGGOTA = 100;

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
