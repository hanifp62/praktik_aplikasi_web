<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kriteria feedback dan error prevention.
 *
 * Dari 27 tampilan Livewire, hanya satu yang dahulu punya indikator sedang memproses.
 * Di jaringan tipis pengguna menekan "Simpan", tidak ada yang terlihat berubah, lalu ia
 * menekannya lagi. Aksi ganda itu bukan kesalahan pengguna, melainkan akibat antarmuka
 * yang diam.
 *
 * Perbaikannya diletakkan di komponen tombol, bukan ditambal satu per satu, supaya
 * perilakunya seragam dan tetap seragam ketika halaman baru ditambahkan. Di React
 * komponennya Button.tsx: prop processing menonaktifkan tombol dan menampilkan
 * penandanya, dan setiap tombol kirim meneruskan status formulirnya sendiri.
 */
class ButtonFeedbackTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    public function test_a_button_disables_itself_while_its_action_runs(): void
    {
        $tombol = $this->tombol();

        $this->assertStringContainsString('disabled={disabled || processing}', $tombol);
        $this->assertStringContainsString('aria-busy={processing || undefined}', $tombol);
    }

    /**
     * Setiap tombol kirim menunggu formulirnya sendiri.
     *
     * Tombol yang tidak diberi status formulirnya tetap dapat ditekan berulang selama
     * permintaannya berjalan, dan itu persis aksi ganda yang hendak dicegah. Sapuan,
     * bukan daftar: halaman yang ditulis besok ikut diperiksa.
     */
    public function test_every_submit_button_waits_for_its_own_form(): void
    {
        $tanpaStatus = [];
        $diperiksa = 0;

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($berkas->getContents(), 'Button') as [$tag, $baris]) {
                if (! str_contains($tag, 'type="submit"')) {
                    continue;
                }

                $diperiksa++;

                if (! preg_match('/\bprocessing=\{/', $tag)) {
                    $tanpaStatus[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertGreaterThan(15, $diperiksa, 'Sapuan tidak menemukan tombol kirim untuk diperiksa.');
        $this->assertSame([], $tanpaStatus, 'Tombol kirim tanpa status memproses: '.implode(', ', $tanpaStatus));
    }

    public function test_the_indicator_is_announced_to_screen_readers(): void
    {
        $tombol = $this->tombol();

        $this->assertMatchesRegularExpression('/<svg[^>]*animate-spin[^>]*aria-hidden="true"/', $tombol);
        $this->assertStringContainsString('<span className="sr-only">Memproses</span>', $tombol);
    }

    /**
     * Penandanya hanya ada selama aksinya berjalan. Tombol yang diam tidak boleh
     * membacakan "Memproses" kepada pembaca layar, dan pemutar yang tertinggal membuat
     * orang menunggu sesuatu yang tidak sedang terjadi.
     */
    public function test_the_indicator_exists_only_while_the_action_runs(): void
    {
        $this->assertMatchesRegularExpression(
            '/\{processing \? \(\s*<span[^>]*>\s*<svg[^>]*animate-spin/',
            $this->tombol(),
            'Pemutar harus dirender hanya ketika processing bernilai benar.'
        );
    }

    /**
     * Tautan bukan aksi yang ditunggu, jadi tidak boleh membawa atribut yang tidak
     * berlaku: tautan yang dinonaktifkan atau "sibuk" hanya membingungkan pembaca layar.
     */
    public function test_a_link_button_carries_no_loading_attributes(): void
    {
        $tombol = $this->tombol();
        $mulai = strpos($tombol, 'if (href) {');

        $this->assertNotFalse($mulai, 'Cabang tautan Button tidak ditemukan.');

        $cabangTautan = substr($tombol, $mulai, strpos($tombol, '<button', $mulai) - $mulai);

        foreach (['aria-busy', 'disabled', 'processing', 'animate-spin'] as $atribut) {
            $this->assertStringNotContainsString($atribut, $cabangTautan);
        }
    }

    /**
     * Aksi yang menghapus atau menghilangkan sesuatu dari hadapan pendaki bertanya
     * dulu, dan pertanyaannya menyebut akibatnya.
     *
     * Komponen tombol Blade dahulu membawa prop confirm. Tombol React tidak: konfirmasi
     * dipasang di halaman pemilik aksinya, lewat konfirmasiLalu() atau window.confirm,
     * jadi yang dijaga adalah setiap aksi itu satu per satu. Keputusan kredensial dan
     * moderasi membaca teks konfirmasinya dari server.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function aksiMerusak(): array
    {
        return [
            'hapus checkpoint' => ['pages/Admin/Checkpoints.tsx', "konfirmasiLalu('Hapus checkpoint ini?'"],
            'hapus aturan perizinan' => ['pages/Admin/Permits.tsx', "window.confirm('Hapus aturan perizinan ini?')"],
            'arsipkan gunung' => ['pages/Admin/Mountains.tsx', "konfirmasiLalu(baris.archived ? null : 'Arsipkan gunung ini? Seluruh jalurnya ikut hilang dari pencarian pendaki.'"],
            'batalkan trip' => ['pages/Trips/Show.tsx', "window.confirm('Batalkan trip ini? Trip yang dibatalkan tidak dapat dimulai lagi.')"],
            'hapus jejak pendakian' => ['pages/Community/HikeSummary.tsx', "window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')"],
            'keputusan kredensial' => ['pages/Admin/Credentials.tsx', 'if (k.konfirmasi && !window.confirm(k.konfirmasi))'],
            'tindakan moderasi' => ['pages/Moderation/Queue.tsx', 'if (t.konfirmasi && !window.confirm(t.konfirmasi))'],
        ];
    }

    #[DataProvider('aksiMerusak')]
    public function test_a_destructive_action_asks_before_it_runs(string $berkas, string $pertanyaan): void
    {
        $this->assertStringContainsString($pertanyaan, File::get(resource_path('js/'.$berkas)));
    }

    /**
     * Kelola Jalur kini halaman React: konfirmasinya window.confirm lewat konfirmasiLalu(),
     * dirender di klien. Yang dapat dijaga dari server adalah halamannya terbuka dengan
     * baris jalurnya, dan dari sumbernya bahwa arah yang menghilangkan jalur dari hadapan
     * pendaki (arsip, tarik publikasi) bertanya dulu dengan menyebut akibatnya.
     */
    public function test_archiving_a_trail_asks_for_confirmation_in_the_admin_list(): void
    {
        Trail::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))
            ->get('/admin/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Trails')->has('trails.data', 1));

        $halaman = File::get(resource_path('js/pages/Admin/Trails.tsx'));
        $this->assertStringContainsString("konfirmasiLalu(j.archived ? null : 'Arsipkan jalur ini? Jalur yang diarsipkan hilang dari pencarian", $halaman);
        $this->assertStringContainsString("konfirmasiLalu(j.published ? 'Tarik jalur ini dari publikasi? Pendaki tidak akan menemukannya lagi", $halaman);
        $this->assertStringContainsString('window.confirm(pesan)', File::get(resource_path('js/components/admin/FormulirAdmin.tsx')));
    }

    private function tombol(): string
    {
        return File::get(resource_path('js/components/ui/Button.tsx'));
    }
}
