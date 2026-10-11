<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthorityController;
use App\Http\Controllers\Admin\CheckpointController;
use App\Http\Controllers\Admin\CredentialController;
use App\Http\Controllers\Admin\DataSourceController;
use App\Http\Controllers\Admin\MountainController;
use App\Http\Controllers\Admin\OfficialStatusController;
use App\Http\Controllers\Admin\PermitController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TrailController;
use App\Http\Controllers\Admin\TrailGeometryController;
use App\Http\Controllers\Admin\UsabilityStudyController;
use App\Http\Controllers\Community\ConditionReportController;
use App\Http\Controllers\Community\HikerProgressController;
use App\Http\Controllers\Community\HikeSummaryController;
use App\Http\Controllers\Community\HikingHistoryController;
use App\Http\Controllers\Community\TrailContributionController;
use App\Http\Controllers\Community\TrailNewsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisplayPreferenceController;
use App\Http\Controllers\Goals\GoalController;
use App\Http\Controllers\HikeTrackController;
use App\Http\Controllers\Moderation\ModerationQueueController;
use App\Http\Controllers\Onboarding\ProfileSetupController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\ProgressiveWebAppController;
use App\Http\Controllers\PublicTrailController;
use App\Http\Controllers\Recommendations\RecommendationController;
use App\Http\Controllers\ReportPhotoController;
use App\Http\Controllers\ThemePreferenceController;
use App\Http\Controllers\Trails\ComparisonShareController;
use App\Http\Controllers\Trails\ConsiderationController;
use App\Http\Controllers\Trails\MountainFollowController;
use App\Http\Controllers\Trails\MountainPageController;
use App\Http\Controllers\Trails\ReportThanksController;
use App\Http\Controllers\Trails\RouteComparisonController;
use App\Http\Controllers\Trails\SavedRoutesController;
use App\Http\Controllers\Trails\SavedTrailNoteController;
use App\Http\Controllers\Trails\TrailDetailController;
use App\Http\Controllers\Trails\TrailFitAssessmentController;
use App\Http\Controllers\Trails\TrailIndexController;
use App\Http\Controllers\Trips\HikeModeController;
use App\Http\Controllers\Trips\PreparationController;
use App\Http\Controllers\Trips\ReadinessController;
use App\Http\Controllers\Trips\TrackController;
use App\Http\Controllers\Trips\TripController;
use App\Http\Controllers\Trips\TripLifecycleController;
use App\Http\Controllers\Trips\TripReminderController;
use Illuminate\Support\Facades\Route;

/*
 * Halaman depan: halaman React pertama (jalur C, 24 September 2026). Judul dan
 * ringkasan dikirim sebagai prop supaya templat akar merender <title> dan meta
 * description di server, sebelum React berjalan.
 */
Route::inertia('/', 'Welcome', [
    'title' => config('app.name').': '.config('brand.tagline'),
    'description' => config('brand.description'),
])->name('welcome');

/*
 * Pilihan tema tampilan, untuk tamu maupun pengguna masuk. Hanya menulis cookie milik
 * peramban yang mengirimnya, jadi tidak ada yang perlu diotorisasi; dibatasi laju
 * supaya tidak menjadi endpoint gratis untuk membanjiri server dengan redirect.
 */
Route::post('tema', [ThemePreferenceController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('tema.simpan');

// Preferensi Tampilan (Efek transparan, Gerak): sama dengan tema, hanya cookie milik peramban.
Route::post('tampilan', [DisplayPreferenceController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('tampilan.simpan');

/*
 * Halaman jalur publik.
 *
 * Satu-satunya bagian aplikasi ini yang terlihat oleh mesin pencari. Seluruh halaman
 * jalur berada di balik login sebelumnya, sehingga orang yang mencari nama gunung tidak
 * pernah menemukan apa pun dari sini.
 *
 * Isinya sengaja hanya keterangan yang berumur panjang: tanpa status resmi dan tanpa
 * prakiraan cuaca. Cuplikan mesin pencari berumur lebih panjang daripada isinya, dan
 * status yang benar hari ini akan tetap terbaca berhari-hari sesudah jalurnya ditutup.
 */
Route::get('pendakian/{trail:slug}', [PublicTrailController::class, 'show'])->name('public.trail');
Route::get('sitemap.xml', [PublicTrailController::class, 'sitemap'])->name('public.sitemap');

/*
 * Tautan bagikan perbandingan (FR-23): dibuka tanpa akun, hanya membaca, tidak diindeks,
 * dan dibatasi laju per alamat dengan hitungannya sendiri (awalan kunci tautan-buka), jadi
 * tidak berbagi kuota dengan rute publik lain yang memakai batas yang sama. Token 32
 * karakter base64url; bentuk lain tidak pernah sampai ke controller.
 */
Route::get('b/{token}', [ComparisonShareController::class, 'show'])
    ->where('token', '[A-Za-z0-9_-]{32}')
    ->middleware('throttle:'.config('hiking.share_links.views_per_minute').',1,tautan-buka')
    ->name('shares.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->middleware('verified')->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('profile/preference', [ProfileController::class, 'preference'])->name('profile.preference');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Profile -> goal -> route fit -> plan -> prepare -> check -> hike -> report -> history.
    Route::get('onboarding', [ProfileSetupController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [ProfileSetupController::class, 'store'])->name('onboarding.store');
    Route::get('goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('recommendations/{run}', [RecommendationController::class, 'show'])->name('recommendations.show');
    Route::post('recommendations/{run}/pilih', [RecommendationController::class, 'pilih'])->name('recommendations.pilih');

    Route::get('trails', TrailIndexController::class)->name('trails.index');
    Route::get('trails/saved', SavedRoutesController::class)->name('trails.saved');
    // Id dari rute dibatasi 18 digit, bukan sekadar angka: id yang melampaui bilangan bulat
    // PHP menjadi TypeError 500 di parameter `int`, bukan 404 (T8-M5 tinjauan akhir W3).
    // Simpanan dan banding (DK-05): menghapus dari simpanan di sini; halaman banding
    // hanya membaca, jadi tidak punya aksi hapus sendiri. Dibatasi laju seperti tulisan
    // pribadi lain, dengan hitungannya sendiri supaya Urungkan tidak ikut habis (m4).
    Route::delete('trails/saved/{trail}', [ConsiderationController::class, 'destroy'])->where('trail', '[0-9]{1,18}')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,hapus-simpanan')->name('trails.saved.remove');
    // Simpan idempoten (spec v3 bagian 6.2 dan 9.1): tombol simpan dan Urungkan sesudah
    // "Hapus dari simpanan", yang mengembalikan jalur beserta catatannya.
    Route::post('trails/saved/{trail}', [ConsiderationController::class, 'store'])->where('trail', '[0-9]{1,18}')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,simpan-jalur')->name('trails.saved.store');
    // Tautan bagikan perbandingan (FR-23): hanya pemiliknya yang membuat dan mencabut.
    Route::post('trails/shares', [ComparisonShareController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,tautan-bagikan')->name('shares.store');
    Route::delete('trails/shares/{share}', [ComparisonShareController::class, 'destroy'])->where('share', '[0-9]{1,18}')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,tautan-bagikan')->name('shares.destroy');
    // Catatan pribadi pada jalur tersimpan (FR-22). Jalur dari rute dan pemilik dari sesi,
    // tidak pernah dari badan permintaan; dibatasi laju seperti tulisan pribadi lain.
    Route::put('trails/saved/{trail}/catatan', [SavedTrailNoteController::class, 'update'])->where('trail', '[0-9]{1,18}')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-simpanan')->name('trails.saved.note.update');
    Route::delete('trails/saved/{trail}/catatan', [SavedTrailNoteController::class, 'destroy'])->where('trail', '[0-9]{1,18}')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-simpanan')->name('trails.saved.note.destroy');
    Route::get('trails/compare', [RouteComparisonController::class, 'show'])->name('trails.compare');
    Route::get('trails/{trail:slug}', TrailDetailController::class)->name('trails.show');
    // Penilaian kecocokan satu jalur (spec v3 bagian 6.5), opsional ?trip= milik pembaca.
    Route::get('trails/{trail:slug}/kecocokan', TrailFitAssessmentController::class)->name('trails.fit');

    // Aksi halaman rinci jalur. Dibatasi laju: keduanya sinyal antarpendaki, dan
    // penulisan berulang tidak menambah arti apa pun.
    Route::post('reports/{report}/thanks', [ReportThanksController::class, 'store'])->middleware('throttle:60,1')->name('reports.thanks');
    Route::post('mountains/{mountain}/follow', [MountainFollowController::class, 'store'])->middleware('throttle:60,1')->name('mountains.follow');
    // Halaman gunung (DK-44): hub jalur sebuah gunung, hanya untuk pengguna masuk.
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');

    Route::get('trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('trips/create', [TripController::class, 'create'])->name('trips.create');
    // Aksi tulis data pribadi dibatasi laju per pengguna; awalan kunci memberi tiap
    // kelompok aksi hitungannya sendiri (config hiking.rate_limits).
    Route::post('trips', [TripController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,rencana-trip')->name('trips.store');
    Route::get('trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::get('trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
    Route::put('trips/{trip}', [TripController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,rencana-trip')->name('trips.update');
    Route::delete('trips/{trip}', [TripController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,rencana-trip')->name('trips.destroy');
    Route::put('trips/{trip}/pengingat', [TripReminderController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,pengingat-trip')->name('trips.reminders.update');
    // Siklus hidup dan daftar persiapan juga tulisan pribadi (W4-Fpre c, W4-FE, ruling W4-FG 6),
    // masing-masing dengan hitungannya sendiri. Centang butir punya batas sendiri yang lebih
    // longgar: pencentangan cepat manusia beserta Urungkan dapat melampaui 30 per menit.
    Route::post('trips/{trip}/start', [TripLifecycleController::class, 'start'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.start');
    Route::post('trips/{trip}/complete', [TripLifecycleController::class, 'complete'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.complete');
    Route::post('trips/{trip}/cancel', [TripLifecycleController::class, 'cancel'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.cancel');
    Route::get('trips/{trip}/preparation', [PreparationController::class, 'show'])->name('trips.preparation');
    Route::post('trips/{trip}/preparation/regenerate', [PreparationController::class, 'regenerate'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,persiapan-daftar')->name('trips.preparation.regenerate');
    Route::post('trips/{trip}/preparation/{item}', [PreparationController::class, 'update'])->whereNumber('item')->middleware('throttle:'.config('hiking.rate_limits.preparation_toggles_per_minute').',1,persiapan-butir')->name('trips.preparation.update');
    Route::get('trips/{trip}/readiness', [ReadinessController::class, 'show'])->name('trips.readiness');
    // W4-6g butir 2: tulisan pribadi yang sama seperti rute trip lain di atas, jadi dibatasi
    // laju yang sama dengan awalan kuncinya sendiri.
    Route::post('trips/{trip}/readiness/recompute', [ReadinessController::class, 'recompute'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,cek-kesiapan')->name('trips.readiness.recompute');
    Route::post('trips/{trip}/readiness/confirm', [ReadinessController::class, 'confirm'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,cek-kesiapan')->name('trips.readiness.confirm');
    // Tab Mendaki (IA v2): membuka pendakian yang berjalan, atau menawarkan trip yang dapat dimulai.
    Route::get('mendaki', TrackController::class)->name('track');
    Route::get('trips/{trip}/hike', [HikeModeController::class, 'show'])->name('trips.hike');
    Route::post('trips/{trip}/hike/position', [HikeModeController::class, 'position'])->name('trips.hike.position');

    /*
     * Jejak dikirim setelah pendaki turun dan mendapat sinyal, ketika halaman hike mode
     * sudah lama ditutup. Karena itu lewat route tersendiri, bukan lewat komponennya.
     */
    Route::post('hike/{session}/track', [HikeTrackController::class, 'store'])->name('hike.track');
    Route::delete('hike/{session}/track', [HikeTrackController::class, 'destroy'])->name('hike.track.destroy');

    Route::get('reports/create', [ConditionReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [ConditionReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}/photo', ReportPhotoController::class)->name('reports.photo');
    Route::get('kabar', TrailNewsController::class)->name('news');
    Route::get('progres', HikerProgressController::class)->name('progress');
    Route::get('history', HikingHistoryController::class)->name('history');
    Route::get('history/{trip}', HikeSummaryController::class)->name('history.summary');

    // Terbuka untuk pemegang kredensial yang berlaku; controllernya menolak yang lain.
    Route::get('kontribusi', [TrailContributionController::class, 'index'])->name('contribute');
    Route::post('kontribusi/{trail}', [TrailContributionController::class, 'store'])->name('contribute.store');

    /*
     * Geometri jalur dan koordinat pos dahulu terkunci di grup admin, padahal kedua
     * komponennya sudah menjaga diri dengan authorize('update', $trail) dan
     * TrailPolicy::update() sudah mengizinkan pemegang kredensial Ahli untuk kawasannya
     * (§43). Izin yang sudah diberikan karena itu tidak pernah dapat dipakai.
     *
     * Ini justru data yang paling mungkin dimiliki pemandu bersertifikat: merekalah yang
     * berjalan di jalurnya sambil membawa GPS. Penjagaannya tetap pada policy, bukan
     * pada middleware, sehingga batas per-kawasan dan masa berlaku sertifikat tetap
     * berlaku utuh.
     */
    Route::get('jalur/{trail}/geometri', [TrailGeometryController::class, 'show'])->name('trails.geometry');
    Route::post('jalur/{trail}/geometri', [TrailGeometryController::class, 'store'])->name('trails.geometry.store');
    Route::post('jalur/{trail}/geometri/berkas', [TrailGeometryController::class, 'preview'])
        ->middleware('throttle:30,1')
        ->name('trails.geometry.preview');
    // OpenStreetMap (Overpass) adalah layanan bersama pihak lain; dibatasi laju.
    Route::post('jalur/{trail}/geometri/osm', [TrailGeometryController::class, 'search'])
        ->middleware('throttle:10,1')
        ->name('trails.geometry.search');
    Route::post('jalur/{trail}/geometri/osm/{osm}', [TrailGeometryController::class, 'choose'])
        ->whereNumber('osm')
        ->name('trails.geometry.choose');
    Route::post('jalur/{trail}/geometri/tanjakan', [TrailGeometryController::class, 'applyElevation'])->name('trails.geometry.elevation');

    Route::get('jalur/{trail}/pos', [CheckpointController::class, 'index'])->name('trails.checkpoints');
    Route::post('jalur/{trail}/pos', [CheckpointController::class, 'store'])->name('trails.checkpoints.store');
    // scopeBindings: checkpoint harus milik jalur di URL-nya, sehingga izin atas satu
    // jalur tidak pernah menjadi izin atas checkpoint jalur lain.
    Route::scopeBindings()->group(function () {
        Route::put('jalur/{trail}/pos/{checkpoint}', [CheckpointController::class, 'update'])->name('trails.checkpoints.update');
        Route::post('jalur/{trail}/pos/{checkpoint}/pindah', [CheckpointController::class, 'move'])->name('trails.checkpoints.move');
        Route::delete('jalur/{trail}/pos/{checkpoint}', [CheckpointController::class, 'destroy'])->name('trails.checkpoints.destroy');
    });
});

Route::middleware(['auth', 'role:moderator'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [ModerationQueueController::class, 'index'])->name('queue');
    Route::post('reports/{report}', [ModerationQueueController::class, 'act'])->name('act');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('mountains', [MountainController::class, 'index'])->name('mountains');
    Route::post('mountains', [MountainController::class, 'store'])->name('mountains.store');
    Route::put('mountains/{mountain}', [MountainController::class, 'update'])->name('mountains.update');
    Route::post('mountains/{mountain}/arsip', [MountainController::class, 'toggleArchive'])->name('mountains.archive');
    Route::get('trails', [TrailController::class, 'index'])->name('trails');
    Route::post('trails', [TrailController::class, 'store'])->name('trails.store');
    Route::put('trails/{trail}', [TrailController::class, 'update'])->name('trails.update');
    Route::post('trails/{trail}/publikasi', [TrailController::class, 'togglePublish'])->name('trails.publish');
    Route::post('trails/{trail}/arsip', [TrailController::class, 'toggleArchive'])->name('trails.archive');
    Route::get('sources', [DataSourceController::class, 'index'])->name('sources');
    Route::post('sources', [DataSourceController::class, 'store'])->name('sources.store');
    Route::put('sources/{source}', [DataSourceController::class, 'update'])->name('sources.update');
    Route::post('sources/{source}/verifikasi', [DataSourceController::class, 'verify'])->name('sources.verify');
    Route::post('sources/{source}/perdebatkan', [DataSourceController::class, 'dispute'])->name('sources.dispute');
    Route::get('official-statuses', [OfficialStatusController::class, 'index'])->name('statuses');
    Route::post('official-statuses', [OfficialStatusController::class, 'store'])->name('statuses.store');
    Route::get('permits', [PermitController::class, 'index'])->name('permits');
    Route::post('permits', [PermitController::class, 'store'])->name('permits.store');
    Route::put('permits/{permit}', [PermitController::class, 'update'])->name('permits.update');
    Route::delete('permits/{permit}', [PermitController::class, 'destroy'])->name('permits.destroy');
    Route::get('authorities', [AuthorityController::class, 'index'])->name('authorities');
    Route::post('authorities', [AuthorityController::class, 'store'])->name('authorities.store');
    Route::put('authorities/{authority}', [AuthorityController::class, 'update'])->name('authorities.update');
    Route::get('credentials', [CredentialController::class, 'index'])->name('credentials');
    Route::post('credentials/{credential}/{keputusan}', [CredentialController::class, 'keputusan'])
        ->whereIn('keputusan', CredentialController::keputusanTersedia())
        ->name('credentials.decide');
    Route::get('audit-logs', AuditLogController::class)->name('audit');
    Route::get('analytics', AnalyticsController::class)->name('analytics');
    Route::get('usability', [UsabilityStudyController::class, 'index'])->name('usability');
    Route::post('usability', [UsabilityStudyController::class, 'store'])->name('usability.store');
    Route::get('health', SystemHealthController::class)->name('health');
});

require __DIR__.'/auth.php';

/*
 * PWA (§106). Manifest dan service worker lewat route karena isinya mengikuti hash
 * build, bukan berkas statis yang harus ditulis ulang setiap deploy.
 */
Route::get('manifest.webmanifest', [ProgressiveWebAppController::class, 'manifest'])->name('pwa.manifest');
Route::get('sw.js', [ProgressiveWebAppController::class, 'serviceWorker'])->name('pwa.sw');
Route::view('offline', 'offline')->name('pwa.offline');

/*
 * Pameran primitif desain (ruling W1-R5): hanya di APP_ENV=local, tidak pernah di staging
 * atau produksi, dan tidak masuk sitemap. Isinya di routes/dev.php.
 */
if (app()->environment('local')) {
    require __DIR__.'/dev.php';
}
