<?php

use App\Http\Controllers\Admin\ThematicBindingController;
use Illuminate\Support\Facades\Route;

Route::get('/themes', [ThematicBindingController::class, 'index']);
Route::post('/themes/upload', [ThematicBindingController::class, 'upload']);
Route::post('/themes/{skin}/activate', [ThematicBindingController::class, 'activate']);
Route::get('/themes/{skin}/export', [ThematicBindingController::class, 'export']);
Route::post('/themes/css', [ThematicBindingController::class, 'updateCss']);
