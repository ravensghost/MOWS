<?php

use App\Http\Controllers\Admin\LexiconCodexController;
use App\Http\Controllers\Admin\SemanticLanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/lexicon', [LexiconCodexController::class, 'index']);
Route::post('/lexicon', [LexiconCodexController::class, 'update']);
Route::delete('/lexicon/term/{id}', [LexiconCodexController::class, 'destroyTerm']);
Route::delete('/lexicon/group/{groupName}', [LexiconCodexController::class, 'destroyGroup']);
Route::delete('/lexicon/group/{master}/subgroup/{subgroup}', [LexiconCodexController::class, 'destroySubgroup']);

Route::post('/lexicon/language', [SemanticLanguageController::class, 'store']);
Route::put('/lexicon/language', [SemanticLanguageController::class, 'update']);
Route::delete('/lexicon/language/{code}', [SemanticLanguageController::class, 'destroy']);
Route::delete('/lexicon/language/{code}/clear', [SemanticLanguageController::class, 'clear']);
Route::get('/lexicon/export/{locale?}', [SemanticLanguageController::class, 'export']);
Route::post('/lexicon/import', [SemanticLanguageController::class, 'import']);
Route::get('/lexicon/template/blank', [SemanticLanguageController::class, 'exportBlank']);
