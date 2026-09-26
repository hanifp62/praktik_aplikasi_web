<?php

namespace Tests\Feature;

use App\Enums\TechnicalDemand;
use App\Enums\WaterAvailability;
use App\Http\Resources\TrailRowResource;
use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Baris jalur, bukan baris basis data.
 *
 * Yang digantikan: judul, satu anak judul, lalu tiga pasang dt/dd berisi Jarak,
 * Tanjakan, dan Teknis. Sepuluh kolom tersedia pada sebuah jalur dan kartunya memakai
 * tiga, dan yang tidak ikut justru estimasi durasi, yaitu angka yang paling menentukan
 * ketika orang memutuskan jalur mana yang akan didaki akhir pekan ini.
 *
 * Di halaman React pembagiannya dua: TrailRowResource memformat angka di server (koma
 * desimal, ketiadaan sebagai tanda strip), dan TrailRow.tsx hanya menggambarnya. Test
 * ini menjaga keduanya: apa yang dikirim, dan bagaimana ia digambar.
 */
class TrailRowTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

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

    /**
     * Baris sebagaimana dikirim ke halaman Jelajah dan daftar simpanan.
     *
     * @return array<string, mixed>
     */
    private function baris(Trail $trail): array
    {
        return (new TrailRowResource($trail->fresh()))->resolve(Request::create('/trails'));
    }

    private function komponen(): string
    {
        return $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/TrailRow.tsx')));
    }

    /**
     * Durasi adalah angka yang menentukan keputusan, jadi ia yang mendapat bobot.
     *
     * Traveloka menaruh harga di posisi tetap yang selalu terbaca lebih dulu; di sini
     * angka setara itu bukan jarak melainkan berapa lama ia akan berjalan.
     */
    public function test_the_duration_is_present_and_carries_the_most_weight(): void
    {
        $this->assertSame('5 j', $this->baris($this->jalur())['durasi']);

        preg_match_all('/<dd data-angka className="([^"]*)">\{trail\.(\w+)\}<\/dd>/', $this->komponen(), $angka, PREG_SET_ORDER);
        $kelas = array_column($angka, 1, 2);

        $this->assertSame(['durasi', 'jarak', 'tanjakan', 'curam'], array_keys($kelas));
        $this->assertMatchesRegularExpression('/\btext-(?:lg|xl|2xl)\b/', $kelas['durasi']);

        foreach (['jarak', 'tanjakan', 'curam'] as $lain) {
            $this->assertDoesNotMatchRegularExpression('/\btext-(?:lg|xl|2xl)\b/', $kelas[$lain], "{$lain} tidak boleh seberat durasi.");
        }
    }

    public function test_it_shows_everything_a_hiker_decides_on(): void
    {
        $baris = $this->baris($this->jalur());

        $this->assertSame('Jalur Patak Banteng', $baris['name']);
        $this->assertSame('Prau', $baris['mountain']);
        $this->assertSame('Jawa Tengah', $baris['province']);
        $this->assertSame('4,2 km', $baris['jarak']);
        $this->assertSame('700 m', $baris['tanjakan']);
        $this->assertSame('Sedang', $baris['technical']);

        foreach (['{trail.name}', '{trail.mountain}', '{trail.province}', '{trail.durasi}', '{trail.jarak}', '{trail.tanjakan}', '{trail.technical}'] as $bagian) {
            $this->assertStringContainsString($bagian, $this->komponen(), "Baris tidak menggambar {$bagian}.");
        }
    }

    /**
     * Kecuraman dihitung dari dua angka yang sudah tersimpan, bukan ditambahkan sebagai
     * kolom baru. Meter per kilometer adalah ukuran yang dipakai pendaki, dan dua jalur
     * sepanjang lima kilometer bisa sangat berbeda karenanya.
     */
    public function test_it_derives_steepness_from_the_two_numbers_it_already_has(): void
    {
        $this->assertSame('167 m/km', $this->baris($this->jalur())['curam']);
    }

    /**
     * Kecuraman hanya muncul ketika kedua angkanya ada. Membaginya dengan nol atau
     * dengan ketiadaan menghasilkan angka yang terlihat pasti dan tidak berdasar apa pun
     * (PRD §91).
     */
    public function test_steepness_stays_away_when_either_number_is_missing(): void
    {
        $this->assertNull($this->baris($this->jalur(['distance_km' => null]))['curam']);
        $this->assertNull($this->baris($this->jalur(['elevation_gain_m' => null]))['curam']);

        $this->assertStringContainsString('{trail.curam ? (', $this->komponen(), 'Kecuraman yang tidak ada tidak boleh digambar.');
    }

    /**
     * R-007: ketiadaan ditulis sebagai ketiadaan, bukan sebagai nol. Durasi nol berarti
     * tidak ada yang pernah mengisinya, dan ikut diperlakukan sama.
     */
    public function test_a_missing_number_never_renders_as_zero(): void
    {
        foreach ([null, 0] as $durasi) {
            $baris = $this->baris($this->jalur([
                'distance_km' => null,
                'elevation_gain_m' => null,
                'estimated_duration_minutes' => $durasi,
            ]));

            $this->assertSame('-', $baris['jarak']);
            $this->assertSame('-', $baris['tanjakan']);
            $this->assertSame('-', $baris['durasi']);
            $this->assertNull($baris['curam']);
        }
    }

    /**
     * Penanda kemudahan hanya muncul ketika ia benar.
     *
     * "Tidak bisa berkemah" sebagai chip menambah baris tanpa menambah keterangan, dan
     * chip yang selalu ada berhenti menjadi penanda.
     */
    public function test_an_affordance_chip_appears_only_when_it_is_true(): void
    {
        $this->assertContains('Bisa berkemah', $this->baris($this->jalur(['camping_available' => true]))['kemudahan']);
        $this->assertNotContains('Bisa berkemah', $this->baris($this->jalur(['camping_available' => false]))['kemudahan']);

        $this->assertStringContainsString('{trail.kemudahan.length > 0 ? <p', $this->komponen());
    }

    /**
     * Tingkat teknis tidak pernah dibawa warna saja (WCAG 1.4.1): labelnya dikirim dan
     * digambar sebagai kata.
     */
    public function test_the_difficulty_is_never_carried_by_colour_alone(): void
    {
        $this->assertSame('Sedang', $this->baris($this->jalur())['technical']);
        $this->assertMatchesRegularExpression('/<span className="[^"]*">\{trail\.technical\}<\/span>/', $this->komponen());
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
        $komponen = $this->komponen();

        $this->assertSame(1, substr_count($komponen, '<Tautan'), 'Baris hanya boleh punya satu tautan.');
        $this->assertMatchesRegularExpression('/<Tautan\s+href=\{trail\.url\}\s+className="[^"]*\bafter:absolute after:inset-0\b/', $komponen);
        $this->assertStringContainsString('<article className="group relative', $komponen);
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
        $baris = $this->baris($this->jalur([
            'water_availability' => WaterAvailability::UNKNOWN->value,
            'camping_available' => false,
        ]));

        $this->assertSame([], $baris['kemudahan']);
    }
}
