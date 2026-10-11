<?php

namespace Tests\Feature;

use App\Enums\ConditionTag;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\RestrictedArea;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Data dinamis di paket lapangan (spec v3 bagian 10.3; rencana induk W6 butir 6; ruling W6-R8).
 * Server menghitung waktu turun status Buka (Q9, bawaan 24 jam dari verified_at, dan tidak
 * melewati expires_at: ruling W6-8b) dan mengirimnya di paket; perangkat
 * (resources/js/luring/kesegaran.ts) menurunkan Buka menjadi "belum dapat dipastikan" sesudah
 * waktu itu, tanpa sinyal, dan menulis Tutup dan Dibatasi dengan umur dan masa berlakunya
 * (W6-8e). Pembatasan segmen dan area terbatas ikut paket (W6-8c). Cuaca dibawa untuk setiap
 * tanggal trip, dan tanggal tanpa baris membedakan "belum terbit" dari "belum tersedia di
 * Memount" (W6-8a). Laporan komunitas paling banyak sepuluh dalam tiga puluh hari.
 *
 * Asersi brief yang diubah ruling, dengan alasannya di docblock test masing-masing: kalimat
 * cuaca 8 Okt (W6-8a), bentuk cuaca per hari dengan jam berzona dan spasi tak terputus
 * (W6-8a, butir 7), dan kalimat Tutup yang kini menyebut umur dan masa berlaku yang lewat (W6-8e).
 *
 * Putaran perbaikan 1: pembatasan segmen menurut urutan rute (ruling W6-8h), masa berlaku isian
 * tanggal ditulis tanggal saja di setiap zona (W6-8k), kalimat area terbatas (W6-8m), dan test
 * pembunuh mutan tinjauan m-3.
 */
class FieldPackStalenessTest extends TestCase
{
    use RefreshDatabase;

    private const R012 = 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_pack.enabled' => true]);
    }

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(string $tanggal = '2026-10-08', ?string $selesai = null, TripStatus $status = TripStatus::PLANNED, ?Mountain $gunung = null): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->published()->create(['weather_adm4_code' => '32.03.13.2001', 'weather_reference_area' => 'Cimacan', ...($gunung ? ['mountain_id' => $gunung->id] : [])]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => $status->value, 'planned_date' => $tanggal, 'end_date' => $selesai]);

        return [$user, $trip->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    private function dinamis(User $user, TripPlan $trip): array
    {
        return json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, 'dinamis']))->assertOk()->getContent(), true);
    }

    /**
     * Satu baris prakiraan BMKG; waktu dalam UTC.
     */
    private function prakiraan(string $waktuUtc, string $cuaca, ?string $analisisUtc = '2026-10-07 00:00:00', string $diambilUtc = '2026-10-07 03:00:00', ?string $area = 'Cimacan', string $kode = '32.03.13.2001'): WeatherSnapshot
    {
        return WeatherSnapshot::create([
            'adm4_code' => $kode,
            'reference_area' => $area,
            'forecast_at' => Carbon::parse($waktuUtc, 'UTC'),
            'local_datetime' => Carbon::parse($waktuUtc, 'UTC')->addHours(7)->toDateTimeString(),
            'weather_description' => $cuaca,
            'temperature_c' => 17,
            'analysis_date' => $analisisUtc === null ? null : Carbon::parse($analisisUtc, 'UTC'),
            'source' => 'BMKG',
            'fetched_at' => Carbon::parse($diambilUtc, 'UTC'),
        ]);
    }

    /**
     * Buka yang verified_at-nya tidak tercatat tetap dapat diunduh, tanpa waktu cek dan tanpa
     * waktu turun, sehingga perangkat langsung menulis "belum dapat dipastikan" (tinjauan m-3).
     */
    public function test_an_open_status_carries_the_time_it_stops_being_trusted(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => Carbon::parse('2026-10-06 01:30:00', 'UTC')]);

        $this->assertSame('2026-10-07T01:30:00+00:00', $this->dinamis($user, $trip)['status']['turunSejak']);

        config(['hiking.field_pack.open_status_stale_after_hours' => 6]);

        $this->assertSame('2026-10-06T07:30:00+00:00', $this->dinamis($user, $trip)['status']['turunSejak']);

        $trip->trail->officialStatuses()->update(['verified_at' => null]);
        $status = $this->dinamis($user, $trip)['status'];

        $this->assertSame(['OPEN', null, null, null], [$status['nilai'], $status['dicek'], $status['dicekTeks'], $status['turunSejak']]);
    }

    /**
     * Ruling W6-8b: turunSejak = min(verified_at + N jam, expires_at). Buka musiman yang berakhir
     * pukul 17.00 WIB turun pada 17.00, bukan sesudah 24 jam, karena server sendiri berhenti
     * memakai catatan itu sesudah expires_at. Masa berlaku menulis jam bila jam itu memang
     * tercatat, dan tanggal saja bila instannya tepat tengah malam di zona gunung (cabang kedua).
     * Bentuk simpanan isian tanggal admin, tengah malam UTC, diuji lewat rute admin di
     * test_an_expiry_entered_as_a_date_is_shown_as_a_date_in_every_zone.
     */
    public function test_an_open_status_never_outlives_its_expiry(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 03:00:00', 'UTC'));
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => Carbon::parse('2026-10-06 01:30:00', 'UTC'), 'expires_at' => Carbon::parse('2026-10-06 10:00:00', 'UTC')]);

        $status = $this->dinamis($user, $trip)['status'];
        $this->assertSame(['2026-10-06T10:00:00+00:00', '2026-10-06T10:00:00+00:00', '6 Okt 2026 17.00 WIB'], [$status['turunSejak'], $status['berlakuIso'], $status['berlaku']]);

        $trip->trail->officialStatuses()->update(['expires_at' => Carbon::parse('2026-10-30 17:00:00', 'UTC')]);

        $status = $this->dinamis($user, $trip)['status'];
        $this->assertSame(['2026-10-07T01:30:00+00:00', '2026-10-30T17:00:00+00:00', '31 Okt 2026 WIB'], [$status['turunSejak'], $status['berlakuIso'], $status['berlaku']]);
    }

    /**
     * Ruling W6-8k (tinjauan I-1, opsi B): masa berlaku yang dimasukkan admin sebagai tanggal
     * tersimpan tengah malam UTC. Paket menulisnya sebagai tanggal saja di gunung WIB, WITA, dan
     * WIT, bukan "07.00 WIB", "08.00 WITA", atau "09.00 WIT" yang tidak pernah dimasukkan siapa
     * pun (R-005). berlakuIso dan turunSejak tetap memakai instan tersimpan.
     */
    public function test_an_expiry_entered_as_a_date_is_shown_as_a_date_in_every_zone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        foreach (['Asia/Jakarta' => 'WIB', 'Asia/Makassar' => 'WITA', 'Asia/Jayapura' => 'WIT'] as $zona => $label) {
            [$user, $trip] = $this->rencana(gunung: Mountain::factory()->create(['timezone' => $zona]));
            $this->actingAs($admin)->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trip->trail_id, 'status' => 'CLOSED', 'reason' => 'Pemulihan ekosistem', 'expires_at' => '2026-10-31'])->assertRedirect(route('admin.statuses'));

            $status = $this->dinamis($user, $trip)['status'];
            $this->assertSame(['CLOSED', '31 Okt 2026 '.$label, '2026-10-31T00:00:00+00:00'], [$status['nilai'], $status['berlaku'], $status['berlakuIso']], $zona);
        }

        $this->actingAs($admin)->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trip->trail_id, 'status' => 'OPEN', 'expires_at' => '2026-10-08'])->assertRedirect(route('admin.statuses'));
        $status = $this->dinamis($user, $trip)['status'];

        $this->assertSame(['OPEN', '8 Okt 2026 WIT', '2026-10-08T00:00:00+00:00'], [$status['nilai'], $status['berlaku'], $status['turunSejak']]);
    }

    /**
     * Tutup dan Dibatasi tidak pernah turun: tetap tampil dengan umurnya. Belum diketahui tetap
     * Belum diketahui (R-006).
     */
    public function test_closed_and_unknown_statuses_never_expire_into_something_else(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value, 'reason' => 'Pemulihan ekosistem']);

        $status = $this->dinamis($user, $trip)['status'];
        $this->assertSame(['CLOSED', null, 'Pemulihan ekosistem'], [$status['nilai'], $status['turunSejak'], $status['alasan']]);

        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::RESTRICTED->value, 'reason' => 'Kuota 600 pendaki per hari']);
        $status = $this->dinamis($user, $trip)['status'];
        $this->assertSame(['RESTRICTED', null], [$status['nilai'], $status['turunSejak']]);

        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::UNKNOWN->value]);
        $this->assertSame(['UNKNOWN', null], [$this->dinamis($user, $trip)['status']['nilai'], $this->dinamis($user, $trip)['status']['turunSejak']]);
    }

    /**
     * Perangkat menurunkan Buka sesudah waktunya, menulis kalimat R-012 sebelum itu, dan tidak
     * pernah menyebut Tutup, Dibatasi, atau Belum diketahui sebagai keadaan terkini.
     *
     * Harapan Tutup brief diubah ruling W6-8e: kalimatnya kini menyebut umur cek ("55 hari
     * lalu") dan bahwa masa berlakunya sudah lewat, karena spec v3 bagian 10.3 meminta umurnya
     * makin menonjol. Kasus tambahan pra-tinjau PT8-10: Dibatasi, Belum diketahui dengan kalimat
     * persis, turunSejak yang tidak terbaca (gagal tertutup), tanpa tanggal cek dengan kalimat
     * persis, dan alasan yang sudah berakhir titik. Masa berlaku Tutup memakai bentuk simpanan
     * isian tanggal admin (tengah malam UTC, ruling W6-8k), dan "sudah lewat" berlaku tepat pada
     * instan itu, batas yang sama dengan turunSejak (tinjauan m-3).
     */
    public function test_the_device_downgrades_an_open_status_after_its_threshold(): void
    {
        $buka = ['nilai' => 'OPEN', 'label' => 'Buka', 'sumber' => 'Balai Besar TNGGP', 'jenisSumber' => 'OFFICIAL', 'alasan' => null, 'dicek' => '2026-10-06T01:30:00+00:00', 'dicekTeks' => '6 Okt 2026 08.30 WIB', 'berlaku' => null, 'berlakuIso' => null, 'turunSejak' => '2026-10-07T01:30:00+00:00', 'pembatasan' => [], 'areaTerbatas' => []];
        $tutup = [...$buka, 'nilai' => 'CLOSED', 'label' => 'Tutup', 'alasan' => 'Pemulihan ekosistem', 'berlaku' => '31 Okt 2026 WIB', 'berlakuIso' => '2026-10-31T00:00:00+00:00', 'turunSejak' => null];
        $tanpaCek = [...$buka, 'dicek' => null, 'dicekTeks' => null, 'turunSejak' => null];
        $dibatasi = [...$buka, 'nilai' => 'RESTRICTED', 'label' => 'Dibatasi', 'alasan' => 'Kuota 600 pendaki per hari.', 'turunSejak' => null];
        $ms = fn (string $utc) => Carbon::parse($utc, 'UTC')->getTimestampMs();

        [$sebelum, $sesudah, $tertutup, $tidakTercatat, $belum, $terbatas, $rusak, $tertutupBerlaku, $tepatBatas] = $this->node('kalimatStatusLuring', [
            [$buka, $ms('2026-10-07 01:29:00')],
            [$buka, $ms('2026-10-07 01:30:00')],
            [$tutup, $ms('2026-12-01 00:00:00')],
            [$tanpaCek, $ms('2026-10-06 02:00:00')],
            [[...$buka, 'nilai' => 'UNKNOWN', 'label' => 'Belum diketahui', 'turunSejak' => null], 0],
            [$dibatasi, $ms('2026-10-06 20:00:00')],
            [[...$buka, 'turunSejak' => 'rusak'], $ms('2026-10-06 02:00:00')],
            [$tutup, $ms('2026-10-20 03:00:00')],
            [$tutup, $ms('2026-10-31 00:00:00')],
        ]);

        $this->assertSame(['judul' => 'Terakhir diketahui: Buka', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB, Balai Besar TNGGP. '.self::R012, 'turun' => false], $sebelum);
        $this->assertSame(['judul' => 'Status belum dapat dipastikan', 'kalimat' => 'Status belum dapat dipastikan sejak 6 Okt 2026 08.30 WIB. Pastikan di pos registrasi.', 'turun' => true], $sesudah);
        $this->assertSame(['judul' => 'Terakhir diketahui: Tutup', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB (55 hari lalu), Balai Besar TNGGP. Alasan: Pemulihan ekosistem. Berlaku sampai 31 Okt 2026 WIB. Masa berlaku itu sudah lewat; pastikan di pos registrasi.', 'turun' => false], $tertutup);
        $this->assertSame(['judul' => 'Status belum dapat dipastikan', 'kalimat' => 'Status belum dapat dipastikan: tanggal cek tidak tercatat. Pastikan di pos registrasi.', 'turun' => true], $tidakTercatat);
        $this->assertSame(['judul' => 'Belum diketahui', 'kalimat' => 'Status resmi jalur ini belum diketahui saat paket dibuat. Pastikan di pos registrasi.', 'turun' => false], $belum);
        $this->assertSame(['judul' => 'Terakhir diketahui: Dibatasi', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB, Balai Besar TNGGP. Alasan: Kuota 600 pendaki per hari.', 'turun' => false], $terbatas);
        $this->assertTrue($rusak['turun']);
        $this->assertSame('Dicek 6 Okt 2026 08.30 WIB (14 hari lalu), Balai Besar TNGGP. Alasan: Pemulihan ekosistem. Berlaku sampai 31 Okt 2026 WIB.', $tertutupBerlaku['kalimat']);
        $this->assertSame('Dicek 6 Okt 2026 08.30 WIB (24 hari lalu), Balai Besar TNGGP. Alasan: Pemulihan ekosistem. Berlaku sampai 31 Okt 2026 WIB. Masa berlaku itu sudah lewat; pastikan di pos registrasi.', $tepatBatas['kalimat']);
    }

    /**
     * Ruling W6-8b di perangkat: Buka musiman menyebut masa berlakunya sebelum kalimat R-012, dan
     * turun tepat pada expires_at yang dikirim server sebagai turunSejak.
     */
    public function test_the_device_drops_a_seasonal_opening_at_its_expiry(): void
    {
        $musiman = ['nilai' => 'OPEN', 'label' => 'Buka', 'sumber' => 'Balai Besar TNGGP', 'jenisSumber' => 'OFFICIAL', 'alasan' => null, 'dicek' => '2026-10-06T01:30:00+00:00', 'dicekTeks' => '6 Okt 2026 08.30 WIB', 'berlaku' => '6 Okt 2026 17.00 WIB', 'berlakuIso' => '2026-10-06T10:00:00+00:00', 'turunSejak' => '2026-10-06T10:00:00+00:00', 'pembatasan' => [], 'areaTerbatas' => []];

        [$sebelum, $tepat] = $this->node('kalimatStatusLuring', [
            [$musiman, Carbon::parse('2026-10-06 09:59:00', 'UTC')->getTimestampMs()],
            [$musiman, Carbon::parse('2026-10-06 10:00:00', 'UTC')->getTimestampMs()],
        ]);

        $this->assertSame(['judul' => 'Terakhir diketahui: Buka', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB, Balai Besar TNGGP. Berlaku sampai 6 Okt 2026 17.00 WIB. '.self::R012, 'turun' => false], $sebelum);
        $this->assertSame(['judul' => 'Status belum dapat dipastikan', 'kalimat' => 'Status belum dapat dipastikan sejak 6 Okt 2026 08.30 WIB. Pastikan di pos registrasi.', 'turun' => true], $tepat);
    }

    /**
     * Ruling W6-8c: kalimat pembatasan segmen dan area terbatas untuk shell luring Tugas 9, di
     * setiap cabang (dengan dan tanpa tanggal cek, dengan dan tanpa alasan, alasan berakhir titik).
     * Kalimat area diubah ruling W6-8m: area dapat bertaut ke gunung saja, jadi kalimatnya tidak
     * menyebut "di sekitar jalur".
     */
    public function test_segment_and_area_sentences_name_their_check_and_reason(): void
    {
        $segmen = ['segmenId' => 7, 'segmen' => 'Ranu Kumbolo sampai Puncak', 'nilai' => 'CLOSED', 'label' => 'Tutup', 'alasan' => 'Aktivitas vulkanik.', 'dicekTeks' => '6 Okt 2026 08.30 WIB'];

        $this->assertSame([
            'Segmen Ranu Kumbolo sampai Puncak: Tutup, dicek 6 Okt 2026 08.30 WIB. Alasan: Aktivitas vulkanik.',
            'Segmen Ranu Kumbolo sampai Puncak: Dibatasi, tanggal cek tidak tercatat.',
        ], $this->node('kalimatPembatasan', [[$segmen], [[...$segmen, 'nilai' => 'RESTRICTED', 'label' => 'Dibatasi', 'alasan' => null, 'dicekTeks' => null]]]));

        $this->assertSame([
            'Area terbatas tercatat untuk jalur atau gunung ini: Zona rawan longsor. Alasan: Aktivitas vulkanik.',
            'Area terbatas tercatat untuk jalur atau gunung ini: Zona inti.',
        ], $this->node('kalimatAreaTerbatas', [[['id' => 1, 'nama' => 'Zona rawan longsor', 'alasan' => 'Aktivitas vulkanik']], [['id' => 2, 'nama' => 'Zona inti', 'alasan' => null]]]));
    }

    /**
     * Ruling W6-8c (pra-tinjau PT8-3): jalur Buka dengan segmen Tutup tetap Buka, dan pembatasan
     * segmen serta area terbatas ikut paket. Fixture disusun berlawanan: segmen ber-id kecil
     * berada di depan menurut id dan nama tetapi di belakang menurut urutan rute, dan area ber-id
     * kecil bernama di belakang. Catatan segmen terbaru yang berlaku menang atas catatan Buka
     * yang lebih tua; segmen Buka, area yang kedaluwarsa, dan area gunung lain tidak ikut.
     *
     * Harapan urutan pembatasan diubah ruling W6-8h: urutan rute (sequence) dengan pemecah seri
     * id, sama dengan peringatan daring, jadi Tanjakan Setan (sequence 1) sebelum Puncak. Area
     * tetap urut id.
     */
    public function test_segment_closures_and_restricted_areas_travel_with_an_open_status(): void
    {
        [$user, $trip] = $this->rencana();
        $jalur = $trip->trail;
        $jalur->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $puncak = TrailSegment::factory()->for($jalur)->create(['sequence' => 2, 'name' => 'Puncak']);
        $tanjakan = TrailSegment::factory()->for($jalur)->create(['sequence' => 1, 'name' => 'Tanjakan Setan']);
        $airPanas = TrailSegment::factory()->for($jalur)->create(['sequence' => 3, 'name' => 'Air Panas']);

        foreach ([
            [$puncak, OfficialStatusValue::OPEN, null, '2026-10-01 02:00:00', now()->subDays(5)],
            [$puncak, OfficialStatusValue::CLOSED, 'Aktivitas vulkanik', '2026-10-05 02:00:00', now()->subDay()],
            [$tanjakan, OfficialStatusValue::RESTRICTED, null, null, now()->subDay()],
            [$airPanas, OfficialStatusValue::OPEN, null, '2026-10-05 02:00:00', now()->subDay()],
        ] as [$segmen, $nilai, $alasan, $dicek, $berlaku]) {
            OfficialStatus::factory()->create([
                'statusable_type' => $segmen->getMorphClass(),
                'statusable_id' => $segmen->id,
                'scope' => StatusScope::SEGMENT->value,
                'status' => $nilai->value,
                'reason' => $alasan,
                'verified_at' => $dicek === null ? null : Carbon::parse($dicek, 'UTC'),
                'effective_at' => $berlaku,
            ]);
        }

        $zonaInti = RestrictedArea::create(['mountain_id' => $jalur->mountain_id, 'name' => 'Zona inti', 'effective_at' => now()->subDay()]);
        $longsor = RestrictedArea::create(['trail_id' => $jalur->id, 'name' => 'Area longsor', 'reason' => 'Rawan longsor', 'effective_at' => now()->subDay()]);
        RestrictedArea::create(['trail_id' => $jalur->id, 'name' => 'Area lama', 'effective_at' => now()->subMonth(), 'expires_at' => now()->subDay()]);
        RestrictedArea::create(['mountain_id' => Mountain::factory()->create()->id, 'name' => 'Area gunung lain', 'effective_at' => now()->subDay()]);

        $status = $this->dinamis($user, $trip)['status'];

        $this->assertSame('OPEN', $status['nilai']);
        $this->assertSame([
            ['segmenId' => $tanjakan->id, 'segmen' => 'Tanjakan Setan', 'nilai' => 'RESTRICTED', 'label' => 'Dibatasi', 'alasan' => null, 'dicekTeks' => null],
            ['segmenId' => $puncak->id, 'segmen' => 'Puncak', 'nilai' => 'CLOSED', 'label' => 'Tutup', 'alasan' => 'Aktivitas vulkanik', 'dicekTeks' => '5 Okt 2026 09.00 WIB'],
        ], $status['pembatasan']);
        $this->assertSame([
            ['id' => $zonaInti->id, 'nama' => 'Zona inti', 'alasan' => null],
            ['id' => $longsor->id, 'nama' => 'Area longsor', 'alasan' => 'Rawan longsor'],
        ], $status['areaTerbatas']);
    }

    /**
     * Cuaca yang belum terbit disebut begitu, dan cuaca yang sudah dalam jangkauan terbit tetapi
     * belum ada di Memount disebut belum tersedia (R-013).
     *
     * Dua asersi brief diubah ruling W6-8a: tanggal 8 Okt sudah dalam jangkauan terbit BMKG pada
     * 7 Okt (terbit 5 Okt), jadi kalimatnya "belum tersedia di Memount", bukan "belum terbit";
     * dan baris kini dikelompokkan per tanggal trip (`hari`) dengan jam berpenanda zona dan suhu
     * berspasi tak terputus (pra-tinjau PT8-11, butir 7).
     */
    public function test_weather_that_is_not_yet_published_is_named_as_such(): void
    {
        [$user, $trip] = $this->rencana();

        $this->assertSame(['kalimat' => 'Data cuaca BMKG tidak tersedia saat paket dibuat.', 'hari' => []], $this->dinamis($user, $trip)['cuaca']);

        $this->prakiraan('2026-10-07 06:00:00', 'Berawan');

        $this->assertSame('Prakiraan BMKG untuk 8 Okt belum tersedia di Memount saat paket dibuat.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        $this->prakiraan('2026-10-08 00:00:00', 'Hujan ringan');

        $cuaca = $this->dinamis($user, $trip)['cuaca'];
        $this->assertSame('Prakiraan area Cimacan (BMKG), analisis 7 Okt 2026 07.00 WIB.', $cuaca['kalimat']);
        $this->assertSame([['tanggal' => '2026-10-08', 'label' => 'Kam, 8 Okt', 'kalimat' => null, 'baris' => [['waktu' => '07.00 WIB', 'cuaca' => 'Hujan ringan', 'suhu' => "17\u{00A0}°C"]]]], $cuaca['hari']);
    }

    /**
     * Ruling W6-8a: tanggal di luar jangkauan terbit BMKG (tanggal terbit sesudah hari ini di
     * zona gunung) disebut belum terbit dengan tanggal terbitnya; tanggal berurutan yang belum
     * terbit dilebur satu entri; tanggal dalam jangkauan tanpa baris disebut belum tersedia.
     */
    public function test_dates_beyond_the_forecast_horizon_are_not_yet_published(): void
    {
        [$user, $trip] = $this->rencana('2026-10-17');
        $this->prakiraan('2026-10-07 06:00:00', 'Berawan');

        $sabtu = [['tanggal' => '2026-10-17', 'label' => 'Sab, 17 Okt', 'kalimat' => 'Prakiraan BMKG untuk 17 Okt belum terbit saat paket dibuat. BMKG menerbitkan 3 hari ke depan, mulai 14 Okt.', 'baris' => []]];
        $this->assertSame(['kalimat' => $sabtu[0]['kalimat'], 'hari' => $sabtu], $this->dinamis($user, $trip)['cuaca']);

        $trip->update(['end_date' => '2026-10-19']);
        $this->assertSame([['tanggal' => '2026-10-17', 'label' => 'Sab, 17 Okt sampai Sen, 19 Okt', 'kalimat' => 'Prakiraan BMKG untuk 17 Okt sampai 19 Okt belum terbit saat paket dibuat. BMKG menerbitkan 3 hari ke depan.', 'baris' => []]], $this->dinamis($user, $trip)['cuaca']['hari']);

        // 9 dan 10 Okt sudah dalam jangkauan (terbit 6 dan 7 Okt); 11 dan 12 Okt belum.
        $trip->update(['planned_date' => '2026-10-09', 'end_date' => '2026-10-12']);
        $this->prakiraan('2026-10-09 03:00:00', 'Cerah');
        $cuaca = $this->dinamis($user, $trip)['cuaca'];

        $this->assertSame('Prakiraan area Cimacan (BMKG), analisis 7 Okt 2026 07.00 WIB.', $cuaca['kalimat']);
        $this->assertSame(['2026-10-09', '2026-10-10', '2026-10-11'], array_column($cuaca['hari'], 'tanggal'));
        $this->assertSame([
            null,
            'Prakiraan BMKG untuk 10 Okt belum tersedia di Memount saat paket dibuat.',
            'Prakiraan BMKG untuk 11 Okt sampai 12 Okt belum terbit saat paket dibuat. BMKG menerbitkan 3 hari ke depan.',
        ], array_column($cuaca['hari'], 'kalimat'));
    }

    /**
     * Ruling W6-8a: trip yang sedang berjalan membawa cuaca mulai hari ini sampai tanggal
     * selesainya, bukan tanggal berangkat yang sudah lewat. Baris dikelompokkan menurut tanggal
     * lokal gunung: 7 Okt 18.00 UTC adalah 8 Okt 01.00 WIB, dan 8 Okt 18.00 UTC sudah 9 Okt.
     */
    public function test_a_trip_in_progress_carries_the_weather_from_today_to_its_last_day(): void
    {
        [$user, $trip] = $this->rencana('2026-10-06', '2026-10-08', TripStatus::IN_PROGRESS);

        foreach (['2026-10-06 03:00:00' => 'Kemarin', '2026-10-07 03:00:00' => 'Hari ini', '2026-10-07 18:00:00' => 'Dini hari besok', '2026-10-08 18:00:00' => 'Lusa dini hari'] as $waktu => $cuaca) {
            $this->prakiraan($waktu, $cuaca);
        }

        $hari = $this->dinamis($user, $trip)['cuaca']['hari'];

        $this->assertSame(['2026-10-07', '2026-10-08'], array_column($hari, 'tanggal'));
        $this->assertSame(['Rab, 7 Okt', 'Kam, 8 Okt'], array_column($hari, 'label'));
        $this->assertSame([[['waktu' => '10.00 WIB', 'cuaca' => 'Hari ini', 'suhu' => "17\u{00A0}°C"]], [['waktu' => '01.00 WIB', 'cuaca' => 'Dini hari besok', 'suhu' => "17\u{00A0}°C"]]], array_column($hari, 'baris'));
    }

    /**
     * Gunung WITA: tanggal dan jam dalam zona gunung dengan penandanya (pra-tinjau PT8-11), dan
     * waktu analisis adalah analisis tertua di antara baris yang dibawa, bukan baris pertama
     * (PT8-9).
     */
    public function test_a_wita_mountain_groups_and_labels_by_its_own_zone(): void
    {
        [$user, $trip] = $this->rencana(gunung: Mountain::factory()->create(['timezone' => 'Asia/Makassar']));

        $this->prakiraan('2026-10-07 15:59:00', 'Masih 7 Okt', '2026-10-07 00:00:00');
        $this->prakiraan('2026-10-07 16:00:00', 'Tengah malam', '2026-10-07 12:00:00');
        $this->prakiraan('2026-10-08 15:00:00', 'Malam', '2026-10-06 18:00:00');

        $cuaca = $this->dinamis($user, $trip)['cuaca'];

        $this->assertSame('Prakiraan area Cimacan (BMKG), analisis 7 Okt 2026 02.00 WITA.', $cuaca['kalimat']);
        $this->assertSame(['00.00 WITA', '23.00 WITA'], array_column($cuaca['hari'][0]['baris'], 'waktu'));
        $this->assertSame('Kam, 8 Okt', $cuaca['hari'][0]['label']);
    }

    /**
     * Waktu pengetahuan prakiraan di setiap cabang (pra-tinjau PT8-9, butir 2): tanpa tanggal
     * analisis, kalimat menyebut kapan Memount mengambilnya; data basi menyebut kapan terakhir
     * diperbarui; area referensi yang tidak tercatat disebut begitu.
     */
    public function test_the_weather_sentence_names_what_is_known_about_its_age(): void
    {
        [$user, $trip] = $this->rencana();
        $baris = $this->prakiraan('2026-10-08 00:00:00', 'Hujan ringan', null, '2026-10-07 02:00:00');

        $this->assertSame('Prakiraan area Cimacan (BMKG), diambil Memount 7 Okt 2026 09.00 WIB.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        $baris->update(['analysis_date' => Carbon::parse('2026-10-05 12:00:00', 'UTC'), 'fetched_at' => Carbon::parse('2026-10-05 20:00:00', 'UTC')]);

        $this->assertSame('Prakiraan area Cimacan (BMKG), analisis 5 Okt 2026 19.00 WIB. Data cuaca terakhir diperbarui 6 Okt 2026 03.00 WIB.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        $baris->update(['reference_area' => null, 'fetched_at' => now()]);
        $trip->trail->forceFill(['weather_reference_area' => null])->saveQuietly();

        $this->assertSame('Prakiraan BMKG (area referensi tidak tercatat), analisis 5 Okt 2026 19.00 WIB.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        // Isian baris yang tidak dicatat BMKG ditulis begitu, bukan dikosongkan (R-007; tinjauan m-3).
        $baris->update(['weather_description' => null, 'temperature_c' => null]);

        $this->assertSame([['waktu' => '07.00 WIB', 'cuaca' => 'Tidak dicatat', 'suhu' => 'Tidak dicatat']], $this->dinamis($user, $trip)['cuaca']['hari'][0]['baris']);
    }

    /**
     * Laporan komunitas: yang lolos moderasi saja, paling banyak sepuluh dalam tiga puluh hari,
     * bertanggal mendaki (bukan "kemarin" yang basi esok hari), tanpa nama pelapor. Satu laporan
     * bersegmen dan bertag diperiksa utuh (tinjauan m-3).
     */
    public function test_community_reports_are_dated_and_bounded(): void
    {
        [$user, $trip] = $this->rencana();
        TrailConditionReport::factory()->count(12)->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-05']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-08-01']);
        TrailConditionReport::factory()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-06']);

        $laporan = $this->dinamis($user, $trip)['laporan'];

        $this->assertCount(10, $laporan);
        $this->assertSame('mendaki 5 Okt', $laporan[0]['waktu']);
        $this->assertSame(['segmen', 'kondisi', 'catatan', 'waktu'], array_keys($laporan[0]));

        $segmen = TrailSegment::factory()->for($trip->trail)->create(['sequence' => 1, 'name' => 'Kandang Badak']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'trail_segment_id' => $segmen->id, 'hike_date' => '2026-10-06', 'condition_tags' => [ConditionTag::MUDDY->value, ConditionTag::SLIPPERY->value], 'note' => 'Licin sesudah hujan.']);

        $this->assertSame(['segmen' => $segmen->id, 'kondisi' => 'Berlumpur, Licin', 'catatan' => 'Licin sesudah hujan.', 'waktu' => 'mendaki 6 Okt'], $this->dinamis($user, $trip)['laporan'][0]);
    }

    /**
     * Pra-tinjau PT8-6: terbaru dulu menurut tanggal mendaki, dengan id yang tidak searah tanggal,
     * dan jendela tiga puluh hari inklusif dari tanggal terbaru di Indonesia (WIT): 7 Sep masuk,
     * 6 Sep tidak. Dua laporan bertanggal sama diputus id menurun (butir 3); laporan yang dibuat
     * terakhir dibuat paling akhir, supaya rowid naik SQLite tidak kebetulan memberi urutan itu.
     */
    public function test_community_reports_are_newest_first_within_the_window(): void
    {
        [$user, $trip] = $this->rencana();

        foreach (['2026-10-01', '2026-10-06', '2026-09-20', '2026-09-07', '2026-09-06', '2026-10-06'] as $i => $tanggal) {
            TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => $tanggal, 'note' => 'Laporan '.$i]);
        }

        $laporan = $this->dinamis($user, $trip)['laporan'];

        $this->assertSame(['mendaki 6 Okt', 'mendaki 6 Okt', 'mendaki 1 Okt', 'mendaki 20 Sep', 'mendaki 7 Sep'], array_column($laporan, 'waktu'));
        $this->assertSame(['Laporan 5', 'Laporan 1', 'Laporan 0', 'Laporan 2', 'Laporan 3'], array_column($laporan, 'catatan'));
    }

    /**
     * Pra-tinjau PT8-12 bagian c: batas laporan satu sumber (hiking.conditions.community_report_limit)
     * untuk paket dan mode lapangan daring.
     */
    public function test_the_report_limit_has_one_source_for_the_pack_and_the_field_mode(): void
    {
        config(['hiking.conditions.community_report_limit' => 3]);
        [$user, $trip] = $this->rencana();
        TrailConditionReport::factory()->count(5)->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-05']);

        $this->assertCount(3, $this->dinamis($user, $trip)['laporan']);
        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->has('laporan', 3));
    }

    /**
     * Memanggil satu fungsi resources/js/luring/kesegaran.ts di Node untuk setiap kasus dan
     * mengembalikan hasilnya berurutan.
     *
     * @param  array<int, array<int, mixed>>  $kasus  argumen setiap panggilan
     * @return array<int, mixed>
     */
    private function node(string $fungsi, array $kasus): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/luring/kesegaran.ts')).').href);'
            .'const kasus = '.json_encode($kasus).';'
            .'process.stdout.write(JSON.stringify(kasus.map((argumen) => modul['.json_encode($fungsi).'](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan kesegaran.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
