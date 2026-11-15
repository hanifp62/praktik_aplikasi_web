<?php

namespace Tests\Feature;

use App\Support\PenjagaDataContoh;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Paritas lintas sistem operasi untuk Windows (PowerShell, CMD, Git Bash), macOS, dan Linux.
 *
 * Menjaga tiga sumber kegagalan yang hanya muncul di mesin Windows atau ARM64:
 * 1. skrip kontainer Linux yang berubah menjadi CRLF saat checkout atau penyuntingan di Windows;
 * 2. pemanggilan Node di test yang mengimpor modul TypeScript tanpa --experimental-strip-types
 *    (gagal pada Node 22.6 sampai 22.17) atau tanpa pathToFileURL;
 * 3. layanan Docker PostGIS tanpa platform linux/amd64 atau tanpa pengikatan ke loopback 127.0.0.1.
 */
class WindowsCompatibilityTest extends TestCase
{
    public function test_gitattributes_locks_lf_line_endings_and_docker_files_have_no_carriage_returns(): void
    {
        $atribut = str_replace("\r\n", "\n", File::get(base_path('.gitattributes')));

        $this->assertMatchesRegularExpression('/^\* text=auto eol=lf$/m', $atribut);
        $this->assertMatchesRegularExpression('/^\*\.sh text eol=lf$/m', $atribut);
        $this->assertMatchesRegularExpression('/^Dockerfile text eol=lf$/m', $atribut);

        $this->assertStringNotContainsString("\r", File::get(base_path('docker/start.sh')), 'docker/start.sh tidak boleh memuat CR agar shebang Linux di kontainer tidak gagal.');
        $this->assertStringNotContainsString("\r", File::get(base_path('Dockerfile')), 'Dockerfile harus memakai LF.');
    }

    public function test_every_node_process_importing_typescript_enables_type_stripping_and_avoids_inline_eval(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(base_path('tests')) as $berkas) {
            if ($berkas->getExtension() !== 'php' || $berkas->getRealPath() === realpath(__FILE__)) {
                continue;
            }

            $isi = $berkas->getContents();

            if (! str_contains($isi, "new Process(['node'") && ! str_contains($isi, "new Process(\n            ['node'")) {
                continue;
            }

            $relatif = str_replace('\\', '/', $berkas->getRelativePathname());
            $mengimporTs = preg_match('/(?:import\b[^\n;]*|pathToFileURL\([^\n;]*)\.ts[\'"]/', $isi) === 1;

            if ($mengimporTs && ! str_contains($isi, '--experimental-strip-types')) {
                $pelanggar[] = "{$relatif}: mengimpor .ts di Node tanpa --experimental-strip-types (gagal pada Node 22.6-22.17)";
            }

            if (str_contains($isi, "'--input-type=module', '-e'")) {
                $pelanggar[] = "{$relatif}: memakai flag -e alih-alih stdin '-' untuk modul ESM";
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    public function test_compose_services_declare_linux_amd64_platform_and_loopback_only_ports(): void
    {
        $compose = Yaml::parseFile(base_path('docker-compose.yml'));
        $layanan = $compose['services'] ?? [];

        $this->assertArrayHasKey('postgis', $layanan);
        $this->assertArrayHasKey('postgres', $layanan);

        foreach ($layanan as $nama => $konfigurasi) {
            $this->assertSame(
                'linux/amd64',
                $konfigurasi['platform'] ?? null,
                "Layanan {$nama} di docker-compose.yml wajib menetapkan platform linux/amd64 untuk citra postgis/postgis."
            );

            foreach ($konfigurasi['ports'] ?? [] as $port) {
                $this->assertStringStartsWith(
                    '127.0.0.1:',
                    (string) $port,
                    "Port layanan {$nama} tidak boleh terbuka ke jaringan luar: {$port}"
                );
            }
        }
    }

    public function test_folder_comparison_handles_windows_drive_letter_casing_and_unix_case_sensitivity(): void
    {
        $this->assertTrue(PenjagaDataContoh::folderSama('C:\\Users\\Pendaki\\memount\\database', 'c:\\users\\pendaki\\memount\\database', pekaHuruf: false));
        $this->assertFalse(PenjagaDataContoh::folderSama('C:\\Users\\Pendaki\\memount\\database2', 'C:\\Users\\Pendaki\\memount\\database', pekaHuruf: false));

        $this->assertTrue(PenjagaDataContoh::folderSama('/var/www/memount/database', '/var/www/memount/database', pekaHuruf: true));
        $this->assertFalse(PenjagaDataContoh::folderSama('/var/www/memount/Database', '/var/www/memount/database', pekaHuruf: true));
    }
}
