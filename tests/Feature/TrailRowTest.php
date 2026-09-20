<?php

namespace Tests\Feature;

use App\Enums\TechnicalDemand;
use App\Enums\WaterAvailability;
use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Baris jalur, bukan baris basis data.
 *
 * Yang digantikan: judul, satu anak judul, lalu tiga pasang dt/dd berisi Jarak,
 * Tanjakan, dan Teknis. Sepuluh kolom tersedia pada sebuah jalur dan kartunya memakai
 * tiga, dan yang tidak ikut justru estimasi durasi, yaitu angka yang paling menentukan
 * ketika orang memutuskan jalur mana yang akan didaki akhir pekan ini.
 */
class TrailRowTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(array $ubah = []): Trail
    {
        return Trail::factory()->for(
            Mountain::factory()->create(['name' => 'Prau', 'province' => 'Jawa Tengah'])
        )->create(array_merge([
            'name' => 'Jalur Patak Banteng',
            'distance_km' => 4.2,
            'elevation_gain_m' => 700,
            'estimated_duration_minutes' => 300,
            'technical_demand' => TechnicalDemand::MODERATE->value,
            'water_availability' => WaterAvailability::LIMITED->value,
            'camping_available' => true,
        ], $ubah));
    }

    private function render(Trail $trail): string
    {
        return Blade::render('<x-ui.trail-row :trail="$trail" />', ['trail' => $trail]);
    }

    /**
     * Durasi adalah angka yang menentukan keputusan, jadi ia yang mendapat bobot.
     *
     * Traveloka menaruh harga di posisi tetap yang selalu terbaca lebih dulu; di sini
     * angka setara itu bukan jarak melainkan berapa lama ia akan berjalan.
     */
    public function test_the_duration_is_present_and_carries_the_most_weight(): void
    {
        $html = $this->render($this->jalur());

        $this->assertStringContainsString('5 j', $html);
        $this->assertMatchesRegularExpression('/text-(?:lg|xl|2xl)[^"]*"[^>]*>\s*5 j/', $html);
    }

    public function test_it_shows_everything_a_hiker_decides_on(): void
    {
        $html = $this->render($this->jalur());

        foreach (['Jalur Patak Banteng', 'Prau', 'Jawa Tengah', '4,2', '700', 'Sedang'] as $bagian) {
            $this->assertStringContainsString($bagian, $html, "Baris tidak menyebut {$bagian}.");
        }
    }

    /**
     * Kecuraman dihitung dari dua angka yang sudah tersimpan, bukan ditambahkan sebagai
     * kolom baru. Meter per kilometer adalah ukuran yang dipakai pendaki, dan dua jalur
     * sepanjang lima kilometer bisa sangat berbeda karenanya.
     */
    public function test_it_derives_steepness_from_the_two_numbers_it_already_has(): void
    {
        $this->assertStringContainsString('167 m/km', $this->render($this->jalur()));
    }

    /**
     * Kecuraman hanya muncul ketika kedua angkanya ada. Membaginya dengan nol atau
     * dengan ketiadaan menghasilkan angka yang terlihat pasti dan tidak berdasar apa pun
     * (PRD §91).
     */
    public function test_steepness_stays_away_when_either_number_is_missing(): void
    {
        $html = $this->render($this->jalur(['distance_km' => null]));

        $this->assertStringNotContainsString('m/km', $html);
    }

    /**
     * Ketiadaan ditulis sebagai ketiadaan, bukan sebagai nol.
     */
    public function test_a_missing_number_never_renders_as_zero(): void
    {
        $html = $this->render($this->jalur([
            'distance_km' => null,
            'elevation_gain_m' => null,
            'estimated_duration_minutes' => null,
        ]));

        $this->assertStringNotContainsString('0 km', $html);
        $this->assertStringNotContainsString('0 m<', $html);
        $this->assertStringNotContainsString('0 j', $html);
    }

    /**
     * Penanda kemudahan hanya muncul ketika ia benar.
     *
     * "Tidak bisa berkemah" sebagai chip menambah baris tanpa menambah keterangan, dan
     * chip yang selalu ada berhenti menjadi penanda.
     */
    public function test_an_affordance_chip_appears_only_when_it_is_true(): void
    {
        $ada = $this->render($this->jalur(['camping_available' => true]));
        $tidak = $this->render($this->jalur(['camping_available' => false]));

        $this->assertStringContainsString('berkemah', $ada);
        $this->assertStringNotContainsString('berkemah', $tidak);
    }

    /**
     * Tingkat teknis tidak pernah dibawa warna saja (WCAG 1.4.1).
     */
    public function test_the_difficulty_is_never_carried_by_colour_alone(): void
    {
        $this->assertStringContainsString('Sedang', $this->render($this->jalur()));
    }

    /**
     * Seluruh baris menjadi satu sasaran sentuh, bukan hanya judulnya.
     *
     * Ini yang dilakukan AllTrails dan Traveloka pada baris hasil, dan alasannya jempol:
     * tautan selebar judul memaksa ketepatan yang tidak dimiliki orang yang sedang
     * berjalan.
     */
    public function test_the_whole_row_is_the_link_target(): void
    {
        $html = $this->render($this->jalur());

        $this->assertStringContainsString('after:absolute', $html);
        $this->assertSame(1, substr_count($html, '<a '), 'Baris hanya boleh punya satu tautan.');
    }

    /**
     * Air yang belum diketahui bukan penanda kemudahan.
     *
     * WaterAvailability::UNKNOWN berlabel "Belum diketahui", dan menaruhnya sebaris
     * dengan "Bisa berkemah" menyajikan ketiadaan keterangan seolah ia keterangan.
     * Pendaki yang membaca sekilas akan mengira ada sesuatu yang sudah diperiksa.
     */
    public function test_unknown_water_is_not_shown_as_an_affordance(): void
    {
        $html = $this->render($this->jalur([
            'water_availability' => WaterAvailability::UNKNOWN->value,
            'camping_available' => false,
        ]));

        $this->assertStringNotContainsString('Belum diketahui', $html);
    }
}
