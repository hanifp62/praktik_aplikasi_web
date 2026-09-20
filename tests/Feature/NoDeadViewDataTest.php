<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Data yang dioper ke view tetapi tidak pernah digambar.
 *
 * Kelas cacat ini sudah muncul tiga kali di proyek ini, dan setiap kali ditemukan
 * kebetulan, bukan oleh penjaga:
 *
 * - `$geometry` dioper ke halaman jalur berbulan-bulan dan tidak pernah sekali pun
 *   dipakai, sehingga seluruh pipeline GPX tidak punya konsumen;
 * - `$mapConfig` tetap dioper setelah petanya pindah ke komponen;
 * - relasi `user:id,name` dimuat eager pada laporan kondisi dan tidak pernah
 *   ditampilkan, sehingga pelapornya anonim padahal query-nya sudah dibayar.
 *
 * Gejalanya selalu sama dan selalu diam: query berjalan, nilainya terkirim, tidak ada
 * yang salah di layar mana pun, dan tidak ada satu pun test yang berubah warna. Test
 * ini yang membuatnya berisik.
 */
class NoDeadViewDataTest extends TestCase
{
    /**
     * Kunci yang memang tidak perlu muncul sebagai $kunci di bladenya.
     *
     * @var array<int, string>
     */
    private const DIKECUALIKAN = [
        // Dipakai lewat $this->... di dalam blade komponen Livewire.
        'trip', 'run', 'trail',
    ];

    public function test_every_variable_passed_to_a_view_is_actually_used(): void
    {
        $mati = [];

        foreach (File::allFiles(app_path('Livewire')) as $berkas) {
            $isi = $berkas->getContents();

            if (! preg_match("/view\(\s*'([a-z0-9._-]+)'\s*,\s*\[(.*?)\n(\s*)\]\s*\)/s", $isi, $cocok)) {
                continue;
            }

            $blade = resource_path('views/'.str_replace('.', '/', $cocok[1]).'.blade.php');

            if (! File::exists($blade)) {
                continue;
            }

            $tampilan = File::get($blade);

            preg_match_all("/'([a-zA-Z_][a-zA-Z0-9_]*)'\s*=>/", $cocok[2], $kunci);

            foreach ($kunci[1] as $nama) {
                if (in_array($nama, self::DIKECUALIKAN, true)) {
                    continue;
                }

                if (! preg_match('/\$'.preg_quote($nama, '/').'\b/', $tampilan)) {
                    $mati[] = $berkas->getFilenameWithoutExtension().' mengirim $'.$nama.' ke '.$cocok[1];
                }
            }
        }

        $this->assertSame(
            [],
            $mati,
            "Dioper ke view tetapi tidak pernah digambar:\n- ".implode("\n- ", $mati)
        );
    }
}
