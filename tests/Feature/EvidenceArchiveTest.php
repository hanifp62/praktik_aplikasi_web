<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\ArsipBukti;
use App\Support\PenjagaDataContoh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Arsip bukti status resmi (spec v3 bagian 10.7; rencana induk W8 butir 6; ruling W8-R9).
 *
 * Bukti yang diunggah kurator disimpan di disk privat dengan nama acak. Gambar ditulis ulang tanpa
 * EXIF (tangkapan layar dari ponsel kurator membawa koordinat tempat ia memotret) dan dikecilkan;
 * PDF disimpan apa adanya. SHA-256 dihitung dari bita yang benar-benar tersimpan, dan arsip yang
 * tidak lagi cocok dengan sidiknya tidak pernah disajikan. Hanya kurator yang berwenang atas
 * kawasannya dan admin yang dapat membukanya.
 */
class EvidenceArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ArsipBukti::disk());
    }

    /**
     * JPEG 3000 x 2000 dengan segmen EXIF berisi tag Make (pola ProfilePhotoTest).
     */
    private function gambarDenganExif(string $nama = 'surat-balai-dari-hp-kurator.jpg'): UploadedFile
    {
        $gambar = imagecreatetruecolor(3000, 2000);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 240, 240, 230));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Kurator\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent($nama, substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('SE-4-KSDAE-2026.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
    }

    public function test_an_image_is_rewritten_without_metadata_and_fingerprinted(): void
    {
        $unggahan = $this->gambarDenganExif();
        $this->assertNotFalse(@exif_read_data($unggahan->getRealPath()), 'Berkas uji harus membawa EXIF sebelum diunggah.');

        $arsip = app(ArsipBukti::class)->simpan($unggahan);

        $this->assertTrue(ArsipBukti::sah($arsip['path']));
        $this->assertStringNotContainsStringIgnoringCase('kurator', $arsip['path'], 'Nama berkas acak, bukan dari nama berkas asal.');
        $berkas = Storage::disk(ArsipBukti::disk())->path($arsip['path']);
        [$lebar, $tinggi] = getimagesize($berkas);
        $this->assertSame([2400, 1600], [$lebar, $tinggi]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');
        $this->assertSame(hash_file('sha256', $berkas), $arsip['sha256']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $arsip['sha256']);
    }

    public function test_a_pdf_is_kept_byte_for_byte_and_fingerprinted(): void
    {
        $unggahan = $this->pdf();
        $isi = file_get_contents($unggahan->getRealPath());

        $arsip = app(ArsipBukti::class)->simpan($unggahan);

        $this->assertStringEndsWith('.pdf', $arsip['path']);
        $this->assertSame($isi, Storage::disk(ArsipBukti::disk())->get($arsip['path']));
        $this->assertSame(hash('sha256', $isi), $arsip['sha256']);
    }

    /**
     * Tipe berkas diperiksa dari isinya, bukan dari nama atau tebakan tipe: skrip yang dinamai
     * .jpg, SVG, dan HTML yang dinamai .pdf ditolak sebelum disimpan.
     */
    public function test_only_real_images_and_pdfs_within_the_limit_are_accepted(): void
    {
        $tolak = [
            'skrip berkedok foto' => UploadedFile::fake()->createWithContent('surat.jpg', "<?php system(\$_GET['c']); ?>\n"),
            'svg' => UploadedFile::fake()->createWithContent('surat.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            'html berkedok pdf' => UploadedFile::fake()->createWithContent('surat.pdf', '<html><script>alert(1)</script></html>'),
            'terlalu besar' => UploadedFile::fake()->create('surat.pdf', 10241, 'application/pdf'),
        ];

        foreach ($tolak as $alasan => $berkas) {
            $this->assertTrue(Validator::make(['bukti' => $berkas], ['bukti' => ArsipBukti::aturan()])->fails(), $alasan);
        }

        $this->assertFalse(Validator::make(['bukti' => $this->pdf()], ['bukti' => ArsipBukti::aturan()])->fails());
        $this->assertFalse(Validator::make(['bukti' => $this->gambarDenganExif()], ['bukti' => ArsipBukti::aturan()])->fails());
        $this->assertSame([], Storage::disk(ArsipBukti::disk())->allFiles());
    }

    public function test_curators_of_the_area_and_admins_download_the_archive_and_nobody_else(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        $luar = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($luar)->for(tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach(Mountain::factory()->create())))->create();

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=bukti-status-'.$status->id.'.pdf');
        $this->assertStringContainsString('no-store', (string) $this->actingAs($kurator)->get(route('curator.evidence.show', $status))->headers->get('Cache-Control'));
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->get(route('curator.evidence.show', $status))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('curator.evidence.show', $status))->assertForbidden();
        $this->actingAs($luar)->get(route('curator.evidence.show', $status))->assertForbidden();
        $this->post(route('logout'));
        $this->get(route('curator.evidence.show', $status))->assertRedirect(route('login'));
    }

    public function test_a_tampered_archive_is_never_served(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        Storage::disk(ArsipBukti::disk())->put($status->evidence_archive_path, "%PDF-1.4\nisi yang diganti\n");

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))
            ->assertStatus(409)
            ->assertSee('Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat.');
    }

    public function test_a_path_not_written_by_the_archive_is_never_served(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        Storage::disk(ArsipBukti::disk())->put('lain/catatan.txt', 'bukan arsip');
        $status->forceFill(['evidence_archive_path' => 'lain/catatan.txt', 'evidence_sha256' => hash('sha256', 'bukan arsip')])->save();

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))->assertNotFound();
    }

    /**
     * Saat data contoh disajikan, disk arsip bukti diputus dari .env seperti disk foto laporan
     * (tinjauan akhir W1, I-3). Disk foto profil W7 belum ikut diputus; ia ditutup di sini juga.
     */
    public function test_serving_sample_data_keeps_uploads_off_production_disks(): void
    {
        config(['filesystems.evidence_disk' => 'supabase', 'filesystems.avatars_disk' => 'supabase']);

        app(PenjagaDataContoh::class)->putuskanSaluranLuar();

        $this->assertSame('local', config('filesystems.evidence_disk'));
        $this->assertSame('local', config('filesystems.avatars_disk'));
    }

    /**
     * @return array{0: OfficialStatus, 1: User}
     */
    private function statusBerarsip(): array
    {
        $gunung = Mountain::factory()->create();
        $badan = Authority::factory()->create();
        $badan->mountains()->attach($gunung);
        $kurator = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($kurator)->for($badan)->create();

        $arsip = app(ArsipBukti::class)->simpan($this->pdf());
        $status = OfficialStatus::factory()->create(['statusable_type' => $gunung->getMorphClass(), 'statusable_id' => $gunung->id, 'scope' => 'MOUNTAIN']);
        $status->forceFill(['evidence_archive_path' => $arsip['path'], 'evidence_sha256' => $arsip['sha256'], 'authority_id' => $badan->id])->save();

        return [$status->fresh(), $kurator];
    }
}
