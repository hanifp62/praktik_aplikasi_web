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
 */
class SemanticTokenTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function tokenSemantik(): array
    {
        return [
            ['text-primary'], ['text-secondary'], ['text-muted'],
            ['canvas'], ['surface'], ['surface-sunken'],
            ['border-subtle'],
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

        foreach (['primary', 'secondary', 'muted', 'canvas', 'surface', 'sunken', 'subtle'] as $nama) {
            $this->assertStringContainsString($nama, $config, "Tailwind belum memetakan {$nama}.");
        }
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
