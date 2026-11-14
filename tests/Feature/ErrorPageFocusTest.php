<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman galat Inertia memindah fokus ke judulnya (audit aksesibilitas W9, WCAG 2.4.3 dan 4.1.3).
 *
 * Kunjungan Inertia yang gagal (429 sesudah Simpan, 419 sesudah sesi habis, 403) mengganti
 * halaman tanpa muat penuh. Tombol yang baru ditekan hilang bersama halaman lama, jadi fokus jatuh
 * ke body dan pembaca layar diam: pendaki tidak tahu aksinya ditolak. Judul galat kini menerima
 * fokus saat dipasang, sehingga yang dibacakan pertama adalah apa yang terjadi. Perilaku klien ini
 * dijaga dari sumbernya karena proyek belum punya runner uji klien (butir izin akhir 7 dan 25).
 */
class ErrorPageFocusTest extends TestCase
{
    public function test_the_inertia_error_page_moves_focus_to_its_title(): void
    {
        $halaman = File::get(resource_path('js/pages/Galat.tsx'));

        $this->assertStringContainsString('<h1 ref={judul} tabIndex={-1}', $halaman);
        $this->assertMatchesRegularExpression('/useEffect\(\(\) => \{\s*judul\.current\?\.focus\(\{ preventScroll: true \}\);\s*\}, \[galat\.kode\]\);/', $halaman);
    }
}
