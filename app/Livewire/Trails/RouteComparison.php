<?php

namespace App\Livewire\Trails;

use App\Models\Trail;
use App\Services\ConsiderationService;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * FR-06 route comparison. The system never declares a winner (PRD §33); it only lays out
 * the differences so the user can decide.
 *
 * Sumber kebenaran tunggal adalah timbangan tersimpan (trail_considerations). Tanpa
 * parameter URL -- yaitu jalan yang dipakai menu "Pertimbangkan" -- halaman ini
 * menampilkan timbangan pemilik akun, bukan "belum ada jalur yang dipilih" padahal
 * barisnya duduk di basis data. Parameter URL tetap didukung sebagai tautan yang dapat
 * dibagikan (dipakai baki di halaman Jelajah untuk mengirim pilihan yang baru saja
 * ditambah tanpa menunggu render berikutnya), dan karena tautan itu bisa dibuka orang
 * lain, kehadirannya membuat halaman ini HANYA membaca, tidak pernah menulis diam-diam
 * ke timbangan pemilik akun (lihat removeTrail()).
 */
#[Layout('layouts.app')]
#[Title('Bandingkan Jalur')]
class RouteComparison extends Component
{
    // Query string stays ?trails=1,2 while the property avoids colliding with the view's $trails.
    #[Url(as: 'trails')]
    public string $selection = '';

    /**
     * Menghapus dari sumber kebenaran tunggal, bukan hanya dari tampilan.
     *
     * Tanpa parameter URL, halaman ini menampilkan timbangan tersimpan (lihat render()),
     * jadi "Hapus dari perbandingan" di sini wajib menghapus dari sana juga --
     * menyunting $selection saja meninggalkan baki di halaman Jelajah tetap menyebut
     * jalur itu sedang ditimbang, dua sumber kebenaran untuk satu konsep, persis cacat
     * yang diperbaiki tugas ini.
     *
     * Dengan parameter URL eksplisit, pembaca sedang melihat tautan yang dibagikan --
     * isinya belum tentu timbangan miliknya sendiri -- jadi di jalur ini penghapusan
     * tetap hanya menyunting URL, tidak menyentuh basis data pemilik akun.
     */
    public function removeTrail(int $trailId, ConsiderationService $consideration): void
    {
        if ($this->selection === '') {
            // remove(), bukan toggle(): "Hapus" adalah penghapusan, bukan sakelar. Klik
            // ganda atau ulang-kirim yang menemukan barisnya sudah tidak ada tidak boleh
            // menambahkannya kembali -- lihat docblock ConsiderationService::remove().
            $consideration->remove(auth()->user(), $trailId);

            return;
        }

        $ids = array_values(array_diff($this->trailIds(), [$trailId]));
        $this->selection = implode(',', $ids);
    }

    /**
     * @return array<int, int>
     */
    private function trailIds(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $this->selection))));
    }

    public function render(OfficialStatusService $officialStatus, ConsiderationService $consideration)
    {
        $ids = $this->trailIds();

        $trails = $ids === []
            ? $consideration->forUser(auth()->user())
            : Trail::query()->published()->whereIn('id', $ids)->with('mountain')->get();

        // Kecocokan dinilai hanya untuk yang profilnya cukup, sama seperti halaman
        // jelajah: menilai tanpa profil menghasilkan label yang terlihat pasti dan
        // berdasar ketiadaan, dan itu persis yang dilarang §91.
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? app(TrailFitService::class)->forTrails(auth()->user(), $trails)
            : [];

        return view('livewire.trails.route-comparison', [
            'trails' => $trails,
            // Satu panggilan batch, bukan effectiveStatusForTrail() sekali per jalur di
            // dalam loop: yang terakhir menambah dua query tak ter-cache per jalur, dan
            // TrailFitService::forTrails() di atas SUDAH memanggil batch yang sama secara
            // internal, jadi loop per-jalur ini murni pekerjaan berulang yang tumbuh
            // linear terhadap jumlah jalur pada halaman yang sama.
            'statuses' => $officialStatus->effectiveStatusesForTrails($trails),
            'ringkasanFit' => $ringkasanFit,
        ]);
    }
}
