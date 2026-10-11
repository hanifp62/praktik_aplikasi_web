<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\HikingSessionStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\SourceType;
use App\Enums\TripStatus;
use App\Models\Authority;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingSession;
use App\Models\PermitRequirement;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\Trips\PaketLapangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Manifest paket lapangan luring (spec v3 bagian 10.2 dan 10.6; rencana induk W6 butir 4; ruling
 * W6-R3): pemilik saja, jalur terbit saja, setiap bagian dengan ukuran dan SHA-256 yang cocok
 * dengan bita yang dikirim, versi berupa hash isi yang stabil, potret bertanggal verifikasi, dan
 * tanpa skor, keluaran AI, GPX, atau orang lain. Mati sampai pemilik memutus Q10 dan Q14.
 *
 * Diperluas pra-tinjau Tugas 8 (ruling P-R10): gerbang jalur terbit dan trip aktif juga di rute
 * bagian (PT8-5), keadaan sesi yang tidak mengubah versi (PT8-7), hitungan persiapan yang sama
 * dengan ruang kerja (PT8-12 bagian b), nama gunung dan tanggal trip di manifest (PT8-13),
 * atribusi sumber garis dari data jalur (PT8-4), ukuran bita pada teks multibita, bagian tak
 * dikenal, dan header private di bagian (PT8-15), serta kedua sakelar bersama (ruling W6-8g).
 *
 * Putaran perbaikan 1: versi meng-hash atribusi (ruling W6-8i), butir persiapan dalam urutan
 * halaman Persiapan (W6-8j), versi yang tidak bergantung pada skema dan host (W6-8l), dan test
 * pembunuh mutan tinjauan m-3 (label dan sumber status, ambang dari config, tanggal trip).
 */
class FieldPackManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_pack.enabled' => true]);
    }

    /**
     * Nama pos ketiga memuat karakter multibita ("°"), supaya ukuran bita berbeda dari jumlah
     * karakter (PT8-15).
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(): array
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $trail = Trail::factory()->published()->create(['name' => 'Gede via Cibodas']);

        foreach (['Pintu Cibodas', 'Telaga Biru', 'Kandang Badak, 2.395 m dpl, 12 °C'] as $i => $nama) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $i + 1, 'name' => $nama, 'elevation_m' => 1400 + $i * 400, 'latitude' => -6.74 - $i * 0.01, 'longitude' => 107.0]);
        }

        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-22']);

        return [$user, $trip->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(User $user, TripPlan $trip): array
    {
        return $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertOk()->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function bagian(User $user, TripPlan $trip, string $nama): array
    {
        return json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, $nama]))->assertOk()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_the_pack_belongs_to_the_trip_owner_only(): void
    {
        [$user, $trip] = $this->rencana();

        $this->get(route('trips.pack', $trip))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack', $trip))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack.section', [$trip, 'rencana']))->assertForbidden();

        $respons = $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertOk();

        $this->assertStringContainsString('no-store', $respons->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $respons->headers->get('Cache-Control'));

        $bagian = $this->actingAs($user)->get(route('trips.pack.section', [$trip, 'rencana']))->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $bagian);
        $this->assertStringContainsString('private', $bagian);

        // Nama bagian di luar ketiganya tidak ada, bukan galat server (PT8-15).
        $this->actingAs($user)->get('/trips/'.$trip->id.'/paket/lain')->assertNotFound();
    }

    /**
     * Selama mati, rute paket menjawab 404 untuk siapa pun, juga sesudah melewati batas laju:
     * middleware fitur berjalan sebelum ThrottleRequests (PT7-17; ruling W6-8g), jadi kiriman
     * ke-31 dalam semenit tidak membocorkan rutenya lewat 429.
     */
    public function test_while_the_switch_is_off_the_pack_does_not_exist(): void
    {
        config(['hiking.field_pack.enabled' => false]);
        [$user, $trip] = $this->rencana();

        $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();
        $this->actingAs($user)->get(route('trips.pack.section', [$trip, 'lapangan']))->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack', $trip))->assertNotFound();

        $jawaban = [];

        for ($i = 0; $i < 31; $i++) {
            $jawaban[] = $this->actingAs($user)->getJson(route('trips.pack', $trip))->status();
        }

        $this->assertSame([404], array_values(array_unique($jawaban)));
    }

    /**
     * Perangkat hanya menyebut paket "Siap luring" bila setiap bagian cocok dengan manifest, jadi
     * ukuran dan hash di manifest harus sama dengan bita yang benar-benar dikirim. Ukuran adalah
     * bita, bukan karakter: bagian lapangan memuat teks multibita (PT8-15). URL bagian relatif
     * (Kontrak keluaran Tugas 8).
     *
     * Rumus versi brief diubah ruling W6-8i: hash ketiga bagian ditambah hash atribusi, supaya
     * sumber garis yang berganti mengganti versi. Ambang Buka dibaca dari config (tinjauan m-3).
     */
    public function test_each_section_matches_its_size_and_hash(): void
    {
        [$user, $trip] = $this->rencana();
        $manifest = $this->manifest($user, $trip);

        $this->assertSame(['lapangan', 'rencana', 'dinamis'], array_column($manifest['bagian'], 'nama'));
        $this->assertSame(array_map(fn (string $nama) => '/trips/'.$trip->id.'/paket/'.$nama, PaketLapangan::BAGIAN), array_column($manifest['bagian'], 'url'));

        foreach ($manifest['bagian'] as $bagian) {
            $isi = $this->actingAs($user)->get($bagian['url'])->assertOk()->assertHeader('Content-Type', 'application/json')->getContent();

            $this->assertSame($bagian['ukuran'], strlen($isi), $bagian['nama']);
            $this->assertSame($bagian['sha256'], hash('sha256', $isi), $bagian['nama']);

            if ($bagian['nama'] === 'lapangan') {
                $this->assertStringContainsString('12 °C', $isi);
                $this->assertNotSame(mb_strlen($isi), strlen($isi), 'Fixture lapangan harus memuat teks multibita.');
            }
        }

        $this->assertSame(substr(hash('sha256', implode('|', [...array_column($manifest['bagian'], 'sha256'), hash('sha256', implode("\n", $manifest['atribusi']))])), 0, 16), $manifest['versi']);
        $this->assertSame(array_sum(array_column($manifest['bagian'], 'ukuran')), $manifest['ukuran']);
        $this->assertSame(24, $manifest['ambang']['bukaJam']);

        config(['hiking.field_pack.open_status_stale_after_hours' => 6]);
        $this->assertSame(6, $this->manifest($user, $trip)['ambang']['bukaJam']);
    }

    /**
     * Ruling W6-8l (tinjauan m-2): URL mode lapangan relatif, jadi data yang sama lewat skema dan
     * host yang berbeda menghasilkan versi yang sama, dan paket tidak membawa http:// dari balik
     * proxy TLS.
     */
    public function test_the_version_does_not_depend_on_the_scheme_or_host(): void
    {
        [$user, $trip] = $this->rencana();
        $jalan = route('trips.pack', $trip, absolute: false);

        $a = $this->actingAs($user)->getJson('http://a.test'.$jalan)->assertOk()->json();
        $b = $this->actingAs($user)->getJson('https://b.test'.$jalan)->assertOk()->json();

        $this->assertSame($a['versi'], $b['versi']);
        $this->assertSame('/trips/'.$trip->id.'/hike/tiba', $this->bagian($user, $trip, 'lapangan')['urls']['tiba']);
    }

    /**
     * Daftar paket luring Tugas 9 membedakan jalur bernama sama lewat gunungnya dan mengurutkan
     * menurut tanggal trip (PT8-13). Tanggal adalah tanggal berangkat, bukan tanggal selesai trip
     * bermalam (tinjauan m-3).
     */
    public function test_the_manifest_names_the_mountain_and_the_trip_date(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->update(['end_date' => '2026-10-24']);

        $this->assertSame([
            'id' => $trip->id,
            'nama' => $trip->name,
            'jalur' => 'Gede via Cibodas',
            'gunung' => $trip->trail->mountain->name,
            'tanggal' => '2026-10-22',
        ], $this->manifest($user, $trip)['trip']);
    }

    /**
     * Atribusi berurutan tetap; indeks 0 menyebut sumber garis jalur dari data_sources jalur,
     * bukan klaim tetap "data rute Memount" (PT8-4). Setiap cabang kalimatnya diuji: sumber
     * lengkap, sumber tanpa pemilik dan tautan, dan sumber yang tidak tercatat. Sumber yang
     * berganti mengganti versi walau ketiga bagian tetap (ruling W6-8i), supaya perangkat tidak
     * menyimpan atribusi basi.
     */
    public function test_the_attribution_names_the_source_of_the_line(): void
    {
        [$user, $trip] = $this->rencana();
        $sumber = DataSource::factory()->create(['source_name' => 'Balai Besar TNGGP', 'source_owner' => 'Kementerian Kehutanan', 'source_url' => 'https://tnggp.go.id/jalur']);
        $trip->trail->forceFill(['data_source_id' => $sumber->id])->saveQuietly();

        $lengkap = $this->manifest($user, $trip);
        $this->assertSame([
            'Garis jalur, pos, dan angka: Balai Besar TNGGP, Kementerian Kehutanan (https://tnggp.go.id/jalur).',
            'Peta dasar tidak dibawa paket.',
            'Prakiraan cuaca: BMKG.',
        ], $lengkap['atribusi']);

        $sumber->update(['source_owner' => null, 'source_url' => null]);
        $ringkas = $this->manifest($user, $trip);
        $this->assertSame('Garis jalur, pos, dan angka: Balai Besar TNGGP.', $ringkas['atribusi'][0]);
        $this->assertSame(array_column($lengkap['bagian'], 'sha256'), array_column($ringkas['bagian'], 'sha256'));
        $this->assertNotSame($lengkap['versi'], $ringkas['versi']);

        $trip->trail->forceFill(['data_source_id' => null])->saveQuietly();
        $this->assertSame('Sumber garis jalur belum tercatat.', $this->manifest($user, $trip)['atribusi'][0]);
    }

    /**
     * Versi adalah hash isi: tidak berubah karena waktu unduh atau karena keadaan sesi (pendaki
     * tiba di pos, menjeda, atau menyalakan rekam jejak; PT8-7), dan berubah ketika datanya
     * berubah. Bagian lapangan tidak membawa laporan; laporan ada di bagian dinamis.
     */
    public function test_the_version_is_stable_until_the_data_changes(): void
    {
        [$user, $trip] = $this->rencana();
        $versi = $this->manifest($user, $trip)['versi'];

        $this->travel(3)->hours();
        $this->assertSame($versi, $this->manifest($user, $trip)['versi']);

        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);
        HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()->subHours(2), 'reached_checkpoint_sequence' => 2, 'paused_at' => now()->subMinutes(10), 'paused_seconds' => 600]);
        UserPreference::factory()->create(['user_id' => $user->id, 'record_track' => true]);
        // actingAs memakai instance yang sama di setiap permintaan; relasi preference yang
        // termuat kosong di muat pertama harus dibaca ulang seperti pada permintaan sungguhan.
        $user->refresh();
        $this->assertTrue($user->preference->record_track);
        $this->assertSame($versi, $this->manifest($user, $trip)['versi']);

        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-06']);

        $this->assertNotSame($versi, $this->manifest($user, $trip)['versi']);
        $this->assertArrayNotHasKey('laporan', $this->bagian($user, $trip, 'lapangan'));
        $this->assertCount(1, $this->bagian($user, $trip, 'dinamis')['laporan']);
    }

    /**
     * R-008 dan R-022: jalur yang tidak terbit atau diarsipkan tidak dipaket, dan trip yang sudah
     * selesai atau dibatalkan tidak membutuhkan paket. Gerbangnya berlaku untuk manifest dan
     * setiap bagian (PT8-5).
     */
    public function test_only_active_trips_on_published_trails_are_packed(): void
    {
        [$user, $trip] = $this->rencana();
        $tidakAda = function () use ($user, $trip): void {
            $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();

            foreach (PaketLapangan::BAGIAN as $bagian) {
                $this->actingAs($user)->get(route('trips.pack.section', [$trip, $bagian]))->assertNotFound();
            }
        };

        $trip->trail->forceFill(['is_published' => false])->saveQuietly();
        $tidakAda();

        $trip->trail->forceFill(['is_published' => true, 'archived_at' => now()])->saveQuietly();
        $tidakAda();

        $trip->trail->forceFill(['archived_at' => null])->saveQuietly();
        $trip->update(['status' => TripStatus::COMPLETED->value]);
        $tidakAda();

        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $tidakAda();
    }

    /**
     * Paket minimal (spec v3 bagian 10.6): tanpa skor atau persen, keluaran AI, GPX, nama
     * pelapor, atau rencana orang lain.
     */
    public function test_the_pack_carries_no_score_ai_gpx_or_other_people(): void
    {
        [$user, $trip] = $this->rencana();
        $orangLain = User::factory()->create(['name' => 'Sari Pelapor']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'user_id' => $orangLain->id, 'hike_date' => '2026-10-05']);
        TripPlan::factory()->create(['user_id' => $orangLain->id, 'trail_id' => $trip->trail_id, 'name' => 'Rencana Sari']);

        $semua = '';

        foreach ($this->manifest($user, $trip)['bagian'] as $bagian) {
            $semua .= $this->actingAs($user)->get($bagian['url'])->getContent();
        }

        foreach (['"skor"', '"score"', '"persen"', '"kecocokan"', '"fit"', 'gpx', 'Sari Pelapor', 'Rencana Sari', '%'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $semua);
        }

        $this->assertStringContainsString('"sumber":"paket"', $semua);
    }

    /**
     * Waktu potret status adalah verified_at, bukan waktu unduh (spec v3 bagian 10.3). Potret
     * membawa label, sumber, dan jenis sumbernya (R-009, R-013; tinjauan m-3), karena perangkat
     * menulis "Terakhir diketahui: {label}" dengan sumbernya.
     */
    public function test_the_status_snapshot_is_dated_by_its_verification(): void
    {
        [$user, $trip] = $this->rencana();
        $resmi = DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value]);
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => Carbon::parse('2026-10-06 01:30:00', 'UTC'), 'source' => 'Balai Besar TNGGP', 'data_source_id' => $resmi->id]);

        $dinamis = json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, 'dinamis']))->getContent(), true);

        $this->assertSame('OPEN', $dinamis['status']['nilai']);
        $this->assertSame(['Buka', 'Balai Besar TNGGP', 'OFFICIAL'], [$dinamis['status']['label'], $dinamis['status']['sumber'], $dinamis['status']['jenisSumber']]);
        $this->assertSame('2026-10-06T01:30:00+00:00', $dinamis['status']['dicek']);
        $this->assertSame('6 Okt 2026 08.30 WIB', $dinamis['status']['dicekTeks']);
    }

    /**
     * Bagian rencana: rencana dan aturan dari sumber yang sama dengan versi cetak
     * (CetakTrip::rencanaDanAturan, ruling W6-8f), dan persiapan dengan hitungan ruang kerja
     * (TripSteps::hitungPersiapan, PT8-12 bagian b): butir Tidak berlaku tidak ikut "berlaku",
     * dan setiap butir menyebutnya.
     *
     * Harapan urutan diubah ruling W6-8j: butir mengikuti urutan halaman Persiapan, jadi butir
     * kritis lebih dulu dalam kategorinya (Jas hujan, Headlamp cadangan, lalu Sarung tangan),
     * bukan urut id.
     */
    public function test_the_plan_section_counts_preparation_like_the_workspace(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->update(['name' => 'Gede akhir pekan', 'start_time' => '05:30', 'registration_number' => 'TNGGP-0042']);
        PermitRequirement::factory()->create(['mountain_id' => $trip->trail->mountain_id, 'notes' => 'Kuota harian.', 'verified_at' => Carbon::parse('2026-09-30 02:00:00', 'UTC')]);

        foreach ([['Jas hujan', true, PreparationStatus::CONFIRMED], ['Sarung tangan', false, PreparationStatus::NOT_APPLICABLE], ['Headlamp cadangan', true, PreparationStatus::NOT_CONFIRMED]] as [$label, $kritis, $status]) {
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => $label, 'is_critical' => $kritis, 'status' => $status->value]);
        }

        $rencana = $this->bagian($user, $trip, 'rencana');

        $this->assertSame(['Gede akhir pekan', '05.30 WIB', 'TNGGP-0042'], [$rencana['rencana']['nama'], $rencana['rencana']['jamMulai'], $rencana['rencana']['registrasi']]);
        $this->assertSame(['Kuota harian.', '30 September 2026 WIB'], [$rencana['aturan']['catatan'], $rencana['aturan']['dicek']]);
        $this->assertSame([
            'dikonfirmasi' => 1,
            'berlaku' => 2,
            'jumlah' => 3,
            'butir' => [
                ['nama' => 'Jas hujan', 'kritis' => true, 'keadaan' => 'Sudah dikonfirmasi', 'berlaku' => true],
                ['nama' => 'Headlamp cadangan', 'kritis' => true, 'keadaan' => 'Belum dikonfirmasi', 'berlaku' => true],
                ['nama' => 'Sarung tangan', 'kritis' => false, 'keadaan' => 'Tidak berlaku', 'berlaku' => false],
            ],
        ], $rencana['persiapan']);
    }

    /**
     * Ruling W6-8j: urutan butir di paket sama dengan halaman Persiapan daring (satu sumber,
     * HalamanPersiapan::urutanTampil): kelompok kategori, lalu kritis, urutan templat, dan id.
     * Fixture dibuat berlawanan: urutan pembuatan (id) memberi Izin pendakian, Sarung tangan,
     * Headlamp, Jas hujan, sedangkan "kritis dulu lalu id" memberi Izin pendakian, Headlamp,
     * Jas hujan, Sarung tangan. Keduanya berbeda dari halaman.
     */
    public function test_the_plan_section_lists_preparation_in_the_page_order(): void
    {
        [$user, $trip] = $this->rencana();
        $templat = PreparationTemplate::create(['name' => 'Uji urutan paket', 'is_default' => false]);

        foreach ([
            ['Izin pendakian', PreparationCategory::LOGISTICS, true, null],
            ['Sarung tangan', PreparationCategory::EQUIPMENT, false, null],
            ['Headlamp cadangan', PreparationCategory::EQUIPMENT, true, 5],
            ['Jas hujan', PreparationCategory::EQUIPMENT, true, 2],
        ] as [$label, $kategori, $kritis, $urutanTemplat]) {
            $sumber = $urutanTemplat === null ? null : PreparationItem::create(['preparation_template_id' => $templat->id, 'category' => $kategori->value, 'label' => $label, 'is_critical' => $kritis, 'sort_order' => $urutanTemplat]);
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'preparation_item_id' => $sumber?->id, 'category' => $kategori->value, 'label' => $label, 'is_critical' => $kritis, 'status' => PreparationStatus::NOT_CONFIRMED->value]);
        }

        $paket = array_column($this->bagian($user, $trip, 'rencana')['persiapan']['butir'], 'nama');
        $halaman = collect($this->actingAs($user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props']['kategori'])
            ->flatMap(fn (array $kategori) => array_column($kategori['items'], 'label'))
            ->all();

        $this->assertSame(['Jas hujan', 'Headlamp cadangan', 'Sarung tangan', 'Izin pendakian'], $paket);
        $this->assertSame($halaman, $paket);
    }

    /**
     * Ruling W6-8g: dengan paket dan alat keselamatan menyala, bagian lapangan membawa kartu
     * darurat (112, Basarnas 115, dan pengelola resmi menurut ruling W6-7a: balai taman nasional
     * mendahului instansi pemerintah yang lebih dulu tercatat, dan pengelola basecamp tidak pernah
     * menjadi kontak), dan versinya tetap sama pada dua muat berturut-turut.
     */
    public function test_with_both_switches_on_the_field_section_carries_the_emergency_card(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->rencana();
        $gunung = $trip->trail->mountain;

        foreach ([
            ['Pemerintah Kabupaten Cianjur', 'pemkab-cianjur', AuthorityType::GOVERNMENT, null, '(0263) 261-100'],
            ['Basecamp Cibodas', 'basecamp-cibodas', AuthorityType::BASECAMP, null, '0812-1111-2222'],
            ['Balai Besar Taman Nasional Gunung Gede Pangrango', 'bbtnggp', AuthorityType::NATIONAL_PARK, 'BBTNGGP', '(0263) 512-776'],
        ] as [$nama, $slug, $jenis, $singkatan, $kontak]) {
            Authority::create(['name' => $nama, 'slug' => $slug, 'type' => $jenis->value, 'abbreviation' => $singkatan, 'contact' => $kontak, 'verified_at' => now()->subMonth()])->mountains()->attach($gunung);
        }

        $versi = $this->manifest($user, $trip)['versi'];
        $darurat = $this->bagian($user, $trip, 'lapangan')['darurat'];

        $this->assertSame([['label' => 'Darurat', 'nomor' => '112'], ['label' => 'Basarnas', 'nomor' => '115']], $darurat['nomor']);
        $this->assertSame(['nama' => 'Balai Besar Taman Nasional Gunung Gede Pangrango (BBTNGGP)', 'kontak' => '(0263) 512-776', 'telepon' => '0263512776'], $darurat['pengelola']);
        $this->assertSame($versi, $this->manifest($user, $trip)['versi']);

        config(['hiking.field_safety.enabled' => false]);
        $this->assertNull($this->bagian($user, $trip, 'lapangan')['darurat']);
    }
}
