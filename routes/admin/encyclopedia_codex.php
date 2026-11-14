<?php

use App\Http\Controllers\Admin\EncyclopediaCodexController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EncyclopediaCodexController::class, 'index'])->name('index');
Route::post('/', [EncyclopediaCodexController::class, 'store'])->name('store');
Route::put('/{encyclopediaCodex}', [EncyclopediaCodexController::class, 'update'])->name('update');
Route::delete('/{encyclopediaCodex}', [EncyclopediaCodexController::class, 'destroy'])->name('destroy');