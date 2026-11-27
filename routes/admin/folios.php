<?php

use App\Http\Controllers\Admin\FolioController;
use Illuminate\Support\Facades\Route;

Route::post('/folios/reorder', [FolioController::class, 'reorder'])->name('folios.reorder');
Route::put('/folios/{id}/move', [FolioController::class, 'move'])->name('folios.move');
Route::delete('/folios/{id}', [FolioController::class, 'destroy'])->name('folios.destroy');

// AI Content & Lore Assistance for Folios
Route::post('/folios/ai-generate', [FolioController::class, 'aiGenerate'])->name('folios.ai_generate');