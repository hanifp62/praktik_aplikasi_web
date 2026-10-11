<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Header per halaman (spec v3 bagian 5.1; rencana induk W2 butir 1; ruling W2-R1).
 *
 * Ponsel tidak punya bilah atas global. Setiap halaman membawa tempatnya sendiri: akar tab
 * memberi judul dan avatar ke Profil, halaman turunan memberi judul dan tombol kembali ke
 * induk logisnya, bukan ke riwayat peramban. Halaman dirender di klien, jadi kontraknya
 * dijaga dari sumber; PageHeader juga mewajibkan `akar` atau `induk` lewat tipenya, sehingga
 * `npx tsc --noEmit` menolak halaman baru yang lupa menyebut tempatnya.
 */
class PageHeaderContractTest extends TestCase
{
    use SumberReact;

    /**
     * Lima akar tab pemilik (DK-25) dan label tabnya. Beranda memakai sapaan sebagai judul
     * dan lockup di baris atasnya (prototype 01), jadi judulnya tidak disamakan dengan tab.
     *
     * @var array<string, string|null>
     */
    private const AKAR = [
        'Dashboard.tsx' => null,
        'Trails/Index.tsx' => 'Jelajah',
        'Trips/Index.tsx' => 'Trip',
        'Track/Index.tsx' => 'Mendaki',
        'Community/HikerProgress.tsx' => 'Progres',
    ];

    public function test_every_page_names_its_title_and_its_place(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach ($this->halamanBerkerangka() as $nama => $isi) {
            $diperiksa++;

            // Mode lapangan memakai tata letaknya sendiri (W6): judul h1 dan tombol kembali
            // ke rincian trip lewat prop kembali.
            if (str_contains($isi, '<TataLetakPendakian')) {
                if (! preg_match('/<TataLetakPendakian\s[^>]*\bkembali=\{/', $isi) || substr_count($isi, '<h1') !== 1) {
                    $pelanggar[] = "{$nama}: mode lapangan tanpa kembali= atau tanpa tepat satu h1";
                }

                continue;
            }

            $kepala = $this->tagBernama($isi, 'PageHeader');

            if (count($kepala) !== 1) {
                $pelanggar[] = "{$nama}: ".count($kepala).' PageHeader, seharusnya satu';

                continue;
            }

            [$tag] = $kepala[0];

            if (! preg_match('/\btitle=(?:"[^"]*\S[^"]*"|\{)/', $tag)) {
                $pelanggar[] = "{$nama}: PageHeader tanpa judul";
            }

            $akar = preg_match('/\sakar(?=[\s\/>])/', $tag) === 1;
            $induk = preg_match('/\sinduk=\{/', $tag) === 1;

            if ($akar === $induk) {
                $pelanggar[] = "{$nama}: PageHeader harus memberi tepat satu dari akar atau induk";
            }
        }

        $this->assertGreaterThan(35, $diperiksa, 'Sapuan tidak menemukan halaman untuk diperiksa.');
        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    /**
     * Hanya lima akar tab yang boleh menjadi akar. Judul akar tab sama dengan label tabnya,
     * supaya kata di bilah tab yang menyala dan kata di kepala halaman tidak berbeda.
     */
    public function test_only_the_five_tab_roots_are_roots_and_they_carry_their_tab_name(): void
    {
        $akar = [];

        foreach ($this->halamanBerkerangka() as $nama => $isi) {
            foreach ($this->tagBernama($isi, 'PageHeader') as [$tag]) {
                if (preg_match('/\sakar(?=[\s\/>])/', $tag)) {
                    $akar[$nama] = $tag;
                }
            }
        }

        $this->assertEqualsCanonicalizing(array_keys(self::AKAR), array_keys($akar));

        foreach (self::AKAR as $nama => $label) {
            if ($label === null) {
                $this->assertMatchesRegularExpression('/\smerek(?=[\s\/>])/', $akar[$nama], 'Beranda memasang lockup di baris atas (prototype 01).');

                continue;
            }

            $this->assertStringContainsString('title="'.$label.'"', $akar[$nama], "{$nama} berjudul label tabnya.");
        }
    }

    /**
     * Tombol kembali adalah tautan ke induk logis, bukan history.back(): halaman yang dibuka
     * dari tautan bagikan, surel, atau tab baru tidak punya riwayat. Lingkaran 44 px, ikon
     * arrow-left 20 px, dan nama "Kembali ke ..." bagi pembaca layar.
     */
    public function test_the_back_button_goes_to_the_logical_parent(): void
    {
        $tombol = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/TombolKembali.tsx')));
        $kepala = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertStringContainsString('<Tautan', $tombol);
        $this->assertStringContainsString('href={href}', $tombol);
        $this->assertStringContainsString('h-11 w-11', $tombol);
        $this->assertStringContainsString('<Icon name="arrow-left" size={20} />', $tombol);
        $this->assertStringContainsString('<span className="sr-only">Kembali ke {label}</span>', $tombol);
        $this->assertStringContainsString('<TombolKembali href={tempat.induk.href} label={tempat.induk.label} />', $kepala);
        $this->assertStringContainsString('type PageHeaderProps = DasarKepala & ({ akar: true; merek?: boolean; induk?: never } | { induk: Induk; akar?: never; merek?: never });', $kepala);

        foreach ([$tombol, $kepala] as $isi) {
            $this->assertDoesNotMatchRegularExpression('/history\.back|window\.history|router\.back/', $isi);
        }
    }

    /**
     * Profil dibuka dari avatar 44 px di setiap akar tab (ruling W2-R1), bukan dari tab
     * keenam. Mulai 1024 px menu avatar di header layar lebar yang mengambil alih, jadi
     * avatar di kepala halaman disembunyikan di sana.
     */
    public function test_a_tab_root_reaches_the_profile_from_its_avatar(): void
    {
        $tautan = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/TautanProfil.tsx')));
        $kepala = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertStringContainsString('href={profile.url()}', $tautan);
        $this->assertStringContainsString('h-11 w-11', $tautan);
        $this->assertStringContainsString('<Avatar nama={auth.user.name} ukuran={44} />', $tautan);
        $this->assertStringContainsString('<span className="sr-only">Profil {auth.user.name}</span>', $tautan);
        $this->assertStringContainsString('{tempat.akar ? <TautanProfil className="lg:hidden" /> : null}', $kepala);
        $this->assertStringContainsString('{tempat.merek ? <Lockup className="lg:hidden" /> : null}', $kepala);
    }

    /**
     * Menu hamburger ponsel dilepas W2, jadi Moderasi, Admin, dan Keluar harus tetap
     * terjangkau dari Profil: pintunya dijaga peran, Keluar untuk semua.
     */
    public function test_the_profile_page_keeps_the_console_doors_and_sign_out(): void
    {
        $pintu = $this->tanpaKomentarJs(File::get(resource_path('js/components/akun/PintuAkun.tsx')));

        $this->assertStringContainsString('<PintuAkun />', File::get(resource_path('js/pages/Profile/Edit.tsx')));
        $this->assertStringContainsString('{pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}', $pintu);
        $this->assertStringContainsString('{pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}', $pintu);
        $this->assertStringContainsString("onClick={() => router.post('/logout')}", $pintu);
        $this->assertMatchesRegularExpression('/>\s*Keluar\s*<\/button>/', $pintu);
    }

    /**
     * Isi setiap halaman berkerangka: semua di resources/js/pages kecuali Auth (GuestLayout)
     * dan Welcome (halaman depan tanpa navigasi aplikasi).
     *
     * @return array<string, string>
     */
    private function halamanBerkerangka(): array
    {
        $hasil = [];

        foreach ($this->berkasTsx('pages') as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (str_starts_with($nama, 'Auth/') || $nama === 'Welcome.tsx') {
                continue;
            }

            $hasil[$nama] = $this->tanpaKomentarJs($berkas->getContents());
        }

        ksort($hasil);

        return $hasil;
    }
}
