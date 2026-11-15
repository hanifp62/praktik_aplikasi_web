<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit W9 hanya menyebut test yang benar-benar ada (R-023: tanpa bukti, tidak ada PASS).
 *
 * Laporan keamanan dan aksesibilitas menyebut buktinya sebagai `tests/...php::test_nama`. Nama
 * test yang salah ketik atau sudah diganti membuat butir terbaca lulus padahal tidak ada yang
 * menjaganya; test ini menolak rujukan seperti itu dan laporan tanpa rujukan sama sekali.
 */
class AuditCitationTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function laporan(): array
    {
        return [
            'keamanan' => ['docs/audit/keamanan-w9.md'],
            'aksesibilitas' => ['docs/audit/aksesibilitas-w9.md'],
        ];
    }

    #[DataProvider('laporan')]
    public function test_every_cited_test_exists(string $laporan): void
    {
        preg_match_all('/`(tests\/[A-Za-z\/]+\.php)::(test_[a-z0-9_]+)`/', File::get(base_path($laporan)), $rujukan, PREG_SET_ORDER);
        $hilang = [];

        foreach ($rujukan as [, $berkas, $test]) {
            if (! is_file(base_path($berkas)) || ! str_contains(File::get(base_path($berkas)), 'function '.$test.'(')) {
                $hilang[] = $berkas.'::'.$test;
            }
        }

        $this->assertGreaterThanOrEqual(10, count($rujukan), 'Laporan audit tanpa rujukan test.');
        $this->assertSame([], $hilang);
    }
}
