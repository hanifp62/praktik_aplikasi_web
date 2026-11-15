<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Profil\FotoProfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Foto profil (spec v3 bagian 6.15; rencana induk W7 butir 5): EXIF dibuang dan ukurannya
 * dibatasi sebelum disimpan, nama berkas acak di disk privat, hanya pemiliknya yang dapat
 * membukanya, berkas lama dibuang ketika diganti atau dihapus, dan unggahan yang bukan gambar
 * ditolak tanpa meninggalkan berkas (PRD §81, §82).
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(FotoProfil::disk());
    }

    /**
     * JPEG 1200 x 900 dengan segmen EXIF berisi tag Make (pola ImageSanitizerTest).
     */
    private function fotoDenganExif(string $nama = 'wajah-budi-setiawan.jpg'): UploadedFile
    {
        $gambar = imagecreatetruecolor(1200, 900);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 40, 90, 60));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Pendaki\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent($nama, substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    public function test_an_uploaded_photo_is_stored_without_metadata_and_scaled_down(): void
    {
        $user = User::factory()->create(['name' => 'Budi Setiawan']);
        $unggahan = $this->fotoDenganExif();
        $this->assertNotFalse(@exif_read_data($unggahan->getRealPath()), 'Berkas uji harus membawa EXIF sebelum diunggah.');

        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $unggahan])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil diganti.']);

        $path = $user->fresh()->profile->avatar_path;
        $this->assertStringStartsWith(FotoProfil::MAP.'/', $path);
        $this->assertStringNotContainsStringIgnoringCase('setiawan', $path, 'Nama berkas acak, bukan dari nama pengguna atau berkas asal.');

        $berkas = Storage::disk(FotoProfil::disk())->path($path);
        [$lebar, $tinggi] = getimagesize($berkas);
        $this->assertSame([512, 384], [$lebar, $tinggi]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');
    }

    /**
     * Berkas disajikan hanya kepada pemilik sesi, dengan cache privat; alamatnya dibagikan ke
     * setiap halaman lewat auth.user.foto beserta penanda versi.
     */
    public function test_only_the_owner_is_served_the_photo(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()])->assertSessionHasNoErrors();
        $user = $user->fresh();

        $respons = $this->actingAs($user)->get(route('profile.photo.show'));
        $respons->assertOk();
        $this->assertStringContainsString('private', (string) $respons->headers->get('Cache-Control'));

        $this->actingAs($orangLain)->get(route('profile.photo.show'))->assertNotFound();
        auth()->logout();
        $this->get(route('profile.photo.show'))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.foto', FotoProfil::url($user)));
        $this->assertStringContainsString('?v=', (string) FotoProfil::url($user));
        $this->actingAs($orangLain)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.foto', null));
    }

    public function test_replacing_or_removing_the_photo_deletes_the_old_file(): void
    {
        $user = User::factory()->create();
        $disk = Storage::disk(FotoProfil::disk());

        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()]);
        $pertama = $user->fresh()->profile->avatar_path;
        $this->actingAs($user->fresh())->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif('lain.jpg')]);
        $kedua = $user->fresh()->profile->avatar_path;

        $this->assertNotSame($pertama, $kedua);
        $this->assertFalse($disk->exists($pertama));
        $this->assertTrue($disk->exists($kedua));

        $this->actingAs($user->fresh())->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil dihapus. Inisial nama Anda dipakai lagi.']);
        $this->assertNull($user->fresh()->profile->avatar_path);
        $this->assertFalse($disk->exists($kedua));
    }

    /**
     * Skrip yang dinamai .jpg, SVG, dan berkas yang terlalu besar ditolak sebelum disentuh, dan
     * tidak meninggalkan berkas apa pun.
     */
    public function test_malicious_or_oversized_uploads_are_refused_without_a_trace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.photo.store'), [])
            ->assertSessionHasErrors(['foto' => 'Pilih foto lebih dulu.']);
        // Skrip berekstensi .jpg. Unggahan nyata ditolak aturan image karena tipe MIME-nya dibaca
        // dari isi berkas; berkas palsu Laravel menebak tipe dari ekstensi, dan tetap ditolak
        // aturan dimensions karena isinya tidak terbaca sebagai gambar. Yang dijaga: ditolak
        // sebelum disimpan, apa pun lapisan yang menolaknya.
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->createWithContent('foto.jpg', "<?php system(\$_GET['c']); ?>\n")])
            ->assertSessionHasErrors('foto');
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors(['foto' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')])
            ->assertSessionHasErrors('foto');
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('besar.jpg', 1200, 900)->size(3000)])
            ->assertSessionHasErrors(['foto' => 'Ukuran foto paling besar 2 MB.']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('kecil.png', 20, 20)])
            ->assertSessionHasErrors(['foto' => 'Foto paling kecil 64 x 64 piksel dan paling besar 8000 piksel di sisi terpanjang.']);

        $this->assertSame([], Storage::disk(FotoProfil::disk())->allFiles());
        $this->assertNull($user->fresh()->profile);
    }

    /**
     * Kolom avatar_path sudah ada sebelum W7. Nilai yang tidak ditulis FotoProfil tidak pernah
     * disajikan, dibagikan sebagai alamat foto, atau dihapus, walau berkasnya ada di disk yang sama.
     */
    public function test_a_path_not_written_by_the_photo_service_is_never_served_or_deleted(): void
    {
        $user = User::factory()->create();
        $disk = Storage::disk(FotoProfil::disk());
        $disk->put('lain/catatan.txt', 'bukan foto');
        $user->profile()->create(['avatar_path' => 'lain/catatan.txt']);

        $this->actingAs($user)->get(route('profile.photo.show'))->assertNotFound();
        $this->assertNull(FotoProfil::url($user->fresh()));

        $this->actingAs($user->fresh())->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()])->assertSessionHasNoErrors();

        $this->assertTrue($disk->exists('lain/catatan.txt'), 'Berkas di luar map foto tidak ikut dihapus.');
        $this->assertStringStartsWith(FotoProfil::MAP.'/', $user->fresh()->profile->avatar_path);
    }

    public function test_deleting_the_account_deletes_the_photo_file(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()]);
        $path = $user->fresh()->profile->avatar_path;

        $this->actingAs($user->fresh())->delete(route('profile.destroy'), ['password' => 'kata-sandi-lama'])->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $this->assertFalse(Storage::disk(FotoProfil::disk())->exists($path));
    }
}
