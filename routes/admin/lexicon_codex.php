<?php

use App\Http\Controllers\Admin\LexiconCodexController;
use App\Http\Controllers\Admin\SemanticLanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/lexicon-codex', [LexiconCodexController::class, 'index'])->name('admin.lexicon_codex.index');
Route::post('/lexicon-codex', [LexiconCodexController::class, 'update'])->name('admin.lexicon_codex.update');
Route::delete('/lexicon-codex/term/{id}', [LexiconCodexController::class, 'destroyTerm'])->name('admin.lexicon_codex.term.destroy');
Route::delete('/lexicon-codex/group/{groupName}', [LexiconCodexController::class, 'destroyGroup'])->name('admin.lexicon_codex.group.destroy');
Route::delete('/lexicon-codex/group/{master}/subgroup/{subgroup}', [LexiconCodexController::class, 'destroySubgroup'])->name('admin.lexicon_codex.subgroup.destroy');

Route::post('/lexicon-codex/language', [SemanticLanguageController::class, 'store'])->name('admin.lexicon_codex.language.store');
Route::put('/lexicon-codex/language', [SemanticLanguageController::class, 'update'])->name('admin.lexicon_codex.language.update');
Route::delete('/lexicon-codex/language/{code}', [SemanticLanguageController::class, 'destroy'])->name('admin.lexicon_codex.language.destroy');
Route::delete('/lexicon-codex/language/{code}/clear', [SemanticLanguageController::class, 'clear'])->name('admin.lexicon_codex.language.clear');
Route::get('/lexicon-codex/export/{locale?}', [SemanticLanguageController::class, 'export'])->name('admin.lexicon_codex.export');
Route::post('/lexicon-codex/import', [SemanticLanguageController::class, 'import'])->name('admin.lexicon_codex.import');
Route::get('/lexicon-codex/template/blank', [SemanticLanguageController::class, 'exportBlank'])->name('admin.lexicon_codex.template.blank');