<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Primitif desain v3 (spec v3 bagian 8.4 sampai 8.8; rencana induk W1 butir 4).
 *
 * Proyek belum punya test runner JavaScript dan dependensi baru tidak diizinkan (DK-24),
 * jadi kontrak setiap primitif dijaga dari sumbernya: fokus terlihat, target sentuh
 * 44 px, nama aksesibel, keadaan yang tidak dibawa warna saja, dan tiga jenis keadaan
 * kosong. Perilaku yang hanya terlihat di layar diperiksa di pameran primitif dan
 * tangkapan pratinjau.
 */
class DesignPrimitivesTest extends TestCase
{
    use SumberReact;

    /** Penanda fokus terlihat yang diakui: kelas bersama gaya.ts, cincin langsung, atau primitif yang membawanya. */
    private const POLA_FOKUS = '/\bfokusTerlihat\b|\bfokusKelompok\b|\bfokusDiDalam\b|\bfocus-visible:ring-2\b|\bfocus:ring-brand-600\b|<Checkbox\b|<TombolIkon\b/';

    /** Penanda target sentuh paling sedikit 44 px. */
    private const POLA_TARGET = '/(?<![\w:-])(?:min-h-11|min-h-12|h-1[1-6])(?![\w-])|<TombolIkon\b/';

    /**
     * Primitif yang menerima fokus atau sentuhan.
     *
     * @return array<string, array{0: string}>
     */
    public static function primitifInteraktif(): array
    {
        return [
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
            'lembar dan dialog' => ['components/ui/Lembar.tsx'],
            'toast urungkan' => ['components/ui/ToastUrungkan.tsx'],
            'bilah tab' => ['components/ui/BilahTab.tsx'],
        ];
    }

    #[DataProvider('primitifInteraktif')]
    public function test_every_interactive_primitive_shows_a_visible_focus(string $berkas): void
    {
        $this->assertMatchesRegularExpression(self::POLA_FOKUS, $this->sumber($berkas), "{$berkas} tanpa fokus terlihat.");
    }

    #[DataProvider('primitifInteraktif')]
    public function test_every_interactive_primitive_offers_a_44px_target(string $berkas): void
    {
        $this->assertMatchesRegularExpression(self::POLA_TARGET, $this->sumber($berkas), "{$berkas} tanpa target sentuh 44 px.");
    }

    /**
     * Kelas bersama membawa cincin fokus merek dan tekan 0,98 selama 120 ms lewat token
     * gerak, sehingga setelan kurangi gerak di app.css memangkasnya tanpa cabang sendiri.
     */
    public function test_the_shared_classes_carry_focus_press_and_reduced_motion(): void
    {
        $gaya = $this->sumber('components/ui/gaya.ts');

        $this->assertStringContainsString("fokusTerlihat = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2'", $gaya);
        $this->assertStringContainsString('active:scale-[0.98]', $gaya);
        $this->assertStringContainsString('group-active:scale-[0.98]', $gaya);
        $this->assertSame(2, substr_count($gaya, 'duration-sentuh ease-gerak'), 'Tekan memakai token durasi dan kurva gerak.');
        $this->assertDoesNotMatchRegularExpression('/\banimate-|\bduration-\d/', $gaya, 'Gerak primitif hanya lewat token bernama.');
    }

    /**
     * Tombol ikon selalu bernama (WCAG 4.1.2): prop label wajib dan dirender sebagai teks
     * sr-only, dan setiap pemakaiannya menyebut label. Pola barisnya memakai \R dan \s+,
     * bukan LF dan empat spasi, supaya berkas bersimpan CRLF tidak membuatnya merah tanpa
     * perubahan perilaku (tinjauan akhir W1, M-8).
     */
    public function test_an_icon_button_always_carries_a_readable_name(): void
    {
        $tombol = $this->sumber('components/ui/TombolIkon.tsx');

        $this->assertMatchesRegularExpression('/\R\s+label: string;/', $tombol, 'Label tombol ikon wajib, bukan opsional.');
        $this->assertStringContainsString("Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children' | 'aria-label'>", $tombol);
        $this->assertStringContainsString('<span className="sr-only">{label}</span>', $tombol);

        $tanpaLabel = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($this->tanpaKomentarJs($berkas->getContents()), 'TombolIkon') as [$tag, $baris]) {
                if (! preg_match('/\blabel=(?:"[^"]*\S[^"]*"|\{)/', $tag)) {
                    $tanpaLabel[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertSame([], $tanpaLabel, 'Tombol ikon tanpa label: '.implode(', ', $tanpaLabel));
    }

    /**
     * Chip terpilih ditandai centang, tebal huruf, garis, dan aria-pressed, bukan warna
     * saja (WCAG 1.4.1); chip saringan aktif menyebut "Lepas saringan" kepada pembaca layar.
     */
    public function test_a_chip_states_its_selection_without_colour_alone(): void
    {
        $chip = $this->sumber('components/ui/Chip.tsx');

        $this->assertStringContainsString('aria-pressed={dipilih}', $chip);
        $this->assertStringContainsString('{dipilih ? <Icon name="check" size={16} /> : null}', $chip);
        $this->assertMatchesRegularExpression("/dipilih \? '[^']*\bfont-bold\b[^']*' : '[^']*\bfont-semibold\b/", $chip);
        $this->assertStringContainsString('<span className="sr-only">Lepas saringan </span>', $chip);
    }

    /**
     * Ruling W1-5a (putaran perbaikan 1): cacat bawaan rencana ditemukan peninjau.
     * `pembungkus` adalah target sentuh tak terlihat di belakang bentuk 36 px yang
     * terlihat (docblock ChipPilihan menjanjikan "bentuk 36 px di dalam target sentuh
     * 44 px", W1-K5). Sebelum perbaikan ini `pembungkus` hanya mengunci tinggi
     * (min-h-11) tanpa lebar minimum, sehingga chip berlabel pendek (satu atau dua
     * huruf) bisa punya target lebih sempit dari 44 px (WCAG 2.5.8). Kedua sumbu wajib
     * dikunci di konstanta yang sama, bukan hanya di suatu tempat dalam berkas.
     */
    public function test_a_chips_invisible_wrapper_locks_both_touch_target_axes(): void
    {
        $chip = $this->sumber('components/ui/Chip.tsx');

        $this->assertMatchesRegularExpression("/const pembungkus = '[^']*';/", $chip, 'Konstanta pembungkus Chip tidak ditemukan.');

        preg_match("/const pembungkus = '([^']*)';/", $chip, $cocok);
        $nilai = $cocok[1] ?? '';

        $this->assertStringContainsString('min-h-11', $nilai, 'Pembungkus Chip kehilangan penjaga tinggi target sentuh 44 px.');
        $this->assertStringContainsString(
            'min-w-11',
            $nilai,
            'Pembungkus Chip kehilangan penjaga lebar target sentuh 44 px: chip berlabel pendek bisa lebih sempit dari 44 px (WCAG 2.5.8, W1-K5, Ruling W1-5a).'
        );
    }

    /**
     * Tiga jenis keadaan kosong (PRD §104, antislop R-27): pertama kali, tersaring, dan
     * menunggu. Jenis menunggu wajib menyebut siapa yang ditunggu, dan setiap pemakaian
     * menyebut jenisnya.
     */
    public function test_the_empty_state_has_three_kinds_and_names_who_is_awaited(): void
    {
        $kosong = $this->sumber('components/ui/EmptyState.tsx');

        $this->assertStringContainsString("(IsiKosong & { jenis: 'pertama' | 'tersaring' }) | (IsiKosong & { jenis: 'menunggu'; menunggu: string })", $kosong);
        $this->assertStringContainsString("{props.jenis === 'menunggu' ? <p", $kosong);
        $this->assertStringContainsString('{props.menunggu}</p>', $kosong);
        $this->assertStringContainsString('data-kosong={jenis}', $kosong);

        $tanpaJenis = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($this->tanpaKomentarJs($berkas->getContents()), 'EmptyState') as [$tag, $baris]) {
                if (! preg_match('/\bjenis="(?:pertama|tersaring|menunggu)"/', $tag)) {
                    $tanpaJenis[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertSame([], $tanpaJenis, 'Keadaan kosong tanpa jenis: '.implode(', ', $tanpaJenis));
    }

    /**
     * Basis data pemilik belum punya jalur terbit (0 jalur bergeometri). Jelajah tanpa
     * saringan yang kosong harus menyebut siapa yang ditunggu, bukan "Tidak ada jalur yang
     * cocok" seolah saringan penyebabnya; saringan yang kosong menawarkan pelepasnya.
     */
    public function test_jelajah_names_who_is_awaited_when_nothing_is_published(): void
    {
        $jelajah = $this->tanpaKomentarJs($this->sumber('pages/Trails/Index.tsx'));

        // W1-F1: "menunggu" diperiksa lebih dulu dari jumlah jalur terbit yang tidak disaring,
        // baru sesudahnya "tersaring"; saringan tidak pernah disalahkan atas katalog kosong.
        $this->assertMatchesRegularExpression('/\{jumlahTerbit === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $jelajah);
        $this->assertMatchesRegularExpression('/\) : trails\.data\.length === 0 \? \(\s*<EmptyState\s+jenis="tersaring"/', $jelajah);
        $this->assertLessThan(strpos($jelajah, 'jenis="tersaring"'), strpos($jelajah, 'jenis="menunggu"'));
        $this->assertMatchesRegularExpression('/jenis="tersaring"[\s\S]*?Tampilkan juga[\s\S]*?Hapus semua saringan/', $jelajah);
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"', $jelajah);
        $this->assertStringNotContainsString('Tidak ada jalur yang cocok', $jelajah);
    }

    /**
     * Lencana berbicara dengan huruf kalimat tebal 13 px, bukan kapital bertracking lebar
     * (spec v3 bagian 8.2; ciri klaster "tampilan AI"). Tidak ada kapital yang diketik di
     * dalam lencana mana pun.
     */
    public function test_a_badge_speaks_in_sentence_case(): void
    {
        $lencana = $this->tanpaKomentarJs($this->sumber('components/ui/Lencana.tsx'));

        $this->assertStringContainsString('text-meta font-bold', $lencana);
        $this->assertDoesNotMatchRegularExpression('/\buppercase\b|\btracking-/', $lencana);

        $kapital = [];

        foreach ($this->berkasTsx() as $berkas) {
            preg_match_all('/<Lencana\b[^>]*>\s*([^<{]+?)\s*</', $this->tanpaKomentarJs($berkas->getContents()), $isi);

            foreach ($isi[1] as $teks) {
                if (preg_match('/^[A-Z][A-Z ]{2,}$/', $teks)) {
                    $kapital[] = $berkas->getRelativePathname().': '.$teks;
                }
            }
        }

        $this->assertSame([], $kapital, 'Lencana berhuruf kapital: '.implode(', ', $kapital));
    }

    /**
     * Avatar berinisial untuk data contoh (antislop R-23). Di samping nama tertulis ia
     * disembunyikan dari pembaca layar; berdiri sendiri, ia bernama.
     */
    public function test_an_avatar_draws_initials_and_hides_beside_a_name(): void
    {
        $avatar = $this->sumber('components/ui/Avatar.tsx');

        $this->assertStringContainsString("toLocaleUpperCase('id-ID')", $avatar);
        $this->assertStringContainsString('hiasan = true', $avatar);
        $this->assertStringContainsString('<span aria-hidden="true" className={dasar}>', $avatar);
        $this->assertStringContainsString('<span role="img" aria-label={nama} className={dasar}>', $avatar);
        $this->assertStringContainsString("alt={hiasan ? '' : nama}", $avatar);
    }

    /**
     * Tombol v3: radius tombol 16 px, label tebal yang membungkus di tengah, tekan 0,98, dan
     * teks on-brand di atas isian merek (spec v3 bagian 8.1, 8.4, dan 8.6). Akhir baris
     * dicocokkan dengan \R, bukan LF saja (tinjauan akhir W1, M-8).
     */
    public function test_the_button_follows_the_v3_shape_weight_and_press(): void
    {
        $tombol = $this->sumber('components/ui/Button.tsx');

        $this->assertMatchesRegularExpression("/const semua = kelas\(\s*'[^']*\brounded-tombol\b[^']*\btext-center\b[^']*\bfont-bold\b[^']*',/", $tombol);
        $this->assertMatchesRegularExpression('/\bumpanTekan,\R/', $tombol);
        $this->assertStringContainsString("primary: 'bg-brand-700 text-on-brand hover:bg-brand-800", $tombol);
    }

    /**
     * Tombol hantu kecil yang ditarik ke tepi kolom dengan margin negatif membatalkan padding
     * kirinya sendiri, supaya labelnya rata dengan teks di atasnya (M-6). Sejak bentuk v3,
     * tombol sm berpadding px-4; offset -ml-3 peninggalan px-3 menggeser labelnya 4 px dari
     * tepi kolom. Padding dibaca dari Button.tsx, jadi keduanya tidak dapat berpisah lagi.
     */
    public function test_a_ghost_button_pulled_to_the_column_edge_cancels_its_own_padding(): void
    {
        $this->assertSame(1, preg_match("/size === 'sm' \? 'px-(\d+) /", $this->sumber('components/ui/Button.tsx'), $padding));

        $meleset = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($this->tanpaKomentarJs($berkas->getContents()), 'Button') as [$tag, $baris]) {
                if (! str_contains($tag, 'variant="ghost"') || ! str_contains($tag, 'size="sm"')) {
                    continue;
                }

                preg_match_all('/(?<![\w-])-ml-(\d+)\b/', $tag, $offset);

                foreach ($offset[1] as $nilai) {
                    if ($nilai !== $padding[1]) {
                        $meleset[] = $berkas->getRelativePathname().':'.$baris." (-ml-{$nilai}, padding px-{$padding[1]})";
                    }
                }
            }
        }

        $this->assertSame([], $meleset, 'Tombol hantu kecil yang tidak rata dengan kolomnya: '.implode(', ', $meleset));
    }

    /**
     * Kontrol segmen adalah grup radio asli: panah papan ketik berpindah pilihan dan pembaca
     * layar menyebut posisinya tanpa ARIA buatan. Terpilih ditandai permukaan, garis, dan
     * tebal huruf. W4 Tugas 3 menambah aria-describedby opsional pada fieldset itu, supaya
     * galat validasi jenis trip di halaman Rencanakan terikat ke grupnya; grupnya tetap asli.
     */
    public function test_the_segmented_control_is_a_native_radio_group(): void
    {
        $segmen = $this->sumber('components/ui/KontrolSegmen.tsx');

        $this->assertStringContainsString('<fieldset className={className} aria-describedby={penjelas}>', $segmen);
        $this->assertStringContainsString('<legend className="sr-only">{label}</legend>', $segmen);
        $this->assertStringContainsString('type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai}', $segmen);
        $this->assertStringContainsString('has-[:checked]:border-control has-[:checked]:bg-surface has-[:checked]:font-bold', $segmen);
        $this->assertStringNotContainsString('role="tab', $segmen);
    }

    /**
     * Ruling koordinator W1-6a (putaran perbaikan 1): hipotesis implementer dikonfirmasi
     * peninjau sebagai cacat Important. Label segmen adalah `flex-1` di baris flex; sebelum
     * perbaikan ini kelasnya `min-w-0`, yang justru mematikan batas bawah lebar bawaan flex
     * item (default browser: min-width otomatis mengikuti konten, dan `min-w-0` membuka
     * penyusutan sampai 0 px). Di wadah sempit dengan banyak pilihan, target tiap segmen
     * bisa menyusut di bawah 44 px (WCAG 2.5.8). `TapTargetTest` hanya mengenal penanda
     * tinggi (`min-h-*`) sehingga tidak menangkap kelemahan lebar ini; kedua sumbu wajib
     * terkunci di kelas label yang sama, sama seperti pola `pembungkus` Chip (Ruling
     * W1-5a).
     */
    public function test_the_segmented_control_label_locks_both_touch_target_axes(): void
    {
        $segmen = $this->sumber('components/ui/KontrolSegmen.tsx');

        $this->assertMatchesRegularExpression(
            "/<label\\s+key=\\{p\\.nilai\\}\\s+className=\\{kelas\\(\\s*'([^']*)'/",
            $segmen,
            'Kelas label segmen tidak ditemukan dalam bentuk yang diharapkan.'
        );

        preg_match("/<label\\s+key=\\{p\\.nilai\\}\\s+className=\\{kelas\\(\\s*'([^']*)'/", $segmen, $cocok);
        $nilai = $cocok[1] ?? '';

        $this->assertStringContainsString('min-h-11', $nilai, 'Label segmen kehilangan penjaga tinggi target sentuh 44 px.');
        $this->assertStringContainsString(
            'min-w-11',
            $nilai,
            'Label segmen kehilangan penjaga lebar target sentuh 44 px: dengan banyak pilihan di wadah sempit, target tiap segmen bisa menyusut di bawah 44 px (WCAG 2.5.8, Ruling W1-6a).'
        );
        $this->assertStringNotContainsString('min-w-0', $nilai, 'Label segmen memakai min-w-0, yang mematikan batas bawah lebar target sentuh (Ruling W1-6a).');
        $this->assertStringContainsString('flex-1', $nilai, 'Segmen kehilangan flex-1: segmen wajib tetap membagi lebar dan tumbuh (Ruling W1-6a).');
    }

    /**
     * Sakelar menyebut keadaannya (aria-checked) dan dinamai judulnya; kenop berpindah
     * posisi dan jalurnya berganti isian, jadi keadaan tidak dibawa warna saja.
     */
    public function test_the_switch_announces_its_state(): void
    {
        $sakelar = $this->sumber('components/ui/Sakelar.tsx');

        $this->assertStringContainsString('role="switch"', $sakelar);
        $this->assertStringContainsString('aria-checked={nyala}', $sakelar);
        $this->assertStringContainsString('aria-labelledby={`${id}-judul`}', $sakelar);
        $this->assertStringContainsString("nyala ? 'border-brand-700 bg-brand-700' : 'border-control bg-control'", $sakelar);
        $this->assertStringContainsString("nyala ? 'translate-x-[1.375rem]' : 'translate-x-0.5'", $sakelar);
    }

    /**
     * Seluruh baris kotak centang adalah labelnya: menyentuh teks ikut mencentang. Kotaknya
     * 28 px bergaris control 2 px (3,65:1 di atas kartu).
     */
    public function test_the_checkbox_row_ties_its_label_to_the_box(): void
    {
        $baris = $this->sumber('components/ui/KotakCentang.tsx');

        $this->assertStringContainsString('<label htmlFor={idKotak}', $baris);
        $this->assertStringContainsString('<Checkbox id={idKotak}', $baris);
        $this->assertMatchesRegularExpression('/type="checkbox" className=\{kelas\(\'[^\']*\bsize-7\b[^\']*\bborder-control\b/', $this->sumber('components/ui/Field.tsx'));
    }

    /**
     * Kedua tombol stepper bernama menurut labelnya, tidak tersedia di batas rentang, dan
     * nilai yang berubah diumumkan pembaca layar.
     *
     * Diperbarui di perbaikan akhir W1 (I-2, butir parkir Tugas 6): tombol yang mencapai
     * batas dahulu disabled, dan atribut itu melepas fokus dari tombol yang baru ditekan
     * sehingga fokus jatuh ke body. Kini aria-disabled="true" dengan klik yang diabaikan:
     * fokus tetap di tombol itu, dan pembaca layar menyebutnya tidak tersedia.
     */
    public function test_the_stepper_names_both_buttons_and_announces_the_value(): void
    {
        $stepper = $this->tanpaKomentarJs($this->sumber('components/ui/Stepper.tsx'));

        $this->assertStringContainsString('role="group" aria-labelledby={`${id}-label`}', $stepper);
        $this->assertStringContainsString('label={`Kurangi ${nama}`}', $stepper);
        $this->assertStringContainsString('label={`Tambah ${nama}`}', $stepper);
        $this->assertStringContainsString('<output aria-live="polite"', $stepper);

        $this->assertStringContainsString('const diMin = nilai <= min;', $stepper);
        $this->assertStringContainsString('const diMax = nilai >= max;', $stepper);
        $this->assertStringContainsString('aria-disabled={diMin || undefined}', $stepper);
        $this->assertStringContainsString('aria-disabled={diMax || undefined}', $stepper);
        $this->assertMatchesRegularExpression('/if \(!diMin\) \{\s*onUbah\(/', $stepper, 'Klik Kurangi di batas bawah harus diabaikan.');
        $this->assertMatchesRegularExpression('/if \(!diMax\) \{\s*onUbah\(/', $stepper, 'Klik Tambah di batas atas harus diabaikan.');
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])disabled=\{/', $stepper, 'Tombol di batas memakai aria-disabled, bukan disabled.');
        $this->assertStringContainsString('aria-disabled:opacity-50', $stepper, 'Tombol yang tidak tersedia tetap terlihat berbeda.');
    }

    /**
     * Lembar bawah dan dialog memakai <dialog> asli dengan showModal(): fokus terkurung,
     * latar inert, dan Escape menutup tanpa kode papan ketik buatan. Setiap cara menutup
     * memicu event close, dan di sanalah fokus dikembalikan ke pemicunya, atau ke #konten
     * bila pemicunya sudah hilang dari DOM (Review Focus W1).
     *
     * Diperbarui di perbaikan akhir W1 (I-2): pemicu diambil dari document.activeElement,
     * dan body ikut diterima sebagai pemicu, jadi cadangan #konten tidak pernah dipakai dan
     * fokus kembali ke body. Itu terjadi setiap ketukan di Safari, yang tidak memfokus
     * tombol saat diklik. Kini body berarti tanpa pemicu, dan pemanggil boleh memberi ref
     * pemicu eksplisit yang didahulukan.
     */
    public function test_the_sheet_and_dialog_use_the_native_modal_and_return_focus(): void
    {
        $lembar = $this->tanpaKomentarJs($this->sumber('components/ui/Lembar.tsx'));

        $this->assertStringContainsString('dialog.showModal();', $lembar);
        $this->assertStringContainsString("dialog.addEventListener('close', saatTutup);", $lembar);
        $this->assertStringContainsString('pemicu.current = aktif instanceof HTMLElement && aktif !== document.body ? aktif : null;', $lembar);
        $this->assertStringContainsString('pemicu?: RefObject<HTMLElement | null>;', $lembar);
        $this->assertStringContainsString('const tujuan = pemicuEksplisit.current?.current ?? pemicu.current;', $lembar);
        $this->assertMatchesRegularExpression("/if \(tujuan\?\.isConnected\) \{\s*tujuan\.focus\(\);\s*\} else \{\s*document\.getElementById\('konten'\)\?\.focus\(\{ preventScroll: true \}\);/", $lembar);
        $this->assertSame(2, substr_count($lembar, 'useDialogAsli(terbuka, onTutup, pemicu)'), 'Lembar bawah dan dialog tengah sama-sama meneruskan pemicu eksplisit.');
        $this->assertSame(2, substr_count($lembar, '<dialog'), 'Lembar bawah dan dialog tengah.');
        $this->assertSame(2, substr_count($lembar, 'aria-labelledby={`${id}-judul`}'));
        $this->assertStringContainsString('<TombolIkon ikon="x" label="Tutup"', $lembar);
        $this->assertStringContainsString('if (e.target === e.currentTarget) {', $lembar);
        $this->assertStringNotContainsString("'Escape'", $lembar, 'Escape ditangani dialog asli, bukan kode sendiri.');
    }

    /**
     * Ruling AF-7 (audit E2E M-5): <dialog> dirender di tempat pemanggil, dan sebagai anak
     * bukan-pertama wadah space-y-*, aturan margin space-y (tiga kelas) mengalahkan mt-auto dan
     * m-auto (satu kelas), sehingga lembar bawah menempel 16 px dari atas layar dan dialog tengah
     * naik ke atas. Margin letaknya karena itu memakai penanda penting di sumbernya, untuk setiap
     * pemanggil.
     */
    public function test_the_sheet_and_dialog_margins_cannot_be_overridden_by_a_spacing_parent(): void
    {
        $lembar = $this->tanpaKomentarJs($this->sumber('components/ui/Lembar.tsx'));

        $this->assertStringContainsString('className="lembar !mb-0 !mt-auto ', $lembar);
        $this->assertStringContainsString(' sm:!mb-auto ', $lembar);
        $this->assertStringContainsString('className="dialog-tengah !m-auto ', $lembar);
        $this->assertDoesNotMatchRegularExpression('/[\s"](mt-auto|mb-0|sm:mb-auto|m-auto)[\s"]/', $lembar, 'Margin letak tanpa penanda penting dapat dikalahkan space-y pemanggil.');
    }

    /**
     * Toast urungkan menunggu pembacanya (WCAG 2.2.1): paling sedikit 8 detik, berhenti
     * selama disentuh penunjuk atau difokus, dan dapat ditutup dengan tombol bernama.
     *
     * Diperbarui di perbaikan akhir W1 (I-2): satu boolean jeda dipakai penunjuk dan fokus
     * sekaligus, jadi penunjuk yang pergi melanjutkan hitungan walau fokus masih di Urungkan,
     * dan toast hilang di tangan pengguna papan ketik. Kini dua penanda terpisah; hitungan
     * hanya berjalan bila keduanya mati, dan jeda fokus baru mati ketika fokus keluar dari
     * toast, bukan ketika berpindah di dalamnya.
     *
     * Diubah ruling CF-7 (efek samping P3 tinjauan perbaikan audit): jeda fokus hanya berlaku
     * bila unsur yang difokus cocok `:focus-visible`. Simpanan memindah fokus ke Urungkan juga
     * sesudah klik tetikus, dan tanpa syarat itu toastnya tidak pernah hilang sendiri bagi
     * pengguna tetikus. Peramban yang belum mengenal pemilih itu dianggap fokus terlihat, jadi
     * toast tetap berhenti bagi pengguna papan ketik. Jeda penunjuk dan batas 8000 ms tetap.
     */
    public function test_the_undo_toast_waits_for_the_reader(): void
    {
        $toast = $this->tanpaKomentarJs($this->sumber('components/ui/ToastUrungkan.tsx'));

        $this->assertSame(1, preg_match('/durasiMs = (\d+)/', $toast, $durasi));
        $this->assertGreaterThanOrEqual(8000, (int) $durasi[1]);

        $this->assertStringContainsString('const [jedaPenunjuk, setJedaPenunjuk] = useState(false);', $toast);
        $this->assertStringContainsString('const [jedaFokus, setJedaFokus] = useState(false);', $toast);
        $this->assertMatchesRegularExpression('/if \(jedaPenunjuk \|\| jedaFokus\) \{\s*return;\s*\}\s*const pewaktu = window\.setTimeout\(onTutup, durasiMs\);/', $toast);
        $this->assertStringContainsString('onMouseEnter={() => setJedaPenunjuk(true)}', $toast);
        $this->assertStringContainsString('onMouseLeave={() => setJedaPenunjuk(false)}', $toast);
        $this->assertMatchesRegularExpression('/onFocus=\{\(e\) => \{\s*fokusDiDalam\.current = true;\s*if \(fokusTerlihatPada\(e\.target\)\) \{\s*setJedaFokus\(true\);\s*\}/', $toast, 'Jeda fokus hanya untuk fokus terlihat (CF-7).');
        $this->assertMatchesRegularExpression("/function fokusTerlihatPada\(unsur: EventTarget\): boolean \{\s*try \{\s*return unsur instanceof Element && unsur\.matches\(':focus-visible'\);\s*\} catch \{\s*return true;\s*\}\s*\}/", $toast);
        $this->assertSame(1, substr_count($toast, 'setJedaFokus(true)'), 'Tidak ada jalan lain yang menjeda toast karena fokus.');

        // Ruling CC-2: DESIGN.md menulis perilaku yang sama, bukan jeda untuk setiap fokus.
        $desain = (string) preg_replace('/\s+/', ' ', File::get(base_path('DESIGN.md')));
        $this->assertStringContainsString('hitungannya berhenti selama fokus papan ketik (`:focus-visible`) berada di dalamnya atau penunjuk berada di atasnya', $desain);
        $this->assertStringNotContainsString('hitungannya berhenti selama ia difokus atau diarahkan penunjuk', $desain);
        $this->assertMatchesRegularExpression('/onBlur=\{\(e\) => \{\s*if \(e\.currentTarget\.contains\(e\.relatedTarget as Node \| null\)\) \{\s*return;\s*\}\s*fokusDiDalam\.current = false;\s*setJedaFokus\(false\);/', $toast);
        $this->assertDoesNotMatchRegularExpression('/\bsetJeda\(/', $toast, 'Satu jeda bersama untuk penunjuk dan fokus sudah diganti dua penanda.');

        $this->assertMatchesRegularExpression('/>\s*Urungkan\s*<\/button>/', $toast);
        $this->assertStringContainsString('<span className="sr-only">Tutup pemberitahuan</span>', $toast);
        $this->assertStringContainsString('<div aria-live="polite" aria-atomic="true"', $toast);
    }

    /**
     * Urungkan terjangkau papan ketik sebelum waktunya habis, dan fokus tidak pernah jatuh ke
     * body di sekitar toast (I-2, WCAG 2.2.1 dan 2.4.3). Sebelum perbaikan akhir W1,
     * pengguna papan ketik butuh 27 kali Tab untuk mencapai Urungkan sementara 8 detik terus
     * berjalan, dan fokus jatuh ke body sesudah Urungkan.
     *
     * Opsi fokus memindah fokus ke Urungkan saat toast tampil; pemanggil menyetelnya bila
     * aksinya dipicu papan ketik. Sesudah Urungkan pemanggil memfokus unsur yang
     * dikembalikan lewat useFokusSesudahRender. Toast yang hilang dengan fokus di dalamnya
     * mengembalikan fokus ke unsur sebelumnya atau ke #konten, kecuali pemanggil sudah
     * memindahkannya. Pameran menerapkan keduanya untuk chip tafsiran dan hapus simpanan.
     */
    public function test_the_undo_toast_takes_focus_from_the_keyboard_and_gives_it_back(): void
    {
        $toast = $this->tanpaKomentarJs($this->sumber('components/ui/ToastUrungkan.tsx'));

        $this->assertStringContainsString('fokus?: boolean;', $toast);
        $this->assertStringContainsString('(pesan: string, onUrungkan: () => void, opsi: OpsiToast = {})', $toast);
        $this->assertStringContainsString('fokus: opsi.fokus', $toast);
        $this->assertMatchesRegularExpression('/if \(toast\.fokus\) \{\s*sebelum\.current \?\?= unsurLuar\(document\.activeElement, wadah\.current\);\s*urungkan\.current\?\.focus\(\);/', $toast);
        $this->assertMatchesRegularExpression('/<button\s+ref=\{urungkan\}/', $toast);
        $this->assertMatchesRegularExpression("/if \(aktif && aktif !== document\.body\) \{\s*return;\s*\}\s*if \(tujuan\?\.isConnected\) \{\s*tujuan\.focus\(\);\s*\} else \{\s*document\.getElementById\('konten'\)\?\.focus\(\{ preventScroll: true \}\);/", $toast);
        $this->assertStringContainsString('export function useFokusSesudahRender()', $toast);

        $pameran = $this->tanpaKomentarJs($this->sumber('pages/Dev/Primitif.tsx'));

        $this->assertStringContainsString('onLepas={(e) => lepasTafsiran(label, e.detail === 0)}', $pameran);
        $this->assertStringContainsString('onClick={(e) => hapusSimpanan(nama, e.detail === 0)}', $pameran);
        $this->assertSame(2, substr_count($pameran, '{ fokus: dariPapanKetik }'), 'Chip tafsiran dan hapus simpanan sama-sama memakai opsi fokus.');
        $this->assertStringContainsString('fokusSesudahRender(idTafsiran(label));', $pameran, 'Urungkan chip memfokus chip yang dikembalikan.');
        $this->assertStringContainsString('fokusSesudahRender(idSimpanan(nama));', $pameran, 'Urungkan simpanan memfokus tombol simpanan yang dikembalikan.');
        $this->assertStringContainsString('<ChipLepas key={label} id={idTafsiran(label)}', $pameran);
        $this->assertStringContainsString('<TombolIkon id={idSimpanan(nama)}', $pameran);
    }

    /**
     * Chip saringan yang sedang difokus menyerahkan fokusnya sebelum hilang (I-2, WCAG
     * 2.4.3): ke chip sesudahnya dalam grup yang sama, lalu ke chip sebelumnya, lalu ke wadah
     * grup yang diberi tabIndex -1. Sebelum perbaikan akhir W1 onLepas menghapus tombol yang
     * sedang difokus, dan fokus jatuh ke body. Event klik diteruskan ke onLepas supaya
     * pemanggil tahu aksi itu dari papan ketik.
     */
    public function test_a_released_chip_hands_its_focus_to_a_neighbour_first(): void
    {
        $chip = $this->tanpaKomentarJs($this->sumber('components/ui/Chip.tsx'));

        $this->assertStringContainsString('onLepas: (e: MouseEvent<HTMLButtonElement>) => void;', $chip);
        $this->assertMatchesRegularExpression('/if \(document\.activeElement === e\.currentTarget\) \{\s*serahkanFokus\(e\.currentTarget\);\s*\}\s*onLepas\(e\);/', $chip);
        $this->assertStringContainsString("grup.querySelectorAll<HTMLElement>(':scope > [data-chip-lepas]')", $chip);
        $this->assertStringContainsString('const tetangga = chip[posisi + 1] ?? chip[posisi - 1];', $chip);
        $this->assertMatchesRegularExpression("/if \(!grup\.hasAttribute\('tabindex'\)\) \{\s*grup\.tabIndex = -1;\s*\}\s*grup\.focus\(\);/", $chip);
        $this->assertStringContainsString('data-chip-lepas=""', $chip);
    }

    /**
     * Bar aksi melayang pada satu elevasi (shadow-overlay) dengan material chrome, di atas
     * navigasi bawah, dengan pengganjal supaya isi terakhir halaman tidak tertutup.
     *
     * Diperbarui di perbaikan akhir W1 (M-1): pengganjal h-32 tetap dan toast yang berjangkar
     * di tepi yang sama membuat toast menutupi tombol bar 60 px di 390 px. Kini bar menulis
     * tingginya ke --bar-aksi di <html> selama terpasang dan menghapusnya saat dilepas;
     * pengganjal dan jarak bawah toast membaca variabel itu.
     *
     * Diperbarui perbaikan akhir W2 (F-1, tinjauan akhir I-1a): jarak bawah toast juga
     * menghitung --baki-banding, tinggi baki banding Jelajah, supaya toast tidak menimpa
     * baris tombol baki di 360 px.
     *
     * Diperbarui W3: baki banding Jelajah dilepas bersama --baki-banding (prototype 02 dan 15
     * tanpa baki), jadi jarak bawah toast kembali menghitung navigasi bawah dan bar aksi.
     * Baki banding Simpanan adalah bar aksi, jadi toast tetap melayang di atasnya.
     */
    public function test_the_action_bar_floats_on_the_single_elevation(): void
    {
        $bar = $this->tanpaKomentarJs($this->sumber('components/ui/BarAksi.tsx'));

        $this->assertStringContainsString('<div aria-hidden="true" className="h-[calc(var(--bar-aksi,8rem)+1.5rem)]" />', $bar);
        $this->assertStringNotContainsString('h-32', $bar);
        $this->assertStringContainsString('<section ref={bar} aria-label={label}', $bar);
        $this->assertStringContainsString('bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+0.75rem)] z-30', $bar);
        $this->assertMatchesRegularExpression('/className="chrome [^"]*\bshadow-overlay\b/', $bar);

        $this->assertStringContainsString('new ResizeObserver(', $bar);
        $this->assertStringContainsString("akar.style.setProperty('--bar-aksi',", $bar);
        $this->assertMatchesRegularExpression("/return \(\) => \{\s*pengamat\.disconnect\(\);\s*akar\.style\.removeProperty\('--bar-aksi'\);/", $bar);
        $this->assertStringContainsString('const akar = document.documentElement;', $bar);

        $this->assertStringContainsString(
            'bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+1rem)] z-40',
            $this->sumber('components/ui/ToastUrungkan.tsx'),
            'Toast melayang di atas bar aksi, bukan di tepi yang sama.'
        );
    }

    /**
     * Tab aktif ditandai empat cara: ikon varian isi, isian pil, tebal huruf, dan
     * aria-current (WCAG 1.4.1); labelnya tidak pernah dipotong.
     */
    public function test_the_tab_bar_marks_the_active_tab_by_shape_weight_and_aria(): void
    {
        $bilah = $this->sumber('components/ui/BilahTab.tsx');

        $this->assertStringContainsString("aria-current={t.aktif ? 'page' : undefined}", $bilah);
        $this->assertStringContainsString('<Icon name={t.ikon} size={22} isi={t.aktif} />', $bilah);
        $this->assertStringContainsString("t.aktif ? 'bg-brand-100 font-bold text-brand-900' : 'font-medium text-secondary", $bilah);
        $this->assertStringContainsString('text-label', $bilah);
    }

    /**
     * Glyph domain tidak punya varian isi: Icon hanya membaca isi di cabang Phosphor,
     * sesudah cabang glyph kembali (tinjauan akhir W1, M-8). Begitu tab Jelajah atau Mendaki
     * memakai glyph (DK-39), ikon isi tidak lagi menandai tab aktif, jadi tab aktif wajib
     * tetap bertanda tanpa ikon: aria-current, label tebal, dan latar sorotan pada tautannya.
     */
    public function test_an_active_tab_stays_marked_when_its_icon_has_no_fill(): void
    {
        $ikon = $this->tanpaKomentarJs($this->sumber('components/ui/Icon.tsx'));
        $cabangGlyph = strpos($ikon, 'if (adalahGlyph(name)) {');
        $isiDibaca = strpos($ikon, 'isi && varian.fill');

        $this->assertNotFalse($cabangGlyph);
        $this->assertNotFalse($isiDibaca);
        $this->assertGreaterThan($cabangGlyph, $isiDibaca, 'Icon membaca isi sebelum cabang glyph; perbarui test ini.');

        $bilah = $this->sumber('components/ui/BilahTab.tsx');

        $this->assertStringContainsString("aria-current={t.aktif ? 'page' : undefined}", $bilah);
        $this->assertSame(1, preg_match("/t\.aktif \? '([^']*)' : '([^']*)'/", $bilah, $kelas), 'Kelas tab aktif tidak ditemukan.');
        $this->assertMatchesRegularExpression('/(?<![\w:-])font-bold\b/', $kelas[1], 'Label tab aktif tidak tebal.');
        $this->assertMatchesRegularExpression('/(?<![\w:-])bg-/', $kelas[1], 'Tab aktif tanpa latar sorotan.');
        $this->assertDoesNotMatchRegularExpression('/(?<![\w:-])(?:font-bold|bg-)/', $kelas[2], 'Tab biasa memakai penanda tab aktif.');
    }

    /**
     * Ruling koordinator W1-7a (putaran perbaikan 1): BilahTab mengulang pola cacat yang
     * sudah ditemukan dan diperbaiki dua kali sebelumnya (pembungkus Chip, Ruling W1-5a;
     * label KontrolSegmen, Ruling W1-6a). Tautan di dalam <li> memakai display flex tanpa
     * lebar sendiri (width: auto), jadi ia selalu mengisi 100% lebar <li> pembungkusnya;
     * <li>-lah yang menentukan lebar target sentuh tab yang sebenarnya, bukan tautannya.
     * Sebelum perbaikan ini <li> memakai min-w-0, yang mematikan lantai lebar bawaan flex
     * item (default browser: min-width mengikuti konten, dan min-w-0 membuka penyusutan
     * sampai 0 px). BilahTab akan menjadi bilah tab bawah aplikasi mulai W2; di layar
     * sangat sempit atau dengan lebih dari lima tab, lebar tiap tab bisa menyusut di bawah
     * 44 px (WCAG 2.5.8) bila tidak dikunci di sini, sama seperti kedua ruling sebelumnya.
     */
    public function test_the_tab_bar_item_locks_both_touch_target_axes(): void
    {
        $bilah = $this->sumber('components/ui/BilahTab.tsx');

        $this->assertMatchesRegularExpression(
            '/<li key=\{t\.label\} className="([^"]*)"/',
            $bilah,
            'Kelas <li> item BilahTab tidak ditemukan dalam bentuk yang diharapkan.'
        );

        preg_match('/<li key=\{t\.label\} className="([^"]*)"/', $bilah, $cocok);
        $nilai = $cocok[1] ?? '';

        $this->assertStringContainsString(
            'min-w-11',
            $nilai,
            'Item BilahTab kehilangan penjaga lebar target sentuh 44 px: pada layar sempit atau lebih dari lima tab, lebar tiap tab bisa menyusut di bawah 44 px (WCAG 2.5.8, Ruling W1-7a).'
        );
        $this->assertStringNotContainsString('min-w-0', $nilai, 'Item BilahTab memakai min-w-0, yang mematikan batas bawah lebar target sentuh (Ruling W1-7a).');
        $this->assertStringContainsString('flex-1', $nilai, 'Item BilahTab kehilangan flex-1: tab wajib tetap membagi lebar dan tumbuh (Ruling W1-7a).');
        $this->assertStringContainsString('h-14', $bilah, 'Penjaga tinggi target sentuh tab tidak ditemukan.');
    }

    private function sumber(string $berkas): string
    {
        return File::get(resource_path('js/'.$berkas));
    }
}
