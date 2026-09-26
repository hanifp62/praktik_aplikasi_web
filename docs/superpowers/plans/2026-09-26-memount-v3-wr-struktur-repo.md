# Memount v3 WR Struktur Repo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membawa repo Memount ke tonggak M1 "siap push" (DK-58): root yang terinventaris, PRD dan dokumen tim di
`docs/`, CI dipecah tiga, PostGIS lokal lewat Docker, berkas kebijakan repo, tata kelola skill dan lisensi, semuanya
dijaga test, tanpa commit atau push sebelum izin pemilik.

**Architecture:** Susunan Laravel monolit modular tidak disentuh (DK-51); prinsip dua panduan struktur pemilik diambil
lewat pemetaan di `docs/ARCHITECTURE.md`, bukan dengan memindahkan `app/`. Setiap keputusan struktur dijadikan test
PHPUnit yang membaca berkas, keluaran `git`, dan YAML (Symfony Yaml), sehingga pelanggaran berikutnya membuat suite
merah alih-alih bergantung pada ingatan. Dokumen tim merangkum dan menunjuk pemilik aturannya; kutipan yang disalin
dijaga identik dengan sumbernya oleh test.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, PHPUnit 12, Pint, Symfony Yaml 8.1.6 (dependensi dev transitif lewat
`laravel/roster` dari `laravel/boost`), Git 2.55, GitHub Actions (`actions/checkout@v5`, `actions/setup-node@v5`,
`shivammathur/setup-php@v2`, `actions/upload-artifact@v6`), Docker Compose opsional dengan `postgis/postgis:16-3.4`,
Node 22 di CI, `@playwright/test` 1.63.0 hanya setelah Q17 disetujui.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` §17 (arahan pemilik verbatim, perbandingan
dua panduan struktur, DK-51 sampai DK-58, izin Q17 sampai Q19) dan §15 (daftar izin), bersama rencana induk
`docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (Global Constraints, Review Focus, bagian WR, kepemilikan
berkas).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, MapLibre 4.7.x, TypeScript 5.9; tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi runtime baru; dependensi dev baru hanya di Tugas 9 dan hanya setelah pemilik menyetujui Q17 secara tertulis.
- Tidak ada akses Supabase, tidak ada `php artisan migrate`, tidak membaca atau mencetak isi `.env` (R-019, DK-23); `SPATIAL_TEST_DSN` hanya menunjuk PostgreSQL lokal.
- Tidak ada commit dan push tanpa izin pemilik (HANDOVER.md bagian 0, PRD §161, R-018); langkah commit dilewati bila izin belum ada, dan berkas yang berubah dicatat.
- Commit per tugas memakai pathspec (`git commit -m "..." -- <jalur>`) supaya perubahan lain yang sudah di-stage dari sesi sebelumnya (misalnya penghapusan Livewire) tidak ikut; banyak berkas yang disunting WR sudah memuat perubahan sesi sebelumnya yang belum di-commit, dan izin commit diminta dengan menyebut hal itu.
- `git mv` dipakai hanya untuk berkas yang sudah dilacak (`PRD.md`, `.github/workflows/ci.yml`); pemindahan itu otomatis masuk index, dan itu bukan commit.
- Struktur Laravel tetap (DK-51): tidak ada `apps/`, `packages/`, layanan mikro, Kubernetes, Terraform, atau Ansible.
- Blok Laravel Boost di `AGENTS.md` dan `CLAUDE.md` tidak diubah isinya; tag pembuka blok itu tidak pernah ditulis di luar blok, karena `GuidelineWriter` Boost mencari tag pembuka pertama dengan regex non-greedy. Blok NgodingPakeAI di `AGENTS.md` tidak diubah.
- `DESIGN.md` dan `PRODUCT.md` tetap di root (DK-52); `docs/DESIGN.md` dan `docs/PRODUCT.md` hanya penunjuk satu paragraf.
- Dokumen bertanggal (`docs/superpowers/**`, `docs/riset/**`, `docs/audit/**`, `docs/arsip/**`, bagian riwayat `HANDOVER.md`) tidak ditulis ulang; jalur di dalamnya benar pada tanggalnya.
- Bahasa Indonesia, sapaan "Anda", tanpa em dash, tanpa klaim "aman" atau "layak berangkat" di dokumen baru; kode dan perintah apa adanya.
- Di dokumen hidup, setiap jalur `.md` yang ditulis di antara backtick adalah jalur dari root repo dan harus ada di commit M1 (dijaga `PrdReferenceIntegrityTest`); contoh nama berkas ditulis tanpa backtick.
- Tidak ada kredensial di berkas; satu-satunya kata sandi di repo adalah kata sandi kontainer uji sekali pakai `hiking`, sama dengan layanan PostGIS di CI.
- Test dibuat dengan `php artisan make:test --phpunit --no-interaction <Nama>`; setelah menyunting PHP, jalankan `vendor/bin/pint --format agent <berkas>` untuk berkas yang disunting tugas itu.
- Tugas dikerjakan berurutan, tidak paralel: tugas-tugas ini berbagi `README.md`, `CONTRIBUTING.md`, `.gitignore`, `docs/PRD.md`, dan konstanta test. Penyuntingan `CLAUDE.md` dan `HANDOVER.md` dikerjakan koordinator (rencana induk, "Kepemilikan berkas").

## Review Focus

- **Rujukan jalur yang patah setelah pemindahan** (`PRD.md`, `ourplan.md`, `ci.yml` masih disebut di dokumen hidup): pembaca dan agen diarahkan ke berkas yang tidak ada, atau ke salinan PRD basi yang ditulis `ngodingpakeai plan get`. Dijaga Tugas 1 `PrdReferenceIntegrityTest::test_every_markdown_file_named_in_a_living_document_exists` dan `test_the_prd_lives_in_docs_and_the_root_has_no_stray_copy`, serta Tugas 3 `CiWorkflowsTest::test_documents_name_only_workflows_that_exist`.
- **Blok Boost tertimpa atau tertelan** (bagian Memount di `AGENTS.md` menulis tag pembuka blok, atau salah satu blok disunting tangan): `php artisan boost:update` akan memakan bagian Memount atau menimpa suntingan. Dijaga Tugas 8 `TeamDocsIndexTest::test_the_boost_block_is_untouched_and_the_memount_section_sits_outside_it`.
- **Rahasia ikut terdorong lewat berkas yang belum dilacak** (557 berkas belum dilacak akan masuk commit M1, sementara pemindai lama hanya membaca berkas terlacak): rahasia baru tertangkap setelah masuk riwayat git publik. Dijaga Tugas 3 `SecretsNotInRepoTest::test_untracked_files_that_are_not_ignored_are_scanned_too` dan `test_each_pattern_catches_a_sample_of_its_secret`.
- **YAML CI rusak atau gerbang hilang saat dipecah** (build sesudah test, dua opsi `--testsuite` yang saling menimpa, action yang masih Node 20): CI hijau palsu atau merah tanpa sebab kode. Dijaga Tugas 3 `CiWorkflowsTest::test_the_test_workflow_keeps_every_gate_in_the_proven_order` dan `test_every_action_is_pinned_to_a_major_that_runs_on_node_24`.
- **Dokumen tim menaut ke berkas yang tidak ada atau indeks tidak lengkap**: anggota baru tersesat justru di pintu masuk. Dijaga Tugas 8 `TeamDocsIndexTest::test_the_docs_index_links_only_to_files_that_exist` dan `test_every_team_document_is_listed_in_the_index`.

---

## File Structure

**Test baru (penjaga keputusan struktur):**
- `tests/Feature/RepoHygieneTest.php`: root hanya berisi entri terinventaris; aturan `.gitignore` untuk keadaan lokal, ruang kerja alat, dan dump; prototype dan arsip strategi di `docs/`.
- `tests/Feature/CiWorkflowsTest.php`: tiga workflow, pemicu, urutan gerbang, izin token, mayor action Node 24, rujukan workflow di dokumen.
- `tests/Feature/LocalPostgisComposeTest.php`: paritas `docker-compose.yml` dengan layanan PostGIS CI dan DSN yang terdokumentasi.
- `tests/Feature/RepoPolicyFilesTest.php`: `SECURITY.md`, templat PR, formulir isu, dan `database/README.md` yang menyebut setiap tabel.
- `tests/Feature/SkillGovernanceTest.php`: setiap skill terpasang terklasifikasi; urutan wewenang DK-57.
- `tests/Feature/ThirdPartyLicenseTest.php`: lisensi MIT Phosphor di samping registri ikon; daftar lisensi mencakup setiap dependensi runtime.
- `tests/Feature/TeamDocsIndexTest.php`: indeks `docs/README.md`, heading wajib `docs/AGENTS.md`, kutipan identik dengan PRD, penunjuk, blok Boost, peta kepemilikan PRD, bagian struktur repo di ARCHITECTURE.
- `tests/Feature/EndToEndIsolationTest.php` (hanya Tugas 9): server e2e tidak pernah membaca `.env`.

**Test yang diubah:**
- `tests/Feature/PrdReferenceIntegrityTest.php`: PRD di `docs/PRD.md`; semua dokumen hidup, bukan hanya `CLAUDE.md`, hanya menyebut berkas `.md` yang ada.
- `tests/Feature/SecretsNotInRepoTest.php`: memindai berkas yang akan ikut commit (dilacak dan baru tetapi tidak diabaikan); pola token NgodingPakeAI; contoh per pola.

**Dokumen dan konfigurasi baru:**
- `docs/PRD.md` (pindahan), `docs/AGENTS.md`, `docs/README.md`, `docs/DESIGN.md`, `docs/PRODUCT.md`, `docs/lisensi-pihak-ketiga.md`.
- `docs/desain/prototype/memount-prototype.html` (pindahan), `docs/desain/prototype/acuan/*.png` (16), `docs/desain/prototype/README.md`.
- `docs/arsip/2026-09-22-strategi-platform.md` (pindahan `ourplan.md`).
- `.github/workflows/test.yml` (pindahan `ci.yml`), `.github/workflows/security.yml`, `.github/workflows/deploy.yml`.
- `.github/PULL_REQUEST_TEMPLATE.md`, `.github/ISSUE_TEMPLATE/bug.yml`, `.github/ISSUE_TEMPLATE/fitur.yml`, `.github/ISSUE_TEMPLATE/config.yml`.
- `docker-compose.yml`, `SECURITY.md`, `database/README.md`, `resources/js/components/ui/LICENSE-phosphor.txt`.
- Hanya Tugas 9: `playwright.config.ts`, `tests/e2e/siapkan-env.mjs`, `tests/e2e/halaman-publik.spec.ts`, `tests/e2e/tsconfig.json`.

**Dokumen dan konfigurasi yang diubah:** `.gitignore`, `.gitattributes`, `.env.example` (komentar), `phpunit.xml`
(komentar), `AGENTS.md` (bagian Memount di luar blok alat), `CLAUDE.md` (rujukan PRD dan satu baris penunjuk),
`README.md`, `CONTRIBUTING.md`, `DESIGN.md` (dua rujukan jalur), `PRODUCT.md` (dua rujukan jalur), `HANDOVER.md`,
`.claude/rules/mountain-core.md` (satu rujukan jalur), `docs/ARCHITECTURE.md`, `docs/KEBIJAKAN-DATA-PRIBADI.md`,
`docs/MATRIKS-SKILL.md`, `resources/js/components/ui/Icon.tsx` dan `resources/views/components/ui/icon.blade.php`
(komentar lisensi).

**Tidak dibuat (terblokir Q18):** `LICENSE`, `.github/CODEOWNERS`.

## Keadaan awal yang terukur (FAKTA, 26 September 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite | 1.252 test, 1.231 lulus, 5 gagal, 15 error, 1 dilewati, 31,3 detik; 20 merah milik W0 | `php artisan test --compact --testsuite=Unit,Feature` |
| TypeScript | 0 galat | `npx tsc --noEmit`, exit 0 |
| Audit PHP | Tidak ada advisori | `composer audit --locked --abandoned=report` |
| Audit npm | Exit 1: `maplibre-gl` sampai 6.4.0 terkena GHSA-jrc7-96c5-q579 (CVE-2026-85061, kritis, CVSS 10.0, diperbaiki di 6.4.1); terpasang 4.7.1. Perintah berjalan dari `package-lock.json` saja, tanpa `node_modules` | `npm audit --audit-level=high`; github.com/advisories/GHSA-jrc7-96c5-q579 |
| Docker | Tidak terpasang | `docker --version`: command not found |
| Remote | Repositori publik `github.com/hanifp62/praktik_aplikasi_web`; `origin/hardening` sama dengan HEAD `6d9e090`; `origin/main` di `68748bd` | `git remote -v`, `git rev-list`, halaman repositori |
| Pohon kerja | 374 entri `git status`; 557 berkas belum dilacak dan tidak diabaikan (migrasi React dan lainnya); pola rahasia atas 557 berkas itu: 0 temuan | `git ls-files --others --exclude-standard`, pindaian pola `SecretsNotInRepoTest` |
| Blok Boost | Identik di `CLAUDE.md` dan `AGENTS.md`, SHA-1 `3e233a75a9edbcc7cadb54f40eb55cc2f8314386`, masing-masing satu kali | `vendor/laravel/boost/src/Install/GuidelineWriter.php:56-63` mengganti blok di tempat |
| Node di runner | Node 20 dihapus dari runner GitHub 23 September 2026; `actions/checkout@v5`, `actions/setup-node@v5`, `actions/upload-artifact@v6`, `shivammathur/setup-php@v2` memakai `node24` | GitHub Changelog 19 September 2025; `action.yml` tiap action |
| Symfony Yaml | v8.1.6, dev transitif; kunci `on:` terbaca sebagai string `on` | `Yaml::parseFile('.github/workflows/ci.yml')` |
| Prototype | `Memount Prototype.html`, 5.289.318 byte, SHA-256 `b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2`, akhir baris LF, 16 layar, 6 entri JPG tanpa segmen EXIF, 246 aset: 0 pola rahasia, 0 alamat email | pembongkaran manifest bundel |
| Gambar acuan | 16 PNG di scratchpad sesi `41b8d56b-cff9-4b16-945c-803c454c9cc5` (daftar SHA-256 di Tugas 2) | `sha256sum` |
| `ourplan.md` | 861 baris CRLF; SHA-256 setelah akhir baris dinormalkan ke LF `4f45e9c461d6f2ae44017a90d194177ae624e7caee4ab4401226088d2ed8e469`; tanpa rujukan §, BR, FR | `hash('sha256', str_replace("\r\n", "\n", ...))` |
| `jelajah-gunung.zip` | PRD NgodingPakeAI 412 baris (satu-satunya salinan lokal; tidak ada salinan identik di `docs/`) dan delapan `features/*.md` yang identik byte dengan folder `features/` | `unzip -l`, `cmp`, `sha256sum` |
| `database/demo.sqlite` | Sudah diabaikan oleh `database/.gitignore:1` (`*.sqlite*`) dan `.gitignore:50` (`/database/*.sqlite`) | `git check-ignore -v` |
| Migrasi | 57 migrasi; 48 tabel dibuat; 6 kolom geografi lewat `App\Support\PostGis::addGeography()` | `database/migrations/*.php` |
| RLS | Migrasi `2026_09_20_000100_tutup_akses_postgrest_ke_tabel_aplikasi` menyalakan RLS pada tabel yang ada saat ia berjalan; hanya `trip_reminders` dan `trail_considerations` yang menyalakannya sendiri sesudahnya | isi migrasi; keadaan Supabase PERLU KLARIFIKASI |
| `.ai/rules` | Tidak ada | `ls .ai` |
| `.git/info/exclude` | Memuat `.impeccable/hook.cache.json`, `hook.pending.json`, `config.local.json` (ditulis pemasang impeccable; hanya berlaku di mesin ini) | `git check-ignore -v --no-index` |

**Rujukan `PRD.md` yang ditemukan** (grep di luar `vendor`, `node_modules`, `docs/riset`, `.git`):

| Berkas | Baris | Tindakan |
|---|---|---|
| `CLAUDE.md` | 22, 55, 78, 79 (dua kali), 190 | Ganti ke `docs/PRD.md` (Tugas 1) |
| `README.md` | 117; 161 sampai 163 (perilaku `plan get`) | Ganti; kalimat perilaku alat ditulis ulang (Tugas 1) |
| `CONTRIBUTING.md` | 108 (perilaku `plan get`) | Tulis ulang (Tugas 1) |
| `DESIGN.md` | 253, 254 | Ganti (Tugas 1) |
| `PRODUCT.md` | 45, 111 | Ganti (Tugas 1) |
| `HANDOVER.md` | 362, 430 (bagian 1 dan 4, masih dipakai sebagai petunjuk) | Ganti (Tugas 1, koordinator) |
| `HANDOVER.md` | 50, 89 | Tidak diubah: baris 50 sudah `docs/PRD.md`, baris 89 riwayat 25 September |
| `docs/ARCHITECTURE.md` | 106, 111 | Ganti (Tugas 1) |
| `docs/KEBIJAKAN-DATA-PRIBADI.md` | 9, 10, 56, 57 | Ganti (Tugas 1) |
| `docs/MATRIKS-SKILL.md` | 43 (perilaku `plan get`) | Tulis ulang (Tugas 1) |
| `.claude/rules/mountain-core.md` | 7 (tanpa backtick) | Ganti ke `docs/PRD.md` (Tugas 1) |
| `PRD.md` sendiri | 15 dan 2267 (`git show HEAD:PRD.md`), 46 (peta kepemilikan), 1722 (risiko R8) | Kunci ke commit `4c7b68e`, peta ditulis ulang, R8 ditulis ulang (Tugas 1) |
| `tests/Feature/PrdReferenceIntegrityTest.php` | 36 (`DOKUMEN_ROOT`), 142 (`prd()`) | Tugas 1 |
| `docs/superpowers/specs/*` (v2, v3, konsolidasi), `docs/superpowers/plans/2026-09-23-konsolidasi-dokumen.md` (21 kali), `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` | beberapa | Tidak diubah: dokumen bertanggal |
| `.ngodingpakeai/pending.json` | 1 | Tidak diubah: keluaran alat, diabaikan git di Tugas 2 |

## Prasyarat (sebelum Tugas 1)

- [ ] **P1: W0 selesai.** Jalankan `php artisan test --compact --testsuite=Unit,Feature`. Harapan: `"result":"passed"`, `failed` 0, `errors` 0. Jalankan `ls docs/fitur/0[1-8]-*.md | wc -l`. Harapan: `8`. Bila salah satu tidak terpenuhi, berhenti: WR bergantung pada W0 (rencana induk), dan Tugas 2 mengabaikan `features/` hanya setelah `docs/fitur/` ada.
- [ ] **P2: Versi (R-020).** Jalankan `php -v`, `composer -V`, `php artisan --version`, `composer show laravel/framework | grep versions`. Harapan: PHP 8.5.10, Composer 2.10.3, Laravel 13.31.0. Bila berbeda, laporkan ketidaksesuaiannya sebelum lanjut.
- [ ] **P3: Potret keadaan git.** Jalankan `git status --porcelain=v1 > "<folder scratchpad sesi Anda>/wr-status-awal.txt"` (jalur scratchpad ada di system prompt eksekutor). Berkas ini dipakai Tugas 10 untuk memisahkan perubahan WR dari perubahan sesi sebelumnya.
- [ ] **P4: Izin yang sudah ada.** Catat di rekap apakah pemilik sudah mengizinkan commit dan apakah Q17 sudah disetujui. Tanpa kutipan izin tertulis, anggap belum.

---

### Task 1: PRD pindah ke `docs/PRD.md` dan rujukan dokumen hidup dijaga test

Tugas ini menyunting `CLAUDE.md` dan `HANDOVER.md`: dikerjakan koordinator, bukan subagen.

**Files:**
- Modify: `tests/Feature/PrdReferenceIntegrityTest.php` (konstanta `JALUR_PRD` dan `DOKUMEN_HIDUP` baru, `DOKUMEN_ROOT` tanpa `PRD.md`, `prd()`, test berkas di `CLAUDE.md` digeneralisasi, satu test baru)
- Move: `PRD.md` ke `docs/PRD.md` (`git mv`)
- Modify: `docs/PRD.md` (baris 15, 17, 27, 28, tabel "Peta kepemilikan" baris 38 sampai 53, R8 baris 1722, tabel §143, GR-04 baru di §146, Lampiran B baris 2267, Lampiran D baris 2478)
- Modify: `CLAUDE.md:22,55,78-79,190` (di luar blok Boost)
- Modify: `README.md:117,161-163`, `CONTRIBUTING.md:108`, `DESIGN.md:253-254`, `PRODUCT.md:45,111`
- Modify: `docs/ARCHITECTURE.md:106,111`, `docs/KEBIJAKAN-DATA-PRIBADI.md:9-10,56-57`, `docs/MATRIKS-SKILL.md:43`
- Modify: `.claude/rules/mountain-core.md:7`, `HANDOVER.md:362,430`
- Modify: `.gitignore` (tambah `/PRD.md`)
- Test: `tests/Feature/PrdReferenceIntegrityTest.php`

**Interfaces:**
- Consumes: tidak ada.
- Produces: jalur `docs/PRD.md`; `PrdReferenceIntegrityTest::JALUR_PRD = 'docs/PRD.md'`; `PrdReferenceIntegrityTest::DOKUMEN_HIDUP` (daftar dokumen hidup; Tugas 5, 7, dan 8 menambah entri); aturan `.gitignore` `/PRD.md`; governance record GR-04 di `docs/PRD.md` §146 yang mencatat seluruh suntingan PRD gelombang WR (Tugas 3 menjalankan suntingan §126 dan §128 yang sudah tercatat di sana).

- [ ] **Step 1: Tulis test yang gagal.** Di `tests/Feature/PrdReferenceIntegrityTest.php`, ganti docblock kelas, konstanta `DOKUMEN_ROOT`, method `test_every_markdown_file_named_in_claude_md_exists`, dan method `prd()`; tambahkan konstanta dan test baru. Bagian lain berkas tidak berubah. Hasil akhirnya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kontrak rujukan PRD.
 *
 * Nomor § di PRD adalah ID permanen, bukan posisi halaman (spec konsolidasi DK-04).
 * Kode, test, dan dokumen merujuknya ratusan kali, jadi bagian yang hilang, bergeser,
 * atau dipakai dua kali memutus rujukan tanpa ada yang menyadarinya. Test ini menjaga
 * enam kontrak: setiap ID yang dirujuk ada, indeks ID lengkap, tidak ada ID yang dipakai
 * ulang, PRD tinggal di docs/PRD.md, berkas yang disebut dokumen hidup ada, dan
 * spesifikasi fitur mengikuti templatnya.
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

    /**
     * PRD pindah ke docs/ lewat GR-04 (spec v3 DK-52). Di root ia pernah tertimpa
     * `npx ngodingpakeai plan get`, yang menulis berkas PRD versi server di sana.
     */
    private const JALUR_PRD = 'docs/PRD.md';

    /**
     * Dokumen hidup di root yang boleh merujuk PRD. Berkas yang belum ada dilewati.
     * PRD sendiri ikut terpindai lewat folder docs/.
     */
    private const DOKUMEN_ROOT = [
        'CLAUDE.md', 'DESIGN.md', 'HANDOVER.md', 'AGENTS.md',
        'README.md', 'CONTRIBUTING.md', 'PRODUCT.md',
    ];

    /**
     * Dokumen yang dibaca sebagai aturan atau pintu masuk hari ini, ditambah delapan
     * spesifikasi di docs/fitur/. Jalur .md yang mereka tulis di antara backtick adalah
     * jalur dari root repo dan wajib ada. Dokumen bertanggal (spec, rencana, riset, audit,
     * arsip) dan HANDOVER.md tidak termasuk: isinya catatan pada tanggalnya, dan menulis
     * ulang catatan adalah menyunting rekaman.
     */
    private const DOKUMEN_HIDUP = [
        'CLAUDE.md', 'AGENTS.md', 'README.md', 'CONTRIBUTING.md', 'DESIGN.md', 'PRODUCT.md',
        'docs/ARCHITECTURE.md', 'docs/KEBIJAKAN-DATA-PRIBADI.md', 'docs/MATRIKS-SKILL.md',
        'docs/PROTOKOL-UJI-KEGUNAAN.md', '.claude/rules/mountain-core.md',
    ];

    private const FOLDER_PERUJUK = [
        'app', 'resources', 'tests', 'database', 'routes', 'config', 'docs', '.claude/rules',
    ];

    /**
     * Frontend React ikut dipindai: sejak migrasi Inertia, rujukan PRD juga tinggal di
     * komponen .tsx, bukan hanya di PHP.
     */
    private const EKSTENSI = ['php', 'md', 'ts', 'tsx', 'js', 'jsx', 'css'];

    /**
     * Arsip riset mengutip dokumen luar dengan penomoran pasalnya sendiri (misalnya
     * manual merek TMEP), jadi tanda pasal di sana bukan rujukan PRD.
     */
    private const FOLDER_DIKECUALIKAN = ['docs/riset/'];

    public function test_every_section_id_referenced_anywhere_has_an_anchor_in_the_prd(): void
    {
        // §0 bukan ID PRD karena indeks dimulai dari §1; di repo ini §0 selalu berarti
        // bagian 0 HANDOVER.md.
        $dirujuk = array_values(array_filter($this->idDirujuk('/§(\d+)/u'), fn (int $id): bool => $id > 0));

        $hilang = array_values(array_diff($dirujuk, $this->anchorBagian()));

        $this->assertSame([], $hilang, 'Bagian PRD dirujuk tetapi tidak ada: §'.implode(', §', $hilang));
    }

    public function test_every_business_rule_and_feature_id_referenced_anywhere_exists_in_the_prd(): void
    {
        $prd = $this->prd();

        preg_match_all('/^\|\s*BR-(\d+)\s*\|/mu', $prd, $br);
        preg_match_all('/^#{2,4}\s+§\d+\s+FR-(\d+)\b/mu', $prd, $fr);

        $brHilang = array_values(array_diff($this->idDirujuk('/\bBR-(\d+)/u'), array_map('intval', $br[1])));
        $frHilang = array_values(array_diff($this->idDirujuk('/\bFR-(\d+)/u'), array_map('intval', $fr[1])));

        $this->assertSame([], $brHilang, 'BR dirujuk tetapi tidak ada di registri §132: '.$this->daftar('BR', $brHilang));
        $this->assertSame([], $frHilang, 'FR dirujuk tetapi tidak ada sebagai heading: '.$this->daftar('FR', $frHilang));
    }

    public function test_the_id_index_is_complete_and_has_no_gaps(): void
    {
        preg_match_all('/^\|\s*§(\d+)\s*\|/mu', $this->prd(), $cocok);
        $indeks = $this->unikUrut(array_map('intval', $cocok[1]));

        $this->assertNotEmpty($indeks, 'Indeks ID tidak ditemukan di PRD.');
        $this->assertSame(range(1, max($indeks)), $indeks, 'Indeks ID melompat.');
        $this->assertSame([], array_values(array_diff($indeks, $this->anchorBagian())), 'Ada ID di indeks tanpa heading atau alias yang sah.');
    }

    public function test_no_id_is_given_to_two_headings_and_feature_and_rule_numbers_have_no_gaps(): void
    {
        $prd = $this->prd();

        preg_match_all('/^#{2,4}\s+§(\d+)\b/mu', $prd, $bagian);
        preg_match_all('/^#{2,4}\s+§\d+\s+FR-(\d+)\b/mu', $prd, $fr);
        preg_match_all('/^\|\s*BR-(\d+)\s*\|/mu', $prd, $br);

        foreach (['§' => $bagian[1], 'FR-' => $fr[1], 'BR-' => $br[1]] as $awalan => $id) {
            $ganda = array_keys(array_filter(array_count_values(array_map('intval', $id)), fn (int $jumlah): bool => $jumlah > 1));

            $this->assertSame([], $ganda, "ID {$awalan} dipakai lebih dari sekali: {$awalan}".implode(", {$awalan}", $ganda));
        }

        foreach (['FR-' => $fr[1], 'BR-' => $br[1]] as $awalan => $id) {
            $nomor = $this->unikUrut(array_map('intval', $id));

            $this->assertNotEmpty($nomor, "Tidak ada {$awalan} di PRD.");
            $this->assertSame(range(1, max($nomor)), $nomor, "Penomoran {$awalan} melompat.");
        }
    }

    public function test_the_prd_lives_in_docs_and_the_root_has_no_stray_copy(): void
    {
        $this->assertFileExists(base_path(self::JALUR_PRD), 'PRD kanonis harus ada di docs/PRD.md (GR-04).');
        $this->assertFileDoesNotExist(
            base_path('PRD.md'),
            'Ada PRD.md di root. Hampir pasti keluaran `npx ngodingpakeai plan get`; PRD kanonis ada di docs/PRD.md. Hapus salinan root itu.'
        );
    }

    public function test_every_markdown_file_named_in_a_living_document_exists(): void
    {
        $hilang = [];
        $spesifikasi = array_map(
            fn (string $path): string => 'docs/fitur/'.basename($path),
            glob(base_path('docs/fitur/0[1-8]-*.md')) ?: []
        );

        foreach (array_merge(self::DOKUMEN_HIDUP, $spesifikasi) as $dokumen) {
            preg_match_all('/`([A-Za-z0-9_\-\/.]+\.md)`/u', File::get(base_path($dokumen)), $cocok);

            foreach (array_unique($cocok[1]) as $jalur) {
                if (! File::exists(base_path($jalur))) {
                    $hilang[] = "{$dokumen} menyebut {$jalur}";
                }
            }
        }

        $this->assertSame([], $hilang, 'Dokumen hidup menyebut berkas yang tidak ada: '.implode('; ', $hilang));
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
        return File::get(base_path(self::JALUR_PRD));
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
        $berkas = array_values(array_filter(
            array_map(base_path(...), self::DOKUMEN_ROOT),
            fn (string $path): bool => File::exists($path)
        ));

        foreach (self::FOLDER_PERUJUK as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                $relatif = str_replace('\\', '/', $dir.'/'.$file->getRelativePathname());

                if (! in_array($file->getExtension(), self::EKSTENSI, true)) {
                    continue;
                }

                foreach (self::FOLDER_DIKECUALIKAN as $kecuali) {
                    if (str_starts_with($relatif, $kecuali)) {
                        continue 2;
                    }
                }

                $berkas[] = $file->getPathname();
            }
        }

        return $berkas;
    }

    /**
     * @param  array<int, int>  $nomor
     */
    private function daftar(string $awalan, array $nomor): string
    {
        return implode(', ', array_map(fn (int $n): string => sprintf('%s-%02d', $awalan, $n), $nomor));
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

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=PrdReferenceIntegrityTest`
Expected: FAIL. `test_the_prd_lives_in_docs_and_the_root_has_no_stray_copy` gagal dengan "PRD kanonis harus ada di docs/PRD.md (GR-04)."; empat test yang membaca PRD berstatus error "File does not exist at path ...docs/PRD.md."; `test_every_markdown_file_named_in_a_living_document_exists` masih lulus karena `PRD.md` masih di root.

- [ ] **Step 3: Pindahkan PRD.**

Run: `git mv PRD.md docs/PRD.md`
Expected: `git status --short docs/PRD.md PRD.md` menampilkan `R  PRD.md -> docs/PRD.md` dan ` M docs/PRD.md` (isi kerja PRD v3.0 yang belum di-commit ikut pindah).

- [ ] **Step 4: Jalankan dan lihat rujukan yang patah.**

Run: `php artisan test --compact --filter=PrdReferenceIntegrityTest`
Expected: hanya `test_every_markdown_file_named_in_a_living_document_exists` gagal, dengan pesan yang menyebut `CLAUDE.md menyebut PRD.md`, `README.md menyebut PRD.md`, `CONTRIBUTING.md menyebut PRD.md`, `DESIGN.md menyebut PRD.md`, `PRODUCT.md menyebut PRD.md`, `docs/ARCHITECTURE.md menyebut PRD.md`, `docs/KEBIJAKAN-DATA-PRIBADI.md menyebut PRD.md`, `docs/MATRIKS-SKILL.md menyebut PRD.md`.

- [ ] **Step 5: Tulis ulang kalimat tentang perilaku `ngodingpakeai plan get` lebih dulu** (kalimat ini menyebut berkas yang ditulis alat di root, jadi tidak boleh ikut penggantian otomatis di Step 6).

`README.md`, ganti tiga baris ini:

```text
**Jangan menjalankan `npx ngodingpakeai plan get` di root repo.** Perintah itu menulis
`PRD.md` dan `features/` dan menimpa PRD kanonis. Jalankan di folder sementara bila
perlu melihat keluarannya.
```

menjadi:

```text
**Jangan menjalankan `npx ngodingpakeai plan get` di root repo.** Perintah itu menulis
berkas PRD versi server dan folder `features/` di root. PRD kanonis ada di `docs/PRD.md`
dan salinan di root ditolak `PrdReferenceIntegrityTest`; jalankan di folder sementara
bila perlu melihat keluarannya.
```

`CONTRIBUTING.md`, ganti baris `- Keluaran `npx ngodingpakeai plan get` di root repo (menimpa `PRD.md`).` menjadi:

```text
- Keluaran `npx ngodingpakeai plan get` di root repo (berkas PRD versi server dan `features/`; PRD kanonis ada di `docs/PRD.md`).
```

`docs/MATRIKS-SKILL.md`, ganti sel terakhir baris `ngodingpakeai` dari `Jangan `plan get` di root (menimpa `PRD.md`)` menjadi `Jangan `plan get` di root (menulis berkas PRD versi server dan `features/`)`.

`docs/PRD.md` baris R8 (§122), ganti seluruh baris menjadi:

```text
| R8 PRD tertimpa lagi | Tinggi | `ngodingpakeai plan get` menulis berkas PRD versi server dan folder `features/` di root. Mitigasi GR-04: PRD kanonis pindah ke `docs/PRD.md`, keluaran alat di root diabaikan git, dan `PrdReferenceIntegrityTest` merah bila berkas PRD muncul lagi di root. Server NgodingPakeAI tetap menyimpan versi lama (spec v2, temuan T9) |
```

- [ ] **Step 6: Ganti rujukan jalur yang tersisa.**

Run:

```bash
cd "E:/praktik_aplikasi_web" && sed -i 's/`PRD\.md`/`docs\/PRD.md`/g' CLAUDE.md DESIGN.md PRODUCT.md README.md docs/ARCHITECTURE.md docs/KEBIJAKAN-DATA-PRIBADI.md docs/PRD.md docs/fitur/0[1-8]-*.md
```

Spesifikasi `docs/fitur/` buatan W0 disertakan untuk berjaga-jaga; menurut rencana W0 isinya tidak menyebut `PRD.md` dengan backtick, jadi biasanya tidak ada yang berubah di sana.

Lalu periksa blok Boost di `CLAUDE.md` tidak tersentuh:

```bash
cd "E:/praktik_aplikasi_web" && php -r '$b = fn (string $f): string => preg_match("/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s", file_get_contents($f), $m) ? sha1($m[0]) : "tidak ada"; echo $b("CLAUDE.md"), " ", $b("AGENTS.md"), PHP_EOL;'
```

Expected: `3e233a75a9edbcc7cadb54f40eb55cc2f8314386 3e233a75a9edbcc7cadb54f40eb55cc2f8314386`.

- [ ] **Step 7: Rujukan tanpa backtick dan HANDOVER (koordinator).**

`.claude/rules/mountain-core.md` baris 7: `  DESIGN.md, PRD.md, riwayat sesi, atau inferensi agen.` menjadi `  DESIGN.md, docs/PRD.md, riwayat sesi, atau inferensi agen.` Bagian Statement, Scope, dan Invariant tidak disentuh.

`HANDOVER.md` baris 362: `Dokumen ini plus `PRD.md` plus `docs/superpowers/specs/` cukup untuk memulihkan seluruh` menjadi `Dokumen ini plus `docs/PRD.md` plus `docs/superpowers/specs/` cukup untuk memulihkan seluruh`.

`HANDOVER.md` baris 430: `` `PRD.md`, 3365 baris, 128 bagian. Yang paling menentukan: `` menjadi `` `docs/PRD.md` (21 September 2026: 3365 baris, 128 bagian). Yang paling menentukan: ``. Baris 89 (riwayat 25 September) tidak diubah.

- [ ] **Step 8: Suntingan isi `docs/PRD.md`.**

Baris 15: `git show HEAD:PRD.md` menjadi `git show 4c7b68e:PRD.md` (commit terakhir yang memuat PRD v2.0 3.670 baris; `HEAD` tidak lagi memuat `PRD.md` di root setelah pemindahan di-commit). Hal yang sama di Lampiran B baris 2267.

Baris 17 dan Lampiran D baris 2478: `docs/riset/2026-09-22-strategi-platform.md` menjadi `docs/arsip/2026-09-22-strategi-platform.md` (berkasnya dibuat Tugas 2, DK-56).

Baris 27: `governance record berikutnya GR-04.` menjadi `governance record berikutnya GR-05.`

Baris 28: kalimat `... indeks tidak boleh melompat, dan tidak ada ID yang dipakai dua kali.` menjadi `... indeks tidak boleh melompat, tidak ada ID yang dipakai dua kali, PRD hanya ada di `docs/PRD.md`, dan setiap berkas `.md` yang disebut dokumen hidup benar-benar ada.`

Tabel "Peta kepemilikan" (baris 38 sampai 53) diganti seluruhnya:

```text
| Jenis isi | Pemilik |
|---|---|
| Pintu masuk tim, cara menjalankan | `README.md` |
| Indeks dokumen tim | `docs/README.md` |
| Kesepakatan kerja tim: izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap, urutan wewenang skill (merangkum dan menunjuk pemilik aturan) | `docs/AGENTS.md` |
| Cara berkontribusi | `CONTRIBUTING.md` |
| Kebijakan keamanan dan pelaporan kerentanan | `SECURITY.md` |
| Kontrak operasi agen, R-018 sampai R-021, R-023, R-024 | `CLAUDE.md` |
| Pintu masuk agen selain Claude: blok Laravel Boost dan penunjuk ke kesepakatan kerja | `AGENTS.md` |
| Aturan domain R-001 sampai R-014 dan R-022 | `.claude/rules/mountain-core.md` |
| Arah desain, R-015 sampai R-017 | `DESIGN.md` di root; `docs/DESIGN.md` hanya penunjuk |
| Visi, misi, north star, ekspektasi pemilik, posisi, persona, prinsip, aturan bisnis BR-01 sampai BR-16, cakupan dan fase, arsitektur informasi, NFR, model domain, metrik, risiko, kesepakatan bersama, tata kelola dan log keputusan | `docs/PRD.md` (dokumen ini) |
| Spesifikasi per fitur, 01 sampai 08 | `docs/fitur/` |
| Arsitektur kode, kontrak SEO teknis, struktur repo | `docs/ARCHITECTURE.md` |
| Skema basis data per domain dan kebijakan migrasi | `database/README.md` |
| Kebijakan data pribadi dan penghapusan akun | `docs/KEBIJAKAN-DATA-PRIBADI.md` |
| Klasifikasi skill dan urutan wewenangnya | `docs/MATRIKS-SKILL.md` |
| Keadaan terkini dan serah terima antarsesi | `HANDOVER.md` |
| Riset asal dan catatan bersumber | `docs/riset/` |
| Dokumen yang tidak lagi hidup, disimpan utuh | `docs/arsip/` |
| Prototype pemilik dan gambar acuannya | `docs/desain/prototype/` |
| Ringkasan produk untuk alat desain impeccable (bukan sumber kebenaran) | `PRODUCT.md` di root; `docs/PRODUCT.md` hanya penunjuk |
| Desain dan rencana kerja bertanggal | `docs/superpowers/` |
```

Tabel §143, tambahkan baris sesudah baris "Dokumen ini":

```text
| Lokasi berkas | `docs/PRD.md` sejak GR-04; keluaran `ngodingpakeai plan get` di root tidak lagi dapat menimpanya |
```

- [ ] **Step 9: Tambahkan GR-04 di §146, sesudah GR-03 dan sebelum "### Log keputusan".**

```text
### GR-04: PRD dan dokumen tim di `docs/` (gelombang WR)

**Requirement changed.** Tidak ada persyaratan produk yang berubah; ID §, BR, dan FR tetap. Yang berubah adalah letak dan rujukan dokumen:

- Berkas PRD pindah dari root ke `docs/PRD.md`; rujukan jalur di dokumen hidup diperbarui dan dijaga `PrdReferenceIntegrityTest`.
- Peta kepemilikan menambah `docs/README.md` (indeks), `docs/AGENTS.md` (kesepakatan kerja tim), `SECURITY.md`, `database/README.md`, `docs/MATRIKS-SKILL.md`, `docs/arsip/`, dan `docs/desain/prototype/`; `DESIGN.md` dan `PRODUCT.md` tetap di root dengan penunjuk di `docs/`.
- Rujukan PRD v2.0 lewat `git show HEAD:PRD.md` dikunci ke commit `4c7b68e`, karena `HEAD` tidak lagi memuat `PRD.md` di root setelah pemindahan di-commit.
- Arsip `ourplan.md` berada di `docs/arsip/2026-09-22-strategi-platform.md` (DK-56), bukan di `docs/riset/` seperti rencana konsolidasi.
- Mitigasi risiko R8 (§122) diperbarui.
- Keadaan nyata CI di §126 dan bukti fase 0 di §128 menunjuk `test.yml`, `security.yml`, dan `deploy.yml` setelah CI dipecah (DK-54).

**Reason.** Arahan pemilik 26 September 2026, verbatim di spec v3 bagian 17.1: "tambahkan folder docs berupa design md, product md, prd md, dan agent md sebagai aturan untuk rekan satu team lainnya". Keputusan DK-52 sampai DK-56 di spec v3 bagian 17.4, disetujui pemilik 26 September 2026. Letak baru juga melindungi PRD dari `npx ngodingpakeai plan get`, yang menulis berkas PRD versi server di root (temuan T9 spec v2).

**Impact.** Tim dan agen membuka PRD di `docs/PRD.md`. Rujukan ID di kode dan test tidak berubah artinya.

**Affected modules.** Dokumentasi: `CLAUDE.md`, `AGENTS.md`, `README.md`, `CONTRIBUTING.md`, `DESIGN.md`, `PRODUCT.md`, `HANDOVER.md`, `.claude/rules/mountain-core.md`, dan dokumen di `docs/`; `tests/Feature/PrdReferenceIntegrityTest.php`. Tidak ada kode aplikasi, skema, atau rute.

**Security impact.** Tidak ada permukaan baru. Berkas PRD di root diabaikan git, sehingga keluaran alat yang memuat PRD lama dari server tidak ikut ter-commit.

**Data impact.** Tidak ada.

**Scope impact.** Tidak ada; batas MVP dan MVP+ tetap (§105, §106). Keputusan DK-30 sampai DK-58 spec v3 masuk log keputusan di gelombang W9 (spec v3 bagian 16).
```

- [ ] **Step 10: Abaikan berkas PRD di root.** Di `.gitignore`, sesudah baris `/.ngodingpakeai/summaries.json`, tambahkan:

```text
# Keluaran `npx ngodingpakeai plan get` di root; PRD kanonis di docs/PRD.md (GR-04).
/PRD.md
```

- [ ] **Step 11: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrityTest|DesignDirectionTest'`
Expected: PASS semua. Lalu:

```bash
cd "E:/praktik_aplikasi_web" && grep -n '`PRD\.md`' CLAUDE.md AGENTS.md README.md CONTRIBUTING.md DESIGN.md PRODUCT.md docs/ARCHITECTURE.md docs/KEBIJAKAN-DATA-PRIBADI.md docs/MATRIKS-SKILL.md docs/PROTOKOL-UJI-KEGUNAAN.md .claude/rules/mountain-core.md docs/PRD.md docs/fitur/0[1-8]-*.md; git check-ignore -v PRD.md
```

Expected: grep tidak mencetak baris apa pun; `check-ignore` mencetak `.gitignore:<baris>:/PRD.md	PRD.md`.

- [ ] **Step 12: Format.** Run: `vendor/bin/pint --format agent tests/Feature/PrdReferenceIntegrityTest.php`. Expected: tanpa perubahan atau hanya perapian format.

- [ ] **Step 13: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah. Commit ini membawa isi PRD v3.0 yang belum pernah di-commit (PRD §143: "menunggu persetujuan pemilik sebelum di-commit"), jadi butuh persetujuan pemilik atas isi PRD selain izin commit.

```bash
git commit -m "docs: PRD pindah ke docs/ dan rujukan dokumen hidup dijaga test (GR-04, DK-52)" -- PRD.md docs/PRD.md CLAUDE.md README.md CONTRIBUTING.md DESIGN.md PRODUCT.md HANDOVER.md docs/ARCHITECTURE.md docs/KEBIJAKAN-DATA-PRIBADI.md docs/MATRIKS-SKILL.md .claude/rules/mountain-core.md .gitignore tests/Feature/PrdReferenceIntegrityTest.php
```

---

### Task 2: Inventaris root dan perapian

**Inventaris root** (setiap entri root pada 26 September 2026, kecuali `.git/`):

| Entri | Keadaan git sekarang | Keputusan | Alasan |
|---|---|---|---|
| `.agents/` | 27 skill dilacak, 14 folder baru belum dilacak | Pertahankan; 14 folder baru tidak di-stage sampai Q19 diputuskan | Salinan skill pihak ketiga (DK-57, Q19) |
| `.claude/` | `rules/`, `settings.json`, keluarga antislop dilacak; skill lain diabaikan | Pertahankan | Aturan kanonis dan batas izin proyek |
| `.codex/`, `.cursor/`, `.zed/` | Diabaikan | Tetap diabaikan | Konfigurasi agen dan editor lokal |
| `.editorconfig`, `.gitattributes`, `.npmrc` | Dilacak | Pertahankan; `.gitattributes` ditambah aturan prototype | Konvensi berkas; `ignore-scripts` dan `audit` npm |
| `.env` | Diabaikan | Tidak disentuh | Rahasia |
| `.env.example` | Dilacak | Pertahankan; komentar PostGIS lokal di Tugas 4 | Templat tanpa nilai rahasia |
| `.github/` | Dilacak | Pertahankan; workflow dipecah (Tugas 3), templat (Tugas 5) | CI dan tata kelola |
| `.gitignore` | Dilacak | Diperketat | DK-56 |
| `.impeccable/` | Hanya `hook.cache.json`, diabaikan oleh `.git/info/exclude` lokal yang ditulis pemasang impeccable (tidak dibagikan ke tim) | Salin tiga pola pemasang itu ke `.gitignore` (`hook.cache.json`, `hook.pending.json`, `config.local.json`); berkas impeccable lain di masa depan harus diputuskan | Keadaan lokal alat |
| `.mcp.json.disabled` | Belum dilacak | Abaikan git (`*.disabled`) | Konfigurasi lokal nonaktif |
| `.ngodingpakeai/` | Belum dilacak | Abaikan git seluruh folder setelah `docs/fitur/` ada | Ruang kerja alat (DK-56) |
| `.ngodingpakeaiignore` | Belum dilacak | Pertahankan dan lacak (keputusan agen, dapat dibatalkan pemilik) | Gerbang CLI NgodingPakeAI; satu-satunya cara berbagi aturan pengecualian sinkron antaranggota tim |
| `.phpunit.result.cache`, `.phpunit.cache/` | Diabaikan | Tetap diabaikan | Cache PHPUnit |
| `.ruff_cache/` | Diabaikan oleh `.gitignore` miliknya sendiri | Tambah aturan eksplisit `/.ruff_cache/` | Cache linter Python lokal |
| `.superpowers/` | Diabaikan | Tetap diabaikan | Ruang kerja subagen |
| `AGENTS.md`, `CLAUDE.md`, `HANDOVER.md`, `README.md`, `CONTRIBUTING.md` | Dilacak | Pertahankan di root | Pintu masuk yang dibaca alat dan orang dari root |
| `DESIGN.md`, `PRODUCT.md` | Dilacak | Pertahankan di root; penunjuk di `docs/` (Tugas 8) | Dibaca impeccable dan antislop dari root (DK-52) |
| `PRD.md` | Sudah dipindah di Tugas 1 | Tercatat | DK-52 |
| `Memount Prototype.html` | Belum dilacak | Pindah ke `docs/desain/prototype/memount-prototype.html` | DK-47 |
| `ourplan.md` | Belum dilacak | Pindah ke `docs/arsip/2026-09-22-strategi-platform.md` | DK-56; nama mengikuti PRD baris 17 |
| `features/` | Belum dilacak | Abaikan git setelah `docs/fitur/` ada | Ruang kerja NgodingPakeAI (DK-56) |
| `jelajah-gunung.zip` | Diabaikan | Keputusan pemilik: arsipkan isinya ke `docs/arsip/2026-09-22-ngodingpakeai/` lalu hapus, atau biarkan diabaikan. Usul: arsipkan lalu hapus | Isinya PRD NgodingPakeAI (satu-satunya salinan lokal) dan delapan features yang identik |
| `opencode.json.disabled` | Diabaikan dengan aturan khusus | Abaikan lewat `*.disabled`; aturan khusus dicabut | Satu aturan untuk semua konfigurasi nonaktif |
| `boost.json` | Belum dilacak | Lacak | Konfigurasi `php artisan boost:update` untuk semua anggota tim |
| `tsconfig.json` | Belum dilacak | Lacak | Bagian build React |
| `skills-lock.json` | Dilacak | Pertahankan | Kunci versi skill pihak ketiga (Q19) |
| `app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `phpunit.xml`, `postcss.config.js`, `tailwind.config.js`, `vite.config.js` | Dilacak (sebagian berkas baru belum dilacak) | Pertahankan (DK-51) | Susunan Laravel |
| `node_modules/`, `vendor/` | Diabaikan | Tetap diabaikan | Dependensi terpasang |
| `docker-compose.yml` (baru, Tugas 4), `SECURITY.md` (baru, Tugas 5), `playwright.config.ts` (baru, Tugas 9 bersyarat) | Belum ada | Tambah | DK-55, §17.3, Q17 |

**Files:**
- Create: `tests/Feature/RepoHygieneTest.php`
- Move (berkas belum dilacak, `mv` biasa): `Memount Prototype.html` ke `docs/desain/prototype/memount-prototype.html`
- Create: `docs/desain/prototype/acuan/00-onboarding.png` sampai `docs/desain/prototype/acuan/15-jelajah-lebar.png` (16 berkas)
- Create: `docs/desain/prototype/README.md`
- Move (`mv` biasa): `ourplan.md` ke `docs/arsip/2026-09-22-strategi-platform.md`
- Modify: `.gitignore`, `.gitattributes`, `DESIGN.md:128`
- Test: `tests/Feature/RepoHygieneTest.php`

**Interfaces:**
- Consumes: aturan `.gitignore` `/PRD.md` dari Tugas 1; `docs/fitur/` dari W0.
- Produces: `RepoHygieneTest::ENTRI_ROOT` (Tugas 4 menambah `docker-compose.yml`, Tugas 5 menambah `SECURITY.md`, Tugas 9 menambah `playwright.config.ts`), `RepoHygieneTest::HARUS_DIABAIKAN` (Tugas 9 menambah keluaran e2e); jalur `docs/desain/prototype/`, `docs/arsip/`.

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction RepoHygieneTest`, lalu ganti seluruh isi berkas:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Root repo hanya berisi entri yang sudah diputuskan.
 *
 * Inventaris root gelombang WR (spec v3 DK-56) memberi setiap berkas dan folder root
 * satu keputusan: dilacak, dipindah, atau diabaikan git. Test ini menjaga keputusan itu
 * tetap berlaku sesudah hari ini. Keluaran alat yang mendarat di root, misalnya PRD dan
 * features/ dari `ngodingpakeai plan get` atau folder design-system/ dari langkah
 * "Persist Design System" ui-ux-pro-max yang diarahkan ke DESIGN.md oleh DK-57, membuat
 * suite merah sampai seseorang memutuskannya dengan sadar.
 */
class RepoHygieneTest extends TestCase
{
    /**
     * Entri root yang dilacak git atau akan ikut commit. Menambah entri berarti menambah
     * baris inventaris beserta alasannya.
     */
    private const ENTRI_ROOT = [
        '.agents', '.claude', '.editorconfig', '.env.example', '.gitattributes', '.github',
        '.gitignore', '.ngodingpakeaiignore', '.npmrc', 'AGENTS.md', 'CLAUDE.md',
        'CONTRIBUTING.md', 'DESIGN.md', 'HANDOVER.md', 'PRODUCT.md', 'README.md',
        'app', 'artisan', 'boost.json', 'bootstrap', 'composer.json', 'composer.lock',
        'config', 'database', 'docs', 'lang', 'package-lock.json', 'package.json',
        'phpunit.xml', 'postcss.config.js', 'public', 'resources', 'routes',
        'skills-lock.json', 'storage', 'tailwind.config.js', 'tests', 'tsconfig.json',
        'vite.config.js',
    ];

    /**
     * Keadaan lokal, ruang kerja alat, arsip pemilik, dan dump yang tidak boleh ikut
     * commit. Jalurnya contoh; yang diuji adalah aturan .gitignore yang menangkapnya.
     */
    private const HARUS_DIABAIKAN = [
        '.env',
        '.mcp.json.disabled',
        'opencode.json.disabled',
        'konfigurasi-lain.disabled',
        '.ruff_cache/CACHEDIR.TAG',
        '.phpunit.result.cache',
        '.impeccable/hook.cache.json',
        '.impeccable/hook.pending.json',
        '.impeccable/config.local.json',
        '.ngodingpakeai/config.json',
        'features/01-jelajah-rute.md',
        'jelajah-gunung.zip',
        'PRD.md',
        'cadangan-supabase.sql',
        'cadangan-supabase.sql.gz',
        'database/cadangan.dump',
        'database/demo.sqlite',
    ];

    /**
     * Bahan tim yang tidak boleh tertelan aturan abaikan yang terlalu luas.
     */
    private const TIDAK_BOLEH_DIABAIKAN = [
        'docs/PRD.md',
        'docs/desain/prototype/memount-prototype.html',
        'docs/desain/prototype/acuan/00-onboarding.png',
        'docs/arsip/2026-09-22-strategi-platform.md',
        'database/migrations/2026_09_19_000220_create_trails_table.php',
        'boost.json',
        'tsconfig.json',
        '.ngodingpakeaiignore',
        'skills-lock.json',
    ];

    private const GAMBAR_ACUAN = [
        '00-onboarding', '01-beranda', '02-jelajah', '03-gunung', '04-detail-jalur',
        '05-route-fit', '06-simpanan', '07-bandingkan', '08-rencanakan', '09-persiapan',
        '10-cek-kesiapan', '11-mendaki', '12-ringkasan', '13-progres', '14-profil',
        '15-jelajah-lebar',
    ];

    /**
     * SHA-256 berkas prototype yang diserahkan pemilik 25 September 2026.
     */
    private const SHA_PROTOTYPE = 'b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2';

    /**
     * SHA-256 ourplan.md dengan akhir baris dinormalkan ke LF, karena git menormalkannya
     * saat commit (.gitattributes: text=auto eol=lf).
     */
    private const SHA_STRATEGI = '4f45e9c461d6f2ae44017a90d194177ae624e7caee4ab4401226088d2ed8e469';

    public function test_the_root_holds_only_inventoried_entries(): void
    {
        $entri = array_values(array_unique(array_map(
            fn (string $jalur): string => explode('/', $jalur, 2)[0],
            $this->berkasYangAkanDicommit()
        )));

        $baru = array_values(array_diff($entri, self::ENTRI_ROOT));

        $this->assertSame(
            [],
            $baru,
            'Entri root tanpa keputusan inventaris: '.implode(', ', $baru).'. Pindahkan ke tempatnya, abaikan git, atau tambahkan ke ENTRI_ROOT beserta alasannya.'
        );
    }

    public function test_local_state_tool_workspaces_and_dumps_stay_out_of_git(): void
    {
        $lolos = array_values(array_filter(self::HARUS_DIABAIKAN, fn (string $jalur): bool => ! $this->diabaikan($jalur)));

        $this->assertSame([], $lolos, 'Tidak diabaikan git padahal harus: '.implode(', ', $lolos));
    }

    public function test_team_material_is_never_swallowed_by_an_ignore_rule(): void
    {
        $tertelan = array_values(array_filter(self::TIDAK_BOLEH_DIABAIKAN, fn (string $jalur): bool => $this->diabaikan($jalur)));

        $this->assertSame([], $tertelan, 'Bahan tim tertelan aturan .gitignore: '.implode(', ', $tertelan));
    }

    public function test_the_owner_prototype_and_its_sixteen_reference_images_live_in_docs(): void
    {
        $prototype = base_path('docs/desain/prototype/memount-prototype.html');

        $this->assertFileDoesNotExist(base_path('Memount Prototype.html'));
        $this->assertFileExists($prototype);
        $this->assertSame(self::SHA_PROTOTYPE, hash_file('sha256', $prototype), 'Isi prototype berbeda dari berkas yang diserahkan pemilik.');

        exec('git -C '.escapeshellarg(base_path()).' check-attr binary -- docs/desain/prototype/memount-prototype.html', $atribut);
        $this->assertStringContainsString('binary: set', implode("\n", $atribut), 'Prototype harus disimpan sebagai artefak biner (.gitattributes).');

        $panduan = File::get(base_path('docs/desain/prototype/README.md'));

        foreach (self::GAMBAR_ACUAN as $nama) {
            $gambar = base_path("docs/desain/prototype/acuan/{$nama}.png");

            $this->assertFileExists($gambar);
            $this->assertSame("\x89PNG", (string) file_get_contents($gambar, false, null, 0, 4), "{$nama}.png bukan berkas PNG.");
            $this->assertStringContainsString("{$nama}.png", $panduan, "{$nama}.png tidak dijelaskan di README prototype.");
        }
    }

    public function test_the_strategy_research_is_archived_whole_not_left_at_the_root(): void
    {
        $arsip = base_path('docs/arsip/2026-09-22-strategi-platform.md');

        $this->assertFileDoesNotExist(base_path('ourplan.md'));
        $this->assertFileExists($arsip);
        $this->assertSame(self::SHA_STRATEGI, hash('sha256', str_replace("\r\n", "\n", File::get($arsip))), 'Arsip strategi tidak lagi utuh.');
    }

    /**
     * Berkas yang dilacak ditambah berkas baru yang tidak diabaikan: persis yang akan masuk
     * bila seseorang menjalankan `git add -A`.
     *
     * @return array<int, string>
     */
    private function berkasYangAkanDicommit(): array
    {
        exec('git -C '.escapeshellarg(base_path()).' ls-files -z --cached --others --exclude-standard', $keluaran, $kode);

        if ($kode !== 0) {
            $this->markTestSkipped('Bukan repositori git.');
        }

        return array_values(array_filter(explode("\0", implode("\n", $keluaran))));
    }

    private function diabaikan(string $jalur): bool
    {
        exec('git -C '.escapeshellarg(base_path()).' check-ignore -q --no-index -- '.escapeshellarg($jalur), $keluaran, $kode);

        return $kode === 0;
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=RepoHygieneTest`
Expected: FAIL. `test_the_root_holds_only_inventoried_entries` menyebut `.mcp.json.disabled`, `.ngodingpakeai`, `Memount Prototype.html`, `features`, `ourplan.md`; `test_local_state_tool_workspaces_and_dumps_stay_out_of_git` menyebut `.mcp.json.disabled`, `konfigurasi-lain.disabled`, `.ngodingpakeai/config.json`, `features/01-jelajah-rute.md`, `cadangan-supabase.sql`, `cadangan-supabase.sql.gz`, `database/cadangan.dump`; dua test berkas pindahan gagal karena berkasnya belum ada. Tiga jalur `.impeccable/` sudah lulus di mesin ini karena `.git/info/exclude` lokal memuatnya; `git check-ignore --no-index` ikut membaca berkas itu, sedangkan di CI hanya `.gitignore` yang berlaku, jadi aturan di Step 8 tetap wajib.

- [ ] **Step 3: Pindahkan prototype.**

```bash
cd "E:/praktik_aplikasi_web" && mkdir -p docs/desain/prototype/acuan && mv "Memount Prototype.html" docs/desain/prototype/memount-prototype.html && sha256sum docs/desain/prototype/memount-prototype.html
```

Expected: `b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2  docs/desain/prototype/memount-prototype.html`.

- [ ] **Step 4: Salin 16 gambar acuan dan periksa keutuhannya.**

```bash
cd "E:/praktik_aplikasi_web" && cp "C:/Users/ACER/AppData/Local/Temp/claude/E--praktik-aplikasi-web/41b8d56b-cff9-4b16-945c-803c454c9cc5/scratchpad/proto-png/"*.png docs/desain/prototype/acuan/ && sha256sum -c - <<'EOF'
87c3cae95bf18de6fe4c13a7efe41e18542fe611a17c6d458fb8993059ddeaff  docs/desain/prototype/acuan/00-onboarding.png
693f0878603d90d33356f36ff078416609c145c5a82a299989075be092142935  docs/desain/prototype/acuan/01-beranda.png
c3eb66b89463178ad871ffc183c4a6ec3281dcf0c7f51c6b0ad9aacef7045bae  docs/desain/prototype/acuan/02-jelajah.png
cfaf82c595e32af013b0f42712d912bfed5d28de20d0eede026d1a6a87065274  docs/desain/prototype/acuan/03-gunung.png
408eaaa54b85c1dda0bebcd6021e6ca2e9d11e37887a3c4c53a1e7ae9296a5a7  docs/desain/prototype/acuan/04-detail-jalur.png
f361df9e7d05ed6f533964277f8c46ccf80dbf6ce0f000aa9e3495a7f6948104  docs/desain/prototype/acuan/05-route-fit.png
a620ccbd3d1f1d268087d732bbfea2f6f59fc89a17a8065ebc93a453c5faf4df  docs/desain/prototype/acuan/06-simpanan.png
39dec7959562eb8511cc2dece9ea8899d9d23de28b1663fd28e96d1ae5f85731  docs/desain/prototype/acuan/07-bandingkan.png
1a25346e1f90e511fd4401aa2bbb6aae0f7966bbe664ddfc347c1a7236cfbb95  docs/desain/prototype/acuan/08-rencanakan.png
830a4f70b65f3044d46360f22fc51024ad72f940ee1a45636328b7c4f382cd1b  docs/desain/prototype/acuan/09-persiapan.png
bb9da612c5e0958974742b91b87cef43c2d1893c1a2e60604f292d0381fb1e9e  docs/desain/prototype/acuan/10-cek-kesiapan.png
b29e67cbfe6fa3d329eea76ce269e006b968224082c7f802908b2427894d80f4  docs/desain/prototype/acuan/11-mendaki.png
f61b340262723be57f8b8e88142e802aaa8bd3af786e41582e762d53b5c8f099  docs/desain/prototype/acuan/12-ringkasan.png
b636039d430b9ba55f77b75891ba54ed8140df5f79f837e62aed6d9b7a68bd1c  docs/desain/prototype/acuan/13-progres.png
d265cf0ca15119d3e939d2322b1e969ee7428c062a311c8436a42615676f778e  docs/desain/prototype/acuan/14-profil.png
1151a146b301297fea77c9120a1667cf96e9fca4b12e5737b91d6de0a05db8a8  docs/desain/prototype/acuan/15-jelajah-lebar.png
EOF
```

Expected: 16 baris berakhiran `OK`.

**Bila folder scratchpad itu sudah tidak ada** (`cp` gagal "No such file"), buat ulang dari prototype dengan Chrome DevTools MCP, lalu lewati `sha256sum -c` (hasil ulang bergantung pada rasio piksel perangkat, jadi hash-nya berbeda; nama berkas tetap):
1. `mcp__plugin_ecc_chrome-devtools__new_page` dengan url `file:///E:/praktik_aplikasi_web/docs/desain/prototype/memount-prototype.html`.
2. `mcp__plugin_ecc_chrome-devtools__resize_page` lebar 1600, tinggi 1000 (bingkai terlebar 1440 px harus muat).
3. `mcp__plugin_ecc_chrome-devtools__wait_for` teks `15 · Jelajah di layar lebar` (judul bingkai terakhir; bundel selesai dibongkar).
4. `mcp__plugin_ecc_chrome-devtools__take_snapshot`, catat `uid` 16 elemen iframe menurut atribut `title`.
5. Untuk setiap iframe, `mcp__plugin_ecc_chrome-devtools__take_screenshot` dengan `uid` iframe itu dan `filePath` `E:/praktik_aplikasi_web/docs/desain/prototype/acuan/<nama>.png`, memakai pemetaan judul ke nama di tabel README Step 5.
6. Periksa: `ls docs/desain/prototype/acuan/*.png | wc -l` harus `16`.

- [ ] **Step 5: Tulis `docs/desain/prototype/README.md`.**

```markdown
# Prototype pemilik

Prototype Memount yang diserahkan pemilik produk pada 25 September 2026 dan dijadikan
patokan desain serta UI/UX (keputusan DK-30; spec v3 bagian 1 dan 3). Berkas dan gambar
acuannya dipindah dari root ke sini dan dilacak git sebagai rujukan tim (DK-47, DK-56).

| Berkas | Isi |
|---|---|
| memount-prototype.html | Bundel asli 5,3 MB: 16 layar dalam bingkai iframe; buka di Chrome dengan JavaScript aktif. SHA-256 b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2, dijaga `RepoHygieneTest` |
| acuan/ | 16 gambar acuan, satu per layar, untuk disandingkan dengan tangkapan 390 px dan 1440 px setiap gelombang (spec v3 bagian 13) |

## Layar

| Gambar | Judul bingkai di prototype | Ukuran bingkai (px) |
|---|---|---|
| acuan/00-onboarding.png | 00 · Profil pendaki (onboarding) | 390 x 844 |
| acuan/01-beranda.png | 01 · Beranda | 390 x 1960 |
| acuan/02-jelajah.png | 02 · Jelajah (daftar, peta, saringan) | 390 x 844 |
| acuan/03-gunung.png | 03 · Halaman gunung | 390 x 1780 |
| acuan/04-detail-jalur.png | 04 · Detail jalur | 390 x 3960 |
| acuan/05-route-fit.png | 05 · Route Fit: kenapa? | 390 x 2020 |
| acuan/06-simpanan.png | 06 · Simpanan + catatan pribadi | 390 x 844 |
| acuan/07-bandingkan.png | 07 · Bandingkan (maks. 3) | 390 x 1640 |
| acuan/08-rencanakan.png | 08 · Rencanakan trip | 390 x 1700 |
| acuan/09-persiapan.png | 09 · Trip: persiapan | 390 x 2080 |
| acuan/10-cek-kesiapan.png | 10 · Cek kesiapan | 390 x 1720 |
| acuan/11-mendaki.png | 11 · Mendaki (mode lapangan) | 390 x 844 |
| acuan/12-ringkasan.png | 12 · Ringkasan + laporan kondisi | 390 x 1940 |
| acuan/13-progres.png | 13 · Progres | 390 x 1880 |
| acuan/14-profil.png | 14 · Profil | 390 x 1740 |
| acuan/15-jelajah-lebar.png | 15 · Jelajah di layar lebar | 1440 x 900 |

## Yang diambil dan yang dikoreksi

Prototype adalah patokan pola, hierarki, bahasa visual, dan alur, bukan angka dan teks per
piksel: semua angka dan teks di aplikasi berasal dari sistem (asumsi A8 spec v3). Koreksi
terukur atas prototype dicatat di DK-31 (kontras batas isian dan garis komunitas, label tab
12 px, tinggi kartu mengikuti isi, waktu tempuh sebagai rentang bersumber, tombol "Jadikan
dasar rencana" berbobot setara). Huruf Geist dan ikon bergaya Lucide di prototype tidak
dipakai; penggantinya DK-38 dan DK-39. Rinciannya di spec v3 bagian 3 dan 8.

## Membuat ulang gambar acuan

Buka memount-prototype.html di Chrome desktop dengan jendela selebar minimal 1500 px dan
tunggu tulisan "Unpacking..." hilang. Setiap layar tampil sebagai bingkai bertajuk seperti di
tabel. Di DevTools, panel Elements, klik kanan node iframe setiap bingkai, pilih "Capture node
screenshot", lalu simpan dengan nama di tabel. Resolusi hasilnya mengikuti rasio piksel
perangkat, jadi hash berkas baru boleh berbeda; namanya tidak.

## Catatan hak

Bundel memuat huruf Geist, Geist Mono, dan Newsreader (SIL Open Font License 1.1) serta foto
JPG tanpa metadata EXIF (diperiksa 26 September 2026). Sumber dan lisensi foto belum
tercatat, sehingga prototype tidak dipakai sebagai sumber aset aplikasi; foto aplikasi
mengikuti DK-37.
```

- [ ] **Step 6: Arsipkan riset strategi.**

```bash
cd "E:/praktik_aplikasi_web" && mkdir -p docs/arsip && mv ourplan.md docs/arsip/2026-09-22-strategi-platform.md && php -r 'echo hash("sha256", str_replace("\r\n", "\n", file_get_contents("docs/arsip/2026-09-22-strategi-platform.md"))), PHP_EOL;'
```

Expected: `4f45e9c461d6f2ae44017a90d194177ae624e7caee4ab4401226088d2ed8e469`.

- [ ] **Step 7: Perbarui rujukan di `DESIGN.md` baris 128.** `Traveloka) serta `ourplan.md` ("Mobile: Bottom nav") menaruh tab di sana. Ikon di bilah` menjadi `Traveloka) serta riset strategi `docs/arsip/2026-09-22-strategi-platform.md` ("Mobile: Bottom nav") menaruh tab di sana. Ikon di bilah`.

- [ ] **Step 8: Tulis ulang `.gitignore` seluruhnya.**

```text
*.log
.DS_Store
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
/.codex
/.cursor/
/.idea
/.nova
/.phpunit.cache
/.vscode
/.zed
/auth.json
/node_modules
/public/build
/public/fonts-manifest.dev.json
/public/hot
/public/storage
# Rute bertipe hasil Wayfinder, dibangkitkan ulang oleh plugin Vite setiap build.
/resources/js/actions
/resources/js/routes
/resources/js/wayfinder
/storage/*.key
/storage/pail
/vendor
_ide_helper.php
Homestead.json
Homestead.yaml
Thumbs.db

# Skill yang dipasang lewat npx (emilkowalski, impeccable, taste-skill).
#
# Berkas aslinya ada di .agents/skills dan itu yang dilacak. Salinan di
# .claude/skills berupa symlink berjalur absolut yang hanya berlaku di mesin
# pemasangnya, dan .github/skills salinan ketiga dari isi yang sama. Skill
# antislop yang sudah lebih dulu dilacak tidak terpengaruh aturan ini.
/.claude/skills/*
/.github/skills/
/.claude/agents/impeccable-*.md
/.claude/settings.local.json

# Mesin impeccable: 15 MB biner Windows-only, dan tiga salinannya. Dapat dipasang
# ulang dengan `npx impeccable install`, jadi tidak ada alasan menanamkannya
# selamanya di riwayat git.
*/skills/impeccable/scripts/bin/

# Basis data lokal untuk menjalankan aplikasi tanpa menyentuh Supabase produksi.
# Data contoh database/demo.sqlite (DK-35) ikut tertutup aturan ini dan
# database/.gitignore (*.sqlite*).
/database/*.sqlite

# Dump basis data tidak pernah masuk repo: isinya dapat memuat data pengguna
# sungguhan. Skema kanonis adalah database/migrations. Aturan ini tidak memengaruhi
# berkas yang sudah dilacak; berkas SQL yang memang harus dilacak ditambahkan
# dengan `git add -f` atas keputusan pemilik.
*.sql
*.sql.gz
*.dump

# Ruang kerja subagent-driven-development, bukan bagian produk.
/.superpowers/

# Ruang kerja NgodingPakeAI (DK-56). Spesifikasi fitur kanonis ada di docs/fitur/
# dan PRD di docs/PRD.md; features/, .ngodingpakeai/, dan PRD.md di root adalah
# keluaran CLI (`sync --plan`, `plan get`) yang dapat dibangkitkan ulang.
/.ngodingpakeai/
/features/
/PRD.md

# Konfigurasi lokal yang dinonaktifkan dan cache alat lokal. Tiga berkas impeccable
# sama dengan yang ditulis pemasangnya ke .git/info/exclude, yang tidak ikut ke clone
# anggota tim lain.
*.disabled
/.ruff_cache/
/.impeccable/hook.cache.json
/.impeccable/hook.pending.json
/.impeccable/config.local.json

# Ekspor NgodingPakeAI milik pemilik (PRD versi server dan delapan features/).
# Menunggu keputusan pemilik: diarsipkan ke docs/arsip/ lalu dihapus, atau tetap di sini.
/jelajah-gunung.zip
```

- [ ] **Step 9: Tambahkan aturan prototype ke `.gitattributes`**, sesudah baris `*.php diff=php` dan sebelum baris kosong:

```text

# Prototype pemilik (DK-47): artefak rujukan yang disimpan apa adanya, tanpa diff teks,
# dan tidak dihitung dalam statistik bahasa GitHub.
/docs/desain/prototype/*.html binary linguist-documentation
```

- [ ] **Step 10: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='RepoHygieneTest|PrdReferenceIntegrityTest|DesignDirectionTest'`
Expected: PASS semua. Lalu `git status --short -- docs/desain docs/arsip features .ngodingpakeai` menampilkan `?? docs/arsip/` dan `?? docs/desain/` saja.

- [ ] **Step 11: `jelajah-gunung.zip`, hanya atas keputusan tertulis pemilik.** Tanpa keputusan: tidak ada perubahan; catat di daftar keputusan M1. Bila pemilik memilih "arsipkan lalu hapus":

```bash
cd "E:/praktik_aplikasi_web" && mkdir -p docs/arsip/2026-09-22-ngodingpakeai && unzip -q jelajah-gunung.zip -d docs/arsip/2026-09-22-ngodingpakeai && unzip -l jelajah-gunung.zip | tail -1 && find docs/arsip/2026-09-22-ngodingpakeai -type f | wc -l
```

Expected: `65217 9 files` dan `9`. Sesudah itu baru `rm jelajah-gunung.zip` (tidak dapat dipulihkan dari git karena berkasnya diabaikan), hapus baris `/jelajah-gunung.zip` beserta komentarnya dari `.gitignore`, hapus `'jelajah-gunung.zip'` dari `RepoHygieneTest::HARUS_DIABAIKAN`, dan jalankan ulang Step 10.

- [ ] **Step 12: Format.** Run: `vendor/bin/pint --format agent tests/Feature/RepoHygieneTest.php`.

- [ ] **Step 13: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add tests/Feature/RepoHygieneTest.php docs/desain docs/arsip boost.json tsconfig.json .ngodingpakeaiignore
git commit -m "chore: root diinventaris; prototype, gambar acuan, dan riset strategi pindah ke docs/ (DK-56)" -- tests/Feature/RepoHygieneTest.php docs/desain docs/arsip boost.json tsconfig.json .ngodingpakeaiignore .gitignore .gitattributes DESIGN.md
```

---

### Task 3: CI dipecah menjadi `test.yml`, `security.yml`, `deploy.yml`; pemindai rahasia mencakup berkas baru

> Catatan eksekusi 26 September 2026 (ruling koordinator W8, dari tinjauan tugas): isi verbatim di bawah diperkuat
> sesudah eksekusi. `CiWorkflowsTest` juga mengunci perintah suite Spatial, urutan build terhadap ketiga suite, tidak
> adanya `secrets` di job `verifikasi`, dan izin tingkat job; langkah `npm audit` di `security.yml` memakai
> `if: always()` dan pemindai rahasia berjalan dengan `--fail-on-skipped`; `SecretsNotInRepoTest` mendapat pola DSN
> `pgsql://` berkata sandi yang mengecualikan host loopback (127.0.0.1, localhost, `[::1]`; ruling W9 menggantikan
> pengecualian kredensial persis dari W8); dua kalimat README dan CONTRIBUTING tentang CI diluruskan. Isi berkas nyata
> di repo adalah acuannya.

**Files:**
- Move: `.github/workflows/ci.yml` ke `.github/workflows/test.yml` (`git mv`, riwayat berkas terjaga), lalu Modify
- Create: `.github/workflows/security.yml`, `.github/workflows/deploy.yml`
- Create: `tests/Feature/CiWorkflowsTest.php`
- Modify: `tests/Feature/SecretsNotInRepoTest.php`
- Modify: `README.md:91-92`, `CONTRIBUTING.md` (paragraf "Gerbang di CI"), `docs/PRD.md` (§126 amandemen, baris fase 0 di §128)
- Test: `tests/Feature/CiWorkflowsTest.php`, `tests/Feature/SecretsNotInRepoTest.php`

**Interfaces:**
- Consumes: GR-04 di `docs/PRD.md` (Tugas 1) yang sudah mencatat suntingan §126 dan §128.
- Produces: workflow `test.yml` dengan `on.workflow_call`, job `test`, layanan `postgis` (citra `postgis/postgis:16-3.4`, env `POSTGRES_USER: hiking`, `POSTGRES_PASSWORD: hiking`, `POSTGRES_DB: hiking_test` dalam urutan itu; dibaca Tugas 4); `security.yml` dengan job `audit-dependensi` dan `pemindai-rahasia`; `deploy.yml` dengan job `verifikasi` (memanggil `./.github/workflows/test.yml`) dan `artefak-build`; `SecretsNotInRepoTest::berkasYangAkanDicommit(): array`.

- [ ] **Step 1: Tulis `CiWorkflowsTest` yang gagal.** Run: `php artisan make:test --phpunit --no-interaction CiWorkflowsTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * CI dipecah tanpa kehilangan satu gerbang pun (spec v3 DK-54).
 *
 * Riwayat ci.yml mencatat dua kegagalan yang hanya terlihat dari urutan dan bentuk
 * langkahnya: build aset pernah berada di bawah test sehingga seluruh test halaman
 * gagal, dan dua opsi --testsuite pernah saling menimpa sehingga 104 test Unit tidak
 * pernah dijalankan. Memecah satu berkas menjadi tiga mengulang risiko yang sama, jadi
 * bentuk ketiganya dibaca sebagai data di sini.
 *
 * symfony/yaml hadir sebagai dependensi dev transitif (laravel/roster lewat
 * laravel/boost); test ini tidak menambah dependensi.
 */
class CiWorkflowsTest extends TestCase
{
    private const FOLDER = '.github/workflows';

    private const WORKFLOW = ['test.yml', 'security.yml', 'deploy.yml'];

    private const CABANG_PUSH = ['main', 'develop', 'hardening'];

    private const CABANG_PR = ['main', 'develop'];

    /**
     * Mayor terendah per action yang berjalan di Node 24. Runner GitHub melepas Node 20
     * pada 23 September 2026 (GitHub Changelog, 19 September 2025). Action baru ditinjau
     * runtime-nya lebih dulu, lalu ditambahkan di sini.
     */
    private const MAYOR_MINIMUM = [
        'actions/checkout' => 5,
        'actions/setup-node' => 5,
        'actions/upload-artifact' => 6,
        'shivammathur/setup-php' => 2,
    ];

    public function test_ci_is_split_into_test_security_and_deploy(): void
    {
        foreach (self::WORKFLOW as $berkas) {
            $this->assertFileExists(base_path(self::FOLDER.'/'.$berkas));
        }

        $this->assertFileDoesNotExist(
            base_path(self::FOLDER.'/ci.yml'),
            'ci.yml sudah dipecah; bila tetap ada, suite yang sama berjalan dua kali di setiap push.'
        );
    }

    public function test_the_test_workflow_keeps_every_gate_in_the_proven_order(): void
    {
        $alur = $this->alur('test.yml');

        $this->assertSame(self::CABANG_PUSH, $alur['on']['push']['branches']);
        $this->assertSame(self::CABANG_PR, $alur['on']['pull_request']['branches']);
        $this->assertArrayHasKey('workflow_call', $alur['on'], 'deploy.yml memanggil test.yml sebagai workflow yang dapat dipakai ulang.');

        $pekerjaan = $alur['jobs']['test'];
        $this->assertSame('postgis/postgis:16-3.4', $pekerjaan['services']['postgis']['image']);

        $langkah = array_map(fn (array $satu): string => $satu['name'] ?? $satu['uses'], $pekerjaan['steps']);

        $this->assertLessThan($this->posisi($langkah, 'Build assets'), $this->posisi($langkah, 'Install PHP dependencies'), 'Build menjalankan Artisan untuk Wayfinder, jadi vendor/ harus ada lebih dulu.');
        $this->assertLessThan($this->posisi($langkah, 'Type check'), $this->posisi($langkah, 'Build assets'), 'Rute bertipe Wayfinder dibangkitkan build; pemeriksaan tipe harus sesudahnya.');
        $this->assertLessThan($this->posisi($langkah, 'Unit tests'), $this->posisi($langkah, 'Build assets'), 'Manifest Vite harus ada sebelum test merender halaman.');

        foreach (['Lint (Pint)', 'Feature tests', 'Spatial tests (PostGIS)'] as $nama) {
            $this->posisi($langkah, $nama);
        }

        $bernama = $this->langkahBernama($pekerjaan['steps']);

        $this->assertSame('php artisan test --testsuite=Unit', $bernama['Unit tests']['run']);
        $this->assertSame('php artisan test --testsuite=Feature', $bernama['Feature tests']['run']);
        $this->assertStringStartsWith('pgsql://', $bernama['Spatial tests (PostGIS)']['env']['SPATIAL_TEST_DSN']);
        $this->assertStringContainsString('@127.0.0.1:', $bernama['Spatial tests (PostGIS)']['env']['SPATIAL_TEST_DSN']);
    }

    public function test_the_security_workflow_audits_dependencies_and_scans_for_secrets(): void
    {
        $alur = $this->alur('security.yml');

        foreach (['push', 'pull_request', 'schedule', 'workflow_dispatch'] as $pemicu) {
            $this->assertArrayHasKey($pemicu, $alur['on'], "security.yml tidak dipicu {$pemicu}.");
        }

        $perintah = $this->semuaPerintah($alur);

        $this->assertStringContainsString('composer audit --locked', $perintah);
        $this->assertStringContainsString('npm audit --audit-level=high', $perintah);
        $this->assertStringContainsString('php artisan test --filter=SecretsNotInRepoTest', $perintah);
    }

    public function test_the_deploy_workflow_runs_only_by_hand_and_deploys_nothing_yet(): void
    {
        $alur = $this->alur('deploy.yml');

        $this->assertSame(['workflow_dispatch'], array_keys($alur['on']));
        $this->assertSame('./.github/workflows/test.yml', $alur['jobs']['verifikasi']['uses']);

        $unggah = array_values(array_filter(
            $alur['jobs']['artefak-build']['steps'],
            fn (array $langkah): bool => str_starts_with($langkah['uses'] ?? '', 'actions/upload-artifact@')
        ));

        $this->assertCount(1, $unggah);
        $this->assertSame('public/build', $unggah[0]['with']['path']);

        $this->assertStringNotContainsString('secrets.', File::get(base_path(self::FOLDER.'/deploy.yml')), 'Rahasia penerapan menunggu keputusan pemilik (DK-54).');
        $this->assertDoesNotMatchRegularExpression('/\bvercel\b/i', $this->semuaPerintah($alur), 'Belum ada langkah deploy sampai pemilik memutuskan jalur teknisnya.');
    }

    public function test_every_workflow_reads_the_repo_with_the_least_permission(): void
    {
        foreach (self::WORKFLOW as $berkas) {
            $this->assertSame(['contents' => 'read'], $this->alur($berkas)['permissions'], "{$berkas} harus membatasi token ke contents: read.");
        }
    }

    public function test_every_action_is_pinned_to_a_major_that_runs_on_node_24(): void
    {
        $pelanggar = [];

        foreach (self::WORKFLOW as $berkas) {
            preg_match_all('/uses:\s*([^\s@]+)@(\S+)/', File::get(base_path(self::FOLDER.'/'.$berkas)), $cocok, PREG_SET_ORDER);

            foreach ($cocok as [, $action, $versi]) {
                if (! preg_match('/^v(\d+)$/', $versi, $mayor)) {
                    $pelanggar[] = "{$berkas}: {$action}@{$versi} tidak dipatok ke tag mayor";
                } elseif (! isset(self::MAYOR_MINIMUM[$action])) {
                    $pelanggar[] = "{$berkas}: {$action} belum ditinjau runtime-nya";
                } elseif ((int) $mayor[1] < self::MAYOR_MINIMUM[$action]) {
                    $pelanggar[] = "{$berkas}: {$action}@{$versi} masih berjalan di Node 20";
                }
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    public function test_documents_name_only_workflows_that_exist(): void
    {
        $hilang = [];

        foreach (array_merge(glob(base_path('*.md')), glob(base_path('docs/*.md'))) as $path) {
            if (basename($path) === 'HANDOVER.md') {
                continue;
            }

            preg_match_all('#\.github/workflows/([A-Za-z0-9_.-]+\.ya?ml)#', File::get($path), $cocok);

            foreach (array_unique($cocok[1]) as $berkas) {
                if (! File::exists(base_path(self::FOLDER.'/'.$berkas))) {
                    $hilang[] = basename($path)." menyebut {$berkas}";
                }
            }
        }

        $this->assertSame([], $hilang, 'Dokumen menyebut workflow yang tidak ada: '.implode('; ', $hilang));
    }

    /**
     * @return array<string, mixed>
     */
    private function alur(string $berkas): array
    {
        return Yaml::parseFile(base_path(self::FOLDER.'/'.$berkas));
    }

    /**
     * @param  array<int, string>  $langkah
     */
    private function posisi(array $langkah, string $nama): int
    {
        $posisi = array_search($nama, $langkah, true);

        $this->assertIsInt($posisi, "Langkah \"{$nama}\" tidak ada.");

        return $posisi;
    }

    /**
     * @param  array<int, array<string, mixed>>  $langkah
     * @return array<string, array<string, mixed>>
     */
    private function langkahBernama(array $langkah): array
    {
        $bernama = [];

        foreach ($langkah as $satu) {
            if (isset($satu['name'])) {
                $bernama[$satu['name']] = $satu;
            }
        }

        return $bernama;
    }

    /**
     * Semua perintah `run` di semua job, dipisah baris baru.
     *
     * @param  array<string, mixed>  $alur
     */
    private function semuaPerintah(array $alur): string
    {
        $perintah = [];

        foreach ($alur['jobs'] as $pekerjaan) {
            foreach ($pekerjaan['steps'] ?? [] as $langkah) {
                if (isset($langkah['run'])) {
                    $perintah[] = $langkah['run'];
                }
            }
        }

        return implode("\n", $perintah);
    }
}
```

- [ ] **Step 2: Tambahkan test pemindai rahasia yang gagal.** Di `tests/Feature/SecretsNotInRepoTest.php`, tambahkan `use Illuminate\Support\Facades\File;` dan tiga test beserta penyedia contoh ini (method lain belum diubah):

```php
    /**
     * Satu contoh per pola, dirakit dari potongan supaya berkas ini sendiri tidak memuat
     * pola yang ia larang.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function contohRahasia(): array
    {
        return [
            'kata sandi basis data terisi' => ['kata sandi basis data terisi', 'DB_'.'PASSWORD=contoh-sandi'],
            'kunci JWT Supabase' => ['kunci JWT Supabase', 'eyJ'.'hbGciOi'.str_repeat('A', 16)],
            'kunci rahasia Supabase' => ['kunci rahasia Supabase', 'sb_'.'secret_'.str_repeat('a', 12)],
            'kunci publishable Supabase' => ['kunci publishable Supabase', 'sb_'.'publishable_'.str_repeat('b', 12)],
            'URL koneksi berkata sandi' => ['URL koneksi berkata sandi', 'postgres'.'ql://pengguna:'.'sandi@db.contoh.test/app'],
            'token NgodingPakeAI' => ['token NgodingPakeAI', 'ng'.'pk_'.str_repeat('c', 16)],
        ];
    }

    #[DataProvider('contohRahasia')]
    public function test_each_pattern_catches_a_sample_of_its_secret(string $nama, string $contoh): void
    {
        $this->assertArrayHasKey($nama, self::polaRahasia(), "Belum ada pola untuk {$nama}.");
        $this->assertMatchesRegularExpression(self::polaRahasia()[$nama][0], $contoh, "Pola {$nama} tidak menangkap contohnya.");
    }

    public function test_every_pattern_has_a_sample(): void
    {
        $this->assertSame(array_keys(self::polaRahasia()), array_keys(self::contohRahasia()));
    }

    public function test_untracked_files_that_are_not_ignored_are_scanned_too(): void
    {
        $berkas = 'tests/Feature/.pemindai-'.bin2hex(random_bytes(4)).'.txt';
        File::put(base_path($berkas), 'berkas sementara pemindai rahasia');

        try {
            $this->assertContains($berkas, $this->berkasYangAkanDicommit());
        } finally {
            File::delete(base_path($berkas));
        }
    }
```

- [ ] **Step 3: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='CiWorkflowsTest|SecretsNotInRepoTest'`
Expected: FAIL. `CiWorkflowsTest`: berkas `test.yml`, `security.yml`, `deploy.yml` tidak ada, `ci.yml` masih ada, `README.md menyebut ci.yml` dan `PRD.md menyebut ci.yml`. `SecretsNotInRepoTest`: `test_each_pattern_catches_a_sample_of_its_secret` dengan data "token NgodingPakeAI" gagal "Belum ada pola untuk token NgodingPakeAI."; `test_every_pattern_has_a_sample` gagal; `test_untracked_files_that_are_not_ignored_are_scanned_too` error "Call to undefined method ...berkasYangAkanDicommit()".

- [ ] **Step 4: Pindahkan `ci.yml` dan tulis `test.yml`.** Run: `git mv .github/workflows/ci.yml .github/workflows/test.yml`, lalu ganti seluruh isinya:

```yaml
name: Test

# Gerbang mutu untuk setiap push dan pull request (spec v3 DK-54). Isinya sama dengan
# ci.yml sebelum dipecah; workflow_call membuat deploy.yml dapat memanggil gerbang yang
# sama tanpa menyalinnya. Mayor action dinaikkan ke versi Node 24 karena runner GitHub
# melepas Node 20 pada 23 September 2026.

on:
  push:
    branches: [main, develop, hardening]
  pull_request:
    branches: [main, develop]
  workflow_call:

permissions:
  contents: read

jobs:
  test:
    runs-on: ubuntu-latest

    # Suite spasial membutuhkan PostGIS sungguhan. Tanpa service ini, seluruh kode
    # geografi hanya dijalankan sebagai no-op di SQLite dan kesalahannya tidak akan
    # pernah terlihat (PRD §66). Citra dan kredensial yang sama dipakai
    # docker-compose.yml untuk paritas lokal (DK-55).
    services:
      postgis:
        image: postgis/postgis:16-3.4
        env:
          POSTGRES_USER: hiking
          POSTGRES_PASSWORD: hiking
          POSTGRES_DB: hiking_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd="pg_isready -U hiking"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          # Disamakan dengan versi pengembangan; perbedaan minor PHP adalah sumber
          # kegagalan yang hanya muncul di satu sisi.
          php-version: '8.5'
          extensions: mbstring, pdo, pdo_sqlite, pdo_pgsql, gd, exif
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v5
        with:
          node-version: '22'
          cache: npm

      - name: Install PHP dependencies
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Install Node dependencies
        run: npm ci

      # Manifest Vite tidak ada di repositori: /public/build di-gitignore. Enam view
      # memakai @vite, jadi seluruh test yang merender halaman akan gagal dengan
      # ViteException bila aset belum dibangun. Langkah ini sebelumnya berada di
      # bawah test, sehingga urutannya sendiri yang membuat CI merah.
      - name: Build assets
        run: npm run build

      # Vite membuang anotasi tipe tanpa memeriksanya, jadi galat tipe di halaman React
      # lolos build. Langkah ini sesudah build karena rute bertipe Wayfinder
      # dibangkitkan oleh build itu.
      - name: Type check
        run: npm run types

      - name: Lint (Pint)
        run: vendor/bin/pint --test

      # Dipisah menjadi dua langkah, bukan satu perintah dengan dua opsi suite: opsi
      # kedua menimpa yang pertama, bukan menambahnya. Perintah lama karena itu hanya
      # menjalankan Feature, dan 104 test Unit tidak pernah dieksekusi CI sama sekali.
      # Dipisah juga membuat kegagalan tiap suite terbaca sendiri-sendiri.
      - name: Unit tests
        run: php artisan test --testsuite=Unit

      - name: Feature tests
        run: php artisan test --testsuite=Feature

      - name: Spatial tests (PostGIS)
        env:
          SPATIAL_TEST_DSN: pgsql://hiking:hiking@127.0.0.1:5432/hiking_test
        run: php artisan test --testsuite=Spatial
```

- [ ] **Step 5: Tulis `.github/workflows/security.yml`.**

```yaml
name: Security

# Pemeriksaan keamanan yang tidak menunggu pull request (spec v3 DK-54). Advisori baru
# terbit tanpa ada perubahan kode, jadi audit juga berjalan terjadwal setiap Senin.
# Merah di sini berarti ada advisori pada dependensi terkunci atau pola rahasia di
# berkas repo. Memperbaiki advisori berarti mengubah versi dependensi, yang butuh izin
# pemilik (CLAUDE.md R-020, PRD §161).

on:
  push:
    branches: [main, develop, hardening]
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 1 * * 1'
  workflow_dispatch:

permissions:
  contents: read

jobs:
  audit-dependensi:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v5
        with:
          node-version: '22'

      # Membaca composer.lock; tidak butuh vendor/. Paket yang ditinggalkan
      # pemeliharanya dilaporkan tanpa menggagalkan job.
      - name: Composer audit
        run: composer audit --locked --no-interaction --abandoned=report

      # Membaca package-lock.json; tidak butuh node_modules/. Advisori high dan
      # critical menggagalkan job.
      - name: npm audit
        run: npm audit --audit-level=high

  pemindai-rahasia:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: mbstring, pdo, pdo_sqlite
          coverage: none

      - name: Install PHP dependencies
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      # Memindai setiap berkas yang akan ikut commit untuk pola kredensial sungguhan.
      - name: Pemindai rahasia
        run: php artisan test --filter=SecretsNotInRepoTest
```

- [ ] **Step 6: Tulis `.github/workflows/deploy.yml`.**

```yaml
name: Deploy

# Hanya dijalankan tangan. Workflow ini memverifikasi kandidat rilis lewat gerbang yang
# sama dengan test.yml (build, tipe, Pint, Unit, Feature, Spatial di PostGIS), lalu
# mengunggah hasil build Vite sebagai artefak.
#
# Langkah deploy sengaja belum ada. Target produksi Vercel sudah diputuskan (DK-12),
# tetapi jalur teknisnya (container FrankenPHP atau runtime vercel-php) dan rahasia
# penerapannya menunggu keputusan pemilik. Push, deploy, dan penambahan rahasia
# repositori tetap butuh izin eksplisit pemilik (CLAUDE.md R-018, spec v3 DK-54).

on:
  workflow_dispatch:

permissions:
  contents: read

jobs:
  verifikasi:
    uses: ./.github/workflows/test.yml

  artefak-build:
    needs: verifikasi
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: mbstring, pdo, pdo_sqlite, pdo_pgsql, gd, exif
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v5
        with:
          node-version: '22'
          cache: npm

      - name: Install PHP dependencies
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Install Node dependencies
        run: npm ci

      # Rute bertipe Wayfinder dibangkitkan build ini lewat Artisan, jadi PHP dan
      # vendor/ harus siap lebih dulu.
      - name: Build assets
        run: npm run build

      - name: Unggah artefak build
        uses: actions/upload-artifact@v6
        with:
          name: "build-${{ github.sha }}"
          path: public/build
          if-no-files-found: error
          retention-days: 14
```

- [ ] **Step 7: Lengkapi `SecretsNotInRepoTest`.** Ganti docblock kelas, tambah pola NgodingPakeAI, ganti test pemindai agar membaca berkas yang akan ikut commit, dan ganti `berkasTerlacak()` dengan tiga helper. Berkas akhir:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kredensial boleh dipakai, tidak boleh ditulis ke berkas.
 *
 * Aturannya mudah disepakati dan mudah dilanggar tanpa sengaja: satu kata sandi yang
 * tertempel di script sekali saja akan ikut ke riwayat git selamanya, bahkan setelah
 * baris itu dihapus. Jadi aturannya ditegakkan mesin, bukan ingatan.
 *
 * Yang diperiksa adalah berkas yang akan ikut commit: yang sudah dilacak git dan yang
 * baru tetapi tidak diabaikan. Memeriksa yang dilacak saja berarti rahasia di berkas
 * baru baru tertangkap setelah masuk riwayat. Isi .env tidak ikut karena memang
 * tempatnya di sana dan sudah di-gitignore.
 */
class SecretsNotInRepoTest extends TestCase
{
    public function test_the_environment_file_is_never_tracked(): void
    {
        $terlacak = $this->berkasTerlacak();

        $this->assertNotContains('.env', $terlacak, '.env tidak boleh masuk git.');
    }

    public function test_the_example_environment_file_carries_no_real_values(): void
    {
        $contoh = base_path('.env.example');

        if (! file_exists($contoh)) {
            $this->markTestSkipped('Tidak ada .env.example.');
        }

        preg_match_all('/^(DB_PASSWORD|SUPABASE_\w*(KEY|SECRET))=(.+)$/m', file_get_contents($contoh), $cocok);

        $this->assertSame([], array_values(array_filter($cocok[3] ?? [])), '.env.example harus kosong nilainya.');
    }

    /**
     * Pola yang menandai kredensial sungguhan, bukan sekadar nama variabelnya.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function polaRahasia(): array
    {
        return [
            // Spasi horizontal saja: \s ikut menelan baris baru, sehingga DB_PASSWORD
            // yang memang kosong terbaca menyambung ke nilai baris berikutnya.
            'kata sandi basis data terisi' => ['/\bDB_PASSWORD[ \t]*=[ \t]*\S+/', 'nilai DB_PASSWORD'],
            'kunci JWT Supabase' => ['/\beyJhbGciOi[A-Za-z0-9_\-]{10,}/', 'kunci JWT Supabase'],
            'kunci rahasia Supabase' => ['/\bsb_secret_[A-Za-z0-9_\-]{8,}/', 'kunci rahasia Supabase'],
            'kunci publishable Supabase' => ['/\bsb_publishable_[A-Za-z0-9_\-]{8,}/', 'kunci publishable Supabase'],
            'URL koneksi berkata sandi' => ['#\bpostgres(?:ql)?://[^\s:@/]+:[^\s@/]+@#', 'kata sandi di dalam URL koneksi'],
            // Token CLI NgodingPakeAI (AGENTS.md, blok NgodingPakeAI): tempatnya
            // variabel lingkungan NGODINGPAKEAI_TOKEN, tidak pernah berkas.
            'token NgodingPakeAI' => ['/\bngpk_[A-Za-z0-9_\-]{8,}/', 'token NgodingPakeAI'],
        ];
    }

    /**
     * Satu contoh per pola, dirakit dari potongan supaya berkas ini sendiri tidak memuat
     * pola yang ia larang.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function contohRahasia(): array
    {
        return [
            'kata sandi basis data terisi' => ['kata sandi basis data terisi', 'DB_'.'PASSWORD=contoh-sandi'],
            'kunci JWT Supabase' => ['kunci JWT Supabase', 'eyJ'.'hbGciOi'.str_repeat('A', 16)],
            'kunci rahasia Supabase' => ['kunci rahasia Supabase', 'sb_'.'secret_'.str_repeat('a', 12)],
            'kunci publishable Supabase' => ['kunci publishable Supabase', 'sb_'.'publishable_'.str_repeat('b', 12)],
            'URL koneksi berkata sandi' => ['URL koneksi berkata sandi', 'postgres'.'ql://pengguna:'.'sandi@db.contoh.test/app'],
            'token NgodingPakeAI' => ['token NgodingPakeAI', 'ng'.'pk_'.str_repeat('c', 16)],
        ];
    }

    #[DataProvider('polaRahasia')]
    public function test_no_committable_file_contains_the_pattern(string $pola, string $keterangan): void
    {
        $pelanggar = [];

        foreach ($this->berkasYangAkanDicommit() as $berkas) {
            $penuh = base_path($berkas);

            if (! is_file($penuh) || ! $this->dapatDibacaSebagaiTeks($penuh)) {
                continue;
            }

            if (preg_match($pola, (string) file_get_contents($penuh))) {
                $pelanggar[] = $berkas;
            }
        }

        $this->assertSame([], $pelanggar, "Ditemukan {$keterangan} pada berkas yang akan ikut commit: ".implode(', ', $pelanggar));
    }

    #[DataProvider('contohRahasia')]
    public function test_each_pattern_catches_a_sample_of_its_secret(string $nama, string $contoh): void
    {
        $this->assertArrayHasKey($nama, self::polaRahasia(), "Belum ada pola untuk {$nama}.");
        $this->assertMatchesRegularExpression(self::polaRahasia()[$nama][0], $contoh, "Pola {$nama} tidak menangkap contohnya.");
    }

    public function test_every_pattern_has_a_sample(): void
    {
        $this->assertSame(array_keys(self::polaRahasia()), array_keys(self::contohRahasia()));
    }

    public function test_untracked_files_that_are_not_ignored_are_scanned_too(): void
    {
        $berkas = 'tests/Feature/.pemindai-'.bin2hex(random_bytes(4)).'.txt';
        File::put(base_path($berkas), 'berkas sementara pemindai rahasia');

        try {
            $this->assertContains($berkas, $this->berkasYangAkanDicommit());
        } finally {
            File::delete(base_path($berkas));
        }
    }

    private function dapatDibacaSebagaiTeks(string $path): bool
    {
        if (filesize($path) > 2_000_000) {
            return false;
        }

        $awal = (string) file_get_contents($path, false, null, 0, 1024);

        return ! str_contains($awal, "\0");
    }

    /**
     * @return array<int, string>
     */
    private function berkasTerlacak(): array
    {
        return $this->daftarGit('');
    }

    /**
     * Berkas yang dilacak ditambah berkas baru yang tidak diabaikan: persis yang akan
     * masuk bila seseorang menjalankan `git add -A`.
     *
     * @return array<int, string>
     */
    private function berkasYangAkanDicommit(): array
    {
        return $this->daftarGit('--cached --others --exclude-standard');
    }

    /**
     * @return array<int, string>
     */
    private function daftarGit(string $opsi): array
    {
        exec('git -C '.escapeshellarg(base_path()).' ls-files -z '.$opsi, $keluaran, $kode);

        if ($kode !== 0) {
            $this->markTestSkipped('Bukan repositori git.');
        }

        return array_values(array_filter(explode("\0", implode("\n", $keluaran))));
    }
}
```

- [ ] **Step 8: Perbarui dokumen yang menyebut CI.**

`README.md` baris 91 sampai 92, ganti:

```text
CI (`.github/workflows/ci.yml`) menjalankan build, pemeriksaan tipe, Pint, Unit, Feature,
dan Spatial di PostGIS untuk setiap push.
```

menjadi:

```text
CI dipecah menjadi tiga workflow (DK-54). `.github/workflows/test.yml` menjalankan build,
pemeriksaan tipe, Pint, Unit, Feature, dan Spatial di PostGIS untuk setiap push dan pull
request. `.github/workflows/security.yml` menjalankan `composer audit`, `npm audit`, dan
pemindai rahasia, juga setiap Senin. `.github/workflows/deploy.yml` hanya dijalankan tangan
untuk memverifikasi kandidat rilis dan belum memuat langkah deploy.
```

`CONTRIBUTING.md`, sesudah blok kode "Gerbang lokal sebelum meminta tinjauan", tambahkan paragraf:

```text
**Gerbang di CI** sama dengan gerbang lokal (`.github/workflows/test.yml`), ditambah
`.github/workflows/security.yml` untuk audit dependensi dan pemindai rahasia. Advisori baru
pada dependensi terkunci dapat membuat `security.yml` merah tanpa perubahan kode;
memperbaikinya berarti mengubah versi dependensi, jadi butuh izin pemilik.
```

`docs/PRD.md` §126, ganti paragraf yang diawali `**Amandemen (keadaan nyata, `.github/workflows/ci.yml`):**` seluruhnya dengan:

```text
**Amandemen (keadaan nyata sejak GR-04, spec v3 DK-54):** CI dipecah menjadi tiga workflow. `.github/workflows/test.yml` dengan layanan `postgis/postgis:16-3.4` menjalankan: instal dependensi PHP dan Node; `npm run build` **sebelum** test; `npm run types`; `vendor/bin/pint --test`; suite Unit; suite Feature; suite Spatial di PostGIS. `.github/workflows/security.yml` menjalankan `composer audit`, `npm audit` dengan ambang high, dan `SecretsNotInRepoTest` pada push, pull request, dan jadwal mingguan. `.github/workflows/deploy.yml` hanya dijalankan tangan: memanggil `test.yml` lalu mengunggah hasil build sebagai artefak, tanpa langkah deploy sampai pemilik memutuskan jalur teknis dan rahasianya. Belum ada analisis statis PHP. Deploy ke Vercel dengan migrasi dijalankan di CI (bukan saat container mulai) disiapkan di sub-proyek kesiapan deploy (§154).
```

`docs/PRD.md` §128, sel terakhir baris `| 0. Fondasi CI | ...` dari `Commit `6d9e090`, `.github/workflows/ci.yml`` menjadi `Commit `6d9e090`; kini `.github/workflows/test.yml` (DK-54)`.

- [ ] **Step 9: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='CiWorkflowsTest|SecretsNotInRepoTest|PrdReferenceIntegrityTest'`
Expected: PASS semua. Pemindai kini membaca kurang lebih 557 berkas tambahan; hasil pindaian 26 September 2026 atas berkas-berkas itu: 0 temuan.

- [ ] **Step 10: Catat audit lokal (baca-saja).**

Run: `composer audit --locked --no-interaction --abandoned=report` lalu `npm audit --audit-level=high; echo "exit=$?"`
Expected: Composer "No security vulnerability advisories found."; npm `exit=1` dengan satu advisori kritis `maplibre-gl` GHSA-jrc7-96c5-q579. Jangan menjalankan `npm audit fix`: perbaikannya menaikkan `maplibre-gl` ke mayor 6 dan melanggar kunci versi MapLibre 4.7.x. Catat sebagai keputusan pemilik Q20 (bagian "Keputusan pemilik yang dikumpulkan WR").

- [ ] **Step 11: Format.** Run: `vendor/bin/pint --format agent tests/Feature/CiWorkflowsTest.php tests/Feature/SecretsNotInRepoTest.php`.

- [ ] **Step 12: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add .github/workflows/test.yml .github/workflows/security.yml .github/workflows/deploy.yml tests/Feature/CiWorkflowsTest.php
git commit -m "ci: pecah ci.yml menjadi test, security, dan deploy manual; pemindai rahasia mencakup berkas baru (DK-54)" -- .github/workflows/ci.yml .github/workflows/test.yml .github/workflows/security.yml .github/workflows/deploy.yml tests/Feature/CiWorkflowsTest.php tests/Feature/SecretsNotInRepoTest.php README.md CONTRIBUTING.md docs/PRD.md
```

---

### Task 4: PostgreSQL + PostGIS lokal lewat `docker-compose.yml`

**Files:**
- Create: `docker-compose.yml`
- Create: `tests/Feature/LocalPostgisComposeTest.php`
- Modify: `tests/Feature/RepoHygieneTest.php` (`ENTRI_ROOT` ditambah `'docker-compose.yml'`)
- Modify: `CONTRIBUTING.md` (bagian baru "Suite spasial di mesin sendiri"; butir pengecualian di "Yang tidak boleh masuk")
- Modify: `.env.example` (komentar di atas `SPATIAL_TEST_DSN`)
- Modify: `phpunit.xml` (komentar suite Spatial menunjuk `CONTRIBUTING.md` di root)
- Modify: `README.md` (bagian suite spasial dan prasyarat), `docs/ARCHITECTURE.md` ("Yang belum ada", butir suite spasial)
- Test: `tests/Feature/LocalPostgisComposeTest.php`

**Interfaces:**
- Consumes: `.github/workflows/test.yml` `jobs.test.services.postgis` (`image`, `env`) dari Tugas 3.
- Produces: layanan Compose `postgis`, volume bernama `memount-postgis-data`, variabel port `SPATIAL_PG_PORT` dengan bawaan `54329`, DSN `pgsql://hiking:hiking@127.0.0.1:54329/hiking_test`.

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction LocalPostgisComposeTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * PostGIS lokal sama dengan PostGIS di CI (spec v3 DK-55, paritas R-024).
 *
 * Suite Spatial dilewati di mesin pengembang sampai SPATIAL_TEST_DSN diisi, sehingga
 * kode spasial praktis hanya diuji CI. docker-compose.yml menyediakan basis data yang
 * sama secara opsional; test ini menjaga agar ia tidak diam-diam berbeda dari CI,
 * terbuka ke jaringan, atau didokumentasikan dengan DSN yang salah.
 */
class LocalPostgisComposeTest extends TestCase
{
    public function test_compose_runs_the_same_postgis_image_and_credentials_as_ci(): void
    {
        $lokal = $this->compose()['services']['postgis'];
        $ci = Yaml::parseFile(base_path('.github/workflows/test.yml'))['jobs']['test']['services']['postgis'];

        $this->assertSame($ci['image'], $lokal['image'], 'Citra PostGIS lokal harus sama dengan CI.');
        $this->assertSame($ci['env'], $lokal['environment'], 'Kredensial kontainer uji harus sama dengan CI.');
    }

    public function test_the_database_port_is_bound_to_localhost_and_data_lives_in_a_named_volume(): void
    {
        $compose = $this->compose();
        $lokal = $compose['services']['postgis'];

        $this->assertCount(1, $lokal['ports']);
        $this->assertStringStartsWith('127.0.0.1:', $lokal['ports'][0], 'Port Postgres tidak boleh terbuka ke jaringan.');
        $this->assertSame(['memount-postgis-data:/var/lib/postgresql/data'], $lokal['volumes']);
        $this->assertArrayHasKey('memount-postgis-data', $compose['volumes']);
        $this->assertStringContainsString('pg_isready', implode(' ', (array) $lokal['healthcheck']['test']));
    }

    public function test_the_contributing_guide_documents_a_dsn_that_matches_compose(): void
    {
        $lokal = $this->compose()['services']['postgis'];

        $this->assertMatchesRegularExpression('/\$\{SPATIAL_PG_PORT:-(\d+)\}/', $lokal['ports'][0]);
        preg_match('/\$\{SPATIAL_PG_PORT:-(\d+)\}/', $lokal['ports'][0], $port);

        $dsn = sprintf(
            'pgsql://%s:%s@127.0.0.1:%s/%s',
            $lokal['environment']['POSTGRES_USER'],
            $lokal['environment']['POSTGRES_PASSWORD'],
            $port[1],
            $lokal['environment']['POSTGRES_DB'],
        );

        $panduan = File::get(base_path('CONTRIBUTING.md'));

        $this->assertStringContainsString('docker compose up -d postgis', $panduan);
        $this->assertStringContainsString($dsn, $panduan);
        $this->assertStringContainsString('docker compose up -d postgis', File::get(base_path('.env.example')));
        $this->assertStringNotContainsString('docs/CONTRIBUTING.md', File::get(base_path('phpunit.xml')), 'CONTRIBUTING.md kini di root.');
    }

    /**
     * @return array<string, mixed>
     */
    private function compose(): array
    {
        return Yaml::parseFile(base_path('docker-compose.yml'));
    }
}
```

Tambahkan juga `'docker-compose.yml',` ke `RepoHygieneTest::ENTRI_ROOT`, di antara `'database',` dan `'docs',`.

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=LocalPostgisComposeTest`
Expected: FAIL, error "File ...docker-compose.yml does not exist" dari Symfony Yaml pada ketiga test.

- [ ] **Step 3: Tulis `docker-compose.yml`** (indentasi 4 spasi sesuai `.editorconfig`):

```yaml
# PostgreSQL + PostGIS lokal untuk suite Spatial (spec v3 DK-55, paritas R-024).
#
# Opsional: aplikasi tidak membutuhkan Docker. Kontainer ini hanya untuk
# SPATIAL_TEST_DSN, bukan basis data aplikasi, karena suite Spatial memakai
# RefreshDatabase dan mengosongkan seluruh isinya setiap kali berjalan.
#
# Citra dan kredensialnya sama dengan layanan postgis di .github/workflows/test.yml
# (dijaga LocalPostgisComposeTest). Kata sandinya bukan rahasia: kontainer sekali
# pakai, hanya terikat ke 127.0.0.1, dan tidak pernah memegang data sungguhan.
#
#   docker compose up -d postgis
#   SPATIAL_TEST_DSN="pgsql://hiking:hiking@127.0.0.1:54329/hiking_test" php artisan test --testsuite=Spatial
#   docker compose down        (data tetap di volume)
#   docker compose down -v     (volume ikut dihapus)

name: memount

services:
    postgis:
        image: postgis/postgis:16-3.4
        environment:
            POSTGRES_USER: hiking
            POSTGRES_PASSWORD: hiking
            POSTGRES_DB: hiking_test
        ports:
            - "127.0.0.1:${SPATIAL_PG_PORT:-54329}:5432"
        volumes:
            - memount-postgis-data:/var/lib/postgresql/data
        healthcheck:
            test: ["CMD-SHELL", "pg_isready -U hiking -d hiking_test"]
            interval: 10s
            timeout: 5s
            retries: 5

volumes:
    memount-postgis-data:
```

- [ ] **Step 4: Dokumentasikan di `CONTRIBUTING.md`.** Tambahkan bagian ini sesudah bagian "Basis data dan migrasi":

````markdown
## Suite spasial di mesin sendiri

Suite `Spatial` butuh PostgreSQL dengan PostGIS dan memakai `RefreshDatabase`, jadi ia
mengosongkan seluruh tabel basis data yang ditunjuknya. Jangan pernah mengarahkannya ke
Supabase; `tests/Spatial/SpatialTestCase.php` menolak host yang tampak seperti produksi.

Cara termudah, opsional dan butuh Docker Desktop: `docker-compose.yml` menyediakan PostGIS
dengan citra dan kredensial yang sama dengan CI (DK-55).

```sh
docker compose up -d postgis
SPATIAL_TEST_DSN="pgsql://hiking:hiking@127.0.0.1:54329/hiking_test" php artisan test --testsuite=Spatial
docker compose down        # data tetap di volume memount-postgis-data
docker compose down -v     # volume ikut dihapus
```

Port di mesin Anda `54329` supaya tidak bertabrakan dengan PostgreSQL lokal di `5432`.
Untuk port lain, jalankan misalnya `SPATIAL_PG_PORT=55432 docker compose up -d postgis` dan
sesuaikan DSN-nya. Di PowerShell, isi variabel dengan `$env:SPATIAL_TEST_DSN="..."` sebelum
perintah test.

Kata sandi `hiking` bukan rahasia: kontainernya sekali pakai, hanya terikat ke 127.0.0.1,
dan sama dengan layanan PostGIS di `.github/workflows/test.yml`.
````

Di bagian "Yang tidak boleh masuk", ganti butir `- Kredensial dalam bentuk apa pun, termasuk di `.env.example`.` menjadi:

```text
- Kredensial dalam bentuk apa pun, termasuk di `.env.example`. Satu-satunya pengecualian
  yang disengaja adalah kata sandi kontainer uji sekali pakai (bagian "Suite spasial di
  mesin sendiri").
```

- [ ] **Step 5: Komentar `.env.example`, `phpunit.xml`, README, dan ARCHITECTURE.**

`.env.example`, ganti dua baris komentar di atas `SPATIAL_TEST_DSN=` menjadi:

```text
# Postgres + PostGIS untuk suite spasial. Kosongkan bila tidak tersedia; suite-nya
# akan melewati dirinya sendiri, bukan gagal. Kontainer lokal opsional yang sama dengan
# CI: `docker compose up -d postgis`, lalu isi DSN menurut CONTRIBUTING.md bagian
# "Suite spasial di mesin sendiri". Jangan pernah mengarahkannya ke Supabase: suite ini
# mengosongkan seluruh tabel.
```

`phpunit.xml`, ganti komentar suite Spatial menjadi:

```xml
        <!--
            Suite spasial membutuhkan Postgres dengan PostGIS. Tanpa SPATIAL_TEST_DSN
            ia melewati dirinya sendiri dengan pesan yang jelas, bukan gagal, sehingga
            kontributor tanpa Postgres lokal tetap dapat menjalankan seluruh suite,
            dan tetap diingatkan bahwa suite ini ada. Cara menjalankannya penuh ada di
            CONTRIBUTING.md, bagian "Suite spasial di mesin sendiri".
        -->
```

`README.md`, di bagian "Perintah harian" sesudah blok kode `SPATIAL_TEST_DSN=...`, tambahkan kalimat: `Tanpa PostgreSQL lokal, `docker compose up -d postgis` menyediakannya (opsional; rinciannya di `CONTRIBUTING.md`).` Di "Prasyarat", ganti `PostgreSQL 16+ dengan PostGIS bila ingin menguji fitur spasial` menjadi `PostgreSQL 16+ dengan PostGIS, atau Docker, bila ingin menguji fitur spasial`.

`docs/ARCHITECTURE.md`, bagian "Yang belum ada", ganti butir `- **Suite spasial di mesin pengembang.** Berjalan di CI, tetapi melewati dirinya secara lokal sampai `SPATIAL_TEST_DSN` diisi.` menjadi:

```text
- **Suite spasial di mesin pengembang.** Berjalan di CI; secara lokal dilewati sampai `SPATIAL_TEST_DSN` diisi. `docker-compose.yml` menyediakan PostGIS yang sama dengan CI (DK-55), tetapi Docker tidak wajib, jadi suite ini tetap mudah terlewat di mesin tanpa Docker.
```

- [ ] **Step 6: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='LocalPostgisComposeTest|RepoHygieneTest|SecretsNotInRepoTest|PrdReferenceIntegrityTest|CiWorkflowsTest'`
Expected: PASS semua.

- [ ] **Step 7: Verifikasi runtime, hanya bila Docker ada.** Docker tidak terpasang di mesin pemilik (FAKTA 26 September 2026), jadi di sana verifikasi berhenti pada test statis di Step 6 dan statusnya dicatat PERLU KLARIFIKASI. Bila `docker --version` berhasil: jalankan `docker compose config --quiet` (expected: exit 0), `docker compose up -d postgis`, tunggu `docker compose ps` menampilkan `healthy`, lalu `SPATIAL_TEST_DSN="pgsql://hiking:hiking@127.0.0.1:54329/hiking_test" php artisan test --compact --testsuite=Spatial` (expected: PASS tanpa test dilewati), dan `docker compose down`.

- [ ] **Step 8: Format.** Run: `vendor/bin/pint --format agent tests/Feature/LocalPostgisComposeTest.php tests/Feature/RepoHygieneTest.php`.

- [ ] **Step 9: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add docker-compose.yml tests/Feature/LocalPostgisComposeTest.php
git commit -m "chore: PostGIS lokal lewat docker-compose.yml sama dengan CI (DK-55)" -- docker-compose.yml tests/Feature/LocalPostgisComposeTest.php tests/Feature/RepoHygieneTest.php CONTRIBUTING.md .env.example phpunit.xml README.md docs/ARCHITECTURE.md
```

---

### Task 5: `SECURITY.md`, `database/README.md`, templat PR dan isu

`LICENSE` dan `.github/CODEOWNERS` **tidak dibuat**: terblokir Q18 (lisensi proprietary atau terbuka, dan pemilik kode per area adalah keputusan pemilik). Catat di daftar keputusan M1, bersama temuan bahwa `composer.json` masih memuat `"name": "laravel/laravel"` dan `"license": "MIT"` warisan kerangka, yang dapat terbaca sebagai klaim lisensi proyek.

**Files:**
- Create: `SECURITY.md`, `database/README.md`, `.github/PULL_REQUEST_TEMPLATE.md`, `.github/ISSUE_TEMPLATE/bug.yml`, `.github/ISSUE_TEMPLATE/fitur.yml`, `.github/ISSUE_TEMPLATE/config.yml`
- Create: `tests/Feature/RepoPolicyFilesTest.php`
- Modify: `tests/Feature/RepoHygieneTest.php` (`ENTRI_ROOT` ditambah `'SECURITY.md'`)
- Modify: `tests/Feature/PrdReferenceIntegrityTest.php` (`DOKUMEN_HIDUP` ditambah `'SECURITY.md'`, `'database/README.md'`, `'.github/PULL_REQUEST_TEMPLATE.md'`)
- Modify: `README.md` (bagian "Keamanan", baris `database/migrations` di "Peta repo"), `CONTRIBUTING.md` (langkah 6 alur kerja, bagian "Basis data dan migrasi", bagian "Keamanan dan privasi")
- Test: `tests/Feature/RepoPolicyFilesTest.php`

**Interfaces:**
- Consumes: `RepoHygieneTest::ENTRI_ROOT` (Tugas 2), `PrdReferenceIntegrityTest::DOKUMEN_HIDUP` (Tugas 1), workflow `security.yml` (Tugas 3).
- Produces: `SECURITY.md`, `database/README.md`, templat PR yang ditambah satu butir di Tugas 8.

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction RepoPolicyFilesTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Berkas kebijakan repo yang dibaca orang sebelum membaca kode.
 *
 * Repositori ini publik. SECURITY.md menentukan ke mana kerentanan dilaporkan, templat
 * pull request dan isu menentukan bukti apa yang diminta sejak awal, dan
 * database/README.md menjadi peta skema bagi anggota baru. Keempatnya mudah basi atau
 * salah bentuk tanpa ada yang menyadarinya, jadi bentuknya dijaga di sini.
 */
class RepoPolicyFilesTest extends TestCase
{
    private const JENIS_ELEMEN_FORMULIR = ['markdown', 'textarea', 'input', 'dropdown', 'checkboxes'];

    private const BERKAS_KEBIJAKAN = [
        'SECURITY.md',
        'database/README.md',
        '.github/PULL_REQUEST_TEMPLATE.md',
        '.github/ISSUE_TEMPLATE/bug.yml',
        '.github/ISSUE_TEMPLATE/fitur.yml',
        '.github/ISSUE_TEMPLATE/config.yml',
    ];

    public function test_the_security_policy_routes_reports_privately_and_names_no_personal_address(): void
    {
        $kebijakan = File::get(base_path('SECURITY.md'));

        $this->assertStringContainsString('private vulnerability reporting', $kebijakan);
        $this->assertStringContainsString('Report a vulnerability', $kebijakan);
        $this->assertStringContainsString('SecretsNotInRepoTest', $kebijakan);
        $this->assertDoesNotMatchRegularExpression('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $kebijakan, 'SECURITY.md tidak boleh menyebut alamat email.');
    }

    public function test_the_pull_request_template_carries_the_merge_gate(): void
    {
        $templat = File::get(base_path('.github/PULL_REQUEST_TEMPLATE.md'));

        foreach ([
            'php artisan test --compact --testsuite=Unit,Feature',
            'npm run types',
            'npm run build',
            'vendor/bin/pint --test',
            'Delivery Gate antislop',
            'SecretsNotInRepoTest',
            'R-019',
        ] as $butir) {
            $this->assertMatchesRegularExpression('/^- \[ \] .*'.preg_quote($butir, '/').'/m', $templat, "Templat PR tidak memuat butir periksa: {$butir}");
        }

        $this->assertStringContainsString('## Bukti', $templat);
    }

    public function test_issue_forms_follow_the_github_form_schema(): void
    {
        foreach (['bug.yml', 'fitur.yml'] as $berkas) {
            $formulir = Yaml::parseFile(base_path('.github/ISSUE_TEMPLATE/'.$berkas));

            foreach (['name', 'description', 'body'] as $kunci) {
                $this->assertArrayHasKey($kunci, $formulir, "{$berkas} tidak punya {$kunci}.");
            }

            $id = [];

            foreach ($formulir['body'] as $elemen) {
                $this->assertContains($elemen['type'], self::JENIS_ELEMEN_FORMULIR, "{$berkas}: jenis {$elemen['type']} tidak dikenal GitHub.");

                if ($elemen['type'] === 'markdown') {
                    $this->assertNotEmpty($elemen['attributes']['value']);

                    continue;
                }

                $this->assertNotEmpty($elemen['attributes']['label'], "{$berkas}: elemen tanpa label.");
                $id[] = $elemen['id'];

                if (in_array($elemen['type'], ['dropdown', 'checkboxes'], true)) {
                    $this->assertNotEmpty($elemen['attributes']['options'], "{$berkas}: {$elemen['id']} tanpa pilihan.");
                }
            }

            $this->assertSame(array_values(array_unique($id)), $id, "{$berkas}: id elemen ganda.");
        }
    }

    public function test_blank_issues_are_off_and_vulnerabilities_go_to_private_reporting(): void
    {
        $konfigurasi = Yaml::parseFile(base_path('.github/ISSUE_TEMPLATE/config.yml'));

        $this->assertFalse($konfigurasi['blank_issues_enabled']);
        $this->assertStringEndsWith('/security/advisories/new', $konfigurasi['contact_links'][0]['url']);
    }

    public function test_the_database_readme_names_every_table_and_spatial_column(): void
    {
        $ringkasan = File::get(base_path('database/README.md'));
        $hilang = [];

        foreach (File::files(base_path('database/migrations')) as $migrasi) {
            $isi = $migrasi->getContents();

            preg_match_all("/Schema::create\('([a-z_]+)'/", $isi, $tabel);
            preg_match_all("/PostGis::addGeography\('([a-z_]+)', '([a-z_]+)'/", $isi, $geografi, PREG_SET_ORDER);

            foreach ($tabel[1] as $nama) {
                if (! str_contains($ringkasan, "`{$nama}`")) {
                    $hilang[] = $nama;
                }
            }

            foreach ($geografi as [, $namaTabel, $kolom]) {
                if (! str_contains($ringkasan, "`{$namaTabel}.{$kolom}`")) {
                    $hilang[] = "{$namaTabel}.{$kolom}";
                }
            }
        }

        $this->assertSame([], $hilang, 'database/README.md belum menyebut: '.implode(', ', $hilang));
    }

    public function test_policy_files_carry_no_em_dash(): void
    {
        foreach (self::BERKAS_KEBIJAKAN as $berkas) {
            $this->assertStringNotContainsString("\u{2014}", File::get(base_path($berkas)), "{$berkas} memuat em dash.");
        }
    }
}
```

Tambahkan `'SECURITY.md',` ke `RepoHygieneTest::ENTRI_ROOT` (sesudah `'README.md',`) dan `'SECURITY.md', 'database/README.md', '.github/PULL_REQUEST_TEMPLATE.md',` ke `PrdReferenceIntegrityTest::DOKUMEN_HIDUP`.

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='RepoPolicyFilesTest|PrdReferenceIntegrityTest'`
Expected: FAIL, "File does not exist" untuk `SECURITY.md`, `database/README.md`, templat PR, dan formulir isu.

- [ ] **Step 3: Tulis `SECURITY.md`.**

```markdown
# Kebijakan Keamanan

## Melaporkan kerentanan

Jangan membuka isu, pull request, atau diskusi publik untuk kerentanan. Laporkan secara
privat lewat fitur GitHub private vulnerability reporting: buka tab **Security** di
repositori ini, pilih **Advisories**, lalu **Report a vulnerability**. Laporan hanya
terlihat oleh pengelola repositori.

Bila tombol itu belum muncul karena fitur belum diaktifkan pemilik, hubungi pemilik
repositori lewat saluran tim yang sudah ada, tanpa menuliskan detail kerentanan di isu,
commit, atau kanal publik.

Sertakan bagian yang terdampak (rute, berkas, atau fitur), langkah memunculkannya, dampak
yang Anda amati, dan commit yang Anda uji. Jangan menyertakan data pribadi pengguna nyata,
dan jangan menguji terhadap basis data produksi atau akun orang lain; pakai lingkungan
lokal sesuai `README.md`.

## Cakupan

Termasuk: kode aplikasi di repositori ini, yaitu autentikasi dan otorisasi (termasuk akses
ke data milik pengguna lain), validasi input, unggahan foto, data lokasi, tautan bagikan,
konsol pengelola, service worker, dan konfigurasi CI.

Tidak termasuk: layanan pihak ketiga (Supabase, BMKG, penyedia ubin peta). Laporkan ke
penyedianya.

## Rahasia

- Kredensial hanya tinggal di `.env`, yang diabaikan git. Kredensial tidak pernah ditulis ke kode, skrip, test, dokumen, atau pesan commit, dan tidak dicetak ke terminal (`CLAUDE.md` R-019).
- `SecretsNotInRepoTest` memindai setiap berkas yang dilacak dan setiap berkas baru yang tidak diabaikan git. Workflow `.github/workflows/security.yml` menjalankannya bersama `composer audit` dan `npm audit` pada setiap push, pull request, dan setiap Senin.
- Satu-satunya kata sandi di repo adalah kata sandi kontainer uji sekali pakai di `docker-compose.yml` dan `.github/workflows/test.yml`: hanya terikat ke 127.0.0.1 dan tidak pernah memegang data sungguhan.
- Bila rahasia terlanjur ter-commit, anggap bocor. Pemilik memutar kredensialnya lebih dulu di dasbor penyedia; menghapus barisnya saja tidak cukup karena riwayat git menyimpannya. Menulis ulang riwayat butuh izin pemilik (`CLAUDE.md` R-018).

## Versi yang didukung

Belum ada rilis bernomor. Perbaikan keamanan dikerjakan di cabang kerja aktif (saat ini
`hardening`) dan masuk ke `main` setelah disetujui pemilik.
```

- [ ] **Step 4: Tulis `database/README.md`.**

```markdown
# Basis data

Ringkasan skema Memount per domain, catatan PostGIS, dan kebijakan migrasi. Skema kanonis
adalah isi `database/migrations/`; ringkasan ini menunjuk ke sana, dan `RepoPolicyFilesTest`
memastikan setiap tabel yang dibuat migrasi tercantum di sini. Model domain dan data ada di
`docs/PRD.md` (§14, §64, §65); lapisan kode dan aturan arsitektur di `docs/ARCHITECTURE.md`.

## Tabel per domain

| Domain | Tabel | Catatan |
|---|---|---|
| Akun dan profil pendaki | `users`, `password_reset_tokens`, `sessions`, `profiles`, `user_experience`, `user_preferences` | Peran di `users.role`; pengalaman yang diisi sendiri dipakai Route Fit |
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain` | Hierarki otoritas (R-009) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
| Pertimbangkan | `trail_considerations` | Simpanan pribadi dan catatannya (FR-22) |
| Rencana dan persiapan | `hiking_goals`, `trip_plans`, `trip_reminders`, `preparation_templates`, `preparation_items`, `trip_preparation_items`, `readiness_checks`, `user_owned_items` | Kesiapan adalah pendukung keputusan (R-014) |
| Pendakian dan progres | `hiking_sessions`, `hike_track_points`, `hiking_history`, `personal_targets` | Lokasi presisi privat secara bawaan (BR-13) |
| Tata kelola dan operasi | `moderation_actions`, `audit_logs`, `analytics_events`, `usability_sessions`, `scheduled_task_runs` | Jejak audit mencatat nilai lama dan nilai baru |
| Kerangka Laravel | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Bawaan kerangka |

## PostGIS

- Ekstensi `postgis` dipasang migrasi `2026_09_19_000001_enable_postgis_extension`.
- Kolom geografi ditambahkan dengan SQL mentah lewat `App\Support\PostGis::addGeography()`: tipe `geography(TYPE,4326)` beserta indeks GIST bernama menurut pola tabel, kolom, dan akhiran `_gix`. Pada koneksi selain PostgreSQL pemanggilannya tidak melakukan apa-apa, sehingga suite SQLite menjalankan migrasi yang sama tanpa kolom spasial.
- Enam kolom geografi: `mountains.location` (POINT), `trails.geometry` (LINESTRING), `trail_segments.geometry` (LINESTRING), `checkpoints.location` (POINT), `restricted_areas.geometry` (POLYGON), `hiking_sessions.last_known_location` (POINT).
- `mountains` dan `checkpoints` juga menyimpan `latitude` dan `longitude` biasa untuk dibaca di luar PostGIS; kueri spasial tetap memakai kolom geografi.
- Kode spasial diuji suite `tests/Spatial` di PostgreSQL + PostGIS (CI, atau lokal lewat `docker-compose.yml`). Suite SQLite yang hijau tidak membuktikan perilaku PostgreSQL (R-024).

## Akses dari luar aplikasi

Aplikasi terhubung langsung sebagai pemilik tabel dan tidak memakai REST API Supabase.
Migrasi `2026_09_20_000100_tutup_akses_postgrest_ke_tabel_aplikasi` mencabut hak peran
`anon` dan `authenticated` (termasuk hak bawaan untuk tabel yang dibuat kemudian, lewat
`ALTER DEFAULT PRIVILEGES`) dan menyalakan RLS tanpa policy pada tabel yang ada saat migrasi
itu berjalan; alasannya di `docs/ARCHITECTURE.md` bagian "Catatan operasional".

Keadaan per 26 September 2026: dari tabel yang dibuat sesudah migrasi itu, hanya
`trip_reminders` yang menyalakan RLS di migrasinya sendiri, dan `trail_considerations` lewat
`2026_09_25_002314_add_note_to_trail_considerations_table`. Dua belas tabel lainnya
(`permit_requirements`, `authorities`, `authority_mountain`, `expert_credentials`,
`credential_mountain`, `usability_sessions`, `scheduled_task_runs`, `hike_track_points`,
`report_thanks`, `mountain_follows`, `personal_targets`, `user_owned_items`) hanya dilindungi
pencabutan hak itu; lapis kedua untuk mereka menunggu keputusan pemilik (spec v3 bagian 15, Q22).
Migrasi baru yang membuat tabel wajib menyalakan RLS pada PostgreSQL mengikuti pola
`2026_09_25_000918_create_trip_reminders_table`.

## Kebijakan migrasi

1. Migrasi dibuat dengan `php artisan make:migration --no-interaction` dan bersifat aditif: tabel atau kolom baru. Menghapus kolom, tabel, atau data butuh izin pemilik (DK-23, R-021).
2. Setiap migrasi diuji suite di SQLite dan, bila menyentuh kolom spasial, transaksi, atau constraint, suite `Spatial` di PostgreSQL + PostGIS.
3. Migrasi tidak pernah dijalankan di Supabase tanpa izin pemilik per migrasi (R-019, DK-23). `.claude/settings.json` menolak `php artisan migrate` bagi agen.
4. Setelah dijalankan atas izin, status migrasi diverifikasi di lingkungan sasaran (`php artisan migrate:status`, atau halaman Kesehatan Sistem di konsol pengelola), bukan disimpulkan dari test (R-024).
5. Kolom yang datanya bisa kosong menyimpan `null`, bukan nol (R-007).

## Data contoh, tanpa dump

- Tidak ada dump basis data di repo: berkas `.sql`, `.sql.gz`, `.dump`, dan SQLite diabaikan git, karena dump dapat memuat data pengguna sungguhan.
- Data lokal berasal dari seeder dan pabrik. `DatabaseSeeder` membuat tiga akun lokal (admin, moderator, pendaki; lihat `README.md`) lalu memanggil `RecommendationRuleSeeder`, `PreparationTemplateSeeder`, `MvpDatasetSeeder`, `MountainSeeder`, `OfficialStatusSeeder`, `PermitRequirementSeeder`, `AuthoritySeeder`, dan `AwaitingTrailSeeder`. Isian yang tidak diketahui sumbernya dibiarkan kosong, tidak ditebak.
- `db:seed` hanya ke basis data lokal. Data contoh berlabel DATA CONTOH untuk peninjauan lokal menyusul di W1 (DK-35, izin Q1).
- Snapshot skema Supabase sebelum hardening (tanpa data) disimpan sebagai catatan sejarah di `docs/db/schema-snapshot-sebelum-hardening.md`.
```

- [ ] **Step 5: Tulis `.github/PULL_REQUEST_TEMPLATE.md`.**

```markdown
## Ringkasan

<!-- Apa yang berubah dan kenapa, dalam satu atau dua kalimat. Tautkan isu, tugas rencana, atau keputusan (DK) yang terkait. -->

## Bukti

<!-- Nama test, keluaran perintah dengan angka persis, dan tangkapan 390 px serta 1440 px untuk perubahan UI. Tanpa bukti, tidak ada PASS (R-023). -->

## Aturan yang tersentuh

<!-- Sebut aturan R dan BR yang disentuh perubahan ini dan bagaimana dipatuhi, misalnya R-004 label kecocokan atau R-012 status Buka bukan jaminan. -->

## Daftar periksa

- [ ] Test ditulis lebih dulu dan gagal sebelum implementasi
- [ ] `php artisan test --compact --testsuite=Unit,Feature` hijau
- [ ] Suite `Spatial` dijalankan bila menyentuh kolom spasial, transaksi, atau constraint (R-024)
- [ ] `npm run types` nol galat
- [ ] `npm run build` sukses
- [ ] `vendor/bin/pint --test` bersih
- [ ] Delivery Gate antislop PASS dengan bukti per butir untuk perubahan UI dan teks
- [ ] Aturan R dan BR yang tersentuh disebut di atas dan dipatuhi
- [ ] Tidak ada kredensial, `.env`, atau dump basis data; `SecretsNotInRepoTest` hijau
- [ ] Migrasi baru tidak dijalankan di Supabase tanpa izin pemilik (R-019)
- [ ] Dokumen yang terdampak diperbarui; `HANDOVER.md` bagian 0 diperbarui di akhir gelombang
- [ ] Rekap memuat persentase progres beserta metodenya
```

- [ ] **Step 6: Tulis formulir isu.**

`.github/ISSUE_TEMPLATE/bug.yml`:

```yaml
name: Laporan bug
description: Sesuatu tidak berjalan seperti seharusnya.
title: "[Bug] "
labels: ["bug"]
body:
  - type: markdown
    attributes:
      value: |
        Terima kasih sudah melapor. Kerentanan keamanan jangan dilaporkan di sini; ikuti SECURITY.md.
        Jangan menempelkan data pribadi, kata sandi, token, atau isi berkas .env.
  - type: textarea
    id: langkah
    attributes:
      label: Langkah untuk memunculkan
      description: Urutan langkah dari halaman awal, termasuk jenis akun dan data yang dipakai, tanpa data pribadi.
      placeholder: "1. Buka /trails\n2. Pilih saringan tanjakan\n3. Buka jalur pertama"
    validations:
      required: true
  - type: textarea
    id: harapan
    attributes:
      label: Yang diharapkan
    validations:
      required: true
  - type: textarea
    id: kenyataan
    attributes:
      label: Yang terjadi
      description: Pesan galat, tangkapan layar, atau keluaran perintah.
    validations:
      required: true
  - type: dropdown
    id: lebar
    attributes:
      label: Lebar layar
      options:
        - Ponsel (sekitar 360 sampai 430 px)
        - Tablet
        - Layar lebar (1024 px ke atas)
    validations:
      required: true
  - type: input
    id: peramban
    attributes:
      label: Peramban dan perangkat
      placeholder: "Chrome di Android"
  - type: dropdown
    id: dampak
    attributes:
      label: Dampak
      options:
        - Menghalangi alur inti (jelajah, rencana, persiapan, mendaki)
        - Mengganggu tetapi ada jalan lain
        - Kosmetik
    validations:
      required: true
  - type: checkboxes
    id: aturan
    attributes:
      label: Aturan yang mungkin terlanggar
      description: Centang bila bug menyentuh aturan kanonis.
      options:
        - label: Data yang tidak ada tampil sebagai angka atau tebakan (R-006, R-007)
        - label: Laporan komunitas atau AI menimpa status resmi (R-009 sampai R-011)
        - label: Status Buka terbaca sebagai jaminan keselamatan (R-012)
        - label: Skor atau persen kecocokan tampil (R-004, R-005)
        - label: Data milik pengguna lain dapat dibuka
```

`.github/ISSUE_TEMPLATE/fitur.yml`:

```yaml
name: Usulan fitur
description: Kebutuhan baru atau perubahan perilaku produk.
title: "[Fitur] "
labels: ["enhancement"]
body:
  - type: markdown
    attributes:
      value: |
        Fitur baru masuk MVP hanya bila memenuhi feature gate PRD §131. Perubahan arah produk butuh persetujuan pemilik dan governance record (R-021, PRD §130).
  - type: textarea
    id: masalah
    attributes:
      label: Masalah pendaki yang diselesaikan
      description: Siapa, dalam situasi apa, dan bukti bahwa masalahnya nyata (riset, uji kegunaan, laporan).
    validations:
      required: true
  - type: dropdown
    id: loop
    attributes:
      label: Posisi di loop produk (PRD §7)
      options:
        - PROFILE
        - ROUTE FIT
        - PLAN
        - PREPARE
        - CHECK
        - HIKE
        - REPORT
        - IMPROVE
    validations:
      required: true
  - type: checkboxes
    id: gerbang
    attributes:
      label: Feature gate (PRD §131), centang yang dipenuhi
      options:
        - label: Meningkatkan pemilihan rute
        - label: Meningkatkan persiapan
        - label: Meningkatkan kesadaran akan kondisi terkini
        - label: Meningkatkan keterpakaian di lapangan
        - label: Meningkatkan kepercayaan data
  - type: textarea
    id: usulan
    attributes:
      label: Usulan perilaku
    validations:
      required: true
  - type: textarea
    id: dampak
    attributes:
      label: Dampak dan risiko
      description: Aturan kanonis yang tersentuh, data, keamanan dan privasi, cakupan MVP.
  - type: dropdown
    id: arah
    attributes:
      label: Apakah usulan ini mengubah arah produk (R-021)?
      options:
        - Tidak
        - Ya, butuh persetujuan pemilik dan governance record
        - Belum tahu
    validations:
      required: true
```

`.github/ISSUE_TEMPLATE/config.yml`:

```yaml
blank_issues_enabled: false
contact_links:
  - name: Laporan kerentanan keamanan
    url: https://github.com/hanifp62/praktik_aplikasi_web/security/advisories/new
    about: Laporkan secara privat lewat GitHub private vulnerability reporting, bukan lewat isu publik (lihat SECURITY.md).
```

- [ ] **Step 7: Tautkan dari README dan CONTRIBUTING.**

`README.md` bagian "Keamanan", ganti butir terakhir `- Laporkan kerentanan langsung dan secara pribadi kepada pemilik produk, bukan lewat isu publik.` (dua baris) menjadi `- Laporkan kerentanan secara privat menurut `SECURITY.md`, bukan lewat isu publik.` Di "Peta repo", ganti sel isi baris `database/migrations`, `database/seeders` menjadi `Skema kanonis dan data contoh; ringkasan per domain dan kebijakan migrasi di `database/README.md``.

`CONTRIBUTING.md`: langkah 6 "Alur kerja" menjadi `6. Buka pull request dengan templatnya (`.github/PULL_REQUEST_TEMPLATE.md`). Push, merge, tag, dan rilis butuh persetujuan pemilik produk (CLAUDE.md R-018).`; bagian "Basis data dan migrasi" ditambah butir pertama `- Ringkasan skema per domain, catatan PostGIS, dan kebijakan migrasi ada di `database/README.md`.`; bagian "Keamanan dan privasi" ditambah butir `- Kerentanan dilaporkan secara privat menurut `SECURITY.md`.`

- [ ] **Step 8: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='RepoPolicyFilesTest|RepoHygieneTest|PrdReferenceIntegrityTest|CiWorkflowsTest|SecretsNotInRepoTest'`
Expected: PASS semua. `RepoPolicyFilesTest::test_the_database_readme_names_every_table_and_spatial_column` membaca 57 migrasi, 48 tabel, 6 kolom geografi.

- [ ] **Step 9: Format.** Run: `vendor/bin/pint --format agent tests/Feature/RepoPolicyFilesTest.php tests/Feature/RepoHygieneTest.php tests/Feature/PrdReferenceIntegrityTest.php`.

- [ ] **Step 10: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add SECURITY.md database/README.md .github/PULL_REQUEST_TEMPLATE.md .github/ISSUE_TEMPLATE tests/Feature/RepoPolicyFilesTest.php
git commit -m "docs: SECURITY.md, ringkasan skema database, dan templat PR serta isu" -- SECURITY.md database/README.md .github/PULL_REQUEST_TEMPLATE.md .github/ISSUE_TEMPLATE tests/Feature/RepoPolicyFilesTest.php tests/Feature/RepoHygieneTest.php tests/Feature/PrdReferenceIntegrityTest.php README.md CONTRIBUTING.md
```

---

### Task 6: Tata kelola skill dan urutan wewenang DK-57

**Files:**
- Modify: `docs/MATRIKS-SKILL.md` (ditulis ulang seluruhnya)
- Create: `tests/Feature/SkillGovernanceTest.php`
- Test: `tests/Feature/SkillGovernanceTest.php`

**Interfaces:**
- Consumes: folder `.agents/skills/` dan `.claude/skills/`, `skills-lock.json`.
- Produces: empat frasa urutan wewenang yang juga dipakai `docs/AGENTS.md` di Tugas 8, persis: `1. **Arahan pemilik dan aturan kanonis**`, `2. **`DESIGN.md`**`, `3. **Saringan antislop**`, `4. **Saran skill**`.

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction SkillGovernanceTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Setiap skill terpasang punya klasifikasi dan tunduk pada satu urutan wewenang.
 *
 * Pemilik meminta skill UI/UX dipasang dan selalu diterapkan dengan dibandingkan lalu
 * digabung (spec v3 bagian 17.1). Skill yang terpasang tanpa klasifikasi dijalankan
 * sembarangan atau dilupakan, dan saran skill yang bertentangan tanpa urutan wewenang
 * diputuskan menurut selera sesi. DK-57 menetapkan urutannya; test ini menjaganya.
 */
class SkillGovernanceTest extends TestCase
{
    private const URUTAN_WEWENANG = [
        '1. **Arahan pemilik dan aturan kanonis**',
        '2. **`DESIGN.md`**',
        '3. **Saringan antislop**',
        '4. **Saran skill**',
    ];

    public function test_every_installed_skill_is_classified_in_the_matrix(): void
    {
        $terpasang = [];

        foreach (['.agents/skills', '.claude/skills'] as $folder) {
            if (File::isDirectory(base_path($folder))) {
                foreach (File::directories(base_path($folder)) as $skill) {
                    $terpasang[] = basename($skill);
                }
            }
        }

        $belum = $this->belumTerklasifikasi(array_unique($terpasang));

        $this->assertSame([], $belum, 'Skill terpasang tanpa klasifikasi di docs/MATRIKS-SKILL.md: '.implode(', ', $belum));
    }

    public function test_every_locked_third_party_skill_is_classified_in_the_matrix(): void
    {
        $kunci = json_decode(File::get(base_path('skills-lock.json')), true, flags: JSON_THROW_ON_ERROR);
        $belum = $this->belumTerklasifikasi(array_keys($kunci['skills']));

        $this->assertSame([], $belum, 'Skill di skills-lock.json tanpa klasifikasi: '.implode(', ', $belum));
    }

    public function test_the_matrix_states_the_dk57_authority_order(): void
    {
        $matriks = $this->matriks();
        $sebelumnya = -1;

        $this->assertStringContainsString('DK-57', $matriks);

        foreach (self::URUTAN_WEWENANG as $tingkat) {
            $posisi = strpos($matriks, $tingkat);

            $this->assertNotFalse($posisi, "Tingkat wewenang tidak ada: {$tingkat}");
            $this->assertGreaterThan($sebelumnya, $posisi, "Urutan wewenang DK-57 terbalik di {$tingkat}.");

            $sebelumnya = $posisi;
        }
    }

    public function test_persisting_a_design_system_is_routed_to_design_md(): void
    {
        $baris = array_values(array_filter(
            preg_split('/\R/', $this->matriks()),
            fn (string $isi): bool => str_starts_with($isi, '| `ui-ux-pro-max`')
        ));

        $this->assertCount(1, $baris, 'Matriks harus punya tepat satu baris untuk ui-ux-pro-max.');
        $this->assertStringContainsString('Persist Design System', $baris[0]);
        $this->assertStringContainsString('`DESIGN.md`', $baris[0]);
    }

    /**
     * @param  array<int, string>  $skill
     * @return array<int, string>
     */
    private function belumTerklasifikasi(array $skill): array
    {
        $matriks = $this->matriks();

        return array_values(array_filter($skill, fn (string $nama): bool => ! str_contains($matriks, "`{$nama}`")));
    }

    private function matriks(): string
    {
        return File::get(base_path('docs/MATRIKS-SKILL.md'));
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=SkillGovernanceTest`
Expected: FAIL. Test pertama menyebut antara lain `antislop`, `antislop-code`, `ui-ux-pro-max`, `design`, `slides`, `ui-styling` (matriks lama menulis nama tanpa backtick); test urutan gagal "Tingkat wewenang tidak ada"; test `ui-ux-pro-max` gagal karena barisnya belum ada.

- [ ] **Step 3: Tulis ulang `docs/MATRIKS-SKILL.md` seluruhnya.**

```markdown
# Matriks Skill

Inventaris skill yang terpasang untuk proyek ini, klasifikasinya (CLAUDE.md bagian 7), cara
menggabungkannya, dan status pelacakannya di git. Diperbarui 26 September 2026 atas arahan
pemilik (spec v3 bagian 17.1): "install dan selalu terapkan skill berikut untuk menunjang
pengembangan app terutama ui/ux compare dan combine skill berikut [...] terapkan combine
dengan skill" lainnya seperti impactcable dll." ("impactcable" adalah `impeccable`.) Arahan
25 September 2026 tetap berlaku: "pastikan untuk selalu menerapkan semua skill disini compare
dan combine semua skill disini".

**Klasifikasi.** INTI: dipakai setiap kali area itu disentuh. PENDUKUNG: dipakai untuk
tugas tertentu di area itu. LINTAS: berlaku di semua area. TIDAK RELEVAN: disebut
alasannya, supaya tidak dijalankan demi formalitas.

## Urutan wewenang ketika skill bertentangan (DK-57)

1. **Arahan pemilik dan aturan kanonis**: keputusan dan kata-kata pemilik (log keputusan `docs/PRD.md`, bagian Arah `DESIGN.md`), aturan R-001 sampai R-024 (`CLAUDE.md`, `.claude/rules/mountain-core.md`, `DESIGN.md`), serta BR-01 sampai BR-16.
2. **`DESIGN.md`**: token dan turunannya (warna, tipografi DK-38, ikon DK-39, radius, gerak).
3. **Saringan antislop**: menyaring, tidak pernah menciptakan arah.
4. **Saran skill**: `ui-ux-pro-max`, `design-taste-frontend`, `impeccable`, `apple-design`, `emil-design-eng`, dan lainnya; diambil mekanismenya, bukan rupanya (prinsip ATM di PRD).

Tiga aturan penggabungan:

- Skill tidak menciptakan arah baru. Palet dan pasangan huruf dari basis data `ui-ux-pro-max` dipakai sebagai pembanding dan pemeriksa, bukan pengganti keputusan DK-38 atau token `resources/css/app.css`.
- Langkah "Persist Design System" `ui-ux-pro-max` ditulis ke `DESIGN.md`, bukan folder `design-system/` baru di root; `RepoHygieneTest` menolak entri root yang belum diputuskan.
- Skill pihak ketiga ditinjau sebelum dipasang atau diperbarui: hasil pemindai keamanan yang ditampilkan `npx skills add` (misalnya Gen, Socket, Snyk) dan isi berkas SKILL.md-nya dibaca lebih dulu; versi yang terpasang tercatat di `skills-lock.json`.

## Menggabungkan skill UI/UX dalam satu pekerjaan

| Langkah | Skill | Hasil yang diambil |
|---|---|---|
| Arah | `DESIGN.md`, `impeccable` (membaca `PRODUCT.md` dan `DESIGN.md` di root) | Batas visual dan nada yang tidak boleh dilanggar |
| Pembanding pola | `ui-ux-pro-max` | Pola UX, daftar periksa aksesibilitas, pembanding palet dan huruf |
| Bentuk, kritik, audit, poles | `impeccable`, `redesign-existing-projects`, `design-taste-frontend` (perombakan layar dan halaman publik) | Susunan layar dan kritik yang bersumber |
| Gerak dan rasa | `emil-design-eng`, `apple-design`, `animate` dan keluarganya, `mobile-native` | Keputusan gerak dan interaksi sentuh di dalam dial dan daftar tertutup `DESIGN.md` |
| Implementasi | `tailwindcss-development`, `laravel-best-practices`, `testing-best-practices` | Kelas Tailwind bertoken, kode server dan test yang benar |
| Saringan | `antislop` dengan `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | Delivery Gate PASS dengan bukti per butir |

Bila saran dua skill bertentangan, tulis keduanya beserta alasannya di rekap, lalu putuskan
dengan urutan wewenang di atas.

## Skill proyek (`.agents/skills`, `.claude/skills`)

| Skill | Kelas | Dipakai untuk | Catatan gabungan |
|---|---|---|---|
| `antislop` | LINTAS | Saringan akhir setiap perubahan UI, teks, dan komentar; Delivery Gate | Dimuat bersama skill desain mana pun; menang atas resep gaya |
| `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | LINTAS | Saringan per area: antarmuka, tata letak seluler, teks, kode, suara manusia | Dimuat bersama `antislop` |
| `impeccable` | INTI desain | Bentuk, kritik, audit, poles, adaptasi | Membaca `PRODUCT.md` dan `DESIGN.md` di root; penanda skema dipertahankan |
| `ui-ux-pro-max` | INTI desain (pembanding) | Pola UX, aksesibilitas, palet dan pasangan huruf sebagai pembanding | "Persist Design System" ditulis ke `DESIGN.md`, bukan folder `design-system/`; tidak mengganti DK-38 |
| `design-system` | PENDUKUNG | Memeriksa arsitektur token tiga lapis terhadap `resources/css/app.css` | Bagian pembuatan slide tidak dipakai |
| `ui-styling` | PENDUKUNG terbatas | Pola gaya Tailwind yang aksesibel | Resep shadcn/ui dan Radix butuh dependensi baru; tidak dipasang tanpa izin (DK-24) |
| `brand` | PENDUKUNG | Konsistensi suara dan identitas | Kontrak merek di `docs/PRD.md` §156 dan `DESIGN.md` menang |
| `design`, `banner-design`, `slides` | TIDAK RELEVAN saat ini | Logo lewat pembangkit gambar, banner iklan, presentasi | Aset logo final menunggu gerbang DK-16; dipakai bila pemilik meminta materi pemasaran |
| `redesign-existing-projects` | INTI desain | Merombak halaman yang ada tanpa merusak fungsi | Audit dulu, lalu ubah per halaman |
| `emil-design-eng` | INTI gerak | Filosofi detail dan gerak (Emil Kowalski) | Bersama `animate`, `improve-animations`, `review-animations` |
| `animate`, `improve-animations`, `find-animation-opportunities`, `review-animations`, `animation-vocabulary` | INTI gerak | Memutuskan, membangun, mengaudit, dan meninjau gerak | Setiap gerak harus menjelaskan; `prefers-reduced-motion` wajib |
| `apple-design` | PENDUKUNG | Gestur, pegas, seret, lembar bawah, material | Kaca paling banyak dua elemen per layar (rencana induk v3) |
| `mobile-native` | INTI seluler | Rasa terpasang di ponsel: area aman, sentuh, meta | PWA sudah ada; lengkapi, jangan ganti |
| `tailwindcss-development` | INTI implementasi | Kelas Tailwind 3.4 dan token | Tanpa `dark:` dan warna palet mentah; token dari `app.css` |
| `design-taste-frontend` | INTI desain (arahan pemilik 26 September 2026: selalu diterapkan dan digabung) | Perombakan layar yang ada dengan audit lebih dulu (cakupannya sendiri: "landing pages, portfolios, and redesigns"), halaman publik, dan pemeriksaan pra-kirim anti-generik | Menurut cakupannya bukan untuk dasbor, tabel data, dan alur bertahap; di layar itu yang diambil hanya pemeriksaan anti-generik dan kontras formulirnya. Rekomendasi pustakanya (Motion, GSAP, Tailwind v4) tidak dipakai (DK-24, R-020); huruf, warna, dan geraknya kalah oleh `DESIGN.md` |
| `design-taste-frontend-v1`, `high-end-visual-design`, `stitch-design-taste`, `minimalist-ui` | PENDUKUNG | Rujukan tata letak dan standar anti-generik tambahan | Font, bayangan, dan warna resepnya kalah oleh `DESIGN.md` |
| `gpt-taste` | TIDAK RELEVAN untuk layar aplikasi | Mewajibkan GSAP dan struktur AIDA pemasaran | Bertentangan dengan DK-24 dan mode kerja aplikasi |
| `industrial-brutalist-ui` | TIDAK RELEVAN | Estetika terminal militer | Tidak sejalan dengan merek dan konteks keselamatan |
| `prototype` | PENDUKUNG | Membandingkan beberapa versi layar kunci | Dipakai bila ada waktu untuk varian |
| `pick-ui-library`, `ask-sonner` | PENDUKUNG keputusan | Tabel pembanding pustaka | Pemasangan tetap butuh izin pemilik |
| `brandkit`, `imagegen-frontend-mobile`, `imagegen-frontend-web`, `image-to-code` | TIDAK RELEVAN saat ini | Butuh pembangkit gambar | Dipakai bila pemilik meminta papan identitas |
| `laravel-best-practices` | INTI backend | Controller, model, migrasi, request, policy | Bersama `testing-best-practices` |
| `testing-best-practices` | INTI test | Cakupan, nama, struktur, isolasi | Test bukan bukti PostgreSQL (R-024) |
| `infer-conventions` | PENDUKUNG | Merekam konvensi ke `.ai/rules` | Hanya bila pemilik meminta aturan dicatat |
| `ngodingpakeai` | LINTAS (memori proyek) | Sinkron ringkasan kode dan tugas | Jangan `plan get` di root (menulis berkas PRD versi server dan `features/`) |
| `full-output-enforcement` | LINTAS | Kode lengkap, tanpa potongan dan placeholder | |
| `livewire-development`, `volt-development` | TIDAK RELEVAN | Livewire sudah dipensiunkan (jalur C) | Paket masih terpasang sampai pemilik memutuskan (P11) |
| `deploying-to-cloud` | TIDAK RELEVAN | Laravel Cloud | Sasaran deploy adalah Vercel (DK-12) |
| `animate-expo`, `write-swift` | TIDAK RELEVAN | React Native dan Swift | Tidak ada aplikasi native |

## Status pelacakan di git (Q19, menunggu keputusan pemilik)

Keadaan 26 September 2026 menurut `git ls-files`:

- Dilacak, milik proyek: `antislop` dan lima turunannya di `.claude/skills/`.
- Dilacak, salinan pihak ketiga di `.agents/skills/`: 26 skill yang tercatat di `skills-lock.json` ditambah `impeccable` (dipasang lewat `npx impeccable install`; biner mesinnya diabaikan git).
- Belum dilacak: tujuh skill dari `uipro init` (`ui-ux-pro-max`, `design-system`, `ui-styling`, `brand`, `design`, `banner-design`, `slides`) dan tujuh skill Laravel Boost (`laravel-best-practices`, `testing-best-practices`, `tailwindcss-development`, `livewire-development`, `volt-development`, `deploying-to-cloud`, `infer-conventions`).
- Diabaikan git: salinan di `.claude/skills/` selain keluarga antislop, termasuk `ngodingpakeai` yang dibangkitkan CLI-nya.

Usulan Q19 (spec v3 bagian 15): skill milik proyek dan `skills-lock.json` dilacak; salinan
pihak ketiga dipulihkan lewat `npx skills experimental_install`. Sampai pemilik memutuskan,
folder yang belum dilacak tidak di-stage.

## Skill proses (superpowers)

| Skill | Kelas | Dipakai untuk |
|---|---|---|
| brainstorming | INTI (awal) | Mengubah arahan menjadi spec |
| writing-plans, executing-plans | INTI | Rencana implementasi bertahap dan pelaksanaannya |
| subagent-driven-development, dispatching-parallel-agents | INTI | Pembagian kerja ke subagen dengan batas berkas yang jelas |
| test-driven-development | INTI | Test merah dulu, lalu implementasi |
| systematic-debugging | PENDUKUNG | Setiap cacat: bukti, hipotesis, uji |
| verification-before-completion | LINTAS | Tidak ada klaim selesai tanpa bukti (R-023) |
| requesting-code-review, receiving-code-review | PENDUKUNG | Tinjauan sebelum diserahkan ke pemilik |
| finishing-a-development-branch | PENDUKUNG | Menutup branch setelah pemilik menyetujui commit |
| using-git-worktrees, writing-skills | TIDAK RELEVAN saat ini | Subagen berbagi satu tree dengan batas berkas; tidak menulis skill baru |

## Skill plugin ECC yang relevan

| Area | Skill | Kelas |
|---|---|---|
| Riset | deep-research, search-first, research-ops, market-research, competitive-platform-analysis, competitive-report-structure | INTI untuk riset |
| Produk | product-lens, product-capability | PENDUKUNG |
| Desain dan gerak | frontend-design-direction, design-system (plugin), liquid-glass-design, make-interfaces-feel-better, motion-foundations, motion-patterns, motion-advanced, dashboard-builder | INTI atau PENDUKUNG sesuai layar |
| Aksesibilitas | accessibility, frontend-a11y | INTI |
| Frontend | react-patterns, react-performance, react-testing, vite-patterns, frontend-patterns | INTI implementasi |
| Backend dan data | laravel-patterns, laravel-security, laravel-tdd, laravel-verification, postgres-patterns, database-migrations | INTI backend |
| Mutu dan rilis | security-review, security-scan, verification-loop, delivery-gate, production-audit, browser-qa, click-path-audit, e2e-testing | PENDUKUNG |
| Dokumen dan repo | codebase-onboarding, living-docs-governance, architecture-decision-records, repo-scan, workspace-surface-audit | PENDUKUNG |
| Lain-lain (sekitar 250 skill: domain kesehatan, keuangan, jaringan, bahasa lain, video) | TIDAK RELEVAN | Di luar domain produk |

## Skill pengguna dan claude-mem

| Skill | Kelas |
|---|---|
| investigate-first, safe-refactor, surgical-patch, verify-and-stop, lean-build, migration | PENDUKUNG (prinsip kerja yang sejalan dengan CLAUDE.md) |
| browser-use | PENDUKUNG (bukti peramban; alternatifnya skrip CDP di scratchpad) |
| caveman dan turunannya | TIDAK RELEVAN (gaya jawaban ringkas bertentangan dengan rekap berbahasa Indonesia yang diminta pemilik) |
| mem-search, smart-explore, make-plan, learn-codebase (claude-mem) | LINTAS (mengingat sesi sebelumnya) |
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='SkillGovernanceTest|PrdReferenceIntegrityTest'`
Expected: PASS semua.

- [ ] **Step 5: Format.** Run: `vendor/bin/pint --format agent tests/Feature/SkillGovernanceTest.php`.

- [ ] **Step 6: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah. Folder skill yang belum dilacak tidak ikut (Q19).

```bash
git add docs/MATRIKS-SKILL.md tests/Feature/SkillGovernanceTest.php
git commit -m "docs: matriks skill mencakup semua skill terpasang dan urutan wewenang DK-57" -- docs/MATRIKS-SKILL.md tests/Feature/SkillGovernanceTest.php
```

---

### Task 7: Lisensi MIT Phosphor dan daftar lisensi pihak ketiga

**Files:**
- Create: `resources/js/components/ui/LICENSE-phosphor.txt`
- Modify: `resources/js/components/ui/Icon.tsx` (komentar lisensi di baris pertama)
- Modify: `resources/views/components/ui/icon.blade.php` (satu baris di komentar `@php`)
- Create: `docs/lisensi-pihak-ketiga.md`
- Create: `tests/Feature/ThirdPartyLicenseTest.php`
- Modify: `tests/Feature/PrdReferenceIntegrityTest.php` (`DOKUMEN_HIDUP` ditambah `'docs/lisensi-pihak-ketiga.md'`)
- Test: `tests/Feature/ThirdPartyLicenseTest.php`

**Interfaces:**
- Consumes: `composer.json` `require`, `package.json` `dependencies`.
- Produces: jalur lisensi `resources/js/components/ui/LICENSE-phosphor.txt` (W1 yang memindahkan registri ikon wajib memindahkan rujukannya); `docs/lisensi-pihak-ketiga.md` (ditautkan indeks di Tugas 8).

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction ThirdPartyLicenseTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Karya pihak ketiga yang ikut dalam aplikasi membawa lisensinya.
 *
 * Path ikon Phosphor ditanam langsung di kode, bukan dipasang sebagai paket, sehingga
 * pemberitahuan hak cipta MIT tidak datang dengan sendirinya dari node_modules. Daftar
 * lisensi pihak ketiga juga harus mengikuti setiap dependensi runtime yang ditambahkan,
 * karena repositori ini publik.
 */
class ThirdPartyLicenseTest extends TestCase
{
    private const LISENSI_PHOSPHOR = 'resources/js/components/ui/LICENSE-phosphor.txt';

    public function test_the_phosphor_licence_sits_next_to_the_icon_registry(): void
    {
        $lisensi = File::get(base_path(self::LISENSI_PHOSPHOR));

        $this->assertStringStartsWith('MIT License', trim($lisensi));
        $this->assertStringContainsString('Copyright (c) 2023 Phosphor Icons', $lisensi);
        $this->assertStringContainsString('The above copyright notice and this permission notice shall be included', $lisensi);
    }

    public function test_every_file_that_embeds_phosphor_paths_points_to_the_licence(): void
    {
        $tanpaRujukan = [];

        foreach ([resource_path('js'), resource_path('views')] as $akar) {
            foreach (File::allFiles($akar) as $berkas) {
                if (! in_array($berkas->getExtension(), ['tsx', 'ts', 'php'], true)) {
                    continue;
                }

                $isi = $berkas->getContents();

                if (stripos($isi, 'phosphor') !== false && ! str_contains($isi, self::LISENSI_PHOSPHOR)) {
                    $tanpaRujukan[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $tanpaRujukan, 'Berkas memuat ikon Phosphor tanpa menunjuk teks lisensinya: '.implode(', ', $tanpaRujukan));
    }

    public function test_the_register_lists_every_runtime_dependency_and_embedded_work(): void
    {
        $daftar = File::get(base_path('docs/lisensi-pihak-ketiga.md'));
        $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $npm = json_decode(File::get(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

        $paket = array_merge(
            array_filter(array_keys($composer['require']), fn (string $nama): bool => $nama !== 'php' && ! str_starts_with($nama, 'ext-')),
            array_keys($npm['dependencies'])
        );

        $hilang = array_values(array_filter($paket, fn (string $nama): bool => ! str_contains($daftar, "`{$nama}`")));

        $this->assertSame([], $hilang, 'Dependensi runtime tanpa catatan lisensi: '.implode(', ', $hilang));

        foreach (['Phosphor Icons', 'Plus Jakarta Sans', 'OpenStreetMap', 'BMKG', self::LISENSI_PHOSPHOR] as $karya) {
            $this->assertStringContainsString($karya, $daftar);
        }
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=ThirdPartyLicenseTest`
Expected: FAIL. Berkas lisensi tidak ada; `components/ui/Icon.tsx` dan `components/ui/icon.blade.php` tercantum tanpa rujukan; `docs/lisensi-pihak-ketiga.md` tidak ada.

- [ ] **Step 3: Unduh teks lisensi asli.**

```bash
cd "E:/praktik_aplikasi_web" && curl -fsSL https://raw.githubusercontent.com/phosphor-icons/core/main/LICENSE -o resources/js/components/ui/LICENSE-phosphor.txt && head -3 resources/js/components/ui/LICENSE-phosphor.txt
```

Expected: `MIT License`, baris kosong, `Copyright (c) 2023 Phosphor Icons`. Bila jaringan tidak tersedia, tulis berkas itu dengan isi ini (teks MIT yang sama, diperiksa dari sumber yang sama pada 26 September 2026):

```text
MIT License

Copyright (c) 2023 Phosphor Icons

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

- [ ] **Step 4: Tunjuk lisensi dari kedua registri ikon.**

`resources/js/components/ui/Icon.tsx`, tambahkan sebagai baris pertama berkas (komentar `/*!` dipertahankan minifier sebagai komentar hukum bila didukung):

```tsx
/*! Ikon Phosphor (phosphor-icons/core), MIT License, Copyright (c) 2023 Phosphor Icons. Teks lisensi: resources/js/components/ui/LICENSE-phosphor.txt */
```

`resources/views/components/ui/icon.blade.php`, di komentar `@php`, sesudah baris ` * Ikon Phosphor berat regular, ditanam sebagai path.` tambahkan baris:

```php
     * Lisensi: MIT, Copyright (c) 2023 Phosphor Icons; teks lengkap di resources/js/components/ui/LICENSE-phosphor.txt.
```

- [ ] **Step 5: Tulis `docs/lisensi-pihak-ketiga.md`.**

```markdown
# Lisensi Pihak Ketiga

Karya pihak ketiga yang ikut dalam aplikasi atau repo, lisensinya, dan tempat teks
lisensinya. Diperbarui setiap kali dependensi runtime, huruf, ikon, data, atau aset
ditambah; penambahan dependensi sendiri butuh izin pemilik (DK-24). Lisensi paket diambil
dari metadata paket yang terpasang (`composer licenses --no-dev` dan bidang `license` di
package.json setiap paket), bukan dari ingatan. `ThirdPartyLicenseTest` memastikan setiap
dependensi runtime tercatat di sini.

## Aset yang ditanam di kode

| Karya | Dipakai di | Lisensi | Teks lisensi |
|---|---|---|---|
| Phosphor Icons (path bobot regular dan fill, repositori phosphor-icons/core) | `resources/js/components/ui/Icon.tsx`, `resources/views/components/ui/icon.blade.php` | MIT, Copyright (c) 2023 Phosphor Icons | `resources/js/components/ui/LICENSE-phosphor.txt` |

## Huruf

| Karya | Dipakai di | Lisensi | Catatan |
|---|---|---|---|
| Plus Jakarta Sans | Dimuat dari Bunny Fonts di `resources/views/components/brand/head.blade.php`; dihosting sendiri mulai W1 (DK-38) | SIL Open Font License 1.1 | Teks OFL wajib ikut bersama berkas huruf saat dihosting sendiri |
| Newsreader | Dimuat dari Bunny Fonts; dilepas di W1 (DK-38) | SIL Open Font License 1.1 | |

## Data dan layanan

| Sumber | Dipakai untuk | Ketentuan | Tempat atribusi |
|---|---|---|---|
| OpenStreetMap (data di balik ubin peta) | Peta dasar | ODbL 1.0; atribusi "© OpenStreetMap contributors" wajib tampil | `config/hiking.php` kunci `map.attribution`, ditampilkan `WadahPeta` |
| OpenTopoMap (ubin peta bawaan) | Peta dasar online | CC-BY-SA menurut atribusi bawaan di `config/hiking.php`; pemakaian luring butuh izin tertulis (spec v3 Q8) | Sama |
| BMKG (prakiraan cuaca) | Prakiraan area sekitar jalur | Atribusi BMKG wajib tampil (`docs/ARCHITECTURE.md`, "Sumber data eksternal") | Halaman yang menampilkan prakiraan |

## Dependensi runtime PHP (`composer.json`, bagian require)

| Paket | Versi terpasang 26 September 2026 | Lisensi |
|---|---|---|
| `inertiajs/inertia-laravel` | 3.3.4 | MIT |
| `laravel/framework` | 13.31.0 | MIT |
| `laravel/tinker` | 3.0.2 | MIT |
| `laravel/wayfinder` | 0.1.21 | MIT |
| `league/flysystem-aws-s3-v3` | 3.35.3 | MIT |
| `livewire/livewire` | 3.8.9 | MIT; antarmuka Livewire sudah dipensiunkan, paketnya menunggu keputusan pemilik (P11) |
| `livewire/volt` | 1.11.2 | MIT; sama |

Daftar transitif lengkap: `composer licenses --no-dev`.

## Dependensi runtime JavaScript (`package.json`, bagian dependencies)

| Paket | Versi terpasang 26 September 2026 | Lisensi |
|---|---|---|
| `@inertiajs/react` | 3.7.1 | MIT |
| `@inertiajs/vite` | 3.7.1 | MIT |
| `maplibre-gl` | 4.7.1 | BSD-3-Clause |
| `react` | 19.3.0 | MIT |
| `react-dom` | 19.3.0 | MIT |

Lisensi paket transitif ada di bidang `license` pada package.json masing-masing paket di
`node_modules/`.
```

Tambahkan `'docs/lisensi-pihak-ketiga.md',` ke `PrdReferenceIntegrityTest::DOKUMEN_HIDUP`.

- [ ] **Step 6: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='ThirdPartyLicenseTest|IconSystemTest|DesignDirectionTest|PrdReferenceIntegrityTest'`
Expected: PASS semua (`IconSystemTest` menjaga registri ikon tetap bekerja; `DesignDirectionTest` menjaga komentar baru tanpa em dash).

- [ ] **Step 7: Periksa apakah pemberitahuan ikut ke bundel produksi.** Run: `npm run build && grep -l "Phosphor Icons" public/build/assets/*.js`. Catat hasilnya di rekap sebagai FAKTA: bila tidak ada berkas yang tercetak, minifier membuang komentar hukum; statusnya PERLU KLARIFIKASI dan menjadi masukan W1 yang menulis ulang registri ikon (DK-39).

- [ ] **Step 8: Format.** Run: `vendor/bin/pint --format agent tests/Feature/ThirdPartyLicenseTest.php tests/Feature/PrdReferenceIntegrityTest.php resources/views/components/ui/icon.blade.php`.

- [ ] **Step 9: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add resources/js/components/ui/LICENSE-phosphor.txt docs/lisensi-pihak-ketiga.md tests/Feature/ThirdPartyLicenseTest.php resources/js/components/ui/Icon.tsx
git commit -m "docs: lisensi MIT Phosphor dan daftar lisensi pihak ketiga" -- resources/js/components/ui/LICENSE-phosphor.txt resources/js/components/ui/Icon.tsx resources/views/components/ui/icon.blade.php docs/lisensi-pihak-ketiga.md tests/Feature/ThirdPartyLicenseTest.php tests/Feature/PrdReferenceIntegrityTest.php
```

---

### Task 8: Dokumen tim di `docs/` (kesepakatan kerja, indeks, penunjuk)

Tugas ini ditulis terakhir di antara tugas dokumen supaya indeks dan kesepakatan kerja hanya menaut ke berkas yang
sudah ada. Tugas ini menyunting `CLAUDE.md`: dikerjakan koordinator.

**Files:**
- Create: `docs/AGENTS.md`, `docs/README.md`, `docs/DESIGN.md`, `docs/PRODUCT.md`
- Create: `tests/Feature/TeamDocsIndexTest.php`
- Modify: `AGENTS.md` (bagian Memount di baris paling atas, di luar blok alat)
- Modify: `CLAUDE.md` bagian 9 (satu baris tabel)
- Modify: `docs/ARCHITECTURE.md` (bagian baru "Struktur repo dan panduan luar" sesudah "Aturan yang tidak boleh dilanggar")
- Modify: `README.md` (bagian "Peta dokumen" dan "Untuk agen AI"), `CONTRIBUTING.md` (paragraf pembuka), `.github/PULL_REQUEST_TEMPLATE.md` (satu butir)
- Modify: `tests/Feature/PrdReferenceIntegrityTest.php` (`DOKUMEN_HIDUP` ditambah `'docs/AGENTS.md'`, `'docs/README.md'`, `'docs/DESIGN.md'`, `'docs/PRODUCT.md'`)
- Test: `tests/Feature/TeamDocsIndexTest.php`

**Interfaces:**
- Consumes: semua berkas Tugas 1 sampai 7; frasa urutan wewenang dari Tugas 6; peta kepemilikan `docs/PRD.md` dari Tugas 1.
- Produces: `docs/AGENTS.md` dengan heading `TeamDocsIndexTest::HEADING_WAJIB`; indeks `docs/README.md` yang wajib diperbarui setiap kali dokumen baru ditambah di `docs/`.

- [ ] **Step 1: Tulis test yang gagal.** Run: `php artisan make:test --phpunit --no-interaction TeamDocsIndexTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Dokumen tim di docs/ dapat dipercaya sebagai pintu masuk.
 *
 * Arahan pemilik 26 September 2026 meminta folder docs yang menyatukan visi, misi,
 * ekspektasi, dan kesepakatan kerja untuk rekan tim (spec v3 bagian 17, DK-52, DK-53).
 * Indeks yang menaut ke berkas yang tidak ada, kesepakatan yang kehilangan bagian
 * wajibnya, atau kutipan visi yang diam-diam berbeda dari PRD menyesatkan justru pada
 * saat seseorang baru bergabung.
 */
class TeamDocsIndexTest extends TestCase
{
    private const HEADING_WAJIB = [
        '## Cara memakai dokumen ini',
        '## Visi, misi, dan ekspektasi pemilik',
        '## Urutan prioritas',
        '## Kesepakatan kerja dan izin',
        '## Alur kerja: riset lebih dulu',
        '## Skill: kapan dipakai dan urutan wewenang',
        '## Tingkat bukti',
        '## Cara audit',
        '## Definisi selesai',
        '## Kriteria mutu',
        '## Lima prinsip rekayasa',
        '## Format rekap',
        '## Protokol tahan-sesi',
        '## Konvensi bahasa dan commit',
    ];

    /**
     * Dokumen di luar docs/ yang wajib tertaut dari indeks, relatif terhadap docs/.
     */
    private const DOKUMEN_LUAR_DI_INDEKS = [
        '../README.md', '../CONTRIBUTING.md', '../SECURITY.md', '../CLAUDE.md', '../AGENTS.md',
        '../HANDOVER.md', '../DESIGN.md', '../PRODUCT.md', '../.claude/rules/mountain-core.md',
        '../database/README.md', '../.github/PULL_REQUEST_TEMPLATE.md',
    ];

    /**
     * Skill yang wajib dijelaskan kesepakatan kerja (arahan pemilik 26 September 2026, DK-57).
     */
    private const SKILL_WAJIB = [
        'ui-ux-pro-max', 'design-taste-frontend', 'impeccable', 'antislop', 'antislop-ui',
        'antislop-layoutmobile', 'antislop-copywriting', 'antislop-code', 'antislop-human',
        'apple-design', 'emil-design-eng', 'laravel-best-practices', 'testing-best-practices',
        'tailwindcss-development',
    ];

    private const URUTAN_WEWENANG = [
        '1. **Arahan pemilik dan aturan kanonis**',
        '2. **`DESIGN.md`**',
        '3. **Saringan antislop**',
        '4. **Saran skill**',
    ];

    private const DOKUMEN_BARU = ['docs/AGENTS.md', 'docs/README.md', 'docs/DESIGN.md', 'docs/PRODUCT.md'];

    public function test_the_docs_index_links_only_to_files_that_exist(): void
    {
        $hilang = array_values(array_filter(
            $this->tautanIndeks(),
            fn (string $target): bool => ! File::exists(base_path('docs/'.$target))
        ));

        $this->assertSame([], $hilang, 'docs/README.md menaut ke yang tidak ada: '.implode(', ', $hilang));
    }

    public function test_every_team_document_is_listed_in_the_index(): void
    {
        $tautan = $this->tautanIndeks();
        $belum = [];

        foreach (File::files(base_path('docs')) as $berkas) {
            if ($berkas->getExtension() === 'md' && $berkas->getFilename() !== 'README.md' && ! in_array($berkas->getFilename(), $tautan, true)) {
                $belum[] = 'docs/'.$berkas->getFilename();
            }
        }

        foreach (File::directories(base_path('docs')) as $folder) {
            $nama = basename($folder);
            $tertaut = array_filter($tautan, fn (string $target): bool => str_starts_with($target, $nama.'/'));

            if ($tertaut === []) {
                $belum[] = "docs/{$nama}/";
            }
        }

        foreach (self::DOKUMEN_LUAR_DI_INDEKS as $luar) {
            if (! in_array($luar, $tautan, true)) {
                $belum[] = $luar;
            }
        }

        $this->assertSame([], $belum, 'Belum terdaftar di docs/README.md: '.implode(', ', $belum));
    }

    public function test_the_working_agreement_has_every_required_section_in_order(): void
    {
        preg_match_all('/^## .+$/m', $this->kesepakatan(), $cocok);

        $this->assertSame(self::HEADING_WAJIB, array_values(array_intersect(array_map('trim', $cocok[0]), self::HEADING_WAJIB)));
    }

    public function test_the_vision_mission_priorities_and_owner_words_match_the_prd(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        $kesepakatan = $this->kesepakatan();

        preg_match('/^## §4 Visi produk\R+> (.+)$/mu', $prd, $visi);
        $this->assertNotEmpty($visi, 'Kutipan visi tidak ditemukan di PRD §4.');
        $this->assertStringContainsString($visi[1], $kesepakatan, 'Visi di docs/AGENTS.md berbeda dari PRD §4.');

        preg_match('/^## §5 Misi produk\R+((?:\d+\. .+\R)+)/mu', $prd, $misi);
        $this->assertNotEmpty($misi, 'Daftar misi tidak ditemukan di PRD §5.');

        foreach (preg_split('/\R/', trim($misi[1])) as $butir) {
            $this->assertStringContainsString($butir, $kesepakatan, 'Misi berbeda dari PRD §5: '.$butir);
        }

        preg_match('/\*\*Urutan prioritas yang mengikat\*\*[^\n]*\R\R((?:\d+\. .+\R)+)/u', $prd, $prioritas);
        $this->assertNotEmpty($prioritas, 'Urutan prioritas tidak ditemukan di PRD §160.');

        $bagianPrioritas = $this->bagian($kesepakatan, '## Urutan prioritas');
        $sebelumnya = -1;

        foreach (preg_split('/\R/', trim($prioritas[1])) as $butir) {
            $teks = rtrim((string) preg_replace('/^\d+\. /', '', $butir), ';.');
            $posisi = mb_strpos($bagianPrioritas, $teks);

            $this->assertNotFalse($posisi, 'Prioritas tidak ada di docs/AGENTS.md: '.$teks);
            $this->assertGreaterThan($sebelumnya, $posisi, 'Urutan prioritas berbeda dari PRD §160 di: '.$teks);

            $sebelumnya = $posisi;
        }

        preg_match('/^## §160 Ekspektasi pemilik$(.*?)^## /msu', $prd, $ekspektasi);
        preg_match_all('/^> "(.+)"$/mu', $ekspektasi[1] ?? '', $kata);

        $this->assertNotEmpty($kata[1], 'Kata-kata pemilik tidak ditemukan di PRD §160.');

        foreach ($kata[1] as $kutipan) {
            $this->assertStringContainsString($kutipan, $kesepakatan, 'Kata-kata pemilik di PRD §160 tidak dikutip utuh: '.$kutipan);
        }
    }

    public function test_the_agreement_names_every_required_skill_and_the_dk57_order(): void
    {
        $bagianSkill = $this->bagian($this->kesepakatan(), '## Skill: kapan dipakai dan urutan wewenang');

        foreach (self::SKILL_WAJIB as $skill) {
            $this->assertStringContainsString("`{$skill}`", $bagianSkill, "Skill {$skill} tidak dijelaskan di docs/AGENTS.md.");
        }

        $sebelumnya = -1;

        foreach (self::URUTAN_WEWENANG as $tingkat) {
            $posisi = strpos($bagianSkill, $tingkat);

            $this->assertNotFalse($posisi, "Tingkat wewenang tidak ada: {$tingkat}");
            $this->assertGreaterThan($sebelumnya, $posisi, "Urutan wewenang DK-57 terbalik di {$tingkat}.");

            $sebelumnya = $posisi;
        }
    }

    public function test_design_and_product_in_docs_are_pointers_not_copies(): void
    {
        foreach (['DESIGN.md', 'PRODUCT.md'] as $nama) {
            $penunjuk = File::get(base_path('docs/'.$nama));

            $this->assertStringContainsString("](../{$nama})", $penunjuk, "docs/{$nama} harus menaut ke {$nama} di root.");
            $this->assertDoesNotMatchRegularExpression('/^##/m', $penunjuk, "docs/{$nama} adalah penunjuk satu paragraf, bukan salinan bersubbagian.");
            $this->assertLessThanOrEqual(12, count(preg_split('/\R/', trim($penunjuk))), "docs/{$nama} terlalu panjang untuk penunjuk.");
        }
    }

    public function test_the_boost_block_is_untouched_and_the_memount_section_sits_outside_it(): void
    {
        $agents = File::get(base_path('AGENTS.md'));
        $claude = File::get(base_path('CLAUDE.md'));

        $this->assertSame(1, substr_count($agents, '<laravel-boost-guidelines>'), 'AGENTS.md harus memuat tag pembuka blok Boost tepat sekali.');
        $this->assertSame(1, substr_count($claude, '<laravel-boost-guidelines>'), 'CLAUDE.md harus memuat tag pembuka blok Boost tepat sekali.');

        preg_match('/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s', $agents, $blokAgents, PREG_OFFSET_CAPTURE);
        preg_match('/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s', $claude, $blokClaude);

        $this->assertSame($blokClaude[0], $blokAgents[0][0], 'Blok Boost di AGENTS.md dan CLAUDE.md berbeda: salah satunya disunting tangan.');

        $mulai = strpos($agents, '<!-- memount:mulai -->');
        $selesai = strpos($agents, '<!-- memount:selesai -->');

        $this->assertNotFalse($mulai, 'Bagian Memount tidak ditemukan di AGENTS.md.');
        $this->assertNotFalse($selesai, 'Penutup bagian Memount tidak ditemukan di AGENTS.md.');
        $this->assertLessThan($blokAgents[0][1], $selesai, 'Bagian Memount harus berada di atas blok Boost, di luarnya.');

        $ngoding = strpos($agents, '<!-- ngodingpakeai:skill:start -->');

        if ($ngoding !== false) {
            $this->assertLessThan($ngoding, $selesai, 'Bagian Memount tidak boleh masuk blok NgodingPakeAI.');
        }

        $this->assertStringContainsString('`docs/AGENTS.md`', substr($agents, $mulai, $selesai - $mulai));
    }

    public function test_the_prd_ownership_map_points_at_files_that_exist(): void
    {
        preg_match('/^### Peta kepemilikan$(.*?)^---$/msu', File::get(base_path('docs/PRD.md')), $peta);

        $this->assertNotEmpty($peta, 'Peta kepemilikan tidak ditemukan di docs/PRD.md.');

        preg_match_all('/`([^`]+)`/u', $peta[1], $jalur);

        $hilang = array_values(array_filter(
            array_unique($jalur[1]),
            fn (string $satu): bool => ! File::exists(base_path(rtrim($satu, '/')))
        ));

        $this->assertSame([], $hilang, 'Peta kepemilikan menunjuk yang tidak ada: '.implode(', ', $hilang));
        $this->assertStringContainsString('`docs/AGENTS.md`', $peta[1]);
        $this->assertStringContainsString('`docs/README.md`', $peta[1]);
    }

    public function test_the_architecture_records_why_the_repo_stays_a_modular_monolith(): void
    {
        $struktur = $this->bagian(File::get(base_path('docs/ARCHITECTURE.md')), '## Struktur repo dan panduan luar');

        $this->assertStringContainsString('DK-51', $struktur);
        $this->assertStringContainsString('Kriteria bukti kebutuhan', $struktur);
        $this->assertStringContainsString('`apps/backend/src', $struktur);
    }

    public function test_new_team_documents_carry_no_em_dash(): void
    {
        foreach (self::DOKUMEN_BARU as $berkas) {
            $this->assertStringNotContainsString("\u{2014}", File::get(base_path($berkas)), "{$berkas} memuat em dash.");
        }

        $agents = File::get(base_path('AGENTS.md'));
        $mulai = (int) strpos($agents, '<!-- memount:mulai -->');
        $selesai = (int) strpos($agents, '<!-- memount:selesai -->');

        $this->assertStringNotContainsString("\u{2014}", substr($agents, $mulai, $selesai - $mulai), 'Bagian Memount di AGENTS.md memuat em dash.');
        $this->assertStringNotContainsString("\u{2014}", $this->bagian(File::get(base_path('docs/ARCHITECTURE.md')), '## Struktur repo dan panduan luar'), 'Bagian struktur repo memuat em dash.');
    }

    /**
     * Target tautan Markdown di docs/README.md, relatif terhadap docs/, tanpa jangkar.
     *
     * @return array<int, string>
     */
    private function tautanIndeks(): array
    {
        preg_match_all('/\]\(([^)\s]+)\)/', File::get(base_path('docs/README.md')), $cocok);

        $target = array_map(fn (string $tautan): string => explode('#', $tautan, 2)[0], $cocok[1]);

        return array_values(array_unique(array_filter(
            $target,
            fn (string $tautan): bool => $tautan !== '' && ! preg_match('#^(https?:|mailto:)#', $tautan)
        )));
    }

    private function kesepakatan(): string
    {
        return File::get(base_path('docs/AGENTS.md'));
    }

    /**
     * Isi satu bagian bertingkat dua, dari heading-nya sampai heading bertingkat dua berikutnya.
     */
    private function bagian(string $isi, string $heading): string
    {
        $awal = strpos($isi, $heading."\n");

        $this->assertNotFalse($awal, "Bagian \"{$heading}\" tidak ditemukan.");

        $akhir = strpos($isi, "\n## ", $awal + strlen($heading));

        return $akhir === false ? substr($isi, $awal) : substr($isi, $awal, $akhir - $awal);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=TeamDocsIndexTest`
Expected: FAIL. "File does not exist" untuk `docs/README.md`, `docs/AGENTS.md`, `docs/DESIGN.md`, `docs/PRODUCT.md`; bagian Memount tidak ditemukan; bagian struktur repo tidak ditemukan.

- [ ] **Step 3: Tulis `docs/AGENTS.md`.** Kutipan ditulis persis seperti sumbernya (visi, misi, prioritas, dan kata-kata pemilik dari `docs/PRD.md` §4, §5, §160; arahan 26 September dari spec v3 bagian 17.1; landasan riset dari arahan pemilik 23 September 2026 yang tercatat di memori agen dan dirangkum PRD §161).

````markdown
# Kesepakatan Kerja Tim Memount

Dokumen ini dibaca setiap anggota tim, manusia maupun agen AI, sebelum mengubah apa pun di
repo. Isinya cara kita bekerja: apa yang dituju pemilik, apa yang butuh izin, bagaimana kita
riset, menguji, mengaudit, dan melapor. Aturan di sini dirangkum dari pemiliknya dan
ditautkan; bila rangkuman ini berbeda dari pemiliknya, pemiliknya yang berlaku. Kutipan
visi, misi, urutan prioritas, dan kata-kata pemilik dijaga identik dengan `docs/PRD.md` oleh
`TeamDocsIndexTest`.

Disusun atas arahan pemilik 26 September 2026 (spec v3 bagian 17.1), keputusan DK-53.

## Cara memakai dokumen ini

Urutan baca untuk sesi atau anggota baru:

1. `CLAUDE.md`: kontrak operasi yang berlaku di setiap sesi.
2. `HANDOVER.md` bagian 0: keadaan terkini, arahan terakhir pemilik, dan prompt untuk melanjutkan.
3. Dokumen ini.
4. `docs/PRD.md` sebelum perubahan produk, domain, atau UX; `DESIGN.md` sebelum pekerjaan antarmuka.

Indeks semua dokumen ada di `docs/README.md`. Setiap aturan punya satu pemilik:

| Aturan | Pemilik |
|---|---|
| R-001 sampai R-014 dan R-022 (domain) | `.claude/rules/mountain-core.md` |
| R-015 sampai R-017 (desain) | `DESIGN.md` |
| R-018 sampai R-021, R-023, R-024 (operasi) | `CLAUDE.md` |
| BR-01 sampai BR-16 (aturan bisnis) | `docs/PRD.md` §132 |
| Data pribadi, penghapusan akun, retensi | `docs/KEBIJAKAN-DATA-PRIBADI.md` |
| Lapisan kode dan aturan arsitektur | `docs/ARCHITECTURE.md` |
| Skema dan migrasi | `database/README.md` |
| Klasifikasi skill | `docs/MATRIKS-SKILL.md` |
| Pelaporan kerentanan dan rahasia | `SECURITY.md` |

## Visi, misi, dan ekspektasi pemilik

**Pernyataan merek pemilik** (verbatim, `docs/PRD.md` §1):

> "MEMOUNT adalah platform perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit, preparation, readiness, field experience, trust, community, dan progression dalam satu perjalanan utuh. MEMOUNT membantu saya menemukan perjalanan gunung yang cocok untuk saya."

**Visi** (§4):

> Membantu pendaki mengambil keputusan pendakian yang lebih terinformasi dengan menghubungkan profil pendaki, karakteristik jalur, rencana perjalanan, dan kondisi terbaru dalam satu alur yang dapat dijelaskan.

**Misi** (§5):

1. Mempermudah pemilihan jalur berdasarkan konteks pengguna.
2. Memberikan rekomendasi yang dapat dijelaskan.
3. Mengubah rekomendasi menjadi rencana persiapan.
4. Membedakan informasi resmi, terverifikasi, komunitas, dan interpretasi sistem.
5. Menyediakan informasi penting menjelang dan selama pendakian.
6. Membentuk lingkaran umpan balik lewat laporan kondisi jalur.

**North star** (§6): membantu pengguna berpindah dari "ingin mendaki" menjadi "memahami jalur
yang dipilih, persiapan yang dibutuhkan, kondisi yang perlu diperiksa, dan hasil perjalanan."

**Ekspektasi pemilik** (verbatim, §160):

> "aplikasi web gunung yang memadukan antara aplikasi web atau aplikasi yang popular dan sudah banyak user nya dengan strava dan traveloka, bagaimana user riset di masing-masing aplikasi nya dengan itu saya ingin mengcompare ke 3 itu di padukan dan menerapkan pada aplikasi gunung."

> "saya mengutamakan untuk pertama imk dan ui/ux"

> "impact dari interaksi manusia komputer, ui, dan ux di semua refrensi yang saya cantumkan dari hasil riset, tujuan, dan kesepakatan diatas itu sama dengan aplikasi web yang akan kita bangun."

> "yang saya lihat masih sama Penilaian "masih CRUD sederhana". yang saya rasakan hanya update fitur crud itupun sedikit dan tidak semua fitur nya tidak dapat berfungsi dengan baik."

Artinya: bukan menyerupai rupa rujukan, melainkan mencapai dampak IMK, UI, dan UX yang setara
dengan AllTrails, Strava, dan Traveloka. Halaman yang hanya menampilkan dan mengubah baris data
tanpa menjawab satu pun dari lima pertanyaan pendaki (jalur mana yang sebaiknya didaki, mengapa
jalur itu cocok, bagaimana merencanakan dan bersiap, apa status jalur terkini, bagaimana
melihat perkembangan diri) belum memenuhi ekspektasi ini (§160, "Tesis"). Tujuh dampak
perilaku yang dituju (confidence, efficiency, continuity, clarity, context, exploration,
focus) tercantum di §160 dan berstatus hipotesis sampai divalidasi (§113).

**Arahan yang melahirkan dokumen ini** (verbatim, 26 September 2026, spec v3 bagian 17.1):

> "[...] tambahkan folder docs berupa design md, product md, prd md, dan agent md sebagai aturan untuk rekan satu team lainnya setelah di repo standart disini agar visi, misi, ekspektasi, dan kesepakatan nya sama serta kriteria" pengerjaan lainnya seperti selalu riset detail dan mendalam melalui analisis brainstorming lalu cara audit nya dan kriteria" lainnya yang sudah menjadi kesepakatan disini pastikan detail dan lengkap. [...]"

## Urutan prioritas

Ketika dua kebutuhan bertabrakan, yang lebih atas menang (§160):

1. IMK/HCI;
2. UI/UX;
3. logika produk dan arsitektur informasi;
4. arsitektur dan skalabilitas;
5. keamanan, privasi, kepatuhan;
6. keandalan dan penanganan kegagalan;
7. kesiapan operasional dan observability;
8. manajemen dan konsistensi data;
9. pemasaran, SEO, pertumbuhan.

Urutan ini mengatur prioritas kerja, bukan izin melanggar aturan: aturan kanonis R-001 sampai
R-024 dan BR-01 sampai BR-16 tetap berlaku pada setiap tingkat.

## Kesepakatan kerja dan izin

| Tindakan | Aturannya | Pemilik aturan |
|---|---|---|
| Commit | Izin pemilik untuk setiap commit sampai pemilik mencabut arahan ini. `CLAUDE.md` R-018 membolehkan commit lokal, tetapi arahan pemilik terbaru lebih ketat, dan yang lebih ketat berlaku | §161, `HANDOVER.md` bagian 0 |
| Push, merge, tag, rilis, force push, menulis ulang riwayat | Persetujuan eksplisit pemilik. Sebelum push: test, lint dan format, tinjau diff, pindai rahasia, verifikasi migrasi | R-018 |
| Akses Supabase, termasuk membaca kuncinya | Konfirmasi pemilik lebih dulu; lingkungan dan operasi disebut; rahasia tidak pernah dicetak | R-019, `HANDOVER.md` bagian 3 |
| Kredensial | Boleh dipakai, tidak pernah ditulis ke skrip, kode, test, dokumen, atau pesan commit, dan tidak ditampilkan di terminal; tempatnya hanya `.env` | R-019, `SECURITY.md` |
| Migrasi di lingkungan pemilik | Izin per migrasi; diuji di SQLite dan PostgreSQL CI lebih dulu; statusnya diverifikasi sesudahnya. `.claude/settings.json` menolak `php artisan migrate` bagi agen | R-019, R-024, DK-23, `database/README.md` |
| Menambah atau melepas dependensi, runtime maupun dev | Izin pemilik | DK-24, spec v3 Q3 dan Q17 |
| Mengubah versi kerangka | Izin pemilik; verifikasi `php -v`, `composer -V`, `php artisan --version`, dan `composer show laravel/framework` sebelum perubahan aplikasi | R-020 |
| Mengubah arah produk: Route Fit, batas MVP dan MVP+, navigasi, hierarki otoritas, privasi atau penghapusan, arsitektur basis data, semantik penyedia luar; menambah fitur besar, monetisasi, atau kompetisi sosial | Usulan yang menyebut aturan sekarang, alasan, bukti, alternatif, dampak, risiko, rekomendasi, dan kebutuhan persetujuan; lalu governance record | R-021, §130, §146 |
| Operasi destruktif di produksi | Persetujuan tepat sebelum eksekusi | R-019 |
| Memasang atau memperbarui skill pihak ketiga | Hasil pemindai keamanan dan isi SKILL.md dibaca lebih dulu | DK-57 |
| `npx ngodingpakeai plan get` | Tidak dijalankan di root: perintah itu menulis berkas PRD versi server dan `features/` | GR-04 |

**Kesepakatan kerja lain** (`HANDOVER.md` bagian 3, §161, dan memori kesepakatan pemilik):

- **Riset mendalam dan brainstorming sebelum memutuskan, bukan menebak.** Landasan kerja pemilik (23 September 2026, verbatim): "saya ingin anda selalu riset detail dan mendalam di semua aspek dan kriteria untuk mengembangkan web app ini yang disesuaikan/kompatibel dengan tools atau framework yang kita gunakan secara maksimal. Pastikan untuk selalu mencari, mendapat, menerapkan informasi terkini, terbaru, sehingga sesuai/kompatibel dengan user sekarang." Sumber primer terkini (dokumentasi resmi, W3C, OWASP, NIST, regulasi Indonesia) dicocokkan dengan versi yang benar-benar terpasang (`composer show`, `package.json`), bukan klaim blog.
- **Keputusan delegasi.** Bila pemilik menjawab pertanyaan keputusan dengan "riset" atau kalimat landasan kerja di atas, keputusan diambil dari riset terkini yang bersumber, lalu dicatat "diputuskan atas delegasi pemilik" beserta sumbernya supaya dapat dibatalkan. Delegasi tidak mencakup push, akses Supabase, atau operasi destruktif.
- **Uji dan periksa silang, buktikan sebelum melapor.** Temuan dibuktikan dengan pengukuran (test, hitungan kueri, kueri basis data, rasio kontras yang dihitung) sebelum disebut masalah. Hal yang dicurigai lalu terbukti bukan masalah ikut dilaporkan, supaya tidak menjadi temuan palsu.
- **Semua skill yang terpasang dinilai relevansinya** (inti, pendukung, lintas, tidak relevan) sebelum bekerja, dan yang relevan diterapkan (`CLAUDE.md` bagian 7, `docs/MATRIKS-SKILL.md`).
- **Setiap tugas diakhiri evaluasi, rencana berikutnya, dan rekap berpersentase** (format di bawah).
- **Belajar di setiap tugas.** Pelajaran lintas sesi yang tidak ada di repo disimpan di memori agen; yang menyangkut tim ditulis ke dokumen pemiliknya.
- **Lima prinsip rekayasa** dijawab untuk setiap perubahan (bagian di bawah).
- **Bekerja dengan NgodingPakeAI.** Satu tugas per putaran lewat `task next`; setiap tugas dipetakan ke kode yang sudah ada dan memakai backend nyata, bukan tiruan; berhenti dan lapor saat `layer` atau `phase.current` berubah; sinkron ringkasan akhir (`sync` tanpa `--plan`) hanya setelah seluruh tugas selesai.

## Alur kerja: riset lebih dulu

1. **Pahami dan riset.** Skill `superpowers:brainstorming` mengubah arahan menjadi pemahaman bersama lewat pertanyaan satu per satu; riset memakai `ecc:deep-research` atau `ecc:search-first` dengan sumber primer. Keputusan yang milik pemilik (R-021) diajukan sebagai pertanyaan dengan pilihan dan rekomendasi, bukan diputuskan diam-diam.
2. **Spec.** Ditulis di `docs/superpowers/specs/` dengan nama berawalan tanggal: arahan pemilik verbatim, temuan berstatus FAKTA dengan bukti, keputusan ber-ID (DK), izin yang dibutuhkan (Q), risiko, dan cara verifikasi. Pemilik menyetujui spec sebelum rencana ditulis.
3. **Rencana.** `superpowers:writing-plans` menulis rencana di `docs/superpowers/plans/`: tugas kecil, test lebih dulu, berkas milik setiap tugas, fokus tinjauan. Pemilik memilih cara eksekusi (subagen atau langsung).
4. **Eksekusi dengan TDD.** `superpowers:test-driven-development`: test ditulis dan dijalankan sampai gagal, implementasi paling sederhana, test lulus. Eksekusi lewat `superpowers:subagent-driven-development` atau `superpowers:executing-plans`. Subagen hanya menyunting berkas miliknya; berkas bersama dipegang koordinator (daftar di rencana induk v3).
5. **Verifikasi.** `superpowers:verification-before-completion`: perintah dijalankan dan keluarannya dibaca sebelum klaim apa pun; runtime nyata diperiksa (peramban 390 px dan 1440 px, klik-lewat setiap kontrol); paritas PostgreSQL bila skema tersentuh.
6. **Tinjauan.** `superpowers:requesting-code-review`, lalu periksa silang produk, UX dan IMK, arsitektur, data, keamanan dan privasi, keandalan, performa, aksesibilitas, testing, dan SEO (`CLAUDE.md` bagian 7).
7. **Antislop akhir dan rekap.** Delivery Gate antislop, rekap berpersentase, dan `HANDOVER.md` bagian 0 diperbarui di akhir setiap gelombang.

Setiap cacat ditangani dengan `superpowers:systematic-debugging` (bukti, hipotesis, uji), dan
setiap cacat produksi yang diperbaiki dilindungi test regresi (R-024).

## Skill: kapan dipakai dan urutan wewenang

Urutan wewenang ketika saran skill bertentangan (DK-57):

1. **Arahan pemilik dan aturan kanonis**: kata-kata dan keputusan pemilik, R-001 sampai R-024, BR-01 sampai BR-16.
2. **`DESIGN.md`**: token dan turunannya.
3. **Saringan antislop**: menyaring, tidak menciptakan arah.
4. **Saran skill**: diambil mekanismenya, bukan rupanya.

| Skill | Kapan dipakai | Batasnya |
|---|---|---|
| `antislop` dengan `antislop-ui`, `antislop-layoutmobile`, `antislop-copywriting`, `antislop-code`, `antislop-human` | Selama bekerja dan sekali lagi sebelum menyatakan selesai; Delivery Gate untuk setiap pekerjaan antarmuka dan teks | Saringan, tidak menciptakan arah |
| `impeccable` | Membentuk, mengkritik, mengaudit, dan memoles antarmuka; membaca `PRODUCT.md` dan `DESIGN.md` di root | Arah dari `DESIGN.md`; hasilnya disaring antislop |
| `ui-ux-pro-max` | Pembanding dan pemeriksa: pola UX, daftar periksa aksesibilitas, palet dan pasangan huruf sebagai rujukan | Tidak mengganti DK-38; langkah "Persist Design System" ditulis ke `DESIGN.md`, bukan folder baru |
| `design-taste-frontend` | Setiap pekerjaan antarmuka: perombakan layar yang ada dengan audit lebih dulu, halaman publik (halaman depan, halaman jalur publik), dan pemeriksaan pra-kirim anti-generik | Menurut cakupannya sendiri bukan untuk dasbor, tabel data, dan alur bertahap; di layar itu yang diambil hanya pemeriksaannya. Rekomendasi pustakanya (Motion, GSAP, Tailwind v4) tidak dipakai (DK-24, R-020); resep huruf, warna, dan geraknya kalah oleh `DESIGN.md` |
| `emil-design-eng` | Detail poles dan keputusan gerak | Gerak dial 2 dan daftar tertutup spec v3 bagian 8.6; `prefers-reduced-motion` dihormati |
| `apple-design` | Gestur, pegas, lembar bawah, material | Kaca paling banyak dua elemen per layar, tidak di atas peta, kartu, status resmi, atau laporan komunitas |
| `tailwindcss-development` | Setiap kelas Tailwind 3.4 | Tanpa `dark:` dan warna palet mentah; token dari `resources/css/app.css` |
| `laravel-best-practices` | Setiap perubahan PHP: controller, model, migrasi, request, policy | Konvensi berkas tetangga; API sesuai versi terpasang |
| `testing-best-practices` | Sebelum menulis atau meninjau test | Suite SQLite bukan bukti PostgreSQL (R-024) |

Pada pekerjaan antarmuka, saran `ui-ux-pro-max`, `design-taste-frontend`, `impeccable`, dan
`antislop` dibandingkan lalu digabung dengan urutan di atas; saran yang ditolak ditulis
alasannya di rekap. Matriks lengkap semua skill terpasang, kelasnya, dan status pelacakannya
di git (Q19) ada di `docs/MATRIKS-SKILL.md`.

## Tingkat bukti

Klaim selalu dipisahkan menjadi FACT, DECISION, INFERENCE, HYPOTHESIS, RECOMMENDATION, dan
OPEN ISSUE; bukti yang ambigu berstatus PERLU KLARIFIKASI (R-023). **Tanpa bukti, tidak ada
PASS.** Bukti yang sah: jalur berkas dan rentang baris; nama kelas, service, migrasi, tabel,
atau indeks; nama rute; jalur view; nama test; keluaran runtime; hasil kueri; bukti peramban.
Kode yang ada, tangkapan layar yang rapi, atau suite lokal yang hijau saja bukan bukti
keberhasilan, dan hasil riset pengguna maupun metrik UX tidak pernah dikarang.

Suite SQLite yang hijau tidak membuktikan kebenaran di PostgreSQL dan Supabase (R-024).
Tingkat selesai L0 sampai L4 ada di §161: fitur dinyatakan selesai minimal L3 (diuji dengan
data nyata di PostgreSQL dan PostGIS, integrasi CI lulus), dan permukaan utama menuju L4
(diverifikasi manusia di peramban dengan data realistis).

## Cara audit

- **Audit kelengkapan** (atas permintaan pemilik): `docs/AUDIT-PROTOCOL.md`, checklist A sampai N, standar bukti, dan skor lulus dibagi semua butir yang berlaku. Butir PERLU KLARIFIKASI tidak dihitung lulus; ambang sehat 80%, perlu perbaikan 50% sampai 79%, kritis di bawah 50%; temuan kritis keamanan, privasi, integritas data, atau runtime dilaporkan terpisah dari skor. Kode tidak diubah sebelum rencana audit disetujui.
- **Audit antislop:** skill `antislop` dan Delivery Gate-nya, laporan PASS atau FAIL per butir dengan bukti; laporan bertanggal disimpan di `docs/audit/anti-slop/`.
- **Tinjauan keamanan:** untuk perubahan yang menyentuh input, autentikasi, otorisasi, unggahan, lokasi, atau rahasia, pakai skill `security-review` dan `ecc:laravel-security`. Test keamanan kritis sebelum rilis MVP ada di `CLAUDE.md` bagian 4 (auth bypass, IDOR, eskalasi hak, mass assignment, unggahan, batas laju, kebocoran rahasia, RLS, lokasi privat, penghapusan akun). `.github/workflows/security.yml` menjalankan `composer audit`, `npm audit`, dan `SecretsNotInRepoTest`; pelaporan kerentanan mengikuti `SECURITY.md`.
- **Audit aksesibilitas:** target WCAG 2.2 AA (`CLAUDE.md` bagian 6). `ColourContrastTest` menghitung kontras; pemeriksaan manual mencakup jalur keyboard (Tab, Enter, Escape), fokus terlihat, zoom 200% dan reflow 320 px, `prefers-reduced-motion`, `forced-colors`, dan pembaca layar menurut `docs/PROTOKOL-UJI-KEGUNAAN.md` bagian 4; skill `ecc:accessibility` dan `ecc:frontend-a11y`.
- **Uji kegunaan:** `docs/PROTOKOL-UJI-KEGUNAAN.md`, yaitu evaluasi heuristik oleh anggota tim secara sendiri-sendiri, uji tugas dengan lima pendaki (dua di antaranya pemula), SUS dengan 12 sampai 14 responden, dan telusur pembaca layar. Hasilnya tidak pernah dikarang.

## Definisi selesai

Sebuah tugas selesai bila (`CLAUDE.md` bagian 7): persyaratan terimplementasi; kriteria
penerimaan lulus; validasi dan otorisasi ada; aturan data ditegakkan; galat dan fallback
tertangani; test yang relevan lulus; keamanan diuji; aksesibilitas dan tampilan seluler
diperiksa; antislop selesai; runtime diverifikasi; dokumentasi diperbarui; tidak ada cacat
kritis tersisa; maksud produk diperiksa silang.

Sebuah gelombang selesai bila (rencana induk v3): suite Unit dan Feature hijau, `npx tsc
--noEmit` nol galat, `npm run build` sukses, Pint bersih, tangkapan 390 px dan 1440 px
disandingkan dengan gambar acuan di `docs/desain/prototype/`, klik-lewat setiap kontrol,
Delivery Gate antislop, dan `HANDOVER.md` bagian 0 diperbarui. Produk tidak selesai hanya
karena halaman render, CRUD berjalan, tangkapan layar rapi, atau test hijau (`CLAUDE.md`
bagian 8).

## Kriteria mutu

Rubrik penilaian pemilik (20 September 2026) dipakai sebagai daftar periksa audit berulang:

- **Sistem:** accuracy, reliability, security, maintainability, tepat waktu, relevan.
- **UI/UX:** clarity, konsistensi, responsif, feedback, accessibility; ditambah riset pengguna, beban kognitif, hierarki visual, dan pencegahan galat.

Ukuran pengalaman di `CLAUDE.md` bagian 8 (discoverability, comprehension, comparability,
confidence, preparedness, continuity) dan tujuh dampak perilaku di §160 melengkapi rubrik ini.

## Lima prinsip rekayasa

Arahan pemilik 24 September 2026 (verbatim): "selalu menerapkan dan menambahkan prinsip
proper architecture, scalable system, secure by design, stable, and reliable". Setiap
perubahan dijawab dengan lima pertanyaan ini sebelum dilaporkan, dan jawabannya masuk rekap
(§160, `HANDOVER.md` bagian 3 butir 9):

| Prinsip | Pertanyaan yang dijawab |
|---|---|
| Proper architecture | Apakah setiap konsep punya satu sumber kebenaran? Contoh: nama produk hanya di `config('app.name')`, palet hanya di token `app.css`. |
| Scalable system | Apakah jumlah kueri dan ukuran respons tetap datar saat data bertambah, dan state tidak menempel pada satu proses? |
| Secure by design | Apakah input tidak dipercaya, daftar putih dipakai alih-alih daftar hitam, otorisasi di server, endpoint tanpa autentikasi dibatasi laju, dan tidak ada rahasia di kode? |
| Stable | Apakah kegagalan terkendali (peta gagal, cuaca tidak tersedia, JavaScript belum jalan) dan halaman pertama benar tanpa skrip? |
| Reliable | Apakah klaimnya dibuktikan test yang menghitung, runtime yang dipotret, dan paritas PostgreSQL bila skema tersentuh (R-023, R-024)? |

## Format rekap

Setiap tugas diakhiri rekap dengan susunan ini. Persentase selalu disertai metode hitungnya,
supaya dapat diperiksa ulang.

```text
Rekap: <tugas>, <tanggal>

Progres: <n> dari <total> (<persen>). Metode: <yang dihitung dibagi apa>, misalnya langkah
  bercentang dibagi total langkah tugas di rencana, tugas gelombang yang lulus dengan bukti
  dibagi total tugas gelombang, atau butir audit lulus dibagi butir yang berlaku. Butir yang
  gagal atau PERLU KLARIFIKASI tidak dihitung lulus.
Selesai: <apa yang dikerjakan>
Bukti: <nama test, perintah dan keluarannya dengan angka persis, jalur berkas dan baris>
Masalah dan akar masalah: <apa yang rusak dan kenapa>
Solusi: <apa yang dilakukan>
Dicurigai lalu terbukti bukan masalah: <supaya tidak menjadi temuan palsu>
Sisa: <yang belum dikerjakan dan kenapa>
Risiko: <yang dapat menggigit berikutnya>
Pelajaran: <yang dipelajari di tugas ini>
Lima prinsip: proper architecture <jawaban>; scalable <jawaban>; secure by design
  <jawaban>; stable <jawaban>; reliable <jawaban>
Rencana berikutnya: <langkah dan izin yang dibutuhkan>
```

## Protokol tahan-sesi

- `HANDOVER.md` bagian 0 selalu memuat arahan terakhir pemilik (verbatim), aliran kerja yang sedang berjalan beserta berkas miliknya, bukti terakhir (angka test dan build), keputusan terbuka, dan satu blok "Tempelkan ini di terminal baru" (§161). Bagian itu diperbarui di akhir setiap gelombang.
- Spec dan rencana di `docs/superpowers/` adalah sumber urutan kerja; `HANDOVER.md` menunjuk ke sana dan tidak menyalinnya.
- Sesi baru dimulai dengan menempelkan blok prompt dari `HANDOVER.md` bagian 0, lalu memeriksa keadaan nyata (`git status`, suite, `npx tsc --noEmit`) sebelum melanjutkan.
- Catatan kerja subagen tinggal di `.superpowers/` (diabaikan git); pelajaran lintas sesi yang tidak ada di repo disimpan di memori agen.
- Dokumen bertanggal tidak ditulis ulang; keadaan yang berubah dicatat di dokumen hidup dan di `HANDOVER.md`.

## Konvensi bahasa dan commit

- Bahasa Indonesia untuk dokumen, teks antarmuka, dan rekap, dengan sapaan "Anda". Kode, perintah, dan nama pustaka ditulis apa adanya; nama pengenal mengikuti berkas tetangga (banyak nama domain berbahasa Indonesia).
- Tanpa em dash (antislop R-02; `DesignDirectionTest` memeriksanya di kode dan `DESIGN.md`). Tanpa klaim "aman" dan "layak berangkat" (R-012, R-014). Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok"; "Belum dinilai" untuk UNKNOWN; tanpa skor atau persen untuk kecocokan dan kesiapan (R-004, R-005).
- Konvensi rujukan: "§N" hanya untuk ID di `docs/PRD.md`; bagian dokumen lain ditulis "bagian N" beserta nama berkasnya, misalnya "`HANDOVER.md` bagian 3".
- Di dokumen hidup, jalur `.md` di antara backtick adalah jalur dari root repo dan harus ada; `PrdReferenceIntegrityTest` menolaknya bila tidak.
- Commit hanya atas izin pemilik. Satu commit untuk satu perubahan yang dapat dijelaskan dalam satu kalimat, dengan awalan `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`, atau `ci:` dan ringkasan berbahasa Indonesia yang menjelaskan kenapa, bukan mengulang diff. Commit memakai pathspec supaya perubahan lain yang sudah di-stage tidak ikut.
- Branch dibuat dari branch kerja aktif (saat ini `hardening`); push dan merge mengikuti R-018.
````

- [ ] **Step 4: Tulis `docs/README.md`.**

```markdown
# Dokumen Tim Memount

Indeks semua dokumen tim. Anggota baru, manusia maupun agen, mulai dari
[kesepakatan kerja tim](AGENTS.md), lalu [PRD](PRD.md). Setiap jenis isi punya satu pemilik;
peta kepemilikannya ada di awal [PRD](PRD.md). Dokumen bertanggal (spec, rencana, riset,
audit, arsip) adalah catatan pada tanggalnya: jalur dan angka di dalamnya benar pada hari
itu dan tidak ditulis ulang.

## Mulai di sini

| Dokumen | Isi |
|---|---|
| [AGENTS.md](AGENTS.md) | Kesepakatan kerja tim: visi dan ekspektasi pemilik, izin, alur riset lebih dulu, skill, tingkat bukti, cara audit, definisi selesai, format rekap |
| [PRD.md](PRD.md) | Sumber kebenaran produk: visi, misi, aturan bisnis BR, persyaratan FR, arsitektur informasi, tata kelola dan log keputusan |
| [DESIGN.md](DESIGN.md) | Penunjuk ke `DESIGN.md` di root |
| [PRODUCT.md](PRODUCT.md) | Penunjuk ke `PRODUCT.md` di root |
| [../README.md](../README.md) | Pintu masuk repo: stack, cara menjalankan, perintah harian |
| [../CONTRIBUTING.md](../CONTRIBUTING.md) | Cara mengubah kode tanpa merusak yang lain; aturan yang ditegakkan test |
| [../SECURITY.md](../SECURITY.md) | Pelaporan kerentanan dan kebijakan rahasia |
| [../HANDOVER.md](../HANDOVER.md) | Keadaan terkini dan titik lanjut antarsesi |

## Aturan

| Dokumen | Isi |
|---|---|
| [../CLAUDE.md](../CLAUDE.md) | Kontrak operasi: R-018 sampai R-021, R-023, R-024 |
| [../AGENTS.md](../AGENTS.md) | Pintu masuk agen selain Claude: blok Laravel Boost dan penunjuk ke kesepakatan kerja |
| [../.claude/rules/mountain-core.md](../.claude/rules/mountain-core.md) | Aturan domain R-001 sampai R-014 dan R-022 |
| [../DESIGN.md](../DESIGN.md) | Arah desain pemilik, turunannya, R-015 sampai R-017 |
| [../PRODUCT.md](../PRODUCT.md) | Ringkasan produk untuk alat desain (bukan sumber kebenaran) |
| [KEBIJAKAN-DATA-PRIBADI.md](KEBIJAKAN-DATA-PRIBADI.md) | Kebijakan data pribadi dan penghapusan akun |

## Teknik

| Dokumen | Isi |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Peta kode, aturan yang tidak boleh dilanggar, struktur repo, kontrak SEO teknis, catatan operasional |
| [../database/README.md](../database/README.md) | Skema per domain, PostGIS, kebijakan migrasi |
| [MATRIKS-SKILL.md](MATRIKS-SKILL.md) | Skill terpasang, kelasnya, urutan wewenang, status pelacakan |
| [lisensi-pihak-ketiga.md](lisensi-pihak-ketiga.md) | Karya pihak ketiga dan lisensinya |
| [../.github/PULL_REQUEST_TEMPLATE.md](../.github/PULL_REQUEST_TEMPLATE.md) | Daftar periksa pull request |

## Mutu dan audit

| Dokumen | Isi |
|---|---|
| [AUDIT-PROTOCOL.md](AUDIT-PROTOCOL.md) | Protokol audit kelengkapan A sampai N, standar bukti, skor |
| [PROTOKOL-UJI-KEGUNAAN.md](PROTOKOL-UJI-KEGUNAAN.md) | Evaluasi heuristik, uji tugas, SUS, pembaca layar |
| [audit/](audit/) | Laporan audit bertanggal, termasuk audit antislop |

## Fitur, desain, dan riset

| Dokumen | Isi |
|---|---|
| [fitur/](fitur/) | Delapan spesifikasi fitur beserta kriteria penerimaan |
| [desain/prototype/](desain/prototype/) | Prototype pemilik 25 September 2026 dan 16 gambar acuan |
| [riset/](riset/) | Riset asal keputusan, bertanggal |
| [superpowers/specs/](superpowers/specs/) | Spec desain per gelombang |
| [superpowers/plans/](superpowers/plans/) | Rencana implementasi per gelombang |

## Arsip

| Dokumen | Isi |
|---|---|
| [arsip/](arsip/) | Dokumen yang tidak lagi hidup, disimpan utuh sebagai bukti asal |
| [db/](db/) | Snapshot skema Supabase 19 September 2026 sebelum migrasi hardening (tanpa data) |
```

- [ ] **Step 5: Tulis kedua penunjuk.**

`docs/DESIGN.md`:

```markdown
# DESIGN.md di folder docs

Arah desain Memount tinggal di [DESIGN.md di root repo](../DESIGN.md): bagian "Arah (dari pemilik produk)" memuat kata-kata pemilik apa adanya, bagian "Turunan" memuat token, tipografi, ikon, gerak, dan komponen, dan berkas itu adalah pemilik aturan R-015 sampai R-017. Berkasnya sengaja tetap di root karena skill impeccable dan antislop membacanya dari sana (keputusan DK-52, spec v3 bagian 17.4). Berkas ini hanya penunjuk, supaya folder `docs/` memuat keempat dokumen tim tanpa dua sumber kebenaran; jangan menyalin isi `DESIGN.md` ke sini.
```

`docs/PRODUCT.md`:

```markdown
# PRODUCT.md di folder docs

Ringkasan produk untuk alat desain tinggal di [PRODUCT.md di root repo](../PRODUCT.md). Isinya dibaca skill impeccable dari root (keputusan DK-52, spec v3 bagian 17.4) dan bukan sumber kebenaran produk: sumber kebenarannya adalah [PRD](PRD.md), yang peta kepemilikannya menyebut siapa memiliki apa. Berkas ini hanya penunjuk; jangan menyalin isi `PRODUCT.md` ke sini.
```

- [ ] **Step 6: Bagian Memount di `AGENTS.md` root.** Sisipkan teks ini di baris paling atas berkas, sebelum blok Laravel Boost, diikuti satu baris kosong. Jangan menulis nama tag blok Boost di dalamnya.

```markdown
<!-- memount:mulai -->
# Memount: mulai di sini

Repo ini aplikasi Memount (Laravel 13, Inertia 3, React 19, PostgreSQL dan PostGIS). Aturan
tim berlaku untuk semua agen AI dan anggota manusia. Baca berurutan sebelum mengubah apa pun:

1. `docs/AGENTS.md`: kesepakatan kerja tim (izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap, urutan wewenang skill).
2. `CLAUDE.md`: kontrak operasi R-018 sampai R-021, R-023, dan R-024 yang berlaku di setiap sesi.
3. `HANDOVER.md` bagian 0: keadaan terkini dan titik lanjut.
4. `docs/PRD.md` sebelum perubahan produk, domain, atau UX; `DESIGN.md` sebelum pekerjaan antarmuka.

Dua blok di bawah bagian ini dikelola alat: pedoman Laravel Boost (ditulis ulang
`php artisan boost:update`) dan blok NgodingPakeAI. Jangan menyunting isinya dengan tangan;
aturan proyek ditulis di `docs/AGENTS.md`.
<!-- memount:selesai -->
```

- [ ] **Step 7: Satu baris penunjuk di `CLAUDE.md` (koordinator).** Di tabel bagian 9 "REFERENSI", sesudah baris `docs/PRD.md`, tambahkan:

```text
| `docs/AGENTS.md` | Kesepakatan kerja tim untuk manusia dan agen: izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap, urutan wewenang skill |
```

- [ ] **Step 8: Bagian struktur repo di `docs/ARCHITECTURE.md`**, sesudah bagian "Aturan yang tidak boleh dilanggar" dan sebelum "Kontrak SEO teknis":

```markdown
## Struktur repo dan panduan luar

Keputusan DK-51 (spec v3 bagian 17): repo tetap monolit modular Laravel. Dua panduan
struktur yang dibandingkan pemilik (monorepo dengan `apps/` dan `packages/`, serta empat repo
dengan layanan mikro, Kubernetes, Terraform, dan Ansible) diambil prinsipnya lewat pemetaan
di bawah, bukan dengan memindahkan `app/`. Memindahkan susunan memutus autoload Composer,
Artisan, Laravel Boost, konvensi Inertia, dan seluruh suite test.

| Unsur panduan | Padanan di Memount |
|---|---|
| `apps/backend/src` (config, controllers, middlewares, models, routes, services) | `config/`, `app/Http/Controllers`, `app/Http/Middleware`, `app/Models`, `routes/`, `app/Services`, `app/Policies` |
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain menyusul di W1 |
| `packages/api-client` | Rute dan aksi bertipe Wayfinder, router Inertia |
| `packages/ui-design-system` | Token `resources/css/app.css` dan primitif desain (W1) |
| `packages/config` (ESLint, Prettier, TypeScript) | `tsconfig.json` dan Pint; ESLint dan Prettier menunggu izin (Q17) |
| `database/` dengan migrasi, seeder, dan README | `database/migrations`, `database/seeders`, `database/factories`, `database/README.md`; dump SQL diabaikan git |
| `tests/e2e` | Belum ada; Playwright menunggu izin (Q17) |
| `.github/workflows` (test dan deploy) | `test.yml`, `security.yml`, `deploy.yml` manual (DK-54) |
| `docker-compose.yml` | PostgreSQL + PostGIS untuk suite Spatial (DK-55) |
| `.gitignore` sebagai benteng | `.gitignore`, `SecretsNotInRepoTest`, `RepoHygieneTest` |
| README panduan pasang | `README.md`, `CONTRIBUTING.md`, `SECURITY.md`, `docs/README.md` |
| `Dockerfile`, layanan mikro, Kubernetes, Terraform, Ansible | Tidak diambil (aturan 10 di atas) |

**Kriteria bukti kebutuhan** sebelum usulan memecah layanan atau menambah infrastruktur
diajukan ke pemilik (R-021). Setiap butir diukur, bukan diperkirakan:

1. Satu domain membutuhkan skala atau sumber daya yang berbeda jauh dari sisanya, terlihat di metrik produksi (latensi p95, CPU, memori) setelah cache, antrean, dan indeks sudah dicoba.
2. Kegagalan satu domain terbukti menjatuhkan domain lain meski batas waktu, antrean, dan penanganan kegagalan sudah ada.
3. Ada tim terpisah yang butuh jadwal rilis sendiri untuk domain itu.
4. Ada kebutuhan runtime atau kepatuhan (misalnya residensi data) yang tidak dapat dipenuhi di dalam monolit.
5. Untuk Kubernetes, Terraform, atau Ansible: ada lebih dari satu lingkungan yang harus disediakan ulang secara identik, dan platform terkelola (Vercel, Supabase) tidak lagi mencukupi.

Sampai salah satunya terbukti, dua belas domain produk tetap batas modul di dalam `app/`.
```

- [ ] **Step 9: README, CONTRIBUTING, templat PR.**

`README.md`, ganti seluruh isi bagian "## Peta dokumen" (tabel 13 baris) dengan:

```markdown
## Peta dokumen

Indeks lengkap dokumen tim ada di [`docs/README.md`](docs/README.md). Urutan baca untuk
anggota baru, manusia maupun agen:

1. [`docs/AGENTS.md`](docs/AGENTS.md): kesepakatan kerja tim (izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap).
2. [`docs/PRD.md`](docs/PRD.md): sumber kebenaran produk.
3. [`DESIGN.md`](DESIGN.md): arah desain; wajib sebelum pekerjaan antarmuka.
4. [`CONTRIBUTING.md`](CONTRIBUTING.md): cara mengubah sesuatu tanpa merusak yang lain.
5. [`SECURITY.md`](SECURITY.md): pelaporan kerentanan dan kebijakan rahasia.
```

`README.md` bagian "## Untuk agen AI", kalimat pertama menjadi: `Mulai dari `CLAUDE.md`, lalu `HANDOVER.md` bagian 0, lalu `docs/AGENTS.md`. Ketiganya memuat aturan kerja, keadaan terkini, dan prompt untuk melanjutkan di terminal baru.`

`CONTRIBUTING.md`, di akhir paragraf pembuka (sesudah kalimat `Berkas ini menjawab: bagaimana saya mengubah sesuatu tanpa merusak yang lain.`), tambahkan kalimat: `Kesepakatan kerja tim (izin, alur riset lebih dulu, cara audit, definisi selesai, format rekap) ada di `docs/AGENTS.md`.`

`.github/PULL_REQUEST_TEMPLATE.md`, sesudah butir `- [ ] Dokumen yang terdampak diperbarui; ...`, tambahkan butir `- [ ] Dokumen baru di folder docs didaftarkan di `docs/README.md``.

Tambahkan `'docs/AGENTS.md', 'docs/README.md', 'docs/DESIGN.md', 'docs/PRODUCT.md',` ke `PrdReferenceIntegrityTest::DOKUMEN_HIDUP`.

- [ ] **Step 10: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='TeamDocsIndexTest|PrdReferenceIntegrityTest|CiWorkflowsTest|RepoPolicyFilesTest|RepoHygieneTest'`
Expected: PASS semua. Lalu periksa blok Boost dengan perintah SHA-1 di Tugas 1 Step 6; expected dua nilai `3e233a75a9edbcc7cadb54f40eb55cc2f8314386`.

- [ ] **Step 11: Pastikan impeccable tetap membaca berkas root.** Bila `.agents/skills/impeccable/scripts/bin/windows-x64/` berisi biner, jalankan `.agents/skills/impeccable/scripts/impeccable.cmd context` dari root repo dan periksa keluarannya menyebut `PRODUCT.md` dan `DESIGN.md` di root, bukan `docs/PRODUCT.md` atau `docs/DESIGN.md`. Bila biner tidak ada dan peluncur meminta unduhan, jangan mengunduh; catat PERLU KLARIFIKASI di rekap (risiko: resolver impeccable tidak dapat diperiksa karena mesinnya berupa biner).

- [ ] **Step 12: Format.** Run: `vendor/bin/pint --format agent tests/Feature/TeamDocsIndexTest.php tests/Feature/PrdReferenceIntegrityTest.php`.

- [ ] **Step 13: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add docs/AGENTS.md docs/README.md docs/DESIGN.md docs/PRODUCT.md tests/Feature/TeamDocsIndexTest.php
git commit -m "docs: kesepakatan kerja tim dan indeks dokumen di docs/ (DK-52, DK-53)" -- docs/AGENTS.md docs/README.md docs/DESIGN.md docs/PRODUCT.md AGENTS.md CLAUDE.md docs/ARCHITECTURE.md README.md CONTRIBUTING.md .github/PULL_REQUEST_TEMPLATE.md tests/Feature/TeamDocsIndexTest.php tests/Feature/PrdReferenceIntegrityTest.php
```

---

### Task 9: `tests/e2e` dengan Playwright (bersyarat: hanya setelah Q17 disetujui pemilik)

**Gerbang.** Jangan mulai tugas ini tanpa izin tertulis pemilik atas Q17 ("Dependensi dev: `@playwright/test` untuk
`tests/e2e`"). Kutip izinnya di rekap. Tanpa izin, tandai tugas ini TERBLOKIR dan lanjut ke Tugas 10.

**Files:**
- Modify: `package.json`, `package-lock.json` (dependensi dev `@playwright/test`, skrip `test:e2e`)
- Create: `playwright.config.ts`, `tests/e2e/siapkan-env.mjs`, `tests/e2e/halaman-publik.spec.ts`, `tests/e2e/tsconfig.json`
- Create: `tests/Feature/EndToEndIsolationTest.php`
- Modify: `.gitignore` (keluaran Playwright dan `.env.e2e`), `tests/Feature/RepoHygieneTest.php` (`ENTRI_ROOT` dan `HARUS_DIABAIKAN`), `CONTRIBUTING.md` (bagian uji ujung-ke-ujung), `docs/ARCHITECTURE.md` (baris `tests/e2e`)
- Test: `tests/Feature/EndToEndIsolationTest.php`, `tests/e2e/halaman-publik.spec.ts`

**Interfaces:**
- Consumes: `RepoHygieneTest::ENTRI_ROOT` dan `HARUS_DIABAIKAN` (Tugas 2).
- Produces: perintah `npm run test:e2e`; server uji di `http://127.0.0.1:8123` dengan `APP_ENV=e2e` yang memuat `.env.e2e`.

Alasan isolasi: `.env` di mesin pemilik menunjuk Supabase (spec v3 §3.5). `php artisan serve` tanpa `--no-reload`
membuang variabel lingkungan selain daftar terusannya lalu memuat ulang `.env`
(`vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php`, `startProcess`), sehingga menimpa
`DB_CONNECTION` lewat variabel lingkungan tidak cukup. `APP_ENV` termasuk daftar terusan, dan Laravel memuat
`.env.{APP_ENV}` bila berkas itu ada (`LoadEnvironmentVariables::checkForSpecificEnvironmentFile`), jadi server uji
memuat `.env.e2e` dan tidak pernah membaca `.env`.

**Batas cakupan.** `DB_DATABASE=:memory:` di bawah `php artisan serve` berarti setiap permintaan mulai dengan basis
data kosong tanpa tabel, jadi hanya halaman yang tidak menyentuh basis data yang dapat diuji. Dua skenario di bawah
memenuhinya: untuk tamu, `/` hanya membaca config (`routes/web.php:55-58`) dan `HandleInertiaRequests::share()`
membaca pengguna dari sesi berkas. Skenario yang butuh data (masuk, Jelajah, trip) menunggu basis data SQLite lokal
yang sudah dimigrasi lewat jalur berpenjaga `memount:demo` (W1, izin Q1). `webServer` tidak boleh menjalankan
`php artisan migrate` dalam bentuk apa pun: aturan proyek menolaknya bagi agen, dan membungkusnya di dalam perintah
lain sama dengan melangkahi aturan itu.

- [ ] **Step 1: Tulis test isolasi yang gagal.** Run: `php artisan make:test --phpunit --no-interaction EndToEndIsolationTest`, lalu ganti seluruh isinya:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Server uji ujung-ke-ujung tidak pernah membaca .env pengembang.
 *
 * Di mesin pemilik .env menunjuk Supabase (produksi). Server e2e yang memakai ulang
 * server pengembang, atau yang memuat .env, akan menjalankan skenario uji terhadap data
 * sungguhan (R-019). Isolasinya bertumpu pada tiga hal yang dijaga di sini: APP_ENV=e2e,
 * .env.e2e yang dibangun dari .env.example, dan larangan memakai ulang server.
 */
class EndToEndIsolationTest extends TestCase
{
    public function test_the_e2e_server_boots_from_its_own_env_file_never_the_developer_env(): void
    {
        $konfigurasi = File::get(base_path('playwright.config.ts'));
        $siapkan = File::get(base_path('tests/e2e/siapkan-env.mjs'));

        $this->assertStringContainsString("APP_ENV: 'e2e'", $konfigurasi);
        $this->assertStringContainsString('reuseExistingServer: false', $konfigurasi);
        $this->assertStringContainsString("readFileSync('.env.example'", $siapkan);
        $this->assertStringNotContainsString("readFileSync('.env'", $siapkan);
        $this->assertStringContainsString("DB_CONNECTION: 'sqlite'", $siapkan);
    }

    public function test_the_generated_env_file_and_reports_stay_out_of_git(): void
    {
        foreach (['.env.e2e', 'test-results/hasil.json', 'playwright-report/index.html', 'blob-report/laporan.zip'] as $jalur) {
            exec('git -C '.escapeshellarg(base_path()).' check-ignore -q --no-index -- '.escapeshellarg($jalur), $keluaran, $kode);

            $this->assertSame(0, $kode, "{$jalur} harus diabaikan git.");
        }
    }
}
```

Tambahkan `'playwright.config.ts',` ke `RepoHygieneTest::ENTRI_ROOT` (sesudah `'phpunit.xml',`) dan `'.env.e2e', 'test-results/hasil.json', 'playwright-report/index.html',` ke `RepoHygieneTest::HARUS_DIABAIKAN`.

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='EndToEndIsolationTest|RepoHygieneTest'`
Expected: FAIL: `playwright.config.ts` tidak ada; `.env.e2e` dan laporan belum diabaikan.

- [ ] **Step 3: Tulis skenario e2e lebih dulu.** `tests/e2e/halaman-publik.spec.ts`:

```ts
import { expect, test } from '@playwright/test';

test.describe('halaman publik tanpa akun', () => {
    test('halaman depan memuat judul dan membuka halaman masuk', async ({ page }) => {
        const galat: string[] = [];
        page.on('pageerror', (e) => galat.push(e.message));

        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        await page.getByRole('link', { name: 'Masuk', exact: true }).first().click();
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { level: 1, name: 'Masuk' })).toBeVisible();

        expect(galat).toEqual([]);
    });

    test('halaman masuk punya isian berlabel dan tombol masuk', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByLabel('Email', { exact: true })).toBeVisible();
        await expect(page.getByLabel('Kata Sandi', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Masuk', exact: true })).toBeEnabled();
    });
});
```

- [ ] **Step 4: Pasang dependensi dev yang disetujui.**

Run: `npm install --save-dev @playwright/test@1.63.0` lalu `npx playwright install chromium`
Expected: `package.json` memuat `"@playwright/test": "^1.63.0"` di `devDependencies`; peramban Chromium terpasang di cache pengguna, bukan di repo. `.npmrc` (`ignore-scripts=true`) membuat skrip pasca-pasang Playwright tidak berjalan, jadi langkah kedua wajib.

- [ ] **Step 5: Jalankan skenario tanpa konfigurasi dan pastikan gagal.**

Run: `npx playwright test tests/e2e`
Expected: FAIL: `page.goto: Protocol error ... Cannot navigate to invalid URL` karena belum ada `baseURL` dan server uji.

- [ ] **Step 6: Tulis `tests/e2e/siapkan-env.mjs`.**

```js
/*
 * Menyiapkan .env.e2e untuk server uji ujung-ke-ujung.
 *
 * Server e2e tidak pernah membaca .env pengembang, karena .env di mesin pemilik menunjuk
 * Supabase (produksi). Berkas ini disalin dari .env.example yang tidak memuat rahasia,
 * lalu basis datanya diarahkan ke SQLite in-memory. APP_ENV=e2e dari playwright.config.ts
 * membuat Laravel memuat .env.e2e, bukan .env.
 */
import { randomBytes } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';

const timpa = {
    APP_ENV: 'e2e',
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_DEBUG: 'false',
    APP_URL: 'http://127.0.0.1:8123',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: ':memory:',
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
};

const bocor = Object.keys(process.env).filter((kunci) => kunci.startsWith('DB_') || kunci.startsWith('SUPABASE_'));

if (bocor.length > 0) {
    throw new Error(`Variabel ${bocor.join(', ')} dari shell akan menimpa .env.e2e. Hapus dulu sebelum menjalankan e2e.`);
}

const belumAda = new Set(Object.keys(timpa));
const baris = readFileSync('.env.example', 'utf8')
    .split(/\r?\n/)
    .map((isi) => {
        const kunci = isi.split('=')[0];

        if (Object.hasOwn(timpa, kunci)) {
            belumAda.delete(kunci);

            return `${kunci}=${timpa[kunci]}`;
        }

        return isi;
    });

if (belumAda.size > 0) {
    throw new Error(`.env.example tidak memuat ${[...belumAda].join(', ')}; .env.e2e tidak dapat disiapkan.`);
}

writeFileSync('.env.e2e', `${baris.join('\n')}\n`);
```

- [ ] **Step 7: Tulis `playwright.config.ts`.**

```ts
import { defineConfig, devices } from '@playwright/test';

/**
 * Uji ujung-ke-ujung untuk alur kritis (spec v3 Q17). Server uji memuat .env.e2e
 * (SQLite in-memory) lewat APP_ENV=e2e dan tidak pernah memakai ulang server yang sudah
 * berjalan, karena server pengembang membaca .env yang menunjuk Supabase.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    reporter: [['list']],
    use: {
        baseURL: 'http://127.0.0.1:8123',
        locale: 'id-ID',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'ponsel-390', use: { ...devices['Pixel 7'], viewport: { width: 390, height: 844 } } },
        { name: 'layar-lebar-1440', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
    ],
    webServer: {
        command: 'node tests/e2e/siapkan-env.mjs && php artisan serve --host=127.0.0.1 --port=8123',
        url: 'http://127.0.0.1:8123/login',
        env: { APP_ENV: 'e2e' },
        reuseExistingServer: false,
        timeout: 60_000,
    },
});
```

- [ ] **Step 8: `tests/e2e/tsconfig.json`, skrip npm, dan `.gitignore`.**

`tests/e2e/tsconfig.json`:

```json
{
    "extends": "../../tsconfig.json",
    "compilerOptions": {
        "types": ["node"]
    },
    "include": ["./**/*.ts", "../../playwright.config.ts"]
}
```

`package.json`, di `scripts` tambahkan `"test:e2e": "playwright test"`.

`.gitignore`, tambahkan di akhir:

```text

# Uji ujung-ke-ujung (Q17): .env.e2e dibangkitkan tests/e2e/siapkan-env.mjs dengan
# APP_KEY acak; laporan dan jejak Playwright dibangkitkan setiap jalan.
/.env.e2e
/test-results/
/playwright-report/
/blob-report/
/playwright/.cache/
```

- [ ] **Step 9: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='EndToEndIsolationTest|RepoHygieneTest'`
Expected: PASS.

Run: `npm run build && npm run test:e2e`
Expected: `4 passed` (dua skenario di proyek `ponsel-390` dan `layar-lebar-1440`).

Run: `npx tsc -p tests/e2e/tsconfig.json --noEmit`
Expected: exit 0.

- [ ] **Step 10: Dokumentasikan.** `CONTRIBUTING.md`, tambahkan bagian sesudah "Suite spasial di mesin sendiri":

````markdown
## Uji ujung-ke-ujung

Skenario Playwright di `tests/e2e` memeriksa alur kritis di peramban sungguhan, di lebar
390 px dan 1440 px. Server ujinya memuat `.env.e2e` (SQLite in-memory) yang dibangkitkan dari
`.env.example`, sehingga tidak pernah menyentuh basis data yang ditunjuk `.env` Anda.

```sh
npx playwright install chromium   # sekali per mesin
npm run build
npm run test:e2e
```

Hentikan `php artisan serve` yang sedang berjalan di port 8123 lebih dulu: konfigurasi uji
sengaja menolak memakai ulang server yang sudah ada.
````

`docs/ARCHITECTURE.md`, di tabel "Struktur repo dan panduan luar", sel padanan baris `tests/e2e` menjadi `` `tests/e2e` dengan Playwright untuk alur halaman publik; server uji memuat `.env.e2e` ``.

- [ ] **Step 11: Format.** Run: `vendor/bin/pint --format agent tests/Feature/EndToEndIsolationTest.php tests/Feature/RepoHygieneTest.php`.

- [ ] **Step 12: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah.

```bash
git add playwright.config.ts tests/e2e tests/Feature/EndToEndIsolationTest.php
git commit -m "test: uji ujung-ke-ujung Playwright untuk halaman publik (Q17)" -- package.json package-lock.json playwright.config.ts tests/e2e tests/Feature/EndToEndIsolationTest.php tests/Feature/RepoHygieneTest.php .gitignore CONTRIBUTING.md docs/ARCHITECTURE.md
```

---

### Task 10: Verifikasi tonggak M1 (DK-58) dan `HANDOVER.md` bagian 0

Tugas ini menyunting `HANDOVER.md`: dikerjakan koordinator. Push tidak dilakukan.

**Files:**
- Modify: `HANDOVER.md` (bagian 0)
- Test: seluruh suite

**Interfaces:**
- Consumes: semua keluaran Tugas 1 sampai 9 dan potret git dari Prasyarat P3.
- Produces: ringkasan diff dan daftar keputusan untuk pemilik; `HANDOVER.md` bagian 0 yang memuat keadaan M1.

- [ ] **Step 1: Suite penuh.** Run: `php artisan test --compact --testsuite=Unit,Feature`. Expected: `"result":"passed"`, `failed` 0, `errors` 0. Catat angka `tests`, `passed`, `skipped`, `duration_ms` persis.

- [ ] **Step 2: Tipe.** Run: `npx tsc --noEmit; echo "exit=$?"`. Expected: `exit=0`.

- [ ] **Step 3: Build.** Run: `npm run build`. Expected: selesai tanpa galat; catat ukuran bundel yang dicetak Vite.

- [ ] **Step 4: Pint seluruh repo.** Run: `vendor/bin/pint --test`. Expected: tanpa berkas bermasalah. Bila ada, jalankan `vendor/bin/pint --dirty --format agent`, catat daftar berkasnya (termasuk berkas sesi sebelumnya yang belum di-commit, karena CI akan memeriksa semuanya setelah commit), lalu ulangi Step 1 dan Step 4.

- [ ] **Step 5: Pemindai rahasia.** Run: `php artisan test --compact --filter=SecretsNotInRepoTest`. Expected: PASS, mencakup berkas dilacak dan berkas baru yang tidak diabaikan.

- [ ] **Step 6: Audit dependensi.** Run: `composer audit --locked --no-interaction --abandoned=report` dan `npm audit --audit-level=high; echo "exit=$?"`. Expected: Composer tanpa advisori; npm `exit=1` karena GHSA-jrc7-96c5-q579 selama Q20 belum diputuskan. Catat apa adanya; jangan mengubah dependensi.

- [ ] **Step 7: Blok alat utuh.** Jalankan perintah SHA-1 di Tugas 1 Step 6. Expected: dua nilai sama; bila berbeda dari `3e233a75a9edbcc7cadb54f40eb55cc2f8314386` tetapi tetap sama satu sama lain, periksa `git diff` apakah `php artisan boost:update` dijalankan dengan sah, dan catat.

- [ ] **Step 8: Antislop atas berkas baru dan yang diubah.** Run:

```bash
cd "E:/praktik_aplikasi_web" && php -r '$berkas = array_merge(["docs/AGENTS.md", "docs/README.md", "docs/DESIGN.md", "docs/PRODUCT.md", "docs/MATRIKS-SKILL.md", "docs/lisensi-pihak-ketiga.md", "docs/desain/prototype/README.md", "SECURITY.md", "database/README.md", ".github/PULL_REQUEST_TEMPLATE.md", "docker-compose.yml"], glob(".github/ISSUE_TEMPLATE/*.yml"), glob(".github/workflows/*.yml")); foreach ($berkas as $f) { if (str_contains(file_get_contents($f), "\u{2014}")) { echo "em dash: {$f}", PHP_EOL; } } echo "selesai", PHP_EOL;'
```

Expected: hanya `selesai`. Lalu baca ulang `docs/AGENTS.md` dan `docs/README.md` dengan butir Blok 1 Delivery Gate antislop yang berlaku untuk teks (copy kaleng, label kabur, kelengkapan semu) dan tulis PASS atau FAIL per butir dengan bukti di rekap.

- [ ] **Step 9: Suite Spatial.** Docker tidak terpasang di mesin pemilik: catat PERLU KLARIFIKASI dengan alasan itu; suite Spatial berjalan di `test.yml` setelah push yang diizinkan. Bila Docker tersedia, jalankan Tugas 4 Step 7.

- [ ] **Step 10: Status migrasi (butir DK-58).** `php artisan migrate:status` membaca Supabase lewat `.env`, jadi hanya dijalankan setelah pemilik mengizinkan akses baca Supabase untuk operasi itu (R-019). Tanpa izin: catat PERLU KLARIFIKASI, dengan fakta bahwa enam migrasi `2026_09_25_*` belum pernah diizinkan dijalankan di Supabase (`HANDOVER.md` catatan 25 September) dan bahwa RLS tabel yang dibuat sesudah migrasi `2026_09_20_000100` perlu diperiksa di advisor Supabase.

- [ ] **Step 11: Ringkasan diff untuk pemilik.** Run:

```bash
cd "E:/praktik_aplikasi_web" && git status --porcelain=v1 > "<folder scratchpad sesi Anda>/wr-status-akhir.txt" && diff "<folder scratchpad sesi Anda>/wr-status-awal.txt" "<folder scratchpad sesi Anda>/wr-status-akhir.txt"; git status --porcelain=v1 | cut -c1-2 | sort | uniq -c; git diff --stat HEAD | tail -1; git ls-files --others --exclude-standard | cut -d/ -f1 | sort | uniq -c
```

Susun tabel untuk pemilik dengan tiga kelompok yang dipisahkan dari keluaran di atas: (a) perubahan WR (berkas di daftar "Files" Tugas 1 sampai 9), (b) perubahan W0, (c) perubahan sesi sebelumnya yang belum di-commit (migrasi React, pensiun Livewire, konsolidasi dokumen). Untuk setiap kelompok: jumlah berkas baru, diubah, dihapus, dan dipindah, beserta usulan urutan commit.

- [ ] **Step 12: Perbarui `HANDOVER.md` bagian 0 (koordinator).** Ubah judul bagian 0 yang ada menjadi `## 0-26. Catatan 26 September 2026 sebelum WR (riwayat)` tanpa mengubah isinya, lalu sisipkan bagian baru di atasnya. Judulnya memakai tanggal hari tugas ini dijalankan, ditulis seperti judul lama; angka di tabel diisi dari keluaran Step 1 sampai Step 11, persis.

```markdown
## 0. Titik lanjut terkini (tonggak M1, <tanggal Tugas 10 dijalankan>)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu `docs/AGENTS.md` (kesepakatan kerja tim) dan rencana
> induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md`. Periksa keadaan nyata dulu: `git status`,
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik terakhir** (26 September 2026, verbatim di spec v3 bagian 17.1): restrukturisasi repo untuk
persiapan push dengan rapi dan profesional; folder `docs/` berisi DESIGN, PRODUCT, PRD, dan AGENTS sebagai aturan
tim yang detail dan lengkap; skill UI/UX dipasang dan selalu diterapkan dengan dibandingkan dan digabung.

**Keadaan tonggak M1 (W0 dan WR):**

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite Unit dan Feature | <tests> test, <passed> lulus, 0 gagal, 0 error, <skipped> dilewati | Tugas 10 Step 1 |
| TypeScript, build, Pint | 0 galat; build sukses; Pint bersih | Tugas 10 Step 2 sampai 4 |
| Pemindai rahasia | Lulus atas berkas dilacak dan berkas baru | `SecretsNotInRepoTest` |
| Audit dependensi | Composer bersih; npm merah karena GHSA-jrc7-96c5-q579 pada `maplibre-gl` 4.7.1 (Q20) | Tugas 10 Step 6 |
| Suite Spatial | PERLU KLARIFIKASI: Docker tidak terpasang; berjalan di CI setelah push yang diizinkan | Tugas 10 Step 9 |
| Status migrasi Supabase | PERLU KLARIFIKASI sampai izin baca Supabase | Tugas 10 Step 10 |
| Dokumen tim | `docs/AGENTS.md`, `docs/README.md`, `docs/PRD.md`, penunjuk DESIGN dan PRODUCT | `TeamDocsIndexTest` |
| Struktur repo | Root terinventaris; CI dipecah; `docker-compose.yml`; `SECURITY.md`; templat PR dan isu | `RepoHygieneTest`, `CiWorkflowsTest`, `RepoPolicyFilesTest` |
| `tests/e2e` | Selesai atau TERBLOKIR (Q17), sesuai keadaan Tugas 9 | Tugas 9 |
| Commit dan push | Belum; menunggu keputusan di bawah | R-018 |

**Keputusan pemilik yang ditunggu:** (salin daftar "Keputusan pemilik yang dikumpulkan WR" dari rencana
`docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`, dengan status terbarunya).

**Langkah berikutnya, berurutan:** pemilik meninjau ringkasan diff dan memutuskan daftar di atas; seri commit atas
izin; push atas izin, lalu CI (`test.yml`, `security.yml`) dibaca hasilnya; pemilik mengaktifkan private
vulnerability reporting; rencana rinci W1 ditulis dengan `writing-plans`.
```

- [ ] **Step 13: Rekap.** Tulis rekap menurut format di `docs/AGENTS.md`. Metode persentase: tugas WR yang lulus dengan bukti dibagi tugas WR yang dapat dikerjakan tanpa izin tambahan; Tugas 9 dihitung terpisah sebagai TERBLOKIR bila Q17 belum disetujui. Sertakan jawaban lima prinsip rekayasa dan daftar keputusan pemilik.

- [ ] **Step 14: Commit.** Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati dan catat berkas yang berubah. Push tidak dilakukan dalam gelombang ini.

```bash
git commit -m "docs: HANDOVER bagian 0 mencatat tonggak M1" -- HANDOVER.md
```

---

## Keputusan pemilik yang dikumpulkan WR

| # | Keputusan | Keadaan dan usulan |
|---|---|---|
| 1 | Izin commit, dan urutan seri commit untuk perubahan WR, W0, dan sesi sebelumnya yang belum di-commit | Usul: satu seri per kelompok (Tugas 10 Step 11), memakai pathspec |
| 2 | Persetujuan isi PRD v3.0 | `docs/PRD.md` §143 masih "Draf konsolidasi v3.0, menunggu persetujuan pemilik sebelum di-commit"; Tugas 1 membawanya |
| 3 | Q17 Playwright | Tugas 9 terblokir sampai disetujui |
| 4 | Q18 `LICENSE` dan `.github/CODEOWNERS` | Belum dibuat. Repositori publik tanpa lisensi berarti hak cipta tetap pada pemilik; `composer.json` masih memuat `"name": "laravel/laravel"` dan `"license": "MIT"` warisan kerangka dan perlu diselaraskan bersama keputusan ini |
| 5 | Q19 folder skill yang dilacak | 14 folder baru di `.agents/skills/` tidak di-stage sampai diputuskan (daftar di `docs/MATRIKS-SKILL.md`) |
| 6 | Q20 (baru): `maplibre-gl` 4.7.1 terkena GHSA-jrc7-96c5-q579 (kritis, CVSS 10.0; diperbaiki di 6.4.1) | Jalur rentan butuh HTML atribusi dari gaya pihak ketiga, `Popup.setHTML`, atau `setDOMContent` berisi HTML tak dipercaya. Kode memakai `setText` dan `setDOMContent` dari node yang disusun dengan `textContent`, dan `MAP_STYLE_URL` kosong di `.env.example` (INFERENCE: tidak terjangkau pada konfigurasi bawaan; terjangkau bila `MAP_STYLE_URL` diarahkan ke gaya pihak ketiga). Pilihan: gelombang upgrade MapLibre 6.x dengan uji peta (mengubah kunci versi 4.7.x rencana induk, butuh izin), atau menerima risiko sementara secara tertulis dengan tanggal tinjau ulang. `security.yml` merah sampai diputuskan |
| 7 | `jelajah-gunung.zip` | Usul: arsipkan PRD NgodingPakeAI dan delapan features ke `docs/arsip/2026-09-22-ngodingpakeai/` lalu hapus zip (Tugas 2 Step 11) |
| 8 | Aktifkan GitHub private vulnerability reporting | Repositori publik; Settings, bagian keamanan (Advanced Security), sakelar "Private vulnerability reporting" |
| 9 | Push ke `origin/hardening` | Setelah tinjauan diff dan keputusan 1 sampai 8 (R-018) |
| 10 | Izin baca Supabase untuk `php artisan migrate:status` dan advisor keamanan | Butir "status migrasi diverifikasi" DK-58; termasuk memeriksa RLS pada tabel yang dibuat setelah migrasi `2026_09_20_000100` (`permit_requirements`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `usability_sessions`, `scheduled_task_runs`, `hike_track_points`, `report_thanks`, `mountain_follows`, `personal_targets`, `user_owned_items`, dan `trail_considerations` sampai migrasi `2026_09_25_002314` berjalan) |
| 11 | Blok NgodingPakeAI di `AGENTS.md` memuat Workspace ID di repositori publik | Bukan kredensial; pemilik memutuskan dipertahankan atau dikeluarkan dari berkas yang di-push |
| 12 | `docs/db/schema-snapshot-sebelum-hardening.md` memuat ref proyek Supabase dan sudah ada di `origin/hardening` | Ref bukan kredensial; menghapusnya dari riwayat butuh izin menulis ulang riwayat (R-018) |
| 13 | Foto di prototype | Sumber dan hak pakainya belum tercatat; repositori publik |
| 14 | Catatan "Transitional duplication" yang basi di `.claude/rules/mountain-core.md` baris 8 (menyebut representasi lama di CLAUDE.md bagian 11 sampai 25) | `CLAUDE.md` kini hanya bagian 1 sampai 9; berkas aturan milik pemilik, jadi tidak diubah WR |

## Self-Review

**1. Cakupan spec §17, §15, dan bagian WR rencana induk.**

| Sumber | Butir | Tugas |
|---|---|---|
| DK-51 | Tetap monolit modular; pemetaan dua panduan; kriteria bukti kebutuhan di ARCHITECTURE | 8 (bagian "Struktur repo dan panduan luar", dijaga `TeamDocsIndexTest`) |
| DK-52 | `docs/PRD.md`, `docs/AGENTS.md`, `docs/README.md`; `DESIGN.md` dan `PRODUCT.md` tetap di root dengan penunjuk; `AGENTS.md` root dengan pintu masuk; `CLAUDE.md` menunjuk `docs/AGENTS.md` | 1, 8 |
| DK-53 | Isi `docs/AGENTS.md`: visi, misi, ekspektasi; kesepakatan kerja; alur riset; tingkat bukti; cara audit; izin; definisi selesai; rekap berpersentase; lima prinsip; matriks skill dan urutan wewenang; protokol tahan-sesi | 8 Step 3 (heading dan kutipan dijaga `TeamDocsIndexTest`) |
| DK-54 | `test.yml`, `security.yml` (composer audit, npm audit, pemindai rahasia), `deploy.yml` manual tanpa deploy | 3 |
| DK-55 | `docker-compose.yml` PostGIS lokal tanpa mengubah aplikasi; Docker opsional | 4 |
| DK-56 | Inventaris root; prototype ke `docs/desain/prototype/`; `ourplan.md` ke `docs/arsip/`; `features/` dan `.ngodingpakeai/` diabaikan setelah `docs/fitur/` ada; berkas lokal diabaikan | 2 |
| DK-57 | Urutan wewenang skill; "Persist Design System" ke `DESIGN.md`; peninjauan skill pihak ketiga | 6, 8 |
| DK-58 | Suite, `tsc`, build, Pint, pemindai rahasia, diff ditinjau, status migrasi, dokumen lengkap; push menunggu izin | 10 (status migrasi PERLU KLARIFIKASI tanpa izin Supabase) |
| Q17 | Playwright untuk `tests/e2e` | 9 (bersyarat) |
| Q18 | `LICENSE`, `CODEOWNERS` | 5 (dicatat terblokir) |
| Q19 | Folder skill yang dilacak | 6 (status dicatat; tidak di-stage) |
| §17.3 | `.gitignore` benteng, `database/README.md`, dump diabaikan, `SECURITY.md`, templat PR dan isu | 2, 3, 5 |
| Permintaan tambahan pemilik | Dump SQL dan `database/demo.sqlite` diabaikan; `*.disabled`, `.ruff_cache`, cache PHPUnit; `jelajah-gunung.zip` diperiksa lalu diputuskan pemilik; lisensi MIT Phosphor; HANDOVER bagian 0 | 2, 7, 10 |

**2. Pemindaian placeholder.** Rencana dipindai untuk kata dan pola yang dilarang: tidak ada "TBD", "TODO", "implement
later", "tambahkan yang sesuai", "mirip Tugas N", atau langkah kode tanpa blok kode. Nilai yang hanya ada saat
eksekusi ditulis sebagai instruksi pengambilan dengan sumber persisnya: angka suite di `HANDOVER.md` (dari Tugas 10
Step 1 sampai 11), tanggal judul bagian 0 (hari Tugas 10 dijalankan), dan jalur folder scratchpad sesi eksekutor.
Isian di templat rekap `docs/AGENTS.md` dan komentar di templat PR adalah isi templat itu sendiri.

**3. Konsistensi nama.** Nama kelas test dipakai sama di semua tugas: `PrdReferenceIntegrityTest`, `RepoHygieneTest`,
`CiWorkflowsTest`, `SecretsNotInRepoTest`, `LocalPostgisComposeTest`, `RepoPolicyFilesTest`, `SkillGovernanceTest`,
`ThirdPartyLicenseTest`, `TeamDocsIndexTest`, `EndToEndIsolationTest`. Konstanta lintas tugas:
`PrdReferenceIntegrityTest::DOKUMEN_HIDUP` (Tugas 1; ditambah di 5, 7, 8), `RepoHygieneTest::ENTRI_ROOT` (Tugas 2;
ditambah di 4, 5, 9), `RepoHygieneTest::HARUS_DIABAIKAN` (Tugas 2; ditambah di 9). Helper `berkasYangAkanDicommit()`
bernama sama di `RepoHygieneTest` dan `SecretsNotInRepoTest`. Empat frasa urutan wewenang identik di
`docs/MATRIKS-SKILL.md`, `docs/AGENTS.md`, `SkillGovernanceTest::URUTAN_WEWENANG`, dan
`TeamDocsIndexTest::URUTAN_WEWENANG`. Job workflow `test`, `audit-dependensi`, `pemindai-rahasia`, `verifikasi`,
`artefak-build`, layanan `postgis`, volume `memount-postgis-data`, variabel `SPATIAL_PG_PORT`, dan DSN
`pgsql://hiking:hiking@127.0.0.1:54329/hiking_test` dipakai sama di Tugas 3, 4, dan test-nya. Penanda bagian
`<!-- memount:mulai -->` dan `<!-- memount:selesai -->` sama di Tugas 8 Step 6 dan test-nya.

**4. Review Focus.** Kelima butir punya test di tugas pemiliknya: rujukan jalur (Tugas 1 dan 3), blok Boost (Tugas
8), rahasia di berkas baru (Tugas 3), YAML CI (Tugas 3), indeks dokumen (Tugas 8). Mode kegagalan yang diperiksa
tetapi tidak masuk lima besar: server e2e membaca `.env` produksi (dijaga `EndToEndIsolationTest` di Tugas 9),
PRD basi muncul lagi di root (Tugas 1), prototype berubah dari berkas pemilik (Tugas 2), folder `design-system/`
dari ui-ux-pro-max (Tugas 2 lewat `ENTRI_ROOT`), dan dependensi runtime baru tanpa catatan lisensi (Tugas 7).
