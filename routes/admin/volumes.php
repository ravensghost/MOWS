<?php

use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\VolumeController;
use App\Http\Controllers\Admin\VolumeReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/volumes', [PublicationController::class, 'storyManager'])->name('volumes.index');
Route::post('/volumes/reorder', [VolumeController::class, 'reorder'])->name('volumes.reorder');
Route::post('/volumes', [VolumeController::class, 'store'])->name('volumes.store');
Route::put('/volumes/{id}', [VolumeController::class, 'update'])->name('volumes.update');
Route::delete('/volumes/{id}', [VolumeController::class, 'destroy'])->name('volumes.destroy');

Route::get('/volumes/{volume}/review', [VolumeReviewController::class, 'showModal'])->name('volumes.review');
Route::post('/volumes/{volume}/review', [VolumeReviewController::class, 'store'])->name('volumes.review.store');