<?php

use App\Http\Controllers\Admin\ChronologicalEngineController;
use Illuminate\Support\Facades\Route;

Route::post('/chronological-engines', [ChronologicalEngineController::class, 'store'])
    ->name('admin.chronological_engines.store');

Route::put('/chronological-engines/{chronologicalEngine}', [ChronologicalEngineController::class, 'update'])
    ->name('admin.chronological_engines.update');

Route::delete('/chronological-engines/{chronologicalEngine}', [ChronologicalEngineController::class, 'destroy'])
    ->name('admin.chronological_engines.destroy');