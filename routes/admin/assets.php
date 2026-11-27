<?php

use App\Http\Controllers\Admin\AssetController;
use Illuminate\Support\Facades\Route;

Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
Route::delete('/assets', [AssetController::class, 'destroy'])->name('assets.destroy');