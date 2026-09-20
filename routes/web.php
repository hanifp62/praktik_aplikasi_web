<?php

use App\Http\Controllers\ProgressiveWebAppController;
use App\Http\Controllers\ReportPhotoController;
use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Admin\AuthorityManager;
use App\Livewire\Admin\CheckpointManager;
use App\Livewire\Admin\CredentialManager;
use App\Livewire\Admin\DataSourceManager;
use App\Livewire\Admin\MountainManager;
use App\Livewire\Admin\OfficialStatusManager;
use App\Livewire\Admin\PermitManager;
use App\Livewire\Admin\SystemHealth;
use App\Livewire\Admin\TrailGeometryImport;
use App\Livewire\Admin\TrailManager;
use App\Livewire\Admin\UsabilityStudy;
use App\Livewire\Contribute\TrailContribution;
use App\Livewire\Dashboard;
use App\Livewire\Goals\GoalForm;
use App\Livewire\History\HikeSummary;
use App\Livewire\History\HikingHistoryPage;
use App\Livewire\Moderation\ModerationQueue;
use App\Livewire\Onboarding\ProfileSetup;
use App\Livewire\Recommendations\RecommendationResults;
use App\Livewire\Reports\ConditionReportForm;
use App\Livewire\Trails\RouteComparison;
use App\Livewire\Trails\TrailDetail;
use App\Livewire\Trails\TrailIndex;
use App\Livewire\Trips\HikeMode;
use App\Livewire\Trips\PreparationChecklist;
use App\Livewire\Trips\ReadinessDashboard;
use App\Livewire\Trips\TripForm;
use App\Livewire\Trips\TripIndex;
use App\Livewire\Trips\TripShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->middleware('verified')->name('dashboard');
    Route::view('profile', 'profile', ['title' => 'Profil Akun'])->name('profile');

    // Profile -> goal -> route fit -> plan -> prepare -> check -> hike -> report -> history.
    Route::get('onboarding', ProfileSetup::class)->name('onboarding');
    Route::get('goals/create', GoalForm::class)->name('goals.create');
    Route::get('recommendations/{run}', RecommendationResults::class)->name('recommendations.show');

    Route::get('trails', TrailIndex::class)->name('trails.index');
    Route::get('trails/compare', RouteComparison::class)->name('trails.compare');
    Route::get('trails/{trail:slug}', TrailDetail::class)->name('trails.show');

    Route::get('trips', TripIndex::class)->name('trips.index');
    Route::get('trips/create', TripForm::class)->name('trips.create');
    Route::get('trips/{trip}', TripShow::class)->name('trips.show');
    Route::get('trips/{trip}/preparation', PreparationChecklist::class)->name('trips.preparation');
    Route::get('trips/{trip}/readiness', ReadinessDashboard::class)->name('trips.readiness');
    Route::get('trips/{trip}/hike', HikeMode::class)->name('trips.hike');

    Route::get('reports/create', ConditionReportForm::class)->name('reports.create');
    Route::get('reports/{report}/photo', ReportPhotoController::class)->name('reports.photo');
    Route::get('history', HikingHistoryPage::class)->name('history');
    Route::get('history/{trip}', HikeSummary::class)->name('history.summary');

    // Terbuka untuk pemegang kredensial yang berlaku; komponennya menolak yang lain.
    Route::get('kontribusi', TrailContribution::class)->name('contribute');

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
    Route::get('jalur/{trail}/geometri', TrailGeometryImport::class)->name('trails.geometry');
    Route::get('jalur/{trail}/pos', CheckpointManager::class)->name('trails.checkpoints');
});

Route::middleware(['auth', 'role:moderator'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', ModerationQueue::class)->name('queue');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('mountains', MountainManager::class)->name('mountains');
    Route::get('trails', TrailManager::class)->name('trails');
    Route::get('sources', DataSourceManager::class)->name('sources');
    Route::get('official-statuses', OfficialStatusManager::class)->name('statuses');
    Route::get('permits', PermitManager::class)->name('permits');
    Route::get('authorities', AuthorityManager::class)->name('authorities');
    Route::get('credentials', CredentialManager::class)->name('credentials');
    Route::get('audit-logs', AuditLogViewer::class)->name('audit');
    Route::get('analytics', AnalyticsDashboard::class)->name('analytics');
    Route::get('usability', UsabilityStudy::class)->name('usability');
    Route::get('health', SystemHealth::class)->name('health');
});

require __DIR__.'/auth.php';

/*
 * PWA (§106). Manifest dan service worker lewat route karena isinya mengikuti hash
 * build, bukan berkas statis yang harus ditulis ulang setiap deploy.
 */
Route::get('manifest.webmanifest', [ProgressiveWebAppController::class, 'manifest'])->name('pwa.manifest');
Route::get('sw.js', [ProgressiveWebAppController::class, 'serviceWorker'])->name('pwa.sw');
Route::view('offline', 'offline')->name('pwa.offline');
