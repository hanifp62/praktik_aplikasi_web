# CLAUDE.md
# Operating Contract — Personalized Mountain Planning & Readiness Platform

> STATUS: MASTER AGENT CONTRACT
> BAHASA: Bahasa Indonesia untuk penjelasan dan rekap, kecuali kode/tooling menuntut lain.
> PRIORITAS: IMK/HCI dan UI/UX lebih dulu, lalu logika produk, arsitektur, keamanan dan
> privasi, keandalan, observability, konsistensi data, pertumbuhan (`PRODUCT.md`).

Berkas ini kontrak operasi: aturan yang berlaku pada **setiap** sesi. Detail produk,
domain, arsitektur, audit, dan kebijakan tinggal di berkas yang disebut pada §2 dan §9,
dan tidak diduplikasi di sini.

---

# 1. ATURAN OPERASI WAJIB

Sebelum mengubah kode aplikasi, skema, UX, konfigurasi, test, data, deployment, atau
dokumentasi yang memengaruhi perilaku produk:

1. Baca `CLAUDE.md` ini sampai selesai.
2. Baca `HANDOVER.md` untuk keadaan kode terakhir, dan `AGENTS.md` bila ada.
3. Baca `PRD.md` sebelum perubahan produk, domain, atau UX.
4. Baca berkas canonical yang relevan menurut peta di §2.
5. Periksa keadaan repositori dan runtime yang sebenarnya. Dokumentasi adalah konteks;
   runtime adalah kebenaran implementasi.
6. Inventarisasi seluruh skill terpasang dan nilai relevansinya sebelum mulai.
7. Terapkan antislop selama bekerja dan sekali lagi sebelum menyatakan selesai.
8. Riset lebih dalam ketika ketidakpastian benar-benar memengaruhi keputusan.
9. Uji dan periksa silang setiap perubahan yang berarti.
10. Jangan pernah mengubah arah produk, aturan bisnis, perilaku privasi, cakupan,
    arsitektur, atau otoritas data secara diam-diam.
11. Bila dokumentasi bertentangan dengan kode, basis data, atau runtime, laporkan
    ketidaksesuaiannya sebelum mengubah arsitektur atau data.

---

# 2. PETA ATURAN CANONICAL

Setiap aturan punya satu pemilik. Ketika dua berkas berbeda, pemiliknya yang berlaku.

**`.claude/rules/mountain-core.md`** — termuat otomatis setiap sesi.
R-001 Mountain ≠ Trail · R-002 Trail unit keputusan · R-003 Route Fit differentiator inti
· R-004 label fit publik terbatas · R-005 tanpa false precision · R-006 UNKNOWN ≠ LOW ·
R-007 missing data bukan nilai rendah · R-008 trail tak lengkap tidak masuk rekomendasi ·
R-009 authority hierarchy · R-010 community tidak override official · R-011 AI tidak
override official · R-012 OPEN ≠ SAFE · R-013 weather punya source/freshness/reference ·
R-014 readiness adalah decision support · R-022 publication gate.

**`DESIGN.md`** — **tidak** termuat otomatis. R-015 progress adalah self-comparison ·
R-016 comparison tanpa winner · R-017 foto komunitas bukan hero default. **Untuk
pekerjaan UI/UX/desain, baca `DESIGN.md`**; isinya tidak disalin ke sini.

**Berkas ini.** R-018 git, R-019 Supabase, dan R-021 arah produk ada di §3; R-023 tingkat
bukti dan R-024 paritas PostgreSQL di §5; R-020 versi framework di §6. Aturan bisnis
produk BR-01–BR-16 ada di `PRD.md` §132.

---

# 3. TATA KELOLA DAN PERSETUJUAN

**R-018 — Git.** Boleh tanpa persetujuan: edit lokal, branch lokal, commit lokal.
Memerlukan persetujuan eksplisit pemilik: push, merge, tag, rilis, force push, dan
penulisan ulang riwayat. Sebelum push: test, lint/format, tinjau diff, pindai secret,
dan verifikasi migrasi.

**R-019 — Supabase.** Sebelum mengakses: izin pemilik, environment target dikonfirmasi,
operasi yang dimaksud dikonfirmasi. Jangan pernah mencetak secret, menaruh kredensial di
kode atau skrip, mengekspos service-role key ke peramban, atau menjalankan operasi
destruktif di produksi tanpa persetujuan tepat sebelum eksekusi.

**R-021 — Arah produk.** Jangan pernah secara diam-diam menambah fitur besar, mengubah
Route Fit, mengubah batas MVP/MVP+, mengubah arsitektur navigasi, mengubah hierarki
otoritas, mengubah kebijakan privasi atau penghapusan, mengubah arsitektur basis data,
mengubah versi framework, mengubah semantik penyedia eksternal, atau memperkenalkan
monetisasi dan kompetisi sosial.

Untuk perubahan berdampak produk, nyatakan: aturan sekarang, alasan, bukti, alternatif,
dampak, risiko, rekomendasi, dan kebutuhan persetujuan. Perubahan `PRD.md` wajib punya
governance record sesuai `PRD.md` §130, dicatat di `PRD.md` §146.

---

# 4. KEAMANAN DAN PRIVASI

Pertahanan berlapis: Browser → Auth → Authorization → Validation → Business Rules →
Database Authorization/RLS → Storage Authorization.

Minimum: hashing kata sandi yang aman; sesi aman; Policy/Gate; otorisasi tingkat objek;
validasi sisi server; proteksi mass assignment; rate limiting; unggahan aman; manajemen
secret; audit log; privasi lokasi; RLS bila data Supabase terekspos. Jangan pernah
mempercayai ID kepemilikan atau peran yang dikirim pengguna.

Test keamanan kritis yang wajib lulus sebelum rilis MVP: auth bypass, IDOR, eskalasi
hak, mass assignment, unggahan tidak aman, rate limiting, kebocoran secret, RLS
allow/deny, akses lokasi privat, dan perilaku privasi pada penghapusan akun. Kebijakan
data pribadi, penghapusan akun, retensi, dan batas paparan pasca-penghapusan ada di
**`docs/KEBIJAKAN-DATA-PRIBADI.md`** dan itu canonical; jangan memutuskan hal-hal
tersebut di tempat lain.

---

# 5. BUKTI DAN MUTU

**R-023 — Tingkat bukti.** Pisahkan selalu FACT, DECISION, INFERENCE, HYPOTHESIS,
RECOMMENDATION, dan OPEN ISSUE. **Tanpa bukti, tidak ada PASS.** Bukti yang sah antara
lain path berkas dan rentang baris, nama kelas/service/migrasi/tabel/indeks, nama route,
path view, nama test, keluaran runtime, hasil query, dan bukti peramban. Bila bukti
ambigu, statusnya PERLU KLARIFIKASI, bukan lulus. Jangan menyatakan berhasil hanya
karena kode ada, tangkapan layar rapi, atau suite lokal hijau, dan jangan mengarang hasil
riset pengguna maupun metrik UX.

**R-024 — Paritas PostgreSQL/PostGIS.** Suite SQLite yang hijau **tidak** membuktikan
kebenaran pada PostgreSQL/Supabase. Insiden historisnya: test memakai SQLite in-memory
dengan seluruh migrasi hadir sementara environment aplikasi kehilangan migrasi; test
lulus, halaman gagal. Test integrasi kompatibel PostgreSQL wajib untuk PostGIS,
konkurensi dan penguncian, transaksi, constraint skema, indeks nyata, dan perilaku basis
data seperti produksi. Setiap kali migrasi atau skema berubah, verifikasi status migrasi
aplikasi yang sebenarnya dan jangan menyimpulkan "DB sehat" hanya dari test. Setiap cacat
produksi yang diperbaiki wajib dilindungi regression test; enam cacat yang sudah ditutup
tercatat di `HANDOVER.md` §7 dan tetap menjadi skenario regresi.

---

# 6. BATASAN ENGINEERING

**R-020 — Versi framework.** Sebelum perubahan aplikasi, verifikasi `php -v`,
`composer -V`, `php artisan --version`, dan `composer show laravel/framework`. Target
runtime PHP 8.5. **Jangan mengubah versi framework secara diam-diam.** Bila versi nyata
berbeda dari yang terdokumentasi, laporkan ketidaksesuaiannya.

**Anggaran query.** Jumlah query tidak boleh tumbuh linear terhadap ukuran data. Hindari
N+1. Utamakan eager/batch loading, join dan agregat seperlunya, paginasi, cache,
ringkasan terhitung, indeks spasial, dan penyegaran di latar belakang.

**Aksesibilitas.** Target WCAG 2.2 AA: HTML semantik, akses keyboard, fokus terlihat,
label dan galat yang dapat diakses, kontras memadai, status tidak hanya lewat warna,
reflow dan zoom seluler, serta kendali ramah sentuh sekitar 44×44 px untuk kendali utama
di lapangan. Uji layar yang sebenarnya; kelas CSS bukan bukti. Arsitektur, lapisan kode,
dan aturan yang tidak boleh dilanggar ada di `docs/ARCHITECTURE.md`.

---

# 7. ALUR KERJA AGEN

**Skill.** Inventarisasi seluruh skill terpasang, klasifikasikan sebagai CORE,
SUPPORTING, CROSS-CUTTING, atau N/A dengan alasan, lalu terapkan yang relevan. Jangan
melewatkan skill hanya karena ia tidak terpikir lebih dulu, dan jangan menjalankan skill
tak relevan demi formalitas.

**Antislop.** Siklusnya rencana → implementasi → cek antislop → test → periksa silang →
antislop akhir → lapor. Cari copy kaleng, kartu dan kontainer yang tidak perlu, hierarki
palsu, pola berulang tanpa maksud, label kabur, elemen dekoratif yang tidak membantu
keputusan, komentar boilerplate, kelengkapan semu, serta state kosong dan galat yang
lemah. Jangan membuang konten berguna semata-mata demi memperpendek teks.

**Periksa silang.** Setelah perubahan berarti, tinjau produk, UX/HCI, arsitektur, data,
keamanan dan privasi, keandalan, performa, aksesibilitas, testing, dan SEO.

**Definition of Done.** Persyaratan terimplementasi; kriteria penerimaan lulus; validasi
dan otorisasi ada; aturan data ditegakkan; galat dan fallback tertangani; test relevan
lulus; keamanan diuji; aksesibilitas dan seluler diperiksa; antislop selesai; runtime
diverifikasi; dokumentasi diperbarui; tidak ada cacat kritis tersisa; maksud produk
diperiksa silang.

**Rekap.** Memuat progres dengan persentase beserta metode hitungnya, yang selesai,
bukti, masalah, akar masalah, solusi, sisa, risiko, pelajaran, dan rencana berikutnya.

---

# 8. MUTU AKHIR

Produk tidak selesai hanya karena semua halaman render, CRUD jalan, tangkapan layar
rapi, atau test hijau. Ukurannya tiga hal berikut.

**Pengalaman:** discoverability, comprehension, comparability, confidence, preparedness,
continuity. **Sistem:** arsitektur koheren, query terskala, garis keturunan data benar,
kegagalan terkendali, observability, otorisasi dan privasi kuat, perilaku PostgreSQL yang
benar, interaksi yang mudah diakses di seluler. **Produk:** penemuan yang mengutamakan
manusia, lingkaran komunitas berguna, nilai kembali lewat progresi, sumber jujur.

> Riset sebelum menebak. Verifikasi sebelum mengklaim. Uji sebelum melapor. Periksa
> silang sebelum menyatakan selesai. Jangan pernah mengubah kontrak produk diam-diam.

---

# 9. REFERENSI

| Berkas | Isi |
|---|---|
| `PRD.md` | Requirement, domain, enum, BR-01–BR-16, batas MVP, arsitektur informasi §145, governance record §146 |
| `PRODUCT.md` | Definisi produk, positioning, konteks operasi, growth, prioritas dan peran, ATM, rujukan, model kerja IMK, hasil UX |
| `DESIGN.md` | Arah desain dan R-015–R-017 |
| `docs/ARCHITECTURE.md` | Peta kode, aturan yang tidak boleh dilanggar, kontrak SEO teknis, catatan operasional |
| `docs/KEBIJAKAN-DATA-PRIBADI.md` | Kebijakan data pribadi dan penghapusan akun |
| `docs/AUDIT-PROTOCOL.md` | Protokol audit kelengkapan, checklist A–N, evidence standard, scoring. Audit-only |
| `docs/PROTOKOL-UJI-KEGUNAAN.md` | Protokol uji kegunaan, SUS, pembaca layar |
| `HANDOVER.md` | Keadaan kode, cacat historis, keputusan yang sudah tertutup |

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
