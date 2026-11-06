<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BerkasRepo;
use Tests\TestCase;

/**
 * Kredensial boleh dipakai, tidak boleh ditulis ke berkas.
 *
 * Aturannya mudah disepakati dan mudah dilanggar tanpa sengaja: satu kata sandi yang
 * tertempel di script sekali saja akan ikut ke riwayat git selamanya, bahkan setelah
 * baris itu dihapus. Jadi aturannya ditegakkan mesin, bukan ingatan.
 *
 * Yang diperiksa adalah berkas yang akan ikut commit: yang sudah dilacak git dan yang
 * baru tetapi tidak diabaikan. Memeriksa yang dilacak saja berarti rahasia di berkas
 * baru baru tertangkap setelah masuk riwayat. Isi .env tidak ikut karena memang
 * tempatnya di sana dan sudah di-gitignore.
 */
class SecretsNotInRepoTest extends TestCase
{
    use BerkasRepo;

    public function test_the_environment_file_is_never_tracked(): void
    {
        $terlacak = $this->berkasTerlacak();

        $this->assertNotContains('.env', $terlacak, '.env tidak boleh masuk git.');
    }

    public function test_the_example_environment_file_carries_no_real_values(): void
    {
        $contoh = base_path('.env.example');

        if (! file_exists($contoh)) {
            $this->markTestSkipped('Tidak ada .env.example.');
        }

        preg_match_all('/^(DB_PASSWORD|SUPABASE_\w*(KEY|SECRET))=(.+)$/m', file_get_contents($contoh), $cocok);

        $this->assertSame([], array_values(array_filter($cocok[3] ?? [])), '.env.example harus kosong nilainya.');
    }

    /**
     * Pola yang menandai kredensial sungguhan, bukan sekadar nama variabelnya.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function polaRahasia(): array
    {
        return [
            // Spasi horizontal saja: \s ikut menelan baris baru, sehingga DB_PASSWORD
            // yang memang kosong terbaca menyambung ke nilai baris berikutnya.
            'kata sandi basis data terisi' => ['/\bDB_PASSWORD[ \t]*=[ \t]*\S+/', 'nilai DB_PASSWORD'],
            'kunci JWT Supabase' => ['/\beyJhbGciOi[A-Za-z0-9_\-]{10,}/', 'kunci JWT Supabase'],
            'kunci rahasia Supabase' => ['/\bsb_secret_[A-Za-z0-9_\-]{8,}/', 'kunci rahasia Supabase'],
            'kunci publishable Supabase' => ['/\bsb_publishable_[A-Za-z0-9_\-]{8,}/', 'kunci publishable Supabase'],
            'URL koneksi berkata sandi' => ['#\bpostgres(?:ql)?://[^\s:@/]+:[^\s@/]+@#', 'kata sandi di dalam URL koneksi'],
            // Skema DSN proyek ini sendiri (README.md, test.yml) tidak "postgres(ql)://"
            // di atas, jadi butuh pola sendiri. DSN loopback (127.0.0.1, localhost, [::1])
            // adalah contoh sekali pakai lokal, termasuk kredensial kontainer CI dan
            // compose hiking:hiking@127.0.0.1; itu dikecualikan. Kata sandi untuk host
            // mana pun selain loopback tetap ditandai, karena di situlah ancaman
            // sungguhan (misalnya pooler Supabase).
            'URL koneksi pgsql berkata sandi' => ['#\bpgsql://[^\s:@/]+:[^\s@/]+@(?!(?:127\.0\.0\.1|localhost|\[::1\])[:/])#', 'kata sandi di dalam URL koneksi pgsql'],
            // Token CLI NgodingPakeAI (AGENTS.md, blok NgodingPakeAI): tempatnya
            // variabel lingkungan NGODINGPAKEAI_TOKEN, tidak pernah berkas.
            'token NgodingPakeAI' => ['/\bngpk_[A-Za-z0-9_\-]{8,}/', 'token NgodingPakeAI'],
        ];
    }

    /**
     * Satu contoh per pola, dirakit dari potongan supaya berkas ini sendiri tidak memuat
     * pola yang ia larang.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function contohRahasia(): array
    {
        return [
            'kata sandi basis data terisi' => ['kata sandi basis data terisi', 'DB_'.'PASSWORD=contoh-sandi'],
            'kunci JWT Supabase' => ['kunci JWT Supabase', 'eyJ'.'hbGciOi'.str_repeat('A', 16)],
            'kunci rahasia Supabase' => ['kunci rahasia Supabase', 'sb_'.'secret_'.str_repeat('a', 12)],
            'kunci publishable Supabase' => ['kunci publishable Supabase', 'sb_'.'publishable_'.str_repeat('b', 12)],
            'URL koneksi berkata sandi' => ['URL koneksi berkata sandi', 'postgres'.'ql://pengguna:'.'sandi@db.contoh.test/app'],
            'URL koneksi pgsql berkata sandi' => ['URL koneksi pgsql berkata sandi', 'pg'.'sql://pengguna:'.'sandi@db.contoh.test/app'],
            'token NgodingPakeAI' => ['token NgodingPakeAI', 'ng'.'pk_'.str_repeat('c', 16)],
        ];
    }

    #[DataProvider('polaRahasia')]
    public function test_no_committable_file_contains_the_pattern(string $pola, string $keterangan): void
    {
        $pelanggar = [];

        foreach ($this->berkasTeksYangAkanDicommit() as $berkas) {
            if (preg_match($pola, (string) file_get_contents(base_path($berkas)))) {
                $pelanggar[] = $berkas;
            }
        }

        $this->assertSame([], $pelanggar, "Ditemukan {$keterangan} pada berkas yang akan ikut commit: ".implode(', ', $pelanggar));
    }

    public function test_the_scan_reads_the_files_it_claims_to_read(): void
    {
        $terbaca = $this->berkasTeksYangAkanDicommit();

        foreach (['README.md', 'composer.json', 'docs/PRD.md'] as $berkas) {
            $this->assertContains($berkas, $terbaca, "Pemindai rahasia tidak membaca {$berkas}.");
        }

        $this->assertGreaterThanOrEqual(100, count($terbaca), 'Pemindai rahasia membaca terlalu sedikit berkas untuk dipercaya.');
    }

    #[DataProvider('contohRahasia')]
    public function test_each_pattern_catches_a_sample_of_its_secret(string $nama, string $contoh): void
    {
        $this->assertArrayHasKey($nama, self::polaRahasia(), "Belum ada pola untuk {$nama}.");
        $this->assertMatchesRegularExpression(self::polaRahasia()[$nama][0], $contoh, "Pola {$nama} tidak menangkap contohnya.");
    }

    public function test_every_pattern_has_a_sample(): void
    {
        $this->assertSame(array_keys(self::polaRahasia()), array_keys(self::contohRahasia()));
    }

    public function test_loopback_dsn_examples_are_not_flagged(): void
    {
        $pola = self::polaRahasia()['URL koneksi pgsql berkata sandi'][0];

        foreach ([
            'pg'.'sql://user:'.'pass@127.0.0.1:5432/hiking_test',
            'pg'.'sql://postgres:'.'postgres@localhost:5432/db',
            'pg'.'sql://%s:'.'%s@127.0.0.1:%s/%s',
            'pg'.'sql://user:'.'pass@[::1]:5432/hiking_test',
        ] as $loopback) {
            $this->assertDoesNotMatchRegularExpression($pola, $loopback, "Pola seharusnya tidak menandai DSN loopback: {$loopback}");
        }

        $this->assertMatchesRegularExpression($pola, 'pg'.'sql://pengguna:'.'sandi@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres', 'Pola harus tetap menandai kata sandi untuk host selain loopback.');
    }

    public function test_untracked_files_that_are_not_ignored_are_scanned_too(): void
    {
        $berkas = 'tests/Feature/.pemindai-'.bin2hex(random_bytes(4)).'.txt';
        File::put(base_path($berkas), 'berkas sementara pemindai rahasia');

        try {
            $this->assertContains($berkas, $this->berkasYangAkanDicommit());
        } finally {
            File::delete(base_path($berkas));
        }
    }

    /**
     * Berkas yang akan ikut commit dan benar-benar dibaca pemindai: ada di disk, berupa teks,
     * dan tidak lebih dari 2 MB.
     *
     * @return array<int, string>
     */
    private function berkasTeksYangAkanDicommit(): array
    {
        return array_values(array_filter(
            $this->berkasYangAkanDicommit(),
            fn (string $berkas): bool => is_file(base_path($berkas)) && $this->dapatDibacaSebagaiTeks(base_path($berkas))
        ));
    }

    private function dapatDibacaSebagaiTeks(string $path): bool
    {
        if (filesize($path) > 2_000_000) {
            return false;
        }

        $awal = (string) file_get_contents($path, false, null, 0, 1024);

        return ! str_contains($awal, "\0");
    }

    /**
     * @return array<int, string>
     */
    private function berkasTerlacak(): array
    {
        return $this->daftarGit('');
    }

    /**
     * @return array<int, string>
     */
    private function daftarGit(string $opsi): array
    {
        exec('git -C '.escapeshellarg(base_path()).' ls-files -z '.$opsi, $keluaran, $kode);

        if ($kode !== 0) {
            $this->markTestSkipped('Bukan repositori git.');
        }

        return array_values(array_filter(explode("\0", implode("\n", $keluaran))));
    }
}
