<?php

use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\MountainController;
use App\Http\Controllers\TrailController;
use Illuminate\Support\Facades\Route;

/*
 * API baca publik dari PR #1 tim (feature/db-migration), dipertahankan di atas skema kanonik.
 * Hanya data terbit (R-022), dibatasi laju per alamat (CLAUDE.md bagian 4), tanpa tulis.
 */
Route::middleware('throttle:60,1')->name('api.')->group(function () {
    Route::get('/mountains', [MountainController::class, 'index'])->name('mountains.index');
    Route::get('/mountains/{id}', [MountainController::class, 'show'])->whereNumber('id')->name('mountains.show');

    Route::get('/trails', [TrailController::class, 'index'])->name('trails.index');
    Route::get('/trails/{id}', [TrailController::class, 'show'])->whereNumber('id')->name('trails.show');

    Route::get('/checkpoints', [CheckpointController::class, 'index'])->name('checkpoints.index');
});
