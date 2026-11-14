<?php

use App\Http\Controllers\Admin\DistinctionCatalogueController;
use App\Http\Controllers\Admin\DistinctionController;
use Illuminate\Support\Facades\Route;

Route::get('distinctions', [DistinctionController::class, 'index'])->name('distinctions.index');
Route::get('distinctions/images', [DistinctionController::class, 'images'])->name('distinctions.images');
Route::post('distinctions', [DistinctionController::class, 'store'])->name('distinctions.store');
Route::put('distinctions/{distinction}', [DistinctionController::class, 'update'])->name('distinctions.update');
Route::delete('distinctions/{distinction}', [DistinctionController::class, 'destroy'])->name('distinctions.destroy');

Route::post('distinctions/catalogues', [DistinctionCatalogueController::class, 'store'])->name('distinctions.catalogues.store');
Route::put('distinctions/catalogues/{catalogue}', [DistinctionCatalogueController::class, 'update'])->name('distinctions.catalogues.update');
Route::delete('distinctions/catalogues/{catalogue}', [DistinctionCatalogueController::class, 'destroy'])->name('distinctions.catalogues.destroy');