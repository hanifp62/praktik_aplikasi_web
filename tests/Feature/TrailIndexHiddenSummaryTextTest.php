<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Temuan audit E2E M-2 (ruling audit-fix-B BF-2): sesudah "Tampilkan juga" diaktifkan,
 * jalur yang tidak lolos saringan tetap tampil di daftar, tetapi ringkasan jumlah terus
 * menyebutnya "disembunyikan" padahal sedang tampil.
 *
 * Halaman React dirender di klien, di luar jangkauan test HTTP (lihat SumberReact), jadi
 * invariannya dikunci pada sumbernya: kedua kalimat ringkasan (di atas daftar dan di baris
 * toggle) wajib bercabang pada filter.tampilkanSemua, bukan menulis "disembunyikan" tanpa
 * syarat untuk jalur yang justru sedang ditampilkan karena "Tampilkan juga".
 */
class TrailIndexHiddenSummaryTextTest extends TestCase
{
    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Trails/Index.tsx'));
    }

    public function test_the_summary_line_branches_on_show_also_instead_of_always_saying_hidden(): void
    {
        $isi = $this->sumber();

        // Spasi dan baris tidak dikunci persis (bisa berubah lewat Prettier); yang dikunci
        // adalah urutan percabangannya: jumlahTersembunyi > 0, lalu filter.tampilkanSemua
        // memilih antara kalimat "tampil karena Tampilkan juga" dan "disembunyikan".
        $this->assertMatchesRegularExpression(
            '#jumlahTersembunyi > 0\s*\?\s*filter\.tampilkanSemua\s*\?\s*`, termasuk \$\{jumlahTersembunyi\} tampil karena Tampilkan juga meski tidak lolos \$\{olehSaringan\}`\s*:\s*`, \$\{jumlahTersembunyi\} disembunyikan \$\{olehSaringan\}`#',
            $isi,
            'Ringkasan jumlah jalur di atas daftar harus membedakan keadaan "Tampilkan juga" dari keadaan disembunyikan (M-2).'
        );
    }

    public function test_the_toggle_row_sentence_branches_on_show_also_instead_of_always_saying_hidden(): void
    {
        $isi = $this->sumber();

        $this->assertStringContainsString(
            "{filter.tampilkanSemua ? 'tampil karena Anda memilih Tampilkan juga, meski tidak lolos' : 'disembunyikan'}",
            $isi,
            'Baris toggle "Tampilkan juga" harus berhenti menulis "disembunyikan" untuk jalur yang sedang ia tampilkan (M-2).'
        );
    }
}
