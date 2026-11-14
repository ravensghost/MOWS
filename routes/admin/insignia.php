<?php

use App\Http\Controllers\Admin\InsigniaCatalogueController;
use App\Http\Controllers\Admin\InsigniaController;
use Illuminate\Support\Facades\Route;

Route::get('/insignia', [InsigniaController::class, 'index'])->name('insignia.index');
Route::get('/insignia/export', [InsigniaController::class, 'export'])->name('insignia.export');
Route::get('/insignia/template', [InsigniaController::class, 'template'])->name('insignia.template');
Route::post('/insignia/import', [InsigniaController::class, 'import'])->name('insignia.import');
Route::get('/insignia/images', [InsigniaController::class, 'images'])->name('insignia.images');
Route::post('/insignia/reorder', [InsigniaController::class, 'reorder'])->name('insignia.reorder');
Route::post('/insignia', [InsigniaController::class, 'store'])->name('insignia.store');
Route::put('/insignia/{insignia}', [InsigniaController::class, 'update'])->name('insignia.update');
Route::delete('/insignia/{insignia}', [InsigniaController::class, 'destroy'])->name('insignia.destroy');

Route::post('/insignia/catalogues', [InsigniaCatalogueController::class, 'store'])->name('insignia.catalogues.store');
Route::put('/insignia/catalogues/{catalogue}', [InsigniaCatalogueController::class, 'update'])->name('insignia.catalogues.update');
Route::delete('/insignia/catalogues/{catalogue}', [InsigniaCatalogueController::class, 'destroy'])->name('insignia.catalogues.destroy');
Route::post('/insignia/catalogues/{catalogue}/assign', [InsigniaCatalogueController::class, 'assignSections'])->name('insignia.catalogues.assign');