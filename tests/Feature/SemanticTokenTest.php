<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Lapisan semantik.
 *
 * Sebelum ini ada 25 token dan hanya satu yang menyebut peran, sementara 597 abu mentah
 * Tailwind dipakai langsung di view dengan empat abu berbeda untuk teks yang saling
 * tertukar. Tanpa lapisan yang menyebut peran, setiap layar memutuskan sendiri dan
 * keputusannya selalu default.
 *
 * Diperluas gelombang 1 v3 (spec v3 bagian 8.1 dan 8.4): token peran baru untuk teks di
 * atas isian merek, permukaan kebalikan, label kecocokan, status akses, aksen pos, tirai,
 * dan bayangan, serta skala radius yang dibaca dari token. Pemeriksaan Tailwind kini
 * mencari pemetaan token('...') per peran, bukan sekadar kata yang mirip di berkasnya.
 */
class SemanticTokenTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function tokenSemantik(): array
    {
        return [
            ['text-primary'], ['text-secondary'], ['text-muted'], ['text-inverse'],
            ['canvas'], ['surface'], ['surface-sunken'], ['surface-inverse'],
            ['border-subtle'], ['control-border'],
            ['brand-ink'], ['on-brand'],
            ['fit-cocok'], ['fit-cocok-teks'], ['fit-perlu'], ['fit-perlu-teks'],
            ['fit-kurang'], ['fit-kurang-teks'], ['fit-belum'], ['fit-belum-teks'],
            ['status-buka'], ['status-dibatasi'], ['status-tutup'], ['status-belum'],
            ['accent-pos'], ['scrim'], ['bayangan'],
        ];
    }

    #[DataProvider('tokenSemantik')]
    public function test_the_semantic_token_is_defined(string $nama): void
    {
        $this->assertMatchesRegularExpression(
            '/--'.preg_quote($nama, '/').':\s*\d+\s+\d+\s+\d+;/',
            File::get(resource_path('css/app.css')),
            "Token --{$nama} belum ada."
        );
    }

    public function test_tailwind_exposes_every_semantic_token(): void
    {
        $config = File::get(base_path('tailwind.config.js'));

        foreach ([
            'text-primary', 'text-secondary', 'text-muted', 'text-inverse',
            'canvas', 'surface', 'surface-sunken', 'surface-inverse',
            'border-subtle', 'control-border', 'brand-ink', 'on-brand',
            'fit-cocok', 'fit-cocok-teks', 'fit-perlu', 'fit-perlu-teks',
            'fit-kurang', 'fit-kurang-teks', 'fit-belum', 'fit-belum-teks',
            'status-buka', 'status-dibatasi', 'status-tutup', 'status-belum', 'accent-pos',
        ] as $nama) {
            $this->assertStringContainsString("token('{$nama}')", $config, "Tailwind belum memetakan {$nama}.");
        }

        $this->assertStringContainsString('rgb(var(--bayangan) / 0.18)', $config, 'shadow-overlay harus membaca token bayangan.');
    }

    /**
     * Skala radius spec v3 bagian 8.4. rounded-lg dipetakan ke token kartu karena peran
     * rounded-lg di DESIGN.md adalah kartu dan panel; kartu yang ada langsung mendapat
     * radius prototype tanpa disunting satu per satu.
     */
    public function test_the_radius_scale_follows_the_spec(): void
    {
        $css = File::get(resource_path('css/app.css'));
        $config = File::get(base_path('tailwind.config.js'));

        foreach ([
            'kecil' => '0.625rem',
            'control' => '0.75rem',
            'tombol' => '1rem',
            'kartu' => '1.125rem',
            'utama' => '1.375rem',
            'lembar' => '1.625rem',
            'lencana' => '0.375rem',
        ] as $nama => $nilai) {
            $this->assertMatchesRegularExpression('/--radius-'.$nama.':\s*'.preg_quote($nilai, '/').';/', $css, "Radius {$nama} bukan {$nilai}.");
            $this->assertMatchesRegularExpression('/\b'.$nama.":\s*'var\(--radius-".$nama."\)'/", $config, "Tailwind belum memetakan rounded-{$nama}.");
        }

        $this->assertMatchesRegularExpression("/\blg:\s*'var\(--radius-kartu\)'/", $config, 'rounded-lg adalah peran kartu.');
    }

    /**
     * Abu mentah Tailwind tidak boleh dipakai langsung lagi.
     *
     * Selama ia boleh, layar berikutnya yang ditulis siapa pun akan mengarang abunya
     * sendiri, dan lapisan semantik menjadi lapisan yang dilewati.
     */
    public function test_no_view_reaches_past_the_semantic_layer(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            // Komentar Blade dilewati: nama kelas lama sering disebut di sana justru
            // untuk menjelaskan mengapa ia diganti.
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border)-gray-\d{2,3}\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Abu mentah masih dipakai di: '.implode(', ', $pelanggar));
    }
}
