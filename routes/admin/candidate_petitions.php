<?php

use App\Http\Controllers\Admin\CandidatePetitionController;
use App\Http\Controllers\Admin\CouncilEvaluationController;
use App\Http\Controllers\Admin\PersonaAssignmentController;
use Illuminate\Support\Facades\Route;

Route::get('/applications', [CandidatePetitionController::class, 'index'])->name('applications.index');
Route::post('/applications/{id}/approve', [CandidatePetitionController::class, 'approve'])->name('applications.approve');
Route::post('/applications/{id}/reject', [CandidatePetitionController::class, 'reject'])->name('applications.reject');

Route::post('/applications/{id}/vote', [CouncilEvaluationController::class, 'store'])->name('applications.vote');

Route::post('/characters/{id}/reassign', PersonaAssignmentController::class)->name('characters.reassign');
