<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/**
 * UI harus dapat diubah dari satu tempat. Palet merek hidup sebagai design token
 * dan hanya boleh disebut oleh komponen di resources/views/components; view fitur
 * memakai komponen tersebut. Test ini menahan agar gaya tidak kembali tersebar.
 */
class DesignSystemTest extends TestCase
{
    public function test_no_view_references_a_raw_palette_colour(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            if (preg_match('/\b(emerald|teal|green)-\d{2,3}\b/', $file->getContents(), $match)) {
                $offenders[] = $file->getRelativePathname().' → '.$match[0];
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Palet mentah harus diganti token merek (brand-*):\n".implode("\n", $offenders)
        );
    }

    /**
     * Yang dilarang bukan pemakaian token merek, token justru boleh dipakai bebas,
     * itu gunanya. Yang dilarang adalah menulis ulang resep tombol primer
     * (bg-brand-600 berpasangan dengan hover:bg-brand-700) di luar komponen,
     * karena resep itulah yang sebelumnya terduplikasi di 18 view.
     */
    public function test_no_view_reimplements_the_primary_button(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            if ($this->isComponent($file->getRelativePathname())) {
                continue;
            }

            $contents = $file->getContents();

            if (str_contains($contents, 'bg-brand-600') && str_contains($contents, 'hover:bg-brand-700')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "View berikut menulis ulang tombol primer, gunakan <x-ui.button>:\n".implode("\n", $offenders)
        );
    }

    public function test_status_messages_go_through_the_alert_component(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            if ($this->isComponent($file->getRelativePathname())) {
                continue;
            }

            if (preg_match('/<div[^>]*role="(status|alert)"/', $file->getContents())) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "View berikut merakit blok pesan sendiri, gunakan <x-ui.alert>:\n".implode("\n", $offenders)
        );
    }

    public function test_brand_tokens_are_declared_once_in_the_stylesheet(): void
    {
        $css = File::get(resource_path('css/app.css'));

        foreach (['--brand-600', '--brand-700', '--warn-100', '--danger-600', '--radius-control'] as $token) {
            $this->assertStringContainsString($token, $css, "Token {$token} belum dideklarasikan.");
        }
    }

    public function test_the_button_component_meets_touch_target_and_focus_requirements(): void
    {
        $button = File::get(resource_path('views/components/ui/button.blade.php'));

        // WCAG 2.2: ukuran target sentuh dan focus yang terlihat (PRD §87).
        $this->assertStringContainsString('min-h-11', $button);
        $this->assertStringContainsString('focus-visible:ring', $button);
    }

    /**
     * Jempol tidak ikut mengecil ketika tombolnya terlihat lebih kecil. WCAG 2.2
     * menuntut area sentuh sekitar 44px, sedangkan tombol kecil buatan tangan di
     * halaman admin sebelumnya hanya sekitar 24px.
     */
    public function test_no_view_builds_an_undersized_touch_target(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            if ($this->isComponent($file->getRelativePathname())) {
                continue;
            }

            if (preg_match('/class="[^"]*(?:px-2 py-1 text-xs|px-3 py-1\.5 text-xs)[^"]*"/', $file->getContents())) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Tombol kecil harus lewat <x-ui.button size=\"sm\">, yang tetap memberi area 44px:\n"
                .implode("\n", $offenders)
        );
    }

    public function test_the_small_button_size_keeps_the_full_touch_area(): void
    {
        $button = File::get(resource_path('views/components/ui/button.blade.php'));

        // min-h-11 berada di kelas dasar, bukan di dalam cabang ukuran, sehingga
        // ukuran "sm" tidak dapat menghilangkannya.
        $this->assertMatchesRegularExpression('/\$base = .*min-h-11/s', $button);
    }

    /**
     * @return array<int, SplFileInfo>
     */
    private function bladeFiles(): array
    {
        return array_filter(
            File::allFiles(resource_path('views')),
            fn ($file) => str_ends_with($file->getFilename(), '.blade.php')
        );
    }

    private function isComponent(string $relativePath): bool
    {
        return str_contains($relativePath, 'components'.DIRECTORY_SEPARATOR)
            || str_contains($relativePath, 'components/');
    }
}
