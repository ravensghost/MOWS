<?php

use App\Http\Controllers\Admin\CandidatePetitionController;
use App\Http\Controllers\Admin\CouncilEvaluationController;
use App\Http\Controllers\Admin\PersonaAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Candidate Petitions & Roster Assignment Routes
|--------------------------------------------------------------------------
| Canonical URI Prefix: /admin/candidate-petitions
| Canonical Name Prefix: admin.candidate_petitions.
*/

Route::get('/candidate-petitions', [CandidatePetitionController::class, 'index'])
    ->name('candidate_petitions.index');

Route::post('/candidate-petitions', [CandidatePetitionController::class, 'store'])
    ->name('candidate_petitions.store');

Route::put('/candidate-petitions/{id}', [CandidatePetitionController::class, 'update'])
    ->name('candidate_petitions.update');

Route::delete('/candidate-petitions/{id}', [CandidatePetitionController::class, 'destroy'])
    ->name('candidate_petitions.destroy');

Route::post('/candidate-petitions/{id}/approve', [CandidatePetitionController::class, 'approve'])
    ->name('candidate_petitions.approve');

Route::post('/candidate-petitions/{id}/reject', [CandidatePetitionController::class, 'reject'])
    ->name('candidate_petitions.reject');

Route::post('/candidate-petitions/{id}/vote', [CouncilEvaluationController::class, 'store'])
    ->name('candidate_petitions.vote');

Route::post('/candidate-petitions/{id}/reassign', [CandidatePetitionController::class, 'reassign'])
    ->name('candidate_petitions.reassign');

Route::post('/candidate-petitions/personae/{id}/reassign', PersonaAssignmentController::class)
    ->name('candidate_petitions.personae.reassign');