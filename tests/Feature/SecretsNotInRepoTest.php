<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kredensial boleh dipakai, tidak boleh ditulis ke berkas.
 *
 * Aturannya mudah disepakati dan mudah dilanggar tanpa sengaja: satu kata sandi yang
 * tertempel di script sekali saja akan ikut ke riwayat git selamanya, bahkan setelah
 * baris itu dihapus. Jadi aturannya ditegakkan mesin, bukan ingatan.
 *
 * Yang diperiksa adalah berkas yang dilacak git. Isi .env tidak ikut karena memang
 * tempatnya di sana dan sudah di-gitignore.
 */
class SecretsNotInRepoTest extends TestCase
{
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
        ];
    }

    #[DataProvider('polaRahasia')]
    public function test_no_tracked_file_contains_the_pattern(string $pola, string $keterangan): void
    {
        $pelanggar = [];

        foreach ($this->berkasTerlacak() as $berkas) {
            $penuh = base_path($berkas);

            if (! is_file($penuh) || ! $this->dapatDibacaSebagaiTeks($penuh)) {
                continue;
            }

            if (preg_match($pola, (string) file_get_contents($penuh))) {
                $pelanggar[] = $berkas;
            }
        }

        $this->assertSame([], $pelanggar, "Ditemukan {$keterangan} pada berkas terlacak: ".implode(', ', $pelanggar));
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
        exec('git -C '.escapeshellarg(base_path()).' ls-files', $keluaran, $kode);

        if ($kode !== 0) {
            $this->markTestSkipped('Bukan repositori git.');
        }

        return array_values(array_filter(array_map('trim', $keluaran)));
    }
}
