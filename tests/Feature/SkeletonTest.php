<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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
    public function test_a_skeleton_takes_the_shape_of_what_is_coming(): void
    {
        $html = Blade::render('<x-ui.skeleton rows="3" />');

        $this->assertSame(3, substr_count($html, 'data-skeleton-row'));
    }

    /**
     * Pembaca layar tidak boleh membacakan kotak kosong. Yang diumumkan keadaannya,
     * bukan bentuknya: membacakan lima kotak kosong memperpanjang tanpa menyampaikan
     * apa pun.
     */
    public function test_the_skeleton_announces_state_not_shape(): void
    {
        $html = Blade::render('<x-ui.skeleton rows="2" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('Memuat', $html);
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
        $isi = File::get(resource_path('views/livewire/recommendations/recommendation-results.blade.php'));

        $this->assertStringContainsString('x-ui.skeleton', $isi);
        $this->assertStringContainsString('wire:target="tampilkanLagi"', $isi);
    }
}
