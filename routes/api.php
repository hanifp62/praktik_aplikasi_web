<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MountainController;
use App\Http\Controllers\TrailController;
use App\Http\Controllers\CheckpointController;


Route::get('/mountains', [MountainController::class, 'index']);

Route::get('/mountains/{id}', [MountainController::class, 'show']);


Route::get('/trails', [TrailController::class, 'index']);

Route::get('/trails/{id}', [TrailController::class, 'show']);


Route::get('/checkpoints', [CheckpointController::class, 'index']);