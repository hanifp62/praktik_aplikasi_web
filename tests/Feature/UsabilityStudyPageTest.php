<?php

namespace Tests\Feature;

use App\Enums\TaskOutcome;
use App\Enums\UserRole;
use App\Models\UsabilitySession;
use App\Models\User;
use App\Services\UsabilityStudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman pencatat studi kegunaan.
 *
 * Yang diuji di sini bukan tampilannya melainkan janjinya: pengamatan tersimpan utuh,
 * skor dihitung sendiri, lembar setengah terisi tidak berpura-pura menjadi skor, dan
 * catatan tentang orang tidak terbuka bagi siapa pun selain admin.
 */
class UsabilityStudyPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    /**
     * @return array<int, int>
     */
    private function susSempurna(): array
    {
        $jawaban = [];

        foreach (range(1, 10) as $nomor) {
            $jawaban[$nomor] = $nomor % 2 === 1 ? 5 : 1;
        }

        return $jawaban;
    }

    /**
     * @param  array<string, mixed>  $isian
     */
    private function simpan(array $isian): TestResponse
    {
        return $this->actingAs($this->admin())->post(route('admin.usability.store'), array_merge([
            'participant_code' => 'P01',
            'kind' => 'TASK',
        ], $isian));
    }

    public function test_a_session_is_recorded_with_its_score_computed(): void
    {
        $this->simpan([
            'tasks' => ['T3' => [
                'outcome' => TaskOutcome::STRUGGLED->value,
                'seconds' => 95,
                'note' => 'Mencari alasannya di halaman jalur, bukan di hasil.',
            ]],
            'sus' => $this->susSempurna(),
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.usability'))
            ->assertSessionHas('status', 'Sesi tersimpan. Skor SUS peserta ini 100.0.');

        $sesi = UsabilitySession::firstOrFail();

        $this->assertSame('P01', $sesi->participant_code);
        $this->assertSame(100.0, $sesi->sus_score);
        $this->assertSame(TaskOutcome::STRUGGLED->value, $sesi->task_results['T3']['outcome']);
        $this->assertSame(95, $sesi->task_results['T3']['seconds']);
    }

    /**
     * Tugas yang tidak sempat dijalankan bukan tugas yang gagal. Menyimpannya sebagai
     * baris kosong membuatnya ikut terhitung sebagai pengamatan dan menurunkan tingkat
     * keberhasilan tanpa ada yang benar-benar diamati.
     */
    public function test_tasks_that_were_not_run_are_not_stored_as_observations(): void
    {
        $this->simpan([
            'tasks' => [
                'T1' => ['outcome' => TaskOutcome::SUCCESS->value, 'seconds' => '', 'note' => ''],
                'T2' => ['outcome' => '', 'seconds' => '', 'note' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $sesi = UsabilitySession::firstOrFail();

        $this->assertArrayHasKey('T1', $sesi->task_results);
        $this->assertArrayNotHasKey('T2', $sesi->task_results);
    }

    /**
     * Responden yang berhenti di tengah kuesioner tetap menyumbang pengamatan tugas.
     * Yang tidak boleh terjadi adalah lembar setengah terisi menghasilkan angka.
     */
    public function test_an_incomplete_questionnaire_is_kept_but_scores_nothing(): void
    {
        $this->simpan([
            'tasks' => ['T1' => ['outcome' => TaskOutcome::SUCCESS->value]],
            'sus' => [1 => 5, 2 => 2, 3 => '', 4 => ''],
        ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Sesi tersimpan. Jawaban SUS belum lengkap, jadi sesi ini belum menyumbang skor.');

        $sesi = UsabilitySession::firstOrFail();

        $this->assertNull($sesi->sus_score);
        $this->assertNull($sesi->sus_answers);
        $this->assertNotEmpty($sesi->task_results);
    }

    public function test_an_answer_outside_the_scale_is_refused(): void
    {
        $this->simpan(['sus' => [1 => 9]])->assertSessionHasErrors('sus.1');

        $this->assertSame(0, UsabilitySession::count());
    }

    /**
     * Berapa peserta lagi yang dibutuhkan dihitung server dari ambang layanan; halaman
     * hanya menampilkannya. Ambang SUS (12 responden) tidak diubah di sini.
     */
    public function test_the_page_says_how_many_participants_are_still_needed(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.usability'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ringkasan.pesertaKurang', UsabilityStudyService::TARGET_PESERTA_TUGAS)
                ->where('ringkasan.respondenSusKurang', UsabilityStudyService::MINIMUM_RESPONDEN_SUS)
                ->where('ringkasan.susDapatDiandalkan', false)
                ->where('kodeBerikutnya', 'P01'));
    }

    /**
     * Catatan studi berisi kutipan peserta dan pengamatan tentang di mana mereka
     * tersesat. Itu bahan penelitian tentang orang, bukan isi aplikasi.
     */
    public function test_a_hiker_cannot_read_the_research_notes(): void
    {
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('admin.usability'))->assertForbidden();
        $this->actingAs($pendaki)
            ->post(route('admin.usability.store'), ['participant_code' => 'P01', 'kind' => 'TASK'])
            ->assertForbidden();

        $this->assertSame(0, UsabilitySession::count());
    }

    public function test_an_admin_can_open_it(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.usability'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Usability')
                ->where('title', 'Studi Kegunaan')
                ->has('daftarTugas', count(UsabilityStudyService::TUGAS))
                ->has('pernyataanSus', 10));
    }
}
