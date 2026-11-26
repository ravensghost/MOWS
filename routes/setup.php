<?php

use App\Http\Controllers\SetupWizardController;
use Illuminate\Support\Facades\Route;

Route::middleware('ensure.installed')
    ->prefix('setup')
    ->name('setup.')
    ->group(function () {
        Route::get('/', [SetupWizardController::class, 'index'])->name('index');
        Route::post('/database', [SetupWizardController::class, 'configureDatabase'])->name('database');
        Route::post('/complete', [SetupWizardController::class, 'complete'])->name('complete');
    });