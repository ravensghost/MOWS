<?php

use App\Http\Controllers\Admin\FolioController;
use App\Http\Controllers\FolioAuthoringController;
use Illuminate\Support\Facades\Route;

Route::post('/chapters/reorder', [FolioController::class, 'reorder']);
Route::put('/chapters/{id}/move', [FolioController::class, 'move']);
Route::delete('/chapters/{id}', [FolioController::class, 'destroy']);

// AI Content & Lore Assistance for Folios
Route::post('/ai/chapter-generate', [FolioAuthoringController::class, 'aiChapterGenerate']);
