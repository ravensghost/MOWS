<?php

use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\DivisionCatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('/sections', [DivisionController::class, 'index'])->name('sections.index');
Route::post('/sections', [DivisionController::class, 'store'])->name('sections.store');
Route::get('/sections/export', [DivisionController::class, 'export'])->name('sections.export');
Route::get('/sections/template', [DivisionController::class, 'template'])->name('sections.template');
Route::post('/sections/import', [DivisionController::class, 'import'])->name('sections.import');
Route::post('/sections/generate-description', [DivisionController::class, 'generateDescription'])->name('sections.generate-description');
Route::put('/sections/{section}', [DivisionController::class, 'update'])->name('sections.update');
Route::delete('/sections/{section}', [DivisionController::class, 'destroy'])->name('sections.destroy');

// Division Catalogues (Ships / Stations / Locations / Groupings)
Route::post('/division-catalogues', [DivisionCatalogueController::class, 'store'])->name('division-catalogues.store');
Route::put('/division-catalogues/{divisionCatalogue}', [DivisionCatalogueController::class, 'update'])->name('division-catalogues.update');
Route::delete('/division-catalogues/{divisionCatalogue}', [DivisionCatalogueController::class, 'destroy'])->name('division-catalogues.destroy');
Route::post('/division-catalogues/{divisionCatalogue}/assign', [DivisionCatalogueController::class, 'assignDivisions'])->name('division-catalogues.assign');
