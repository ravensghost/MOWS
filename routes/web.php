<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WriteController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    logger($query->sql);
});

Route::middleware('auth')->group(function () {
    // User Dashboard (Invokable) & LOA Toggle
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/dashboard/loa', [DashboardController::class, 'updateLoa'])->name('dashboard.loa');

    // Profile Management
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Story Engine: Writing Chapters
    Route::get('/chapters/create', [WriteController::class, 'createChapter'])->name('chapters.create');
    Route::get('/chapters/{chapter}/edit', [WriteController::class, 'editChapter'])->name('chapters.edit');
    Route::post('/chapters', [WriteController::class, 'storeChapter'])->name('chapters.store');
    Route::post('/chapters/autosave', [WriteController::class, 'autoSave']);
    Route::post('/chapters/{chapter}/autosave', [WriteController::class, 'autoSave']);
    Route::post('/chapters/{chapter}/lock', [WriteController::class, 'acquireLock']);

    // Private Messaging Engine
    Route::get('/messenger/{threadId?}', [MessengerController::class, 'index'])->name('messenger.index');
    Route::post('/messenger/new', [MessengerController::class, 'store'])->name('messenger.store');
    Route::post('/messenger/{thread}/reply', [MessengerController::class, 'reply'])->name('messenger.reply');

    // Character Avatar & Portrait Gallery Management (AJAX via Bio Page)
    Route::post('/bio/{character}/avatar', [PublicController::class, 'uploadCharacterAvatar'])->name('bio.avatar.upload');
    Route::post('/bio/{character}/avatar/primary', [PublicController::class, 'setPrimaryCharacterAvatar'])->name('bio.avatar.primary');
    Route::delete('/bio/{character}/avatar', [PublicController::class, 'deleteCharacterAvatar'])->name('bio.avatar.delete');

    // Explicit Form Submission
    Route::post('/form/submit/{identifier}', [PublicFormController::class, 'submit']);
});

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';

// Dynamic Secondary Forms
Route::get('/form/{type}', [PublicFormController::class, 'show']);
Route::post('/form/{type}', [PublicFormController::class, 'submit']);

Route::get('/bio/{character}', [PublicController::class, 'showBio'])->name('bio.show');

// The Page Builder Catch-All (Must remain absolute last)
Route::get('/{slug?}', [PublicController::class, 'showPage'])->where('slug', '.*');
