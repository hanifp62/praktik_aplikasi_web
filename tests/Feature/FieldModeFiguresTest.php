<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Angka mode lapangan (spec v3 bagian 6.12; rencana induk W6 butir 2): pembulatan jarak, sisa
 * jarak menyusuri jalur atau garis lurus, naik lagi dari profil elevasi, tiba di pos, dan pos
 * berikutnya.
 *
 * Hitungannya berjalan di perangkat (resources/js/components/mendaki/hitung.ts) supaya mode
 * lapangan tetap bekerja tanpa sinyal dan posisi tidak dikirim ke server. Test ini menjalankan
 * modul itu di Node lewat proses terpisah, jadi yang diuji perilakunya, bukan teks sumbernya.
 * Node 22.6 atau lebih baru dibutuhkan untuk membuang anotasi tipe; Node yang sama sudah
 * dibutuhkan build aset.
 */
class FieldModeFiguresTest extends TestCase
{
    public function test_distances_are_rounded_to_fifty_metres_below_a_kilometre_and_a_tenth_above(): void
    {
        $this->assertSame(
            ["< 50\u{A0}m", "50\u{A0}m", "950\u{A0}m", "1,0\u{A0}km", "1,3\u{A0}km", "1,9\u{A0}km", "12,3\u{A0}km"],
            $this->hitung([['teksJarak', [30]], ['teksJarak', [74]], ['teksJarak', [940]], ['teksJarak', [975]], ['teksJarak', [1260]], ['teksJarak', [1949]], ['teksJarak', [12345]]])
        );
    }

    /**
     * Garis jalur berbentuk L: 1 km ke utara lalu 1 km ke timur, pos tujuan di ujungnya. Dari
     * awal garis, sisa menyusuri jalur 2,0 km; garis lurusnya hanya sekitar 1,4 km dan hanya
     * dipakai, dengan namanya, bila garis jalur tidak ada.
     */
    public function test_the_remaining_distance_follows_the_trail_and_names_a_straight_line_when_it_cannot(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99100679], [110.00906, -6.99100679]];
        $ujung = [110.00906, -6.99100679];

        [$sepanjang, $menyusuri, $lurus] = $this->hitung([
            ['sepanjangPos', [$garis, $ujung[0], $ujung[1]]],
            ['sisaJarak', [$garis, [110.0, -7.0], ['lng' => $ujung[0], 'lat' => $ujung[1], 'sepanjangM' => 2000.0]]],
            ['sisaJarak', [null, [110.0, -7.0], ['lng' => $ujung[0], 'lat' => $ujung[1], 'sepanjangM' => null]]],
        ]);

        $this->assertEqualsWithDelta(2000, $sepanjang, 5);
        $this->assertSame('jalur', $menyusuri['cara']);
        $this->assertEqualsWithDelta(2000, $menyusuri['meter'], 1);
        $this->assertSame('garis lurus', $lurus['cara']);
        $this->assertSame(["2,0\u{A0}km", "1,4\u{A0}km"], $this->hitung([['teksJarak', [$menyusuri['meter']]], ['teksJarak', [$lurus['meter']]]]));
    }

    /**
     * Posisi 300 m di samping garis tidak diukur menyusuri jalur: jaraknya garis lurus, dan
     * caranya disebut.
     */
    public function test_a_position_far_from_the_line_is_measured_in_a_straight_line(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99]];

        $hasil = $this->hitung([['sisaJarak', [$garis, [110.0027, -6.995], ['lng' => 110.0, 'lat' => -6.99, 'sepanjangM' => 1112.0]]]])[0];

        $this->assertSame('garis lurus', $hasil['cara']);
    }

    /**
     * Naik lagi dibaca dari profil elevasi, bukan dari ketinggian GPS: kenaikan dijumlahkan dan
     * turunan tidak mengurangi. Tanpa profil, naik tidak ditebak.
     */
    public function test_the_remaining_climb_comes_from_the_elevation_profile(): void
    {
        $profil = [[0, 1500], [1, 1800], [1.5, 1750], [2, 2000]];

        $this->assertSame(
            [400, 550, null, "↑1.650\u{A0}m"],
            $this->hitung([['naikDiantara', [$profil, 0.5, 2]], ['naikDiantara', [$profil, 0, 2]], ['naikDiantara', [[], 0, 2]], ['teksNaik', [1650]]])
        );
        $this->assertStringNotContainsString('altitude', File::get(resource_path('js/components/mendaki/hitung.ts')));
    }

    /**
     * Garis rute dan profil dapat berasal dari rekaman yang panjangnya berbeda: letak pada garis
     * dipetakan ke profil menurut perbandingan panjang.
     */
    public function test_a_position_on_the_line_maps_onto_the_profile_by_proportion(): void
    {
        $this->assertSame([5, 2.5, null], $this->hitung([
            ['kmPadaProfil', [1000, 2000, [[0, 1500], [10, 2500]]]],
            ['kmPadaProfil', [500, 2000, [[0, 1500], [10, 2500]]]],
            ['kmPadaProfil', [500, 0, [[0, 1500], [10, 2500]]]],
        ]));
    }

    /**
     * Tiba di pos: otomatis bila jarak ditambah akurasi paling jauh 75 m; ditanya bila hanya
     * jarak dikurangi akurasi yang masuk; selain itu belum.
     */
    public function test_arrival_is_automatic_only_when_distance_plus_accuracy_is_within_the_radius(): void
    {
        $this->assertSame(
            ['tiba', 'tiba', 'tanya', 'belum'],
            $this->hitung([['keputusanTiba', [40, 20, 75]], ['keputusanTiba', [70, 5, 75]], ['keputusanTiba', [60, 20, 75]], ['keputusanTiba', [120, 20, 75]]])
        );
    }

    /**
     * Kartu "Sudah tiba?" hanya pantas ketika akurasi cukup tajam untuk dipercaya (I-5/ruling
     * W6-3e): keputusanTiba sudah menjawab 'tanya', tetapi akurasi 51 m ke atas tidak lagi
     * lolos. 'tiba' dan 'belum' tidak pernah lolos, berapa pun akurasinya.
     */
    public function test_the_arrival_card_only_appears_when_accuracy_is_sharp_enough(): void
    {
        $this->assertSame(
            [true, false, false, false, false],
            $this->hitung([
                ['bolehTanyaTiba', ['tanya', 50]],
                ['bolehTanyaTiba', ['tanya', 51]],
                ['bolehTanyaTiba', ['tanya', 600]],
                ['bolehTanyaTiba', ['tiba', 10]],
                ['bolehTanyaTiba', ['belum', 10]],
            ])
        );
    }

    /**
     * Pos yang sudah terlampaui di garis dipulihkan sebagai pertanyaan (I-6/ruling W6-3f):
     * pendaki yang letaknya 500 m dari awal garis sudah melewati Pos 1 (100 m) dan Pos 2
     * (300 m) lebih dari BATAS_MENYIMPANG_M, tetapi belum Pos 3 (900 m). Akurasi kasar atau
     * posisi jauh dari garis tidak memicu pertanyaan ini, dan pos yang sudah tercatat (tercapai)
     * tidak ditanya ulang.
     */
    public function test_passed_checkpoints_are_offered_as_a_question_not_recorded_automatically(): void
    {
        $pos = [
            ['urutan' => 1, 'sepanjangM' => 100.0],
            ['urutan' => 2, 'sepanjangM' => 300.0],
            ['urutan' => 3, 'sepanjangM' => 900.0],
        ];

        $this->assertSame(
            [['dari' => 1, 'sampai' => 2], null, null, null],
            $this->hitung([
                ['posTerlampaui', [$pos, 0, ['sepanjangM' => 500.0, 'menyimpangM' => 10.0], 20]],
                ['posTerlampaui', [$pos, 0, ['sepanjangM' => 500.0, 'menyimpangM' => 10.0], 80]],
                ['posTerlampaui', [$pos, 0, ['sepanjangM' => 500.0, 'menyimpangM' => 200.0], 20]],
                ['posTerlampaui', [$pos, 2, ['sepanjangM' => 500.0, 'menyimpangM' => 10.0], 20]],
            ])
        );
    }

    /**
     * Keputusan kartu lapangan, satu fungsi murni (W6-3m): kartu tiba dan kartu lewat masing-
     * masing digerbangi di sini, tidak di hook atau komponen, sehingga membuang gerbangnya di
     * salah satu tempat itu tidak mungkin tanpa membuat test ini atau asersi sumbernya merah.
     * Penundaan "Belum" pada kartu lewat berbasis rentang (N-6/ruling W6-3n), bukan waktu:
     * rentang yang sama tetap ditunda walau jam berjalan, dan muncul lagi begitu rentangnya
     * bertambah.
     */
    public function test_the_field_card_decision_gates_accuracy_and_remembers_declines(): void
    {
        $tujuan = ['urutan' => 3, 'nama' => 'Pos 3'];

        $this->assertSame(
            [
                ['tiba' => ['jenis' => 'tanya', 'urutan' => 3, 'nama' => 'Pos 3'], 'lewat' => null],
                ['tiba' => ['jenis' => 'akurasi-kurang', 'akurasiM' => 80], 'lewat' => null],
                ['tiba' => null, 'lewat' => null],
                ['tiba' => null, 'lewat' => null],
                ['tiba' => null, 'lewat' => ['dari' => 1, 'sampai' => 2]],
                ['tiba' => null, 'lewat' => null],
                ['tiba' => null, 'lewat' => ['dari' => 1, 'sampai' => 3]],
            ],
            $this->hitung([
                // Akurasi cukup tajam: kartu tanya.
                ['kartuLapangan', [['keputusan' => 'tanya', 'akurasiM' => 20, 'tujuan' => $tujuan, 'lewat' => null, 'tolakTiba' => null, 'tolakLewatSampai' => null, 'sekarang' => 1000]]],
                // Akurasi kasar: kalimat akurasi-kurang, bukan kartu tanya.
                ['kartuLapangan', [['keputusan' => 'tanya', 'akurasiM' => 80, 'tujuan' => $tujuan, 'lewat' => null, 'tolakTiba' => null, 'tolakLewatSampai' => null, 'sekarang' => 1000]]],
                // keputusan bukan 'tanya': tidak ada kartu atau kalimat sama sekali.
                ['kartuLapangan', [['keputusan' => 'belum', 'akurasiM' => 20, 'tujuan' => $tujuan, 'lewat' => null, 'tolakTiba' => null, 'tolakLewatSampai' => null, 'sekarang' => 1000]]],
                // Pos yang sama baru dijawab "Belum" dan belum lewat JEDA_TANYA_MS: ditunda.
                ['kartuLapangan', [['keputusan' => 'tanya', 'akurasiM' => 20, 'tujuan' => $tujuan, 'lewat' => null, 'tolakTiba' => ['urutan' => 3, 'sampai' => 2000], 'tolakLewatSampai' => null, 'sekarang' => 1500]]],
                // Kartu lewat tampil normal tanpa penolakan sebelumnya.
                ['kartuLapangan', [['keputusan' => 'belum', 'akurasiM' => null, 'tujuan' => null, 'lewat' => ['dari' => 1, 'sampai' => 2], 'tolakTiba' => null, 'tolakLewatSampai' => null, 'sekarang' => 1000]]],
                // N-6: rentang sampai=2 baru ditolak (tolakLewatSampai=2); rentang yang SAMA tetap ditunda.
                ['kartuLapangan', [['keputusan' => 'belum', 'akurasiM' => null, 'tujuan' => null, 'lewat' => ['dari' => 1, 'sampai' => 2], 'tolakTiba' => null, 'tolakLewatSampai' => 2, 'sekarang' => 999999]]],
                // N-6: rentang bertambah menjadi sampai=3 setelah ditolak pada sampai=2: tampil lagi.
                ['kartuLapangan', [['keputusan' => 'belum', 'akurasiM' => null, 'tujuan' => null, 'lewat' => ['dari' => 1, 'sampai' => 3], 'tolakTiba' => null, 'tolakLewatSampai' => 2, 'sekarang' => 1000]]],
            ])
        );
    }

    /**
     * Keputusan tiba dan kartu lapangan dari nilai mentah, satu fungsi murni (Q-1/ruling W6-3p):
     * setiap gerbang di keadaanKartu punya kasus yang merah bila gerbang itu dibuang di sana.
     * Gerbangnya: akurasi tiba, ada atau tiada pos lewat, akurasi lewat, tolak tiba, tolak lewat,
     * dijeda, dan tidak berlangsung; dijeda dan tidak berlangsung masing-masing diuji pada kartu
     * tiba dan kartu lewat, karena keduanya menggerbangi keputusan dan rentang lewat.
     *
     * Garis lurus ke utara di bujur 110,0: lintang -7,0 ditambah meter dibagi 111.194,9 adalah
     * letak sejauh itu dari awal garis, jadi jarak pendaki ke pos sama dengan selisih letaknya
     * (Pos 1 di 100 m, Pos 2 di 300 m, Pos 3 di 900 m; radius tiba 75 m).
     */
    public function test_the_card_state_has_one_case_per_gate(): void
    {
        $pos = [['urutan' => 1, 'sepanjangM' => 100.0], ['urutan' => 2, 'sepanjangM' => 300.0], ['urutan' => 3, 'sepanjangM' => 900.0]];
        $dasar = ['dijeda' => false, 'berlangsung' => true, 'radiusTiba' => 75, 'pos' => $pos, 'tolakTiba' => null, 'tolakLewatSampai' => null, 'sekarang' => 1000];

        // Pendaki 90 m sebelum Pos 3 (tercapai 2): keputusanTiba 'tanya' pada akurasi 30 m dan 80 m.
        $menujuPos3 = fn (array $ubah = []): array => [...$dasar, ...[
            'posisi' => ['lng' => 110.0, 'lat' => -6.9927155, 'akurasi' => 30],
            'tujuan' => ['urutan' => 3, 'nama' => 'Pos 3', 'lng' => 110.0, 'lat' => -6.9919061],
            'tercapai' => 2,
            'letak' => ['sepanjangM' => 810.0, 'menyimpangM' => 5.0],
        ], ...$ubah];
        // Pendaki di 500 m (tercapai 0): Pos 1 dan Pos 2 terlampaui lebih dari 150 m, Pos 1 400 m di belakang.
        $lewatiPos2 = fn (array $ubah = []): array => [...$dasar, ...[
            'posisi' => ['lng' => 110.0, 'lat' => -6.9955034, 'akurasi' => 20],
            'tujuan' => ['urutan' => 1, 'nama' => 'Pos 1', 'lng' => 110.0, 'lat' => -6.9991007],
            'tercapai' => 0,
            'letak' => ['sepanjangM' => 500.0, 'menyimpangM' => 5.0],
        ], ...$ubah];

        $tanpaKartu = ['tiba' => null, 'lewat' => null];
        $kasus = [
            'akurasi tiba 30 m: kartu tanya' => [$menujuPos3(), ['keputusan' => 'tanya', 'kartu' => ['tiba' => ['jenis' => 'tanya', 'urutan' => 3, 'nama' => 'Pos 3'], 'lewat' => null]]],
            'akurasi tiba 80 m: kalimat akurasi, bukan kartu tanya' => [
                $menujuPos3(['posisi' => ['lng' => 110.0, 'lat' => -6.9927155, 'akurasi' => 80]]),
                ['keputusan' => 'tanya', 'kartu' => ['tiba' => ['jenis' => 'akurasi-kurang', 'akurasiM' => 80], 'lewat' => null]],
            ],
            'jarak 20 m, akurasi 20 m: tiba otomatis tanpa kartu' => [
                $menujuPos3(['posisi' => ['lng' => 110.0, 'lat' => -6.992086, 'akurasi' => 20], 'letak' => ['sepanjangM' => 880.0, 'menyimpangM' => 5.0]]),
                ['keputusan' => 'tiba', 'kartu' => $tanpaKartu],
            ],
            'ada pos lewat' => [$lewatiPos2(), ['keputusan' => 'belum', 'kartu' => ['tiba' => null, 'lewat' => ['dari' => 1, 'sampai' => 2]]]],
            'tiada pos lewat: Pos 1 baru 100 m di belakang' => [
                $lewatiPos2(['posisi' => ['lng' => 110.0, 'lat' => -6.9982014, 'akurasi' => 20], 'letak' => ['sepanjangM' => 200.0, 'menyimpangM' => 5.0]]),
                ['keputusan' => 'belum', 'kartu' => $tanpaKartu],
            ],
            'akurasi lewat 80 m' => [$lewatiPos2(['posisi' => ['lng' => 110.0, 'lat' => -6.9955034, 'akurasi' => 80]]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'tolak tiba Pos 3 belum lewat waktunya' => [$menujuPos3(['tolakTiba' => ['urutan' => 3, 'sampai' => 2000]]), ['keputusan' => 'tanya', 'kartu' => $tanpaKartu]],
            'tolak lewat rentang sampai 2' => [$lewatiPos2(['tolakLewatSampai' => 2]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'dijeda, kartu tiba' => [$menujuPos3(['dijeda' => true]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'dijeda, kartu lewat' => [$lewatiPos2(['dijeda' => true]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'tidak berlangsung, kartu tiba' => [$menujuPos3(['berlangsung' => false]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'tidak berlangsung, kartu lewat' => [$lewatiPos2(['berlangsung' => false]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'tanpa posisi' => [$menujuPos3(['posisi' => null, 'letak' => null]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
            'tujuan tanpa koordinat' => [$menujuPos3(['tujuan' => ['urutan' => 3, 'nama' => 'Pos 3', 'lng' => null, 'lat' => null]]), ['keputusan' => 'belum', 'kartu' => $tanpaKartu]],
        ];

        $hasil = $this->hitung(array_map(fn (array $satu): array => ['keadaanKartu', [$satu[0]]], array_values($kasus)));

        $this->assertSame(array_map(fn (array $satu): array => $satu[1], $kasus), array_combine(array_keys($kasus), $hasil));
    }

    /**
     * Kartu lewat menyebut setiap pos yang ikut tercatat bila dijawab "Ya" (Q-7 dan Q-13/ruling
     * W6-3v). Kalimatnya dari kalimatKartuLewat: bentuk tunggal bila dari === sampai, bentuk
     * rentang selain itu. Rentangnya dari posTerlampaui: `dari` adalah pos pertama sesudah
     * tercapai, jadi Pos 1 tanpa letak di garis di depan Pos 2 yang terlampaui ikut disebut,
     * karena "Ya" mengirim Pos 2 dan mencatat Pos 1 juga. Pada urutan bercelah (Pos 2 tidak ada,
     * misalnya dihapus admin), `dari` adalah pos yang ada, bukan tercapai + 1 harfiah. Daftar pos
     * yang sama dengan urutan terbalik memberi rentang yang sama: `dari` dan `sampai` ditentukan
     * menurut urutan pos, bukan menurut letaknya di larik masukan.
     */
    public function test_the_passed_card_names_every_checkpoint_a_yes_records(): void
    {
        $this->assertSame(
            [
                ['judul' => 'Tandai Pos 2 sudah dilewati?', 'status' => 'Pos 2 tampak sudah dilewati. Tandai di kartu di bawah bila benar.'],
                ['judul' => 'Tandai Pos 2 sampai 3 sudah dilewati?', 'status' => 'Pos 2 sampai 3 tampak sudah dilewati. Tandai di kartu di bawah bila benar.'],
            ],
            $this->hitung([['kalimatKartuLewat', [2, 2]], ['kalimatKartuLewat', [2, 3]]])
        );

        $letak = ['sepanjangM' => 500.0, 'menyimpangM' => 10.0];
        $posQ13 = [['urutan' => 1, 'sepanjangM' => null], ['urutan' => 2, 'sepanjangM' => 300.0], ['urutan' => 3, 'sepanjangM' => 900.0]];

        [$q13, $bercelah, $terbalik] = $this->hitung([
            ['posTerlampaui', [$posQ13, 0, $letak, 20]],
            ['posTerlampaui', [[['urutan' => 1, 'sepanjangM' => 100.0], ['urutan' => 3, 'sepanjangM' => 300.0], ['urutan' => 4, 'sepanjangM' => 900.0]], 1, $letak, 20]],
            ['posTerlampaui', [array_reverse($posQ13), 0, $letak, 20]],
        ]);

        $this->assertSame(['dari' => 1, 'sampai' => 2], $q13, 'Q-13: Pos 1 tanpa letak ikut tercatat oleh "Ya", jadi wajib disebut.');
        $this->assertSame(['dari' => 3, 'sampai' => 3], $bercelah, 'Urutan bercelah: kartu menyebut Pos 3 yang ada, bukan Pos 2.');
        $this->assertSame(['dari' => 1, 'sampai' => 2], $terbalik, 'Daftar pos terbalik memberi rentang yang sama.');
        $this->assertSame(
            ['Tandai Pos 1 sampai 2 sudah dilewati?', 'Tandai Pos 3 sudah dilewati?'],
            array_column($this->hitung([['kalimatKartuLewat', [$q13['dari'], $q13['sampai']]], ['kalimatKartuLewat', [$bercelah['dari'], $bercelah['sampai']]]]), 'judul')
        );
    }

    /**
     * Pos berikutnya adalah pos pertama menurut urutan yang belum tercapai, bukan "satu setelah
     * yang terdekat" (F-09): pendaki yang sudah mencapai Pos 2 menuju Pos 3.
     */
    public function test_the_next_checkpoint_is_the_first_one_not_yet_reached(): void
    {
        $pos = [['urutan' => 3, 'nama' => 'Pos 3'], ['urutan' => 1, 'nama' => 'Pos 1'], ['urutan' => 2, 'nama' => 'Pos 2']];

        $this->assertSame(
            [['urutan' => 1, 'nama' => 'Pos 1'], ['urutan' => 3, 'nama' => 'Pos 3'], null],
            $this->hitung([['posBerikutnya', [$pos, 0]], ['posBerikutnya', [$pos, 2]], ['posBerikutnya', [$pos, 3]]])
        );
    }

    /**
     * Peta mode lapangan membelah garis pada letak yang sudah dilalui: bagian dilalui berakhir
     * dan bagian sisa bermula di titik belah yang sama, jadi garisnya tidak terputus.
     */
    public function test_the_line_splits_where_the_hiker_has_reached(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99]];

        [$tengah, $awal] = $this->hitung([['potongGaris', [$garis, 556]], ['potongGaris', [$garis, 0]]]);

        $this->assertCount(2, $tengah['dilalui']);
        $this->assertSame($tengah['dilalui'][1], $tengah['sisa'][0]);
        $this->assertEqualsWithDelta(-6.995, $tengah['sisa'][0][1], 0.0001);
        $this->assertSame([], $awal['dilalui']);
        $this->assertEquals($garis, $awal['sisa']);
    }

    public function test_time_and_accuracy_read_as_people_say_them(): void
    {
        $mulai = 1_000_000;

        $this->assertSame(
            ['4 jam 12 menit', '45 menit', 'baru saja', '30 detik lalu', '2 menit lalu', '±15 m', 3600],
            $this->hitung([
                ['teksLama', [15120]],
                ['teksLama', [2700]],
                ['teksUmur', [4]],
                ['teksUmur', [30]],
                ['teksUmur', [150]],
                ['teksAkurasi', [14.6]],
                ['detikBergerak', [$mulai, 1800, $mulai + 5_400_000, $mulai + 7_200_000]],
            ])
        );
    }

    /**
     * Putaran perbaikan 1 (ruling W6-2a, Important #1): profil elevasi dari rekaman nyata
     * (GPX, basis data) tidak menjamin urutan menurut km. Profil yang sama dengan urutan
     * dibalik harus memberi jawaban yang sama dengan profil yang sudah terurut naik, baik
     * untuk naik yang tersisa maupun letak pada profil.
     */
    public function test_climb_and_profile_lookup_are_unaffected_by_profile_point_order(): void
    {
        $profilTerbalik = [[2, 2000], [1.5, 1750], [1, 1800], [0, 1500]];

        $this->assertSame(
            [400, 550, 2.5],
            $this->hitung([
                ['naikDiantara', [$profilTerbalik, 0.5, 2]],
                ['naikDiantara', [$profilTerbalik, 0, 2]],
                ['kmPadaProfil', [500, 2000, [[10, 2500], [0, 1500]]]],
            ])
        );
    }

    /**
     * Putaran perbaikan 1 (ruling W6-2a, Important #2 / M5): pos tujuan yang sudah
     * terlewati (sepanjangM tujuan lebih kecil dari letak posisi sekarang di garis) tidak
     * boleh memberi jarak menyusuri jalur yang negatif; penjaga ini jatuh ke garis lurus.
     */
    public function test_the_remaining_distance_falls_back_to_a_straight_line_when_the_checkpoint_is_behind(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99100679], [110.00906, -6.99100679]];
        $ujung = [110.00906, -6.99100679];

        $hasil = $this->hitung([
            ['sisaJarak', [$garis, $ujung, ['lng' => 110.0, 'lat' => -7.0, 'sepanjangM' => 0.0]]],
        ])[0];

        $this->assertSame('garis lurus', $hasil['cara']);
    }

    /**
     * Putaran perbaikan 1 (ruling W6-2a, Important #3 / M6): naik yang tersisa saat
     * menyusuri mundur (sampaiKm lebih kecil dari dariKm) tetap benar, bukan sekadar
     * menjumlah titik tengah dalam urutan maju yang salah arah.
     */
    public function test_the_remaining_climb_handles_backward_traversal(): void
    {
        $profil = [[0, 1500], [1, 1800], [1.5, 1750], [2, 2000]];

        $this->assertSame(50, $this->hitung([['naikDiantara', [$profil, 2, 0.5]]])[0]);
    }

    /**
     * Putaran perbaikan 1 (ruling W6-2a, Important #4 / M7): dariKm/sampaiKm jauh di luar
     * rentang profil dijepit ke ujungnya, memberi jawaban yang sama dengan rentang penuh.
     */
    public function test_the_remaining_climb_clamps_a_range_outside_the_profile(): void
    {
        $profil = [[0, 1500], [1, 1800], [1.5, 1750], [2, 2000]];

        $this->assertSame(550, $this->hitung([['naikDiantara', [$profil, -5, 10]]])[0]);
    }

    /**
     * Putaran perbaikan 1 (ruling W6-2a, M8): jeda yang tercatat lebih lama dari waktu
     * yang sudah berjalan tidak membalik detik bergerak ke bawah nol.
     */
    public function test_moving_seconds_never_goes_negative_when_pauses_exceed_elapsed_time(): void
    {
        $mulai = 1_000_000;

        $this->assertSame(0, $this->hitung([['detikBergerak', [$mulai, 20, null, $mulai + 10_000]]])[0]);
    }

    /**
     * Blok lokasi kartu darurat (spec v3 bagian 10.2; ruling W6-R4): koordinat dibacakan dengan
     * penanda arah Indonesia, dan ketinggian dibaca dari profil elevasi pada satu jarak, bukan
     * dari GPS. Tanpa profil, ketinggian tidak ditebak.
     *
     * Pra-tinjau Tugas 7 (ruling P-R10): derajat dan penanda arah dipisah spasi tak terputus
     * (PT7-13, harapan brief diubah), profil terbalik memberi jawaban yang sama dengan profil
     * terurut (PT7-11, kontrak W6-2a), dan kasus pembunuh PT7-10 M8 (pembulatan, nilai mentah
     * 1.633,67), M9 (profil satu titik), dan M10 (bujur barat).
     */
    public function test_the_emergency_location_block_reads_as_it_is_spoken(): void
    {
        $profil = [[0, 1500], [2, 1900], [4, 2400]];

        $this->assertSame(
            ["6,78412°\u{00A0}LS, 106,99031°\u{00A0}BT", "0,50000°\u{00A0}LU, 100,10000°\u{00A0}BT", 1700, 2400, null, null],
            $this->hitung([
                ['teksKoordinat', [-6.784123, 106.990314]],
                ['teksKoordinat', [0.5, 100.1]],
                ['elevasiPada', [$profil, 1]],
                ['elevasiPada', [$profil, 9]],
                ['elevasiPada', [null, 1]],
                ['elevasiPada', [$profil, null]],
            ])
        );

        $this->assertSame(
            [1700, 1634, null, "0,50000°\u{00A0}LS, 100,10000°\u{00A0}BB"],
            $this->hitung([
                ['elevasiPada', [array_reverse($profil), 1]],
                ['elevasiPada', [[[0, 1500], [3, 1901]], 1]],
                ['elevasiPada', [[[0, 1500]], 1]],
                ['teksKoordinat', [-0.5, -100.1]],
            ])
        );
    }

    /**
     * PT7-1: ketinggian kartu darurat dibaca dari letak posisi itu sendiri di garis jalur, dan
     * hanya bila pendaki memang di jalur dengan akurasi yang cukup. Pendaki yang menyimpang
     * lebih dari BATAS_MENYIMPANG_M tidak diberi ketinggian titik jalur terdekat (R-007).
     * Garis sama dengan test_a_position_far_from_the_line_is_measured_in_a_straight_line;
     * proyeksi titik tengah memberi km 0,5, jadi 1500 + 400 x 0,5 = 1700.
     */
    public function test_the_emergency_elevation_is_read_only_where_the_hiker_is_on_the_line(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99]];
        $profil = [[0, 1500], [1, 1900]];

        $this->assertSame([1700, null, null, null, null], $this->hitung([
            ['ketinggianDiPosisi', [$garis, $profil, ['lng' => 110.0, 'lat' => -6.995, 'akurasi' => 10]]],
            // Sekitar 298 m di timur garis: di luar jalur.
            ['ketinggianDiPosisi', [$garis, $profil, ['lng' => 110.0027, 'lat' => -6.995, 'akurasi' => 10]]],
            // Akurasi 80 m: letak di garis tidak dapat dipercaya.
            ['ketinggianDiPosisi', [$garis, $profil, ['lng' => 110.0, 'lat' => -6.995, 'akurasi' => 80]]],
            ['ketinggianDiPosisi', [null, $profil, ['lng' => 110.0, 'lat' => -6.995, 'akurasi' => 10]]],
            ['ketinggianDiPosisi', [$garis, null, ['lng' => 110.0, 'lat' => -6.995, 'akurasi' => 10]]],
        ]));
    }

    /**
     * PT7-3a: tanpa posisi baru, kartu darurat menyebut alasannya per keadaan lokasi
     * (usePosisiLapangan); "belum" hanya untuk keadaan yang memang masih menunggu.
     */
    public function test_the_emergency_card_says_why_there_is_no_fresh_position(): void
    {
        $this->assertSame([
            'Posisi belum didapat dari perangkat.',
            'Posisi belum terbaca; perangkat mencoba lagi.',
            'Izin lokasi ditolak, jadi koordinat tidak dapat dibaca. Izinkan lokasi untuk peramban ini di pengaturan perangkat.',
            'Peramban ini tidak dapat membaca lokasi.',
            'Lokasi tidak dibaca selama pendakian dijeda atau belum dimulai.',
        ], $this->hitung([
            ['kalimatPosisiDarurat', ['menunggu']],
            ['kalimatPosisiDarurat', ['galat']],
            ['kalimatPosisiDarurat', ['ditolak']],
            ['kalimatPosisiDarurat', ['tidak-didukung']],
            ['kalimatPosisiDarurat', ['mati']],
        ]));
    }

    /**
     * Ruling W6-7h (I-2 tinjauan): saat kartu masih menampilkan posisi terakhir, keterangannya
     * tentang posisi BARU, karena posisi lama tidak pernah dikosongkan (usePosisiLapangan).
     * Kalimat "tanpa posisi" di atas salah di cabang ini; aktif tidak berketerangan.
     *
     * Kalimat izin dicabut diubah ruling W6-7h2 (tinjauan ulang Tugas 7): "sampai izin diberikan
     * lagi" menjanjikan pemulihan sendiri, padahal pengawas posisi baru dipasang ulang sesudah
     * halaman tersembunyi lalu terlihat.
     */
    public function test_the_emergency_card_keeps_the_last_position_and_says_why_it_is_not_new(): void
    {
        $this->assertSame([
            null,
            'Lokasi tidak diperbarui selama pendakian dijeda. Koordinat di atas adalah posisi terakhir yang terukur.',
            'Izin lokasi dicabut, jadi posisi tidak diperbarui. Koordinat di atas adalah posisi terakhir yang terukur. Izinkan lokasi untuk peramban ini di pengaturan perangkat.',
            'Posisi baru belum terbaca; perangkat mencoba lagi. Koordinat di atas adalah posisi terakhir yang terukur.',
            'Menunggu posisi baru dari perangkat. Koordinat di atas adalah posisi terakhir yang terukur.',
            'Menunggu posisi baru dari perangkat. Koordinat di atas adalah posisi terakhir yang terukur.',
        ], $this->hitung([
            ['kalimatPosisiLama', ['aktif']],
            ['kalimatPosisiLama', ['mati']],
            ['kalimatPosisiLama', ['ditolak']],
            ['kalimatPosisiLama', ['galat']],
            ['kalimatPosisiLama', ['menunggu']],
            ['kalimatPosisiLama', ['tidak-didukung']],
        ]));
    }

    /**
     * PT7-3c dan d: pos terdekat garis lurus hanya dengan akurasi yang cukup tajam (ruling
     * W6-3e), disebut dengan nomornya karena nama pos tidak unik, dan pos tanpa koordinat
     * dilewati. Urutan larik berlawanan dengan jawaban, dan dua pos bernama sama (butir 3).
     */
    public function test_the_nearest_checkpoint_is_named_by_number_and_only_with_sharp_accuracy(): void
    {
        $pos = [
            ['urutan' => 3, 'nama' => 'Puncak', 'lng' => null, 'lat' => null],
            ['urutan' => 2, 'nama' => 'Shelter', 'lng' => 110.0, 'lat' => -6.99],
            ['urutan' => 1, 'nama' => 'Shelter', 'lng' => 110.0, 'lat' => -7.0],
        ];

        // Dua pos di titik yang sama (pos bayangan dan pos utama): seri dipecah urutan, bukan
        // urutan larik.
        $berimpit = [
            ['urutan' => 2, 'nama' => 'Shelter', 'lng' => 110.0, 'lat' => -7.0],
            ['urutan' => 1, 'nama' => 'Gerbang', 'lng' => 110.0, 'lat' => -7.0],
        ];

        // Koordinat null terbaca 0 dalam aritmetika JavaScript, jadi pos tanpa koordinat hanya
        // tampak "terdekat" di dekat titik 0, 0; kasus ini menjaga saringannya.
        $tanpaKoordinat = [
            ['urutan' => 3, 'nama' => 'Puncak', 'lng' => null, 'lat' => null],
            ['urutan' => 1, 'nama' => 'Gerbang', 'lng' => 0.01, 'lat' => 0.01],
        ];

        [$tajam, $kasar, $seri, $tanpa] = $this->hitung([
            ['posTerdekatDarurat', [$pos, ['lng' => 110.0, 'lat' => -6.999, 'akurasi' => 10]]],
            ['posTerdekatDarurat', [$pos, ['lng' => 110.0, 'lat' => -6.999, 'akurasi' => 80]]],
            ['posTerdekatDarurat', [$berimpit, ['lng' => 110.0, 'lat' => -6.999, 'akurasi' => 10]]],
            ['posTerdekatDarurat', [$tanpaKoordinat, ['lng' => 0.0001, 'lat' => 0.0001, 'akurasi' => 10]]],
        ]);

        $this->assertSame(1, $tajam['urutan']);
        $this->assertSame('Shelter', $tajam['nama']);
        $this->assertNull($kasar);
        $this->assertSame(1, $seri['urutan']);
        $this->assertSame(1, $tanpa['urutan']);
    }

    /**
     * PT7-13: ketinggian dan jarak kartu darurat tidak terpotong di antara angka dan satuannya
     * (spasi tak terputus, pola teksJarak dan teksNaik, ruling W6-3g M-5), dan jarak di bawah
     * 50 m tidak ditulis "sekitar < 50 m".
     */
    public function test_emergency_heights_and_distances_keep_number_and_unit_together(): void
    {
        $this->assertSame(
            ["2.395\u{00A0}m", "kurang dari 50\u{00A0}m", "sekitar 300\u{00A0}m", "sekitar 1,3\u{00A0}km"],
            $this->hitung([
                ['teksKetinggian', [2395.4]],
                ['teksSekitar', [30]],
                ['teksSekitar', [320]],
                ['teksSekitar', [1260]],
            ])
        );
    }

    /**
     * Memanggil fungsi hitung.ts di Node dan mengembalikan hasilnya berurutan.
     *
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function hitung(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/hitung.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan hitung.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
