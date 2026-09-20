<?php

namespace Tests\Feature;

use App\Enums\TaskOutcome;
use App\Enums\UsabilitySessionKind;
use App\Models\UsabilitySession;
use App\Services\UsabilityStudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perhitungan di balik protokol uji kegunaan.
 *
 * Angka-angka ini yang nanti dikutip untuk menyatakan sistem sudah diuji, jadi rumusnya
 * diperiksa terhadap nilai yang sudah diketahui, bukan terhadap dirinya sendiri.
 */
class UsabilityStudyServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): UsabilityStudyService
    {
        return app(UsabilityStudyService::class);
    }

    /**
     * Menjawab 5 pada semua pernyataan menghasilkan 50, bukan 100.
     *
     * Ini justru bukti selang-seling positif dan negatifnya bekerja: responden yang
     * mencentang satu kolom lurus ke bawah mendapat nilai tengah, bukan nilai sempurna.
     */
    public function test_answering_five_to_everything_lands_in_the_middle(): void
    {
        $this->assertSame(50.0, $this->service()->susScore(array_fill_keys(range(1, 10), 5)));
    }

    public function test_answering_three_to_everything_lands_in_the_middle_too(): void
    {
        $this->assertSame(50.0, $this->service()->susScore(array_fill_keys(range(1, 10), 3)));
    }

    /**
     * Nilai sempurna menuntut setuju penuh pada pernyataan positif dan tidak setuju
     * penuh pada pernyataan negatif.
     */
    public function test_the_perfect_pattern_scores_one_hundred(): void
    {
        $jawaban = [];

        foreach (range(1, 10) as $nomor) {
            $jawaban[$nomor] = $nomor % 2 === 1 ? 5 : 1;
        }

        $this->assertSame(100.0, $this->service()->susScore($jawaban));
        $this->assertSame(0.0, $this->service()->susScore(array_map(fn ($n) => 6 - $n, $jawaban)));
    }

    public function test_an_incomplete_or_out_of_range_answer_sheet_has_no_score(): void
    {
        $lengkap = array_fill_keys(range(1, 10), 4);

        $this->assertNull($this->service()->susScore(array_slice($lengkap, 0, 9, true)));
        $this->assertNull($this->service()->susScore(['1' => 9] + $lengkap));
        $this->assertNull($this->service()->susScore(array_fill_keys(range(1, 10), 0)));
    }

    /**
     * 68 adalah rata-rata seluruh produk yang pernah diukur, jadi ia bernilai C.
     * Membacanya sebagai nilai ujian membuat produk yang persis rata-rata terlihat
     * nyaris gagal, dan itu memicu perbaikan pada hal yang tidak rusak.
     */
    public function test_the_average_score_grades_as_average_not_as_nearly_failing(): void
    {
        $this->assertSame('C', $this->service()->grade(68.0));
        $this->assertSame('A+', $this->service()->grade(85.0));
        $this->assertSame('D', $this->service()->grade(55.0));
        $this->assertSame('F', $this->service()->grade(45.0));
    }

    /**
     * Kurva Nielsen dan Landauer dengan peluang temu 31%. Dari sinilah angka "lima
     * peserta" berasal, dan test ini yang menahannya tetap berarti angka, bukan slogan.
     */
    public function test_the_discovery_curve_matches_the_published_figures(): void
    {
        $service = $this->service();

        $this->assertSame(0.0, $service->discoveryRate(0));
        $this->assertEqualsWithDelta(0.31, $service->discoveryRate(1), 0.001);
        $this->assertEqualsWithDelta(0.843, $service->discoveryRate(5), 0.001);
        $this->assertEqualsWithDelta(0.9754, $service->discoveryRate(10), 0.001);
    }

    /**
     * Berhasil dengan kesulitan bernilai setengah. Membulatkannya menjadi berhasil
     * menghapus persis masalah yang sedang dicari studi ini.
     */
    public function test_a_struggled_task_is_worth_half(): void
    {
        $this->buatSesi('P01', ['T3' => TaskOutcome::SUCCESS]);
        $this->buatSesi('P02', ['T3' => TaskOutcome::STRUGGLED]);
        $this->buatSesi('P03', ['T3' => TaskOutcome::FAILED]);

        $ringkas = $this->service()->summary();

        $this->assertSame(3, $ringkas['tugas']['T3']['diamati']);
        $this->assertSame(0.5, $ringkas['tugas']['T3']['tingkat_berhasil']);
        $this->assertSame(1, $ringkas['tugas']['T3']['gagal']);
        $this->assertTrue($ringkas['tugas']['T3']['inti']);
    }

    public function test_the_summary_says_how_many_participants_are_still_needed(): void
    {
        $this->buatSesi('P01', ['T1' => TaskOutcome::SUCCESS]);
        $this->buatSesi('P02', ['T1' => TaskOutcome::SUCCESS]);

        $ringkas = $this->service()->summary();

        $this->assertSame(2, $ringkas['peserta_tugas']);
        $this->assertSame(3, $ringkas['peserta_kurang']);
        $this->assertEqualsWithDelta(0.5239, $ringkas['cakupan_masalah'], 0.001);
    }

    /**
     * Rata-rata SUS dari sedikit responden tetap ditampilkan, tetapi ditandai tidak
     * dapat diandalkan. Menyembunyikannya membuat peneliti menghitung sendiri di luar
     * sistem, tanpa penanda apa pun yang ikut terbawa.
     */
    public function test_a_thin_sus_sample_is_averaged_but_flagged(): void
    {
        foreach (['P01', 'P02'] as $kode) {
            UsabilitySession::create([
                'participant_code' => $kode,
                'kind' => UsabilitySessionKind::SUS_ONLY->value,
                'conducted_at' => now(),
                'sus_answers' => array_fill_keys(range(1, 10), 3),
                'sus_score' => 50.0,
            ]);
        }

        $ringkas = $this->service()->summary();

        $this->assertSame(50.0, $ringkas['sus_rata']);
        $this->assertSame('F', $ringkas['sus_grade']);
        $this->assertFalse($ringkas['sus_dapat_diandalkan']);
        $this->assertSame(10, $ringkas['responden_sus_kurang']);
    }

    public function test_an_empty_study_reports_nothing_rather_than_zero(): void
    {
        $ringkas = $this->service()->summary();

        $this->assertNull($ringkas['sus_rata'], 'Belum ada responden bukan berarti skornya nol.');
        $this->assertNull($ringkas['sus_grade']);
        $this->assertNull($ringkas['tugas']['T1']['tingkat_berhasil']);
    }

    /**
     * @param  array<string, TaskOutcome>  $hasil
     */
    private function buatSesi(string $kode, array $hasil): UsabilitySession
    {
        return UsabilitySession::create([
            'participant_code' => $kode,
            'kind' => UsabilitySessionKind::TASK->value,
            'conducted_at' => now(),
            'task_results' => collect($hasil)
                ->map(fn (TaskOutcome $o) => ['outcome' => $o->value])
                ->all(),
        ]);
    }
}
