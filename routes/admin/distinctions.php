<?php

use App\Http\Controllers\Admin\DistinctionController;
use App\Http\Controllers\Admin\DistinctionCatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('awards', [DistinctionController::class, 'index'])->name('awards.index');
Route::get('awards/images', [DistinctionController::class, 'images'])->name('awards.images');
Route::post('awards', [DistinctionController::class, 'store'])->name('awards.store');
Route::put('awards/{award}', [DistinctionController::class, 'update'])->name('awards.update');
Route::delete('awards/{award}', [DistinctionController::class, 'destroy'])->name('awards.destroy');

Route::post('awards/categories', [DistinctionCatalogueController::class, 'store'])->name('awards.categories.store');
Route::put('awards/categories/{id}', [DistinctionCatalogueController::class, 'update'])->name('awards.categories.update');
Route::delete('awards/categories/{id}', [DistinctionCatalogueController::class, 'destroy'])->name('awards.categories.destroy');
