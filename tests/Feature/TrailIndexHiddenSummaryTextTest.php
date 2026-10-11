<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Kalimat ringkasan di atas daftar Jelajah (audit E2E M-2, lalu N-2 dan tinjauan TA-I1; ruling
 * CF-1).
 *
 * Diganti dari asersi sumber menjadi test perilaku (ruling CF-1). Asersi sumber lama mengunci
 * bentuk kalimat "{jumlah} jalur, termasuk {N} tampil ...", padahal saat "Tampilkan juga" aktif
 * `ringkasan.jumlah` hanya menghitung jalur yang lolos, sehingga angka depan lebih kecil dari
 * bagian yang "termasuk" di dalamnya. Test hijau itu menjaga kalimat yang salah.
 *
 * Sekarang props `ringkasan` dan `filter.tampilkanSemua` diambil dari GET /trails sungguhan, lalu
 * diumpankan ke `kalimatRingkasan` di resources/js/components/trails/jelajah/ringkasan.ts, yang
 * dijalankan di Node (pola FieldModeFiguresTest::hitung). Asersinya: angka depan sama dengan
 * `trails.total`, yaitu jumlah kartu yang tampil di semua halaman daftar, dan kalimatnya persis.
 *
 * Fixture: lima jalur terbit di satu gunung. Dua ringan lolos saringan teknis, dua sedang tidak
 * lolos saringan teknis, dan satu ringan yang Tutup tidak lolos saringan status bawaan.
 */
class TrailIndexHiddenSummaryTextTest extends TestCase
{
    use RefreshDatabase;

    private const MODUL = 'js/components/trails/jelajah/ringkasan.ts';

    private User $pembaca;

    protected function setUp(): void
    {
        parent::setUp();

        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan A']);
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan B']);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Sedang C']);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Sedang D']);
        $tutup = Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan Tutup']);
        $tutup->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);

        $this->pembaca = User::factory()->create();
    }

    public function test_the_lead_number_counts_every_card_shown_when_show_also_is_on(): void
    {
        $this->assertKalimat(
            ['teknisRendah' => '1', 'tampilkanSemua' => '1'],
            5,
            '5 jalur tampil: 2 lolos saringan, 3 tampil karena Tampilkan juga meski tidak lolos saringan: status resmi (1), tuntutan teknis (2)',
        );

        // Satu saringan saja: namanya disebut tanpa rincian per saringan.
        $this->assertKalimat(
            ['teknisRendah' => '1', 'status' => 'OPEN,RESTRICTED,UNKNOWN,CLOSED', 'tampilkanSemua' => '1'],
            5,
            '5 jalur tampil: 3 lolos saringan, 2 tampil karena Tampilkan juga meski tidak lolos saringan tuntutan teknis',
        );
    }

    public function test_hidden_trails_are_named_as_hidden_while_show_also_is_off(): void
    {
        $this->assertKalimat(
            ['teknisRendah' => '1'],
            2,
            '2 jalur, 3 disembunyikan saringan: status resmi (1), tuntutan teknis (2)',
        );
    }

    public function test_nothing_hidden_reads_as_a_plain_count_in_both_states(): void
    {
        $semuaStatus = 'OPEN,RESTRICTED,UNKNOWN,CLOSED';

        $this->assertKalimat(['status' => $semuaStatus], 5, '5 jalur');
        $this->assertKalimat(['status' => $semuaStatus, 'tampilkanSemua' => '1'], 5, '5 jalur');
    }

    /**
     * Halaman benar-benar memakai modul itu: angka depan di span tebal data-angka, sisa kalimat
     * sesudahnya, keduanya dari kalimatRingkasan dengan filter.tampilkanSemua dari server.
     */
    public function test_the_page_renders_the_sentence_from_the_module(): void
    {
        $isi = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString("from '@/components/trails/jelajah/ringkasan';", $isi);
        $this->assertStringContainsString('const kalimat = kalimatRingkasan(ringkasan, filter.tampilkanSemua);', $isi);
        $this->assertMatchesRegularExpression('/<span data-angka="" className="font-bold text-primary">\s*\{kalimat\.angka\}\s*<\/span>\s*\{kalimat\.sisa\}\s*<\/p>/', $isi);
        $this->assertStringNotContainsString(', termasuk ', $isi);
        $this->assertStringNotContainsString('import ', File::get(resource_path(self::MODUL)), 'Modul ringkasan dijalankan langsung di Node, jadi tanpa impor.');
    }

    public function test_the_toggle_row_sentence_branches_on_show_also_instead_of_always_saying_hidden(): void
    {
        $isi = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString(
            "{filter.tampilkanSemua ? 'tampil karena Anda memilih Tampilkan juga, meski tidak lolos' : 'disembunyikan'}",
            $isi,
            'Baris toggle "Tampilkan juga" harus berhenti menulis "disembunyikan" untuk jalur yang sedang ia tampilkan (M-2).'
        );
    }

    /**
     * @param  array<string, string>  $kueri
     */
    private function assertKalimat(array $kueri, int $totalDiharapkan, string $kalimatDiharapkan): void
    {
        $props = $this->actingAs($this->pembaca)->get(route('trails.index', $kueri))->assertOk()->viewData('page')['props'];

        $this->assertSame($totalDiharapkan, $props['trails']['total'], 'Fixture: jumlah kartu di semua halaman daftar.');

        $hasil = $this->kalimat($props['ringkasan'], $props['filter']['tampilkanSemua']);

        $this->assertSame($props['trails']['total'], $hasil['angka'], 'Angka depan harus sama dengan jumlah kartu yang tampil (trails.total).');
        $this->assertSame($kalimatDiharapkan, $hasil['angka'].$hasil['sisa']);
    }

    /**
     * Memanggil kalimatRingkasan di Node dengan props dari server.
     *
     * @param  array{jumlah: int, tersembunyi: array<int, array{kunci: string, label: string, jumlah: int}>}  $ringkasan
     * @return array{angka: int, sisa: string}
     */
    private function kalimat(array $ringkasan, bool $tampilkanSemua): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path(self::MODUL)).').href);'
            .'process.stdout.write(JSON.stringify(modul.kalimatRingkasan('.json_encode($ringkasan).', '.json_encode($tampilkanSemua).')));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan ringkasan.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
