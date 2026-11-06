<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TechnicalDemand;
use App\Enums\TripType;
use App\Http\Resources\KartuJalurResource;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\TrailFitService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Props halaman Jelajah (spec v3 bagian 6.2, prototype 02 dan 15; W3 Tugas 4).
 *
 * Satu kelas halaman seperti RingkasanDasbor dan TrailDetailPage (spec v3 bagian 9.2):
 * TrailSearch memilih kandidat dan memegang aturan saringan, kelas ini menilai kecocokan dan
 * status seluruh kandidat sekaligus (batch, kueri tidak tumbuh bersama jumlah jalur), memisah
 * hasil dari yang disembunyikan saringan, menghitung setiap pilihan saringan, lalu membentuk
 * kartu untuk satu halaman hasil.
 *
 * Kandidat dibatasi hiking.jelajah.batas_kandidat supaya penilaian di memori tetap terbatas;
 * bila terpotong, halaman mengatakannya. Jumlah jalur terbit dikirim terpisah (W1-F1):
 * ketika belum ada satu pun jalur terbit, halaman menyebut siapa yang ditunggu, apa pun
 * saringannya, alih-alih menyalahkan saringan.
 */
final class HalamanJelajah
{
    /** Nama setiap saringan dalam kalimat "N jalur disembunyikan saringan ...". */
    private const NAMA_SARINGAN = [
        'status' => 'status resmi',
        'kecocokan' => 'kecocokan',
        'teknis' => 'tuntutan teknis',
        'jenis' => 'jenis trip',
        'naik' => 'tanjakan',
    ];

    /** Urutan kecocokan pada daftar: Cocok lebih dulu, yang tidak dinilai paling akhir. */
    private const PERINGKAT = ['COCOK' => 0, 'PERLU_PERSIAPAN' => 1, 'KURANG_COCOK' => 2];

    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $status,
        private readonly PetaSebaranJalur $peta,
        private readonly ConsiderationService $simpanan,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(TrailSearch $cari, User $pembaca, Request $request): array
    {
        $batas = (int) config('hiking.jelajah.batas_kandidat', 500);
        $kandidat = $cari->kandidat($batas);
        $terpotong = $kandidat->count() > $batas;
        $kandidat = $kandidat->take($batas)->values();

        $dinilai = $pembaca->hasCompletedProfile();
        $fit = $dinilai ? $this->fit->forTrails($pembaca, $kandidat) : [];
        $status = $this->status->untuk($kandidat);
        $atribut = $kandidat->mapWithKeys(fn (Trail $t) => [$t->id => $this->atribut($t, $fit[$t->id] ?? null, $status[$t->id])])->all();

        $gagal = $kandidat->mapWithKeys(fn (Trail $t) => [$t->id => $cari->gagalPada($atribut[$t->id], $dinilai)])->all();
        $hasil = $this->urutkan($kandidat->filter(fn (Trail $t) => $gagal[$t->id] === null), $atribut, $dinilai);
        $tersembunyi = $kandidat->reject(fn (Trail $t) => $gagal[$t->id] === null)->values();
        $daftar = $cari->tampilkanSemua() ? $hasil->concat($tersembunyi) : $hasil;

        $nomor = max(1, $request->integer('page', 1));
        $halaman = new LengthAwarePaginator(
            $daftar->forPage($nomor, TrailSearch::PER_HALAMAN)->values(),
            $daftar->count(),
            TrailSearch::PER_HALAMAN,
            $nomor,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        /** @var Collection<int, Trail> $isi */
        $isi = collect($halaman->items());
        $garis = SkalaProfil::naikBersama($isi->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all());
        $catatan = $this->simpanan->catatanUntuk($pembaca, $isi->pluck('id'));
        $menunggu = $cari->menunggu();

        return [
            'filter' => $cari->filter(),
            'tafsiran' => $cari->tafsiran(),
            'trails' => [
                'data' => $isi->map(fn (Trail $t) => (new KartuJalurResource(
                    $t,
                    $fit[$t->id] ?? null,
                    $catatan->has($t->id),
                    $status[$t->id],
                    $garis['garis'][$t->id] ?? null,
                    $catatan->get($t->id) !== null,
                    $gagal[$t->id] === null ? null : self::NAMA_SARINGAN[$gagal[$t->id]],
                ))->resolve($request))->values()->all(),
                'total' => $halaman->total(),
                'links' => $halaman->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
            'ringkasan' => [
                'jumlah' => $hasil->count(),
                'tersembunyi' => collect(TrailSearch::SARINGAN)
                    ->map(fn (string $kunci) => ['kunci' => $kunci, 'label' => self::NAMA_SARINGAN[$kunci], 'jumlah' => count(array_keys($gagal, $kunci, true))])
                    ->filter(fn (array $s) => $s['jumlah'] > 0)
                    ->values()
                    ->all(),
                'terpotong' => $terpotong ? $batas : null,
                'urutan' => $dinilai ? 'kecocokan' : 'nama',
            ],
            'saringan' => $this->saringan($cari, $kandidat, $atribut, $dinilai),
            'atribut' => $kandidat->map(fn (Trail $t) => ['id' => $t->id, ...$atribut[$t->id]])->values()->all(),
            'dinilai' => $dinilai,
            'dasarPenilaian' => $dinilai ? $this->dasarPenilaian($pembaca) : null,
            'jumlahTerbit' => Trail::query()->published()->count(),
            'menunggu' => $menunggu->map(fn (Trail $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'url' => route('trails.show', $t->slug),
                'mountain' => $t->mountain?->name,
                'province' => $t->mountain?->province,
            ])->values()->all(),
            // Bentuk jalur, bukan titik gunung (keputusan F2), untuk satu halaman hasil.
            'petaSebaran' => $this->peta->untuk($isi),
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
        ];
    }

    /**
     * Dasar label di Jelajah, seperti prototype 02: tingkat pengalaman dan tanjakan tertinggi
     * yang diisi sendiri. Rincian lengkapnya ada di halaman penilaian kecocokan.
     */
    private function dasarPenilaian(User $pembaca): string
    {
        $tertinggi = $pembaca->experience?->highest_elevation_gain_m;

        return implode(', ', array_filter([
            $pembaca->profile?->experience_level?->label(),
            $tertinggi !== null ? 'tanjakan tertinggi '.number_format($tertinggi, 0, ',', '.').' m' : null,
        ]));
    }

    /**
     * Atribut saringan satu jalur, dihitung sekali dan dipakai tiga kali: menyaring hasil,
     * menghitung pilihan, dan (di klien) menghitung "Tampilkan N jalur".
     *
     * @param  array<string, mixed>  $status
     * @return array{kecocokan: string|null, jenis: array<int, string>, teknisRendah: bool, status: string, naik: int|null}
     */
    private function atribut(Trail $t, ?TrailFitSummary $fit, array $status): array
    {
        $durasi = $t->estimated_duration_minutes;

        return [
            'kecocokan' => $fit?->label?->value,
            'jenis' => array_values(array_filter([
                $durasi !== null && $durasi > 0 && $durasi <= TripType::TEKTOK->typicalMaxDurationMinutes() ? TripType::TEKTOK->value : null,
                $t->camping_available ? TripType::CAMPING->value : null,
            ])),
            'teknisRendah' => $t->technical_demand === TechnicalDemand::LOW,
            'status' => $status['status'] ?? OfficialStatusValue::UNKNOWN->value,
            'naik' => $t->elevation_gain_m,
        ];
    }

    /**
     * Hitungan setiap pilihan saringan: jalur yang lolos semua saringan lain dan bernilai
     * pilihan itu. Kecocokan hanya ditawarkan kepada pembaca yang dinilai.
     *
     * @param  Collection<int, Trail>  $kandidat
     * @param  array<int, array<string, mixed>>  $atribut
     * @return array<string, mixed>
     */
    private function saringan(TrailSearch $cari, Collection $kandidat, array $atribut, bool $dinilai): array
    {
        $lolosKecuali = fn (string $kunci) => $kandidat->filter(fn (Trail $t) => $cari->gagalPada($atribut[$t->id], $dinilai, $kunci) === null);

        $tanpaKecocokan = $lolosKecuali('kecocokan');
        $tanpaJenis = $lolosKecuali('jenis');
        $tanpaStatus = $lolosKecuali('status');
        $tanpaTeknis = $lolosKecuali('teknis');

        return [
            'kecocokan' => $dinilai ? array_map(fn (RouteFitLabel $l) => [
                'nilai' => $l->value,
                'label' => $l->label(),
                'jumlah' => $tanpaKecocokan->filter(fn (Trail $t) => $atribut[$t->id]['kecocokan'] === $l->value)->count(),
            ], RouteFitLabel::cases()) : null,
            'jenis' => array_map(fn (TripType $j) => [
                'nilai' => $j->value,
                'label' => $j === TripType::TEKTOK ? 'Tektok' : 'Camping',
                'jumlah' => $tanpaJenis->filter(fn (Trail $t) => in_array($j->value, $atribut[$t->id]['jenis'], true))->count(),
            ], TrailSearch::JENIS),
            'teknisRendah' => [
                'aktif' => $cari->filter()['teknisRendah'] || $cari->teknisDariTafsiran(),
                'jumlah' => $tanpaTeknis->filter(fn (Trail $t) => $atribut[$t->id]['teknisRendah'])->count(),
            ],
            'status' => array_map(fn (OfficialStatusValue $s) => [
                'nilai' => $s->value,
                'label' => $s->label(),
                'jumlah' => $tanpaStatus->filter(fn (Trail $t) => $atribut[$t->id]['status'] === $s->value)->count(),
            ], [OfficialStatusValue::OPEN, OfficialStatusValue::RESTRICTED, OfficialStatusValue::UNKNOWN, OfficialStatusValue::CLOSED]),
        ];
    }

    /**
     * Hasil diurut kecocokan untuk pembaca yang dinilai (Cocok lebih dulu), lalu nama; untuk
     * pembaca tanpa profil cukup nama, karena tidak ada kecocokan untuk diurutkan.
     *
     * Seri kecocokan tidak diputus pembanding string PHP (m1 tinjauan akhir W3): urutan byte
     * PHP berbeda dari kolasi basis data. Sort PHP 8 stabil, jadi seri mengikuti urutan kandidat dari basis
     * data (nama lalu id, TrailSearch::terapkan()).
     *
     * @param  Collection<int, Trail>  $hasil
     * @param  array<int, array<string, mixed>>  $atribut
     * @return Collection<int, Trail>
     */
    private function urutkan(Collection $hasil, array $atribut, bool $dinilai): Collection
    {
        if (! $dinilai) {
            return $hasil->values();
        }

        return $hasil
            ->sortBy([
                fn (Trail $a, Trail $b) => (self::PERINGKAT[$atribut[$a->id]['kecocokan']] ?? 3) <=> (self::PERINGKAT[$atribut[$b->id]['kecocokan']] ?? 3),
            ])
            ->values();
    }
}
