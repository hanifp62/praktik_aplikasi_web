<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Authority;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\KartuDarurat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kartu darurat dan versi cetak (spec v3 bagian 10.2 dan 10.8; rencana induk W6 butir 8;
 * ruling W6-R4). Spec v3 bagian 15 mengaitkan alat keselamatan dengan Q14, jadi keduanya mati
 * sampai pemilik memutus; test ini menjaga kedua keadaan sakelar.
 *
 * Diperluas pra-tinjau Tugas 7 (ruling P-R10): kontak pengelola deterministik dengan prioritas
 * jenis dan tanpa badan basecamp (ruling W6-7a, PT7-4), blok lokasi yang jujur per keadaan
 * lokasi (PT7-1, PT7-3, PT7-5, PT7-6, PT7-14), cetak yang menulis jam dalam zona gunung dan
 * tidak membawa eskalasi untuk trip yang sudah usai (PT7-7, PT7-8, PT7-10, PT7-15), dan daftar
 * putih middleware fitur (PT7-10 M5).
 */
class EmergencyCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianBerlangsung(?Mountain $gunung = null): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas', 'mountain_id' => ($gunung ?? Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']))->id]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subHour(),
        ]);

        return [$user, $trip];
    }

    private function balai(Mountain $gunung, array $isian = []): Authority
    {
        $balai = Authority::create([
            'name' => 'Balai Besar Taman Nasional Gunung Gede Pangrango',
            'slug' => 'bbtnggp-'.$gunung->id,
            'abbreviation' => 'BBTNGGP',
            'type' => AuthorityType::NATIONAL_PARK->value,
            'contact' => '(0263) 512-776',
            'verified_at' => now()->subMonth(),
            ...$isian,
        ]);
        $balai->mountains()->attach($gunung);

        return $balai;
    }

    public function test_while_the_switch_is_off_there_is_no_card_and_no_print_version(): void
    {
        [$user, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('darurat', null));

        $this->actingAs($user)->get(route('trips.print', $trip))->assertNotFound();
        // Rute yang mati tidak membocorkan keberadaannya lewat 403 ke pengguna lain.
        $this->actingAs(User::factory()->create())->get(route('trips.print', $trip))->assertNotFound();
    }

    public function test_the_card_carries_112_and_115_and_tells_the_hiker_to_stay_put(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung();

        $respons = $this->actingAs($user)->get(route('trips.hike', $trip));

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.nomor', [['label' => 'Darurat', 'nomor' => '112'], ['label' => 'Basarnas', 'nomor' => '115']])
            ->where('darurat.pengelola', null)
            ->where('darurat.tetap', 'Setelah menelepon, tetap di tempat kecuali tempat itu berbahaya. Petugas mencari dari lokasi yang Anda sebutkan.'));

        // R-012/R-014: tanpa janji keselamatan.
        $this->assertDoesNotMatchRegularExpression('/\b(aman|dijamin|terjamin|pasti)\b/i', json_encode($respons->viewData('page')['props']['darurat'], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Kontak pengelola hanya bila resmi diketahui: badan berwenang atas status jalur, sudah
     * diverifikasi, dan kontaknya tercatat. Nomor tidak pernah ditebak (R-007).
     */
    public function test_the_manager_contact_appears_only_when_it_is_officially_known(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create(['name' => 'Gunung Gede']);
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $balai = $this->balai($gunung, ['verified_at' => null]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola', null));

        $balai->update(['verified_at' => now()->subMonth()]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.pengelola', ['nama' => 'Balai Besar Taman Nasional Gunung Gede Pangrango (BBTNGGP)', 'kontak' => '(0263) 512-776', 'telepon' => '0263512776']));

        $balai->update(['contact' => 'https://tnggp.go.id/kontak']);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.pengelola.kontak', 'https://tnggp.go.id/kontak')
            ->where('darurat.pengelola.telepon', null));
    }

    /**
     * Lembaga sertifikasi mengesahkan orang, bukan keadaan jalur: kontaknya bukan kontak darurat.
     */
    public function test_a_certification_body_is_never_the_manager_contact(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create();
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $this->balai($gunung, ['name' => 'Badan Nasional Sertifikasi Profesi', 'abbreviation' => 'BNSP', 'type' => AuthorityType::CERTIFICATION_BODY->value]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola', null));
    }

    /**
     * Versi cetak: pemilik saja, dengan aturan resmi bertanggal cek dan kartu darurat.
     */
    public function test_the_print_version_is_for_the_owner_and_dates_the_official_rules(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        [$user, $trip] = $this->pendakianBerlangsung();
        PermitRequirement::factory()->create(['trail_id' => $trip->trail_id, 'authority' => 'BBTNGGP', 'verified_at' => Carbon::parse('2026-09-12 02:00:00', 'UTC')]);

        $this->get(route('trips.print', $trip))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('trips.print', $trip))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trips/Print')
            ->where('jalur.nama', 'Gede via Cibodas')
            ->where('aturan.ringkasan', 'BBTNGGP · kuota 200 pendaki/hari · pemesanan ditutup H-2 · maksimal 2 hari')
            ->where('aturan.dicek', '12 September 2026 WIB')
            ->where('darurat.nomor.1.nomor', '115')
            ->where('dicetak', '7 Oktober 2026 10.00 WIB'));

        $this->actingAs(User::factory()->create())->get(route('trips.print', $trip))->assertForbidden();
    }

    public function test_without_recorded_rules_the_print_version_says_so(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('aturan', null));
        $this->assertStringContainsString('Aturan resmi jalur ini belum tercatat di Memount.', File::get(resource_path('js/pages/Trips/Print.tsx')));
    }

    /**
     * Blok lokasi dihitung di perangkat; kartu ini tidak mengirim posisi ke mana pun (W6-R5,
     * PRD §145.1, ruling W6-P2). Badan test brief diganti pra-tinjau PT7-6: larangan dua kata
     * saja (fetch dan router) tidak menangkap sendBeacon, XMLHttpRequest, axios, antrean
     * kirimAksi, useForm, atau gambar beralamat. Daftar putih impor runtime, daftar larangan,
     * dan satu-satunya bentuk tautan (telepon) menutup celah itu. Asersi elevasiPada brief
     * diganti ketinggianDiPosisi (PT7-1): ketinggian dibaca dari letak posisi di garis, bukan
     * dari dilaluiM milik peta yang jatuh ke pos terakhir yang dicapai.
     */
    public function test_the_location_block_is_computed_on_the_device(): void
    {
        $kartu = File::get(resource_path('js/components/mendaki/KartuDarurat.tsx'));

        preg_match_all("/^import (?!type )[^;]+ from '([^']+)';$/m", $kartu, $impor);
        $modul = $impor[1];
        sort($modul);
        $this->assertSame(['./hitung', '@/components/ui/Icon', '@/components/ui/Lembar', '@/components/ui/gaya', '@/lib/kelas', 'react'], $modul, 'Kartu darurat hanya mengimpor primitif tampilan dan hitungan murni (W6-R5, PRD §145.1).');

        // Putaran perbaikan 1 (ruling W6-7i, M-2): suar lewat src yang diisi JavaScript, objek
        // Image, impor dinamis, soket, aliran peristiwa, dan postMessage juga dilarang.
        foreach (['fetch(', 'sendBeacon', 'XMLHttpRequest', 'axios', 'router', 'useForm', 'kirimAksi', 'navigator.', 'src=', 'action=', 'new Image', 'import(', 'WebSocket', 'EventSource', 'postMessage'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $kartu, "Kartu darurat tidak boleh memuat {$terlarang}.");
        }
        $this->assertDoesNotMatchRegularExpression('/\bsrc\s*=/', $kartu, 'Kartu darurat tidak boleh mengisi src apa pun.');

        preg_match_all('/href=\{`([^`]*)`\}/', $kartu, $tautan);
        $this->assertSame(['tel:${n.nomor}', 'tel:${darurat.pengelola.telepon}'], $tautan[1], 'Satu-satunya tautan kartu adalah telepon.');
        $this->assertStringContainsString('teksKoordinat(posisi.lat, posisi.lng)', $kartu);
        $this->assertStringContainsString('ketinggianDiPosisi(data.garis, data.profil, posisi)', $kartu);
        $this->assertStringNotContainsString('dilaluiM', $kartu);

        // Tinjauan ulang Tugas 7 (m-1): daftar kata tidak menangkap setAttribute('src'), prefetch
        // <link> dengan href biasa, atau gambar latar url(). Kartu tidak menyentuh API global yang
        // dapat membuat permintaan atau mengubah DOM di luar React, tidak memuat elemen atau gaya
        // yang mengambil sumber dari alamat, dan href-nya hanya dua tautan telepon.
        $this->assertDoesNotMatchRegularExpression('/\b(document|window|globalThis|self|location|setAttribute)\b/', $kartu);
        $this->assertDoesNotMatchRegularExpression('/<(link|img|iframe|script|object|embed|source|video|audio|form)\b|style=|url\(/i', $kartu);
        $this->assertSame(2, substr_count($kartu, 'href='));
    }

    /**
     * Isi blok lokasi per keadaan (PT7-3, PT7-5, PT7-12, PT7-14). Kalimat keadaan lokasi dan
     * pos terdekat datang dari fungsi murni hitung.ts yang diuji Node di FieldModeFiguresTest;
     * di sini dijaga bahwa kartu memakainya, menyebut pos dengan nomornya (nama pos tidak unik),
     * menulis jam dalam zona gunung, dan berkata jujur bahwa posisinya tidak diteruskan.
     */
    public function test_the_location_block_says_what_it_knows_and_who_reads_it(): void
    {
        $kartu = File::get(resource_path('js/components/mendaki/KartuDarurat.tsx'));
        $layanan = File::get(app_path('Services/Trips/KartuDarurat.php'));

        $this->assertStringContainsString('Dihitung di perangkat ini. Memount tidak meneruskan posisi ini ke petugas atau ke kontak Anda; bacakan sendiri saat menelepon.', $kartu);
        $this->assertStringContainsString('kartu ini tidak mengirim posisi ke mana pun', $kartu);
        $this->assertStringContainsString('kartu ini tidak mengirim posisi ke mana pun', $layanan);

        foreach ([$kartu, $layanan] as $isi) {
            $this->assertStringNotContainsString('tidak pernah dikirim'.' ke server', $isi, 'Salah pada cabang rekam jejak menyala (ruling W6-P2).');
        }

        $this->assertStringContainsString('kalimatPosisiDarurat(lokasi)', $kartu);

        // Ruling W6-7h (I-2): di cabang yang menampilkan posisi terakhir, keterangannya tentang
        // posisi BARU (kalimatPosisiLama); kalimat "tanpa posisi" hanya di cabang tanpa posisi.
        $cabangPosisi = strpos($kartu, '{posisi ? (');
        $cabangTanpa = strpos($kartu, ') : (', (int) $cabangPosisi);
        $this->assertNotFalse($cabangPosisi);
        $this->assertNotFalse($cabangTanpa);
        $this->assertSame(1, substr_count($kartu, 'kalimatPosisiLama(lokasi)'));
        $this->assertGreaterThan($cabangPosisi, strpos($kartu, 'kalimatPosisiLama(lokasi)'));
        $this->assertLessThan($cabangTanpa, strpos($kartu, 'kalimatPosisiLama(lokasi)'));
        $this->assertSame(1, substr_count($kartu, 'kalimatPosisiDarurat(lokasi)'));
        $this->assertGreaterThan($cabangTanpa, strpos($kartu, 'kalimatPosisiDarurat(lokasi)'));

        $this->assertStringContainsString('posTerdekatDarurat(data.pos, posisi)', $kartu);
        $this->assertStringContainsString('Pos terdekat (garis lurus)', $kartu);
        $this->assertStringContainsString('Pos terakhir yang dicapai', $kartu);
        $this->assertStringContainsString('`nomor ${posTercapai.urutan}, ${posTercapai.nama}`', $kartu);
        $this->assertStringNotContainsString('Posisi belum didapat', $kartu, 'Kalimat keadaan lokasi hanya dari kalimatPosisiDarurat di hitung.ts.');
        $this->assertStringContainsString('timeZone: darurat.zona', $kartu);
        $this->assertStringContainsString('{darurat.labelZona}', $kartu);
        $this->assertStringContainsString('teksUmur(', $kartu);

        // Ruling W6-7d: warna yang dipakai komponen adalah pasangan yang dihitung
        // ColourContrastTest::fieldPairs (7:1 di kedua tema lapangan).
        $this->assertMatchesRegularExpression('/href=\{`tel:\$\{n\.nomor\}`\}.*?className=\{kelas\(\'[^\']*\bbg-danger-900\b[^\']*\btext-inverse\b/s', $kartu);
        $this->assertMatchesRegularExpression('/\bborder-danger-600 bg-surface\b[^\']*\btext-danger-900\b[^\']*\'\, fokusTerlihat, umpanTekan\)\}\s*>\s*<Icon name="warning"/s', $kartu);

        // PT7-12: tautan telepon pengelola setinggi kendali lapangan dengan nama berkonteks.
        $this->assertStringContainsString('aria-label={`Telepon ${darurat.pengelola.nama}, ${darurat.pengelola.kontak}`}', $kartu);
        $this->assertMatchesRegularExpression('/href=\{`tel:\$\{darurat\.pengelola\.telepon\}`\}[^>]*className=\{kelas\(\'inline-flex min-h-12 /s', $kartu);
    }

    /**
     * Ruling W6-7a (PT7-4): pilihan kontak deterministik. Balai taman nasional lebih dulu, lalu
     * instansi pemerintah, lalu id. Badan basecamp tidak pernah terpilih sampai badan dapat
     * ditautkan ke jalur (titipan W8), karena basecamp dapat mewakili jalur lain di gunung yang
     * sama (R-001, R-002). Badan prioritas tinggi dibuat terakhir, jadi urutan pembuatan dan id
     * berlawanan dengan jawaban.
     */
    public function test_the_manager_contact_prefers_the_national_park_and_never_a_basecamp(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create(['name' => 'Gunung Gede']);
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $basecamp = $this->balai($gunung, ['name' => 'Basecamp Cibodas', 'slug' => 'basecamp-cibodas', 'abbreviation' => null, 'type' => AuthorityType::BASECAMP->value, 'contact' => '0812 1111 2222']);
        $pemda = $this->balai($gunung, ['name' => 'Dinas Pariwisata Cianjur', 'slug' => 'dispar-cianjur', 'abbreviation' => null, 'type' => AuthorityType::GOVERNMENT->value, 'contact' => '(0263) 261-234']);
        $balai = $this->balai($gunung);

        $pengelola = fn () => $this->actingAs($user)->get(route('trips.hike', $trip))->viewData('page')['props']['darurat']['pengelola'];

        $this->assertSame('Balai Besar Taman Nasional Gunung Gede Pangrango (BBTNGGP)', $pengelola()['nama']);

        $balai->update(['verified_at' => null]);
        $this->assertSame('Dinas Pariwisata Cianjur', $pengelola()['nama']);

        $pemda->update(['verified_at' => null]);
        $this->assertNull($pengelola(), 'Basecamp terverifikasi berkontak tetap tidak dipakai.');
        $this->assertNotNull($basecamp->fresh()->verified_at);
    }

    /**
     * Pemecah seri id di antara dua badan sejenis (ruling W6-7a). Badan beridentitas lebih
     * besar ditautkan lebih dulu, supaya urutan relasi berlawanan dengan urutan id.
     */
    public function test_two_bodies_of_the_same_kind_are_ordered_by_id(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create();
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $pertama = Authority::create(['name' => 'Dinas A', 'slug' => 'dinas-a', 'type' => AuthorityType::GOVERNMENT->value, 'contact' => '0263 100', 'verified_at' => now()]);
        $kedua = Authority::create(['name' => 'Dinas B', 'slug' => 'dinas-b', 'type' => AuthorityType::GOVERNMENT->value, 'contact' => '0263 200', 'verified_at' => now()]);
        $kedua->mountains()->attach($gunung);
        $pertama->mountains()->attach($gunung);

        $this->assertLessThan($kedua->id, $pertama->id);
        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola.nama', 'Dinas A'));
    }

    /**
     * PT7-10 M6: badan terverifikasi tanpa kontak tercatat tidak menjadi kontak (R-007).
     */
    public function test_a_verified_body_without_a_recorded_contact_is_not_offered(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create();
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $this->balai($gunung, ['contact' => null]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola', null));
    }

    /**
     * PT7-10 M7: tautan telepon hanya dari kontak yang memang satu nomor utuh.
     */
    public function test_a_phone_link_is_built_only_from_one_whole_number(): void
    {
        $this->assertSame('+62263512776', KartuDarurat::telepon('+62 263 512776'));
        $this->assertSame('0263512776', KartuDarurat::telepon('(0263) 512-776'));
        $this->assertNull(KartuDarurat::telepon('12'));
        $this->assertNull(KartuDarurat::telepon('0263 512776 / 0812'));
        $this->assertNull(KartuDarurat::telepon('1234567890123456'));
    }

    /**
     * PT7-14: jam pengukuran dibacakan dalam zona gunung dengan penandanya, bukan zona ponsel.
     */
    public function test_the_card_carries_the_mountain_time_zone(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung(Mountain::factory()->create(['name' => 'Gunung Rinjani', 'timezone' => 'Asia/Makassar']));

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.zona', 'Asia/Makassar')
            ->where('darurat.labelZona', 'WITA'));
    }

    /**
     * PT7-10 M5: middleware fitur hanya mengenal dua sakelar. Nama lain menjawab 404 walau
     * konfigurasi dengan nama itu kebetulan menyala.
     */
    public function test_the_feature_middleware_only_knows_its_two_switches(): void
    {
        config(['hiking.tidak_ada.enabled' => true, 'hiking.field_safety.enabled' => true]);
        Route::middleware(['web', 'fitur:tidak_ada'])->get('/_uji-fitur-asing', fn () => 'ok');
        Route::middleware(['web', 'fitur:field_safety'])->get('/_uji-fitur-dikenal', fn () => 'ok');

        $this->get('/_uji-fitur-asing')->assertNotFound();
        $this->get('/_uji-fitur-dikenal')->assertOk();
    }

    /**
     * PT7-10 M2 dan M3, PT7-7a: jam di kertas dibaca dalam zona gunung dengan penandanya, dan
     * jam kembali yang tidak lagi sesudah mulai trip tidak dicetak seolah masih berlaku.
     */
    public function test_the_print_version_writes_its_times_in_the_mountain_zone(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        [$user, $trip] = $this->pendakianBerlangsung();
        $trip->update(['planned_date' => '2026-10-17', 'start_time' => '07:00', 'expected_return_at' => Carbon::parse('2026-10-18 08:00:00', 'UTC'), 'registration_number' => 'GPP-1017-0042']);

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jalur.gunung', 'Gunung Gede')
            ->where('rencana.jamMulai', '07.00 WIB')
            ->where('rencana.kembali', 'Minggu, 18 Oktober 2026 15.00 WIB')
            ->where('rencana.registrasi', 'GPP-1017-0042')
            ->where('pesan', fn (?string $pesan) => str_contains((string) $pesan, 'sampai Minggu, 18 Oktober 2026 pukul 16.00 WIB')));

        $trip->update(['planned_date' => '2026-10-24', 'end_date' => '2026-10-25']);

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rencana.kembali', 'Perlu diperbarui di ruang kerja trip: jam kembali tidak sesudah mulai trip.')
            ->where('pesan', null));
    }

    /**
     * PT7-8: kertas trip yang sudah dibatalkan atau selesai tidak memuat instruksi eskalasi.
     */
    public function test_a_finished_or_cancelled_trip_prints_no_escalation(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        [$user, $trip] = $this->pendakianBerlangsung();
        $trip->update(['planned_date' => '2026-10-17', 'expected_return_at' => Carbon::parse('2026-10-18 08:00:00', 'UTC')]);

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('pesan', fn (?string $pesan) => $pesan !== null));

        foreach ([TripStatus::CANCELLED, TripStatus::COMPLETED] as $status) {
            $trip->update(['status' => $status->value]);

            $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('pesan', null));
        }
    }

    /**
     * PT7-10 M13: pos tanpa koordinat tidak digambar di sketsa (nilainya bukan titik 0, 0).
     */
    public function test_the_sketch_leaves_out_checkpoints_without_coordinates(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung();

        foreach ([[3, -6.99, 110.0], [2, null, null], [1, -7.0, 110.0]] as [$urutan, $lat, $lng]) {
            Checkpoint::factory()->create(['trail_id' => $trip->trail_id, 'sequence' => $urutan, 'name' => 'Pos '.$urutan, 'latitude' => $lat, 'longitude' => $lng]);
        }

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            // Bilangan bulat karena JSON menulis 110.0 sebagai 110.
            ->where('sketsa.pos', [['urutan' => 1, 'lng' => 110, 'lat' => -7], ['urutan' => 3, 'lng' => 110, 'lat' => -6.99]])
            ->where('pos', fn ($pos) => collect($pos)->pluck('urutan')->all() === [1, 2, 3]));
    }

    /**
     * PT7-15 dan ruling W6-7a: label kertas sesuai isinya. Aturan tanpa tanggal verifikasi
     * disebut belum diverifikasi (bukan "tanggal cek tidak tercatat"), baris ringkasan rencana
     * berlabel Ringkasan, dan kontak resmi berlabel pengelola resmi di gunung, bukan pengelola
     * jalur.
     */
    public function test_the_print_labels_match_what_they_carry(): void
    {
        $cetak = File::get(resource_path('js/pages/Trips/Print.tsx'));
        $kartu = File::get(resource_path('js/components/mendaki/KartuDarurat.tsx'));

        $this->assertStringContainsString('Belum diverifikasi Memount. Pastikan di pos registrasi.', $cetak);
        $this->assertStringNotContainsString('Tanggal cek tidak tercatat', $cetak);
        $this->assertStringContainsString('<dt className="font-bold">Ringkasan</dt>', $cetak);
        $this->assertStringContainsString("Pengelola resmi di \${jalur.gunung ?? 'gunung ini'}", $cetak);
        $this->assertStringContainsString('Pengelola resmi di {data.trip.gunung}', $kartu);

        foreach ([$cetak, $kartu] as $isi) {
            $this->assertStringNotContainsString('Pengelola jalur', $isi);
            $this->assertStringContainsString('Kontak pengelola resmi gunung ini belum tercatat di Memount.', $isi);
        }

        // Putaran perbaikan 1 (ruling W6-7i): tabel pos bergulir di wadahnya sendiri pada 320 px
        // (M-1 N18, WCAG 1.4.10); tautan "Lewati ke konten utama" dapat memfokuskan isi (M-4); dan
        // waktu di kertas adalah waktu data, bukan waktu cetak (M-5), benar di setiap cabang.
        $this->assertMatchesRegularExpression('/<div className="relative mt-2 overflow-x-auto">\s*<table/', $cetak);
        $this->assertStringContainsString('<main id="konten" tabIndex={-1}', $cetak);
        $this->assertStringContainsString('data per {dicetak}', $cetak);
        $this->assertStringNotContainsString('· dicetak {dicetak}', $cetak);
    }
}
