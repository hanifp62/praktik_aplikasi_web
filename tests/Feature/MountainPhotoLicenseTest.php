<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\PhotoLicense;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Mountain;
use App\Models\MountainPhoto;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Mountains\FotoGunung;
use App\Support\PenjagaDataContoh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 6.3, 8.7, 9.3; rencana induk W8 butir 8; ruling
 * W8-R19): unggahan admin dengan kolom wajib lisensinya, EXIF dibuang dan sidik SHA-256 dicatat,
 * atribusi di pojok kepala halaman gunung, visual data bila tanpa foto, dan foto komunitas tidak
 * pernah menjadi kepala halaman (R-017).
 */
class MountainPhotoLicenseTest extends TestCase
{
    use RefreshDatabase;

    private Mountain $gunung;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.mountain_photos_disk'));
        $this->gunung = Mountain::factory()->create(['name' => 'Gunung Gede']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    /**
     * JPEG 2400 x 1500 dengan segmen EXIF berisi tag Make (pola ProfilePhotoTest).
     */
    private function fotoDenganExif(): UploadedFile
    {
        $gambar = imagecreatetruecolor(2400, 1500);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 40, 90, 60));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Kamera\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent('gede-dari-alun-alun.jpg', substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'foto' => $this->fotoDenganExif(),
            'creator' => 'Rani Prasetyo',
            'license' => PhotoLicense::CC_BY->value,
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Gede.jpg',
            'attribution' => 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons',
            'alt_text' => 'Puncak Gunung Gede dilihat dari alun-alun Surya Kencana.',
            ...$ganti,
        ];
    }

    public function test_an_admin_uploads_a_licensed_photo_without_location_metadata(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['mountain_id' => 999, 'uploaded_by' => 999, 'path' => '../.env', 'sha256' => str_repeat('0', 64)]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.mountains.photo', $this->gunung));

        $foto = MountainPhoto::sole();
        $this->assertSame($this->gunung->id, $foto->mountain_id);
        $this->assertSame($admin->id, $foto->uploaded_by);
        $this->assertTrue(FotoGunung::sah($foto->path));
        $this->assertStringNotContainsString('alun', $foto->path, 'Nama berkas acak, bukan dari nama berkas asal.');

        $berkas = Storage::disk(FotoGunung::disk())->path($foto->path);
        $this->assertSame(hash_file('sha256', $berkas), $foto->sha256);
        $this->assertSame([(int) config('hiking.uploads.mountain_photo_dimension'), 1250], [$foto->width, $foto->height]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');

        $jejak = AuditLog::where('action', 'mountain_photo.saved')->sole();
        $this->assertNull($jejak->before);
        $this->assertSame('Rani Prasetyo', $jejak->after['creator']);
    }

    public function test_every_license_field_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['creator' => '', 'license' => 'BEBAS', 'source_url' => '', 'attribution' => '', 'alt_text' => '']))
            ->assertSessionHasErrors(['creator', 'license', 'attribution', 'alt_text']);

        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['source_url' => '']))
            ->assertSessionHasErrors('source_url');

        $this->assertSame(0, MountainPhoto::count());

        // Foto milik Memount boleh tanpa alamat sumber.
        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['license' => PhotoLicense::OWN->value, 'source_url' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull(MountainPhoto::sole()->source_url);
    }

    public function test_svg_scripts_and_tiny_images_are_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')]))->assertSessionHasErrors('foto');
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->createWithContent('foto.jpg', "<?php system(\$_GET['c']); ?>\n")]))->assertSessionHasErrors('foto');
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->image('kecil.jpg', 300, 200)]))->assertSessionHasErrors('foto');

        $this->assertSame(0, MountainPhoto::count());
        $this->assertSame([], Storage::disk(FotoGunung::disk())->allFiles());
    }

    public function test_only_admins_manage_photos(): void
    {
        $kurator = User::factory()->create(['role' => UserRole::CURATOR->value]);
        $pendaki = User::factory()->create();

        $this->actingAs($kurator)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian())->assertForbidden();
        $this->actingAs($pendaki)->get(route('admin.mountains.photo', $this->gunung))->assertForbidden();
        $this->assertSame(0, MountainPhoto::count());
    }

    /**
     * Atribusi, lisensi, dan teks alternatif sampai ke kepala halaman gunung; berkasnya disajikan
     * dengan cache privat.
     */
    public function test_the_mountain_page_shows_the_photo_with_its_attribution(): void
    {
        $this->actingAs($this->admin())->post(route('admin.mountains.photo.store', $this->gunung), $this->isian());
        $foto = MountainPhoto::sole();
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('foto.url', route('mountains.photo', ['mountain' => $this->gunung, 'v' => substr($foto->sha256, 0, 12)]))
                ->where('foto.atribusi', 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons')
                ->where('foto.lisensi', 'CC BY')
                ->where('foto.alt', 'Puncak Gunung Gede dilihat dari alun-alun Surya Kencana.')
                ->where('foto.sumber', 'https://commons.wikimedia.org/wiki/File:Gede.jpg'));

        $respons = $this->actingAs($pendaki)->get(route('mountains.photo', $this->gunung));
        $respons->assertOk();
        $this->assertStringContainsString('private', (string) $respons->headers->get('Cache-Control'));
    }

    /**
     * R-017: tanpa foto berlisensi kepala halamannya visual data, walaupun ada laporan komunitas
     * berfoto yang sudah disetujui di gunung itu.
     */
    public function test_a_community_photo_never_becomes_the_hero(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->create();
        TrailConditionReport::factory()->for($jalur)->create([
            'moderation_status' => ModerationStatus::APPROVED->value,
            'photo_path' => 'report-photos/contoh.jpg',
        ]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('foto', null));
        $this->actingAs(User::factory()->create())->get(route('mountains.photo', $this->gunung))->assertNotFound();
    }

    public function test_replacing_or_deleting_removes_the_old_file_and_is_audited(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian());
        $lama = MountainPhoto::sole()->path;
        $disk = Storage::disk(FotoGunung::disk());

        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['creator' => 'Dimas Pratama']))->assertSessionHasNoErrors();
        $baru = MountainPhoto::sole();
        $this->assertNotSame($lama, $baru->path);
        $disk->assertMissing($lama);
        $this->assertSame('Rani Prasetyo', AuditLog::where('action', 'mountain_photo.saved')->latest('id')->first()->before['creator']);

        $this->actingAs($admin)->delete(route('admin.mountains.photo.destroy', $this->gunung))->assertRedirect(route('admin.mountains.photo', $this->gunung));
        $this->assertSame(0, MountainPhoto::count());
        $disk->assertMissing($baru->path);
        $this->assertSame(1, AuditLog::where('action', 'mountain_photo.deleted')->count());
    }

    /**
     * Simpan yang gagal di basis data tidak meninggalkan berkas tanpa baris yang menunjuknya.
     */
    public function test_a_failed_save_leaves_no_orphan_file(): void
    {
        $this->mock(AuditLogService::class, fn ($audit) => $audit->shouldReceive('record')->andThrow(new RuntimeException('Audit gagal.')));
        $isian = $this->isian();

        try {
            app(FotoGunung::class)->simpan($this->gunung, $isian['foto'], $isian, $this->admin());
            $this->fail('Galat audit harus diteruskan.');
        } catch (RuntimeException $galat) {
            $this->assertSame('Audit gagal.', $galat->getMessage());
        }

        $this->assertSame(0, MountainPhoto::count());
        $this->assertSame([], Storage::disk(FotoGunung::disk())->allFiles());
    }

    public function test_a_path_not_written_by_the_service_is_never_served(): void
    {
        MountainPhoto::factory()->for($this->gunung)->create(['path' => 'report-photos/laporan-lain.jpg']);
        Storage::disk(FotoGunung::disk())->put('report-photos/laporan-lain.jpg', 'foto laporan komunitas');

        $this->actingAs(User::factory()->create())->get(route('mountains.photo', $this->gunung))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('foto', null));
    }

    /**
     * Saat data contoh disajikan, disk foto gunung diputus dari .env seperti disk unggahan lain.
     */
    public function test_serving_sample_data_keeps_the_photo_disk_local(): void
    {
        config(['filesystems.mountain_photos_disk' => 'supabase']);

        app(PenjagaDataContoh::class)->putuskanSaluranLuar();

        $this->assertSame('local', config('filesystems.mountain_photos_disk'));
    }
}
