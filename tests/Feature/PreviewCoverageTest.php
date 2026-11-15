<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga cakupan pratinjau W9 (rencana induk W9 butir 1, "Klik-lewat setiap kontrol di setiap
 * layar"). Setiap komponen halaman React di resources/js/pages wajib punya pratinjau di
 * tests/Pratinjau dan baris di daftar periksa klik-lewat, supaya halaman baru tidak lagi lolos
 * dari inspeksi peramban.
 */
class PreviewCoverageTest extends TestCase
{
    public function test_every_react_page_has_a_preview_in_the_pratinjau_suite(): void
    {
        $sumberPratinjau = collect(File::files(base_path('tests/Pratinjau')))
            ->filter(fn ($berkas) => str_ends_with($berkas->getFilename(), 'Test.php'))
            ->map(fn ($berkas) => $berkas->getContents())
            ->implode("\n");

        $terlewat = [];

        foreach ($this->semuaHalamanReact() as $komponen) {
            if (! str_contains($sumberPratinjau, "'{$komponen}'")) {
                $terlewat[] = $komponen;
            }
        }

        $this->assertSame([], $terlewat, 'Halaman React tanpa pratinjau di tests/Pratinjau: '.implode(', ', $terlewat));
    }

    public function test_every_react_page_is_listed_in_the_w9_click_through_checklist(): void
    {
        $jalur = base_path('docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md');
        $this->assertFileExists($jalur);

        $daftar = File::get($jalur);
        $terlewat = [];

        foreach ($this->semuaHalamanReact() as $komponen) {
            if (! str_contains($daftar, "| `{$komponen}` |")) {
                $terlewat[] = $komponen;
            }
        }

        $this->assertSame([], $terlewat, 'Halaman React yang belum masuk daftar periksa klik-lewat: '.implode(', ', $terlewat));
    }

    /**
     * @return list<string>
     */
    private function semuaHalamanReact(): array
    {
        $akar = resource_path('js/pages');

        return collect(File::allFiles($akar))
            ->filter(fn ($berkas) => $berkas->getExtension() === 'tsx')
            ->map(fn ($berkas) => str_replace(['\\', '.tsx'], ['/', ''], $berkas->getRelativePathname()))
            ->sort()
            ->values()
            ->all();
    }
}
