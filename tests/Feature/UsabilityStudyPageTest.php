<?php

namespace Tests\Feature;

use App\Enums\TaskOutcome;
use App\Enums\UserRole;
use App\Livewire\Admin\UsabilityStudy;
use App\Models\UsabilitySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_a_session_is_recorded_with_its_score_computed(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UsabilityStudy::class)
            ->set('participant_code', 'P01')
            ->set('kind', 'TASK')
            ->set('tasks.T3.outcome', TaskOutcome::STRUGGLED->value)
            ->set('tasks.T3.seconds', 95)
            ->set('tasks.T3.note', 'Mencari alasannya di halaman jalur, bukan di hasil.')
            ->set('sus', $this->susSempurna())
            ->call('save')
            ->assertHasNoErrors();

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
        Livewire::actingAs($this->admin())
            ->test(UsabilityStudy::class)
            ->set('participant_code', 'P01')
            ->set('tasks.T1.outcome', TaskOutcome::SUCCESS->value)
            ->call('save')
            ->assertHasNoErrors();

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
        Livewire::actingAs($this->admin())
            ->test(UsabilityStudy::class)
            ->set('participant_code', 'P01')
            ->set('tasks.T1.outcome', TaskOutcome::SUCCESS->value)
            ->set('sus.1', 5)
            ->set('sus.2', 2)
            ->call('save')
            ->assertHasNoErrors();

        $sesi = UsabilitySession::firstOrFail();

        $this->assertNull($sesi->sus_score);
        $this->assertNotEmpty($sesi->task_results);
    }

    public function test_an_answer_outside_the_scale_is_refused(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UsabilityStudy::class)
            ->set('participant_code', 'P01')
            ->set('sus.1', 9)
            ->call('save')
            ->assertHasErrors('sus.1');
    }

    public function test_the_page_says_how_many_participants_are_still_needed(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UsabilityStudy::class)
            ->assertSee('5 peserta lagi');
    }

    /**
     * Catatan studi berisi kutipan peserta dan pengamatan tentang di mana mereka
     * tersesat. Itu bahan penelitian tentang orang, bukan isi aplikasi.
     */
    public function test_a_hiker_cannot_read_the_research_notes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.usability'))
            ->assertForbidden();
    }

    public function test_an_admin_can_open_it(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.usability'))
            ->assertOk()
            ->assertSee('Studi Kegunaan');
    }
}
