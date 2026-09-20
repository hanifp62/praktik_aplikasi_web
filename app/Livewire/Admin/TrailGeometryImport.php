<?php

namespace App\Livewire\Admin;

use App\Models\Trail;
use App\Services\AuditLogService;
use App\Support\GpxTrack;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * PRD §65 dan §109: memasukkan geometri jalur lewat berkas GPX.
 *
 * Sebelum ini writeLineString() tidak pernah dipanggil dari mana pun, sehingga geometri
 * hanya bisa masuk lewat SQL manual. Itulah sebabnya tidak satu pun jalur punya geometri,
 * peta tidak dapat menggambar apa pun, dan gerbang §110 menolak seluruh jalur.
 */
#[Layout('layouts.app')]
#[Title('Impor Geometri Jalur')]
class TrailGeometryImport extends Component
{
    use WithFileUploads;

    public Trail $trail;

    public $berkas;

    /** @var array<int, array{0: float, 1: float}> */
    public array $pratinjau = [];

    public ?float $panjangKm = null;

    public ?string $galat = null;

    public function mount(Trail $trail): void
    {
        $this->authorize('update', $trail);

        $this->trail = $trail;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            // Ekstensi hanya penyaring pertama. Penjaga sebenarnya ada di GpxTrack, yang
            // menolak apa pun yang akar XML-nya bukan <gpx>.
            'berkas' => ['required', 'file', 'extensions:gpx,xml', 'max:'.config('hiking.uploads.gpx_max_kb')],
        ];
    }

    /**
     * Berkas dibaca dan diperiksa sebelum apa pun ditulis, supaya kurator melihat jumlah
     * titik dan panjang jejaknya dulu dan sempat membatalkan berkas yang keliru.
     */
    public function updatedBerkas(): void
    {
        $this->reset(['pratinjau', 'panjangKm', 'galat']);
        $this->validate();

        try {
            $this->pratinjau = GpxTrack::fromXml(file_get_contents($this->berkas->getRealPath()));
            $this->panjangKm = round(GpxTrack::lengthKm($this->pratinjau), 2);
        } catch (RuntimeException $e) {
            $this->galat = $e->getMessage();
        }
    }

    public function simpan(AuditLogService $audit): void
    {
        $this->authorize('update', $this->trail);

        if ($this->pratinjau === []) {
            $this->galat = 'Belum ada jejak yang siap disimpan.';

            return;
        }

        // writeLineString() diam saja pada koneksi tanpa PostGIS, jadi tanpa penjagaan ini
        // kurator akan melihat pesan berhasil padahal tidak ada yang tersimpan.
        if (! Trail::spatialSupported()) {
            $this->galat = 'Koneksi basis data ini tidak mendukung PostGIS, sehingga geometri tidak dapat disimpan.';

            return;
        }

        $this->trail->writeLineString('geometry', $this->pratinjau);

        $audit->record(auth()->user(), 'trail.geometry_imported', $this->trail, null, [
            'titik' => count($this->pratinjau),
            'panjang_km' => $this->panjangKm,
            'berkas' => $this->berkas->getClientOriginalName(),
        ]);

        $this->reset(['berkas', 'pratinjau', 'panjangKm']);

        session()->flash('status', 'Geometri jalur tersimpan.');
    }

    /**
     * Selisih panjang jejak terhadap jarak yang sudah tercatat. Null bila salah satunya
     * belum ada, sehingga tampilan tidak mengarang perbandingan.
     */
    public function selisihPersen(): ?float
    {
        if ($this->panjangKm === null || $this->trail->distance_km === null) {
            return null;
        }

        $tercatat = (float) $this->trail->distance_km;

        if ($tercatat <= 0) {
            return null;
        }

        return round(abs($this->panjangKm - $tercatat) / $tercatat * 100, 1);
    }

    public function render()
    {
        return view('livewire.admin.trail-geometry-import', [
            'geometriTersimpan' => $this->trail->readGeoJson('geometry'),
            'selisih' => $this->selisihPersen(),
        ]);
    }
}
