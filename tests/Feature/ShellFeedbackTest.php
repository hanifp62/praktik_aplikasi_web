<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Umpan balik kerangka (rencana induk W2 butir 6; ruling W2-R3): wadah toast dipasang
 * sekali di AppLayout, dan kunjungan yang gagal karena jaringan menampilkan pesan jujur.
 *
 * Satu wadah berarti satu wilayah pengumuman untuk pembaca layar dan satu tempat toast di
 * layar; wadah per halaman menggandakan pengumuman dan hilang ketika halaman berganti.
 *
 * Putaran perbaikan 1 (W2-5a sampai W2-5f) menambah: pesan jaringan mengukur dan
 * mencadangkan ruangnya sendiri (W2-5a), mengikuti kunjungan terakhir apa pun hasilnya
 * (W2-5b), toast tidak lagi tertinggal lintas kunjungan (W2-5c), salinan pesan tidak
 * menyuruh mengirim ulang (W2-5d), dan pemeriksaan pemanggil useToastUrungkan ikut
 * memindai berkas .ts (W2-5f).
 *
 * Putaran perbaikan 4 (W2-5i) mengikat umur toast ke komponen yang memunculkannya dan
 * membuang penutupan lewat event navigate (W2-5c, W2-5g, W2-5h), yang belum menutup
 * kunjungan replace dan kunjungan ulang ke URL yang sama.
 *
 * Ruling W2-5k (digabung ke Tugas 6 lembar Pengingat) menambah console.warn khusus
 * pengembangan saat toast diminta sesudah pemiliknya dilepas, supaya kelalaian itu terlihat
 * saat dikembangkan, bukan diam-diam terbuang.
 */
class ShellFeedbackTest extends TestCase
{
    use SumberReact;

    /**
     * Diperbarui ruling W2-5i: nilai KonteksKerangka tetap dimemoisasi (W2-5f), tetapi kini
     * berisi toast bertuan (tampilkanMilik dan tutupMilik dari useToastUrungkan), bukan
     * tampilkanToast tanpa pemilik. tampilkanToast dibentuk useKerangka untuk setiap komponen
     * pemanggil.
     *
     * Diperbarui rencana induk W2 butir 5: nilai itu membawa juga bukaPengingat, layanan
     * lembar Pengingat yang dipasang di kerangka yang sama.
     */
    public function test_the_toast_container_is_mounted_once_in_the_layout(): void
    {
        $wadah = [];
        $kaitan = [];

        foreach ($this->berkasTsx('', true) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($nama === 'components/ui/ToastUrungkan.tsx') {
                continue;
            }

            $isi = $this->tanpaKomentarJs($berkas->getContents());

            if (preg_match('/<WadahToast\b/', $isi)) {
                $wadah[] = $nama;
            }

            if (str_contains($isi, 'useToastUrungkan(')) {
                $kaitan[] = $nama;
            }
        }

        $this->assertSame(['layouts/AppLayout.tsx'], $wadah, 'Wadah toast dipasang di luar AppLayout.');
        $this->assertSame(['layouts/AppLayout.tsx'], $kaitan, 'Keadaan toast dipegang di luar AppLayout (pemeriksaan ini memindai .ts dan .tsx, ruling W2-5f).');

        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('<KonteksKerangka value={layanan}>', $layout);
        $this->assertStringContainsString('<WadahToast toast={toast} onTutup={tutup} />', $layout);
        $this->assertStringContainsString(
            'const layanan = useMemo<NilaiKerangka>(() => ({ tampilkanToastMilik: tampilkanMilik, tutupToastMilik: tutupMilik, bukaPengingat }), [tampilkanMilik, tutupMilik, bukaPengingat]);',
            $layout,
            'Nilai KonteksKerangka harus dimemoisasi, bukan objek baru setiap render (ruling W2-5f), berisi toast bertuan (ruling W2-5i), dan bukaPengingat untuk lembar Pengingat (rencana induk W2 butir 5).',
        );
        $this->assertStringContainsString('const { tampilkanToast: tampilkan } = useKerangka();', File::get(resource_path('js/pages/Dev/Primitif.tsx')));
    }

    /**
     * Halaman di luar AppLayout tidak punya kerangka; memakai useKerangka di sana adalah
     * galat pemrograman yang harus terlihat, bukan toast yang diam-diam tidak muncul.
     *
     * Diperbarui ruling W2-5i: konteks kini membawa NilaiKerangka, yaitu toast bertuan dari
     * AppLayout, dan useKerangka yang membentuk LayananKerangka untuk setiap komponen
     * pemanggil. Galat di luar AppLayout tidak berubah.
     */
    public function test_the_shell_hook_refuses_to_work_outside_the_shell(): void
    {
        $kerangka = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/Kerangka.tsx')));

        $this->assertStringContainsString('export const KonteksKerangka = createContext<NilaiKerangka | null>(null);', $kerangka);
        $this->assertMatchesRegularExpression('/if \(layanan === null\) \{\s*throw new Error\(/', $kerangka);
    }

    /**
     * Toast milik halaman yang sudah dilepas menyimpan closure yang basi: menekan
     * Urungkan-nya akan mengubah state yang sudah tidak dibaca halaman mana pun (ruling
     * W2-5c). AppLayout menutup toast begitu halaman benar-benar berganti, dan kontrak
     * LayananKerangka/useKerangka menyatakan batas itu.
     *
     * Diperbarui ruling W2-5g: event navigate juga muncul pada muat ulang parsial dan pada
     * kunjungan ke halaman yang sama (saringan dengan preserveState, reload dengan only,
     * redirect kembali ke halaman yang sama sesudah POST/DELETE). Menutup toast pada SETIAP
     * navigate membuat Urungkan tidak pernah sempat ditekan pada kasus itu, jadi penangannya
     * membandingkan komponen dan path halaman baru dengan kunjungan sebelumnya, dan hanya
     * menutup toast bila salah satunya berbeda.
     *
     * Diperbarui ruling W2-5h: listener navigate baru terpasang sesudah render pertama, jadi
     * nilai awal kunjunganSebelumnya tidak boleh null (yang berarti "belum tahu", membuat
     * kunjungan pertama ke halaman yang sama sesudah AppLayout terpasang keliru dianggap
     * berbeda dan menutup toast). Nilai awalnya diambil dari halaman yang sedang tampil lewat
     * usePage(), bukan null.
     *
     * Diperbarui ruling W2-5i: perbandingan itu belum menutup semua kasus. Inertia memaksa
     * replace untuk kunjungan ke URL yang sama dan tidak memancarkan navigate untuk kunjungan
     * replace, padahal tanpa preserveState halaman lama tetap dilepas dan dipasang ulang; toast
     * bertahan dan Urungkan memanggil instance yang sudah dilepas. Umur toast kini diikat ke
     * komponen pemanggilnya (test berikutnya), jadi asersi yang mengunci perbandingan navigate
     * (component dari usePage, ref kunjunganSebelumnya, event.detail.page, jalurBaru) diganti
     * dua hal: AppLayout tidak lagi mendengarkan navigate untuk menutup toast, dan kontrak di
     * docblock Kerangka dan AppLayout berbunyi Urungkan berlaku selama komponen pemanggil masih
     * terpasang, bukan selama pengguna masih di komponen dan path yang sama.
     *
     * Diperbarui Ruling W2-6e #1: AppLayout kini memang mendengarkan navigate lagi, tetapi
     * untuk keperluan LAIN sama sekali (menutup lembar Pengingat pada navigasi mundur/maju
     * peramban, yang tidak memancarkan start). Toast tidak ikut memakainya; test ini
     * memastikan satu-satunya pemakaian navigate persis milik lembar Pengingat, bukan
     * kembali ke perbandingan komponen/path toast yang sudah diganti.
     */
    public function test_a_stale_toast_does_not_survive_a_real_page_change(): void
    {
        $layout = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/AppLayout.tsx')));

        // Diperbarui Ruling W2-6e #1: navigate kini dipakai untuk menutup lembar Pengingat
        // pada navigasi mundur/maju peramban, yang tidak memancarkan start. Toast tidak
        // boleh ikut memakainya lagi: satu-satunya pemakaian navigate di berkas ini harus
        // persis milik lembar Pengingat, bukan perbandingan komponen/path toast yang sudah
        // diganti ikatan pemilik (ruling W2-5i).
        $this->assertSame(1, substr_count($layout, "router.on('navigate'"), 'Toast tidak boleh kembali memakai navigate (ruling W2-5i); satu-satunya pemakaian yang boleh tersisa milik lembar Pengingat (Ruling W2-6e #1).');
        $this->assertStringContainsString("router.on('navigate', () => setPengingatTerbuka(false))", $layout);
        $this->assertStringNotContainsString('kunjunganSebelumnya', $layout);
        $this->assertStringNotContainsString('event.detail.page', $layout);

        foreach (['js/layouts/Kerangka.tsx', 'js/layouts/AppLayout.tsx'] as $berkas) {
            $dokumentasi = $this->teksKomentar(File::get(resource_path($berkas)));

            $this->assertStringContainsString('Urungkan berlaku selama komponen yang memanggil tampilkanToast masih terpasang', $dokumentasi, "{$berkas} tanpa kontrak W2-5i.");
            $this->assertStringNotContainsString('masih di halaman (komponen dan path)', $dokumentasi, "{$berkas} masih memuat kontrak W2-5g yang sudah diganti.");
        }
    }

    /**
     * Umur toast diikat ke komponen yang memunculkannya (ruling W2-5i). useKerangka memberi
     * setiap komponen pemanggil satu pemilik (useRef di dalam hook, jadi satu per instance),
     * dan toast dari tampilkanToast membawa pemilik itu. Saat komponen dilepas, cleanup
     * efeknya menurunkan tanda terpasang lalu menutup toast miliknya; tutupMilik hanya menutup
     * toast yang sedang tampil bila pemiliknya sama, jadi toast milik komponen lain tidak
     * tersentuh.
     *
     * Hasilnya mengikuti umur halaman, bukan event router: muat ulang parsial dan kunjungan
     * preserveState ke komponen yang sama tidak melepas halaman, jadi toast-nya bertahan;
     * pindah halaman dan kunjungan tanpa preserveState, termasuk kunjungan ulang ke URL yang
     * sama dan replace, melepasnya, jadi toast-nya tertutup. Hanya useKerangka yang membaca
     * KonteksKerangka, supaya tidak ada jalan memunculkan toast tanpa pemilik.
     */
    public function test_a_toast_closes_when_the_component_that_raised_it_unmounts(): void
    {
        $kerangka = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/Kerangka.tsx')));
        $toast = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/ToastUrungkan.tsx')));

        $this->assertStringContainsString('const pemilik = useRef<PemilikToast>({ terpasang: true });', $kerangka, 'Satu pemilik untuk setiap instance komponen pemanggil.');
        $this->assertMatchesRegularExpression(
            '/const milik = pemilik\.current;\s*milik\.terpasang = true;\s*return \(\) => \{\s*milik\.terpasang = false;\s*tutupToastMilik\?\.\(milik\);\s*\};/',
            $kerangka,
            'Cleanup saat komponen dilepas harus menutup toast milik komponen itu.',
        );
        $this->assertMatchesRegularExpression('/tampilkanToastMilik\(\s*milik,\s*pesan,/', $kerangka, 'Toast dari tampilkanToast harus membawa pemilik pemanggilnya.');

        $this->assertStringContainsString('setToast({ id: Date.now(), pesan, onUrungkan, fokus: opsi.fokus, pemilik })', $toast);
        $this->assertStringContainsString('setToast((kini) => (kini?.pemilik === pemilik ? null : kini))', $toast, 'Toast milik komponen lain tidak boleh ikut ditutup.');

        $pembaca = [];

        foreach ($this->berkasTsx('', true) as $berkas) {
            if (preg_match('/\buse(?:Context)?\(\s*KonteksKerangka\s*\)|\bKonteksKerangka\.Consumer\b/', $this->tanpaKomentarJs($berkas->getContents()))) {
                $pembaca[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        $this->assertSame(['layouts/Kerangka.tsx'], $pembaca, 'KonteksKerangka dibaca di luar useKerangka, jadi ada toast yang dapat muncul tanpa pemilik.');
    }

    /**
     * Komponen yang sudah dilepas tidak lagi memegang Urungkan (ruling W2-5i). Inertia
     * menjalankan onSuccess sesudah halaman baru terpasang dan halaman lama dilepas, jadi
     * toast yang diminta dari onSuccess kunjungan yang memasang ulang pemanggilnya datang
     * dari komponen yang sudah tidak ada: toast itu tidak dimunculkan. Urungkan yang sempat
     * ditekan pada toast yang sedang ditutup karena pemiliknya pergi juga tidak menjalankan
     * apa pun. Keduanya jalan terakhir menuju Urungkan yang memanggil closure instance lama
     * dan tidak memulihkan apa pun (I-3).
     *
     * Ruling W2-5k: permintaan yang diabaikan itu tidak diam-diam di lingkungan pengembangan.
     * console.warn hanya di bawah import.meta.env.DEV (tidak pernah di build produksi)
     * menjelaskan sebabnya dan menyarankan memanggil useKerangka di komponen yang bertahan,
     * atau memunculkan toast dari halaman tujuan lewat data flash.
     */
    public function test_an_unmounted_component_can_neither_raise_nor_undo_a_toast(): void
    {
        $kerangka = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/Kerangka.tsx')));

        $this->assertMatchesRegularExpression(
            '/const milik = pemilik\.current;\s*if \(!milik\.terpasang\) \{\s*if \(import\.meta\.env\.DEV\) \{\s*console\.warn\(.*?\);\s*\}\s*return;\s*\}\s*tampilkanToastMilik\(/',
            $kerangka,
            'Permintaan toast dari komponen yang sudah dilepas harus diabaikan, dan di DEV disertai console.warn (ruling W2-5k).',
        );
        $this->assertStringContainsString(
            "console.warn('Toast diminta sesudah komponen pemiliknya dilepas, jadi tidak dimunculkan. Panggil useKerangka di komponen yang bertahan, atau munculkan toast dari halaman tujuan lewat data flash.');",
            $kerangka,
            'Pesan console.warn harus menjelaskan sebabnya dan menyarankan perbaikannya (ruling W2-5k).',
        );
        $this->assertMatchesRegularExpression(
            '/\(\) => \{\s*if \(milik\.terpasang\) \{\s*onUrungkan\(\);\s*\}\s*\}/',
            $kerangka,
            'Urungkan tidak boleh menjalankan closure komponen yang sudah dilepas.',
        );
    }

    /**
     * Toast yang ditutup karena pemiliknya dilepas tidak mengembalikan fokus ke unsur yang
     * difokus sebelum fokus masuk ke toast (ruling W2-5i). Unsur itu milik halaman yang
     * ditinggalkan, atau kerangka di sekitarnya (tautan bilah tab tempat fokus masuk ke
     * toast), jadi fokus yang ditarik ke sana membawa pengguna keluar dari halaman baru.
     * Fokus dibiarkan pada unsur yang dipilih halaman baru, atau ke #konten lewat cabang W1
     * yang sama, yang dijaga DesignPrimitivesTest.
     */
    public function test_a_toast_closed_because_its_owner_left_lets_the_new_page_take_focus(): void
    {
        $sumber = File::get(resource_path('js/components/ui/ToastUrungkan.tsx'));

        $this->assertStringContainsString('const tujuan = toast.pemilik?.terpasang === false ? null : sebelum.current;', $this->tanpaKomentarJs($sumber));
        $this->assertStringContainsString(
            'Toast yang ditutup karena pemiliknya dilepas (ruling W2-5i) tidak mengembalikan fokus ke unsur sebelumnya',
            $this->teksKomentar($sumber),
            'Alasan pengecualian fokus ini ditulis di docblock WadahToast.',
        );
    }

    /**
     * Pesan jaringan tidak mengaku tahu nasib aksi terakhir (yang putus bisa jawabannya),
     * diumumkan sebagai peringatan, memberi satu langkah untuk memastikan, dan dapat
     * ditutup. Mengembalikan false mencegah janji yang ditolak tanpa penangan.
     *
     * Diperbarui ruling W2-5b: pesan mengikuti kunjungan terakhir, bukan hanya yang
     * berhasil. Ia disembunyikan begitu kunjungan berikutnya DIMULAI (start), apa pun
     * hasilnya nanti, sehingga galat validasi dan respons non-Inertia ikut menutupnya,
     * bukan hanya keberhasilan (success).
     *
     * Diperbarui ruling W2-5d: salinannya tidak menyuruh mengirim ulang, karena mengirim
     * ulang formulir buat bisa menggandakan data; ia hanya meminta memeriksa hasilnya.
     *
     * Diperbarui Ruling W2-6a #7: muat ulang lembar Pengingat sendiri (only:
     * ['daftarPengingat']) juga memancarkan start, tetapi bersifat async; start non-async
     * saja yang boleh menyembunyikan pesan ini, atau pesan global ikut hilang setiap kali
     * lembar itu dibuka.
     *
     * Diperbarui Ruling W2-6e #5: penjaga async W2-6a #7 semula mengecualikan SEMUA
     * kunjungan async, padahal aksi pengguna lain juga async, yaitu Trips/Preparation.tsx
     * (router.post dengan async: true) dan Community/ConditionReport.tsx (router.reload
     * only: ['segments'], yang dipaksa async oleh doReload() Inertia 3 sendiri), sehingga
     * pesan bertahan sesudah aksi ITU berhasil. Pengecualiannya kini hanya untuk muat ulang
     * lembar Pengingat sendiri (async DAN only berisi 'daftarPengingat'); kunjungan async
     * lain tetap menyembunyikan pesan pada start.
     *
     * Diperbarui perbaikan akhir W2 (F-3): tombol "Muat ulang halaman" menyembunyikan pesan
     * lewat tutup(), yang lebih dulu memindah fokus ke #konten; urutan W2-6d (sembunyikan,
     * lalu muat ulang) tetap.
     *
     * Diperbarui Ruling W2-F6: pesan yang sama kini punya dua jenis, jaringan dan versi baru,
     * jadi galat jaringan menyetel jenisnya lebih dulu.
     *
     * Diperbarui Ruling W2-F7 (B-1): tombol pesan jaringan kembali memanggil router.reload()
     * tanpa penanda milik kerangka. Konflik versi yang menjawabnya memunculkan pemberitahuan
     * versi baru, bukan muat penuh, sehingga isian lokal yang dipertahankan jawaban 200 tidak
     * hilang; muat penuhnya dipilih pengguna lewat tombol pemberitahuan itu.
     */
    public function test_a_network_error_shows_an_honest_message(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));

        $this->assertMatchesRegularExpression("/router\.on\('networkError', \(\) => \{\s*setJenis\('jaringan'\);\s*setTampil\(true\);\s*return false;\s*\}\)/", $pesan);
        $this->assertMatchesRegularExpression(
            "/router\.on\('start', \(event\) => \{\s*const muatUlangLembarPengingat = event\.detail\.visit\.async && event\.detail\.visit\.only\?\.includes\('daftarPengingat'\);\s*if \(!muatUlangLembarPengingat\) \{\s*setTampil\(false\);\s*\}\s*\}\)/",
            $pesan,
            'Hanya muat ulang lembar Pengingat sendiri (async DAN only berisi daftarPengingat) yang boleh tidak menyembunyikan pesan; aksi async lain (mis. Trips/Preparation.tsx, Community/ConditionReport.tsx) tetap harus menyembunyikannya (Ruling W2-6e #5).',
        );
        $this->assertStringNotContainsString("router.on('success'", $pesan, 'Pesan tidak lagi menunggu keberhasilan saja; galat validasi dan respons non-Inertia juga harus menutupnya (ruling W2-5b).');
        $this->assertStringContainsString('<Alert variant="warning" title="Tidak tersambung ke server"', $pesan, 'Peringatan lewat komponen Alert (role alert), bukan blok pesan rakitan sendiri.');
        $this->assertMatchesRegularExpression(
            "/onClick=\{\(\) => \{\s*tutup\(\);\s*router\.reload\(\);\s*\}\}/",
            $pesan,
            'Tombol harus menyembunyikan pesan sendiri lebih dulu: router.reload() Inertia 3 selalu async, jadi start tidak lagi menyembunyikannya sesudah W2-6a #7, dan pesan lama tampak bertahan sesudah muat ulang berhasil (Ruling W2-6d).',
        );
        $this->assertMatchesRegularExpression('/>\s*Muat ulang halaman\s*<\/Button>/', $pesan);
        $this->assertStringContainsString('<TombolIkon ikon="x" label="Tutup pesan"', $pesan);
        $this->assertStringContainsString(
            'Aksi terakhir Anda mungkin belum tercatat. Periksa hasilnya setelah sinyal kembali, sebelum mengirim ulang.',
            $pesan,
            'Salinan tidak boleh menyuruh mengirim ulang; mengirim ulang formulir buat bisa menggandakan data (ruling W2-5d).',
        );
        $this->assertDoesNotMatchRegularExpression('/\b(?:aman|gagal disimpan|tidak tersimpan|sudah tersimpan)\b/i', $pesan);
        $this->assertStringContainsString('<PesanJaringan />', File::get(resource_path('js/layouts/AppLayout.tsx')));
    }

    /**
     * Perbaikan akhir W2 (F-3, tinjauan akhir M-1): menutup pesan jaringan melepas tombol yang
     * sedang difokus bersama pesannya, dan tanpa tujuan pengganti fokus jatuh ke body
     * (DESIGN.md, "Fokus tidak pernah jatuh ke body"); pengguna pembaca layar kehilangan
     * posisinya. Kedua tombol, "Tutup pesan" dan "Muat ulang halaman", menyembunyikan pesan
     * lewat tutup(), yang memindah fokus ke #konten tanpa menggulir SEBELUM pesan hilang, pola
     * yang sama dengan toast (W1). Satu-satunya jalan lain yang menyembunyikan pesan adalah
     * start kunjungan baru, yang dimulai dari unsur lain yang sudah memegang fokus.
     */
    public function test_closing_the_network_message_hands_focus_to_the_content_not_the_body(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));

        $this->assertMatchesRegularExpression(
            "/const tutup = \(\) => \{\s*document\.getElementById\('konten'\)\?\.focus\(\{ preventScroll: true \}\);\s*setTampil\(false\);\s*\};/",
            $pesan,
            'Fokus harus pindah ke #konten sebelum pesan, beserta tombol yang difokus, dilepas.',
        );
        $this->assertStringContainsString('<TombolIkon ikon="x" label="Tutup pesan" varian="polos" className="absolute end-1 top-1" onClick={tutup} />', $pesan);
        $this->assertSame(2, substr_count($pesan, 'setTampil(false)'), 'Hanya tutup() dan start kunjungan baru yang menyembunyikan pesan.');
    }

    /**
     * Perbaikan akhir W2 (F-2, tinjauan akhir I-2), dibatasi Ruling W2-F6. Sesudah deploy yang
     * mengganti versi aset, server menjawab kunjungan GET Inertia berversi lama dengan 409 dan
     * X-Inertia-Location (inertia-laravel Middleware::onVersionChange). Inertia 3 memuat penuh
     * halaman untuk kunjungan sinkron, tetapi berhenti tanpa callback apa pun untuk kunjungan
     * async (@inertiajs/core dist/index.js, locationVisit, baris 2584-2596).
     *
     * Penangan location di PesanJaringan memisahkan tiga kasus. Jawaban tanpa versionChange dan
     * konflik yang datang selagi kunjungan sinkron berjalan tidak disentuh: kunjungan sinkron
     * dilacak per id lewat start dan finish, dan Inertia sendiri memuat penuh untuknya, jadi
     * tidak ada dua navigasi. Muat ulang async milik kerangka (lembar Pengingat dan tombol "Muat
     * ulang halaman"), yang ditandai muatUlangMilikKerangka di Kerangka.tsx, dimuat penuh.
     * Kunjungan async lain, misalnya muat ulang segmen laporan kondisi yang bisa berjalan saat
     * ada isian yang belum dikirim, hanya memunculkan pemberitahuan versi baru, jadi isian tidak
     * hilang diam-diam. Hanya kerangka yang memakai penanda itu, dan window.location.reload()
     * hanya ada di cabang milik kerangka dan di tombol pemberitahuan.
     *
     * Diperbarui Ruling W2-F7. (B-1) Penanda milik kerangka hanya untuk muat ulang lembar
     * Pengingat. Tombol pesan jaringan kembali memanggil router.reload() biasa: pesan itu paling
     * sering muncul sesudah formulir yang putus, dan 409 berversinya kini memunculkan
     * pemberitahuan alih-alih membuang isian yang dipertahankan jawaban 200. (B-3) Penghitung
     * dinaikkan di onStart kunjungan itu dan diturunkan di onFinish hanya bila onStart sudah
     * terjadi, karena kunjungan yang dibatalkan onBefore atau event before (dist/index.js baris
     * 3204) tidak pernah sampai ke onStart maupun onFinish. (B-4) Muat penuh lewat
     * muatPenuhVersiBaru(), satu-satunya pemanggil window.location.reload(), yang menyalakan
     * sedangMemuatPenuh() supaya jaring onFinish lembar tidak mengklaim galat jaringan.
     */
    public function test_a_version_change_reloads_only_the_shells_own_reloads_and_notifies_otherwise(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));
        $kerangka = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/Kerangka.tsx')));

        $this->assertMatchesRegularExpression(
            "/export function muatUlangMilikKerangka\(opsi: OpsiMuatUlang = \{\}\): void \{\s*let dimulai = false;\s*router\.reload\(\{\s*\.\.\.opsi,\s*onStart: \(kunjungan\) => \{\s*dimulai = true;\s*muatUlangKerangkaBerjalan \+= 1;\s*opsi\.onStart\?\.\(kunjungan\);\s*\},\s*onFinish: \(kunjungan\) => \{\s*if \(dimulai\) \{\s*muatUlangKerangkaBerjalan -= 1;\s*\}\s*opsi\.onFinish\?\.\(kunjungan\);\s*\},\s*\}\);\s*\}/",
            $kerangka,
            'Penghitung naik di onStart dan turun di onFinish sebagai pasangan, lalu callback pemanggil tetap berjalan (B-3).',
        );
        $this->assertMatchesRegularExpression('/export function adaMuatUlangMilikKerangka\(\): boolean \{\s*return muatUlangKerangkaBerjalan > 0;\s*\}/', $kerangka);
        $this->assertMatchesRegularExpression('/export function muatPenuhVersiBaru\(\): void \{\s*memuatPenuh = true;\s*window\.location\.reload\(\);\s*\}/', $kerangka, 'Muat penuh menyalakan penanda sebelum memuat ulang (B-4).');
        $this->assertMatchesRegularExpression('/export function sedangMemuatPenuh\(\): boolean \{\s*return memuatPenuh;\s*\}/', $kerangka);

        $this->assertMatchesRegularExpression(
            "/router\.on\('start', \(event\) => \{\s*if \(!event\.detail\.visit\.async\) \{\s*sinkron\.add\(event\.detail\.visit\.id\);\s*\}\s*\}\)/",
            $pesan,
            'Kunjungan sinkron yang sedang berjalan dilacak per id.',
        );
        $this->assertMatchesRegularExpression("/router\.on\('finish', \(event\) => \{\s*sinkron\.delete\(event\.detail\.visit\.id\);\s*\}\)/", $pesan);
        $this->assertMatchesRegularExpression(
            "/router\.on\('location', \(event\) => \{\s*if \(!event\.detail\.versionChange \|\| sinkron\.size > 0\) \{\s*return;\s*\}\s*if \(adaMuatUlangMilikKerangka\(\)\) \{\s*muatPenuhVersiBaru\(\);\s*return;\s*\}\s*setJenis\('versi'\);\s*setTampil\(true\);\s*\}\)/",
            $pesan,
            'Tanpa versionChange atau selagi kunjungan sinkron berjalan: diam. Muat ulang milik kerangka: muat penuh. Kunjungan async lain: pemberitahuan versi baru.',
        );

        $pemakaiPenanda = [];
        $penanganLokasi = [];
        $muatPenuh = [];
        $pemanggilMuatPenuh = [];

        foreach ($this->berkasTsx('', true) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());
            $isi = $this->tanpaKomentarJs($berkas->getContents());

            if (str_contains($isi, 'muatUlangMilikKerangka(')) {
                $pemakaiPenanda[] = $nama;
            }

            if (($jumlah = substr_count($isi, "router.on('location'")) > 0) {
                $penanganLokasi[$nama] = $jumlah;
            }

            if (($jumlah = substr_count($isi, 'window.location.reload(')) > 0) {
                $muatPenuh[$nama] = $jumlah;
            }

            if (($jumlah = substr_count($isi, 'muatPenuhVersiBaru(')) > 0) {
                $pemanggilMuatPenuh[$nama] = $jumlah;
            }
        }

        sort($pemakaiPenanda);

        $this->assertSame(['layouts/Kerangka.tsx', 'layouts/Pengingat.tsx'], $pemakaiPenanda, 'Hanya lembar Pengingat yang menandai muat ulangnya sebagai milik kerangka (B-1); halaman dan tombol pesan jaringan tidak.');
        $this->assertSame(['layouts/PesanJaringan.tsx' => 1], $penanganLokasi, 'Satu penangan location untuk seluruh aplikasi.');
        $this->assertSame(['layouts/Kerangka.tsx' => 1], $muatPenuh, 'window.location.reload() hanya di muatPenuhVersiBaru(), tidak pernah tanpa syarat.');
        $this->assertSame(['layouts/Kerangka.tsx' => 1, 'layouts/PesanJaringan.tsx' => 2], $pemanggilMuatPenuh, 'Muat penuh hanya dari cabang milik kerangka dan dari tombol pemberitahuan versi baru.');
    }

    /**
     * Ruling W2-F6: pemberitahuan versi baru memakai pesan kerangka yang sama (lapisan, jarak,
     * dan tombol tutup) dengan salinan yang berbeda dari pesan jaringan. Ia informasi, bukan
     * galat, jadi Alert info (role status). Tombolnya memuat penuh halaman atas pilihan
     * pengguna, sesudah isiannya tersimpan. Nama merek dibaca dari props bersama merek, satu
     * sumber identitas (BrandIdentityTest).
     */
    public function test_the_new_version_notice_lets_the_hiker_choose_when_to_reload(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));

        $this->assertStringContainsString("{jenis === 'versi' ? (", $pesan);
        $this->assertStringContainsString('<Alert variant="info" title={`Versi baru ${merek.nama} tersedia`} className="pe-14 shadow-overlay">', $pesan);
        $this->assertStringContainsString('Halaman ini masih memakai versi lama, jadi sebagian isinya tidak dapat diperbarui. Muat ulang halaman sesudah isian Anda tersimpan.', $pesan);
        $this->assertMatchesRegularExpression('/onClick=\{\(\) => muatPenuhVersiBaru\(\)\}\s*>\s*Muat ulang halaman\s*<\/Button>/', $pesan, 'Tombol pemberitahuan memuat penuh halaman, bukan muat ulang parsial yang akan terkena konflik versi lagi.');
        // W6 (ruling W6-R9, PT10-2, W6-9k bagian b): pesan yang sama juga membaca sakelar paket
        // untuk "Buka paket luring", hanya bila paket pengguna ini ada di perangkat ini (kunci
        // tersimpan sama dengan kunci perangkat). Tautannya dokumen biasa di tab yang sama ke
        // /offline?dibuka=paket, yang dijawab service worker: /offline tercatat bukan halaman
        // React (resources/js/lib/bukanHalamanReact.json), jadi Tautan merender <a href> biasa
        // (resources/js/components/ui/Tautan.tsx), bukan kunjungan Inertia yang gagal tanpa
        // sinyal. Tanpa external: external membuka tab baru (resources/js/components/ui/Button.tsx),
        // yang di aplikasi Layar Utama iPhone terbuka di Safari dengan penyimpanan terpisah.
        $this->assertStringContainsString('const { merek, perangkat } = usePage<PropsBersama>().props;', $pesan);
        $this->assertStringContainsString('{perangkat?.paket && perangkat.kunci === kunciTersimpan() ? (', $pesan);
        $this->assertStringContainsString("<Button variant=\"ghost\" size=\"sm\" className=\"mt-3\" href={offline.url({ query: { dibuka: 'paket' } })}>", $pesan);
        $this->assertStringNotContainsString("href={offline.url({ query: { dibuka: 'paket' } })} external", $pesan);
    }

    /**
     * Lapisan pesan jaringan fixed dan dapat menutupi isi di puncak halaman (ruling
     * W2-5a): tingginya diukur lewat ResizeObserver dan ditulis ke --pesan-jaringan
     * selagi tampil. scroll-padding-top membacanya supaya fokus papan ketik tidak
     * berhenti di baliknya, dan main memberi ruang atas yang sama supaya isi puncak
     * halaman tidak tertutup.
     */
    public function test_the_network_message_reserves_space_for_its_own_height(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));
        $css = File::get(resource_path('css/app.css'));
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('useLayoutEffect', $pesan);
        $this->assertStringContainsString('new ResizeObserver(ukur)', $pesan);
        $this->assertStringContainsString("akar.style.setProperty('--pesan-jaringan',", $pesan);
        $this->assertStringContainsString("akar.style.removeProperty('--pesan-jaringan');", $pesan);
        $this->assertStringContainsString('scroll-padding-top: calc(var(--kepala-atas) + var(--pesan-jaringan, 0px) + 0.5rem);', $css);
        $this->assertStringContainsString('<main id="konten" tabIndex={-1} className="pt-[var(--pesan-jaringan,0px)] pb-[var(--nav-bawah)]">', $layout);
    }

    /**
     * Sumber dengan baris docblock disambung, supaya kalimat kontrak dapat dicari walau
     * terlipat di tengah (putaran perbaikan 2 sempat memecah asersinya karena lipatan baris).
     */
    private function teksKomentar(string $isi): string
    {
        return (string) preg_replace('/[ \t]*\R[ \t]*\*(?!\/)[ \t]*/', ' ', $isi);
    }
}
