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
     * Ruling AF-2 (audit E2E I-2, daftar periksa butir 7): tombol yang difokus menjadi `disabled`
     * selama `processing`, dan peramban menjatuhkan fokusnya ke body. Button karena itu mencatat
     * bila ia diaktifkan pengguna (onClick, termasuk klik sintetis submit implisit), dan saat
     * `processing` kembali false sesudah sempat true, fokus kembali ke tombol itu hanya bila fokus
     * sedang di body atau tidak ada dan tombolnya masih terpasang. `disabled` tidak berubah, jadi
     * kiriman ganda tetap tercegah, dan onClick pemanggil tetap berjalan sesudah tanda dicatat.
     */
    public function test_a_button_takes_its_focus_back_when_its_action_finishes(): void
    {
        $tombol = $this->tombol();

        $this->assertMatchesRegularExpression(
            '/useEffect\(\(\) => \{\s*if \(sibuk === sibukLalu\.current\) \{\s*return;\s*\}\s*sibukLalu\.current = sibuk;\s*if \(sibuk \|\| !ditekan\.current\) \{\s*return;\s*\}\s*ditekan\.current = false;\s*const aktif = document\.activeElement;\s*if \(\(aktif === null \|\| aktif === document\.body\) && tombol\.current\?\.isConnected\) \{\s*tombol\.current\.focus\(\);\s*\}\s*\}, \[sibuk\]\);/',
            $tombol,
        );
        $this->assertStringContainsString('const { tombol, tandai } = useFokusKembali(processing);', $tombol);
        $this->assertMatchesRegularExpression('/<button\s+ref=\{tombol\}[^>]*?onClick=\{\(e\) => \{\s*tandai\(\);\s*onClick\?\.\(e\);\s*\}\}\s*\{\.\.\.rest\}\s*>/s', $tombol);
        $this->assertStringContainsString('disabled={disabled || processing}', $tombol);

        // TombolIkon tidak punya processing: TombolSimpan memakai hook yang sama untuk bentuk ikon dan bar.
        $simpan = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));
        $this->assertStringContainsString('const fokusKembali = useFokusKembali(memproses);', $simpan);
        $this->assertMatchesRegularExpression('/const tekan = \(e: MouseEvent<HTMLButtonElement>\) => \{\s*papanKetik\.current = e\.detail === 0;\s*pemicu\.current = e\.currentTarget;\s*fokusKembali\.tandai\(e\);/', $simpan);
        // Urungkan sesudah lepas menyimpan lagi lewat tombol yang sama; tombol itu `disabled` saat
        // toast mengembalikan fokus kepadanya, jadi tandanya dicatat supaya fokus tidak jatuh ke body.
        $this->assertMatchesRegularExpression('/\(\) => \{\s*fokusKembali\.tandai\(\);\s*simpan\(terhapus\?\.catatan \?\? null, false\);\s*\}/', $simpan);
        // Kartu Simpanan lepas bersama tombol hapusnya, jadi fokus pindah ke Urungkan juga dari tetikus.
        $this->assertStringContainsString("{ fokus: papanKetik.current || bentuk === 'hapus' },", $simpan);
    }

    /**
     * Ruling AF-2: aksi yang melepas tombolnya sendiri memberi tujuan fokus yang disengaja.
     * - "Kosongkan pencarian" memfokus kotak cari di penangan yang sama;
     * - "Buat tautan" memfokus blok tautan baru, yang bernama judulnya dan dijelaskan URL-nya, dan
     *   pengumuman server dibacakan wilayah status di dalam lembar (wilayah halaman inert selama
     *   dialog modal terbuka); "Cabut" memfokus wilayah status itu;
     * - "Saya sudah meninjau semuanya" yang berhasil memfokus kalimat "Tinjauan tercatat".
     */
    public function test_actions_that_remove_their_own_button_move_focus_on_purpose(): void
    {
        $cari = File::get(resource_path('js/components/trails/jelajah/KotakCari.tsx'));
        $this->assertMatchesRegularExpression("/onClick=\{\(\) => \{\s*onUbah\(''\);\s*isian\.current\?\.focus\(\);\s*\}\}/", $cari);
        $this->assertStringContainsString('ref={isian}', $cari);

        $bagikan = File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx'));
        $this->assertMatchesRegularExpression('/onSuccess: \(halaman\) => \{\s*setKabar\(pengumumanDari\(halaman\.props\)\);\s*if \(\(halaman\.props as unknown as PropsBersama\)\.flash\.tautanBagikan\) \{\s*fokusKe\(idBaru\);\s*\}\s*\},/', $bagikan);
        $this->assertMatchesRegularExpression('/onSuccess: \(halaman\) => \{\s*setKabar\(pengumumanDari\(halaman\.props\)\);\s*fokusKe\(idKabar\);\s*\},/', $bagikan);
        $this->assertMatchesRegularExpression('/<div\s+id=\{idBaru\}\s+role="group"\s+tabIndex=\{-1\}\s+aria-labelledby=\{`\$\{idBaru\}-judul`\}\s+aria-describedby=\{`\$\{idBaru\}-url`\}/', $bagikan);
        $this->assertStringContainsString('<p id={idKabar} role="status" tabIndex={-1}', $bagikan);

        $kesiapan = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString("onSuccess: () => fokusKe('tinjauan-tercatat')", $kesiapan);
        $this->assertStringContainsString('<p id="tinjauan-tercatat" tabIndex={-1}', $kesiapan);
    }

    /**
     * Ruling CF-6 (audit E2E CU-1): sesudah Urungkan berhasil pada bentuk hapus, fokus pindah ke
     * tombol hapus kartu Simpanan yang dipulihkan, bukan tertinggal di #konten. Kartu itu instans
     * baru, jadi tujuannya id stabil per jalur pada tombol hapus. Pemindahan ada di onSuccess
     * kiriman simpan ulang (halaman baru sudah dirender karena swap Inertia memakai flushSync), hanya
     * bila fokus masih di body atau #konten, jadi fokus yang sudah dipindah pengguna tidak direbut.
     * Bila kartunya tidak ada, getElementById kosong dan cadangan #konten dari toast tetap.
     */
    public function test_undoing_a_removal_in_saved_returns_focus_to_the_restored_card(): void
    {
        $simpan = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('const idHapus = `hapus-simpanan-${jalur.id}`;', $simpan);
        $this->assertStringContainsString('<Button id={idHapus} variant="ghost" size="sm" processing={memproses} onClick={tekan}>', $simpan);
        $this->assertMatchesRegularExpression(
            "/if \(!urungkan && !ditolak && bentuk === 'hapus'\) \{\s*const aktif = document\.activeElement;\s*if \(aktif === null \|\| aktif === document\.body \|\| aktif\.id === 'konten'\) \{\s*document\.getElementById\(idHapus\)\?\.focus\(\);\s*\}\s*\}/",
            $simpan,
        );
        // Urungkan pada bentuk hapus memanggil simpan dengan urungkan false; tanpa itu cabang di atas mati.
        $this->assertMatchesRegularExpression('/\(\) => \{\s*fokusKembali\.tandai\(\);\s*simpan\(terhapus\?\.catatan \?\? null, false\);\s*\}/', $simpan);
    }

    /**
     * Ruling CF-5 (tinjauan TA-m7, daftar periksa butir 7): tombol yang menonaktifkan dirinya
     * sendiri karena aksinya sedang berjalan memakai `processing`, bukan `disabled` saja, karena
     * useFokusKembali di Button hanya mengikuti `processing`. Tanpa itu tombol yang difokus
     * menjadi nonaktif dan fokusnya jatuh ke body.
     *
     * Kelas yang disapu: deretan tombol aksi yang berbagi satu keadaan sibuk
     * (`disabled={sibuk !== null}`, `disabled={sedang !== null}`). Tombol yang ditekan memegang
     * `processing`, tombol lain tetap `disabled`. Sapuan, bukan daftar: Pratinjau kandidat OSM di
     * Impor Geometri dahulu hanya `disabled` (TA-m7).
     *
     * Sengaja tidak termasuk: tombol Batal atau Kembali yang `disabled` selama aksi tombol LAIN
     * berjalan (bukan aksinya sendiri), dan tombol yang `disabled` oleh keadaan data, bukan
     * keadaan sibuk. Daftar lengkapnya di laporan audit-fix-C.
     */
    public function test_a_button_disabled_by_a_shared_busy_state_holds_its_own_processing(): void
    {
        $tanpaStatus = [];
        $diperiksa = 0;

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($berkas->getContents(), 'Button') as [$tag, $baris]) {
                if (! preg_match('/\bdisabled=\{\w+ !== null\}/', $tag)) {
                    continue;
                }

                $diperiksa++;

                if (! preg_match('/\bprocessing=\{/', $tag)) {
                    $tanpaStatus[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertGreaterThanOrEqual(5, $diperiksa, 'Sapuan tidak menemukan deretan tombol bersibuk-bersama untuk diperiksa.');
        $this->assertSame([], $tanpaStatus, 'Tombol yang menonaktifkan dirinya tanpa processing: '.implode(', ', $tanpaStatus));

        $geometri = File::get(resource_path('js/pages/Admin/TrailGeometry.tsx'));
        $this->assertStringContainsString("processing={sibuk === 'pilih' && dipratinjau === c.id}", $geometri, 'Hanya Pratinjau kandidat yang ditekan yang memproses.');
        $this->assertMatchesRegularExpression("/onClick=\{\(\) => \{\s*setDipratinjau\(c\.id\);\s*kirim\('pilih', choose\.url\(\{ trail: trail\.id, osm: c\.id \}\)\);\s*\}\}/", $geometri);
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
            // W6: dialog BatalkanTrip (alasan opsional DK-50) menggantikan window.confirm.
            // Putaran perbaikan 1 (ruling W6-5f): keterangan kini bercabang menurut
            // trip.berlangsung, jadi anchor memeriksa kalimat itu sendiri, bukan lagi atribut
            // keterangan="..." yang literal (sekarang sebuah ekspresi ternary).
            'batalkan trip' => ['components/trips/BatalkanTrip.tsx', 'Trip yang dibatalkan tidak dapat dimulai lagi. Alasannya opsional dan hanya untuk catatan Anda sendiri.'],
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
