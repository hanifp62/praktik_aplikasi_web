<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\OfficialStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Sasaran status resmi ditunjuk dengan jenis dan id mentah. Salah ketik satu angka dulu
 * menghasilkan halaman 404 alih-alih galat pada isiannya, sehingga pekerjaan yang sudah diisi
 * pada form itu hilang dan tidak ada yang menunjukkan isian mana yang keliru (WCAG 3.3.1).
 *
 * Diperbarui W8 (ruling W8-R16): pencatatan pindah dari halaman admin ke meja kurator, jadi niat
 * yang sama kini dijaga pada formulir meja kurator (SimpanCatatanStatusRequest). Pemeriksaannya
 * tetap mengikuti jenis sasaran: id gunung yang sah bukan id jalur yang sah.
 */
class OfficialStatusTargetTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(string $sasaran): array
    {
        return [
            'sasaran' => $sasaran,
            'otoritas' => $this->balai->id,
            'status' => 'OPEN',
            'jenis_bukti' => 'OFFICIAL_PAGE',
            'mulai_berlaku' => now()->toDateString(),
            'cara_berakhir' => 'NOT_STATED',
            'ringkasan' => 'Pendakian dibuka.',
        ];
    }

    public function test_a_target_that_does_not_exist_is_reported_on_the_field(): void
    {
        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('MOUNTAIN:999999'))
            ->assertSessionHasErrors('sasaran');
    }

    public function test_the_check_follows_the_selected_kind_of_target(): void
    {
        $gunungLain = Mountain::factory()->create();

        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('TRAIL:'.$gunungLain->id))
            ->assertSessionHasErrors('sasaran');
    }

    public function test_a_real_target_still_saves(): void
    {
        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('MOUNTAIN:'.$this->gunung->id))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->gunung->id, OfficialStatus::sole()->statusable_id);
    }
}
