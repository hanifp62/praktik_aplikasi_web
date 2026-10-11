<?php

namespace App\Services\Community;

use App\Enums\CompletionState;
use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\ProfilElevasiJalur;
use App\Services\Trails\SkalaProfil;
use App\Support\Durasi;
use App\Support\GpxTrack;
use App\Support\Timezone;
use Illuminate\Support\Collection;

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13, prototype 12; rencana induk W6 butir 9).
 *
 * Dua varian (DK-50): selesai, dengan angka, banding diri sendiri, pencapaian, dan formulir
 * laporan; dan dibatalkan, pendek, dengan alasannya. Angka berasal dari rekaman jejak bila ada,
 * dari data jalur bila tidak, atau dari pendaki sendiri lewat "Ubah angka"; sumbernya selalu
 * disebut. Pembandingnya hanya riwayat pendaki ini (R-015), dan riwayat tidak mengubah tingkat
 * pengalaman (BR-11). Yang tidak diketahui ditulis null, bukan nol (R-007), dan tidak ada
 * persen (R-005).
 */
final class HasilPendakian
{
    /**
     * Tonggak "tanjakan pertama di atas N m": 500 m sebagai tonggak pertama, lalu kelipatan
     * 1.000 m, sesuai contoh prototype 12 (700 m lalu 1.660 m menjadi "di atas 1.000 m").
     */
    private const TONGGAK_TANJAKAN = [500, 1000, 2000, 3000];

    /** Anak tangga: pendakian ini dan tiga jalur tertinggi sebelumnya, sama dengan Beranda. */
    private const BARIS_TANGGA = 4;

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain', 'trail.checkpoints', 'trail.segments', 'hikingSession', 'history.conditionReport.moderationActions');

        $riwayat = $trip->history;
        $jalur = $trip->trail;
        $sesi = $trip->hikingSession;
        $titik = $this->titikJejak($sesi);

        $dasar = [
            'jalur' => [
                'nama' => $jalur->name,
                'slug' => $jalur->slug,
                'wilayah' => implode(' · ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            ],
            'varian' => $riwayat === null ? 'batal' : 'selesai',
            // Ruling W6-6b: zona gunung, bukan UTC server, sama dengan laporan.formulir.tanggal;
            // pendakian larut malam UTC dapat jatuh ke tanggal kalender berikutnya di WIB/WITA/WIT.
            'tanggal' => ($riwayat?->completed_at ?? $trip->planned_date)->copy()->setTimezone(Timezone::forTrail($jalur))->translatedFormat('l, j F Y'),
            'hasil' => $this->hasil($trip, $riwayat),
            // Ruling W6-6f (M-1): trip yang dibatalkan tidak punya pos terjauh atau peta jejak
            // untuk ditampilkan, baik yang belum pernah dimulai maupun yang dihentikan sesudah
            // dimulai (TripLifecycle::cancel() menutup sesi tanpa menghapusnya, jadi sesi dan jejaknya tetap
            // ADA di basis data). sesiDimulai membedakan dua kalimat varian batal di
            // HikeSummary.tsx. Peta jejak hanya untuk varian selesai dengan paling sedikit dua
            // titik: satu titik bukan garis.
            'posTerjauh' => $riwayat === null ? null : $this->posTerjauh($trip)?->name,
            'jejak' => $riwayat === null || $titik->count() < 2 ? null : [
                'geometri' => ['type' => 'LineString', 'coordinates' => $titik->map(fn (HikeTrackPoint $t) => [(float) $t->longitude, (float) $t->latitude])->all()],
            ],
            // Ruling W6-6s (B-2): jejak yang tersimpan selalu dapat dihapus dari ringkasannya,
            // di kedua varian, mulai dari SATU titik (hak menghapus konten pribadi, PRD §84).
            // Hanya id sesi yang dikirim, tanpa koordinat, jadi varian batal tidak membawa lokasi
            // ke peramban (minimisasi data, PRD §83); keberadaan objek ini sudah berarti ada
            // titik. Rutenya hike.track.destroy.
            'jejakTersimpan' => $titik->isEmpty() ? null : ['sesi' => $sesi->id],
            'sesiDimulai' => $sesi !== null,
            // Sisipan W6-P2 (ruling W6-P2h): titik yang direkam baru dikirim sesudah pendakian
            // berakhir, jadi ringkasan juga membaca titik yang masih di perangkat. sesiId memilih
            // titik sesi ini di basis data jejak perangkat (hanya id, tanpa koordinat), dan
            // rekamJejak memilih kalimat baris perangkat saat perekaman mati.
            'sesiId' => $sesi?->id,
            'rekamJejak' => (bool) $pengguna->preference?->record_track,
            'garisJalur' => $jalur->readGeoJson('geometry'),
            'penandaPos' => $jalur->checkpoints
                ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
                ->sortBy('sequence')
                ->map(fn (Checkpoint $p) => ['lng' => (float) $p->longitude, 'lat' => (float) $p->latitude, 'label' => $p->sequence.'. '.$p->name])
                ->values()
                ->all(),
            'profil' => ProfilElevasiJalur::untuk($jalur->elevation_profile, $jalur->checkpoints, []),
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, [], []),
            'catatan' => $riwayat?->personal_notes,
        ];

        if ($riwayat === null) {
            return [...$dasar, 'angka' => null, 'tangga' => null, 'banding' => null, 'pencapaian' => [], 'tingkat' => null, 'laporan' => null];
        }

        $angka = $this->angka($trip, $riwayat, $titik);
        $tuntas = $riwayat->completion_state === CompletionState::COMPLETED;
        $tangga = $tuntas ? $this->tangga($pengguna, $trip, $riwayat) : null;

        return [
            ...$dasar,
            'angka' => $angka,
            'tangga' => $tangga['tangga'] ?? null,
            'banding' => match (true) {
                ! $tuntas => 'Pendakian yang berbalik sebelum tujuan tidak dihitung sebagai tanjakan yang dituntaskan. Keputusan berbalik tetap tercatat apa adanya.',
                $tangga === null => 'Tanjakan jalur ini belum tercatat, jadi belum dapat dibandingkan dengan riwayat Anda.',
                default => null,
            },
            'pencapaian' => $tangga['pencapaian'] ?? [],
            'tingkat' => [
                'kalimat' => ($tingkat = $pengguna->profile?->experience_level) !== null
                    ? 'Tingkat pengalaman Anda tetap '.$tingkat->label().' sampai Anda mengubahnya sendiri di Profil.'
                    : 'Riwayat pendakian tidak mengubah tingkat pengalaman Anda; Anda sendiri yang mengisinya di Profil.',
                'url' => route('profile'),
            ],
            'laporan' => $this->laporan($trip, $riwayat, $pengguna),
        ];
    }

    private function hasil(TripPlan $trip, ?HikingHistory $riwayat): string
    {
        $dasar = $riwayat?->completion_state->label() ?? 'Dibatalkan';
        $alasan = $trip->cancellation_reason?->label();

        return $alasan !== null && ($riwayat === null || $riwayat->completion_state === CompletionState::ABANDONED) ? $dasar.' · '.$alasan : $dasar;
    }

    /**
     * Angka pendakian dan sumbernya (ruling W6-R13): angka pendaki bila ia mengubahnya, rekaman
     * jejak bila ada, selain itu data jalur untuk pendakian yang tuntas, dengan jarak pulang-pergi
     * lewat jalur yang sama seperti prototype 12 (9,7 km menjadi 19,4 km). Pendakian yang
     * berbalik tanpa jejak tidak punya jarak, tanjakan, atau titik tertinggi yang diketahui, dan
     * angka jalur penuh akan melebih-lebihkannya (R-007). Waktu bergerak dari sesi mode lapangan
     * (dikurangi jeda).
     *
     * @param  Collection<int, HikeTrackPoint>  $titik
     * @return array<string, mixed>
     */
    private function angka(TripPlan $trip, HikingHistory $riwayat, Collection $titik): array
    {
        $jalur = $trip->trail;
        $menitSesi = self::menitBergerak($trip->hikingSession);

        if ($riwayat->numbers_edited_at !== null) {
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['pendaki', $riwayat->recorded_distance_m, $riwayat->recorded_gain_m, $riwayat->recorded_moving_minutes, $riwayat->recorded_highest_m];
            $keterangan = 'Angka ini Anda ubah sendiri.';
        } elseif ($titik->count() >= 2) {
            $ketinggian = $titik->pluck('elevation_m')->map(fn ($m) => $m === null ? null : (float) $m)->all();
            $koordinat = $titik->map(fn (HikeTrackPoint $t) => [(float) $t->longitude, (float) $t->latitude])->all();
            $tinggi = array_filter($ketinggian, fn ($m) => $m !== null);
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['jejak', (int) round(GpxTrack::lengthKm($koordinat) * 1000), GpxTrack::gainLoss($ketinggian)['gain'] ?? null, $menitSesi, $tinggi === [] ? null : (int) round(max($tinggi))];
            $keterangan = 'Dari rekaman jejak Anda. Ubah bila berbeda.';
        } elseif ($riwayat->completion_state !== CompletionState::COMPLETED) {
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['kosong', null, null, $menitSesi, null];
            $keterangan = 'Tanpa rekaman jejak di Memount, jarak dan tanjakan pendakian yang berbalik tidak diketahui. Isi sendiri bila Anda mencatatnya.';
        } else {
            $profil = SkalaProfil::titik($jalur->elevation_profile);
            $tertinggiPos = $jalur->checkpoints->max('elevation_m');
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = [
                'jalur',
                $jalur->distance_km === null ? null : (int) round((float) $jalur->distance_km * 2000),
                $jalur->elevation_gain_m,
                $menitSesi,
                $profil !== null ? (int) round(max(array_column($profil, 1))) : ($tertinggiPos === null ? null : (int) $tertinggiPos),
            ];
            $keterangan = 'Tanpa rekaman jejak di Memount, angka ini diisi dari data jalur, dengan jarak pulang-pergi lewat jalur yang sama. Ubah bila berbeda.';
        }

        $angka = fn (?int $m) => $m === null ? null : number_format($m, 0, ',', '.');

        return [
            // Spasi tak terputus U+00A0 antara angka dan satuannya (ruling W6-6h, M-3): label
            // ini dipasang di grid dua kolom sempit (AngkaPendakian.tsx); spasi biasa memecah
            // "19,4" dari "km" atau "10 jam" dari "35 menit" ke baris terpisah di 320-390 px.
            'jarak' => $jarakM === null ? null : self::nbsp(number_format($jarakM / 1000, 1, ',', '.').' km'),
            'naik' => $naik === null ? null : self::nbsp('↑'.$angka($naik).' m'),
            'waktu' => $menit === null ? null : self::nbsp(Durasi::panjang($menit)),
            'tertinggi' => $tertinggi === null ? null : self::nbsp($angka($tertinggi).' m'),
            'sumber' => $sumber,
            'keterangan' => $keterangan,
            'awal' => [
                'jarak_km' => $jarakM === null ? null : round($jarakM / 1000, 1),
                'naik_m' => $naik,
                'waktu_menit' => $menit,
                'tertinggi_m' => $tertinggi,
            ],
            'ubah' => route('history.numbers.update', $trip),
        ];
    }

    /**
     * Tangga tanjakan dari riwayat pendaki ini saja (R-015), dengan ukuran yang sama dengan tangga
     * Beranda (App\Services\Dashboard\TanggaTanjakan): tanjakan jalur, satu anak tangga per
     * jalur, hanya pendakian yang tuntas. Isinya pendakian ini dan paling banyak tiga jalur
     * tertinggi yang dituntaskan sebelumnya, naik dari kiri; satu kalimat selisih; pencapaian
     * "tanjakan pertama di atas N m" untuk tonggak tertinggi yang baru dilewati. Null bila
     * tanjakan jalur ini tidak diketahui (R-007).
     *
     * Setiap anak tangga membawa id jalur dan nama gunung (ruling W6-6a, konsisten dengan ruling
     * W5-6a Beranda/TanggaTanjakan): trails.name tidak unik, jadi dua jalur di dua gunung berbeda
     * dapat bernama sama. id dan gunung mendisambiguasinya di kunci React dan label, sama seperti
     * TanggaTanjakan.
     *
     * @return array{tangga: array{baris: array<int, array{id: int, nama: string, gunung: string, m: int, teks: string, ini: bool}>, kalimat: string}, pencapaian: array<int, string>}|null
     */
    private function tangga(User $pengguna, TripPlan $trip, HikingHistory $riwayat): ?array
    {
        $meter = $trip->trail->elevation_gain_m;

        if ($meter === null) {
            return null;
        }

        $sebelumnya = HikingHistory::query()
            ->where('hiking_history.user_id', $pengguna->id)
            ->where('hiking_history.completion_state', CompletionState::COMPLETED->value)
            ->where('hiking_history.completed_at', '<', $riwayat->completed_at)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->join('mountains', 'mountains.id', '=', 'trails.mountain_id')
            ->whereNotNull('trails.elevation_gain_m')
            ->groupBy('trails.id', 'trails.name', 'trails.elevation_gain_m', 'mountains.name')
            ->orderByDesc('trails.elevation_gain_m')
            ->orderBy('trails.id')
            ->limit(self::BARIS_TANGGA - 1)
            ->toBase()
            ->get(['trails.id', 'trails.name', 'trails.elevation_gain_m', 'mountains.name as gunung'])
            ->map(fn (object $t) => ['id' => (int) $t->id, 'nama' => (string) $t->name, 'gunung' => (string) $t->gunung, 'm' => (int) $t->elevation_gain_m, 'ini' => false]);

        $tertinggi = $sebelumnya->first();
        $angka = fn (int $m) => number_format($m, 0, ',', '.');

        // Ruling W6-6n (M-9): kalimat menyebut gunung, bukan hanya nama jalur, supaya dua jalur
        // bernama sama (skenario ruling W6-6a) tidak terbaca ambigu di kalimat perbandingan.
        $kalimat = match (true) {
            $tertinggi === null => 'Ini pendakian tuntas pertama yang tercatat di riwayat Anda.',
            $meter > $tertinggi['m'] => '+'.$angka($meter - $tertinggi['m']).' m dari tanjakan tertinggi Anda sebelumnya ('.$tertinggi['nama'].', '.$tertinggi['gunung'].', '.$angka($tertinggi['m']).' m).',
            $meter === $tertinggi['m'] => 'Setara dengan tanjakan tertinggi Anda sebelumnya ('.$tertinggi['nama'].', '.$tertinggi['gunung'].', '.$angka($tertinggi['m']).' m).',
            default => 'Tanjakan tertinggi Anda tetap '.$tertinggi['nama'].' ('.$tertinggi['gunung'].', '.$angka($tertinggi['m']).' m).',
        };

        // Ruling W6-6g (M-2): "di atas" memakai perbandingan tegas (>), bukan >=, supaya tanjakan
        // yang TEPAT menyentuh sebuah tonggak (misalnya 1.000 m persis) tidak diklaim "di atas
        // 1.000 m". Tonggak yang sudah tepat disentuh sebelumnya (<=, bukan <) tetap terbuka
        // untuk pendakian BERIKUTNYA yang benar-benar melewatinya (1.000 lalu 1.660 m tetap
        // mendapat pencapaian "di atas 1.000 m" untuk pendakian 1.660 m itu).
        $tonggak = collect(self::TONGGAK_TANJAKAN)->filter(fn (int $m) => $meter > $m && ($tertinggi['m'] ?? 0) <= $m)->last();

        return [
            'tangga' => [
                'baris' => $sebelumnya
                    ->push(['id' => $trip->trail->id, 'nama' => $trip->trail->name, 'gunung' => $trip->trail->mountain->name, 'm' => $meter, 'ini' => true])
                    ->sortBy('m')
                    ->map(fn (array $h) => ['id' => $h['id'], 'nama' => $h['nama'], 'gunung' => $h['gunung'], 'm' => $h['m'], 'teks' => self::nbsp($angka($h['m']).' m'), 'ini' => $h['ini']])
                    ->values()
                    ->all(),
                // nbsp() juga untuk kalimat (ruling W6-6h): di 320 px "700" dan "m)." terpisah ke
                // dua baris pada sebagian nama jalur dan gunung (tinjauan task-6-fix1-review bagian 6).
                'kalimat' => self::nbsp($kalimat.' Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.'),
            ],
            'pencapaian' => $tonggak === null ? [] : [self::nbsp('Tanjakan pertama di atas '.$angka($tonggak).' m')],
        ];
    }

    /**
     * Spasi tak terputus U+00A0 antara angka dan satuan yang mengikutinya segera sesudahnya
     * (ruling W6-6h, M-3). Hanya spasi tepat sesudah digit dan sebelum huruf yang diganti, jadi
     * "10 jam 35 menit" menjadi dua pasangan terikat ("10\u{00A0}jam", "35\u{00A0}menit") dengan
     * spasi biasa di antara pasangannya tetap boleh patah baris.
     */
    private static function nbsp(string $teks): string
    {
        return preg_replace('/(?<=\d)\x20+(?=[a-zA-Z])/u', "\u{00A0}", $teks);
    }

    /**
     * Formulir laporan kondisi di ringkasan (spec v3 bagian 6.13) dan keadaannya. Laporan yang
     * belum dimoderasi dapat diubah pemiliknya; sesudahnya tidak. Tautan ubah mengikuti
     * TrailConditionReportPolicy::update() (pemilik dan PENDING), satu sumber dengan Policy,
     * bukan aturan yang ditulis ulang di sini (ruling W6-6r huruf b). Laporan FLAGGED tetap
     * "menunggu" di mata pelapor tetapi tidak dapat diubah lagi.
     *
     * @return array<string, mixed>
     */
    private function laporan(TripPlan $trip, HikingHistory $riwayat, User $pengguna): array
    {
        $laporan = $riwayat->conditionReport;
        $menunggu = $laporan !== null && in_array($laporan->moderation_status, [ModerationStatus::PENDING, ModerationStatus::FLAGGED], true);

        return [
            'keadaan' => match (true) {
                $laporan === null => 'belum',
                $menunggu => 'menunggu',
                $laporan->moderation_status === ModerationStatus::APPROVED => 'terbit',
                default => 'ditolak',
            },
            'alasanTolak' => $laporan?->rejectionReason(),
            'privasi' => 'Yang terbit: tanggal, kondisi, segmen, catatan, foto, dan nama tampilan Anda. Tidak terbit: jejak, titik awal, dan titik akhir Anda.',
            'formulir' => [
                'kirim' => route('reports.store'),
                'ubah' => $laporan !== null && $pengguna->can('update', $laporan) ? route('reports.update', $laporan) : null,
                'tripId' => $trip->id,
                'trailId' => $trip->trail_id,
                'tanggal' => $riwayat->completed_at->copy()->setTimezone(Timezone::forTrail($trip->trail))->toDateString(),
                'tags' => array_map(fn (ConditionTag $t) => ['value' => $t->value, 'label' => $t->label()], ConditionTag::cases()),
                'segmen' => $trip->trail->segments->sortBy('sequence')->map(fn (TrailSegment $s) => ['value' => $s->id, 'label' => $s->sequence.'. '.$s->name])->values()->all(),
                'awal' => $laporan === null ? null : ['condition_tags' => $laporan->condition_tags, 'trail_segment_id' => $laporan->trail_segment_id, 'note' => $laporan->note],
            ],
        ];
    }

    /**
     * Lama bergerak menurut sesi mode lapangan (jeda tidak dihitung, ruling W6-R7), untuk daftar
     * riwayat. Null ketika mode lapangan tidak pernah dibuka atau tidak pernah diakhiri, dan itu
     * keadaan yang umum.
     */
    public static function durasiSesi(?HikingSession $sesi): ?string
    {
        $menit = self::menitBergerak($sesi);

        return $menit === null ? null : Durasi::panjang($menit);
    }

    private static function menitBergerak(?HikingSession $sesi): ?int
    {
        if ($sesi?->started_at === null || $sesi->ended_at === null) {
            return null;
        }

        return intdiv((int) $sesi->detikBergerak(), 60);
    }

    /**
     * Pos terjauh yang dicapai menurut sesi mode lapangan; null bila tidak tercatat.
     */
    private function posTerjauh(TripPlan $trip): ?Checkpoint
    {
        $urutan = $trip->hikingSession?->reached_checkpoint_sequence;

        return $urutan === null ? null : $trip->trail->checkpoints->firstWhere('sequence', $urutan);
    }

    /**
     * Titik jejak yang direkam pendaki, urut waktu rekam.
     *
     * @return Collection<int, HikeTrackPoint>
     */
    private function titikJejak(?HikingSession $sesi): Collection
    {
        if ($sesi === null) {
            return collect();
        }

        return HikeTrackPoint::query()
            ->where('hiking_session_id', $sesi->id)
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude', 'elevation_m']);
    }
}
