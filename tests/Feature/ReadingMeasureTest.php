<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lebar baca dan pemenggalan.
 *
 * Satu max-w-prose di seluruh aplikasi dan nol text-wrap: balance. Pada layar lebar,
 * kalimat penjelas membentang penuh dan mata kehilangan awal baris berikutnya; judul dua
 * baris kerap menyisakan satu kata sendirian, dan itu terbaca sebagai kelalaian tata
 * letak, bukan sebagai pilihan.
 */
class ReadingMeasureTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    public function test_headings_are_balanced_across_their_lines(): void
    {
        $this->assertMatchesRegularExpression(
            '/h1[^{]*\{[^}]*text-wrap:\s*balance/s',
            $this->css(),
            'Judul harus diseimbangkan, bukan dipenggal seadanya.'
        );
    }

    /**
     * Paragraf memakai pretty, bukan balance: balance menyeimbangkan seluruh blok dan
     * peramban berhenti menerapkannya setelah beberapa baris, sedangkan pretty hanya
     * mencegah baris terakhir tersisa satu kata. Itu yang dibutuhkan prosa.
     */
    public function test_paragraphs_never_end_on_an_orphan(): void
    {
        $this->assertMatchesRegularExpression(
            '/(?<![\w-])p\s*\{[^}]*text-wrap:\s*pretty/s',
            $this->css(),
            'Paragraf harus memakai text-wrap: pretty.'
        );
    }

    /**
     * Pembatas lebar baca dipasang di komponen bersama, bukan diingat satu per satu.
     *
     * Sebelum ini hanya ada satu max-w-prose di seluruh aplikasi, dan satu-satunya
     * alasan angkanya satu adalah karena tidak ada tempat yang memasangnya untuk semua.
     */
    public function test_the_shared_components_bound_their_prose(): void
    {
        foreach (['page-header', 'card'] as $komponen) {
            $this->assertStringContainsString(
                'max-w-prose',
                File::get(resource_path("views/components/ui/{$komponen}.blade.php")),
                "Komponen {$komponen} membiarkan teksnya membentang penuh."
            );
        }
    }
}
