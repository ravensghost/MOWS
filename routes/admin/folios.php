<?php

use App\Http\Controllers\Admin\FolioController;
use Illuminate\Support\Facades\Route;

Route::post('/folios/reorder', [FolioController::class, 'reorder'])->name('admin.folios.reorder');
Route::put('/folios/{id}/move', [FolioController::class, 'move'])->name('admin.folios.move');
Route::delete('/folios/{id}', [FolioController::class, 'destroy'])->name('admin.folios.destroy');

// AI Content & Lore Assistance for Folios
Route::post('/folios/ai-generate', [FolioController::class, 'aiGenerate'])->name('admin.folios.ai_generate');