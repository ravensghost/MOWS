<?php

use App\Http\Controllers\Admin\ModuleController;
use Illuminate\Support\Facades\Route;

Route::post('/modules/sync', [ModuleController::class, 'sync'])->name('modules.sync');
Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
Route::put('/modules/{id}', [ModuleController::class, 'update'])->name('modules.update');
Route::delete('/modules/{id}', [ModuleController::class, 'destroy'])->name('modules.destroy');