<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Teks yang dibaca pendaki tidak memakai kata klaim keselamatan (R-012, R-014; Delivery Gate W9
 * butir Salinan; titipan W2 "Ini area aman aplikasi").
 *
 * Yang disapu hanya teks, bukan komentar: string PHP di app/ (lewat token_get_all), isi Blade di
 * luar komentar, nilai lang/id.json, dan sumber TS/TSX di resources/js sesudah komentarnya
 * dibuang. Kata yang sama boleh muncul hanya di kalimat yang menyangkal atau menyatakan
 * ketidakpastian, dan setiap kalimat seperti itu disebut di PENYANGKALAN dengan alasannya. Huruf
 * besar semua (konstanta seperti SEHAT di pemeriksaan penjadwal) bukan teks dan tidak disapu.
 */
class SafetyClaimWordTest extends TestCase
{
    private const POLA = '/\b(?:[Aa]man|[Ss]ehat|[Dd]ijamin|[Tt]erjamin|[Pp]asti)\b/u';

    /**
     * Potongan kalimat yang memakai kata itu untuk menyangkal klaim atau mengaku tidak pasti.
     *
     * @var array<string, string>
     */
    private const PENYANGKALAN = [
        'Sistem tidak menyatakan bahwa suatu gunung atau jalur aman' => 'Penafian R-012 dan R-014 di Beranda.',
        'jarak dan tiba di pos kurang pasti' => 'Mode lapangan mengaku akurasi GPS rendah (R-005).',
        'memberi kesan pasti' => 'Penilaian kecocokan menjelaskan mengapa tidak ada skor angka (R-005).',
        'batasan yang bersifat pasti' => 'Rekomendasi menyebut jenis batasan yang mengecualikan jalur, bukan keadaan jalur.',
        'belum pasti pendakian sudah dimulai' => 'Galat jaringan mulai pendakian menyatakan ketidakpastian jawaban server (ruling W6-4k).',
        'Belum pasti dimulai: jawaban server tidak dapat dibaca' => 'Galat HTTP non-Inertia mulai pendakian menyatakan ketidakpastian (ruling W6-FE).',
        'belum pasti hasilnya tersimpan' => 'Galat jaringan simpan hasil menyatakan ketidakpastian jawaban server (ruling W6-5h).',
        'Belum pasti tersimpan: jawaban server tidak dapat dibaca' => 'Galat HTTP non-Inertia simpan hasil/jam kembali menyatakan ketidakpastian (ruling W6-FE).',
        'belum pasti jam kembali tersimpan' => 'Galat jaringan simpan jam kembali menyatakan ketidakpastian jawaban server (PT7-2).',
        'Belum pasti keluar:' => 'Rute keluar yang putus atau tidak terbaca menyatakan ketidakpastian sesi (ruling W6-11c/W6-11h).',
        'belum pasti terhapus dari Memount' => 'Galat hapus jejak di server menyatakan ketidakpastian penghapusan jarak jauh (ruling W6-P2o).',
    ];

    public function test_no_text_a_hiker_reads_claims_safety_or_certainty(): void
    {
        $pelanggar = [];

        foreach ($this->teks() as $asal => $isi) {
            foreach (array_keys(self::PENYANGKALAN) as $kalimat) {
                $isi = str_replace($kalimat, '', $isi);
            }

            if (preg_match_all(self::POLA, $isi, $temuan)) {
                $pelanggar[$asal] = array_values(array_unique($temuan[0]));
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_every_allowed_sentence_still_exists(): void
    {
        $semua = implode("\n", $this->teks());

        foreach (array_keys(self::PENYANGKALAN) as $kalimat) {
            $this->assertStringContainsString($kalimat, $semua, 'Kalimat pengecualian sudah tidak ada; hapus dari PENYANGKALAN.');
        }
    }

    /**
     * @return array<string, string> teks per berkas, tanpa komentar
     */
    private function teks(): array
    {
        $teks = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            $string = array_filter(token_get_all($berkas->getContents()), fn ($token) => is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true));
            $teks['app/'.str_replace('\\', '/', $berkas->getRelativePathname())] = implode("\n", array_map(fn (array $token) => $token[1], $string));
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $teks['views/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('/\{\{--.*?--\}\}|<!--.*?-->|\/\*.*?\*\//s', '', $berkas->getContents());
        }

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            $tanpaKomentar = (string) preg_replace(['/\/\*.*?\*\//s', '/(?<![:\'"\w])\/\/.*$/m'], '', $berkas->getContents());
            $teks['js/'.str_replace('\\', '/', $berkas->getRelativePathname())] = $tanpaKomentar;
        }

        $teks['lang/id.json'] = implode("\n", array_values(json_decode(File::get(lang_path('id.json')), true)));

        return $teks;
    }
}
