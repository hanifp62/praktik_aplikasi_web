<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PublicationGateViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GpxUploadRequest;
use App\Models\Trail;
use App\Services\Admin\GeometryDraft;
use App\Services\AuditLogService;
use App\Services\OpenStreetMapTrails;
use App\Support\GpxTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * PRD §65 dan §109: memasukkan geometri jalur lewat berkas GPX atau kandidat
 * OpenStreetMap, selalu lewat pratinjau yang dilihat manusia sebelum apa pun ditulis.
 *
 * Seluruh aksinya menjaga diri dengan authorize('update', $trail), sehingga pemandu
 * bersertifikat dapat memakainya untuk kawasannya sendiri (TrailPolicy, §43).
 */
class TrailGeometryController extends Controller
{
    /** Selisih panjang yang dianggap patut dicurigai, dalam persen dari jarak tercatat. */
    private const SELISIH_MENCURIGAKAN = 30;

    public function show(Request $request, Trail $trail): Response
    {
        $this->authorize('update', $trail);

        $trail->loadMissing('mountain');
        $draf = GeometryDraft::untuk($request->user(), $trail)->baca();
        $saringan = trim((string) $request->query('saringan', ''));
        $geometri = $trail->readGeoJson('geometry');
        $angka = fn (int|float $nilai, int $desimal = 0) => number_format($nilai, $desimal, ',', '.');

        return Inertia::render('Admin/TrailGeometry', [
            'title' => 'Impor Geometri Jalur',
            'trail' => ['id' => $trail->id, 'name' => $trail->name, 'mountain' => $trail->mountain?->name],
            'spasial' => Trail::spatialSupported(),
            'titikTersimpan' => $geometri !== null ? $angka(count($geometri['coordinates'] ?? [])) : null,
            'maksTitik' => $angka((int) config('hiking.uploads.gpx_max_points')),
            'ambangKetinggian' => (string) config('hiking.uploads.gpx_elevation_threshold_m'),
            'kandidat' => [
                'jumlah' => count($draf['kandidat']),
                'jumlahTeks' => $angka(count($draf['kandidat'])),
                'saringan' => $saringan,
                'tampil' => array_map(fn (array $calon) => [
                    'id' => $calon['id'],
                    'nama' => $calon['nama'],
                    'keterangan' => $angka($calon['panjang_km'], 2).' km · '.$angka($calon['titik']).' titik · OSM way '.$calon['id'],
                ], GeometryDraft::saringKandidat($draf['kandidat'], $saringan)),
            ],
            'pratinjau' => $draf['pratinjau'] === [] ? null : $this->pratinjau($trail, $draf, $angka),
            'galat' => session('galat'),
            'galatOsm' => session('galatOsm'),
            'penolakan' => session('penolakan'),
        ]);
    }

    /**
     * Berkas dibaca dan diperiksa sebelum apa pun ditulis, supaya kurator melihat jumlah
     * titik dan panjang jejaknya dulu dan sempat membatalkan berkas yang keliru.
     */
    public function preview(GpxUploadRequest $request, Trail $trail): RedirectResponse
    {
        $draf = GeometryDraft::untuk($request->user(), $trail);
        $berkas = $request->file('berkas');

        try {
            $jejak = GpxTrack::trackFromXml((string) file_get_contents($berkas->getRealPath()));
        } catch (RuntimeException $e) {
            $draf->kosongkanPratinjau();

            return back(fallback: route('trails.geometry', $trail))->withErrors(['berkas' => $e->getMessage()]);
        }

        $draf->dariBerkas($jejak, $berkas->getClientOriginalName());

        return back(fallback: route('trails.geometry', $trail));
    }

    /**
     * Mencari kandidat geometri di OpenStreetMap, berpusat pada koordinat gunung yang
     * sudah dicatat kurator.
     *
     * Sengaja tidak memakai pencarian nama. Nominatim mengembalikan bukit di Ponorogo
     * untuk "Gunung Prau", bukan Prau di Dieng, dan jalur yang salah menempatkan pendaki
     * di gunung yang salah.
     */
    public function search(Request $request, Trail $trail, OpenStreetMapTrails $osm): RedirectResponse
    {
        $this->authorize('update', $trail);

        $draf = GeometryDraft::untuk($request->user(), $trail);
        $draf->mulaiPencarian([]);

        $gunung = $trail->mountain;

        if ($gunung === null || ! $gunung->hasCoordinates()) {
            return to_route('trails.geometry', $trail)->with('galatOsm', 'Koordinat '.($gunung?->name ?? 'gunung').' belum dicatat, '
                .'sehingga pencarian tidak punya titik pusat. Isi dulu di halaman Kelola Gunung.');
        }

        $kandidat = $osm->near($gunung->latitude, $gunung->longitude);
        $draf->mulaiPencarian($kandidat);

        if ($kandidat === []) {
            return to_route('trails.geometry', $trail)->with('galatOsm', 'Tidak ada jalur kaki terpetakan di sekitar titik ini, atau OpenStreetMap '
                .'sedang tidak dapat dihubungi. Unggah berkas GPX sebagai gantinya.');
        }

        return to_route('trails.geometry', $trail);
    }

    /**
     * Kandidat dipilih manusia, tidak pernah otomatis. Setelah dipilih, jalannya sama
     * dengan berkas GPX: pratinjau dulu, perbandingan panjang, baru disimpan.
     */
    public function choose(Request $request, Trail $trail, int $osm): RedirectResponse
    {
        $this->authorize('update', $trail);

        GeometryDraft::untuk($request->user(), $trail)->pilihKandidat($osm);

        return back(fallback: route('trails.geometry', $trail));
    }

    /**
     * Menerapkan taksiran tanjakan ke jalur, atas persetujuan kurator.
     *
     * Tidak pernah ditulis diam-diam saat menyimpan geometri. Angkanya taksiran yang
     * bergantung pada ambang derau, dan jalur yang sudah punya angka resmi dari
     * pengelola tidak boleh tertimpa oleh hitungan dari satu berkas GPX.
     */
    public function applyElevation(Request $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $tanjakan = GeometryDraft::untuk($request->user(), $trail)->baca()['tanjakan'];

        if ($tanjakan === null) {
            return back(fallback: route('trails.geometry', $trail));
        }

        $sebelum = $trail->only(['elevation_gain_m', 'elevation_loss_m']);

        try {
            $trail->update([
                'elevation_gain_m' => $tanjakan['gain'],
                'elevation_loss_m' => $tanjakan['loss'],
            ]);
        } catch (PublicationGateViolation $e) {
            return back(fallback: route('trails.geometry', $trail))->with('penolakan', $e->curatorMessage());
        }

        $audit->record($request->user(), 'trail.elevation_applied', $trail, $sebelum, [
            'elevation_gain_m' => $tanjakan['gain'],
            'elevation_loss_m' => $tanjakan['loss'],
            'sumber' => 'taksiran dari berkas GPX',
        ]);

        return back(fallback: route('trails.geometry', $trail))->with('status', 'Tanjakan dan turunan diperbarui dari berkas GPX.');
    }

    public function store(Request $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $trail);

        $penyimpan = GeometryDraft::untuk($request->user(), $trail);
        $draf = $penyimpan->baca();
        $kembali = back(fallback: route('trails.geometry', $trail));

        if ($draf['pratinjau'] === []) {
            return $kembali->with('galat', 'Belum ada jejak yang siap disimpan.');
        }

        // writeLineString() diam saja pada koneksi tanpa PostGIS, jadi tanpa penjagaan ini
        // kurator akan melihat pesan berhasil padahal tidak ada yang tersimpan.
        if (! Trail::spatialSupported()) {
            return $kembali->with('galat', 'Koneksi basis data ini tidak mendukung PostGIS, sehingga geometri tidak dapat disimpan.');
        }

        try {
            // Geometri, profil, dan jejak auditnya satu kesatuan: penolakan penjaga pada
            // profil tidak boleh meninggalkan geometri tertulis tanpa jejak audit.
            DB::transaction(function () use ($request, $trail, $draf, $audit): void {
                $trail->writeLineString('geometry', $draf['pratinjau']);

                // Profil ikut tersimpan karena ia berasal dari berkas yang sama dan tidak
                // dapat diturunkan kembali dari geometrinya yang dua dimensi.
                if ($draf['profil'] !== []) {
                    $trail->update(['elevation_profile' => $draf['profil']]);
                }

                // Asalnya dicatat apa adanya. Geometri dari OSM adalah data komunitas,
                // bukan data resmi pengelola, dan §60 menuntut bedanya tetap terbaca.
                $audit->record($request->user(), 'trail.geometry_imported', $trail, null, [
                    'titik' => count($draf['pratinjau']),
                    'panjang_km' => $draf['panjangKm'],
                    'asal' => $draf['asal'] ?? $draf['berkas'] ?? 'tidak tercatat',
                ]);
            });
        } catch (PublicationGateViolation $e) {
            return $kembali->with('penolakan', $e->curatorMessage());
        }

        $penyimpan->buang();

        return $kembali->with('status', 'Geometri jalur tersimpan.');
    }

    /**
     * @param  array{pratinjau: array<int, array{0: float, 1: float}>, panjangKm: float|null, tanjakan: array{gain: int, loss: int}|null, asal: string|null}  $draf
     * @param  callable(int|float, int=): string  $angka
     * @return array<string, mixed>
     */
    private function pratinjau(Trail $trail, array $draf, callable $angka): array
    {
        $tercatat = $trail->distance_km !== null ? (float) $trail->distance_km : null;
        $selisih = GeometryDraft::selisihPersen($draf['panjangKm'], $tercatat);
        $meter = fn (?int $nilai) => $nilai !== null ? $angka($nilai).' m' : 'belum diisi';

        return [
            'asal' => $draf['asal'],
            'titik' => $angka(count($draf['pratinjau'])),
            'panjang' => $angka((float) $draf['panjangKm'], 2).' km',
            'jarakTercatat' => $tercatat !== null ? $angka($tercatat, 2).' km' : 'belum diisi',
            // Selisih besar hampir selalu berarti berkasnya milik jalur lain. Kurator
            // tetap yang memutuskan, jadi ini peringatan, bukan penolakan.
            'selisih' => $selisih !== null && $selisih > self::SELISIH_MENCURIGAKAN ? $angka($selisih, 1) : null,
            'tanjakan' => $draf['tanjakan'] === null ? null : [
                'gain' => $angka($draf['tanjakan']['gain']).' m',
                'gainTercatat' => $meter($trail->elevation_gain_m),
                'loss' => $angka($draf['tanjakan']['loss']).' m',
                'lossTercatat' => $meter($trail->elevation_loss_m),
            ],
            // Garisnya sendiri, supaya kurator melihat bentuk dan letaknya sebelum
            // menyimpan, bukan hanya angkanya. Draf sudah berurutan bujur-lintang (GeoJSON).
            'garis' => ['type' => 'LineString', 'coordinates' => $draf['pratinjau']],
        ];
    }
}
