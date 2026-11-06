<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * CI dipecah tanpa kehilangan satu gerbang pun (spec v3 DK-54).
 *
 * Riwayat ci.yml mencatat dua kegagalan yang hanya terlihat dari urutan dan bentuk
 * langkahnya: build aset pernah berada di bawah test sehingga seluruh test halaman
 * gagal, dan dua opsi --testsuite pernah saling menimpa sehingga 104 test Unit tidak
 * pernah dijalankan. Memecah satu berkas menjadi tiga mengulang risiko yang sama, jadi
 * bentuk ketiganya dibaca sebagai data di sini.
 *
 * symfony/yaml hadir sebagai dependensi dev transitif (laravel/roster lewat
 * laravel/boost); test ini tidak menambah dependensi.
 */
class CiWorkflowsTest extends TestCase
{
    private const FOLDER = '.github/workflows';

    private const WORKFLOW = ['test.yml', 'security.yml', 'deploy.yml'];

    private const CABANG_PUSH = ['main', 'develop', 'hardening'];

    private const CABANG_PR = ['main', 'develop'];

    /**
     * Mayor terendah per action yang berjalan di Node 24. Runner GitHub melepas Node 20
     * pada 23 September 2026 (GitHub Changelog, 19 September 2025). Action baru ditinjau
     * runtime-nya lebih dulu, lalu ditambahkan di sini.
     */
    private const MAYOR_MINIMUM = [
        'actions/checkout' => 5,
        'actions/setup-node' => 5,
        'actions/upload-artifact' => 6,
        'shivammathur/setup-php' => 2,
    ];

    public function test_ci_is_split_into_test_security_and_deploy(): void
    {
        foreach (self::WORKFLOW as $berkas) {
            $this->assertFileExists(base_path(self::FOLDER.'/'.$berkas));
        }

        $this->assertFileDoesNotExist(
            base_path(self::FOLDER.'/ci.yml'),
            'ci.yml sudah dipecah; bila tetap ada, suite yang sama berjalan dua kali di setiap push.'
        );
    }

    public function test_the_test_workflow_keeps_every_gate_in_the_proven_order(): void
    {
        $alur = $this->alur('test.yml');

        $this->assertSame(self::CABANG_PUSH, $alur['on']['push']['branches']);
        $this->assertSame(self::CABANG_PR, $alur['on']['pull_request']['branches']);
        $this->assertArrayHasKey('workflow_call', $alur['on'], 'deploy.yml memanggil test.yml sebagai workflow yang dapat dipakai ulang.');

        $pekerjaan = $alur['jobs']['test'];
        $this->assertSame('postgis/postgis:16-3.4', $pekerjaan['services']['postgis']['image']);

        $langkah = array_map(fn (array $satu): string => $satu['name'] ?? $satu['uses'], $pekerjaan['steps']);

        $this->assertLessThan($this->posisi($langkah, 'Build assets'), $this->posisi($langkah, 'Install PHP dependencies'), 'Build menjalankan Artisan untuk Wayfinder, jadi vendor/ harus ada lebih dulu.');
        $this->assertLessThan($this->posisi($langkah, 'Type check'), $this->posisi($langkah, 'Build assets'), 'Rute bertipe Wayfinder dibangkitkan build; pemeriksaan tipe harus sesudahnya.');
        $this->assertLessThan($this->posisi($langkah, 'Unit tests'), $this->posisi($langkah, 'Build assets'), 'Manifest Vite harus ada sebelum test merender halaman.');
        $this->assertLessThan($this->posisi($langkah, 'Feature tests'), $this->posisi($langkah, 'Build assets'), 'Manifest Vite harus ada sebelum test Feature merender halaman.');
        $this->assertLessThan($this->posisi($langkah, 'Spatial tests (PostGIS)'), $this->posisi($langkah, 'Build assets'), 'Manifest Vite harus ada sebelum test Spatial merender halaman.');

        foreach (['Lint (Pint)', 'Feature tests', 'Spatial tests (PostGIS)'] as $nama) {
            $this->posisi($langkah, $nama);
        }

        $bernama = $this->langkahBernama($pekerjaan['steps']);

        // Diubah 12 Oktober: setiap suite menulis JUnit ke RUNNER_TEMP (root repo dijaga RepoHygieneTest), yang
        // diterbitkan sebagai anotasi publik bila gagal, karena log job hanya terbaca admin repo.
        $this->assertSame('php artisan test --testsuite=Unit --log-junit "$RUNNER_TEMP/unit-junit.xml"', $bernama['Unit tests']['run']);
        $this->assertSame('php artisan test --testsuite=Feature --log-junit "$RUNNER_TEMP/feature-junit.xml"', $bernama['Feature tests']['run']);
        // Diubah ruling W6-FI: --fail-on-skipped wajib, karena test Spatial yang dilewati membuat
        // langkah ini hijau tanpa satu pun test PostGIS yang berjalan.
        $this->assertSame('php artisan test --testsuite=Spatial --fail-on-skipped --log-junit "$RUNNER_TEMP/spatial-junit.xml"', $bernama['Spatial tests (PostGIS)']['run'], 'Langkah Spatial harus menjalankan suite Spatial dan gagal bila ada test yang dilewati; nama langkah saja tidak menjamin gerbang PostGIS (R-024) masih ada.');
        $this->assertSame('failure()', $bernama['Ringkas kegagalan test']['if']);
        $this->assertSame('python3 .github/scripts/junit_anotasi.py "$RUNNER_TEMP/unit-junit.xml" "$RUNNER_TEMP/feature-junit.xml" "$RUNNER_TEMP/spatial-junit.xml"', $bernama['Ringkas kegagalan test']['run']);
        $this->assertLessThan($this->posisi($langkah, 'Ringkas kegagalan test'), $this->posisi($langkah, 'Spatial tests (PostGIS)'), 'Ringkasan harus sesudah suite terakhir, atau kegagalannya tidak pernah terbaca.');
        $this->assertStringStartsWith('pgsql://', $bernama['Spatial tests (PostGIS)']['env']['SPATIAL_TEST_DSN']);
        $this->assertStringContainsString('@127.0.0.1:', $bernama['Spatial tests (PostGIS)']['env']['SPATIAL_TEST_DSN']);
    }

    /**
     * Image produksi yang dipakai vercel.json dibangun dan dijalankan di setiap push, dengan urutan platform: migrasi
     * sebagai langkah tersendiri terhadap PostGIS yang sama dengan job test, lalu image melayani port 80 sebagai
     * www-data seperti di Vercel, /up dan halaman masuk menjawab 200, dan rute Vercel Cron menolak panggilan tanpa
     * rahasia tetapi menjalankan tugas dengan rahasianya. Vercel men-deploy setiap push ke main tanpa menunggu CI, jadi
     * job ini menahan image yang rusak di cabang kerja, sebelum commit yang sama dibawa ke main. Start kontainer tidak
     * menjalankan migrasi maupun schedule:work: panduan Vercel melarang migrasi saat start, dan instans tidur sesudah
     * lima menit sepi.
     */
    public function test_the_production_image_is_built_and_smoke_tested_against_postgis(): void
    {
        $alur = $this->alur('test.yml');
        $pekerjaan = $alur['jobs']['image'];

        $this->assertSame($alur['jobs']['test']['services']['postgis']['image'], $pekerjaan['services']['postgis']['image']);

        $bernama = $this->langkahBernama($pekerjaan['steps']);
        $urutan = array_keys($bernama);
        $jalankan = $bernama['Jalankan image']['run'];

        $this->assertSame('docker build --tag memount:ci .', $bernama['Build image']['run']);
        $this->assertStringContainsString('::add-mask::', $bernama['Siapkan variabel image']['run']);
        $this->assertStringContainsString('memount:ci php artisan migrate --force --no-interaction', $bernama['Migrasi']['run']);
        $this->assertLessThan(array_search('Jalankan image', $urutan, true), array_search('Migrasi', $urutan, true), 'Migrasi harus selesai sebelum aplikasi melayani.');
        $this->assertStringContainsString('--env DB_URL=pgsql://hiking:hiking@127.0.0.1:5432/hiking_test', $bernama['Migrasi']['run']);
        $this->assertStringContainsString('--env DB_URL=pgsql://hiking:hiking@127.0.0.1:5432/hiking_test', $jalankan);
        $this->assertStringNotContainsString('--env PORT=', $jalankan, 'Image diuji di port 80, port yang dipakai Vercel.');
        $this->assertStringContainsString('curl --silent --fail --output /dev/null http://127.0.0.1/up', $jalankan);
        $this->assertStringContainsString('curl --silent --fail --output /dev/null http://127.0.0.1/login', $jalankan);
        $this->assertStringContainsString('/api/cron/data-freshness-check)" = 401', $jalankan);
        $this->assertStringContainsString('--header "Authorization: Bearer $CRON_SECRET" http://127.0.0.1/api/cron/data-freshness-check', $jalankan);
        $this->assertStringContainsString('test "$(docker inspect --format \'{{.Config.User}}\' memount)" = www-data', $jalankan);
        $this->assertStringContainsString("curl --silent --fail http://127.0.0.1/login | grep --quiet 'https://127.0.0.1/build/assets/'", $jalankan, 'Aset harus https di produksi, atau halaman kosong.');
        $this->assertStringContainsString("--header 'X-Forwarded-Host: contoh.test' http://127.0.0.1/login | grep --quiet 'https://contoh.test/build/assets/'", $jalankan, 'Proxy tepercaya harus berlaku sesudah config:cache di dalam image.');
        $this->assertSame('always()', $bernama['Log kontainer']['if']);

        // Baris komentar dibuang: komentarnya justru menjelaskan mengapa migrasi dan schedule:work tidak ada di sini.
        $mulai = implode("\n", array_filter(
            explode("\n", File::get(base_path('docker/start.sh'))),
            fn (string $baris): bool => ! str_starts_with(ltrim($baris), '#'),
        ));
        $this->assertStringContainsString('set -e', $mulai);
        $this->assertStringContainsString('php artisan optimize --no-interaction', $mulai);
        $this->assertStringContainsString('--listen ":${PORT:-80}"', $mulai);
        $this->assertStringNotContainsString('migrate', $mulai, 'Panduan Vercel melarang migrasi saat kontainer mulai.');
        $this->assertStringNotContainsString('schedule:', $mulai, 'Di Vercel tugas terjadwal dipicu Vercel Cron, bukan proses latar.');

        $dockerfile = File::get(base_path('Dockerfile'));
        $this->assertMatchesRegularExpression('/^USER www-data$/m', $dockerfile, 'Kontainer tidak boleh berjalan sebagai root.');
        $this->assertMatchesRegularExpression('/^ENV PORT=80$/m', $dockerfile, 'Vercel mengirim trafik ke port 80.');
    }

    /**
     * 13 Oktober 2026: variabel Vercel berisi APP_ENV=local dan APP_DEBUG=true tanpa TRUSTED_PROXIES. Situs publik
     * memperlihatkan jejak galat dan header permintaan, memuat rute pengembangan, dan aset termuat lewat http sehingga
     * halamannya kosong. docker/start.sh karena itu memaksa production tanpa debug sebelum config:cache, dan job image
     * menjalankan image dengan variabel keliru yang sama untuk membuktikannya.
     */
    public function test_the_production_image_ignores_debug_and_non_production_settings(): void
    {
        $mulai = File::get(base_path('docker/start.sh'));
        $paksa = strpos($mulai, "\nexport APP_ENV=production APP_DEBUG=false\n");

        $this->assertNotFalse($paksa, 'Image produksi harus memaksa APP_ENV=production dan APP_DEBUG=false.');
        $this->assertLessThan(strpos($mulai, "\nphp artisan optimize --no-interaction\n"), $paksa, 'Nilai dipaksa sebelum konfigurasi di-cache.');
        $this->assertStringContainsString('TRUSTED_PROXIES="${TRUSTED_PROXIES:-*}"', $mulai);
        $this->assertStringContainsString('LOG_CHANNEL="${LOG_CHANNEL:-stderr}"', $mulai);

        $bernama = $this->langkahBernama($this->alur('test.yml')['jobs']['image']['steps']);
        $urutan = array_keys($bernama);
        $keliru = $bernama['Jalankan image dengan variabel keliru']['run'];

        $this->assertLessThan(array_search('Jalankan image dengan variabel keliru', $urutan, true), array_search('Jalankan image', $urutan, true));
        $this->assertStringContainsString('--env APP_ENV=local --env APP_DEBUG=true', $keliru);
        $this->assertStringNotContainsString('TRUSTED_PROXIES', $keliru, 'Bawaan proxy tepercaya diuji tanpa nilai dari platform.');
        $this->assertStringContainsString("if grep --quiet 'MethodNotAllowedHttpException'", $keliru, 'Halaman galat tidak boleh memperlihatkan jejak.');
        $this->assertStringContainsString('http://127.0.0.1/dev/primitif)" = 404', $keliru, 'Rute pengembangan tidak boleh termuat.');
        $this->assertStringContainsString("http://127.0.0.1/login | grep --quiet 'https://contoh.test/build/assets/'", $keliru);
    }

    public function test_the_security_workflow_audits_dependencies_and_scans_for_secrets(): void
    {
        $alur = $this->alur('security.yml');

        foreach (['push', 'pull_request', 'schedule', 'workflow_dispatch'] as $pemicu) {
            $this->assertArrayHasKey($pemicu, $alur['on'], "security.yml tidak dipicu {$pemicu}.");
        }

        $perintah = $this->semuaPerintah($alur);

        $this->assertStringContainsString('composer audit --locked', $perintah);
        $this->assertStringContainsString('npm audit --audit-level=high', $perintah);
        $this->assertStringContainsString('php artisan test --filter=SecretsNotInRepoTest --fail-on-skipped', $perintah);
    }

    public function test_the_deploy_workflow_runs_only_by_hand_and_deploys_nothing_yet(): void
    {
        $alur = $this->alur('deploy.yml');

        $this->assertSame(['workflow_dispatch'], array_keys($alur['on']));
        $this->assertSame('./.github/workflows/test.yml', $alur['jobs']['verifikasi']['uses']);
        $this->assertArrayNotHasKey('secrets', $alur['jobs']['verifikasi'], 'Rahasia penerapan menunggu keputusan pemilik (DK-54); "secrets: inherit" tidak boleh menyelinap lewat pemeriksaan substring saja.');

        $unggah = array_values(array_filter(
            $alur['jobs']['artefak-build']['steps'],
            fn (array $langkah): bool => str_starts_with($langkah['uses'] ?? '', 'actions/upload-artifact@')
        ));

        $this->assertCount(1, $unggah);
        $this->assertSame('public/build', $unggah[0]['with']['path']);

        $this->assertStringNotContainsString('secrets.', File::get(base_path(self::FOLDER.'/deploy.yml')), 'Rahasia penerapan menunggu keputusan pemilik (DK-54).');
        $this->assertDoesNotMatchRegularExpression('/\bvercel\b/i', $this->semuaPerintah($alur), 'Belum ada langkah deploy sampai pemilik memutuskan jalur teknisnya.');
    }

    public function test_every_workflow_reads_the_repo_with_the_least_permission(): void
    {
        foreach (self::WORKFLOW as $berkas) {
            $alur = $this->alur($berkas);

            $this->assertSame(['contents' => 'read'], $alur['permissions'], "{$berkas} harus membatasi token ke contents: read.");

            foreach ($alur['jobs'] as $nama => $pekerjaan) {
                if (! array_key_exists('permissions', $pekerjaan)) {
                    continue;
                }

                $this->assertSame(['contents' => 'read'], $pekerjaan['permissions'], "{$berkas}: job \"{$nama}\" melonggarkan permissions di tingkat job, menimpa contents: read di tingkat workflow.");
            }
        }
    }

    public function test_every_action_is_pinned_to_a_major_that_runs_on_node_24(): void
    {
        $pelanggar = [];

        foreach (self::WORKFLOW as $berkas) {
            preg_match_all('/uses:\s*([^\s@]+)@(\S+)/', File::get(base_path(self::FOLDER.'/'.$berkas)), $cocok, PREG_SET_ORDER);

            foreach ($cocok as [, $action, $versi]) {
                if (! preg_match('/^v(\d+)$/', $versi, $mayor)) {
                    $pelanggar[] = "{$berkas}: {$action}@{$versi} tidak dipatok ke tag mayor";
                } elseif (! isset(self::MAYOR_MINIMUM[$action])) {
                    $pelanggar[] = "{$berkas}: {$action} belum ditinjau runtime-nya";
                } elseif ((int) $mayor[1] < self::MAYOR_MINIMUM[$action]) {
                    $pelanggar[] = "{$berkas}: {$action}@{$versi} masih berjalan di Node 20";
                }
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    public function test_documents_name_only_workflows_that_exist(): void
    {
        $hilang = [];

        foreach (array_merge(glob(base_path('*.md')), glob(base_path('docs/*.md'))) as $path) {
            if (basename($path) === 'HANDOVER.md') {
                continue;
            }

            preg_match_all('#\.github/workflows/([A-Za-z0-9_.-]+\.ya?ml)#', File::get($path), $cocok);

            foreach (array_unique($cocok[1]) as $berkas) {
                if (! File::exists(base_path(self::FOLDER.'/'.$berkas))) {
                    $hilang[] = basename($path)." menyebut {$berkas}";
                }
            }
        }

        $this->assertSame([], $hilang, 'Dokumen menyebut workflow yang tidak ada: '.implode('; ', $hilang));
    }

    /**
     * @return array<string, mixed>
     */
    private function alur(string $berkas): array
    {
        return Yaml::parseFile(base_path(self::FOLDER.'/'.$berkas));
    }

    /**
     * @param  array<int, string>  $langkah
     */
    private function posisi(array $langkah, string $nama): int
    {
        $posisi = array_search($nama, $langkah, true);

        $this->assertIsInt($posisi, "Langkah \"{$nama}\" tidak ada.");

        return $posisi;
    }

    /**
     * @param  array<int, array<string, mixed>>  $langkah
     * @return array<string, array<string, mixed>>
     */
    private function langkahBernama(array $langkah): array
    {
        $bernama = [];

        foreach ($langkah as $satu) {
            if (isset($satu['name'])) {
                $bernama[$satu['name']] = $satu;
            }
        }

        return $bernama;
    }

    /**
     * Semua perintah `run` di semua job, dipisah baris baru.
     *
     * @param  array<string, mixed>  $alur
     */
    private function semuaPerintah(array $alur): string
    {
        $perintah = [];

        foreach ($alur['jobs'] as $pekerjaan) {
            foreach ($pekerjaan['steps'] ?? [] as $langkah) {
                if (isset($langkah['run'])) {
                    $perintah[] = $langkah['run'];
                }
            }
        }

        return implode("\n", $perintah);
    }
}
