<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

/**
 * Proteksi mass assignment sebagai aturan umum (CLAUDE.md bagian 4; audit keamanan W9,
 * docs/audit/keamanan-w9.md).
 *
 * Test per fitur sudah menjaga kolom tertentu: asal status resmi, pemilik dan status trip, peran
 * pendaftar, dan peran lewat formulir profil. Yang belum dijaga adalah aturan umumnya, yang
 * menentukan apakah model atau pengendali berikutnya ikut aman: tidak ada model yang membuka
 * semua kolomnya, dan tidak ada kode HTTP yang meneruskan seluruh isi permintaan ke model alih-alih
 * isian yang sudah divalidasi.
 */
class MassAssignmentSweepTest extends TestCase
{
    public function test_no_model_opens_every_column_to_mass_assignment(): void
    {
        $terbuka = [];

        foreach (File::files(app_path('Models')) as $berkas) {
            $kelas = 'App\\Models\\'.$berkas->getFilenameWithoutExtension();

            if (! is_subclass_of($kelas, Model::class) || (new ReflectionClass($kelas))->isAbstract()) {
                continue;
            }

            if ((new $kelas)->getGuarded() === []) {
                $terbuka[] = $kelas;
            }
        }

        $this->assertSame([], $terbuka);
        $this->assertDoesNotMatchRegularExpression('/Model::unguard|->unguard\(/', implode("\n", array_map(fn ($berkas) => $berkas->getContents(), File::allFiles(app_path()))));
    }

    public function test_no_http_code_passes_the_whole_request_on(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(app_path('Http')) as $berkas) {
            if (preg_match('/(\$request|request\(\))->(all|input)\(\)/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar);
    }
}
