<?php

use App\Http\Controllers\CandidatePetitionSubmissionController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolioAuthoringController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated Member Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Dashboard & Leave of Absence (LOA) Management
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/dashboard/loa', [DashboardController::class, 'updateLoa'])->name('dashboard.loa');

    // Profile Management
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Story Engine: Folio Authoring & Co-Writing Locks
    Route::get('/folios/create', [FolioAuthoringController::class, 'createFolio'])->name('folios.create');
    Route::get('/folios/{folio}/edit', [FolioAuthoringController::class, 'editFolio'])->name('folios.edit');
    Route::post('/folios', [FolioAuthoringController::class, 'storeFolio'])->name('folios.store');
    Route::post('/folios/autosave', [FolioAuthoringController::class, 'autoSave'])->name('folios.autosave.new');
    Route::post('/folios/{folio}/autosave', [FolioAuthoringController::class, 'autoSave'])->name('folios.autosave');
    Route::post('/folios/{folio}/lock', [FolioAuthoringController::class, 'acquireLock'])->name('folios.lock');
    Route::post('/folios/{folio}/ping', [FolioAuthoringController::class, 'pingLockWriter'])->name('folios.ping');

    // Correspondence Engine (Private Messaging)
    Route::get('/correspondence/{threadId?}', [CorrespondenceController::class, 'index'])->name('correspondence.index');
    Route::post('/correspondence/new', [CorrespondenceController::class, 'store'])->name('correspondence.store');
    Route::post('/correspondence/{thread}/reply', [CorrespondenceController::class, 'reply'])->name('correspondence.reply');

    // Persona Portrait & Gallery Management
    Route::post('/bio/{persona}/portrait', [PortalController::class, 'uploadPersonaPortrait'])->name('bio.portrait.upload');
    Route::post('/bio/{persona}/portrait/primary', [PortalController::class, 'setPrimaryPersonaPortrait'])->name('bio.portrait.primary');
    Route::delete('/bio/{persona}/portrait', [PortalController::class, 'deletePersonaPortrait'])->name('bio.portrait.delete');

    // Explicit Candidate Petition Submission
    Route::post('/form/submit/{identifier}', [CandidatePetitionSubmissionController::class, 'submit'])->name('petition.submit.id');
});

/*
|--------------------------------------------------------------------------
| Core Framework & Modular Includes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';

/*
|--------------------------------------------------------------------------
| Public Portal & Catch-All Routes
|--------------------------------------------------------------------------
*/

// Dynamic Candidate Petition Forms
Route::get('/form/{type}', [CandidatePetitionSubmissionController::class, 'show'])->name('petition.show');
Route::post('/form/{type}', [CandidatePetitionSubmissionController::class, 'submit'])->name('petition.submit');

// Public Persona Biographies
Route::get('/bio/{persona}', [PortalController::class, 'showBio'])->name('bio.show');

// The Book Builder Catch-All (Must remain absolute last)
Route::get('/{bookUrl?}', [PortalController::class, 'showBook'])
    ->where('bookUrl', '.*')
    ->name('book.show');