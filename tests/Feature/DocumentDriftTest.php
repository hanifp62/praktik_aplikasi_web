<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Dokumen tim tidak lagi memuat drift yang dicatat spec v3 bagian 3.6 (W9).
 *
 * docs/ARCHITECTURE.md dibaca anggota baru untuk tahu berkas mana yang disentuh; sampai W9 ia masih
 * menunjuk komponen Livewire, `resources/js/app.js`, dan menu admin Blade yang sudah tidak ada.
 * PRODUCT.md dibaca alat desain dan masih menjanjikan "layak berangkat" (R-014) serta perbandingan
 * lima jalur (DK-05). Setiap jalur repo di dalam backtick ARCHITECTURE kini harus ada, kecuali yang
 * memang dicatat sebagai belum ada.
 */
class DocumentDriftTest extends TestCase
{
    /**
     * Jalur yang disebut dokumen justru karena belum ada.
     *
     * @var array<string, string>
     */
    private const BELUM_ADA = [
        'tests/e2e' => 'Runner end-to-end menunggu izin Playwright (Q17).',
    ];

    public function test_every_repo_path_in_the_code_map_exists(): void
    {
        preg_match_all('/`([^`\s]+)`/', File::get(base_path('docs/ARCHITECTURE.md')), $temuan);
        $hilang = [];

        foreach (array_unique($temuan[1]) as $jalur) {
            $jalur = rtrim(explode('::', $jalur)[0], '.,;:');

            if (! preg_match('#^(app|resources|config|routes|tests|database|docs|bootstrap|public|lang)/#', $jalur) || preg_match('/[*{}<>]/', $jalur) || array_key_exists($jalur, self::BELUM_ADA)) {
                continue;
            }

            if (! file_exists(base_path($jalur))) {
                $hilang[] = $jalur;
            }
        }

        $this->assertSame([], $hilang);
    }

    public function test_the_code_map_no_longer_describes_a_livewire_frontend(): void
    {
        $dokumen = File::get(base_path('docs/ARCHITECTURE.md'));

        $this->assertStringNotContainsString('sedang dimigrasi', $dokumen);
        $this->assertStringNotContainsString('Setiap komponen Livewire', $dokumen);
    }

    public function test_the_product_summary_keeps_readiness_as_decision_support_and_compares_three(): void
    {
        $produk = File::get(base_path('PRODUCT.md'));

        $this->assertStringNotContainsString('layak berangkat', $produk);
        $this->assertStringNotContainsString('maksimum lima', str_replace("\n", ' ', $produk));
        $this->assertStringNotContainsString('Pertimbangkan', $produk);
    }
}
