<?php

use App\Http\Controllers\Admin\ThematicBindingController;
use Illuminate\Support\Facades\Route;

Route::get('/thematic-bindings', [ThematicBindingController::class, 'index'])->name('thematic_bindings.index');
Route::post('/thematic-bindings/upload', [ThematicBindingController::class, 'upload'])->name('thematic_bindings.upload');
Route::post('/thematic-bindings/{skin}/activate', [ThematicBindingController::class, 'activate'])->name('thematic_bindings.activate');
Route::get('/thematic-bindings/{skin}/export', [ThematicBindingController::class, 'export'])->name('thematic_bindings.export');
Route::post('/thematic-bindings/css', [ThematicBindingController::class, 'updateCss'])->name('thematic_bindings.css');