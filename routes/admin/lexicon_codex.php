<?php

use App\Http\Controllers\Admin\LexiconCodexController;
use App\Http\Controllers\Admin\SemanticLanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/lexicon-codex', [LexiconCodexController::class, 'index'])->name('lexicon_codex.index');
Route::post('/lexicon-codex', [LexiconCodexController::class, 'update'])->name('lexicon_codex.update');
Route::delete('/lexicon-codex/term/{id}', [LexiconCodexController::class, 'destroyTerm'])->name('lexicon_codex.term.destroy');
Route::delete('/lexicon-codex/group/{groupName}', [LexiconCodexController::class, 'destroyGroup'])->name('lexicon_codex.group.destroy');
Route::delete('/lexicon-codex/group/{master}/subgroup/{subgroup}', [LexiconCodexController::class, 'destroySubgroup'])->name('lexicon_codex.subgroup.destroy');

Route::post('/lexicon-codex/language', [SemanticLanguageController::class, 'store'])->name('lexicon_codex.language.store');
Route::put('/lexicon-codex/language', [SemanticLanguageController::class, 'update'])->name('lexicon_codex.language.update');
Route::delete('/lexicon-codex/language/{code}', [SemanticLanguageController::class, 'destroy'])->name('lexicon_codex.language.destroy');
Route::delete('/lexicon-codex/language/{code}/clear', [SemanticLanguageController::class, 'clear'])->name('lexicon_codex.language.clear');
Route::get('/lexicon-codex/export/{locale?}', [SemanticLanguageController::class, 'export'])->name('lexicon_codex.export');
Route::post('/lexicon-codex/import', [SemanticLanguageController::class, 'import'])->name('lexicon_codex.import');
Route::get('/lexicon-codex/template/blank', [SemanticLanguageController::class, 'exportBlank'])->name('lexicon_codex.template.blank');