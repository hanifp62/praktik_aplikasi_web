<?php

namespace App\Http\Controllers\Community;

use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\SaveManualHikeRequest;
use App\Models\HikingHistory;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;

/**
 * Catat pendakian di luar trip (FR-25, spec v3 bagian 6.14; ruling W7-R3). Pemilik dari sesi dan
 * asal MANUAL dari kode, tidak pernah dari isian. Catatan manual adalah pendakian yang selesai,
 * diisi sendiri, dapat diperbaiki dan dihapus, dan tidak menyentuh profil pendaki (BR-11).
 * Sesudah menulis, Progres dibuka pada tahun catatan itu, dengan kabarnya di bagian riwayat.
 */
class ManualHikeController extends Controller
{
    public function store(SaveManualHikeRequest $request): RedirectResponse
    {
        $riwayat = $request->user()->hikingHistory()->create([
            ...$request->kolom(),
            'source' => HikeRecordSource::MANUAL->value,
            'trip_plan_id' => null,
            'completion_state' => CompletionState::COMPLETED->value,
        ]);

        return $this->keProgres($riwayat, 'Pendakian dicatat di riwayat Anda.');
    }

    public function update(SaveManualHikeRequest $request, HikingHistory $riwayat): RedirectResponse
    {
        $riwayat->update($request->kolom());

        return $this->keProgres($riwayat, 'Catatan pendakian diperbarui.');
    }

    public function destroy(HikingHistory $riwayat): RedirectResponse
    {
        $this->authorize('delete', $riwayat);

        $riwayat->delete();

        return $this->keProgres($riwayat, 'Catatan pendakian dihapus dari riwayat Anda.');
    }

    private function keProgres(HikingHistory $riwayat, string $kabar): RedirectResponse
    {
        return redirect()
            ->route('progress', ['tahun' => $riwayat->completed_at->copy()->setTimezone(Timezone::DEFAULT)->year])
            ->with('kabar-progres', ['bagian' => 'riwayat', 'teks' => $kabar]);
    }
}
