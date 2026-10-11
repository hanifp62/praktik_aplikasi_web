<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\StoreComparisonShareRequest;
use App\Models\ComparisonShare;
use App\Models\Trail;
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TautanBagikan;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148).
 *
 * Membuat dan mencabut hanya untuk pemiliknya. Halaman publik /b/{token} dibuka tanpa akun,
 * hanya membaca, dan hanya memuat data publik jalur beserta sumber dan waktunya: tanpa
 * catatan pribadi, tanpa label kecocokan pembuatnya, dan tanpa nama pembuatnya. Halamannya
 * tidak diindeks (X-Robots-Tag dan meta robots) dan dibatasi laju per alamat.
 */
class ComparisonShareController extends Controller
{
    public function store(StoreComparisonShareRequest $request, TautanBagikan $tautan): RedirectResponse
    {
        $ids = array_map('intval', $request->validated('trails'));
        $trails = Trail::query()->published()->whereKey($ids)->get()
            ->sortBy(fn (Trail $t) => array_search($t->id, $ids, true))
            ->values();

        if ($trails->count() !== count($ids)) {
            throw ValidationException::withMessages(['trails' => 'Hanya jalur yang sedang terbit yang dapat dibagikan.']);
        }

        $hasil = $tautan->buat($request->user(), new Collection($trails->all()));

        if ($hasil === null) {
            throw ValidationException::withMessages(['trails' => 'Tautan aktif Anda sudah '.(int) config('hiking.share_links.max_active_per_user').'. Cabut satu dulu sebelum membuat yang baru.']);
        }

        [$token, $share] = $hasil;

        // Token hanya ditunjukkan sekali, di sesi pembuatnya sendiri; basis data menyimpan hash-nya.
        return back()->with('tautan-bagikan', [
            'id' => $share->id,
            'url' => route('shares.show', $token),
            'berlakuSampai' => $share->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
        ])->with('pengumuman', 'Tautan bagikan dibuat.');
    }

    /**
     * Mencabut tautan milik pengguna yang masuk. Tautan orang lain dan yang tidak ada dijawab
     * sama (404), supaya jawaban tidak membocorkan tautan siapa pun.
     */
    public function destroy(Request $request, int $share, TautanBagikan $tautan): RedirectResponse
    {
        $baris = ComparisonShare::query()->whereBelongsTo($request->user())->whereNull('revoked_at')->findOrFail($share);

        $tautan->cabut($baris);

        return back()->with('pengumuman', 'Tautan bagikan dicabut. Siapa pun yang membukanya kini melihat bahwa tautan itu tidak berlaku.');
    }

    public function show(Request $request, string $token, TautanBagikan $tautan, RouteComparisonTable $tabel, StatusJalurRingkas $status): Response
    {
        $share = $tautan->buka($token);

        if ($share === null) {
            return $this->tanpaIndeks(
                Inertia::render('Public/SharedComparison', ['title' => 'Tautan tidak berlaku', 'berlaku' => false, 'hariBerlaku' => $tautan->hariBerlaku()])
                    ->toResponse($request)
                    ->setStatusCode(404)
            );
        }

        $urutan = $share->trail_ids;
        $trails = new Collection(Trail::query()->published()->whereKey($urutan)->with(['mountain', 'dataSource'])->get()
            ->sortBy(fn (Trail $t) => array_search($t->id, $urutan, true))
            ->values()
            ->all());
        $ringkas = $status->untuk($trails);

        return $this->tanpaIndeks(Inertia::render('Public/SharedComparison', [
            'title' => 'Perbandingan jalur',
            'berlaku' => true,
            'berlakuSampai' => $share->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
            'jalur' => $trails->map(fn (Trail $t) => [
                'id' => $t->id,
                'nama' => $t->name,
                'gunung' => $t->mountain?->name,
                'provinsi' => $t->mountain?->province,
                'status' => $ringkas[$t->id],
                'sumber' => $t->dataSource?->source_name,
                // Waktu verifikasi sumber data, bukan trails.updated_at yang berubah oleh suntingan
                // apa pun (T8-M6, R-013). Tanpa verifikasi tidak ada waktu yang ditulis.
                'diverifikasi' => $t->dataSource?->verified_at?->copy()->setTimezone(Timezone::forTrail($t))->translatedFormat('j M Y'),
            ])->values()->all(),
            'baris' => $tabel->baris($trails),
            'ditarik' => count($urutan) - $trails->count(),
            // Untuk tamu juga (m8): "Masuk" menuju perbandingan ini, dan middleware auth menyimpan
            // intended URL, jadi sesudah masuk pembaca kembali ke sini, bukan ke dasbor.
            'bandingUrl' => $trails->isEmpty() ? null : route('trails.compare', ['trails' => $trails->pluck('id')->join(',')]),
            'tamu' => $request->user() === null,
        ])->toResponse($request));
    }

    /**
     * Tidak diindeks, dan tanpa Referer (T8-M3 tinjauan akhir W3): token tautan ada di URL,
     * jadi kebijakan bawaan strict-origin-when-cross-origin membawa token lengkap ke log sesama
     * asal. SecurityHeaders tidak menimpa kebijakan yang sudah disetel respons ini.
     */
    private function tanpaIndeks(Response $respons): Response
    {
        $respons->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $respons->headers->set('Referrer-Policy', 'no-referrer');

        return $respons;
    }
}
