<?php

use App\Http\Controllers\Admin\InsigniaController;
use App\Http\Controllers\Admin\InsigniaCatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('/ranks', [InsigniaController::class, 'index'])->name('ranks.index');
Route::get('/ranks/export', [InsigniaController::class, 'export'])->name('ranks.export');
Route::get('/ranks/template', [InsigniaController::class, 'template'])->name('ranks.template');
Route::post('/ranks/import', [InsigniaController::class, 'import'])->name('ranks.import');
Route::get('/ranks/images', [InsigniaController::class, 'images'])->name('ranks.images');
Route::post('/ranks/reorder', [InsigniaController::class, 'reorder']);
Route::post('/ranks', [InsigniaController::class, 'store']);
Route::put('/ranks/{rank}', [InsigniaController::class, 'update']);
Route::delete('/ranks/{rank}', [InsigniaController::class, 'destroy']);

Route::post('/rank-sets', [InsigniaCatalogueController::class, 'store']);
Route::put('/rank-sets/{rankSet}', [InsigniaCatalogueController::class, 'update']);
Route::delete('/rank-sets/{rankSet}', [InsigniaCatalogueController::class, 'destroy']);
Route::post('/rank-sets/{rankSet}/assign', [InsigniaCatalogueController::class, 'assignSections']);
