<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Aturan gerak.
 *
 * Aplikasi ini mode operasional: pendaki datang untuk menyelesaikan tugas, bukan untuk
 * menonton. Gerak karena itu hanya dipakai sebagai umpan balik perubahan keadaan, bukan
 * sebagai animasi masuk, dan satu-satunya momen yang diarahkan dipasang pada interaksi
 * yang paling sering diulang.
 */
class MotionTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Sebagian orang mual karena gerak di layar, dan sebagian lagi menyetel sistemnya
     * mengurangi gerak karena perangkatnya lambat. Keduanya menyatakan kebutuhan lewat
     * satu setelan yang sama, dan menghormatinya bukan pilihan (WCAG 2.3.3).
     *
     * Dipasang sekali di stylesheet, bukan sebagai motion-reduce: di tiap elemen: ada
     * dua puluh pemakaian transition yang sudah ada sebelum aturan ini, dan yang
     * ditulis besok tidak akan ingat menambahkannya.
     */
    public function test_reduced_motion_is_honoured_everywhere_at_once(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)/',
            $this->css(),
            'Setelan kurangi gerak harus dihormati sekali untuk seluruh aplikasi.'
        );
    }

    /**
     * Mengurangi gerak bukan berarti mematikan umpan balik. Durasi yang dipangkas
     * habis tetap menyampaikan bahwa sesuatu berubah, hanya tanpa perjalanan.
     */
    public function test_reducing_motion_shortens_it_rather_than_removing_the_feedback(): void
    {
        preg_match('/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)\s*\{(.*?)\n    \}/s', $this->css(), $blok);

        $this->assertNotEmpty($blok, 'Blok kurangi gerak tidak ditemukan.');
        $this->assertStringContainsString('animation-duration', $blok[1]);
        $this->assertStringContainsString('transition-duration', $blok[1]);
    }

    /**
     * Tidak ada yang boleh muncul dari ketiadaan.
     *
     * Elemen yang dimulai dari opacity nol lalu dinaikkan skrip akan tetap tidak
     * terlihat ketika skripnya gagal, dan di aplikasi ini skrip gagal justru pada
     * jaringan tempat pendaki paling membutuhkannya.
     */
    public function test_nothing_starts_invisible_waiting_for_a_script(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            // wire:loading menyembunyikan dengan style sebaris yang dibuka Livewire
            // setelah hidrasi, dan itu memang disengaja serta sudah diuji terpisah.
            if (preg_match('/class="[^"]*\bopacity-0\b/', $isi) && ! str_contains($isi, 'wire:loading')) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Mulai dari tak terlihat: '.implode(', ', $pelanggar));
    }
}
