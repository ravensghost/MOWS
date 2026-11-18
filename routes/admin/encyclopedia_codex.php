<?php

use App\Http\Controllers\Admin\EncyclopediaCodexController;
use Illuminate\Support\Facades\Route;

Route::get('/encyclopedia-codex', [EncyclopediaCodexController::class, 'index'])->name('encyclopedia_codex.index');
Route::post('/encyclopedia-codex', [EncyclopediaCodexController::class, 'store'])->name('encyclopedia_codex.store');
Route::put('/encyclopedia-codex/{encyclopediaCodex}', [EncyclopediaCodexController::class, 'update'])->name('encyclopedia_codex.update');
Route::delete('/encyclopedia-codex/{encyclopediaCodex}', [EncyclopediaCodexController::class, 'destroy'])->name('encyclopedia_codex.destroy');