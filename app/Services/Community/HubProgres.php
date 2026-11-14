<?php

namespace App\Services\Community;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Enums\PersonalTargetMetric;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\PersonalTarget;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\HikerProgressService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Hub Progres (spec v3 bagian 6.14, prototype 13; rencana induk W7 butir 1). Satu tahun dalam
 * satu halaman: ringkasan tahun, grafik per pendakian, riwayat dengan status laporannya, dan
 * catatan pendakian di luar trip; ditambah dampak laporan dan peta gunung yang sudah didaki.
 *
 * Pembandingnya hanya riwayat pendaki ini (R-015): tidak ada pendaki lain, peringkat, atau
 * catatan kecepatan. Pendakian yang dibatalkan, sebelum berangkat maupun di jalur, tetap
 * tercatat dan terhitung sebagai pendakian (PRD §156 "tidak pernah menghukum keputusan yang
 * benar"), dengan alasan DK-50 bila disebut.
 *
 * Angka satu pendakian (ruling W7-R4): angka yang ditulis pendaki sendiri (catatan manual atau
 * "Ubah angka") bila ada, data jalur untuk pendakian yang tuntas (tanjakan jalur, jarak
 * pulang-pergi lewat jalur yang sama, aturan W6-R13), selain itu tidak diketahui (R-007).
 * Rekaman jejak tidak dijumlahkan di sini, dan halaman mengatakannya.
 *
 * Kueri tetap berapa pun jumlah riwayatnya: satu kueri per tabel untuk tahun yang dibuka dengan
 * relasi dimuat bersama, satu kueri rentang tahun per tabel, dan kueri dampak laporan serta peta.
 */
final class HubProgres
{
    /** Batas baris yang dimuat per tabel untuk satu tahun; halaman menyebutnya bila terlampaui. */
    private const BATAS_TAHUN = 500;

    /** Pendakian terakhir yang digambar di grafik. */
    private const BATAS_GRAFIK = 12;

    public function __construct(
        private readonly HikerProgressService $progres,
        private readonly PencapaianPribadi $pencapaian,
    ) {}

    public static function tahunKini(): int
    {
        return Carbon::now(Timezone::DEFAULT)->year;
    }

    /**
     * @param  array{bagian: string, teks: string}|null  $kabar  kabar tulisan terakhir di halaman ini
     * @return array<string, mixed>
     */
    public function untuk(User $pengguna, ?int $diminta, ?array $kabar = null): array
    {
        [$pertama, $terakhir] = $this->rentang($pengguna);
        $kini = self::tahunKini();
        $tahun = $diminta !== null && $pertama !== null && $diminta >= $pertama && $diminta <= max($kini, (int) $terakhir) ? $diminta : $kini;

        [$riwayat, $batal] = $this->tahun($pengguna, $tahun);
        $butir = $this->butir($riwayat, $batal);
        $dampak = $this->progres->dampakLaporan($pengguna);
        $perStatus = TrailConditionReport::query()
            ->where('user_id', $pengguna->id)
            ->selectRaw('moderation_status, count(*) as jumlah')
            ->groupBy('moderation_status')
            ->get()
            ->mapWithKeys(fn (TrailConditionReport $baris) => [$baris->moderation_status->value => (int) $baris->jumlah]);

        $menunggu = ($perStatus[ModerationStatus::PENDING->value] ?? 0) + ($perStatus[ModerationStatus::FLAGGED->value] ?? 0);
        $ditolak = ($perStatus[ModerationStatus::REJECTED->value] ?? 0) + ($perStatus[ModerationStatus::REMOVED->value] ?? 0);
        $peta = $this->progres->petaGunung($pengguna);

        return [
            'kosong' => $pertama === null && $dampak['laporan_terbit'] === 0,
            'tahun' => [
                'dipilih' => $tahun,
                'semua' => $pertama === null ? [$kini] : range(max($kini, (int) $terakhir), min($pertama, $kini)),
            ],
            'ringkasan' => $butir->isEmpty() ? null : $this->ringkasan($butir, $tahun),
            'grafik' => $butir->count() < 2 ? null : $this->grafik($butir, $tahun),
            // Target pribadi berlaku untuk bulan berjalan, jadi hanya tampil di tahun ini.
            'target' => $tahun === $kini ? $this->target($pengguna, $butir) : null,
            // Pencapaian milik seluruh riwayat, bukan satu tahun.
            'pencapaian' => $this->pencapaian->untuk($pengguna),
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
            'riwayatTerpotong' => $riwayat->count() > self::BATAS_TAHUN || $batal->count() > self::BATAS_TAHUN
                ? 'Halaman ini memuat '.self::BATAS_TAHUN.' catatan terbaru per jenis untuk '.$tahun.'.'
                : null,
            'laporan' => [
                'terbit' => $dampak['laporan_terbit'],
                'menunggu' => $menunggu,
                'ditolak' => $ditolak,
                'terimaKasih' => $dampak['terima_kasih'],
            ],
            'gunung' => $peta['penanda'],
            'keteranganPeta' => count($peta['penanda']) === 1 ? 'Satu gunung yang sudah Anda daki.' : count($peta['penanda']).' gunung yang sudah Anda daki.',
            'gunungTanpaKoordinat' => $peta['tanpaKoordinat'] > 0 ? $peta['tanpaKoordinat'].' gunung lainnya belum berkoordinat, jadi belum dapat digambar.' : null,
            'formulirCatatan' => [
                'simpan' => route('progress.records.store'),
                'tanggalMaks' => Timezone::latestDateInIndonesia(),
                'jenis' => array_map(fn (TripType $j) => ['nilai' => $j->value, 'label' => $j->labelPendek()], TripType::cases()),
            ],
            'kabar' => $kabar,
        ];
    }

    /**
     * Tahun pertama dan terakhir yang punya catatan, dari dua agregat MIN dan MAX yang sama di
     * SQLite dan PostgreSQL (tanpa fungsi tahun khusus mesin).
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function rentang(User $pengguna): array
    {
        $riwayat = HikingHistory::query()->where('user_id', $pengguna->id)->toBase()->selectRaw('min(completed_at) as awal, max(completed_at) as akhir')->first();
        $batal = TripPlan::query()->where('user_id', $pengguna->id)->where('status', TripStatus::CANCELLED->value)->toBase()->selectRaw('min(planned_date) as awal, max(planned_date) as akhir')->first();

        $tahun = array_values(array_filter([
            $riwayat?->awal === null ? null : Carbon::parse($riwayat->awal, 'UTC')->setTimezone(Timezone::DEFAULT)->year,
            $riwayat?->akhir === null ? null : Carbon::parse($riwayat->akhir, 'UTC')->setTimezone(Timezone::DEFAULT)->year,
            $batal?->awal === null ? null : Carbon::parse($batal->awal)->year,
            $batal?->akhir === null ? null : Carbon::parse($batal->akhir)->year,
        ]));

        return $tahun === [] ? [null, null] : [min($tahun), max($tahun)];
    }

    /**
     * Riwayat dan trip yang dibatalkan sebelum berangkat untuk satu tahun (WIB), terbaru dulu.
     *
     * @return array{0: Collection<int, HikingHistory>, 1: Collection<int, TripPlan>}
     */
    private function tahun(User $pengguna, int $tahun): array
    {
        $awal = Carbon::create($tahun, 1, 1, 0, 0, 0, Timezone::DEFAULT)->utc();

        $riwayat = HikingHistory::query()
            ->where('user_id', $pengguna->id)
            ->where('completed_at', '>=', $awal)
            ->where('completed_at', '<', $awal->copy()->addYear())
            ->with([
                'trail:id,name,mountain_id,elevation_gain_m,distance_km',
                'tripPlan:id,planned_date,end_date,cancellation_reason',
                'conditionReport' => fn ($laporan) => $laporan->select(['id', 'moderation_status'])->withCount('thanks'),
            ])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(self::BATAS_TAHUN + 1)
            ->get();

        $batal = TripPlan::query()
            ->where('user_id', $pengguna->id)
            ->where('status', TripStatus::CANCELLED->value)
            ->whereDoesntHave('history')
            ->whereBetween('planned_date', [$tahun.'-01-01', $tahun.'-12-31'])
            ->with('trail:id,name')
            ->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->limit(self::BATAS_TAHUN + 1)
            ->get();

        return [$riwayat->take(self::BATAS_TAHUN), $batal->take(self::BATAS_TAHUN)];
    }

    /**
     * Satu daftar untuk riwayat dan grafik, terbaru dulu. Setiap butir membawa angka mentahnya
     * (untuk ringkasan dan grafik) dan bentuk tampilnya (untuk daftar riwayat).
     *
     * @param  Collection<int, HikingHistory>  $riwayat
     * @param  Collection<int, TripPlan>  $batal
     * @return Collection<int, array<string, mixed>>
     */
    private function butir(Collection $riwayat, Collection $batal): Collection
    {
        $dariRiwayat = $riwayat->map(function (HikingHistory $h) {
            $tanggal = $h->completed_at->copy()->setTimezone(Timezone::DEFAULT);
            $keadaan = match ($h->completion_state) {
                CompletionState::COMPLETED => 'selesai',
                CompletionState::PARTIAL => 'sebagian',
                default => 'batal',
            };
            ['naik' => $naik, 'jarakM' => $jarakM] = self::angka($h);
            $alasan = $keadaan === 'batal' ? $h->tripPlan?->cancellation_reason : null;

            return [
                'urut' => $tanggal->format('Y-m-d H:i:s').sprintf('%012d', $h->id),
                'bulanAngka' => $tanggal->month,
                'nama' => $h->namaJalur(),
                'bulan' => $tanggal->translatedFormat('M'),
                'bulanPanjang' => $tanggal->translatedFormat('F'),
                'keadaan' => $keadaan,
                'alasan' => $alasan,
                'naik' => $naik,
                'jarakM' => $jarakM,
                'tampil' => [
                    'kunci' => 'r'.$h->id,
                    'nama' => $h->namaJalur(),
                    'tanggal' => $h->tripPlan !== null ? self::tanggalTrip($h->tripPlan) : $tanggal->translatedFormat('j M'),
                    'baris' => self::barisAngka($naik, $jarakM, $h->trip_type, $keadaan),
                    'keadaan' => $keadaan,
                    'lencana' => self::lencana($keadaan, $alasan, berbalik: true),
                    'catatan' => self::catatanLaporan($h),
                    'kalimatBatal' => $keadaan === 'batal' ? 'Keputusan yang baik tetap tercatat sebagai pendakian.' : null,
                    'url' => $h->trip_plan_id !== null ? route('history.summary', $h->trip_plan_id) : null,
                    'ubah' => $h->manual() ? self::ubah($h, $tanggal) : null,
                ],
            ];
        });

        $dariTrip = $batal->map(function (TripPlan $t) {
            return [
                'urut' => $t->planned_date->format('Y-m-d').' 00:00:00'.sprintf('%012d', 0),
                'bulanAngka' => $t->planned_date->month,
                'nama' => $t->trail?->name ?? $t->name,
                'bulan' => $t->planned_date->translatedFormat('M'),
                'bulanPanjang' => $t->planned_date->translatedFormat('F'),
                'keadaan' => 'batal',
                'alasan' => $t->cancellation_reason,
                'naik' => null,
                'jarakM' => null,
                'tampil' => [
                    'kunci' => 't'.$t->id,
                    'nama' => $t->trail?->name ?? $t->name,
                    'tanggal' => self::tanggalTrip($t),
                    'baris' => 'batal sebelum pendakian dimulai',
                    'keadaan' => 'batal',
                    'lencana' => self::lencana('batal', $t->cancellation_reason, berbalik: false),
                    'catatan' => null,
                    'kalimatBatal' => 'Keputusan yang baik tetap tercatat sebagai pendakian.',
                    'url' => route('history.summary', $t),
                    'ubah' => null,
                ],
            ];
        });

        return $dariRiwayat->concat($dariTrip)->sortByDesc('urut')->values();
    }

    /**
     * Angka satu pendakian (ruling W7-R4): angka pendaki, lalu data jalur untuk yang tuntas,
     * selain itu tidak diketahui. Jarak dari data jalur adalah pulang-pergi lewat jalur yang sama
     * (prototype 12, ruling W6-R13).
     *
     * @return array{naik: int|null, jarakM: int|null}
     */
    public static function angka(HikingHistory $riwayat): array
    {
        if ($riwayat->manual() || $riwayat->numbers_edited_at !== null) {
            return ['naik' => $riwayat->recorded_gain_m, 'jarakM' => $riwayat->recorded_distance_m];
        }

        if ($riwayat->completion_state === CompletionState::COMPLETED && $riwayat->trail !== null) {
            return [
                'naik' => $riwayat->trail->elevation_gain_m,
                'jarakM' => $riwayat->trail->distance_km === null ? null : (int) round((float) $riwayat->trail->distance_km * 2000),
            ];
        }

        return ['naik' => null, 'jarakM' => null];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<string, mixed>
     */
    private function ringkasan(Collection $butir, int $tahun): array
    {
        // Trip yang batal sebelum berangkat tidak punya angka; pendakian yang berbalik ikut
        // dijumlahkan hanya bila pendakinya menulis angkanya sendiri.
        $naik = $butir->pluck('naik')->reject(fn (?int $m) => $m === null);
        $jarak = $butir->pluck('jarakM')->reject(fn (?int $m) => $m === null);
        $dijalani = $butir->reject(fn (array $b) => $b['keadaan'] === 'batal');
        $tanpaNaik = $dijalani->filter(fn (array $b) => $b['naik'] === null)->count();
        $tanpaJarak = $dijalani->filter(fn (array $b) => $b['jarakM'] === null)->count();

        $rincian = collect([
            'selesai' => $butir->where('keadaan', 'selesai')->count(),
            'sebagian' => $butir->where('keadaan', 'sebagian')->count(),
            'batal' => $butir->where('keadaan', 'batal')->count(),
        ])->filter()->map(fn (int $n, string $kata) => $n.' '.$kata)->implode(', ');

        return [
            'judul' => 'Pendakian '.$tahun,
            'pendakian' => (string) $butir->count(),
            'rincian' => $rincian,
            'tanjakan' => $naik->isEmpty() ? null : number_format((int) $naik->sum(), 0, ',', '.'),
            'tanjakanCatatan' => $tanpaNaik > 0 ? $tanpaNaik.' pendakian tanpa angka tanjakan' : null,
            'jarak' => $jarak->isEmpty() ? null : number_format($jarak->sum() / 1000, 1, ',', '.'),
            'jarakCatatan' => $tanpaJarak > 0 ? $tanpaJarak.' pendakian tanpa angka jarak' : null,
            'sumber' => 'Dari data jalur dan angka yang Anda isi sendiri; rekaman jejak tidak ikut dijumlahkan.',
        ];
    }

    /**
     * Grafik per pendakian, dari yang paling awal, paling banyak BATAS_GRAFIK pendakian terakhir.
     * Setiap batang menulis nilainya; yang dibatalkan dan yang tanpa angka menyebut dirinya,
     * tidak pernah digambar sebagai nol (R-007).
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<string, mixed>
     */
    private function grafik(Collection $butir, int $tahun): array
    {
        $digambar = $butir->take(self::BATAS_GRAFIK)->reverse()->values();
        $terbaru = $digambar->count() - 1;

        $nilai = fn (array $b, string $ukuran): array => match (true) {
            $b['keadaan'] === 'batal' && $b[$ukuran] === null => ['nilai' => null, 'teks' => 'batal'],
            $b[$ukuran] === null => ['nilai' => null, 'teks' => 'belum ada'],
            $ukuran === 'naik' => ['nilai' => $b['naik'], 'teks' => number_format($b['naik'], 0, ',', '.').' m'],
            default => ['nilai' => round($b['jarakM'] / 1000, 1), 'teks' => number_format($b['jarakM'] / 1000, 1, ',', '.').' km'],
        };

        return [
            'batang' => $digambar->map(fn (array $b, int $i) => [
                'kunci' => $b['tampil']['kunci'],
                'nama' => $b['nama'],
                'bulan' => $b['bulan'],
                'keadaan' => $b['keadaan'],
                'terbaru' => $i === $terbaru,
                'tanjakan' => $nilai($b, 'naik'),
                'jarak' => $nilai($b, 'jarakM'),
            ])->all(),
            'keterangan' => [
                'tanjakan' => $this->keterangan($digambar, 'naik', 'Tanjakan', fn (int $m) => number_format($m, 0, ',', '.').' m', 'tanjakan'),
                'jarak' => $this->keterangan($digambar, 'jarakM', 'Jarak', fn (int $m) => number_format($m / 1000, 1, ',', '.').' km', 'jarak'),
            ],
            'catatan' => $butir->count() > self::BATAS_GRAFIK ? self::BATAS_GRAFIK.' pendakian terakhir di '.$tahun.' dari '.$butir->count().'; seluruhnya ada di riwayat di bawah.' : null,
        ];
    }

    /**
     * Keterangan kalimat grafik (spec v3 bagian 6.14 dan 7): nilai pertama dan terbaru yang
     * diketahui tanpa menilai, pendakian yang dibatalkan, dan pendakian tanpa angka.
     *
     * @param  Collection<int, array<string, mixed>>  $batang
     */
    private function keterangan(Collection $batang, string $kunci, string $nama, callable $teks, string $kataAngka): string
    {
        $diketahui = $batang->filter(fn (array $b) => $b[$kunci] !== null)->values();
        $kalimat = [];

        if ($diketahui->isEmpty()) {
            $kalimat[] = 'Belum ada angka '.$kataAngka.' untuk pendakian di sini.';
        } elseif ($diketahui->count() === 1) {
            $satu = $diketahui->first();
            $kalimat[] = $nama.' satu-satunya pendakian dengan angka: '.$teks($satu[$kunci]).' ('.$satu['nama'].', '.$satu['bulan'].').';
        } else {
            $awal = $diketahui->first();
            $akhir = $diketahui->last();
            $sebut = fn (array $b) => $teks($b[$kunci]).' ('.$b['nama'].', '.$b['bulan'].')';

            $kalimat[] = match ($akhir[$kunci] <=> $awal[$kunci]) {
                1 => $nama.' naik dari '.$sebut($awal).' ke '.$sebut($akhir).'.',
                -1 => $nama.' pendakian pertama di sini '.$sebut($awal).'; yang terbaru '.$sebut($akhir).'.',
                default => $nama.' pendakian pertama dan terbaru sama, '.$teks($akhir[$kunci]).'.',
            };
        }

        $batal = $batang->where('keadaan', 'batal')->values();

        if ($batal->count() > 2) {
            $kalimat[] = $batal->count().' pendakian dibatalkan dan tetap tercatat.';
        } else {
            foreach ($batal as $b) {
                $karena = self::karena($b['alasan']);
                $kalimat[] = $b['nama'].' di '.$b['bulanPanjang'].' dibatalkan'.($karena === null ? '' : ' karena '.$karena).' dan tetap tercatat.';
            }
        }

        $tanpa = $batang->filter(fn (array $b) => $b['keadaan'] !== 'batal' && $b[$kunci] === null)->count();

        if ($tanpa > 0) {
            $kalimat[] = $tanpa.' pendakian belum punya angka '.$kataAngka.'.';
        }

        return implode(' ', $kalimat);
    }

    /**
     * Target pribadi bulan berjalan (FR-25; ruling W7-R7): capaian dari pendakian pendaki ini saja,
     * dengan aturan angka yang sama dengan ringkasan tahun. Pendakian yang dibatalkan ikut
     * terhitung pada target jumlah pendakian, sesuai prototype 13 ("tetap dihitung"). Tanpa
     * persen dan tanpa rentetan; target yang belum tercapai disebut angkanya, tanpa teguran.
     *
     * @param  Collection<int, array<string, mixed>>  $butir  butir tahun ini
     * @return array<string, mixed>
     */
    private function target(User $pengguna, Collection $butir): array
    {
        $sekarang = Carbon::now(Timezone::DEFAULT);
        $bulanIni = $butir->where('bulanAngka', $sekarang->month);
        $dijalani = $bulanIni->reject(fn (array $b) => $b['keadaan'] === 'batal');
        $target = $pengguna->personalTargets()->get()->keyBy(fn (PersonalTarget $t) => $t->metric->value);
        $angka = fn (int|float $n, int $desimal = 0): string => number_format($n, $desimal, ',', '.');

        $daftar = collect(PersonalTargetMetric::cases())
            ->filter(fn (PersonalTargetMetric $ukuran) => $target->has($ukuran->value))
            ->map(function (PersonalTargetMetric $ukuran) use ($target, $bulanIni, $dijalani, $sekarang, $butir, $angka) {
                $sasaran = $target[$ukuran->value]->monthly_value;
                $capaian = match ($ukuran) {
                    PersonalTargetMetric::GAIN => (int) $bulanIni->sum('naik'),
                    PersonalTargetMetric::DISTANCE => $bulanIni->sum('jarakM') / 1000,
                    PersonalTargetMetric::HIKES => $bulanIni->count(),
                };
                $tanpa = match ($ukuran) {
                    PersonalTargetMetric::GAIN => $dijalani->filter(fn (array $b) => $b['naik'] === null)->count(),
                    PersonalTargetMetric::DISTANCE => $dijalani->filter(fn (array $b) => $b['jarakM'] === null)->count(),
                    PersonalTargetMetric::HIKES => 0,
                };
                $teks = $ukuran === PersonalTargetMetric::DISTANCE ? $angka($capaian, 1) : $angka($capaian);
                $satuan = $ukuran->satuan() === '' ? '' : ' '.$ukuran->satuan();
                $tercapai = $capaian >= $sasaran;

                return [
                    'metrik' => $ukuran->value,
                    'judul' => $ukuran->label(),
                    'capaian' => $teks,
                    'sasaran' => $angka($sasaran).$satuan,
                    'tercapai' => $tercapai,
                    'kalimat' => $sekarang->translatedFormat('F').': '.($tercapai ? 'tercapai' : $teks.' dari '.$angka($sasaran).$satuan),
                    'rasio' => round(min(1, $capaian / max(1, $sasaran)), 3),
                    'catatan' => $tanpa > 0 ? $tanpa.' pendakian bulan ini belum punya angka '.($ukuran === PersonalTargetMetric::GAIN ? 'tanjakan' : 'jarak').', jadi belum ikut dihitung.' : null,
                    'bulan' => $ukuran === PersonalTargetMetric::HIKES ? $this->bulanTahun($butir, $sekarang) : null,
                    'keterangan' => $ukuran === PersonalTargetMetric::HIKES ? $this->keteranganBulan($butir, $sekarang) : null,
                ];
            })
            ->values()
            ->all();

        return [
            'daftar' => $daftar,
            'bulan' => $sekarang->translatedFormat('F Y'),
            'ubah' => route('progress.targets.update'),
            'awal' => collect(PersonalTargetMetric::cases())
                ->mapWithKeys(fn (PersonalTargetMetric $u) => [$u->isian() => $target->has($u->value) ? (string) $target[$u->value]->monthly_value : ''])
                ->all(),
            'aturan' => 'Target tidak mengubah label kecocokan dan tidak menaikkan tingkat pengalaman Anda.',
        ];
    }

    /**
     * Dua belas bulan tahun ini untuk target jumlah pendakian: ada pendakian, hanya pembatalan,
     * kosong, atau belum tiba. Bukan rentetan: tidak ada hitungan bulan berturut-turut.
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<int, array{nama: string, pendek: string, keadaan: string, teks: string}>
     */
    private function bulanTahun(Collection $butir, Carbon $sekarang): array
    {
        return collect(range(1, 12))->map(function (int $bulan) use ($butir, $sekarang) {
            $isi = $butir->where('bulanAngka', $bulan);
            $batal = $isi->where('keadaan', 'batal')->count();
            $jalan = $isi->count() - $batal;
            $tanggal = $sekarang->copy()->startOfYear()->month($bulan);
            $keadaan = match (true) {
                $bulan > $sekarang->month => 'nanti',
                $jalan > 0 => 'ada',
                $batal > 0 => 'batal',
                default => 'kosong',
            };

            return [
                'nama' => $tanggal->translatedFormat('F'),
                'pendek' => $tanggal->translatedFormat('M'),
                'keadaan' => $keadaan,
                'teks' => match ($keadaan) {
                    'nanti' => 'belum tiba',
                    'kosong' => 'tidak ada pendakian',
                    default => $isi->count().' pendakian'.($batal > 0 ? ', '.$batal.' dibatalkan' : ''),
                },
            ];
        })->all();
    }

    /**
     * Keterangan kalimat kotak bulan: rentangnya, dan bulan yang hanya berisi pembatalan.
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     */
    private function keteranganBulan(Collection $butir, Carbon $sekarang): string
    {
        $kalimat = ['Januari sampai Desember '.$sekarang->year.'.'];
        $hanyaBatal = collect(range(1, $sekarang->month))
            ->filter(fn (int $bulan) => $butir->where('bulanAngka', $bulan)->isNotEmpty() && $butir->where('bulanAngka', $bulan)->every(fn (array $b) => $b['keadaan'] === 'batal'))
            ->take(2);

        foreach ($hanyaBatal as $bulan) {
            $b = $butir->where('bulanAngka', $bulan)->first();
            $karena = self::karena($b['alasan']);
            $kalimat[] = $b['bulanPanjang'].' berisi '.$b['nama'].' yang dibatalkan'.($karena === null ? '' : ' karena '.$karena).', dan tetap dihitung.';
        }

        return implode(' ', $kalimat);
    }

    private static function karena(?CancellationReason $alasan): ?string
    {
        return match ($alasan) {
            null, CancellationReason::OTHER => null,
            default => mb_strtolower($alasan->label()),
        };
    }

    private static function tanggalTrip(TripPlan $trip): string
    {
        $mulai = $trip->planned_date;
        $selesai = $trip->end_date;

        if ($selesai === null || $selesai->isSameDay($mulai)) {
            return $mulai->translatedFormat('j M');
        }

        return $mulai->isSameMonth($selesai)
            ? $mulai->format('j').'-'.$selesai->translatedFormat('j M')
            : $mulai->translatedFormat('j M').'-'.$selesai->translatedFormat('j M');
    }

    private static function barisAngka(?int $naik, ?int $jarakM, ?TripType $jenis, string $keadaan): string
    {
        $bagian = array_values(array_filter([
            $keadaan === 'batal' ? 'berbalik sebelum tujuan' : null,
            $naik === null ? null : '↑'.number_format($naik, 0, ',', '.').' m',
            $jarakM === null ? null : number_format($jarakM / 1000, 1, ',', '.').' km',
            $jenis === null ? null : mb_strtolower($jenis->labelPendek()),
        ]));

        return implode(' · ', $bagian);
    }

    /**
     * Lencana keadaan dengan kata, tidak warna saja: alasan pembatalan DK-50 ditulis bila disebut.
     */
    private static function lencana(string $keadaan, ?CancellationReason $alasan, bool $berbalik): string
    {
        $dasar = match ($keadaan) {
            'selesai' => 'Selesai',
            'sebagian' => 'Selesai sebagian',
            default => $berbalik ? 'Dibatalkan di jalur' : 'Dibatalkan',
        };

        return $alasan === null ? $dasar : $dasar.' · '.$alasan->label();
    }

    /**
     * Keadaan laporan kondisi yang ditautkan ke pendakian ini, atau tanda catatan manual.
     *
     * @return array{teks: string, nada: string}|null
     */
    private static function catatanLaporan(HikingHistory $riwayat): ?array
    {
        if ($riwayat->manual()) {
            return ['teks' => 'Dicatat manual', 'nada' => 'redup'];
        }

        $laporan = $riwayat->conditionReport;

        return match (true) {
            $laporan === null => null,
            in_array($laporan->moderation_status, [ModerationStatus::PENDING, ModerationStatus::FLAGGED], true) => ['teks' => 'Laporan kondisi menunggu moderasi', 'nada' => 'komunitas'],
            $laporan->moderation_status === ModerationStatus::APPROVED => ['teks' => 'Laporan terbit'.($laporan->thanks_count > 0 ? ' · '.$laporan->thanks_count.' terima kasih' : ''), 'nada' => 'komunitas'],
            default => ['teks' => 'Laporan tidak diterbitkan moderator', 'nada' => 'redup'],
        };
    }

    /**
     * Isi awal formulir "Ubah catatan" untuk catatan manual milik pembaca.
     *
     * @return array<string, mixed>
     */
    private static function ubah(HikingHistory $riwayat, Carbon $tanggal): array
    {
        return [
            'simpan' => route('progress.records.update', $riwayat),
            'hapus' => route('progress.records.destroy', $riwayat),
            'jalur' => $riwayat->trail === null ? null : ['id' => $riwayat->trail->id, 'nama' => $riwayat->trail->name],
            'awal' => [
                'trail_id' => $riwayat->trail_id === null ? '' : (string) $riwayat->trail_id,
                'nama_jalur' => (string) $riwayat->trail_name,
                'tanggal' => $tanggal->toDateString(),
                'jenis' => $riwayat->trip_type?->value ?? TripType::TEKTOK->value,
                'jarak_km' => $riwayat->recorded_distance_m === null ? '' : str_replace('.', ',', (string) round($riwayat->recorded_distance_m / 1000, 1)),
                'naik_m' => $riwayat->recorded_gain_m === null ? '' : (string) $riwayat->recorded_gain_m,
                'jam' => $riwayat->recorded_moving_minutes === null ? '' : (string) intdiv($riwayat->recorded_moving_minutes, 60),
                'menit' => $riwayat->recorded_moving_minutes === null ? '' : (string) ($riwayat->recorded_moving_minutes % 60),
                'tertinggi_m' => $riwayat->recorded_highest_m === null ? '' : (string) $riwayat->recorded_highest_m,
                'catatan' => (string) $riwayat->personal_notes,
            ],
        ];
    }
}
