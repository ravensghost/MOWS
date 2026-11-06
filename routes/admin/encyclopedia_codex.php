<?php
use App\Http\Controllers\Admin\EncyclopediaCodexController;
use Illuminate\Support\Facades\Route;

Route::get('/wiki', [EncyclopediaCodexController::class, 'index'])->name('wiki.index');
Route::post('/wiki', [EncyclopediaCodexController::class, 'store'])->name('wiki.store');
Route::put('/wiki/{wikiArticle}', [EncyclopediaCodexController::class, 'update'])->name('wiki.update');
Route::delete('/wiki/{wikiArticle}', [EncyclopediaCodexController::class, 'destroy'])->name('wiki.destroy');
