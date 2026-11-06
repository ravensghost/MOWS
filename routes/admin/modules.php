<?php

use App\Http\Controllers\Admin\ModuleController;
use Illuminate\Support\Facades\Route;

Route::post('/widgets/sync', [ModuleController::class, 'sync'])->name('widgets.sync');
Route::post('/widgets', [ModuleController::class, 'store']);
Route::put('/widgets/{id}', [ModuleController::class, 'update']);
Route::delete('/widgets/{id}', [ModuleController::class, 'destroy']);
