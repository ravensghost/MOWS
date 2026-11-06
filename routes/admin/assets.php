<?php

use App\Http\Controllers\Admin\AssetController;
use Illuminate\Support\Facades\Route;

Route::get('/general-images', [AssetController::class, 'index'])->name('general-images.index');
Route::post('/general-images', [AssetController::class, 'store'])->name('general-images.store');
Route::delete('/general-images', [AssetController::class, 'destroy'])->name('general-images.destroy');
