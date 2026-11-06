<?php

use App\Http\Controllers\Admin\TableOfContentsController;
use Illuminate\Support\Facades\Route;

Route::get('/navigation', [TableOfContentsController::class, 'index']);
Route::post('/navigation', [TableOfContentsController::class, 'store']);
Route::put('/navigation/{link}', [TableOfContentsController::class, 'update']);
Route::delete('/navigation/{link}', [TableOfContentsController::class, 'destroy']);

Route::get('/navigation/options', [TableOfContentsController::class, 'optionsIndex'])->name('navigation.options');
Route::post('/navigation/options', [TableOfContentsController::class, 'storeOption'])->name('navigation.options.store');
Route::put('/navigation/options/{option}', [TableOfContentsController::class, 'updateOption'])->name('navigation.options.update');
Route::delete('/navigation/options/{option}', [TableOfContentsController::class, 'destroyOption'])->name('navigation.options.destroy');
