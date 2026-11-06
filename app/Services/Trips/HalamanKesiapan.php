<?php

namespace App\Services\Trips;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\ConditionAggregatorService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Readiness\ReadinessAssessment;
use App\Services\TrailFitService;
use App\Services\Trails\StatusJalurRingkas;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

/**
 * Kelas halaman Cek kesiapan (spec v3 bagian 6.11, prototype 10, rencana induk W4 butir 4).
 *
 * Keadaan DK-48 beserta kalimatnya, lalu tiga hal yang diperiksa: kecocokan untuk rencana,
 * persiapan (hitungan butir kritis dan butir yang kurang dengan aksinya), dan kondisi terkini
 * (status resmi dengan sumber dan kesegarannya, prakiraan untuk tanggal trip, laporan komunitas
 * terbaru). "Sejak cek terakhir" membandingkan penilaian sekarang dengan snapshot
 * readiness_checks terakhir yang disimpan pendaki.
 *
 * Penilaiannya dihitung ReadinessService::compute() setiap halaman dibuka dan tidak ditulis
 * (ReadinessDashboardTest). Kesiapan adalah dukungan keputusan, bukan izin medis, izin hukum,
 * atau jaminan keselamatan (R-014); tidak ada skor atau persen (R-005, BR-09).
 */
final class HalamanKesiapan
{
    public function __construct(
        private readonly KepalaTrip $kepala,
        private readonly TrailFitService $trailFit,
        private readonly PermitService $permits,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly ConditionAggregatorService $kondisi,
    ) {}

    /**
     * Membutuhkan relasi trail.mountain dan preparationItems sudah dimuat.
     *
     * @return array<string, mixed>
     */
    public function props(TripPlan $trip, User $pembaca, ReadinessAssessment $penilaian, ?ReadinessCheck $terakhir): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $kritis = PreparationService::kritis($trip->preparationItems);
        $jalurTerbit = $trip->trail->terbit();
        $kecocokan = $this->kecocokan($trip, $pembaca, $jalurTerbit);
        $prakiraan = $this->prakiraan($trip, $penilaian->conditionSnapshot['weather'] ?? [], $zona);

        return [
            'kepala' => $this->kepala->untuk($trip),
            'tautanLangkah' => array_filter([
                'jalur' => $jalurTerbit ? route('trails.show', $trip->trail) : null,
                'rencana' => route('trips.show', $trip),
                'persiapan' => route('trips.preparation', $trip),
            ]),
            'keadaan' => [
                'nilai' => $penilaian->state->value,
                'label' => $penilaian->state->label(),
                'kalimat' => $this->kalimat($penilaian),
                // Semua alasan mesin untuk keadaan selain lengkap, supaya alasan yang paling
                // menentukan (misalnya jalur ditarik dari katalog) tidak tertutup alasan lain.
                'alasan' => $penilaian->state === ReadinessState::READY ? [] : array_values(array_unique($penilaian->explanation['reasons'] ?? [])),
                // §93: jam dibaca dalam zona gunungnya, lengkap penandanya, dengan satu format
                // jam untuk seluruh halaman (M3 tinjauan akhir W4).
                'dihitung' => Timezone::display(now(), $zona, 'j M Y H.i'),
            ],
            'kecocokan' => $kecocokan,
            'persiapan' => $this->persiapan($trip, $kritis),
            'kondisi' => [
                'status' => $this->statusJalur->untuk(EloquentCollection::make([$trip->trail]))[$trip->trail->id],
                'prakiraan' => $prakiraan,
                'laporan' => $this->laporan($penilaian->conditionSnapshot['community'] ?? []),
                'peringatan' => $this->peringatan($penilaian, $prakiraan),
            ],
            'sejakTerakhir' => $terakhir === null ? null : $this->sejak($penilaian, $terakhir, $zona, $kecocokan),
            'hariKeberangkatan' => 'Pastikan status langsung di pos registrasi'.($trip->trail->starting_point ? ' '.$trip->trail->starting_point : '')
                .'. Memount tidak menampilkan status dari simpanan lama, karena status "Buka" yang basi bisa menyesatkan.',
            // W4-4d: "Selesaikan N butir" dan "Simpan hasil cek ini" hanya untuk rencana yang
            // masih di depan; trip batal, selesai, berlangsung, atau yang tanggalnya lewat tidak
            // ditawari aksi persiapan. Predikat yang sama menolak recompute di server.
            'tindakLanjut' => $trip->masihDirencanakan(),
        ];
    }

    /**
     * Satu kalimat di bawah label keadaan. Untuk NEEDS_PREPARATION menyebut SEMUA sebab yang
     * berlaku (W4-6a, putaran perbaikan 2), dihitung ReadinessService::needsPreparationCauses()
     * dari masukan yang sama dengan determineState() -- satu sumber, tanpa salinan logika
     * cabang di sini dan tanpa membaca status bercache (n-2). READY dan NOT_RECOMMENDED tetap
     * ringkasan tetap karena alasannya sudah tertulis lengkap di `keadaan.alasan`.
     */
    private function kalimat(ReadinessAssessment $penilaian): string
    {
        return match ($penilaian->state) {
            ReadinessState::READY => 'Butir kritis sudah dikonfirmasi, dan kondisi terkini tidak menunjukkan penutupan atau peringatan resmi.',
            ReadinessState::NOT_RECOMMENDED => 'Rencana ini berbenturan dengan syarat dasar atau status resmi jalurnya.',
            ReadinessState::NEEDS_PREPARATION => $this->sebabGabungan($penilaian),
        };
    }

    /**
     * Semua sebab NEEDS_PREPARATION digabung satu kalimat per sebab (W4-6a): pendaki yang
     * menyelesaikan sebab pertama tidak boleh menemukan sebab kedua yang sudah ada sejak awal
     * tetapi tidak pernah disebut (probe R4-A/B).
     */
    private function sebabGabungan(ReadinessAssessment $penilaian): string
    {
        $sebab = $penilaian->explanation['preparation_causes'] ?? [];

        return $sebab === [] ? 'Masih ada yang perlu diperiksa sebelum berangkat.' : implode(' ', $sebab);
    }

    /**
     * Kecocokan untuk rencana ini: jenis dan tanggal trip di atas salinan tujuannya
     * (TripPlan::tujuanPenilaian(), ruling W4-FA), sama dengan ruang kerja trip dan halaman
     * penilaian. Pendaki tanpa profil lengkap tidak diberi label (§91, I4, W4-6d): kartunya
     * memotong ke "Belum dinilai" dengan tautan ke onboarding. Syarat dasar tidak memakai data
     * pengguna, jadi tetap dihitung untuknya: rencana yang gagal disebut "Tidak memenuhi syarat
     * dasar" beserta sebabnya, sejalan dengan keadaan "tidak disarankan" di atasnya (M2 tinjauan
     * akhir W4). `profilLengkap` dibawa supaya sejak() memakai gerbang yang sama.
     *
     * @return array{label: string|null, labelText: string, alasan: string, denganRencana: bool, layak: bool, tautan: string|null, tautanOnboarding: string|null, profilLengkap: bool}
     */
    private function kecocokan(TripPlan $trip, User $pembaca, bool $jalurTerbit): array
    {
        $tautan = $jalurTerbit ? route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]) : null;
        $fit = $this->trailFit->forTrails($pembaca, EloquentCollection::make([$trip->trail]), $trip->tujuanPenilaian())[$trip->trail->id];

        if (! $pembaca->hasCompletedProfile()) {
            return [
                'label' => null,
                'labelText' => RouteFitLabel::textFor(null),
                'alasan' => $fit->eligible ? 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.' : $fit->alasan,
                'denganRencana' => false,
                'layak' => $fit->eligible,
                'tautan' => null,
                'tautanOnboarding' => route('onboarding'),
                'profilLengkap' => false,
            ];
        }

        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'alasan' => $fit->alasan,
            'denganRencana' => $fit->denganRencana,
            'layak' => $fit->eligible,
            'tautan' => $tautan,
            'tautanOnboarding' => null,
            'profilLengkap' => true,
        ];
    }

    /**
     * Hitungan persiapan dan butir kritis yang belum dikonfirmasi, masing-masing dengan aksinya:
     * butir izin menautkan sistem resmi bila jendelanya terbuka (atau tanggal bukanya belum
     * tercatat), selain itu menulis keadaan jendelanya (M1 tinjauan akhir W4); butir cuaca
     * menunggu BMKG; butir lain dibuka di daftar persiapan pada barisnya sendiri. Rencana yang
     * tidak lagi di depan (batal, selesai, berlangsung, tanggal lewat) tidak ditawari aksi
     * persiapan (W4-4d).
     *
     * @param  array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}  $kritis
     * @return array<string, mixed>
     */
    private function persiapan(TripPlan $trip, array $kritis): array
    {
        $hitungan = TripSteps::hitungPersiapan($trip->preparationItems);
        $terbit = PreparationService::terbitPrakiraan($trip);
        $tindakLanjut = $trip->masihDirencanakan();
        $belum = $trip->preparationItems
            ->where('is_critical', true)
            ->where('status', PreparationStatus::NOT_CONFIRMED)
            ->sortBy('id')
            ->values();
        $aturan = $belum->contains(fn (TripPreparationItem $item) => PreparationService::butirIzin($item))
            ? $this->permits->requirementFor($trip->trail)
            : null;
        $pemesanan = $aturan !== null ? $this->permits->pemesanan($trip->trail, $trip->planned_date, $aturan, $tindakLanjut) : null;

        $butir = $belum->map(function (TripPreparationItem $item) use ($trip, $terbit, $pemesanan, $tindakLanjut) {
            $izin = PreparationService::butirIzin($item);
            $menunggu = PreparationService::menungguBmkg($item, $terbit);
            $tautanIzin = $izin ? ($pemesanan['tautan'] ?? null) : null;

            return [
                'id' => $item->id,
                'label' => $item->label,
                'menunggu' => $menunggu ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M') : null,
                // Butir izin tanpa ajakan memesan menyebut keadaan jendelanya, bukan "Pesan".
                'keterangan' => $izin && $pemesanan !== null && $tautanIzin === null ? $pemesanan['keterangan'] : null,
                'aksi' => match (true) {
                    ! $tindakLanjut, $menunggu => null,
                    $tautanIzin !== null => ['label' => $tautanIzin['label'], 'href' => $tautanIzin['href'], 'luar' => true],
                    default => ['label' => 'Konfirmasi', 'href' => route('trips.preparation', $trip).'#butir-'.$item->id, 'luar' => false],
                },
            ];
        })->values();

        // m1: hitungan "Selesaikan" hanya butir yang benar-benar dapat diselesaikan sekarang.
        // Butir yang masih menunggu BMKG tidak boleh ikut, supaya tombolnya tidak membawa
        // pendaki ke daftar persiapan yang justru menolak konfirmasinya lebih awal (P9).
        $siapDiselesaikan = $butir->filter(fn (array $b) => $b['aksi'] !== null)->count();
        // Dari predikat menunggu, bukan dari aksi yang kosong: rencana yang tidak lagi di depan
        // tidak ditawari aksi apa pun, padahal butir bukan cuacanya tidak sedang menunggu BMKG.
        // Butir cuacanya pun tidak menunggu, karena terbitPrakiraan() null untuk rencana seperti
        // itu (W4-FK N3).
        $kritisMenunggu = $butir->filter(fn (array $b) => $b['menunggu'] !== null)->count();

        // MIN-4: butir cuaca NON-KRITIS yang menunggu BMKG tidak masuk daftar $butir (yang hanya
        // kritis), jadi dihitung terpisah supaya kalimatnya dapat menyebut tanggal terbit.
        $nonKritisBelum = $trip->preparationItems
            ->where('is_critical', false)
            ->where('status', PreparationStatus::NOT_CONFIRMED);
        $nonKritisMenunggu = $nonKritisBelum->filter(fn (TripPreparationItem $item) => PreparationService::menungguBmkg($item, $terbit))->count();

        // m-2: "Lanjutkan persiapan" hanya berarti bila ada butir NON-KRITIS yang benar-benar
        // dapat dikonfirmasi sekarang; butir yang menunggu BMKG akan ditolak server lebih awal.
        $nonKritisBisaDikonfirmasi = $nonKritisBelum->contains(fn (TripPreparationItem $item) => ! PreparationService::menungguBmkg($item, $terbit));

        // MIN-1: trip tanpa satu pun butir persiapan; aksinya menuju halaman Persiapan, yang
        // membangkitkan daftar begitu dibuka kosong.
        $daftarBelumAda = $trip->preparationItems->isEmpty();

        // W4-6a (probe R4-D): bila seluruh butir ditandai Tidak berlaku, kasus ini perlu aksinya
        // sendiri (tinjau ulang daftar), bukan diam tanpa tautan apa pun.
        $semuaTidakBerlaku = ! $daftarBelumAda && $hitungan['berlaku'] === 0;

        return [
            'dikonfirmasi' => $kritis['dikonfirmasi'],
            'berlaku' => $kritis['berlaku'],
            // Kalimat yang sama dengan ruang kerja dan Persiapan, termasuk daftar kosong dan
            // daftar yang seluruhnya Tidak berlaku (MIN-2; tinjauan Tugas 4 M4).
            'teks' => TripSteps::teksPersiapan($hitungan),
            'tautan' => route('trips.preparation', $trip),
            'butir' => $butir->all(),
            'siapDiselesaikan' => $siapDiselesaikan,
            'semuaMenungguBmkg' => $butir->isNotEmpty() && $kritisMenunggu === $butir->count()
                ? $kritisMenunggu.' butir kritis menunggu BMKG (terbit '.$terbit->translatedFormat('D, j M').').'
                : null,
            // MIN-4: butir NON-KRITIS yang menunggu BMKG, supaya tidak hilang dari kartu.
            'menungguLain' => $nonKritisMenunggu > 0
                ? $nonKritisMenunggu.' butir lain menunggu BMKG (terbit '.$terbit->translatedFormat('D, j M').').'
                : null,
            'daftarBelumAda' => $daftarBelumAda,
            'semuaTidakBerlaku' => $semuaTidakBerlaku,
            // I1, diperluas MIN-4: butir kritis sudah habis ATAU seluruhnya menunggu BMKG, dan
            // daftar penuh belum: aksinya menuju sisa butir non-kritis yang dapat dikerjakan.
            'lanjutkan' => $tindakLanjut && $siapDiselesaikan === 0 && ! $semuaTidakBerlaku && $nonKritisBisaDikonfirmasi,
        ];
    }

    /**
     * Prakiraan untuk tanggal trip (R-013): sebelum jendela terbit BMKG hanya tanggal terbitnya;
     * sesudahnya baris untuk tanggal itu saja, dengan sumber, area referensi, dan kesegaran.
     * Kegagalan mengambil data disebut, tidak diganti cuaca karangan.
     *
     * @param  array<string, mixed>  $cuaca  konteks WeatherService::contextForTrail()
     * @return array<string, mixed>
     */
    private function prakiraan(TripPlan $trip, array $cuaca, string $zona): array
    {
        $tanggal = $trip->planned_date->toDateString();
        $kesegaran = FreshnessState::tryFrom((string) ($cuaca['freshness'] ?? ''));
        $dasar = [
            'judul' => 'Prakiraan untuk '.$trip->planned_date->translatedFormat('D, j M'),
            'sumber' => $cuaca['source'] ?? 'BMKG',
            'area' => $cuaca['reference_area'] ?? null,
            'diperbarui' => isset($cuaca['fetched_at']) ? Timezone::display(Carbon::parse($cuaca['fetched_at']), $zona, 'j M H.i') : null,
            'kesegaran' => $kesegaran !== null && $kesegaran !== FreshnessState::UNKNOWN ? $kesegaran->label() : null,
            // m3: baris tabel memformat jamnya dalam zona gunung ini; kolomnya perlu menyebut
            // penanda yang sama (WIB/WITA/WIT), supaya jam itu tidak terbaca seperti UTC.
            'zona' => Timezone::label($zona),
        ];

        // W4-FK N3: kartu ini menimbang status trip, bukan hanya tanggalnya. Trip yang batal atau
        // selesai tidak punya prakiraan untuk diperiksa, berapa pun tanggalnya.
        if (! $trip->status->isActive()) {
            return $dasar + ['keadaan' => 'tidakDiperiksa', 'pesan' => sprintf('Trip ini berstatus %s, jadi prakiraannya tidak diperiksa lagi.', $trip->status->label()), 'baris' => [], 'sebagian' => null];
        }

        $terbit = PreparationService::terbitPrakiraan($trip);

        if ($terbit !== null) {
            return $dasar + ['keadaan' => 'menunggu', 'pesan' => 'Menunggu BMKG. Terbit mulai '.$terbit->translatedFormat('D, j M').'; Memount tidak menebak cuaca sebelum itu.', 'baris' => [], 'sebagian' => null];
        }

        // W4-4d: tanggal yang sudah lewat tidak punya prakiraan untuk diperiksa. Tanpa cabang ini
        // halaman menulis "belum tersedia dari BMKG", yang terbaca seolah prakiraannya akan datang.
        // Pendakian multi-hari yang sedang berjalan bukan rencana yang lewat: kalimatnya netral,
        // tanpa prakiraan karangan untuk hari ini (R-013, W4-FK N3).
        if (Timezone::hariSampai($trip->planned_date, $zona) < 0) {
            return $trip->status === TripStatus::IN_PROGRESS
                ? $dasar + ['keadaan' => 'berjalan', 'pesan' => 'Pendakian ini sedang berjalan. Cek kesiapan menilai persiapan sebelum berangkat, jadi prakiraan untuk tanggal berangkat tidak ditampilkan lagi di sini.', 'baris' => [], 'sebagian' => null]
                : $dasar + ['keadaan' => 'lewat', 'pesan' => 'Tanggal trip ini sudah lewat, jadi tidak ada prakiraan yang perlu diperiksa.', 'baris' => [], 'sebagian' => null];
        }

        if (! ($cuaca['available'] ?? false)) {
            return $dasar + ['keadaan' => 'gagal', 'pesan' => $cuaca['message'] ?? 'Data cuaca tidak tersedia.', 'baris' => [], 'sebagian' => null];
        }

        // m5: satu kueri untuk SATU HARI tanggal trip dalam zona gunung, bukan dipotong dari
        // jendela 72 jam WeatherService::forecastForTrail(). Jendela itu menjawab pertanyaan
        // berbeda (ketersediaan dan kesegaran "sekarang"); baris tabel di sini menjawab cuaca
        // pada TANGGAL TRIP, dan pada hari BMKG baru terbit untuk tanggal itu jendela 72 jam
        // dari sekarang memotong baris sore/malamnya meskipun datanya sudah tersimpan (P12).
        $baris = WeatherSnapshot::query()
            ->where('adm4_code', $trip->trail->weather_adm4_code)
            ->whereBetween('forecast_at', [
                Carbon::parse($tanggal, $zona)->startOfDay()->utc(),
                Carbon::parse($tanggal, $zona)->endOfDay()->utc(),
            ])
            ->orderBy('forecast_at')
            ->get()
            ->map(fn (WeatherSnapshot $s) => [
                'waktu' => $s->forecast_at->copy()->setTimezone($zona)->format('H.i'),
                'cuaca' => $s->weather_description ?? 'Tidak dicatat',
                'suhu' => $s->temperature_c !== null ? number_format((float) $s->temperature_c, 1, ',', '.').' °C' : 'Tidak dicatat',
            ])
            ->values()
            ->all();

        if ($baris === []) {
            return $dasar + ['keadaan' => 'belumTersedia', 'pesan' => 'Prakiraan untuk tanggal ini belum tersedia dari BMKG.', 'baris' => [], 'sebagian' => null];
        }

        // m5 "sebagian" (putaran perbaikan 2, R8): sinyal yang berdasar adalah akhir data
        // prakiraan tersimpan untuk area ini jatuh pada tanggal trip SENDIRI, bukan pada
        // tanggal sesudahnya -- itu berarti BMKG belum menerbitkan lewat hari ini, jadi baris
        // yang tampil belum tentu seluruh hari. Tanpa ambang jumlah slot yang diasumsikan
        // (tidak ada "jumlah harapan" yang berdasar untuk menyatakan sebagian atau penuh).
        $akhirData = WeatherSnapshot::query()->where('adm4_code', $trip->trail->weather_adm4_code)->max('forecast_at');
        $akhirDataLokal = $akhirData !== null ? Carbon::parse($akhirData, 'UTC')->setTimezone($zona) : null;
        $sebagian = $akhirDataLokal !== null && $akhirDataLokal->toDateString() === $tanggal
            ? 'Prakiraan BMKG baru menjangkau sampai pukul '.$akhirDataLokal->format('H.i').' '.Timezone::label($zona).' pada tanggal ini.'
            : null;

        return $dasar + ['keadaan' => 'tersedia', 'pesan' => null, 'baris' => $baris, 'sebagian' => $sebagian];
    }

    /**
     * Peringatan kondisi, tanpa mengulang hal yang sudah dikatakan kartu prakiraan (m10, n-4).
     * route_warnings (tanda kondisi komunitas, pembatasan segmen, area terbatas) SELALU
     * tampil di sini. data_warnings (ketersediaan atau kesegaran data cuaca) hanya ditambah
     * ketika kartu prakiraan BELUM menyinggung ketersediaan data sama sekali (keadaan
     * 'menunggu' hanya bicara soal tanggal terbit, bukan ketersediaan); pada keadaan 'gagal'
     * kartu sudah mengatakan "Data cuaca tidak tersedia." sendiri, jadi tidak diulang.
     * Pemisahannya memakai kunci terstruktur ReadinessService::explain() (route_warnings vs
     * data_warnings), bukan mencocokkan awalan teks ke keluaran ConditionAggregatorService --
     * perubahan salinan di hulu tidak lagi diam-diam menggandakan peringatan di sini.
     *
     * @return array<int, string>
     */
    private function peringatan(ReadinessAssessment $penilaian, array $prakiraan): array
    {
        $peringatan = array_values($penilaian->explanation['route_warnings'] ?? []);

        if ($prakiraan['keadaan'] !== 'gagal') {
            $peringatan = array_merge($peringatan, array_values($penilaian->explanation['data_warnings'] ?? []));
        }

        return $peringatan;
    }

    /**
     * Laporan komunitas terbaru yang lolos moderasi, dengan kesegarannya dari tanggal
     * pendakian (§59). Komunitas tidak pernah menggantikan status resmi (R-010).
     *
     * @param  array<string, mixed>  $komunitas
     * @return array{ringkas: string, segmen: string|null, kapan: string, kesegaran: string}|null
     */
    private function laporan(array $komunitas): ?array
    {
        $terbaru = $komunitas['reports'][0] ?? null;

        if (! $terbaru instanceof TrailConditionReport) {
            return null;
        }

        $tanda = array_map(fn ($tag) => $tag->label(), $terbaru->tags());
        $hari = $terbaru->daysSinceHike();

        return [
            'ringkas' => $tanda === [] ? 'Tanpa tanda kondisi' : implode(', ', $tanda),
            'segmen' => $terbaru->segment?->name,
            'kapan' => match ($hari) {
                0 => 'pendakian hari ini',
                1 => 'pendakian kemarin',
                default => 'pendakian '.$hari.' hari lalu',
            },
            'kesegaran' => $this->kondisi->reportFreshness($terbaru)->label(),
        ];
    }

    /**
     * "Sejak cek terakhir" (prototype 10): status resmi, laporan komunitas yang masuk sesudah cek
     * itu, butir persiapan yang dikonfirmasi, dan label kecocokan. Setiap baris menyebut keadaannya,
     * juga ketika tidak ada yang berubah.
     *
     * @param  array<string, mixed>  $kecocokanSekarang  hasil kecocokan() untuk penilaian ini
     * @return array{tanggal: string|null, butir: array<int, array{jenis: string, teks: string}>}
     */
    private function sejak(ReadinessAssessment $kini, ReadinessCheck $lalu, string $zona, array $kecocokanSekarang): array
    {
        $statusLalu = OfficialStatusValue::tryFrom((string) ($lalu->official_status_snapshot['status'] ?? '')) ?? OfficialStatusValue::UNKNOWN;
        $statusKini = OfficialStatusValue::tryFrom((string) ($kini->officialStatusSnapshot['status'] ?? '')) ?? OfficialStatusValue::UNKNOWN;
        $laporanBaru = collect($kini->conditionSnapshot['community']['reports'] ?? [])
            ->filter(fn ($r) => $r instanceof TrailConditionReport && $lalu->computed_at !== null && $r->created_at?->gt($lalu->computed_at))
            ->count();
        $labelLalu = RouteFitLabel::textFor(RouteFitLabel::tryFrom((string) ($lalu->route_fit_snapshot['label'] ?? '')));
        $labelKini = RouteFitLabel::textFor(RouteFitLabel::tryFrom((string) ($kini->routeFitSnapshot['label'] ?? '')));

        return [
            'tanggal' => $lalu->computed_at?->copy()->setTimezone($zona)->translatedFormat('j M'),
            'butir' => [
                $statusLalu === $statusKini
                    ? ['jenis' => 'sama', 'teks' => 'Status resmi tidak berubah.']
                    : ['jenis' => 'berubah', 'teks' => 'Status resmi berubah dari '.$statusLalu->label().' menjadi '.$statusKini->label().'.'],
                $laporanBaru > 0
                    ? ['jenis' => 'bertambah', 'teks' => $laporanBaru.' laporan komunitas baru.']
                    : ['jenis' => 'sama', 'teks' => 'Tidak ada laporan komunitas baru.'],
                $this->sejakPersiapan($kini->preparationState, $lalu->preparation_state ?? []),
                // I4: gerbang yang sama dengan kartu kecocokan. Tanpa profil lengkap, label
                // compute() sudah null (bukan hasil yang pernah dilihat pendaki), jadi
                // membandingkannya apa adanya hanya akan berbunyi "tetap Belum dinilai" tanpa
                // menyebut sebabnya; pendaki perlu tahu itu karena profilnya, bukan karena
                // jalurnya berubah jadi tidak terukur.
                ! $kecocokanSekarang['profilLengkap']
                    ? ['jenis' => 'sama', 'teks' => 'Label kecocokan belum dinilai karena profil pendaki belum lengkap.']
                    : ($labelLalu === $labelKini
                        ? ['jenis' => 'sama', 'teks' => 'Label kecocokan tetap '.$labelKini.'.']
                        : ['jenis' => 'berubah', 'teks' => 'Label kecocokan berubah dari '.$labelLalu.' menjadi '.$labelKini.'.']),
            ],
        ];
    }

    /**
     * Baris persiapan "Sejak cek terakhir" (m2, diperbaiki sungguhan di putaran perbaikan 3
     * sesudah IMP-1: klaim putaran 2 tentang perbandingan `not_confirmed` tidak sesuai kode,
     * kodenya tidak pernah ditulis). Dibandingkan lewat hitungan `confirmed`, `not_applicable`,
     * DAN `not_confirmed`, serta himpunan `critical_outstanding`, bukan hanya selisih bersih
     * `confirmed`:
     * - R5-b: satu butir dikonfirmasi DAN satu lain ditandai Tidak berlaku pada permintaan
     *   yang sama menyebut KEDUANYA, bukan berhenti di not_applicable;
     * - butir yang berpindah KE Tidak berlaku tidak terbaca "kembali belum dikonfirmasi" (P5);
     * - dua butir kritis yang bertukar antara Belum dan Dikonfirmasi tidak terbaca "tidak
     *   berubah" semata-mata karena saling meniadakan di hitungan confirmed (P4). Pertukaran
     *   yang tidak menggeser hitungan mana pun maupun himpunan butir kritis yang belum (misalnya
     *   Dikonfirmasi dengan Tidak berlaku, juga pada butir kritis) tetap terbaca "tidak berubah":
     *   snapshot menyimpan hitungan, bukan status per butir (batas yang diterima ruling W4-6l,
     *   R1/R5-d);
     * - R5-a: butir NON-KRITIS yang berpindah dari Tidak berlaku ke Belum (not_applicable
     *   turun, not_confirmed naik, confirmed tetap) tidak terbaca "tidak berubah" -- himpunan
     *   critical_outstanding hanya memuat butir KRITIS, jadi perubahan butir non-kritis
     *   tidak pernah tertangkap olehnya; `not_confirmed` adalah jaring yang menutup celah itu.
     *
     * @param  array<string, mixed>  $kini
     * @param  array<string, mixed>  $lalu
     * @return array{jenis: string, teks: string}
     */
    private function sejakPersiapan(array $kini, array $lalu): array
    {
        $confirmedDelta = (int) ($kini['confirmed'] ?? 0) - (int) ($lalu['confirmed'] ?? 0);
        $notApplicableDelta = (int) ($kini['not_applicable'] ?? 0) - (int) ($lalu['not_applicable'] ?? 0);
        $notConfirmedDelta = (int) ($kini['not_confirmed'] ?? 0) - (int) ($lalu['not_confirmed'] ?? 0);
        $kritisLalu = collect($lalu['critical_outstanding'] ?? [])->sort()->values()->all();
        $kritisKini = collect($kini['critical_outstanding'] ?? [])->sort()->values()->all();

        if ($confirmedDelta > 0 && $notApplicableDelta > 0) {
            return ['jenis' => 'berubah', 'teks' => $confirmedDelta.' butir persiapan dikonfirmasi, '.$notApplicableDelta.' ditandai tidak berlaku.'];
        }

        if ($notApplicableDelta > 0) {
            return ['jenis' => 'berubah', 'teks' => $notApplicableDelta.' butir ditandai tidak berlaku.'];
        }

        if ($confirmedDelta > 0) {
            return ['jenis' => 'bertambah', 'teks' => $confirmedDelta.' butir persiapan dikonfirmasi.'];
        }

        if ($confirmedDelta < 0) {
            return ['jenis' => 'berubah', 'teks' => abs($confirmedDelta).' butir persiapan kembali belum dikonfirmasi.'];
        }

        if ($kritisLalu !== $kritisKini || $notConfirmedDelta !== 0) {
            $berubah = max(
                count(array_merge(array_diff($kritisLalu, $kritisKini), array_diff($kritisKini, $kritisLalu))),
                abs($notConfirmedDelta)
            );

            return ['jenis' => 'berubah', 'teks' => 'Status '.$berubah.' butir persiapan berubah.'];
        }

        return ['jenis' => 'sama', 'teks' => 'Persiapan tidak berubah.'];
    }
}
