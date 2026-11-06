<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CheckpointType;
use App\Exceptions\PublicationGateViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CheckpointRequest;
use App\Http\Resources\Admin\CheckpointRowResource;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-16: tambah, ubah, urutkan, dan hapus checkpoint satu jalur.
 *
 * Terbuka bagi admin dan bagi pemandu bersertifikat untuk kawasannya sendiri; seluruh
 * penjagaannya authorize('update', $trail) lewat TrailPolicy, bukan middleware peran.
 * Checkpoint pada rute aksi terikat ke jalurnya (scopeBindings), sehingga izin atas satu
 * jalur tidak pernah menjadi izin atas checkpoint jalur lain.
 */
class CheckpointController extends Controller
{
    public function index(Request $request, Trail $trail): Response
    {
        $this->authorize('update', $trail);

        $checkpoints = $trail->checkpoints()->get();

        return Inertia::render('Admin/Checkpoints', [
            'title' => 'Kelola Checkpoint',
            'trail' => ['id' => $trail->id, 'name' => $trail->name],
            'checkpoints' => CheckpointRowResource::collection($checkpoints)->resolve($request),
            'nextSequence' => (string) (((int) $checkpoints->max('sequence')) + 1),
            'types' => array_map(fn (CheckpointType $type) => ['value' => $type->value, 'label' => $type->label()], CheckpointType::cases()),
            'penolakan' => session('penolakan'),
        ]);
    }

    public function store(CheckpointRequest $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $attributes = $this->atribut($request, $trail);

        $checkpoint = Checkpoint::create($attributes);
        $audit->record($request->user(), 'checkpoint.created', $checkpoint, null, $attributes);

        $this->koordinat($request, $checkpoint);

        return back()->with('status', 'Checkpoint tersimpan.');
    }

    public function update(CheckpointRequest $request, Trail $trail, Checkpoint $checkpoint, AuditLogService $audit): RedirectResponse
    {
        $checkpoint->update($this->atribut($request, $trail));
        $audit->recordChange($request->user(), 'checkpoint.updated', $checkpoint);

        $this->koordinat($request, $checkpoint);

        return back()->with('status', 'Checkpoint tersimpan.');
    }

    /**
     * Menukar urutan dengan tetangganya. Tiga penulisan dalam satu transaksi: pasangan
     * (trail_id, sequence) unik, jadi baris yang berpindah diparkir dulu di urutan
     * kosong, dan kegagalan di tengah tidak boleh meninggalkannya terparkir.
     */
    public function move(Request $request, Trail $trail, Checkpoint $checkpoint, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $arah = (int) $request->validate(['arah' => ['required', 'integer', Rule::in([-1, 1])]])['arah'];

        $target = $trail->checkpoints()
            ->where('sequence', $arah < 0 ? '<' : '>', $checkpoint->sequence)
            ->reorder('sequence', $arah < 0 ? 'desc' : 'asc')
            ->first();

        if ($target === null) {
            return back();
        }

        $semula = $checkpoint->sequence;

        DB::transaction(function () use ($trail, $checkpoint, $target, $semula): void {
            $parkir = ((int) $trail->checkpoints()->max('sequence')) + 1;
            $tujuan = $target->sequence;

            $checkpoint->update(['sequence' => $parkir]);
            $target->update(['sequence' => $semula]);
            $checkpoint->update(['sequence' => $tujuan]);
        });

        $audit->record($request->user(), 'checkpoint.reordered', $checkpoint, ['sequence' => $semula], ['sequence' => $checkpoint->sequence]);

        return back();
    }

    /**
     * Menghapus checkpoint, dengan dua urutan yang penting.
     *
     * Pertama, jejak audit ditulis setelah penghapusan berhasil, bukan sebelumnya: sejak
     * gerbang publikasi menjadi invariant keadaan, delete() dapat ditolak, dan jejak audit
     * yang berbohong lebih buruk daripada tidak ada jejak sama sekali.
     *
     * Kedua, penolakan penjaga adalah keputusan domain, bukan kerusakan sistem. Admin
     * berhak membaca alasannya beserta langkah berikutnya, bukan halaman 500.
     */
    public function destroy(Request $request, Trail $trail, Checkpoint $checkpoint, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $sebelum = $checkpoint->toArray();

        try {
            $checkpoint->delete();
        } catch (PublicationGateViolation) {
            return back()->with('penolakan', 'Checkpoint terakhir tidak dapat dihapus selama jalur '
                .'masih dipublikasikan. Turunkan jalur ke DRAFT terlebih dahulu.');
        }

        $audit->record($request->user(), 'checkpoint.deleted', $checkpoint, $sebelum, null);

        return back()->with('status', 'Checkpoint dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(CheckpointRequest $request, Trail $trail): array
    {
        $data = $request->validated();

        return [
            'trail_id' => $trail->id,
            'name' => $data['name'],
            'sequence' => (int) $data['sequence'],
            'checkpoint_type' => $data['checkpoint_type'],
            'elevation_m' => isset($data['elevation_m']) ? (int) $data['elevation_m'] : null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Koordinat hanya ditulis bila keduanya diisi, sama seperti komponen lama: setengah
     * koordinat bukan lokasi.
     */
    private function koordinat(CheckpointRequest $request, Checkpoint $checkpoint): void
    {
        $lintang = $request->validated('latitude');
        $bujur = $request->validated('longitude');

        if ($lintang !== null && $bujur !== null) {
            $checkpoint->setCoordinates((float) $lintang, (float) $bujur);
        }
    }
}
