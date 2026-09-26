<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Berkas kebijakan repo yang dibaca orang sebelum membaca kode.
 *
 * Repositori ini publik. SECURITY.md menentukan ke mana kerentanan dilaporkan, templat
 * pull request dan isu menentukan bukti apa yang diminta sejak awal, dan
 * database/README.md menjadi peta skema bagi anggota baru. Keempatnya mudah basi atau
 * salah bentuk tanpa ada yang menyadarinya, jadi bentuknya dijaga di sini.
 */
class RepoPolicyFilesTest extends TestCase
{
    private const JENIS_ELEMEN_FORMULIR = ['markdown', 'textarea', 'input', 'dropdown', 'checkboxes'];

    private const BERKAS_KEBIJAKAN = [
        'SECURITY.md',
        'database/README.md',
        '.github/PULL_REQUEST_TEMPLATE.md',
        '.github/ISSUE_TEMPLATE/bug.yml',
        '.github/ISSUE_TEMPLATE/fitur.yml',
        '.github/ISSUE_TEMPLATE/config.yml',
    ];

    public function test_the_security_policy_routes_reports_privately_and_names_no_personal_address(): void
    {
        $kebijakan = File::get(base_path('SECURITY.md'));

        $this->assertStringContainsString('private vulnerability reporting', $kebijakan);
        $this->assertStringContainsString('Report a vulnerability', $kebijakan);
        $this->assertStringContainsString('SecretsNotInRepoTest', $kebijakan);
        $this->assertDoesNotMatchRegularExpression('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $kebijakan, 'SECURITY.md tidak boleh menyebut alamat email.');
    }

    public function test_the_pull_request_template_carries_the_merge_gate(): void
    {
        $templat = File::get(base_path('.github/PULL_REQUEST_TEMPLATE.md'));

        foreach ([
            'php artisan test --compact --testsuite=Unit,Feature',
            'npm run types',
            'npm run build',
            'vendor/bin/pint --test',
            'Delivery Gate antislop',
            'SecretsNotInRepoTest',
            'R-019',
        ] as $butir) {
            $this->assertMatchesRegularExpression('/^- \[ \] .*'.preg_quote($butir, '/').'/m', $templat, "Templat PR tidak memuat butir periksa: {$butir}");
        }

        $this->assertStringContainsString('## Bukti', $templat);
    }

    public function test_issue_forms_follow_the_github_form_schema(): void
    {
        foreach (['bug.yml', 'fitur.yml'] as $berkas) {
            $formulir = Yaml::parseFile(base_path('.github/ISSUE_TEMPLATE/'.$berkas));

            foreach (['name', 'description', 'body'] as $kunci) {
                $this->assertArrayHasKey($kunci, $formulir, "{$berkas} tidak punya {$kunci}.");
            }

            $id = [];

            foreach ($formulir['body'] as $elemen) {
                $this->assertContains($elemen['type'], self::JENIS_ELEMEN_FORMULIR, "{$berkas}: jenis {$elemen['type']} tidak dikenal GitHub.");

                if ($elemen['type'] === 'markdown') {
                    $this->assertNotEmpty($elemen['attributes']['value']);

                    continue;
                }

                $this->assertNotEmpty($elemen['attributes']['label'], "{$berkas}: elemen tanpa label.");
                $id[] = $elemen['id'];

                if (in_array($elemen['type'], ['dropdown', 'checkboxes'], true)) {
                    $this->assertNotEmpty($elemen['attributes']['options'], "{$berkas}: {$elemen['id']} tanpa pilihan.");
                }
            }

            $this->assertSame(array_values(array_unique($id)), $id, "{$berkas}: id elemen ganda.");
        }
    }

    public function test_blank_issues_are_off_and_vulnerabilities_go_to_private_reporting(): void
    {
        $konfigurasi = Yaml::parseFile(base_path('.github/ISSUE_TEMPLATE/config.yml'));

        $this->assertFalse($konfigurasi['blank_issues_enabled']);
        $this->assertStringEndsWith('/security/advisories/new', $konfigurasi['contact_links'][0]['url']);
    }

    public function test_the_database_readme_names_every_table_and_spatial_column(): void
    {
        $ringkasan = File::get(base_path('database/README.md'));
        $hilang = [];
        $tabelTertangkap = [];
        $kolomTertangkap = [];

        foreach (File::files(base_path('database/migrations')) as $migrasi) {
            $isi = $migrasi->getContents();

            preg_match_all('/Schema::create\(["\']([a-z_]+)["\']/', $isi, $tabel);
            preg_match_all('/PostGis::addGeography\(["\']([a-z_]+)["\'], ["\']([a-z_]+)["\']/', $isi, $geografi, PREG_SET_ORDER);

            foreach ($tabel[1] as $nama) {
                $tabelTertangkap[] = $nama;

                if (! str_contains($ringkasan, "`{$nama}`")) {
                    $hilang[] = $nama;
                }
            }

            foreach ($geografi as [, $namaTabel, $kolom]) {
                $kolomTertangkap[] = "{$namaTabel}.{$kolom}";

                if (! str_contains($ringkasan, "`{$namaTabel}.{$kolom}`")) {
                    $hilang[] = "{$namaTabel}.{$kolom}";
                }
            }
        }

        foreach (['users', 'mountains', 'trails'] as $nama) {
            $this->assertContains($nama, $tabelTertangkap, "Pemindai migrasi tidak menangkap tabel {$nama}.");
        }

        $this->assertContains('trails.geometry', $kolomTertangkap, 'Pemindai migrasi tidak menangkap kolom geografi trails.geometry.');
        $this->assertSame([], $hilang, 'database/README.md belum menyebut: '.implode(', ', $hilang));
    }

    public function test_policy_files_carry_no_em_dash(): void
    {
        foreach (self::BERKAS_KEBIJAKAN as $berkas) {
            $this->assertStringNotContainsString("\u{2014}", File::get(base_path($berkas)), "{$berkas} memuat em dash.");
        }
    }
}
