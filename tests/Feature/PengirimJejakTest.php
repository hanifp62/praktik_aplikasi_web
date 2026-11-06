<?php

namespace Tests\Feature;

use App\Services\Trips\PenerimaanJejak;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Kiriman jejak mengikuti persetujuan Profil (ruling W6-P2; sisipan W6-P2 fase A dan B).
 *
 * Keputusan pengirim ada di luring/kirimJejakMurni.ts tanpa impor nilai (ruling L3), jadi diuji
 * di Node dengan bahan tiruan, pola AntreanLapanganTest: tanpa unggahan sebelum server menjawab
 * `kirim: true`, titik dihapus hanya sesudah 2xx, jawaban 403/404/409/422/429 menurut tabel 2.4
 * rancangan, saringan W6-P2e, dua sumber basis data (W6-11e), dan satu putaran per tab. Perekam
 * (jejak.js) dan pemuatnya (dasar.js, ruling W6-P2n) juga dijalankan di Node dengan IndexedDB dan
 * impor tiruan. Sambungan React diperiksa lewat sumbernya.
 */
class PengirimJejakTest extends TestCase
{
    use SumberReact;

    /**
     * Bahan alur tiruan: basis data di memori ({nama: titik[]}, milik lebih dulu), log permintaan
     * dan penghapusan, jam yang dapat digeser.
     */
    private const BAHAN = <<<'JS'
        const titikDi = (sesi, menit, lain = {}) => ({ sesi, recorded_at: new Date(Date.UTC(2026, 8, 15, 4, menit)).toISOString(), latitude: -7.45, longitude: 110.44, elevation_m: 1200, accuracy_m: 8, ...lain });
        const buatBahan = (opsi) => {
            const db = opsi.db;
            const log = [];
            let jam = 1_000_000;
            const ingatan = opsi.ingatan ?? murni.buatIngatan();
            const bahan = {
                basisData: async () => Object.keys(db),
                daftarSesi: async (n) => [...new Set(db[n].map((t) => t.sesi))].sort((a, b) => a - b),
                bacaKelompok: async (n, s, b) => db[n].filter((t) => t.sesi === s).sort((a, c) => (a.recorded_at < c.recorded_at ? -1 : 1)).slice(0, b),
                hapusKunci: async (n, kunci) => {
                    log.push('hapus ' + n + ' ' + kunci.length);
                    db[n] = db[n].filter((t) => !kunci.some(([s, w]) => s === t.sesi && w === t.recorded_at));
                },
                sisa: async (s) => Object.values(db).flat().filter((t) => t.sesi === s).length,
                periksa: async (s) => {
                    log.push('GET ' + s);
                    return opsi.periksa(s);
                },
                unggah: async (s, titik) => {
                    log.push('POST ' + s + ' ' + titik.length + ' ada ' + Object.values(db).flat().filter((t) => t.sesi === s).length);
                    (opsi.isi ??= []).push(titik);
                    return opsi.unggah(s, titik);
                },
                ingatan,
                sekarang: () => jam,
                masihBoleh: () => true,
                kabar: (s, sisa, diterima) => log.push('kabar ' + s + ' sisa ' + sisa + ' diterima ' + diterima),
            };
            return { bahan, log, db, ingatan, geser: (ms) => { jam += ms; }, opsi };
        };
        const ya = async () => ({ status: 200, kirim: true });
        const ok = async () => ({ status: 200 });
        JS;

    /**
     * Tanpa jawaban `kirim: true` tidak ada satu titik pun yang diunggah (ruling W6-P2b): jawaban
     * "belum berakhir", "pendakian lain", atau "rekam mati" menahan sesi, dan titiknya tetap di
     * perangkat. Keputusan itu diingat (tanpa pertanyaan ulang) sampai dilupakan.
     */
    public function test_nothing_is_uploaded_before_the_server_says_yes(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            let jawab = { status: 200, kirim: false };
            const t = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0), titikDi(7, 1)] }, periksa: async () => jawab, unggah: ok });
            const pertama = await murni.alurKirimJejak(t.bahan);
            const logTahan = t.log.slice();
            await murni.alurKirimJejak(t.bahan);
            const logDiingat = t.log.slice();
            t.ingatan.lupakanSementara();
            jawab = { status: 200, kirim: true };
            const kedua = await murni.alurKirimJejak(t.bahan);
            process.stdout.write(JSON.stringify({ pertama, logTahan, logDiingat, kedua, log: t.log, sisa: t.db['jejak-pendakian-k1'].length }));
            JS);

        $this->assertSame(['hasil' => 'selesai'], $hasil['pertama']);
        $this->assertSame(['GET 7'], $hasil['logTahan'], 'Tanpa kirim: true tidak ada POST.');
        $this->assertSame(['GET 7'], $hasil['logDiingat'], 'Keputusan "belum sekarang" diingat tanpa bertanya ulang.');
        $this->assertSame(['hasil' => 'selesai'], $hasil['kedua']);
        $this->assertSame(['GET 7', 'GET 7', 'POST 7 2 ada 2', 'hapus jejak-pendakian-k1 2', 'kabar 7 sisa 0 diterima 2'], $hasil['log']);
        $this->assertSame(0, $hasil['sisa']);
    }

    /**
     * Titik dihapus dari perangkat hanya sesudah server menerima kelompoknya (I4). Galat jaringan,
     * 401, 419, dan 5xx menghentikan putaran tanpa menghapus dan tanpa mengingat apa pun, jadi
     * putaran berikutnya bertanya lagi.
     */
    public function test_points_leave_the_device_only_after_a_2xx(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const hasil = {};
            for (const status of [null, 401, 419, 500, 503]) {
                const t = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0)] }, periksa: ya, unggah: async () => ({ status }) });
                const putaran = await murni.alurKirimJejak(t.bahan);
                await murni.alurKirimJejak(t.bahan);
                hasil[String(status)] = { putaran, sisa: t.db['jejak-pendakian-k1'].length, get: t.log.filter((l) => l.startsWith('GET')).length };
            }
            const t = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0)] }, periksa: ya, unggah: ok });
            await murni.alurKirimJejak(t.bahan);
            hasil.urutan = t.log;
            process.stdout.write(JSON.stringify(hasil));
            JS);

        foreach (['null', '401', '419', '500', '503'] as $status) {
            $this->assertSame(['hasil' => 'berhenti'], $hasil[$status]['putaran'], $status);
            $this->assertSame(1, $hasil[$status]['sisa'], "Jawaban {$status} tidak menghapus titik.");
            $this->assertSame(2, $hasil[$status]['get'], "Jawaban {$status} tidak diingat; putaran berikutnya bertanya lagi.");
        }

        $this->assertSame(['GET 7', 'POST 7 1 ada 1', 'hapus jejak-pendakian-k1 1', 'kabar 7 sisa 0 diterima 1'], $hasil['urutan'], 'Hapus sesudah jawaban 2xx, bukan sebelum unggahan.');
    }

    /**
     * 403 untuk pertanyaan berarti sesi akun lain (basis data lama, W6-11e): diingat sampai muat
     * penuh, jadi tidak ditanyakan lagi, dan titiknya tetap di perangkat. 404 sama.
     */
    public function test_a_403_or_404_question_is_remembered_for_the_tab(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const hasil = {};
            for (const status of [403, 404]) {
                const t = buatBahan({ db: { 'jejak-pendakian': [titikDi(99, 0)] }, periksa: async () => ({ status }), unggah: ok });
                await murni.alurKirimJejak(t.bahan);
                t.ingatan.lupakanSementara();
                t.geser(10 * 60 * 1000);
                await murni.alurKirimJejak(t.bahan);
                hasil[status] = { log: t.log, sisa: t.db['jejak-pendakian'].length };
            }
            process.stdout.write(JSON.stringify(hasil));
            JS);

        foreach (['403', '404'] as $status) {
            $this->assertSame(['GET 99'], $hasil[$status]['log'], "{$status} ditanyakan sekali per tab, tanpa POST.");
            $this->assertSame(1, $hasil[$status]['sisa']);
        }
    }

    /**
     * Tabel 2.4 untuk unggahan: 409 dan 403 (perekaman baru dimatikan) menahan sesi sementara,
     * yaitu sampai dilupakan atau lima menit; 422 menahannya sampai muat penuh; 429 menjeda tab
     * selama Retry-After (bawaan 60 detik). Titiknya tetap di perangkat di setiap cabang.
     */
    public function test_upload_refusals_follow_the_answer_table(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const hasil = {};
            for (const status of [409, 403, 422]) {
                const t = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0)] }, periksa: ya, unggah: async () => ({ status }) });
                await murni.alurKirimJejak(t.bahan);
                await murni.alurKirimJejak(t.bahan);
                const sebelumLupa = t.log.filter((l) => l.startsWith('GET')).length;
                t.ingatan.lupakanSementara();
                await murni.alurKirimJejak(t.bahan);
                const sesudahLupa = t.log.filter((l) => l.startsWith('GET')).length;
                t.geser(murni.INGAT_SEMENTARA_MS + 1);
                await murni.alurKirimJejak(t.bahan);
                hasil[status] = { sebelumLupa, sesudahLupa, sesudahLimaMenit: t.log.filter((l) => l.startsWith('GET')).length, sisa: t.db['jejak-pendakian-k1'].length };
            }
            const j = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0)] }, periksa: ya, unggah: async () => ({ status: 429, retryAfterMs: 30000 }) });
            hasil.jedaUnggah = await murni.alurKirimJejak(j.bahan);
            const g = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(7, 0)] }, periksa: async () => ({ status: 429, retryAfterMs: null }), unggah: ok });
            hasil.jedaTanya = await murni.alurKirimJejak(g.bahan);
            hasil.sisaJeda = [j.db['jejak-pendakian-k1'].length, g.db['jejak-pendakian-k1'].length];
            process.stdout.write(JSON.stringify(hasil));
            JS);

        $this->assertSame(['sebelumLupa' => 1, 'sesudahLupa' => 2, 'sesudahLimaMenit' => 3, 'sisa' => 1], $hasil['409']);
        $this->assertSame(['sebelumLupa' => 1, 'sesudahLupa' => 2, 'sesudahLimaMenit' => 3, 'sisa' => 1], $hasil['403']);
        $this->assertSame(['sebelumLupa' => 1, 'sesudahLupa' => 1, 'sesudahLimaMenit' => 1, 'sisa' => 1], $hasil['422'], '422 tidak diulang terus di tab ini.');
        $this->assertSame(['hasil' => 'jeda', 'ms' => 30000], $hasil['jedaUnggah']);
        $this->assertSame(['hasil' => 'jeda', 'ms' => 60000], $hasil['jedaTanya']);
        $this->assertSame([1, 1], $hasil['sisaJeda']);
    }

    /**
     * Tabel jawaban lengkap, satu baris per status dan tahap.
     */
    public function test_every_status_maps_to_its_action(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], <<<'JS'
            const kasus = [null, 200, 201, 204, 401, 403, 404, 409, 419, 422, 429, 400, 500, 503];
            process.stdout.write(JSON.stringify(kasus.map((s) => [String(s), murni.tindakanUntuk(s, 'periksa'), murni.tindakanUntuk(s, 'unggah')])));
            JS);

        $this->assertSame([
            ['null', 'berhenti', 'berhenti'],
            ['200', 'lanjut', 'lanjut'],
            ['201', 'lanjut', 'lanjut'],
            ['204', 'lanjut', 'lanjut'],
            ['401', 'berhenti', 'berhenti'],
            ['403', 'tahan-tab', 'tahan-sementara'],
            ['404', 'tahan-tab', 'tahan-tab'],
            ['409', 'tahan-sementara', 'tahan-sementara'],
            ['419', 'berhenti', 'berhenti'],
            ['422', 'tahan-tab', 'tahan-tab'],
            ['429', 'jeda', 'jeda'],
            ['400', 'tahan-tab', 'tahan-tab'],
            ['500', 'berhenti', 'berhenti'],
            ['503', 'berhenti', 'berhenti'],
        ], $hasil);
    }

    /**
     * Saringan W6-P2e: titik yang tidak pernah diterima server (lintang atau bujur di luar batas,
     * akurasi di atas 10.000 m, waktu yang tidak terbaca) tidak dikirim, dan dihapus bersama
     * kelompoknya sesudah kelompok itu diterima. Ketinggian di luar rentang dikirim null, bukan
     * dijepit (R-007). Kelompok yang seluruhnya tak sah dihapus tanpa unggahan.
     */
    public function test_invalid_points_never_block_the_session(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const db = { 'jejak-pendakian-k1': [
                titikDi(7, 0),
                titikDi(7, 1, { latitude: 95 }),
                titikDi(7, 2, { longitude: -181 }),
                titikDi(7, 3, { accuracy_m: 10001 }),
                titikDi(7, 4, { elevation_m: 9500 }),
                titikDi(7, 5, { elevation_m: null, accuracy_m: null }),
                { ...titikDi(7, 6), recorded_at: 'bukan waktu' },
            ] };
            const t = buatBahan({ db, periksa: ya, unggah: ok });
            await murni.alurKirimJejak(t.bahan);
            const semuaTakSah = buatBahan({ db: { 'jejak-pendakian-k1': [titikDi(8, 0, { latitude: 91 }), titikDi(8, 1, { accuracy_m: 20000 })] }, periksa: ya, unggah: ok });
            await murni.alurKirimJejak(semuaTakSah.bahan);
            process.stdout.write(JSON.stringify({ isi: t.opsi.isi, log: t.log, sisa: t.db['jejak-pendakian-k1'].length, logTakSah: semuaTakSah.log, sisaTakSah: semuaTakSah.db['jejak-pendakian-k1'].length, batas: murni.BATAS_NILAI }));
            JS);

        $this->assertCount(1, $hasil['isi']);
        $this->assertSame(
            [[-7.45, 1200, 8], [-7.45, null, 8], [-7.45, null, null]],
            array_map(fn (array $t) => [$t['latitude'], $t['elevation_m'], $t['accuracy_m']], $hasil['isi'][0]),
        );
        $this->assertSame(['recorded_at', 'latitude', 'longitude', 'elevation_m', 'accuracy_m'], array_keys($hasil['isi'][0][0]), 'Isi kiriman tanpa id sesi.');
        $this->assertSame(['GET 7', 'POST 7 3 ada 7', 'hapus jejak-pendakian-k1 7', 'kabar 7 sisa 0 diterima 3'], $hasil['log']);
        $this->assertSame(0, $hasil['sisa']);
        $this->assertSame(['GET 8', 'hapus jejak-pendakian-k1 2', 'kabar 8 sisa 0 diterima 0'], $hasil['logTakSah'], 'Kelompok yang seluruhnya tak sah dihapus tanpa unggahan.');
        $this->assertSame(0, $hasil['sisaTakSah']);

        // Batas klien sama dengan batas server (pola JENDELA_LAPORAN).
        $this->assertSame([
            'lintang' => 90,
            'bujur' => 180,
            'akurasiMaksM' => PenerimaanJejak::AKURASI_MAKS_M,
            'tinggiMinM' => PenerimaanJejak::TINGGI_MIN_M,
            'tinggiMaksM' => PenerimaanJejak::TINGGI_MAKS_M,
        ], $hasil['batas']);
    }

    /**
     * Dua sumber (ruling W6-11e): basis data milik lebih dulu, lalu basis data lama. Sesi yang ada
     * di keduanya ditanyakan sekali per putaran; sesi akun lain di basis data lama dijawab 403 dan
     * titiknya tidak pernah diunggah (I7). Kelompok paling banyak 500 titik terlama lebih dulu,
     * dengan sisa yang dikabarkan sesudah setiap kelompok.
     */
    public function test_both_stores_are_sent_per_session_in_groups(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const milik = [];
            for (let i = 1100; i >= 0; i--) milik.push({ ...titikDi(7, 0), recorded_at: new Date(Date.UTC(2026, 8, 15, 4, 0, 0) + i * 5000).toISOString() });
            const db = { 'jejak-pendakian-k1': milik, 'jejak-pendakian': [titikDi(7, 0, { recorded_at: '2026-09-14T03:00:00.123Z' }), titikDi(99, 0)] };
            const t = buatBahan({ db, periksa: async (s) => (s === 99 ? { status: 403 } : { status: 200, kirim: true }), unggah: ok });
            await murni.alurKirimJejak(t.bahan);
            process.stdout.write(JSON.stringify({ log: t.log, pertama: t.opsi.isi[0][0].recorded_at, terakhir: t.opsi.isi[2].at(-1).recorded_at, milik: t.db['jejak-pendakian-k1'].length, lama: t.db['jejak-pendakian'].map((p) => p.sesi), batas: murni.BATAS_TITIK }));
            JS);

        $this->assertSame(500, $hasil['batas']);
        $this->assertSame([
            'GET 7',
            'POST 7 500 ada 1102',
            'hapus jejak-pendakian-k1 500',
            'kabar 7 sisa 602 diterima 500',
            'POST 7 500 ada 602',
            'hapus jejak-pendakian-k1 500',
            'kabar 7 sisa 102 diterima 500',
            'POST 7 101 ada 102',
            'hapus jejak-pendakian-k1 101',
            'kabar 7 sisa 1 diterima 101',
            'POST 7 1 ada 1',
            'hapus jejak-pendakian 1',
            'kabar 7 sisa 0 diterima 1',
            'GET 99',
        ], $hasil['log']);
        $this->assertSame('2026-09-15T04:00:00.000Z', $hasil['pertama'], 'Kelompok pertama berisi titik terlama, walau fixture disusun terbalik.');
        $this->assertSame('2026-09-15T05:31:40.000Z', $hasil['terakhir']);
        $this->assertSame(0, $hasil['milik']);
        $this->assertSame([99], $hasil['lama'], 'Titik sesi akun lain tetap di perangkat sampai keluar.');
    }

    /**
     * Sesi yang ditandai dihapus pendaki (ringkasan, W6-P2h) tidak dikirim lagi dari tab ini, juga
     * di tengah unggahan berkelompok. Halaman AppLayout yang dilepas (pindah ke mode lapangan)
     * menghentikan putaran sebelum permintaan berikutnya.
     */
    public function test_a_deleted_session_and_an_unmounted_page_stop_the_upload(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], self::BAHAN.<<<'JS'
            const banyak = () => Array.from({ length: 1200 }, (_, i) => ({ ...titikDi(7, 0), recorded_at: new Date(Date.UTC(2026, 8, 15, 4) + i * 5000).toISOString() }));
            const t = buatBahan({ db: { 'jejak-pendakian-k1': banyak() }, periksa: ya, unggah: async () => { t.ingatan.tandaiDihapus(7); return { status: 200 }; } });
            await murni.alurKirimJejak(t.bahan);
            await murni.alurKirimJejak(t.bahan);
            let terpasang = true;
            const u = buatBahan({ db: { 'jejak-pendakian-k1': banyak() }, periksa: ya, unggah: async () => { terpasang = false; return { status: 200 }; } });
            u.bahan.masihBoleh = () => terpasang;
            const lepas = await murni.alurKirimJejak(u.bahan);
            process.stdout.write(JSON.stringify({ log: t.log, logLepas: u.log, lepas }));
            JS);

        $this->assertSame(['GET 7', 'POST 7 500 ada 1200', 'hapus jejak-pendakian-k1 500', 'kabar 7 sisa 700 diterima 500'], $hasil['log']);
        $this->assertSame(['hasil' => 'berhenti'], $hasil['lepas']);
        $this->assertSame(['GET 7', 'POST 7 500 ada 1200', 'hapus jejak-pendakian-k1 500', 'kabar 7 sisa 700 diterima 500'], $hasil['logLepas']);
    }

    /**
     * Kalimat baris perangkat ringkasan (rancangan W6-P2 bagian 4, butir 2), satu kasus per
     * cabang, persis: titik masih di perangkat dengan rekam menyala, dengan pendakian lain, dengan
     * rekam mati (rekam mati didahulukan); sisa nol sesudah diterima di halaman ini; sesudah
     * dimuat ulang dengan dan tanpa peta jejak (ruling W6-P2p); ketiga hasil hapus di Memount (ruling
     * W6-P2o); hapus di perangkat yang ditolak; dan kosong bila tidak ada titik atau titik tidak
     * terbaca. Angka ditulis id-ID dengan spasi tak terputus sebelum "titik".
     *
     * Diubah putaran perbaikan 1 W6-P2 dengan alasan: kalimat "Jejak pendakian ini dihapus dari
     * perangkat ini." dibuang, karena hapus kini selalu sampai ke Memount; hasil DELETE yang diterima
     * tidak membuat baris mengklaim apa pun (kabar server yang berbicara), dan yang gagal berkata
     * "belum pasti".
     *
     * Diubah putaran residu W6-FR3 (N-3, temuan pemeriksa final-fix-check): kiriman DELETE yang
     * dijawab 401 atau 419 memakai kalimat cabang sesi (mengajak masuk lagi), bukan kalimat W6-P2o
     * (mengajak mengetuk lagi "saat ada sinyal", padahal sinyal sudah ada). Mutasi yang menyamakan
     * cabang sesi dengan cabang jaringan/status lain membuat dua kasus sesiBerakhir:true di bawah
     * ini merah.
     */
    public function test_the_device_row_sentence_is_exact_in_every_branch(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], <<<'JS'
            const dasar = { jumlah: 1234, rekamJejak: true, pendakianLain: false, diterimaDiHalaman: false, dimuatUlang: false, jejakTampil: false, hapus: null, sesiBerakhir: false, gagalHapus: false };
            const k = (ubah) => murni.kalimatJejakPerangkat({ ...dasar, ...ubah });
            process.stdout.write(JSON.stringify({
                kalimat: [
                    k({}),
                    k({ pendakianLain: true }),
                    k({ rekamJejak: false }),
                    k({ rekamJejak: false, pendakianLain: true }),
                    k({ jumlah: 0, diterimaDiHalaman: true }),
                    k({ jumlah: 0, diterimaDiHalaman: true, dimuatUlang: true, jejakTampil: true }),
                    k({ jumlah: 0, diterimaDiHalaman: true, dimuatUlang: true, jejakTampil: false }),
                    k({ jumlah: 0, hapus: murni.hasilHapusJejak(false, true) }),
                    k({ jumlah: 0, hapus: murni.hasilHapusJejak(false, false) }),
                    k({ jumlah: 0, hapus: murni.hasilHapusJejak(false, true), sesiBerakhir: true }),
                    k({ jumlah: 0, hapus: murni.hasilHapusJejak(false, false), sesiBerakhir: true }),
                    k({ jumlah: 0, diterimaDiHalaman: true, hapus: murni.hasilHapusJejak(true, true) }),
                    k({ gagalHapus: true }),
                    k({ jumlah: 0 }),
                    k({ jumlah: null }),
                    k({ jumlah: null, diterimaDiHalaman: true }),
                    k({ jumlah: 1 }),
                ],
                hasil: [murni.hasilHapusJejak(true, true), murni.hasilHapusJejak(true, false), murni.hasilHapusJejak(false, true), murni.hasilHapusJejak(false, false)],
            }));
            JS);

        $this->assertSame(['terhapus', 'terhapus', 'gagal-sesudah-perangkat', 'gagal-tanpa-perangkat'], $hasil['hasil']);
        $this->assertSame([
            "1.234\u{a0}titik jejak pendakian ini masih di perangkat ini dan belum dipastikan terkirim ke Memount. Pengiriman dicoba lagi saat ada sinyal.",
            "1.234\u{a0}titik jejak pendakian ini masih di perangkat ini. Titik itu baru dikirim sesudah pendakian yang sedang berlangsung selesai atau dibatalkan.",
            "1.234\u{a0}titik jejak pendakian ini masih di perangkat ini dan tidak diterima Memount selama perekaman jejak mati. Nyalakan lagi di Profil untuk mengirimnya, atau hapus dari perangkat ini.",
            "1.234\u{a0}titik jejak pendakian ini masih di perangkat ini dan tidak diterima Memount selama perekaman jejak mati. Nyalakan lagi di Profil untuk mengirimnya, atau hapus dari perangkat ini.",
            'Rekaman jejak pendakian ini sudah diterima Memount.',
            'Ringkasan sudah dimuat ulang dengan rekaman jejak yang terkirim.',
            'Rekaman jejak pendakian ini sudah diterima Memount, tetapi kurang dari dua titik, jadi belum dapat digambar di peta.',
            'Jejak pendakian ini dihapus dari perangkat ini, tetapi belum pasti terhapus dari Memount. Ketuk Hapus jejak ini lagi saat ada sinyal.',
            'Jejak pendakian ini belum pasti terhapus dari Memount. Ketuk Hapus jejak ini lagi saat ada sinyal.',
            'Jejak pendakian ini dihapus dari perangkat ini, tetapi belum pasti terhapus dari Memount karena sesi Anda berakhir. Masuk lagi, lalu ketuk Hapus jejak ini.',
            'Jejak pendakian ini belum pasti terhapus dari Memount karena sesi Anda berakhir. Masuk lagi, lalu ketuk Hapus jejak ini.',
            '',
            'Jejak pendakian ini belum dapat dihapus seluruhnya dari perangkat ini. Coba lagi.',
            '',
            '',
            '',
            "1\u{a0}titik jejak pendakian ini masih di perangkat ini dan belum dipastikan terkirim ke Memount. Pengiriman dicoba lagi saat ada sinyal.",
        ], $hasil['kalimat']);
    }

    /**
     * Gerbang pengirim (ruling W6-P2a, W6-11d, W6-11g): setiap syarat yang salah menahan putaran,
     * termasuk halaman AppLayout yang tidak terpasang (mode lapangan dan shell luring, I6).
     *
     * Diperluas putaran perbaikan 1 W6-P2 (ruling W6-P2q, tinjauan m-2): syarat terpasang dulu
     * ditulis di kirimJejak.ts dan tidak dijaga test (mutan Xb selamat).
     */
    public function test_the_sender_starts_only_through_its_gate(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], <<<'JS'
            const dasar = { terpasang: true, adaPerangkat: true, pendakianAktif: false, daring: true, kunciTertutup: false, propsDariServer: true };
            const hasil = { semua: murni.bolehMulai(dasar) };
            for (const [kunci, nilai] of [['terpasang', false], ['adaPerangkat', false], ['pendakianAktif', true], ['daring', false], ['kunciTertutup', true], ['propsDariServer', false]]) {
                hasil[kunci] = murni.bolehMulai({ ...dasar, [kunci]: nilai });
            }
            process.stdout.write(JSON.stringify(hasil));
            JS);

        $this->assertSame(['semua' => true, 'terpasang' => false, 'adaPerangkat' => false, 'pendakianAktif' => false, 'daring' => false, 'kunciTertutup' => false, 'propsDariServer' => false], $hasil);
    }

    /**
     * Syarat sebelum setiap permintaan di tengah putaran (ruling W6-P2q, tinjauan m-2): halaman
     * AppLayout masih terpasang (pindah ke mode lapangan menghentikan putaran, I6), kunci halaman
     * masih kunci putaran, tanpa pendakian berlangsung (I1), daring, dan kunci belum ditutup. Props
     * pulihan riwayat bukan syarat di sini, karena tombol Kembali tidak mengganti sesi masuk.
     */
    public function test_a_running_round_continues_only_while_its_page_still_allows_it(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], <<<'JS'
            const dasar = { terpasang: true, kunciPutaran: 'k1', kunciHalaman: 'k1', pendakianAktif: false, daring: true, kunciTertutup: false };
            const hasil = { semua: murni.bolehLanjut(dasar) };
            for (const [kunci, nilai] of [['terpasang', false], ['kunciHalaman', 'k2'], ['kunciHalaman', null], ['pendakianAktif', true], ['daring', false], ['kunciTertutup', true]]) {
                hasil[kunci + ':' + String(nilai)] = murni.bolehLanjut({ ...dasar, [kunci]: nilai });
            }
            process.stdout.write(JSON.stringify(hasil));
            JS);

        $this->assertSame([
            'semua' => true,
            'terpasang:false' => false,
            'kunciHalaman:k2' => false,
            'kunciHalaman:null' => false,
            'pendakianAktif:true' => false,
            'daring:false' => false,
            'kunciTertutup:true' => false,
        ], $hasil);
    }

    /**
     * Satu putaran per tab: pemicu selama putaran berjalan menjalankan satu putaran lagi
     * sesudahnya (bukan dua bersamaan), gerbang dibaca setiap kali, dan 429 menjeda tab sampai
     * Retry-After, lalu mencoba sekali.
     */
    public function test_one_round_runs_at_a_time_per_tab(): void
    {
        $hasil = $this->node(['murni' => 'luring/kirimJejakMurni.ts'], <<<'JS'
            let jalan = 0;
            let selesaikan = [];
            let hasilBerikut = { hasil: 'selesai' };
            let gerbang = true;
            let jam = 0;
            const terjadwal = [];
            const putaran = murni.buatPutaran({
                jalankan: () => { jalan++; return new Promise((r) => selesaikan.push(() => r(hasilBerikut))); },
                gerbang: () => gerbang,
                sekarang: () => jam,
                jadwalkan: (f, ms) => terjadwal.push({ f, ms }),
            });
            const tunggu = () => new Promise((r) => setImmediate(r));
            const mulai = [putaran.coba(), putaran.coba(), putaran.coba()];
            const jalanAwal = jalan;
            const ditunggu = putaran.tunggu();
            selesaikan.shift()();
            await ditunggu;
            await tunggu();
            const jalanSesudahLagi = jalan;
            selesaikan.shift()();
            await tunggu(); await tunggu();
            gerbang = false;
            const tertutup = putaran.coba();
            gerbang = true;
            hasilBerikut = { hasil: 'jeda', ms: 30000 };
            putaran.coba();
            selesaikan.shift()();
            await tunggu(); await tunggu();
            const selamaJeda = putaran.coba();
            const jalanSelamaJeda = jalan;
            hasilBerikut = { hasil: 'selesai' };
            jam = 30000;
            terjadwal.shift().f();
            const jalanSesudahJeda = jalan;
            process.stdout.write(JSON.stringify({ mulai, jalanAwal, jalanSesudahLagi, tertutup, selamaJeda, jalanSelamaJeda, jalanSesudahJeda, sisaJadwal: terjadwal.length }));
            JS);

        $this->assertSame([true, false, false], $hasil['mulai']);
        $this->assertSame(1, $hasil['jalanAwal'], 'Pemicu selama putaran berjalan tidak memulai putaran kedua bersamaan.');
        $this->assertSame(2, $hasil['jalanSesudahLagi'], 'Pemicu yang tertunda menjalankan satu putaran lagi sesudahnya.');
        $this->assertFalse($hasil['tertutup']);
        $this->assertFalse($hasil['selamaJeda'], 'Selama jeda 429 tidak ada putaran baru.');
        $this->assertSame(3, $hasil['jalanSelamaJeda']);
        $this->assertSame(4, $hasil['jalanSesudahJeda'], 'Sesudah Retry-After, satu percobaan ulang.');
        $this->assertSame(0, $hasil['sisaJadwal']);
    }

    /**
     * Perekam (ruling W6-P2c, W6-P2d): waktu rekam dibulatkan ke bawah ke detik, dan posisi yang
     * datang kurang dari lima detik sesudah titik terakhir sesi itu tidak disimpan. Waktu yang
     * mundur tidak menahan perekam.
     */
    public function test_the_recorder_rounds_to_the_second_and_keeps_one_point_per_five_seconds(): void
    {
        $hasil = $this->node(['jejak' => 'jejak.js'], <<<'JS'
            const disimpan = [];
            let panggil = null;
            Object.defineProperty(globalThis, 'navigator', { value: { geolocation: { watchPosition: (f) => { panggil = f; return 1; }, clearWatch() {} } }, configurable: true, writable: true });
            globalThis.indexedDB = {
                open() {
                    const r = {};
                    queueMicrotask(() => {
                        r.result = { objectStoreNames: { contains: () => true }, close() {}, transaction: () => ({ objectStore: () => ({ put: (t) => disimpan.push(t.recorded_at) }) }) };
                        r.onsuccess?.();
                    });
                    return r;
                },
            };
            jejak.aturPengguna('k1');
            const berhenti = jejak.mulaiMerekam(5);
            const dasar = Date.UTC(2026, 8, 15, 4, 0, 0);
            for (const ms of [400, 1600, 4999, 5200, 9400, 10000, 10400, -60000 + 300, -54000]) {
                panggil({ timestamp: dasar + ms, coords: { latitude: -7.45, longitude: 110.44, altitude: 1200.4, accuracy: 7.6 } });
                await new Promise((r) => setImmediate(r));
            }
            berhenti();
            process.stdout.write(JSON.stringify({ disimpan, selang: jejak.SELANG_TITIK_MS }));
            JS);

        $this->assertSame(5000, $hasil['selang']);
        $this->assertSame([
            '2026-09-15T04:00:00.000Z',
            '2026-09-15T04:00:05.000Z',
            '2026-09-15T04:00:10.000Z',
            '2026-09-15T03:59:00.000Z',
            '2026-09-15T03:59:06.000Z',
        ], $hasil['disimpan']);
    }

    /**
     * Fungsi berbasis nama tidak pernah membuat basis data (ruling W6-P2g): tanpa
     * indexedDB.databases(), basis data yang belum ada dibuka tanpa versi dan transaksi upgrade-nya
     * dibatalkan, jadi basis data jejak lama tidak dibuat. Dengan databases(), tidak ada pembukaan.
     */
    public function test_name_based_reads_never_create_a_database(): void
    {
        $hasil = $this->node(['jejak' => 'jejak.js'], <<<'JS'
            const ada = new Set(['jejak-pendakian-k1']);
            const dibuat = [];
            const dibuka = [];
            globalThis.IDBKeyRange = { bound: (bawah, atas) => ({ bawah, atas }) };
            globalThis.indexedDB = {
                open(nama, versi) {
                    dibuka.push(nama + (versi === undefined ? '' : '@' + versi));
                    const r = {};
                    queueMicrotask(() => {
                        if (!ada.has(nama)) {
                            let dibatalkan = false;
                            r.transaction = { abort() { dibatalkan = true; } };
                            r.result = { objectStoreNames: { contains: () => false } };
                            r.onupgradeneeded?.();
                            if (dibatalkan) {
                                r.error = new Error('AbortError');
                                r.onerror?.({ preventDefault() {} });
                                return;
                            }
                            ada.add(nama);
                            dibuat.push(nama);
                        }
                        r.result = {
                            objectStoreNames: { contains: () => true },
                            close() {},
                            transaction: () => ({ objectStore: () => ({
                                getAllKeys() { const q = {}; queueMicrotask(() => { q.result = [[7, 'b'], [3, 'a'], [7, 'a']]; q.onsuccess?.(); }); return q; },
                                count() { const q = {}; queueMicrotask(() => { q.result = 2; q.onsuccess?.(); }); return q; },
                                getAll(rentang, batas) { const q = {}; queueMicrotask(() => { q.result = [{ recorded_at: '2026-09-15T04:00:10.000Z' }, { recorded_at: '2026-09-15T04:00:00.000Z' }, { recorded_at: '2026-09-15T04:00:05.000Z' }].slice(0, batas ?? 3); q.onsuccess?.(); }); return q; },
                            }) }),
                        };
                        r.onsuccess?.();
                    });
                    return r;
                },
            };
            const tanpaDaftar = {
                adaLama: await jejak.adaBasisDataJejak('jejak-pendakian'),
                sesiLama: await jejak.daftarSesi('jejak-pendakian'),
                jumlahLama: await jejak.jumlahTitikSesi('jejak-pendakian', 7),
                bacaLama: await jejak.bacaTitikSesi('jejak-pendakian', 7, 500),
                adaMilik: await jejak.adaBasisDataJejak('jejak-pendakian-k1'),
                sesiMilik: await jejak.daftarSesi('jejak-pendakian-k1'),
                jumlahMilik: await jejak.jumlahTitikSesi('jejak-pendakian-k1', 7),
                bacaMilik: (await jejak.bacaTitikSesi('jejak-pendakian-k1', 7)).map((t) => t.recorded_at),
            };
            const bukaTanpaDaftar = dibuka.slice();
            globalThis.indexedDB.databases = async () => [{ name: 'jejak-pendakian-k1', version: 1 }];
            const denganDaftar = [await jejak.adaBasisDataJejak('jejak-pendakian'), await jejak.adaBasisDataJejak('jejak-pendakian-k1')];
            process.stdout.write(JSON.stringify({ tanpaDaftar, bukaTanpaDaftar, denganDaftar, bukaDenganDaftar: dibuka.length - bukaTanpaDaftar.length, dibuat }));
            JS);

        $this->assertSame([
            'adaLama' => false,
            'sesiLama' => [],
            'jumlahLama' => 0,
            'bacaLama' => [],
            'adaMilik' => true,
            'sesiMilik' => [3, 7],
            'jumlahMilik' => 2,
            'bacaMilik' => ['2026-09-15T04:00:00.000Z', '2026-09-15T04:00:05.000Z', '2026-09-15T04:00:10.000Z'],
        ], $hasil['tanpaDaftar']);
        $this->assertSame([], $hasil['dibuat'], 'Tidak ada basis data yang dibuat.');
        $this->assertNotContains('jejak-pendakian@1', $hasil['bukaTanpaDaftar'], 'Pembukaan tanpa versi, bukan pembukaan perekam yang membuat.');
        $this->assertSame([false, true], $hasil['denganDaftar']);
        $this->assertSame(0, $hasil['bukaDenganDaftar'], 'Dengan indexedDB.databases(), keberadaan dibaca tanpa membuka.');
    }

    /**
     * Penghapusan titik selesai sesudah transaksinya tersimpan (oncomplete), bukan saat
     * permintaannya diterima; transaksi yang batal menolak, jadi pengirim tidak mengabarkan sisa
     * yang salah.
     */
    public function test_deleting_points_waits_for_the_transaction_to_commit(): void
    {
        $hasil = $this->node(['jejak' => 'jejak.js'], <<<'JS'
            const transaksi = [];
            globalThis.IDBKeyRange = { bound: (bawah, atas) => ({ bawah, atas }) };
            globalThis.indexedDB = {
                databases: async () => [{ name: 'jejak-pendakian-k1' }],
                open() {
                    const r = {};
                    queueMicrotask(() => {
                        r.result = { objectStoreNames: { contains: () => true }, close() {}, onversionchange: null, transaction: () => {
                            const t = { dihapus: [], objectStore: () => ({ delete: (k) => t.dihapus.push(k) }) };
                            transaksi.push(t);
                            return t;
                        } };
                        r.onsuccess?.();
                    });
                    return r;
                },
            };
            const tunggu = () => new Promise((r) => setImmediate(r));
            let selesai = false;
            const janji = jejak.hapusTitikKunci('jejak-pendakian-k1', [[7, 'a'], [7, 'b']]).then(() => { selesai = true; });
            await tunggu(); await tunggu();
            const sebelum = selesai;
            const dihapus = transaksi[0].dihapus;
            transaksi[0].oncomplete();
            await janji;
            let batal = null;
            const janjiBatal = jejak.hapusTitikSesi('jejak-pendakian-k1', 7).catch((e) => { batal = e.message; });
            await tunggu(); await tunggu();
            const rentang = transaksi[1].dihapus;
            transaksi[1].error = new Error('QuotaExceededError');
            transaksi[1].onabort();
            await janjiBatal;
            const kosong = await jejak.hapusTitikKunci('jejak-pendakian-k1', []);
            process.stdout.write(JSON.stringify({ sebelum, sesudah: selesai, dihapus, rentang, batal, transaksi: transaksi.length, kosong: kosong ?? null }));
            JS);

        $this->assertFalse($hasil['sebelum'], 'Belum selesai sebelum oncomplete.');
        $this->assertTrue($hasil['sesudah']);
        $this->assertSame([[7, 'a'], [7, 'b']], $hasil['dihapus']);
        $this->assertSame([['bawah' => [7], 'atas' => [7, []]]], $hasil['rentang'], 'Hapus jejak satu sesi memakai rentang kuncinya.');
        $this->assertSame('QuotaExceededError', $hasil['batal']);
        $this->assertSame(2, $hasil['transaksi'], 'Daftar kunci kosong tidak membuka transaksi.');
    }

    /**
     * Pemuat jejak tidak menyimpan kegagalan (ruling W6-P2n): janji yang ditolak dikosongkan, dan
     * panggilan berikutnya meminta lagi lewat imporUlang dengan nomor percobaan. Peramban
     * menyimpan kegagalan impor per URL, jadi imporUlang di dasar.js meminta URL potongan yang sama
     * dengan penanda ?ulang=n; spesifiernya dibaca dari teks fungsi impor, juga bentuk keluaran
     * build (pembungkus pramuat Vite, literal templat).
     */
    public function test_the_track_loader_retries_after_a_failed_import(): void
    {
        $hasil = $this->node([], <<<'JS'
            globalThis.window = { matchMedia: () => ({ matches: false }), addEventListener() {} };
            globalThis.document = { cookie: '', documentElement: { dataset: {} } };
            const dasar = await import(pathToFileURL(process.cwd() + '/resources/js/dasar.js').href);
            const log = [];
            let gagalkan = true;
            const muat = dasar.pemuatUlang(
                () => { log.push('awal'); return gagalkan ? Promise.reject(new Error('Failed to fetch dynamically imported module')) : Promise.resolve('modul'); },
                (ke) => { log.push('ulang:' + ke); return Promise.resolve('modul ulang'); },
            );
            const bersamaan = muat() === muat();
            let pertama = null;
            try { await muat(); } catch (e) { pertama = e.message; }
            const kedua = await muat();
            const ketiga = await muat();
            const spesifier = [
                dasar.spesifierImpor(() => p(() => import(`./jejak-HSw4eLQ9.js`), [])),
                dasar.spesifierImpor(() => import('/resources/js/jejak.js')),
                dasar.spesifierImpor(() => 1),
            ];
            function p() {}
            process.stdout.write(JSON.stringify({ bersamaan, pertama, kedua, ketiga, log, spesifier, terpasang: typeof globalThis.window.muatJejak }));
            JS);

        $this->assertTrue($hasil['bersamaan'], 'Panggilan selama impor berjalan memakai janji yang sama.');
        $this->assertSame('Failed to fetch dynamically imported module', $hasil['pertama']);
        $this->assertSame('modul ulang', $hasil['kedua'], 'Sesudah gagal, panggilan berikutnya meminta lagi.');
        $this->assertSame('modul ulang', $hasil['ketiga']);
        $this->assertSame(['awal', 'ulang:1'], $hasil['log'], 'Modul yang sudah termuat dipakai bersama, tanpa impor ketiga.');
        $this->assertSame(['./jejak-HSw4eLQ9.js', '/resources/js/jejak.js', null], $hasil['spesifier']);
        $this->assertSame('function', $hasil['terpasang']);

        $dasar = File::get(resource_path('js/dasar.js'));

        $this->assertStringContainsString('const imporJejak = () => import(\'./jejak.js\');', $dasar);
        $this->assertStringContainsString('window.muatJejak = pemuatUlang(imporJejak, (ke) =>', $dasar);
        $this->assertStringContainsString('import(/* @vite-ignore */ `${new URL(spesifierJejak, import.meta.url).href}?ulang=${ke}`)', $dasar);
    }

    /**
     * Perekam hanya merekam (ruling W6-P2a): mode lapangan dan jejak.js tidak punya pengirim, tidak
     * memanggil fetch, dan tidak mendengarkan `online`. Pengirim dipasang di AppLayout saja; mode
     * lapangan dan shell luring memakai TataLetakPendakian, jadi tidak pernah mengirim (I6).
     *
     * Diperluas putaran perbaikan 1 W6-P2 (ruling W6-P2q, tinjauan m-2): perekam yang mengirim lewat
     * sendBeacon, XMLHttpRequest, atau router Inertia, atau yang mendengarkan "online" berpetik apa
     * pun, juga merah (mutan M1s selamat sebelumnya).
     */
    public function test_the_recorder_never_sends_and_the_sender_lives_only_in_the_app_layout(): void
    {
        foreach (['components/trips/usePendakian.ts', 'jejak.js'] as $berkas) {
            $isi = $this->tanpaKomentarJs(File::get(resource_path('js/'.$berkas)));

            $this->assertStringNotContainsString('fetch(', $isi, $berkas);
            $this->assertStringNotContainsString('kirimJejak', $isi, $berkas);
            $this->assertStringNotContainsString('sendBeacon', $isi, $berkas);
            $this->assertStringNotContainsString('XMLHttpRequest', $isi, $berkas);
            $this->assertStringNotContainsString('router.', $isi, $berkas);
            $this->assertDoesNotMatchRegularExpression('/[\'"`]online[\'"`]/', $isi, $berkas);
        }

        $this->assertStringContainsString(
            'useJejakPendakian(data.rekamJejak && data.trip.berlangsung && !mode.dijeda, data.sesiId, kunci);',
            File::get(resource_path('js/pages/Trips/Hike.tsx')),
        );

        $pemasang = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (preg_match_all('/\busePengirimJejak\(\);/', $this->tanpaKomentarJs($berkas->getContents())) > 0) {
                $pemasang[] = $nama;
            }
        }

        $this->assertSame(['layouts/AppLayout.tsx'], $pemasang, 'usePengirimJejak dipasang hanya di AppLayout.');
        $this->assertSame(1, substr_count(File::get(resource_path('js/layouts/AppLayout.tsx')), 'usePengirimJejak();'));
    }

    /**
     * Sambungan pengirim ke server dan halaman: URL dari Wayfinder (bukan rekaman sesi paket),
     * token XSRF dari cookie saat kirim, gerbang props server yang sama dengan penjaga ganti akun,
     * dan pemicunya.
     */
    public function test_the_sender_is_wired_to_wayfinder_the_cookie_token_and_its_gate(): void
    {
        $kirim = $this->tanpaKomentarJs(File::get(resource_path('js/luring/kirimJejak.ts')));

        foreach ([
            "import { track as unggahJejak } from '@/routes/hike';",
            "import { status as periksaJejak } from '@/routes/hike/track';",
            "const respons = await minta(periksaJejak.url(sesi), { method: 'GET' });",
            'const respons = await minta(unggahJejak.url(sesi), {',
            "headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': tokenXsrf() },",
            'body: JSON.stringify({ points: titik }),',
            'propsDariServer: propsDariServer(),',
            "pendakianAktif,\n            daring: navigator.onLine !== false,",
            'kunciTertutup: kunci !== null && kunciTertutup(kunci),',
            // Ruling W6-P2q: gerbang dan syarat lanjut lewat fungsi murni, dengan halaman terpasang
            // dan pendakianAktif dari props halaman yang sebenarnya.
            'const pendakianAktif = props.pendakianAktif != null;',
            'kunciHalaman: propsTerbaru.kunci,',
            'pendakianAktif: propsTerbaru.pendakianAktif,',
            'kunciTertutup: kunciTertutup(kunci),',
            'masihBoleh: () => masihBoleh(kunci),',
            'gerbang: gerbangTerbaru,',
            'propsTerbaru = { kunci, pendakianAktif };',
            "window.addEventListener('online', coba);",
            "document.addEventListener('visibilitychange', saatTerlihat);",
            '}, [url, kunci, pendakianAktif]);',
            'if (berlangsungSebelumnya.current && !pendakianAktif) {',
        ] as $potongan) {
            $this->assertStringContainsString($potongan, $kirim);
        }

        $this->assertSame(2, substr_count($kirim, 'terpasang: terpasang > 0,'), 'Gerbang mulai dan syarat lanjut sama-sama membaca halaman yang terpasang.');
        $this->assertStringContainsString("return (\n        typeof indexedDB !== 'undefined' &&\n        bolehMulai({", $kirim);
        $this->assertStringContainsString("function masihBoleh(kunci: string): boolean {\n    return bolehLanjut({", $kirim);
        $this->assertStringNotContainsString('urls.jejak', $kirim);
        $this->assertStringNotContainsString('csrf-token', $kirim);
        $this->assertStringContainsString("export function propsDariServer(): boolean {\n    return riwayat.boleh();\n}", File::get(resource_path('js/luring/keluar.ts')));
    }

    /**
     * Menjalankan badan skrip di Node atas modul tanpa impor nilai (ruling L3), pola
     * OfflineShellTest::node.
     *
     * @param  array<string, string>  $modul  nama variabel => jalur di bawah resources/js
     */
    private function node(array $modul, string $badan): mixed
    {
        $impor = '';

        foreach ($modul as $nama => $berkas) {
            $impor .= 'const '.$nama.' = await import(pathToFileURL('.json_encode(resource_path('js/'.$berkas)).').href);';
        }

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, 'import { pathToFileURL } from "node:url";'.$impor.$badan, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal: '.mb_substr($proses->getErrorOutput(), 0, 900));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
