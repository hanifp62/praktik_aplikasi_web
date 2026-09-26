# Rencana Implementasi: Konsolidasi Dokumen Kanonis

> **Untuk agen pelaksana:** SUB-SKILL WAJIB: superpowers:subagent-driven-development (bila pemilik mengizinkan subagen) atau superpowers:executing-plans. Langkah memakai checkbox (`- [ ]`).

**Goal:** Menulis ulang `PRD.md` menjadi PRD inti bergabung (PRD lama + PRD NgodingPakeAI + `ourplan.md` + PRODUCT.md) dengan ID § permanen, delapan spesifikasi fitur di `docs/fitur/`, dan pembaruan berkas kanonis yang merujuknya, dijaga oleh satu test kontrak.

**Architecture:** Dokumen ditulis bertahap: test kontrak lebih dulu (RED), lalu kerangka PRD dengan semua anchor ID dan indeks (GREEN untuk kontrak ID), lalu isi per bagian, lalu spesifikasi fitur, lalu berkas kanonis yang merujuk. Setiap tugas ditutup dengan test kontrak dan pemeriksaan em dash.

**Tech Stack:** Markdown; PHPUnit 12 di Laravel 13.31.0, PHP 8.5.10; ripgrep.

**Spec:** `docs/superpowers/specs/2026-09-23-konsolidasi-dokumen-design.md`

## Global Constraints

- Semua teks baru berbahasa Indonesia; istilah pengguna: pos, jalur, kawasan, pengelola (PRODUCT.md "Capabilities and Constraints").
- Tanpa em dash (U+2014) di teks baru (antislop R-02). En dash hanya untuk rentang angka atau ID.
- Nomor § adalah ID permanen (DK-04): §1–§146 tidak dihapus atau dipakai ulang; bagian baru §147–§155; FR baru FR-22–FR-27; BR baru, bila ada, memakai nomor berikutnya setelah BR-16; governance record berikutnya GR-02.
- Aturan kanonis R-001–R-024 berlaku; teks PRD NgodingPakeAI yang melanggarnya diselaraskan (DK-10, K2–K5, K8, K16–K19).
- Skema data kanonis adalah migrasi di `database/migrations/` (K16), bukan skema usulan PRD NgodingPakeAI §6.
- Struktur `app/` tidak diubah (DK-01). Satu-satunya berkas PHP baru: test kontrak di Tugas 1.
- Tidak mengakses Supabase (R-019). Tidak push (R-018). Tidak commit sampai pemilik menyetujui di Tugas 14.
- Klaim eksternal hanya dari sumber di spec §7, dengan tautan.
- Keputusan DK-05, DK-06, DK-09 ditandai "diputuskan atas delegasi pemilik" di mana pun disebut.
- Bahan sumber: PRD lama `git show HEAD:PRD.md`; PRD NgodingPakeAI `PRD.md` working tree sebelum Tugas 2 (salin dulu); `features/*.md`; `ourplan.md`; `PRODUCT.md`; catatan riset di scratchpad sesi `riset-konsolidasi/`.

## Struktur berkas

| Berkas | Aksi | Tanggung jawab |
|---|---|---|
| `tests/Feature/PrdReferenceIntegrityTest.php` | Buat | Kontrak ID dan rujukan dokumen |
| `PRD.md` | Tulis ulang | PRD inti |
| `docs/fitur/01-jelajah-rute.md` … `08-konsol-pengelola.md` | Buat | Spesifikasi per fitur |
| `.claude/rules/mountain-core.md` | Ubah R-009 | Aturan domain (DK-09) |
| `CLAUDE.md` | Ubah tabel §9 | Rujukan dokumen |
| `docs/ARCHITECTURE.md` | Ubah rujukan PRODUCT.md | Peta kode |
| `HANDOVER.md` | Perbarui | Keadaan terkini |
| `docs/riset/2026-09-22-strategi-platform.md` | Buat (pindahan `ourplan.md`) | Riset asal |
| `PRODUCT.md` | Hapus setelah dilebur | |
| `features/` | Hapus setelah dimigrasi | |

## Pembagian ID per bagian PRD (mengikat)

Menyelesaikan tumpang-tindih di spec §5 (§7, §87–§93, §95) dan menempatkan §63 serta §155.

| Bagian | ID |
|---|---|
| Pembuka | §135 |
| I. Produk | §1–§6, §63, §8–§13, §144, §155 |
| II. Aturan dan model domain | §14–§18, §24–§31, §38–§51, §59–§60, §95 |
| III. Pengalaman | §7, §19, §89–§93, §145 |
| IV. Fungsional ringkas | §20–§23, §32–§37, §52–§58, §61–§62, §147–§153 |
| V. Non-fungsional | §75–§88, §94, §96–§99 |
| VI. Data dan teknik | §64–§74, §100–§104, §123–§126, §154 |
| VII. Cakupan dan peta jalan | §105–§122, §127–§129 |
| VIII. Tata kelola | §130–§132, §142–§143, §146 |
| Alias (baris indeks saja, tanpa heading) | §133 → §19, §134 → §7, §136 → §105, §137 → §1, §138 → §2, §139 → §1, §140 → §8, §141 → §7 |

Heading FR (masing-masing tepat sekali): §20 FR-01, §21 FR-02, §23 FR-03, §24 FR-04, §32 FR-05, §33 FR-06, §34 FR-07, §35 FR-08, §36 FR-09, §38 FR-10, §41 FR-11, §44 FR-12, §48 FR-13, §52 FR-14, §53 FR-15, §56 FR-16, §57 FR-17, §58 FR-18, §61 FR-19, §62 FR-20, §145 FR-21, §147 FR-22, §148 FR-23, §149 FR-24, §150 FR-25, §151 FR-26, §152 FR-27.

---

### Tugas 1: Test kontrak rujukan PRD (RED)

**Files:**
- Create: `tests/Feature/PrdReferenceIntegrityTest.php`

**Interfaces:**
- Produces (dipakai Tugas 2–12): sintaks anchor yang diperiksa test.
  - Heading bagian: `## §N Judul` sampai `#### §N Judul`.
  - Heading FR: `### §N FR-M Judul`.
  - Registri BR: baris tabel `| BR-NN | ... |` di §132.
  - Indeks ID: baris tabel `| §N | ... |`; alias berisi teks `alias → §M`.
  - Spesifikasi fitur: 8 berkas `docs/fitur/0[1-8]-*.md`, masing-masing memuat 9 heading templat persis seperti konstanta `TEMPLAT_FITUR`.

- [ ] **Langkah 1: Tulis test**

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kontrak rujukan PRD.
 *
 * Nomor § di PRD adalah ID permanen, bukan posisi halaman (spec konsolidasi DK-04).
 * Kode, test, dan dokumen merujuknya ratusan kali, jadi bagian yang hilang atau
 * bergeser memutus rujukan tanpa ada yang menyadarinya. Test ini menjaga empat
 * kontrak: setiap ID yang dirujuk ada, indeks ID lengkap, berkas yang disebut
 * CLAUDE.md ada, dan spesifikasi fitur mengikuti templatnya.
 */
class PrdReferenceIntegrityTest extends TestCase
{
    private const TEMPLAT_FITUR = [
        '## Tujuan dan pertanyaan pengguna',
        '## Posisi di loop dan permukaan',
        '## Kriteria penerimaan',
        '## Aturan yang berlaku',
        '## Data dan sumber',
        '## State',
        '## Status implementasi',
        '## Task NgodingPakeAI',
        '## Keputusan dan konflik terkait',
    ];

    public function test_every_section_id_referenced_anywhere_has_an_anchor_in_the_prd(): void
    {
        $hilang = array_values(array_diff($this->idDirujuk('/§(\d+)/u'), $this->anchorBagian()));

        $this->assertSame([], $hilang, 'Bagian PRD dirujuk tetapi tidak ada: §'.implode(', §', $hilang));
    }

    public function test_every_business_rule_and_feature_id_referenced_anywhere_exists_in_the_prd(): void
    {
        $prd = $this->prd();

        preg_match_all('/^\|\s*BR-(\d+)\s*\|/mu', $prd, $br);
        preg_match_all('/^#{2,4}\s+§\d+\s+FR-(\d+)\b/mu', $prd, $fr);

        $brHilang = array_values(array_diff($this->idDirujuk('/\bBR-(\d+)/u'), array_map('intval', $br[1])));
        $frHilang = array_values(array_diff($this->idDirujuk('/\bFR-(\d+)/u'), array_map('intval', $fr[1])));

        $this->assertSame([], $brHilang, 'BR dirujuk tetapi tidak ada di registri §132: BR-'.implode(', BR-', $brHilang));
        $this->assertSame([], $frHilang, 'FR dirujuk tetapi tidak ada sebagai heading: FR-'.implode(', FR-', $frHilang));
    }

    public function test_the_id_index_is_complete_and_has_no_gaps(): void
    {
        preg_match_all('/^\|\s*§(\d+)\s*\|/mu', $this->prd(), $cocok);
        $indeks = $this->unikUrut(array_map('intval', $cocok[1]));

        $this->assertNotEmpty($indeks, 'Indeks ID tidak ditemukan di PRD.');
        $this->assertSame(range(1, max($indeks)), $indeks, 'Indeks ID melompat.');
        $this->assertSame([], array_values(array_diff($indeks, $this->anchorBagian())), 'Ada ID di indeks tanpa heading atau alias yang sah.');
    }

    public function test_every_markdown_file_named_in_claude_md_exists(): void
    {
        preg_match_all('/`([A-Za-z0-9_\-\/.]+\.md)`/u', File::get(base_path('CLAUDE.md')), $cocok);

        $hilang = array_values(array_filter(
            array_unique($cocok[1]),
            fn (string $path): bool => ! File::exists(base_path($path))
        ));

        $this->assertSame([], $hilang, 'CLAUDE.md menyebut berkas yang tidak ada: '.implode(', ', $hilang));
    }

    public function test_the_eight_feature_specs_follow_the_template(): void
    {
        $berkas = glob(base_path('docs/fitur/0[1-8]-*.md'));

        $this->assertCount(8, $berkas, 'docs/fitur/ harus berisi tepat delapan spesifikasi.');

        foreach ($berkas as $path) {
            $isi = File::get($path);

            foreach (self::TEMPLAT_FITUR as $heading) {
                $this->assertStringContainsString($heading, $isi, basename($path).' tidak memuat "'.$heading.'".');
            }
        }
    }

    private function prd(): string
    {
        return File::get(base_path('PRD.md'));
    }

    /**
     * ID bagian yang punya heading, ditambah alias yang menunjuk heading yang ada.
     *
     * @return array<int, int>
     */
    private function anchorBagian(): array
    {
        $prd = $this->prd();

        preg_match_all('/^#{2,4}\s+§(\d+)\b/mu', $prd, $heading);
        $ada = array_map('intval', $heading[1]);

        preg_match_all('/^\|\s*§(\d+)\s*\|[^\n]*alias → §(\d+)/mu', $prd, $alias, PREG_SET_ORDER);

        foreach ($alias as [, $id, $target]) {
            if (in_array((int) $target, $ada, true)) {
                $ada[] = (int) $id;
            }
        }

        return $this->unikUrut($ada);
    }

    /**
     * @return array<int, int>
     */
    private function idDirujuk(string $pola): array
    {
        $id = [];

        foreach ($this->berkasPerujuk() as $path) {
            preg_match_all($pola, File::get($path), $cocok);
            array_push($id, ...array_map('intval', $cocok[1]));
        }

        return $this->unikUrut($id);
    }

    /**
     * @return array<int, string>
     */
    private function berkasPerujuk(): array
    {
        $berkas = array_map(base_path(...), ['CLAUDE.md', 'DESIGN.md', 'HANDOVER.md']);

        foreach (['app', 'resources', 'tests', 'database', 'routes', 'config', 'docs', '.claude/rules'] as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                if (in_array($file->getExtension(), ['php', 'md'], true)) {
                    $berkas[] = $file->getPathname();
                }
            }
        }

        return $berkas;
    }

    /**
     * @param  array<int, int>  $nilai
     * @return array<int, int>
     */
    private function unikUrut(array $nilai): array
    {
        $nilai = array_values(array_unique($nilai));
        sort($nilai);

        return $nilai;
    }
}
```

- [ ] **Langkah 2: Jalankan, pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php`
Expected: 4 gagal (anchor §, BR/FR, indeks, `docs/fitur/` belum ada) dan 1 lulus (`test_every_markdown_file_named_in_claude_md_exists`, penjaga yang baru berguna setelah PRODUCT.md dihapus di Tugas 12). Pesan gagal harus menyebut ID atau berkas, bukan galat sintaks.

- [ ] **Langkah 3: Format**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"result":"passed"}` atau berkas diperbaiki.

### Tugas 2: Kerangka PRD, indeks ID, tabel paritas

**Files:**
- Modify (tulis ulang): `PRD.md`

**Interfaces:**
- Consumes: sintaks anchor Tugas 1; pembagian ID di atas.
- Produces: setiap heading §N dan FR sudah ada (isi boleh singkat); Tugas 3–10 hanya mengisi isi di bawah heading yang ada.

- [ ] **Langkah 1: Amankan sumber**

```bash
SCR="C:/Users/ACER/AppData/Local/Temp/claude/E--praktik-aplikasi-web/b70ca62b-af6e-4828-8c5c-cb10185fd416/scratchpad/riset-konsolidasi"
cp PRD.md "$SCR/sumber-prd-ngodingpakeai.md"
git show HEAD:PRD.md > "$SCR/sumber-prd-lama.md"
wc -l "$SCR/sumber-prd-ngodingpakeai.md" "$SCR/sumber-prd-lama.md"
```
Expected: 412 dan 3670 baris.

- [ ] **Langkah 2: Tulis kerangka `PRD.md`**, berurutan:
  1. Judul "PRD: Personalized Mountain Planning & Readiness Platform", versi "PRD v3.0 (konsolidasi 23 September 2026)", status "Draf menunggu persetujuan pemilik".
  2. "Cara membaca": aturan ID (salin spec §4), istilah (pos, jalur, kawasan, pengelola), tingkat bukti FACT/DECISION/INFERENCE (R-023).
  3. `## §135 Baseline teknis`: PHP 8.5.10, Laravel 13.31.0 (terpasang; header lama menulis 13.32.0, diskrepansi D-A), Livewire 3.8.9, Volt 1.11.2, PostgreSQL/Supabase + PostGIS, MapLibre, BMKG, target deploy Vercel (DK-12), WCAG 2.2 AA.
  4. Bagian I–VIII sebagai heading `# Bagian I: Produk` dst., dengan heading `## §N Judul` untuk setiap ID sesuai tabel pembagian; judul bahasa Indonesia dari judul lama (contoh: §1 Ringkasan eksekutif produk, §91 Tanpa presisi palsu, §145 FR-21 Arsitektur informasi dan permukaan produk). Heading FR memakai `### §N FR-M Judul` di bawah bagiannya.
  5. `# Lampiran A: Indeks ID`: tabel `| ID | Judul | Bagian | Status |`, satu baris per §1–§155; baris alias berisi `alias → §M`.
  6. `# Lampiran B: Tabel paritas PRD lama`: tabel `| § lama | Judul lama | Status | Lokasi baru |`, 146 baris; status salah satu: dipertahankan, dipadatkan, digabung, diamandemen, alias.
  7. `# Lampiran C: Tabel paritas PRODUCT.md`: satu baris per heading PRODUCT.md (17 heading) dengan lokasi barunya.
  8. `# Lampiran D: Sumber riset`: semua tautan spec §7.
  9. Di §132 tulis tabel registri `| BR-01 | Mountain ≠ Trail |` sampai `| BR-16 | ... |` dengan teks BR lama apa adanya.
  Isi setiap bagian sementara boleh satu kalimat ringkasan; diperdalam di Tugas 3–10.

- [ ] **Langkah 3: Jalankan test kontrak**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php`
Expected: 4 lulus; `test_the_eight_feature_specs_follow_the_template` masih gagal (dikerjakan Tugas 11).

- [ ] **Langkah 4: Periksa em dash**

Run: `grep -c $'\xe2\x80\x94' PRD.md`
Expected: `0`

### Tugas 3: Bagian I Produk

**Files:** Modify `PRD.md` (heading §1–§6, §63, §8–§13, §144, §155)

- [ ] **Langkah 1: Tulis isi** dengan unsur wajib berikut:
  - §1: definisi decision-support (lama §1) + alur 8 kata kerja (PRD NgodingPakeAI §1).
  - §2: masalah (lama §2) + latar belakang PRD NgodingPakeAI (sumber tersebar, data jalur belum bergeometri).
  - §3: peluang (alur lama ke alur baru, lama §3).
  - §4, §5, §6: visi, misi 6 butir, north star (lama, tanpa perubahan makna).
  - §63: north star metric (lama §63).
  - §8, §9: diferensiator dan posisi (lama) + ATM dan rujukan (PRODUCT.md) + temuan spec §7.1 dengan tautan.
  - §10: empat persona lama + persona "Ahli bersertifikat wilayah" (alur C PRD NgodingPakeAI, istilah DK-09).
  - §11, §12: JTBD dan tujuan G1–G5; enam tujuan PRD NgodingPakeAI dipetakan ke G1–G5 dalam satu tabel.
  - §13: non-goals lama + "pemesanan izin tidak direplikasi, ditautkan (§153)".
  - §144: prinsip akhir lama + lima Product Principles PRODUCT.md + Brand Commitments + urutan prioritas mengikat (IMK/HCI 1 sampai SEO 9).
  - §155 Pertumbuhan dan halaman publik: bagian Growth PRODUCT.md utuh.
- [ ] **Langkah 2: Tandai paritas** di Lampiran B dan C untuk semua baris sumber bagian ini.
- [ ] **Langkah 3: Verifikasi**: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php` (4 lulus) dan `grep -c $'\xe2\x80\x94' PRD.md` (0).

### Tugas 4: Bagian II Aturan dan model domain

**Files:** Modify `PRD.md` (§14–§18, §24–§31, §38–§51, §59–§60, §95)

- [ ] **Langkah 1: Tulis isi**:
  - §14–§18: model domain, Mountain ≠ Trail, karakteristik rute, kesulitan multidimensi, MDPL (lama).
  - §24–§31 (FR-04): Route Fit tiga lapis, hard constraint, bobot V1 sebagai konfigurasi di tabel `recommendation_rules`, skor internal tidak tampil, label publik COCOK / PERLU PERSIAPAN / KURANG COCOK, explainability (Why It Fits, What to Watch, Preparation Gap) dengan penanda ✓/⚠ dari PRD NgodingPakeAI, audit run (K2, K3).
  - §38–§40 (FR-10): READY / NEEDS_PREPARATION / NOT_RECOMMENDED dengan faktor yang kurang, tanpa angka dan tanpa penilaian medis; "kebugaran" hanya sebagai butir persiapan (K4).
  - §41–§43 (FR-11): status OPEN / RESTRICTED / CLOSED / UNKNOWN; "waspada" dari PRD NgodingPakeAI dipetakan menjadi peringatan kondisi, bukan status (K5); cakupan status; hierarki otoritas dengan ACCREDITED EXPERT "Ahli bersertifikat" (DK-09, delegasi).
  - §44–§47 (FR-12): BMKG adm4 per jalur (BR-15, K17) dengan fakta spec §7.5.
  - §48–§51 (FR-13): laporan kondisi, prinsip, konflik, kesegaran.
  - §59–§60: kesegaran per sumber, bukan ambang universal 7 hari (K8); model kepercayaan.
  - §95: default-safe.
- [ ] **Langkah 2: Tandai paritas.**
- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 5: Bagian III Pengalaman

**Files:** Modify `PRD.md` (§7, §19, §89–§93, §145)

- [ ] **Langkah 1: Tulis isi**:
  - §7: loop PROFILE → ROUTE FIT → PLAN → PREPARE → CHECK → HIKE → REPORT → IMPROVE, disandingkan dengan alur 8 kata kerja; pilar FIT/PREPARE/CHECK/HIKE/REPORT (dari §134).
  - §19: alur pendaki (lama + alur A PRD NgodingPakeAI), alur pengelola (alur B), alur ahli bersertifikat wilayah (alur C).
  - §89–§93: prinsip UX lama; §92 biru untuk komunitas (K18).
  - §145 (FR-21): lima permukaan; Pertimbangkan diamandemen: simpan longgar dengan batas teknis, banding memilih maksimal 3 (DK-05, delegasi), catatan pribadi (FR-22), tautan bagikan read-only (FR-23); Jelajah diamandemen: peta digerbang data, hanya jalur bergeometri, tanpa titik gunung sebagai pengganti jalur (DK-06, delegasi); §145.2 URL publik tetap target dengan catatan diskrepansi D-B.
- [ ] **Langkah 2: Tandai paritas.**
- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 6: Bagian IV Fungsional ringkas

**Files:** Modify `PRD.md` (§20–§23, §32–§37, §52–§58, §61–§62, §147–§153)

- [ ] **Langkah 1: Tulis isi**: satu sampai tiga paragraf per FR dengan tautan ke `docs/fitur/NN-*.md`, dan:
  - §20 FR-01 diamandemen: email + kata sandi, passkey (`laravel/passkeys`), masuk dengan Google (Socialite); tabel metode dari spec §7.4.
  - §33 FR-06 diamandemen: banding maksimal 3 dipilih dari simpanan; tanpa pemenang (R-016) (DK-05).
  - §35 FR-08 diamandemen: jumlah peserta dan tanggal selesai (K12); state trip tetap enam.
  - §52 FR-14: rujuk FR-25 untuk catatan manual.
  - §147 FR-22 catatan pribadi; §148 FR-23 tautan bagikan (publik, read-only, bertoken, berkedaluwarsa, tidak menulis ke simpanan siapa pun); §149 FR-24 pengingat (email sebagai kanal awal; butuh driver mail dan Vercel Cron); §150 FR-25 target pribadi dan catatan manual (R-015, BR-11); §151 FR-26 verifikasi kode (desain dan aturan kode spec §7.4 lengkap); §152 FR-27 pencarian kalimat berbasis aturan dengan batas jujur (K20, temuan F3); §153 izin resmi (tautan e-Rinjani dan booking Semeru, spec §7.5).
- [ ] **Langkah 2: Tandai paritas.**
- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 7: Bagian V Non-fungsional

**Files:** Modify `PRD.md` (§75–§88, §94, §96–§99)

- [ ] **Langkah 1: Tulis isi**:
  - §75 diamandemen: lapisan keamanan lama + OWASP Top 10:2025 (A03 rantai pasok: lockfile, `composer audit`, `npm audit`; A10 penanganan kondisi luar biasa: gagal tertutup).
  - §76–§83: lama.
  - §84 diamandemen: kontrol privasi lama + rujukan UU PDP (spec §7.2) sebagai rekomendasi untuk `docs/KEBIJAKAN-DATA-PRIBADI.md`, bukan kebijakan baru.
  - §85–§86: kebijakan AI lama + Laravel AI SDK sebagai opsi masa depan.
  - §87 diamandemen: sembilan SC baru WCAG 2.2 dengan level, target 44×44 untuk kontrol utama, temuan F1 dan F7.
  - §88, §94: lama.
  - §96 diamandemen: LCP < 2,5 s, INP < 200 ms, CLS < 0,1 pada persentil 75; API kritis p95 ≤ 1 s.
  - §97: lama. §98 diamandemen: `weather:refresh` 06:00 dan 18:00 UTC, `data:freshness-check` 07:00 UTC, dijalankan Vercel Cron lewat endpoint terautentikasi (K24). §99 diamandemen: `LOG_CHANNEL=stderr` di Vercel; kontrak nama event telemetry lama tetap.
- [ ] **Langkah 2: Tandai paritas.**
- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 8: Bagian VI Data dan teknik

**Files:** Modify `PRD.md` (§64–§74, §100–§104, §123–§126, §154)

- [ ] **Langkah 1: Ambil daftar tabel nyata**

Run: `ls database/migrations | sed -E 's/^[0-9_]+//; s/\.php$//'`
Expected: 51 nama migrasi; tabel diturunkan dari nama `create_*_table`.

- [ ] **Langkah 2: Tulis isi**:
  - §64 diamandemen: model data dari migrasi nyata, dikelompokkan seperti lama (identitas, geografi, kondisi, perencanaan, aktivitas, rekomendasi, tata kelola) + tabel tambahan (`trail_considerations`, `mountain_follows`, `hike_track_points`, `report_thanks`, `authorities`, `expert_credentials`, `permit_requirements`, `usability_sessions`, `scheduled_task_runs`); catatan: skema usulan PRD NgodingPakeAI §6 tidak dipakai (K16).
  - §65–§68: lama; §67 disk `supabase` sudah ada di `config/filesystems.php`.
  - §69–§74: arsitektur monolit modular per domain (ARCHITECTURE.md aturan 10) + diagram domain PRD NgodingPakeAI dengan Redis ditandai "bila beban menuntut" (K15) dan otorisasi Policy/Gate (K19).
  - §100–§104: lama + suite nyata (Unit, Feature, Spatial) dan test kontrak Tugas 1.
  - §123–§126 diamandemen: kesiapan produksi untuk Vercel, pertimbangan Supabase pooler (K26), cabang kerja nyata, CI GitHub Actions nyata + deploy Vercel (K25).
  - §154 Arsitektur operasional di Vercel: jalur container FrankenPHP (rekomendasi) dan `vercel-php@0.9.0` (alternatif); session dan cache `database`; berkas di disk `supabase`; Vercel Cron; antrean; log stderr; migrasi di CI; `APP_KEY` stabil; tanpa `php artisan optimize` saat build (K28); uji header `X-Livewire` dan `X-Csrf-Token` (K27); sumber spec §7.7.
- [ ] **Langkah 3: Tandai paritas.**
- [ ] **Langkah 4: Verifikasi** seperti Tugas 3.

### Tugas 9: Bagian VII Cakupan dan peta jalan

**Files:** Modify `PRD.md` (§105–§122, §127–§129)

- [ ] **Langkah 1: Tulis isi**:
  - §105 diamandemen: 26 butir MVP lama + FR-22 sampai FR-27 (DK-07, DK-08); butir 25 Pertimbangkan mengikuti DK-05.
  - §106: lama. §107 diamandemen: offline lewat PMTiles/Protomaps (spec §7.3).
  - §108 diamandemen: target dataset lama + keadaan nyata (16 gunung berkoordinat Wikidata, 29 jalur tanpa karakteristik, 0 geometri; PRODUCT.md Evidence on Hand); golden trail sebagai prasyarat DK-06.
  - §109–§110: lama.
  - §111–§113 diamandemen: riset lama + model kerja IMK dan enam hasil UX terukur beserta target validasi PRODUCT.md, ditandai "target, bukan hasil".
  - §114–§120: kriteria penerimaan lama + kriteria "Selesai bila" yang tidak bertentangan, dirujuk ke `docs/fitur/`.
  - §121–§122 diamandemen: asumsi dan risiko lama + risiko Vercel (spec §12).
  - §127 diamandemen: tim lima peran + peran agen PRODUCT.md.
  - §128 diamandemen: satu tabel fase yang menyatukan fase lama 1–8, Fase 1–4 NgodingPakeAI, dan roadmap 0–15 `ourplan.md`, dengan status nyata per fase.
  - §129 diamandemen: DoD lama + tingkat bukti L0–L4 `ourplan.md` + R-023.
- [ ] **Langkah 2: Tandai paritas.**
- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 10: Bagian VIII Tata kelola

**Files:** Modify `PRD.md` (§130–§132, §142–§143, §146)

- [ ] **Langkah 1: Tulis isi**:
  - §130–§131: lama.
  - §132: registri BR-01 sampai BR-16 (sudah ada dari Tugas 2), teks lama tidak diubah.
  - §142 diamandemen: kunci keputusan lama + peta digerbang data (DK-06), deploy Vercel (DK-12), perbandingan maksimal 3 (DK-05).
  - §143 diamandemen: status per dimensi; "MVP boundary: diamandemen GR-01 dan GR-02".
  - §146: GR-01 utuh + GR-02 dari spec §10 (tujuh butir; tandai DK-05, DK-06, DK-09 sebagai delegasi) + log keputusan DK-01 sampai DK-12 + ringkasan register K1–K28 dengan resolusinya.
- [ ] **Langkah 2: Tandai paritas**; Lampiran B harus 146 baris tanpa status kosong.

Run: `grep -cE '^\| §[0-9]+ \|' PRD.md`
Expected: 155 (indeks) + 146 (paritas lama) = 301 baris.

- [ ] **Langkah 3: Verifikasi** seperti Tugas 3.

### Tugas 11: Delapan spesifikasi fitur

**Files:**
- Create: `docs/fitur/01-jelajah-rute.md`, `02-detail-rute.md`, `03-pertimbangkan.md`, `04-perjalanan-persiapan.md`, `05-kabar-kondisi.md`, `06-pendakian-progres.md`, `07-akun-masuk.md`, `08-konsol-pengelola.md`

**Interfaces:**
- Consumes: FR dan § dari Tugas 2–10; `features/NN-*.md` sebagai bahan.

- [ ] **Langkah 1: Tulis tiap berkas** dengan heading judul fitur lalu 9 heading `TEMPLAT_FITUR` persis. Isi wajib:
  - Kriteria penerimaan Given/When/Then dari "Selesai bila", diselaraskan ke aturan kanonis (K2–K5, K8).
  - "Aturan yang berlaku": daftar ID R, BR, § yang mengikat.
  - "Data dan sumber": tabel nyata dari migrasi.
  - "Status implementasi": per sub-fitur, salah satu dari ada (dengan rujukan berkas, contoh `app/Livewire/Trails/SavedRoutes.php`), sebagian, belum ada, atau berbeda; sertakan temuan F1–F7 yang relevan.
  - "Task NgodingPakeAI": semua task dari `features/NN-*.md`, masing-masing dipetakan ke kode yang ada atau ditandai baru; task #1–#10 Jelajah berstatus done di server, #1–#9 belum diverifikasi.
- [ ] **Langkah 2: Jalankan test kontrak**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php`
Expected: 5 lulus.

- [ ] **Langkah 3: Periksa em dash**: `grep -c $'\xe2\x80\x94' docs/fitur/*.md` harus 0 untuk tiap berkas.

### Tugas 12: Berkas kanonis yang merujuk, arsip, penghapusan

**Files:**
- Modify: `.claude/rules/mountain-core.md` (R-009), `CLAUDE.md` (tabel §9), `docs/ARCHITECTURE.md` (baris "Strategi pertumbuhan ada di `PRODUCT.md`")
- Create: `docs/riset/2026-09-22-strategi-platform.md`
- Delete: `PRODUCT.md`, `features/`

- [ ] **Langkah 1: Amandemen R-009**: ganti Statement dengan teks spec §11; Scope dan Invariant tidak diubah; tambahkan satu baris provenance: "Diamandemen 23 September 2026 atas delegasi pemilik (spec konsolidasi DK-09, PRD §146 GR-02)".
- [ ] **Langkah 2: CLAUDE.md §9**: hapus baris `PRODUCT.md`; ubah baris `PRD.md` menjadi "Requirement, domain, BR-01–BR-16, visi, misi, pertumbuhan (§155), arsitektur informasi §145, governance §146"; tambah baris `docs/fitur/` (spesifikasi per fitur) dan `docs/riset/` (riset asal). Di §2 dan tempat lain, ganti setiap rujukan `PRODUCT.md` ke lokasi barunya di PRD.
- [ ] **Langkah 3: Cari semua rujukan PRODUCT.md**

Run: `rg -n "PRODUCT\.md" --glob '!docs/superpowers/**' --glob '!vendor/**'`
Expected setelah diperbaiki: tidak ada hasil kecuali rencana dan spec ini (catatan sejarah).

- [ ] **Langkah 4: Arsip dan hapus**

```bash
mkdir -p docs/riset
mv ourplan.md docs/riset/2026-09-22-strategi-platform.md
git rm PRODUCT.md
rm -r features
```

- [ ] **Langkah 5: Verifikasi**: test kontrak 5 lulus (termasuk penjaga berkas CLAUDE.md).

### Tugas 13: HANDOVER.md

**Files:** Modify `HANDOVER.md`

- [ ] **Langkah 1: Perbarui** bagian 1 (cara melanjutkan, termasuk loop NgodingPakeAI), 4 (dasar produk menunjuk PRD v3.0), 7 (keadaan kode: suite 933/916/0/17, task #10 selesai, working tree belum di-commit), 8 (keputusan tertutup pindah ke PRD §146, sisakan tautan), 9 (yang masih terbuka: F1–F7, sub-proyek 2, 4, 5), 10 (umpan balik pemilik terbaru: landasan riset mendalam, deploy Vercel). Tanggal "Terakhir diperbarui" diganti.
- [ ] **Langkah 2: Verifikasi**: test kontrak 5 lulus; em dash 0 pada teks yang ditulis ulang.

### Tugas 14: Verifikasi akhir dan tinjauan pemilik

- [ ] **Langkah 1: V1–V6**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php` (5 lulus); `grep -c $'\xe2\x80\x94' PRD.md docs/fitur/*.md` (0 semua); baca ulang GR-02 terhadap tujuh butir §130.

- [ ] **Langkah 2: V7 suite penuh**

Run: `php artisan test --compact`
Expected: 0 gagal (sebelumnya 933 test, 916 lulus, 17 dilewati; bertambah 5 test kontrak).

- [ ] **Langkah 3: V8 tinjauan pemilik**: sajikan ringkasan perubahan, tautan berkas, hasil test; tanya persetujuan commit lokal (hanya berkas dokumen dan test kontrak, dengan `git add` per path). Tidak push.
