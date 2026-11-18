<?php

use App\Http\Controllers\Admin\DivisionCatalogueController;
use App\Http\Controllers\Admin\DivisionController;
use Illuminate\Support\Facades\Route;

Route::get('/divisions', [DivisionController::class, 'index'])->name('divisions.index');
Route::get('/divisions/export', [DivisionController::class, 'export'])->name('divisions.export');
Route::get('/divisions/template', [DivisionController::class, 'template'])->name('divisions.template');
Route::post('/divisions/import', [DivisionController::class, 'import'])->name('divisions.import');
Route::post('/divisions/generate-description', [DivisionController::class, 'generateDescription'])->name('divisions.generate_description');
Route::post('/divisions', [DivisionController::class, 'store'])->name('divisions.store');
Route::put('/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');

Route::post('/divisions/catalogues', [DivisionCatalogueController::class, 'store'])->name('divisions.catalogues.store');
Route::put('/divisions/catalogues/{divisionCatalogue}', [DivisionCatalogueController::class, 'update'])->name('divisions.catalogues.update');
Route::delete('/divisions/catalogues/{divisionCatalogue}', [DivisionCatalogueController::class, 'destroy'])->name('divisions.catalogues.destroy');
Route::post('/divisions/catalogues/{divisionCatalogue}/assign', [DivisionCatalogueController::class, 'assignDivisions'])->name('divisions.catalogues.assign');