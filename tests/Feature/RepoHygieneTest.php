<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\BerkasRepo;
use Tests\TestCase;

/**
 * Root repo hanya berisi entri yang sudah diputuskan.
 *
 * Inventaris root gelombang WR (spec v3 DK-56) memberi setiap berkas dan folder root
 * satu keputusan: dilacak, dipindah, atau diabaikan git. Test ini menjaga keputusan itu
 * tetap berlaku sesudah hari ini. Keluaran alat yang mendarat di root, misalnya PRD dan
 * features/ dari `ngodingpakeai plan get` atau folder design-system/ dari langkah
 * "Persist Design System" ui-ux-pro-max yang diarahkan ke DESIGN.md oleh DK-57, membuat
 * suite merah sampai seseorang memutuskannya dengan sadar.
 */
class RepoHygieneTest extends TestCase
{
    use BerkasRepo;

    /**
     * Entri root yang dilacak git atau akan ikut commit. Menambah entri berarti menambah
     * baris inventaris beserta alasannya. Upgrade tailwind 4 mencabut postcss.config.js dan
     * tailwind.config.js: konfigurasinya kini di resources/css/app.css dan vite.config.js.
     */
    private const ENTRI_ROOT = [
        '.agents', '.claude', '.editorconfig', '.env.example', '.gitattributes', '.github',
        '.gitignore', '.ngodingpakeaiignore', '.npmrc', 'AGENTS.md', 'CLAUDE.md',
        'CONTRIBUTING.md', 'DESIGN.md', 'HANDOVER.md', 'PRODUCT.md', 'README.md',
        'SECURITY.md',
        'app', 'artisan', 'boost.json', 'bootstrap', 'composer.json', 'composer.lock',
        'config', 'database', 'docker-compose.yml', 'docs', 'lang', 'package-lock.json', 'package.json',
        'phpunit.xml', 'public', 'resources', 'routes',
        'skills-lock.json', 'storage', 'tests', 'tsconfig.json',
        'vite.config.js',
    ];

    /**
     * Keadaan lokal, ruang kerja alat, arsip pemilik, dan dump yang tidak boleh ikut
     * commit. Jalurnya contoh; yang diuji adalah aturan .gitignore yang menangkapnya.
     */
    private const HARUS_DIABAIKAN = [
        '.env',
        '.env.local',
        '.env.staging',
        '.mcp.json.disabled',
        'opencode.json.disabled',
        'konfigurasi-lain.disabled',
        '.ruff_cache/CACHEDIR.TAG',
        '.phpunit.result.cache',
        '.impeccable/hook.cache.json',
        '.impeccable/hook.pending.json',
        '.impeccable/config.local.json',
        '.ngodingpakeai/config.json',
        'features/01-jelajah-rute.md',
        'jelajah-gunung.zip',
        'PRD.md',
        'cadangan-supabase.sql',
        'cadangan-supabase.sql.gz',
        'database/cadangan.dump',
        'database/demo.sqlite',
        'public/_pratinjau/dasbor.html',
    ];

    /**
     * Bahan tim yang tidak boleh tertelan aturan abaikan yang terlalu luas.
     */
    private const TIDAK_BOLEH_DIABAIKAN = [
        'docs/PRD.md',
        'docs/desain/prototype/memount-prototype.html',
        'docs/desain/prototype/acuan/00-onboarding.png',
        'docs/arsip/2026-09-22-strategi-platform.md',
        'database/migrations/2026_09_19_000220_create_trails_table.php',
        'boost.json',
        'tsconfig.json',
        '.ngodingpakeaiignore',
        'skills-lock.json',
        '.env.example',
        '.claude/skills/antislop-ui/berkas-baru.md',
        'public/fonts/plus-jakarta-sans/OFL.txt',
        'public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
        'tests/Pratinjau/statis.php',
    ];

    private const GAMBAR_ACUAN = [
        '00-onboarding', '01-beranda', '02-jelajah', '03-gunung', '04-detail-jalur',
        '05-route-fit', '06-simpanan', '07-bandingkan', '08-rencanakan', '09-persiapan',
        '10-cek-kesiapan', '11-mendaki', '12-ringkasan', '13-progres', '14-profil',
        '15-jelajah-lebar',
    ];

    /**
     * SHA-256 berkas prototype yang diserahkan pemilik 25 September 2026.
     */
    private const SHA_PROTOTYPE = 'b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2';

    /**
     * SHA-256 ourplan.md dengan akhir baris dinormalkan ke LF, karena git menormalkannya
     * saat commit (.gitattributes: text=auto eol=lf).
     */
    private const SHA_STRATEGI = '4f45e9c461d6f2ae44017a90d194177ae624e7caee4ab4401226088d2ed8e469';

    public function test_the_root_holds_only_inventoried_entries(): void
    {
        $entri = array_values(array_unique(array_map(
            fn (string $jalur): string => explode('/', $jalur, 2)[0],
            $this->berkasYangAkanDicommit()
        )));

        $baru = array_values(array_diff($entri, self::ENTRI_ROOT));

        $this->assertSame(
            [],
            $baru,
            'Entri root tanpa keputusan inventaris: '.implode(', ', $baru).'. Pindahkan ke tempatnya, abaikan git, atau tambahkan ke ENTRI_ROOT beserta alasannya.'
        );
    }

    public function test_local_state_tool_workspaces_and_dumps_stay_out_of_git(): void
    {
        $lolos = array_values(array_filter(self::HARUS_DIABAIKAN, fn (string $jalur): bool => ! $this->diabaikan($jalur)));

        $this->assertSame([], $lolos, 'Tidak diabaikan git padahal harus: '.implode(', ', $lolos));
    }

    public function test_team_material_is_never_swallowed_by_an_ignore_rule(): void
    {
        $tertelan = array_values(array_filter(self::TIDAK_BOLEH_DIABAIKAN, fn (string $jalur): bool => $this->diabaikan($jalur)));

        $this->assertSame([], $tertelan, 'Bahan tim tertelan aturan .gitignore: '.implode(', ', $tertelan));
    }

    public function test_the_owner_prototype_and_its_sixteen_reference_images_live_in_docs(): void
    {
        $prototype = base_path('docs/desain/prototype/memount-prototype.html');

        $this->assertFileDoesNotExist(base_path('Memount Prototype.html'));
        $this->assertFileExists($prototype);
        $this->assertSame(self::SHA_PROTOTYPE, hash_file('sha256', $prototype), 'Isi prototype berbeda dari berkas yang diserahkan pemilik.');

        exec('git -C '.escapeshellarg(base_path()).' check-attr binary -- docs/desain/prototype/memount-prototype.html', $atribut);
        $this->assertStringContainsString('binary: set', implode("\n", $atribut), 'Prototype harus disimpan sebagai artefak biner (.gitattributes).');

        $panduan = File::get(base_path('docs/desain/prototype/README.md'));

        foreach (self::GAMBAR_ACUAN as $nama) {
            $gambar = base_path("docs/desain/prototype/acuan/{$nama}.png");

            $this->assertFileExists($gambar);
            $this->assertSame("\x89PNG", (string) file_get_contents($gambar, false, null, 0, 4), "{$nama}.png bukan berkas PNG.");
            $this->assertStringContainsString("{$nama}.png", $panduan, "{$nama}.png tidak dijelaskan di README prototype.");
        }
    }

    public function test_the_strategy_research_is_archived_whole_not_left_at_the_root(): void
    {
        $arsip = base_path('docs/arsip/2026-09-22-strategi-platform.md');

        $this->assertFileDoesNotExist(base_path('ourplan.md'));
        $this->assertFileExists($arsip);
        $this->assertSame(self::SHA_STRATEGI, hash('sha256', str_replace("\r\n", "\n", File::get($arsip))), 'Arsip strategi tidak lagi utuh.');
    }

    private function diabaikan(string $jalur): bool
    {
        exec('git -C '.escapeshellarg(base_path()).' check-ignore -q --no-index -- '.escapeshellarg($jalur), $keluaran, $kode);

        return $kode === 0;
    }
}
