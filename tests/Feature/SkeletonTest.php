<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Bentuk saat memuat.
 *
 * Empat wire:loading di seluruh aplikasi, tanpa satu pun skeleton. Halaman yang
 * menjalankan mesin rekomendasi diam sampai isinya tiba.
 *
 * Bentuk yang sudah terlihat memberi tahu apa yang sedang ditunggu, sedangkan pemutar
 * lingkaran hanya memberi tahu bahwa sesuatu sedang terjadi. Perbedaannya paling terasa
 * di jaringan lambat, dan jaringan lambat adalah keadaan normal bagi pengguna ini.
 */
class SkeletonTest extends TestCase
{
    /**
     * Satu baris bentuk per baris yang sedang ditunggu, sebanyak yang diminta pemanggil.
     */
    public function test_a_skeleton_takes_the_shape_of_what_is_coming(): void
    {
        $kerangka = $this->kerangka();

        $this->assertMatchesRegularExpression('/Array\.from\(\{ length: rows \}, \(_, i\) => \(\s*<div key=\{i\} data-skeleton-row\b/', $kerangka);
        $this->assertStringContainsString('<Skeleton rows={4}', File::get(resource_path('js/pages/Recommendations/Show.tsx')));
    }

    /**
     * Pembaca layar tidak boleh membacakan kotak kosong. Yang diumumkan keadaannya,
     * bukan bentuknya: membacakan lima kotak kosong memperpanjang tanpa menyampaikan
     * apa pun.
     */
    public function test_the_skeleton_announces_state_not_shape(): void
    {
        $kerangka = $this->kerangka();

        $this->assertMatchesRegularExpression('/<div aria-hidden="true"[^>]*>\s*\{Array\.from/', $kerangka, 'Baris bentuk harus tersembunyi dari pembaca layar.');
        $this->assertMatchesRegularExpression('/<p aria-live="polite" className="sr-only">Memuat[^<]*<\/p>/', $kerangka, 'Keadaan memuat harus diumumkan sebagai kalimat.');
    }

    /**
     * Denyutnya berhenti ketika pengguna meminta gerak dikurangi (WCAG 2.3.3). Aturan
     * menyeluruh sudah ada di stylesheet, dan test ini menjaganya tetap berlaku di sini:
     * animate-pulse berdenyut tanpa henti, dan itu persis jenis gerak yang membuat
     * sebagian orang mual.
     */
    public function test_the_pulse_respects_reduced_motion(): void
    {
        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion:\s*reduce/',
            File::get(resource_path('css/app.css'))
        );
    }

    /**
     * Skeleton dipakai di halaman yang benar-benar menunggu, bukan ditaburkan.
     *
     * Halaman hasil rekomendasi memanggil mesin penilaian dan memuat jalur tambahan
     * atas permintaan; itu penantian yang terlihat pengguna.
     */
    public function test_the_results_page_shows_a_shape_while_it_waits(): void
    {
        $isi = File::get(resource_path('js/pages/Recommendations/Show.tsx'));

        // Halaman React: bentuknya tampil selama permintaan "tampilkan lagi" berjalan.
        $this->assertStringContainsString('const tampilkanLagi', $isi);
        $this->assertStringContainsString('onStart: () => setMemuat(true)', $isi);
        $this->assertStringContainsString('{memuat ? <Skeleton', $isi);
    }

    private function kerangka(): string
    {
        return File::get(resource_path('js/components/ui/Skeleton.tsx'));
    }
}
